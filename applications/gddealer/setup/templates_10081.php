<?php
namespace IPS\gddealer\setup;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

$gddealerV10081Templates = [];

/* supportView */
$gddealerV10081Templates[] = [
    'set_id'            => 1,
    'app'               => 'gddealer',
    'location'          => 'front',
    'group'             => 'dealers',
    'template_name'     => 'supportView',
    'template_data'     => '$ticket, $ticketBody, $ticketAttachments, $replies, $replyEditorHtml, $csrfKey, $replyUrl, $closeUrl, $backUrl, $canReply, $canClose, $events, $newTicketUrl',
    'template_content'  => <<<'TEMPLATE_EOT'
<div class="gdTicketDetail">
    <div class="gdTicketDetail__back">
        <a href="{$backUrl}" class="gdBtn gdBtn--ghost gdBtn--sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            All tickets
        </a>
    </div>

    <div class="gdTicketDetail__header">
        <div class="gdTicketDetail__titleBlock">
            <div class="gdTicketDetail__badges">
                <span class="gdStatusPill" style="background: {$ticket['status_bg']}; color: {$ticket['status_color']};">{$ticket['status_label']}</span>
                <span class="gdPriorityPill" style="background: {$ticket['priority_bg']}; color: {$ticket['priority_color']};">{$ticket['priority_label']}</span>
                <span class="gdTicketDetail__id">#{$ticket['id']}</span>
            </div>
            <h1 class="gdTicketDetail__subject">{$ticket['subject']}</h1>
            <p class="gdTicketDetail__sub">
                {{if $ticket['department_name']}}{$ticket['department_name']} &middot; {{endif}}
                Opened {$ticket['created_at']}
            </p>
        </div>
        {{if $canClose}}
        <div class="gdTicketDetail__actions">
            <a href="{$closeUrl}" class="gdBtn gdBtn--secondary gdBtn--sm" onclick="return confirm('Close this ticket? You can reopen by posting a new reply.');">Close ticket</a>
        </div>
        {{endif}}
    </div>

    <div class="gdTicketLayout">

        <div class="gdTicketLayout__main">
            <article class="gdTicketMsg gdTicketMsg--customer">
                <header class="gdTicketMsg__head">
                    <span class="gdTicketMsg__role" style="background:#dcfce7;color:#166534">You</span>
                    <span class="gdTicketMsg__time">{$ticket['created_at']}</span>
                </header>
                <div class="gdTicketMsg__body">{$ticketBody|raw}</div>
            </article>

            {{foreach $replies as $r}}
            <article class="gdTicketMsg gdTicketMsg--{$r['role']}">
                <header class="gdTicketMsg__head">
                    <span class="gdTicketMsg__role" style="background: {$r['role_bg']}; color: {$r['role_color']};">{$r['role_label']}</span>
                    {{if $r['author_name']}}<span class="gdTicketMsg__author">{$r['author_name']}</span>{{endif}}
                    <span class="gdTicketMsg__time">{$r['created_at']}</span>
                </header>
                <div class="gdTicketMsg__body">{$r['body']|raw}</div>
            </article>
            {{endforeach}}

            {{if $canReply}}
            <div class="gdReplyBox">
                <div class="gdReplyBox__head">Write a reply</div>
                <form method="post" action="{$replyUrl}" enctype="multipart/form-data">
                    <input type="hidden" name="csrfKey" value="{$csrfKey}">
                    <div class="gdReplyBox__editor">
                        {$replyEditorHtml|raw}
                    </div>
                    <div class="gdReplyBox__toolbar">
                        <span class="gdReplyBox__hint">Support will be notified when you post.</span>
                        <button type="submit" class="gdBtn gdBtn--primary gdBtn--sm">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            Post reply
                        </button>
                    </div>
                </form>
            </div>
            {{else}}
            <div class="gdPanel" style="text-align:center;padding:24px">
                <p style="margin:0 0 12px;color:var(--gd-text-subtle);font-size:13px">This ticket is closed.</p>
                <a href="{$newTicketUrl}" class="gdBtn gdBtn--primary gdBtn--sm">Open a new ticket</a>
            </div>
            {{endif}}
        </div>

        <aside class="gdTicketLayout__side">
            <div class="gdRailCard">
                <div class="gdRailCard__title">Ticket info</div>
                <div class="gdInfoRows">
                    <div class="gdInfoRow"><span class="gdInfoRow__label">Status</span><span class="gdStatusPill" style="background: {$ticket['status_bg']}; color: {$ticket['status_color']};">{$ticket['status_label']}</span></div>
                    <div class="gdInfoRow"><span class="gdInfoRow__label">Priority</span><span class="gdPriorityPill" style="background: {$ticket['priority_bg']}; color: {$ticket['priority_color']};">{$ticket['priority_label']}</span></div>
                    {{if $ticket['department_name']}}
                    <div class="gdInfoRow"><span class="gdInfoRow__label">Department</span><span class="gdInfoRow__value">{$ticket['department_name']}</span></div>
                    {{endif}}
                    <div class="gdInfoRow"><span class="gdInfoRow__label">Ticket ID</span><span class="gdInfoRow__value">#{$ticket['id']}</span></div>
                    <div class="gdInfoRow"><span class="gdInfoRow__label">Created</span><span class="gdInfoRow__value">{$ticket['created_at']}</span></div>
                </div>
            </div>

            {{if count($events) > 0}}
            <div class="gdRailCard">
                <div class="gdRailCard__title">Activity</div>
                <ul class="gdTimeline">
                    {{foreach $events as $ev}}
                    <li class="gdTimeline__item">
                        <span class="gdTimeline__dot"></span>
                        <div class="gdTimeline__body">
                            <div class="gdTimeline__label">{$ev['verb']}</div>
                            <div class="gdTimeline__time">{$ev['when']}</div>
                        </div>
                    </li>
                    {{endforeach}}
                </ul>
            </div>
            {{endif}}
        </aside>

    </div>
</div>
TEMPLATE_EOT
];

/* supportNew */
$gddealerV10081Templates[] = [
    'set_id'            => 1,
    'app'               => 'gddealer',
    'location'          => 'front',
    'group'             => 'dealers',
    'template_name'     => 'supportNew',
    'template_data'     => '$departments, $canSetUrgent, $bodyEditorHtml, $csrfKey, $submitUrl, $cancelUrl',
    'template_content'  => <<<'TEMPLATE_EOT'
<div class="gdNewTicket">
    <div class="gdTicketDetail__back">
        <a href="{$cancelUrl}" class="gdBtn gdBtn--ghost gdBtn--sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            All tickets
        </a>
    </div>

    <div class="gdPageHeader">
        <div class="gdPageHeader__titleBlock">
            <h1 class="gdPageHeader__title">Open a new ticket</h1>
            <p class="gdPageHeader__sub">We'll route it to the right team and respond within your plan's SLA.</p>
        </div>
    </div>

    <div class="gdNewTicket__card">
        <form method="post" action="{$submitUrl}" enctype="multipart/form-data">
            <input type="hidden" name="csrfKey" value="{$csrfKey}">

            <div class="gdField">
                <label class="gdField__label gdField__label--required">Department</label>
                <select name="support_department" class="gdSelect" required>
                    <option value="">Choose a department&hellip;</option>
                    {{foreach $departments as $d}}
                    <option value="{$d['id']}">{$d['name']}</option>
                    {{endforeach}}
                </select>
            </div>

            <div class="gdField">
                <label class="gdField__label gdField__label--required">Priority</label>
                <div class="gdPriorityPicker">
                    <label class="gdPriorityOption">
                        <input type="radio" name="support_priority" value="low">
                        <div class="gdPriorityOption__name">Low</div>
                        <div class="gdPriorityOption__desc">General questions, feature requests</div>
                    </label>
                    <label class="gdPriorityOption">
                        <input type="radio" name="support_priority" value="normal" checked>
                        <div class="gdPriorityOption__name">Normal</div>
                        <div class="gdPriorityOption__desc">Issues that affect your dashboard</div>
                    </label>
                    <label class="gdPriorityOption {expression="$canSetUrgent ? '' : 'is-locked'"}">
                        <input type="radio" name="support_priority" value="urgent" {expression="$canSetUrgent ? '' : 'disabled'"}>
                        <div class="gdPriorityOption__name">Urgent</div>
                        <div class="gdPriorityOption__desc">{{if $canSetUrgent}}Site-wide issues blocking business{{else}}Founding &amp; Enterprise plans only{{endif}}</div>
                    </label>
                </div>
                {{if !$canSetUrgent}}
                <div class="gdField__hint">Urgent priority is available on Founding and Enterprise plans.</div>
                {{endif}}
            </div>

            <div class="gdField">
                <label class="gdField__label gdField__label--required">Subject</label>
                <input type="text" name="support_subject" class="gdInput" required maxlength="255" placeholder="Brief summary of what's going on">
            </div>

            <div class="gdField">
                <label class="gdField__label gdField__label--required">Details</label>
                <div class="gdField__editor">
                    {$bodyEditorHtml|raw}
                </div>
                <div class="gdField__hint">Include steps to reproduce, screenshots, UPCs, or dealer IDs as relevant. The more detail the faster we can help.</div>
            </div>

            <div class="gdNewTicket__actions">
                <a href="{$cancelUrl}" class="gdBtn gdBtn--secondary">Cancel</a>
                <button type="submit" class="gdBtn gdBtn--primary">Submit ticket</button>
            </div>
        </form>
    </div>
</div>
TEMPLATE_EOT
];

foreach ( $gddealerV10081Templates as $tpl ) {
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
