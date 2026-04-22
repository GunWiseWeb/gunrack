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
 * Preserve nowdoc heredoc syntax (<<<'TEMPLATE_EOT') so real newlines/tabs
 * are stored and comment syntax is not mangled (Rules 4 and 9). Never put
 * HTML comments or {{-- --}} inside the nowdoc body (Rule 9).
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
<style>
.gdDealerPage {
	--gd-brand: {expression="$data['dealer']['brand_color'] ?? '#1E40AF'"};
	--gd-brand-hover: #1E3A8A;
	--gd-brand-light: #EFF6FF;
	--gd-brand-border: #BFDBFE;
	--gd-tier-founding: #B45309;
	--gd-tier-founding-bg: #FEF3C7;
	--gd-tier-basic: #64748B;
	--gd-tier-basic-bg: #F1F5F9;
	--gd-tier-pro: #1E40AF;
	--gd-tier-pro-bg: #DBEAFE;
	--gd-tier-enterprise: #6D28D9;
	--gd-tier-enterprise-bg: #EDE9FE;
	--gd-success: #047857;
	--gd-success-bg: #D1FAE5;
	--gd-warn: #B45309;
	--gd-warn-bg: #FEF3C7;
	--gd-danger: #B91C1C;
	--gd-danger-bg: #FEE2E2;
	--gd-surface: #FFFFFF;
	--gd-surface-muted: #F8FAFC;
	--gd-border: #E5E7EB;
	--gd-border-strong: #CBD5E1;
	--gd-border-subtle: #F1F5F9;
	--gd-text: #0F172A;
	--gd-text-muted: #475569;
	--gd-text-subtle: #64748B;
	--gd-text-faint: #94A3B8;
	--gd-rating-great: #16A34A;
	--gd-star: #F59E0B;
	--gd-r-md: 6px;
	--gd-r-lg: 10px;
	--gd-r-xl: 14px;
	--gd-r-pill: 999px;
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
	color: var(--gd-text);
	font-size: 14px;
	line-height: 1.5;
	max-width: 1200px;
	margin: 0 auto;
	padding: 1.5rem;
	box-sizing: border-box;
}
.gdDealerPage *, .gdDealerPage *::before, .gdDealerPage *::after { box-sizing: border-box; }
.gdDealerPage a { color: inherit; text-decoration: none; }
.gdDealerPage h1, .gdDealerPage h2, .gdDealerPage h3 { margin: 0; font-weight: 600; }
.gdDealerPage p { margin: 0; }

.gdDealerPage .gd-breadcrumbs { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gd-text-subtle); margin-bottom: 1rem; }
.gdDealerPage .gd-breadcrumbs a { color: var(--gd-text-muted); }
.gdDealerPage .gd-breadcrumbs a:hover { color: var(--gd-text); text-decoration: underline; }
.gdDealerPage .gd-breadcrumbs .sep { color: var(--gd-text-faint); }

.gdDealerPage .hero { background: var(--gd-surface); border: 1px solid var(--gd-border); border-radius: var(--gd-r-xl); overflow: hidden; margin-bottom: 1.5rem; }
.gdDealerPage .hero-cover { height: 140px; background: linear-gradient(135deg, var(--gd-brand) 0%, #3B82F6 100%); position: relative; background-size: cover; background-position: center; }
.gdDealerPage .hero-body { padding: 0 2rem 1.5rem; position: relative; }
.gdDealerPage .hero-identity { display: flex; gap: 1.25rem; align-items: flex-end; margin-top: -40px; margin-bottom: 1.25rem; position: relative; z-index: 1; }
.gdDealerPage .hero-avatar { width: 92px; height: 92px; border-radius: var(--gd-r-xl); background: var(--gd-brand); color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 32px; border: 4px solid var(--gd-surface); flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; line-height: 1; }
.gdDealerPage .hero-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
.gdDealerPage .hero-name-block { padding-bottom: 8px; flex: 1; min-width: 0; }
.gdDealerPage .hero-name-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 4px; }
.gdDealerPage .hero-name { font-size: 28px; font-weight: 600; letter-spacing: -0.02em; line-height: 1.2; color: var(--gd-text); }
.gdDealerPage .hero-tagline { font-size: 14px; color: var(--gd-text-muted); margin-bottom: 6px; }
.gdDealerPage .hero-meta { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; font-size: 13px; color: var(--gd-text-subtle); }
.gdDealerPage .hero-meta-item { display: inline-flex; align-items: center; gap: 5px; }
.gdDealerPage .hero-meta-sep { color: var(--gd-text-faint); }
.gdDealerPage .hero-actions { display: flex; gap: 8px; padding-bottom: 12px; flex-shrink: 0; flex-wrap: wrap; }

.gdDealerPage .badge { font-size: 11px; padding: 3px 10px; border-radius: var(--gd-r-pill); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; }
.gdDealerPage .badge-founding { background: var(--gd-tier-founding-bg); color: var(--gd-tier-founding); }
.gdDealerPage .badge-basic { background: var(--gd-tier-basic-bg); color: var(--gd-tier-basic); }
.gdDealerPage .badge-pro { background: var(--gd-tier-pro-bg); color: var(--gd-tier-pro); }
.gdDealerPage .badge-enterprise { background: var(--gd-tier-enterprise-bg); color: var(--gd-tier-enterprise); }
.gdDealerPage .badge-inactive { background: var(--gd-surface-muted); color: var(--gd-text-subtle); }

.gdDealerPage .hero-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border-top: 1px solid var(--gd-border-subtle); padding-top: 1.25rem; }
.gdDealerPage .hero-stat { padding: 0 1.25rem; border-right: 1px solid var(--gd-border-subtle); }
.gdDealerPage .hero-stat:last-child { border-right: none; padding-right: 0; }
.gdDealerPage .hero-stat:first-child { padding-left: 0; }
.gdDealerPage .hero-stat-label { font-size: 11px; color: var(--gd-text-subtle); text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; margin-bottom: 6px; }
.gdDealerPage .hero-stat-value { font-size: 24px; font-weight: 600; line-height: 1.1; color: var(--gd-text); }
.gdDealerPage .hero-stat-sub { font-size: 12px; color: var(--gd-text-subtle); margin-top: 2px; }

.gdDealerPage .btn { font-family: inherit; font-size: 13px; font-weight: 500; padding: 8px 14px; border-radius: var(--gd-r-md); cursor: pointer; border: 1px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; line-height: 1.2; }
.gdDealerPage .btn-primary { background: var(--gd-brand); color: #fff; }
.gdDealerPage .btn-primary:hover { background: var(--gd-brand-hover); color: #fff; }
.gdDealerPage .btn-secondary { background: var(--gd-surface); border-color: var(--gd-border); color: var(--gd-text); }
.gdDealerPage .btn-secondary:hover { background: var(--gd-surface-muted); border-color: var(--gd-border-strong); color: var(--gd-text); }
.gdDealerPage .btn-ghost { background: transparent; color: var(--gd-text-muted); border-color: var(--gd-border); }

.gdDealerPage .gd-grid { display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; align-items: start; }

@media (max-width: 960px) {
	.gdDealerPage .gd-grid { grid-template-columns: 1fr; }
	.gdDealerPage .hero-stats { grid-template-columns: 1fr 1fr; gap: 1rem; }
	.gdDealerPage .hero-stat { border-right: none; padding: 0.75rem 0; }
	.gdDealerPage .hero-identity { flex-direction: column; align-items: flex-start; }
	.gdDealerPage .hero-actions { width: 100%; }
	.gdDealerPage .hero-actions .btn { flex: 1; justify-content: center; }
}
@media (max-width: 640px) {
	.gdDealerPage { padding: 1rem; }
	.gdDealerPage .hero-body { padding: 0 1.25rem 1.25rem; }
	.gdDealerPage .hero-stats { grid-template-columns: 1fr; }
}
</style>

<div class="gdDealerPage">

	<nav class="gd-breadcrumbs">
		<a href="{parse url="app=gddealer&module=dealers&controller=list" seoTemplate="gddealer_list"}">Dealers</a>
		<span class="sep">/</span>
		<span>{$data['dealer']['dealer_name']}</span>
	</nav>

	<div class="hero">
		<div class="hero-cover"{{if $data['dealer']['cover_photo_url']}} style="{expression="'background-image: url(' . $data['dealer']['cover_photo_url'] . ');'"}"{{endif}}></div>
		<div class="hero-body">

			<div class="hero-identity">
				<div class="hero-avatar">
					{{if $data['dealer']['avatar_url']}}
						<img src="{$data['dealer']['avatar_url']}" alt="{$data['dealer']['dealer_name']}">
					{{else}}
						{expression="mb_strtoupper( mb_substr( $data['dealer']['dealer_name'], 0, 1 ) )"}
					{{endif}}
				</div>
				<div class="hero-name-block">
					<div class="hero-name-row">
						<h1 class="hero-name">{$data['dealer']['dealer_name']}</h1>
						{{if $data['dealer']['tier_label']}}
						<span class="{expression="'badge badge-' . $data['dealer']['tier']"}">{$data['dealer']['tier_label']}</span>
						{{endif}}
						{{if !$data['dealer']['is_active']}}
						<span class="badge badge-inactive">Inactive</span>
						{{endif}}
					</div>
					{{if $data['dealer']['tagline']}}
					<div class="hero-tagline">{$data['dealer']['tagline']}</div>
					{{endif}}
					<div class="hero-meta">
						{{if $data['dealer']['address_city_state']}}
						<span class="hero-meta-item">{$data['dealer']['address_city_state']}</span>
						{{endif}}
						{{if $data['dealer']['member_since']}}
						{{if $data['dealer']['address_city_state']}}<span class="hero-meta-sep">·</span>{{endif}}
						<span class="hero-meta-item">Member since {$data['dealer']['member_since']}</span>
						{{endif}}
						{{if $data['dealer']['website_url']}}
						<span class="hero-meta-sep">·</span>
						<span class="hero-meta-item"><a href="{$data['dealer']['website_url']}" target="_blank" rel="nofollow noopener" style="color: var(--gd-brand);">Website</a></span>
						{{endif}}
					</div>
				</div>
				<div class="hero-actions">
					{{if $data['dealer']['contact_email']}}
					<a class="btn btn-secondary" href="{expression="'mailto:' . $data['dealer']['contact_email']"}">Contact</a>
					{{endif}}
					{{if $data['can_rate']}}
					<a class="btn btn-primary" href="#gd-leave-review">Write a review</a>
					{{elseif $data['login_required']}}
					<a class="btn btn-primary" href="{$data['login_url']}">Sign in to review</a>
					{{endif}}
				</div>
			</div>

			<div class="hero-stats">
				<div class="hero-stat">
					<div class="hero-stat-label">Overall rating</div>
					<div class="hero-stat-value" style="{expression="'color: ' . ( $data['stats']['rating_color'] ?? '#16A34A' )"}">{$data['stats']['avg_overall']}</div>
					<div class="hero-stat-sub">{$data['stats']['rating_label']}</div>
				</div>
				<div class="hero-stat">
					<div class="hero-stat-label">Reviews</div>
					<div class="hero-stat-value">{$data['stats']['total']}</div>
					<div class="hero-stat-sub">Verified transactions</div>
				</div>
				<div class="hero-stat">
					<div class="hero-stat-label">Active listings</div>
					<div class="hero-stat-value">{expression="number_format( (int) $data['dealer']['active_listings'] )"}</div>
					<div class="hero-stat-sub">&nbsp;</div>
				</div>
				<div class="hero-stat">
					<div class="hero-stat-label">Tier</div>
					<div class="hero-stat-value" style="font-size: 20px;">{$data['dealer']['tier_label']}</div>
					<div class="hero-stat-sub">&nbsp;</div>
				</div>
			</div>

		</div>
	</div>

	<div class="gd-grid">
		<div>
			<!-- Part 2 (ratings card) inserts here -->
			<!-- Part 3 (reviews loop) inserts here -->
		</div>
		<div>
			<!-- Part 4 (sidebar) inserts here -->
		</div>
	</div>

</div>
TEMPLATE_EOT,
	],

];
