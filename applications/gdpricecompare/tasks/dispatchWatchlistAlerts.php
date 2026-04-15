<?php
/**
 * @brief       GD Price Comparison — Watchlist alert dispatch task
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\tasks;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _dispatchWatchlistAlerts extends \IPS\Task
{
	public function execute()
	{
		try
		{
			\IPS\gdpricecompare\Alert\Dispatcher::run();
		}
		catch ( \Exception $e )
		{
			$this->log( 'dispatchWatchlistAlerts failed: ' . $e->getMessage() );
		}
	}
}

class dispatchWatchlistAlerts extends _dispatchWatchlistAlerts {}
