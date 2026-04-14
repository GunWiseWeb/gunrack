<?php
/**
 * @brief       GD Master Catalog — ACP Feed Configuration Controller
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Lists all six distributor feed configs, provides edit form for
 * URL, auth, field mapping, category mapping, schedule, and
 * conflict detection toggles.
 */

namespace IPS\gdcatalog\modules\admin\catalog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher;
use IPS\Output;
use IPS\Request;
use IPS\Helpers\Form;
use IPS\gdcatalog\Feed\Distributor;
use IPS\gdcatalog\Feed\CategoryMapper;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class feeds extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		Dispatcher::i()->checkAcpPermission( 'catalog_manage' );
		parent::execute();
	}

	/**
	 * Feed list — default view.
	 */
	protected function manage()
	{
		$feeds = Distributor::loadAll();

		Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_feeds_title' );

		$html = '<div class="ipsBox"><h2 class="ipsBox_title">Distributor Feeds</h2>';
		$html .= '<div class="ipsTable ipsTable_zebra"><div class="ipsTable_header"><div class="ipsTable_row">';
		$html .= '<div class="ipsTable_cell" style="width:5%">#</div>';
		$html .= '<div class="ipsTable_cell" style="width:18%">Name</div>';
		$html .= '<div class="ipsTable_cell" style="width:14%">Distributor</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%">Format</div>';
		$html .= '<div class="ipsTable_cell" style="width:10%">Schedule</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%">Active</div>';
		$html .= '<div class="ipsTable_cell" style="width:14%">Last Run</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%">Records</div>';
		$html .= '<div class="ipsTable_cell" style="width:8%">Status</div>';
		$html .= '<div class="ipsTable_cell" style="width:7%"></div>';
		$html .= '</div></div>';
		foreach ( $feeds as $feed )
		{
			$html .= '<div class="ipsTable_row">';
			$html .= '<div class="ipsTable_cell">' . (int) $feed->priority . '</div>';
			$html .= '<div class="ipsTable_cell"><strong>' . htmlspecialchars( $feed->feed_name ) . '</strong></div>';
			$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $feed->distributor ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . strtoupper( htmlspecialchars( $feed->feed_format ) ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $feed->import_schedule ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . ( $feed->active ? '<span class="ipsBadge ipsBadge--positive">Active</span>' : '<span class="ipsBadge ipsBadge--neutral">Inactive</span>' ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . ( $feed->last_run ? htmlspecialchars( $feed->last_run ) : '&mdash;' ) . '</div>';
			$html .= '<div class="ipsTable_cell">' . number_format( (int) $feed->last_record_count ) . '</div>';
			if ( $feed->last_run_status === 'completed' )
			{
				$html .= '<div class="ipsTable_cell"><span class="ipsBadge ipsBadge--positive">OK</span></div>';
			}
			elseif ( $feed->last_run_status === 'failed' )
			{
				$html .= '<div class="ipsTable_cell"><span class="ipsBadge ipsBadge--negative">Failed</span></div>';
			}
			elseif ( $feed->last_run_status === 'running' )
			{
				$html .= '<div class="ipsTable_cell"><span class="ipsBadge ipsBadge--warning">Running</span></div>';
			}
			else
			{
				$html .= '<div class="ipsTable_cell">&mdash;</div>';
			}
			$editUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=feeds&do=edit&id=' . (int) $feed->id )->csrf();
			$html .= '<div class="ipsTable_cell"><a href="' . $editUrl . '" class="ipsButton ipsButton--small ipsButton--primary">Edit</a></div>';
			$html .= '</div>';
		}
		$html .= '</div>';
		$html .= '<div class="ipsBox_content ipsPad"><p class="ipsType_light">Configure feed URLs, authentication, field mappings, and import schedules for each distributor.</p></div>';
		$html .= '</div>';

		Output::i()->output = $html;
	}

	/**
	 * Edit a single feed configuration.
	 */
	protected function edit()
	{
		\IPS\Session::i()->csrfCheck();

		$id   = (int) Request::i()->id;
		$feed = Distributor::load( $id );

		$form = new Form;

		$form->add( new Form\Text( 'gdcatalog_feed_name', $feed->feed_name, TRUE ) );
		$form->add( new Form\Url( 'gdcatalog_feed_url', $feed->feed_url, FALSE ) );
		$form->add( new Form\Select( 'gdcatalog_feed_format', $feed->feed_format, TRUE, [
			'options' => [ 'xml' => 'XML', 'json' => 'JSON', 'csv' => 'CSV' ],
		] ) );
		$form->add( new Form\Select( 'gdcatalog_feed_auth_type', $feed->auth_type, TRUE, [
			'options' => [
				'none'   => 'None',
				'basic'  => 'Basic Auth',
				'apikey' => 'API Key',
				'ftp'    => 'FTP Credentials',
			],
		] ) );
		$form->add( new Form\TextArea( 'gdcatalog_feed_auth_credentials', $feed->getCredentials() ?? '', FALSE, [
			'placeholder' => 'JSON: {"username":"...","password":"..."} or {"api_key":"..."}',
		] ) );
		$form->add( new Form\Select( 'gdcatalog_feed_schedule', $feed->import_schedule, TRUE, [
			'options' => [
				'15min' => 'Every 15 minutes',
				'30min' => 'Every 30 minutes',
				'1hr'   => 'Every hour',
				'6hr'   => 'Every 6 hours',
				'daily' => 'Daily',
			],
		] ) );
		$form->add( new Form\YesNo( 'gdcatalog_feed_active', $feed->active, FALSE ) );

		/* Field mapping — JSON textarea */
		$form->addHeader( 'gdcatalog_feed_field_mapping' );
		$form->add( new Form\TextArea( 'gdcatalog_feed_field_mapping_json', $feed->field_mapping ?? '', FALSE, [
			'rows'        => 12,
			'placeholder' => '{"DIST_FIELD":"canonical_field", "PROD_NAME":"title", ...}',
		] ) );

		/* Category mapping — JSON textarea */
		$form->addHeader( 'gdcatalog_feed_category_mapping' );
		$form->add( new Form\TextArea( 'gdcatalog_feed_category_mapping_json', $feed->category_mapping ?? '', FALSE, [
			'rows'        => 12,
			'placeholder' => '{"DIST CATEGORY":"canonical-slug", "HANDGUNS":"handguns", ...}',
		] ) );

		/* Conflict detection field toggles — Section 2.11.2 */
		$form->addHeader( 'gdcatalog_feed_conflict_detection' );
		$conflictFields = $feed->getConflictDetectionFields();
		foreach ( $conflictFields as $fieldName => $enabled )
		{
			$form->add( new Form\YesNo(
				'gdcatalog_conflict_' . $fieldName,
				$enabled,
				FALSE
			) );
		}

		if ( $values = $form->values() )
		{
			$feed->feed_name       = $values['gdcatalog_feed_name'];
			$feed->feed_url        = (string) $values['gdcatalog_feed_url'];
			$feed->feed_format     = $values['gdcatalog_feed_format'];
			$feed->auth_type       = $values['gdcatalog_feed_auth_type'];
			$feed->import_schedule = $values['gdcatalog_feed_schedule'];
			$feed->active          = (int) $values['gdcatalog_feed_active'];

			/* Encrypt credentials */
			$creds = trim( $values['gdcatalog_feed_auth_credentials'] );
			$feed->setCredentials( $creds !== '' ? $creds : null );

			/* Field mapping JSON */
			$fieldJson = trim( $values['gdcatalog_feed_field_mapping_json'] );
			$feed->field_mapping = ( $fieldJson !== '' && json_decode( $fieldJson ) !== null )
				? $fieldJson
				: null;

			/* Category mapping JSON */
			$catJson = trim( $values['gdcatalog_feed_category_mapping_json'] );
			$feed->category_mapping = ( $catJson !== '' && json_decode( $catJson ) !== null )
				? $catJson
				: null;

			/* Conflict detection toggles */
			$updatedConflict = [];
			foreach ( $conflictFields as $fieldName => $default )
			{
				$updatedConflict[$fieldName] = (bool) $values['gdcatalog_conflict_' . $fieldName];
			}
			$feed->setConflictDetectionFields( $updatedConflict );

			$feed->save();

			Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=feeds' ),
				'saved'
			);
		}

		Output::i()->title  = $feed->feed_name;
		Output::i()->output = (string) $form;
	}
}
