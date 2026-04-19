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
</style>';
	}
}
