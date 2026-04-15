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
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gdpc_dash_title"}</h1>
	<div class="ipsPad">

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
				<h3 class="ipsType_sectionHead" style="margin-top:0">{lang="gdpc_dash_top_searches"}</h3>
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th style="width:80px">{lang="gdpc_searchlog_count"}</th></tr></thead>
					<tbody>
					{{foreach $data['top_searches'] as $row}}
					<tr><td>{$row['query']}</td><td>{expression="number_format( $row['count'] )"}</td></tr>
					{{endforeach}}
					{{if count( $data['top_searches'] ) === 0}}
					<tr><td colspan="2" style="text-align:center;color:#999;padding:24px">No searches recorded in the last 7 days.</td></tr>
					{{endif}}
					</tbody>
				</table>
			</div>
			<div class="ipsBox" style="flex:1 1 320px;padding:16px">
				<h3 class="ipsType_sectionHead" style="margin-top:0">{lang="gdpc_dash_zero_results"}</h3>
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th style="width:80px">{lang="gdpc_searchlog_count"}</th></tr></thead>
					<tbody>
					{{foreach $data['zero_searches'] as $row}}
					<tr><td>{$row['query']}</td><td>{expression="number_format( $row['count'] )"}</td></tr>
					{{endforeach}}
					{{if count( $data['zero_searches'] ) === 0}}
					<tr><td colspan="2" style="text-align:center;color:#999;padding:24px">No zero-result searches in the last 7 days.</td></tr>
					{{endif}}
					</tbody>
				</table>
			</div>
		</div>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: settings ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'admin',
		'group'         => 'pricecompare',
		'template_name' => 'settings',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gdpc_settings_title"}</h1>
	<div class="ipsPad">

		<p class="ipsType_light" style="margin-bottom:16px">{lang="gdpc_settings_intro"}</p>

		<form method="post" action="" class="ipsForm ipsForm_vertical">
			<input type="hidden" name="csrfKey" value="{$data['csrf_key']}" />

			<div class="ipsBox" style="margin-bottom:16px">
				<h2 class="ipsBox_title">{lang="gdpc_settings_section_display"}</h2>
				<div class="ipsPad">
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label" for="gdpc_default_sort">{lang="gdpc_settings_default_sort"}</label>
						<select id="gdpc_default_sort" name="gdpc_default_sort" class="ipsField_select">
							<option value="total_price_asc" {{if $data['default_sort'] === 'total_price_asc'}}selected{{endif}}>Total price, lowest first</option>
							<option value="total_price_desc" {{if $data['default_sort'] === 'total_price_desc'}}selected{{endif}}>Total price, highest first</option>
							<option value="price_asc" {{if $data['default_sort'] === 'price_asc'}}selected{{endif}}>Dealer price, lowest first</option>
							<option value="cpr_asc" {{if $data['default_sort'] === 'cpr_asc'}}selected{{endif}}>Cost per round, lowest first</option>
						</select>
						<p class="ipsType_light ipsType_small">{lang="gdpc_settings_default_sort_desc"}</p>
					</div>
				</div>
			</div>

			<div class="ipsBox" style="margin-bottom:16px">
				<h2 class="ipsBox_title">{lang="gdpc_settings_section_shipping"}</h2>
				<div class="ipsPad">
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label" for="gdpc_free_ship_threshold">{lang="gdpc_settings_free_ship_threshold"}</label>
						<input type="number" step="0.01" min="0" id="gdpc_free_ship_threshold" name="gdpc_free_ship_threshold" value="{$data['free_ship_threshold']}" />
						<p class="ipsType_light ipsType_small">{lang="gdpc_settings_free_ship_threshold_desc"}</p>
					</div>
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label">
							<input type="checkbox" name="gdpc_cpr_include_shipping_default" value="1" {{if $data['cpr_include_shipping']}}checked{{endif}} />
							{lang="gdpc_settings_cpr_shipping_default"}
						</label>
						<p class="ipsType_light ipsType_small">{lang="gdpc_settings_cpr_shipping_default_desc"}</p>
					</div>
				</div>
			</div>

			<div class="ipsBox" style="margin-bottom:16px">
				<h2 class="ipsBox_title">{lang="gdpc_settings_section_ffl"}</h2>
				<div class="ipsPad">
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label" for="gdpc_ffl_radius_default">{lang="gdpc_settings_ffl_radius"}</label>
						<input type="number" min="1" max="200" id="gdpc_ffl_radius_default" name="gdpc_ffl_radius_default" value="{$data['ffl_radius']}" />
						<p class="ipsType_light ipsType_small">{lang="gdpc_settings_ffl_radius_desc"}</p>
					</div>
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label" for="gdpc_google_maps_api_key">{lang="gdpc_settings_gmaps_key"}</label>
						<input type="text" id="gdpc_google_maps_api_key" name="gdpc_google_maps_api_key" value="{$data['google_maps_key']}" class="ipsField_fullWidth" />
						<p class="ipsType_light ipsType_small">{lang="gdpc_settings_gmaps_key_desc"}</p>
					</div>
				</div>
			</div>

			<div class="ipsBox" style="margin-bottom:16px">
				<h2 class="ipsBox_title">{lang="gdpc_settings_section_history"}</h2>
				<div class="ipsPad">
					<p class="ipsType_light ipsType_small" style="margin-bottom:12px">{lang="gdpc_settings_history_desc"}</p>
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label" for="gdpc_price_history_days_basic">{lang="gdpc_settings_history_basic"}</label>
						<input type="number" min="1" id="gdpc_price_history_days_basic" name="gdpc_price_history_days_basic" value="{$data['history_basic']}" />
					</div>
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label" for="gdpc_price_history_days_pro">{lang="gdpc_settings_history_pro"}</label>
						<input type="number" min="1" id="gdpc_price_history_days_pro" name="gdpc_price_history_days_pro" value="{$data['history_pro']}" />
					</div>
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label" for="gdpc_price_history_days_enterprise">{lang="gdpc_settings_history_enterprise"}</label>
						<input type="number" min="1" id="gdpc_price_history_days_enterprise" name="gdpc_price_history_days_enterprise" value="{$data['history_enterprise']}" />
					</div>
				</div>
			</div>

			<div class="ipsBox" style="margin-bottom:16px">
				<h2 class="ipsBox_title">{lang="gdpc_settings_section_alerts"}</h2>
				<div class="ipsPad">
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label" for="gdpc_alert_dedupe_hours">{lang="gdpc_settings_alert_dedupe"}</label>
						<input type="number" min="1" id="gdpc_alert_dedupe_hours" name="gdpc_alert_dedupe_hours" value="{$data['alert_dedupe_hours']}" />
						<p class="ipsType_light ipsType_small">{lang="gdpc_settings_alert_dedupe_desc"}</p>
					</div>
					<div class="ipsFieldRow">
						<label class="ipsFieldRow_label" for="gdpc_report_priority_threshold">{lang="gdpc_settings_report_threshold"}</label>
						<input type="number" min="1" id="gdpc_report_priority_threshold" name="gdpc_report_priority_threshold" value="{$data['report_threshold']}" />
						<p class="ipsType_light ipsType_small">{lang="gdpc_settings_report_threshold_desc"}</p>
					</div>
				</div>
			</div>

			<div style="margin-top:16px;text-align:right">
				<button type="submit" class="ipsButton ipsButton--primary">{lang="gdpc_settings_save"}</button>
			</div>
		</form>
	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: ffldata ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'admin',
		'group'         => 'pricecompare',
		'template_name' => 'ffldata',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gdpc_ffldata_title"}</h1>
	<div class="ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['count'] )"}</div>
				<div>{lang="gdpc_ffldata_count"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:1.3em;font-weight:bold">{{if $data['last']}}{$data['last']}{{else}}&mdash;{{endif}}</div>
				<div>{lang="gdpc_ffldata_last_updated"}</div>
			</div>
		</div>

		<div>
			<a href="{$data['refresh_url']}" class="ipsButton ipsButton--primary">{lang="gdpc_ffldata_refresh_now"}</a>
		</div>

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
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gdpc_searchlog_title"}</h1>
	<div class="ipsPad">

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
				<h3 class="ipsType_sectionHead" style="margin-top:0">{lang="gdpc_searchlog_top_title"}</h3>
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th style="width:80px">{lang="gdpc_searchlog_count"}</th></tr></thead>
					<tbody>
					{{foreach $data['top'] as $row}}
					<tr><td>{$row['query']}</td><td>{expression="number_format( $row['count'] )"}</td></tr>
					{{endforeach}}
					{{if count( $data['top'] ) === 0}}
					<tr><td colspan="2" style="text-align:center;color:#999;padding:24px">No searches recorded in the last 7 days.</td></tr>
					{{endif}}
					</tbody>
				</table>
			</div>
			<div class="ipsBox" style="flex:1 1 320px;padding:16px">
				<h3 class="ipsType_sectionHead" style="margin-top:0">{lang="gdpc_searchlog_zero_title"}</h3>
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th style="width:80px">{lang="gdpc_searchlog_count"}</th></tr></thead>
					<tbody>
					{{foreach $data['zero'] as $row}}
					<tr><td>{$row['query']}</td><td>{expression="number_format( $row['count'] )"}</td></tr>
					{{endforeach}}
					{{if count( $data['zero'] ) === 0}}
					<tr><td colspan="2" style="text-align:center;color:#999;padding:24px">No zero-result searches in the last 7 days.</td></tr>
					{{endif}}
					</tbody>
				</table>
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
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gdpc_compliance_title"}</h1>
	<div class="ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['count'] )"}</div>
				<div>{lang="gdpc_compliance_count"}</div>
			</div>
		</div>

		<div style="margin-bottom:12px">
			<a href="{$data['add_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdpc_compliance_add_rule"}</a>
		</div>

		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th style="width:80px">{lang="gdpc_compliance_state"}</th>
					<th>{lang="gdpc_compliance_type"}</th>
					<th>{lang="gdpc_compliance_criteria"}</th>
					<th>{lang="gdpc_compliance_notes"}</th>
					<th style="width:100px">{lang="gdpc_compliance_active"}</th>
					<th style="width:160px">{lang="gdpc_compliance_actions"}</th>
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
						<a href="{$row['edit_url']}" class="ipsButton ipsButton--small ipsButton--primary">{lang="gdpc_compliance_edit"}</a>
						<a href="{$row['delete_url']}" class="ipsButton ipsButton--small ipsButton--negative" data-confirm>{lang="gdpc_compliance_delete"}</a>
					</td>
				</tr>
			{{endforeach}}
			{{if count( $data['rows'] ) === 0}}
				<tr><td colspan="6" style="text-align:center;color:#999;padding:24px">{lang="gdpc_compliance_empty"}</td></tr>
			{{endif}}
			</tbody>
		</table>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: compliance add/edit form ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'admin',
		'group'         => 'pricecompare',
		'template_name' => 'complianceForm',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h1 class="ipsBox_title">
		{{if $data['is_edit']}}{lang="gdpc_compliance_edit_title"}{{else}}{lang="gdpc_compliance_add_title"}{{endif}}
	</h1>
	<div class="ipsPad">

		{{if count( $data['errors'] ) > 0}}
		<div class="ipsMessage ipsMessage--error ipsPad" style="margin-bottom:16px">
			<strong>{lang="gdpc_compliance_form_errors"}</strong>
			<ul style="margin:8px 0 0 20px">
			{{foreach $data['errors'] as $err}}
				<li>{$err}</li>
			{{endforeach}}
			</ul>
		</div>
		{{endif}}

		<form method="post" action="{$data['submit_url']}" class="ipsForm ipsForm_vertical">
			<input type="hidden" name="csrfKey" value="{$data['csrf_key']}" />

			<div class="ipsFieldRow">
				<label class="ipsFieldRow_label" for="state_code">{lang="gdpc_compliance_state"}</label>
				<select id="state_code" name="state_code" class="ipsField_select" required>
					<option value="">{lang="gdpc_compliance_state_select"}</option>
					{{foreach $data['states'] as $st}}
						<option value="{$st['code']}" {{if $data['state'] === $st['code']}}selected{{endif}}>{$st['code']} &mdash; {$st['name']}</option>
					{{endforeach}}
				</select>
			</div>

			<div class="ipsFieldRow">
				<label class="ipsFieldRow_label" for="restriction_type">{lang="gdpc_compliance_type"}</label>
				<input type="text" id="restriction_type" name="restriction_type" value="{$data['type']}" class="ipsField_fullWidth" maxlength="40" pattern="[a-z0-9_]+" placeholder="nfa, magazine_capacity, assault_weapon, handgun, shipping_prohibited, silencer, sbr, sbs" required />
				<p class="ipsType_light ipsType_small">{lang="gdpc_compliance_type_desc"}</p>
			</div>

			<div class="ipsFieldRow">
				<label class="ipsFieldRow_label" for="criteria_json">{lang="gdpc_compliance_criteria"}</label>
				<textarea id="criteria_json" name="criteria_json" rows="4" class="ipsField_fullWidth" style="font-family:monospace" placeholder='{"magazine_capacity":[">",10]}'>{$data['criteria']}</textarea>
				<p class="ipsType_light ipsType_small">{lang="gdpc_compliance_criteria_desc"}</p>
			</div>

			<div class="ipsFieldRow">
				<label class="ipsFieldRow_label" for="notes">{lang="gdpc_compliance_notes"}</label>
				<textarea id="notes" name="notes" rows="2" class="ipsField_fullWidth">{$data['notes']}</textarea>
			</div>

			<div class="ipsFieldRow">
				<label class="ipsFieldRow_label">
					<input type="checkbox" name="active" value="1" {{if $data['active']}}checked{{endif}} />
					{lang="gdpc_compliance_active"}
				</label>
			</div>

			<div style="margin-top:16px;display:flex;gap:8px">
				<button type="submit" class="ipsButton ipsButton--primary">
					{{if $data['is_edit']}}{lang="gdpc_compliance_save"}{{else}}{lang="gdpc_compliance_create"}{{endif}}
				</button>
				<a href="{$data['cancel_url']}" class="ipsButton ipsButton--normal">{lang="gdpc_compliance_cancel"}</a>
			</div>
		</form>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: product ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdpricecompare',
		'location'      => 'front',
		'group'         => 'pricecompare',
		'template_name' => 'product',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsPad">
	<div class="ipsType_breadcrumb">
		<a href="/products/{$data['product']['category_slug']}">{$data['product']['category_slug']}</a>
	</div>

	<div class="ipsGrid ipsGrid_collapsePhone">
		<div class="ipsGrid_span4">
			{{if $data['product']['primary_image']}}
			<img src="{$data['product']['primary_image']}" alt="{$data['product']['title']}" class="ipsImage" />
			{{endif}}
		</div>
		<div class="ipsGrid_span8">
			<h1 class="ipsType_pageTitle">{$data['product']['title']}</h1>
			<div class="ipsType_light">{$data['product']['brand']} {$data['product']['model']}</div>
			<div class="ipsSpacer_top">
				{{if $data['is_nfa']}}<span class="ipsBadge ipsBadge_negative">{lang="gdpc_front_nfa_item"}</span>{{endif}}
				{{if $data['is_ffl']}}<span class="ipsBadge ipsBadge_warning">{lang="gdpc_front_ffl_required"}</span>{{endif}}
				{{if $data['state_restriction']}}<span class="ipsBadge ipsBadge_warning">{lang="gdpc_front_state_restricted"}</span>{{endif}}
			</div>
			<div class="ipsSpacer_top ipsType_small">UPC: {$data['product']['upc']}</div>

			<div class="ipsSpacer_top">
				<a href="{$data['watch_url']}" class="ipsButton ipsButton_primary">
					{{if $data['watching']}}★ Watching{{else}}{lang="gdpc_front_watch_product"}{{endif}}
				</a>
			</div>
		</div>
	</div>

	<h2 class="ipsType_sectionHead ipsSpacer_top">Price Comparison</h2>
	{{if count($data['in_stock']) > 0}}
	<table class="ipsTable">
		<thead>
			<tr>
				<th>Dealer</th>
				<th>Price</th>
				<th>Shipping</th>
				<th>Total</th>
				{{if $data['is_ammo']}}<th>CPR</th>{{endif}}
				<th></th>
			</tr>
		</thead>
		<tbody>
		{{foreach $data['in_stock'] as $row}}
			<tr>
				<td>
					{$row['dealer_name']}
					{{if $row['is_best_price']}}<span class="ipsBadge ipsBadge_positive">{lang="gdpc_front_best_price"}</span>{{endif}}
					{{if $row['is_restricted_state']}}<span class="ipsBadge ipsBadge_warning">{lang="gdpc_front_restricted"}</span>{{endif}}
				</td>
				<td>{expression="'$' . number_format($row['dealer_price'], 2)"}</td>
				<td>
					{{if $row['free_shipping']}}<span class="ipsBadge ipsBadge_positive">{lang="gdpc_front_free_ship"}</span>
					{{elseif $row['shipping_cost'] === null}}—
					{{else}}{expression="'$' . number_format($row['shipping_cost'], 2)"}
					{{endif}}
				</td>
				<td>
					{{if $row['total_unknown']}}{expression="'$' . number_format($row['dealer_price'], 2) . ' + ship'"}
					{{else}}{expression="'$' . number_format($row['total_cost'], 2)"}
					{{endif}}
				</td>
				{{if $data['is_ammo']}}
				<td>{{if $row['cpr'] === null}}—{{else}}{expression="number_format($row['cpr'], 4) . '/rd'"}{{endif}}</td>
				{{endif}}
				<td>
					<a href="/go/{$row['listing_id']}" rel="nofollow sponsored noopener" target="_blank" class="ipsButton ipsButton_primary">Buy</a>
				</td>
			</tr>
		{{endforeach}}
		</tbody>
	</table>
	{{else}}
	<p class="ipsType_light">No active listings currently available for this product.</p>
	{{endif}}

	{{if count($data['out_of_stock']) > 0}}
	<h3 class="ipsType_sectionHead ipsSpacer_top">{lang="gdpc_front_out_of_stock"}</h3>
	<table class="ipsTable">
		<tbody>
		{{foreach $data['out_of_stock'] as $row}}
			<tr>
				<td>{$row['dealer_name']}</td>
				<td>{expression="'$' . number_format($row['dealer_price'], 2)"}</td>
				<td><em>out of stock</em></td>
			</tr>
		{{endforeach}}
		</tbody>
	</table>
	{{endif}}
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
<h1 class="ipsType_pageTitle">{{if $data['category']}}{$data['category']}{{else}}{lang="gdpc_front_browse_title"}{{endif}}</h1>
<div class="ipsPad">
	<div class="ipsType_light">{$data['count']} products</div>
	<div class="ipsGrid ipsGrid_collapsePhone ipsSpacer_top">
	{{foreach $data['rows'] as $row}}
		<div class="ipsGrid_span3">
			<div class="ipsBox ipsPad">
				<a href="{$row['url']}">
					{{if $row['image']}}<img src="{$row['image']}" alt="{$row['title']}" class="ipsImage" />{{endif}}
					<div class="ipsType_reset ipsType_bold">{$row['title']}</div>
				</a>
				<div class="ipsType_light">{$row['brand']}</div>
				<div class="ipsSpacer_top">
					{{if $row['from_price'] === null}}—{{else}}{expression="'From $' . number_format($row['from_price'], 2)"}{{endif}}
					{{if $row['free_ship']}}<span class="ipsBadge ipsBadge_positive">{lang="gdpc_front_free_ship"}</span>{{endif}}
				</div>
				<div class="ipsType_small">{$row['dealer_count']} dealers</div>
				<div>
					{{if $row['is_nfa']}}<span class="ipsBadge ipsBadge_negative">{lang="gdpc_front_nfa_item"}</span>{{endif}}
					{{if $row['is_ffl']}}<span class="ipsBadge ipsBadge_warning">{lang="gdpc_front_ffl_required"}</span>{{endif}}
				</div>
			</div>
		</div>
	{{endforeach}}
	</div>
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
<h1 class="ipsType_pageTitle">{lang="gdpc_front_watchlist_title"}</h1>
<div class="ipsPad">
	{{if $data['count'] === 0}}
	<p class="ipsType_light">{lang="gdpc_front_watchlist_empty"}</p>
	{{else}}
	<table class="ipsTable">
		<thead>
			<tr>
				<th>Product</th>
				<th>Current Lowest</th>
				<th>Target</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		{{foreach $data['rows'] as $row}}
			<tr>
				<td><a href="{$row['product_url']}">{$row['title']}</a></td>
				<td>{{if $row['current_min'] === null}}—{{else}}{expression="'$' . number_format($row['current_min'], 2)"}{{endif}}</td>
				<td>{{if $row['target_price'] === null}}any decrease{{else}}{expression="'$' . number_format($row['target_price'], 2)"}{{endif}}</td>
				<td><a href="{$row['remove_url']}" class="ipsButton ipsButton_light">Remove</a></td>
			</tr>
		{{endforeach}}
		</tbody>
	</table>
	{{endif}}
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
<h1 class="ipsType_pageTitle">{lang="gdpc_front_ffl_title"}</h1>
<div class="ipsPad">
	<form method="get" action="" class="ipsForm ipsForm_horizontal">
		<input type="hidden" name="app" value="gdpricecompare" />
		<input type="hidden" name="module" value="ffl" />
		<input type="hidden" name="controller" value="locator" />
		<div class="ipsFieldRow">
			<label class="ipsFieldRow_label">ZIP code</label>
			<input type="text" name="zip" value="{$data['zip']}" maxlength="5" />
		</div>
		<div class="ipsFieldRow">
			<label class="ipsFieldRow_label">Radius (miles)</label>
			<input type="number" min="1" max="200" name="radius" value="{$data['radius']}" />
		</div>
		<div class="ipsPad">
			<button type="submit" class="ipsButton ipsButton_primary">Search</button>
		</div>
	</form>

	{{if count($data['results']) === 0}}
		{{if $data['zip']}}<p class="ipsType_light">{lang="gdpc_front_ffl_none"}</p>
		{{else}}<p class="ipsType_light">{lang="gdpc_front_ffl_prompt"}</p>
		{{endif}}
	{{else}}
	<table class="ipsTable ipsSpacer_top">
		<thead>
			<tr><th>Dealer</th><th>Address</th><th>Phone</th><th>Distance</th></tr>
		</thead>
		<tbody>
		{{foreach $data['results'] as $row}}
			<tr>
				<td>{{if $row['business_name']}}{$row['business_name']}{{else}}{$row['licensee_name']}{{endif}}</td>
				<td>{$row['premise_street']}, {$row['premise_city']}, {$row['premise_state']} {$row['premise_zip']}</td>
				<td>{$row['voice_phone']}</td>
				<td>{expression="number_format($row['distance'], 1) . ' mi'"}</td>
			</tr>
		{{endforeach}}
		</tbody>
	</table>
	{{endif}}
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
