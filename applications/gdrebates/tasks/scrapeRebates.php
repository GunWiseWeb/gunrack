<?php
/**
 * @brief       GD Rebates — Nightly manufacturer rebate scraper
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Iterates all enabled gd_scrape_targets and hands each off to the
 * Scraper engine (sources/Rebate/Scraper.php). Each target run produces
 * a gd_scrape_log row; failures are logged but don't abort the batch.
 *
 * Gate: if gdr_scraper_enabled setting is off the whole task is a
 * no-op. Admins can flip it from ACP > GD Rebates > Settings.
 */

namespace IPS\gdrebates\tasks;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _scrapeRebates extends \IPS\Task
{
	public function execute()
	{
		$settings = \IPS\Settings::i();
		if ( (int) ( $settings->gdr_scraper_enabled ?? 0 ) !== 1 )
		{
			return null;
		}

		$targets = \IPS\gdrebates\Rebate\Target::fetchEnabled();
		foreach ( $targets as $t )
		{
			$this->runOne( $t );
		}

		return null;
	}

	/**
	 * @param array<string,mixed> $target
	 */
	private function runOne( array $target ): void
	{
		$id  = (int) ( $target['id'] ?? 0 );
		$now = date( 'Y-m-d H:i:s' );

		try
		{
			$summary = \IPS\gdrebates\Rebate\Scraper::runTarget( $target );
		}
		catch ( \Exception $e )
		{
			$summary = [
				'status'    => 'failed',
				'found'     => 0,
				'created'   => 0,
				'updated'   => 0,
				'unchanged' => 0,
				'failures'  => 0,
				'error'     => $e->getMessage(),
			];
		}

		try
		{
			\IPS\Db::i()->insert( 'gd_scrape_log', [
				'target_id'         => $id,
				'manufacturer'      => (string) ( $target['manufacturer'] ?? '' ),
				'scrape_url'        => (string) ( $target['scrape_url']   ?? '' ),
				'run_at'            => $now,
				'rebates_found'     => (int) ( $summary['found']     ?? 0 ),
				'rebates_created'   => (int) ( $summary['created']   ?? 0 ),
				'rebates_updated'   => (int) ( $summary['updated']   ?? 0 ),
				'rebates_unchanged' => (int) ( $summary['unchanged'] ?? 0 ),
				'parse_failures'    => (int) ( $summary['failures']  ?? 0 ),
				'status'            => (string) ( $summary['status'] ?? 'success' ),
				'error_log'         => $summary['error'] !== '' ? (string) $summary['error'] : null,
			] );
		}
		catch ( \Exception ) {}

		try
		{
			\IPS\Db::i()->update( 'gd_scrape_targets', [
				'last_run'    => $now,
				'last_status' => (string) ( $summary['status'] ?? 'success' ),
			], [ 'id=?', $id ] );
		}
		catch ( \Exception ) {}
	}
}

class scrapeRebates extends _scrapeRebates {}
