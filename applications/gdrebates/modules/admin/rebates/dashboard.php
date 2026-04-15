<?php
/**
 * @brief       GD Rebates — Admin dashboard
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Summary view for ACP > GD Rebates. Per CLAUDE.md Rule #8 every DB
 * query is wrapped in its own try/catch so one missing table does not
 * break the page, and absolutely no live HTTP calls happen here — the
 * scraper runs only inside tasks/scrapeRebates.php.
 */

namespace IPS\gdrebates\modules\admin\rebates;

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
		\IPS\Dispatcher::i()->checkAcpPermission( 'rebates_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$settings = \IPS\Settings::i();
		$counts   = \IPS\gdrebates\Rebate\Rebate::countByStatus();

		$expiringDays = max( 1, (int) ( $settings->gdr_hub_expiring_days ?? 7 ) );
		$expiringSoon = \IPS\gdrebates\Rebate\Rebate::expiringWithinDays( $expiringDays );
		$totalSavings = \IPS\gdrebates\Rebate\Rebate::totalActiveSavings();

		$flagged    = 0;
		$threshold  = max( 1, (int) ( $settings->gdr_flag_threshold ?? 3 ) );
		try
		{
			$flagged = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_rebates',
				[ 'status=? AND flag_count>=?', 'active', $threshold ]
			)->first();
		}
		catch ( \Exception ) {}

		$byType = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'product_type, COUNT(*) AS c', 'gd_rebates',
				[ 'status=?', 'active' ], 'c DESC', null, 'product_type'
			) as $r )
			{
				$byType[] = [
					'type'  => (string) ( $r['product_type'] ?? '' ),
					'count' => (int)    ( $r['c']            ?? 0 ),
				];
			}
		}
		catch ( \Exception ) {}

		$topMfrs = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'manufacturer, COUNT(*) AS c', 'gd_rebates',
				[ 'status=?', 'active' ], 'c DESC', 10, 'manufacturer'
			) as $r )
			{
				$topMfrs[] = [
					'manufacturer' => (string) ( $r['manufacturer'] ?? '' ),
					'count'        => (int)    ( $r['c']            ?? 0 ),
				];
			}
		}
		catch ( \Exception ) {}

		$recentScrapes = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_scrape_log', null, 'run_at DESC', 10
			) as $r )
			{
				$recentScrapes[] = is_array( $r ) ? $r : [];
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_dash_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'admin' )->dashboard( [
			'counts'         => $counts,
			'expiring_days'  => $expiringDays,
			'expiring_soon'  => $expiringSoon,
			'total_savings'  => $totalSavings,
			'flagged'        => $flagged,
			'by_type'        => $byType,
			'top_mfrs'       => $topMfrs,
			'recent_scrapes' => $recentScrapes,
		] );
	}
}

class dashboard extends _dashboard {}
