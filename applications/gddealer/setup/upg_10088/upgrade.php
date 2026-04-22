<?php
namespace IPS\gddealer\setup\upg_10088;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $columns = [
            [ 'name' => 'tagline',          'type' => 'VARCHAR',    'length' => 160, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'about',            'type' => 'MEDIUMTEXT', 'allow_null' => true,  'default' => null ],
            [ 'name' => 'logo_url',         'type' => 'VARCHAR',    'length' => 500, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'cover_url',        'type' => 'VARCHAR',    'length' => 500, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'public_phone',     'type' => 'VARCHAR',    'length' => 32,  'allow_null' => true,  'default' => null ],
            [ 'name' => 'public_email',     'type' => 'VARCHAR',    'length' => 160, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'website_url',      'type' => 'VARCHAR',    'length' => 500, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'address_street',   'type' => 'VARCHAR',    'length' => 255, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'address_city',     'type' => 'VARCHAR',    'length' => 100, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'address_state',    'type' => 'VARCHAR',    'length' => 2,   'allow_null' => true,  'default' => null ],
            [ 'name' => 'address_zip',      'type' => 'VARCHAR',    'length' => 10,  'allow_null' => true,  'default' => null ],
            [ 'name' => 'address_public',   'type' => 'TINYINT',    'length' => 1,   'allow_null' => false, 'default' => 1 ],
            [ 'name' => 'hours_json',       'type' => 'TEXT',       'allow_null' => true,  'default' => null ],
            [ 'name' => 'social_facebook',  'type' => 'VARCHAR',    'length' => 500, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'social_instagram', 'type' => 'VARCHAR',    'length' => 500, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'social_youtube',   'type' => 'VARCHAR',    'length' => 500, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'social_twitter',   'type' => 'VARCHAR',    'length' => 500, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'social_tiktok',    'type' => 'VARCHAR',    'length' => 500, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'shipping_policy',  'type' => 'MEDIUMTEXT', 'allow_null' => true,  'default' => null ],
            [ 'name' => 'return_policy',    'type' => 'MEDIUMTEXT', 'allow_null' => true,  'default' => null ],
            [ 'name' => 'additional_notes', 'type' => 'MEDIUMTEXT', 'allow_null' => true,  'default' => null ],
            [ 'name' => 'payment_methods',  'type' => 'VARCHAR',    'length' => 255, 'allow_null' => true,  'default' => null ],
            [ 'name' => 'brand_color',      'type' => 'VARCHAR',    'length' => 7,   'allow_null' => false, 'default' => '#1E40AF' ],
        ];
        foreach ( $columns as $col )
        {
            if ( !\IPS\Db::i()->checkForColumn( 'gd_dealer_feed_config', $col['name'] ) )
            {
                try { \IPS\Db::i()->addColumn( 'gd_dealer_feed_config', $col ); } catch ( \Throwable ) {}
            }
        }
        return TRUE;
    }

    public function step2(): bool
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
                        'word_key'     => 'gddealer_front_profile_saved',
                        'word_default' => 'Your dealer profile has been saved.',
                        'word_js'      => 0,
                        'word_export'  => 1,
                    ] );
                } catch ( \Throwable ) {}
            }
        } catch ( \Throwable ) {}
        return TRUE;
    }

    public function step3(): bool
    {
        require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10088.php';

        try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Throwable ) {}
        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Throwable ) {}
        try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Throwable ) {}

        return TRUE;
    }
}
class upgrade extends _upgrade {}
