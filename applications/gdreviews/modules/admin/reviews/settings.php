<?php
/**
 * @brief       GD Product Reviews — Settings controller
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 *
 * Uses \IPS\Helpers\Form so the ACP chrome (section headers, native
 * field styling, Save/Cancel footer) renders automatically. Field
 * names match data/settings.json keys so IPS resolves labels and
 * descriptions from lang.xml (key = field name, key_desc = description).
 */

namespace IPS\gdreviews\modules\admin\reviews;

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
		\IPS\Dispatcher::i()->checkAcpPermission( 'reviews_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$settings = \IPS\Settings::i();
		$form     = new \IPS\Helpers\Form;

		$form->addHeader( 'gdr_settings_section_submission' );
		$form->add( new \IPS\Helpers\Form\Number( 'gdr_min_body_chars',
			(int) ( $settings->gdr_min_body_chars ?? 50 ), TRUE, [ 'min' => 50 ] ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gdr_max_images_per_review',
			(int) ( $settings->gdr_max_images_per_review ?? 5 ), TRUE, [ 'min' => 0, 'max' => 5 ] ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gdr_max_image_size_mb',
			(int) ( $settings->gdr_max_image_size_mb ?? 5 ), TRUE, [ 'min' => 1, 'max' => 5 ] ) );

		$form->addHeader( 'gdr_settings_section_moderation' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'gdr_auto_approve_verified',
			(bool) ( $settings->gdr_auto_approve_verified ?? 0 ), FALSE ) );

		$form->addHeader( 'gdr_settings_section_hub' );
		$form->add( new \IPS\Helpers\Form\Number( 'gdr_hub_latest_limit',
			(int) ( $settings->gdr_hub_latest_limit ?? 20 ), TRUE, [ 'min' => 1, 'max' => 100 ] ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gdr_top_rated_min_reviews',
			(int) ( $settings->gdr_top_rated_min_reviews ?? 5 ), TRUE, [ 'min' => 1 ] ) );
		$form->add( new \IPS\Helpers\Form\Number( 'gdr_featured_review_id',
			(int) ( $settings->gdr_featured_review_id ?? 0 ), FALSE, [ 'min' => 0 ] ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( [
				'gdr_min_body_chars'        => (int) $values['gdr_min_body_chars'],
				'gdr_max_images_per_review' => (int) $values['gdr_max_images_per_review'],
				'gdr_max_image_size_mb'     => (int) $values['gdr_max_image_size_mb'],
				'gdr_auto_approve_verified' => (int) $values['gdr_auto_approve_verified'],
				'gdr_hub_latest_limit'      => (int) $values['gdr_hub_latest_limit'],
				'gdr_top_rated_min_reviews' => (int) $values['gdr_top_rated_min_reviews'],
				'gdr_featured_review_id'    => (int) $values['gdr_featured_review_id'],
			]);

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdreviews&module=reviews&controller=settings' ),
				'gdr_settings_saved'
			);
		}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_settings_title' );
		\IPS\Output::i()->output = (string) $form;
	}
}

class settings extends _settings {}
