<?php
/**
 * @brief       GD Rebates — Daily archive pass
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Section 8.6 — expired rebates stay visible for N days (default 30),
 * after which they transition to archived: still accessible via direct
 * URL and searchable via the hub's archive tab, but removed from
 * active browse. expires_archived_at is stamped on transition so the
 * retention window is measurable.
 */

namespace IPS\gdrebates\tasks;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _archiveRebates extends \IPS\Task
{
	public function execute()
	{
		$settings = \IPS\Settings::i();
		$days     = max( 1, (int) ( $settings->gdr_archive_after_days ?? 30 ) );
		$cutoff   = date( 'Y-m-d', strtotime( '-' . $days . ' days' ) );
		$now      = date( 'Y-m-d H:i:s' );

		try
		{
			\IPS\Db::i()->update(
				'gd_rebates',
				[ 'status' => 'archived', 'expires_archived_at' => $now ],
				[ 'status=? AND end_date IS NOT NULL AND end_date<?', 'expired', $cutoff ]
			);
		}
		catch ( \Exception ) {}

		return null;
	}
}

class archiveRebates extends _archiveRebates {}
