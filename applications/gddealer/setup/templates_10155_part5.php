<?php
if ( !defined( '\\IPS\\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.155 PART 5 of 6 - Persist setupWizardStep4 template to database.
 *
 * Must run after parts 3 and 4 which assemble $step4Tpl.
 */

if ( !isset( $step4Tpl ) )
{
    throw new \RuntimeException( 'templates_10155_part5.php loaded before part3+part4' );
}

$templateData = '$wizardData,$values';

try
{
    \IPS\Db::i()->insert( 'core_theme_templates',
        [
            'template_set_id'   => 1,
            'template_app'      => 'gddealer',
            'template_location' => 'front',
            'template_group'    => 'dealers',
            'template_name'     => 'setupWizardStep4',
            'template_data'     => $templateData,
            'template_content'  => $step4Tpl,
            'template_updated'  => time(),
        ],
        TRUE
    );

    \IPS\Db::i()->update( 'core_theme_templates',
        [
            'template_data'    => $templateData,
            'template_content' => $step4Tpl,
            'template_updated' => time(),
        ],
        [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
          'gddealer', 'front', 'dealers', 'setupWizardStep4' ]
    );
}
catch ( \Throwable $e )
{
    try { \IPS\Log::log( 'templates_10155_part5.php failed: ' . $e->getMessage(), 'gddealer_upg_10155' ); }
    catch ( \Throwable ) {}
}
