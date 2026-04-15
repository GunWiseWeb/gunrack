<?php
/**
 * @brief       GD Price Comparison — installer
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * Runs from Application::installOther() once schema.json tables are in place.
 * Seeds core_theme_templates rows directly so the IPS XML theme importer
 * cannot strip template comments or corrupt whitespace (CLAUDE.md Rule #4).
 * Also installs a small seed set of state compliance rules for launch.
 */

namespace IPS\gdpricecompare\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$gdpcAdminDashboard = <<<'TEMPLATE_EOT'
<h1 class="ipsType_pageTitle">{lang="gdpc_dash_title"}</h1>
<div class="ipsPad">
	<div class="ipsGrid ipsGrid_collapsePhone">
		<div class="ipsGrid_span3"><div class="ipsBox ipsPad">
			<div class="ipsType_reset ipsType_bold ipsType_large">{$data['product_count']}</div>
			<div class="ipsType_light">{lang="gdpc_dash_products_indexed"}</div>
		</div></div>
		<div class="ipsGrid_span3"><div class="ipsBox ipsPad">
			<div class="ipsType_reset ipsType_bold ipsType_large">{$data['listing_count']}</div>
			<div class="ipsType_light">{lang="gdpc_dash_listings_total"}</div>
		</div></div>
		<div class="ipsGrid_span3"><div class="ipsBox ipsPad">
			<div class="ipsType_reset ipsType_bold ipsType_large">{$data['watchlist_count']}</div>
			<div class="ipsType_light">{lang="gdpc_dash_watchlists"}</div>
		</div></div>
		<div class="ipsGrid_span3"><div class="ipsBox ipsPad">
			<div class="ipsType_reset ipsType_bold ipsType_large">{$data['ffl_count']}</div>
			<div class="ipsType_light">{lang="gdpc_dash_ffl_dealers"}</div>
		</div></div>
	</div>

	<div class="ipsSpacer_top">
		<div class="ipsBox ipsPad">
			<div class="ipsType_reset ipsType_bold">{$data['clicks_today']}</div>
			<div class="ipsType_light">{lang="gdpc_dash_clicks_today"}</div>
		</div>
	</div>

	<div class="ipsGrid ipsGrid_collapsePhone ipsSpacer_top">
		<div class="ipsGrid_span6"><div class="ipsBox ipsPad">
			<h3 class="ipsType_sectionHead">{lang="gdpc_dash_top_searches"}</h3>
			{{if count($data['top_searches']) > 0}}
			<table class="ipsTable">
				<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th>{lang="gdpc_searchlog_count"}</th></tr></thead>
				<tbody>
				{{foreach $data['top_searches'] as $row}}
				<tr><td>{$row['query']}</td><td>{$row['count']}</td></tr>
				{{endforeach}}
				</tbody>
			</table>
			{{endif}}
		</div></div>
		<div class="ipsGrid_span6"><div class="ipsBox ipsPad">
			<h3 class="ipsType_sectionHead">{lang="gdpc_dash_zero_results"}</h3>
			{{if count($data['zero_searches']) > 0}}
			<table class="ipsTable">
				<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th>{lang="gdpc_searchlog_count"}</th></tr></thead>
				<tbody>
				{{foreach $data['zero_searches'] as $row}}
				<tr><td>{$row['query']}</td><td>{$row['count']}</td></tr>
				{{endforeach}}
				</tbody>
			</table>
			{{endif}}
		</div></div>
	</div>
</div>
TEMPLATE_EOT;

$gdpcAdminSettings = <<<'TEMPLATE_EOT'
<h1 class="ipsType_pageTitle">{lang="gdpc_settings_title"}</h1>
<form method="post" action="" class="ipsForm ipsForm_vertical ipsPad">
	<input type="hidden" name="csrfKey" value="{$data['csrf_key']}" />

	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_default_sort"}</label>
		<input type="text" name="gdpc_default_sort" value="{$data['default_sort']}" class="ipsField_fullWidth" />
	</div>
	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_free_ship_threshold"}</label>
		<input type="text" name="gdpc_free_ship_threshold" value="{$data['free_ship_threshold']}" class="ipsField_fullWidth" />
	</div>
	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_cpr_shipping_default"}</label>
		<input type="checkbox" name="gdpc_cpr_include_shipping_default" value="1" {{if $data['cpr_include_shipping']}}checked{{endif}} />
	</div>
	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_ffl_radius"}</label>
		<input type="number" min="1" name="gdpc_ffl_radius_default" value="{$data['ffl_radius']}" />
	</div>
	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_history_basic"}</label>
		<input type="number" min="1" name="gdpc_price_history_days_basic" value="{$data['history_basic']}" />
	</div>
	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_history_pro"}</label>
		<input type="number" min="1" name="gdpc_price_history_days_pro" value="{$data['history_pro']}" />
	</div>
	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_history_enterprise"}</label>
		<input type="number" min="1" name="gdpc_price_history_days_enterprise" value="{$data['history_enterprise']}" />
	</div>
	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_gmaps_key"}</label>
		<input type="text" name="gdpc_google_maps_api_key" value="{$data['google_maps_key']}" class="ipsField_fullWidth" />
	</div>
	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_alert_dedupe"}</label>
		<input type="number" min="1" name="gdpc_alert_dedupe_hours" value="{$data['alert_dedupe_hours']}" />
	</div>
	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdpc_settings_report_threshold"}</label>
		<input type="number" min="1" name="gdpc_report_priority_threshold" value="{$data['report_threshold']}" />
	</div>

	<div class="ipsPad">
		<button type="submit" class="ipsButton ipsButton_primary">{lang="gdpc_settings_save"}</button>
	</div>
</form>
TEMPLATE_EOT;

$gdpcAdminFflData = <<<'TEMPLATE_EOT'
<h1 class="ipsType_pageTitle">{lang="gdpc_ffldata_title"}</h1>
<div class="ipsPad">
	<div class="ipsBox ipsPad">
		<div>{lang="gdpc_ffldata_count"}: <strong>{$data['count']}</strong></div>
		<div>{lang="gdpc_ffldata_last_updated"}: <strong>{{if $data['last']}}{$data['last']}{{else}}—{{endif}}</strong></div>
	</div>
	<div class="ipsSpacer_top">
		<a href="{$data['refresh_url']}" class="ipsButton ipsButton_primary">{lang="gdpc_ffldata_refresh_now"}</a>
	</div>
</div>
TEMPLATE_EOT;

$gdpcAdminSearchLog = <<<'TEMPLATE_EOT'
<h1 class="ipsType_pageTitle">{lang="gdpc_searchlog_title"}</h1>
<div class="ipsPad">
	<div class="ipsGrid ipsGrid_collapsePhone">
		<div class="ipsGrid_span6"><div class="ipsBox ipsPad">
			<h3 class="ipsType_sectionHead">{lang="gdpc_searchlog_top_title"}</h3>
			{{if count($data['top']) > 0}}
			<table class="ipsTable">
				<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th>{lang="gdpc_searchlog_count"}</th></tr></thead>
				<tbody>
				{{foreach $data['top'] as $row}}
				<tr><td>{$row['query']}</td><td>{$row['count']}</td></tr>
				{{endforeach}}
				</tbody>
			</table>
			{{endif}}
		</div></div>
		<div class="ipsGrid_span6"><div class="ipsBox ipsPad">
			<h3 class="ipsType_sectionHead">{lang="gdpc_searchlog_zero_title"}</h3>
			{{if count($data['zero']) > 0}}
			<table class="ipsTable">
				<thead><tr><th>{lang="gdpc_searchlog_query"}</th><th>{lang="gdpc_searchlog_count"}</th></tr></thead>
				<tbody>
				{{foreach $data['zero'] as $row}}
				<tr><td>{$row['query']}</td><td>{$row['count']}</td></tr>
				{{endforeach}}
				</tbody>
			</table>
			{{endif}}
		</div></div>
	</div>
</div>
TEMPLATE_EOT;

$gdpcAdminCompliance = <<<'TEMPLATE_EOT'
<h1 class="ipsType_pageTitle">{lang="gdpc_compliance_title"}</h1>
<div class="ipsPad">
	<div>{lang="gdpc_compliance_count"}: <strong>{$data['count']}</strong></div>
	<table class="ipsTable ipsSpacer_top">
		<thead>
			<tr>
				<th>{lang="gdpc_compliance_state"}</th>
				<th>{lang="gdpc_compliance_type"}</th>
				<th>{lang="gdpc_compliance_active"}</th>
			</tr>
		</thead>
		<tbody>
		{{foreach $data['rows'] as $row}}
			<tr>
				<td>{$row['state']}</td>
				<td>{$row['type']}</td>
				<td>{{if $row['active']}}✓{{else}}—{{endif}}</td>
			</tr>
		{{endforeach}}
		</tbody>
	</table>
</div>
TEMPLATE_EOT;

$gdpcFrontProduct = <<<'TEMPLATE_EOT'
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
TEMPLATE_EOT;

$gdpcFrontBrowse = <<<'TEMPLATE_EOT'
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
TEMPLATE_EOT;

$gdpcFrontWatchlist = <<<'TEMPLATE_EOT'
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
TEMPLATE_EOT;

$gdpcFrontFfl = <<<'TEMPLATE_EOT'
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
TEMPLATE_EOT;

$gdpricecompareTemplates = [
	[ 'location' => 'admin', 'group' => 'pricecompare', 'name' => 'dashboard',  'data' => '$data',           'content' => $gdpcAdminDashboard ],
	[ 'location' => 'admin', 'group' => 'pricecompare', 'name' => 'settings',   'data' => '$data',           'content' => $gdpcAdminSettings ],
	[ 'location' => 'admin', 'group' => 'pricecompare', 'name' => 'ffldata',    'data' => '$data',           'content' => $gdpcAdminFflData ],
	[ 'location' => 'admin', 'group' => 'pricecompare', 'name' => 'searchlog',  'data' => '$data',           'content' => $gdpcAdminSearchLog ],
	[ 'location' => 'admin', 'group' => 'pricecompare', 'name' => 'compliance', 'data' => '$data',           'content' => $gdpcAdminCompliance ],
	[ 'location' => 'front', 'group' => 'pricecompare', 'name' => 'product',    'data' => '$data',           'content' => $gdpcFrontProduct ],
	[ 'location' => 'front', 'group' => 'pricecompare', 'name' => 'browse',     'data' => '$data',           'content' => $gdpcFrontBrowse ],
	[ 'location' => 'front', 'group' => 'pricecompare', 'name' => 'watchlist',  'data' => '$data',           'content' => $gdpcFrontWatchlist ],
	[ 'location' => 'front', 'group' => 'pricecompare', 'name' => 'ffl',        'data' => '$data',           'content' => $gdpcFrontFfl ],
];

foreach ( $gdpricecompareTemplates as $tpl )
{
	try
	{
		\IPS\Db::i()->delete( 'core_theme_templates', [
			'template_set_id=? AND template_app=? AND template_location=? AND template_group=? AND template_name=?',
			1, 'gdpricecompare', $tpl['location'], $tpl['group'], $tpl['name']
		]);
	}
	catch ( \Exception ) {}

	\IPS\Db::i()->insert( 'core_theme_templates', [
		'template_set_id'  => 1,
		'template_app'     => 'gdpricecompare',
		'template_location'=> $tpl['location'],
		'template_group'   => $tpl['group'],
		'template_name'    => $tpl['name'],
		'template_data'    => $tpl['data'],
		'template_content' => $tpl['content'],
	]);
}

$stateSeeds = [
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

foreach ( $stateSeeds as $seed )
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
