<?php
namespace IPS\gddealer\setup;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

$gddealerV10077Templates = [];

/* reviews — foldable contest block + foldable activity timeline */
$gddealerV10077Templates[] = [
    'set_id'            => 1,
    'app'               => 'gddealer',
    'location'          => 'front',
    'group'             => 'dealers',
    'template_name'     => 'reviews',
    'template_data'     => '$data',
    'template_content'  => <<<'TEMPLATE_EOT'
<div class="gdPageHeader">
    <div class="gdPageHeader__titleBlock">
        <h1 class="gdPageHeader__title">Reviews</h1>
        <p class="gdPageHeader__sub">Manage customer feedback and respond to build trust</p>
    </div>
</div>

<div class="gdReviewSummary">
    <div class="gdReviewSummary__score">
        <div class="gdReviewSummary__avg">{expression="number_format($data['summary']['average'], 1)"}</div>
        <div class="gdReviewSummary__stars">
            {{foreach $data['five'] as $n}}
                <svg class="gdReviewSummary__star {expression="$n <= round($data['summary']['average']) ? 'is-filled' : ''"}" viewBox="0 0 24 24" fill="currentColor"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            {{endforeach}}
        </div>
        <div class="gdReviewSummary__count">{expression="number_format($data['summary']['count'])"} reviews</div>
    </div>
    <div class="gdReviewSummary__breakdown">
        {{foreach $data['five_rev'] as $star}}
        <div class="gdReviewBar">
            <span class="gdReviewBar__label">{$star}</span>
            <svg class="gdReviewBar__star" viewBox="0 0 24 24" fill="currentColor"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            <div class="gdReviewBar__track">
                <div class="gdReviewBar__fill" style="width: {expression="$data['summary']['count'] > 0 ? round(($data['summary']['distribution'][$star] / $data['summary']['count']) * 100) : 0"}%"></div>
            </div>
            <span class="gdReviewBar__count">{expression="number_format($data['summary']['distribution'][$star])"}</span>
        </div>
        {{endforeach}}
    </div>
    <div class="gdReviewSummary__dimensions">
        <div class="gdReviewDim">
            <div class="gdReviewDim__label">Pricing</div>
            <div class="gdReviewDim__value">{expression="number_format($data['summary']['avg_pricing'], 1)"}</div>
        </div>
        <div class="gdReviewDim">
            <div class="gdReviewDim__label">Shipping</div>
            <div class="gdReviewDim__value">{expression="number_format($data['summary']['avg_shipping'], 1)"}</div>
        </div>
        <div class="gdReviewDim">
            <div class="gdReviewDim__label">Service</div>
            <div class="gdReviewDim__value">{expression="number_format($data['summary']['avg_service'], 1)"}</div>
        </div>
    </div>
</div>

<div class="gdSubNav">
    <a href="{$data['review_tab_urls']['attention']}" class="gdSubNavTab {expression="$data['active_tab'] === 'attention' ? 'is-active' : ''"}">
        Needs attention <span class="gdSubNavTab__count">{$data['counts']['attention']}</span>
    </a>
    <a href="{$data['review_tab_urls']['contested']}" class="gdSubNavTab {expression="$data['active_tab'] === 'contested' ? 'is-active' : ''"}">
        Contested <span class="gdSubNavTab__count">{$data['counts']['contested']}</span>
    </a>
    <a href="{$data['review_tab_urls']['recent']}" class="gdSubNavTab {expression="$data['active_tab'] === 'recent' ? 'is-active' : ''"}">
        Recent <span class="gdSubNavTab__count">{$data['counts']['recent']}</span>
    </a>
    <a href="{$data['review_tab_urls']['all']}" class="gdSubNavTab {expression="$data['active_tab'] === 'all' ? 'is-active' : ''"}">
        All <span class="gdSubNavTab__count">{$data['counts']['all']}</span>
    </a>
</div>

<form method="get" class="gdFilterBar">
    <input type="hidden" name="app" value="gddealer">
    <input type="hidden" name="module" value="dealers">
    <input type="hidden" name="controller" value="dashboard">
    <input type="hidden" name="do" value="reviews">
    <input type="hidden" name="tab" value="{$data['active_tab']}">
    <div class="gdFilterBar__search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" placeholder="Search review text" value="{$data['search']}">
    </div>
    <select name="rating" class="gdSelect" style="max-width:180px" onchange="this.form.submit()">
        <option value="all" {expression="$data['rating_filter'] === 'all' ? 'selected' : ''"}>Any rating</option>
        <option value="5" {expression="$data['rating_filter'] === '5' ? 'selected' : ''"}>5 stars only</option>
        <option value="4-5" {expression="$data['rating_filter'] === '4-5' ? 'selected' : ''"}>4-5 stars</option>
        <option value="3-" {expression="$data['rating_filter'] === '3-' ? 'selected' : ''"}>3 stars and below</option>
        <option value="1-2" {expression="$data['rating_filter'] === '1-2' ? 'selected' : ''"}>1-2 stars only</option>
    </select>
    <select name="date" class="gdSelect" style="max-width:180px" onchange="this.form.submit()">
        <option value="any"  {expression="$data['date_filter'] === 'any' ? 'selected' : ''"}>Any date</option>
        <option value="7"    {expression="$data['date_filter'] === '7' ? 'selected' : ''"}>Last 7 days</option>
        <option value="30"   {expression="$data['date_filter'] === '30' ? 'selected' : ''"}>Last 30 days</option>
        <option value="90"   {expression="$data['date_filter'] === '90' ? 'selected' : ''"}>Last 90 days</option>
        <option value="year" {expression="$data['date_filter'] === 'year' ? 'selected' : ''"}>This year</option>
    </select>
    <button type="submit" class="gdBtn gdBtn--primary gdBtn--sm">Apply</button>
</form>

{{if count($data['reviews']) === 0}}
<div class="gdPanel" style="text-align:center;padding:48px 24px">
    <h3 style="font-size:16px;font-weight:600;margin:0 0 6px">No reviews match these filters</h3>
    <p style="color:var(--gd-text-subtle);margin:0">Try clearing filters or switching tabs.</p>
</div>
{{else}}
<div class="gdReviewList">
    {{foreach $data['reviews'] as $r}}
    <article class="gdReviewCard {expression="$r['dispute_status'] !== 'none' ? 'gdReviewCard--contested' : ''"}">

        <header class="gdReviewCard__identity">
            <div class="gdReviewCard__avatar">{$r['customer_initials']}</div>
            <div class="gdReviewCard__identityInfo">
                <div class="gdReviewCard__authorLine">
                    <span class="gdReviewCard__author">{$r['author_name']}</span>
                    {{if $r['verified_buyer']}}
                    <span class="gdReviewCard__verifiedBadge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 6.5L19.5 7.5L16 11.5L17 16.5L12 14L7 16.5L8 11.5L4.5 7.5L9.5 6.5L12 2Z"/></svg>
                        Verified Buyer
                    </span>
                    {{endif}}
                </div>
                <div class="gdReviewCard__metaLine">
                    <time>{$r['created_at_display']}</time>
                    {{if $r['product_name']}}
                    <span class="gdReviewCard__sep">&middot;</span>
                    <span class="gdReviewCard__product">{$r['product_name']}</span>
                    {{elseif $r['upc']}}
                    <span class="gdReviewCard__sep">&middot;</span>
                    <span class="gdReviewCard__product">UPC: {$r['upc']}</span>
                    {{endif}}
                </div>
            </div>
            <div class="gdReviewCard__ratingPill" style="background:{$r['avg_color']}">
                {expression="number_format($r['avg_overall'], 1)"}
            </div>
        </header>

        <div class="gdReviewCard__dimensions">
            <span class="gdReviewDim__inline">Pricing <strong>{$r['rating_pricing']}</strong></span>
            <span class="gdReviewDim__inline">Shipping <strong>{$r['rating_shipping']}</strong></span>
            <span class="gdReviewDim__inline">Service <strong>{$r['rating_service']}</strong></span>
        </div>

        {{if $r['review_body']}}
        <div class="gdReviewCard__body">{$r['review_body']|raw}</div>
        {{endif}}

        {{if $r['dealer_response']}}
        <div class="gdReviewCard__response">
            <div class="gdReviewCard__responseLabel">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
                Your response &middot; <time>{$r['response_at_display']}</time>
            </div>
            <div class="gdReviewCard__responseBody">{$r['dealer_response']|raw}</div>
            {{if $r['edit_editor_html']}}
            <details class="gdReviewCard__editForm">
                <summary class="gdBtn gdBtn--ghost gdBtn--sm">Edit response</summary>
                <form method="post" action="{$r['respond_url']}">
                    {$r['edit_editor_html']|raw}
                    <button type="submit" class="gdBtn gdBtn--primary gdBtn--sm">Save changes</button>
                </form>
            </details>
            {{endif}}
        </div>
        {{elseif $r['respond_editor_html']}}
        <div class="gdReviewCard__respondForm">
            <form method="post" action="{$r['respond_url']}">
                {$r['respond_editor_html']|raw}
                <div class="gdReviewCard__respondActions">
                    <button type="submit" class="gdBtn gdBtn--primary gdBtn--sm">Post response</button>
                    {{if $r['dispute_url']}}
                    <a href="{$r['dispute_url']}" class="gdBtn gdBtn--ghost gdBtn--sm">Dispute this review</a>
                    {{endif}}
                </div>
            </form>
        </div>
        {{endif}}

        {{if $r['dispute_reason']}}
        <details class="gdReviewCard__contestFold" {expression="$r['dispute_status'] === 'pending_customer' ? 'open' : ''"}>
            <summary class="gdReviewCard__contestFoldSummary">
                <span class="gdReviewCard__contestFoldBadge gdReviewCard__contestFoldBadge--{$r['dispute_status']}">
                    {{if $r['dispute_status'] === 'pending_customer'}}{lang="gddealer_front_fold_pending_customer"}
                    {{elseif $r['dispute_status'] === 'pending_admin'}}{lang="gddealer_front_fold_pending_admin"}
                    {{elseif $r['dispute_status'] === 'resolved_dealer'}}{lang="gddealer_front_fold_resolved_dealer"}
                    {{elseif $r['dispute_status'] === 'resolved_customer'}}{lang="gddealer_front_fold_resolved_customer"}
                    {{else}}{lang="gddealer_front_fold_dispute_details"}{{endif}}
                </span>
                {{if $r['dispute_status'] === 'pending_customer' and $r['deadline_display']}}
                <span class="gdReviewCard__contestFoldMeta">Deadline: {$r['deadline_display']}{{if $r['deadline_days_left'] >= 0}} &middot; {$r['deadline_days_left']}d left{{endif}}</span>
                {{elseif $r['customer_response']}}
                <span class="gdReviewCard__contestFoldMeta">{lang="gddealer_front_fold_customer_replied"}</span>
                {{endif}}
                <svg class="gdReviewCard__contestFoldChevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </summary>
            <div class="gdReviewCard__contestFoldBody">

                <div class="gdContestBlock gdContestBlock--dealer">
                    <div class="gdContestBlock__header">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Your contest reason
                        {{if $r['dispute_at_date']}}
                        <span style="margin-left:auto;font-weight:400;font-size:11px;color:var(--gd-text-subtle)">Submitted {$r['dispute_at_date']}</span>
                        {{endif}}
                    </div>
                    <div class="gdContestBlock__body">{$r['dispute_reason']|raw}</div>
                    {{if $r['dispute_evidence']}}
                    <details class="gdContestBlock__evidence">
                        <summary>View your evidence</summary>
                        <div class="gdContestBlock__evidenceBody">{$r['dispute_evidence']|raw}</div>
                    </details>
                    {{endif}}
                </div>

                {{if $r['customer_response']}}
                <div class="gdContestBlock gdContestBlock--customer">
                    <div class="gdContestBlock__header">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        {$r['author_name']}'s reply
                        {{if $r['customer_responded_at_date']}}
                        <span style="margin-left:auto;font-weight:400;font-size:11px;color:var(--gd-text-subtle)">Submitted {$r['customer_responded_at_date']}</span>
                        {{endif}}
                    </div>
                    <div class="gdContestBlock__body">{$r['customer_response']|raw}</div>
                    {{if $r['customer_evidence']}}
                    <details class="gdContestBlock__evidence">
                        <summary>View their evidence</summary>
                        <div class="gdContestBlock__evidenceBody">{$r['customer_evidence']|raw}</div>
                    </details>
                    {{endif}}
                </div>
                {{endif}}

                {{if $r['dispute_outcome']}}
                <div class="gdContestBlock gdContestBlock--outcome">
                    <div class="gdContestBlock__header">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Resolution: {$r['dispute_status_label']}
                    </div>
                    <div class="gdContestBlock__body">{$r['dispute_outcome']}</div>
                </div>
                {{endif}}

                {{if $r['dispute_status'] === 'pending_customer'}}
                <div class="gdContestBlock__nextSteps">
                    <strong>What happens next:</strong> The customer has until {$r['deadline_display']} to respond to your contest. If they don't reply, the review will be removed and your rating recalculated. If they do reply, our review team will assess both sides within 3 business days.
                </div>
                {{elseif $r['dispute_status'] === 'pending_admin'}}
                <div class="gdContestBlock__nextSteps">
                    <strong>What happens next:</strong> Our review team is evaluating both sides. You'll receive a decision within 3 business days.
                </div>
                {{endif}}

            </div>
        </details>
        {{elseif $r['dispute_reason_editor_html']}}
        <details class="gdReviewCard__disputeForm">
            <summary class="gdBtn gdBtn--warn gdBtn--sm" style="margin-top:10px">Dispute this review</summary>
            <form method="post" action="{$r['dispute_url']}" style="margin-top:10px">
                <div style="margin-bottom:8px">
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Reason for dispute</label>
                    {$r['dispute_reason_editor_html']|raw}
                </div>
                <div style="margin-bottom:8px">
                    <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Supporting evidence (optional)</label>
                    {$r['dispute_evidence_editor_html']|raw}
                </div>
                <button type="submit" class="gdBtn gdBtn--warn gdBtn--sm">Submit dispute</button>
            </form>
        </details>
        {{endif}}

        {{if count($r['activity']) > 0}}
        <details class="gdReviewCard__activityFold" {expression="in_array($r['dispute_status'], array('pending_customer','pending_admin'), true) ? 'open' : ''"}>
            <summary class="gdReviewCard__activityFoldSummary">
                <span class="gdReviewCard__activityFoldLabel">Activity &middot; {expression="count($r['activity'])"} {expression="count($r['activity']) === 1 ? 'event' : 'events'"}</span>
                <svg class="gdReviewCard__activityFoldChevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </summary>
            <ul class="gdTimeline">
                {{foreach $r['activity'] as $ev}}
                <li class="gdTimeline__item">
                    <span class="gdTimeline__dot gdTimeline__dot--{$ev['dot']}"></span>
                    <div class="gdTimeline__body">
                        <div class="gdTimeline__label">{$ev['label']}</div>
                        {{if $ev['note']}}
                        <div class="gdTimeline__note">&ldquo;{$ev['note']}&rdquo;</div>
                        {{endif}}
                        <div class="gdTimeline__time">{$ev['time']}</div>
                    </div>
                </li>
                {{endforeach}}
            </ul>
        </details>
        {{endif}}

    </article>
    {{endforeach}}
</div>

{{if $data['total_pages'] > 1}}
<div class="gdPagination">
    <div class="gdPagination__info">Page {$data['page']} of {$data['total_pages']} &middot; {expression="number_format($data['total_count'])"} reviews</div>
    <div class="gdPagination__controls">
        {{if $data['prev_url']}}
        <a href="{$data['prev_url']}" class="gdBtn gdBtn--secondary gdBtn--sm">Previous</a>
        {{endif}}
        {{foreach $data['pages_array'] as $p}}
        {{if $p['is_current']}}
        <span class="gdBtn gdBtn--primary gdBtn--sm">{$p['num']}</span>
        {{else}}
        <a href="{$p['url']}" class="gdBtn gdBtn--secondary gdBtn--sm">{$p['num']}</a>
        {{endif}}
        {{endforeach}}
        {{if $data['next_url']}}
        <a href="{$data['next_url']}" class="gdBtn gdBtn--secondary gdBtn--sm">Next</a>
        {{endif}}
    </div>
</div>
{{endif}}
{{endif}}
TEMPLATE_EOT
];

foreach ( $gddealerV10077Templates as $tpl ) {
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
