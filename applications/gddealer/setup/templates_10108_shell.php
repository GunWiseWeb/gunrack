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

    /* Strip v10107 injection. */
    if ( strpos( $shellBody, 'GD_CARD_THEME_INJECTED_v10107' ) !== false )
    {
        $shellBody = preg_replace(
            '#<style>\s*/\* GD_CARD_THEME_INJECTED_v10107 .*?</style>\s*#s',
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

    $marker = '/* GD_CARD_THEME_INJECTED_v10108 */';
    if ( strpos( $shellBody, $marker ) === false )
    {
        $cardCss = <<<'CSS'
<style>
/* GD_CARD_THEME_INJECTED_v10108
   SURGICAL card theming. Zero wildcards. Targets only specific BEM text slots
   used by the card templates (gd{Component}__{slot} classes enumerated from
   templates_10072..10077). Nav, pills, form inputs, nested cards are untouched.

   Scoping rules applied:
     - Shell wrapper exists, but .gdNavItem, .gdSidebarNav, .gdShell* are NOT re-themed.
     - Pills (*Pill*, gdTierBadge, gdReviewCard__deadlineBadge) keep their own colors.
     - Form inputs/selects/textareas keep their own styling.
     - Top-level cards get themed; nested .gdPanel inside other cards stays default.
*/

/* ──────── Card containers (top-level only) ──────── */

/* DEFAULT — light surface, no !important. */
.gd-shell-themed[data-gd-card-theme="default"] > .gdKpi,
.gd-shell-themed[data-gd-card-theme="default"] .gdKpiGrid > .gdKpi,
.gd-shell-themed[data-gd-card-theme="default"] > .gdPanel,
.gd-shell-themed[data-gd-card-theme="default"] > .gdRailCard,
.gd-shell-themed[data-gd-card-theme="default"] > .gdSetupCard,
.gd-shell-themed[data-gd-card-theme="default"] > .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="default"] > .gdContestBlock,
.gd-shell-themed[data-gd-card-theme="default"] > .gdEmptyState,
.gd-shell-themed[data-gd-card-theme="default"] .gdReviewList > .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="default"] .gdContentSplit > .gdPanel,
.gd-shell-themed[data-gd-card-theme="default"] .gdContentSplit > .gdRailCard { background:#FFFFFF; color:#0F172A; border-color:#E5E7EB; }

/* DARK */
.gd-shell-themed[data-gd-card-theme="dark"] > .gdKpi,
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpiGrid > .gdKpi,
.gd-shell-themed[data-gd-card-theme="dark"] > .gdPanel,
.gd-shell-themed[data-gd-card-theme="dark"] > .gdRailCard,
.gd-shell-themed[data-gd-card-theme="dark"] > .gdSetupCard,
.gd-shell-themed[data-gd-card-theme="dark"] > .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="dark"] > .gdContestBlock,
.gd-shell-themed[data-gd-card-theme="dark"] > .gdEmptyState,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewList > .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="dark"] .gdContentSplit > .gdPanel,
.gd-shell-themed[data-gd-card-theme="dark"] .gdContentSplit > .gdRailCard { background:#0F172A !important; color:#F8FAFC !important; border-color:#1E293B !important; }

/* ACCENT */
.gd-shell-themed[data-gd-card-theme="accent"] > .gdKpi,
.gd-shell-themed[data-gd-card-theme="accent"] .gdKpiGrid > .gdKpi,
.gd-shell-themed[data-gd-card-theme="accent"] > .gdPanel,
.gd-shell-themed[data-gd-card-theme="accent"] > .gdRailCard,
.gd-shell-themed[data-gd-card-theme="accent"] > .gdSetupCard,
.gd-shell-themed[data-gd-card-theme="accent"] > .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="accent"] > .gdContestBlock,
.gd-shell-themed[data-gd-card-theme="accent"] > .gdEmptyState,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewList > .gdReviewCard,
.gd-shell-themed[data-gd-card-theme="accent"] .gdContentSplit > .gdPanel,
.gd-shell-themed[data-gd-card-theme="accent"] .gdContentSplit > .gdRailCard { background:#1E40AF !important; color:#FFFFFF !important; border-color:#1E3A8A !important; }

/* ──────── Text slots (BEM __child classes only; no wildcards, no pills) ──────── */

/* Headings/titles - dark/accent need override since defaults are near-black */
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpi__value,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel__title,
.gd-shell-themed[data-gd-card-theme="dark"] .gdRailCard__title,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSetupCard__title,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard__body,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard__authorLine,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard__product,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard__responseBody,
.gd-shell-themed[data-gd-card-theme="dark"] .gdContestBlock__body,
.gd-shell-themed[data-gd-card-theme="dark"] .gdContestBlock__evidenceBody,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSyncBanner__title,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSyncStat__value,
.gd-shell-themed[data-gd-card-theme="dark"] .gdTimeline__text         { color:#F8FAFC !important; }

.gd-shell-themed[data-gd-card-theme="accent"] .gdKpi__value,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel__title,
.gd-shell-themed[data-gd-card-theme="accent"] .gdRailCard__title,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSetupCard__title,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard__body,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard__authorLine,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard__product,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard__responseBody,
.gd-shell-themed[data-gd-card-theme="accent"] .gdContestBlock__body,
.gd-shell-themed[data-gd-card-theme="accent"] .gdContestBlock__evidenceBody,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSyncBanner__title,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSyncStat__value,
.gd-shell-themed[data-gd-card-theme="accent"] .gdTimeline__text       { color:#FFFFFF !important; }

/* Muted/sub/meta text (softer contrast for hierarchy) */
.gd-shell-themed[data-gd-card-theme="dark"] .gdKpi__label,
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel__sub,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSetupCard__sub,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard__meta,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard__metaLine,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard__sep,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSyncBanner__sub,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSyncStat__label,
.gd-shell-themed[data-gd-card-theme="dark"] .gdSyncStat__sub,
.gd-shell-themed[data-gd-card-theme="dark"] .gdTimeline__time,
.gd-shell-themed[data-gd-card-theme="dark"] .gdTimeline__label,
.gd-shell-themed[data-gd-card-theme="dark"] .gdTimeline__note,
.gd-shell-themed[data-gd-card-theme="dark"] .gdContestBlock__header,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard__responseLabel,
.gd-shell-themed[data-gd-card-theme="dark"] .gdReviewCard__disputeLabel { color:#94A3B8 !important; }

.gd-shell-themed[data-gd-card-theme="accent"] .gdKpi__label,
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel__sub,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSetupCard__sub,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard__meta,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard__metaLine,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard__sep,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSyncBanner__sub,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSyncStat__label,
.gd-shell-themed[data-gd-card-theme="accent"] .gdSyncStat__sub,
.gd-shell-themed[data-gd-card-theme="accent"] .gdTimeline__time,
.gd-shell-themed[data-gd-card-theme="accent"] .gdTimeline__label,
.gd-shell-themed[data-gd-card-theme="accent"] .gdTimeline__note,
.gd-shell-themed[data-gd-card-theme="accent"] .gdContestBlock__header,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard__responseLabel,
.gd-shell-themed[data-gd-card-theme="accent"] .gdReviewCard__disputeLabel { color:#BFDBFE !important; }

/* Links inside cards (not in sidebar — sidebar is .gdNavItem, not in this selector list) */
.gd-shell-themed[data-gd-card-theme="dark"] .gdPanel__link,
.gd-shell-themed[data-gd-card-theme="dark"] .gdIdentity__url           { color:#60A5FA !important; }
.gd-shell-themed[data-gd-card-theme="accent"] .gdPanel__link,
.gd-shell-themed[data-gd-card-theme="accent"] .gdIdentity__url         { color:#DBEAFE !important; text-decoration:underline; }

/* Borders/dividers inside cards */
.gd-shell-themed[data-gd-card-theme="dark"] .gdSetupSteps,
.gd-shell-themed[data-gd-card-theme="dark"] .gdActionList__item,
.gd-shell-themed[data-gd-card-theme="dark"] .gdTimeline__item          { border-color:#1E293B !important; }
.gd-shell-themed[data-gd-card-theme="accent"] .gdSetupSteps,
.gd-shell-themed[data-gd-card-theme="accent"] .gdActionList__item,
.gd-shell-themed[data-gd-card-theme="accent"] .gdTimeline__item        { border-color:#1E3A8A !important; }

/* ──────── Explicit un-theming for things we must NOT recolor ──────── */

/* Pills keep their own colors (enumerated from templates_10072..10077) */
.gd-shell-themed .gdStatusPill,
.gd-shell-themed .gdPriorityPill,
.gd-shell-themed .gdTierBadge,
.gd-shell-themed .gdReviewCard__ratingPill,
.gd-shell-themed .gdReviewCard__deadlineBadge,
.gd-shell-themed .gdReviewCard__contestFoldBadge,
.gd-shell-themed .gdReviewCard__verifiedBadge                          { background:initial; color:initial; border-color:initial; }

/* Form controls keep their own styling (browser defaults + any site CSS) */
.gd-shell-themed input,
.gd-shell-themed select,
.gd-shell-themed textarea                                              { background:initial; color:initial; border-color:initial; }

/* Setup step pills keep their white-pill look (they are .gdStep inside .gdSetupSteps) */
.gd-shell-themed .gdStep,
.gd-shell-themed .gdStep__text,
.gd-shell-themed .gdStep__icon                                         { background:initial; color:initial; border-color:initial; }

/* Filter tabs (Listings tab chips) keep their own colors */
.gd-shell-themed .gdFilterTab,
.gd-shell-themed .gdFilterTab__count,
.gd-shell-themed .gdSubNavTab,
.gd-shell-themed .gdSubNavTab__count                                   { background:initial; color:initial; border-color:initial; }

/* Buttons keep their own colors */
.gd-shell-themed .gdBtn                                                { background:initial; color:initial; border-color:initial; }
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
