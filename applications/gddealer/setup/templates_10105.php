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

/* Strip out the broken v10103 injection if present (wrong classes, never worked). */
$body = preg_replace(
    '#<style>\s*/\* GD_CARD_THEME_INJECTED_v10103 \*/.*?</style>\s*#s',
    '',
    $body
);
$body = preg_replace(
    '#<div class="gd-overview" data-gd-card-theme="\{\\\$data\[\'card_theme\'\]\}">\s*#s',
    '',
    $body
);
$body = preg_replace( '#\s*</div>\s*$#s', '', $body );

/* v10105 injection — targets the REAL stat card classes used by templates_10072.php:
     .gdKpi          — the card itself
     .gdKpi__value   — the big number
     .gdKpi__label   — the label
   Injection is idempotent via marker. */
$marker = '/* GD_CARD_THEME_INJECTED_v10105 */';
if ( strpos( $body, $marker ) === false )
{
    $cardCss = <<<'CSS'
<style>
/* GD_CARD_THEME_INJECTED_v10105 */
.gd-overview-themed[data-gd-card-theme="dark"]   .gdKpi          { background:#0F172A !important; color:#F8FAFC !important; border-color:#1E293B !important; }
.gd-overview-themed[data-gd-card-theme="dark"]   .gdKpi__value   { color:#F8FAFC !important; }
.gd-overview-themed[data-gd-card-theme="dark"]   .gdKpi__label   { color:#94A3B8 !important; }

.gd-overview-themed[data-gd-card-theme="accent"] .gdKpi          { background:#1E40AF !important; color:#FFFFFF !important; border-color:#1E3A8A !important; }
.gd-overview-themed[data-gd-card-theme="accent"] .gdKpi__value   { color:#FFFFFF !important; }
.gd-overview-themed[data-gd-card-theme="accent"] .gdKpi__label   { color:#BFDBFE !important; }

.gd-overview-themed[data-gd-card-theme="default"] .gdKpi         { background:#FFFFFF; color:#0F172A; border-color:#E5E7EB; }
.gd-overview-themed[data-gd-card-theme="default"] .gdKpi__value  { color:#0F172A; }
.gd-overview-themed[data-gd-card-theme="default"] .gdKpi__label  { color:#64748B; }
</style>
CSS;

    $body = $cardCss . "\n<div class=\"gd-overview-themed\" data-gd-card-theme=\"{\$data['card_theme']}\">\n" . $body . "\n</div>\n";

    \IPS\Db::i()->update( 'core_theme_templates',
        [ 'template_content' => $body, 'template_updated' => time() ],
        [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
          'gddealer', 'front', 'dealers', 'overview' ]
    );
}
