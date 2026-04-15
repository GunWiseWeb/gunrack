<?php
/**
 * @brief       GD Price Comparison — FFL data refresh task
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Pulls the ATF FFL monthly dataset. At launch this task only ensures the
 * scheduled entry exists — the real ingestion pipeline (ZIP geocoding, CSV
 * parse, upsert) is wired in Phase 2. Admin may manually populate via SQL
 * import in the meantime.
 */

namespace IPS\gdpricecompare\tasks;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _refreshFflData extends \IPS\Task
{
	public function execute()
	{
		$this->log( 'refreshFflData: scheduled placeholder — ingestion pipeline to be connected in Phase 2.' );
	}
}

class refreshFflData extends _refreshFflData {}
