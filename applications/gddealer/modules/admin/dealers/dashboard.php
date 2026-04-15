<?php
/**
 * @brief       GD Dealer Manager — ACP Dashboard Controller
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Section 3.12 summary. No live OpenSearch calls (Rule #8) — all metrics
 * come from SQL aggregates. Every query is isolated in its own try/catch so
 * a single missing table cannot hang the page.
 */

namespace IPS\gddealer\modules\admin\dealers;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _dashboard extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		parent::execute();
	}

	protected function manage()
	{
		$totalDealers     = 0;
		$activeDealers    = 0;
		$suspendedDealers = 0;
		$totalListings    = 0;
		$inStockListings  = 0;
		$unmatchedTotal   = 0;
		$lastRunTime      = null;
		$lastRunStatus    = null;

		try { $totalDealers     = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config' )->first(); } catch ( \Exception ) {}
		try { $activeDealers    = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config', [ 'active=? AND suspended=?', 1, 0 ] )->first(); } catch ( \Exception ) {}
		try { $suspendedDealers = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config', [ 'suspended=?', 1 ] )->first(); } catch ( \Exception ) {}
		try { $totalListings    = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings' )->first(); } catch ( \Exception ) {}
		try { $inStockListings  = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings', [ 'listing_status=? AND in_stock=?', 'active', 1 ] )->first(); } catch ( \Exception ) {}
		try { $unmatchedTotal   = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_unmatched_upcs', [ 'admin_excluded=?', 0 ] )->first(); } catch ( \Exception ) {}

		try
		{
			$lastLog = \IPS\Db::i()->select( '*', 'gd_dealer_import_log', null, 'run_start DESC', [ 0, 1 ] )->first();
			$lastRunTime   = $lastLog['run_start'] ?? null;
			$lastRunStatus = $lastLog['status']   ?? null;
		}
		catch ( \Exception ) {}

		/* Per-tier counts for quick overview */
		$tierCounts = [ 'basic' => 0, 'pro' => 0, 'enterprise' => 0, 'founding' => 0 ];
		try
		{
			foreach ( \IPS\Db::i()->select( 'subscription_tier, COUNT(*) AS c', 'gd_dealer_feed_config', null, null, null, 'subscription_tier' ) as $row )
			{
				$tierCounts[ (string) $row['subscription_tier'] ] = (int) $row['c'];
			}
		}
		catch ( \Exception ) {}

		$dealersUrl   = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers' );
		$mrrUrl       = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=mrr' );
		$unmatchedUrl = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=unmatched' );

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_dash_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'admin' )->dashboard(
			$totalDealers, $activeDealers, $suspendedDealers,
			$totalListings, $inStockListings, $unmatchedTotal,
			$lastRunTime, $lastRunStatus,
			$tierCounts,
			$dealersUrl, $mrrUrl, $unmatchedUrl
		);
	}
}

class dashboard extends _dashboard {}
