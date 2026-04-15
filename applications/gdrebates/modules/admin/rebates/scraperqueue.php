<?php
/**
 * @brief       GD Rebates — Scraper approval queue
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Scraped rebates from targets with is_known = 0 land in status=pending
 * with source=scraped (Section 8.2.3). Admin reviews them here and
 * either approves (→ active) or rejects. Distinct from
 * submissions.php which handles community (source=community) submissions.
 */

namespace IPS\gdrebates\modules\admin\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _scraperqueue extends \IPS\Dispatcher\Controller
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
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_rebates',
				[ 'status=? AND source IN (?,?)', 'pending', 'scraped', 'scraped_community' ],
				'created_at DESC', 100
			) as $r )
			{
				if ( !is_array( $r ) )
				{
					continue;
				}
				$id = (int) ( $r['id'] ?? 0 );
				$r['approve_url'] = (string) \IPS\Http\Url::internal(
					'app=gdrebates&module=rebates&controller=scraperqueue&do=approve&id=' . $id
				)->csrf();
				$r['reject_url']  = (string) \IPS\Http\Url::internal(
					'app=gdrebates&module=rebates&controller=scraperqueue&do=reject&id=' . $id
				)->csrf();
				$rows[] = $r;
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_scraperq_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'admin' )->scraperqueue( [
			'rows' => $rows,
		] );
	}

	protected function approve(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) \IPS\Request::i()->id;
		if ( $id > 0 )
		{
			try
			{
				\IPS\Db::i()->update( 'gd_rebates', [
					'status'      => 'active',
					'verified_by' => (int) \IPS\Member::loggedIn()->member_id,
				], [ 'id=?', $id ] );
			}
			catch ( \Exception ) {}
		}
		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=scraperqueue' ),
			'gdr_submissions_approved_ok'
		);
	}

	protected function reject(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) \IPS\Request::i()->id;
		if ( $id > 0 )
		{
			try
			{
				\IPS\Db::i()->update( 'gd_rebates', [
					'status'      => 'rejected',
					'verified_by' => (int) \IPS\Member::loggedIn()->member_id,
				], [ 'id=?', $id ] );
			}
			catch ( \Exception ) {}
		}
		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=scraperqueue' ),
			'gdr_submissions_rejected_ok'
		);
	}
}

class scraperqueue extends _scraperqueue {}
