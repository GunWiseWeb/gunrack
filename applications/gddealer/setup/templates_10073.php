<?php
namespace IPS\gddealer\setup;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

$gddealerV10073Templates = [];

/* feedSettings */
$gddealerV10073Templates[] = [
    'set_id'            => 1,
    'app'               => 'gddealer',
    'location'          => 'front',
    'group'             => 'dealers',
    'template_name'     => 'feedSettings',
    'template_data'     => '$data',
    'template_content'  => <<<'TEMPLATE_EOT'
<div class="gdPageHeader">
    <div class="gdPageHeader__titleBlock">
        <h1 class="gdPageHeader__title">Feed settings</h1>
        <p class="gdPageHeader__sub">Configure your product feed and review import history</p>
    </div>
    <div class="gdPageHeader__actions">
        <a href="{$data['tab_urls']['help']}" class="gdBtn gdBtn--secondary">Feed docs</a>
    </div>
</div>

<div class="gdSyncBanner gdSyncBanner--{$data['sync_health']}">
    <div class="gdSyncBanner__icon">
        {{if $data['sync_health'] === 'healthy'}}
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        {{elseif $data['sync_health'] === 'error'}}
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        {{else}}
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/><circle cx="12" cy="12" r="10"/></svg>
        {{endif}}
    </div>
    <div class="gdSyncBanner__body">
        <div class="gdSyncBanner__title">{$data['sync_title']}</div>
        <div class="gdSyncBanner__sub">{$data['sync_sub']}</div>
    </div>
</div>

{{if $data['latest']}}
<div class="gdSyncStats">
    <div class="gdSyncStat">
        <div class="gdSyncStat__label">Total records</div>
        <div class="gdSyncStat__value">{expression="number_format($data['latest']['records'])"}</div>
        <div class="gdSyncStat__sub">Last import</div>
    </div>
    <div class="gdSyncStat">
        <div class="gdSyncStat__label">New</div>
        <div class="gdSyncStat__value {expression="$data['latest']['new'] > 0 ? 'gdSyncStat__value--success' : ''"}">{{if $data['latest']['new'] > 0}}+{expression="number_format($data['latest']['new'])"}{{else}}0{{endif}}</div>
        <div class="gdSyncStat__sub">Created this run</div>
    </div>
    <div class="gdSyncStat">
        <div class="gdSyncStat__label">Updated</div>
        <div class="gdSyncStat__value">{expression="number_format($data['latest']['updated'])"}</div>
        <div class="gdSyncStat__sub">Price, stock, etc.</div>
    </div>
    <div class="gdSyncStat">
        <div class="gdSyncStat__label">Unmatched</div>
        <div class="gdSyncStat__value {expression="$data['latest']['unmatched'] > 0 ? 'gdSyncStat__value--warn' : ''"}">{expression="number_format($data['latest']['unmatched'])"}</div>
        <div class="gdSyncStat__sub">{{if $data['latest']['unmatched'] > 0}}<a href="{$data['tab_urls']['unmatched']}">Review &rarr;</a>{{else}}All matched{{endif}}</div>
    </div>
</div>
{{endif}}

<div class="gdContentSplit">

    <div>
        <div class="gdPanel">
            <div class="gdPanel__head">
                <div>
                    <div class="gdPanel__title">Feed configuration</div>
                    <div class="gdPanel__sub">Point us at your feed. We'll import on schedule based on your plan.</div>
                </div>
            </div>
            {$data['form']|raw}
        </div>
    </div>

    <div>
        <div class="gdRailCard">
            <div class="gdRailCard__title">Import history</div>
            {{if count($data['import_log']) === 0}}
            <p style="color: var(--gd-text-subtle); font-size: 13px; margin: 0;">No imports yet. Save your feed URL above to kick off the first one.</p>
            {{else}}
            <table class="gdTable">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Status</th>
                        <th class="is-num">Records</th>
                    </tr>
                </thead>
                <tbody>
                    {{foreach $data['import_log'] as $row}}
                    <tr>
                        <td style="font-size: 12px;">{$row['when_ago']}</td>
                        <td><span class="gdLogStatus gdLogStatus--{$row['status']}">{expression="ucfirst($row['status'])"}</span></td>
                        <td class="is-num">{{if $row['records'] > 0}}{expression="number_format($row['records'])"}{{else}}&mdash;{{endif}}</td>
                    </tr>
                    {{endforeach}}
                </tbody>
            </table>
            {{endif}}
        </div>
    </div>

</div>
TEMPLATE_EOT
];

foreach ( $gddealerV10073Templates as $tpl ) {
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
