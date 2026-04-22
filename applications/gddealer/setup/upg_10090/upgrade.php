<?php
namespace IPS\gddealer\setup\upg_10090;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10090.php';

        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Throwable ) {}
        try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Throwable ) {}

        return TRUE;
    }
}
class upgrade extends _upgrade {}
