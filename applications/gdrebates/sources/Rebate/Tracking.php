<?php
/**
 * @brief       GD Rebates — Tracking ActiveRecord
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * One row per member per rebate. uq_rebate_member UNIQUE KEY enforces
 * this — updating a status value replaces the existing row.
 * Feeds the 'community success rate' block on the rebate detail page
 * (Section 8.5).
 */

namespace IPS\gdrebates\Rebate;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Tracking extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable    = 'gd_rebate_tracking';
	public static string  $databaseColumnId = 'id';
	public static string  $databasePrefix   = '';

	/**
	 * Allowed status enum values.
	 *
	 * @return array<int,string>
	 */
	public static function statuses(): array
	{
		return [ 'saved', 'submitted', 'received', 'rejected', 'expired' ];
	}

	/**
	 * Return this member's tracking row for a rebate, or null if none.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function findForMember( int $rebateId, int $memberId ): ?array
	{
		if ( $rebateId <= 0 || $memberId <= 0 )
		{
			return null;
		}
		try
		{
			$row = \IPS\Db::i()->select(
				'*', 'gd_rebate_tracking', [ 'rebate_id=? AND member_id=?', $rebateId, $memberId ]
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

	/**
	 * Aggregate the community success metrics for a rebate.
	 * Returns an array with:
	 *   total       => total tracker rows for this rebate
	 *   received    => rows with status = 'received'
	 *   submitted   => rows with status = 'submitted'
	 *   rejected    => rows with status = 'rejected'
	 *   success_pct => percentage of (received / total)
	 *   avg_days    => mean days between submitted_date and received_date
	 *                  across rows that have both populated
	 *
	 * @return array{total:int, received:int, submitted:int, rejected:int, success_pct:float, avg_days:float}
	 */
	public static function successMetrics( int $rebateId ): array
	{
		$out = [
			'total'       => 0,
			'received'    => 0,
			'submitted'   => 0,
			'rejected'    => 0,
			'success_pct' => 0.0,
			'avg_days'    => 0.0,
		];
		if ( $rebateId <= 0 )
		{
			return $out;
		}
		try
		{
			foreach ( \IPS\Db::i()->select(
				'status, COUNT(*) AS c', 'gd_rebate_tracking', [ 'rebate_id=?', $rebateId ], null, null, 'status'
			) as $r )
			{
				$s = (string) ( $r['status'] ?? '' );
				$c = (int) ( $r['c'] ?? 0 );
				$out['total'] += $c;
				if ( $s === 'received' )  { $out['received']  = $c; }
				if ( $s === 'submitted' ) { $out['submitted'] = $c; }
				if ( $s === 'rejected' )  { $out['rejected']  = $c; }
			}

			if ( $out['total'] > 0 )
			{
				$out['success_pct'] = round( ( $out['received'] / $out['total'] ) * 100, 1 );
			}

			$avg = \IPS\Db::i()->select(
				'AVG(DATEDIFF(received_date, submitted_date))',
				'gd_rebate_tracking',
				[
					'rebate_id=? AND submitted_date IS NOT NULL AND received_date IS NOT NULL',
					$rebateId,
				]
			)->first();
			$out['avg_days'] = $avg === null ? 0.0 : round( (float) $avg, 1 );
		}
		catch ( \Exception ) {}

		return $out;
	}
}

class Tracking extends _Tracking {}
