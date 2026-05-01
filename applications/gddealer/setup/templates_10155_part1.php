<?php
if ( !defined( '\\IPS\\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.155 PART 1 of 6 - Updated setupWizardStep3 HTML body.
 *
 * Same as v154 except the default-value pane now renders a SELECT
 * (true/false dropdown) when the published default is boolean-like,
 * and a TEXT INPUT otherwise. Implemented via the new
 * 'is_boolean_default' flag on each row, set by buildStep3Values.
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
		<strong>Step 3 saved!</strong> Your field mapping has been recorded. Continuing to Step 4 (Validate).
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
		<p>Match each of our canonical fields to one of your discovered feed fields, OR accept a sensible default value when your feed doesn't include the field. We've auto-suggested mappings where the field names look familiar.</p>

		<div class="gdSetupWizard__step3Stats">
			<div class="gdSetupWizard__stat gdSetupWizard__stat--{{if $values['required_mapped'] === $values['required_total']}}good{{else}}warn{{endif}}">
				<span class="gdSetupWizard__statNum">{$values['required_mapped']} / {$values['required_total']}</span>
				<span class="gdSetupWizard__statLabel">Required fields satisfied</span>
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
				<strong>Required fields not yet satisfied:</strong>
				<ul>
				{{foreach $values['required_unmapped'] as $rf}}
					<li><code>{$rf['slug']}</code> &mdash; {$rf['label']}</li>
				{{endforeach}}
				</ul>
				<p>You can save and continue, but listings will fail validation until these are mapped to a feed field, or use a default value.</p>
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
							<th class="gdSetupWizard__colSource">Source</th>
							<th class="gdSetupWizard__colValue">Value</th>
							<th class="gdSetupWizard__colSample">Sample</th>
						</tr>
					</thead>
					<tbody>
						{{foreach $group['rows'] as $row}}
							{{$slug = $row['slug'];}}
							{{$reqClass = $row['req'] === 'required' ? ' is-required' : '';}}
							{{$feedChecked = $row['source'] === 'feed' ? 'checked' : '';}}
							{{$defaultChecked = $row['source'] === 'default' ? 'checked' : '';}}
							{{$noneChecked = $row['source'] === 'none' ? 'checked' : '';}}
							<tr class="gdSetupWizard__mapRow{$reqClass}" data-slug="{$slug}">
								<td class="gdSetupWizard__colCanonical">
									<div class="gdSetupWizard__canonName">
										<code>{$row['slug']}</code>
										{{if $row['req'] === 'required'}}<span class="gdSetupWizard__reqBadge gdSetupWizard__reqBadge--required">Required</span>{{endif}}
										{{if $row['req'] === 'conditional'}}<span class="gdSetupWizard__reqBadge gdSetupWizard__reqBadge--conditional">Conditional</span>{{endif}}
										{{if $row['req'] === 'cat_required'}}<span class="gdSetupWizard__reqBadge gdSetupWizard__reqBadge--catreq">Required for category</span>{{endif}}
									</div>
									<div class="gdSetupWizard__canonLabel">{$row['label']}</div>
								</td>
								<td class="gdSetupWizard__colSource">
									<div class="gdSetupWizard__sourceRadios">
										<label class="gdSetupWizard__sourceRadio">
											<input type="radio" name="source[{$slug}]" value="feed" {$feedChecked}>
											<span>Feed field</span>
										</label>
										{{if $row['has_default']}}
											<label class="gdSetupWizard__sourceRadio">
												<input type="radio" name="source[{$slug}]" value="default" {$defaultChecked}>
												<span>Default</span>
											</label>
										{{endif}}
										<label class="gdSetupWizard__sourceRadio">
											<input type="radio" name="source[{$slug}]" value="none" {$noneChecked}>
											<span>Not mapped</span>
										</label>
										{{if $row['is_auto_suggested']}}
											<span class="gdSetupWizard__autoTag" title="We suggested this match automatically">auto</span>
										{{endif}}
									</div>
								</td>
								<td class="gdSetupWizard__colValue">
									<div class="gdSetupWizard__valuePane gdSetupWizard__valuePane--feed" data-pane="feed">
										<select name="mapping[{$slug}]" class="gdSetupWizard__select">
											<option value="">&mdash; Pick a field &mdash;</option>
											{{foreach $values['discovered'] as $df}}
												{{$sel = $df === $row['selected_dealer_field'] ? 'selected' : '';}}
												<option value="{$df}" {$sel}>{$df}</option>
											{{endforeach}}
										</select>
									</div>
									{{if $row['has_default']}}
										<div class="gdSetupWizard__valuePane gdSetupWizard__valuePane--default" data-pane="default">
											{{$dv = $row['default_override'] !== '' ? $row['default_override'] : $row['published_default'];}}
											{{if $row['is_boolean_default']}}
												<select name="default[{$slug}]" class="gdSetupWizard__select gdSetupWizard__select--default">
													<option value="true" {{if $dv === 'true'}}selected{{endif}}>true</option>
													<option value="false" {{if $dv === 'false'}}selected{{endif}}>false</option>
												</select>
												<span class="gdSetupWizard__defaultHint">Will be applied to every record. Default: <code>{$row['published_default']}</code></span>
											{{else}}
												<input type="text" name="default[{$slug}]" value="{$dv}" class="gdSetupWizard__input gdSetupWizard__input--default" placeholder="{$row['published_default']}">
												<span class="gdSetupWizard__defaultHint">Will be applied to every record. Default: <code>{$row['published_default']}</code></span>
											{{endif}}
										</div>
									{{endif}}
									<div class="gdSetupWizard__valuePane gdSetupWizard__valuePane--none" data-pane="none">
										<span class="gdSetupWizard__nonePane">&mdash;</span>
									</div>
								</td>
								<td class="gdSetupWizard__colSample">
									{{if $row['source'] === 'feed' && $row['selected_dealer_field'] && isset($values['sample_for'][$row['selected_dealer_field']])}}
										<code class="gdSetupWizard__sample">{$values['sample_for'][$row['selected_dealer_field']]}</code>
									{{elseif $row['source'] === 'default' && $row['has_default']}}
										{{$dv2 = $row['default_override'] !== '' ? $row['default_override'] : $row['published_default'];}}
										<code class="gdSetupWizard__sample gdSetupWizard__sample--default">{$dv2}</code>
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
	function refreshRow(row) {
		var sel = row.querySelector('input[type="radio"]:checked');
		var source = sel ? sel.value : 'none';
		row.querySelectorAll('[data-pane]').forEach(function(p){
			p.style.display = (p.getAttribute('data-pane') === source) ? '' : 'none';
		});
		var sampleCell = row.querySelector('.gdSetupWizard__colSample');
		if (sampleCell) {
			if (source === 'feed') {
				var fieldSel = row.querySelector('select[name^="mapping["]');
				var df = fieldSel ? fieldSel.value : '';
				if (df && window._gdSamples && window._gdSamples[df]) {
					sampleCell.innerHTML = '<code class="gdSetupWizard__sample"></code>';
					sampleCell.firstChild.textContent = window._gdSamples[df];
				} else {
					sampleCell.innerHTML = '<span class="gdSetupWizard__samplePlaceholder">&mdash;</span>';
				}
			} else if (source === 'default') {
				var defInp = row.querySelector('[name^="default["]');
				var dv = defInp ? defInp.value : '';
				if (dv) {
					sampleCell.innerHTML = '<code class="gdSetupWizard__sample gdSetupWizard__sample--default"></code>';
					sampleCell.firstChild.textContent = dv;
				} else {
					sampleCell.innerHTML = '<span class="gdSetupWizard__samplePlaceholder">&mdash;</span>';
				}
			} else {
				sampleCell.innerHTML = '<span class="gdSetupWizard__samplePlaceholder">&mdash;</span>';
			}
		}
	}
	function refreshStats() {
		var rows = document.querySelectorAll('.gdSetupWizard__mapRow');
		var requiredTotal = 0, requiredMapped = 0;
		var usedSet = {};
		rows.forEach(function(row){
			var isReq = row.classList.contains('is-required');
			var sel = row.querySelector('input[type="radio"]:checked');
			var source = sel ? sel.value : 'none';
			var satisfied = false;
			if (source === 'feed') {
				var fieldSel = row.querySelector('select[name^="mapping["]');
				if (fieldSel && fieldSel.value) {
					satisfied = true;
					usedSet[fieldSel.value] = true;
				}
			} else if (source === 'default') {
				satisfied = true;
			}
			if (isReq) {
				requiredTotal++;
				if (satisfied) requiredMapped++;
			}
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
			var total = stats[1].textContent.split('/')[1].trim();
			stats[1].textContent = usedCount + ' / ' + total;
		}
	}
	document.querySelectorAll('.gdSetupWizard__mapRow').forEach(function(row){
		row.querySelectorAll('input[type="radio"], select, input[type="text"]').forEach(function(el){
			el.addEventListener('change', function(){ refreshRow(row); refreshStats(); });
			if (el.tagName === 'INPUT' && el.type === 'text') {
				el.addEventListener('input', function(){ refreshRow(row); });
			}
		});
		refreshRow(row);
	});
	refreshStats();
})();
</script>
TEMPLATE_EOT;
