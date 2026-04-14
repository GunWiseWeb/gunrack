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

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

use IPS\Dispatcher;
use IPS\Output;
use IPS\Request;
use IPS\Helpers\Form;
use IPS\gdcatalog\Feed\Distributor;
use IPS\gdcatalog\Feed\CategoryMapper;

class feeds extends \IPS\Dispatcher\Controller
{
	protected static $csrfProtected = true;

	public function execute()
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

		Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_feeds_title' );
		Output::i()->output = \IPS\Theme::i()->getTemplate( 'catalog', 'gdcatalog', 'admin' )->feedList( $feeds );
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
