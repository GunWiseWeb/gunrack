<?php
/**
 * @brief       GD Price Comparison — Settings controller
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
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
		\IPS\Dispatcher::i()->checkAcpPermission( 'can_manage_settings' );
		parent::execute();
	}

	protected function manage(): void
	{
		$settings = \IPS\Settings::i();

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			$updates = [
				'gdpc_default_sort'                   => (string) \IPS\Request::i()->gdpc_default_sort,
				'gdpc_free_ship_threshold'            => (string) (float) \IPS\Request::i()->gdpc_free_ship_threshold,
				'gdpc_cpr_include_shipping_default'   => \IPS\Request::i()->gdpc_cpr_include_shipping_default ? '1' : '0',
				'gdpc_ffl_radius_default'             => (string) max( 1, (int) \IPS\Request::i()->gdpc_ffl_radius_default ),
				'gdpc_price_history_days_basic'       => (string) max( 1, (int) \IPS\Request::i()->gdpc_price_history_days_basic ),
				'gdpc_price_history_days_pro'         => (string) max( 1, (int) \IPS\Request::i()->gdpc_price_history_days_pro ),
				'gdpc_price_history_days_enterprise'  => (string) max( 1, (int) \IPS\Request::i()->gdpc_price_history_days_enterprise ),
				'gdpc_google_maps_api_key'            => (string) \IPS\Request::i()->gdpc_google_maps_api_key,
				'gdpc_alert_dedupe_hours'             => (string) max( 1, (int) \IPS\Request::i()->gdpc_alert_dedupe_hours ),
				'gdpc_report_priority_threshold'      => (string) max( 1, (int) \IPS\Request::i()->gdpc_report_priority_threshold ),
			];

			foreach ( $updates as $k => $v )
			{
				\IPS\Db::i()->update( 'core_sys_conf_settings', [ 'conf_value' => $v ], [ 'conf_key=?', $k ]);
			}
			\IPS\Settings::i()->clearCache();

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=settings' ),
				'gdpc_settings_saved'
			);
			return;
		}

		$data = [
			'default_sort'           => (string) ( $settings->gdpc_default_sort ?? 'total_price_asc' ),
			'free_ship_threshold'    => (string) ( $settings->gdpc_free_ship_threshold ?? '0' ),
			'cpr_include_shipping'   => (int) ( $settings->gdpc_cpr_include_shipping_default ?? 0 ) === 1,
			'ffl_radius'             => (int) ( $settings->gdpc_ffl_radius_default ?? 25 ),
			'history_basic'          => (int) ( $settings->gdpc_price_history_days_basic ?? 30 ),
			'history_pro'            => (int) ( $settings->gdpc_price_history_days_pro ?? 90 ),
			'history_enterprise'     => (int) ( $settings->gdpc_price_history_days_enterprise ?? 365 ),
			'google_maps_key'        => (string) ( $settings->gdpc_google_maps_api_key ?? '' ),
			'alert_dedupe_hours'     => (int) ( $settings->gdpc_alert_dedupe_hours ?? 24 ),
			'report_threshold'       => (int) ( $settings->gdpc_report_priority_threshold ?? 3 ),
			'csrf_key'               => \IPS\Session::i()->csrfKey,
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_settings_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'admin' )
			->settings( $data );
	}
}

class settings extends _settings {}
