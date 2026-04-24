/**
 * GD Dealer Manager — Verified Dealer Badge Picker
 *
 * Hooks up the badge dropdown, live preview, tab switching, and copy-to-clipboard
 * for the "Get your verified-dealer badge" card on the dashboard Overview tab.
 *
 * Reads JSON from #gdBadgePicker[data-badges] which the controller pre-encodes
 * with hex-escaped quotes (JSON_HEX_QUOT etc.) so it's safe in any HTML context.
 *
 * Loaded inline by DealerShellTrait::output() — does NOT go through the IPS
 * template parser (which strips script blocks unreliably).
 */
(function() {
	'use strict';

	function init() {
		var wrap = document.getElementById('gdBadgePicker');
		if (!wrap) return;

		var BADGES = {};
		try {
			BADGES = JSON.parse(wrap.getAttribute('data-badges') || '{}');
		} catch (e) {
			console.error('[gddealer] Badge data parse failed:', e);
			return;
		}

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
			wrap.querySelectorAll('.gdBadgeTab').forEach(function(btn) {
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

		wrap.querySelectorAll('.gdBadgeTab').forEach(function(btn) {
			btn.addEventListener('click', function() {
				switchTab(btn.getAttribute('data-tab'));
			});
		});

		var copyBtn = document.getElementById('gd_badge_copy_btn');
		if (copyBtn) copyBtn.addEventListener('click', copyCode);

		refresh();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
