<?php
namespace IPS\gddealer\setup\upg_10147;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        /* Update help template (one file) */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10147_help.php';
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'v1.0.147 help template failed: ' . $e->getMessage(), 'gddealer_upg_10147' ); } catch ( \Throwable ) {}
        }

        /* Update public schema page (3 parts assembled in sequence) */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10147_schema_part1.php';
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10147_schema_part2.php';
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10147_schema_part3.php';
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'v1.0.147 schema templates failed: ' . $e->getMessage(), 'gddealer_upg_10147' ); } catch ( \Throwable ) {}
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
        return 'Adding new ammo subfields (fire_type, bullet_design, tip_color, case_material)';
    }
}
class upgrade extends _upgrade {}
