<?php
namespace IPS\gddealer\setup\upg_10089;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        try
        {
            foreach ( \IPS\Db::i()->select( 'lang_id', 'core_sys_lang' ) as $langId )
            {
                try
                {
                    \IPS\Db::i()->replace( 'core_sys_lang_words', [
                        'lang_id'      => (int) $langId,
                        'word_app'     => 'gddealer',
                        'word_key'     => 'gddealer_front_tab_edit_profile',
                        'word_default' => 'Edit profile',
                        'word_js'      => 0,
                        'word_export'  => 1,
                    ] );
                } catch ( \Throwable ) {}
            }
        } catch ( \Throwable ) {}
        return TRUE;
    }

    public function step2(): bool
    {
        require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10089.php';

        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Throwable ) {}
        try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Throwable ) {}

        return TRUE;
    }
}
class upgrade extends _upgrade {}
