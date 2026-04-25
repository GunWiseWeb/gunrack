<?php
namespace IPS\gddealer\setup\upg_10132;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $newDirectoryTpl = <<<'TPL'
<div class="gd-directory">
<style>
.gd-directory {
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
.gd-directory *, .gd-directory *::before, .gd-directory *::after { box-sizing: border-box; }
.gd-directory a { color: inherit; text-decoration: none; }
.gd-directory h1, .gd-directory h2, .gd-directory h3 { margin: 0; font-weight: 600; }
.gd-directory p { margin: 0; }

.gd-directory .directory-hero { background: linear-gradient(135deg, var(--gd-surface) 0%, #EFF4FA 100%); border: 1px solid var(--gd-border); border-radius: var(--gd-r-xl); padding: 2.5rem 2.5rem 2rem; margin-bottom: 1.5rem; position: relative; overflow: hidden; }
.gd-directory .directory-hero::after { content: ''; position: absolute; top: -40%; right: -10%; width: 420px; height: 420px; background: radial-gradient(circle, rgba(30, 64, 175, 0.08) 0%, transparent 60%); pointer-events: none; }
.gd-directory .hero-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem; margin-bottom: 1.5rem; position: relative; z-index: 1; }
.gd-directory .hero-title-block { flex: 1; }
.gd-directory .hero-eyebrow { font-size: 12px; font-weight: 600; color: var(--gd-brand); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
.gd-directory .hero-title { font-size: 36px; font-weight: 600; letter-spacing: -0.025em; line-height: 1.15; margin-bottom: 10px; margin-top: 0; }
.gd-directory .hero-sub { font-size: 15px; color: var(--gd-text-muted); max-width: 560px; line-height: 1.6; margin: 0; }
.gd-directory .hero-stats { display: flex; gap: 2rem; align-items: center; flex-shrink: 0; }
.gd-directory .hero-stat { text-align: center; }
.gd-directory .hero-stat-value { font-size: 28px; font-weight: 600; letter-spacing: -0.02em; color: var(--gd-text); line-height: 1; }
.gd-directory .hero-stat-label { font-size: 11px; color: var(--gd-text-subtle); text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; margin-top: 6px; }

.gd-directory .search-row { display: flex; gap: 10px; align-items: center; position: relative; z-index: 1; }
.gd-directory .search-input { flex: 1; position: relative; }
.gd-directory .search-input input { width: 100%; font-family: inherit; font-size: 14px; padding: 12px 16px 12px 44px; border-radius: var(--gd-r-lg); border: 1px solid var(--gd-border); background: var(--gd-surface); color: var(--gd-text); transition: all 0.15s; box-sizing: border-box; }
.gd-directory .search-input input:focus { outline: none; border-color: var(--gd-brand); box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.1); }
.gd-directory .search-input input::placeholder { color: var(--gd-text-faint); }
.gd-directory .search-input svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--gd-text-faint); }
.gd-directory .search-submit { padding: 12px 20px; border-radius: var(--gd-r-lg); background: var(--gd-brand); color: white; border: none; font-family: inherit; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.15s; white-space: nowrap; }
.gd-directory .search-submit:hover { background: var(--gd-brand-hover); }

.gd-directory .filters-bar { background: var(--gd-surface); border: 1px solid var(--gd-border); border-radius: var(--gd-r-lg); padding: 14px 18px; margin-bottom: 1.25rem; display: flex; gap: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
.gd-directory .filters-left { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.gd-directory .filter-group-label { font-size: 12px; color: var(--gd-text-subtle); font-weight: 500; margin-right: 4px; }
.gd-directory .filter-chip { font-size: 12px; padding: 6px 12px; border-radius: var(--gd-r-pill); background: var(--gd-surface-muted); color: var(--gd-text-muted); cursor: pointer; font-weight: 500; border: 1px solid transparent; transition: all 0.15s; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; }
.gd-directory .filter-chip:hover { background: var(--gd-border-subtle); color: var(--gd-text); }
.gd-directory .filter-chip.active { background: var(--gd-brand-light); color: var(--gd-brand); border-color: var(--gd-brand-border); }
.gd-directory .filters-right { display: flex; gap: 10px; align-items: center; }
.gd-directory .sort-label { font-size: 12px; color: var(--gd-text-subtle); font-weight: 500; }
.gd-directory .select-sort { font-family: inherit; font-size: 13px; padding: 7px 28px 7px 10px; border-radius: var(--gd-r-md); border: 1px solid var(--gd-border); background: var(--gd-surface); cursor: pointer; color: var(--gd-text); appearance: none; }

.gd-directory .results-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-size: 13px; color: var(--gd-text-subtle); }
.gd-directory .results-count strong { color: var(--gd-text); font-weight: 600; }
.gd-directory .view-toggle { display: inline-flex; gap: 2px; padding: 2px; background: var(--gd-border-subtle); border-radius: var(--gd-r-md); }
.gd-directory .view-toggle button { font-family: inherit; font-size: 12px; padding: 5px 10px; border-radius: 4px; border: none; background: transparent; cursor: pointer; color: var(--gd-text-subtle); font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }
.gd-directory .view-toggle button.active { background: var(--gd-surface); color: var(--gd-text); box-shadow: 0 1px 2px rgba(0,0,0,0.05); }

.gd-directory .dealer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 2rem; }
.gd-directory .dealer-card { background: var(--gd-surface); border: 1px solid var(--gd-border); border-radius: var(--gd-r-lg); padding: 1.25rem; transition: all 0.15s; display: flex; flex-direction: column; }
.gd-directory .dealer-card:hover { border-color: var(--gd-border-strong); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.04); }

.gd-directory .dealer-head { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 14px; }
.gd-directory .dealer-avatar { width: 48px; height: 48px; border-radius: var(--gd-r-lg); background: var(--gd-brand); color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 18px; flex-shrink: 0; overflow: hidden; }
.gd-directory .dealer-avatar img { width: 100%; height: 100%; object-fit: cover; }
.gd-directory .dealer-identity { flex: 1; min-width: 0; }
.gd-directory .dealer-name-row { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.gd-directory .dealer-name { font-size: 15px; font-weight: 600; letter-spacing: -0.01em; color: var(--gd-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.gd-directory .dealer-tier { font-size: 10px; padding: 2px 8px; border-radius: var(--gd-r-pill); font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; display: inline-flex; align-items: center; gap: 3px; }
.gd-directory .dealer-tier-founding { background: var(--gd-tier-founding-bg); color: var(--gd-tier-founding); }
.gd-directory .dealer-tier-basic { background: var(--gd-tier-basic-bg); color: var(--gd-tier-basic); }
.gd-directory .dealer-tier-pro { background: var(--gd-tier-pro-bg); color: var(--gd-tier-pro); }
.gd-directory .dealer-tier-enterprise { background: var(--gd-tier-enterprise-bg); color: var(--gd-tier-enterprise); }

.gd-directory .dealer-rating-row { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; font-size: 13px; }
.gd-directory .dealer-rating-stars { display: inline-flex; gap: 2px; color: var(--gd-star); }
.gd-directory .dealer-rating-stars svg { width: 13px; height: 13px; }
.gd-directory .dealer-rating-value { font-weight: 600; color: var(--gd-text); font-variant-numeric: tabular-nums; }
.gd-directory .dealer-rating-count { color: var(--gd-text-subtle); font-size: 12px; }

.gd-directory .dealer-stats { display: flex; gap: 12px; padding: 12px 0; border-top: 1px solid var(--gd-border-subtle); border-bottom: 1px solid var(--gd-border-subtle); margin-bottom: 14px; }
.gd-directory .dealer-stat { flex: 1; min-width: 0; }
.gd-directory .dealer-stat-value { font-size: 15px; font-weight: 600; color: var(--gd-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.gd-directory .dealer-stat-label { font-size: 11px; color: var(--gd-text-subtle); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.04em; font-weight: 500; }

.gd-directory .dealer-actions { display: flex; gap: 6px; margin-top: auto; }
.gd-directory .dealer-btn { flex: 1; font-family: inherit; font-size: 12px; font-weight: 500; padding: 8px 10px; border-radius: var(--gd-r-md); border: 1px solid var(--gd-border); background: var(--gd-surface); color: var(--gd-text); cursor: pointer; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 4px; transition: all 0.15s; text-decoration: none; }
.gd-directory .dealer-btn:hover { background: var(--gd-surface-muted); border-color: var(--gd-border-strong); }
.gd-directory .dealer-btn.primary { background: var(--gd-brand); color: white; border-color: var(--gd-brand); }
.gd-directory .dealer-btn.primary:hover { background: var(--gd-brand-hover); }
.gd-directory .dealer-btn.following { background: var(--gd-brand-light); color: var(--gd-brand); border-color: var(--gd-brand-border); }

.gd-directory .gd-pagination { display: flex; justify-content: center; margin-top: 1rem; }

.gd-directory .gd-empty { text-align: center; padding: 4rem 2rem; background: var(--gd-surface); border: 1px solid var(--gd-border); border-radius: var(--gd-r-lg); color: var(--gd-text-subtle); }
.gd-directory .gd-empty h3 { font-size: 16px; font-weight: 600; color: var(--gd-text); margin: 0 0 6px; }
.gd-directory .gd-empty p { font-size: 13px; margin: 0; }

.gd-directory .join-banner { background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); border-radius: var(--gd-r-xl); padding: 2rem 2.5rem; margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 2rem; color: white; }
.gd-directory .join-banner-content { flex: 1; }
.gd-directory .join-banner-title { font-size: 20px; font-weight: 600; letter-spacing: -0.015em; margin-bottom: 6px; }
.gd-directory .join-banner-sub { font-size: 14px; color: #CBD5E1; line-height: 1.5; max-width: 480px; margin: 0; }
.gd-directory .join-banner-cta { padding: 10px 20px; background: white; color: var(--gd-text); border-radius: var(--gd-r-md); font-weight: 500; font-size: 14px; text-decoration: none; transition: all 0.15s; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; }
.gd-directory .join-banner-cta:hover { background: var(--gd-surface-muted); }
.gd-directory .join-banner-cta svg { width: 14px; height: 14px; }

@media (max-width: 900px) {
    .gd-directory .hero-top { flex-direction: column; gap: 1.5rem; }
    .gd-directory .hero-stats { width: 100%; justify-content: space-around; }
    .gd-directory .filters-bar { flex-direction: column; align-items: stretch; }
    .gd-directory .filters-left, .gd-directory .filters-right { justify-content: flex-start; }
    .gd-directory .join-banner { flex-direction: column; align-items: flex-start; padding: 1.5rem; }
}
@media (max-width: 640px) {
    .gd-directory { padding: 1rem; }
    .gd-directory .directory-hero { padding: 1.5rem; }
    .gd-directory .hero-title { font-size: 28px; }
    .gd-directory .dealer-grid { grid-template-columns: 1fr; }
    .gd-directory .search-row { flex-direction: column; }
    .gd-directory .search-submit { width: 100%; }
}
</style>
<!-- HEADER_MARKER -->
<!-- CARDS_MARKER -->
</div>
TPL;

        \IPS\Db::i()->update(
            'core_theme_templates',
            [ 'template_content' => $newDirectoryTpl, 'template_updated' => time() ],
            [ 'template_app=? AND template_group=? AND template_name=?', 'gddealer', 'dealers', 'dealerDirectory' ]
        );

        \IPS\Db::i()->delete( 'core_cache' );
        \IPS\Db::i()->delete( 'core_store', [ "store_key LIKE 'theme_%' OR store_key LIKE 'template_%'" ] );
        foreach ( glob( \IPS\ROOT_PATH . '/datastore/template_*dealers*' ) ?: [] as $f ) { @unlink( $f ); }

        return TRUE;
    }

    public function step1CustomTitle()
    {
        return 'Rebuilding dealer directory with the new design system';
    }
}
class upgrade extends _upgrade {}
