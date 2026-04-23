<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/* ─── PART 1: Extract the real Edit-profile template body from the v10088 file ─── */
$v10088Path = \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10088.php';
if ( !is_file( $v10088Path ) ) { return; }

$v10088Source = (string) file_get_contents( $v10088Path );
if ( $v10088Source === '' ) { return; }

/* Match the heredoc body. The content lives between <<<'TEMPLATE_EOT' and
   TEMPLATE_EOT on its own line followed by a comma. Use ungreedy matching. */
if ( !preg_match( "/<<<'TEMPLATE_EOT'\s*\n(.*?)\nTEMPLATE_EOT,/s", $v10088Source, $m ) )
{
    return;
}
$editProfileBody = (string) $m[1];
if ( strpos( $editProfileBody, 'Edit dealer profile' ) === false )
{
    /* Sanity check failed — bail rather than overwriting the DB with broken content. */
    return;
}

/* ─── PART 2: Inject FFL section above the Identity section ─── */
$fflSection = <<<'HTML'
<!-- GD_FFL_SECTION_INJECTED_v10113 -->
<div class="gdPanel" style="margin-bottom:24px;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:24px;">
	<div class="gdPanel__head" style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;">
		<h2 style="margin:0 0 4px 0;font-size:16px;font-weight:600;color:#0f172a;">FFL license</h2>
		<p style="margin:0;font-size:13px;color:#64748b;">Required for the verified badge on your public profile. Admin reviews within 24 hours.</p>
	</div>

	{{if $data['profile']['ffl_status'] === 'pending'}}
	<div style="background:#fef3c7;border:1px solid #fde68a;color:#92400e;padding:12px 14px;border-radius:6px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
		<div style="font-size:13px;line-height:1.5;"><strong>Pending review.</strong> We'll email you once a site admin has verified your license (usually within 24 hours).</div>
	</div>
	{{elseif $data['profile']['ffl_status'] === 'verified'}}
	<div style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:12px 14px;border-radius:6px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;margin-top:1px;"><path d="M12 2 4 5v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V5l-8-3zm-1.4 14.2L7 12.6l1.4-1.4 2.2 2.2 4.4-4.4L16.4 10l-5.8 6.2z"/></svg>
		<div style="font-size:13px;line-height:1.5;"><strong>FFL verified.</strong> The green checkmark now appears on your public profile.</div>
	</div>
	{{elseif $data['profile']['ffl_status'] === 'rejected'}}
	<div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 14px;border-radius:6px;margin-bottom:20px;">
		<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;font-size:13px;">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
			<strong>Rejected (attempt {$data['profile']['ffl_rejection_count']} of 3).</strong>
		</div>
		<div style="font-size:13px;line-height:1.5;margin-left:28px;">Reason: {$data['profile']['ffl_rejection_reason']}</div>
		<div style="font-size:12px;margin-top:8px;margin-left:28px;opacity:0.85;">Update your FFL number or license URL below and save to re-submit for review.</div>
	</div>
	{{elseif $data['profile']['ffl_status'] === 'blocked'}}
	<div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 14px;border-radius:6px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
		<div style="font-size:13px;line-height:1.5;"><strong>Verification blocked.</strong> Your submission has been rejected 3 times. Please contact support to resolve.</div>
	</div>
	{{endif}}

	<div class="gdFormGrid2" style="display:grid;grid-template-columns:1fr;gap:16px;">
		<div>
			<label for="gd_ffl_number" style="display:block;font-size:13px;font-weight:600;color:#0f172a;margin-bottom:6px;">FFL number</label>
			<input type="text" id="gd_ffl_number" name="ffl_number" class="ipsInput ipsInput--text" pattern="\d-\d{2}-\d{5}" placeholder="3-37-06855" value="{$data['profile']['ffl_number']}" {{if $data['profile']['ffl_blocked']}}disabled{{endif}} style="width:100%;font-family:ui-monospace,'SF Mono',Menlo,monospace;box-sizing:border-box;">
			<p style="margin:6px 0 0 0;font-size:12px;color:#64748b;">Format: X-XX-XXXXX (shown on your Federal Firearms License, e.g. 3-37-06855).</p>
		</div>

		<div>
			<label for="gd_ffl_license_url" style="display:block;font-size:13px;font-weight:600;color:#0f172a;margin-bottom:6px;">FFL license URL</label>
			<input type="url" id="gd_ffl_license_url" name="ffl_license_url" class="ipsInput ipsInput--text" placeholder="https://drive.google.com/..." value="{$data['profile']['ffl_license_url']}" {{if $data['profile']['ffl_blocked']}}disabled{{endif}} style="width:100%;box-sizing:border-box;">
			<p style="margin:6px 0 0 0;font-size:12px;color:#64748b;">Upload your FFL license to Dropbox, Google Drive, or your own host and paste the public/shareable direct link here.</p>
		</div>
	</div>
</div>

HTML;

/* Inject the FFL section at the start of the form, right after <form ...> opens.
   The v10088 Edit profile template has exactly one <form method="post"> tag. */
$finalBody = preg_replace(
    '#(<form\s+method="post"[^>]*>\s*<input\s+type="hidden"\s+name="csrfKey"[^>]*>)#',
    "$1\n" . $fflSection,
    $editProfileBody,
    1
);

/* Sanity check: FFL section was actually inserted. */
if ( strpos( (string) $finalBody, 'GD_FFL_SECTION_INJECTED_v10113' ) === false )
{
    /* Fallback: prepend to the body (won't look as clean but won't lose the form). */
    $finalBody = $fflSection . $editProfileBody;
}

/* ─── PART 3: Write to both set_id rows via update() keyed on (app, location, group, name) ─── */
/* Rule 18: Never use replace() with set_id=0 on existing templates. Use update()
   keyed on the natural key so both rows (set_id=0 and set_id=1) get patched. */
try
{
    \IPS\Db::i()->update( 'core_theme_templates',
        [
            'template_data'    => '$data',
            'template_content' => $finalBody,
            'template_updated' => time(),
        ],
        [ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
          'gddealer', 'front', 'dealers', 'dashboardCustomize' ]
    );
}
catch ( \Throwable ) {}
