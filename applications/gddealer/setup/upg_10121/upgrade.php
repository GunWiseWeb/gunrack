<?php
namespace IPS\gddealer\setup\upg_10121;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $errors = [];

        /* Seed FFL notification defaults for existing installs. */
        $fflNotifKeys = [
            'gddealer_ffl_verified' => [ 'default' => 'inline,email', 'disabled' => '' ],
            'gddealer_ffl_rejected' => [ 'default' => 'inline,email', 'disabled' => '' ],
        ];

        foreach ( $fflNotifKeys as $key => $data )
        {
            try
            {
                $exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_notification_defaults', [ 'notification_key=?', $key ] )->first();
                if ( $exists === 0 )
                {
                    \IPS\Db::i()->insert( 'core_notification_defaults', [
                        'notification_key' => $key,
                        'default'          => $data['default'],
                        'disabled'         => $data['disabled'],
                    ] );
                }
                else
                {
                    \IPS\Db::i()->update( 'core_notification_defaults', [
                        'default'  => $data['default'],
                        'disabled' => $data['disabled'],
                    ], [ 'notification_key=?', $key ] );
                }
            }
            catch ( \Throwable $e ) { $errors[] = 'notification default ' . $key . ': ' . $e->getMessage(); }
        }

        /* Re-run the template seed chain for consistency. */
        $templatesFile = \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10090b.php';
        $newContent = null;

        try
        {
            if ( is_file( $templatesFile ) )
            {
                $raw = @file_get_contents( $templatesFile );
                if ( $raw !== false && preg_match( "/<<<'TEMPLATE_EOT'\n(.*?)\nTEMPLATE_EOT;/s", $raw, $m ) )
                {
                    $newContent = $m[1];
                }
            }
        }
        catch ( \Throwable $e ) { $errors[] = 'template read failed: ' . $e->getMessage(); }

        if ( $newContent )
        {
            try
            {
                \IPS\Db::i()->update( 'core_theme_templates',
                    [ 'template_data' => '$data', 'template_content' => $newContent, 'template_updated' => time() ],
                    [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
                      'gddealer', 'front', 'dealers', 'dealerProfile' ]
                );
            }
            catch ( \Throwable $e ) { $errors[] = 'template UPDATE failed: ' . $e->getMessage(); }
        }
        else { $errors[] = 'could not extract template body'; }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10103.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10103.php failed: ' . $e->getMessage(); }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10105.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10105.php failed: ' . $e->getMessage(); }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10106.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10106.php failed: ' . $e->getMessage(); }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10107.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10107.php (dealerNavIcon) failed: ' . $e->getMessage(); }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10107_shell.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10107_shell.php (dealerShell) failed: ' . $e->getMessage(); }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10108_shell.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10108_shell.php failed: ' . $e->getMessage(); }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10113.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10113.php (Edit profile restore + FFL re-inject) failed: ' . $e->getMessage(); }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10114.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10114.php (FFL admin templates) failed: ' . $e->getMessage(); }

        $fflLangStrings = [
            'gddealer_ffl_rejection_illegible' => 'License document is illegible',
            'gddealer_ffl_rejection_expired'   => 'License has expired',
            'gddealer_ffl_rejection_mismatch'  => 'Name or address does not match the submitted FFL number',
            'gddealer_ffl_rejection_other'     => 'Other (see admin notes)',
            'gddealer_ffl_verified_badge'      => 'FFL Verified',
            'gddealer_ffl_unverified_badge'    => 'Unverified',
            'gddealer_notif_ffl_verified'      => 'FFL license verified',
            'gddealer_notif_ffl_verified_desc' => 'Receive a notification when GunRack Deals staff verify your FFL license submission.',
            'gddealer_notif_ffl_rejected'      => 'FFL license rejected',
            'gddealer_notif_ffl_rejected_desc' => 'Receive a notification when your FFL license submission is rejected and needs to be re-submitted.',
        ];
        try
        {
            $langIds = [];
            foreach ( \IPS\Db::i()->select( 'lang_id', 'core_sys_lang' ) as $lid )
            {
                $langIds[] = (int) $lid;
            }
            foreach ( $langIds as $lid )
            {
                foreach ( $fflLangStrings as $key => $val )
                {
                    try
                    {
                        \IPS\Db::i()->insert( 'core_sys_lang_words', [
                            'word_app'           => 'gddealer',
                            'word_key'           => $key,
                            'word_default'       => $val,
                            'word_default_version'=> '1',
                            'word_custom'        => null,
                            'word_custom_version'=> null,
                            'word_js'            => 0,
                            'word_export'        => 1,
                            'lang_id'            => $lid,
                            'word_theme'         => 0,
                            'word_plugin'        => 0,
                            'word_top_level'     => null,
                        ] );
                    }
                    catch ( \Throwable )
                    {
                        try
                        {
                            \IPS\Db::i()->update( 'core_sys_lang_words',
                                [ 'word_default' => $val ],
                                [ 'word_app=? AND word_key=? AND lang_id=?', 'gddealer', $key, $lid ]
                            );
                        }
                        catch ( \Throwable ) {}
                    }
                }
            }
        }
        catch ( \Throwable $e )
        {
            $errors[] = 'FFL lang seed failed: ' . $e->getMessage();
        }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10119.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10119.php (FFL queue + reset button) failed: ' . $e->getMessage(); }

        try { require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10121.php'; }
        catch ( \Throwable $e ) { $errors[] = 'templates_10121.php (badge card v2) failed: ' . $e->getMessage(); }

        try { \IPS\Db::i()->update( 'core_themes', [ 'set_cache_key' => md5( microtime() . mt_rand() ) ] ); }
        catch ( \Throwable $e ) { $errors[] = 'set_cache_key rotation failed: ' . $e->getMessage(); }

        try { \IPS\Theme::deleteCompiledTemplate( 'gddealer', 'front', 'dealers' ); }
        catch ( \Throwable $e ) { $errors[] = 'deleteCompiledTemplate failed: ' . $e->getMessage(); }

        try { \IPS\Data\Store::i()->clearAll(); } catch ( \Throwable $e ) { $errors[] = 'Store clearAll failed: ' . $e->getMessage(); }
        try { \IPS\Data\Cache::i()->clearAll(); } catch ( \Throwable $e ) { $errors[] = 'Cache clearAll failed: ' . $e->getMessage(); }

        try
        {
            foreach ( glob( \IPS\ROOT_PATH . '/datastore/template_*_dealers.*.php' ) ?: [] as $f ) { @unlink( $f ); }
        }
        catch ( \Throwable $e ) { $errors[] = 'unlink failed: ' . $e->getMessage(); }

        try { \IPS\Log::log( $errors ? implode( "\n", $errors ) : 'v1.0.121 upgrade completed cleanly', 'gddealer_upg_10121' ); }
        catch ( \Throwable ) {}

        return TRUE;
    }
}
class upgrade extends _upgrade {}
