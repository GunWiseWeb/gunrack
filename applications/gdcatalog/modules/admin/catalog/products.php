<?php
/**
 * @brief       GD Master Catalog — ACP Products Controller
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Product search/browse by UPC, title, brand, category.
 * Inline edit with field locking (Section 2.2.3).
 * Admin Review queue with one-click resolve.
 */

namespace IPS\gdcatalog\modules\admin\catalog;

use IPS\gdcatalog\Catalog\Product;
use IPS\gdcatalog\Catalog\Category;
use IPS\gdcatalog\Conflict\FieldLock;
use IPS\gdcatalog\Search\OpenSearchIndexer;

class products extends \IPS\Dispatcher\Controller
{
	protected static $csrfProtected = true;

	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'catalog_manage' );
		parent::execute();
	}

	/**
	 * Product list with search/filter.
	 */
	protected function manage()
	{
		$where   = [];
		$search  = \IPS\Request::i()->q ?? '';
		$status  = \IPS\Request::i()->status ?? '';
		$catId   = (int) ( \IPS\Request::i()->category ?? 0 );

		if ( $search !== '' )
		{
			$where[] = [
				'(upc LIKE ? OR title LIKE ? OR brand LIKE ?)',
				'%' . $search . '%', '%' . $search . '%', '%' . $search . '%'
			];
		}

		if ( $status !== '' )
		{
			$where[] = [ 'record_status=?', $status ];
		}

		if ( $catId > 0 )
		{
			$where[] = [ 'category_id=?', $catId ];
		}

		$page    = max( 1, (int) ( \IPS\Request::i()->page ?? 1 ) );
		$perPage = 50;

		$total = \IPS\Db::i()->select( 'COUNT(*)', 'gd_catalog', $where )->first();

		$products = [];
		foreach (
			\IPS\Db::i()->select( '*', 'gd_catalog', $where, 'last_updated DESC', [ ( $page - 1 ) * $perPage, $perPage ] ) as $row
		)
		{
			$products[] = Product::constructFromData( $row );
		}

		$categories = Category::roots();

		$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=products' ),
			ceil( $total / $perPage ),
			$page,
			$perPage
		);

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_products_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'catalog', 'gdcatalog', 'admin' )->productList(
			$products, $categories, $search, $status, $catId, $total, $pagination
		);
	}

	/**
	 * Edit a single product.
	 */
	protected function edit()
	{
		$upc = \IPS\Request::i()->upc;

		try
		{
			$product = Product::load( $upc );
		}
		catch ( \OutOfRangeException )
		{
			\IPS\Output::i()->error( 'node_error', '2GDC102/1', 404 );
			return;
		}

		$locks = FieldLock::loadForProduct( $upc );

		$form = new \IPS\Helpers\Form;

		/* Editable fields */
		$editableFields = [
			'title'       => [ 'Text', 255 ],
			'brand'       => [ 'Text', 100 ],
			'model'       => [ 'Text', 100 ],
			'caliber'     => [ 'Text', 50 ],
			'action_type' => [ 'Text', 50 ],
			'finish'      => [ 'Text', 100 ],
			'msrp'        => [ 'Number', null ],
			'description' => [ 'TextArea', null ],
		];

		foreach ( $editableFields as $field => $config )
		{
			$isLocked = $product->isFieldLocked( $field );
			$formClass = $config[0] === 'TextArea'
				? \IPS\Helpers\Form\TextArea::class
				: ( $config[0] === 'Number' ? \IPS\Helpers\Form\Number::class : \IPS\Helpers\Form\Text::class );

			$form->add( new $formClass(
				'gdcatalog_product_' . $field,
				$product->$field ?? '',
				FALSE,
				$config[0] === 'Number' ? [ 'decimals' => 2 ] : []
			));
		}

		/* Boolean flags */
		$form->add( new \IPS\Helpers\Form\YesNo( 'gdcatalog_product_nfa_item', $product->nfa_item ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'gdcatalog_product_requires_ffl', $product->requires_ffl ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'gdcatalog_product_is_ammo', $product->is_ammo ) );

		/* Status */
		$form->add( new \IPS\Helpers\Form\Select(
			'gdcatalog_product_status', $product->record_status, TRUE,
			[ 'options' => [
				'active'       => 'Active',
				'discontinued' => 'Discontinued',
				'admin_review' => 'Admin Review',
				'pending'      => 'Pending',
			]]
		));

		if ( $values = $form->values() )
		{
			\IPS\Session::i()->csrfCheck();

			foreach ( $editableFields as $field => $config )
			{
				$product->$field = $values[ 'gdcatalog_product_' . $field ];
			}

			$product->nfa_item      = (int) $values['gdcatalog_product_nfa_item'];
			$product->requires_ffl  = (int) $values['gdcatalog_product_requires_ffl'];
			$product->is_ammo       = (int) $values['gdcatalog_product_is_ammo'];
			$product->record_status = $values['gdcatalog_product_status'];
			$product->last_updated  = date( 'Y-m-d H:i:s' );
			$product->save();

			/* Reindex */
			OpenSearchIndexer::i()->indexProduct( $product );

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=products' ),
				'saved'
			);
		}

		\IPS\Output::i()->title  = $product->title . ' (' . $product->upc . ')';
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'catalog', 'gdcatalog', 'admin' )->productEdit(
			$product, $locks, (string) $form
		);
	}

	/**
	 * Lock a field on a product.
	 */
	protected function lockField()
	{
		\IPS\Session::i()->csrfCheck();

		$upc   = \IPS\Request::i()->upc;
		$field = \IPS\Request::i()->field;

		$product = Product::load( $upc );
		$product->lockField( $field );
		$product->save();

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=products&do=edit&upc=' . urlencode( $upc ) ),
			'Field locked'
		);
	}

	/**
	 * Unlock a field on a product.
	 */
	protected function unlockField()
	{
		\IPS\Session::i()->csrfCheck();

		$upc   = \IPS\Request::i()->upc;
		$field = \IPS\Request::i()->field;

		$product = Product::load( $upc );
		$product->unlockField( $field );
		$product->save();

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=products&do=edit&upc=' . urlencode( $upc ) ),
			'Field unlocked'
		);
	}

	/**
	 * Admin review queue — resolve a product out of admin_review status.
	 */
	protected function resolveReview()
	{
		\IPS\Session::i()->csrfCheck();

		$upc = \IPS\Request::i()->upc;
		$product = Product::load( $upc );

		$product->record_status = Product::STATUS_ACTIVE;
		$product->last_updated  = date( 'Y-m-d H:i:s' );
		$product->save();

		OpenSearchIndexer::i()->indexProduct( $product );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=products&status=admin_review' ),
			'Product approved'
		);
	}
}
