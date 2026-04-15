<?php
/**
 * @brief       GD Price Comparison — Product lookup (reads from gd_catalog)
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Reads against the master catalog table owned by Plugin 1. Plugin 3 does not
 * write to gd_catalog. We wrap reads here so the rest of Plugin 3 does not
 * embed table names directly.
 */

namespace IPS\gdpricecompare\Product;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Product
{
	public static function loadByUpc( string $upc ): ?array
	{
		try
		{
			$row = \IPS\Db::i()->select( '*', 'gd_catalog', [ 'upc=?', $upc ] )->first();
			return $row;
		}
		catch ( \Exception )
		{
			return null;
		}
	}

	public static function loadBySlug( string $slug ): ?array
	{
		try
		{
			$row = \IPS\Db::i()->select( '*', 'gd_catalog', [ 'slug=?', $slug ] )->first();
			return $row;
		}
		catch ( \Exception )
		{
			return null;
		}
	}

	/**
	 * Total catalog count — for admin dashboard.
	 */
	public static function totalCount(): int
	{
		try
		{
			return (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_catalog' )->first();
		}
		catch ( \Exception )
		{
			return 0;
		}
	}

	/**
	 * Active listings count across all products — admin dashboard.
	 */
	public static function activeListingsCount(): int
	{
		try
		{
			return (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_dealer_listings', [ 'listing_status=?', 'active' ]
			)->first();
		}
		catch ( \Exception )
		{
			return 0;
		}
	}
}
