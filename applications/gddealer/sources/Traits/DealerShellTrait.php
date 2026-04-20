<?php
/**
 * @brief  GD Dealer Manager — DealerShell Trait
 *
 * Shared helpers for front-end controllers that render inside the dealer
 * dashboard shell (nav bar, QUICK LINKS sidebar). Consumers must set
 * $this->dealer (of type \IPS\gddealer\Dealer\Dealer) before calling
 * output() / dealerSummary(). Extracted from dashboard.php in v1.0.55.
 */

namespace IPS\gddealer\Traits;

use IPS\gddealer\Dealer\Dealer;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

trait DealerShellTrait
{
	/**
	 * Wrap a tab body in the dealerShell template and push to output.
	 */
	protected function output( string $activeTab, string $body ): void
	{
		$canSupport = Dealer::canAccessSupport( \IPS\Member::loggedIn() );
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_frontend_dashboard_title' );
		\IPS\Output::i()->output = $this->themeVars() . \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->dealerShell(
			$this->dealerSummary(),
			$activeTab,
			$this->tabUrls(),
			$body,
			$canSupport,
			$canSupport ? $this->supportUrl() : ''
		);
	}

	/**
	 * Scalar summary of the dealer used by every tab template header.
	 */
	protected function dealerSummary(): array
	{
		$d    = $this->dealer;
		$tier = (string) $d->subscription_tier;

		$avatarUrl       = '';
		$coverPhotoUrl   = '';
		$coverOffset     = 0;
		try
		{
			$ipsMember = \IPS\Member::loggedIn();
			$avatarUrl = (string) ( $ipsMember->get_photo( true, false ) ?? '' );
			$cp        = $ipsMember->coverPhoto();
			if ( $cp->file )
			{
				$coverPhotoUrl = (string) $cp->file->url;
			}
			$coverOffset = (int) ( $cp->offset ?? 0 );
		}
		catch ( \Exception ) {}

		$lastVisit  = (string) ( $d->last_review_check ?? '2000-01-01 00:00:00' );
		$newReviews = 0;
		try
		{
			$newReviews = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_ratings', [
				'dealer_id=? AND created_at>? AND status=?',
				(int) $d->dealer_id, $lastVisit, 'approved'
			] )->first();
		}
		catch ( \Exception ) {}

		return [
			'dealer_id'             => (int) $d->dealer_id,
			'dealer_name'           => (string) $d->dealer_name,
			'dealer_slug'           => (string) ( $d->dealer_slug ?? '' ),
			'subscription_tier'     => $tier,
			'tier_label'            => ucfirst( $tier ),
			'tier_color'            => match( $tier ) {
				'founding'   => (string) ( \IPS\Settings::i()->gddealer_founding_badge_color   ?: '#b45309' ),
				'pro'        => (string) ( \IPS\Settings::i()->gddealer_pro_badge_color        ?: '#2563eb' ),
				'enterprise' => (string) ( \IPS\Settings::i()->gddealer_enterprise_badge_color ?: '#7c3aed' ),
				default      => (string) ( \IPS\Settings::i()->gddealer_basic_badge_color      ?: '#6b7280' ),
			},
			'onboarding_incomplete' => empty( $d->feed_url ),
			'suspended'             => (bool) $d->suspended,
			'disputes_suspended'    => (bool) ( $d->disputes_suspended ?? 0 ),
			'help_email'            => (string) ( \IPS\Settings::i()->gddealer_help_contact ?: 'dealers@gunrack.deals' ),
			'avatar_url'            => $avatarUrl,
			'cover_photo_url'       => $coverPhotoUrl,
			'cover_offset'          => $coverOffset,
			'new_reviews'           => $newReviews,
		];
	}

	/**
	 * URLs for the dashboard tabs — pre-built in the controller so
	 * templates never nest {url=...} inside conditionals (Rule #12.6).
	 */
	protected function tabUrls(): array
	{
		$base = 'app=gddealer&module=dealers&controller=dashboard&do=';
		return [
			'overview'     => (string) \IPS\Http\Url::internal( $base . 'overview' ),
			'feedSettings' => (string) \IPS\Http\Url::internal( $base . 'feedSettings' ),
			'listings'     => (string) \IPS\Http\Url::internal( $base . 'listings' ),
			'unmatched'    => (string) \IPS\Http\Url::internal( $base . 'unmatched' ),
			'analytics'    => (string) \IPS\Http\Url::internal( $base . 'analytics' ),
			'reviews'      => (string) \IPS\Http\Url::internal( $base . 'reviews' ),
			'subscription' => (string) \IPS\Http\Url::internal( $base . 'subscription' ),
			'help'         => (string) \IPS\Http\Url::internal( $base . 'help' ),
		];
	}

	protected function supportUrl(): string
	{
		return (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support' );
	}

	/**
	 * Admin-configurable theme variables injected at the top of every
	 * dealer page. CSS rules target the gdDealerWrapper / gdDealerTabs /
	 * gdStatCard / gdDealerCoverFallback classes to override any inline
	 * color fallbacks left over in the templates.
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
.gdDealerCoverFallback {
	background: var(--gd-header-bg) !important;
}

/* ─── Mobile & tablet (≤768px) ─── */
@media (max-width: 768px) {
  .gdDealerWrapper { padding: 0 !important; }
  .gdDealerWrapper > * { margin-left: 12px !important; margin-right: 12px !important; }

  .gdDealerWrapper .ipsPageHeader .ipsCoverPhoto__container { height: 100px !important; }
  .gdDealerWrapper .ipsPageHeader .ipsCoverPhoto { min-height: 100px !important; }
  .gdDealerWrapper .ipsCoverPhotoMeta {
    padding: 12px 14px !important;
    gap: 10px !important;
    flex-direction: column !important;
    align-items: flex-start !important;
  }
  .gdDealerWrapper .ipsCoverPhoto__avatar { margin-top: -40px !important; }
  .gdDealerWrapper .ipsCoverPhoto__avatar .ipsUserPhoto img { width: 64px !important; height: 64px !important; }
  .gdDealerWrapper .ipsCoverPhoto__titles { width: 100%; }
  .gdDealerWrapper .ipsCoverPhoto__titles h1 { font-size: 1.2em !important; }
  .gdDealerWrapper .ipsCoverPhoto__buttons { width: 100%; }
  .gdDealerWrapper .ipsCoverPhoto__buttons .ipsButton { width: 100%; justify-content: center; }

  .gdDealerWrapper > div[style*="#fefce8"] {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 10px !important;
  }
  .gdDealerWrapper > div[style*="#fefce8"] .ipsButton { width: 100%; justify-content: center; }

  .gdShellTabs [role="tablist"] {
    overflow-x: auto !important;
    overflow-y: hidden !important;
    scroll-snap-type: x proximity !important;
    -webkit-overflow-scrolling: touch !important;
    scrollbar-width: none !important;
    flex-wrap: nowrap !important;
    position: relative !important;
    padding: 0 !important;
    mask-image: linear-gradient(90deg, transparent 0, #000 20px, #000 calc(100% - 20px), transparent 100%) !important;
  }
  .gdShellTabs [role="tablist"]::-webkit-scrollbar { display: none !important; }
  .gdShellTabs [role="tablist"] a {
    flex: 0 0 auto !important;
    scroll-snap-align: start !important;
    padding: 14px 16px !important;
    font-size: 14px !important;
    min-height: 44px !important;
    display: flex !important;
    align-items: center !important;
  }
  .gdShellTabs [role="tablist"] a[aria-selected="true"] { scroll-snap-align: center !important; }

  #elDealerTabs_content {
    padding: 16px !important;
    border-left: none !important;
    border-right: none !important;
    border-radius: 0 !important;
  }

  .gdStatCards, .gdOverviewStats, .gdRatingCards {
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 10px !important;
  }

  .gdHelpLayout {
    flex-direction: column !important;
    gap: 16px !important;
  }
  .gdHelpSidebar, .gdHelpContent, .gdHelpMain {
    width: 100% !important;
    position: static !important;
    max-width: none !important;
    flex: 1 1 auto !important;
  }

  .gdTableWrap table, .gdResponsiveTable {
    display: block !important;
  }
  .gdTableWrap thead, .gdResponsiveTable thead { display: none !important; }
  .gdTableWrap tbody, .gdResponsiveTable tbody { display: block !important; }
  .gdTableWrap tr, .gdResponsiveTable tr {
    display: block !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 10px !important;
    padding: 12px 14px !important;
    margin-bottom: 10px !important;
    background: #fff !important;
  }
  .gdTableWrap td, .gdResponsiveTable td {
    display: flex !important;
    justify-content: space-between !important;
    align-items: flex-start !important;
    padding: 6px 0 !important;
    border: none !important;
    font-size: 14px !important;
    gap: 14px !important;
  }
  .gdTableWrap td::before, .gdResponsiveTable td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #64748b;
    min-width: 100px;
    flex-shrink: 0;
  }

  .gdSupportList__grid { display: block !important; }
  .gdSupportList__header { display: none !important; }
  .gdSupportList__row {
    display: block !important;
    padding: 14px !important;
  }
  .gdSupportList__row .gdSupportList__iconCell {
    display: inline-block !important;
    margin-right: 10px !important;
    vertical-align: middle !important;
  }
  .gdSupportList__row .gdSupportList__subject {
    display: inline-block !important;
    vertical-align: middle !important;
    max-width: calc(100% - 50px);
  }
  .gdSupportList__row .gdSupportList__meta {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
    margin-top: 10px !important;
    font-size: 12px !important;
  }

  .gdTicketHistory > div {
    flex-direction: column !important;
    gap: 2px !important;
    padding-bottom: 10px !important;
    border-bottom: 1px solid #f1f5f9;
  }
  .gdTicketHistory > div > span:first-child {
    width: 100% !important;
    font-size: 11px !important;
    color: #94a3b8 !important;
  }

  .gdSubNav {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    scrollbar-width: none !important;
    white-space: nowrap !important;
    padding-bottom: 2px !important;
  }
  .gdSubNav::-webkit-scrollbar { display: none !important; }
  .gdSubNav a, .gdSubNav button { flex-shrink: 0 !important; }

  .gdFilterBar {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 8px !important;
  }
  .gdFilterBar > * { width: 100% !important; }
  .gdFilterBar select, .gdFilterBar input[type="text"], .gdFilterBar input[type="search"] {
    min-height: 44px !important;
    font-size: 16px !important;
  }
  .gdFilterBar button { min-height: 44px !important; }

  .gdDealerWrapper form input[type="text"],
  .gdDealerWrapper form input[type="email"],
  .gdDealerWrapper form select,
  .gdDealerWrapper form textarea {
    font-size: 16px !important;
    min-height: 44px !important;
    box-sizing: border-box !important;
  }
  .gdDealerWrapper form textarea { min-height: 120px !important; }
  .gdDealerWrapper form button[type="submit"],
  .gdDealerWrapper form input[type="submit"] {
    width: 100% !important;
    min-height: 48px !important;
    font-size: 15px !important;
  }

  .gdFormGrid2 {
    grid-template-columns: 1fr !important;
    gap: 12px !important;
  }

  .gdReplyCard {
    padding: 14px !important;
    border-radius: 8px !important;
  }
  .gdReplyCard > div:first-child {
    flex-wrap: wrap !important;
    gap: 6px !important;
  }

  .gdReviewCard {
    padding: 14px !important;
  }
  .gdReviewCard__header {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 6px !important;
  }
  .gdReviewCard__ratings {
    flex-wrap: wrap !important;
    gap: 6px 12px !important;
  }

  .gdPagination a, .gdPagination span {
    min-width: 40px !important;
    min-height: 40px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 8px 12px !important;
  }

  body > footer[style*="#1e3a5f"] > div > div {
    grid-template-columns: 1fr !important;
    gap: 28px !important;
  }
}

/* ─── Phone-only tightening (≤480px) ─── */
@media (max-width: 480px) {
  .gdShellTabs [role="tablist"] a {
    padding: 12px 14px !important;
    font-size: 13px !important;
  }
  .gdDealerWrapper .ipsPageHeader .ipsCoverPhoto__container { height: 80px !important; }
  .gdDealerWrapper .ipsCoverPhoto__avatar .ipsUserPhoto img { width: 56px !important; height: 56px !important; }
  .gdDealerWrapper .ipsCoverPhoto__titles h1 { font-size: 1.1em !important; }
  #elDealerTabs_content { padding: 12px !important; }

  .gdStatCards .gdStatCard__value,
  .gdOverviewStats .gdStatCard__value,
  .gdRatingCards .gdStatCard__value {
    font-size: 28px !important;
  }
}

/* ─── Tablet only (481–768px) — 2-column stat grid ─── */
@media (min-width: 481px) and (max-width: 768px) {
  .gdStatCards, .gdOverviewStats, .gdRatingCards {
    grid-template-columns: 1fr 1fr !important;
  }
}
</style>';
	}
}
