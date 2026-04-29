<?php
namespace IPS\gddealer\setup\upg_10144;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        /* Seed the new lang string into core_sys_lang_words for every
         * language pack so existing installs render the new sidebar entry. */
        try
        {
            $langIds = \IPS\Db::i()->select( 'lang_id', 'core_sys_lang' )->setKeyField( 'lang_id' );
            foreach ( $langIds as $langId )
            {
                try
                {
                    \IPS\Db::i()->replace( 'core_sys_lang_words', [
                        'lang_id'      => (int) $langId,
                        'word_app'     => 'gddealer',
                        'word_key'     => 'gddealer_front_tab_validator',
                        'word_default' => 'Feed Validator',
                        'word_js'      => 0,
                        'word_export'  => 1,
                    ] );
                }
                catch ( \Throwable ) {}
            }
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'v1.0.144 lang seed failed: ' . $e->getMessage(), 'gddealer_upg_10144' ); } catch ( \Throwable ) {}
        }

        /* Update dealerNavIcon template to add the validator icon SVG. */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10144.php';
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'v1.0.144 templates failed: ' . $e->getMessage(), 'gddealer_upg_10144' ); } catch ( \Throwable ) {}
        }

        /* Cache invalidation so the new nav entry, lang string, and icon
         * all show up immediately. */
        try { \IPS\Db::i()->delete( 'core_cache' ); } catch ( \Throwable ) {}
        try { \IPS\Db::i()->delete( 'core_store', [ "store_key LIKE 'theme_%' OR store_key LIKE 'template_%'" ] ); } catch ( \Throwable ) {}

        foreach ( glob( \IPS\ROOT_PATH . '/datastore/template_*dealers*' ) ?: [] as $f )
        {
            @unlink( $f );
        }

        try { unset( \IPS\Data\Store::i()->extensions );   } catch ( \Throwable ) {}
        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { unset( \IPS\Data\Store::i()->furl );         } catch ( \Throwable ) {}
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
        return 'Adding Feed Validator link to dealer dashboard sidebar';
    }
}
class upgrade extends _upgrade {}
