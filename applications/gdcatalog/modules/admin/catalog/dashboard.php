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

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\gdcatalog\Feed\Distributor;
use IPS\gdcatalog\Feed\Importer;
use IPS\gdcatalog\Catalog\Product;
use IPS\gdcatalog\Catalog\Category;
use IPS\gdcatalog\Search\OpenSearchIndexer;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class dashboard extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
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

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_dash_title' );

		$html = '<div class="ipsBox"><h2 class="ipsBox_title">GD Catalog Dashboard</h2><div class="ipsBox_content">';

		/* Summary cards */
		$html .= '<div class="ipsGrid ipsGrid--3">';
		$html .= '<div class="ipsBox ipsBox--alt ipsPad"><h3 class="ipsType_sectionTitle">Total Products</h3>';
		$html .= '<div class="ipsType_large">' . number_format( $totalProducts ) . '</div>';
		$html .= '<div class="ipsType_light">Active: ' . number_format( $activeProducts ) . ' | Review: ' . number_format( $reviewProducts ) . '</div></div>';
		$html .= '<div class="ipsBox ipsBox--alt ipsPad"><h3 class="ipsType_sectionTitle">OpenSearch</h3>';
		if ( $osExists )
		{
			$html .= '<div class="ipsType_large">' . number_format( $osStats['doc_count'] ) . '</div>';
			$html .= '<div class="ipsType_light">Documents indexed</div>';
		}
		else
		{
			$html .= '<div class="ipsType_warning">Index not found</div>';
		}
		$html .= '</div>';
		$html .= '<div class="ipsBox ipsBox--alt ipsPad"><h3 class="ipsType_sectionTitle">Pending Actions</h3>';
		$html .= '<div class="ipsType_light">';
		$html .= 'Compliance flags: <strong>' . (int) $pendingCompliance . '</strong><br>';
		$html .= 'Feed conflicts: <strong>' . (int) $pendingConflicts . '</strong><br>';
		$html .= 'Locked fields: <strong>' . (int) $lockedFields . '</strong><br>';
		$html .= 'Reindex queue: <strong>' . (int) $reindexQueue . '</strong>';
		$html .= '</div></div></div>';

		/* Action buttons */
		$rebuildUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=dashboard&do=rebuildIndex' )->csrf();
		$html .= '<div class="ipsPad ipsGap_3"><a href="' . $rebuildUrl . '" class="ipsButton ipsButton--primary" data-confirm>Rebuild Index</a>';
		if ( $reindexQueue > 0 )
		{
			$queueUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=dashboard&do=processQueue' )->csrf();
			$html .= ' <a href="' . $queueUrl . '" class="ipsButton ipsButton--normal">Process Queue (' . (int) $reindexQueue . ')</a>';
		}
		$html .= '</div>';

		/* Distributor stats table */
		$html .= '<h3 class="ipsType_sectionTitle ipsPad_top">By Distributor</h3>';
		$html .= '<div class="ipsTable ipsTable_zebra"><div class="ipsTable_header"><div class="ipsTable_row">';
		$html .= '<div class="ipsTable_cell" style="width:5%">#</div>';
		$html .= '<div class="ipsTable_cell" style="width:20%">Distributor</div>';
		$html .= '<div class="ipsTable_cell" style="width:10%">Products</div>';
		$html .= '<div class="ipsTable_cell" style="width:10%">Active</div>';
		$html .= '<div class="ipsTable_cell" style="width:15%">Last Run</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%">Status</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%">Created</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%">Updated</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%">Errors</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%"></div>';
		$html .= '</div></div>';
		foreach ( $distributorStats as $ds )
		{
			$html .= '<div class="ipsTable_row">';
			$html .= '<div class="ipsTable_cell">' . (int) $ds['feed']->priority . '</div>';
			$html .= '<div class="ipsTable_cell"><strong>' . htmlspecialchars( $ds['feed']->feed_name ) . '</strong></div>';
			$html .= '<div class="ipsTable_cell">' . number_format( $ds['product_count'] ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . ( $ds['feed']->active ? '<span class="ipsBadge ipsBadge--positive">Active</span>' : '<span class="ipsBadge ipsBadge--neutral">Inactive</span>' ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . ( $ds['last_log'] ? htmlspecialchars( $ds['last_log']['run_start'] ) : '&mdash;' ) . '</div>';
			if ( $ds['last_log'] )
			{
				$st = $ds['last_log']['status'];
				$badge = match( $st ) { 'completed' => 'ipsBadge--positive', 'failed' => 'ipsBadge--negative', default => 'ipsBadge--warning' };
				$label = match( $st ) { 'completed' => 'OK', 'failed' => 'Failed', default => 'Running' };
				$html .= '<div class="ipsTable_cell"><span class="ipsBadge ' . $badge . '">' . $label . '</span></div>';
				$html .= '<div class="ipsTable_cell">' . (int) $ds['last_log']['records_created'] . '</div>';
				$html .= '<div class="ipsTable_cell">' . (int) $ds['last_log']['records_updated'] . '</div>';
				$html .= '<div class="ipsTable_cell">' . (int) $ds['last_log']['records_errored'] . '</div>';
			}
			else
			{
				$html .= '<div class="ipsTable_cell">&mdash;</div><div class="ipsTable_cell">&mdash;</div><div class="ipsTable_cell">&mdash;</div><div class="ipsTable_cell">&mdash;</div>';
			}
			$html .= '<div class="ipsTable_cell">';
			if ( $ds['feed']->active )
			{
				$importUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=dashboard&do=runImport&id=' . $ds['feed']->id )->csrf();
				$html .= '<a href="' . $importUrl . '" class="ipsButton ipsButton--small ipsButton--primary" data-confirm>Import</a>';
			}
			$html .= '</div></div>';
		}
		$html .= '</div>';

		/* Category counts */
		$html .= '<h3 class="ipsType_sectionTitle ipsPad_top">By Category</h3>';
		$html .= '<div class="ipsTable ipsTable_zebra">';
		foreach ( $categoryCounts as $cc )
		{
			$html .= '<div class="ipsTable_row">';
			$html .= '<div class="ipsTable_cell" style="width:70%">' . htmlspecialchars( $cc['name'] ) . '</div>';
			$html .= '<div class="ipsTable_cell" style="width:30%">' . number_format( $cc['count'] ) . '</div>';
			$html .= '</div>';
		}
		$html .= '</div>';

		$html .= '</div></div>';

		\IPS\Output::i()->output = $html;
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
