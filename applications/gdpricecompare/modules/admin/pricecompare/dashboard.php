<?php
/**
 * @brief       GD Price Comparison — Admin Dashboard
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Per CLAUDE.md Rule #8: no live OpenSearch calls here. Every DB query is
 * wrapped in its own try/catch so one missing table cannot break the page.
 */

namespace IPS\gdpricecompare\modules\admin\pricecompare;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _dashboard extends \IPS\Dispatcher\Controller
{
	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'can_view_dashboard' );
		parent::execute();
	}

	protected function manage(): void
	{
		$productCount    = 0;
		$listingCount    = 0;
		$watchlistCount  = 0;
		$fflCount        = 0;
		$clicksToday     = 0;
		$topSearches     = [];
		$zeroSearches    = [];

		try
		{
			$productCount = \IPS\gdpricecompare\Product\Product::totalCount();
		}
		catch ( \Exception ) {}

		try
		{
			$listingCount = \IPS\gdpricecompare\Product\Product::activeListingsCount();
		}
		catch ( \Exception ) {}

		try
		{
			$watchlistCount = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_watchlist' )->first();
		}
		catch ( \Exception ) {}

		try
		{
			$fflCount = \IPS\gdpricecompare\Ffl\FflDealer::totalCount();
		}
		catch ( \Exception ) {}

		try
		{
			$clicksToday = \IPS\gdpricecompare\Click\ClickLog::clicksToday();
		}
		catch ( \Exception ) {}

		try
		{
			$cutoff = date( 'Y-m-d H:i:s', time() - ( 7 * 86400 ) );
			foreach ( \IPS\Db::i()->select(
				'query_text, COUNT(*) AS c',
				'gd_search_log',
				[ 'searched_at >= ? AND result_count > ?', $cutoff, 0 ],
				'c DESC',
				[ 0, 10 ],
				[ 'query_text' ]
			) as $r )
			{
				$topSearches[] = [ 'query' => (string) $r['query_text'], 'count' => (int) $r['c'] ];
			}
		}
		catch ( \Exception ) {}

		try
		{
			$cutoff = date( 'Y-m-d H:i:s', time() - ( 7 * 86400 ) );
			foreach ( \IPS\Db::i()->select(
				'query_text, COUNT(*) AS c',
				'gd_search_log',
				[ 'searched_at >= ? AND result_count = ?', $cutoff, 0 ],
				'c DESC',
				[ 0, 10 ],
				[ 'query_text' ]
			) as $r )
			{
				$zeroSearches[] = [ 'query' => (string) $r['query_text'], 'count' => (int) $r['c'] ];
			}
		}
		catch ( \Exception ) {}

		$data = [
			'product_count'   => $productCount,
			'listing_count'   => $listingCount,
			'watchlist_count' => $watchlistCount,
			'ffl_count'       => $fflCount,
			'clicks_today'    => $clicksToday,
			'top_searches'    => $topSearches,
			'zero_searches'   => $zeroSearches,
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_dash_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'admin' )
			->dashboard( $data );
	}
}

class dashboard extends _dashboard {}
