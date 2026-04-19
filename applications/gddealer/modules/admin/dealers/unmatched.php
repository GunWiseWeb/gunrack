<?php
/**
 * @brief       GD Dealer Manager — ACP Unmatched UPCs Controller
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Cross-dealer unmatched UPCs. Sortable by occurrence count. "Add to Catalog"
 * creates a minimal gd_catalog stub and clears the unmatched row.
 */

namespace IPS\gddealer\modules\admin\dealers;

use IPS\gddealer\Unmatched\UnmatchedUpc;
use IPS\gddealer\Dealer\Dealer;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _unmatched extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		parent::execute();
	}

	protected function manage()
	{
		$page    = max( 1, (int) ( \IPS\Request::i()->page ?? 1 ) );
		$perPage = 50;
		$offset  = ( $page - 1 ) * $perPage;

		$rawRows = UnmatchedUpc::loadAll( $offset, $perPage );

		/* Build a dealer-id -> name lookup so we can display dealer names */
		$dealerNames = [];
		try
		{
			foreach ( \IPS\Db::i()->select( 'dealer_id, dealer_name', 'gd_dealer_feed_config' ) as $r )
			{
				$dealerNames[ (int) $r['dealer_id'] ] = (string) $r['dealer_name'];
			}
		}
		catch ( \Exception ) {}

		$rows = [];
		foreach ( $rawRows as $r )
		{
			$excludeUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=unmatched&do=exclude&id=' . (int) $r['id']
			)->csrf();
			$addUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=unmatched&do=addToCatalog&id=' . (int) $r['id']
			)->csrf();

			$rows[] = [
				'id'               => (int) $r['id'],
				'upc'              => (string) $r['upc'],
				'dealer_name'      => $dealerNames[ (int) $r['dealer_id'] ] ?? ( 'Dealer #' . (int) $r['dealer_id'] ),
				'first_seen'       => (string) $r['first_seen'],
				'last_seen'        => (string) $r['last_seen'],
				'occurrence_count' => (int) $r['occurrence_count'],
				'exclude_url'      => $excludeUrl,
				'add_url'          => $addUrl,
			];
		}

		$total = 0;
		try { $total = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_unmatched_upcs', [ 'admin_excluded=?', 0 ] )->first(); } catch ( \Exception ) {}

		$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=unmatched' ),
			(int) ceil( max( 1, $total ) / $perPage ),
			$page,
			$perPage
		);

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_unmatched_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'admin' )->unmatchedList(
			$rows, $total, $pagination
		);
	}

	protected function exclude()
	{
		\IPS\Session::i()->csrfCheck();
		UnmatchedUpc::exclude( (int) \IPS\Request::i()->id );
		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=unmatched' ),
			'UPC excluded from queue'
		);
	}

	/**
	 * Add an unmatched UPC to the master catalog with placeholder values.
	 * Admin can then open the catalog to flesh out the details.
	 */
	protected function addToCatalog()
	{
		\IPS\Session::i()->csrfCheck();

		$id = (int) \IPS\Request::i()->id;
		$row = \IPS\Db::i()->select( '*', 'gd_unmatched_upcs', [ 'id=?', $id ] )->first();
		$upc = (string) $row['upc'];

		try
		{
			\IPS\Db::i()->insert( 'gd_catalog', [
				'upc'            => $upc,
				'title'          => 'Placeholder — UPC ' . $upc,
				'brand'          => '',
				'category_id'    => 0,
				'record_status'  => 'admin_review',
				'primary_source' => 'dealer_feed',
				'last_updated'   => date( 'Y-m-d H:i:s' ),
			]);
		}
		catch ( \Exception $e )
		{
			/* Swallow duplicate key — product may have been added by another
			 * admin in a race. Continue with the sweep below. */
		}

		\IPS\Db::i()->delete( 'gd_unmatched_upcs', [ 'upc=?', $upc ]);

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=unmatched' ),
			'Placeholder product created in catalog — edit in Master Catalog to complete'
		);
	}
}

class unmatched extends _unmatched {}
