<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.153 PART 1 of 3 - HTML body of setupWizardStep3.
 *
 * Field Mapping with Auto-Suggest. Inverse-view UI (one row per
 * canonical field). Required-fields summary at top. Save button is
 * always enabled - dealer can proceed even with unmapped requireds
 * (the validator in step 4 will surface those).
 *
 * Initializes $step3Tpl. Part 2 appends CSS, part 3 writes to DB.
 */

$step3Tpl = <<<'TEMPLATE_EOT'
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
		<strong>Step 3 saved!</strong> Your field mapping has been recorded. Steps 4-5 (validate &amp; preview) ship in upcoming releases. You can revisit and edit your mapping anytime.
	</div>
	{{endif}}

	{{if count($values['errors']) > 0}}
	<div class="gdSetupWizard__flash gdSetupWizard__flash--error">
		<strong>Please fix the following before continuing:</strong>
		<ul>
		{{foreach $values['errors'] as $err}}
			<li>{$err}</li>
		{{endforeach}}
		</ul>
	</div>
	{{endif}}

	<section class="gdSetupWizard__card">
		<h3>Step 3 of {$wizardData['totalSteps']}: Field Mapping</h3>
		<p>Match each of our canonical fields to one of your discovered feed fields. We've auto-suggested mappings where the field names look familiar - review them and adjust as needed. You don't have to map everything; only Core Required fields must be mapped before your feed will import cleanly.</p>

		<div class="gdSetupWizard__step3Stats">
			<div class="gdSetupWizard__stat gdSetupWizard__stat--{{if $values['required_mapped'] === $values['required_total']}}good{{else}}warn{{endif}}">
				<span class="gdSetupWizard__statNum">{$values['required_mapped']} / {$values['required_total']}</span>
				<span class="gdSetupWizard__statLabel">Required fields mapped</span>
			</div>
			<div class="gdSetupWizard__stat">
				<span class="gdSetupWizard__statNum">{$values['used_dealer_count']} / {$values['discovered_count']}</span>
				<span class="gdSetupWizard__statLabel">Of your fields used</span>
			</div>
			<div class="gdSetupWizard__stat">
				<span class="gdSetupWizard__statNum">{$values['auto_count']}</span>
				<span class="gdSetupWizard__statLabel">Auto-suggested</span>
			</div>
		</div>

		{{if count($values['required_unmapped']) > 0}}
			<div class="gdSetupWizard__warning">
				<strong>Required fields not yet mapped:</strong>
				<ul>
				{{foreach $values['required_unmapped'] as $rf}}
					<li><code>{$rf['slug']}</code> &mdash; {$rf['label']}</li>
				{{endforeach}}
				</ul>
				<p>You can save and continue, but listings will fail validation until these are mapped or your feed adds them.</p>
			</div>
		{{endif}}
	</section>

	<form method="post" action="{$values['urls']['save_step3']}" class="gdSetupWizard__form" data-step="3">
		<input type="hidden" name="csrfKey" value="{$values['csrfKey']}">

		{{foreach $values['grouped'] as $group}}
			<section class="gdSetupWizard__card gdSetupWizard__group" data-group="{$group['key']}">
				<h4>{$group['label']}</h4>
				<table class="gdSetupWizard__mapTable">
					<thead>
						<tr>
							<th class="gdSetupWizard__colCanonical">Our field</th>
							<th class="gdSetupWizard__colDealer">Your field</th>
							<th class="gdSetupWizard__colSample">Sample</th>
						</tr>
					</thead>
					<tbody>
						{{foreach $group['fields'] as $field}}
							{{$slug = $field['slug'];}}
							{{$selected = $values['current_mapping'][$slug] ?? '';}}
							{{$isAuto = isset($values['auto_suggested'][$slug]);}}
							{{$reqClass = $field['req'] === 'required' ? ' is-required' : '';}}
							<tr class="gdSetupWizard__mapRow{$reqClass}">
								<td class="gdSetupWizard__colCanonical">
									<div class="gdSetupWizard__canonName">
										<code>{$field['slug']}</code>
										{{if $field['req'] === 'required'}}<span class="gdSetupWizard__reqBadge gdSetupWizard__reqBadge--required">Required</span>{{endif}}
										{{if $field['req'] === 'conditional'}}<span class="gdSetupWizard__reqBadge gdSetupWizard__reqBadge--conditional">Conditional</span>{{endif}}
										{{if $field['req'] === 'cat_required'}}<span class="gdSetupWizard__reqBadge gdSetupWizard__reqBadge--catreq">Required for category</span>{{endif}}
									</div>
									<div class="gdSetupWizard__canonLabel">{$field['label']}</div>
								</td>
								<td class="gdSetupWizard__colDealer">
									<select name="mapping[{$slug}]" class="gdSetupWizard__select" data-canonical="{$slug}">
										<option value="">&mdash; Not mapped &mdash;</option>
										{{foreach $values['discovered'] as $df}}
											{{$sel = $df === $selected ? 'selected' : '';}}
											<option value="{$df}" {$sel}>{$df}</option>
										{{endforeach}}
									</select>
									{{if $isAuto}}
										<span class="gdSetupWizard__autoTag" title="We suggested this match automatically">auto</span>
									{{endif}}
								</td>
								<td class="gdSetupWizard__colSample">
									{{if $selected && isset($values['sample_for'][$selected])}}
										<code class="gdSetupWizard__sample">{$values['sample_for'][$selected]}</code>
									{{else}}
										<span class="gdSetupWizard__samplePlaceholder">&mdash;</span>
									{{endif}}
								</td>
							</tr>
						{{endforeach}}
					</tbody>
				</table>
			</section>
		{{endforeach}}

		<div class="gdSetupWizard__actions">
			<a href="{$values['urls']['step2']}" class="gdSetupWizard__btn gdSetupWizard__btn--ghost">&larr; Back to Step 2</a>
			<a href="{$values['urls']['reset_step3']}" class="gdSetupWizard__btn gdSetupWizard__btn--ghost" title="Discard your edits and re-run auto-suggest">Reset to Auto-Suggest</a>
			<button type="submit" class="gdSetupWizard__btn gdSetupWizard__btn--primary">Save Mapping &amp; Continue &rarr;</button>
		</div>
	</form>

</div>

<script>
window._gdSamples = {$values['sample_for_json']};
(function(){
	function refreshSamples() {
		var selects = document.querySelectorAll('select[name^="mapping["]');
		selects.forEach(function(sel){
			var row = sel.closest('tr');
			if (!row) return;
			var sampleCell = row.querySelector('.gdSetupWizard__colSample');
			if (!sampleCell) return;
			var dealerField = sel.value;
			if (!dealerField) {
				sampleCell.innerHTML = '<span class="gdSetupWizard__samplePlaceholder">&mdash;</span>';
				return;
			}
			var sampleData = window._gdSamples || {};
			var s = sampleData[dealerField];
			if (s) {
				var code = document.createElement('code');
				code.className = 'gdSetupWizard__sample';
				code.textContent = s;
				sampleCell.innerHTML = '';
				sampleCell.appendChild(code);
			} else {
				sampleCell.innerHTML = '<span class="gdSetupWizard__samplePlaceholder">&mdash;</span>';
			}
		});
	}
	function refreshStats() {
		var selects = document.querySelectorAll('select[name^="mapping["]');
		var requiredTotal = 0, requiredMapped = 0;
		var usedSet = {};
		selects.forEach(function(sel){
			var row = sel.closest('tr');
			if (row && row.classList.contains('is-required')) {
				requiredTotal++;
				if (sel.value) requiredMapped++;
			}
			if (sel.value) usedSet[sel.value] = true;
		});
		var stats = document.querySelectorAll('.gdSetupWizard__statNum');
		if (stats[0]) stats[0].textContent = requiredMapped + ' / ' + requiredTotal;
		var statContainer = stats[0] ? stats[0].closest('.gdSetupWizard__stat') : null;
		if (statContainer) {
			statContainer.classList.remove('gdSetupWizard__stat--good', 'gdSetupWizard__stat--warn');
			statContainer.classList.add(requiredMapped === requiredTotal ? 'gdSetupWizard__stat--good' : 'gdSetupWizard__stat--warn');
		}
		if (stats[1]) {
			var usedCount = Object.keys(usedSet).length;
			var totalDiscovered = stats[1].textContent.split('/')[1].trim();
			stats[1].textContent = usedCount + ' / ' + totalDiscovered;
		}
	}
	document.querySelectorAll('select[name^="mapping["]').forEach(function(sel){
		sel.addEventListener('change', function(){ refreshSamples(); refreshStats(); });
	});
})();
</script>
TEMPLATE_EOT;
