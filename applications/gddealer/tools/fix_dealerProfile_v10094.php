<?php
/**
 * Manual recovery: applies v1.0.94 template fix to core_theme_templates
 * and clears all compiled-template caches. Safe to re-run.
 *
 * CLI: php -d memory_limit=256M applications/gddealer/tools/fix_dealerProfile_v10094.php
 *      (run from IPS ROOT with init.php in parent dirs)
 */

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    require_once __DIR__ . '/../../../init.php';
}

$report = [];

$templatesFile = \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10090b.php';
$report[] = "templates file: $templatesFile";
$report[] = 'exists: ' . ( is_file( $templatesFile ) ? 'yes' : 'NO' );

$newContent = null;
if ( is_file( $templatesFile ) )
{
    $raw = file_get_contents( $templatesFile );
    $report[] = 'raw size: ' . strlen( $raw );
    if ( preg_match( "/<<<'TEMPLATE_EOT'\n(.*?)\nTEMPLATE_EOT;/s", $raw, $m ) )
    {
        $newContent = $m[1];
        $report[] = 'extracted body: ' . strlen( $newContent ) . ' bytes';
        $report[] = 'starts with: ' . substr( $newContent, 0, 80 ) . '...';
    }
    else
    {
        $report[] = 'REGEX FAILED to match TEMPLATE_EOT block';
    }
}

if ( $newContent )
{
    try
    {
        $affected = \IPS\Db::i()->update( 'core_theme_templates',
            [ 'template_data' => '$data', 'template_content' => $newContent, 'template_updated' => time() ],
            [ "template_app=? AND template_location=? AND template_group=? AND template_name=?",
              'gddealer', 'front', 'dealers', 'dealerProfile' ]
        );
        $report[] = "UPDATE ok, affected rows: $affected";
    }
    catch ( \Throwable $e ) { $report[] = 'UPDATE failed: ' . $e->getMessage(); }
}

try { \IPS\Db::i()->update( 'core_themes', [ 'set_cache_key' => md5( microtime() . mt_rand() ) ] ); $report[] = 'set_cache_key rotated'; }
catch ( \Throwable $e ) { $report[] = 'set_cache_key failed: ' . $e->getMessage(); }

try { \IPS\Theme::deleteCompiledTemplate( 'gddealer', 'front', 'dealers' ); $report[] = 'deleteCompiledTemplate ok'; }
catch ( \Throwable $e ) { $report[] = 'deleteCompiledTemplate failed: ' . $e->getMessage(); }

try { \IPS\Data\Store::i()->clearAll(); $report[] = 'Store cleared'; }
catch ( \Throwable $e ) { $report[] = 'Store clearAll failed: ' . $e->getMessage(); }

try { \IPS\Data\Cache::i()->clearAll(); $report[] = 'Cache cleared'; }
catch ( \Throwable $e ) { $report[] = 'Cache clearAll failed: ' . $e->getMessage(); }

$pattern = \IPS\ROOT_PATH . '/datastore/template_*_dealers.*.php';
$unlinked = 0;
foreach ( glob( $pattern ) ?: [] as $f ) { if ( @unlink( $f ) ) $unlinked++; }
$report[] = "unlinked $unlinked compiled file(s)";

header( 'Content-Type: text/plain' );
echo implode( "\n", $report ) . "\n";
