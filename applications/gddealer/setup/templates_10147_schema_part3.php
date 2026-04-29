<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.147 SCHEMA PART 3 of 3 - Validation rules + footer + CSS + JS + DB write.
 *
 * Identical to v146 schema part 3. Appends the closing markup, then writes
 * the assembled $feedSchemaTpl to core_theme_templates.
 *
 * Must run AFTER schema part1 and schema part2.
 */

if ( !isset( $feedSchemaTpl ) )
{
    throw new \RuntimeException( 'templates_10147_schema_part3.php loaded before part1+part2' );
}

$feedSchemaTpl .= <<<'TEMPLATE_EOT'
		<section id="validation" class="gd-fs__section">
			<h2>Validation Rules</h2>
			<p>Before pointing the importer at a live feed, dealers can paste a sample at our validator and get a per-row report. The validator parses the feed exactly the way the importer does, runs every check listed below, and returns errors (block import) and warnings (allowed but flagged).</p>
			<div class="gd-fs__rulesGrid">
				<div class="gd-fs__rule"><strong>UPC format</strong>Must be 12 or 13 digits. Non-digit characters get stripped with a warning.</div>
				<div class="gd-fs__rule"><strong>UPC duplicates</strong>Same UPC appearing more than once in one feed produces a warning. Last occurrence wins on import.</div>
				<div class="gd-fs__rule"><strong>Price greater than 0</strong>Prices of 0 or negative are rejected. Prices over $100,000 produce a warning.</div>
				<div class="gd-fs__rule"><strong>MAP greater than price</strong>When map_price is set, it must exceed price. Otherwise MAP is ignored on import.</div>
				<div class="gd-fs__rule"><strong>URL https only</strong>http:// URLs are rejected. Mixed-content image URLs trigger a warning.</div>
				<div class="gd-fs__rule"><strong>Category enum</strong>Must be exactly one of the 8 allowed values. Case-insensitive.</div>
				<div class="gd-fs__rule"><strong>Condition enum</strong>Must be new, used, or refurbished. Case-insensitive.</div>
				<div class="gd-fs__rule"><strong>Shipping conditional</strong>shipping_cost is required unless free_shipping is 1.</div>
				<div class="gd-fs__rule"><strong>Category subfields</strong>ammo.caliber/rounds, part.type, reloading.type/rounds + type-specific subfield, optic.type, knife.type all required when category matches.</div>
				<div class="gd-fs__rule"><strong>Ammo enums</strong>fire_type, bullet_design, tip_color, case_material all enforce their valid value lists. case_material is required when fire_type=centerfire.</div>
				<div class="gd-fs__rule"><strong>Field length</strong>name 200 chars, brand/mpn/sku 100 chars. Over-length values trigger warnings (or errors for sku).</div>
			</div>

			<div class="gd-fs__validatorCta">
				<h3>Test your feed</h3>
				<p>Subscribed dealers can paste a feed sample at the validator and get a real-time validation report. The validator runs the same checks the importer does &mdash; if it passes the validator, it'll pass the importer.</p>
				<a href="/dealers/feed-validator" class="gd-fs__btn gd-fs__btn--primary">Open Validator</a>
				<a href="/dealers/join" class="gd-fs__btn">Become a Dealer</a>
			</div>
		</section>

	</main>

	<footer class="gd-fs__footer">
		<p>Schema version 1.1 &middot; Last updated April 2026 &middot; Questions? Contact <a href="mailto:dealers@gunrack.deals">dealers@gunrack.deals</a></p>
	</footer>

</div>

<style>
.gd-fs {
	--gd-fs-bg: #ffffff;
	--gd-fs-surface: #f8fafc;
	--gd-fs-border: #e5e7eb;
	--gd-fs-border-strong: #cbd5e1;
	--gd-fs-text: #0f172a;
	--gd-fs-muted: #475569;
	--gd-fs-subtle: #64748b;
	--gd-fs-brand: #1e40af;
	--gd-fs-brand-hover: #1e3a8a;
	--gd-fs-brand-light: #eff6ff;
	--gd-fs-req: #b91c1c;
	--gd-fs-rec: #b45309;
	--gd-fs-opt: #64748b;
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
	color: var(--gd-fs-text);
	font-size: 15px;
	line-height: 1.6;
	background: var(--gd-fs-bg);
}
.gd-fs *, .gd-fs *::before, .gd-fs *::after { box-sizing: border-box; }

.gd-fs__hero { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); color: #fff; padding: 64px 24px 56px; }
.gd-fs__heroInner { max-width: 1100px; margin: 0 auto; }
.gd-fs__eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 2px; color: #93c5fd; margin-bottom: 12px; }
.gd-fs__hero h1 { margin: 0 0 12px; font-size: 2.5em; font-weight: 700; letter-spacing: -0.02em; color: #fff; }
.gd-fs__lede { margin: 0 0 24px; color: #cbd5e1; font-size: 1.1em; max-width: 760px; }
.gd-fs__heroCtas { display: flex; gap: 12px; flex-wrap: wrap; }

.gd-fs__btn { display: inline-block; padding: 12px 22px; border-radius: 8px; background: rgba(255,255,255,0.1); color: #fff; text-decoration: none; font-weight: 500; font-size: 14px; border: 1px solid rgba(255,255,255,0.2); transition: all 0.15s; }
.gd-fs__btn:hover { background: rgba(255,255,255,0.2); color: #fff; }
.gd-fs__btn--primary { background: #2563eb; border-color: #2563eb; }
.gd-fs__btn--primary:hover { background: #1d4ed8; border-color: #1d4ed8; }

.gd-fs__toc { position: sticky; top: 0; z-index: 10; background: var(--gd-fs-bg); border-bottom: 1px solid var(--gd-fs-border); padding: 12px 24px; }
.gd-fs__tocInner { max-width: 1100px; margin: 0 auto; display: flex; gap: 24px; overflow-x: auto; white-space: nowrap; font-size: 13px; font-weight: 500; }
.gd-fs__toc a { color: var(--gd-fs-muted); text-decoration: none; }
.gd-fs__toc a:hover { color: var(--gd-fs-brand); }

.gd-fs__main { max-width: 1100px; margin: 0 auto; padding: 48px 24px; }
.gd-fs__section { margin-bottom: 56px; scroll-margin-top: 70px; }
.gd-fs__section h2 { font-size: 1.6em; font-weight: 700; color: var(--gd-fs-text); margin: 0 0 16px; padding-bottom: 8px; border-bottom: 2px solid var(--gd-fs-border); }
.gd-fs__section h3 { font-size: 1.2em; font-weight: 600; margin: 0 0 12px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

.gd-fs__catTag { font-size: 11px; font-weight: 600; font-family: 'JetBrains Mono', ui-monospace, monospace; background: var(--gd-fs-brand-light); color: var(--gd-fs-brand); padding: 3px 10px; border-radius: 4px; }
.gd-fs__catCard { background: var(--gd-fs-surface); border: 1px solid var(--gd-fs-border); border-radius: 10px; padding: 20px; margin-bottom: 16px; }

.gd-fs__tableWrap { overflow-x: auto; margin: 8px 0; }
.gd-fs__table { width: 100%; border-collapse: collapse; font-size: 14px; background: #fff; border: 1px solid var(--gd-fs-border); border-radius: 6px; overflow: hidden; }
.gd-fs__table th { background: var(--gd-fs-surface); text-align: left; font-weight: 600; padding: 10px 14px; border-bottom: 1px solid var(--gd-fs-border); white-space: nowrap; }
.gd-fs__table td { padding: 10px 14px; border-bottom: 1px solid var(--gd-fs-border); vertical-align: top; }
.gd-fs__table tr:last-child td { border-bottom: none; }
.gd-fs__table code { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 12.5px; background: var(--gd-fs-brand-light); color: var(--gd-fs-brand); padding: 1px 6px; border-radius: 3px; white-space: nowrap; }
.gd-fs__req { color: var(--gd-fs-req); font-weight: 600; white-space: nowrap; }
.gd-fs__rec { color: var(--gd-fs-rec); font-weight: 600; white-space: nowrap; }
.gd-fs__opt { color: var(--gd-fs-opt); white-space: nowrap; }
.gd-fs__note { font-size: 13px; color: var(--gd-fs-muted); font-style: italic; margin-top: 8px; }

.gd-fs__tabs { display: flex; gap: 4px; border-bottom: 2px solid var(--gd-fs-border); margin-bottom: 0; }
.gd-fs__tabBtn { background: transparent; border: none; border-bottom: 3px solid transparent; padding: 12px 24px; font-family: inherit; font-size: 14px; font-weight: 600; color: var(--gd-fs-muted); cursor: pointer; margin-bottom: -2px; transition: all 0.15s; }
.gd-fs__tabBtn:hover { color: var(--gd-fs-brand); }
.gd-fs__tabBtn.is-active { color: var(--gd-fs-brand); border-bottom-color: var(--gd-fs-brand); }
.gd-fs__tabPanel { display: none; }
.gd-fs__tabPanel.is-active { display: block; }
.gd-fs__code { background: #0f172a; color: #e2e8f0; padding: 20px 24px; border-radius: 0 0 8px 8px; overflow-x: auto; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 12.5px; line-height: 1.55; white-space: pre; margin: 0; }

.gd-fs__rulesGrid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; margin: 16px 0; }
.gd-fs__rule { background: var(--gd-fs-surface); border: 1px solid var(--gd-fs-border); border-radius: 8px; padding: 14px 16px; font-size: 13.5px; }
.gd-fs__rule strong { display: block; margin-bottom: 4px; color: var(--gd-fs-text); font-weight: 600; }

.gd-fs__validatorCta { background: var(--gd-fs-brand-light); border: 1px solid #bfdbfe; border-radius: 12px; padding: 24px; margin-top: 32px; text-align: center; }
.gd-fs__validatorCta h3 { margin: 0 0 8px; color: var(--gd-fs-brand); display: block; }
.gd-fs__validatorCta p { margin: 0 0 16px; color: var(--gd-fs-muted); }
.gd-fs__validatorCta .gd-fs__btn { margin: 0 4px; }
.gd-fs__validatorCta .gd-fs__btn--primary { background: var(--gd-fs-brand); border-color: var(--gd-fs-brand); }
.gd-fs__validatorCta .gd-fs__btn--primary:hover { background: var(--gd-fs-brand-hover); border-color: var(--gd-fs-brand-hover); }
.gd-fs__validatorCta .gd-fs__btn:not(.gd-fs__btn--primary) { background: #fff; color: var(--gd-fs-text); border-color: var(--gd-fs-border-strong); }
.gd-fs__validatorCta .gd-fs__btn:not(.gd-fs__btn--primary):hover { background: var(--gd-fs-surface); }

.gd-fs__footer { border-top: 1px solid var(--gd-fs-border); background: var(--gd-fs-surface); padding: 24px; text-align: center; color: var(--gd-fs-subtle); font-size: 13px; }
.gd-fs__footer a { color: var(--gd-fs-brand); }

@media (max-width: 720px) {
	.gd-fs__hero { padding: 40px 20px 36px; }
	.gd-fs__hero h1 { font-size: 1.8em; }
	.gd-fs__main { padding: 32px 16px; }
	.gd-fs__tabBtn { padding: 10px 14px; font-size: 13px; }
	.gd-fs__code { font-size: 11.5px; padding: 14px 16px; }
}
</style>

<script>
(function(){
	var tabs = document.querySelectorAll('.gd-fs__tabBtn');
	var panels = document.querySelectorAll('.gd-fs__tabPanel');
	tabs.forEach(function(btn){
		btn.addEventListener('click', function(){
			var target = btn.getAttribute('data-tab');
			tabs.forEach(function(t){ t.classList.toggle('is-active', t.getAttribute('data-tab') === target); });
			panels.forEach(function(p){ p.classList.toggle('is-active', p.getAttribute('data-panel') === target); });
		});
	});
})();
</script>
TEMPLATE_EOT;

try
{
	\IPS\Db::i()->update( 'core_theme_templates',
		[
			'template_data'    => '',
			'template_content' => $feedSchemaTpl,
			'template_updated' => time(),
		],
		[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		  'gddealer', 'front', 'dealers', 'feedSchema' ]
	);
}
catch ( \Throwable $e )
{
	try { \IPS\Log::log( 'templates_10147_schema_part3.php failed: ' . $e->getMessage(), 'gddealer_upg_10147' ); }
	catch ( \Throwable ) {}
}
