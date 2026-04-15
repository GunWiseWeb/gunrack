<?php
/**
 * @brief       GD Dealer Manager — Front Dealer Dashboard
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Section 3.11 — Dealer Dashboard at /dealers/dashboard. Visible only to
 * authenticated members with a gd_dealer_feed_config row. Presents
 * overview metrics (listings, last import, click totals) the dealer needs
 * to monitor their subscription.
 */

namespace IPS\gddealer\modules\front\dealers;

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
		$member = \IPS\Member::loggedIn();
		if ( !$member->member_id )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front' )
			);
			return;
		}
		parent::execute();
	}

	protected function manage(): void
	{
		$member  = \IPS\Member::loggedIn();
		$dealer  = null;

		try
		{
			$dealer = \IPS\Db::i()->select( '*', 'gd_dealer_feed_config', [ 'dealer_id=?', (int) $member->member_id ] )->first();
		}
		catch ( \UnderflowException )
		{
			$dealer = null;
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title = $member->language()->addToStack( 'gddealer_front_dash_title' );

		if ( !$dealer )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->noAccess();
			return;
		}

		$tier          = (string) ( $dealer['subscription_tier'] ?? 'basic' );
		$active        = (int)    ( $dealer['active']            ?? 0 ) === 1;
		$suspended     = (int)    ( $dealer['suspended']         ?? 0 ) === 1;

		$totalListings    = 0;
		$inStockListings  = 0;
		$outOfStock       = 0;
		$unmatchedCount   = 0;
		$clicks7d         = 0;
		$clicks30d        = 0;
		$lastRunTime      = null;
		$lastRunStatus    = null;
		$lastRecordCount  = 0;
		$recentImports    = [];

		try
		{
			$totalListings = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings',
				[ 'dealer_id=?', (int) $dealer['dealer_id'] ] )->first();
		}
		catch ( \Exception ) {}

		try
		{
			$inStockListings = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings',
				[ 'dealer_id=? AND listing_status=? AND in_stock=?', (int) $dealer['dealer_id'], 'active', 1 ] )->first();
		}
		catch ( \Exception ) {}

		try
		{
			$outOfStock = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings',
				[ 'dealer_id=? AND in_stock=?', (int) $dealer['dealer_id'], 0 ] )->first();
		}
		catch ( \Exception ) {}

		try
		{
			$unmatchedCount = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_unmatched_upcs',
				[ 'dealer_id=? AND admin_excluded=?', (int) $dealer['dealer_id'], 0 ] )->first();
		}
		catch ( \Exception ) {}

		try
		{
			$clicks7d  = (int) \IPS\Db::i()->select( 'SUM(click_count_7d)',  'gd_dealer_listings',
				[ 'dealer_id=?', (int) $dealer['dealer_id'] ] )->first();
			$clicks30d = (int) \IPS\Db::i()->select( 'SUM(click_count_30d)', 'gd_dealer_listings',
				[ 'dealer_id=?', (int) $dealer['dealer_id'] ] )->first();
		}
		catch ( \Exception ) {}

		try
		{
			$lastLog = \IPS\Db::i()->select( '*', 'gd_dealer_import_log',
				[ 'dealer_id=?', (int) $dealer['dealer_id'] ],
				'run_start DESC', [ 0, 1 ] )->first();
			$lastRunTime     = $lastLog['run_start']      ?? null;
			$lastRunStatus   = $lastLog['status']         ?? null;
			$lastRecordCount = (int) ( $lastLog['records_total'] ?? 0 );
		}
		catch ( \Exception ) {}

		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_import_log',
				[ 'dealer_id=?', (int) $dealer['dealer_id'] ],
				'run_start DESC', [ 0, 10 ] ) as $row )
			{
				$recentImports[] = [
					'run_start'       => (string) $row['run_start'],
					'status'          => (string) ( $row['status'] ?? '' ),
					'records_total'   => (int)    ( $row['records_total']   ?? 0 ),
					'records_created' => (int)    ( $row['records_created'] ?? 0 ),
					'records_updated' => (int)    ( $row['records_updated'] ?? 0 ),
					'records_unmatched' => (int)  ( $row['records_unmatched'] ?? 0 ),
				];
			}
		}
		catch ( \Exception ) {}

		$data = [
			'dealer_name'       => (string) ( $dealer['dealer_name'] ?? $member->name ),
			'tier'              => $tier,
			'tier_label'        => ucfirst( $tier ),
			'active'            => $active,
			'suspended'         => $suspended,
			'analytics_enabled' => in_array( $tier, [ 'pro', 'enterprise', 'founding' ], true ),
			'total_listings'    => $totalListings,
			'in_stock'          => $inStockListings,
			'out_of_stock'      => $outOfStock,
			'unmatched_count'   => $unmatchedCount,
			'clicks_7d'         => $clicks7d,
			'clicks_30d'        => $clicks30d,
			'last_run_time'     => $lastRunTime,
			'last_run_status'   => $lastRunStatus,
			'last_record_count' => $lastRecordCount,
			'recent_imports'    => $recentImports,
		];

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->dashboard( $data );
	}
}

class dashboard extends _dashboard {}
