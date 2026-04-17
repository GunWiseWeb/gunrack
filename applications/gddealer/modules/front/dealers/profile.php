<?php
/**
 * @brief       GD Dealer Manager — Public Dealer Profile Page
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       16 Apr 2026
 *
 * Public-facing dealer profile at /dealers/{slug}. Renders the dealer
 * header, aggregated ratings, all approved reviews, and — for logged-in
 * members who have not yet rated this dealer — an inline rating form.
 */

namespace IPS\gddealer\modules\front\dealers;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _profile extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		parent::execute();
	}

	/**
	 * Default: render the public profile for the dealer identified by
	 * the {dealer_slug} URL segment.
	 */
	protected function manage(): void
	{
		$slug   = trim( (string) ( \IPS\Request::i()->dealer_slug ?? '' ) );
		$member = \IPS\Member::loggedIn();

		if ( $slug === '' )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/1', 404 );
			return;
		}

		try
		{
			$dealerRow = \IPS\Db::i()->select( '*', 'gd_dealer_feed_config',
				[ 'dealer_slug=?', $slug ] )->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/2', 404 );
			return;
		}

		$dealerId = (int) $dealerRow['dealer_id'];
		$isActive = (bool) ( $dealerRow['active'] ?? 0 );

		/* Aggregated ratings. Reviews resolved in the dealer's favor are
		   excluded; pending disputes and dismissed contests still count. */
		$stats = [
			'total'        => 0,
			'avg_pricing'  => 0.0,
			'avg_shipping' => 0.0,
			'avg_service'  => 0.0,
			'avg_overall'  => 0.0,
		];
		try
		{
			$agg = \IPS\Db::i()->select(
				'COUNT(*) as c, AVG(rating_pricing) as p, AVG(rating_shipping) as s, AVG(rating_service) as sv',
				'gd_dealer_ratings',
				[ 'dealer_id=? AND status=? AND dispute_status<>?', $dealerId, 'approved', 'resolved_dealer' ]
			)->first();
			$stats['total']        = (int) $agg['c'];
			$stats['avg_pricing']  = round( (float) $agg['p'], 1 );
			$stats['avg_shipping'] = round( (float) $agg['s'], 1 );
			$stats['avg_service']  = round( (float) $agg['sv'], 1 );
			$stats['avg_overall']  = $stats['total'] > 0
				? round( ( $stats['avg_pricing'] + $stats['avg_shipping'] + $stats['avg_service'] ) / 3, 1 )
				: 0.0;
		}
		catch ( \Exception ) {}

		/* Reviews list — all approved rows. Dispute status is surfaced per
		   row so the template can render the appropriate badge. */
		$reviews = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_ratings',
				[ 'dealer_id=? AND status=?', $dealerId, 'approved' ],
				'created_at DESC', [ 0, 50 ]
			) as $r )
			{
				$reviews[] = [
					'id'              => (int) $r['id'],
					'member_id'       => (int) ( $r['member_id'] ?? 0 ),
					'rating_pricing'  => (int) $r['rating_pricing'],
					'rating_shipping' => (int) $r['rating_shipping'],
					'rating_service'  => (int) $r['rating_service'],
					'review_body'     => (string) ( $r['review_body'] ?? '' ),
					'dealer_response' => (string) ( $r['dealer_response'] ?? '' ),
					'created_at'      => (string) $r['created_at'],
					'dispute_status'  => (string) ( $r['dispute_status'] ?? 'none' ),
					'dispute_outcome' => (string) ( $r['dispute_outcome'] ?? '' ),
				];
			}
		}
		catch ( \Exception ) {}

		$alreadyRated = false;
		if ( $member->member_id )
		{
			try
			{
				$alreadyRated = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_ratings',
					[ 'dealer_id=? AND member_id=?', $dealerId, (int) $member->member_id ] )->first() > 0;
			}
			catch ( \Exception ) {}
		}

		/* A member cannot review their own dealership, and inactive dealers
		   no longer accept new reviews (the rate() action enforces this too). */
		$isOwnDealer   = $member->member_id && (int) $member->member_id === $dealerId;
		$canRate       = $isActive && $member->member_id && !$alreadyRated && !$isOwnDealer;
		$loginRequired = !$member->member_id;

		/* If this visit is in response to a dispute notification ("You have
		   been contested"), build the banner data and customer response
		   form target. */
		$customerDispute = null;
		$disputeId       = (int) ( \IPS\Request::i()->dispute ?? 0 );
		if ( $disputeId > 0 && $member->member_id )
		{
			try
			{
				$cd = \IPS\Db::i()->select( '*', 'gd_dealer_ratings',
					[ 'id=? AND dealer_id=? AND member_id=? AND dispute_status=?',
						$disputeId, $dealerId, (int) $member->member_id, 'pending_customer' ]
				)->first();
				$customerDispute = [
					'id'               => (int) $cd['id'],
					'dispute_reason'   => (string) ( $cd['dispute_reason'] ?? '' ),
					'dispute_evidence' => (string) ( $cd['dispute_evidence'] ?? '' ),
					'dispute_deadline' => (string) ( $cd['dispute_deadline'] ?? '' ),
					'respond_url'      => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=profile&do=disputeRespond&dealer_slug=' . urlencode( $slug ) . '&id=' . (int) $cd['id']
					)->csrf(),
				];
			}
			catch ( \Exception ) {}
		}

		$rateUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=profile&do=rate&dealer_slug=' . urlencode( $slug )
		)->csrf();

		$loginUrl = (string) \IPS\Http\Url::internal(
			'app=core&module=system&controller=login',
			'front', 'login'
		);

		$guidelinesUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=profile&do=guidelines',
			'front', 'dealers_review_guidelines'
		);

		/* Pull the matching IPS member so we can show their avatar, cover
		   photo, and accurate join date — the dealer profile page mirrors
		   the IPS member profile layout. */
		$avatar      = '';
		$coverPhoto  = '';
		$memberSince = '';
		try
		{
			$ipsMember = \IPS\Member::load( $dealerId );
			if ( $ipsMember->member_id )
			{
				$avatar = htmlspecialchars( (string) $ipsMember->photo, ENT_QUOTES, 'UTF-8' );
				if ( $ipsMember->joined instanceof \IPS\DateTime )
				{
					$memberSince = $ipsMember->joined->format( 'F Y' );
				}
				$coverPhoto = (string) ( $ipsMember->pp_cover_photo ?? '' );
			}
		}
		catch ( \Exception ) {}

		if ( $memberSince === '' )
		{
			$createdAtRaw = (string) ( $dealerRow['created_at'] ?? '' );
			$memberSince  = $createdAtRaw ? substr( $createdAtRaw, 0, 7 ) : '';
		}

		$activeListings = 0;
		try
		{
			$activeListings = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings',
				[ 'dealer_id=? AND listing_status=?', $dealerId, 'active' ] )->first();
		}
		catch ( \Exception ) {}

		$tier      = (string) ( $dealerRow['subscription_tier'] ?? 'basic' );
		$tierLabel = ucfirst( $tier );
		$tierColor = match( $tier ) {
			'founding'   => '#b45309',
			'pro'        => '#2563eb',
			'enterprise' => '#7c3aed',
			default      => '#6b7280',
		};

		$coverStyle = $coverPhoto !== ''
			? 'background-image:url(' . $coverPhoto . ');background-size:cover;background-position:center'
			: 'background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%)';

		$contactEmail = '';
		try
		{
			if ( isset( $ipsMember ) && $ipsMember->member_id )
			{
				$contactEmail = (string) $ipsMember->email;
			}
		}
		catch ( \Exception ) {}
		if ( $contactEmail === '' )
		{
			$contactEmail = (string) ( \IPS\Settings::i()->gddealer_help_contact ?: 'dealers@gunrack.deals' );
		}

		$dealer = [
			'dealer_id'       => $dealerId,
			'dealer_name'     => (string) $dealerRow['dealer_name'],
			'dealer_slug'     => (string) $dealerRow['dealer_slug'],
			'member_since'    => $memberSince,
			'is_active'       => $isActive,
			'avatar'          => $avatar,
			'cover_photo'     => $coverPhoto,
			'cover_style'     => $coverStyle,
			'tier'            => $tier,
			'tier_label'      => $tierLabel,
			'tier_color'      => $tierColor,
			'active_listings' => $activeListings,
			'listing_count'   => $activeListings,
			'contact_email'   => $contactEmail,
		];

		$stats['pct_pricing']  = $stats['avg_pricing']  > 0 ? (int) round( ( $stats['avg_pricing']  / 5 ) * 100 ) : 0;
		$stats['pct_shipping'] = $stats['avg_shipping'] > 0 ? (int) round( ( $stats['avg_shipping'] / 5 ) * 100 ) : 0;
		$stats['pct_service']  = $stats['avg_service']  > 0 ? (int) round( ( $stats['avg_service']  / 5 ) * 100 ) : 0;

		foreach ( $reviews as &$rRef )
		{
			$rRef['pricing_stars']  = str_repeat( '★', max( 0, min( 5, (int) $rRef['rating_pricing'] ) ) );
			$rRef['shipping_stars'] = str_repeat( '★', max( 0, min( 5, (int) $rRef['rating_shipping'] ) ) );
			$rRef['service_stars']  = str_repeat( '★', max( 0, min( 5, (int) $rRef['rating_service'] ) ) );
			$rRef['is_under_review'] = in_array( $rRef['dispute_status'], [ 'pending_customer', 'pending_admin' ], true );
		}
		unset( $rRef );

		$csrfKey = (string) \IPS\Session::i()->csrfKey;

		\IPS\Output::i()->title  = $dealer['dealer_name'];
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )
			->dealerProfile( $dealer, $stats, $reviews, $canRate, $alreadyRated, $loginRequired, $rateUrl, $csrfKey, $loginUrl, $customerDispute, $guidelinesUrl );
	}

	/**
	 * Public "Review & Dispute Guidelines" page at /dealers/review-guidelines.
	 * Pulls all section titles/bodies from settings so admin can edit.
	 */
	protected function guidelines(): void
	{
		$settings = \IPS\Settings::i();

		$content = [
			'buyer_title'   => (string) ( $settings->gddealer_guidelines_buyer_title   ?? '' ),
			'buyer_body'    => (string) ( $settings->gddealer_guidelines_buyer_body    ?? '' ),
			'dispute_title' => (string) ( $settings->gddealer_guidelines_dispute_title ?? '' ),
			'dispute_body'  => (string) ( $settings->gddealer_guidelines_dispute_body  ?? '' ),
			'dealer_title'  => (string) ( $settings->gddealer_guidelines_dealer_title  ?? '' ),
			'dealer_body'   => (string) ( $settings->gddealer_guidelines_dealer_body   ?? '' ),
		];

		$contactEmail = (string) ( $settings->gddealer_help_contact ?: 'dealers@gunrack.deals' );

		\IPS\Output::i()->title  = 'Review & Dispute Guidelines';
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )
			->reviewGuidelines( $content, $contactEmail );
	}

	/**
	 * POST — create or update a member's rating for this dealer.
	 * Uniqueness is enforced by the uq_dealer_member index: each member
	 * can only rate a given dealer once (subsequent submits are updates).
	 */
	protected function rate(): void
	{
		\IPS\Session::i()->csrfCheck();

		$member = \IPS\Member::loggedIn();
		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD300/3', 403 );
			return;
		}

		$slug = trim( (string) ( \IPS\Request::i()->dealer_slug ?? '' ) );
		if ( $slug === '' )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/4', 404 );
			return;
		}

		try
		{
			$dealerRow = \IPS\Db::i()->select( '*', 'gd_dealer_feed_config',
				[ 'dealer_slug=? AND active=?', $slug, 1 ] )->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/5', 404 );
			return;
		}

		$dealerId = (int) $dealerRow['dealer_id'];
		if ( (int) $member->member_id === $dealerId )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD300/6', 403 );
			return;
		}

		$pricing  = max( 1, min( 5, (int) ( \IPS\Request::i()->rating_pricing ?? 0 ) ) );
		$shipping = max( 1, min( 5, (int) ( \IPS\Request::i()->rating_shipping ?? 0 ) ) );
		$service  = max( 1, min( 5, (int) ( \IPS\Request::i()->rating_service ?? 0 ) ) );
		$body     = trim( (string) ( \IPS\Request::i()->review_body ?? '' ) );

		$profileUrl = \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug )
		);

		if ( $pricing < 1 || $shipping < 1 || $service < 1 )
		{
			\IPS\Output::i()->redirect( $profileUrl, 'gddealer_profile_rating_invalid' );
			return;
		}

		try
		{
			$existing = (int) \IPS\Db::i()->select( 'id', 'gd_dealer_ratings',
				[ 'dealer_id=? AND member_id=?', $dealerId, (int) $member->member_id ] )->first();
		}
		catch ( \Exception )
		{
			$existing = 0;
		}

		try
		{
			if ( $existing > 0 )
			{
				\IPS\Db::i()->update( 'gd_dealer_ratings', [
					'rating_pricing'  => $pricing,
					'rating_shipping' => $shipping,
					'rating_service'  => $service,
					'review_body'     => $body !== '' ? $body : null,
				], [ 'id=?', $existing ] );
			}
			else
			{
				\IPS\Db::i()->insert( 'gd_dealer_ratings', [
					'dealer_id'       => $dealerId,
					'member_id'       => (int) $member->member_id,
					'rating_pricing'  => $pricing,
					'rating_shipping' => $shipping,
					'rating_service'  => $service,
					'review_body'     => $body !== '' ? $body : null,
					'created_at'      => date( 'Y-m-d H:i:s' ),
					'status'          => 'approved',
					'disputed'        => 0,
				]);
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect( $profileUrl, 'gddealer_profile_rating_saved' );
	}

	/**
	 * POST — customer response to a dealer's contest.
	 * Requires the review is in 'pending_customer' status and the
	 * logged-in member is the review author. On success the dispute
	 * advances to 'pending_admin' and an admin notification is sent.
	 */
	protected function disputeRespond(): void
	{
		\IPS\Session::i()->csrfCheck();

		$member = \IPS\Member::loggedIn();
		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD300/7', 403 );
			return;
		}

		$slug = trim( (string) ( \IPS\Request::i()->dealer_slug ?? '' ) );
		$id   = (int) ( \IPS\Request::i()->id ?? 0 );
		if ( $slug === '' || $id <= 0 )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/8', 404 );
			return;
		}

		try
		{
			$dealerRow = \IPS\Db::i()->select( '*', 'gd_dealer_feed_config',
				[ 'dealer_slug=?', $slug ] )->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/9', 404 );
			return;
		}

		$dealerId   = (int) $dealerRow['dealer_id'];
		$profileUrl = \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug )
		);

		try
		{
			$review = \IPS\Db::i()->select( '*', 'gd_dealer_ratings',
				[ 'id=? AND dealer_id=? AND member_id=? AND dispute_status=?',
					$id, $dealerId, (int) $member->member_id, 'pending_customer' ]
			)->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->redirect( $profileUrl );
			return;
		}

		$response = trim( (string) ( \IPS\Request::i()->customer_response ?? '' ) );
		$evidence = trim( (string) ( \IPS\Request::i()->customer_evidence ?? '' ) );

		if ( $response === '' )
		{
			\IPS\Output::i()->redirect( $profileUrl, 'gddealer_profile_dispute_response_required' );
			return;
		}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_ratings', [
				'dispute_status'        => 'pending_admin',
				'customer_response'     => $response,
				'customer_evidence'     => $evidence !== '' ? $evidence : null,
				'customer_responded_at' => date( 'Y-m-d H:i:s' ),
			], [ 'id=?', (int) $review['id'] ] );
		}
		catch ( \Exception ) {}

		/* Notify admins so they can resolve the dispute. */
		try
		{
			$adminUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=dealers&do=disputes',
				'admin'
			);

			foreach ( \IPS\Db::i()->select( '*', 'core_members',
				[ \IPS\Db::i()->in( 'member_group_id', [ 4 ] ) ],
				null, [ 0, 50 ]
			) as $m )
			{
				try
				{
					$admin = \IPS\Member::constructFromData( $m );
					if ( $admin->member_id )
					{
						\IPS\Email::buildFromTemplate( 'gddealer', 'disputeAdminNotify', [
							'admin_url' => $adminUrl,
						], \IPS\Email::TYPE_TRANSACTIONAL )->send( $admin );
					}
				}
				catch ( \Exception ) {}
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect( $profileUrl, 'gddealer_profile_dispute_response_saved' );
	}
}

class profile extends _profile {}
