<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

try
{
    $row = \IPS\Db::i()->select( 'template_content', 'core_theme_templates',
        [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
          'gddealer', 'front', 'dealers', 'overview' ]
    )->first();
}
catch ( \Throwable ) { $row = ''; }

$body = (string) $row;
if ( $body === '' ) { return; }

$marker = '/* GD_CARD_THEME_INJECTED_v10103 */';
if ( strpos( $body, $marker ) === false )
{
    $cardCss = <<<'CSS'
<style>
/* GD_CARD_THEME_INJECTED_v10103 */
.gd-overview[data-gd-card-theme="dark"]   .gd-stat-card,
.gd-overview[data-gd-card-theme="dark"]   .gdStatCard   { background:#0F172A !important; color:#F8FAFC !important; border-color:#1E293B !important; }
.gd-overview[data-gd-card-theme="dark"]   .gd-stat-card .gd-stat-value,
.gd-overview[data-gd-card-theme="dark"]   .gdStatCard .gdStatCard__value { color:#F8FAFC !important; }
.gd-overview[data-gd-card-theme="dark"]   .gd-stat-card .gd-stat-label,
.gd-overview[data-gd-card-theme="dark"]   .gdStatCard .gdStatCard__label { color:#94A3B8 !important; }

.gd-overview[data-gd-card-theme="accent"] .gd-stat-card,
.gd-overview[data-gd-card-theme="accent"] .gdStatCard   { background:#1E40AF !important; color:#FFFFFF !important; border-color:#1E3A8A !important; }
.gd-overview[data-gd-card-theme="accent"] .gd-stat-card .gd-stat-value,
.gd-overview[data-gd-card-theme="accent"] .gdStatCard .gdStatCard__value { color:#FFFFFF !important; }
.gd-overview[data-gd-card-theme="accent"] .gd-stat-card .gd-stat-label,
.gd-overview[data-gd-card-theme="accent"] .gdStatCard .gdStatCard__label { color:#BFDBFE !important; }
</style>
CSS;

    $body = $cardCss . "\n<div class=\"gd-overview\" data-gd-card-theme=\"{\$data['card_theme']}\">\n" . $body . "\n</div>\n";

    \IPS\Db::i()->update( 'core_theme_templates',
        [ 'template_content' => $body, 'template_updated' => time() ],
        [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
          'gddealer', 'front', 'dealers', 'overview' ]
    );
}
