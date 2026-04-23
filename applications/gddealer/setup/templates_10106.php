<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

try
{
    $row = \IPS\Db::i()->select( 'template_content', 'core_theme_templates',
        [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
          'gddealer', 'front', 'dealers', 'dealerShell' ]
    )->first();

    $shellBody = (string) $row;
    if ( $shellBody === '' ) { return; }

    /* Strip the v1.0.105 injection (limited to .gdKpi only) so we can replace
       it with the v1.0.106 expanded version. Idempotent: if v10105 marker
       isn't present we leave the body alone. */
    if ( strpos( $shellBody, 'GD_CARD_THEME_INJECTED_v10105' ) !== false )
    {
        $shellBody = preg_replace(
            '#<style>\s*/\* GD_CARD_THEME_INJECTED_v10105 \*/.*?</style>\s*#s',
            '',
            $shellBody
        );
        $shellBody = preg_replace(
            '#<div class="gd-shell-themed" data-gd-card-theme="\{expression="[^"]*"\}">\s*#s',
            '',
            $shellBody
        );
        $shellBody = preg_replace( '#\s*</div>\s*$#s', '', $shellBody );
    }

    /* v1.0.106 — comprehensive injection. Idempotent via marker. */
    $marker = '/* GD_CARD_THEME_INJECTED_v10106 */';
    if ( strpos( $shellBody, $marker ) === false )
    {
        $cardCss = <<<'CSS'
<style>
/* GD_CARD_THEME_INJECTED_v10106
   Targets every cardlike class used across all dashboard tabs:
     .gdKpi          KPI cards on overview
     .gdPanel        Generic panel containers (most tabs)
     .gdRailCard     Side-rail cards
     .gdSetupCard    Setup checklist cards on overview
     .gdReviewCard   Review cards on My Reviews tab
     .gdContestBlock Contest blocks on dispute view
     .gdEmptyState   Empty-state placeholders
   Plus headers/values/labels inside KPIs so number color stays legible. */

/* DEFAULT — neutral light surface, no !important so user CSS can override. */
.gd-shell-themed[data-gd-card-theme="default"] .gdKpi,
.gd-shell-themed[data-gd-card-theme="default"] .gdPanel,
.gd-shell-themed[data-gd-card-theme="default"] .gdRailCard,
.gd-shell-themed[data-gd-card-theme="default"] .gdSetupCard,
.gd-shell-themed[data-gd-card-theme="default"] .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="default"] .gdContestBlock,
.gd-shell-themed[data-gd-card-theme="default"] .gdEmptyState   { background:#FFFFFF; color:#0F172A; border-color:#E5E7EB; }
.gd-shell-themed[data-gd-card-theme="default"] .gdKpi__value   { color:#0F172A; }
.gd-shell-themed[data-gd-card-theme="default"] .gdKpi__label   { color:#64748B; }

/* DARK — high-contrast deep navy. */
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpi,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel,
.gd-shell-themed[data-gd-card-theme="dark"] .gdRailCard,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSetupCard,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="dark"] .gdContestBlock,
.gd-shell-themed[data-gd-card-theme="dark"] .gdEmptyState      { background:#0F172A !important; color:#F8FAFC !important; border-color:#1E293B !important; }
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpi__value      { color:#F8FAFC !important; }
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpi__label      { color:#94A3B8 !important; }
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel h2,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel h3,
.gd-shell-themed[data-gd-card-theme="dark"] .gdRailCard h2,
.gd-shell-themed[data-gd-card-theme="dark"] .gdRailCard h3,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSetupCard h3,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard h3   { color:#F8FAFC !important; }
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel a,
.gd-shell-themed[data-gd-card-theme="dark"] .gdRailCard a      { color:#60A5FA !important; }

/* ACCENT — brand-blue background, white text. */
.gd-shell-themed[data-gd-card-theme="accent"] .gdKpi,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel,
.gd-shell-themed[data-gd-card-theme="accent"] .gdRailCard,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSetupCard,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="accent"] .gdContestBlock,
.gd-shell-themed[data-gd-card-theme="accent"] .gdEmptyState    { background:#1E40AF !important; color:#FFFFFF !important; border-color:#1E3A8A !important; }
.gd-shell-themed[data-gd-card-theme="accent"] .gdKpi__value    { color:#FFFFFF !important; }
.gd-shell-themed[data-gd-card-theme="accent"] .gdKpi__label    { color:#BFDBFE !important; }
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel h2,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel h3,
.gd-shell-themed[data-gd-card-theme="accent"] .gdRailCard h2,
.gd-shell-themed[data-gd-card-theme="accent"] .gdRailCard h3,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSetupCard h3,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard h3 { color:#FFFFFF !important; }
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel a,
.gd-shell-themed[data-gd-card-theme="accent"] .gdRailCard a    { color:#DBEAFE !important; }
</style>
CSS;

        $shellBody = $cardCss
            . "\n<div class=\"gd-shell-themed\" data-gd-card-theme=\"{expression=\"(string) ( \$dealer['card_theme'] ?? 'default' )\"}\">\n"
            . $shellBody
            . "\n</div>\n";

        \IPS\Db::i()->update( 'core_theme_templates',
            [ 'template_content' => $shellBody, 'template_updated' => time() ],
            [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
              'gddealer', 'front', 'dealers', 'dealerShell' ]
        );
    }
}
catch ( \Throwable ) {}
