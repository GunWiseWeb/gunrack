<?php
/**
 * @brief       GD Dealer Manager — Frontend Dealer Join Page (/dealers/join)
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       16 Apr 2026
 *
 * Section 3.3 of the spec. Public-facing landing page that advertises
 * the subscription tiers and links directly to IPS Commerce checkout.
 */

namespace IPS\gddealer\modules\front\dealers;

use IPS\gddealer\Dealer\Dealer;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _join extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		parent::execute();
	}

	/**
	 * Landing page — hero, pricing cards, how-it-works, FAQ, bottom CTA.
	 * If the logged-in member already has a gd_dealer_feed_config row,
	 * they are sent to their dashboard instead.
	 */
	protected function manage()
	{
		if ( \IPS\Member::loggedIn()->member_id )
		{
			try
			{
				Dealer::load( (int) \IPS\Member::loggedIn()->member_id );
				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard' )
				);
				return;
			}
			catch ( \OutOfRangeException ) { /* not a dealer yet, show landing */ }
		}

		$fallbackUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=join'
		);

		$tiers = [
			[
				'key'          => Dealer::TIER_BASIC,
				'label'        => 'Basic',
				'price'        => '$39 / month',
				'schedule'     => 'Every 6 hours',
				'popular'      => false,
				'features'     => [
					'Unlimited listings',
					'XML / JSON / CSV feed formats',
					'Import logs + unmatched UPC report',
					'Basic click-through stats',
				],
				'commerce_url' => $this->commerceUrlFor( (int) \IPS\Settings::i()->gddealer_commerce_basic_id, $fallbackUrl ),
			],
			[
				'key'          => Dealer::TIER_PRO,
				'label'        => 'Pro',
				'price'        => '$99 / month',
				'schedule'     => 'Every 30 minutes',
				'popular'      => true,
				'features'     => [
					'Everything in Basic',
					'Priority placement in price comparison',
					'Full analytics — top listings, price competitiveness',
					'Revenue opportunity report',
				],
				'commerce_url' => $this->commerceUrlFor( (int) \IPS\Settings::i()->gddealer_commerce_pro_id, $fallbackUrl ),
			],
			[
				'key'          => Dealer::TIER_ENTERPRISE,
				'label'        => 'Enterprise',
				'price'        => '$249 / month',
				'schedule'     => 'Every 15 minutes',
				'popular'      => false,
				'features'     => [
					'Everything in Pro',
					'Fastest feed sync available',
					'Dedicated onboarding support',
					'Early access to new features',
				],
				'commerce_url' => $this->commerceUrlFor( (int) \IPS\Settings::i()->gddealer_commerce_enterprise_id, $fallbackUrl ),
			],
		];

		$contactEmail = (string) ( \IPS\Settings::i()->gddealer_help_contact ?: 'dealers@gunrack.deals' );

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_front_join_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->join(
			$tiers,
			$contactEmail
		);
	}

	/**
	 * Resolve a tier's CTA URL. If an IPS Commerce product ID is configured,
	 * send the dealer straight into the nexus checkout flow. Otherwise fall
	 * back to the join landing page itself.
	 */
	protected function commerceUrlFor( int $productId, string $fallback ): string
	{
		if ( $productId <= 0 )
		{
			return $fallback;
		}
		return (string) \IPS\Http\Url::internal(
			'app=nexus&module=store&controller=product&id=' . $productId
		);
	}
}

class join extends _join {}
