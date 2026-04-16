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
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gddealer_dash_title"}</h1>
	<div class="ipsPad">

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

		<h2>Subscription Tier Breakdown</h2>
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

		<h2>{lang="gddealer_dash_last_run"}</h2>
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

		<div style="margin-top:24px">
			<a href="{$dealersUrl}" class="ipsButton ipsButton--primary">Manage Dealers</a>
			<a href="{$mrrUrl}" class="ipsButton ipsButton--normal">MRR Dashboard</a>
			<a href="{$unmatchedUrl}" class="ipsButton ipsButton--normal">Unmatched UPCs</a>
		</div>

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
		'template_data' => '$dealers, $onboardUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gddealer_dealers_title"}</h1>
	<div class="ipsPad">

		<div style="margin-bottom:16px">
			<a href="{$onboardUrl}" class="ipsButton ipsButton--primary">{lang="gddealer_onboard_button"}</a>
		</div>

		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th>{lang="gddealer_dealer_name"}</th>
					<th>{lang="gddealer_dealer_tier"}</th>
					<th>{lang="gddealer_dealer_status"}</th>
					<th>{lang="gddealer_dealer_listing_count"}</th>
					<th>{lang="gddealer_dealer_last_import"}</th>
					<th>{lang="gddealer_dealer_mrr"}</th>
					<th>{lang="gddealer_dealer_actions"}</th>
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
						<a href="{$d['view_url']}" class="ipsButton ipsButton--small ipsButton--primary">View</a>
						<a href="{$d['edit_url']}" class="ipsButton ipsButton--small ipsButton--normal">Edit</a>
						<a href="{$d['import_url']}" class="ipsButton ipsButton--small ipsButton--normal">Import</a>
						{{if $d['suspended']}}
							<a href="{$d['suspend_url']}" class="ipsButton ipsButton--small ipsButton--positive">Unsuspend</a>
						{{else}}
							<a href="{$d['suspend_url']}" class="ipsButton ipsButton--small ipsButton--negative">Suspend</a>
						{{endif}}
					</td>
				</tr>
				{{endforeach}}
				{{if count( $dealers ) === 0}}
				<tr><td colspan="7" style="text-align:center;color:#999;padding:24px">No dealers yet. Dealers are created on first IPS Commerce subscription purchase.</td></tr>
				{{endif}}
			</tbody>
		</table>

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
<div class="ipsBox">
	<h1 class="ipsBox_title">{$dealer['dealer_name']}</h1>
	<div class="ipsPad">

		<p><a href="{$backUrl}">&larr; Back to dealer list</a></p>

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

		<div style="margin:16px 0">
			<a href="{$editUrl}" class="ipsButton ipsButton--primary">Edit Feed Config</a>
			<a href="{$importUrl}" class="ipsButton ipsButton--normal">Force Import Now</a>
			{{if $dealer['suspended']}}
				<a href="{$suspendUrl}" class="ipsButton ipsButton--positive">Unsuspend Dealer</a>
			{{else}}
				<a href="{$suspendUrl}" class="ipsButton ipsButton--negative">Suspend Dealer</a>
			{{endif}}
		</div>

		<h2>Recent Import Log</h2>
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
				{{if count( $logs ) === 0}}
				<tr><td colspan="8" style="text-align:center;color:#999;padding:24px">No imports have run for this dealer yet.</td></tr>
				{{endif}}
			</tbody>
		</table>

		<h2>Recent Listings</h2>
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
				{{if count( $listings ) === 0}}
				<tr><td colspan="5" style="text-align:center;color:#999;padding:24px">No listings.</td></tr>
				{{endif}}
			</tbody>
		</table>

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
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gddealer_mrr_title"}</h1>
	<div class="ipsPad">

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

		<h2>{lang="gddealer_mrr_by_tier"}</h2>
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
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gddealer_unmatched_title"}</h1>
	<div class="ipsPad">

		<p>{expression="number_format( $total )"} unmatched UPCs across all dealer feeds.</p>

		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th>{lang="gddealer_unmatched_upc"}</th>
					<th>{lang="gddealer_unmatched_dealer"}</th>
					<th>{lang="gddealer_unmatched_first_seen"}</th>
					<th>{lang="gddealer_unmatched_last_seen"}</th>
					<th>{lang="gddealer_unmatched_count"}</th>
					<th></th>
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
						<a href="{$r['add_url']}" class="ipsButton ipsButton--small ipsButton--positive">{lang="gddealer_unmatched_add_to_catalog"}</a>
						<a href="{$r['exclude_url']}" class="ipsButton ipsButton--small ipsButton--neutral">{lang="gddealer_unmatched_exclude"}</a>
					</td>
				</tr>
				{{endforeach}}
				{{if count( $rows ) === 0}}
				<tr><td colspan="6" style="text-align:center;color:#999;padding:24px">No unmatched UPCs.</td></tr>
				{{endif}}
			</tbody>
		</table>

		<div style="margin-top:16px">{$pagination}</div>

	</div>
</div>
TEMPLATE_EOT,
	],
	/* ===== FRONT: notSubscribed ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'notSubscribed',
		'template_data' => '$joinUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gddealer_frontend_dashboard_title"}</h1>
	<div class="ipsPad">
		<p>{lang="gddealer_frontend_not_subscribed"}</p>
		<p style="margin-top:16px">
			<a href="{$joinUrl}" class="ipsButton ipsButton--primary">{lang="gddealer_front_join_cta"}</a>
		</p>
	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: dealerShell (tab wrapper) ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'dealerShell',
		'template_data' => '$dealer, $activeTab, $tabUrls, $body',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<div class="ipsPad">
		<h1 class="ipsType_pageTitle">{$dealer['dealer_name']}</h1>
		<p style="color:#666;margin-top:-4px">
			{lang="gddealer_front_dashboard_welcome"} &mdash;
			<strong>{$dealer['tier_label']}</strong> plan
		</p>

		{{if $dealer['suspended']}}
			<div class="ipsMessage ipsMessage_error" style="margin:16px 0">
				{lang="gddealer_front_suspended_banner"}
			</div>
		{{endif}}

		{{if $dealer['onboarding_incomplete']}}
			<div class="ipsMessage ipsMessage_warning" style="margin:16px 0">
				<p>{lang="gddealer_front_onboarding_incomplete"}</p>
				<p style="margin-top:8px">
					<a href="{$tabUrls['feedSettings']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gddealer_front_onboarding_go_settings"}</a>
				</p>
			</div>
		{{endif}}

		<ul class="ipsTabs" style="display:flex;gap:4px;list-style:none;padding:0;margin:16px 0;border-bottom:1px solid #ddd;flex-wrap:wrap">
			<li><a href="{$tabUrls['overview']}" class="ipsButton ipsButton--small {expression="$activeTab === 'overview' ? 'ipsButton--primary' : 'ipsButton--normal'"}">{lang="gddealer_front_tab_overview"}</a></li>
			<li><a href="{$tabUrls['feedSettings']}" class="ipsButton ipsButton--small {expression="$activeTab === 'feedSettings' ? 'ipsButton--primary' : 'ipsButton--normal'"}">{lang="gddealer_front_tab_feed"}</a></li>
			<li><a href="{$tabUrls['listings']}" class="ipsButton ipsButton--small {expression="$activeTab === 'listings' ? 'ipsButton--primary' : 'ipsButton--normal'"}">{lang="gddealer_front_tab_listings"}</a></li>
			<li><a href="{$tabUrls['unmatched']}" class="ipsButton ipsButton--small {expression="$activeTab === 'unmatched' ? 'ipsButton--primary' : 'ipsButton--normal'"}">{lang="gddealer_front_tab_unmatched"}</a></li>
			<li><a href="{$tabUrls['analytics']}" class="ipsButton ipsButton--small {expression="$activeTab === 'analytics' ? 'ipsButton--primary' : 'ipsButton--normal'"}">{lang="gddealer_front_tab_analytics"}</a></li>
			<li><a href="{$tabUrls['subscription']}" class="ipsButton ipsButton--small {expression="$activeTab === 'subscription' ? 'ipsButton--primary' : 'ipsButton--normal'"}">{lang="gddealer_front_tab_subscription"}</a></li>
		</ul>

		<div class="ipsTabs_content" style="margin-top:16px">
			{$body}
		</div>
	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: overview tab body ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'overview',
		'template_data' => '$dealer, $overview, $tabUrls',
		'template_content' => <<<'TEMPLATE_EOT'
<div>

	<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
		<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:bold">{expression="number_format( $overview['active_listings'] )"}</div>
			<div>{lang="gddealer_front_active_listings"}</div>
		</div>
		<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:bold;color:#c00">{expression="number_format( $overview['out_of_stock'] )"}</div>
			<div>{lang="gddealer_front_out_of_stock"}</div>
		</div>
		<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:bold;color:#c96">{expression="number_format( $overview['unmatched_count'] )"}</div>
			<div>{lang="gddealer_front_unmatched_count"}</div>
		</div>
		<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:bold">{expression="number_format( $overview['clicks_7d'] )"}</div>
			<div>{lang="gddealer_front_clicks_7d"}</div>
		</div>
		<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:bold">{expression="number_format( $overview['clicks_30d'] )"}</div>
			<div>{lang="gddealer_front_clicks_30d"}</div>
		</div>
	</div>

	<h2>{lang="gddealer_front_last_import"}</h2>
	<p>
		{{if $overview['last_run_time']}}
			<strong>{$overview['last_run_time']}</strong> &mdash;
			{{if $overview['last_run_status'] === 'completed'}}
				<span class="ipsBadge ipsBadge--positive">Completed</span>
			{{elseif $overview['last_run_status'] === 'failed'}}
				<span class="ipsBadge ipsBadge--negative">Failed</span>
			{{elseif $overview['last_run_status'] === 'running'}}
				<span class="ipsBadge ipsBadge--warning">Running</span>
			{{else}}
				<span class="ipsBadge ipsBadge--neutral">{$overview['last_run_status']}</span>
			{{endif}}
			&mdash; {expression="number_format( $overview['last_run_total'] )"} records processed
			{{if $overview['last_run_errors']}}
				<br><span style="color:#c00">Errors were logged &mdash; see Feed Settings &rarr; Import History.</span>
			{{endif}}
		{{else}}
			<em>{lang="gddealer_front_last_import_none"}</em>
		{{endif}}
	</p>

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: feedSettings tab body ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'feedSettings',
		'template_data' => '$dealer, $form, $logs, $importUrl, $tabUrls',
		'template_content' => <<<'TEMPLATE_EOT'
<div>

	{$form|raw}

	<div style="margin:16px 0">
		<a href="{$importUrl}" class="ipsButton ipsButton--primary">{lang="gddealer_front_run_import"}</a>
	</div>

	<h2>{lang="gddealer_front_import_history"}</h2>
	<table class="ipsTable ipsTable_zebra" style="width:100%">
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
			{{if count( $logs ) === 0}}
			<tr><td colspan="8" style="text-align:center;color:#999;padding:24px">No imports have run yet.</td></tr>
			{{endif}}
		</tbody>
	</table>

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: listings tab body ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'listings',
		'template_data' => '$dealer, $rows, $total, $page, $pages, $baseUrl, $filter, $search, $exportUrl, $tabUrls',
		'template_content' => <<<'TEMPLATE_EOT'
<div>

	<form method="get" action="{$tabUrls['listings']}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-bottom:16px">
		<input type="hidden" name="app" value="gddealer">
		<input type="hidden" name="module" value="dealers">
		<input type="hidden" name="controller" value="dashboard">
		<input type="hidden" name="do" value="listings">
		<div>
			<label style="display:block;font-size:0.85em;color:#666">{lang="gddealer_front_filter"}</label>
			<select name="filter">
				<option value="" {expression="$filter === '' ? 'selected' : ''"}>All</option>
				<option value="active" {expression="$filter === 'active' ? 'selected' : ''"}>Active</option>
				<option value="in_stock" {expression="$filter === 'in_stock' ? 'selected' : ''"}>In Stock</option>
				<option value="out_of_stock" {expression="$filter === 'out_of_stock' ? 'selected' : ''"}>Out of Stock</option>
				<option value="suspended" {expression="$filter === 'suspended' ? 'selected' : ''"}>Suspended</option>
				<option value="discontinued" {expression="$filter === 'discontinued' ? 'selected' : ''"}>Discontinued</option>
			</select>
		</div>
		<div>
			<label style="display:block;font-size:0.85em;color:#666">{lang="gddealer_front_search_upc"}</label>
			<input type="text" name="q" value="{$search}" placeholder="{lang='gddealer_front_search_placeholder'}">
		</div>
		<button type="submit" class="ipsButton ipsButton--primary ipsButton--small">{lang="gddealer_front_apply"}</button>
		<a href="{$exportUrl}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gddealer_front_export_csv"}</a>
	</form>

	<p style="color:#666">{expression="number_format( $total )"} listings total.</p>

	<table class="ipsTable ipsTable_zebra" style="width:100%">
		<thead>
			<tr>
				<th>{lang="gddealer_listing_upc"}</th>
				<th>{lang="gddealer_listing_price"}</th>
				<th>{lang="gddealer_listing_stock"}</th>
				<th>{lang="gddealer_listing_condition"}</th>
				<th>Status</th>
				<th>{lang="gddealer_listing_last_updated"}</th>
			</tr>
		</thead>
		<tbody>
			{{foreach $rows as $r}}
			<tr>
				<td><code>{$r['upc']}</code></td>
				<td>{$r['dealer_price']}</td>
				<td>
					{{if $r['in_stock']}}
						<span class="ipsBadge ipsBadge--positive">In Stock</span>
					{{else}}
						<span class="ipsBadge ipsBadge--neutral">Out</span>
					{{endif}}
				</td>
				<td>{$r['condition']}</td>
				<td>{$r['listing_status']}</td>
				<td>{$r['last_updated']}</td>
			</tr>
			{{endforeach}}
			{{if count( $rows ) === 0}}
			<tr><td colspan="6" style="text-align:center;color:#999;padding:24px">{lang="gddealer_front_listings_empty"}</td></tr>
			{{endif}}
		</tbody>
	</table>

	{{if $pages > 1}}
	<div style="margin-top:16px">
		Page {$page} of {$pages}
	</div>
	{{endif}}

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: unmatched tab body ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'unmatched',
		'template_data' => '$dealer, $rows, $exportUrl, $tabUrls',
		'template_content' => <<<'TEMPLATE_EOT'
<div>

	<p>{lang="gddealer_front_unmatched_intro"}</p>

	<p style="margin:8px 0 16px 0">
		<a href="{$exportUrl}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gddealer_front_export_csv"}</a>
	</p>

	<table class="ipsTable ipsTable_zebra" style="width:100%">
		<thead>
			<tr>
				<th>{lang="gddealer_unmatched_upc"}</th>
				<th>{lang="gddealer_unmatched_first_seen"}</th>
				<th>{lang="gddealer_unmatched_last_seen"}</th>
				<th>{lang="gddealer_unmatched_count"}</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			{{foreach $rows as $r}}
			<tr>
				<td><code>{$r['upc']}</code></td>
				<td>{$r['first_seen']}</td>
				<td>{$r['last_seen']}</td>
				<td>{$r['occurrence_count']}</td>
				<td><a href="{$r['exclude_url']}" class="ipsButton ipsButton--small ipsButton--negative">{lang="gddealer_front_unmatched_exclude"}</a></td>
			</tr>
			{{endforeach}}
			{{if count( $rows ) === 0}}
			<tr><td colspan="5" style="text-align:center;color:#999;padding:24px">{lang="gddealer_front_unmatched_empty"}</td></tr>
			{{endif}}
		</tbody>
	</table>

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: analytics tab body ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'analytics',
		'template_data' => '$dealer, $gated, $topClicked, $tabUrls',
		'template_content' => <<<'TEMPLATE_EOT'
<div>

	{{if $gated}}
		<div class="ipsMessage ipsMessage_info">
			<h2>{lang="gddealer_front_analytics_gated_title"}</h2>
			<p>{lang="gddealer_front_analytics_gated_body"}</p>
			<p style="margin-top:8px">
				<a href="{$tabUrls['subscription']}" class="ipsButton ipsButton--primary">{lang="gddealer_front_subscription_manage"}</a>
			</p>
		</div>
	{{else}}
		<h2>{lang="gddealer_front_analytics_top_clicked"}</h2>
		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th>{lang="gddealer_listing_upc"}</th>
					<th>Price</th>
					<th>Clicks (30d)</th>
					<th>Clicks (7d)</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				{{foreach $topClicked as $r}}
				<tr>
					<td><code>{$r['upc']}</code></td>
					<td>{$r['dealer_price']}</td>
					<td>{$r['clicks_30d']}</td>
					<td>{$r['clicks_7d']}</td>
					<td>{$r['listing_status']}</td>
				</tr>
				{{endforeach}}
				{{if count( $topClicked ) === 0}}
				<tr><td colspan="5" style="text-align:center;color:#999;padding:24px">{lang="gddealer_front_analytics_empty"}</td></tr>
				{{endif}}
			</tbody>
		</table>
	{{endif}}

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: subscription tab body ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'subscription',
		'template_data' => '$dealer, $sub, $tabUrls',
		'template_content' => <<<'TEMPLATE_EOT'
<div>

	<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
		<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
			<div style="color:#666;font-size:0.9em">{lang="gddealer_front_subscription_current"}</div>
			<div style="font-size:1.6em;font-weight:bold;margin-top:4px">{$sub['tier_label']}</div>
		</div>
		<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
			<div style="color:#666;font-size:0.9em">{lang="gddealer_front_subscription_mrr"}</div>
			<div style="font-size:1.6em;font-weight:bold;margin-top:4px">{$sub['mrr']}</div>
		</div>
		<div class="ipsBox" style="flex:1 1 200px;padding:16px;text-align:center">
			<div style="color:#666;font-size:0.9em">{lang="gddealer_front_subscription_status"}</div>
			<div style="font-size:1.2em;font-weight:bold;margin-top:4px">
				{{if $sub['suspended']}}
					<span class="ipsBadge ipsBadge--negative">Suspended</span>
				{{elseif $sub['active']}}
					<span class="ipsBadge ipsBadge--positive">Active</span>
				{{else}}
					<span class="ipsBadge ipsBadge--neutral">Pending Setup</span>
				{{endif}}
			</div>
		</div>
	</div>

	<p>{lang="gddealer_front_subscription_note"}</p>

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: join (landing) ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'join',
		'template_data' => '$tiers, $requestUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<div class="ipsPad">

		<h1 class="ipsType_pageTitle">{lang="gddealer_front_join_title"}</h1>
		<p style="max-width:720px">{lang="gddealer_front_join_intro"}</p>

		<div style="display:flex;gap:16px;margin:24px 0;flex-wrap:wrap">
			{{foreach $tiers as $t}}
			<div class="ipsBox" style="flex:1 1 260px;padding:20px">
				<h2 style="margin:0">{$t['label']}</h2>
				<div style="font-size:1.6em;font-weight:bold;margin:8px 0">{$t['price']}</div>
				<div style="color:#666;margin-bottom:12px">{lang="gddealer_front_join_tier_schedule"} {$t['schedule']}</div>
				<ul style="padding-left:20px;margin:0">
					{{foreach $t['features'] as $f}}
					<li>{$f}</li>
					{{endforeach}}
				</ul>
			</div>
			{{endforeach}}
		</div>

		<p style="margin-top:16px">
			<a href="{$requestUrl}" class="ipsButton ipsButton--primary">{lang="gddealer_front_join_cta"}</a>
		</p>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: joinRequest (contact form) ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'joinRequest',
		'template_data' => '$form',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<div class="ipsPad">
		<h1 class="ipsType_pageTitle">{lang="gddealer_front_request_title"}</h1>
		<p style="max-width:720px">{lang="gddealer_front_request_intro"}</p>
		{$form|raw}
	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: joinThanks ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'joinThanks',
		'template_data' => '',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<div class="ipsPad">
		<h1 class="ipsType_pageTitle">{lang="gddealer_front_thanks_title"}</h1>
		<p>{lang="gddealer_front_thanks_body"}</p>
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
