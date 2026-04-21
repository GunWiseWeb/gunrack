<?php
namespace IPS\gddealer\setup;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

$gddealerV10075Templates = [];

/* reviews */
$gddealerV10075Templates[] = [
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
        <header class="gdReviewCard__head">
            <div class="gdReviewCard__rating">
                {{foreach $data['five'] as $n}}
                <svg class="gdReviewCard__star {expression="$n <= round($r['avg_overall']) ? 'is-filled' : ''"}" viewBox="0 0 24 24" fill="currentColor"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                {{endforeach}}
                <span class="gdReviewCard__ratingValue">{expression="number_format($r['avg_overall'], 1)"}</span>
            </div>
            <div class="gdReviewCard__meta">
                <span class="gdReviewCard__author">{$r['author_name']}</span>
                <span class="gdReviewCard__sep">&middot;</span>
                <time>{$r['created_at']}</time>
                {{if $r['dispute_status'] !== 'none'}}
                <span class="gdStatusPill gdStatusPill--suspended" style="margin-left:8px">Disputed</span>
                {{endif}}
            </div>
        </header>

        <div class="gdReviewCard__dimensions">
            <span class="gdReviewDim__inline">Pricing <strong>{$r['rating_pricing']}</strong></span>
            <span class="gdReviewDim__inline">Shipping <strong>{$r['rating_shipping']}</strong></span>
            <span class="gdReviewDim__inline">Service <strong>{$r['rating_service']}</strong></span>
        </div>

        <div class="gdReviewCard__body">{$r['review_body']|raw}</div>

        {{if $r['dealer_response']}}
        <div class="gdReviewCard__response">
            <div class="gdReviewCard__responseLabel">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/></svg>
                Your response &middot; <time>{$r['response_at']}</time>
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

        {{if $r['dispute_status'] !== 'none' and $r['dispute_outcome']}}
        <div class="gdReviewCard__dispute">
            <div class="gdReviewCard__disputeLabel">Dispute status: {$r['dispute_status_label']}</div>
            <p style="margin:6px 0 0;font-size:13px;color:var(--gd-text-muted)">{$r['dispute_outcome']}</p>
        </div>
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

foreach ( $gddealerV10075Templates as $tpl ) {
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
