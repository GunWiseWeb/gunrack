<?php
/**
 * @brief       GD Rebates — Rebate ActiveRecord + helpers
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * One row per rebate — scraped, community-submitted, or merged.
 * Lifecycle:
 *   pending   → active   (admin approval, or auto-approve for known-mfr scrapes)
 *   active    → expired  (daily task when end_date passes)
 *   expired   → archived (daily task N days after expiry, default 30)
 * Dedup:
 *   dedup_hash = sha256( manufacturer + '|' + title + '|' + end_date + '|' + rebate_amount )
 *   (Section 8.2.3 / 8.2.4)
 */

namespace IPS\gdrebates\Rebate;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Rebate extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable    = 'gd_rebates';
	public static string  $databaseColumnId = 'id';
	public static string  $databasePrefix   = '';

	/**
	 * Allowed product_type enum values (Section 8.3).
	 *
	 * @return array<int,string>
	 */
	public static function productTypes(): array
	{
		return [ 'firearm', 'ammo', 'optic', 'accessory', 'suppressor', 'any' ];
	}

	/**
	 * Allowed rebate_type enum values (Section 8.3).
	 *
	 * @return array<int,string>
	 */
	public static function rebateTypes(): array
	{
		return [ 'mail_in', 'instant', 'online', 'combo' ];
	}

	/**
	 * Compute the SHA256 dedup hash used by scraper + community merge.
	 * Passing the raw end_date and amount means updates to the actual
	 * offer (e.g. a price increase or a new end date) create a new
	 * hash — which is correct, they represent a different rebate.
	 */
	public static function dedupHash( string $manufacturer, string $title, ?string $endDate, ?float $amount ): string
	{
		$parts = [
			strtolower( trim( $manufacturer ) ),
			strtolower( trim( $title ) ),
			$endDate ?? '',
			$amount === null ? '' : number_format( $amount, 2, '.', '' ),
		];
		return hash( 'sha256', implode( '|', $parts ) );
	}

	/**
	 * URL-safe slug for a rebate title. Used in /rebates/{id}-{slug}.
	 */
	public static function slug( string $title ): string
	{
		$s = strtolower( $title );
		$s = preg_replace( '/[^a-z0-9]+/', '-', $s ) ?? '';
		$s = trim( $s, '-' );
		if ( $s === '' )
		{
			return 'rebate';
		}
		return substr( $s, 0, 80 );
	}

	/**
	 * Count rebates bucketed by status.
	 *
	 * @return array<string,int>
	 */
	public static function countByStatus(): array
	{
		$out = [ 'active' => 0, 'pending' => 0, 'expired' => 0, 'archived' => 0, 'rejected' => 0 ];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'status, COUNT(*) AS c', 'gd_rebates', null, null, null, 'status'
			) as $r )
			{
				$k = (string) ( $r['status'] ?? '' );
				if ( isset( $out[ $k ] ) )
				{
					$out[ $k ] = (int) $r['c'];
				}
			}
		}
		catch ( \Exception ) {}
		return $out;
	}

	/**
	 * Count of currently-active rebates expiring within N days (inclusive).
	 */
	public static function expiringWithinDays( int $days ): int
	{
		if ( $days < 0 ) { $days = 0; }
		$cutoff = date( 'Y-m-d', strtotime( '+' . $days . ' days' ) );
		try
		{
			return (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_rebates',
				[ 'status=? AND end_date<=? AND end_date>=?', 'active', $cutoff, date( 'Y-m-d' ) ]
			)->first();
		}
		catch ( \Exception )
		{
			return 0;
		}
	}

	/**
	 * Sum of rebate_amount across all active rebates — the 'total potential
	 * savings' number shown on the public hub hero and the ACP dashboard.
	 */
	public static function totalActiveSavings(): float
	{
		try
		{
			$v = \IPS\Db::i()->select(
				'COALESCE(SUM(rebate_amount),0)', 'gd_rebates', [ 'status=?', 'active' ]
			)->first();
			return (float) $v;
		}
		catch ( \Exception )
		{
			return 0.0;
		}
	}

	/**
	 * Look up a rebate by its dedup hash. Returns the full row or null.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function findByDedupHash( string $hash ): ?array
	{
		if ( $hash === '' )
		{
			return null;
		}
		try
		{
			$row = \IPS\Db::i()->select(
				'*', 'gd_rebates', [ 'dedup_hash=?', $hash ]
			)->first();
			return is_array( $row ) ? $row : null;
		}
		catch ( \UnderflowException )
		{
			return null;
		}
		catch ( \Exception )
		{
			return null;
		}
	}
}

class Rebate extends _Rebate {}
