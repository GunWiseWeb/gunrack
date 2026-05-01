<?php
namespace IPS\gddealer\setup\upg_10151;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _upgrade
{
    public function step1(): bool
    {
        /* Replace the broken v1.0.150 setupWizardStep2 template with a
         * version that uses only safe display interpolation - no inline
         * function calls or cast expressions in {} braces. The IPS
         * template compiler rejects those and produces EX0 at runtime. */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10151.php';
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'v1.0.151 templates failed: ' . $e->getMessage(), 'gddealer_upg_10151' ); } catch ( \Throwable ) {}
        }

        try { \IPS\Db::i()->delete( 'core_cache' ); } catch ( \Throwable ) {}
        try { \IPS\Db::i()->delete( 'core_store', [ "store_key LIKE 'theme_%' OR store_key LIKE 'template_%'" ] ); } catch ( \Throwable ) {}

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

        return TRUE;
    }

    public function step1CustomTitle()
    {
        return 'Hotfix: Setup Wizard step 2 EX0 error';
    }
}

class upgrade extends _upgrade {}
