<?php
/**
 * @brief       GD Dealer Manager — ACP Settings
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 */

namespace IPS\gddealer\modules\admin\dealers;

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
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		parent::execute();
	}

	protected function manage()
	{
		$form = new \IPS\Helpers\Form;

		$form->add( new \IPS\Helpers\Form\Number( 'gddealer_group_id',
			(int) \IPS\Settings::i()->gddealer_group_id, TRUE ) );

		$form->add( new \IPS\Helpers\Form\Select( 'gddealer_default_import_schedule',
			(string) \IPS\Settings::i()->gddealer_default_import_schedule, TRUE, [
				'options' => [
					'15min' => 'Every 15 minutes',
					'30min' => 'Every 30 minutes',
					'1hr'   => 'Hourly',
					'6hr'   => 'Every 6 hours',
					'daily' => 'Daily',
				],
			] ) );

		$form->add( new \IPS\Helpers\Form\Number( 'gddealer_out_of_stock_grace_hours',
			(int) \IPS\Settings::i()->gddealer_out_of_stock_grace_hours, TRUE ) );

		$form->add( new \IPS\Helpers\Form\YesNo( 'gddealer_click_tracking_enabled',
			(bool) \IPS\Settings::i()->gddealer_click_tracking_enabled, FALSE ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( [
				'gddealer_group_id'                => (int) $values['gddealer_group_id'],
				'gddealer_default_import_schedule' => (string) $values['gddealer_default_import_schedule'],
				'gddealer_out_of_stock_grace_hours'=> (int) $values['gddealer_out_of_stock_grace_hours'],
				'gddealer_click_tracking_enabled'  => (int) $values['gddealer_click_tracking_enabled'],
			]);

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=settings' ),
				'saved'
			);
		}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_settings_title' );
		\IPS\Output::i()->output = (string) $form;
	}
}

class settings extends _settings {}
