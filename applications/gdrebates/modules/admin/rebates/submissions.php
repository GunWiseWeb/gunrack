<?php
/**
 * @brief       GD Rebates — Community submission queue
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Lists pending community submissions (Section 8.8). Admin can approve
 * or reject each one. Approved rebates transition to status = active;
 * verified_by is stamped with the acting admin's member_id.
 */

namespace IPS\gdrebates\modules\admin\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _submissions extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'rebates_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$rows = $this->fetchPending( 'community' );

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_submissions_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'admin' )->submissions( [
			'rows'        => $rows,
			'approve_url' => (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=submissions&do=approve' ),
			'reject_url'  => (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=submissions&do=reject' ),
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
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=submissions' ),
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
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=submissions' ),
			'gdr_submissions_rejected_ok'
		);
	}

	/**
	 * Pull pending rebates, optionally filtered by source.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function fetchPending( string $source ): array
	{
		$out = [];
		try
		{
			$where = [ 'status=? AND source=?', 'pending', $source ];
			foreach ( \IPS\Db::i()->select( '*', 'gd_rebates', $where, 'created_at DESC', 100 ) as $r )
			{
				if ( !is_array( $r ) )
				{
					continue;
				}
				$id = (int) ( $r['id'] ?? 0 );
				$r['approve_url'] = (string) \IPS\Http\Url::internal(
					'app=gdrebates&module=rebates&controller=submissions&do=approve&id=' . $id
				)->csrf();
				$r['reject_url']  = (string) \IPS\Http\Url::internal(
					'app=gdrebates&module=rebates&controller=submissions&do=reject&id=' . $id
				)->csrf();
				$out[] = $r;
			}
		}
		catch ( \Exception ) {}
		return $out;
	}
}

class submissions extends _submissions {}
