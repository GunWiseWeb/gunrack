<?php
/**
 * @brief       GD Dealer Manager — Dealer-Facing Dashboard
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Section 3.11. Accessible at /dealers/dashboard. Only dealers with an
 * active feed config record may access — IPS Commerce group promotion
 * controls who that is, but we additionally verify a Dealer record
 * exists to protect against stale group memberships.
 */

namespace IPS\gddealer\modules\front\dashboard;

use IPS\gddealer\Dealer\Dealer;
use IPS\gddealer\Listing\Listing;
use IPS\gddealer\Log\ImportLog;
use IPS\gddealer\Unmatched\UnmatchedUpc;
use IPS\gddealer\Feed\Importer;
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
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD300/1', 403 );
			return;
		}
		parent::execute();
	}

	protected function manage()
	{
		$memberId = (int) \IPS\Member::loggedIn()->member_id;

		try
		{
			$dealer = Dealer::load( $memberId );
		}
		catch ( \OutOfRangeException )
		{
			\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_frontend_dashboard_title' );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dashboard', 'gddealer', 'front' )->notSubscribed();
			return;
		}

		$tab = (string) ( \IPS\Request::i()->tab ?? 'overview' );

		/* Counts for overview */
		$activeListings    = 0;
		$outOfStockListings = 0;
		$unmatchedCount    = 0;
		$clicks7d          = 0;
		$clicks30d         = 0;

		try { $activeListings     = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings', [ 'dealer_id=? AND listing_status=?', $memberId, Listing::STATUS_ACTIVE ] )->first(); } catch ( \Exception ) {}
		try { $outOfStockListings = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings', [ 'dealer_id=? AND listing_status=?', $memberId, Listing::STATUS_OUT_OF_STOCK ] )->first(); } catch ( \Exception ) {}
		try { $unmatchedCount     = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_unmatched_upcs', [ 'dealer_id=? AND admin_excluded=?', $memberId, 0 ] )->first(); } catch ( \Exception ) {}
		try { $clicks7d           = (int) \IPS\Db::i()->select( 'SUM(click_count_7d)',  'gd_dealer_listings', [ 'dealer_id=?', $memberId ] )->first(); } catch ( \Exception ) {}
		try { $clicks30d          = (int) \IPS\Db::i()->select( 'SUM(click_count_30d)', 'gd_dealer_listings', [ 'dealer_id=?', $memberId ] )->first(); } catch ( \Exception ) {}

		$overview = [
			'tier'              => (string) $dealer->subscription_tier,
			'active_listings'   => $activeListings,
			'oos_listings'      => $outOfStockListings,
			'unmatched_count'   => $unmatchedCount,
			'last_run'          => $dealer->last_run ?? '',
			'last_run_status'   => $dealer->last_run_status ?? '',
			'last_record_count' => (int) $dealer->last_record_count,
			'clicks_7d'         => $clicks7d,
			'clicks_30d'        => $clicks30d,
		];

		/* Listings tab */
		$listings = [];
		foreach ( Listing::loadForDealer( $memberId, 0, 50 ) as $l )
		{
			$listings[] = [
				'upc'          => (string) $l->upc,
				'price'        => '$' . number_format( (float) $l->dealer_price, 2 ),
				'in_stock'     => (bool) $l->in_stock,
				'condition'    => (string) $l->condition,
				'last_updated' => (string) ( $l->last_seen_in_feed ?? '' ),
				'status'       => (string) $l->listing_status,
			];
		}

		/* Unmatched tab */
		$unmatchedRows = [];
		foreach ( UnmatchedUpc::loadForDealer( $memberId, 0, 100 ) as $r )
		{
			$unmatchedRows[] = [
				'upc'              => (string) $r['upc'],
				'first_seen'       => (string) $r['first_seen'],
				'last_seen'        => (string) $r['last_seen'],
				'occurrence_count' => (int) $r['occurrence_count'],
			];
		}

		/* Import log */
		$logs = [];
		foreach ( ImportLog::loadForDealer( $memberId, 20 ) as $log )
		{
			$logs[] = [
				'run_start'         => (string) $log->run_start,
				'run_end'           => (string) ( $log->run_end ?? '' ),
				'status'            => (string) $log->status,
				'records_total'     => (int) $log->records_total,
				'records_created'   => (int) $log->records_created,
				'records_updated'   => (int) $log->records_updated,
				'records_unmatched' => (int) $log->records_unmatched,
			];
		}

		$feed = [
			'feed_url'        => (string) ( $dealer->feed_url ?? '' ),
			'feed_format'     => strtoupper( (string) $dealer->feed_format ),
			'import_schedule' => (string) $dealer->import_schedule,
			'last_run'        => (string) ( $dealer->last_run ?? '' ),
		];

		$tabUrls = [
			'overview'     => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dashboard&controller=dashboard&tab=overview' ),
			'feed'         => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dashboard&controller=dashboard&tab=feed' ),
			'listings'     => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dashboard&controller=dashboard&tab=listings' ),
			'unmatched'    => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dashboard&controller=dashboard&tab=unmatched' ),
			'analytics'    => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dashboard&controller=dashboard&tab=analytics' ),
			'subscription' => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dashboard&controller=dashboard&tab=subscription' ),
		];

		$importUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dashboard&controller=dashboard&do=runImport'
		)->csrf();

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_frontend_dashboard_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dashboard', 'gddealer', 'front' )->dashboard(
			$tab, $overview, $feed, $listings, $unmatchedRows, $logs, $tabUrls, $importUrl
		);
	}

	/**
	 * Dealer-triggered manual import.
	 */
	protected function runImport()
	{
		\IPS\Session::i()->csrfCheck();
		$memberId = (int) \IPS\Member::loggedIn()->member_id;

		try
		{
			$dealer = Dealer::load( $memberId );
		}
		catch ( \OutOfRangeException )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD300/2', 403 );
			return;
		}

		$log = Importer::run( $dealer );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dashboard&controller=dashboard&tab=feed' ),
			$log->status === 'completed'
				? "Import complete — {$log->records_created} new, {$log->records_updated} updated"
				: 'Import failed: ' . ( $log->error_log ?? 'unknown error' )
		);
	}
}

class dashboard extends _dashboard {}
