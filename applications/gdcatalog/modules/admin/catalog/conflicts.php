<?php
/**
 * @brief       GD Master Catalog — ACP Conflict Log Controller
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Section 2.9: Conflict log browser with filters by field,
 * distributor, date range, and rule applied.
 */

namespace IPS\gdcatalog\modules\admin\catalog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class conflicts extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'catalog_manage' );
		parent::execute();
	}

	/**
	 * Conflict log browser.
	 */
	protected function manage()
	{
		$where = [];

		$filterField = \IPS\Request::i()->field ?? '';
		$filterSource = \IPS\Request::i()->source ?? '';
		$filterRule  = \IPS\Request::i()->rule ?? '';
		$filterUpc   = \IPS\Request::i()->upc ?? '';

		if ( $filterField !== '' )
		{
			$where[] = [ 'field_name=?', $filterField ];
		}
		if ( $filterSource !== '' )
		{
			$where[] = [ 'winning_source=? OR losing_source=?', $filterSource, $filterSource ];
		}
		if ( $filterRule !== '' )
		{
			$where[] = [ 'rule_applied=?', $filterRule ];
		}
		if ( $filterUpc !== '' )
		{
			$where[] = [ 'upc=?', $filterUpc ];
		}

		$page    = max( 1, (int) ( \IPS\Request::i()->page ?? 1 ) );
		$perPage = 50;

		$total = \IPS\Db::i()->select( 'COUNT(*)', 'gd_conflict_log', $where )->first();

		$entries = [];
		foreach (
			\IPS\Db::i()->select( '*', 'gd_conflict_log', $where, 'resolved_at DESC', [ ( $page - 1 ) * $perPage, $perPage ] ) as $row
		)
		{
			$entries[] = $row;
		}

		$totalPages = max( 1, (int) ceil( $total / $perPage ) );
		$baseUrl    = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=conflicts' );

		$paginationHtml = '';
		if ( $totalPages > 1 )
		{
			$paginationHtml .= '<ul class="ipsPagination">';
			for ( $p = 1; $p <= $totalPages; $p++ )
			{
				$cls = ( $p === $page ) ? ' ipsPagination_active' : '';
				$paginationHtml .= '<li class="ipsPagination_page' . $cls . '"><a href="' . $baseUrl . '&page=' . $p . '">' . $p . '</a></li>';
			}
			$paginationHtml .= '</ul>';
		}

		$html = '<div class="ipsBox"><h2 class="ipsBox_title">Conflict Log (' . number_format( $total ) . ')</h2>';

		/* Filter bar */
		$html .= '<form method="get" action="' . $baseUrl . '" class="ipsPad ipsGap_2">';
		$html .= '<input type="text" name="upc" value="' . htmlspecialchars( $filterUpc ) . '" placeholder="UPC" class="ipsField_text" style="width:150px">';
		$html .= '<input type="text" name="field" value="' . htmlspecialchars( $filterField ) . '" placeholder="Field name" class="ipsField_text" style="width:150px">';
		$html .= '<input type="text" name="source" value="' . htmlspecialchars( $filterSource ) . '" placeholder="Distributor" class="ipsField_text" style="width:150px">';
		$html .= '<select name="rule" class="ipsField_select"><option value="">All rules</option>';
		foreach ( [ 'priority' => 'Priority', 'longest' => 'Longest', 'highest_res' => 'Highest Res', 'highest_val' => 'Highest Val', 'flagged_for_review' => 'Flagged', 'any_true' => 'Any True', 'admin_override' => 'Admin Override' ] as $rv => $rl )
		{
			$sel = ( $filterRule === $rv ) ? ' selected' : '';
			$html .= '<option value="' . $rv . '"' . $sel . '>' . $rl . '</option>';
		}
		$html .= '</select>';
		$html .= '<button type="submit" class="ipsButton ipsButton--primary ipsButton--small">Filter</button></form>';

		/* Log table */
		$html .= '<div class="ipsTable ipsTable_zebra"><div class="ipsTable_header"><div class="ipsTable_row">';
		$html .= '<div class="ipsTable_cell" style="width:10%">UPC</div>';
		$html .= '<div class="ipsTable_cell" style="width:10%">Field</div>';
		$html .= '<div class="ipsTable_cell" style="width:12%">Winner</div>';
		$html .= '<div class="ipsTable_cell" style="width:18%">Winner Val</div>';
		$html .= '<div class="ipsTable_cell" style="width:12%">Loser</div>';
		$html .= '<div class="ipsTable_cell" style="width:18%">Loser Val</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%">Rule</div>';
		$html .= '<div class="ipsTable_cell" style="width:12%">Date</div>';
		$html .= '</div></div>';
		foreach ( $entries as $entry )
		{
			$html .= '<div class="ipsTable_row">';
			$html .= '<div class="ipsTable_cell"><code>' . htmlspecialchars( $entry['upc'] ) . '</code></div>';
			$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $entry['field_name'] ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $entry['winning_source'] ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . htmlspecialchars( mb_substr( $entry['winning_value'] ?? '', 0, 80 ) ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $entry['losing_source'] ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . htmlspecialchars( mb_substr( $entry['losing_value'] ?? '', 0, 80 ) ) . '</div>';
			$html .= '<div class="ipsTable_cell"><span class="ipsBadge ipsBadge--neutral">' . htmlspecialchars( $entry['rule_applied'] ) . '</span></div>';
			$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $entry['resolved_at'] ) . '</div>';
			$html .= '</div>';
		}
		if ( \count( $entries ) === 0 )
		{
			$html .= '<div class="ipsTable_row"><div class="ipsTable_cell" colspan="8">No conflict log entries found.</div></div>';
		}
		$html .= '</div>';
		$html .= '<div class="ipsPad">' . $paginationHtml . '</div></div>';

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_conflicts_title' );
		\IPS\Output::i()->output = $html;
	}
}
