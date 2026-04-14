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

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\gdcatalog\Catalog\Product;
use IPS\gdcatalog\Catalog\Category;
use IPS\gdcatalog\Conflict\FieldLock;
use IPS\gdcatalog\Search\OpenSearchIndexer;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _products extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
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

		$totalPages = (int) ceil( $total / $perPage );

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_products_title' );

		$baseUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=products' );

		$html  = '<h2>Products (' . (int) $total . ')</h2>';
		$html .= '<form method="get" action="' . htmlspecialchars( (string) $baseUrl ) . '">';
		$html .= '<input type="hidden" name="app" value="gdcatalog">';
		$html .= '<input type="hidden" name="module" value="catalog">';
		$html .= '<input type="hidden" name="controller" value="products">';
		$html .= '<input type="text" name="q" value="' . htmlspecialchars( $search ) . '" placeholder="UPC / title / brand">';
		$html .= '<select name="status"><option value="">— Status —</option>';
		foreach ( [ 'active', 'discontinued', 'admin_review', 'pending' ] as $s )
		{
			$sel = $status === $s ? ' selected' : '';
			$html .= '<option value="' . $s . '"' . $sel . '>' . $s . '</option>';
		}
		$html .= '</select>';
		$html .= '<select name="category"><option value="0">— Category —</option>';
		foreach ( $categories as $c )
		{
			$sel = $catId === (int) $c->id ? ' selected' : '';
			$html .= '<option value="' . (int) $c->id . '"' . $sel . '>' . htmlspecialchars( $c->name ) . '</option>';
		}
		$html .= '</select>';
		$html .= '<button type="submit">Filter</button>';
		$html .= '</form>';

		$html .= '<table><tr><th>UPC</th><th>Title</th><th>Brand</th><th>Status</th><th>Updated</th><th></th></tr>';
		foreach ( $products as $p )
		{
			$editUrl = (string) \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=products&do=edit&upc=' . urlencode( $p->upc ) );
			$html .= '<tr>';
			$html .= '<td>' . htmlspecialchars( $p->upc ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $p->title ?? '' ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $p->brand ?? '' ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $p->record_status ?? '' ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $p->last_updated ?? '' ) . '</td>';
			$html .= '<td><a href="' . htmlspecialchars( $editUrl ) . '">Edit</a></td>';
			$html .= '</tr>';
		}
		$html .= '</table>';

		if ( $totalPages > 1 )
		{
			$html .= '<p>Page ' . $page . ' of ' . $totalPages . ' | ';
			for ( $i = 1; $i <= $totalPages; $i++ )
			{
				$pUrl = $baseUrl->setQueryString( [ 'q' => $search, 'status' => $status, 'category' => $catId, 'page' => $i ] );
				$html .= '<a href="' . htmlspecialchars( (string) $pUrl ) . '">' . $i . '</a> ';
			}
			$html .= '</p>';
		}

		\IPS\Output::i()->output = $html;
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

		\IPS\Output::i()->title = $product->title . ' (' . $product->upc . ')';

		$html  = '<h2>' . htmlspecialchars( $product->title ?? '' ) . ' <small>(' . htmlspecialchars( $product->upc ) . ')</small></h2>';

		if ( \count( $locks ) )
		{
			$html .= '<h3>Locked Fields</h3><ul>';
			foreach ( $locks as $lock )
			{
				$unlockUrl = (string) \IPS\Http\Url::internal(
					'app=gdcatalog&module=catalog&controller=compliance&do=unlock&id=' . (int) $lock->id
				)->csrf();
				$html .= '<li>' . htmlspecialchars( $lock->field_name ?? '' )
					. ' (' . htmlspecialchars( $lock->lock_type ?? '' ) . ')'
					. ' <a href="' . htmlspecialchars( $unlockUrl ) . '">unlock</a></li>';
			}
			$html .= '</ul>';
		}

		$html .= (string) $form;

		\IPS\Output::i()->output = $html;
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

class products extends _products {}
