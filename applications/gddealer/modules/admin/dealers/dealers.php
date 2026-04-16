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

			$slug = (string) ( $dealer->dealer_slug ?? '' );
			$profileUrl = $slug !== ''
				? (string) \IPS\Http\Url::internal(
					'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ),
					'front', 'dealers_profile', [ $slug ]
				)
				: '';

			$dealers[] = [
				'dealer_id'        => (int) $dealer->dealer_id,
				'dealer_name'      => (string) $dealer->dealer_name,
				'dealer_slug'      => $slug,
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
				'profile_url'      => $profileUrl,
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

		$slug       = (string) ( $dealer->dealer_slug ?? '' );
		$profileUrl = $slug !== ''
			? (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ),
				'front', 'dealers_profile', [ $slug ]
			)
			: '';

		$dealerData = [
			'dealer_id'         => (int) $dealer->dealer_id,
			'dealer_name'       => (string) $dealer->dealer_name,
			'dealer_slug'       => $slug,
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
			'profile_url'       => $profileUrl,
		];

		$backUrl    = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers' );
		$editUrl    = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=edit&id=' . (int) $dealer->dealer_id )->csrf();
		$importUrl  = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=forceImport&id=' . (int) $dealer->dealer_id )->csrf();
		$suspendUrl = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=toggleSuspend&id=' . (int) $dealer->dealer_id )->csrf();

		\IPS\Output::i()->title  = $dealerData['dealer_name'];
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'admin' )->dealerDetail(
			$dealerData, $logs, $listings, $backUrl, $editUrl, $importUrl, $suspendUrl, $invoiceUrl
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
	 * assigns the tier-specific Dealers secondary group, and
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

			$dealerName = (string) $values['gddealer_onboard_name'];

			/* Build a URL-safe slug from the dealer name. Uniqueness is
			   enforced by the uq_dealer_slug index; we append -1, -2, ...
			   until a free slug is found. */
			$slug = strtolower( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $dealerName ) ) );
			$slug = trim( $slug, '-' );
			if ( $slug === '' )
			{
				$slug = 'dealer-' . $memberId;
			}
			$base = $slug;
			$i    = 1;
			while ( true )
			{
				try
				{
					$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config', [ 'dealer_slug=?', $slug ] )->first();
				}
				catch ( \Exception )
				{
					$exists = 0;
				}
				if ( $exists === 0 ) { break; }
				$slug = $base . '-' . $i++;
			}

			\IPS\Db::i()->insert( 'gd_dealer_feed_config', [
				'dealer_id'         => $memberId,
				'dealer_name'       => $dealerName,
				'dealer_slug'       => $slug,
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

			$this->assignDealersGroup( $member, $tierKey );

			try
			{
				\IPS\Email::buildFromTemplate( 'gddealer', 'dealerWelcome', [
					'name'          => $member->name,
					'api_key'       => $apiKey,
					'contact_email' => (string) ( \IPS\Settings::i()->gddealer_help_contact ?: 'dealers@gunrack.deals' ),
				], \IPS\Email::TYPE_TRANSACTIONAL )->send( $member );
			}
			catch ( \Exception ) {}

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
	 * Add the tier-specific Dealers secondary group to a member.
	 * Uses Dealer::groupIdForTier() to resolve the correct group.
	 * No-op if the setting is unconfigured or the member already
	 * belongs to that group.
	 */
	protected function assignDealersGroup( \IPS\Member $member, string $tier ): void
	{
		$groupId = Dealer::groupIdForTier( $tier );
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

	/* =============== Disputed Reviews Queue =============== */

	/**
	 * Show every review in 'pending_admin' or 'pending_customer' status.
	 * Admin sees both sides of each dispute.
	 */
	protected function disputes()
	{
		$rows = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_ratings',
				[ \IPS\Db::i()->in( 'dispute_status', [ 'pending_admin', 'pending_customer' ] ) ],
				'dispute_at ASC'
			) as $r )
			{
				$dealerName = '';
				try
				{
					$dealerName = (string) \IPS\Db::i()->select( 'dealer_name', 'gd_dealer_feed_config', [ 'dealer_id=?', (int) $r['dealer_id'] ] )->first();
				}
				catch ( \Exception ) {}

				$memberName = '';
				try
				{
					$m = \IPS\Member::load( (int) $r['member_id'] );
					if ( $m->member_id ) { $memberName = (string) $m->name; }
				}
				catch ( \Exception ) {}

				$rows[] = [
					'id'                    => (int) $r['id'],
					'dealer_id'             => (int) $r['dealer_id'],
					'dealer_name'           => $dealerName,
					'member_id'             => (int) $r['member_id'],
					'member_name'           => $memberName,
					'rating_pricing'        => (int) $r['rating_pricing'],
					'rating_shipping'       => (int) $r['rating_shipping'],
					'rating_service'        => (int) $r['rating_service'],
					'review_body'           => (string) ( $r['review_body'] ?? '' ),
					'dealer_response'       => (string) ( $r['dealer_response'] ?? '' ),
					'dispute_status'        => (string) ( $r['dispute_status'] ?? '' ),
					'dispute_reason'        => (string) ( $r['dispute_reason'] ?? '' ),
					'dispute_evidence'      => (string) ( $r['dispute_evidence'] ?? '' ),
					'dispute_at'            => (string) ( $r['dispute_at'] ?? '' ),
					'dispute_deadline'      => (string) ( $r['dispute_deadline'] ?? '' ),
					'customer_response'     => (string) ( $r['customer_response'] ?? '' ),
					'customer_evidence'     => (string) ( $r['customer_evidence'] ?? '' ),
					'customer_responded_at' => (string) ( $r['customer_responded_at'] ?? '' ),
					'created_at'            => (string) $r['created_at'],
					'uphold_url'            => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dealers&do=upholdDispute&id=' . (int) $r['id']
					)->csrf(),
					'dismiss_url'           => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dealers&do=dismissDispute&id=' . (int) $r['id']
					)->csrf(),
					'request_edit_url'      => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dealers&do=requestEdit&id=' . (int) $r['id']
					)->csrf(),
				];
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_disputes_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'admin' )->disputeQueue( $rows );
	}

	/**
	 * Uphold the dealer's contest. Review stays visible with a badge but
	 * is excluded from rating averages. Dealer cannot be contested twice
	 * for the same review.
	 */
	protected function upholdDispute()
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) \IPS\Request::i()->id;

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_ratings', [
				'dispute_status'      => 'resolved_dealer',
				'dispute_outcome'     => 'upheld',
				'dispute_resolved_by' => (int) \IPS\Member::loggedIn()->member_id,
				'dispute_resolved_at' => date( 'Y-m-d H:i:s' ),
			], [ 'id=? AND ' . \IPS\Db::i()->in( 'dispute_status', [ 'pending_admin', 'pending_customer' ] ), $id ] );
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=disputes' ),
			'gddealer_dispute_upheld'
		);
	}

	/**
	 * Dismiss the dealer's contest. Review stands; dealer cannot re-dispute.
	 * Dealer receives a notification.
	 */
	protected function dismissDispute()
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) \IPS\Request::i()->id;

		$dealerId = 0;
		try
		{
			$row = \IPS\Db::i()->select( 'dealer_id', 'gd_dealer_ratings', [ 'id=?', $id ] )->first();
			$dealerId = (int) $row;
		}
		catch ( \Exception ) {}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_ratings', [
				'dispute_status'      => 'dismissed',
				'dispute_outcome'     => 'dismissed',
				'dispute_resolved_by' => (int) \IPS\Member::loggedIn()->member_id,
				'dispute_resolved_at' => date( 'Y-m-d H:i:s' ),
			], [ 'id=? AND ' . \IPS\Db::i()->in( 'dispute_status', [ 'pending_admin', 'pending_customer' ] ), $id ] );
		}
		catch ( \Exception ) {}

		/* Notify the dealer that their contest was dismissed. */
		if ( $dealerId > 0 )
		{
			try
			{
				$dealerMember = \IPS\Member::load( $dealerId );
				if ( $dealerMember->member_id )
				{
					\IPS\Email::buildFromTemplate( 'gddealer', 'disputeDismissed', [
						'name' => $dealerMember->name,
					], \IPS\Email::TYPE_TRANSACTIONAL )->send( $dealerMember );
				}
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=disputes' ),
			'gddealer_dispute_dismissed'
		);
	}

	/**
	 * Send the dispute back to the customer with an admin note asking
	 * them to update the review. Resets dispute_status to
	 * pending_customer and extends the deadline by 30 days.
	 */
	protected function requestEdit()
	{
		\IPS\Session::i()->csrfCheck();
		$id       = (int) \IPS\Request::i()->id;
		$adminNote = trim( (string) ( \IPS\Request::i()->admin_note ?? '' ) );

		try
		{
			$row = \IPS\Db::i()->select( '*', 'gd_dealer_ratings',
				[ 'id=? AND ' . \IPS\Db::i()->in( 'dispute_status', [ 'pending_admin', 'pending_customer' ] ), $id ]
			)->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=disputes' )
			);
			return;
		}

		$deadline = date( 'Y-m-d H:i:s', strtotime( '+30 days' ) );

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_ratings', [
				'dispute_status'   => 'pending_customer',
				'dispute_deadline' => $deadline,
				'dispute_reason'   => trim( (string) ( $row['dispute_reason'] ?? '' ) ) . ( $adminNote !== '' ? "\n\n[Admin note: {$adminNote}]" : '' ),
			], [ 'id=?', $id ] );
		}
		catch ( \Exception ) {}

		/* Re-notify the customer. */
		if ( (int) ( $row['member_id'] ?? 0 ) > 0 )
		{
			try
			{
				$customer = \IPS\Member::load( (int) $row['member_id'] );
				$slug = '';
				try
				{
					$slug = (string) \IPS\Db::i()->select( 'dealer_slug', 'gd_dealer_feed_config', [ 'dealer_id=?', (int) $row['dealer_id'] ] )->first();
				}
				catch ( \Exception ) {}
				$dealerName = '';
				try
				{
					$dealerName = (string) \IPS\Db::i()->select( 'dealer_name', 'gd_dealer_feed_config', [ 'dealer_id=?', (int) $row['dealer_id'] ] )->first();
				}
				catch ( \Exception ) {}

				if ( $customer->member_id && $slug !== '' )
				{
					$respondUrl = (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ) . '&dispute=' . $id
					);
					\IPS\Email::buildFromTemplate( 'gddealer', 'disputeNotify', [
						'name'        => $customer->name,
						'dealer_name' => $dealerName,
						'reason'      => (string) ( $row['dispute_reason'] ?? '' ) . ( $adminNote !== '' ? "\n\n[Admin note: {$adminNote}]" : '' ),
						'deadline'    => date( 'F j, Y', strtotime( $deadline ) ),
						'respond_url' => $respondUrl,
					], \IPS\Email::TYPE_TRANSACTIONAL )->send( $customer );
				}
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=disputes' ),
			'gddealer_dispute_edit_requested'
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
