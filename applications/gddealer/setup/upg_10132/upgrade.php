<?php
namespace IPS\gddealer\setup\upg_10132;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $newDirectoryTpl = <<<'TPL'
<div class="gd-directory">
<style>
/* CSS_MARKER */
</style>
<!-- HEADER_MARKER -->
<!-- CARDS_MARKER -->
</div>
TPL;

        \IPS\Db::i()->update(
            'core_theme_templates',
            [ 'template_content' => $newDirectoryTpl, 'template_updated' => time() ],
            [ 'template_app=? AND template_group=? AND template_name=?', 'gddealer', 'dealers', 'dealerDirectory' ]
        );

        \IPS\Db::i()->delete( 'core_cache' );
        \IPS\Db::i()->delete( 'core_store', [ "store_key LIKE 'theme_%' OR store_key LIKE 'template_%'" ] );
        foreach ( glob( \IPS\ROOT_PATH . '/datastore/template_*dealers*' ) ?: [] as $f ) { @unlink( $f ); }

        return TRUE;
    }

    public function step1CustomTitle()
    {
        return 'Rebuilding dealer directory with the new design system';
    }
}
class upgrade extends _upgrade {}
