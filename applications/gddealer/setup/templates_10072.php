<?php
namespace IPS\gddealer\setup;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

$gddealerV10072Templates = [];

/* overview */
$gddealerV10072Templates[] = [
    'set_id'            => 1,
    'app'               => 'gddealer',
    'location'          => 'front',
    'group'             => 'dealers',
    'template_name'     => 'overview',
    'template_data'     => '$data',
    'template_content'  => <<<'TEMPLATE_EOT'
<div class="gdPageHeader">
    <div class="gdPageHeader__titleBlock">
        <h1 class="gdPageHeader__title">Overview</h1>
        <p class="gdPageHeader__sub">Last updated {expression="date('g:i A')"}</p>
    </div>
    <div class="gdPageHeader__actions">
        <a href="{$data['tab_urls']['analytics']}" class="gdBtn gdBtn--secondary">Analytics</a>
        <a href="{$data['tab_urls']['feedSettings']}" class="gdBtn gdBtn--primary">Upload feed</a>
    </div>
</div>

<div class="gdIdentity">
    <div class="gdIdentity__left">
        <div class="gdIdentity__avatar">
            {{if $data['dealer']['avatar_url']}}
                <img src="{$data['dealer']['avatar_url']}" alt="">
            {{else}}
                {expression="mb_substr($data['dealer']['dealer_name'], 0, 2)"}
            {{endif}}
        </div>
        <div>
            <div class="gdIdentity__name">
                <h2>{$data['dealer']['dealer_name']}</h2>
                <span class="gdTierBadge gdTierBadge--{$data['dealer']['subscription_tier']}">{$data['dealer']['tier_label']} dealer</span>
            </div>
            <p class="gdIdentity__url">gunrack.deals/dealers/{$data['dealer']['dealer_slug']}</p>
        </div>
    </div>
    <div class="gdIdentity__actions">
        <a href="{$data['public_profile_url']}" class="gdBtn gdBtn--secondary">View public profile</a>
        <a href="{$data['tab_urls']['subscription']}" class="gdBtn gdBtn--secondary">Manage subscription</a>
    </div>
</div>

{{if $data['steps_pct'] < 100}}
<div class="gdSetupCard">
    <div class="gdSetupCard__head">
        <div>
            <div class="gdSetupCard__title">Finish setting up your storefront</div>
            <div class="gdSetupCard__sub">{$data['steps_done']} of {$data['steps_total']} steps complete</div>
        </div>
        <div class="gdSetupCard__pct">{$data['steps_pct']}%</div>
    </div>
    <div class="gdProgress">
        <div class="gdProgress__fill" style="width: {$data['steps_pct']}%"></div>
    </div>
    <div class="gdSetupSteps">
        {{foreach $data['setup_steps'] as $step}}
        <div class="gdStep gdStep--{$step['state']}">
            <span class="gdStep__icon">
                {{if $step['state'] === 'done'}}&#10003;{{elseif $step['state'] === 'active'}}!{{endif}}
            </span>
            <span class="gdStep__text">{$step['label']}</span>
        </div>
        {{endforeach}}
    </div>
</div>
{{endif}}

<div class="gdKpiGrid">
    <div class="gdKpi">
        <div class="gdKpi__label">Active listings</div>
        <div class="gdKpi__value">{expression="number_format($data['stats']['active'])"}</div>
    </div>
    <div class="gdKpi">
        <div class="gdKpi__label">Out of stock</div>
        <div class="gdKpi__value">{expression="number_format($data['stats']['out'])"}</div>
    </div>
    <div class="gdKpi">
        <div class="gdKpi__label">Unmatched UPCs</div>
        <div class="gdKpi__value">{expression="number_format($data['stats']['unmatched'])"}</div>
    </div>
    <div class="gdKpi">
        <div class="gdKpi__label">Clicks &middot; 30d</div>
        <div class="gdKpi__value">{expression="number_format($data['stats']['clicks_30'])"}</div>
    </div>
</div>

<div class="gdContentSplit">

    <div>
        <div class="gdPanel">
            <div class="gdPanel__head">
                <div>
                    <div class="gdPanel__title">Last feed import</div>
                    <div class="gdPanel__sub">Most recent import activity</div>
                </div>
                <a href="{$data['tab_urls']['feedSettings']}" class="gdPanel__link">Feed settings &rarr;</a>
            </div>
            {{if $data['last_import']}}
            <table class="gdTable">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Status</th>
                        <th class="is-num">Records</th>
                        <th class="is-num">Updated</th>
                        <th class="is-num">Unmatched</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{$data['last_import']['when_label']}</td>
                        <td><span class="gdLogStatus gdLogStatus--{$data['last_import']['status']}">{expression="ucfirst($data['last_import']['status'])"}</span></td>
                        <td class="is-num">{expression="number_format($data['last_import']['records'])"}</td>
                        <td class="is-num">{expression="number_format($data['last_import']['updated'])"}</td>
                        <td class="is-num">{expression="number_format($data['last_import']['unmatched'])"}</td>
                    </tr>
                </tbody>
            </table>
            {{else}}
            <p style="color: var(--gd-text-subtle); font-size: 13px; margin: 0;">No feed imports yet. <a href="{$data['tab_urls']['feedSettings']}" style="color: var(--gd-brand)">Configure your feed &rarr;</a></p>
            {{endif}}
        </div>
    </div>

    <div>
        <div class="gdRailCard">
            <div class="gdRailCard__title">Quick actions</div>
            <div class="gdActionList">
                <a href="{$data['tab_urls']['feedSettings']}" class="gdActionList__item">
                    <span>Upload product feed</span>
                    <span class="gdActionList__arrow">&rarr;</span>
                </a>
                <a href="{$data['tab_urls']['unmatched']}" class="gdActionList__item">
                    <span>Resolve unmatched UPCs</span>
                    {{if $data['stats']['unmatched'] > 0}}
                    <span class="gdActionList__count is-urgent">{$data['stats']['unmatched']}</span>
                    {{else}}
                    <span class="gdActionList__arrow">&rarr;</span>
                    {{endif}}
                </a>
                <a href="{$data['tab_urls']['reviews']}" class="gdActionList__item">
                    <span>Respond to reviews</span>
                    {{if $data['dealer']['new_reviews'] > 0}}
                    <span class="gdActionList__count is-warn">{$data['dealer']['new_reviews']}</span>
                    {{else}}
                    <span class="gdActionList__arrow">&rarr;</span>
                    {{endif}}
                </a>
                <a href="{$data['tab_urls']['analytics']}" class="gdActionList__item">
                    <span>View analytics</span>
                    <span class="gdActionList__arrow">&rarr;</span>
                </a>
                <a href="{$data['tab_urls']['subscription']}" class="gdActionList__item">
                    <span>Manage subscription</span>
                    <span class="gdActionList__arrow">&rarr;</span>
                </a>
            </div>
        </div>
    </div>

</div>
TEMPLATE_EOT
];

/* feedSettings */
$gddealerV10072Templates[] = [
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

foreach ( $gddealerV10072Templates as $tpl ) {
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
