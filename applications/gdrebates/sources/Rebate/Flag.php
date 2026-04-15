<?php
/**
 * @brief       GD Rebates — Flag ActiveRecord
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * One row per member per rebate flag. uq_rebate_member UNIQUE KEY stops
 * a single member from inflating flag_count with repeat flags on the
 * same rebate (Section 8.7). The flag_count column on gd_rebates is a
 * denormalised counter that should be recomputed from this table, not
 * mutated in isolation.
 */

namespace IPS\gdrebates\Rebate;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Flag extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable    = 'gd_rebate_flags';
	public static string  $databaseColumnId = 'id';
	public static string  $databasePrefix   = '';

	/**
	 * Allowed reason enum values.
	 *
	 * @return array<int,string>
	 */
	public static function reasons(): array
	{
		return [ 'expired', 'incorrect_amount', 'broken_link', 'duplicate', 'other' ];
	}

	/**
	 * Has this member already flagged this rebate?
	 */
	public static function alreadyFlagged( int $rebateId, int $memberId ): bool
	{
		if ( $rebateId <= 0 || $memberId <= 0 )
		{
			return false;
		}
		try
		{
			$c = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_rebate_flags', [ 'rebate_id=? AND member_id=?', $rebateId, $memberId ]
			)->first();
			return $c > 0;
		}
		catch ( \Exception )
		{
			return false;
		}
	}

	/**
	 * Recompute the flag_count counter on gd_rebates from actual flag rows
	 * (only un-reviewed flags count toward the threshold).
	 */
	public static function recountForRebate( int $rebateId ): void
	{
		if ( $rebateId <= 0 )
		{
			return;
		}
		try
		{
			$c = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_rebate_flags', [ 'rebate_id=? AND reviewed=?', $rebateId, 0 ]
			)->first();
			\IPS\Db::i()->update( 'gd_rebates', [ 'flag_count' => $c ], [ 'id=?', $rebateId ] );
		}
		catch ( \Exception ) {}
	}
}

class Flag extends _Flag {}
