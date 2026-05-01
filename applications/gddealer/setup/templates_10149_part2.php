<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.149 PART 2 of 3 - Persist setupWizardStep1 template to database.
 *
 * Must run AFTER part 1 (which defines $step1Tpl).
 *
 * Following project rule #19: uses replace() to insert-or-update the row
 * idempotently. Existing template rows for this name get overwritten;
 * fresh installs get a new row inserted.
 */

if ( !isset( $step1Tpl ) )
{
    throw new \RuntimeException( 'templates_10149_part2.php loaded before part1' );
}

/* The template accepts 3 named arguments: $wizardData, $values, $errors. */
$templateData = '$wizardData,$values,$errors=array()';

try
{
    /* Insert if missing. */
    \IPS\Db::i()->insert( 'core_theme_templates',
        [
            'template_set_id'   => 1,
            'template_app'      => 'gddealer',
            'template_location' => 'front',
            'template_group'    => 'dealers',
            'template_name'     => 'setupWizardStep1',
            'template_data'     => $templateData,
            'template_content'  => $step1Tpl,
            'template_updated'  => time(),
        ],
        TRUE
    );

    /* Update if it exists (no-op for fresh installs - the IGNORE flag
     * above already handled the new-row case). */
    \IPS\Db::i()->update( 'core_theme_templates',
        [
            'template_data'    => $templateData,
            'template_content' => $step1Tpl,
            'template_updated' => time(),
        ],
        [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
          'gddealer', 'front', 'dealers', 'setupWizardStep1' ]
    );
}
catch ( \Throwable $e )
{
    try { \IPS\Log::log( 'templates_10149_part2.php failed: ' . $e->getMessage(), 'gddealer_upg_10149' ); }
    catch ( \Throwable ) {}
}
