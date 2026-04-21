<?php
namespace IPS\gddealer\setup\upg_10076;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $table = 'gd_dealer_ratings';

        if ( !\IPS\Db::i()->checkForColumn( $table, 'upc' ) )
        {
            \IPS\Db::i()->addColumn( $table, [
                'name'       => 'upc',
                'type'       => 'VARCHAR',
                'length'     => 20,
                'allow_null' => true,
                'default'    => null,
            ] );
        }

        if ( !\IPS\Db::i()->checkForColumn( $table, 'verified_buyer' ) )
        {
            \IPS\Db::i()->addColumn( $table, [
                'name'       => 'verified_buyer',
                'type'       => 'TINYINT',
                'length'     => 1,
                'allow_null' => false,
                'default'    => 0,
            ] );
        }

        return TRUE;
    }

    public function step2(): bool
    {
        try
        {
            $tables = \IPS\Db::i()->query( "SHOW TABLES LIKE 'gd_click_log'" );
            if ( $tables->num_rows > 0 )
            {
                \IPS\Db::i()->query( "
                    UPDATE gd_dealer_ratings r
                    INNER JOIN (
                        SELECT DISTINCT member_id, dealer_id, upc
                        FROM gd_click_log
                        WHERE clicked_at > DATE_SUB(NOW(), INTERVAL 90 DAY)
                    ) cl ON cl.member_id = r.member_id AND cl.dealer_id = r.dealer_id AND cl.upc = r.upc
                    SET r.verified_buyer = 1
                    WHERE r.upc IS NOT NULL AND r.upc != '' AND r.verified_buyer = 0
                " );
            }
        }
        catch ( \Exception ) {}

        return TRUE;
    }

    public function step3(): bool
    {
        require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10076.php';

        try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Throwable ) {}
        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Throwable ) {}
        try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Throwable ) {}

        return TRUE;
    }

    public function step4(): bool
    {
        try
        {
            $row = \IPS\Db::i()->select( 'template_content', 'core_theme_templates', [
                'template_app=? AND template_location=? AND template_group=? AND template_name=?',
                'gddealer', 'front', 'dealers', 'dealerProfile'
            ] )->first();

            if ( strpos( $row, 'name="upc"' ) === false )
            {
                $needle = '</div>
						<div style="margin-bottom:12px">{$reviewBodyEditorHtml|raw}</div>';

                $replacement = '</div>
						<div style="margin-bottom:16px">
							<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Product UPC <span style="font-weight:400;color:#6b7280">(optional)</span></label>
							<input type="text" name="upc" value="" placeholder="e.g. 798681234567" pattern="[0-9]{8,14}" style="width:100%;max-width:280px;padding:8px 12px;font-size:14px;font-family:monospace;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#111827;box-sizing:border-box">
							<div style="font-size:12px;color:#6b7280;margin-top:4px">Enter the UPC of the product you purchased so other shoppers can find your review.</div>
						</div>
						<div style="margin-bottom:12px">{$reviewBodyEditorHtml|raw}</div>';

                $newContent = str_replace( $needle, $replacement, $row );
                if ( $newContent !== $row )
                {
                    \IPS\Db::i()->update( 'core_theme_templates', [
                        'template_content' => $newContent,
                        'template_updated' => time(),
                    ], [
                        'template_app=? AND template_location=? AND template_group=? AND template_name=?',
                        'gddealer', 'front', 'dealers', 'dealerProfile'
                    ] );
                }
            }
        }
        catch ( \Exception ) {}

        try { \IPS\Theme::master()->recompileTemplates(); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll(); }           catch ( \Throwable ) {}

        return TRUE;
    }
}
class upgrade extends _upgrade {}
