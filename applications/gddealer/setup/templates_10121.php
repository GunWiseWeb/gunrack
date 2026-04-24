<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

try
{
	$row = \IPS\Db::i()->select( 'template_content', 'core_theme_templates',
		[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		  'gddealer', 'front', 'dealers', 'overview' ]
	)->first();
}
catch ( \Throwable ) { $row = ''; }

$body = (string) $row;
if ( $body === '' ) { return; }

/* Strip the broken v10120 injection (everything from its marker to end of body). */
if ( strpos( $body, 'GD_VERIFIED_BADGE_CARD_v10120' ) !== false )
{
	$body = preg_replace( '#<!-- GD_VERIFIED_BADGE_CARD_v10120 -->.*$#s', '', $body );
}

$marker = '<!-- GD_VERIFIED_BADGE_CARD_v10121 -->';
if ( strpos( $body, $marker ) !== false ) { return; }

$badgeCard = <<<'HTML'
<!-- GD_VERIFIED_BADGE_CARD_v10121 -->
{{if !empty( $data['verified_badge']['show'] )}}
<div class="gdPanel" id="gdBadgePicker" data-badges="{expression="htmlspecialchars( json_encode( $data['verified_badge']['badges'] ?? [] ), ENT_QUOTES, 'UTF-8' )"}" data-profile-url="{$data['verified_badge']['profile_url']}" style="margin-top:24px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:24px;">
	<div style="margin-bottom:18px;">
		<h2 style="margin:0 0 4px;font-size:16px;font-weight:600;color:#0f172a;display:flex;align-items:center;gap:8px;">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="#10b981" style="flex-shrink:0;"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
			Get your verified-dealer badge
		</h2>
		<p style="margin:0;font-size:13px;color:#64748b;">Show customers you're a verified Gun Rack Deals dealer. Pick a style and paste the embed code on your website.</p>
	</div>

	<div style="display:grid;grid-template-columns:1fr;gap:16px;">
		<div>
			<label for="gd_badge_picker" style="display:block;font-size:13px;font-weight:600;color:#0f172a;margin-bottom:6px;">Badge style</label>
			<select id="gd_badge_picker" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;font:inherit;background:#fff;">
				{{foreach $data['verified_badge']['badges'] as $b}}
				<option value="{$b['id']}">{$b['label']}</option>
				{{endforeach}}
			</select>
		</div>

		<div>
			<div style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">Live preview</div>
			<div style="padding:24px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;min-height:90px;">
				<a id="gd_badge_preview_link" href="{$data['verified_badge']['profile_url']}" target="_blank" rel="noopener">
					<img id="gd_badge_preview_img" src="" alt="Verified Gun Rack Deals dealer">
				</a>
			</div>
		</div>

		<div>
			<div style="display:flex;gap:6px;border-bottom:1px solid #e5e7eb;margin-bottom:0;">
				<button type="button" data-tab="html" class="gdBadgeTab" style="padding:8px 14px;border:0;border-bottom:2px solid #1e40af;background:none;color:#1e40af;font-weight:600;font-size:13px;cursor:pointer;">HTML embed</button>
				<button type="button" data-tab="md" class="gdBadgeTab" style="padding:8px 14px;border:0;border-bottom:2px solid transparent;background:none;color:#64748b;font-weight:500;font-size:13px;cursor:pointer;">Markdown</button>
				<button type="button" data-tab="link" class="gdBadgeTab" style="padding:8px 14px;border:0;border-bottom:2px solid transparent;background:none;color:#64748b;font-weight:500;font-size:13px;cursor:pointer;">Direct link</button>
			</div>
			<div style="position:relative;">
				<textarea id="gd_badge_code" readonly rows="4" style="width:100%;padding:12px;border:1px solid #e5e7eb;border-top:0;border-radius:0 0 6px 6px;font-family:ui-monospace,'SF Mono',Menlo,monospace;font-size:12px;color:#0f172a;background:#f8fafc;resize:vertical;box-sizing:border-box;"></textarea>
				<button type="button" id="gd_badge_copy_btn" style="position:absolute;top:8px;right:8px;padding:6px 12px;background:#1e40af;color:#fff;border:0;border-radius:5px;font-size:12px;font-weight:600;cursor:pointer;">Copy</button>
			</div>
		</div>
	</div>
</div>

<script>
(function() {
	var wrap = document.getElementById('gdBadgePicker');
	if (!wrap) return;

	var BADGES = {};
	try { BADGES = JSON.parse(wrap.getAttribute('data-badges') || '{}'); }
	catch (e) { console.error('Badge data parse failed', e); return; }

	var PROFILE = wrap.getAttribute('data-profile-url') || '';
	var currentTab = 'html';

	function refresh() {
		var sel = document.getElementById('gd_badge_picker');
		var bid = sel ? sel.value : Object.keys(BADGES)[0];
		var b = BADGES[bid];
		if (!b) return;
		var img  = document.getElementById('gd_badge_preview_img');
		var link = document.getElementById('gd_badge_preview_link');
		if (img)  { img.src = b.svg; img.width = b.width; img.height = b.height; }
		if (link) { link.href = PROFILE; }
		render();
	}

	function switchTab(tab) {
		currentTab = tab;
		var btns = wrap.querySelectorAll('.gdBadgeTab');
		btns.forEach(function(btn) {
			var on = btn.getAttribute('data-tab') === tab;
			btn.style.color = on ? '#1e40af' : '#64748b';
			btn.style.borderBottomColor = on ? '#1e40af' : 'transparent';
			btn.style.fontWeight = on ? '600' : '500';
		});
		render();
	}

	function render() {
		var sel = document.getElementById('gd_badge_picker');
		var bid = sel ? sel.value : Object.keys(BADGES)[0];
		var b = BADGES[bid];
		if (!b) return;
		var ta = document.getElementById('gd_badge_code');
		if (!ta) return;
		var out = '';
		if (currentTab === 'html') {
			out = '<a href="' + PROFILE + '" target="_blank" rel="noopener">\n  <img src="' + b.svg + '" alt="Verified Gun Rack Deals dealer" width="' + b.width + '" height="' + b.height + '">\n</a>';
		} else if (currentTab === 'md') {
			out = '[![Verified Gun Rack Deals dealer](' + b.svg + ')](' + PROFILE + ')';
		} else {
			out = b.svg;
		}
		ta.value = out;
	}

	function copyCode() {
		var ta = document.getElementById('gd_badge_code');
		var btn = document.getElementById('gd_badge_copy_btn');
		if (!ta) return;
		ta.select();
		try {
			document.execCommand('copy');
			if (btn) {
				var orig = btn.textContent;
				btn.textContent = 'Copied!';
				btn.style.background = '#10b981';
				setTimeout(function(){ btn.textContent = orig; btn.style.background = '#1e40af'; }, 1400);
			}
		} catch (e) {}
	}

	var sel = document.getElementById('gd_badge_picker');
	if (sel) sel.addEventListener('change', refresh);

	wrap.querySelectorAll('.gdBadgeTab').forEach(function(btn){
		btn.addEventListener('click', function(){ switchTab(btn.getAttribute('data-tab')); });
	});

	var copyBtn = document.getElementById('gd_badge_copy_btn');
	if (copyBtn) copyBtn.addEventListener('click', copyCode);

	refresh();
})();
</script>
{{endif}}

HTML;

$body = $body . "\n" . $badgeCard;

try
{
	\IPS\Db::i()->update( 'core_theme_templates',
		[
			'template_data'    => '$data',
			'template_content' => $body,
			'template_updated' => time(),
		],
		[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		  'gddealer', 'front', 'dealers', 'overview' ]
	);
}
catch ( \Throwable ) {}
