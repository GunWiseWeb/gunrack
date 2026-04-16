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

		$form->addHeader( 'gddealer_settings_member_groups' );

		$form->add( new \IPS\Helpers\Form\Number( 'gddealer_group_founding',
			(int) \IPS\Settings::i()->gddealer_group_founding, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gddealer_group_basic',
			(int) \IPS\Settings::i()->gddealer_group_basic, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gddealer_group_pro',
			(int) \IPS\Settings::i()->gddealer_group_pro, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gddealer_group_enterprise',
			(int) \IPS\Settings::i()->gddealer_group_enterprise, FALSE ) );

		$form->addHeader( 'gddealer_settings_general' );

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

		$form->addHeader( 'gddealer_commerce_header' );

		$form->add( new \IPS\Helpers\Form\Number( 'gddealer_commerce_basic_id',
			(int) \IPS\Settings::i()->gddealer_commerce_basic_id, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gddealer_commerce_pro_id',
			(int) \IPS\Settings::i()->gddealer_commerce_pro_id, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gddealer_commerce_enterprise_id',
			(int) \IPS\Settings::i()->gddealer_commerce_enterprise_id, FALSE ) );

		$form->addHeader( 'gddealer_settings_subscription_tab' );

		$settings = \IPS\Settings::i();

		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_subscription_billing_note',
			(string) ( $settings->gddealer_subscription_billing_note ?? '' ), FALSE, [ 'rows' => 3 ] ) );

		$subscribeUrlValue = (string) ( $settings->gddealer_subscribe_url ?? '' );
		$form->add( new \IPS\Helpers\Form\Url( 'gddealer_subscribe_url',
			$subscribeUrlValue ? \IPS\Http\Url::external( $subscribeUrlValue ) : null,
			FALSE ) );

		$form->addHeader( 'gddealer_settings_help_content' );

		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_help_intro',
			(string) ( $settings->gddealer_help_intro ?? '' ), FALSE, [ 'rows' => 3 ] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_help_step1',
			(string) ( $settings->gddealer_help_step1 ?? '' ), FALSE, [ 'rows' => 4 ] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_help_step2',
			(string) ( $settings->gddealer_help_step2 ?? '' ), FALSE, [ 'rows' => 4 ] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_help_step3',
			(string) ( $settings->gddealer_help_step3 ?? '' ), FALSE, [ 'rows' => 4 ] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_help_step4',
			(string) ( $settings->gddealer_help_step4 ?? '' ), FALSE, [ 'rows' => 4 ] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_help_step5',
			(string) ( $settings->gddealer_help_step5 ?? '' ), FALSE, [ 'rows' => 4 ] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_help_requirements',
			(string) ( $settings->gddealer_help_requirements ?? '' ), FALSE, [ 'rows' => 8 ] ) );
		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_help_contact',
			(string) ( $settings->gddealer_help_contact ?? '' ), FALSE ) );

		$form->addHeader( 'gddealer_settings_guidelines' );

		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_guidelines_buyer_title',
			(string) ( $settings->gddealer_guidelines_buyer_title ?? '' ), FALSE ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_guidelines_buyer_body',
			(string) ( $settings->gddealer_guidelines_buyer_body ?? '' ), FALSE, [ 'rows' => 10 ] ) );

		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_guidelines_dispute_title',
			(string) ( $settings->gddealer_guidelines_dispute_title ?? '' ), FALSE ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_guidelines_dispute_body',
			(string) ( $settings->gddealer_guidelines_dispute_body ?? '' ), FALSE, [ 'rows' => 12 ] ) );

		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_guidelines_dealer_title',
			(string) ( $settings->gddealer_guidelines_dealer_title ?? '' ), FALSE ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_guidelines_dealer_body',
			(string) ( $settings->gddealer_guidelines_dealer_body ?? '' ), FALSE, [ 'rows' => 10 ] ) );

		$form->addHeader( 'gddealer_settings_emails' );
		$form->addMessage( 'gddealer_settings_emails_help' );

		$getEmailTemplate = function( string $key, string $field ) {
			try {
				return (string) \IPS\Db::i()->select( $field, 'core_email_templates',
					[ 'template_app=? AND template_name=?', 'gddealer', $key ]
				)->first();
			} catch ( \Exception ) { return ''; }
		};

		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_email_welcome_subject',
			$getEmailTemplate( 'dealerWelcome', 'template_subject' ), FALSE, [],
			NULL, NULL, NULL, 'gddealer_email_welcome_subject' ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_email_welcome_body',
			$getEmailTemplate( 'dealerWelcome', 'template_body' ), FALSE, [ 'rows' => 8 ],
			NULL, NULL, NULL, 'gddealer_email_welcome_body' ) );

		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_email_expiring_subject',
			$getEmailTemplate( 'trialExpiringSoon', 'template_subject' ), FALSE, [],
			NULL, NULL, NULL, 'gddealer_email_expiring_subject' ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_email_expiring_body',
			$getEmailTemplate( 'trialExpiringSoon', 'template_body' ), FALSE, [ 'rows' => 8 ],
			NULL, NULL, NULL, 'gddealer_email_expiring_body' ) );

		$form->add( new \IPS\Helpers\Form\Text( 'gddealer_email_expired_subject',
			$getEmailTemplate( 'trialExpired', 'template_subject' ), FALSE, [],
			NULL, NULL, NULL, 'gddealer_email_expired_subject' ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_email_expired_body',
			$getEmailTemplate( 'trialExpired', 'template_body' ), FALSE, [ 'rows' => 8 ],
			NULL, NULL, NULL, 'gddealer_email_expired_body' ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( [
				'gddealer_group_founding'             => (int) $values['gddealer_group_founding'],
				'gddealer_group_basic'                => (int) $values['gddealer_group_basic'],
				'gddealer_group_pro'                  => (int) $values['gddealer_group_pro'],
				'gddealer_group_enterprise'           => (int) $values['gddealer_group_enterprise'],
				'gddealer_default_import_schedule'    => (string) $values['gddealer_default_import_schedule'],
				'gddealer_out_of_stock_grace_hours'   => (int) $values['gddealer_out_of_stock_grace_hours'],
				'gddealer_click_tracking_enabled'     => (int) $values['gddealer_click_tracking_enabled'],
				'gddealer_commerce_basic_id'          => (int) $values['gddealer_commerce_basic_id'],
				'gddealer_commerce_pro_id'            => (int) $values['gddealer_commerce_pro_id'],
				'gddealer_commerce_enterprise_id'     => (int) $values['gddealer_commerce_enterprise_id'],
				'gddealer_subscription_billing_note'  => (string) $values['gddealer_subscription_billing_note'],
				'gddealer_subscribe_url'              => (string) $values['gddealer_subscribe_url'],
				'gddealer_help_intro'                 => (string) $values['gddealer_help_intro'],
				'gddealer_help_step1'                 => (string) $values['gddealer_help_step1'],
				'gddealer_help_step2'                 => (string) $values['gddealer_help_step2'],
				'gddealer_help_step3'                 => (string) $values['gddealer_help_step3'],
				'gddealer_help_step4'                 => (string) $values['gddealer_help_step4'],
				'gddealer_help_step5'                 => (string) $values['gddealer_help_step5'],
				'gddealer_help_requirements'          => (string) $values['gddealer_help_requirements'],
				'gddealer_help_contact'               => (string) $values['gddealer_help_contact'],
				'gddealer_guidelines_buyer_title'     => (string) $values['gddealer_guidelines_buyer_title'],
				'gddealer_guidelines_buyer_body'      => (string) $values['gddealer_guidelines_buyer_body'],
				'gddealer_guidelines_dispute_title'   => (string) $values['gddealer_guidelines_dispute_title'],
				'gddealer_guidelines_dispute_body'    => (string) $values['gddealer_guidelines_dispute_body'],
				'gddealer_guidelines_dealer_title'    => (string) $values['gddealer_guidelines_dealer_title'],
				'gddealer_guidelines_dealer_body'     => (string) $values['gddealer_guidelines_dealer_body'],
			]);

			$updateEmail = function( string $key, string $subject, string $body ) {
				try {
					\IPS\Db::i()->update( 'core_email_templates',
						[ 'template_subject' => $subject, 'template_body' => $body ],
						[ 'template_app=? AND template_name=?', 'gddealer', $key ]
					);
				} catch ( \Exception ) {}
			};

			$updateEmail( 'dealerWelcome',     $values['gddealer_email_welcome_subject'],  $values['gddealer_email_welcome_body'] );
			$updateEmail( 'trialExpiringSoon', $values['gddealer_email_expiring_subject'], $values['gddealer_email_expiring_body'] );
			$updateEmail( 'trialExpired',      $values['gddealer_email_expired_subject'],  $values['gddealer_email_expired_body'] );

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
