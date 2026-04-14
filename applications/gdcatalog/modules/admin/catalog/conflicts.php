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

class _conflicts extends \IPS\Dispatcher\Controller
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

		$totalPages = (int) ceil( $total / $perPage );

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_conflicts_title' );

		$baseUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=conflicts' );

		$html  = '<h2>Conflict Log (' . (int) $total . ')</h2>';
		$html .= '<form method="get" action="' . htmlspecialchars( (string) $baseUrl ) . '">';
		$html .= '<input type="hidden" name="app" value="gdcatalog">';
		$html .= '<input type="hidden" name="module" value="catalog">';
		$html .= '<input type="hidden" name="controller" value="conflicts">';
		$html .= 'Field: <input type="text" name="field" value="' . htmlspecialchars( $filterField ) . '"> ';
		$html .= 'Source: <input type="text" name="source" value="' . htmlspecialchars( $filterSource ) . '"> ';
		$html .= 'Rule: <input type="text" name="rule" value="' . htmlspecialchars( $filterRule ) . '"> ';
		$html .= 'UPC: <input type="text" name="upc" value="' . htmlspecialchars( $filterUpc ) . '"> ';
		$html .= '<button type="submit">Filter</button>';
		$html .= '</form>';

		$html .= '<table><tr><th>Resolved At</th><th>UPC</th><th>Field</th><th>Winner</th><th>Loser</th><th>Rule</th></tr>';
		foreach ( $entries as $row )
		{
			$html .= '<tr>';
			$html .= '<td>' . htmlspecialchars( $row['resolved_at'] ?? '' ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $row['upc'] ?? '' ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $row['field_name'] ?? '' ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $row['winning_source'] ?? '' ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $row['losing_source'] ?? '' ) . '</td>';
			$html .= '<td>' . htmlspecialchars( $row['rule_applied'] ?? '' ) . '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';

		if ( $totalPages > 1 )
		{
			$html .= '<p>Page ' . $page . ' of ' . $totalPages . ' | ';
			for ( $i = 1; $i <= $totalPages; $i++ )
			{
				$pUrl = $baseUrl->setQueryString( [
					'field' => $filterField, 'source' => $filterSource,
					'rule' => $filterRule, 'upc' => $filterUpc, 'page' => $i,
				] );
				$html .= '<a href="' . htmlspecialchars( (string) $pUrl ) . '">' . $i . '</a> ';
			}
			$html .= '</p>';
		}

		\IPS\Output::i()->output = $html;
	}
}

class conflicts extends _conflicts {}
