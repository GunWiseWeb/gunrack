<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

try
{
	$row = \IPS\Db::i()->select( 'template_content', 'core_theme_templates',
		[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		  'gddealer', 'front', 'dealers', 'dashboardCustomize' ]
	)->first();
}
catch ( \Throwable ) { $row = ''; }

$body = (string) $row;
if ( $body === '' ) { return; }

$marker = '<!-- GD_FFL_SECTION_INJECTED_v10111 -->';
if ( strpos( $body, $marker ) !== false ) { return; }

/* FFL section to inject. Status banners use inline styles so they render the same
   across all themes. Form inputs use the plugin's existing gdPanel__input pattern. */
$fflSection = <<<'HTML'
<!-- GD_FFL_SECTION_INJECTED_v10111 -->
<div class="gdPanel gdFflSection" style="margin-bottom:24px;">
	<div class="gdPanel__head">
		<h2 class="gdPanel__title">FFL license</h2>
		<p class="gdPanel__sub">Required for the verified badge on your public profile. Admin reviews within 24 hours.</p>
	</div>

	{{if $data['profile']['ffl_status'] === 'pending'}}
	<div style="background:#fef3c7;border:1px solid #fde68a;color:#92400e;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
		<span><strong>Pending review.</strong> We'll email you once a site admin has verified your license (usually within 24 hours).</span>
	</div>
	{{elseif $data['profile']['ffl_status'] === 'verified'}}
	<div style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 4 5v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V5l-8-3zm-1.4 14.2L7 12.6l1.4-1.4 2.2 2.2 4.4-4.4L16.4 10l-5.8 6.2z"/></svg>
		<span><strong>FFL verified.</strong> The green checkmark now appears on your public profile.</span>
	</div>
	{{elseif $data['profile']['ffl_status'] === 'rejected'}}
	<div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
		<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
			<strong>Rejected (attempt {$data['profile']['ffl_rejection_count']} of 3).</strong>
		</div>
		<div style="font-size:13px;line-height:1.5;">Reason: {$data['profile']['ffl_rejection_reason']}</div>
		<div style="font-size:12px;margin-top:8px;opacity:0.85;">Update your FFL number or license URL below and save to re-submit for review.</div>
	</div>
	{{elseif $data['profile']['ffl_status'] === 'blocked'}}
	<div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
		<span><strong>Verification blocked.</strong> Your submission has been rejected 3 times. Please contact support to resolve.</span>
	</div>
	{{endif}}

	<div class="gdPanel__body">
		<label class="gdPanel__label" for="gd_ffl_number">FFL number</label>
		<input type="text" id="gd_ffl_number" name="ffl_number" class="gdPanel__input" pattern="\d-\d{2}-\d{5}" placeholder="3-37-06855" value="{$data['profile']['ffl_number']}" {{if $data['profile']['ffl_blocked']}}disabled{{endif}} style="font-family:ui-monospace,'SF Mono',Menlo,monospace;">
		<p class="gdPanel__hint" style="font-size:12px;color:#64748b;margin-top:4px;">Format: X-XX-XXXXX (shown on your Federal Firearms License, e.g. 3-37-06855).</p>

		<label class="gdPanel__label" for="gd_ffl_license_url" style="margin-top:16px;">FFL license URL</label>
		<input type="url" id="gd_ffl_license_url" name="ffl_license_url" class="gdPanel__input" placeholder="https://drive.google.com/..." value="{$data['profile']['ffl_license_url']}" {{if $data['profile']['ffl_blocked']}}disabled{{endif}}>
		<p class="gdPanel__hint" style="font-size:12px;color:#64748b;margin-top:4px;">Upload your FFL license to Dropbox, Google Drive, or your own host and paste the public/shareable direct link here.</p>
	</div>
</div>

HTML;

/* Inject the FFL section immediately above the Identity section.
   Identity section is marked by `<h2 class="gdPanel__title">Identity</h2>` — we
   locate the enclosing gdPanel opening tag and insert BEFORE it. Falls back to
   prepending to the form body if the anchor isn't found. */
$identityAnchor = '<div class="gdPanel" ';
$identityIndex  = strpos( $body, $identityAnchor );
if ( $identityIndex === false )
{
	$identityAnchor = '<div class="gdPanel">';
	$identityIndex  = strpos( $body, $identityAnchor );
}

if ( $identityIndex !== false )
{
	$body = substr( $body, 0, $identityIndex ) . $fflSection . substr( $body, $identityIndex );
}
else
{
	/* Fallback: inject after <form> opening tag. */
	$body = preg_replace( '#(<form[^>]*>)#', '$1' . "\n" . $fflSection, $body, 1 );
}

try
{
	\IPS\Db::i()->update( 'core_theme_templates',
		[ 'template_content' => $body, 'template_updated' => time() ],
		[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		  'gddealer', 'front', 'dealers', 'dashboardCustomize' ]
	);
}
catch ( \Throwable ) {}
