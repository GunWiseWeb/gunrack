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

		$onboardUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=dealers&do=manualOnboard'
		);

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
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'admin' )->dealerList( $dealers, $onboardUrl );
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

		$trialRaw      = $dealer->trial_expires_at ?? null;
		$trialExpires  = $trialRaw ? (string) $trialRaw : '';
		$trialSoon     = false;
		if ( $trialRaw )
		{
			try
			{
				$dt = new \DateTime( $trialRaw );
				$now = new \DateTime();
				$trialSoon = ( $dt > $now && $dt->getTimestamp() - $now->getTimestamp() <= 30 * 86400 );
			}
			catch ( \Exception ) {}
		}

		$invoiceUrl = (string) \IPS\Http\Url::internal(
			'app=nexus&module=customers&controller=search&do=view&id=' . (int) $dealer->dealer_id
		);

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
			'trial_expires_at'  => $trialExpires,
			'trial_expires_soon'=> $trialSoon,
			'billing_note'      => (string) ( $dealer->billing_note ?? '' ),
			'invoice_url'       => $invoiceUrl,
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
		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();
		}

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

		$existingTrialDate = null;
		if ( $dealer->trial_expires_at )
		{
			try { $existingTrialDate = new \IPS\DateTime( $dealer->trial_expires_at ); }
			catch ( \Exception ) {}
		}
		$form->add( new \IPS\Helpers\Form\Date( 'gddealer_onboard_trial_expires', $existingTrialDate, FALSE ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_onboard_billing_note', (string) ( $dealer->billing_note ?? '' ), FALSE, [ 'rows' => 3 ] ) );

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

			if ( $values['gddealer_onboard_trial_expires'] instanceof \IPS\DateTime )
			{
				$dealer->trial_expires_at = $values['gddealer_onboard_trial_expires']->format( 'Y-m-d H:i:s' );
			}
			else
			{
				$dealer->trial_expires_at = null;
			}
			$billingNote = trim( (string) $values['gddealer_onboard_billing_note'] );
			$dealer->billing_note = $billingNote !== '' ? $billingNote : null;

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
	 * Manual onboarding — admin-only stub used until IPS Commerce + Stripe
	 * is live. Creates a gd_dealer_feed_config row for the selected member,
	 * assigns the Dealers secondary group (gddealer_group_id setting), and
	 * generates an API key. feed_url is left NULL, which triggers the
	 * "onboarding incomplete" banner on the frontend dashboard. Once
	 * Commerce is wired (Section 3.3), this becomes a group-promotion
	 * listener instead.
	 */
	protected function manualOnboard()
	{
		$form = new \IPS\Helpers\Form( 'form', 'gddealer_onboard_submit' );
		$form->add( new \IPS\Helpers\Form\Member( 'gddealer_onboard_member', null, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_onboard_name', '', TRUE ) );
		$form->add( new \IPS\Helpers\Form\Select( 'gddealer_onboard_tier', Dealer::TIER_BASIC, TRUE, [
			'options' => [
				Dealer::TIER_BASIC      => 'Basic',
				Dealer::TIER_PRO        => 'Pro',
				Dealer::TIER_ENTERPRISE => 'Enterprise',
				Dealer::TIER_FOUNDING   => 'Founding',
			],
		] ) );
		$form->add( new \IPS\Helpers\Form\Date( 'gddealer_onboard_trial_expires', null, FALSE ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_onboard_billing_note', '', FALSE, [ 'rows' => 3 ] ) );

		if ( $values = $form->values() )
		{
			$member = $values['gddealer_onboard_member'];
			if ( !( $member instanceof \IPS\Member ) || !$member->member_id )
			{
				\IPS\Output::i()->error( 'gddealer_onboard_no_member', '2GDD210/1', 400 );
				return;
			}

			$memberId = (int) $member->member_id;

			try
			{
				$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config', [ 'dealer_id=?', $memberId ] )->first();
			}
			catch ( \Exception )
			{
				$exists = 0;
			}

			if ( $exists > 0 )
			{
				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=view&id=' . $memberId ),
					'gddealer_onboard_existing'
				);
				return;
			}

			$tier    = (string) $values['gddealer_onboard_tier'];
			$tierKey = array_key_exists( $tier, Dealer::$tierSchedules ) ? $tier : Dealer::TIER_BASIC;
			$apiKey  = bin2hex( random_bytes( 24 ) );

			$trialExpires = null;
			if ( $values['gddealer_onboard_trial_expires'] instanceof \IPS\DateTime )
			{
				$trialExpires = $values['gddealer_onboard_trial_expires']->format( 'Y-m-d H:i:s' );
			}

			\IPS\Db::i()->insert( 'gd_dealer_feed_config', [
				'dealer_id'         => $memberId,
				'dealer_name'       => (string) $values['gddealer_onboard_name'],
				'subscription_tier' => $tierKey,
				'feed_url'          => null,
				'feed_format'       => 'xml',
				'auth_type'         => 'none',
				'auth_credentials'  => null,
				'field_mapping'     => null,
				'import_schedule'   => Dealer::$tierSchedules[ $tierKey ],
				'active'            => 0,
				'suspended'         => 0,
				'last_run'          => null,
				'last_run_status'   => null,
				'last_record_count' => 0,
				'api_key'           => $apiKey,
				'created_at'        => date( 'Y-m-d H:i:s' ),
				'trial_expires_at'  => $trialExpires,
				'billing_note'      => trim( (string) $values['gddealer_onboard_billing_note'] ) ?: null,
			]);

			$this->assignDealersGroup( $member );

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=view&id=' . $memberId ),
				'gddealer_onboard_created'
			);
			return;
		}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_onboard_title' );
		\IPS\Output::i()->output = (string) $form;
	}

	/**
	 * Add the Dealers secondary group to a member based on the
	 * gddealer_group_id setting. No-op if the setting is unconfigured
	 * or the member is already a member of that group.
	 */
	protected function assignDealersGroup( \IPS\Member $member ): void
	{
		$groupId = (int) \IPS\Settings::i()->gddealer_group_id;
		if ( $groupId <= 0 )
		{
			return;
		}

		$current = $member->mgroup_others ? explode( ',', (string) $member->mgroup_others ) : [];
		$current = array_filter( array_map( 'intval', $current ) );
		if ( !in_array( $groupId, $current, true ) )
		{
			$current[] = $groupId;
			$member->mgroup_others = implode( ',', $current );
			$member->save();
		}
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
