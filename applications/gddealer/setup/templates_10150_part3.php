<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.150 PART 3 of 3 - Persist setupWizardStep2 template to database.
 *
 * Must run after parts 1 and 2 which assemble $step2Tpl. Uses
 * insert+update so it works for both fresh installs and existing rows.
 */

if ( !isset( $step2Tpl ) )
{
    throw new \RuntimeException( 'templates_10150_part3.php loaded before part1+part2' );
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
            'template_name'     => 'setupWizardStep2',
            'template_data'     => $templateData,
            'template_content'  => $step2Tpl,
            'template_updated'  => time(),
        ],
        TRUE
    );

    \IPS\Db::i()->update( 'core_theme_templates',
        [
            'template_data'    => $templateData,
            'template_content' => $step2Tpl,
            'template_updated' => time(),
        ],
        [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
          'gddealer', 'front', 'dealers', 'setupWizardStep2' ]
    );
}
catch ( \Throwable $e )
{
    try { \IPS\Log::log( 'templates_10150_part3.php failed: ' . $e->getMessage(), 'gddealer_upg_10150' ); }
    catch ( \Throwable ) {}
}
