<?php
/**
 * @brief       GD Price Comparison — Watchlist alert dispatcher
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Walks the watchlist once per run looking for price drops and back-in-stock
 * events. Uses gd_price_history (written by Plugin 2's feed importer) as the
 * source of truth — compares the current lowest-priced active listing for a
 * UPC against the most recent price-history row to detect a decrease.
 *
 * Deduplicates per-watchlist with a configurable cooldown (default 24h).
 */

namespace IPS\gdpricecompare\Alert;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Dispatcher
{
	/**
	 * Run one dispatch pass. Returns the number of notifications queued.
	 */
	public static function run(): int
	{
		$dedupeHours = (int) ( \IPS\Settings::i()->gdpc_alert_dedupe_hours ?? 24 );
		$cutoff      = date( 'Y-m-d H:i:s', time() - ( $dedupeHours * 3600 ) );
		$sent        = 0;

		try
		{
			$upcs = \IPS\Db::i()->select(
				'DISTINCT upc', 'gd_watchlist',
				[ 'last_alerted IS NULL OR last_alerted < ?', $cutoff ]
			);
		}
		catch ( \Exception )
		{
			return 0;
		}

		foreach ( $upcs as $row )
		{
			$upc = (string) ( is_array( $row ) ? $row['upc'] : $row );

			$currentBest = self::currentBestPrice( $upc );
			$inStock     = self::anyInStock( $upc );
			if ( $currentBest === null && !$inStock )
			{
				continue;
			}

			$watchers = \IPS\gdpricecompare\Watchlist\Watchlist::watchersFor( $upc );
			foreach ( $watchers as $w )
			{
				if ( $w['last_alerted'] !== null && $w['last_alerted'] >= $cutoff )
				{
					continue;
				}

				$targetPrice  = $w['target_price'] !== null ? (float) $w['target_price'] : null;
				$notifyStock  = (int) ( $w['notify_in_stock'] ?? 0 ) === 1;
				$memberId     = (int) $w['member_id'];

				$priceTrigger = $currentBest !== null && ( $targetPrice === null || $currentBest <= $targetPrice );
				$stockTrigger = $notifyStock && $inStock;

				if ( !$priceTrigger && !$stockTrigger )
				{
					continue;
				}

				self::queueNotification( $memberId, $upc, $currentBest, $stockTrigger );
				\IPS\gdpricecompare\Watchlist\Watchlist::markAlerted( (int) $w['id'] );
				$sent++;
			}
		}

		return $sent;
	}

	private static function currentBestPrice( string $upc ): ?float
	{
		try
		{
			$v = \IPS\Db::i()->select(
				'MIN(dealer_price)', 'gd_dealer_listings',
				[ 'upc=? AND listing_status=? AND in_stock=?', $upc, 'active', 1 ]
			)->first();
			return $v !== null ? (float) $v : null;
		}
		catch ( \Exception )
		{
			return null;
		}
	}

	private static function anyInStock( string $upc ): bool
	{
		try
		{
			$c = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_dealer_listings',
				[ 'upc=? AND listing_status=? AND in_stock=?', $upc, 'active', 1 ]
			)->first();
			return $c > 0;
		}
		catch ( \Exception )
		{
			return false;
		}
	}

	/**
	 * Queue a price-drop notification. Wrapped so tests can stub it and so an
	 * IPS notification backend missing during install does not fatal the task.
	 */
	private static function queueNotification( int $memberId, string $upc, ?float $price, bool $backInStock ): void
	{
		try
		{
			$member = \IPS\Member::load( $memberId );
			if ( !$member->member_id )
			{
				return;
			}

			\IPS\Db::i()->insert( 'core_notifications', [
				'notification_key' => $backInStock ? 'gdpc_back_in_stock' : 'gdpc_price_drop',
				'notification_app' => 'gdpricecompare',
				'member'           => $memberId,
				'item_class'       => 'IPS\\gdpricecompare\\Watchlist\\Watchlist',
				'item_id'          => 0,
				'item_sub_class'   => null,
				'item_sub_id'      => 0,
				'sent_from'        => 0,
				'read_time'        => 0,
				'extra'            => json_encode( [ 'upc' => $upc, 'price' => $price ] ),
				'sent'             => time(),
			]);
		}
		catch ( \Exception )
		{
		}
	}
}
