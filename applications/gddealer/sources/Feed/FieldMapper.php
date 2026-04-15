<?php
/**
 * @brief       GD Dealer Manager — Field Mapping applier
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Given a raw parsed record and a JSON field map (dealer_field => canonical
 * field), produce a normalized canonical record ready for upsert into
 * gd_dealer_listings.
 */

namespace IPS\gddealer\Feed;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class FieldMapper
{
	/**
	 * Canonical fields that can be populated from a dealer feed.
	 */
	public static array $canonicalFields = [
		'upc', 'dealer_sku', 'dealer_price', 'shipping_cost', 'free_shipping',
		'in_stock', 'stock_qty', 'condition', 'listing_url',
	];

	/**
	 * @param array<string, mixed>  $record     Raw record from the feed parser
	 * @param array<string, string> $fieldMap   dealer_field => canonical_field
	 * @return array<string, mixed>  Canonical record
	 */
	public static function apply( array $record, array $fieldMap ): array
	{
		$out = [];
		foreach ( $fieldMap as $dealerField => $canonical )
		{
			if ( !in_array( $canonical, self::$canonicalFields, true ) )
			{
				continue;
			}
			if ( !array_key_exists( $dealerField, $record ) )
			{
				continue;
			}
			$out[ $canonical ] = $record[ $dealerField ];
		}
		return self::normalize( $out );
	}

	/**
	 * Normalize types on canonical record.
	 *
	 * @param array<string, mixed> $r
	 * @return array<string, mixed>
	 */
	protected static function normalize( array $r ): array
	{
		if ( isset( $r['upc'] ) )
		{
			$r['upc'] = preg_replace( '/[^0-9A-Za-z]/', '', (string) $r['upc'] );
		}
		if ( isset( $r['dealer_price'] ) )
		{
			$r['dealer_price'] = (float) preg_replace( '/[^0-9.]/', '', (string) $r['dealer_price'] );
		}
		if ( isset( $r['shipping_cost'] ) && $r['shipping_cost'] !== null && $r['shipping_cost'] !== '' )
		{
			$r['shipping_cost'] = (float) preg_replace( '/[^0-9.]/', '', (string) $r['shipping_cost'] );
		}
		if ( isset( $r['free_shipping'] ) )
		{
			$r['free_shipping'] = self::truthy( $r['free_shipping'] ) ? 1 : 0;
		}
		if ( isset( $r['in_stock'] ) )
		{
			$r['in_stock'] = self::truthy( $r['in_stock'] ) ? 1 : 0;
		}
		if ( isset( $r['stock_qty'] ) && $r['stock_qty'] !== null && $r['stock_qty'] !== '' )
		{
			$r['stock_qty'] = (int) $r['stock_qty'];
		}
		if ( isset( $r['condition'] ) )
		{
			$r['condition'] = strtolower( trim( (string) $r['condition'] ) );
			if ( !in_array( $r['condition'], [ 'new', 'used', 'refurbished' ], true ) )
			{
				$r['condition'] = 'new';
			}
		}
		return $r;
	}

	protected static function truthy( $v ): bool
	{
		if ( is_bool( $v ) ) return $v;
		if ( is_numeric( $v ) ) return ( (int) $v ) > 0;
		$s = strtolower( trim( (string) $v ) );
		return in_array( $s, [ '1', 'yes', 'y', 'true', 't', 'in stock', 'instock', 'available' ], true );
	}
}
