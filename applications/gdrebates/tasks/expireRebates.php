<?php
/**
 * @brief       GD Rebates — Daily expiry pass
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Section 8.8 expiry automation — flips status from active → expired
 * for any rebate whose end_date has passed. Run daily.
 */

namespace IPS\gdrebates\tasks;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _expireRebates extends \IPS\Task
{
	public function execute()
	{
		$today = date( 'Y-m-d' );

		try
		{
			\IPS\Db::i()->update(
				'gd_rebates',
				[ 'status' => 'expired' ],
				[ 'status=? AND end_date IS NOT NULL AND end_date<?', 'active', $today ]
			);
		}
		catch ( \Exception ) {}

		return null;
	}
}

class expireRebates extends _expireRebates {}
