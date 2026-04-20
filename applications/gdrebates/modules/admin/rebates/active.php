<?php
/**
 * @brief       GD Rebates — Active rebates list + lifecycle actions
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Admin list of every active rebate with inline expire / archive
 * actions (Section 8.8). Full edit is deferred to a later iteration —
 * the spec places higher priority on the scraper, submission queue,
 * and flag handling, which ship in this release.
 */

namespace IPS\gdrebates\modules\admin\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _active extends \IPS\Dispatcher\Controller
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
				'*', 'gd_rebates', [ 'status=?', 'active' ], 'end_date ASC', 200
			) as $r )
			{
				if ( !is_array( $r ) )
				{
					continue;
				}
				$id = (int) ( $r['id'] ?? 0 );
				$r['expire_url']  = (string) \IPS\Http\Url::internal(
					'app=gdrebates&module=rebates&controller=active&do=expire&id=' . $id
				)->csrf();
				$r['archive_url'] = (string) \IPS\Http\Url::internal(
					'app=gdrebates&module=rebates&controller=active&do=archive&id=' . $id
				)->csrf();
				$rows[] = $r;
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_active_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'admin' )->active( [
			'rows' => $rows,
		] );
	}

	protected function expire(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) \IPS\Request::i()->id;
		if ( $id > 0 )
		{
			try
			{
				\IPS\Db::i()->update( 'gd_rebates', [ 'status' => 'expired' ], [ 'id=?', $id ] );
			}
			catch ( \Exception ) {}
		}
		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=active' ),
			'gdr_active_expired_ok'
		);
	}

	protected function archive(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) \IPS\Request::i()->id;
		if ( $id > 0 )
		{
			try
			{
				\IPS\Db::i()->update( 'gd_rebates', [
					'status'              => 'archived',
					'expires_archived_at' => date( 'Y-m-d H:i:s' ),
				], [ 'id=?', $id ] );
			}
			catch ( \Exception ) {}
		}
		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=active' ),
			'gdr_active_archived_ok'
		);
	}
}

class active extends _active {}
