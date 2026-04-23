<?php
namespace IPS\gddealer\setup\upg_10093;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10090b.php';
        try { \IPS\Db::i()->update( 'core_themes', [ 'set_cache_key' => md5( microtime() . mt_rand() ) ] ); } catch ( \Throwable ) {}
        try { \IPS\Theme::deleteCompiledTemplate( 'gddealer', 'front', 'dealers' ); } catch ( \Throwable ) {}
        try { \IPS\Data\Store::i()->clearAll(); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll(); } catch ( \Throwable ) {}
        $pattern = \IPS\ROOT_PATH . '/datastore/template_*_dealers.*.php';
        foreach ( glob( $pattern ) ?: [] as $file ) { @unlink( $file ); }
        return TRUE;
    }
}
class upgrade extends _upgrade {}
