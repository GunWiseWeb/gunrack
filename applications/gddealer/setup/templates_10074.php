<?php
namespace IPS\gddealer\setup;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

$gddealerV10074Templates = [];

/* listings */
$gddealerV10074Templates[] = [
    'set_id'            => 1,
    'app'               => 'gddealer',
    'location'          => 'front',
    'group'             => 'dealers',
    'template_name'     => 'listings',
    'template_data'     => '$data',
    'template_content'  => <<<'TEMPLATE_EOT'
<div class="gdPageHeader">
    <div class="gdPageHeader__titleBlock">
        <h1 class="gdPageHeader__title">Listings</h1>
        <p class="gdPageHeader__sub">{expression="number_format($data['total'])"} products synced from your feed</p>
    </div>
    <div class="gdPageHeader__actions">
        <a href="{$data['export_url']}" class="gdBtn gdBtn--secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
        <a href="{$data['tab_urls']['feedSettings']}" class="gdBtn gdBtn--primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"/></svg>
            Run import now
        </a>
    </div>
</div>

<form method="get" class="gdFilterBar">
    <input type="hidden" name="app" value="gddealer">
    <input type="hidden" name="module" value="dealers">
    <input type="hidden" name="controller" value="dashboard">
    <input type="hidden" name="do" value="listings">
    {{if $data['active_filter'] !== 'all'}}<input type="hidden" name="filter" value="{$data['active_filter']}">{{endif}}
    <div class="gdFilterBar__search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" placeholder="Search by UPC" value="{$data['search']}">
    </div>
    <div class="gdFilterTabs">
        <a href="{$data['filter_urls']['all']}" class="gdFilterTab {expression="$data['active_filter'] === 'all' ? 'is-active' : ''"}">
            All <span class="gdFilterTab__count">{expression="number_format($data['status_counts']['all'])"}</span>
        </a>
        <a href="{$data['filter_urls']['active']}" class="gdFilterTab {expression="$data['active_filter'] === 'active' ? 'is-active' : ''"}">
            Active <span class="gdFilterTab__count">{expression="number_format($data['status_counts']['active'])"}</span>
        </a>
        <a href="{$data['filter_urls']['out_of_stock']}" class="gdFilterTab {expression="$data['active_filter'] === 'out_of_stock' ? 'is-active' : ''"}">
            Out of stock <span class="gdFilterTab__count">{expression="number_format($data['status_counts']['out_of_stock'])"}</span>
        </a>
        {{if $data['status_counts']['suspended'] > 0}}
        <a href="{$data['filter_urls']['suspended']}" class="gdFilterTab {expression="$data['active_filter'] === 'suspended' ? 'is-active' : ''"}">
            Suspended <span class="gdFilterTab__count">{expression="number_format($data['status_counts']['suspended'])"}</span>
        </a>
        {{endif}}
        {{if $data['status_counts']['discontinued'] > 0}}
        <a href="{$data['filter_urls']['discontinued']}" class="gdFilterTab {expression="$data['active_filter'] === 'discontinued' ? 'is-active' : ''"}">
            Discontinued <span class="gdFilterTab__count">{expression="number_format($data['status_counts']['discontinued'])"}</span>
        </a>
        {{endif}}
    </div>
</form>

<div class="gdPanel gdPanel--tableShell">
    {{if count($data['rows']) === 0}}
    <div class="gdEmptyState">
        <p style="margin:0;color:var(--gd-text-subtle);font-size:14px">
            {{if $data['search'] !== '' or $data['active_filter'] !== 'all'}}
            No listings match your filters. <a href="{$data['filter_urls']['all']}" style="color:var(--gd-brand)">Clear filters &rarr;</a>
            {{else}}
            No listings yet. <a href="{$data['tab_urls']['feedSettings']}" style="color:var(--gd-brand)">Configure your feed &rarr;</a>
            {{endif}}
        </p>
    </div>
    {{else}}
    <table class="gdListingsTable">
        <thead>
            <tr>
                <th>UPC</th>
                <th class="is-num">Price</th>
                <th>Condition</th>
                <th>Status</th>
                <th class="is-num">Last updated</th>
            </tr>
        </thead>
        <tbody>
            {{foreach $data['rows'] as $row}}
            <tr>
                <td data-label="UPC"><span class="gdListingsTable__upc">{$row['upc']}</span></td>
                <td class="is-num" data-label="Price">{$row['dealer_price']}</td>
                <td data-label="Condition">{expression="ucfirst($row['condition'] ?: 'new')"}</td>
                <td data-label="Status">
                    {{if $row['listing_status'] === 'active' and $row['in_stock']}}
                    <span class="gdStatusPill gdStatusPill--active">In stock</span>
                    {{elseif $row['listing_status'] === 'active'}}
                    <span class="gdStatusPill gdStatusPill--outofstock">Out of stock</span>
                    {{elseif $row['listing_status'] === 'suspended'}}
                    <span class="gdStatusPill gdStatusPill--suspended">Suspended</span>
                    {{elseif $row['listing_status'] === 'discontinued'}}
                    <span class="gdStatusPill gdStatusPill--discontinued">Discontinued</span>
                    {{else}}
                    <span class="gdStatusPill gdStatusPill--muted">{expression="ucfirst($row['listing_status'])"}</span>
                    {{endif}}
                </td>
                <td class="is-num" data-label="Last updated" style="font-size:12px;color:var(--gd-text-subtle)">{$row['last_updated']}</td>
            </tr>
            {{endforeach}}
        </tbody>
    </table>
    {{endif}}
</div>

{{if $data['pages'] > 1}}
<div class="gdPagination">
    <div class="gdPagination__info">
        Showing {expression="number_format((($data['page'] - 1) * $data['per_page']) + 1)"}&ndash;{expression="number_format(min($data['page'] * $data['per_page'], $data['total']))"} of {expression="number_format($data['total'])"}
    </div>
    <div class="gdPagination__controls">
        {{if $data['page'] > 1}}
        <a href="{$data['base_url']}&page={expression="$data['page'] - 1"}" class="gdBtn gdBtn--secondary gdBtn--sm">Previous</a>
        {{endif}}
        <span class="gdPagination__page">Page {$data['page']} of {$data['pages']}</span>
        {{if $data['page'] < $data['pages']}}
        <a href="{$data['base_url']}&page={expression="$data['page'] + 1"}" class="gdBtn gdBtn--secondary gdBtn--sm">Next</a>
        {{endif}}
    </div>
</div>
{{endif}}
TEMPLATE_EOT
];

/* unmatched */
$gddealerV10074Templates[] = [
    'set_id'            => 1,
    'app'               => 'gddealer',
    'location'          => 'front',
    'group'             => 'dealers',
    'template_name'     => 'unmatched',
    'template_data'     => '$data',
    'template_content'  => <<<'TEMPLATE_EOT'
<div class="gdPageHeader">
    <div class="gdPageHeader__titleBlock">
        <h1 class="gdPageHeader__title">
            Unmatched UPCs
            {{if $data['total'] > 0}}
            <span class="gdPageHeader__titleBadge">{expression="number_format($data['total'])"}</span>
            {{endif}}
        </h1>
        <p class="gdPageHeader__sub">UPCs from your feed that don't match our master catalog. We'll automatically re-match as our catalog grows.</p>
    </div>
    <div class="gdPageHeader__actions">
        {{if $data['total'] > 0}}
        <a href="{$data['export_url']}" class="gdBtn gdBtn--secondary">Export CSV</a>
        {{endif}}
    </div>
</div>

{{if $data['total'] === 0}}
<div class="gdPanel" style="text-align:center;padding:48px 24px">
    <div style="font-size:40px;margin-bottom:12px">&#x2713;</div>
    <h3 style="font-size:16px;font-weight:600;margin:0 0 6px">All UPCs matched</h3>
    <p style="color:var(--gd-text-subtle);margin:0">Every product in your feed matched our catalog. Nice work.</p>
</div>
{{else}}
<div class="gdPanel gdPanel--info" style="margin-bottom:16px;background:var(--gd-info-bg);border-color:var(--gd-brand-border);color:var(--gd-text)">
    <p style="margin:0;font-size:13px">
        <strong>What this means:</strong> Our master catalog doesn't have these UPCs yet, so your listings for them aren't appearing in price comparison results. Most unmatched UPCs auto-resolve within a week as our catalog expands. If you believe a UPC should be matched, contact support.
    </p>
</div>

<div class="gdPanel gdPanel--tableShell">
    <table class="gdListingsTable">
        <thead>
            <tr>
                <th>UPC</th>
                <th class="is-num">First seen</th>
                <th class="is-num">Last seen</th>
                <th class="is-num">Times in feed</th>
                <th class="is-num">Action</th>
            </tr>
        </thead>
        <tbody>
            {{foreach $data['rows'] as $row}}
            <tr>
                <td data-label="UPC"><span class="gdListingsTable__upc">{$row['upc']}</span></td>
                <td class="is-num" data-label="First seen" style="font-size:12px;color:var(--gd-text-subtle)">{$row['first_seen']}</td>
                <td class="is-num" data-label="Last seen" style="font-size:12px;color:var(--gd-text-subtle)">{$row['last_seen']}</td>
                <td class="is-num" data-label="Times in feed">{expression="number_format($row['occurrence_count'])"}</td>
                <td class="is-num" data-label="Action">
                    <a href="{$row['exclude_url']}" class="gdBtn gdBtn--ghost gdBtn--sm" onclick="return confirm('Stop tracking this UPC?');">
                        Exclude
                    </a>
                </td>
            </tr>
            {{endforeach}}
        </tbody>
    </table>
</div>
{{endif}}
TEMPLATE_EOT
];

foreach ( $gddealerV10074Templates as $tpl ) {
    $masterKey = md5( $tpl['app'] . ';' . $tpl['location'] . ';' . $tpl['group'] . ';' . $tpl['template_name'] );

    \IPS\Db::i()->replace( 'core_theme_templates', [
        'template_set_id'        => $tpl['set_id'],
        'template_app'           => $tpl['app'],
        'template_location'      => $tpl['location'],
        'template_group'         => $tpl['group'],
        'template_name'          => $tpl['template_name'],
        'template_data'          => $tpl['template_data'],
        'template_content'       => $tpl['template_content'],
        'template_master_key'    => $masterKey,
        'template_updated'       => time(),
    ] );
}

try { \IPS\Theme::master()->recompileTemplates(); } catch ( \Throwable ) {}
try { \IPS\Data\Cache::i()->clearAll(); } catch ( \Throwable ) {}
try { \IPS\Data\Store::i()->clearAll(); } catch ( \Throwable ) {}
