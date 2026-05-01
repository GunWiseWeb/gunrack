<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.149 PART 1 of 3 - setupWizardStep1 template.
 *
 * Renders the Step 1 form: feed URL/paste mode toggle, format dropdown,
 * auth fields, and submit button. Wrapped by the dealer dashboard shell
 * (sidebar nav remains visible alongside).
 *
 * Template arguments: $wizardData, $values, $errors=[]
 *
 * Per project rule #28: full template body is declared inline as nowdoc.
 */

$step1Tpl = <<<'TEMPLATE_EOT'
<div class="gdSetupWizard">

	<header class="gdSetupWizard__header">
		<div class="gdSetupWizard__heading">
			<h2>Feed Setup Wizard</h2>
			<p>Walk through 5 steps to connect your product feed. We'll auto-suggest field mappings and validate a sample so you know it'll import cleanly before you go live.</p>
		</div>
	</header>

	<nav class="gdSetupWizard__progress" aria-label="Wizard progress">
		<ol class="gdSetupWizard__steps">
			{{foreach $wizardData['steps'] as $s}}
				{{if $s['num'] < $wizardData['currentStep']}}
					{{$cls = 'is-done';}}
				{{elseif $s['num'] === $wizardData['currentStep']}}
					{{$cls = 'is-current';}}
				{{else}}
					{{$cls = 'is-upcoming';}}
				{{endif}}
				<li class="gdSetupWizard__step {$cls}">
					<span class="gdSetupWizard__stepNum">{$s['num']}</span>
					<span class="gdSetupWizard__stepLabel">{$s['label']}</span>
					<span class="gdSetupWizard__stepDesc">{$s['desc']}</span>
				</li>
			{{endforeach}}
		</ol>
	</nav>

	{{if $wizardData['savedFlash']}}
	<div class="gdSetupWizard__flash gdSetupWizard__flash--success">
		<strong>Step 1 saved!</strong> Your feed input has been recorded. The wizard's later steps (test, mapping, validation, preview) ship in upcoming releases. For now, you can re-edit step 1 anytime.
	</div>
	{{endif}}

	{{if count($errors) > 0}}
	<div class="gdSetupWizard__flash gdSetupWizard__flash--error">
		<strong>Please fix the following before continuing:</strong>
		<ul>
		{{foreach $errors as $err}}
			<li>{$err}</li>
		{{endforeach}}
		</ul>
	</div>
	{{endif}}

	<form method="post" action="{url='app=gddealer&module=dealers&controller=setupwizard&do=saveStep1' base="front" seoTemplate="dealers_setup_wizard"}" class="gdSetupWizard__form" data-step="1">
		<input type="hidden" name="csrfKey" value="{expression="\IPS\Session::i()->csrfKey"}">

		<section class="gdSetupWizard__card">
			<h3>Step 1 of {$wizardData['totalSteps']}: Feed Input</h3>
			<p>Choose how you'd like to provide your product feed. If you have a public URL where your feed lives, use Fetch Mode. If you're still setting things up and want to test with a local sample, use Paste Mode.</p>

			<div class="gdSetupWizard__modeToggle" role="radiogroup" aria-label="Input mode">
				{{$urlChecked = ( $values['mode'] ?? 'url' ) === 'url' ? 'checked' : '';}}
				{{$pasteChecked = ( $values['mode'] ?? '' ) === 'paste' ? 'checked' : '';}}
				<label class="gdSetupWizard__modeOption">
					<input type="radio" name="mode" value="url" {$urlChecked} data-toggle="urlMode">
					<span class="gdSetupWizard__modeLabel">Fetch from URL</span>
					<span class="gdSetupWizard__modeHint">We pull the feed from a public HTTPS URL on a schedule. Best for production.</span>
				</label>
				<label class="gdSetupWizard__modeOption">
					<input type="radio" name="mode" value="paste" {$pasteChecked} data-toggle="pasteMode">
					<span class="gdSetupWizard__modeLabel">Paste feed body</span>
					<span class="gdSetupWizard__modeHint">Paste a sample directly. Use this for testing if your feed isn't deployed yet.</span>
				</label>
			</div>
		</section>

		<section class="gdSetupWizard__card gdSetupWizard__urlMode" data-mode-pane="url">
			<h4>Feed URL</h4>
			<label class="gdSetupWizard__field">
				<span class="gdSetupWizard__fieldLabel">URL <span class="gdSetupWizard__req">*</span></span>
				{{$fu = htmlspecialchars( $values['feed_url'] ?? '', ENT_QUOTES, 'UTF-8' );}}
				<input type="url" name="feed_url" value="{$fu}" placeholder="https://your-store.com/gunrack-feed.xml" class="gdSetupWizard__input">
				<span class="gdSetupWizard__fieldHelp">Must start with https:// (or http:// for testing). The feed should be publicly reachable - we'll fetch it on a schedule based on your subscription tier.</span>
			</label>

			<h4>Authentication</h4>
			<label class="gdSetupWizard__field">
				<span class="gdSetupWizard__fieldLabel">Auth Type</span>
				{{$at = $values['auth_type'] ?? 'none';}}
				<select name="auth_type" class="gdSetupWizard__select">
					<option value="none" {{if $at === 'none'}}selected{{endif}}>None (public URL)</option>
					<option value="basic" {{if $at === 'basic'}}selected{{endif}}>Basic Authentication</option>
					<option value="api_key" {{if $at === 'api_key'}}selected{{endif}}>API Key Header</option>
				</select>
			</label>

			<label class="gdSetupWizard__field">
				<span class="gdSetupWizard__fieldLabel">Credentials (JSON)</span>
				{{$ac = htmlspecialchars( $values['auth_credentials'] ?? '', ENT_QUOTES, 'UTF-8' );}}
				<input type="text" name="auth_credentials" value="{$ac}" placeholder='{"username":"user","password":"pass"} or {"api_key":"your-key"}' class="gdSetupWizard__input gdSetupWizard__input--mono">
				<span class="gdSetupWizard__fieldHelp">Leave blank if Auth Type is None. For Basic Auth: <code>{"username":"user","password":"pass"}</code>. For API Key: <code>{"api_key":"your-key"}</code>.</span>
			</label>
		</section>

		<section class="gdSetupWizard__card gdSetupWizard__pasteMode" data-mode-pane="paste" style="display:none">
			<h4>Paste Feed Body</h4>
			<label class="gdSetupWizard__field">
				<span class="gdSetupWizard__fieldLabel">Feed body <span class="gdSetupWizard__req">*</span></span>
				{{$pb = htmlspecialchars( $values['paste_body'] ?? '', ENT_QUOTES, 'UTF-8' );}}
				<textarea name="paste_body" rows="14" class="gdSetupWizard__textarea gdSetupWizard__textarea--mono" placeholder="Paste your XML, JSON, or CSV feed body here...">{$pb}</textarea>
				<span class="gdSetupWizard__fieldHelp">Paste up to 10 MB. We'll parse this and discover your fields in the next step.</span>
			</label>
		</section>

		<section class="gdSetupWizard__card">
			<h4>Feed Format</h4>
			<label class="gdSetupWizard__field">
				<span class="gdSetupWizard__fieldLabel">Format <span class="gdSetupWizard__req">*</span></span>
				{{$ff = $values['feed_format'] ?? 'xml';}}
				<select name="feed_format" class="gdSetupWizard__select">
					<option value="xml" {{if $ff === 'xml'}}selected{{endif}}>XML</option>
					<option value="json" {{if $ff === 'json'}}selected{{endif}}>JSON</option>
					<option value="csv" {{if $ff === 'csv'}}selected{{endif}}>CSV</option>
				</select>
				<span class="gdSetupWizard__fieldHelp">Pick whichever format your existing feed uses. We support all three equally - see the <a href="/dealers/feed-schema" target="_blank" rel="noopener">schema documentation</a> for details.</span>
			</label>
		</section>

		<div class="gdSetupWizard__actions">
			<a href="{url='app=gddealer&module=dealers&controller=dashboard&do=overview' seoTemplate="dealer_dashboard"}" class="gdSetupWizard__btn gdSetupWizard__btn--ghost">Cancel</a>
			<button type="submit" class="gdSetupWizard__btn gdSetupWizard__btn--primary">Save Step 1</button>
		</div>
	</form>

</div>

<style>
.gdSetupWizard {
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
	color: #0f172a;
	max-width: 1100px;
	margin: 0 auto;
	padding: 24px 16px 80px;
}
.gdSetupWizard *, .gdSetupWizard *::before, .gdSetupWizard *::after { box-sizing: border-box; }

.gdSetupWizard__header { margin-bottom: 24px; }
.gdSetupWizard__header h2 { margin: 0 0 8px; font-size: 1.65em; font-weight: 700; color: #0f172a; }
.gdSetupWizard__header p { margin: 0; color: #64748b; max-width: 700px; }

.gdSetupWizard__progress {
	background: #f8fafc;
	border: 1px solid #e2e8f0;
	border-radius: 12px;
	padding: 16px;
	margin-bottom: 24px;
}
.gdSetupWizard__steps {
	display: grid;
	grid-template-columns: repeat(5, 1fr);
	gap: 12px;
	margin: 0;
	padding: 0;
	list-style: none;
}
.gdSetupWizard__step {
	display: flex;
	flex-direction: column;
	gap: 4px;
	padding: 10px 12px;
	border-radius: 8px;
	background: #fff;
	border: 1px solid #e2e8f0;
	position: relative;
}
.gdSetupWizard__step.is-current {
	background: #eff6ff;
	border-color: #2563eb;
	box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
.gdSetupWizard__step.is-done {
	background: #f0fdf4;
	border-color: #86efac;
}
.gdSetupWizard__step.is-upcoming {
	opacity: 0.65;
}
.gdSetupWizard__stepNum {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 22px;
	height: 22px;
	border-radius: 50%;
	background: #cbd5e1;
	color: #fff;
	font-size: 11px;
	font-weight: 700;
}
.gdSetupWizard__step.is-current .gdSetupWizard__stepNum { background: #2563eb; }
.gdSetupWizard__step.is-done .gdSetupWizard__stepNum { background: #16a34a; }
.gdSetupWizard__stepLabel { font-size: 13px; font-weight: 600; color: #0f172a; }
.gdSetupWizard__stepDesc { font-size: 11.5px; color: #64748b; }

.gdSetupWizard__flash {
	border-radius: 8px;
	padding: 14px 16px;
	margin-bottom: 16px;
	font-size: 14px;
}
.gdSetupWizard__flash strong { display: block; margin-bottom: 4px; }
.gdSetupWizard__flash--success {
	background: #f0fdf4;
	border: 1px solid #86efac;
	color: #14532d;
}
.gdSetupWizard__flash--error {
	background: #fef2f2;
	border: 1px solid #fca5a5;
	color: #7f1d1d;
}
.gdSetupWizard__flash--error ul { margin: 4px 0 0; padding-left: 20px; }
.gdSetupWizard__flash--error li { margin: 2px 0; }

.gdSetupWizard__form { display: flex; flex-direction: column; gap: 16px; }

.gdSetupWizard__card {
	background: #fff;
	border: 1px solid #e2e8f0;
	border-radius: 12px;
	padding: 20px;
}
.gdSetupWizard__card h3 {
	margin: 0 0 8px;
	font-size: 1.1em;
	font-weight: 700;
	color: #0f172a;
}
.gdSetupWizard__card h4 {
	margin: 0 0 8px;
	font-size: 1em;
	font-weight: 600;
	color: #0f172a;
}
.gdSetupWizard__card p { margin: 0 0 12px; color: #475569; font-size: 14px; }
.gdSetupWizard__card p:last-child { margin-bottom: 0; }

.gdSetupWizard__modeToggle {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 12px;
	margin-top: 12px;
}
.gdSetupWizard__modeOption {
	display: flex;
	flex-direction: column;
	gap: 4px;
	padding: 14px 16px;
	border: 2px solid #e2e8f0;
	border-radius: 10px;
	cursor: pointer;
	transition: all 0.15s;
}
.gdSetupWizard__modeOption:has(input:checked) {
	border-color: #2563eb;
	background: #eff6ff;
}
.gdSetupWizard__modeOption input { margin-right: 8px; }
.gdSetupWizard__modeLabel {
	display: inline-block;
	font-weight: 600;
	font-size: 14px;
	color: #0f172a;
}
.gdSetupWizard__modeHint { font-size: 12.5px; color: #64748b; line-height: 1.45; }

.gdSetupWizard__field {
	display: flex;
	flex-direction: column;
	gap: 6px;
	margin-bottom: 14px;
}
.gdSetupWizard__field:last-child { margin-bottom: 0; }
.gdSetupWizard__fieldLabel {
	font-size: 13px;
	font-weight: 600;
	color: #0f172a;
}
.gdSetupWizard__fieldHelp {
	font-size: 12.5px;
	color: #64748b;
	line-height: 1.45;
}
.gdSetupWizard__fieldHelp code {
	background: #f1f5f9;
	padding: 1px 6px;
	border-radius: 3px;
	font-family: 'JetBrains Mono', ui-monospace, monospace;
	font-size: 11.5px;
}
.gdSetupWizard__req { color: #dc2626; }

.gdSetupWizard__input,
.gdSetupWizard__select,
.gdSetupWizard__textarea {
	width: 100%;
	padding: 9px 12px;
	border: 1px solid #cbd5e1;
	border-radius: 6px;
	font-family: inherit;
	font-size: 14px;
	color: #0f172a;
	background: #fff;
}
.gdSetupWizard__input:focus,
.gdSetupWizard__select:focus,
.gdSetupWizard__textarea:focus {
	outline: none;
	border-color: #2563eb;
	box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
.gdSetupWizard__input--mono,
.gdSetupWizard__textarea--mono {
	font-family: 'JetBrains Mono', ui-monospace, monospace;
	font-size: 12.5px;
}
.gdSetupWizard__textarea { resize: vertical; min-height: 240px; line-height: 1.5; }

.gdSetupWizard__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	padding-top: 8px;
}
.gdSetupWizard__btn {
	padding: 10px 18px;
	border-radius: 8px;
	font-weight: 600;
	font-size: 14px;
	border: 1px solid transparent;
	text-decoration: none;
	cursor: pointer;
	transition: all 0.15s;
}
.gdSetupWizard__btn--primary {
	background: #2563eb;
	color: #fff;
	border-color: #2563eb;
}
.gdSetupWizard__btn--primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
.gdSetupWizard__btn--ghost {
	background: #fff;
	color: #475569;
	border-color: #cbd5e1;
}
.gdSetupWizard__btn--ghost:hover { background: #f8fafc; color: #0f172a; }

@media (max-width: 720px) {
	.gdSetupWizard { padding: 16px 12px 60px; }
	.gdSetupWizard__steps { grid-template-columns: 1fr 1fr; }
	.gdSetupWizard__modeToggle { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
	.gdSetupWizard__steps { grid-template-columns: 1fr; }
}
</style>

<script>
(function(){
	function refreshModePanes() {
		var modeRadios = document.querySelectorAll('input[name="mode"]');
		var current = 'url';
		modeRadios.forEach(function(r){ if (r.checked) current = r.value; });
		document.querySelectorAll('[data-mode-pane]').forEach(function(p){
			p.style.display = p.getAttribute('data-mode-pane') === current ? '' : 'none';
		});
	}
	document.querySelectorAll('input[name="mode"]').forEach(function(r){
		r.addEventListener('change', refreshModePanes);
	});
	refreshModePanes();
})();
</script>
TEMPLATE_EOT;
