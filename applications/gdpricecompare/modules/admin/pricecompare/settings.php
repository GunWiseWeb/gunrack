<?php
/**
 * @brief       GD Price Comparison — Settings controller
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Uses the IPS form builder (\IPS\Helpers\Form) so the page renders with
 * the full ACP chrome — grouped sections via addHeader(), native field
 * types, and the standard save/cancel footer — matching Plugin 2's
 * settings pattern exactly.
 */

namespace IPS\gdpricecompare\modules\admin\pricecompare;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _settings extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'pricecompare_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$settings = \IPS\Settings::i();
		$form     = new \IPS\Helpers\Form;

		$form->addHeader( 'gdpc_settings_section_display' );
		$form->add( new \IPS\Helpers\Form\Select( 'gdpc_default_sort',
			(string) ( $settings->gdpc_default_sort ?? 'total_price_asc' ), TRUE, [
				'options' => [
					'total_price_asc'  => 'Total price, lowest first',
					'total_price_desc' => 'Total price, highest first',
					'price_asc'        => 'Dealer price, lowest first',
					'cpr_asc'          => 'Cost per round, lowest first',
				],
			] ) );

		$form->addHeader( 'gdpc_settings_section_shipping' );
		$form->add( new \IPS\Helpers\Form\Number( 'gdpc_free_ship_threshold',
			(float) ( $settings->gdpc_free_ship_threshold ?? 0 ), TRUE, [
				'decimals' => 2,
				'min'      => 0,
			] ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'gdpc_cpr_include_shipping_default',
			(bool) ( $settings->gdpc_cpr_include_shipping_default ?? 0 ), FALSE ) );

		$form->addHeader( 'gdpc_settings_section_ffl' );
		$form->add( new \IPS\Helpers\Form\Number( 'gdpc_ffl_radius_default',
			(int) ( $settings->gdpc_ffl_radius_default ?? 25 ), TRUE, [
				'min' => 1,
				'max' => 200,
			] ) );
		$form->add( new \IPS\Helpers\Form\Text( 'gdpc_google_maps_api_key',
			(string) ( $settings->gdpc_google_maps_api_key ?? '' ), FALSE ) );

		$form->addHeader( 'gdpc_settings_section_history' );
		$form->add( new \IPS\Helpers\Form\Number( 'gdpc_price_history_days_basic',
			(int) ( $settings->gdpc_price_history_days_basic ?? 30 ), TRUE, [ 'min' => 1 ] ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gdpc_price_history_days_pro',
			(int) ( $settings->gdpc_price_history_days_pro ?? 90 ), TRUE, [ 'min' => 1 ] ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gdpc_price_history_days_enterprise',
			(int) ( $settings->gdpc_price_history_days_enterprise ?? 365 ), TRUE, [ 'min' => 1 ] ) );

		$form->addHeader( 'gdpc_settings_section_alerts' );
		$form->add( new \IPS\Helpers\Form\Number( 'gdpc_alert_dedupe_hours',
			(int) ( $settings->gdpc_alert_dedupe_hours ?? 24 ), TRUE, [ 'min' => 1 ] ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gdpc_report_priority_threshold',
			(int) ( $settings->gdpc_report_priority_threshold ?? 3 ), TRUE, [ 'min' => 1 ] ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( [
				'gdpc_default_sort'                  => (string) $values['gdpc_default_sort'],
				'gdpc_free_ship_threshold'           => (string) (float) $values['gdpc_free_ship_threshold'],
				'gdpc_cpr_include_shipping_default'  => (int) $values['gdpc_cpr_include_shipping_default'],
				'gdpc_ffl_radius_default'            => (int) $values['gdpc_ffl_radius_default'],
				'gdpc_price_history_days_basic'      => (int) $values['gdpc_price_history_days_basic'],
				'gdpc_price_history_days_pro'        => (int) $values['gdpc_price_history_days_pro'],
				'gdpc_price_history_days_enterprise' => (int) $values['gdpc_price_history_days_enterprise'],
				'gdpc_google_maps_api_key'           => (string) $values['gdpc_google_maps_api_key'],
				'gdpc_alert_dedupe_hours'            => (int) $values['gdpc_alert_dedupe_hours'],
				'gdpc_report_priority_threshold'     => (int) $values['gdpc_report_priority_threshold'],
			]);

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=settings' ),
				'gdpc_settings_saved'
			);
		}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_settings_title' );
		\IPS\Output::i()->output = (string) $form;
	}
}

class settings extends _settings {}
