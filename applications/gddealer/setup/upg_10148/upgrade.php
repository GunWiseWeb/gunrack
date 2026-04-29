<?php
namespace IPS\gddealer\setup\upg_10148;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
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
        return 'Adding feed wizard columns (wizard_step, wizard_completed_at, wizard_state_json)';
    }
}
class upgrade extends _upgrade {}
