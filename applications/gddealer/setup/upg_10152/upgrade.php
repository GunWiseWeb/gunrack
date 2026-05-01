<?php
namespace IPS\gddealer\setup\upg_10152;

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
        /* Replace BOTH wizard step templates - step 1 and step 2 - with
         * versions that use $values['urls']['key'] string interpolation
         * instead of {url=...} template directives.
         *
         * The {url=...} directive's nested quoting was breaking HTML
         * attribute parsing; form action= attributes ended up containing
         * literal "{url=app=gddealer..." text which 404'd on submit. */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10152_part1.php';
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10152_part2.php';
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10152_part3.php';
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'v1.0.152 templates failed: ' . $e->getMessage(), 'gddealer_upg_10152' ); } catch ( \Throwable ) {}
        }

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
        return 'Hotfix: replace broken {url=...} directives in wizard templates';
    }
}

class upgrade extends _upgrade {}
