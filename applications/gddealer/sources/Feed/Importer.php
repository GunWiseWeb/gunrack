<?php
/**
 * @brief       GD Dealer Manager — Feed Importer
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Orchestrates a full dealer feed ingestion (Section 3.8 of the spec).
 * Fetch → parse → field-map → validate → upsert listings → write
 * price history on change → mark stale listings out of stock → log run.
 */

namespace IPS\gddealer\Feed;

use IPS\gddealer\Dealer\Dealer;
use IPS\gddealer\Listing\Listing;
use IPS\gddealer\Listing\PriceHistory;
use IPS\gddealer\Unmatched\UnmatchedUpc;
use IPS\gddealer\Log\ImportLog;
use IPS\gddealer\Feed\Parser\XmlParser;
use IPS\gddealer\Feed\Parser\JsonParser;
use IPS\gddealer\Feed\Parser\CsvParser;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Importer
{
	/**
	 * Run a full feed import for a dealer and return the completed log.
	 */
	public static function run( Dealer $dealer ): ImportLog
	{
		$log = ImportLog::start( (int) $dealer->dealer_id, (string) $dealer->subscription_tier );

		try
		{
			if ( empty( $dealer->feed_url ) )
			{
				throw new \RuntimeException( 'Feed URL is not configured for this dealer.' );
			}

			$body    = self::fetch( $dealer );
			$records = self::parseBody( $body, (string) $dealer->feed_format );
			$map     = self::decodeMap( (string) ( $dealer->field_mapping ?? '' ) );

			$stats = [
				'records_total'     => 0,
				'records_created'   => 0,
				'records_updated'   => 0,
				'records_unchanged' => 0,
				'records_unmatched' => 0,
				'price_drops'       => 0,
				'price_increases'   => 0,
				'alerts_triggered'  => 0,
			];

			$runStart = date( 'Y-m-d H:i:s' );
			$seenIds  = [];

			foreach ( $records as $raw )
			{
				$stats['records_total']++;

				$canonical = FieldMapper::apply( $raw, $map );

				if ( empty( $canonical['upc'] ) || !isset( $canonical['dealer_price'] ) || (float) $canonical['dealer_price'] <= 0 )
				{
					continue;
				}

				$upc = (string) $canonical['upc'];

				if ( !self::upcExistsInCatalog( $upc ) )
				{
					UnmatchedUpc::record( $upc, (int) $dealer->dealer_id );
					$stats['records_unmatched']++;
					continue;
				}

				$listing = Listing::loadFor( (int) $dealer->dealer_id, $upc );
				if ( $listing === null )
				{
					$listing = self::createListing( $dealer, $canonical, $runStart );
					PriceHistory::record( (int) $dealer->dealer_id, $upc, (float) $canonical['dealer_price'], !empty( $canonical['in_stock'] ) );
					$stats['records_created']++;
				}
				else
				{
					$diff = self::applyChanges( $listing, $canonical, $runStart );
					if ( $diff['priceChanged'] || $diff['stockChanged'] )
					{
						PriceHistory::record( (int) $dealer->dealer_id, $upc, (float) $canonical['dealer_price'], !empty( $canonical['in_stock'] ) );
						$stats['records_updated']++;
						if ( $diff['priceDelta'] < 0 ) { $stats['price_drops']++; }
						if ( $diff['priceDelta'] > 0 ) { $stats['price_increases']++; }
					}
					else
					{
						$stats['records_unchanged']++;
					}
					$listing->save();
				}

				$seenIds[] = (int) $listing->id;
			}

			self::markStale( (int) $dealer->dealer_id, $runStart );

			$dealer->last_run           = $runStart;
			$dealer->last_record_count  = $stats['records_total'];
			$dealer->last_run_status    = 'completed';
			$dealer->save();

			UnmatchedUpc::sweepMatched();
			$log->complete( $stats );
			return $log;
		}
		catch ( \Throwable $e )
		{
			$dealer->last_run        = date( 'Y-m-d H:i:s' );
			$dealer->last_run_status = 'failed';
			$dealer->save();
			$log->fail( $e->getMessage() );
			return $log;
		}
	}

	/**
	 * Fetch the feed body with optional auth credentials.
	 */
	protected static function fetch( Dealer $dealer ): string
	{
		$url  = (string) $dealer->feed_url;
		$auth = $dealer->getCredentials();

		$http = \IPS\Http\Url::external( $url )->request( 90 );

		if ( $auth )
		{
			$decoded = json_decode( $auth, true );
			if ( is_array( $decoded ) && isset( $decoded['username'], $decoded['password'] ) && $dealer->auth_type === 'basic' )
			{
				$http = $http->login( (string) $decoded['username'], (string) $decoded['password'] );
			}
			elseif ( is_array( $decoded ) && isset( $decoded['api_key'] ) && $dealer->auth_type === 'apikey' )
			{
				$http = $http->setHeaders( [ 'Authorization' => 'Bearer ' . $decoded['api_key'] ] );
			}
		}

		$response = $http->get();
		if ( (int) $response->httpResponseCode >= 400 )
		{
			throw new \RuntimeException( 'Feed fetch failed: HTTP ' . $response->httpResponseCode );
		}
		return (string) $response->content;
	}

	protected static function parseBody( string $body, string $format ): array
	{
		return match ( strtolower( $format ) ) {
			'xml'  => XmlParser::parse( $body ),
			'json' => JsonParser::parse( $body ),
			'csv'  => CsvParser::parse( $body ),
			default => throw new \RuntimeException( "Unsupported feed format: {$format}" ),
		};
	}

	/**
	 * @return array<string, string>
	 */
	protected static function decodeMap( string $json ): array
	{
		if ( $json === '' )
		{
			return [];
		}
		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	protected static function upcExistsInCatalog( string $upc ): bool
	{
		try
		{
			$count = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_catalog', [ 'upc=?', $upc ] )->first();
			return $count > 0;
		}
		catch ( \Exception )
		{
			return false;
		}
	}

	protected static function createListing( Dealer $dealer, array $canonical, string $runStart ): Listing
	{
		$listing = new Listing;
		$listing->dealer_id          = (int) $dealer->dealer_id;
		$listing->upc                = (string) $canonical['upc'];
		$listing->dealer_sku         = $canonical['dealer_sku'] ?? null;
		$listing->dealer_price       = (float) $canonical['dealer_price'];
		$listing->shipping_cost      = $canonical['shipping_cost'] ?? null;
		$listing->free_shipping      = !empty( $canonical['free_shipping'] ) ? 1 : 0;
		$listing->in_stock           = !empty( $canonical['in_stock'] ) ? 1 : 0;
		$listing->stock_qty          = $canonical['stock_qty'] ?? null;
		$listing->condition          = $canonical['condition'] ?? 'new';
		$listing->listing_url        = $canonical['listing_url'] ?? null;
		$listing->subscription_tier  = (string) $dealer->subscription_tier;
		$listing->listing_status     = $listing->in_stock ? Listing::STATUS_ACTIVE : Listing::STATUS_OUT_OF_STOCK;
		$listing->first_seen_in_feed = $runStart;
		$listing->last_seen_in_feed  = $runStart;
		$listing->last_price_change  = $runStart;
		$listing->save();
		return $listing;
	}

	/**
	 * @return array{priceChanged:bool, stockChanged:bool, priceDelta:float}
	 */
	protected static function applyChanges( Listing $listing, array $canonical, string $runStart ): array
	{
		$newPrice = (float) $canonical['dealer_price'];
		$newStock = !empty( $canonical['in_stock'] ) ? 1 : 0;

		$priceChanged = abs( (float) $listing->dealer_price - $newPrice ) > 0.001;
		$stockChanged = ( (int) $listing->in_stock ) !== $newStock;
		$delta        = $newPrice - (float) $listing->dealer_price;

		if ( $priceChanged )
		{
			$listing->dealer_price      = $newPrice;
			$listing->last_price_change = $runStart;
		}
		$listing->in_stock          = $newStock;
		$listing->stock_qty         = $canonical['stock_qty'] ?? null;
		$listing->shipping_cost     = $canonical['shipping_cost'] ?? null;
		$listing->free_shipping     = !empty( $canonical['free_shipping'] ) ? 1 : 0;
		$listing->condition         = $canonical['condition'] ?? $listing->condition;
		$listing->listing_url       = $canonical['listing_url'] ?? $listing->listing_url;
		$listing->subscription_tier = (string) $listing->subscription_tier;
		$listing->last_seen_in_feed = $runStart;

		if ( $newStock === 0 )
		{
			$listing->listing_status = Listing::STATUS_OUT_OF_STOCK;
		}
		elseif ( $listing->listing_status === Listing::STATUS_OUT_OF_STOCK )
		{
			$listing->listing_status = Listing::STATUS_ACTIVE;
		}

		return [ 'priceChanged' => $priceChanged, 'stockChanged' => $stockChanged, 'priceDelta' => $delta ];
	}

	/**
	 * Any listing for this dealer whose last_seen_in_feed < $runStart is now
	 * stale and gets marked out of stock.
	 */
	protected static function markStale( int $dealerId, string $runStart ): void
	{
		\IPS\Db::i()->update(
			'gd_dealer_listings',
			[ 'listing_status' => Listing::STATUS_OUT_OF_STOCK, 'in_stock' => 0 ],
			[ 'dealer_id=? AND ( last_seen_in_feed IS NULL OR last_seen_in_feed < ? ) AND listing_status <> ?', $dealerId, $runStart, Listing::STATUS_SUSPENDED ]
		);
	}
}
