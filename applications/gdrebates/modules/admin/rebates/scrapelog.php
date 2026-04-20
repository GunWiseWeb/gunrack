<?php
/**
 * @brief       GD Rebates — Scrape log browser
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Read-only view of gd_scrape_log (Section 8.2.5). Shows per-run
 * counters (found/created/updated/unchanged/failures) plus any error
 * log written by the scraper task. Read-only intentionally: deletions
 * shouldn't happen from the UI because the log is the paper trail.
 */

namespace IPS\gdrebates\modules\admin\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _scrapelog extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'rebates_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$rows = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_scrape_log', null, 'run_at DESC', 200 ) as $r )
			{
				if ( is_array( $r ) )
				{
					$rows[] = $r;
				}
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_scrapelog_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'admin' )->scrapelog( [
			'rows' => $rows,
		] );
	}
}

class scrapelog extends _scrapelog {}
