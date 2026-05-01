<?php
namespace IPS\gddealer\setup\upg_10156;

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
        /* Controller patch was applied at build time. The patched
         * setupwizard.php is already in place. We just need to
         * re-seed the step 4 template (which is the actual fix). */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10156_part1.php';
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10156_part2.php';
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'v1.0.156 templates failed: ' . $e->getMessage(), 'gddealer_upg_10156' ); } catch ( \Throwable ) {}
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
        return 'EX0 hotfix - pre-compute step 4 stat-pill classes (HTML attribute parser was choking on > inside {{if}})';
    }
}

class upgrade extends _upgrade {}
