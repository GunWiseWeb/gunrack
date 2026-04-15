<?php
/**
 * @brief       GD Price Comparison — Install routine
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Runs after schema.json tables are created. Seeds templates directly into
 * core_theme_templates using nowdoc heredocs so real newlines/tabs are
 * preserved. No comments inside template bodies (CLAUDE.md Rule #9) — the IPS
 * template compiler does not parse comment syntax.
 *
 * Also installs a small seed set of state compliance rules for launch.
 */

$gdpricecompareTemplates = [

	/* ===== ADMIN: dashboard ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'admin',
		'group'         => 'pricecompare',
		'template_name' => 'dashboard',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['product_count'] )"}</div>
				<div>{lang="gdpc_dash_products_indexed"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['listing_count'] )"}</div>
				<div>{lang="gdpc_dash_listings_total"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['watchlist_count'] )"}</div>
				<div>{lang="gdpc_dash_watchlists"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['ffl_count'] )"}</div>
				<div>{lang="gdpc_dash_ffl_dealers"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['clicks_today'] )"}</div>
				<div>{lang="gdpc_dash_clicks_today"}</div>
			</div>
		</div>

		<div style="display:flex;gap:16px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 320px;padding:16px">
				<h2 class="ipsType_sectionHead" style="margin:0 0 12px">{lang="gdpc_dash_top_searches"}</h2>
				{{if count( $data['top_searches'] ) === 0}}
					<div class="ipsEmptyMessage"><p>No searches recorded in the last 7 days.</p></div>
				{{else}}
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th style="width:80px">{lang="gdpc_searchlog_count"}</th></tr></thead>
					<tbody>
					{{foreach $data['top_searches'] as $row}}
					<tr><td>{$row['query']}</td><td>{expression="number_format( $row['count'] )"}</td></tr>
					{{endforeach}}
					</tbody>
				</table>
				{{endif}}
			</div>
			<div class="ipsBox" style="flex:1 1 320px;padding:16px">
				<h2 class="ipsType_sectionHead" style="margin:0 0 12px">{lang="gdpc_dash_zero_results"}</h2>
				{{if count( $data['zero_searches'] ) === 0}}
					<div class="ipsEmptyMessage"><p>No zero-result searches in the last 7 days.</p></div>
				{{else}}
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th style="width:80px">{lang="gdpc_searchlog_count"}</th></tr></thead>
					<tbody>
					{{foreach $data['zero_searches'] as $row}}
					<tr><td>{$row['query']}</td><td>{expression="number_format( $row['count'] )"}</td></tr>
					{{endforeach}}
					</tbody>
				</table>
				{{endif}}
			</div>
		</div>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* settings page is rendered by \IPS\Helpers\Form (see settings.php) — no template needed. */

	/* ===== ADMIN: ffldata ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'admin',
		'group'         => 'pricecompare',
		'template_name' => 'ffldata',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div style="display:flex;justify-content:flex-end;gap:8px;padding:10px 16px;border-bottom:1px solid var(--i-border-color, #e0e0e0);flex-wrap:wrap">
		<a href="{$data['add_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdpc_ffldata_add"}</a>
		<a href="{$data['refresh_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdpc_ffldata_refresh"}</a>
	</div>
	<div class="ipsBox_body ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['count'] )"}</div>
				<div>{lang="gdpc_ffldata_count"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['active_cnt'] )"}</div>
				<div>{lang="gdpc_ffldata_active_count"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:1.3em;font-weight:bold">{{if $data['last']}}{$data['last']}{{else}}&mdash;{{endif}}</div>
				<div>{lang="gdpc_ffldata_last_updated"}</div>
			</div>
		</div>

		<form method="get" action="{$data['form_action']}" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
			<input type="hidden" name="app" value="gdpricecompare" />
			<input type="hidden" name="module" value="pricecompare" />
			<input type="hidden" name="controller" value="ffldata" />
			<input type="text" name="q" value="{$data['search']}" placeholder="{lang="gdpc_ffldata_search_placeholder"}" class="ipsField_text" style="width:320px" />
			<button type="submit" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdpc_ffldata_search"}</button>
		</form>

		{{if count( $data['rows'] ) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdpc_ffldata_empty"}</p></div>
		{{else}}
		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th>{lang="gdpc_ffldata_business"}</th>
					<th>{lang="gdpc_ffldata_license"}</th>
					<th>{lang="gdpc_ffldata_type"}</th>
					<th>{lang="gdpc_ffldata_city_state"}</th>
					<th>{lang="gdpc_ffldata_phone"}</th>
					<th>{lang="gdpc_ffldata_expiry"}</th>
					<th style="width:80px">{lang="gdpc_ffldata_active"}</th>
					<th style="width:180px">{lang="gdpc_ffldata_actions"}</th>
				</tr>
			</thead>
			<tbody>
			{{foreach $data['rows'] as $row}}
				<tr>
					<td><strong>{$row['business']}</strong></td>
					<td><code>{$row['license']}</code></td>
					<td>{$row['lic_type']}</td>
					<td>{$row['city']}, {$row['state']} {$row['zip']}</td>
					<td>{$row['phone']}</td>
					<td>{$row['lic_xprdte']}</td>
					<td>
						{{if $row['active']}}
							<span class="ipsBadge ipsBadge--positive">{lang="gdpc_ffldata_status_active"}</span>
						{{else}}
							<span class="ipsBadge ipsBadge--neutral">{lang="gdpc_ffldata_status_inactive"}</span>
						{{endif}}
					</td>
					<td>
						<a href="{$row['edit_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdpc_ffldata_edit"}</a>
						<a href="{$row['delete_url']}" class="ipsButton ipsButton--negative ipsButton--small" data-confirm>{lang="gdpc_ffldata_delete"}</a>
					</td>
				</tr>
			{{endforeach}}
			</tbody>
		</table>
		{{endif}}

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: searchlog ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'admin',
		'group'         => 'pricecompare',
		'template_name' => 'searchlog',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="count( $data['top'] )"}</div>
				<div>{lang="gdpc_searchlog_top_title"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="count( $data['zero'] )"}</div>
				<div>{lang="gdpc_searchlog_zero_title"}</div>
			</div>
		</div>

		<div style="display:flex;gap:16px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 320px;padding:16px">
				<h2 class="ipsType_sectionHead" style="margin:0 0 12px">{lang="gdpc_searchlog_top_title"}</h2>
				{{if count( $data['top'] ) === 0}}
					<div class="ipsEmptyMessage"><p>No searches recorded in the last 7 days.</p></div>
				{{else}}
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th style="width:80px">{lang="gdpc_searchlog_count"}</th></tr></thead>
					<tbody>
					{{foreach $data['top'] as $row}}
					<tr><td>{$row['query']}</td><td>{expression="number_format( $row['count'] )"}</td></tr>
					{{endforeach}}
					</tbody>
				</table>
				{{endif}}
			</div>
			<div class="ipsBox" style="flex:1 1 320px;padding:16px">
				<h2 class="ipsType_sectionHead" style="margin:0 0 12px">{lang="gdpc_searchlog_zero_title"}</h2>
				{{if count( $data['zero'] ) === 0}}
					<div class="ipsEmptyMessage"><p>No zero-result searches in the last 7 days.</p></div>
				{{else}}
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th style="width:80px">{lang="gdpc_searchlog_count"}</th></tr></thead>
					<tbody>
					{{foreach $data['zero'] as $row}}
					<tr><td>{$row['query']}</td><td>{expression="number_format( $row['count'] )"}</td></tr>
					{{endforeach}}
					</tbody>
				</table>
				{{endif}}
			</div>
		</div>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: compliance ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'admin',
		'group'         => 'pricecompare',
		'template_name' => 'compliance',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div style="display:flex;justify-content:flex-end;padding:10px 16px;border-bottom:1px solid var(--i-border-color, #e0e0e0)">
		<a href="{$data['add_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdpc_compliance_add_rule"}</a>
	</div>
	<div class="ipsBox_body ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['count'] )"}</div>
				<div>{lang="gdpc_compliance_count"}</div>
			</div>
		</div>

		{{if count( $data['rows'] ) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdpc_compliance_empty"}</p></div>
		{{else}}
		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th style="width:80px">{lang="gdpc_compliance_state"}</th>
					<th>{lang="gdpc_compliance_type"}</th>
					<th>{lang="gdpc_compliance_criteria"}</th>
					<th>{lang="gdpc_compliance_notes"}</th>
					<th style="width:100px">{lang="gdpc_compliance_active"}</th>
					<th style="width:180px">{lang="gdpc_compliance_actions"}</th>
				</tr>
			</thead>
			<tbody>
			{{foreach $data['rows'] as $row}}
				<tr>
					<td><strong>{$row['state']}</strong></td>
					<td><code>{$row['type']}</code></td>
					<td><code style="font-size:0.85em">{expression="htmlspecialchars( mb_substr( $row['criteria'], 0, 80 ) )"}</code></td>
					<td>{expression="htmlspecialchars( mb_substr( $row['notes'], 0, 80 ) )"}</td>
					<td>
						{{if $row['active']}}
							<span class="ipsBadge ipsBadge--positive">{lang="gdpc_compliance_status_active"}</span>
						{{else}}
							<span class="ipsBadge ipsBadge--neutral">{lang="gdpc_compliance_status_inactive"}</span>
						{{endif}}
					</td>
					<td>
						<a href="{$row['edit_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdpc_compliance_edit"}</a>
						<a href="{$row['delete_url']}" class="ipsButton ipsButton--negative ipsButton--small" data-confirm>{lang="gdpc_compliance_delete"}</a>
					</td>
				</tr>
			{{endforeach}}
			</tbody>
		</table>
		{{endif}}

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: compliance form — rendered by \IPS\Helpers\Form in controller ===== */

	/* ===== FRONT: product ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'front',
		'group'         => 'pricecompare',
		'template_name' => 'product',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">
		<div class="ipsType_breadcrumb ipsType_small" style="margin-bottom:8px">
			<a href="/">Home</a> &rsaquo;
			<a href="/products/{$data['product']['category_slug']}">{$data['product']['category_slug']}</a>
		</div>
		<div class="ipsGrid ipsGrid_collapsePhone">
			<div class="ipsGrid_span4">
				{{if $data['product']['primary_image']}}
					<img src="{$data['product']['primary_image']}" alt="{$data['product']['title']}" class="ipsImage" style="width:100%;border-radius:6px" />
				{{else}}
					<div style="background:#eee;border-radius:6px;aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;color:#999">No image</div>
				{{endif}}
			</div>
			<div class="ipsGrid_span8">
				<h1 class="ipsType_pageTitle" style="margin:0 0 6px 0">{$data['product']['title']}</h1>
				<div class="ipsType_light" style="font-size:16px;margin-bottom:12px">{$data['product']['brand']} {$data['product']['model']}</div>
				<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px">
					{{if $data['is_nfa']}}<span class="ipsBadge ipsBadge_negative">{lang="gdpc_front_nfa_item"}</span>{{endif}}
					{{if $data['is_ffl']}}<span class="ipsBadge ipsBadge_warning">{lang="gdpc_front_ffl_required"}</span>{{endif}}
					{{if $data['state_restriction']}}<span class="ipsBadge ipsBadge_warning">{lang="gdpc_front_state_restricted"}</span>{{endif}}
				</div>
				{{if count($data['spec_pills']) > 0}}
				<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px">
				{{foreach $data['spec_pills'] as $pill}}
					<span class="ipsBadge ipsBadge_neutral" style="background:#f2f2f2;color:#333">{$pill['label']}: <strong>{$pill['value']}</strong></span>
				{{endforeach}}
				</div>
				{{endif}}
				<div class="ipsType_small ipsType_light" style="margin-bottom:16px">UPC: {$data['product']['upc']}</div>
				<div style="display:flex;gap:8px;flex-wrap:wrap">
					<a href="{$data['watch_url']}" class="ipsButton ipsButton--primary">
						{{if $data['watching']}}&#9733; Watching{{else}}{lang="gdpc_front_watch_product"}{{endif}}
					</a>
					{{if $data['is_ffl']}}
						<a href="/ffl" class="ipsButton ipsButton--normal">{lang="gdpc_front_find_ffl"}</a>
					{{endif}}
				</div>
			</div>
		</div>
	</div>
</div>

<div class="ipsBox ipsPull ipsSpacer_top">
	<div class="ipsBox_body ipsPad">
		<h2 class="ipsType_sectionHead" style="margin-top:0">{lang="gdpc_front_price_comparison"}</h2>
		{{if $data['best_total'] !== null}}
			<div class="ipsType_light" style="margin-bottom:12px">
				{lang="gdpc_front_best_deal_label"} <strong style="color:#2a8a2a;font-size:18px">{$data['best_total_fmt']}</strong>
			</div>
		{{endif}}
		{{if count($data['in_stock']) > 0}}
		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th>{lang="gdpc_front_col_dealer"}</th>
					<th style="text-align:right">{lang="gdpc_front_col_price"}</th>
					<th style="text-align:right">{lang="gdpc_front_col_shipping"}</th>
					<th style="text-align:right">{lang="gdpc_front_col_total"}</th>
					{{if $data['is_ammo']}}<th style="text-align:right">{lang="gdpc_front_col_cpr"}</th>{{endif}}
					<th></th>
				</tr>
			</thead>
			<tbody>
			{{foreach $data['in_stock'] as $row}}
				<tr>
					<td>
						<strong>{$row['dealer_name']}</strong>
						{{if $row['is_best_price']}}<span class="ipsBadge ipsBadge_positive" style="margin-left:6px">{lang="gdpc_front_best_price"}</span>{{endif}}
						{{if $row['is_restricted_state']}}<span class="ipsBadge ipsBadge_warning" style="margin-left:6px">{lang="gdpc_front_restricted"}</span>{{endif}}
					</td>
					<td style="text-align:right">{expression="'$' . number_format($row['dealer_price'], 2)"}</td>
					<td style="text-align:right">
						{{if $row['free_shipping']}}<span class="ipsBadge ipsBadge_positive">{lang="gdpc_front_free_ship"}</span>
						{{elseif $row['shipping_cost'] === null}}<span class="ipsType_light">&mdash;</span>
						{{else}}{expression="'$' . number_format($row['shipping_cost'], 2)"}
						{{endif}}
					</td>
					<td style="text-align:right">
						<strong>
						{{if $row['total_unknown']}}{expression="'$' . number_format($row['dealer_price'], 2) . ' + ship'"}
						{{else}}{expression="'$' . number_format($row['total_cost'], 2)"}
						{{endif}}
						</strong>
					</td>
					{{if $data['is_ammo']}}
					<td style="text-align:right">{{if $row['cpr'] === null}}<span class="ipsType_light">&mdash;</span>{{else}}{expression="'$' . number_format($row['cpr'], 4) . '/rd'"}{{endif}}</td>
					{{endif}}
					<td style="text-align:right">
						<a href="/go/{$row['listing_id']}" rel="nofollow sponsored noopener" target="_blank" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdpc_front_buy"}</a>
					</td>
				</tr>
			{{endforeach}}
			</tbody>
		</table>
		{{else}}
		<div class="ipsEmptyMessage"><p>{lang="gdpc_front_no_listings"}</p></div>
		{{endif}}

		{{if count($data['out_of_stock']) > 0}}
		<h3 class="ipsType_sectionHead" style="margin-top:24px">{lang="gdpc_front_out_of_stock"}</h3>
		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<tbody>
			{{foreach $data['out_of_stock'] as $row}}
				<tr>
					<td>{$row['dealer_name']}</td>
					<td style="text-align:right">{expression="'$' . number_format($row['dealer_price'], 2)"}</td>
					<td style="text-align:right"><span class="ipsBadge ipsBadge_neutral">{lang="gdpc_front_oos_badge"}</span></td>
				</tr>
			{{endforeach}}
			</tbody>
		</table>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: browse ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'front',
		'group'         => 'pricecompare',
		'template_name' => 'browse',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">
		<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
			<div>
				<h1 class="ipsType_pageTitle" style="margin:0">{{if $data['category']}}{$data['category']}{{else}}{lang="gdpc_front_browse_title"}{{endif}}</h1>
				<div class="ipsType_light" style="margin-top:4px">{$data['count']} {lang="gdpc_front_products_suffix"}</div>
			</div>
			<form method="get" action="" style="display:flex;gap:8px;align-items:center">
				<input type="hidden" name="app" value="gdpricecompare" />
				<input type="hidden" name="module" value="products" />
				<input type="hidden" name="controller" value="products" />
				<input type="hidden" name="do" value="browse" />
				<input type="hidden" name="category" value="{$data['category']}" />
				<input type="search" name="q" placeholder="{lang='gdpc_front_search_placeholder'}" value="{$data['q']}" style="padding:8px 12px;border:1px solid #ddd;border-radius:4px" />
				<button type="submit" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdpc_front_search"}</button>
			</form>
		</div>
	</div>
</div>

<div class="ipsSpacer_top">
	{{if $data['count'] === 0}}
		<div class="ipsBox ipsPull"><div class="ipsBox_body ipsPad"><div class="ipsEmptyMessage"><p>{lang="gdpc_front_browse_empty"}</p></div></div></div>
	{{else}}
	<div class="ipsGrid ipsGrid_collapsePhone">
	{{foreach $data['rows'] as $row}}
		<div class="ipsGrid_span3">
			<div class="ipsBox ipsPull" style="height:100%">
				<div class="ipsBox_body ipsPad" style="display:flex;flex-direction:column;height:100%">
					<a href="{$row['url']}" style="text-decoration:none;color:inherit">
						{{if $row['image']}}
							<img src="{$row['image']}" alt="{$row['title']}" style="width:100%;aspect-ratio:1/1;object-fit:contain;background:#fafafa;border-radius:4px;margin-bottom:10px" />
						{{else}}
							<div style="width:100%;aspect-ratio:1/1;background:#f0f0f0;border-radius:4px;margin-bottom:10px;display:flex;align-items:center;justify-content:center;color:#aaa">No image</div>
						{{endif}}
						<div class="ipsType_bold" style="font-size:14px;line-height:1.3;margin-bottom:4px;min-height:36px">{$row['title']}</div>
					</a>
					<div class="ipsType_light ipsType_small" style="margin-bottom:8px">{$row['brand']}</div>
					<div style="margin-top:auto">
						<div style="font-size:18px;font-weight:700;color:#2a8a2a">
							{{if $row['from_price'] === null}}—{{else}}{expression="'$' . number_format($row['from_price'], 2)"}{{endif}}
						</div>
						<div class="ipsType_small ipsType_light" style="margin-bottom:6px">{lang="gdpc_front_from_label"} &middot; {$row['dealer_count']} {lang="gdpc_front_dealers_suffix"}</div>
						<div style="display:flex;gap:4px;flex-wrap:wrap">
							{{if $row['free_ship']}}<span class="ipsBadge ipsBadge_positive" style="font-size:11px">{lang="gdpc_front_free_ship"}</span>{{endif}}
							{{if $row['is_nfa']}}<span class="ipsBadge ipsBadge_negative" style="font-size:11px">{lang="gdpc_front_nfa_item"}</span>{{endif}}
							{{if $row['is_ffl']}}<span class="ipsBadge ipsBadge_warning" style="font-size:11px">{lang="gdpc_front_ffl_required"}</span>{{endif}}
						</div>
					</div>
				</div>
			</div>
		</div>
	{{endforeach}}
	</div>
	{{endif}}
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: watchlist ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'front',
		'group'         => 'pricecompare',
		'template_name' => 'watchlist',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">
		<h1 class="ipsType_pageTitle" style="margin:0 0 4px 0">{lang="gdpc_front_watchlist_title"}</h1>
		<div class="ipsType_light">{lang="gdpc_front_watchlist_subtitle"}</div>
	</div>
</div>

<div class="ipsBox ipsPull ipsSpacer_top">
	<div class="ipsBox_body ipsPad">
		{{if $data['count'] === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdpc_front_watchlist_empty"}</p></div>
		{{else}}
		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th>{lang="gdpc_front_col_product"}</th>
					<th style="text-align:right">{lang="gdpc_front_col_current_lowest"}</th>
					<th style="text-align:right">{lang="gdpc_front_col_target"}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			{{foreach $data['rows'] as $row}}
				<tr>
					<td><a href="{$row['product_url']}">{$row['title']}</a></td>
					<td style="text-align:right">{{if $row['current_min'] === null}}<span class="ipsType_light">&mdash;</span>{{else}}{expression="'$' . number_format($row['current_min'], 2)"}{{endif}}</td>
					<td style="text-align:right">{{if $row['target_price'] === null}}<span class="ipsType_light">{lang="gdpc_front_any_decrease"}</span>{{else}}{expression="'$' . number_format($row['target_price'], 2)"}{{endif}}</td>
					<td style="text-align:right"><a href="{$row['remove_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdpc_front_remove"}</a></td>
				</tr>
			{{endforeach}}
			</tbody>
		</table>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: ffl ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'front',
		'group'         => 'pricecompare',
		'template_name' => 'ffl',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull" style="background:linear-gradient(135deg,#1e3a5f,#2c5282);color:#fff">
	<div class="ipsBox_body ipsPad" style="padding:40px 24px;text-align:center">
		<h1 class="ipsType_pageTitle" style="color:#fff;margin:0 0 8px 0;font-size:32px">{lang="gdpc_front_ffl_title"}</h1>
		<p style="color:rgba(255,255,255,0.85);font-size:16px;max-width:540px;margin:0 auto 24px auto">{lang="gdpc_front_ffl_hero_body"}</p>
		<form method="get" action="" style="max-width:520px;margin:0 auto;display:flex;gap:8px;flex-wrap:wrap;justify-content:center">
			<input type="hidden" name="app" value="gdpricecompare" />
			<input type="hidden" name="module" value="ffl" />
			<input type="hidden" name="controller" value="locator" />
			<input type="text" name="zip" value="{$data['zip']}" maxlength="5" placeholder="{lang='gdpc_front_ffl_zip_placeholder'}" style="flex:1 1 180px;padding:12px 14px;font-size:16px;border:none;border-radius:4px;color:#222" required />
			<select name="radius" style="padding:12px 14px;font-size:16px;border:none;border-radius:4px;color:#222">
				<option value="10" {{if $data['radius'] === 10}}selected{{endif}}>10 mi</option>
				<option value="25" {{if $data['radius'] === 25}}selected{{endif}}>25 mi</option>
				<option value="50" {{if $data['radius'] === 50}}selected{{endif}}>50 mi</option>
				<option value="100" {{if $data['radius'] === 100}}selected{{endif}}>100 mi</option>
			</select>
			<button type="submit" class="ipsButton ipsButton--primary">{lang="gdpc_front_ffl_search"}</button>
		</form>
	</div>
</div>

<div class="ipsBox ipsPull ipsSpacer_top">
	<div class="ipsBox_body ipsPad">
		{{if $data['zip'] === ''}}
			<div class="ipsEmptyMessage"><p>{lang="gdpc_front_ffl_prompt"}</p></div>
		{{elseif count($data['results']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdpc_front_ffl_none"}</p></div>
		{{else}}
			<h2 class="ipsType_sectionHead" style="margin-top:0">{$data['count_label']}</h2>
			<div class="ipsGrid ipsGrid_collapsePhone">
			{{foreach $data['results'] as $row}}
				<div class="ipsGrid_span6">
					<div style="padding:14px 16px;border:1px solid #e0e0e0;border-radius:6px;margin-bottom:12px">
						<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
							<div style="flex:1">
								<div class="ipsType_bold" style="font-size:15px">{{if $row['business_name']}}{$row['business_name']}{{else}}{$row['licensee_name']}{{endif}}</div>
								<div class="ipsType_light ipsType_small">{$row['premise_street']}</div>
								<div class="ipsType_light ipsType_small">{$row['premise_city']}, {$row['premise_state']} {$row['premise_zip']}</div>
								{{if $row['voice_phone']}}<div class="ipsType_small" style="margin-top:4px">&#9742; {$row['voice_phone']}</div>{{endif}}
							</div>
							<div style="text-align:right">
								<div class="ipsBadge ipsBadge_positive" style="font-size:12px">{expression="number_format($row['distance'], 1) . ' mi'"}</div>
								{{if $row['lic_type']}}<div class="ipsType_small ipsType_light" style="margin-top:6px">{lang="gdpc_front_ffl_lic"} {$row['lic_type']}</div>{{endif}}
							</div>
						</div>
					</div>
				</div>
			{{endforeach}}
			</div>
			{{if $data['gmaps_key']}}
			<div style="margin-top:16px;padding:12px;background:#fafafa;border-radius:4px;text-align:center">
				<p class="ipsType_small ipsType_light" style="margin:0">{lang="gdpc_front_ffl_maps_hint"}</p>
			</div>
			{{endif}}
		{{endif}}
	</div>
</div>
TEMPLATE_EOT,
	],

];

foreach ( $gdpricecompareTemplates as $tpl )
{
	\IPS\Db::i()->insert( 'core_theme_templates', [
		'template_set_id'   => $tpl['set_id'],
		'template_app'      => $tpl['app'],
		'template_location' => $tpl['location'],
		'template_group'    => $tpl['group'],
		'template_name'     => $tpl['template_name'],
		'template_data'     => $tpl['template_data'],
		'template_content'  => $tpl['template_content'],
	]);
}

$gdpricecompareStateSeeds = [
	[ 'CA', 'magazine_capacity', '{"magazine_capacity":[">",10]}', 'California — magazines over 10 rounds restricted.' ],
	[ 'NY', 'magazine_capacity', '{"magazine_capacity":[">",10]}', 'New York — magazines over 10 rounds restricted.' ],
	[ 'NJ', 'magazine_capacity', '{"magazine_capacity":[">",10]}', 'New Jersey — magazines over 10 rounds restricted.' ],
	[ 'MA', 'magazine_capacity', '{"magazine_capacity":[">",10]}', 'Massachusetts — magazines over 10 rounds restricted.' ],
	[ 'CT', 'magazine_capacity', '{"magazine_capacity":[">",10]}', 'Connecticut — magazines over 10 rounds restricted.' ],
	[ 'CO', 'magazine_capacity', '{"magazine_capacity":[">",15]}', 'Colorado — magazines over 15 rounds restricted.' ],
	[ 'MD', 'magazine_capacity', '{"magazine_capacity":[">",10]}', 'Maryland — magazines over 10 rounds restricted.' ],
	[ 'HI', 'magazine_capacity', '{"magazine_capacity":[">",10]}', 'Hawaii — magazines over 10 rounds on handguns restricted.' ],
	[ 'IL', 'nfa',               '{"nfa_item":true}',              'Illinois — assorted NFA restrictions apply; verify locally.' ],
	[ 'HI', 'nfa',               '{"nfa_item":true}',              'Hawaii — suppressors and SBRs restricted.' ],
];

foreach ( $gdpricecompareStateSeeds as $seed )
{
	try
	{
		\IPS\Db::i()->select(
			'id', 'gd_state_restrictions',
			[ 'state_code=? AND restriction_type=?', $seed[0], $seed[1] ]
		)->first();
	}
	catch ( \UnderflowException )
	{
		\IPS\Db::i()->insert( 'gd_state_restrictions', [
			'state_code'       => $seed[0],
			'restriction_type' => $seed[1],
			'criteria_json'    => $seed[2],
			'notes'            => $seed[3],
			'active'           => 1,
		]);
	}
	catch ( \Exception ) {}
}
