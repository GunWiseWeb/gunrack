<?php
namespace IPS\gddealer\setup\upg_10133;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $errors = [];

        /* Re-seed dealerDirectory with corrected class names and field bindings.
           v1.0.132 shipped a template whose markup used class names like .card-top
           that had no matching CSS, plus field names like $d['avatar_url'] that
           don't exist in the controller's data shape. */
        try
        {
            require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10133.php';
        }
        catch ( \Throwable $e )
        {
            $errors[] = 'templates_10133.php failed: ' . $e->getMessage();
        }

        /* Bust the IPS template cache (rule #40). The compiled template class
           lives in core_store; if we don't clear it, IPS keeps serving the
           old compiled version even though the DB row has the new content. */
        try { \IPS\Db::i()->delete( 'core_cache' ); } catch ( \Throwable ) {}
        try
        {
            \IPS\Db::i()->delete( 'core_store',
                [ "store_key LIKE 'theme_%' OR store_key LIKE 'template_%'" ] );
        }
        catch ( \Throwable ) {}

        /* Datastore file cleanup. */
        foreach ( glob( \IPS\ROOT_PATH . '/datastore/template_*dealers*' ) ?: [] as $f )
        {
            @unlink( $f );
        }

        /* Datastore key invalidation (rule #22). */
        try { unset( \IPS\Data\Store::i()->extensions );   } catch ( \Throwable ) {}
        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll();            } catch ( \Throwable ) {}

        /* Belt-and-suspenders: official IPS API to delete the compiled template
           cache for every theme set. Wrapped because the method may not exist
           on older 5.x patches. */
        try
        {
            if ( method_exists( '\IPS\Theme', 'deleteCompiledTemplate' ) )
            {
                \IPS\Theme::deleteCompiledTemplate( 'gddealer', 'front', 'dealers' );
            }
        }
        catch ( \Throwable ) {}

        if ( !empty( $errors ) )
        {
            try { \IPS\Log::log( 'v1.0.133 upgrade errors: ' . implode( ' | ', $errors ), 'gddealer_upg_10133' ); }
            catch ( \Throwable ) {}
        }

        return TRUE;
    }

    public function step1CustomTitle()
    {
        return 'Fixing dealer directory class names and field bindings';
    }
}
class upgrade extends _upgrade {}
