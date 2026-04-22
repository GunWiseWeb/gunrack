<?php
namespace IPS\gddealer\setup\upg_10082;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $words = [
            'gddealer_help_step2_code_header' => 'Step 2 — Format examples',
            'gddealer_help_step2_csv'         => 'CSV example',
            'gddealer_help_step2_csv_desc'    => 'Raw CSV content shown in the CSV tab of Step 2. Leave blank to hide the CSV tab.',
            'gddealer_help_step2_json'        => 'JSON example',
            'gddealer_help_step2_json_desc'   => 'Raw JSON content shown in the JSON tab of Step 2. Leave blank to hide the JSON tab.',
            'gddealer_help_step2_xml'         => 'XML example',
            'gddealer_help_step2_xml_desc'    => 'Raw XML content shown in the XML tab of Step 2. Leave blank to hide the XML tab.',
            'gddealer_help_sync_header'       => 'Sync schedule by plan',
            'gddealer_help_sync_basic'        => 'Basic sync frequency',
            'gddealer_help_sync_basic_desc'   => 'Display text for Basic plan sync frequency (e.g. "Every 6 hours")',
            'gddealer_help_sync_pro'          => 'Pro sync frequency',
            'gddealer_help_sync_pro_desc'     => 'Display text for Pro plan sync frequency (e.g. "Every 30 minutes")',
            'gddealer_help_sync_enterprise'       => 'Enterprise sync frequency',
            'gddealer_help_sync_enterprise_desc'  => 'Display text for Enterprise plan sync frequency (e.g. "Every 15 minutes")',
        ];
        try {
            foreach ( \IPS\Db::i()->select( 'lang_id', 'core_sys_lang' ) as $langId ) {
                foreach ( $words as $key => $default ) {
                    try {
                        \IPS\Db::i()->replace( 'core_sys_lang_words', [
                            'lang_id'      => (int) $langId,
                            'word_app'     => 'gddealer',
                            'word_key'     => $key,
                            'word_default' => $default,
                            'word_js'      => 0,
                            'word_export'  => 1,
                        ] );
                    } catch ( \Throwable ) {}
                }
            }
        } catch ( \Throwable ) {}
        return TRUE;
    }

    public function step2(): bool
    {
        require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10082.php';
        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Throwable ) {}
        try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Throwable ) {}
        return TRUE;
    }
}
class upgrade extends _upgrade {}
