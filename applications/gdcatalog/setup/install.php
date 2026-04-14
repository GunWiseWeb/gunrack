<?php
/**
 * @brief       GD Master Catalog — Install routine
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Runs after schema.json tables are created.
 * Seeds the six distributor feed records and the category taxonomy.
 */

/* Seed the six distributor feed records with priority hierarchy (Section 2.2.1) */
$distributors = [
	['feed_name' => 'RSR Group Primary',        'distributor' => 'rsr_group',     'priority' => 1],
	['feed_name' => 'Sports South Primary',      'distributor' => 'sports_south',  'priority' => 2],
	['feed_name' => "Davidson's Primary",        'distributor' => 'davidsons',     'priority' => 3],
	['feed_name' => "Lipsey's Primary",          'distributor' => 'lipseys',       'priority' => 4],
	['feed_name' => 'Zanders Sporting Goods',    'distributor' => 'zanders',       'priority' => 5],
	['feed_name' => 'Bill Hicks Primary',        'distributor' => 'bill_hicks',    'priority' => 6],
];

/* Default conflict detection fields per Section 2.11.2 */
$defaultConflictFields = json_encode([
	'restricted_states' => true,
	'nfa_item'          => true,
	'requires_ffl'      => true,
	'caliber'           => true,
	'rounds_per_box'    => true,
	'category'          => false,
	'manufacturer'      => false,
	'description'       => false,
]);

foreach ( $distributors as $dist )
{
	\IPS\Db::i()->insert( 'gd_distributor_feeds', [
		'feed_name'                => $dist['feed_name'],
		'distributor'              => $dist['distributor'],
		'priority'                 => $dist['priority'],
		'feed_url'                 => '',
		'feed_format'              => 'xml',
		'auth_type'                => 'none',
		'auth_credentials'         => NULL,
		'field_mapping'            => NULL,
		'category_mapping'         => NULL,
		'import_schedule'          => '6hr',
		'conflict_detection_fields'=> $defaultConflictFields,
		'active'                   => 0,
		'last_run'                 => NULL,
		'last_record_count'        => 0,
		'last_run_status'          => NULL,
	]);
}

/* Seed category taxonomy (Section 2.4) */
$categories = [
	'Handguns'               => ['Pistols', 'Revolvers', 'Derringers'],
	'Rifles'                 => ['Semi-Automatic', 'Bolt-Action', 'Lever-Action', 'Single-Shot', 'Muzzleloaders'],
	'Shotguns'               => ['Semi-Automatic', 'Pump-Action', 'Break-Action', 'Over/Under', 'Side-by-Side'],
	'Ammunition'             => ['Handgun Ammo', 'Rifle Ammo', 'Shotgun Ammo', 'Rimfire', 'Specialty/Exotic'],
	'NFA Items'              => ['Suppressors', 'Short-Barreled Rifles', 'Short-Barreled Shotguns', 'Machine Guns', 'AOW'],
	'Magazines'              => ['Handgun', 'Rifle', 'Shotgun', 'Drum'],
	'Optics'                 => ['Red Dots', 'Rifle Scopes', 'LPVOs', 'Prism Scopes', 'Night Vision', 'Thermal', 'Magnifiers'],
	'Parts & Accessories'    => ['Barrels', 'Triggers', 'Stocks', 'Grips', 'Rails', 'Handguards', 'Muzzle Devices'],
	'Holsters & Carry'       => ['IWB', 'OWB', 'Shoulder', 'Ankle', 'Appendix', 'Duty', 'Vehicle'],
	'Storage & Safety'       => ['Gun Safes', 'Hard Cases', 'Soft Cases', 'Lock Boxes', 'Trigger Locks'],
	'Cleaning & Maintenance' => ['Cleaning Kits', 'Lubricants', 'Solvents', 'Bore Snakes', 'Patches'],
	'Tactical Gear'          => ['Weapon Lights', 'Lasers', 'Bipods', 'Slings', 'Foregrips', 'Vertical Grips'],
	'Hunting Gear'           => ['Game Calls', 'Scent Control', 'Blinds', 'Feeders', 'Trail Cameras'],
];

$position = 0;
foreach ( $categories as $parentName => $children )
{
	$slug = mb_strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $parentName ) );
	$slug = trim( $slug, '-' );

	$parentId = \IPS\Db::i()->insert( 'gd_categories', [
		'parent_id'     => 0,
		'name'          => $parentName,
		'slug'          => $slug,
		'position'      => $position++,
		'product_count' => 0,
	]);

	$childPos = 0;
	foreach ( $children as $childName )
	{
		$childSlug = $slug . '-' . mb_strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $childName ) );
		$childSlug = trim( $childSlug, '-' );

		\IPS\Db::i()->insert( 'gd_categories', [
			'parent_id'     => $parentId,
			'name'          => $childName,
			'slug'          => $childSlug,
			'position'      => $childPos++,
			'product_count' => 0,
		]);
	}
}

/* Seed templates directly into core_theme_templates to bypass IPS XML import bug
 * that corrupts {{-- comments --}} during theme.xml installation. */
$gdcatalogTemplates = [
	[
		'template_name' => 'feedList',
		'template_data' => '$feeds',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h2 class="ipsBox_title">{lang="gdcatalog_feeds_title"}</h2>

	<div class="ipsTable ipsTable_zebra">
		<div class="ipsTable_header">
			<div class="ipsTable_row">
				<div class="ipsTable_cell" style="width:5%">#</div>
				<div class="ipsTable_cell" style="width:18%">{lang="gdcatalog_feed_name"}</div>
				<div class="ipsTable_cell" style="width:14%">{lang="gdcatalog_feed_distributor"}</div>
				<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_feed_format"}</div>
				<div class="ipsTable_cell" style="width:10%">{lang="gdcatalog_feed_schedule"}</div>
				<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_feed_active"}</div>
				<div class="ipsTable_cell" style="width:14%">{lang="gdcatalog_feed_last_run"}</div>
				<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_feed_last_count"}</div>
				<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_feed_last_status"}</div>
				<div class="ipsTable_cell" style="width:7%"></div>
			</div>
		</div>

		{{foreach $feeds as $feed}}
		<div class="ipsTable_row">
			<div class="ipsTable_cell">{$feed->priority}</div>
			<div class="ipsTable_cell"><strong>{$feed->feed_name}</strong></div>
			<div class="ipsTable_cell">{lang="gdcatalog_dist_{$feed->distributor}"}</div>
			<div class="ipsTable_cell">{expression="strtoupper( $feed->feed_format )"}</div>
			<div class="ipsTable_cell">{$feed->import_schedule}</div>
			<div class="ipsTable_cell">
				{{if $feed->active}}
					<span class="ipsBadge ipsBadge--positive">{lang="gdcatalog_feed_active"}</span>
				{{else}}
					<span class="ipsBadge ipsBadge--neutral">Inactive</span>
				{{endif}}
			</div>
			<div class="ipsTable_cell">
				{{if $feed->last_run}}
					{datetime="$feed->last_run"}
				{{else}}
					&mdash;
				{{endif}}
			</div>
			<div class="ipsTable_cell">{expression="number_format( $feed->last_record_count )"}</div>
			<div class="ipsTable_cell">
				{{if $feed->last_run_status === 'completed'}}
					<span class="ipsBadge ipsBadge--positive">OK</span>
				{{elseif $feed->last_run_status === 'failed'}}
					<span class="ipsBadge ipsBadge--negative">Failed</span>
				{{elseif $feed->last_run_status === 'running'}}
					<span class="ipsBadge ipsBadge--warning">Running</span>
				{{else}}
					&mdash;
				{{endif}}
			</div>
			<div class="ipsTable_cell">
				<a href="{url="app=gdcatalog&module=catalog&controller=feeds&do=edit&id={$feed->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--primary">
					Edit
				</a>
			</div>
		</div>
		{{endforeach}}
	</div>

	<div class="ipsBox_content ipsPad">
		<p class="ipsType_light">
			{lang="gdcatalog_feeds_help"}
		</p>
	</div>
</div>
TEMPLATE_EOT,
	],
	[
		'template_name' => 'conflictLog',
		'template_data' => '$entries, $filterField, $filterSource, $filterRule, $filterUpc, $total, $pagination',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h2 class="ipsBox_title">{lang="gdcatalog_conflicts_title"} ({expression="number_format( $total )"})</h2>

	{{-- Filter bar --}}
	<form method="get" action="{url="app=gdcatalog&module=catalog&controller=conflicts"}" class="ipsPad ipsGap_2">
		<input type="text" name="upc" value="{$filterUpc}" placeholder="UPC" class="ipsField_text" style="width:150px">
		<input type="text" name="field" value="{$filterField}" placeholder="Field name" class="ipsField_text" style="width:150px">
		<input type="text" name="source" value="{$filterSource}" placeholder="Distributor" class="ipsField_text" style="width:150px">
		<select name="rule" class="ipsField_select">
			<option value="">All rules</option>
			<option value="priority" {{if $filterRule === 'priority'}}selected{{endif}}>Priority</option>
			<option value="longest" {{if $filterRule === 'longest'}}selected{{endif}}>Longest</option>
			<option value="highest_res" {{if $filterRule === 'highest_res'}}selected{{endif}}>Highest Res</option>
			<option value="highest_val" {{if $filterRule === 'highest_val'}}selected{{endif}}>Highest Val</option>
			<option value="flagged_for_review" {{if $filterRule === 'flagged_for_review'}}selected{{endif}}>Flagged</option>
			<option value="any_true" {{if $filterRule === 'any_true'}}selected{{endif}}>Any True</option>
			<option value="admin_override" {{if $filterRule === 'admin_override'}}selected{{endif}}>Admin Override</option>
		</select>
		<button type="submit" class="ipsButton ipsButton--primary ipsButton--small">Filter</button>
	</form>

	{{-- Log table --}}
	<div class="ipsTable ipsTable_zebra">
		<div class="ipsTable_header">
			<div class="ipsTable_row">
				<div class="ipsTable_cell" style="width:10%">{lang="gdcatalog_conflict_upc"}</div>
				<div class="ipsTable_cell" style="width:10%">{lang="gdcatalog_conflict_field"}</div>
				<div class="ipsTable_cell" style="width:12%">{lang="gdcatalog_conflict_winner"}</div>
				<div class="ipsTable_cell" style="width:18%">{lang="gdcatalog_conflict_winner_val"}</div>
				<div class="ipsTable_cell" style="width:12%">{lang="gdcatalog_conflict_loser"}</div>
				<div class="ipsTable_cell" style="width:18%">{lang="gdcatalog_conflict_loser_val"}</div>
				<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_conflict_rule"}</div>
				<div class="ipsTable_cell" style="width:12%">{lang="gdcatalog_conflict_date"}</div>
			</div>
		</div>

		{{foreach $entries as $entry}}
		<div class="ipsTable_row">
			<div class="ipsTable_cell"><code>{$entry['upc']}</code></div>
			<div class="ipsTable_cell">{$entry['field_name']}</div>
			<div class="ipsTable_cell">{$entry['winning_source']}</div>
			<div class="ipsTable_cell">{expression="htmlspecialchars( mb_substr( $entry['winning_value'] ?? '', 0, 80 ) )"}</div>
			<div class="ipsTable_cell">{$entry['losing_source']}</div>
			<div class="ipsTable_cell">{expression="htmlspecialchars( mb_substr( $entry['losing_value'] ?? '', 0, 80 ) )"}</div>
			<div class="ipsTable_cell"><span class="ipsBadge ipsBadge--neutral">{$entry['rule_applied']}</span></div>
			<div class="ipsTable_cell">{$entry['resolved_at']}</div>
		</div>
		{{endforeach}}

		{{if count( $entries ) === 0}}
		<div class="ipsTable_row"><div class="ipsTable_cell" colspan="8">No conflict log entries found.</div></div>
		{{endif}}
	</div>

	<div class="ipsPad">{$pagination}</div>
</div>
TEMPLATE_EOT,
	],
	[
		'template_name' => 'productEdit',
		'template_data' => '$product, $locks, $formHtml',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h2 class="ipsBox_title">{$product->title} <span class="ipsType_light">({$product->upc})</span></h2>

	<div class="ipsBox_content">

		{{-- Product meta --}}
		<div class="ipsPad ipsType_light">
			<strong>Primary source:</strong> {$product->primary_source} |
			<strong>Sources:</strong> {$product->distributor_sources} |
			<strong>Last updated:</strong> {$product->last_updated} |
			<strong>Status:</strong>
			{{if $product->record_status === 'active'}}
				<span class="ipsBadge ipsBadge--positive">Active</span>
			{{elseif $product->record_status === 'admin_review'}}
				<span class="ipsBadge ipsBadge--warning">Admin Review</span>
			{{else}}
				<span class="ipsBadge ipsBadge--neutral">{$product->record_status}</span>
			{{endif}}
		</div>

		{{-- Locked fields summary --}}
		{{if count( $locks ) > 0}}
		<div class="ipsMessage ipsMessage--info ipsPad">
			<strong>{lang="gdcatalog_product_locked_fields"}:</strong>
			{{foreach $locks as $lock}}
				<span class="ipsBadge {{if $lock->isHardLock()}}ipsBadge--negative{{else}}ipsBadge--warning{{endif}}">
					{$lock->field_name}
					{{if $lock->isHardLock()}} (hard){{else}} (dist){{endif}}
				</span>
			{{endforeach}}
		</div>
		{{endif}}

		{{-- Edit form --}}
		{$formHtml}

	</div>
</div>
TEMPLATE_EOT,
	],
	[
		'template_name' => 'compliancePanel',
		'template_data' => '$tab, $counts, $pendingFlags, $pendingConflicts, $allLocks, $adminFlags',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h2 class="ipsBox_title">{lang="gdcatalog_compliance_title"}</h2>

	{{-- Tab navigation --}}
	<div class="ipsTabs" data-ipsTabBar>
		<a href="{url="app=gdcatalog&module=catalog&controller=compliance&tab=new"}" class="ipsTabs_item {{if $tab === 'new'}}ipsTabs_activeItem{{endif}}">
			{lang="gdcatalog_compliance_tab_new"} ({$counts['new']})
		</a>
		<a href="{url="app=gdcatalog&module=catalog&controller=compliance&tab=conflicts"}" class="ipsTabs_item {{if $tab === 'conflicts'}}ipsTabs_activeItem{{endif}}">
			{lang="gdcatalog_compliance_tab_conflicts"} ({$counts['conflicts']})
		</a>
		<a href="{url="app=gdcatalog&module=catalog&controller=compliance&tab=locks"}" class="ipsTabs_item {{if $tab === 'locks'}}ipsTabs_activeItem{{endif}}">
			{lang="gdcatalog_compliance_tab_locks"} ({$counts['locks']})
		</a>
		<a href="{url="app=gdcatalog&module=catalog&controller=compliance&tab=admin"}" class="ipsTabs_item {{if $tab === 'admin'}}ipsTabs_activeItem{{endif}}">
			{lang="gdcatalog_compliance_tab_admin"} ({$counts['admin']})
		</a>
	</div>

	<div class="ipsBox_content">

		{{-- Tab: New Restrictions --}}
		{{if $tab === 'new'}}
		<div class="ipsTable ipsTable_zebra">
			<div class="ipsTable_header">
				<div class="ipsTable_row">
					<div class="ipsTable_cell" style="width:12%">UPC</div>
					<div class="ipsTable_cell" style="width:15%">Type</div>
					<div class="ipsTable_cell" style="width:20%">Value</div>
					<div class="ipsTable_cell" style="width:15%">Distributor</div>
					<div class="ipsTable_cell" style="width:15%">First Seen</div>
					<div class="ipsTable_cell" style="width:23%">Actions</div>
				</div>
			</div>
			{{foreach $pendingFlags as $flag}}
			<div class="ipsTable_row">
				<div class="ipsTable_cell"><code>{$flag->upc}</code></div>
				<div class="ipsTable_cell">{$flag->flag_type}</div>
				<div class="ipsTable_cell"><strong>{$flag->flag_value}</strong></div>
				<div class="ipsTable_cell">{$flag->distributor_id}</div>
				<div class="ipsTable_cell">{$flag->first_seen_at}</div>
				<div class="ipsTable_cell">
					<a href="{url="app=gdcatalog&module=catalog&controller=compliance&do=approve&id={$flag->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--positive">{lang="gdcatalog_compliance_approve"}</a>
					<a href="{url="app=gdcatalog&module=catalog&controller=compliance&do=reject&id={$flag->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--negative">{lang="gdcatalog_compliance_reject"}</a>
				</div>
			</div>
			{{endforeach}}
			{{if count( $pendingFlags ) === 0}}
			<div class="ipsTable_row"><div class="ipsTable_cell" colspan="6">No pending restrictions.</div></div>
			{{endif}}
		</div>
		{{endif}}

		{{-- Tab: Feed Conflicts --}}
		{{if $tab === 'conflicts'}}
		<div class="ipsTable ipsTable_zebra">
			<div class="ipsTable_header">
				<div class="ipsTable_row">
					<div class="ipsTable_cell" style="width:10%">UPC</div>
					<div class="ipsTable_cell" style="width:12%">Field</div>
					<div class="ipsTable_cell" style="width:15%">Current</div>
					<div class="ipsTable_cell" style="width:15%">Incoming</div>
					<div class="ipsTable_cell" style="width:10%">Auto-resolve</div>
					<div class="ipsTable_cell" style="width:38%">Actions</div>
				</div>
			</div>
			{{foreach $pendingConflicts as $conflict}}
			<div class="ipsTable_row">
				<div class="ipsTable_cell"><code>{$conflict->upc}</code></div>
				<div class="ipsTable_cell">{$conflict->field_name}</div>
				<div class="ipsTable_cell">{expression="htmlspecialchars( mb_substr( $conflict->current_value, 0, 60 ) )"}</div>
				<div class="ipsTable_cell">{expression="htmlspecialchars( mb_substr( $conflict->incoming_value, 0, 60 ) )"}</div>
				<div class="ipsTable_cell">{$conflict->auto_resolve_at}</div>
				<div class="ipsTable_cell">
					<a href="{url="app=gdcatalog&module=catalog&controller=compliance&do=acceptConflict&id={$conflict->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--positive">{lang="gdcatalog_compliance_accept_incoming"}</a>
					<a href="{url="app=gdcatalog&module=catalog&controller=compliance&do=keepConflict&id={$conflict->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--warning">{lang="gdcatalog_compliance_keep_existing"}</a>
					<a href="{url="app=gdcatalog&module=catalog&controller=compliance&do=customConflict&id={$conflict->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--normal">{lang="gdcatalog_compliance_set_custom"}</a>
				</div>
			</div>
			{{endforeach}}
			{{if count( $pendingConflicts ) === 0}}
			<div class="ipsTable_row"><div class="ipsTable_cell" colspan="6">No pending feed conflicts.</div></div>
			{{endif}}
		</div>
		{{endif}}

		{{-- Tab: Locked Fields --}}
		{{if $tab === 'locks'}}
		<div class="ipsTable ipsTable_zebra">
			<div class="ipsTable_header">
				<div class="ipsTable_row">
					<div class="ipsTable_cell" style="width:10%">UPC</div>
					<div class="ipsTable_cell" style="width:12%">Field</div>
					<div class="ipsTable_cell" style="width:15%">Locked Value</div>
					<div class="ipsTable_cell" style="width:10%">Type</div>
					<div class="ipsTable_cell" style="width:20%">Reason</div>
					<div class="ipsTable_cell" style="width:12%">Locked At</div>
					<div class="ipsTable_cell" style="width:11%">Actions</div>
				</div>
			</div>
			{{foreach $allLocks as $lock}}
			<div class="ipsTable_row">
				<div class="ipsTable_cell"><code>{$lock->upc}</code></div>
				<div class="ipsTable_cell">{$lock->field_name}</div>
				<div class="ipsTable_cell">{expression="htmlspecialchars( mb_substr( $lock->locked_value, 0, 60 ) )"}</div>
				<div class="ipsTable_cell">
					{{if $lock->isHardLock()}}
						<span class="ipsBadge ipsBadge--negative">{lang="gdcatalog_lock_type_hard"}</span>
					{{else}}
						<span class="ipsBadge ipsBadge--warning">{lang="gdcatalog_lock_type_distributor"}</span>
					{{endif}}
				</div>
				<div class="ipsTable_cell">{expression="htmlspecialchars( mb_substr( $lock->lock_reason, 0, 80 ) )"}</div>
				<div class="ipsTable_cell">{$lock->locked_at}</div>
				<div class="ipsTable_cell">
					<a href="{url="app=gdcatalog&module=catalog&controller=compliance&do=unlock&id={$lock->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--negative" data-confirm>{lang="gdcatalog_lock_unlock"}</a>
				</div>
			</div>
			{{endforeach}}
			{{if count( $allLocks ) === 0}}
			<div class="ipsTable_row"><div class="ipsTable_cell" colspan="7">No locked fields.</div></div>
			{{endif}}
		</div>
		{{endif}}

		{{-- Tab: Admin Restrictions --}}
		{{if $tab === 'admin'}}
		<div class="ipsPad">
			<a href="{url="app=gdcatalog&module=catalog&controller=compliance&do=addRestriction" csrf="true"}" class="ipsButton ipsButton--primary ipsButton--small">Add State Restriction</a>
		</div>
		<div class="ipsTable ipsTable_zebra">
			<div class="ipsTable_header">
				<div class="ipsTable_row">
					<div class="ipsTable_cell" style="width:12%">UPC</div>
					<div class="ipsTable_cell" style="width:12%">Scope</div>
					<div class="ipsTable_cell" style="width:12%">Type</div>
					<div class="ipsTable_cell" style="width:20%">Value</div>
					<div class="ipsTable_cell" style="width:12%">Set By</div>
					<div class="ipsTable_cell" style="width:15%">Date</div>
					<div class="ipsTable_cell" style="width:17%">Source</div>
				</div>
			</div>
			{{foreach $adminFlags as $flag}}
			<div class="ipsTable_row">
				<div class="ipsTable_cell"><code>{$flag->upc}</code></div>
				<div class="ipsTable_cell">{{if $flag->listing_id}}Listing{{else}}Product{{endif}}</div>
				<div class="ipsTable_cell">{$flag->flag_type}</div>
				<div class="ipsTable_cell"><strong>{$flag->flag_value}</strong></div>
				<div class="ipsTable_cell">{$flag->admin_reviewed_by}</div>
				<div class="ipsTable_cell">{$flag->admin_reviewed_at}</div>
				<div class="ipsTable_cell">{$flag->source}</div>
			</div>
			{{endforeach}}
			{{if count( $adminFlags ) === 0}}
			<div class="ipsTable_row"><div class="ipsTable_cell" colspan="7">No admin-set restrictions.</div></div>
			{{endif}}
		</div>
		{{endif}}

	</div>
</div>
TEMPLATE_EOT,
	],
	[
		'template_name' => 'dashboard',
		'template_data' => '$totalProducts, $activeProducts, $reviewProducts, $categoryCounts, $distributorStats, $osExists, $osStats, $pendingConflicts, $pendingCompliance, $lockedFields, $reindexQueue',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h2 class="ipsBox_title">{lang="gdcatalog_dash_title"}</h2>
	<div class="ipsBox_content">

		{{-- Summary cards --}}
		<div class="ipsGrid ipsGrid--3">
			<div class="ipsBox ipsBox--alt ipsPad">
				<h3 class="ipsType_sectionTitle">{lang="gdcatalog_dash_total_products"}</h3>
				<div class="ipsType_large">{expression="number_format( $totalProducts )"}</div>
				<div class="ipsType_light">Active: {expression="number_format( $activeProducts )"} | Review: {expression="number_format( $reviewProducts )"}</div>
			</div>
			<div class="ipsBox ipsBox--alt ipsPad">
				<h3 class="ipsType_sectionTitle">{lang="gdcatalog_dash_opensearch_status"}</h3>
				{{if $osExists}}
					<div class="ipsType_large">{expression="number_format( $osStats['doc_count'] )"}</div>
					<div class="ipsType_light">{lang="gdcatalog_dash_opensearch_count"}</div>
				{{else}}
					<div class="ipsType_warning">Index not found</div>
				{{endif}}
			</div>
			<div class="ipsBox ipsBox--alt ipsPad">
				<h3 class="ipsType_sectionTitle">Pending Actions</h3>
				<div class="ipsType_light">
					Compliance flags: <strong>{$pendingCompliance}</strong><br>
					Feed conflicts: <strong>{$pendingConflicts}</strong><br>
					Locked fields: <strong>{$lockedFields}</strong><br>
					Reindex queue: <strong>{$reindexQueue}</strong>
				</div>
			</div>
		</div>

		{{-- Action buttons --}}
		<div class="ipsPad ipsGap_3">
			<a href="{url="app=gdcatalog&module=catalog&controller=dashboard&do=rebuildIndex" csrf="true"}" class="ipsButton ipsButton--primary" data-confirm>
				{lang="gdcatalog_dash_rebuild_index"}
			</a>
			{{if $reindexQueue > 0}}
			<a href="{url="app=gdcatalog&module=catalog&controller=dashboard&do=processQueue" csrf="true"}" class="ipsButton ipsButton--normal">
				Process Reindex Queue ({$reindexQueue})
			</a>
			{{endif}}
		</div>

		{{-- Per-distributor stats --}}
		<h3 class="ipsType_sectionTitle ipsPad_top">{lang="gdcatalog_dash_by_distributor"}</h3>
		<div class="ipsTable ipsTable_zebra">
			<div class="ipsTable_header">
				<div class="ipsTable_row">
					<div class="ipsTable_cell" style="width:5%">#</div>
					<div class="ipsTable_cell" style="width:20%">{lang="gdcatalog_feed_distributor"}</div>
					<div class="ipsTable_cell" style="width:10%">Products</div>
					<div class="ipsTable_cell" style="width:10%">{lang="gdcatalog_feed_active"}</div>
					<div class="ipsTable_cell" style="width:15%">{lang="gdcatalog_dash_last_run"}</div>
					<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_dash_run_status"}</div>
					<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_dash_records_created"}</div>
					<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_dash_records_updated"}</div>
					<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_dash_records_errored"}</div>
					<div class="ipsTable_cell" style="width:8%"></div>
				</div>
			</div>
			{{foreach $distributorStats as $ds}}
			<div class="ipsTable_row">
				<div class="ipsTable_cell">{$ds['feed']->priority}</div>
				<div class="ipsTable_cell"><strong>{$ds['feed']->feed_name}</strong></div>
				<div class="ipsTable_cell">{expression="number_format( $ds['product_count'] )"}</div>
				<div class="ipsTable_cell">
					{{if $ds['feed']->active}}
						<span class="ipsBadge ipsBadge--positive">Active</span>
					{{else}}
						<span class="ipsBadge ipsBadge--neutral">Inactive</span>
					{{endif}}
				</div>
				<div class="ipsTable_cell">
					{{if $ds['last_log']}}
						{$ds['last_log']['run_start']}
					{{else}}
						&mdash;
					{{endif}}
				</div>
				<div class="ipsTable_cell">
					{{if $ds['last_log']}}
						{{if $ds['last_log']['status'] === 'completed'}}
							<span class="ipsBadge ipsBadge--positive">OK</span>
						{{elseif $ds['last_log']['status'] === 'failed'}}
							<span class="ipsBadge ipsBadge--negative">Failed</span>
						{{else}}
							<span class="ipsBadge ipsBadge--warning">Running</span>
						{{endif}}
					{{else}}
						&mdash;
					{{endif}}
				</div>
				<div class="ipsTable_cell">{expression="$ds['last_log'] ? $ds['last_log']['records_created'] : '—'"}</div>
				<div class="ipsTable_cell">{expression="$ds['last_log'] ? $ds['last_log']['records_updated'] : '—'"}</div>
				<div class="ipsTable_cell">{expression="$ds['last_log'] ? $ds['last_log']['records_errored'] : '—'"}</div>
				<div class="ipsTable_cell">
					{{if $ds['feed']->active}}
					<a href="{url="app=gdcatalog&module=catalog&controller=dashboard&do=runImport&id={$ds['feed']->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--primary" data-confirm>
						{lang="gdcatalog_dash_trigger_import"}
					</a>
					{{endif}}
				</div>
			</div>
			{{endforeach}}
		</div>

		{{-- Per-category counts --}}
		<h3 class="ipsType_sectionTitle ipsPad_top">{lang="gdcatalog_dash_by_category"}</h3>
		<div class="ipsTable ipsTable_zebra">
			{{foreach $categoryCounts as $cc}}
			<div class="ipsTable_row">
				<div class="ipsTable_cell" style="width:70%">{$cc['name']}</div>
				<div class="ipsTable_cell" style="width:30%">{expression="number_format( $cc['count'] )"}</div>
			</div>
			{{endforeach}}
		</div>

	</div>
</div>
TEMPLATE_EOT,
	],
	[
		'template_name' => 'productList',
		'template_data' => '$products, $categories, $search, $status, $catId, $total, $pagination',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h2 class="ipsBox_title">{lang="gdcatalog_products_title"} ({expression="number_format( $total )"})</h2>

	{{-- Search/filter bar --}}
	<form method="get" action="{url="app=gdcatalog&module=catalog&controller=products"}" class="ipsPad ipsGap_2">
		<input type="text" name="q" value="{$search}" placeholder="Search UPC, title, or brand..." class="ipsField_text" style="width:300px">
		<select name="status" class="ipsField_select">
			<option value="">All statuses</option>
			<option value="active" {{if $status === 'active'}}selected{{endif}}>Active</option>
			<option value="discontinued" {{if $status === 'discontinued'}}selected{{endif}}>Discontinued</option>
			<option value="admin_review" {{if $status === 'admin_review'}}selected{{endif}}>Admin Review</option>
			<option value="pending" {{if $status === 'pending'}}selected{{endif}}>Pending</option>
		</select>
		<select name="category" class="ipsField_select">
			<option value="0">All categories</option>
			{{foreach $categories as $cat}}
				<option value="{$cat->id}" {{if $catId === (int) $cat->id}}selected{{endif}}>{$cat->name}</option>
			{{endforeach}}
		</select>
		<button type="submit" class="ipsButton ipsButton--primary ipsButton--small">Filter</button>
	</form>

	{{-- Product table --}}
	<div class="ipsTable ipsTable_zebra">
		<div class="ipsTable_header">
			<div class="ipsTable_row">
				<div class="ipsTable_cell" style="width:12%">{lang="gdcatalog_product_upc"}</div>
				<div class="ipsTable_cell" style="width:25%">{lang="gdcatalog_product_title"}</div>
				<div class="ipsTable_cell" style="width:12%">{lang="gdcatalog_product_brand"}</div>
				<div class="ipsTable_cell" style="width:10%">{lang="gdcatalog_product_caliber"}</div>
				<div class="ipsTable_cell" style="width:8%">{lang="gdcatalog_product_msrp"}</div>
				<div class="ipsTable_cell" style="width:10%">{lang="gdcatalog_product_status"}</div>
				<div class="ipsTable_cell" style="width:12%">{lang="gdcatalog_product_primary_source"}</div>
				<div class="ipsTable_cell" style="width:11%"></div>
			</div>
		</div>

		{{foreach $products as $product}}
		<div class="ipsTable_row">
			<div class="ipsTable_cell"><code>{$product->upc}</code></div>
			<div class="ipsTable_cell">{$product->title}</div>
			<div class="ipsTable_cell">{$product->brand}</div>
			<div class="ipsTable_cell">{$product->caliber}</div>
			<div class="ipsTable_cell">{expression="$product->msrp ? '$' . number_format( (float) $product->msrp, 2 ) : '—'"}</div>
			<div class="ipsTable_cell">
				{{if $product->record_status === 'active'}}
					<span class="ipsBadge ipsBadge--positive">{lang="gdcatalog_status_active"}</span>
				{{elseif $product->record_status === 'admin_review'}}
					<span class="ipsBadge ipsBadge--warning">{lang="gdcatalog_status_admin_review"}</span>
				{{elseif $product->record_status === 'discontinued'}}
					<span class="ipsBadge ipsBadge--negative">{lang="gdcatalog_status_discontinued"}</span>
				{{else}}
					<span class="ipsBadge ipsBadge--neutral">{lang="gdcatalog_status_pending"}</span>
				{{endif}}
			</div>
			<div class="ipsTable_cell">{$product->primary_source}</div>
			<div class="ipsTable_cell">
				<a href="{url="app=gdcatalog&module=catalog&controller=products&do=edit&upc={$product->upc}"}" class="ipsButton ipsButton--small ipsButton--primary">Edit</a>
				{{if $product->record_status === 'admin_review'}}
					<a href="{url="app=gdcatalog&module=catalog&controller=products&do=resolveReview&upc={$product->upc}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--positive">Approve</a>
				{{endif}}
			</div>
		</div>
		{{endforeach}}
	</div>

	<div class="ipsPad">{$pagination}</div>
</div>
TEMPLATE_EOT,
	],
];

foreach ( $gdcatalogTemplates as $tpl )
{
	\IPS\Db::i()->insert( 'core_theme_templates', [
		'template_set_id'   => 1,
		'template_app'      => 'gdcatalog',
		'template_location' => 'admin',
		'template_group'    => 'catalog',
		'template_name'     => $tpl['template_name'],
		'template_data'     => $tpl['template_data'],
		'template_content'  => $tpl['template_content'],
	]);
}
