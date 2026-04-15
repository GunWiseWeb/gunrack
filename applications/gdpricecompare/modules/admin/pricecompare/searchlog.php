<?php
/**
 * @brief       GD Price Comparison — Search log viewer
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

class _searchlog extends \IPS\Dispatcher\Controller
{
	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'can_view_searchlog' );
		parent::execute();
	}

	protected function manage(): void
	{
		$cutoff = date( 'Y-m-d H:i:s', time() - ( 7 * 86400 ) );

		$top  = [];
		$zero = [];

		try
		{
			foreach ( \IPS\Db::i()->select(
				'query_text, SUM(result_count>0) AS hits, COUNT(*) AS c',
				'gd_search_log',
				[ 'searched_at >= ?', $cutoff ],
				'c DESC',
				[ 0, 50 ],
				[ 'query_text' ]
			) as $r )
			{
				$top[] = [ 'query' => (string) $r['query_text'], 'count' => (int) $r['c'] ];
			}
		}
		catch ( \Exception ) {}

		try
		{
			foreach ( \IPS\Db::i()->select(
				'query_text, COUNT(*) AS c',
				'gd_search_log',
				[ 'searched_at >= ? AND result_count=?', $cutoff, 0 ],
				'c DESC',
				[ 0, 50 ],
				[ 'query_text' ]
			) as $r )
			{
				$zero[] = [ 'query' => (string) $r['query_text'], 'count' => (int) $r['c'] ];
			}
		}
		catch ( \Exception ) {}

		$data = [ 'top' => $top, 'zero' => $zero ];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_searchlog_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'admin' )
			->searchlog( $data );
	}
}

class searchlog extends _searchlog {}
