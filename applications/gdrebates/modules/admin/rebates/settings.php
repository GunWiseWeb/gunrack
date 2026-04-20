<?php
/**
 * @brief       GD Rebates — Settings form
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * IPS Form builder-backed settings page. Uses saveAsSettings() so each
 * field persists into core_sys_conf_settings keyed by the field name
 * exactly (see data/settings.json for the default values).
 */

namespace IPS\gdrebates\modules\admin\rebates;

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
		\IPS\Dispatcher::i()->checkAcpPermission( 'rebates_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$member   = \IPS\Member::loggedIn();
		$lang     = $member->language();
		$settings = \IPS\Settings::i();

		$form = new \IPS\Helpers\Form( 'gdr_settings', 'gdr_settings_save' );

		$form->addHeader( $lang->addToStack( 'gdr_settings_scraper_header' ) );
		$form->add( new \IPS\Helpers\Form\YesNo(   'gdr_scraper_enabled',         (int) ( $settings->gdr_scraper_enabled ?? 0 ) === 1 ) );
		$form->add( new \IPS\Helpers\Form\Text(    'gdr_scraper_user_agent',      (string) ( $settings->gdr_scraper_user_agent ?? 'GunRackDealsBot/1.0' ), TRUE ) );
		$form->add( new \IPS\Helpers\Form\Number(  'gdr_scraper_timeout',         max( 1, (int) ( $settings->gdr_scraper_timeout ?? 15 ) ), TRUE, [ 'min' => 1, 'max' => 120 ] ) );
		$form->add( new \IPS\Helpers\Form\Number(  'gdr_scraper_default_rate_ms', max( 0, (int) ( $settings->gdr_scraper_default_rate_ms ?? 2000 ) ), TRUE, [ 'min' => 0 ] ) );
		$form->add( new \IPS\Helpers\Form\YesNo(   'gdr_scraper_respect_robots',  (int) ( $settings->gdr_scraper_respect_robots ?? 1 ) === 1 ) );

		$form->addHeader( $lang->addToStack( 'gdr_settings_hub_header' ) );
		$form->add( new \IPS\Helpers\Form\Number(  'gdr_featured_rebate_id',      max( 0, (int) ( $settings->gdr_featured_rebate_id ?? 0 ) ), FALSE, [ 'min' => 0 ] ) );
		$form->add( new \IPS\Helpers\Form\Number(  'gdr_hub_expiring_days',       max( 1, (int) ( $settings->gdr_hub_expiring_days ?? 7 ) ), TRUE, [ 'min' => 1, 'max' => 90 ] ) );

		$form->addHeader( $lang->addToStack( 'gdr_settings_moderation_header' ) );
		$form->add( new \IPS\Helpers\Form\Number(  'gdr_archive_after_days',      max( 1, (int) ( $settings->gdr_archive_after_days ?? 30 ) ), TRUE, [ 'min' => 1, 'max' => 365 ] ) );
		$form->add( new \IPS\Helpers\Form\Number(  'gdr_flag_threshold',          max( 1, (int) ( $settings->gdr_flag_threshold ?? 3 ) ), TRUE, [ 'min' => 1, 'max' => 100 ] ) );
		$form->add( new \IPS\Helpers\Form\Number(  'gdr_submission_min_desc',     max( 0, (int) ( $settings->gdr_submission_min_desc ?? 30 ) ), TRUE, [ 'min' => 0, 'max' => 5000 ] ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( $values );
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=settings' ),
				'gdr_settings_saved'
			);
			return;
		}

		\IPS\Output::i()->title  = $lang->addToStack( 'gdr_settings_title' );
		\IPS\Output::i()->output = (string) $form;
	}
}

class settings extends _settings {}
