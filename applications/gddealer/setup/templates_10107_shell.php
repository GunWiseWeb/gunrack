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

    if ( strpos( $shellBody, 'data-gd-card-theme=' ) !== false )
    {
        return;
    }

    /* Strip v10106 injection (header-only color overrides). */
    if ( strpos( $shellBody, 'GD_CARD_THEME_INJECTED_v10106' ) !== false )
    {
        $shellBody = preg_replace(
            '#<style>\s*/\* GD_CARD_THEME_INJECTED_v10106 .*?</style>\s*#s',
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

    $marker = '/* GD_CARD_THEME_INJECTED_v10107 */';
    if ( strpos( $shellBody, $marker ) === false )
    {
        $cardCss = <<<'CSS'
<style>
/* GD_CARD_THEME_INJECTED_v10107
   Comprehensive card-theme styles. Targets every cardlike class plus
   ALL nested content elements so body text remains readable on any theme.

   Card container classes:
     .gdKpi .gdPanel .gdRailCard .gdSetupCard .gdReviewCard
     .gdContestBlock .gdEmptyState

   DEFAULT theme uses no !important so site-wide CSS still wins where appropriate.
   DARK + ACCENT use !important to fight against legacy inline color rules. */

/* ==================== DEFAULT ==================== */
.gd-shell-themed[data-gd-card-theme="default"] .gdKpi,
.gd-shell-themed[data-gd-card-theme="default"] .gdPanel,
.gd-shell-themed[data-gd-card-theme="default"] .gdRailCard,
.gd-shell-themed[data-gd-card-theme="default"] .gdSetupCard,
.gd-shell-themed[data-gd-card-theme="default"] .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="default"] .gdContestBlock,
.gd-shell-themed[data-gd-card-theme="default"] .gdEmptyState   { background:#FFFFFF; color:#0F172A; border-color:#E5E7EB; }

/* ==================== DARK ==================== */
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpi,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel,
.gd-shell-themed[data-gd-card-theme="dark"] .gdRailCard,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSetupCard,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="dark"] .gdContestBlock,
.gd-shell-themed[data-gd-card-theme="dark"] .gdEmptyState        { background:#0F172A !important; color:#F8FAFC !important; border-color:#1E293B !important; }

/* All nested text inside any card, dark theme */
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpi *,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel *,
.gd-shell-themed[data-gd-card-theme="dark"] .gdRailCard *,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSetupCard *,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard *,
.gd-shell-themed[data-gd-card-theme="dark"] .gdContestBlock *,
.gd-shell-themed[data-gd-card-theme="dark"] .gdEmptyState *      { color:#F8FAFC !important; border-color:#1E293B !important; }

/* Muted/sub text gets a softer color for hierarchy */
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpi__label,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel small,
.gd-shell-themed[data-gd-card-theme="dark"] [class*="__label"],
.gd-shell-themed[data-gd-card-theme="dark"] [class*="__sub"],
.gd-shell-themed[data-gd-card-theme="dark"] [class*="__meta"],
.gd-shell-themed[data-gd-card-theme="dark"] [class*="__hint"]    { color:#94A3B8 !important; }

/* Links inside cards — dark theme */
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpi a,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel a,
.gd-shell-themed[data-gd-card-theme="dark"] .gdRailCard a,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSetupCard a,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard a,
.gd-shell-themed[data-gd-card-theme="dark"] .gdContestBlock a    { color:#60A5FA !important; }

/* Inputs/textareas inside cards — dark theme */
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel input[type="text"],
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel input[type="email"],
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel input[type="url"],
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel input[type="number"],
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel select,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel textarea    { background:#1E293B !important; color:#F8FAFC !important; border-color:#334155 !important; }

/* Table cells/dividers inside cards — dark theme */
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel table,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel td,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel th,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel hr,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard hr     { border-color:#1E293B !important; background:transparent !important; }

/* ==================== ACCENT ==================== */
.gd-shell-themed[data-gd-card-theme="accent"] .gdKpi,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel,
.gd-shell-themed[data-gd-card-theme="accent"] .gdRailCard,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSetupCard,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="accent"] .gdContestBlock,
.gd-shell-themed[data-gd-card-theme="accent"] .gdEmptyState      { background:#1E40AF !important; color:#FFFFFF !important; border-color:#1E3A8A !important; }

.gd-shell-themed[data-gd-card-theme="accent"] .gdKpi *,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel *,
.gd-shell-themed[data-gd-card-theme="accent"] .gdRailCard *,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSetupCard *,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard *,
.gd-shell-themed[data-gd-card-theme="accent"] .gdContestBlock *,
.gd-shell-themed[data-gd-card-theme="accent"] .gdEmptyState *    { color:#FFFFFF !important; border-color:#1E3A8A !important; }

.gd-shell-themed[data-gd-card-theme="accent"] .gdKpi__label,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel small,
.gd-shell-themed[data-gd-card-theme="accent"] [class*="__label"],
.gd-shell-themed[data-gd-card-theme="accent"] [class*="__sub"],
.gd-shell-themed[data-gd-card-theme="accent"] [class*="__meta"],
.gd-shell-themed[data-gd-card-theme="accent"] [class*="__hint"]  { color:#BFDBFE !important; }

.gd-shell-themed[data-gd-card-theme="accent"] .gdKpi a,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel a,
.gd-shell-themed[data-gd-card-theme="accent"] .gdRailCard a,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSetupCard a,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard a,
.gd-shell-themed[data-gd-card-theme="accent"] .gdContestBlock a  { color:#FFFFFF !important; text-decoration:underline; }

.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel input[type="text"],
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel input[type="email"],
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel input[type="url"],
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel input[type="number"],
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel select,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel textarea  { background:#1E3A8A !important; color:#FFFFFF !important; border-color:#1E40AF !important; }

.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel table,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel td,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel th,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel hr,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard hr   { border-color:#1E3A8A !important; background:transparent !important; }
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
