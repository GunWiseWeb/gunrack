<?php
/**
 * @brief       GD Master Catalog — ACP Dashboard Controller
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Section 2.9: Total product count, per-distributor stats, manual
 * import trigger, OpenSearch status, rebuild index button.
 */

namespace IPS\gdcatalog\modules\admin\catalog;

use IPS\gdcatalog\Feed\Distributor;
use IPS\gdcatalog\Feed\Importer;
use IPS\gdcatalog\Catalog\Product;
use IPS\gdcatalog\Catalog\Category;
use IPS\gdcatalog\Search\OpenSearchIndexer;

class _dashboard extends \IPS\Dispatcher\Controller
{
	protected static $csrfProtected = true;

	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'catalog_manage' );
		parent::execute();
	}

	/**
	 * Dashboard overview.
	 */
	protected function manage()
	{
		/* Total product counts */
		$totalProducts = \IPS\Db::i()->select( 'COUNT(*)', 'gd_catalog' )->first();
		$activeProducts = \IPS\Db::i()->select( 'COUNT(*)', 'gd_catalog', [ 'record_status=?', 'active' ] )->first();
		$reviewProducts = \IPS\Db::i()->select( 'COUNT(*)', 'gd_catalog', [ 'record_status=?', 'admin_review' ] )->first();

		/* Per-category counts */
		$categoryCounts = [];
		foreach ( Category::roots() as $cat )
		{
			$count = \IPS\Db::i()->select( 'COUNT(*)', 'gd_catalog', [ 'category_id=?', $cat->id ] )->first();
			$categoryCounts[] = [ 'name' => $cat->name, 'count' => $count ];
		}

		/* Per-distributor stats from latest import logs */
		$distributorStats = [];
		foreach ( Distributor::loadAll() as $feed )
		{
			$lastLog = null;
			try
			{
				$lastLog = \IPS\Db::i()->select(
					'*', 'gd_import_log',
					[ 'feed_id=?', $feed->id ],
					'run_start DESC', [ 0, 1 ]
				)->first();
			}
			catch ( \UnderflowException ) {}

			$productCount = \IPS\Db::i()->select(
				'COUNT(*)', 'gd_catalog',
				[ 'FIND_IN_SET(?, distributor_sources)', $feed->distributor ]
			)->first();

			$distributorStats[] = [
				'feed'          => $feed,
				'product_count' => $productCount,
				'last_log'      => $lastLog,
			];
		}

		/* OpenSearch stats */
		$osIndexer = OpenSearchIndexer::i();
		$osStats   = $osIndexer->getStats();
		$osExists  = $osIndexer->indexExists();

		/* Pending items */
		$pendingConflicts  = \IPS\Db::i()->select( 'COUNT(*)', 'gd_feed_conflicts', [ 'status=?', 'pending' ] )->first();
		$pendingCompliance = \IPS\Db::i()->select( 'COUNT(*)', 'gd_compliance_flags', [ 'status=?', 'pending_review' ] )->first();
		$lockedFields      = \IPS\Db::i()->select( 'COUNT(*)', 'gd_field_locks' )->first();
		$reindexQueue      = \IPS\Db::i()->select( 'COUNT(*)', 'gd_reindex_queue' )->first();

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_dash_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'catalog', 'gdcatalog', 'admin' )->dashboard(
			$totalProducts, $activeProducts, $reviewProducts,
			$categoryCounts, $distributorStats,
			$osExists, $osStats,
			$pendingConflicts, $pendingCompliance, $lockedFields, $reindexQueue
		);
	}

	/**
	 * Manual import trigger — runs a single feed immediately.
	 */
	protected function runImport()
	{
		\IPS\Session::i()->csrfCheck();

		$feedId = (int) \IPS\Request::i()->id;
		$feed   = Distributor::load( $feedId );

		$log = Importer::run( $feed );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=dashboard' ),
			$log->status === 'completed'
				? "Import completed: {$log->records_created} created, {$log->records_updated} updated"
				: "Import failed: " . ( $log->error_log ?? 'unknown error' )
		);
	}

	/**
	 * Rebuild OpenSearch index.
	 */
	protected function rebuildIndex()
	{
		\IPS\Session::i()->csrfCheck();

		$indexer = OpenSearchIndexer::i();
		$count   = $indexer->rebuildIndex();

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=dashboard' ),
			"OpenSearch index rebuilt: {$count} documents indexed"
		);
	}

	/**
	 * Process the reindex queue now.
	 */
	protected function processQueue()
	{
		\IPS\Session::i()->csrfCheck();

		$indexer = OpenSearchIndexer::i();
		$count   = $indexer->processQueue( 2000 );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=dashboard' ),
			"Processed reindex queue: {$count} documents indexed"
		);
	}
}
