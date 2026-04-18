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
	<div class="ipsBox_body ipsPad">

		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;display:flex;flex-wrap:wrap;margin-bottom:24px">
			<div style="flex:1 1 150px;padding:20px;text-align:center;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">{lang="gddealer_dash_total_dealers"}</div>
				<div style="font-size:2em;font-weight:700">{$totalDealers}</div>
			</div>
			<div style="flex:1 1 150px;padding:20px;text-align:center;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">{lang="gddealer_dash_active_dealers"}</div>
				<div style="font-size:2em;font-weight:700;color:#2a8a2a">{$activeDealers}</div>
			</div>
			<div style="flex:1 1 150px;padding:20px;text-align:center;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">{lang="gddealer_dash_suspended_dealers"}</div>
				<div style="font-size:2em;font-weight:700;color:#c00">{$suspendedDealers}</div>
			</div>
			<div style="flex:1 1 150px;padding:20px;text-align:center;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">{lang="gddealer_dash_total_listings"}</div>
				<div style="font-size:2em;font-weight:700">{expression="number_format( $totalListings )"}</div>
			</div>
			<div style="flex:1 1 150px;padding:20px;text-align:center;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">{lang="gddealer_dash_in_stock_listings"}</div>
				<div style="font-size:2em;font-weight:700">{expression="number_format( $inStockListings )"}</div>
			</div>
			<div style="flex:1 1 150px;padding:20px;text-align:center">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">{lang="gddealer_dash_unmatched_total"}</div>
				<div style="font-size:2em;font-weight:700">{expression="number_format( $unmatchedTotal )"}</div>
			</div>
		</div>

		<div style="padding:12px 20px;font-weight:700;font-size:0.9em;text-transform:uppercase;letter-spacing:0.05em;color:#666;border-bottom:1px solid var(--i-border-color,#e0e0e0)">Subscription Tier Breakdown</div>
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

		<div style="padding:12px 20px;font-weight:700;font-size:0.9em;text-transform:uppercase;letter-spacing:0.05em;color:#666;border-bottom:1px solid var(--i-border-color,#e0e0e0)">{lang="gddealer_dash_last_run"}</div>
		<div style="padding:12px 20px">
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
		</div>

		<div style="padding:12px 20px;border-top:1px solid var(--i-border-color,#e0e0e0);display:flex;gap:8px">
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
<div class="ipsBox ipsPull">
	<div class="ipsBox_body">

		<div style="padding:12px 20px;border-bottom:1px solid var(--i-border-color,#e0e0e0);display:flex;gap:8px">
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
						{{if $d['disputes_suspended']}}
							<span class="ipsBadge ipsBadge--warning" style="margin-left:4px">Disputes Off</span>
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
						{{if $d['profile_url']}}
							<a href="{$d['profile_url']}" target="_blank" class="ipsButton ipsButton--small ipsButton--normal">Profile</a>
						{{endif}}
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
		'template_data' => '$dealer, $logs, $listings, $backUrl, $editUrl, $importUrl, $suspendUrl, $invoiceUrl, $disputeSuspendUrl, $reviews',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body">

		<div style="padding:16px 20px">
			<a href="{$backUrl}" style="color:#666;text-decoration:none">&larr; Back to dealer list</a>
		</div>

		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;display:flex;flex-wrap:wrap;margin:0 20px 16px">
			<div style="flex:1 1 180px;padding:16px 20px;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Subscription Tier</div>
				<div style="font-weight:700;font-size:1.05em">{$dealer['subscription_tier']}</div>
			</div>
			<div style="flex:1 1 180px;padding:16px 20px;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">MRR Contribution</div>
				<div style="font-weight:700;font-size:1.05em">{$dealer['mrr']}</div>
			</div>
			<div style="flex:1 1 180px;padding:16px 20px;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Feed Format</div>
				<div style="font-weight:700;font-size:1.05em">{$dealer['feed_format']}</div>
			</div>
			<div style="flex:1 1 180px;padding:16px 20px;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Schedule</div>
				<div style="font-weight:700;font-size:1.05em">{$dealer['import_schedule']}</div>
			</div>
			<div style="flex:1 1 180px;padding:16px 20px">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Last Record Count</div>
				<div style="font-weight:700;font-size:1.05em">{expression="number_format( $dealer['last_record_count'] )"}</div>
			</div>
		</div>

		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin:0 20px 16px">
			<div style="padding:16px 20px;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Feed URL</div>
				<div style="font-weight:700;font-size:1.05em"><code>{$dealer['feed_url']}</code></div>
			</div>
			<div style="padding:16px 20px;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">API Key</div>
				<div style="font-family:monospace;font-size:0.85em;background:#f4f4f4;padding:6px 10px;border-radius:4px;word-break:break-all">{$dealer['api_key']}</div>
			</div>
			{{if $dealer['profile_url']}}
			<div style="padding:16px 20px;border-top:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Public Profile URL</div>
				<div style="display:flex;gap:8px;align-items:center">
					<code style="font-size:0.85em;background:#f4f4f4;padding:6px 10px;border-radius:4px;flex:1;word-break:break-all">{$dealer['profile_url']}</code>
					<a href="{$dealer['profile_url']}" target="_blank" class="ipsButton ipsButton--normal ipsButton--small">View</a>
				</div>
			</div>
			{{endif}}
			{{if $dealer['trial_expires_at']}}
			<div style="padding:16px 20px;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Trial Expires</div>
				<div style="font-weight:700;font-size:1.05em">
					{$dealer['trial_expires_at']}
					{{if $dealer['trial_expires_soon']}}
						<span class="ipsBadge ipsBadge--warning" style="margin-left:8px">Expires within 30 days</span>
					{{endif}}
				</div>
			</div>
			{{endif}}
			{{if $dealer['billing_note']}}
			<div style="padding:16px 20px">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Billing Notes</div>
				<div style="font-size:0.95em;white-space:pre-wrap">{$dealer['billing_note']}</div>
			</div>
			{{endif}}
		</div>

		{{if $dealer['disputes_suspended']}}
		<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:10px 16px;margin:0 20px 12px;display:flex;align-items:center;gap:8px">
			<span class="ipsBadge ipsBadge--negative">DISPUTES SUSPENDED</span>
			<span style="font-size:0.85em;color:#991b1b">This dealer cannot file new review contests until an admin lifts the suspension.</span>
		</div>
		{{endif}}

		<div style="padding:12px 20px;border-top:1px solid var(--i-border-color,#e0e0e0);border-bottom:1px solid var(--i-border-color,#e0e0e0);display:flex;gap:8px;flex-wrap:wrap">
			<a href="{$editUrl}" class="ipsButton ipsButton--primary ipsButton--small">Edit Feed Config</a>
			<a href="{$importUrl}" class="ipsButton ipsButton--normal ipsButton--small">Force Import Now</a>
			<a href="{$invoiceUrl}" class="ipsButton ipsButton--normal ipsButton--small">View in Commerce</a>
			<a href="{$suspendUrl}" class="ipsButton ipsButton--negative ipsButton--small">{{if $dealer['suspended']}}Unsuspend Dealer{{else}}Suspend Dealer{{endif}}</a>
			<a href="{$disputeSuspendUrl}" class="ipsButton ipsButton--small {{if $dealer['disputes_suspended']}}ipsButton--positive{{else}}ipsButton--warning{{endif}}">{{if $dealer['disputes_suspended']}}Unsuspend Disputes{{else}}Suspend Disputes{{endif}}</a>
		</div>

		<div style="padding:12px 20px;font-weight:700;font-size:0.9em;text-transform:uppercase;letter-spacing:0.05em;color:#666;border-bottom:1px solid var(--i-border-color,#e0e0e0)">Recent Import Log</div>
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
				<tr><td colspan="8" style="text-align:center;color:#999;padding:24px">No imports have run for this dealer yet.</td></tr>
				{{endif}}
			</tbody>
		</table>

		<div style="padding:12px 20px;font-weight:700;font-size:0.9em;text-transform:uppercase;letter-spacing:0.05em;color:#666;border-bottom:1px solid var(--i-border-color,#e0e0e0)">Recent Listings</div>
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

		<div style="padding:12px 20px;font-weight:700;font-size:0.9em;text-transform:uppercase;letter-spacing:0.05em;color:#666;border-bottom:1px solid var(--i-border-color,#e0e0e0);border-top:1px solid var(--i-border-color,#e0e0e0)">Recent Reviews</div>
		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr><th>Reviewer</th><th>Pricing</th><th>Shipping</th><th>Service</th><th>Status</th><th>Posted</th><th style="text-align:right">Actions</th></tr>
			</thead>
			<tbody>
				{{foreach $reviews as $r}}
				<tr>
					<td><strong>{$r['member_name']}</strong></td>
					<td>{$r['rating_pricing']} / 5</td>
					<td>{$r['rating_shipping']} / 5</td>
					<td>{$r['rating_service']} / 5</td>
					<td>
						{{if $r['dispute_status'] === 'none'}}
							<span class="ipsBadge ipsBadge--neutral">Live</span>
						{{elseif $r['dispute_status'] === 'pending_customer'}}
							<span class="ipsBadge ipsBadge--warning">Pending Customer</span>
						{{elseif $r['dispute_status'] === 'pending_admin'}}
							<span class="ipsBadge ipsBadge--warning">Pending Admin</span>
						{{elseif $r['dispute_status'] === 'resolved_dealer'}}
							<span class="ipsBadge ipsBadge--positive">Upheld</span>
						{{elseif $r['dispute_status'] === 'dismissed'}}
							<span class="ipsBadge ipsBadge--neutral">Dismissed</span>
						{{else}}
							<span class="ipsBadge ipsBadge--neutral">{$r['dispute_status']}</span>
						{{endif}}
					</td>
					<td>{$r['created_at']}</td>
					<td style="text-align:right">
						<a href="{$r['delete_url']}" class="ipsButton ipsButton--negative ipsButton--tiny"
						   data-confirm data-confirmMessage="Are you sure you want to delete this review? This cannot be undone.">
							<i class="fa-solid fa-trash" aria-hidden="true"></i>
						</a>
					</td>
				</tr>
				{{if $r['review_body']}}
				<tr><td colspan="7" style="color:#555;background:#fafafa"><em>{$r['review_body']}</em></td></tr>
				{{endif}}
				{{endforeach}}
				{{if count( $reviews ) === 0}}
				<tr><td colspan="7" style="text-align:center;color:#999;padding:24px">No reviews yet.</td></tr>
				{{endif}}
			</tbody>
		</table>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: allReviews ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'admin',
		'group'         => 'dealers',
		'template_name' => 'allReviews',
		'template_data' => '$rows, $dealerOptions, $filterStatus, $filterDealer, $formUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body">

		<div style="padding:16px 20px;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
			<form method="get" action="{$formUrl}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
				<input type="hidden" name="app" value="gddealer">
				<input type="hidden" name="module" value="dealers">
				<input type="hidden" name="controller" value="dealers">
				<input type="hidden" name="do" value="reviews">
				<div>
					<label style="display:block;font-size:0.8em;color:#666;margin-bottom:4px">Dealer</label>
					<select name="dealer_id">
						{{foreach $dealerOptions as $did => $dname}}
							<option value="{$did}" {{if $did === $filterDealer}}selected{{endif}}>{$dname}</option>
						{{endforeach}}
					</select>
				</div>
				<div>
					<label style="display:block;font-size:0.8em;color:#666;margin-bottom:4px">Status</label>
					<select name="status">
						<option value="" {{if $filterStatus === ''}}selected{{endif}}>All statuses</option>
						<option value="none" {{if $filterStatus === 'none'}}selected{{endif}}>Live</option>
						<option value="pending_customer" {{if $filterStatus === 'pending_customer'}}selected{{endif}}>Pending Customer</option>
						<option value="pending_admin" {{if $filterStatus === 'pending_admin'}}selected{{endif}}>Pending Admin</option>
						<option value="resolved_dealer" {{if $filterStatus === 'resolved_dealer'}}selected{{endif}}>Upheld</option>
						<option value="dismissed" {{if $filterStatus === 'dismissed'}}selected{{endif}}>Dismissed</option>
					</select>
				</div>
				<button type="submit" class="ipsButton ipsButton--primary ipsButton--small">Filter</button>
			</form>
		</div>

		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th>Dealer</th>
					<th>Reviewer</th>
					<th>Pricing</th>
					<th>Shipping</th>
					<th>Service</th>
					<th>Status</th>
					<th>Posted</th>
					<th style="text-align:right">Actions</th>
				</tr>
			</thead>
			<tbody>
				{{foreach $rows as $r}}
				<tr>
					<td><strong>{$r['dealer_name']}</strong></td>
					<td>{$r['member_name']}</td>
					<td>{$r['rating_pricing']} / 5</td>
					<td>{$r['rating_shipping']} / 5</td>
					<td>{$r['rating_service']} / 5</td>
					<td>
						{{if $r['dispute_status'] === 'none'}}
							<span class="ipsBadge ipsBadge--neutral">Live</span>
						{{elseif $r['dispute_status'] === 'pending_customer'}}
							<span class="ipsBadge ipsBadge--warning">Pending Customer</span>
						{{elseif $r['dispute_status'] === 'pending_admin'}}
							<span class="ipsBadge ipsBadge--warning">Pending Admin</span>
						{{elseif $r['dispute_status'] === 'resolved_dealer'}}
							<span class="ipsBadge ipsBadge--positive">Upheld</span>
						{{elseif $r['dispute_status'] === 'dismissed'}}
							<span class="ipsBadge ipsBadge--neutral">Dismissed</span>
						{{else}}
							<span class="ipsBadge ipsBadge--neutral">{$r['dispute_status']}</span>
						{{endif}}
					</td>
					<td>{$r['created_at']}</td>
					<td style="text-align:right">
						<a href="{$r['delete_url']}" class="ipsButton ipsButton--negative ipsButton--tiny"
						   data-confirm data-confirmMessage="Are you sure you want to delete this review? This cannot be undone.">
							<i class="fa-solid fa-trash" aria-hidden="true"></i>
						</a>
					</td>
				</tr>
				{{if $r['review_body']}}
				<tr><td colspan="8" style="color:#555;background:#fafafa"><em>{$r['review_body']}</em></td></tr>
				{{endif}}
				{{endforeach}}
				{{if count( $rows ) === 0}}
				<tr><td colspan="8" style="text-align:center;color:#999;padding:24px">No reviews match the current filter.</td></tr>
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
<div class="ipsBox ipsPull">
	<div class="ipsBox_body ipsPad">

		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;display:flex;flex-wrap:wrap;margin-bottom:24px">
			<div style="flex:1 1 200px;padding:20px;text-align:center;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">{lang="gddealer_mrr_total"}</div>
				<div style="font-size:2em;font-weight:700">{$totalMrr}</div>
			</div>
			<div style="flex:1 1 200px;padding:20px;text-align:center;border-right:1px solid var(--i-border-color,#e0e0e0)">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">{lang="gddealer_mrr_new_signups"}</div>
				<div style="font-size:2em;font-weight:700;color:#2a8a2a">{$newSignups}</div>
			</div>
			<div style="flex:1 1 200px;padding:20px;text-align:center">
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">{lang="gddealer_mrr_churn"}</div>
				<div style="font-size:2em;font-weight:700;color:#c00">{$churn}</div>
			</div>
		</div>

		<div style="padding:12px 20px;font-weight:700;font-size:0.9em;text-transform:uppercase;letter-spacing:0.05em;color:#666;border-bottom:1px solid var(--i-border-color,#e0e0e0)">{lang="gddealer_mrr_by_tier"}</div>
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
<div class="ipsBox ipsPull">
	<div class="ipsBox_body">

		<div style="padding:12px 20px;color:#666;font-size:0.9em">{expression="number_format( $total )"} unmatched UPCs across all dealer feeds.</div>

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

		<div style="padding:12px 20px">{$pagination|raw}</div>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: disputeQueue ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'admin',
		'group'         => 'dealers',
		'template_name' => 'disputeQueue',
		'template_data' => '$rows',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h2 class="ipsBox_title">{lang="gddealer_disputes_title"}</h2>
	<div style="padding:16px">

		{{if count($rows) === 0}}
			<div class="ipsEmptyMessage"><p>No disputed reviews pending resolution.</p></div>
		{{else}}
			{{foreach $rows as $r}}
			<div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;margin-bottom:16px">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;flex-wrap:wrap;gap:8px">
					<div>
						<strong>{$r['dealer_name']}</strong>
						<span style="color:#999;font-size:0.85em">(dealer #{$r['dealer_id']})</span>
						<span style="color:#666;font-size:0.85em;margin-left:8px">Reviewer: {$r['member_name']}</span>
					</div>
					<div style="font-size:0.8em;color:#999;text-align:right">
						Review: {$r['created_at']}<br>
						{{if $r['dispute_status'] === 'pending_customer'}}
							<span class="ipsBadge ipsBadge--warning">Awaiting customer &mdash; {$r['days_remaining']} days left (deadline {$r['dispute_deadline']})</span>
						{{elseif $r['dispute_status'] === 'pending_admin'}}
							<span class="ipsBadge ipsBadge--style1">Awaiting admin decision (disputed {$r['dispute_at']})</span>
						{{endif}}
					</div>
				</div>

				<div style="display:flex;gap:16px;margin-bottom:8px;flex-wrap:wrap">
					<span style="font-size:0.8em;color:#666">Pricing: <strong>{$r['rating_pricing']}/5</strong></span>
					<span style="font-size:0.8em;color:#666">Shipping: <strong>{$r['rating_shipping']}/5</strong></span>
					<span style="font-size:0.8em;color:#666">Service: <strong>{$r['rating_service']}/5</strong></span>
				</div>

				{{if $r['review_body']}}
				<div style="background:#f9fafb;border:1px solid #e5e7eb;padding:12px;border-radius:4px;margin-bottom:12px">
					<div style="font-size:0.75em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Customer review</div>
					<p style="margin:0;color:#333;font-size:0.9em">{$r['review_body']}</p>
				</div>
				{{endif}}

				{{if $r['dealer_response']}}
				<div style="background:#f0f7ff;border-left:3px solid #2563eb;padding:8px 12px;border-radius:0 4px 4px 0;margin-bottom:12px;font-size:0.85em">
					<strong style="color:#2563eb">Dealer public response:</strong> {$r['dealer_response']}
				</div>
				{{endif}}

				<div style="background:#fff8f0;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:0 4px 4px 0;margin-bottom:12px;font-size:0.85em;color:#92400e">
					<strong>Dealer contest ({$r['dispute_at']}):</strong>
					<div style="white-space:pre-wrap;margin-top:4px">{$r['dispute_reason']}</div>
					{{if $r['dispute_evidence']}}
					<div style="margin-top:8px"><strong>Evidence:</strong><div style="white-space:pre-wrap;margin-top:4px">{$r['dispute_evidence']}</div></div>
					{{endif}}
				</div>

				{{if $r['customer_response']}}
				<div style="background:#ecfdf5;border-left:3px solid #10b981;padding:10px 14px;border-radius:0 4px 4px 0;margin-bottom:12px;font-size:0.85em;color:#065f46">
					<strong>Customer response ({$r['customer_responded_at']}):</strong>
					<div style="white-space:pre-wrap;margin-top:4px">{$r['customer_response']}</div>
					{{if $r['customer_evidence']}}
					<div style="margin-top:8px"><strong>Evidence:</strong><div style="white-space:pre-wrap;margin-top:4px">{$r['customer_evidence']}</div></div>
					{{endif}}
				</div>
				{{else}}
				<div style="background:#f3f4f6;border:1px dashed #d1d5db;padding:10px 14px;border-radius:4px;margin-bottom:12px;font-size:0.85em;color:#6b7280">
					<em>Customer has not yet responded.</em>
				</div>
				{{endif}}

				<div style="display:flex;gap:8px;flex-wrap:wrap">
					<a href="{$r['uphold_url']}" class="ipsButton ipsButton--negative ipsButton--small">Uphold Dealer (exclude from avg)</a>
					<a href="{$r['dismiss_url']}" class="ipsButton ipsButton--positive ipsButton--small">Dismiss Contest (keep review)</a>
					<details style="display:inline-block">
						<summary style="cursor:pointer;padding:6px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:0.85em">Request Customer Edit</summary>
						<form method="post" action="{$r['request_edit_url']}" style="margin-top:8px;background:#f9fafb;padding:12px;border-radius:4px">
							<textarea name="admin_note" rows="3" style="width:100%;border:1px solid #ccc;border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box" placeholder="Note to customer explaining what to clarify..."></textarea>
							<button type="submit" class="ipsButton ipsButton--normal ipsButton--small" style="margin-top:8px">Send request to customer</button>
						</form>
					</details>
				</div>
			</div>
			{{endforeach}}
		{{endif}}

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
		'template_data' => '$joinUrl, $contactEmail',
		'template_content' => <<<'TEMPLATE_EOT'
<div style="max-width:600px;margin:40px auto;padding:0 16px">
	<div class="ipsBox" style="text-align:center;padding:48px 32px">
		<i class="fa-solid fa-store-slash" style="font-size:3em;color:#9ca3af;margin-bottom:16px;display:block" aria-hidden="true"></i>
		<h2 style="margin:0 0 12px;font-size:1.4em;font-weight:800">No Dealer Subscription Found</h2>
		<p style="color:#6b7280;margin:0 0 24px;line-height:1.6">
			You don't have an active dealer subscription on your account.<br>
			Sign up to list your inventory and reach buyers on GunRack.deals.
		</p>
		<a href="{$joinUrl}" class="ipsButton ipsButton--primary" style="padding:12px 32px">
			<i class="fa-solid fa-store" aria-hidden="true"></i>
			<span>Become a Dealer</span>
		</a>
		<p style="margin:16px 0 0;font-size:0.85em;color:#9ca3af">
			Already subscribed? Contact us at <a href="mailto:{$contactEmail}">{$contactEmail}</a> and we'll get your account set up.
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
<style>
@media (max-width: 768px) {
  .gdTableWrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
  .gdHelpLayout { flex-direction:column !important; }
  .gdHelpSidebar { width:100% !important; position:static !important; }
  .gdFlexWrap > .ipsProfile__aside { width:100% !important; flex-shrink:1 !important; }
  .gdShellTabs [role="tablist"] { flex-wrap:wrap !important; }
  .gdShellTabs [role="tablist"] a { flex:1 1 auto; text-align:center; padding:10px 12px !important; font-size:0.85em; }
}
@media (max-width: 480px) {
  .gdShellTabs [role="tablist"] a { flex:1 1 100%; }
}
</style>
<div class="gdDealerWrapper" style="width:100%;box-sizing:border-box">

	<header class="ipsPageHeader ipsBox ipsBox--profileHeader ipsPull i-margin-bottom_block" style="width:100%;box-sizing:border-box;border-radius:8px;overflow:hidden;margin-bottom:16px">
		<div class="ipsCoverPhoto ipsCoverPhoto--profile" style="position:relative;overflow:hidden;min-height:160px">
			<div class="ipsCoverPhoto__container" style="width:100%;height:160px;overflow:hidden">
				{{if $dealer['cover_photo_url']}}
					<img src="{$dealer['cover_photo_url']}" class="ipsCoverPhoto__image" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
				{{else}}
					<div class="ipsFallbackImage gdDealerCoverFallback" style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);width:100%;height:160px"></div>
				{{endif}}
			</div>
		</div>
		<div class="ipsCoverPhotoMeta" style="background:#fff;border-top:none;padding:16px 20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
			{{if $dealer['avatar_url']}}
			<div class="ipsCoverPhoto__avatar" id="elProfilePhoto" style="margin-top:-50px">
				<span class="ipsUserPhoto ipsUserPhoto--xlarge">
					<img src="{$dealer['avatar_url']}" alt="" loading="lazy" onerror="this.style.display='none'">
				</span>
			</div>
			{{endif}}
			<div class="ipsCoverPhoto__titles" style="flex:1;min-width:200px">
				<div class="ipsCoverPhoto__title">
					<h1 style="margin:0;font-size:1.4em;font-weight:800">{$dealer['dealer_name']}</h1>
				</div>
				<div class="ipsCoverPhoto__desc" style="margin-top:4px">
					<span style="background:{$dealer['tier_color']};color:#fff;padding:2px 10px;border-radius:20px;font-size:0.8em;font-weight:700">{$dealer['tier_label']}</span>
					{{if $dealer['suspended']}}
					<span style="background:#dc2626;color:#fff;padding:2px 10px;border-radius:20px;font-size:0.8em;font-weight:700;margin-left:6px">Suspended</span>
					{{endif}}
				</div>
			</div>
			<div class="ipsCoverPhoto__buttons">
				<a href="{$tabUrls['subscription']}" class="ipsButton ipsButton--inherit ipsButton--small">
					<i class="fa-solid fa-credit-card" aria-hidden="true"></i>
					<span>{lang="gddealer_front_tab_subscription"}</span>
				</a>
			</div>
		</div>
	</header>

	{{if $dealer['onboarding_incomplete']}}
	<div style="background:#fefce8;border:1px solid #fde047;border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
		<div>
			<strong style="color:#854d0e">Complete Your Setup</strong>
			<span style="color:#713f12;margin-left:6px">&mdash; Your account is active but your product feed hasn't been configured yet.</span>
		</div>
		<a href="{$tabUrls['feedSettings']}" class="ipsButton ipsButton--normal ipsButton--small">
			<i class="fa-solid fa-gear" aria-hidden="true"></i>
			<span>Configure Feed Now</span>
		</a>
	</div>
	{{endif}}

	<div class="ipsPwaStickyFix ipsPwaStickyFix--ipsTabs"></div>
	<i-tabs class="ipsTabs ipsTabs--sticky ipsTabs--profile ipsTabs--stretch gdShellTabs gdDealerTabs">
		<div role="tablist" style="display:flex;gap:0;border-bottom:1px solid var(--i-border-color,#e0e0e0);overflow-x:auto;background:#fff;border-radius:8px 8px 0 0">
			<a href="{$tabUrls['overview']}" class="ipsTabs__tab {expression="$activeTab === 'overview' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'overview' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'overview' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'overview' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_overview"}</a>
			<a href="{$tabUrls['feedSettings']}" class="ipsTabs__tab {expression="$activeTab === 'feedSettings' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'feedSettings' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'feedSettings' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'feedSettings' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_feed"}</a>
			<a href="{$tabUrls['listings']}" class="ipsTabs__tab {expression="$activeTab === 'listings' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'listings' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'listings' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'listings' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_listings"}</a>
			<a href="{$tabUrls['unmatched']}" class="ipsTabs__tab {expression="$activeTab === 'unmatched' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'unmatched' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'unmatched' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'unmatched' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_unmatched"}</a>
			<a href="{$tabUrls['analytics']}" class="ipsTabs__tab {expression="$activeTab === 'analytics' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'analytics' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'analytics' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'analytics' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_analytics"}</a>
			<a href="{$tabUrls['reviews']}" class="ipsTabs__tab {expression="$activeTab === 'reviews' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'reviews' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'reviews' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'reviews' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_reviews"}{{if $dealer['new_reviews'] > 0}} <span style="background:#dc2626;color:#fff;border-radius:10px;padding:1px 6px;font-size:0.7em;font-weight:700;margin-left:4px">{$dealer['new_reviews']}</span>{{endif}}</a>
			<a href="{$tabUrls['help']}" class="ipsTabs__tab {expression="$activeTab === 'help' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'help' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'help' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'help' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_help"}</a>
		</div>
	</i-tabs>
	<div id="elDealerTabs_content" class="ipsTabs__panels ipsTabs__panels--profile" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-top:none;border-radius:0 0 8px 8px;padding:24px">
		<div class="ipsTabs__panel">
			{$body|raw}
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
		'template_data' => '$dealer, $overview, $tabUrls, $prefs',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsProfile ipsProfile--profile gdFlexWrap" style="display:flex;gap:24px;flex-wrap:wrap">
	<aside class="ipsProfile__aside" style="flex:0 0 260px;min-width:240px">
		<div class="ipsProfile__sticky-outer">
			<div class="ipsProfile__sticky-inner">
				<div class="ipsWidget" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:16px">
					<h3 class="ipsWidget__title" style="margin:0;padding:12px 16px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:0.85em;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#475569">Quick Links</h3>
					<div class="ipsWidget__content">
						<ul class="ipsList_reset" style="list-style:none;padding:0;margin:0">
							{{foreach $overview['quick_links'] as $ql}}
							<li style="border-bottom:1px solid var(--i-border-color,#f0f0f0)">
								<a href="{$ql['url']}" {{if $ql['external']}}target="_blank" rel="noopener"{{endif}} style="display:flex;align-items:center;gap:10px;padding:10px 16px;color:inherit;text-decoration:none">
									<i class="{$ql['icon']}" aria-hidden="true" style="width:16px;text-align:center;color:var(--gd-primary,#2563eb);flex-shrink:0"></i>
									<span style="font-size:0.9em">{$ql['label']}</span>
								</a>
							</li>
							{{endforeach}}
						</ul>
					</div>
				</div>

				{{if $prefs['show_profile_url']}}
				<div class="ipsWidget" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
					<h3 class="ipsWidget__title" style="margin:0;padding:12px 16px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:0.85em;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#475569">Your Profile URL</h3>
					<div class="ipsWidget__content i-padding_2" style="padding:16px">
						<code style="display:block;font-size:0.78em;word-break:break-all;background:var(--i-background,#f5f5f5);padding:8px;border-radius:4px;margin-bottom:8px">gunrack.deals/dealers/profile/{$dealer['dealer_slug']}</code>
						<a href="{$overview['profile_url']}" target="_blank" class="ipsButton ipsButton--primary ipsButton--small" style="width:100%;text-align:center;display:block">View Public Profile</a>
					</div>
				</div>
				{{endif}}
			</div>
		</div>
	</aside>

	<div class="ipsProfile__main" style="flex:1;min-width:300px">
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:24px">
			{{if $prefs['show_active']}}
			<div class="ipsWidget gdStatCard" style="background:{$overview['card_styles']['bg']};color:{$overview['card_styles']['color']};border:1px solid {$overview['card_styles']['border']};border-radius:8px">
				<div class="ipsWidget__content i-padding_2" style="padding:16px;text-align:center">
					<div style="font-size:2em;font-weight:800;color:{{if $overview['numbers_light']}}#ffffff{{else}}var(--gd-accent,#16a34a){{endif}}">{expression="number_format($overview['active_listings'])"}</div>
					<div style="font-size:0.82em;color:{$overview['card_styles']['label']};margin-top:4px">{lang="gddealer_front_active_listings"}</div>
				</div>
			</div>
			{{endif}}
			{{if $prefs['show_outofstock']}}
			<div class="ipsWidget gdStatCard" style="background:{$overview['card_styles']['bg']};color:{$overview['card_styles']['color']};border:1px solid {$overview['card_styles']['border']};border-radius:8px">
				<div class="ipsWidget__content i-padding_2" style="padding:16px;text-align:center">
					<div style="font-size:2em;font-weight:800;color:{{if $overview['numbers_light']}}#ffffff{{else}}var(--gd-danger,#dc2626){{endif}}">{expression="number_format($overview['out_of_stock'])"}</div>
					<div style="font-size:0.82em;color:{$overview['card_styles']['label']};margin-top:4px">{lang="gddealer_front_out_of_stock"}</div>
				</div>
			</div>
			{{endif}}
			{{if $prefs['show_unmatched']}}
			<div class="ipsWidget gdStatCard" style="background:{$overview['card_styles']['bg']};color:{$overview['card_styles']['color']};border:1px solid {$overview['card_styles']['border']};border-radius:8px">
				<div class="ipsWidget__content i-padding_2" style="padding:16px;text-align:center">
					<div style="font-size:2em;font-weight:800;color:{{if $overview['numbers_light']}}#ffffff{{else}}var(--gd-warning,#d97706){{endif}}">{expression="number_format($overview['unmatched'])"}</div>
					<div style="font-size:0.82em;color:{$overview['card_styles']['label']};margin-top:4px">{lang="gddealer_front_unmatched_count"}</div>
				</div>
			</div>
			{{endif}}
			{{if $prefs['show_clicks_7d']}}
			<div class="ipsWidget gdStatCard" style="background:{$overview['card_styles']['bg']};color:{$overview['card_styles']['color']};border:1px solid {$overview['card_styles']['border']};border-radius:8px">
				<div class="ipsWidget__content i-padding_2" style="padding:16px;text-align:center">
					<div style="font-size:2em;font-weight:800;color:{{if $overview['numbers_light']}}#ffffff{{else}}inherit{{endif}}">{expression="number_format($overview['clicks_7d'])"}</div>
					<div style="font-size:0.82em;color:{$overview['card_styles']['label']};margin-top:4px">{lang="gddealer_front_clicks_7d"}</div>
				</div>
			</div>
			{{endif}}
			{{if $prefs['show_clicks_30d']}}
			<div class="ipsWidget gdStatCard" style="background:{$overview['card_styles']['bg']};color:{$overview['card_styles']['color']};border:1px solid {$overview['card_styles']['border']};border-radius:8px">
				<div class="ipsWidget__content i-padding_2" style="padding:16px;text-align:center">
					<div style="font-size:2em;font-weight:800;color:{{if $overview['numbers_light']}}#ffffff{{else}}inherit{{endif}}">{expression="number_format($overview['clicks_30d'])"}</div>
					<div style="font-size:0.82em;color:{$overview['card_styles']['label']};margin-top:4px">{lang="gddealer_front_clicks_30d"}</div>
				</div>
			</div>
			{{endif}}
		</div>

		{{if $prefs['show_last_import']}}
		<div class="ipsBox" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
			<h3 class="ipsBox__header" style="margin:0;padding:12px 16px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">{lang="gddealer_front_last_import"}</h3>
			<div class="i-padding_2" style="padding:16px">
				{{if $overview['last_import']}}
				<div style="display:flex;gap:24px;flex-wrap:wrap">
					<div><span style="font-size:0.8em;color:#666;display:block">Started</span><strong>{$overview['last_import']['run_start']}</strong></div>
					<div><span style="font-size:0.8em;color:#666;display:block">Status</span><strong>{$overview['last_import']['status']}</strong></div>
					<div><span style="font-size:0.8em;color:#666;display:block">Total Records</span><strong>{expression="number_format($overview['last_import']['records_total'])"}</strong></div>
					<div><span style="font-size:0.8em;color:#666;display:block">New</span><strong style="color:#16a34a">{expression="number_format($overview['last_import']['records_created'])"}</strong></div>
					<div><span style="font-size:0.8em;color:#666;display:block">Updated</span><strong style="color:#2563eb">{expression="number_format($overview['last_import']['records_updated'])"}</strong></div>
					<div><span style="font-size:0.8em;color:#666;display:block">Unmatched</span><strong style="color:#f59e0b">{expression="number_format($overview['last_import']['records_unmatched'])"}</strong></div>
				</div>
				{{if $overview['last_import']['has_errors']}}
				<p style="margin:12px 0 0;color:#c00;font-size:0.9em">Errors were logged &mdash; see Feed Settings &rarr; Import History.</p>
				{{endif}}
				{{else}}
				<p style="margin:0;color:#999;font-style:italic">{lang="gddealer_front_last_import_none"}</p>
				{{endif}}
			</div>
		</div>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: dashboardCustomize ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'dashboardCustomize',
		'template_data' => '$prefs, $saveUrl, $cancelUrl, $csrfKey',
		'template_content' => <<<'TEMPLATE_EOT'
<div style="max-width:720px">
	<h2 style="margin:0 0 6px;font-size:1.3em;font-weight:800">{lang="gddealer_front_customize_title"}</h2>
	<p style="margin:0 0 20px;color:#666">{lang="gddealer_front_customize_intro"}</p>

	<form method="post" action="{$saveUrl}">
		<input type="hidden" name="csrfKey" value="{$csrfKey}">

		<div class="ipsBox" style="padding:20px;margin-bottom:16px">
			<h3 style="margin:0 0 12px;font-size:1em;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#475569">{lang="gddealer_front_customize_section_visibility"}</h3>
			<div style="display:flex;flex-direction:column;gap:10px">
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_active" value="1" {{if $prefs['show_active']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_active"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_outofstock" value="1" {{if $prefs['show_outofstock']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_outofstock"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_unmatched" value="1" {{if $prefs['show_unmatched']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_unmatched"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_clicks_7d" value="1" {{if $prefs['show_clicks_7d']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_clicks_7d"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_clicks_30d" value="1" {{if $prefs['show_clicks_30d']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_clicks_30d"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_last_import" value="1" {{if $prefs['show_last_import']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_last_import"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_profile_url" value="1" {{if $prefs['show_profile_url']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_profile_url"}</span>
				</label>
			</div>
		</div>

		<div class="ipsBox" style="padding:20px;margin-bottom:20px">
			<h3 style="margin:0 0 12px;font-size:1em;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#475569">{lang="gddealer_front_customize_section_theme"}</h3>
			<div style="display:flex;flex-direction:column;gap:10px">
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="radio" name="card_theme" value="default" {{if $prefs['card_theme'] === 'default'}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_theme_default"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="radio" name="card_theme" value="dark" {{if $prefs['card_theme'] === 'dark'}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_theme_dark"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="radio" name="card_theme" value="accent" {{if $prefs['card_theme'] === 'accent'}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_theme_accent"}</span>
				</label>
			</div>
		</div>

		<div style="display:flex;gap:8px">
			<button type="submit" class="ipsButton ipsButton--primary">{lang="gddealer_front_customize_save"}</button>
			<a href="{$cancelUrl}" class="ipsButton ipsButton--normal">Cancel</a>
		</div>
	</form>
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

	<div class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:24px">
		<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">Feed Configuration</h3>
		<div class="i-padding_2" style="padding:18px">
			{$form|raw}
			<div style="margin-top:16px">
				<a href="{$importUrl}" class="ipsButton ipsButton--primary">{lang="gddealer_front_run_import"}</a>
			</div>
		</div>
	</div>

	<div class="ipsBox" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
		<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">{lang="gddealer_front_import_history"}</h3>
		<div class="gdTableWrap">
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
	</div>

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
<div class="ipsBox" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
	<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">{lang="gddealer_front_tab_listings"}</h3>
	<div class="i-padding_2" style="padding:18px">

	<form method="get" action="{$tabUrls['listings']}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-bottom:16px">
		<input type="hidden" name="app" value="gddealer">
		<input type="hidden" name="module" value="dealers">
		<input type="hidden" name="controller" value="dashboard">
		<input type="hidden" name="do" value="listings">
		<div>
			<label style="display:block;font-size:0.85em;color:#666">{lang="gddealer_front_filter"}</label>
			<select name="filter" style="border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:6px 10px;background:#fff">
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
			<input type="text" name="q" value="{$search}" placeholder="{lang='gddealer_front_search_placeholder'}" style="border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:6px 10px;background:#fff">
		</div>
		<button type="submit" class="ipsButton ipsButton--primary ipsButton--small">{lang="gddealer_front_apply"}</button>
		<a href="{$exportUrl}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gddealer_front_export_csv"}</a>
	</form>

	<p style="color:#666">{expression="number_format( $total )"} listings total.</p>

	<div class="gdTableWrap">
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
	</div>

	{{if $pages > 1}}
	<div style="margin-top:16px">
		Page {$page} of {$pages}
	</div>
	{{endif}}

	</div>
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
<div class="ipsBox" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
	<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">{lang="gddealer_front_tab_unmatched"}</h3>
	<div class="i-padding_2" style="padding:18px">

	<p>{lang="gddealer_front_unmatched_intro"}</p>

	<p style="margin:8px 0 16px 0">
		<a href="{$exportUrl}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gddealer_front_export_csv"}</a>
	</p>

	<div class="gdTableWrap">
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

	</div>
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
		'template_data' => '$dealer, $gated, $analytics, $topClicked, $opportunities, $tabUrls',
		'template_content' => <<<'TEMPLATE_EOT'
<div>

	{{if $gated}}
		<div style="text-align:center;padding:48px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
			<div style="font-size:2em;margin-bottom:8px">&#x1F4CA;</div>
			<h3 style="margin:0 0 8px">Analytics &mdash; Pro &amp; Enterprise Only</h3>
			<p style="color:#666;margin:0 0 16px">Upgrade to Pro or Enterprise to unlock full analytics including price competitiveness, revenue opportunities, and click-through data.</p>
			<a href="{$tabUrls['subscription']}" class="ipsButton ipsButton--primary">View Upgrade Options</a>
		</div>
	{{else}}

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div style="flex:1 1 180px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:700;color:#16a34a">{$analytics['comp_lowest']}</div>
				<div style="color:#666;font-size:0.9em">Listings &mdash; Lowest Price</div>
			</div>
			<div style="flex:1 1 180px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:700;color:#f59e0b">{$analytics['comp_mid']}</div>
				<div style="color:#666;font-size:0.9em">Listings &mdash; Mid Range</div>
			</div>
			<div style="flex:1 1 180px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:700;color:#dc2626">{$analytics['comp_high']}</div>
				<div style="color:#666;font-size:0.9em">Listings &mdash; Highest Price</div>
			</div>
			<div style="flex:1 1 180px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:700;color:#2563eb">{$analytics['comp_only']}</div>
				<div style="color:#666;font-size:0.9em">Only Dealer for UPC</div>
			</div>
			<div style="flex:1 1 180px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:700">{$analytics['price_drop_count']}</div>
				<div style="color:#666;font-size:0.9em">Price Drops (30 days)</div>
			</div>
		</div>

		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:24px">
			<div style="padding:16px;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
				<h3 style="margin:0;font-size:1em;font-weight:700">Top 20 Most-Clicked Listings (Last 30 Days)</h3>
			</div>
			<div class="gdTableWrap">
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr>
					<th>UPC</th>
					<th style="width:120px">Your Price</th>
					<th style="width:100px">Clicks (30d)</th>
					<th style="width:100px">Clicks (7d)</th>
					<th style="width:100px">Status</th>
				</tr></thead>
				<tbody>
				{{if count( $topClicked ) === 0}}
					<tr><td colspan="5" style="text-align:center;color:#999;padding:24px">No click-through data yet.</td></tr>
				{{else}}
					{{foreach $topClicked as $r}}
					<tr>
						<td><code>{$r['upc']}</code></td>
						<td>${expression="number_format( (float) $r['dealer_price'], 2 )"}</td>
						<td>{$r['click_count_30d']}</td>
						<td>{$r['click_count_7d']}</td>
						<td>{{if $r['in_stock']}}<span style="color:#16a34a;font-weight:600">In Stock</span>{{else}}<span style="color:#dc2626">Out of Stock</span>{{endif}}</td>
					</tr>
					{{endforeach}}
				{{endif}}
				</tbody>
			</table>
			</div>
		</div>

		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
			<div style="padding:16px;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
				<h3 style="margin:0;font-size:1em;font-weight:700">Revenue Opportunities &mdash; You Are Not the Lowest Price</h3>
				<p style="margin:4px 0 0;color:#666;font-size:0.85em">Products where lowering your price could win more clicks.</p>
			</div>
			<div class="gdTableWrap">
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr>
					<th>UPC</th>
					<th style="width:120px">Your Price</th>
					<th style="width:120px">Lowest Price</th>
					<th style="width:100px">Gap</th>
					<th style="width:100px">Clicks (30d)</th>
				</tr></thead>
				<tbody>
				{{if count( $opportunities ) === 0}}
					<tr><td colspan="5" style="text-align:center;color:#999;padding:24px">No opportunities found &mdash; you may already be competitive!</td></tr>
				{{else}}
					{{foreach $opportunities as $r}}
					<tr>
						<td><code>{$r['upc']}</code></td>
						<td>${expression="number_format( (float) $r['your_price'], 2 )"}</td>
						<td>${expression="number_format( (float) $r['lowest_price'], 2 )"}</td>
						<td style="color:#dc2626;font-weight:600">+${expression="number_format( (float) $r['gap'], 2 )"}</td>
						<td>{$r['click_count_30d']}</td>
					</tr>
					{{endforeach}}
				{{endif}}
				</tbody>
			</table>
			</div>
		</div>

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
		'template_data' => '$dealer, $sub, $billingNote, $tabUrls',
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

	{{if $sub['trial_expires_at']}}
	<div style="margin-bottom:24px;padding:16px;border-radius:8px;border:1px solid {expression="$sub['trial_expiring_soon'] ? '#fca5a5' : 'var(--i-border-color,#e0e0e0)'"};background:{expression="$sub['trial_expiring_soon'] ? '#fff5f5' : '#fff'"}">
		<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
			<div>
				<div style="font-size:0.8em;color:#666;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Trial Period</div>
				<div style="font-weight:700;font-size:1.05em">
					Expires {$sub['trial_expires_formatted']}
				</div>
				{{if $sub['trial_expiring_soon']}}
					<div style="margin-top:4px;color:#dc2626;font-size:0.9em;font-weight:600">
						Expires in {$sub['trial_days_left']} day{{if $sub['trial_days_left'] !== 1}}s{{endif}} — subscribe to keep your listings live
					</div>
				{{else}}
					<div style="margin-top:4px;color:#666;font-size:0.85em">
						{$sub['trial_days_left']} days remaining on your trial
					</div>
				{{endif}}
			</div>
			{{if $sub['trial_expiring_soon']}}
			<div>
				<a href="{$sub['subscribe_url']}" class="ipsButton ipsButton--primary ipsButton--small">Subscribe Now</a>
			</div>
			{{endif}}
		</div>
	</div>
	{{endif}}

	<p>{$billingNote}</p>

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: help tab body ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'help',
		'template_data' => '$helpData',
		'template_content' => <<<'TEMPLATE_EOT'
<div>

	<h2 style="margin:0 0 4px">Feed Setup Guide</h2>
	<p style="color:#666;margin:0 0 24px">{$helpData['intro']}</p>

	<div class="gdHelpLayout" style="display:flex;gap:24px;align-items:flex-start">

		<div style="flex:1 1 0;min-width:0">

			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px;margin-bottom:16px">
				<h3 style="margin:0 0 12px;font-size:1.05em;font-weight:700;color:#1e3a5f">
					<span style="background:#2563eb;color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.8em;margin-right:8px;font-weight:700">1</span>
					Prepare your product feed
				</h3>
				<p>{$helpData['step1']}</p>
				<p><strong>Required fields per product:</strong></p>
				<ul style="margin:8px 0;padding-left:20px">
					<li><strong>UPC</strong> &mdash; 12-digit UPC barcode. Must match our catalog exactly.</li>
					<li><strong>Price</strong> &mdash; Your retail price as a decimal (e.g. 499.99)</li>
					<li><strong>In Stock</strong> &mdash; Boolean or quantity (0/1, true/false, or quantity integer)</li>
				</ul>
				<p><strong>Optional but recommended:</strong></p>
				<ul style="margin:8px 0;padding-left:20px">
					<li><strong>SKU</strong> &mdash; Your internal product identifier</li>
					<li><strong>Shipping Cost</strong> &mdash; Flat shipping fee. Use 0 for free shipping.</li>
					<li><strong>Condition</strong> &mdash; new, used, or refurbished</li>
					<li><strong>Product URL</strong> &mdash; Direct link to the product on your website</li>
					<li><strong>Stock Quantity</strong> &mdash; Exact quantity on hand</li>
				</ul>
			</div>

			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px;margin-bottom:16px">
				<h3 style="margin:0 0 12px;font-size:1.05em;font-weight:700;color:#1e3a5f">
					<span style="background:#2563eb;color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.8em;margin-right:8px;font-weight:700">2</span>
					Format your feed
				</h3>
				<p>{$helpData['step2']}</p>

				<p style="margin-top:12px"><strong>CSV format example:</strong></p>
				<pre style="background:#f4f4f4;padding:12px;border-radius:4px;overflow-x:auto;font-size:0.85em">upc,price,in_stock,shipping_cost,condition,product_url
026495088565,499.99,1,15.00,new,https://yourstore.com/product/123
000000000000,299.99,0,0.00,new,https://yourstore.com/product/456</pre>

				<p style="margin-top:16px"><strong>JSON format example:</strong></p>
				<pre style="background:#f4f4f4;padding:12px;border-radius:4px;overflow-x:auto;font-size:0.85em">[
  {
    "upc": "026495088565",
    "price": 499.99,
    "in_stock": true,
    "shipping_cost": 15.00,
    "condition": "new",
    "product_url": "https://yourstore.com/product/123"
  }
]</pre>

				<p style="margin-top:16px"><strong>XML format example:</strong></p>
				<pre style="background:#f4f4f4;padding:12px;border-radius:4px;overflow-x:auto;font-size:0.85em">&lt;products&gt;
  &lt;product&gt;
    &lt;upc&gt;026495088565&lt;/upc&gt;
    &lt;price&gt;499.99&lt;/price&gt;
    &lt;in_stock&gt;1&lt;/in_stock&gt;
    &lt;shipping_cost&gt;15.00&lt;/shipping_cost&gt;
    &lt;condition&gt;new&lt;/condition&gt;
  &lt;/product&gt;
&lt;/products&gt;</pre>
			</div>

			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px;margin-bottom:16px">
				<h3 style="margin:0 0 12px;font-size:1.05em;font-weight:700;color:#1e3a5f">
					<span style="background:#2563eb;color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.8em;margin-right:8px;font-weight:700">3</span>
					Configure field mapping
				</h3>
				<p>{$helpData['step3']}</p>
				<pre style="background:#f4f4f4;padding:12px;border-radius:4px;overflow-x:auto;font-size:0.85em;margin-top:12px">{
  "UPC": "upc",
  "PRICE": "dealer_price",
  "QTY": "stock_qty",
  "INSTOCK": "in_stock",
  "SHIP": "shipping_cost",
  "COND": "condition",
  "URL": "listing_url"
}</pre>
			</div>

			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px;margin-bottom:16px">
				<h3 style="margin:0 0 12px;font-size:1.05em;font-weight:700;color:#1e3a5f">
					<span style="background:#2563eb;color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.8em;margin-right:8px;font-weight:700">4</span>
					Enter your feed URL
				</h3>
				<p>{$helpData['step4']}</p>
				<ul style="margin:8px 0;padding-left:20px">
					<li>Basic Auth: <code style="background:#f4f4f4;padding:1px 6px;border-radius:3px">{"username":"user","password":"pass"}</code></li>
					<li>API Key: <code style="background:#f4f4f4;padding:1px 6px;border-radius:3px">{"api_key":"your-key-here"}</code></li>
				</ul>
			</div>

			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px;margin-bottom:24px">
				<h3 style="margin:0 0 12px;font-size:1.05em;font-weight:700;color:#1e3a5f">
					<span style="background:#2563eb;color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.8em;margin-right:8px;font-weight:700">5</span>
					Review your listings
				</h3>
				<p>{$helpData['step5']}</p>
			</div>

			<div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:6px;padding:16px">
				<h3 style="margin:0 0 8px;color:#1e40af">Feed Requirements Summary</h3>
				<ul style="margin:0;padding-left:20px;color:#1e3a5f">
					{{foreach $helpData['requirements'] as $req}}
					<li>{$req}</li>
					{{endforeach}}
				</ul>
			</div>

		</div>

		<div class="gdHelpSidebar" style="width:280px;flex-shrink:0;position:sticky;top:24px">

			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px;margin-bottom:16px">
				<h3 style="margin:0 0 12px;font-size:0.95em;font-weight:700">Quick Field Reference</h3>
				<table style="width:100%;font-size:0.85em;border-collapse:collapse">
					<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0;font-weight:600">upc</td><td style="padding:6px 0;color:#666">Required</td></tr>
					<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0;font-weight:600">dealer_price</td><td style="padding:6px 0;color:#666">Required</td></tr>
					<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0;font-weight:600">in_stock</td><td style="padding:6px 0;color:#666">Required</td></tr>
					<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0;font-weight:600">shipping_cost</td><td style="padding:6px 0;color:#666">Optional</td></tr>
					<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0;font-weight:600">condition</td><td style="padding:6px 0;color:#666">Optional</td></tr>
					<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0;font-weight:600">listing_url</td><td style="padding:6px 0;color:#666">Optional</td></tr>
					<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0;font-weight:600">stock_qty</td><td style="padding:6px 0;color:#666">Optional</td></tr>
					<tr><td style="padding:6px 0;font-weight:600">dealer_sku</td><td style="padding:6px 0;color:#666">Optional</td></tr>
				</table>
			</div>

			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px;margin-bottom:16px">
				<h3 style="margin:0 0 12px;font-size:0.95em;font-weight:700">Sync Schedule</h3>
				<table style="width:100%;font-size:0.85em;border-collapse:collapse">
					<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0;font-weight:600">Basic</td><td style="padding:6px 0;color:#666">Every 6 hours</td></tr>
					<tr style="border-bottom:1px solid #f0f0f0"><td style="padding:6px 0;font-weight:600">Pro</td><td style="padding:6px 0;color:#666">Every 30 min</td></tr>
					<tr><td style="padding:6px 0;font-weight:600">Enterprise</td><td style="padding:6px 0;color:#666">Every 15 min</td></tr>
				</table>
			</div>

			{{if $helpData['contact']}}
			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px">
				<h3 style="margin:0 0 8px;font-size:0.95em;font-weight:700">Need Help?</h3>
				<p style="margin:0 0 12px;font-size:0.85em;color:#666">Our team can help you get your feed configured and your first import running.</p>
				<a href="mailto:{$helpData['contact']}" class="ipsButton ipsButton--primary ipsButton--small" style="width:100%;text-align:center;display:block">Email Support</a>
			</div>
			{{endif}}

		</div>

	</div>

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
		'template_data' => '$tiers, $contactEmail, $guidelinesUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<div style="max-width:1100px;margin:0 auto;padding:0 16px">

	<div style="text-align:center;padding:48px 24px 40px;border-bottom:1px solid var(--i-border-color,#e0e0e0);margin-bottom:48px">
		<div style="display:inline-block;background:#eff6ff;color:#2563eb;font-size:0.8em;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;padding:6px 14px;border-radius:20px;margin-bottom:16px">Dealer Program</div>
		<h1 style="font-size:2.2em;font-weight:800;margin:0 0 16px;line-height:1.2">Reach Buyers Actively Searching<br>for What You Sell</h1>
		<p style="font-size:1.1em;color:#555;max-width:620px;margin:0 auto 24px">GunRack.deals puts your inventory in front of price-conscious buyers comparing products across multiple dealers. List once, sync automatically, sell more.</p>
		<div style="display:flex;gap:32px;justify-content:center;flex-wrap:wrap">
			<div style="text-align:center">
				<div style="font-size:1.8em;font-weight:800;color:#2563eb">3</div>
				<div style="font-size:0.85em;color:#666">Feed Formats</div>
			</div>
			<div style="text-align:center">
				<div style="font-size:1.8em;font-weight:800;color:#2563eb">Auto</div>
				<div style="font-size:0.85em;color:#666">Price Sync</div>
			</div>
			<div style="text-align:center">
				<div style="font-size:1.8em;font-weight:800;color:#2563eb">Live</div>
				<div style="font-size:0.85em;color:#666">Click Analytics</div>
			</div>
			<div style="text-align:center">
				<div style="font-size:1.8em;font-weight:800;color:#2563eb">FFL</div>
				<div style="font-size:0.85em;color:#666">Verified Platform</div>
			</div>
		</div>
	</div>

	<h2 style="text-align:center;font-size:1.5em;font-weight:700;margin:0 0 8px">Choose Your Plan</h2>
	<p style="text-align:center;color:#666;margin:0 0 32px">All plans include unlimited listings, automatic sync, and a self-service dealer dashboard.</p>

	<div style="display:flex;gap:20px;margin-bottom:48px;flex-wrap:wrap;align-items:stretch">
		{{foreach $tiers as $t}}
		<div style="flex:1 1 280px;background:#fff;border:2px solid {expression="$t['popular'] ? '#2563eb' : 'var(--i-border-color,#e0e0e0)'"};border-radius:12px;padding:28px;position:relative;display:flex;flex-direction:column">
			{{if $t['popular']}}
			<div style="position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:#2563eb;color:#fff;font-size:0.75em;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;padding:4px 14px;border-radius:20px">Most Popular</div>
			{{endif}}
			<div style="margin-bottom:20px">
				<h3 style="margin:0 0 4px;font-size:1.1em;font-weight:700">{$t['label']}</h3>
				<div style="font-size:2em;font-weight:800;margin:8px 0 4px">{$t['price']}</div>
				<div style="font-size:0.85em;color:#666">Feed sync: <strong>{$t['schedule']}</strong></div>
			</div>
			<ul style="list-style:none;padding:0;margin:0 0 24px;flex:1">
				{{foreach $t['features'] as $f}}
				<li style="display:flex;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid #f5f5f5;font-size:0.9em">
					<span style="color:#16a34a;font-weight:700;flex-shrink:0">&#10003;</span>
					<span>{$f}</span>
				</li>
				{{endforeach}}
			</ul>
			<a href="{$t['commerce_url']}" class="ipsButton {expression="$t['popular'] ? 'ipsButton--primary' : 'ipsButton--normal'"}" style="width:100%;text-align:center;display:block;padding:10px">Get Started</a>
		</div>
		{{endforeach}}
	</div>

	<div style="background:#f8faff;border:1px solid #dbeafe;border-radius:12px;padding:40px;margin-bottom:48px">
		<h2 style="text-align:center;font-size:1.4em;font-weight:700;margin:0 0 32px">How It Works</h2>
		<div style="display:flex;gap:24px;flex-wrap:wrap;justify-content:center">
			<div style="flex:1 1 180px;text-align:center;max-width:220px">
				<div style="width:48px;height:48px;background:#2563eb;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2em;font-weight:800;margin:0 auto 12px">1</div>
				<h4 style="margin:0 0 6px;font-weight:700">Subscribe</h4>
				<p style="margin:0;font-size:0.85em;color:#666">Choose a plan and complete checkout through our secure store.</p>
			</div>
			<div style="flex:1 1 180px;text-align:center;max-width:220px">
				<div style="width:48px;height:48px;background:#2563eb;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2em;font-weight:800;margin:0 auto 12px">2</div>
				<h4 style="margin:0 0 6px;font-weight:700">Connect Your Feed</h4>
				<p style="margin:0;font-size:0.85em;color:#666">Add your product feed URL in your dealer dashboard. XML, JSON, and CSV supported.</p>
			</div>
			<div style="flex:1 1 180px;text-align:center;max-width:220px">
				<div style="width:48px;height:48px;background:#2563eb;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2em;font-weight:800;margin:0 auto 12px">3</div>
				<h4 style="margin:0 0 6px;font-weight:700">Go Live</h4>
				<p style="margin:0;font-size:0.85em;color:#666">Your listings go live immediately after your first import completes.</p>
			</div>
			<div style="flex:1 1 180px;text-align:center;max-width:220px">
				<div style="width:48px;height:48px;background:#2563eb;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2em;font-weight:800;margin:0 auto 12px">4</div>
				<h4 style="margin:0 0 6px;font-weight:700">Track Performance</h4>
				<p style="margin:0;font-size:0.85em;color:#666">Monitor clicks, price competitiveness, and revenue opportunities in your dashboard.</p>
			</div>
		</div>
	</div>

	<div style="margin-bottom:48px">
		<h2 style="text-align:center;font-size:1.4em;font-weight:700;margin:0 0 24px">Frequently Asked Questions</h2>
		<div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr))">
			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px">
				<h4 style="margin:0 0 8px;font-weight:700">What feed formats do you support?</h4>
				<p style="margin:0;color:#555;font-size:0.9em">We support XML, JSON, and CSV feeds. Your feed must include UPC, price, and in-stock status at minimum. See the Help &amp; Setup guide in your dashboard for full requirements.</p>
			</div>
			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px">
				<h4 style="margin:0 0 8px;font-weight:700">How often does my inventory sync?</h4>
				<p style="margin:0;color:#555;font-size:0.9em">Basic syncs every 6 hours, Pro every 30 minutes, Enterprise every 15 minutes. You can also trigger a manual import anytime from your dealer dashboard.</p>
			</div>
			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px">
				<h4 style="margin:0 0 8px;font-weight:700">Do I need to be an FFL dealer?</h4>
				<p style="margin:0;color:#555;font-size:0.9em">Yes — GunRack.deals is a licensed FFL platform. You must hold a valid FFL to list firearms. Accessories and non-firearm products do not require an FFL.</p>
			</div>
			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px">
				<h4 style="margin:0 0 8px;font-weight:700">Can I cancel anytime?</h4>
				<p style="margin:0;color:#555;font-size:0.9em">Yes. Cancel anytime from your Subscription tab. Your listings remain live until the end of your current billing period, then are automatically suspended.</p>
			</div>
			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px">
				<h4 style="margin:0 0 8px;font-weight:700">What if my UPCs aren't in your catalog?</h4>
				<p style="margin:0;color:#555;font-size:0.9em">Unmatched UPCs are logged in your dashboard. Contact us and we'll add missing products to the catalog promptly — usually within 24 hours.</p>
			</div>
			<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px">
				<h4 style="margin:0 0 8px;font-weight:700">How do I upgrade or downgrade?</h4>
				<p style="margin:0;color:#555;font-size:0.9em">Manage your subscription from the Subscription tab in your dealer dashboard. Changes take effect on your next billing cycle.</p>
			</div>
		</div>
	</div>

	<div style="text-align:center;padding:40px 24px;background:#eff6ff;border-radius:12px;margin-bottom:32px">
		<h2 style="margin:0 0 8px;font-size:1.4em;font-weight:700">Ready to reach more buyers?</h2>
		<p style="margin:0 0 20px;color:#555">Join dealers already listing on GunRack.deals.</p>
		<a href="{$tiers[1]['commerce_url']}" class="ipsButton ipsButton--primary" style="padding:12px 32px;font-size:1em">Get Started with Pro</a>
		<p style="margin:12px 0 0;font-size:0.85em;color:#666">Questions? Email us at <a href="mailto:{$contactEmail}" style="color:#2563eb">{$contactEmail}</a></p>
	</div>

	<div style="text-align:center;padding:16px 0 32px;font-size:0.85em;color:#666">
		<a href="{$guidelinesUrl}" style="color:#2563eb">Review &amp; Dispute Policy</a>
	</div>

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: dealerRegister (self-service onboarding) ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'dealerRegister',
		'template_data' => '$form, $tier, $name, $guidelinesUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<div style="max-width:600px;margin:0 auto;padding:24px 16px">
	<div class="ipsBox">
		<div style="padding:32px 28px;text-align:center;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
			<i class="fa-solid fa-store" style="font-size:2.5em;color:#2563eb;margin-bottom:12px;display:block" aria-hidden="true"></i>
			<h1 style="margin:0 0 8px;font-size:1.4em;font-weight:800">Complete Your Dealer Setup</h1>
			<p style="margin:0;color:#666">Your subscription is active. Just a few details to get your dealer profile live.</p>
			<div style="margin-top:12px">
				<span style="background:#2563eb;color:#fff;padding:3px 12px;border-radius:20px;font-size:0.8em;font-weight:700;text-transform:uppercase">{$tier} Plan</span>
			</div>
		</div>
		<div style="padding:28px">
			{$form|raw}
		</div>
	</div>
	<p style="text-align:center;margin-top:16px;font-size:0.85em;color:#888">
		Need help? Visit our <a href="{$guidelinesUrl}" style="color:#2563eb">Review &amp; Setup Guidelines</a>
	</p>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: dealerReviews ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'dealerReviews',
		'template_data' => '$data, $csrfKey',
		'template_content' => <<<'TEMPLATE_EOT'
<div style="margin-bottom:24px">

	<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
		<div style="flex:1 1 160px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:800;color:{$data['rating_color']};line-height:1">{$data['avg_overall']}</div>
			<div style="color:#666;font-size:0.85em;margin-top:4px">Overall Rating</div>
			<div style="font-size:0.72em;font-weight:600;color:{$data['rating_color']};margin-top:4px">{$data['rating_label']}</div>
			<div style="color:#999;font-size:0.8em;margin-top:4px">{$data['total']} reviews</div>
		</div>
		<div style="flex:1 1 160px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:800;color:{$data['color_pricing']}">{$data['avg_pricing']}</div>
			<div style="color:#666;font-size:0.85em">Pricing Accuracy</div>
		</div>
		<div style="flex:1 1 160px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:800;color:{$data['color_shipping']}">{$data['avg_shipping']}</div>
			<div style="color:#666;font-size:0.85em">Shipping Speed</div>
		</div>
		<div style="flex:1 1 160px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:800;color:{$data['color_service']}">{$data['avg_service']}</div>
			<div style="color:#666;font-size:0.85em">Customer Service</div>
		</div>
	</div>

	{{if $data['disputes_suspended']}}
	<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
		<div>
			<strong style="color:#991b1b">Disputes Suspended</strong>
			<span style="color:#7f1d1d;margin-left:6px">&mdash; Your ability to contest reviews has been suspended by a site administrator. Contact <a href="mailto:{$data['help_email']}" style="color:#2563eb">{$data['help_email']}</a> for more information.</span>
		</div>
	</div>
	{{endif}}

	<div style="background:#f8fafc;border:1px solid var(--i-border-color,#e0e0e0);border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:0.85em;color:#334155">
		{{if $data['disputes_unlimited']}}
			You have <strong>unlimited</strong> review contests this month (Enterprise plan).
		{{else}}
			You have <strong>{$data['disputes_remaining']}</strong> review contests remaining this month.
		{{endif}}
	</div>

	{{if count($data['rows']) === 0}}
		<div class="ipsEmptyMessage"><p>No reviews yet. Reviews appear here once customers rate your dealership.</p></div>
	{{else}}
		{{foreach $data['rows'] as $r}}
		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px;margin-bottom:12px">
			<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;flex-wrap:wrap;gap:8px">
				<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center">
					<span style="font-size:0.8em;font-weight:700;color:{$r['avg_color']};background:{$r['avg_color']}18;padding:2px 8px;border-radius:12px">{$r['avg_overall']} / 5</span>
					<span style="font-size:0.8em;color:#666">Pricing: <strong>{$r['rating_pricing']}/5</strong></span>
					<span style="font-size:0.8em;color:#666">Shipping: <strong>{$r['rating_shipping']}/5</strong></span>
					<span style="font-size:0.8em;color:#666">Service: <strong>{$r['rating_service']}/5</strong></span>
				</div>
				<span style="font-size:0.8em;color:#999">{$r['created_at']}</span>
			</div>

			{{if $r['review_body']}}
			<p style="margin:0 0 12px;color:#333">{$r['review_body']}</p>
			{{endif}}

			{{if $r['dealer_response']}}
				<div style="background:#f0f7ff;border-left:3px solid #2563eb;padding:12px 16px;border-radius:0 6px 6px 0;margin-top:8px">
					<div style="font-size:0.8em;color:#2563eb;font-weight:700;margin-bottom:4px">Your Response &mdash; {$r['response_at']}</div>
					<p style="margin:0;font-size:0.9em">{$r['dealer_response']}</p>
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'pending_customer'}}
				<div style="background:#fff8f0;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#92400e">
					<strong>Contest submitted &mdash; awaiting customer response.</strong>
					{{if $r['dispute_deadline']}}<div style="font-size:0.8em;margin-top:2px">Customer has until {$r['dispute_deadline']} to respond.</div>{{endif}}
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'pending_admin'}}
				<div style="background:#eff6ff;border-left:3px solid #2563eb;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#1e3a8a">
					<strong>Contest under admin review.</strong> The customer has responded and admin will resolve shortly.
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'resolved_dealer'}}
				<div style="background:#f0fdf4;border-left:3px solid #16a34a;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#14532d">
					<strong>Contest resolved in your favor.</strong> This review no longer affects your rating average.
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'resolved_customer'}}
				<div style="background:#fef2f2;border-left:3px solid #dc2626;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#7f1d1d">
					<strong>Contest resolved in the customer's favor.</strong> The review stands.
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'dismissed'}}
				<div style="background:#f1f5f9;border-left:3px solid #64748b;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#334155">
					<strong>Contest dismissed.</strong> This review cannot be contested again.
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'none'}}
				{{if $r['dealer_response'] === ''}}
				<details style="margin-top:8px">
					<summary style="cursor:pointer;font-size:0.85em;color:#2563eb;font-weight:600">Respond to this review</summary>
					<form method="post" action="{$r['respond_url']}" style="margin-top:8px">
						<input type="hidden" name="csrfKey" value="{$csrfKey}">
						<textarea name="response" rows="3" style="width:100%;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box" placeholder="Write a professional response visible to all buyers..."></textarea>
						<button type="submit" class="ipsButton ipsButton--primary ipsButton--small" style="margin-top:8px">Post Response</button>
					</form>
				</details>
				{{endif}}

				{{if $data['disputes_unlimited']}}
				<details style="margin-top:8px">
					<summary style="cursor:pointer;font-size:0.85em;color:#dc2626;font-weight:600">Contest this review</summary>
					<form method="post" action="{$r['dispute_url']}" style="margin-top:8px">
						<input type="hidden" name="csrfKey" value="{$csrfKey}">
						<p style="margin:0 0 8px;font-size:0.8em;color:#666">Read the <a href="{$data['guidelines_url']}" style="color:#2563eb" target="_blank">Dispute Guidelines</a> before contesting a review.</p>
						<label style="display:block;font-size:0.8em;font-weight:600;margin-bottom:4px">Reason for contest</label>
						<textarea name="dispute_reason" rows="3" required style="width:100%;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Explain why this review should be removed (e.g. never purchased from us, fraudulent, violates terms)..."></textarea>
						<label style="display:block;font-size:0.8em;font-weight:600;margin-bottom:4px">Supporting evidence (order numbers, screenshots, transaction IDs)</label>
						<textarea name="dispute_evidence" rows="3" style="width:100%;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box" placeholder="Paste order numbers, links, or evidence that supports your contest..."></textarea>
						<button type="submit" class="ipsButton ipsButton--negative ipsButton--small" style="margin-top:8px">Submit Contest</button>
					</form>
				</details>
				{{else}}
					{{if $data['disputes_remaining'] > 0}}
					<details style="margin-top:8px">
						<summary style="cursor:pointer;font-size:0.85em;color:#dc2626;font-weight:600">Contest this review</summary>
						<form method="post" action="{$r['dispute_url']}" style="margin-top:8px">
							<input type="hidden" name="csrfKey" value="{$csrfKey}">
							<p style="margin:0 0 8px;font-size:0.8em;color:#666">Read the <a href="{$data['guidelines_url']}" style="color:#2563eb" target="_blank">Dispute Guidelines</a> before contesting a review.</p>
							<label style="display:block;font-size:0.8em;font-weight:600;margin-bottom:4px">Reason for contest</label>
							<textarea name="dispute_reason" rows="3" required style="width:100%;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Explain why this review should be removed (e.g. never purchased from us, fraudulent, violates terms)..."></textarea>
							<label style="display:block;font-size:0.8em;font-weight:600;margin-bottom:4px">Supporting evidence (order numbers, screenshots, transaction IDs)</label>
							<textarea name="dispute_evidence" rows="3" style="width:100%;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box" placeholder="Paste order numbers, links, or evidence that supports your contest..."></textarea>
							<button type="submit" class="ipsButton ipsButton--negative ipsButton--small" style="margin-top:8px">Submit Contest</button>
						</form>
					</details>
					{{else}}
					<div style="background:#f1f5f9;border-left:3px solid #64748b;padding:8px 12px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.8em;color:#475569">
						You have reached your monthly contest limit. Upgrade your plan for more contests.
					</div>
					{{endif}}
				{{endif}}
			{{endif}}
		</div>
		{{endforeach}}
	{{endif}}

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: dealerProfile ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'dealerProfile',
		'template_data' => '$dealer, $stats, $reviews, $canRate, $alreadyRated, $loginRequired, $rateUrl, $csrfKey, $loginUrl, $customerDispute, $guidelinesUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<style>
#ipsLayout_mainArea { max-width: 100% !important; }
#ipsLayout_main { max-width: 100% !important; }
.ipsLayout_container { max-width: 1446px !important; }
.gdDealerStats { display:flex; flex-wrap:wrap; border-top:1px solid var(--i-border-color,#e8e8e8); margin-top:16px; }
.gdDealerStats > div { flex:1 1 120px; padding:16px 20px; text-align:center; min-height:80px; display:flex; flex-direction:column; justify-content:center; }
.gdDealerStats > div + div { border-left:1px solid var(--i-border-color,#e8e8e8); }
@media (max-width: 768px) {
  .gdDealerStats > div { flex:1 1 45%; min-height:60px; }
  .gdDealerStats > div + div { border-left:none; }
  .gdDealerStats > div:nth-child(odd) { border-right:1px solid var(--i-border-color,#e8e8e8); }
  .gdDealerStats > div:nth-child(n+3) { border-top:1px solid var(--i-border-color,#e8e8e8); }
  .gdProfileSidebar { width:100% !important; flex-shrink:1 !important; }
  .gdProfileButtons { justify-content:center; }
  .gdTableWrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
}
@media (max-width: 480px) {
  .gdDealerStats > div { flex:1 1 100%; border-left:none !important; border-right:none !important; }
  .gdDealerStats > div + div { border-top:1px solid var(--i-border-color,#e8e8e8); }
}
</style>

<div class="gdDealerWrapper" style="width:100%;max-width:1446px;margin:0 auto;padding:0 24px;box-sizing:border-box">

	<header class="ipsPageHeader ipsBox ipsBox--profileHeader ipsPull i-margin-bottom_block" style="width:100%;box-sizing:border-box;border-radius:8px;overflow:hidden;margin-bottom:16px">
		<div class="ipsCoverPhoto ipsCoverPhoto--profile" style="position:relative;overflow:hidden;min-height:180px">
			<div class="ipsCoverPhoto__container" style="width:100%;height:180px;overflow:hidden">
				{{if $dealer['cover_photo_url']}}
					<img src="{$dealer['cover_photo_url']}" class="ipsCoverPhoto__image" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
				{{else}}
					<div class="ipsFallbackImage gdDealerCoverFallback" style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);width:100%;height:180px"></div>
				{{endif}}
			</div>
		</div>
		<div class="ipsCoverPhotoMeta" style="background:#fff;border-top:none;padding:20px 24px">
			<div style="display:flex;gap:20px;align-items:flex-end;flex-wrap:wrap">
				{{if $dealer['avatar_url']}}
				<div class="ipsCoverPhoto__avatar" id="elProfilePhoto" style="margin-top:-60px">
					<span class="ipsUserPhoto ipsUserPhoto--xlarge">
						<img src="{$dealer['avatar_url']}" alt="" loading="lazy" onerror="this.style.display='none'">
					</span>
				</div>
				{{endif}}
				<div class="ipsCoverPhoto__titles" style="flex:1;min-width:200px">
					<div class="ipsCoverPhoto__title">
						<h1 style="margin:0;font-size:1.6em;font-weight:800">{$dealer['dealer_name']}</h1>
					</div>
					<div class="ipsCoverPhoto__desc" style="margin-top:6px">
						<span style="background:{$dealer['tier_color']};color:#fff;padding:2px 10px;border-radius:20px;font-size:0.8em;font-weight:700">{$dealer['tier_label']}</span>
					</div>
				</div>
				<div class="ipsCoverPhoto__buttons gdProfileButtons" style="display:flex;gap:8px;flex-wrap:wrap">
					<a href="mailto:{$dealer['contact_email']}" class="ipsButton ipsButton--primary">
						<i class="fa-solid fa-envelope" aria-hidden="true"></i>
						<span>Contact Dealer</span>
					</a>
					<a href="{$guidelinesUrl}" class="ipsButton ipsButton--inherit">
						<i class="fa-solid fa-circle-info" aria-hidden="true"></i>
						<span>Review Guidelines</span>
					</a>
				</div>
			</div>
			<div class="gdDealerStats">
				<div>
					<div style="font-size:0.72em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Overall Rating</div>
					<div style="font-size:1.6em;font-weight:800;color:{$stats['rating_color']};line-height:1">{$stats['avg_overall']}<span style="font-size:0.45em;color:#888;font-weight:400"> /5</span></div>
					<div style="font-size:0.72em;font-weight:600;color:{$stats['rating_color']};margin-top:4px">{$stats['rating_label']}</div>
				</div>
				<div>
					<div style="font-size:0.75em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Reviews</div>
					<div style="font-size:1.6em;font-weight:800;line-height:1">{$stats['total']}</div>
				</div>
				<div>
					<div style="font-size:0.75em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Member Since</div>
					<div style="font-size:1.6em;font-weight:800;line-height:1">{{if $dealer['member_since']}}{$dealer['member_since']}{{else}}&mdash;{{endif}}</div>
				</div>
				<div>
					<div style="font-size:0.75em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Active Listings</div>
					<div style="font-size:1.6em;font-weight:800;color:#16a34a;line-height:1">{$dealer['listing_count']}</div>
				</div>
			</div>
		</div>
	</header>

	{{if !$dealer['is_active']}}
	<div style="background:#f8f9fa;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:14px 18px;margin-bottom:16px">
		<strong style="color:#374151">This dealer's listings are currently inactive.</strong>
		<p style="margin:4px 0 0;color:#6b7280;font-size:0.9em">Inventory and pricing are not being updated. Existing reviews and ratings are shown below for reference.</p>
	</div>
	{{endif}}

	{{if $customerDispute}}
	<div class="ipsBox i-margin-bottom_block" style="background:#fff8f0;border:1px solid #f59e0b;border-radius:8px;padding:20px;margin-bottom:24px">
		<h2 style="margin:0 0 8px;font-size:1.05em;font-weight:700;color:#92400e">{$dealer['dealer_name']} has contested your review</h2>
		<p style="margin:0 0 12px;font-size:0.9em;color:#78350f">
			The dealer has submitted a contest against the review you left.
			{{if $customerDispute['deadline_formatted']}}You have until <strong>{$customerDispute['deadline_formatted']}</strong> to respond, or the contest will be automatically resolved in the dealer's favor.{{endif}}
		</p>
		{{if $customerDispute['dispute_reason']}}
		<div style="background:#fff;border-left:3px solid #f59e0b;padding:10px 14px;margin-bottom:12px;border-radius:0 4px 4px 0">
			<div style="font-size:0.8em;font-weight:700;color:#92400e;margin-bottom:4px">Dealer's reason</div>
			<p style="margin:0;font-size:0.9em;color:#333">{$customerDispute['dispute_reason']}</p>
		</div>
		{{endif}}
		{{if $customerDispute['dispute_evidence']}}
		<div style="background:#fff;border-left:3px solid #f59e0b;padding:10px 14px;margin-bottom:12px;border-radius:0 4px 4px 0">
			<div style="font-size:0.8em;font-weight:700;color:#92400e;margin-bottom:4px">Dealer's evidence</div>
			<p style="margin:0;font-size:0.9em;color:#333;white-space:pre-wrap">{$customerDispute['dispute_evidence']}</p>
		</div>
		{{endif}}
		<form method="post" action="{$customerDispute['respond_url']}">
			<input type="hidden" name="csrfKey" value="{$csrfKey}">
			<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:4px;color:#78350f">Your response</label>
			<textarea name="customer_response" rows="4" required class="ipsInput ipsInput--text" style="width:100%;border:1px solid #f59e0b;border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Explain your side of the story. Admin will review both accounts."></textarea>
			<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:4px;color:#78350f">Supporting evidence (optional)</label>
			<textarea name="customer_evidence" rows="3" class="ipsInput ipsInput--text" style="width:100%;border:1px solid #f59e0b;border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Paste order numbers, links, receipts, or other evidence that supports your review..."></textarea>
			<button type="submit" class="ipsButton ipsButton--primary">Submit My Response</button>
		</form>
	</div>
	{{endif}}

	<div class="ipsProfile ipsProfile--profile" style="display:flex;gap:24px;flex-wrap:wrap">
		<aside class="ipsProfile__aside gdProfileSidebar" style="width:300px;flex-shrink:0">
			<div class="ipsProfile__sticky-outer">
				<div class="ipsProfile__sticky-inner">
					<div class="ipsWidget" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:16px">
						<h3 class="ipsWidget__title" style="margin:0;padding:12px 16px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:0.9em;font-weight:700">Rating Breakdown</h3>
						<div class="ipsWidget__content i-padding_2" style="padding:16px">
							<div style="margin-bottom:14px">
								<div style="display:flex;justify-content:space-between;margin-bottom:4px">
									<span style="font-size:0.85em">Pricing Accuracy</span>
									<strong style="color:{$stats['color_pricing']}">{$stats['avg_pricing']}/5</strong>
								</div>
								<div style="background:var(--i-border-color,#e0e0e0);border-radius:4px;height:8px">
									<div style="background:{$stats['color_pricing']};border-radius:4px;height:8px;width:{$stats['pct_pricing']}%;transition:width 0.3s ease"></div>
								</div>
							</div>
							<div style="margin-bottom:14px">
								<div style="display:flex;justify-content:space-between;margin-bottom:4px">
									<span style="font-size:0.85em">Shipping Speed</span>
									<strong style="color:{$stats['color_shipping']}">{$stats['avg_shipping']}/5</strong>
								</div>
								<div style="background:var(--i-border-color,#e0e0e0);border-radius:4px;height:8px">
									<div style="background:{$stats['color_shipping']};border-radius:4px;height:8px;width:{$stats['pct_shipping']}%;transition:width 0.3s ease"></div>
								</div>
							</div>
							<div>
								<div style="display:flex;justify-content:space-between;margin-bottom:4px">
									<span style="font-size:0.85em">Customer Service</span>
									<strong style="color:{$stats['color_service']}">{$stats['avg_service']}/5</strong>
								</div>
								<div style="background:var(--i-border-color,#e0e0e0);border-radius:4px;height:8px">
									<div style="background:{$stats['color_service']};border-radius:4px;height:8px;width:{$stats['pct_service']}%;transition:width 0.3s ease"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</aside>

		<div class="ipsProfile__main" style="flex:1 1 0;min-width:0">
			{{if $canRate}}
			<div class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:24px">
				<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">Leave a Review</h3>
				<div class="i-padding_2" style="padding:18px">
					<form method="post" action="{$rateUrl}">
						<input type="hidden" name="csrfKey" value="{$csrfKey}">
						<div style="display:flex;gap:20px;margin-bottom:16px;flex-wrap:wrap">
							<div style="flex:1 1 140px">
								<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Pricing Accuracy</label>
								<select name="rating_pricing" class="ipsInput ipsInput--select" required>
									<option value="">Rate...</option>
									<option value="5">★★★★★ Excellent</option>
									<option value="4">★★★★☆ Good</option>
									<option value="3">★★★☆☆ Average</option>
									<option value="2">★★☆☆☆ Poor</option>
									<option value="1">★☆☆☆☆ Terrible</option>
								</select>
							</div>
							<div style="flex:1 1 140px">
								<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Shipping Speed</label>
								<select name="rating_shipping" class="ipsInput ipsInput--select" required>
									<option value="">Rate...</option>
									<option value="5">★★★★★ Excellent</option>
									<option value="4">★★★★☆ Good</option>
									<option value="3">★★★☆☆ Average</option>
									<option value="2">★★☆☆☆ Poor</option>
									<option value="1">★☆☆☆☆ Terrible</option>
								</select>
							</div>
							<div style="flex:1 1 140px">
								<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Customer Service</label>
								<select name="rating_service" class="ipsInput ipsInput--select" required>
									<option value="">Rate...</option>
									<option value="5">★★★★★ Excellent</option>
									<option value="4">★★★★☆ Good</option>
									<option value="3">★★★☆☆ Average</option>
									<option value="2">★★☆☆☆ Poor</option>
									<option value="1">★☆☆☆☆ Terrible</option>
								</select>
							</div>
						</div>
						<textarea name="review_body" rows="4" class="ipsInput ipsInput--text" style="width:100%;box-sizing:border-box;margin-bottom:12px;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em" placeholder="Share your experience (optional but helpful)..."></textarea>
						<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
							<span style="font-size:0.8em;color:#666">By submitting you agree to our <a href="{$guidelinesUrl}" style="color:#2563eb">review guidelines</a>.</span>
							<button type="submit" class="ipsButton ipsButton--primary">Submit Review</button>
						</div>
					</form>
				</div>
			</div>
			{{elseif $alreadyRated}}
			<div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 18px;margin-bottom:24px;color:#1e40af">
				You have already reviewed this dealer. Thank you for your feedback!
			</div>
			{{elseif $loginRequired}}
			<div class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:24px">
				<div class="i-padding_2" style="padding:24px;text-align:center">
					<p style="margin:0 0 12px;color:#666">Sign in to leave a review for this dealer.</p>
					<a href="{$loginUrl}" class="ipsButton ipsButton--primary">Sign In to Review</a>
				</div>
			</div>
			{{endif}}

			<div class="ipsBox" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
				<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">Customer Reviews <span style="font-size:0.75em;font-weight:400;color:#666">({expression="number_format($stats['total'])"})</span></h3>
				{{if count($reviews) === 0}}
				<div class="i-padding_2" style="padding:32px;text-align:center;color:#999">
					<i class="fa-regular fa-star" style="font-size:2em;display:block;margin-bottom:8px;opacity:0.4" aria-hidden="true"></i>
					No reviews yet. Be the first to review this dealer.
				</div>
				{{else}}
					{{foreach $reviews as $r}}
					<div style="padding:20px;border-bottom:1px solid var(--i-border-color,#f0f0f0)">

						<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
							<div style="display:flex;align-items:center;gap:10px">
								<span class="ipsUserPhoto ipsUserPhoto--small">
									<img src="{$r['reviewer_avatar']}" alt="" loading="lazy">
								</span>
								<div>
									<div style="font-weight:700;font-size:0.9em">{$r['reviewer_name']}</div>
									<div style="font-size:0.75em;color:#9ca3af">{$r['created_at_formatted']}</div>
								</div>
							</div>
							<div style="background:{$r['avg_color']}18;border:1px solid {$r['avg_color']}40;border-radius:20px;padding:4px 12px;display:flex;align-items:center;gap:6px">
								<span style="color:{$r['avg_color']};font-weight:800;font-size:1em">{$r['avg_score']}</span>
								<span style="color:#9ca3af;font-size:0.75em">/ 5</span>
							</div>
						</div>

						<div style="display:flex;gap:16px;margin-bottom:12px;flex-wrap:wrap">
							<div style="display:flex;align-items:center;gap:4px">
								<span style="font-size:0.75em;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Pricing</span>
								<span style="color:#f59e0b;font-size:0.9em">{$r['stars_pricing']}</span>
							</div>
							<div style="display:flex;align-items:center;gap:4px">
								<span style="font-size:0.75em;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Shipping</span>
								<span style="color:#f59e0b;font-size:0.9em">{$r['stars_shipping']}</span>
							</div>
							<div style="display:flex;align-items:center;gap:4px">
								<span style="font-size:0.75em;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Service</span>
								<span style="color:#f59e0b;font-size:0.9em">{$r['stars_service']}</span>
							</div>
						</div>

						{{if $r['review_body']}}
						<p style="margin:0 0 12px;line-height:1.6;color:#374151">{$r['review_body']}</p>
						{{endif}}

						{{if $r['is_own_review'] and $r['dispute_status'] === 'pending_customer'}}
						<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px 16px;font-size:0.85em;color:#991b1b;margin-bottom:8px">
							<strong>Action Required:</strong> The dealer has contested this review. You must respond or the dispute will be resolved in their favor.
							{{if $r['dispute_respond_url']}}<a href="{$r['dispute_respond_url']}" style="color:#dc2626;font-weight:700;margin-left:6px">Respond Now &rarr;</a>{{endif}}
						</div>
						{{elseif $r['dispute_status'] === 'pending_customer' or $r['dispute_status'] === 'pending_admin'}}
						<div style="background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:8px 12px;font-size:0.82em;color:#854d0e;margin-bottom:8px">
							This review is currently under dispute review.
						</div>
						{{endif}}

						{{if $r['dealer_response']}}
						<div style="background:#f0f7ff;border-left:3px solid var(--gd-primary,#2563eb);padding:12px 16px;border-radius:0 6px 6px 0;margin-top:8px">
							<div style="font-size:0.75em;color:var(--gd-primary,#2563eb);font-weight:700;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.05em">
								<i class="fa-solid fa-reply" aria-hidden="true"></i> Dealer Response
								<span style="color:#9ca3af;font-weight:400;text-transform:none;margin-left:6px">{$r['response_at']}</span>
							</div>
							<p style="margin:0;font-size:0.9em;line-height:1.5;color:#374151">{$r['dealer_response']}</p>
						</div>
						{{endif}}

					</div>
					{{endforeach}}
				{{endif}}
			</div>
		</div>
	</div>

</div>
TEMPLATE_EOT,
	],


	/* ===== FRONT: reviewGuidelines ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'reviewGuidelines',
		'template_data' => '$content, $contactEmail',
		'template_content' => <<<'TEMPLATE_EOT'
<div style="max-width:900px;margin:0 auto;padding:0 16px">

	<div style="text-align:center;padding:32px 0 24px">
		<h1 style="font-size:1.8em;font-weight:800;margin:0 0 8px">Review &amp; Dispute Guidelines</h1>
		<p style="color:#666;margin:0">Everything you need to know about leaving reviews and how disputes work.</p>
	</div>

	<div style="display:flex;gap:12px;margin-bottom:32px;flex-wrap:wrap;justify-content:center">
		<a href="#buyers" class="ipsButton ipsButton--normal ipsButton--small">For Buyers</a>
		<a href="#disputes" class="ipsButton ipsButton--normal ipsButton--small">Dispute Process</a>
		<a href="#dealers" class="ipsButton ipsButton--normal ipsButton--small">For Dealers</a>
	</div>

	<div id="buyers" class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:20px">
		<h3 class="ipsBox__header" style="margin:0;padding:16px 24px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1.1em;font-weight:700;display:flex;align-items:center;gap:10px">
			<span style="background:#eff6ff;color:#2563eb;width:32px;height:32px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.9em">&#9733;</span>
			{$content['buyer_title']}
		</h3>
		<div class="i-padding_2" style="padding:24px;color:#444;line-height:1.7;white-space:pre-line">{$content['buyer_body']}</div>
	</div>

	<div id="disputes" class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:20px">
		<h3 class="ipsBox__header" style="margin:0;padding:16px 24px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1.1em;font-weight:700;display:flex;align-items:center;gap:10px">
			<span style="background:#fff8f0;color:#f59e0b;width:32px;height:32px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.9em">&#9878;</span>
			{$content['dispute_title']}
		</h3>
		<div class="i-padding_2" style="padding:24px;color:#444;line-height:1.7;white-space:pre-line">{$content['dispute_body']}</div>
	</div>

	<div id="dealers" class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:32px">
		<h3 class="ipsBox__header" style="margin:0;padding:16px 24px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1.1em;font-weight:700;display:flex;align-items:center;gap:10px">
			<span style="background:#f0fdf4;color:#16a34a;width:32px;height:32px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.9em">&#127978;</span>
			{$content['dealer_title']}
		</h3>
		<div class="i-padding_2" style="padding:24px;color:#444;line-height:1.7;white-space:pre-line">{$content['dealer_body']}</div>
	</div>

	<div style="text-align:center;padding:16px;color:#999;font-size:0.85em">
		Questions? Contact us at <a href="mailto:{$contactEmail}" style="color:#2563eb">{$contactEmail}</a>
	</div>

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: dealerDirectory ===== */
	[
		'set_id'        => 1,
		'app'           => 'gddealer',
		'location'      => 'front',
		'group'         => 'dealers',
		'template_name' => 'dealerDirectory',
		'template_data' => '$dealers, $total, $page, $perPage, $pagination, $tier, $sort, $search, $loggedIn, $joinUrl, $directoryUrl',
		'template_content' => <<<'TEMPLATE_EOT'
<div style="max-width:1400px;margin:0 auto;padding:0 24px;box-sizing:border-box">

	<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
		<div>
			<h1 style="margin:0 0 4px;font-size:1.6em;font-weight:800">{lang="gddealer_directory_title"}</h1>
			<p style="margin:0;color:#666;font-size:0.9em">{$total} active dealers on GunRack.deals</p>
		</div>
		<a href="{$joinUrl}" class="ipsButton ipsButton--primary">
			<i class="fa-solid fa-store" aria-hidden="true"></i>
			<span>Become a Dealer</span>
		</a>
	</div>

	<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;margin-bottom:24px">
		<form method="get" action="{$directoryUrl}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
			<div style="flex:1 1 200px">
				<label style="display:block;font-size:0.8em;font-weight:600;color:#666;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em">Search</label>
				<input type="text" name="search" value="{$search}" placeholder="Search dealers..." class="ipsInput ipsInput--text" style="width:100%;box-sizing:border-box">
			</div>
			<div style="flex:0 1 160px">
				<label style="display:block;font-size:0.8em;font-weight:600;color:#666;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em">Tier</label>
				<select name="tier" class="ipsInput ipsInput--select" style="width:100%">
					<option value="">All Tiers</option>
					<option value="founding" {{if $tier === 'founding'}}selected{{endif}}>Founding</option>
					<option value="enterprise" {{if $tier === 'enterprise'}}selected{{endif}}>Enterprise</option>
					<option value="pro" {{if $tier === 'pro'}}selected{{endif}}>Pro</option>
					<option value="basic" {{if $tier === 'basic'}}selected{{endif}}>Basic</option>
				</select>
			</div>
			<div style="flex:0 1 160px">
				<label style="display:block;font-size:0.8em;font-weight:600;color:#666;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em">Sort By</label>
				<select name="sort" class="ipsInput ipsInput--select" style="width:100%">
					<option value="rating" {{if $sort === 'rating'}}selected{{endif}}>Highest Rated</option>
					<option value="listings" {{if $sort === 'listings'}}selected{{endif}}>Most Listings</option>
					<option value="newest" {{if $sort === 'newest'}}selected{{endif}}>Newest</option>
					<option value="alpha" {{if $sort === 'alpha'}}selected{{endif}}>A&ndash;Z</option>
				</select>
			</div>
			<div>
				<button type="submit" class="ipsButton ipsButton--primary">
					<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
					<span>Filter</span>
				</button>
				{{if $search || $tier}}
				<a href="{$directoryUrl}" class="ipsButton ipsButton--normal" style="margin-left:8px">Clear</a>
				{{endif}}
			</div>
		</form>
	</div>

	{{if count($dealers) === 0}}
	<div style="text-align:center;padding:64px 24px;color:#9ca3af">
		<i class="fa-solid fa-store-slash" style="font-size:3em;margin-bottom:16px;display:block" aria-hidden="true"></i>
		<h3 style="margin:0 0 8px;color:#374151">No dealers found</h3>
		<p style="margin:0">Try adjusting your filters or search terms.</p>
	</div>
	{{else}}
	<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin-bottom:32px">
		{{foreach $dealers as $d}}
		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow 0.2s">

			<div style="padding:20px;display:flex;align-items:center;gap:14px;border-bottom:1px solid var(--i-border-color,#f0f0f0)">
				<a href="{$d['profile_url']}" style="flex-shrink:0">
					<span class="ipsUserPhoto ipsUserPhoto--medium">
						<img src="{$d['avatar']}" alt="" loading="lazy">
					</span>
				</a>
				<div style="flex:1;min-width:0">
					<a href="{$d['profile_url']}" style="text-decoration:none;color:inherit">
						<h3 style="margin:0 0 4px;font-size:1em;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{$d['dealer_name']}</h3>
					</a>
					<span style="background:{$d['tier_color']};color:#fff;padding:2px 8px;border-radius:20px;font-size:0.72em;font-weight:700;text-transform:uppercase;letter-spacing:0.04em">{$d['tier_label']}</span>
				</div>
				<div style="text-align:center;flex-shrink:0">
					<div style="font-size:1.4em;font-weight:800;color:{$d['rating_color']};line-height:1">{$d['avg_overall']}</div>
					<div style="font-size:0.7em;color:#9ca3af">/ 5</div>
				</div>
			</div>

			<div style="display:flex;padding:12px 20px;gap:0;border-bottom:1px solid var(--i-border-color,#f0f0f0)">
				<div style="flex:1;text-align:center">
					<div style="font-size:1.1em;font-weight:700">{$d['listing_count']}</div>
					<div style="font-size:0.72em;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">Listings</div>
				</div>
				<div style="flex:1;text-align:center;border-left:1px solid var(--i-border-color,#f0f0f0)">
					<div style="font-size:1.1em;font-weight:700">{$d['total_reviews']}</div>
					<div style="font-size:0.72em;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">Reviews</div>
				</div>
				<div style="flex:1;text-align:center;border-left:1px solid var(--i-border-color,#f0f0f0)">
					<div style="font-size:0.85em;font-weight:600">{$d['member_since']}</div>
					<div style="font-size:0.72em;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">Member Since</div>
				</div>
			</div>

			<div style="padding:12px 16px;display:flex;gap:8px;margin-top:auto">
				<a href="{$d['profile_url']}" class="ipsButton ipsButton--primary ipsButton--small" style="flex:1;text-align:center;justify-content:center">
					<i class="fa-solid fa-store" aria-hidden="true"></i>
					<span>View Profile</span>
				</a>
				{{if $loggedIn}}
				<a href="{$d['follow_url']}" class="ipsButton ipsButton--small {{if $d['is_following']}}ipsButton--primary{{else}}ipsButton--normal{{endif}}" title="{{if $d['is_following']}}Unfollow{{else}}Follow{{endif}} this dealer">
					<i class="fa-solid {{if $d['is_following']}}fa-bell-slash{{else}}fa-bell{{endif}}" aria-hidden="true"></i>
				</a>
				{{endif}}
			</div>
		</div>
		{{endforeach}}
	</div>

	{$pagination|raw}
	{{endif}}

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

/* Backfill missing dealer slugs. Existing rows from earlier installs may have
   dealer_slug IS NULL; generate a URL-safe slug from dealer_name using the
   same algorithm as manualOnboard() in modules/admin/dealers/dealers.php.
   Uniqueness is enforced by the uq_dealer_slug index — append -1, -2, ...
   until a free slug is found. */
try
{
	foreach ( \IPS\Db::i()->select( 'dealer_id, dealer_name', 'gd_dealer_feed_config',
		[ 'dealer_slug IS NULL' ] ) as $row )
	{
		$slug = strtolower( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $row['dealer_name'] ) ) );
		$slug = trim( $slug, '-' );
		if ( $slug === '' )
		{
			$slug = 'dealer-' . (int) $row['dealer_id'];
		}
		$base = $slug;
		$i    = 1;
		while ( (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config',
			[ 'dealer_slug=?', $slug ] )->first() > 0 )
		{
			$slug = $base . '-' . $i++;
		}
		\IPS\Db::i()->update( 'gd_dealer_feed_config',
			[ 'dealer_slug' => $slug ],
			[ 'dealer_id=?', (int) $row['dealer_id'] ] );
	}
}
catch ( \Exception ) {}

/* Schema migration: add dealer_dashboard_prefs column on upgrade installs.
   On fresh installs schema.json creates it; this guards re-installs over
   an older copy of the table. */
try
{
	$cols = \IPS\Db::i()->getTableDefinition( 'gd_dealer_feed_config' );
	if ( !isset( $cols['columns']['dealer_dashboard_prefs'] ) )
	{
		\IPS\Db::i()->addColumn( 'gd_dealer_feed_config', [
			'name'       => 'dealer_dashboard_prefs',
			'type'       => 'TEXT',
			'length'     => null,
			'allow_null' => true,
			'default'    => null,
		] );
	}
}
catch ( \Exception ) {}

/* Force furl + applications cache rebuild so new routes/templates appear
   without a manual cache flush. */
unset( \IPS\Data\Store::i()->furl_configuration );
unset( \IPS\Data\Store::i()->applications );
