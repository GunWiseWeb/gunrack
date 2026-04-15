<?php
/**
 * @brief       GD Price Comparison — FFL data admin
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\modules\admin\pricecompare;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _ffldata extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'can_manage_ffl' );
		parent::execute();
	}

	protected function manage(): void
	{
		$count  = \IPS\gdpricecompare\Ffl\FflDealer::totalCount();
		$last   = \IPS\gdpricecompare\Ffl\FflDealer::lastRefreshedAt();

		$refreshUrl = (string) \IPS\Http\Url::internal(
			'app=gdpricecompare&module=pricecompare&controller=ffldata&do=refresh'
		)->csrf();

		$data = [
			'count'       => $count,
			'last'        => $last,
			'refresh_url' => $refreshUrl,
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_ffldata_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'admin' )
			->ffldata( $data );
	}

	protected function refresh(): void
	{
		\IPS\Session::i()->csrfCheck();

		try
		{
			\IPS\Db::i()->update( 'core_tasks', [ 'next_run' => time() ], [ 'app=? AND `key`=?', 'gdpricecompare', 'refreshFflData' ]);
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=ffldata' ),
			'gdpc_ffldata_refresh_queued'
		);
	}
}

class ffldata extends _ffldata {}
