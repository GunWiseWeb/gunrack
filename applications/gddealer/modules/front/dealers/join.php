<?php
/**
 * @brief       GD Dealer Manager — Frontend Dealer Onboarding (/dealers/join)
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       16 Apr 2026
 *
 * Section 3.3 of the spec. Public-facing landing page that:
 *   - advertises the subscription tier options and what each includes
 *   - tells prospective dealers what to do next
 *
 * Until IPS Commerce + Stripe is live, this page does NOT take payment.
 * Interested dealers submit contact details via a simple request form
 * and a GunRack admin runs the ACP "Manual Onboard" action to provision
 * their account. Once Commerce is wired (Phase 1 of the spec), this
 * controller's checkout() method will redirect straight into the IPS
 * nexus subscription flow.
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
	 * Landing page — pricing table + CTA.
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

		$requestUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=join&do=requestAccess'
		);

		$tiers = [
			[
				'key'         => Dealer::TIER_BASIC,
				'label'       => 'Basic',
				'price'       => '$39 / month',
				'schedule'    => 'Feed imports every 6 hours',
				'features'    => [
					'Unlimited listings',
					'XML / JSON / CSV feed formats',
					'Import logs + unmatched UPC report',
					'Basic click-through stats',
				],
				'commerce_url' => $this->commerceUrlFor( (int) \IPS\Settings::i()->gddealer_commerce_basic_id, $requestUrl ),
			],
			[
				'key'         => Dealer::TIER_PRO,
				'label'       => 'Pro',
				'price'       => '$99 / month',
				'schedule'    => 'Feed imports every 30 minutes',
				'features'    => [
					'Everything in Basic',
					'Priority placement in price comparison',
					'Full analytics — top listings, price competitiveness',
					'Revenue opportunity report',
				],
				'commerce_url' => $this->commerceUrlFor( (int) \IPS\Settings::i()->gddealer_commerce_pro_id, $requestUrl ),
				'featured'     => TRUE,
			],
			[
				'key'         => Dealer::TIER_ENTERPRISE,
				'label'       => 'Enterprise',
				'price'       => '$249 / month',
				'schedule'    => 'Feed imports every 15 minutes',
				'features'    => [
					'Everything in Pro',
					'Fastest feed sync available',
					'Dedicated onboarding support',
					'Early access to new features',
				],
				'commerce_url' => $this->commerceUrlFor( (int) \IPS\Settings::i()->gddealer_commerce_enterprise_id, $requestUrl ),
			],
		];

		/* Normalize — PHP warns if 'featured' is missing on a row, so default
		 * it on every tier to keep the template simple. */
		foreach ( $tiers as &$t )
		{
			$t['featured'] = !empty( $t['featured'] );
		}
		unset( $t );

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_front_join_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->join(
			$tiers,
			$requestUrl
		);
	}

	/**
	 * Resolve a tier's CTA URL. If an IPS Commerce product ID is configured
	 * in the gddealer_commerce_*_id settings, send the dealer straight into
	 * the nexus checkout flow for that product. Otherwise fall back to the
	 * manual "Request Dealer Access" form — needed for the stub onboarding
	 * path before Commerce+Stripe goes live.
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

	/**
	 * Interim "request access" form. Collects contact details and the
	 * desired tier, then emails the GunRack admin team. No DB row is
	 * created here — a human runs the ACP Manual Onboard action after
	 * verifying the dealer's FFL.
	 */
	protected function requestAccess()
	{
		$form = new \IPS\Helpers\Form( 'form', 'gddealer_front_request_submit' );
		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_front_request_business', '', TRUE ) );
		$form->add( new \IPS\Helpers\Form\Email( 'gddealer_front_request_email', \IPS\Member::loggedIn()->email ?: '', TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_front_request_ffl', '', TRUE ) );
		$form->add( new \IPS\Helpers\Form\Url( 'gddealer_front_request_website', '', FALSE ) );
		$form->add( new \IPS\Helpers\Form\Select( 'gddealer_front_request_tier', Dealer::TIER_BASIC, TRUE, [
			'options' => [
				Dealer::TIER_BASIC      => 'Basic — $39/mo',
				Dealer::TIER_PRO        => 'Pro — $99/mo',
				Dealer::TIER_ENTERPRISE => 'Enterprise — $249/mo',
			],
		] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_front_request_notes', '', FALSE ) );

		if ( $values = $form->values() )
		{
			$payload = [
				'business' => (string) $values['gddealer_front_request_business'],
				'email'    => (string) $values['gddealer_front_request_email'],
				'ffl'      => (string) $values['gddealer_front_request_ffl'],
				'website'  => (string) $values['gddealer_front_request_website'],
				'tier'     => (string) $values['gddealer_front_request_tier'],
				'notes'    => (string) $values['gddealer_front_request_notes'],
				'member'   => (int) \IPS\Member::loggedIn()->member_id,
			];

			try
			{
				\IPS\Log::log( json_encode( $payload ), 'gddealer_onboarding_request' );
			}
			catch ( \Throwable ) { /* don't block the user flow on logging */ }

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join&do=thanks' )
			);
			return;
		}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_front_request_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->joinRequest( (string) $form );
	}

	protected function thanks()
	{
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_front_thanks_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->joinThanks();
	}
}

class join extends _join {}
