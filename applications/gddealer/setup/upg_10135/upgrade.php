<?php
namespace IPS\gddealer\setup\upg_10135;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $errors = [];

        /* Restore the dealerDirectory layout that v1.0.134 incorrectly stripped:
           sort dropdown, grid/list view toggle (with localStorage), and 1446px
           max-width. */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10135.php';
        }
        catch ( \Throwable $e )
        {
            $errors[] = 'templates_10135.php failed: ' . $e->getMessage();
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
            try { \IPS\Log::log( 'v1.0.135 upgrade errors: ' . implode( ' | ', $errors ), 'gddealer_upg_10135' ); }
            catch ( \Throwable ) {}
        }

        return TRUE;
    }

    public function step1CustomTitle()
    {
        return 'Restoring dealer directory sort dropdown, grid/list toggle, and 1446px width';
    }
}
class upgrade extends _upgrade {}
