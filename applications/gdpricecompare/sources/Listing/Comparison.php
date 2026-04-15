<?php
/**
 * @brief       GD Price Comparison — Comparison table builder
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Given a UPC, assembles the ranked price-comparison table as a flat array of
 * rows ready for template rendering. Applies the ordering rules from
 * spec Section 4.1.2:
 *   1. Sort by total cost (dealer_price + shipping_cost) ascending.
 *   2. When totals are equal, Pro/Enterprise listings rank ahead of Basic.
 *   3. Out-of-stock listings are separated into their own bucket.
 *   4. The lowest total cost row is flagged is_best_price = true.
 *   5. Null shipping_cost rows sort after rows with known totals.
 */

namespace IPS\gdpricecompare\Listing;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Comparison
{
	const TIER_RANK = [
		'enterprise' => 1,
		'pro'        => 2,
		'founding'   => 3,
		'basic'      => 4,
	];

	/**
	 * Build the comparison table for a UPC.
	 *
	 * @return array{in_stock: array<int, array<string,mixed>>, out_of_stock: array<int, array<string,mixed>>, best_total: ?float}
	 */
	public static function build( string $upc, ?string $userState = null, bool $cprIncludeShipping = false ): array
	{
		$rows = [];

		try
		{
			$select = \IPS\Db::i()->select(
				'l.*, f.store_name, f.tier, f.slug AS dealer_slug',
				[ 'gd_dealer_listings', 'l' ],
				[ 'l.upc=? AND l.listing_status IN ( ?, ? )', $upc, 'active', 'out_of_stock' ]
			)->join(
				[ 'gd_dealer_feed_config', 'f' ],
				'f.dealer_id = l.dealer_id'
			);

			foreach ( $select as $r )
			{
				$rows[] = $r;
			}
		}
		catch ( \Exception )
		{
			return [ 'in_stock' => [], 'out_of_stock' => [], 'best_total' => null ];
		}

		$inStock   = [];
		$outStock  = [];

		foreach ( $rows as $r )
		{
			$price     = (float) $r['dealer_price'];
			$ship      = $r['shipping_cost'] === null ? null : (float) $r['shipping_cost'];
			$freeShip  = (int) ( $r['free_shipping'] ?? 0 ) === 1;
			$hasTotal  = $freeShip || $ship !== null;
			$total     = $freeShip ? $price : ( $ship !== null ? $price + $ship : null );

			$row = [
				'listing_id'          => (int) $r['id'],
				'dealer_id'           => (int) $r['dealer_id'],
				'dealer_name'         => (string) ( $r['store_name'] ?? 'Dealer' ),
				'dealer_slug'         => (string) ( $r['dealer_slug'] ?? '' ),
				'tier'                => (string) ( $r['tier'] ?? 'basic' ),
				'dealer_price'        => $price,
				'shipping_cost'       => $ship,
				'free_shipping'       => $freeShip,
				'total_cost'          => $total,
				'total_unknown'       => !$hasTotal,
				'in_stock'            => (int) ( $r['in_stock'] ?? 0 ) === 1,
				'listing_status'      => (string) ( $r['listing_status'] ?? 'active' ),
				'rounds_per_box'      => $r['rounds_per_box'] !== null ? (int) $r['rounds_per_box'] : null,
				'ships_to_restriction'=> $r['ships_to_restriction'] ?? null,
				'is_restricted_state' => false,
				'cpr'                 => null,
				'is_best_price'       => false,
			];

			if ( $userState !== null && $row['ships_to_restriction'] !== null )
			{
				$restricted = array_map( 'trim', explode( ',', (string) $row['ships_to_restriction'] ) );
				$row['is_restricted_state'] = in_array( strtoupper( $userState ), array_map( 'strtoupper', $restricted ), true );
			}

			if ( $row['rounds_per_box'] !== null && $row['rounds_per_box'] > 0 )
			{
				$divisor = $row['rounds_per_box'];
				$basis   = $cprIncludeShipping ? ( $total ?? $price ) : $price;
				$row['cpr'] = round( $basis / $divisor, 4 );
			}

			if ( $row['in_stock'] && $row['listing_status'] === 'active' )
			{
				$inStock[] = $row;
			}
			else
			{
				$outStock[] = $row;
			}
		}

		self::sortListings( $inStock );
		self::sortListings( $outStock );

		$bestTotal = null;
		foreach ( $inStock as $i => $row )
		{
			if ( $row['total_cost'] !== null )
			{
				$bestTotal = $row['total_cost'];
				$inStock[$i]['is_best_price'] = true;
				break;
			}
		}

		return [
			'in_stock'     => $inStock,
			'out_of_stock' => $outStock,
			'best_total'   => $bestTotal,
		];
	}

	/**
	 * Sort in place by: total_cost ASC (unknown last), then tier rank ASC,
	 * then dealer_price ASC.
	 *
	 * @param array<int, array<string, mixed>> $rows
	 */
	private static function sortListings( array &$rows ): void
	{
		usort( $rows, function ( array $a, array $b ): int
		{
			$aUnknown = $a['total_unknown'] ? 1 : 0;
			$bUnknown = $b['total_unknown'] ? 1 : 0;
			if ( $aUnknown !== $bUnknown )
			{
				return $aUnknown <=> $bUnknown;
			}

			if ( !$a['total_unknown'] && !$b['total_unknown'] )
			{
				$cmp = $a['total_cost'] <=> $b['total_cost'];
				if ( $cmp !== 0 )
				{
					return $cmp;
				}
			}

			$aRank = Comparison::TIER_RANK[ $a['tier'] ] ?? 99;
			$bRank = Comparison::TIER_RANK[ $b['tier'] ] ?? 99;
			if ( $aRank !== $bRank )
			{
				return $aRank <=> $bRank;
			}

			return $a['dealer_price'] <=> $b['dealer_price'];
		});
	}
}
