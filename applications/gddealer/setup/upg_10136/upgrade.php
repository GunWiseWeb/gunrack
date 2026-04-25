<?php
namespace IPS\gddealer\setup\upg_10136;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $errors = [];

        /* Two fixes in this version:
           1. Re-seed dealerDirectory with the v1.0.133 layout (sort dropdown,
              grid/list toggle, 1446px width) that v1.0.134 incorrectly removed.
           2. Patch modules/front/dealers/directory.php to pass seoTemplate to
              every Url::internal() call so friendly URLs work. The PHP file
              ships in the tarball; this upgrade just clears caches. */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10136.php';
        }
        catch ( \Throwable $e )
        {
            $errors[] = 'templates_10136.php failed: ' . $e->getMessage();
        }

        /* Bust IPS template cache (rule #40). */
        try { \IPS\Db::i()->delete( 'core_cache' ); } catch ( \Throwable ) {}
        try
        {
            \IPS\Db::i()->delete( 'core_store',
                [ "store_key LIKE 'theme_%' OR store_key LIKE 'template_%'" ] );
        }
        catch ( \Throwable ) {}

        foreach ( glob( \IPS\ROOT_PATH . '/datastore/template_*dealers*' ) ?: [] as $f )
        {
            @unlink( $f );
        }

        try { unset( \IPS\Data\Store::i()->extensions );   } catch ( \Throwable ) {}
        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll();            } catch ( \Throwable ) {}

        try
        {
            if ( method_exists( '\IPS\Theme', 'deleteCompiledTemplate' ) )
            {
                \IPS\Theme::deleteCompiledTemplate( 'gddealer', 'front', 'dealers' );
            }
        }
        catch ( \Throwable ) {}

        if ( !empty( $errors ) )
        {
            try { \IPS\Log::log( 'v1.0.136 upgrade errors: ' . implode( ' | ', $errors ), 'gddealer_upg_10136' ); }
            catch ( \Throwable ) {}
        }

        return TRUE;
    }

    public function step1CustomTitle()
    {
        return 'Restoring directory layout and fixing friendly URL generation';
    }
}
class upgrade extends _upgrade {}
