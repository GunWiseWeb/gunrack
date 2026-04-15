<?php
/**
 * @brief       GD Dealer Manager — ACP Dealers Controller
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Section 3.12: dealers list with tier / status / listing count,
 * per-dealer detail view, suspend/unsuspend, force feed import.
 */

namespace IPS\gddealer\modules\admin\dealers;

use IPS\gddealer\Dealer\Dealer;
use IPS\gddealer\Feed\Importer;
use IPS\gddealer\Listing\Listing;
use IPS\gddealer\Log\ImportLog;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _dealers extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		parent::execute();
	}

	protected function manage()
	{
		$raw = Dealer::loadAll();

		$dealers = [];
		foreach ( $raw as $dealer )
		{
			$listingCount = 0;
			try
			{
				$listingCount = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings', [ 'dealer_id=?', (int) $dealer->dealer_id ] )->first();
			}
			catch ( \Exception ) {}

			$viewUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=dealers&do=view&id=' . (int) $dealer->dealer_id
			);
			$editUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=dealers&do=edit&id=' . (int) $dealer->dealer_id
			)->csrf();
			$suspendUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=dealers&do=toggleSuspend&id=' . (int) $dealer->dealer_id
			)->csrf();
			$importUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=dealers&do=forceImport&id=' . (int) $dealer->dealer_id
			)->csrf();

			$dealers[] = [
				'dealer_id'        => (int) $dealer->dealer_id,
				'dealer_name'      => (string) $dealer->dealer_name,
				'subscription_tier'=> (string) $dealer->subscription_tier,
				'active'           => (bool) $dealer->active,
				'suspended'        => (bool) $dealer->suspended,
				'listing_count'    => $listingCount,
				'last_run'         => $dealer->last_run ?? null,
				'last_run_status'  => $dealer->last_run_status ?? null,
				'mrr'              => '$' . number_format( $dealer->mrrContribution(), 2 ),
				'view_url'         => $viewUrl,
				'edit_url'         => $editUrl,
				'suspend_url'      => $suspendUrl,
				'import_url'       => $importUrl,
			];
		}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_dealers_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'admin' )->dealerList( $dealers );
	}

	/**
	 * View a single dealer — import log, listings, unmatched summary.
	 */
	protected function view()
	{
		$id = (int) \IPS\Request::i()->id;
		try
		{
			$dealer = Dealer::load( $id );
		}
		catch ( \OutOfRangeException )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD200/1', 404 );
			return;
		}

		$logs = [];
		foreach ( ImportLog::loadForDealer( (int) $dealer->dealer_id, 20 ) as $log )
		{
			$logs[] = [
				'id'                 => (int) $log->id,
				'run_start'          => (string) $log->run_start,
				'run_end'            => (string) ( $log->run_end ?? '' ),
				'status'             => (string) $log->status,
				'records_total'      => (int) $log->records_total,
				'records_created'    => (int) $log->records_created,
				'records_updated'    => (int) $log->records_updated,
				'records_unchanged'  => (int) $log->records_unchanged,
				'records_unmatched'  => (int) $log->records_unmatched,
				'price_drops'        => (int) $log->price_drops,
				'error_log'          => (string) ( $log->error_log ?? '' ),
			];
		}

		$listings = [];
		foreach ( Listing::loadForDealer( (int) $dealer->dealer_id, 0, 25 ) as $l )
		{
			$listings[] = [
				'upc'            => (string) $l->upc,
				'dealer_price'   => '$' . number_format( (float) $l->dealer_price, 2 ),
				'in_stock'       => (bool) $l->in_stock,
				'listing_status' => (string) $l->listing_status,
				'last_updated'   => (string) ( $l->last_seen_in_feed ?? '' ),
			];
		}

		$dealerData = [
			'dealer_id'         => (int) $dealer->dealer_id,
			'dealer_name'       => (string) $dealer->dealer_name,
			'subscription_tier' => (string) $dealer->subscription_tier,
			'feed_url'          => (string) ( $dealer->feed_url ?? '' ),
			'feed_format'       => strtoupper( (string) $dealer->feed_format ),
			'import_schedule'   => (string) $dealer->import_schedule,
			'active'            => (bool) $dealer->active,
			'suspended'         => (bool) $dealer->suspended,
			'last_run'          => $dealer->last_run ?? null,
			'last_record_count' => (int) $dealer->last_record_count,
			'api_key'           => (string) ( $dealer->api_key ?? '' ),
			'mrr'               => '$' . number_format( $dealer->mrrContribution(), 2 ),
		];

		$backUrl    = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers' );
		$editUrl    = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=edit&id=' . (int) $dealer->dealer_id )->csrf();
		$importUrl  = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=forceImport&id=' . (int) $dealer->dealer_id )->csrf();
		$suspendUrl = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=toggleSuspend&id=' . (int) $dealer->dealer_id )->csrf();

		\IPS\Output::i()->title  = $dealerData['dealer_name'];
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'admin' )->dealerDetail(
			$dealerData, $logs, $listings, $backUrl, $editUrl, $importUrl, $suspendUrl
		);
	}

	/**
	 * Edit a dealer's feed configuration.
	 */
	protected function edit()
	{
		\IPS\Session::i()->csrfCheck();

		$id     = (int) \IPS\Request::i()->id;
		$dealer = Dealer::load( $id );

		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_dealer_name', $dealer->dealer_name, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Select( 'gddealer_dealer_tier', $dealer->subscription_tier, TRUE, [
			'options' => [
				'basic'      => 'Basic',
				'pro'        => 'Pro',
				'enterprise' => 'Enterprise',
				'founding'   => 'Founding',
			],
		] ) );
		$form->add( new \IPS\Helpers\Form\Url( 'gddealer_dealer_feed_url', $dealer->feed_url, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Select( 'gddealer_dealer_feed_format', $dealer->feed_format, TRUE, [
			'options' => [ 'xml' => 'XML', 'json' => 'JSON', 'csv' => 'CSV' ],
		] ) );
		$form->add( new \IPS\Helpers\Form\Select( 'gddealer_dealer_auth_type', $dealer->auth_type, TRUE, [
			'options' => [ 'none' => 'None', 'basic' => 'Basic Auth', 'apikey' => 'API Key', 'ftp' => 'FTP' ],
		] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_dealer_auth_credentials', $dealer->getCredentials() ?? '', FALSE, [
			'placeholder' => 'JSON: {"username":"...","password":"..."} or {"api_key":"..."}',
		] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_dealer_field_mapping', $dealer->field_mapping ?? '', FALSE, [
			'rows'        => 10,
			'placeholder' => '{"DEALER_FIELD":"canonical_field", "UPC_CODE":"upc", "PRICE":"dealer_price", ...}',
		] ) );
		$form->add( new \IPS\Helpers\Form\Select( 'gddealer_dealer_import_schedule', $dealer->import_schedule, TRUE, [
			'options' => [
				'15min' => 'Every 15 minutes',
				'30min' => 'Every 30 minutes',
				'1hr'   => 'Hourly',
				'6hr'   => 'Every 6 hours',
				'daily' => 'Daily',
			],
		] ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'gddealer_dealer_active', $dealer->active, FALSE ) );

		if ( $values = $form->values() )
		{
			$dealer->dealer_name       = $values['gddealer_dealer_name'];
			$dealer->subscription_tier = $values['gddealer_dealer_tier'];
			$dealer->feed_url          = (string) $values['gddealer_dealer_feed_url'];
			$dealer->feed_format       = $values['gddealer_dealer_feed_format'];
			$dealer->auth_type         = $values['gddealer_dealer_auth_type'];
			$dealer->import_schedule   = $values['gddealer_dealer_import_schedule'];
			$dealer->active            = (int) $values['gddealer_dealer_active'];

			$creds = trim( $values['gddealer_dealer_auth_credentials'] );
			$dealer->setCredentials( $creds !== '' ? $creds : null );

			$mapJson = trim( $values['gddealer_dealer_field_mapping'] );
			$dealer->field_mapping = ( $mapJson !== '' && json_decode( $mapJson ) !== null ) ? $mapJson : null;

			$dealer->save();

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=view&id=' . (int) $dealer->dealer_id ),
				'saved'
			);
		}

		\IPS\Output::i()->title  = $dealer->dealer_name;
		\IPS\Output::i()->output = (string) $form;
	}

	/**
	 * Toggle dealer suspension. Suspended dealers' listings become invisible.
	 */
	protected function toggleSuspend()
	{
		\IPS\Session::i()->csrfCheck();

		$dealer = Dealer::load( (int) \IPS\Request::i()->id );
		$dealer->suspended = $dealer->suspended ? 0 : 1;
		$dealer->save();

		/* Cascade to listings */
		if ( $dealer->suspended )
		{
			\IPS\Db::i()->update(
				'gd_dealer_listings',
				[ 'listing_status' => Listing::STATUS_SUSPENDED ],
				[ 'dealer_id=? AND listing_status<>?', (int) $dealer->dealer_id, Listing::STATUS_SUSPENDED ]
			);
		}
		else
		{
			\IPS\Db::i()->update(
				'gd_dealer_listings',
				[ 'listing_status' => Listing::STATUS_ACTIVE ],
				[ 'dealer_id=? AND listing_status=?', (int) $dealer->dealer_id, Listing::STATUS_SUSPENDED ]
			);
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=view&id=' . (int) $dealer->dealer_id ),
			$dealer->suspended ? 'Dealer suspended' : 'Dealer unsuspended'
		);
	}

	/**
	 * Force a feed import immediately for a dealer.
	 */
	protected function forceImport()
	{
		\IPS\Session::i()->csrfCheck();

		$dealer = Dealer::load( (int) \IPS\Request::i()->id );
		$log    = Importer::run( $dealer );

		$msg = $log->status === 'completed'
			? "Import complete — {$log->records_created} new, {$log->records_updated} updated, {$log->records_unmatched} unmatched"
			: 'Import failed: ' . ( $log->error_log ?? 'unknown error' );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=view&id=' . (int) $dealer->dealer_id ),
			$msg
		);
	}
}

class dealers extends _dealers {}
