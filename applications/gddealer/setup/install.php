<?php
/**
 * @brief       GD Dealer Manager — Install routine
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Runs after schema.json tables are created. Seeds templates directly into
 * core_theme_templates using nowdoc heredocs so real newlines/tabs are
 * preserved. No comments inside template bodies (Rule #9) — the IPS
 * template compiler does not parse comment syntax.
 */

$gddealerTemplates = [

	/* ===== ADMIN: dashboard ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'admin',
		'group'         => 'dealers',
		'template_name' => 'dashboard',
		'template_data' => '$totalDealers, $activeDealers, $suspendedDealers, $totalListings, $inStockListings, $unmatchedTotal, $lastRunTime, $lastRunStatus, $tierCounts, $dealersUrl, $mrrUrl, $unmatchedUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div style="display:flex;justify-content:flex-end;gap:8px;padding:10px 16px;border-bottom:1px solid var(--i-border-color, #e0e0e0)">
		<a href="{$dealersUrl}" class="ipsButton ipsButton--primary ipsButton--small">Manage Dealers</a>
		<a href="{$mrrUrl}" class="ipsButton ipsButton--normal ipsButton--small">MRR Dashboard</a>
		<a href="{$unmatchedUrl}" class="ipsButton ipsButton--normal ipsButton--small">Unmatched UPCs</a>
	</div>
	<div class="ipsBox_body ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{$totalDealers}</div>
				<div>{lang="gddealer_dash_total_dealers"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold;color:#2a8a2a">{$activeDealers}</div>
				<div>{lang="gddealer_dash_active_dealers"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold;color:#c00">{$suspendedDealers}</div>
				<div>{lang="gddealer_dash_suspended_dealers"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $totalListings )"}</div>
				<div>{lang="gddealer_dash_total_listings"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $inStockListings )"}</div>
				<div>{lang="gddealer_dash_in_stock_listings"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 150px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $unmatchedTotal )"}</div>
				<div>{lang="gddealer_dash_unmatched_total"}</div>
			</div>
		</div>

		<h2 class="ipsType_sectionHead" style="margin:0 0 12px">Subscription Tier Breakdown</h2>
		<table class="ipsTable ipsTable_zebra" style="width:100%;margin-bottom:24px">
			<thead>
				<tr><th>Tier</th><th>Dealers</th></tr>
			</thead>
			<tbody>
				<tr><td><strong>Basic</strong></td><td>{$tierCounts['basic']}</td></tr>
				<tr><td><strong>Pro</strong></td><td>{$tierCounts['pro']}</td></tr>
				<tr><td><strong>Enterprise</strong></td><td>{$tierCounts['enterprise']}</td></tr>
				<tr><td><strong>Founding</strong></td><td>{$tierCounts['founding']}</td></tr>
			</tbody>
		</table>

		<h2 class="ipsType_sectionHead" style="margin:0 0 12px">{lang="gddealer_dash_last_run"}</h2>
		<p>
			{{if $lastRunTime}}
				{$lastRunTime} &mdash;
				{{if $lastRunStatus === 'completed'}}
					<span class="ipsBadge ipsBadge--positive">Completed</span>
				{{elseif $lastRunStatus === 'failed'}}
					<span class="ipsBadge ipsBadge--negative">Failed</span>
				{{elseif $lastRunStatus === 'running'}}
					<span class="ipsBadge ipsBadge--warning">Running</span>
				{{else}}
					<span class="ipsBadge ipsBadge--neutral">{$lastRunStatus}</span>
				{{endif}}
			{{else}}
				<em>No imports have run yet.</em>
			{{endif}}
		</p>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: dealerList ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'admin',
		'group'         => 'dealers',
		'template_name' => 'dealerList',
		'template_data' => '$dealers',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">
		{{if count( $dealers ) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gddealer_dealers_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead>
					<tr>
						<th>{lang="gddealer_dealer_name"}</th>
						<th>{lang="gddealer_dealer_tier"}</th>
						<th>{lang="gddealer_dealer_status"}</th>
						<th>{lang="gddealer_dealer_listing_count"}</th>
						<th>{lang="gddealer_dealer_last_import"}</th>
						<th>{lang="gddealer_dealer_mrr"}</th>
						<th style="width:320px">{lang="gddealer_dealer_actions"}</th>
					</tr>
				</thead>
				<tbody>
					{{foreach $dealers as $d}}
					<tr>
						<td><strong>{$d['dealer_name']}</strong><br><small>ID {$d['dealer_id']}</small></td>
						<td>
							{{if $d['subscription_tier'] === 'enterprise'}}
								<span class="ipsBadge ipsBadge--positive">Enterprise</span>
							{{elseif $d['subscription_tier'] === 'pro'}}
								<span class="ipsBadge ipsBadge--style1">Pro</span>
							{{elseif $d['subscription_tier'] === 'founding'}}
								<span class="ipsBadge ipsBadge--warning">Founding</span>
							{{else}}
								<span class="ipsBadge ipsBadge--neutral">Basic</span>
							{{endif}}
						</td>
						<td>
							{{if $d['suspended']}}
								<span class="ipsBadge ipsBadge--negative">Suspended</span>
							{{elseif $d['active']}}
								<span class="ipsBadge ipsBadge--positive">Active</span>
							{{else}}
								<span class="ipsBadge ipsBadge--neutral">Inactive</span>
							{{endif}}
						</td>
						<td>{expression="number_format( $d['listing_count'] )"}</td>
						<td>
							{{if $d['last_run']}}
								{$d['last_run']}
								{{if $d['last_run_status'] === 'failed'}}
									<br><span class="ipsBadge ipsBadge--negative">Failed</span>
								{{endif}}
							{{else}}
								&mdash;
							{{endif}}
						</td>
						<td>{$d['mrr']}</td>
						<td>
							<a href="{$d['view_url']}" class="ipsButton ipsButton--primary ipsButton--small">View</a>
							<a href="{$d['edit_url']}" class="ipsButton ipsButton--normal ipsButton--small">Edit</a>
							<a href="{$d['import_url']}" class="ipsButton ipsButton--normal ipsButton--small">Import</a>
							{{if $d['suspended']}}
								<a href="{$d['suspend_url']}" class="ipsButton ipsButton--primary ipsButton--small">Unsuspend</a>
							{{else}}
								<a href="{$d['suspend_url']}" class="ipsButton ipsButton--negative ipsButton--small">Suspend</a>
							{{endif}}
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

	/* ===== ADMIN: dealerDetail ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'admin',
		'group'         => 'dealers',
		'template_name' => 'dealerDetail',
		'template_data' => '$dealer, $logs, $listings, $backUrl, $editUrl, $importUrl, $suspendUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding:10px 16px;border-bottom:1px solid var(--i-border-color, #e0e0e0)">
		<a href="{$backUrl}" class="ipsButton ipsButton--normal ipsButton--small">&larr; Back to dealer list</a>
		<div style="display:flex;gap:8px">
			<a href="{$editUrl}" class="ipsButton ipsButton--primary ipsButton--small">Edit Feed Config</a>
			<a href="{$importUrl}" class="ipsButton ipsButton--normal ipsButton--small">Force Import Now</a>
			{{if $dealer['suspended']}}
				<a href="{$suspendUrl}" class="ipsButton ipsButton--primary ipsButton--small">Unsuspend Dealer</a>
			{{else}}
				<a href="{$suspendUrl}" class="ipsButton ipsButton--negative ipsButton--small">Suspend Dealer</a>
			{{endif}}
		</div>
	</div>
	<div class="ipsBox_body ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 200px;padding:16px">
				<div style="color:#666;font-size:0.9em">Subscription Tier</div>
				<div style="font-weight:bold;margin-top:4px">{$dealer['subscription_tier']}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px">
				<div style="color:#666;font-size:0.9em">MRR Contribution</div>
				<div style="font-weight:bold;margin-top:4px">{$dealer['mrr']}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px">
				<div style="color:#666;font-size:0.9em">Feed Format</div>
				<div style="font-weight:bold;margin-top:4px">{$dealer['feed_format']}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px">
				<div style="color:#666;font-size:0.9em">Schedule</div>
				<div style="font-weight:bold;margin-top:4px">{$dealer['import_schedule']}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px">
				<div style="color:#666;font-size:0.9em">Last Record Count</div>
				<div style="font-weight:bold;margin-top:4px">{expression="number_format( $dealer['last_record_count'] )"}</div>
			</div>
		</div>

		<p><strong>Feed URL:</strong> <code>{$dealer['feed_url']}</code></p>
		<p><strong>API Key:</strong> <code>{$dealer['api_key']}</code></p>

		<h2 class="ipsType_sectionHead" style="margin:24px 0 12px">Recent Import Log</h2>
		{{if count( $logs ) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gddealer_detail_logs_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%;margin-bottom:24px">
				<thead>
					<tr>
						<th>Started</th>
						<th>Status</th>
						<th>Total</th>
						<th>New</th>
						<th>Updated</th>
						<th>Unchanged</th>
						<th>Unmatched</th>
						<th>Drops</th>
					</tr>
				</thead>
				<tbody>
					{{foreach $logs as $l}}
					<tr>
						<td>{$l['run_start']}</td>
						<td>
							{{if $l['status'] === 'completed'}}
								<span class="ipsBadge ipsBadge--positive">OK</span>
							{{elseif $l['status'] === 'failed'}}
								<span class="ipsBadge ipsBadge--negative">Failed</span>
							{{else}}
								<span class="ipsBadge ipsBadge--warning">{$l['status']}</span>
							{{endif}}
						</td>
						<td>{$l['records_total']}</td>
						<td>{$l['records_created']}</td>
						<td>{$l['records_updated']}</td>
						<td>{$l['records_unchanged']}</td>
						<td>{$l['records_unmatched']}</td>
						<td>{$l['price_drops']}</td>
					</tr>
					{{if $l['error_log']}}
					<tr><td colspan="8"><pre style="white-space:pre-wrap;color:#c00;margin:0">{$l['error_log']}</pre></td></tr>
					{{endif}}
					{{endforeach}}
				</tbody>
			</table>
		{{endif}}

		<h2 class="ipsType_sectionHead" style="margin:24px 0 12px">Recent Listings</h2>
		{{if count( $listings ) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gddealer_detail_listings_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead>
					<tr><th>UPC</th><th>Price</th><th>Stock</th><th>Status</th><th>Last Updated</th></tr>
				</thead>
				<tbody>
					{{foreach $listings as $l}}
					<tr>
						<td><code>{$l['upc']}</code></td>
						<td>{$l['dealer_price']}</td>
						<td>
							{{if $l['in_stock']}}
								<span class="ipsBadge ipsBadge--positive">In Stock</span>
							{{else}}
								<span class="ipsBadge ipsBadge--neutral">Out</span>
							{{endif}}
						</td>
						<td>{$l['listing_status']}</td>
						<td>{$l['last_updated']}</td>
					</tr>
					{{endforeach}}
				</tbody>
			</table>
		{{endif}}

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: mrrDashboard ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'admin',
		'group'         => 'dealers',
		'template_name' => 'mrrDashboard',
		'template_data' => '$totalMrr, $tierRows, $newSignups, $churn',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{$totalMrr}</div>
				<div>{lang="gddealer_mrr_total"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold;color:#2a8a2a">{$newSignups}</div>
				<div>{lang="gddealer_mrr_new_signups"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold;color:#c00">{$churn}</div>
				<div>{lang="gddealer_mrr_churn"}</div>
			</div>
		</div>

		<h2 class="ipsType_sectionHead" style="margin:0 0 12px">{lang="gddealer_mrr_by_tier"}</h2>
		{{if count( $tierRows ) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gddealer_mrr_tiers_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead>
					<tr><th>Tier</th><th>Dealers</th><th>MRR</th></tr>
				</thead>
				<tbody>
					{{foreach $tierRows as $r}}
					<tr>
						<td><strong>{$r['label']}</strong></td>
						<td>{$r['count']}</td>
						<td>{$r['mrr']}</td>
					</tr>
					{{endforeach}}
				</tbody>
			</table>
		{{endif}}

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: unmatchedList ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'admin',
		'group'         => 'dealers',
		'template_name' => 'unmatchedList',
		'template_data' => '$rows, $total, $pagination',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">

		<p>{expression="number_format( $total )"} unmatched UPCs across all dealer feeds.</p>

		{{if count( $rows ) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gddealer_unmatched_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead>
					<tr>
						<th>{lang="gddealer_unmatched_upc"}</th>
						<th>{lang="gddealer_unmatched_dealer"}</th>
						<th>{lang="gddealer_unmatched_first_seen"}</th>
						<th>{lang="gddealer_unmatched_last_seen"}</th>
						<th>{lang="gddealer_unmatched_count"}</th>
						<th style="width:260px"></th>
					</tr>
				</thead>
				<tbody>
					{{foreach $rows as $r}}
					<tr>
						<td><code>{$r['upc']}</code></td>
						<td>{$r['dealer_name']}</td>
						<td>{$r['first_seen']}</td>
						<td>{$r['last_seen']}</td>
						<td>{$r['occurrence_count']}</td>
						<td>
							<a href="{$r['add_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gddealer_unmatched_add_to_catalog"}</a>
							<a href="{$r['exclude_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gddealer_unmatched_exclude"}</a>
						</td>
					</tr>
					{{endforeach}}
				</tbody>
			</table>

			<div style="margin-top:16px">{$pagination}</div>
		{{endif}}

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: dashboard ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'dashboard',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">
		<div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
			<div>
				<h1 class="ipsType_pageTitle" style="margin:0 0 4px 0">{$data['dealer_name']}</h1>
				<div class="ipsType_light">
					{lang="gddealer_front_dash_subtitle"}
				</div>
			</div>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
				<span class="ipsBadge ipsBadge_positive" style="font-size:13px;padding:6px 12px">{$data['tier_label']} {lang="gddealer_front_plan"}</span>
				{{if $data['suspended']}}
					<span class="ipsBadge ipsBadge_negative" style="font-size:13px;padding:6px 12px">{lang="gddealer_front_suspended"}</span>
				{{elseif $data['active']}}
					<span class="ipsBadge ipsBadge_positive" style="font-size:13px;padding:6px 12px">{lang="gddealer_front_active"}</span>
				{{else}}
					<span class="ipsBadge ipsBadge_warning" style="font-size:13px;padding:6px 12px">{lang="gddealer_front_inactive"}</span>
				{{endif}}
			</div>
		</div>
	</div>
</div>

<div class="ipsGrid ipsGrid_collapsePhone ipsSpacer_top">
	<div class="ipsGrid_span3">
		<div class="ipsBox ipsPull">
			<div class="ipsBox_body ipsPad" style="text-align:center">
				<div class="ipsType_light ipsType_small" style="text-transform:uppercase;letter-spacing:0.5px">{lang="gddealer_front_total_listings"}</div>
				<div style="font-size:36px;font-weight:700;line-height:1.2;margin-top:6px">{$data['total_listings']}</div>
				<div class="ipsType_light ipsType_small">
					{$data['in_stock']} {lang="gddealer_front_in_stock"} · {$data['out_of_stock']} {lang="gddealer_front_oos"}
				</div>
			</div>
		</div>
	</div>
	<div class="ipsGrid_span3">
		<div class="ipsBox ipsPull">
			<div class="ipsBox_body ipsPad" style="text-align:center">
				<div class="ipsType_light ipsType_small" style="text-transform:uppercase;letter-spacing:0.5px">{lang="gddealer_front_clicks_7d"}</div>
				<div style="font-size:36px;font-weight:700;line-height:1.2;margin-top:6px">{$data['clicks_7d']}</div>
				<div class="ipsType_light ipsType_small">{$data['clicks_30d']} {lang="gddealer_front_clicks_30d_suffix"}</div>
			</div>
		</div>
	</div>
	<div class="ipsGrid_span3">
		<div class="ipsBox ipsPull">
			<div class="ipsBox_body ipsPad" style="text-align:center">
				<div class="ipsType_light ipsType_small" style="text-transform:uppercase;letter-spacing:0.5px">{lang="gddealer_front_unmatched"}</div>
				<div style="font-size:36px;font-weight:700;line-height:1.2;margin-top:6px">{$data['unmatched_count']}</div>
				<div class="ipsType_light ipsType_small">{lang="gddealer_front_unmatched_hint"}</div>
			</div>
		</div>
	</div>
	<div class="ipsGrid_span3">
		<div class="ipsBox ipsPull">
			<div class="ipsBox_body ipsPad" style="text-align:center">
				<div class="ipsType_light ipsType_small" style="text-transform:uppercase;letter-spacing:0.5px">{lang="gddealer_front_last_import"}</div>
				{{if $data['last_run_time']}}
					<div style="font-size:20px;font-weight:700;line-height:1.2;margin-top:6px">{$data['last_run_time']}</div>
					<div class="ipsType_light ipsType_small">
						{$data['last_run_status']} · {$data['last_record_count']} {lang="gddealer_front_records"}
					</div>
				{{else}}
					<div style="font-size:20px;font-weight:700;line-height:1.2;margin-top:6px">—</div>
					<div class="ipsType_light ipsType_small">{lang="gddealer_front_no_imports"}</div>
				{{endif}}
			</div>
		</div>
	</div>
</div>

<div class="ipsBox ipsPull ipsSpacer_top">
	<div class="ipsBox_body ipsPad">
		<h2 class="ipsType_sectionHead" style="margin-top:0">{lang="gddealer_front_import_history"}</h2>
		{{if count($data['recent_imports']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gddealer_front_no_imports_body"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead>
					<tr>
						<th>{lang="gddealer_front_col_run"}</th>
						<th>{lang="gddealer_front_col_status"}</th>
						<th style="text-align:right">{lang="gddealer_front_col_total"}</th>
						<th style="text-align:right">{lang="gddealer_front_col_created"}</th>
						<th style="text-align:right">{lang="gddealer_front_col_updated"}</th>
						<th style="text-align:right">{lang="gddealer_front_col_unmatched"}</th>
					</tr>
				</thead>
				<tbody>
				{{foreach $data['recent_imports'] as $run}}
					<tr>
						<td>{$run['run_start']}</td>
						<td>
							{{if $run['status'] === 'success'}}
								<span class="ipsBadge ipsBadge_positive">{$run['status']}</span>
							{{elseif $run['status'] === 'failed'}}
								<span class="ipsBadge ipsBadge_negative">{$run['status']}</span>
							{{else}}
								<span class="ipsBadge ipsBadge_neutral">{$run['status']}</span>
							{{endif}}
						</td>
						<td style="text-align:right">{$run['records_total']}</td>
						<td style="text-align:right">{$run['records_created']}</td>
						<td style="text-align:right">{$run['records_updated']}</td>
						<td style="text-align:right">{$run['records_unmatched']}</td>
					</tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>

{{if $data['analytics_enabled']}}
<div class="ipsBox ipsPull ipsSpacer_top">
	<div class="ipsBox_body ipsPad">
		<h2 class="ipsType_sectionHead" style="margin-top:0">{lang="gddealer_front_analytics"}</h2>
		<p class="ipsType_light">{lang="gddealer_front_analytics_body"}</p>
	</div>
</div>
{{else}}
<div class="ipsBox ipsPull ipsSpacer_top">
	<div class="ipsBox_body ipsPad">
		<h2 class="ipsType_sectionHead" style="margin-top:0">{lang="gddealer_front_upgrade_title"}</h2>
		<p>{lang="gddealer_front_upgrade_body"}</p>
	</div>
</div>
{{endif}}
TEMPLATE_EOT,
	],

	/* ===== FRONT: noAccess ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'noAccess',
		'template_data' => '',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad" style="text-align:center;padding:48px 24px">
		<h1 class="ipsType_pageTitle" style="margin:0 0 12px 0">{lang="gddealer_front_noaccess_title"}</h1>
		<p class="ipsType_light" style="max-width:560px;margin:0 auto 24px auto">{lang="gddealer_front_noaccess_body"}</p>
		<a href="/" class="ipsButton ipsButton--primary">{lang="gddealer_front_noaccess_home"}</a>
	</div>
</div>
TEMPLATE_EOT,
	],
];

foreach ( $gddealerTemplates as $tpl )
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
