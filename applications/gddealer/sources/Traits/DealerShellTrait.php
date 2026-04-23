<?php
/**
 * @brief  GD Dealer Manager — DealerShell Trait
 *
 * Shared helpers for front-end controllers that render inside the dealer
 * dashboard shell (left sidebar nav). Consumers must set $this->dealer
 * (of type \IPS\gddealer\Dealer\Dealer) before calling output() /
 * dealerSummary(). Extracted from dashboard.php in v1.0.55, redesigned
 * in v1.0.71 to use a SaaS-style left-sidebar layout.
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
		$css = '';
		$cssPath = \IPS\ROOT_PATH . '/applications/gddealer/dev/css/front/dealer.css';
		if ( file_exists( $cssPath ) ) {
			$css = '<style>' . file_get_contents( $cssPath ) . '</style>';
		}
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_frontend_dashboard_title' );
		\IPS\Output::i()->output = $css . \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->dealerShell(
			$this->dealerSummary(),
			$activeTab,
			$this->sidebarNav(),
			$body
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
			'card_theme'            => (function() use ( $d ) {
				try {
					$raw = (string) ( $d->dealer_dashboard_prefs ?? '' );
					if ( $raw !== '' ) {
						$stored = json_decode( $raw, true );
						if ( is_array( $stored ) && isset( $stored['card_theme'] ) ) {
							return (string) $stored['card_theme'];
						}
					}
				} catch ( \Throwable ) {}
				return 'default';
			})(),
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
			'customize'    => (string) \IPS\Http\Url::internal( $base . 'customize' ),
			'help'         => (string) \IPS\Http\Url::internal( $base . 'help' ),
		];
	}

	protected function supportUrl(): string
	{
		return (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support' );
	}

	/**
	 * Returns the nav structure consumed by the new dealerShell template.
	 */
	protected function sidebarNav(): array
	{
		$urls        = $this->tabUrls();
		$summary     = $this->dealerSummary();
		$canSupport  = Dealer::canAccessSupport( \IPS\Member::loggedIn() );
		$supportUrl  = $canSupport ? $this->supportUrl() : '';

		$unmatched = 0;
		try
		{
			$unmatched = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_unmatched_upcs',
				[ 'dealer_id=? AND status=?', (int) $this->dealer->dealer_id, 'pending' ]
			)->first();
		}
		catch ( \Exception ) {}

		$lang = \IPS\Member::loggedIn()->language();

		return [
			'main' => [
				'label' => 'Main',
				'items' => [
					[ 'key' => 'overview',  'label' => $lang->addToStack('gddealer_front_tab_overview'),
					  'url' => $urls['overview'],  'icon' => 'dashboard', 'badge' => null ],
					[ 'key' => 'listings',  'label' => $lang->addToStack('gddealer_front_tab_listings'),
					  'url' => $urls['listings'],  'icon' => 'listings',  'badge' => null ],
					[ 'key' => 'reviews',   'label' => $lang->addToStack('gddealer_front_tab_reviews'),
					  'url' => $urls['reviews'],   'icon' => 'reviews',
					  'badge' => $summary['new_reviews'] > 0 ? [ 'count' => $summary['new_reviews'], 'variant' => 'warn' ] : null ],
				]
			],
			'catalog' => [
				'label' => 'Catalog',
				'items' => [
					[ 'key' => 'feedSettings', 'label' => $lang->addToStack('gddealer_front_tab_feed'),
					  'url' => $urls['feedSettings'], 'icon' => 'feed', 'badge' => null ],
					[ 'key' => 'unmatched', 'label' => $lang->addToStack('gddealer_front_tab_unmatched'),
					  'url' => $urls['unmatched'], 'icon' => 'unmatched',
					  'badge' => $unmatched > 0 ? [ 'count' => $unmatched, 'variant' => 'urgent' ] : null ],
				]
			],
			'growth' => [
				'label' => 'Growth',
				'items' => [
					[ 'key' => 'analytics', 'label' => $lang->addToStack('gddealer_front_tab_analytics'),
					  'url' => $urls['analytics'], 'icon' => 'analytics', 'badge' => null ],
				]
			],
			'account' => [
				'label' => 'Account',
				'items' => array_values( array_filter( [
					[ 'key' => 'customize', 'label' => $lang->addToStack('gddealer_front_tab_edit_profile'),
					  'url' => $urls['customize'], 'icon' => 'profile', 'badge' => null ],
					[ 'key' => 'subscription', 'label' => $lang->addToStack('gddealer_front_tab_subscription'),
					  'url' => $urls['subscription'], 'icon' => 'billing', 'badge' => null ],
					[ 'key' => 'help', 'label' => $lang->addToStack('gddealer_front_tab_help'),
					  'url' => $urls['help'], 'icon' => 'help', 'badge' => null ],
					$canSupport ? [ 'key' => 'support', 'label' => $lang->addToStack('gddealer_support_nav'),
					  'url' => $supportUrl, 'icon' => 'support', 'badge' => null ] : null,
				] ) )
			],
		];
	}
}
