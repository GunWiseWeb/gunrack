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

	/** Map a 1–5 rating to a color on the shared rating scale. */
	private static function ratingColor( float $r ): string
	{
		return match( true ) {
			$r >= 4.0 => '#16a34a',
			$r >= 3.0 => '#d97706',
			$r > 0    => '#dc2626',
			default   => '#9ca3af',
		};
	}

	/**
	 * Admin-configurable theme variables. Mirrors the dashboard
	 * controller's themeVars() so the public dealer profile picks up
	 * the same site-wide palette.
	 */
	protected function themeVars(): string
	{
		$s = \IPS\Settings::i();
		return '<style>
:root {
	--gd-primary:            ' . ( $s->gddealer_color_primary ?: '#2563eb' ) . ';
	--gd-primary-text:       ' . ( $s->gddealer_color_primary_text ?: '#ffffff' ) . ';
	--gd-tab-active-bg:      ' . ( $s->gddealer_color_active_tab_bg ?: '#1e3a5f' ) . ';
	--gd-tab-active-text:    ' . ( $s->gddealer_color_active_tab_text ?: '#ffffff' ) . ';
	--gd-tab-inactive-text:  ' . ( $s->gddealer_color_inactive_tab_text ?: '#374151' ) . ';
	--gd-accent:             ' . ( $s->gddealer_color_accent ?: '#16a34a' ) . ';
	--gd-warning:            ' . ( $s->gddealer_color_warning ?: '#d97706' ) . ';
	--gd-danger:             ' . ( $s->gddealer_color_danger ?: '#dc2626' ) . ';
	--gd-header-bg:          ' . ( $s->gddealer_color_header_bg ?: '#1e3a5f' ) . ';
	--gd-card-bg:            ' . ( $s->gddealer_color_card_bg ?: '#ffffff' ) . ';
	--gd-card-border:        ' . ( $s->gddealer_color_card_border ?: '#e0e0e0' ) . ';
}
.gdDealerTabs .ipsTabs__tab[aria-selected="true"] {
	background: var(--gd-tab-active-bg) !important;
	color: var(--gd-tab-active-text) !important;
	border-color: var(--gd-tab-active-bg) !important;
}
.gdDealerTabs .ipsTabs__tab[aria-selected="false"] {
	color: var(--gd-tab-inactive-text) !important;
}
.gdDealerWrapper .ipsButton--primary {
	background: var(--gd-primary) !important;
	color: var(--gd-primary-text) !important;
	border-color: var(--gd-primary) !important;
}
.gdStatCard {
	background: var(--gd-card-bg) !important;
	border-color: var(--gd-card-border) !important;
}
.gdDealerCoverFallback {
	background: var(--gd-header-bg) !important;
}
</style>';
	}

	/** Map a 1–5 rating to a human-readable label on the shared scale. */
	private static function ratingLabel( float $r ): string
	{
		return match( true ) {
			$r >= 4.0 => 'Excellent',
			$r >= 3.0 => 'Good',
			$r > 0    => 'Poor',
			default   => 'No ratings yet',
		};
	}

	/**
	 * Default: render the public profile for the dealer identified by
	 * the {dealer_slug} URL segment.
	 */
	protected function manage(): void
	{
		$member = \IPS\Member::loggedIn();

		/* Primary: IPS furl sets this via matched params. */
		$slug = trim( (string) ( \IPS\Request::i()->dealer_slug ?? '' ) );

		/* Fallback: when Force Friendly URLs is enabled, IPS can redirect
		   the raw URL to the compiled friendly form but strip the matched
		   dealer_slug param before the controller runs. Parse REQUEST_URI
		   directly so the profile still resolves. */
		if ( $slug === '' )
		{
			$uri   = (string) parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
			$parts = array_values( array_filter( explode( '/', trim( $uri, '/' ) ) ) );

			foreach ( $parts as $i => $part )
			{
				if ( $part === 'profile' && isset( $parts[ $i + 1 ] ) )
				{
					$slug = trim( (string) $parts[ $i + 1 ] );
					break;
				}
			}

			if ( $slug === '' )
			{
				$last = end( $parts );
				if ( $last && $last !== 'profile' && $last !== 'dealers' )
				{
					try
					{
						$count = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config',
							[ 'dealer_slug=?', $last ] )->first();
						if ( $count > 0 )
						{
							$slug = (string) $last;
						}
					}
					catch ( \Exception ) {}
				}
			}
		}

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

		$stats['rating_color']   = self::ratingColor( (float) $stats['avg_overall'] );
		$stats['rating_label']   = self::ratingLabel( (float) $stats['avg_overall'] );
		$stats['color_pricing']  = self::ratingColor( (float) $stats['avg_pricing'] );
		$stats['color_shipping'] = self::ratingColor( (float) $stats['avg_shipping'] );
		$stats['color_service']  = self::ratingColor( (float) $stats['avg_service'] );

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
				$pricing  = max( 0, min( 5, (int) $r['rating_pricing'] ) );
				$shipping = max( 0, min( 5, (int) $r['rating_shipping'] ) );
				$service  = max( 0, min( 5, (int) $r['rating_service'] ) );
				$avg      = round( ( $pricing + $shipping + $service ) / 3, 1 );

				$reviewerName   = 'Anonymous';
				$reviewerAvatar = '';
				if ( (int) ( $r['member_id'] ?? 0 ) > 0 )
				{
					try
					{
						$rm = \IPS\Member::load( (int) $r['member_id'] );
						if ( $rm->member_id )
						{
							$reviewerName   = (string) $rm->name;
							$reviewerAvatar = (string) ( $rm->get_photo( true, false ) ?? '' );
						}
					}
					catch ( \Exception ) {}
				}

				$createdAt  = (string) $r['created_at'];
				$responseAt = (string) ( $r['response_at'] ?? '' );

				$reviewMemberId = (int) ( $r['member_id'] ?? 0 );
				$isOwnReview    = $member->member_id && $reviewMemberId === (int) $member->member_id;
				$rowDisputeStatus = (string) ( $r['dispute_status'] ?? 'none' );

				$disputeRespondUrl = '';
				if ( $isOwnReview && $rowDisputeStatus === 'pending_customer' )
				{
					$disputeRespondUrl = (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ) . '&dispute=' . (int) $r['id']
					);
				}

				$editReviewUrl = '';
				if ( $isOwnReview && $rowDisputeStatus === 'none' )
				{
					$editReviewUrl = (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=profile&do=editReview&dealer_slug=' . urlencode( $slug ) . '&id=' . (int) $r['id']
					);
				}

				$reviews[] = [
					'id'                   => (int) $r['id'],
					'member_id'            => $reviewMemberId,
					'rating_pricing'       => $pricing,
					'rating_shipping'      => $shipping,
					'rating_service'       => $service,
					'review_body'          => (string) ( $r['review_body'] ?? '' ),
					'dealer_response'      => (string) ( $r['dealer_response'] ?? '' ),
					'created_at'           => $createdAt,
					'created_at_formatted' => $createdAt ? date( 'M j, Y', strtotime( $createdAt ) ) : '',
					'response_at'          => $responseAt ? date( 'M j, Y', strtotime( $responseAt ) ) : '',
					'dispute_status'       => $rowDisputeStatus,
					'dispute_outcome'      => (string) ( $r['dispute_outcome'] ?? '' ),
					'avg_score'            => $avg,
					'avg_color'            => self::ratingColor( (float) $avg ),
					'reviewer_name'        => $reviewerName,
					'reviewer_avatar'      => $reviewerAvatar,
					'stars_pricing'        => str_repeat( '★', $pricing ) . str_repeat( '☆', 5 - $pricing ),
					'stars_shipping'       => str_repeat( '★', $shipping ) . str_repeat( '☆', 5 - $shipping ),
					'stars_service'        => str_repeat( '★', $service ) . str_repeat( '☆', 5 - $service ),
					'is_own_review'        => $isOwnReview,
					'dispute_respond_url'  => $disputeRespondUrl,
					'edit_review_url'      => $editReviewUrl,
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
				$deadlineRaw = (string) ( $cd['dispute_deadline'] ?? '' );
				$customerDispute = [
					'id'                 => (int) $cd['id'],
					'dispute_reason'     => (string) ( $cd['dispute_reason'] ?? '' ),
					'dispute_evidence'   => (string) ( $cd['dispute_evidence'] ?? '' ),
					'dispute_deadline'   => $deadlineRaw,
					'deadline_formatted' => $deadlineRaw !== '' ? date( 'F j, Y', strtotime( $deadlineRaw ) ) : '',
					'respond_url'        => (string) \IPS\Http\Url::internal(
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
		$avatarUrl     = '';
		$coverPhotoUrl = '';
		$coverOffset   = 0;
		$memberSince   = '';
		try
		{
			$ipsMember = \IPS\Member::load( $dealerId );
			if ( $ipsMember->member_id )
			{
				$avatarUrl = (string) ( $ipsMember->get_photo( true, false ) ?? '' );
				if ( $ipsMember->joined instanceof \IPS\DateTime )
				{
					$memberSince = $ipsMember->joined->format( 'F Y' );
				}
				$cp = $ipsMember->coverPhoto();
				if ( $cp->file )
				{
					$coverPhotoUrl = (string) $cp->file->url;
				}
				$coverOffset = (int) ( $cp->offset ?? 0 );
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
			'founding'   => (string) ( \IPS\Settings::i()->gddealer_founding_badge_color   ?: '#b45309' ),
			'pro'        => (string) ( \IPS\Settings::i()->gddealer_pro_badge_color        ?: '#2563eb' ),
			'enterprise' => (string) ( \IPS\Settings::i()->gddealer_enterprise_badge_color ?: '#7c3aed' ),
			default      => (string) ( \IPS\Settings::i()->gddealer_basic_badge_color      ?: '#6b7280' ),
		};

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
			'avatar_url'      => $avatarUrl,
			'cover_photo_url' => $coverPhotoUrl,
			'cover_offset'    => $coverOffset,
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

		$csrfKey = (string) \IPS\Session::i()->csrfKey;

		\IPS\Output::i()->title  = $dealer['dealer_name'];
		\IPS\Output::i()->output = $this->themeVars() . \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )
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
		\IPS\Output::i()->output = $this->themeVars() . \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )
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
			\IPS\Db::i()->insert( 'gd_dealer_ratings', [
				'dealer_id'       => $dealerId,
				'member_id'       => (int) $member->member_id,
				'rating_pricing'  => $pricing,
				'rating_shipping' => $shipping,
				'rating_service'  => $service,
				'review_body'     => $body !== '' ? $body : null,
				'created_at'      => date( 'Y-m-d H:i:s' ),
				'status'          => 'approved',
				'dispute_status'  => 'none',
			]);
		}
		catch ( \Exception ) {}

		$dealerMember = NULL;
		try
		{
			$dealerMember = \IPS\Member::load( $dealerId );
			if ( $dealerMember->member_id )
			{
				\IPS\Email::buildFromTemplate( 'gddealer', 'newDealerReview', [
					'name'          => $dealerMember->name,
					'reviewer_name' => (string) $member->name,
					'dealer_name'   => (string) $dealerRow['dealer_name'],
					'review_url'    => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=reviews'
					),
				], \IPS\Email::TYPE_TRANSACTIONAL )->send( $dealerMember );
			}
		}
		catch ( \Exception ) {}

		/* IPS inline notification to the dealer — completely independent
		   try/catch so a failed email above does not suppress it. */
		try
		{
			$dealerMember = $dealerMember ?? \IPS\Member::load( $dealerId );
			if ( $dealerMember && $dealerMember->member_id )
			{
				$notification = new \IPS\Notification(
					\IPS\Application::load( 'gddealer' ),
					'new_dealer_review',
					$dealerMember,
					[ $dealerMember ],
					[
						'reviewer_id'   => (int) $member->member_id,
						'reviewer_name' => (string) $member->name,
						'dealer_name'   => (string) $dealerRow['dealer_name'],
					]
				);
				$notification->recipients->attach( $dealerMember );
				$notification->send();
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

		/* Email + IPS notification to admins so they can resolve the dispute. */
		try
		{
			$adminUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=dealers&do=disputes',
				'admin'
			);
			$dealerName = (string) ( $dealerRow['dealer_name'] ?? '' );

			foreach ( \IPS\Db::i()->select( '*', 'core_members',
				[ \IPS\Db::i()->in( 'member_group_id', [ 4 ] ) ],
				null, [ 0, 50 ]
			) as $m )
			{
				$admin = NULL;
				try
				{
					$admin = \IPS\Member::constructFromData( $m );
				}
				catch ( \Exception ) {}

				/* Email to this admin — own try/catch. */
				try
				{
					if ( $admin && $admin->member_id )
					{
						\IPS\Email::buildFromTemplate( 'gddealer', 'disputeAdminNotify', [
							'admin_url' => $adminUrl,
						], \IPS\Email::TYPE_TRANSACTIONAL )->send( $admin );
					}
				}
				catch ( \Exception ) {}

				/* IPS notification to this admin — completely independent. */
				try
				{
					if ( $admin && $admin->member_id )
					{
						$notification = new \IPS\Notification(
							\IPS\Application::load( 'gddealer' ),
							'dispute_admin_review',
							$admin,
							[ $admin ],
							[
								'dealer_name'   => $dealerName,
								'reviewer_name' => (string) $member->name,
							]
						);
						$notification->recipients->attach( $admin );
						$notification->send();
					}
				}
				catch ( \Exception ) {}
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect( $profileUrl, 'gddealer_profile_dispute_response_saved' );
	}

	/**
	 * GET — render an edit form; POST — save updated ratings/body.
	 * Only the review author may edit, and only while the review is not
	 * under active dispute (pending_customer or pending_admin).
	 */
	protected function editReview(): void
	{
		$member = \IPS\Member::loggedIn();
		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD300/10', 403 );
			return;
		}

		$id   = (int) ( \IPS\Request::i()->id ?? 0 );
		$slug = trim( (string) ( \IPS\Request::i()->dealer_slug ?? '' ) );

		try
		{
			$review = \IPS\Db::i()->select( '*', 'gd_dealer_ratings',
				[ 'id=? AND member_id=?', $id, (int) $member->member_id ]
			)->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD300/11', 404 );
			return;
		}

		$profileUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug )
		);

		if ( in_array( (string) ( $review['dispute_status'] ?? '' ), [ 'pending_customer', 'pending_admin' ], TRUE ) )
		{
			\IPS\Output::i()->redirect( $profileUrl, 'gddealer_cannot_edit_disputed' );
			return;
		}

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			$pricing  = max( 1, min( 5, (int) ( \IPS\Request::i()->rating_pricing  ?? 0 ) ) );
			$shipping = max( 1, min( 5, (int) ( \IPS\Request::i()->rating_shipping ?? 0 ) ) );
			$service  = max( 1, min( 5, (int) ( \IPS\Request::i()->rating_service  ?? 0 ) ) );
			$body     = trim( (string) ( \IPS\Request::i()->review_body ?? '' ) );

			try
			{
				\IPS\Db::i()->update( 'gd_dealer_ratings', [
					'rating_pricing'  => $pricing,
					'rating_shipping' => $shipping,
					'rating_service'  => $service,
					'review_body'     => $body !== '' ? $body : null,
				], [ 'id=? AND member_id=?', $id, (int) $member->member_id ] );
			}
			catch ( \Exception ) {}

			$dealerId = (int) $review['dealer_id'];
			try
			{
				$dealerRow = \IPS\Db::i()->select( '*', 'gd_dealer_feed_config',
					[ 'dealer_id=?', $dealerId ] )->first();
			}
			catch ( \Exception )
			{
				$dealerRow = NULL;
			}

			$dealerMember = NULL;
			try
			{
				$dealerMember = \IPS\Member::load( $dealerId );
				if ( $dealerMember->member_id && $dealerRow )
				{
					\IPS\Email::buildFromTemplate( 'gddealer', 'updatedDealerReview', [
						'name'          => $dealerMember->name,
						'reviewer_name' => (string) $member->name,
						'dealer_name'   => (string) $dealerRow['dealer_name'],
						'review_url'    => (string) \IPS\Http\Url::internal(
							'app=gddealer&module=dealers&controller=dashboard&do=reviews'
						),
					], \IPS\Email::TYPE_TRANSACTIONAL )->send( $dealerMember );
				}
			}
			catch ( \Exception ) {}

			try
			{
				$dealerMember = $dealerMember ?? \IPS\Member::load( $dealerId );
				if ( $dealerMember && $dealerMember->member_id && $dealerRow )
				{
					$notification = new \IPS\Notification(
						\IPS\Application::load( 'gddealer' ),
						'updated_dealer_review',
						$dealerMember,
						[ $dealerMember ],
						[
							'reviewer_id'   => (int) $member->member_id,
							'reviewer_name' => (string) $member->name,
							'dealer_name'   => (string) $dealerRow['dealer_name'],
						]
					);
					$notification->recipients->attach( $dealerMember );
					$notification->send();
				}
			}
			catch ( \Exception ) {}

			\IPS\Output::i()->redirect( $profileUrl, 'gddealer_review_updated' );
			return;
		}

		$reviewData = [
			'id'              => (int) $review['id'],
			'rating_pricing'  => (int) $review['rating_pricing'],
			'rating_shipping' => (int) $review['rating_shipping'],
			'rating_service'  => (int) $review['rating_service'],
			'review_body'     => (string) ( $review['review_body'] ?? '' ),
		];

		$editUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=profile&do=editReview&dealer_slug=' . urlencode( $slug ) . '&id=' . $id
		)->csrf();

		$csrfKey = (string) \IPS\Session::i()->csrfKey;

		\IPS\Output::i()->title  = 'Edit Your Review';
		\IPS\Output::i()->output = $this->themeVars() . \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )
			->editReview( $reviewData, $editUrl, $profileUrl, $csrfKey );
	}
}

class profile extends _profile {}
