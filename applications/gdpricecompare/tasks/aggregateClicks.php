<?php
/**
 * @brief       GD Price Comparison — Click aggregation task
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Rolls raw rows from gd_click_log into gd_click_daily once per day, then
 * deletes the aggregated raw rows. Keeps the raw table small and the daily
 * table is what analytics queries against.
 */

namespace IPS\gdpricecompare\tasks;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _aggregateClicks extends \IPS\Task
{
	public function execute()
	{
		try
		{
			\IPS\gdpricecompare\Click\ClickLog::aggregateYesterday();
		}
		catch ( \Exception $e )
		{
			$this->log( 'aggregateClicks failed: ' . $e->getMessage() );
		}
	}
}

class aggregateClicks extends _aggregateClicks {}
