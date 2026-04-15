<?php
/**
 * @brief       GD Price Comparison — Product detail / category browse
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Handles /products/{category-slug} (browse) and
 * /products/{category-slug}/{slug} (detail).
 */

namespace IPS\gdpricecompare\modules\front\products;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _products extends \IPS\Dispatcher\Controller
{
	public function execute(): void
	{
		parent::execute();
	}

	protected function manage(): void
	{
		if ( (string) \IPS\Request::i()->slug !== '' )
		{
			$this->view();
			return;
		}
		$this->browse();
	}

	protected function view(): void
	{
		$slug = (string) \IPS\Request::i()->slug;
		$product = \IPS\gdpricecompare\Product\Product::loadBySlug( $slug );
		if ( $product === null )
		{
			\IPS\Output::i()->error( 'gdpc_err_no_product', '2GDPC/1', 404, '' );
			return;
		}

		$userState = self::userState();
		$cprShip   = self::cprIncludeShippingState();

		$comparison = \IPS\gdpricecompare\Listing\Comparison::build(
			(string) $product['upc'], $userState, $cprShip
		);

		$stateRestriction = null;
		if ( $userState !== null )
		{
			$stateRestriction = \IPS\gdpricecompare\Compliance\StateRestriction::matchProduct(
				$userState, $product
			);
		}

		$isNfa = (int) ( $product['nfa_item'] ?? 0 ) === 1;
		$isFfl = (int) ( $product['requires_ffl'] ?? 0 ) === 1;
		$isAmmo= (int) ( $product['is_ammo'] ?? 0 ) === 1;

		$watching = false;
		$memberId = (int) \IPS\Member::loggedIn()->member_id;
		if ( $memberId > 0 )
		{
			try
			{
				\IPS\Db::i()->select(
					'id', 'gd_watchlist',
					[ 'member_id=? AND upc=?', $memberId, (string) $product['upc'] ]
				)->first();
				$watching = true;
			}
			catch ( \UnderflowException )
			{
				$watching = false;
			}
			catch ( \Exception )
			{
				$watching = false;
			}
		}

		$specPills = [];
		foreach ( [
			'caliber'       => 'Caliber',
			'action_type'   => 'Action',
			'barrel_length' => 'Barrel',
			'capacity'      => 'Capacity',
			'finish'        => 'Finish',
		] as $col => $label )
		{
			$v = (string) ( $product[ $col ] ?? '' );
			if ( $v !== '' )
			{
				$specPills[] = [ 'label' => $label, 'value' => $v ];
			}
		}

		$bestTotalFmt = null;
		if ( $comparison['best_total'] !== null )
		{
			$bestTotalFmt = '$' . number_format( (float) $comparison['best_total'], 2 );
		}

		$data = [
			'product'       => $product,
			'is_nfa'        => $isNfa,
			'is_ffl'        => $isFfl,
			'is_ammo'       => $isAmmo,
			'spec_pills'    => $specPills,
			'in_stock'      => $comparison['in_stock'],
			'out_of_stock'  => $comparison['out_of_stock'],
			'best_total'    => $comparison['best_total'],
			'best_total_fmt'=> $bestTotalFmt,
			'user_state'    => $userState,
			'cpr_ship'      => $cprShip,
			'state_restriction' => $stateRestriction,
			'watching'      => $watching,
			'watch_url'     => (string) \IPS\Http\Url::internal(
				'app=gdpricecompare&module=account&controller=watchlist&do=toggle&upc=' . urlencode( (string) $product['upc'] )
			)->csrf(),
		];

		\IPS\Output::i()->title  = (string) ( $product['title'] ?? 'Product' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'front' )
			->product( $data );
	}

	protected function browse(): void
	{
		$category = (string) \IPS\Request::i()->category;
		$rows = [];

		try
		{
			$where = $category !== ''
				? [ 'category_slug=?', $category ]
				: null;

			foreach ( \IPS\Db::i()->select(
				'upc, title, brand, slug, category_slug, primary_image, total_min_price, free_ship_avail, active_dealer_count, nfa_item, requires_ffl',
				'gd_catalog',
				$where,
				'total_min_price ASC',
				[ 0, 48 ]
			) as $r )
			{
				$rows[] = [
					'upc'           => (string) $r['upc'],
					'title'         => (string) ( $r['title'] ?? '' ),
					'brand'         => (string) ( $r['brand'] ?? '' ),
					'slug'          => (string) ( $r['slug'] ?? '' ),
					'category'      => (string) ( $r['category_slug'] ?? '' ),
					'image'         => (string) ( $r['primary_image'] ?? '' ),
					'from_price'    => $r['total_min_price'] !== null ? (float) $r['total_min_price'] : null,
					'free_ship'     => (int) ( $r['free_ship_avail'] ?? 0 ) === 1,
					'dealer_count'  => (int) ( $r['active_dealer_count'] ?? 0 ),
					'is_nfa'        => (int) ( $r['nfa_item'] ?? 0 ) === 1,
					'is_ffl'        => (int) ( $r['requires_ffl'] ?? 0 ) === 1,
					'url'           => (string) \IPS\Http\Url::internal(
						'app=gdpricecompare&module=products&controller=products&do=view&category=' . urlencode( (string) ( $r['category_slug'] ?? 'all' ) ) . '&slug=' . urlencode( (string) ( $r['slug'] ?? '' ) )
					),
				];
			}
		}
		catch ( \Exception ) {}

		$data = [
			'category' => $category,
			'q'        => (string) \IPS\Request::i()->q,
			'rows'     => $rows,
			'count'    => count( $rows ),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_front_browse_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'front' )
			->browse( $data );
	}

	private static function userState(): ?string
	{
		$member = \IPS\Member::loggedIn();
		if ( $member->member_id && isset( $member->profileFields()['core_pfield_gdpc_state'] ) )
		{
			$s = (string) $member->profileFields()['core_pfield_gdpc_state'];
			if ( $s !== '' ) { return strtoupper( $s ); }
		}
		if ( !empty( $_COOKIE['gdpc_state'] ) )
		{
			return strtoupper( substr( (string) $_COOKIE['gdpc_state'], 0, 2 ) );
		}
		return null;
	}

	private static function cprIncludeShippingState(): bool
	{
		if ( isset( $_COOKIE['gdpc_cpr_ship'] ) )
		{
			return $_COOKIE['gdpc_cpr_ship'] === '1';
		}
		return (int) ( \IPS\Settings::i()->gdpc_cpr_include_shipping_default ?? 0 ) === 1;
	}
}

class products extends _products {}
