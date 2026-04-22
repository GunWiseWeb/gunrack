<?php
/**
 * GD Dealer Manager — v1.0.90b template bodies.
 *
 * Returns an array of template definitions keyed by template_name. Each entry
 * declares its parameter signature (`data` key, matching template_data in
 * core_theme_templates) and its rendered body (`content` key).
 *
 * Consumed by:
 *   - setup/upg_10091/queries.php   (upgrade step — applies via Db::i()->update())
 *   - tools/fix_templates_v10091.php (one-shot prod recovery)
 *
 * Rule 18 (CLAUDE.md): these modify templates that already exist in
 * core_theme_templates (seeded by setup/install.php at set_id=1), so they
 * MUST be applied with Db::i()->update() keyed on
 * (template_app, template_location, template_group, template_name).
 * Db::i()->replace() with template_set_id=0 creates a second row that gets
 * overwritten by the stale set_id=1 row during compilation, causing an
 * ArgumentCountError when the signature has changed.
 *
 * ─────────────────────────────────────────────────────────────────────────
 *  ⚠  PLACEHOLDER CONTENT — MUST BE REPLACED BEFORE SHIPPING v1.0.91
 * ─────────────────────────────────────────────────────────────────────────
 * The v1.0.90/90a/90b redesign was edited directly on production and was
 * never committed to this repo. The template `content` strings below are
 * marked with the sentinel "__REQUIRES_PROD_CONTENT__". The v1.0.91 upgrade
 * step (setup/upg_10091/queries.php) detects that sentinel and will SKIP
 * the UPDATE rather than overwrite live prod template bodies with a stub.
 *
 * To finalise v1.0.91:
 *   1. SSH to gunrack.deals:2200
 *   2. Export the current dealerProfile body:
 *        SELECT template_content
 *        FROM   core_theme_templates
 *        WHERE  template_app='gddealer'
 *          AND  template_location='front'
 *          AND  template_group='dealers'
 *          AND  template_name='dealerProfile'
 *          AND  template_set_id=0
 *        INTO OUTFILE '/tmp/dealerProfile.tpl';
 *   3. Paste the file contents into the `content` nowdoc heredoc below,
 *      replacing everything between `<<<'TEMPLATE_EOT'` and `TEMPLATE_EOT;`
 *   4. Rebuild the tar and bump this note out of the file.
 *
 * Preserve nowdoc heredoc syntax (<<<'TEMPLATE_EOT') so real newlines/tabs
 * are stored and comment syntax is not mangled (Rules 4 and 9).
 */

return [

	/* ---- front/dealers/dealerProfile ---- */
	/* New signature: 1 param ($data) replacing the v1.0.0 19-param list.
	 * $data is an associative array assembled by the controller with keys
	 *   dealer, stats, reviews, canRate, editUrl, suspendUrl, importUrl,
	 *   backUrl, logs, listings, tierLabel, rebates, ...
	 * All data-flattening logic lives in modules/front/dealers/profile.php. */
	'dealerProfile' => [
		'data'    => '$data',
		'content' => <<<'TEMPLATE_EOT'
__REQUIRES_PROD_CONTENT__
TEMPLATE_EOT,
	],

];
