<?php
namespace IPS\gddealer\setup\upg_10149;

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
        /* Seed the new lang string for the Setup Wizard nav item into
         * core_sys_lang_words for every language pack. Pattern matches
         * v144's validator string seed. */
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
                        'word_key'     => 'gddealer_front_tab_wizard',
                        'word_default' => 'Setup Wizard',
                        'word_js'      => 0,
                        'word_export'  => 1,
                    ] );
                }
                catch ( \Throwable ) {}
            }
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'v1.0.149 lang seed failed: ' . $e->getMessage(), 'gddealer_upg_10149' ); } catch ( \Throwable ) {}
        }

        /* Seed the setupWizardStep1 template (parts 1 + 2) and update the
         * dealerNavIcon template to add the wizard icon (part 3). */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10149_part1.php';
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10149_part2.php';
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10149_part3.php';
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'v1.0.149 templates failed: ' . $e->getMessage(), 'gddealer_upg_10149' ); } catch ( \Throwable ) {}
        }

        /* Cache invalidation so the new nav entry, lang string, FURL
         * route, and templates all become visible immediately. */
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
        return 'Adding Feed Setup Wizard (step 1 - feed input)';
    }
}

class upgrade extends _upgrade {}
