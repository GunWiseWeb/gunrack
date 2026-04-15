<?php
/**
 * @brief       GD Price Comparison — Member watchlist
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\modules\front\account;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _watchlist extends \IPS\Dispatcher\Controller
{
	public function execute(): void
	{
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'gdpc_err_guest_watchlist', '2GDPC/2', 403, '' );
			return;
		}
		parent::execute();
	}

	protected function manage(): void
	{
		$memberId = (int) \IPS\Member::loggedIn()->member_id;
		$watches  = \IPS\gdpricecompare\Watchlist\Watchlist::forMember( $memberId );

		$rows = [];
		foreach ( $watches as $w )
		{
			$upc     = (string) $w['upc'];
			$product = \IPS\gdpricecompare\Product\Product::loadByUpc( $upc );
			if ( $product === null )
			{
				continue;
			}

			$rows[] = [
				'id'           => (int) $w['id'],
				'upc'          => $upc,
				'title'        => (string) ( $product['title'] ?? '' ),
				'slug'         => (string) ( $product['slug'] ?? '' ),
				'category'     => (string) ( $product['category_slug'] ?? 'all' ),
				'image'        => (string) ( $product['primary_image'] ?? '' ),
				'current_min'  => $product['total_min_price'] !== null ? (float) $product['total_min_price'] : null,
				'target_price' => $w['target_price'] !== null ? (float) $w['target_price'] : null,
				'notify_stock' => (int) ( $w['notify_in_stock'] ?? 0 ) === 1,
				'created_at'   => (string) $w['created_at'],
				'product_url'  => (string) \IPS\Http\Url::internal(
					'app=gdpricecompare&module=products&controller=products&do=view&category=' . urlencode( (string) ( $product['category_slug'] ?? 'all' ) ) . '&slug=' . urlencode( (string) ( $product['slug'] ?? '' ) )
				),
				'remove_url'   => (string) \IPS\Http\Url::internal(
					'app=gdpricecompare&module=account&controller=watchlist&do=remove&upc=' . urlencode( $upc )
				)->csrf(),
			];
		}

		$data = [ 'rows' => $rows, 'count' => count( $rows ) ];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_front_watchlist_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'front' )
			->watchlist( $data );
	}

	protected function toggle(): void
	{
		\IPS\Session::i()->csrfCheck();
		$memberId = (int) \IPS\Member::loggedIn()->member_id;
		$upc      = (string) \IPS\Request::i()->upc;
		if ( $upc === '' || $memberId === 0 )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gdpricecompare&module=account&controller=watchlist' ) );
			return;
		}

		try
		{
			\IPS\Db::i()->select( 'id', 'gd_watchlist', [ 'member_id=? AND upc=?', $memberId, $upc ])->first();
			\IPS\gdpricecompare\Watchlist\Watchlist::unwatch( $memberId, $upc );
		}
		catch ( \UnderflowException )
		{
			$targetPrice = \IPS\Request::i()->target_price !== null && \IPS\Request::i()->target_price !== ''
				? (float) \IPS\Request::i()->target_price : null;
			$notifyStock = (bool) \IPS\Request::i()->notify_in_stock;
			\IPS\gdpricecompare\Watchlist\Watchlist::watch( $memberId, $upc, $targetPrice, $notifyStock );
		}

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gdpricecompare&module=account&controller=watchlist' ) );
	}

	protected function remove(): void
	{
		\IPS\Session::i()->csrfCheck();
		$memberId = (int) \IPS\Member::loggedIn()->member_id;
		$upc      = (string) \IPS\Request::i()->upc;
		if ( $upc !== '' && $memberId > 0 )
		{
			\IPS\gdpricecompare\Watchlist\Watchlist::unwatch( $memberId, $upc );
		}
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gdpricecompare&module=account&controller=watchlist' ) );
	}
}

class watchlist extends _watchlist {}
