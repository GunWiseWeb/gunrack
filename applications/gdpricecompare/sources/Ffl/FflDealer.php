<?php
/**
 * @brief       GD Price Comparison — FFL dealer record and locator helpers
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\Ffl;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _FflDealer extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable    = 'gd_ffl_dealers';
	public static string  $databasePrefix   = '';
	public static string  $databaseColumnId = 'id';

	/**
	 * Total FFL records loaded — used on admin dashboard.
	 */
	public static function totalCount(): int
	{
		try
		{
			return (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_ffl_dealers' )->first();
		}
		catch ( \Exception )
		{
			return 0;
		}
	}

	public static function lastRefreshedAt(): ?string
	{
		try
		{
			$v = \IPS\Db::i()->select( 'MAX(last_updated)', 'gd_ffl_dealers' )->first();
			return $v ? (string) $v : null;
		}
		catch ( \Exception )
		{
			return null;
		}
	}

	/**
	 * Find FFL dealers near a ZIP code centroid. Uses a bounding-box pre-filter
	 * against the lat/lng indexes then computes precise great-circle distance
	 * in PHP. Caller is responsible for providing centroid lat/lng.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function nearby( float $lat, float $lng, int $radiusMiles = 25, int $limit = 10 ): array
	{
		$latDelta = $radiusMiles / 69.0;
		$lngDelta = $radiusMiles / max( 1.0, 69.0 * cos( deg2rad( $lat ) ) );

		$rows = [];
		foreach ( \IPS\Db::i()->select( '*', 'gd_ffl_dealers', [
			'active=? AND lat BETWEEN ? AND ? AND lng BETWEEN ? AND ?',
			1,
			$lat - $latDelta, $lat + $latDelta,
			$lng - $lngDelta, $lng + $lngDelta,
		]) as $r )
		{
			if ( $r['lat'] === null || $r['lng'] === null )
			{
				continue;
			}
			$d = self::haversineMiles( $lat, $lng, (float) $r['lat'], (float) $r['lng'] );
			if ( $d <= $radiusMiles )
			{
				$r['distance'] = $d;
				$rows[] = $r;
			}
		}

		usort( $rows, function ( array $a, array $b ): int
		{
			return $a['distance'] <=> $b['distance'];
		});

		return array_slice( $rows, 0, $limit );
	}

	private static function haversineMiles( float $lat1, float $lng1, float $lat2, float $lng2 ): float
	{
		$earthRadiusMiles = 3958.8;
		$dLat = deg2rad( $lat2 - $lat1 );
		$dLng = deg2rad( $lng2 - $lng1 );
		$a = sin( $dLat / 2 ) ** 2
			+ cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dLng / 2 ) ** 2;
		$c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
		return $earthRadiusMiles * $c;
	}
}

class FflDealer extends _FflDealer {}
