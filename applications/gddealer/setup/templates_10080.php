<?php
namespace IPS\gddealer\setup;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

$gddealerV10080Templates = [];

/* supportList */
$gddealerV10080Templates[] = [
    'set_id'            => 1,
    'app'               => 'gddealer',
    'location'          => 'front',
    'group'             => 'dealers',
    'template_name'     => 'supportList',
    'template_data'     => '$tickets, $subNav',
    'template_content'  => <<<'TEMPLATE_EOT'
<div class="gdPageHeader">
    <div class="gdPageHeader__titleBlock">
        <h1 class="gdPageHeader__title">Support tickets</h1>
        <p class="gdPageHeader__sub">Get help with feed imports, account issues, or anything else. Pro dealers get 24-hour responses; Enterprise gets 4-hour.</p>
    </div>
    <div class="gdPageHeader__actions">
        <a href="{$subNav['new_url']}" class="gdBtn gdBtn--primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New ticket
        </a>
    </div>
</div>

<div class="gdSubNav">
    <a href="{$subNav['open_url']}" class="gdSubNavTab {expression="$subNav['active'] === 'open' ? 'is-active' : ''"}">
        Open <span class="gdSubNavTab__count">{$subNav['open_count']}</span>
    </a>
    <a href="{$subNav['closed_url']}" class="gdSubNavTab {expression="$subNav['active'] === 'closed' ? 'is-active' : ''"}">
        Closed <span class="gdSubNavTab__count">{$subNav['closed_count']}</span>
    </a>
    <a href="{$subNav['all_url']}" class="gdSubNavTab {expression="$subNav['active'] === 'all' ? 'is-active' : ''"}">
        All
    </a>
</div>

{{if count($tickets) === 0}}
<div class="gdPanel" style="text-align:center;padding:48px 24px">
    <div style="font-size:32px;color:var(--gd-text-faint);margin-bottom:10px">&#x1F4AC;</div>
    <h3 style="font-size:16px;font-weight:600;margin:0 0 6px">
        {{if $subNav['active'] === 'open'}}No open tickets
        {{elseif $subNav['active'] === 'closed'}}No closed tickets
        {{else}}No tickets yet
        {{endif}}
    </h3>
    <p style="color:var(--gd-text-subtle);margin:0 0 16px;font-size:13px">Need help with something? Open a ticket and we'll get back to you.</p>
    <a href="{$subNav['new_url']}" class="gdBtn gdBtn--primary gdBtn--sm">Open a ticket</a>
</div>
{{else}}
<div class="gdPanel gdPanel--tableShell">
    <div class="gdTicketList">
        {{foreach $tickets as $t}}
        <a href="{$t['view_url']}" class="gdTicketRow {expression="$t['needs_attention'] ? 'gdTicketRow--attention' : ''"}">
            <span class="gdTicketRow__icon" style="background: {$t['icon_bg']}; color: {$t['icon_color']};">{$t['icon_glyph']}</span>

            <div class="gdTicketRow__body">
                <div class="gdTicketRow__subject">{$t['subject']}</div>
                <div class="gdTicketRow__meta">
                    {{if $t['department_name']}}
                    <span>{$t['department_name']}</span>
                    <span class="gdTicketRow__sep">&middot;</span>
                    {{endif}}
                    <span>#{$t['id']}</span>
                    <span class="gdTicketRow__sep">&middot;</span>
                    <span>{$t['created_at_short']}</span>
                    {{if $t['last_reply_role']}}
                    <span class="gdTicketRow__sep">&middot;</span>
                    <span>Last reply: {expression="$t['last_reply_role'] === 'staff' ? 'Support' : ( $t['last_reply_role'] === 'customer' ? 'You' : ucfirst($t['last_reply_role']) )"}</span>
                    {{endif}}
                </div>
            </div>

            <span class="gdStatusPill" style="background: {$t['status_bg']}; color: {$t['status_color']};">{$t['status_label']}</span>

            <span class="gdPriorityPill" style="background: {$t['priority_bg']}; color: {$t['priority_color']};">{$t['priority_label']}</span>

            <span class="gdTicketRow__updated">{$t['updated_at_relative']}</span>

            <svg class="gdTicketRow__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        {{endforeach}}
    </div>
</div>
{{endif}}
TEMPLATE_EOT
];

foreach ( $gddealerV10080Templates as $tpl ) {
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
