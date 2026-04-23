<?php
namespace IPS\gddealer\setup\upg_10108;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $errors = [];
        $templatesFile = \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10090b.php';
        $newContent = null;

        try
        {
            if ( is_file( $templatesFile ) )
            {
                $raw = @file_get_contents( $templatesFile );
                if ( $raw !== false && preg_match( "/<<<'TEMPLATE_EOT'\n(.*?)\nTEMPLATE_EOT;/s", $raw, $m ) )
                {
                    $newContent = $m[1];
                }
            }
        }
        catch ( \Throwable $e ) { $errors[] = 'template read failed: ' . $e->getMessage(); }

        if ( $newContent )
        {
            try
            {
                \IPS\Db::i()->update( 'core_theme_templates',
                    [ 'template_data' => '$data', 'template_content' => $newContent, 'template_updated' => time() ],
                    [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
                      'gddealer', 'front', 'dealers', 'dealerProfile' ]
                );
            }
            catch ( \Throwable $e ) { $errors[] = 'template UPDATE failed: ' . $e->getMessage(); }
        }
        else { $errors[] = 'could not extract template body'; }

        /* Also apply v1.0.103 overview template card-theme injection. */
        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10103.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10103.php failed: ' . $e->getMessage(); }

        /* Apply v1.0.105 overview template re-injection (correct stat card classes). */
        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10105.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10105.php failed: ' . $e->getMessage(); }

        /* Apply v1.0.106 dealerShell card-theme injection (all dashboard card classes). */
        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10106.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10106.php failed: ' . $e->getMessage(); }

        /* Apply v1.0.107 dealerNavIcon fix (profile icon, Rule 18 compliant). */
        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10107.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10107.php (dealerNavIcon) failed: ' . $e->getMessage(); }

        /* Apply v1.0.107 dealerShell comprehensive card-text theming. */
        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10107_shell.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10107_shell.php (dealerShell) failed: ' . $e->getMessage(); }

        /* Apply v1.0.108 surgical card-theme (no wildcards, BEM slots only). */
        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10108_shell.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10108_shell.php failed: ' . $e->getMessage(); }

        try { \IPS\Db::i()->update( 'core_themes', [ 'set_cache_key' => md5( microtime() . mt_rand() ) ] ); }
        catch ( \Throwable $e ) { $errors[] = 'set_cache_key rotation failed: ' . $e->getMessage(); }

        try { \IPS\Theme::deleteCompiledTemplate( 'gddealer', 'front', 'dealers' ); }
        catch ( \Throwable $e ) { $errors[] = 'deleteCompiledTemplate failed: ' . $e->getMessage(); }

        try { \IPS\Data\Store::i()->clearAll(); } catch ( \Throwable $e ) { $errors[] = 'Store clearAll failed: ' . $e->getMessage(); }
        try { \IPS\Data\Cache::i()->clearAll(); } catch ( \Throwable $e ) { $errors[] = 'Cache clearAll failed: ' . $e->getMessage(); }

        try
        {
            foreach ( glob( \IPS\ROOT_PATH . '/datastore/template_*_dealers.*.php' ) ?: [] as $f ) { @unlink( $f ); }
        }
        catch ( \Throwable $e ) { $errors[] = 'unlink failed: ' . $e->getMessage(); }

        try { \IPS\Log::log( $errors ? implode( "\n", $errors ) : 'v1.0.108 upgrade completed cleanly', 'gddealer_upg_10108' ); }
        catch ( \Throwable ) {}

        return TRUE;
    }
}
class upgrade extends _upgrade {}
