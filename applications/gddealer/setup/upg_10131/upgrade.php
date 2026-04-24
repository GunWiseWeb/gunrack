<?php
namespace IPS\gddealer\setup\upg_10131;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $cleanFeedSettings = <<<'TPL'
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
    <div class="gdSyncBanner__body">
        <div class="gdSyncBanner__title">{$data['sync_title']}</div>
        <div class="gdSyncBanner__sub">{$data['sync_sub']}</div>
    </div>
</div>

{{if $data['latest']}}
<div class="gdSyncStats">
    <div class="gdSyncStat"><div class="gdSyncStat__label">Total records</div><div class="gdSyncStat__value">{expression="number_format($data['latest']['records'])"}</div></div>
    <div class="gdSyncStat"><div class="gdSyncStat__label">New</div><div class="gdSyncStat__value">{expression="number_format($data['latest']['new'])"}</div></div>
    <div class="gdSyncStat"><div class="gdSyncStat__label">Updated</div><div class="gdSyncStat__value">{expression="number_format($data['latest']['updated'])"}</div></div>
    <div class="gdSyncStat"><div class="gdSyncStat__label">Unmatched</div><div class="gdSyncStat__value">{expression="number_format($data['latest']['unmatched'])"}</div></div>
</div>
{{endif}}

<div class="gdContentSplit">
    <div>
        <div class="gdPanel">
            <div class="gdPanel__head">
                <div>
                    <div class="gdPanel__title">Feed configuration</div>
                    <div class="gdPanel__sub">Point us at your feed.</div>
                </div>
            </div>
            {$data['form']|raw}
        </div>
    </div>
    <div>
        <div class="gdRailCard">
            <div class="gdRailCard__title">Import history</div>
            {{if count($data['import_log']) === 0}}
            <p style="color:#64748b;font-size:13px;margin:0;">No imports yet.</p>
            {{else}}
            <table class="gdTable">
                <thead><tr><th>When</th><th>Status</th><th class="is-num">Records</th></tr></thead>
                <tbody>
                {{foreach $data['import_log'] as $row}}
                    <tr><td>{$row['when_ago']}</td><td>{$row['status']}</td><td class="is-num">{$row['records']}</td></tr>
                {{endforeach}}
                </tbody>
            </table>
            {{endif}}
        </div>
    </div>
</div>

<div id="gdManualUploadPanel" style="display:{{if !empty($data['delivery_mode']) && $data['delivery_mode'] === 'manual'}}block{{else}}none{{endif}};margin-top:16px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:24px;">
    <div style="margin-bottom:18px;">
        <h2 style="margin:0 0 4px;font-size:16px;font-weight:600;color:#0f172a;">Upload feed file</h2>
        <p style="margin:0;font-size:13px;color:#64748b;">Drop a CSV, XML, or JSON file from your store's export. The system will import it on the next sync cycle.</p>
    </div>
    <form method="post" action="{$data['upload_url']}" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
        <input type="file" name="gddealer_front_feed_file" accept=".csv,.xml,.json,.tsv,.txt" required style="display:block;width:100%;padding:12px;border:1px dashed #cbd5e1;border-radius:6px;background:#f8fafc;font:inherit;cursor:pointer;margin-bottom:12px;">
        <button type="submit" style="padding:9px 18px;background:#1e40af;color:#fff;border:0;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;">Upload feed file</button>
    </form>
    {{if !empty($data['recent_uploads'])}}
    <div style="margin-top:24px;padding-top:18px;border-top:1px solid #f1f5f9;">
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">Recent uploads</div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead><tr><th style="text-align:left;padding:6px 8px;color:#64748b;font-size:11px;text-transform:uppercase;">File</th><th style="text-align:left;padding:6px 8px;color:#64748b;font-size:11px;text-transform:uppercase;">Format</th><th style="text-align:left;padding:6px 8px;color:#64748b;font-size:11px;text-transform:uppercase;">Size</th><th style="text-align:left;padding:6px 8px;color:#64748b;font-size:11px;text-transform:uppercase;">Uploaded</th></tr></thead>
            <tbody>
            {{foreach $data['recent_uploads'] as $u}}
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:8px;font-family:ui-monospace,Menlo,monospace;font-size:12px;">{$u['file_name']}</td>
                    <td style="padding:8px;text-transform:uppercase;font-size:11px;font-weight:600;">{$u['upload_format']}</td>
                    <td style="padding:8px;">{expression="number_format((int)$u['file_size_bytes']/1024)"} KB</td>
                    <td style="padding:8px;">{$u['uploaded_ago']}</td>
                </tr>
            {{endforeach}}
            </tbody>
        </table>
    </div>
    {{endif}}
</div>

<script>
(function(){
    function move(){
        var panel = document.getElementById('gdManualUploadPanel');
        var radios = document.querySelectorAll('input[type=radio][name="gddealer_front_feed_delivery_mode"]');
        if (!panel || !radios.length) return setTimeout(move, 100);
        var container = radios[0].closest('[data-role="formItem"], .ipsForm__row, fieldset') || radios[0].closest('li, div');
        if (container && container.parentNode) {
            container.parentNode.insertBefore(panel, container.nextSibling);
        }
        function sync(){
            var checked = document.querySelector('input[type=radio][name="gddealer_front_feed_delivery_mode"]:checked');
            panel.style.display = (checked && checked.value === 'manual') ? 'block' : 'none';
        }
        radios.forEach(function(r){ r.addEventListener('change', sync); });
        sync();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', move);
    else move();
})();
</script>
TPL;

        \IPS\Db::i()->update(
            'core_theme_templates',
            ['template_content' => $cleanFeedSettings, 'template_updated' => time()],
            ['template_app=? AND template_group=? AND template_name=?', 'gddealer', 'dealers', 'feedSettings']
        );

        \IPS\Db::i()->delete('core_cache');
        \IPS\Db::i()->delete('core_store', ["store_key LIKE 'theme_%' OR store_key LIKE 'template_%'"]);
        foreach (glob(\IPS\ROOT_PATH . '/datastore/template_*dealers*') ?: [] as $f) {
            @unlink($f);
        }

        try { \IPS\Log::log( 'v1.0.131 upgrade completed — feedSettings template replaced with clean heredoc', 'gddealer_upg_10131' ); }
        catch ( \Throwable ) {}

        return TRUE;
    }

    public function step1CustomTitle()
    {
        return "Restoring clean feedSettings template with inline upload panel";
    }
}
class upgrade extends _upgrade {}
