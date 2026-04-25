<?php
namespace IPS\gddealer\setup\upg_10134;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $errors = [];

        /* Replace dealerDirectory template — strips broken UI (tier filter,
           sort dropdown, grid/list toggle) and fixes the search form so it
           routes back to gddealer instead of falling through to /index.php
           and the default app. */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10134.php';
        }
        catch ( \Throwable $e )
        {
            $errors[] = 'templates_10134.php failed: ' . $e->getMessage();
        }

        /* Cache busting (rule #40) */
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

        /* Datastore key invalidation (rule #22) */
        try { unset( \IPS\Data\Store::i()->extensions );   } catch ( \Throwable ) {}
        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll();            } catch ( \Throwable ) {}

        /* Belt-and-suspenders for compiled template cache */
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
            try { \IPS\Log::log( 'v1.0.134 upgrade errors: ' . implode( ' | ', $errors ), 'gddealer_upg_10134' ); }
            catch ( \Throwable ) {}
        }

        return TRUE;
    }

    public function step1CustomTitle()
    {
        return 'Stripping broken UI and fixing dealer directory search';
    }
}
class upgrade extends _upgrade {}
