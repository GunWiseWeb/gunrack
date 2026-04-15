<?php
define('SUITE_UNIQUE_KEY', 'test');
chdir('/home/gunrack/domains/gunrack.deals/public_html');
require_once 'init.php';
IPS\IPS::init();

$templates = [
    'dashboard' => [
        'data' => '$totalProducts, $activeProducts, $reviewProducts, $categoryCounts, $distributorStats, $osExists, $osStats, $pendingConflicts, $pendingCompliance, $lockedFields, $reindexQueue',
        'content' => '<div class="ipsBox">
<h1 class="ipsBox_title">GD Master Catalog Dashboard</h1>
<div class="ipsPad">
<div style="display:flex;gap:16px;margin-bottom:24px">
<div class="ipsBox" style="flex:1;padding:16px;text-align:center">
<div style="font-size:2em;font-weight:bold">{expression="number_format($totalProducts)"}</div>
<div>Total Products</div>
<div style="color:#666">Active: {expression="number_format($activeProducts)"} | Review: {expression="number_format($reviewProducts)"}</div>
</div>
<div class="ipsBox" style="flex:1;padding:16px;text-align:center">
<div style="font-size:2em;font-weight:bold">{$pendingConflicts}</div>
<div>Pending Conflicts</div>
</div>
<div class="ipsBox" style="flex:1;padding:16px;text-align:center">
<div style="font-size:2em;font-weight:bold">{$pendingCompliance}</div>
<div>Pending Compliance Flags</div>
</div>
</div>
<h2>Distributor Feeds</h2>
<table class="ipsTable ipsTable_zebra" style="width:100%">
<thead><tr><th>#</th><th>Feed</th><th>Products</th><th>Status</th><th>Last Run</th><th>Last Status</th><th>Action</th></tr></thead>
<tbody>
{{foreach $distributorStats as $ds}}
<tr>
<td>{$ds[\'feed\']->priority}</td>
<td><strong>{$ds[\'feed\']->feed_name}</strong></td>
<td>{expression="number_format($ds[\'product_count\'])"}</td>
<td>{{if $ds[\'feed\']->active}}<span class="ipsBadge ipsBadge--positive">Active</span>{{else}}<span class="ipsBadge ipsBadge--neutral">Inactive</span>{{endif}}</td>
<td>{{if isset($ds[\'last_log\']) and $ds[\'last_log\']}}{$ds[\'last_log\'][\'run_start\']}{{else}}&mdash;{{endif}}</td>
<td>{{if isset($ds[\'last_log\']) and $ds[\'last_log\']}}{$ds[\'last_log\'][\'status\']}{{else}}&mdash;{{endif}}</td>
<td>{{if $ds[\'feed\']->active}}<a href="{url="app=gdcatalog&module=catalog&controller=dashboard&do=runImport&id={$ds[\'feed\']->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--primary">Run Import</a>{{endif}}</td>
</tr>
{{endforeach}}
</tbody>
</table>
</div>
</div>'
    ],
    'feedList' => [
        'data' => '$feeds',
        'content' => '<div class="ipsBox">
<h1 class="ipsBox_title">Feed Configuration</h1>
<div class="ipsPad">
<p>Configure your six distributor feed URLs. Contact RSR Group and Sports South for feed access credentials.</p>
<table class="ipsTable ipsTable_zebra" style="width:100%">
<thead><tr><th>#</th><th>Feed Name</th><th>Distributor</th><th>Format</th><th>URL</th><th>Status</th><th>Schedule</th><th>Last Run</th><th></th></tr></thead>
<tbody>
{{foreach $feeds as $feed}}
<tr>
<td>{$feed->priority}</td>
<td><strong>{$feed->feed_name}</strong></td>
<td>{$feed->distributor}</td>
<td>{expression="strtoupper($feed->feed_format)"}</td>
<td>{{if $feed->feed_url}}{$feed->feed_url}{{else}}<em style="color:#999">Not configured</em>{{endif}}</td>
<td>{{if $feed->active}}<span class="ipsBadge ipsBadge--positive">Active</span>{{else}}<span class="ipsBadge ipsBadge--neutral">Inactive</span>{{endif}}</td>
<td>{$feed->import_schedule}</td>
<td>{{if $feed->last_run}}{$feed->last_run}{{else}}&mdash;{{endif}}</td>
<td><a href="{url="app=gdcatalog&module=catalog&controller=feeds&do=edit&id={$feed->id}" csrf="true"}" class="ipsButton ipsButton--small ipsButton--primary">Edit</a></td>
</tr>
{{endforeach}}
</tbody>
</table>
</div>
</div>'
    ],
    'productList' => [
        'data' => '$products, $categories, $search, $status, $catId, $total, $pagination',
        'content' => '<div class="ipsBox">
<h1 class="ipsBox_title">Products ({expression="number_format($total)"})</h1>
<div class="ipsPad">
<p>Total products in catalog. Configure distributor feeds and run imports to populate.</p>
</div>
</div>'
    ],
    'conflictLog' => [
        'data' => '$entries, $filterField, $filterSource, $filterRule, $filterUpc, $total, $pagination',
        'content' => '<div class="ipsBox">
<h1 class="ipsBox_title">Conflict Log ({expression="number_format($total)"})</h1>
<div class="ipsPad">
<p>Feed conflicts appear here when distributors provide different values for the same product field.</p>
</div>
</div>'
    ],
    'compliancePanel' => [
        'data' => '$tab, $counts, $pendingFlags, $pendingConflicts, $allLocks, $adminFlags',
        'content' => '<div class="ipsBox">
<h1 class="ipsBox_title">Compliance Review</h1>
<div class="ipsPad">
<p>State shipping restrictions and compliance flags appear here for admin review.</p>
</div>
</div>'
    ],
    'productEdit' => [
        'data' => '$product, $form',
        'content' => '<div class="ipsBox">
<h1 class="ipsBox_title">Edit Product</h1>
<div class="ipsPad">
{$form}
</div>
</div>'
    ],
];

foreach( $templates as $name => $tpl ) {
    IPS\Db::i()->update(
        'core_theme_templates',
        ['template_content' => $tpl['content'], 'template_data' => $tpl['data']],
        ['template_app=? AND template_name=?', 'gdcatalog', $name]
    );
    echo "Updated: $name\n";
}
echo "Done\n";
