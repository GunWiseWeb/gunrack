<?php
namespace IPS\gddealer\setup\upg_10132;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $directoryBody = <<<'TEMPLATE_EOT'
<style>
.gdDirectory {
    --gd-brand: #1E40AF;
    --gd-brand-hover: #1E3A8A;
    --gd-brand-light: #EFF6FF;
    --gd-brand-border: #BFDBFE;
    --gd-tier-founding: #B45309;
    --gd-tier-founding-bg: #FEF3C7;
    --gd-tier-basic: #64748B;
    --gd-tier-basic-bg: #F1F5F9;
    --gd-tier-pro: #1E40AF;
    --gd-tier-pro-bg: #DBEAFE;
    --gd-tier-enterprise: #6D28D9;
    --gd-tier-enterprise-bg: #EDE9FE;
    --gd-bg: #F6F8FA;
    --gd-surface: #FFFFFF;
    --gd-surface-muted: #F8FAFC;
    --gd-border: #E5E7EB;
    --gd-border-strong: #CBD5E1;
    --gd-border-subtle: #F1F5F9;
    --gd-text: #0F172A;
    --gd-text-muted: #475569;
    --gd-text-subtle: #64748B;
    --gd-text-faint: #94A3B8;
    --gd-rating-great: #16A34A;
    --gd-rating-ok: #D97706;
    --gd-star: #F59E0B;
    --gd-r-md: 6px;
    --gd-r-lg: 10px;
    --gd-r-xl: 14px;
    --gd-r-pill: 999px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--gd-text);
    font-size: 14px;
    line-height: 1.5;
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    box-sizing: border-box;
}
.gdDirectory *, .gdDirectory *::before, .gdDirectory *::after { box-sizing: border-box; }
.gdDirectory a { color: inherit; text-decoration: none; }
.gdDirectory h1, .gdDirectory h2, .gdDirectory h3 { margin: 0; font-weight: 600; }
.gdDirectory p { margin: 0; }

.gdDirectory .directory-hero { background: linear-gradient(135deg, var(--gd-surface) 0%, #EFF4FA 100%); border: 1px solid var(--gd-border); border-radius: var(--gd-r-xl); padding: 2.5rem 2.5rem 2rem; margin-bottom: 1.5rem; position: relative; overflow: hidden; }
.gdDirectory .directory-hero::after { content: ''; position: absolute; top: -40%; right: -10%; width: 420px; height: 420px; background: radial-gradient(circle, rgba(30, 64, 175, 0.08) 0%, transparent 60%); pointer-events: none; }
.gdDirectory .hero-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem; margin-bottom: 1.5rem; position: relative; z-index: 1; }
.gdDirectory .hero-eyebrow { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--gd-brand); margin-bottom: 8px; }
.gdDirectory .hero-title { font-size: 28px; letter-spacing: -0.02em; line-height: 1.15; margin-bottom: 8px; }
.gdDirectory .hero-sub { font-size: 14px; color: var(--gd-text-muted); max-width: 540px; }
.gdDirectory .hero-stats { display: flex; gap: 1.5rem; }
.gdDirectory .hero-stat { text-align: right; }
.gdDirectory .hero-stat-value { font-size: 22px; font-weight: 600; color: var(--gd-text); line-height: 1; }
.gdDirectory .hero-stat-label { font-size: 11px; color: var(--gd-text-subtle); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; margin-top: 4px; }

.gdDirectory .hero-cta-row { display: flex; justify-content: flex-end; position: relative; z-index: 1; }
.gdDirectory .hero-cta { background: var(--gd-brand); color: #fff; padding: 10px 18px; border-radius: var(--gd-r-md); font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }
.gdDirectory .hero-cta:hover { background: var(--gd-brand-hover); color: #fff; }

.gdDirectory .dealer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.gdDirectory .dealer-card { background: var(--gd-surface); border: 1px solid var(--gd-border); border-radius: var(--gd-r-xl); padding: 1.25rem; transition: box-shadow 0.15s, transform 0.15s; display: flex; flex-direction: column; gap: 0.85rem; }
.gdDirectory .dealer-card:hover { box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); transform: translateY(-2px); }
.gdDirectory .dealer-head { display: flex; gap: 0.85rem; align-items: center; }
.gdDirectory .dealer-avatar { width: 44px; height: 44px; border-radius: var(--gd-r-md); background: var(--gd-brand); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 16px; flex-shrink: 0; overflow: hidden; line-height: 1; }
.gdDirectory .dealer-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
.gdDirectory .dealer-identity { min-width: 0; flex: 1; }
.gdDirectory .dealer-name-row { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.gdDirectory .dealer-name { font-size: 14px; font-weight: 600; color: var(--gd-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gdDirectory .dealer-verified { color: var(--gd-rating-great); display: inline-flex; flex-shrink: 0; }
.gdDirectory .dealer-verified svg { width: 14px; height: 14px; }
.gdDirectory .dealer-tier { font-size: 10px; padding: 2px 8px; border-radius: var(--gd-r-pill); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; display: inline-block; }
.gdDirectory .dealer-tier-founding { background: var(--gd-tier-founding-bg); color: var(--gd-tier-founding); }
.gdDirectory .dealer-tier-basic { background: var(--gd-tier-basic-bg); color: var(--gd-tier-basic); }
.gdDirectory .dealer-tier-pro { background: var(--gd-tier-pro-bg); color: var(--gd-tier-pro); }
.gdDirectory .dealer-tier-enterprise { background: var(--gd-tier-enterprise-bg); color: var(--gd-tier-enterprise); }

.gdDirectory .dealer-rating-row { display: flex; align-items: center; gap: 6px; }
.gdDirectory .dealer-rating-stars { display: inline-flex; gap: 1px; }
.gdDirectory .dealer-rating-stars svg { width: 14px; height: 14px; color: var(--gd-star); }
.gdDirectory .dealer-rating-value { font-size: 13px; font-weight: 600; }
.gdDirectory .dealer-rating-count { font-size: 12px; color: var(--gd-text-subtle); }

.gdDirectory .dealer-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; padding: 0.75rem 0; border-top: 1px solid var(--gd-border-subtle); border-bottom: 1px solid var(--gd-border-subtle); }
.gdDirectory .dealer-stat-value { font-size: 13px; font-weight: 600; color: var(--gd-text); margin-bottom: 2px; }
.gdDirectory .dealer-stat-label { font-size: 10px; color: var(--gd-text-subtle); text-transform: uppercase; letter-spacing: 0.04em; font-weight: 600; }

.gdDirectory .dealer-actions { display: flex; gap: 8px; }
.gdDirectory .dealer-btn { flex: 1; font-family: inherit; font-size: 12px; font-weight: 600; padding: 8px 12px; border-radius: var(--gd-r-md); cursor: pointer; border: 1px solid var(--gd-border); background: var(--gd-surface); color: var(--gd-text); text-align: center; line-height: 1.2; }
.gdDirectory .dealer-btn:hover { background: var(--gd-surface-muted); }
.gdDirectory .dealer-btn.primary { background: var(--gd-brand); color: #fff; border-color: var(--gd-brand); }
.gdDirectory .dealer-btn.primary:hover { background: var(--gd-brand-hover); border-color: var(--gd-brand-hover); color: #fff; }
.gdDirectory .dealer-btn.following { background: var(--gd-tier-pro-bg); color: var(--gd-brand); border-color: var(--gd-brand-border); }

.gdDirectory .empty-state { text-align: center; padding: 3rem 1.5rem; background: var(--gd-surface); border: 1px dashed var(--gd-border-strong); border-radius: var(--gd-r-lg); }
.gdDirectory .empty-state-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
.gdDirectory .empty-state-sub { font-size: 13px; color: var(--gd-text-subtle); }

.gdDirectory .pagination-row { display: flex; justify-content: center; margin-top: 1.5rem; }

@media (max-width: 768px) {
    .gdDirectory { padding: 1rem; }
    .gdDirectory .directory-hero { padding: 1.75rem 1.5rem; }
    .gdDirectory .hero-top { flex-direction: column; gap: 1rem; }
    .gdDirectory .hero-stats { width: 100%; justify-content: space-between; }
    .gdDirectory .hero-stat { text-align: left; }
    .gdDirectory .hero-title { font-size: 22px; }
    .gdDirectory .dealer-grid { grid-template-columns: 1fr; }
}
</style>

<div class="gdDirectory">

    <div class="directory-hero">
        <div class="hero-top">
            <div class="hero-title-block">
                <div class="hero-eyebrow">Dealer directory</div>
                <h1 class="hero-title">Find a trusted FFL dealer</h1>
                <p class="hero-sub">Every dealer on gunrack.deals is a verified FFL holder. Browse {$total} active dealers, compare ratings, and shop from the ones that match your needs.</p>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value">{$total}</div>
                    <div class="hero-stat-label">Dealers</div>
                </div>
            </div>
        </div>
        <div class="hero-cta-row">
            <a href="{$joinUrl}" class="hero-cta">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Become a dealer
            </a>
        </div>
    </div>

    {{if count($dealers) === 0}}
    <div class="empty-state">
        <div class="empty-state-title">No dealers yet</div>
        <div class="empty-state-sub">Check back soon — new dealers join every week.</div>
    </div>
    {{else}}
    <div class="dealer-grid">
        {{foreach $dealers as $d}}
        <div class="dealer-card">
            <div class="dealer-head">
                <a href="{$d['profile_url']}" class="dealer-avatar">
                    {{if $d['avatar']}}<img src="{$d['avatar']}" alt="" loading="lazy">{{else}}{expression="mb_strtoupper(mb_substr($d['dealer_name'], 0, 1))"}{{endif}}
                </a>
                <div class="dealer-identity">
                    <div class="dealer-name-row">
                        <a href="{$d['profile_url']}"><span class="dealer-name">{$d['dealer_name']}</span></a>
                        {{if !empty($d['verified'])}}
                        <span class="dealer-verified" title="Verified FFL">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        {{endif}}
                    </div>
                    <span class="dealer-tier dealer-tier-{$d['tier']}">{$d['tier_label']}</span>
                </div>
            </div>

            <div class="dealer-rating-row">
                <span class="dealer-rating-value">{$d['avg_overall']}</span>
                <span class="dealer-rating-count">&middot; {$d['total_reviews']} reviews</span>
            </div>

            <div class="dealer-stats">
                <div class="dealer-stat">
                    <div class="dealer-stat-value">{$d['listing_count']}</div>
                    <div class="dealer-stat-label">Listings</div>
                </div>
                <div class="dealer-stat">
                    <div class="dealer-stat-value">{$d['member_since']}</div>
                    <div class="dealer-stat-label">Member since</div>
                </div>
            </div>

            <div class="dealer-actions">
                {{if $loggedIn}}
                <a href="{$d['follow_url']}" class="dealer-btn{{if $d['is_following']}} following{{endif}}">
                    {{if $d['is_following']}}&#10003; Following{{else}}Follow{{endif}}
                </a>
                {{endif}}
                <a href="{$d['profile_url']}" class="dealer-btn primary">View profile &rarr;</a>
            </div>
        </div>
        {{endforeach}}
    </div>

    {{if $pagination}}
    <div class="pagination-row">{$pagination|raw}</div>
    {{endif}}
    {{endif}}

</div>
TEMPLATE_EOT;

        \IPS\Db::i()->update(
            'core_theme_templates',
            [
                'template_data'    => '$dealers, $total, $page, $perPage, $pagination, $tier, $sort, $search, $loggedIn, $joinUrl, $directoryUrl',
                'template_content' => $directoryBody,
                'template_updated' => time(),
            ],
            [ 'template_app=? AND template_group=? AND template_name=?', 'gddealer', 'dealers', 'dealerDirectory' ]
        );

        return TRUE;
    }

    public function step1CustomTitle(): string
    {
        return "Replacing dealerDirectory template with --gd-* design-token layout";
    }

    public function step2(): bool
    {
        try { \IPS\Db::i()->delete( 'core_cache' ); }
        catch ( \Throwable ) {}

        try { \IPS\Db::i()->delete( 'core_store', [ "store_key LIKE 'theme_%' OR store_key LIKE 'template_%'" ] ); }
        catch ( \Throwable ) {}

        try
        {
            foreach ( glob( \IPS\ROOT_PATH . '/datastore/template_*dealers*' ) ?: [] as $f )
            {
                @unlink( $f );
            }
        }
        catch ( \Throwable ) {}

        try { \IPS\Theme::deleteCompiledTemplate( 'gddealer', 'front', 'dealers' ); }
        catch ( \Throwable ) {}

        try { \IPS\Db::i()->update( 'core_themes', [ 'set_cache_key' => md5( microtime() . mt_rand() ) ] ); }
        catch ( \Throwable ) {}

        try { \IPS\Log::log( 'v1.0.132 upgrade completed — dealerDirectory template replaced', 'gddealer_upg_10132' ); }
        catch ( \Throwable ) {}

        return TRUE;
    }

    public function step2CustomTitle(): string
    {
        return "Busting template caches for dealerDirectory";
    }
}
class upgrade extends _upgrade {}
