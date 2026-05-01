<?php
if ( !defined( '\\IPS\\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.156 PART 1 of 2 - Updated setupWizardStep4 HTML body.
 *
 * Fixes the EX0 from v155: removes {{if expr > 0}} blocks from
 * inside HTML class attributes. The IPS template parser misreads
 * the > as end-of-tag. Now uses pre-computed class strings
 * passed in $values: 'error_class', 'warn_class', 'continue_ready'.
 *
 * The good-class is fixed (always 'gdSetupWizard__stat--good').
 * The error/warn classes flip between 'bad' and 'neutral' based
 * on counts, computed in the controller.
 */

$step4Tpl = <<<'TEMPLATE_EOT'
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
		<strong>Step 4 saved!</strong> Your validation results have been recorded. Continuing to Step 5 (Preview &amp; Save) - shipping in next release.
	</div>
	{{endif}}

	<section class="gdSetupWizard__card">
		<h3>Step 4 of {$wizardData['totalSteps']}: Validate Sample</h3>
		<p>We applied your field mapping (including any defaults) to the sample records from step 2 and ran them through our validator. Below are the results - records with errors won't import. Records with only warnings will import but with caveats.</p>
	</section>

	{{if $values['report'] === null}}
		<section class="gdSetupWizard__card gdSetupWizard__card--warning">
			<p>No validation results yet. <a href="{$values['urls']['step4_revalidate']}">Click here to run validation now.</a></p>
		</section>
	{{else}}

		{{$summary = $values['report']['summary'];}}

		<section class="gdSetupWizard__card">
			<div class="gdSetupWizard__step4Stats">
				<div class="gdSetupWizard__stat gdSetupWizard__stat--good">
					<span class="gdSetupWizard__statNum">{$summary['valid_records']}</span>
					<span class="gdSetupWizard__statLabel">Valid records</span>
				</div>
				<div class="gdSetupWizard__stat {$values['error_class']}">
					<span class="gdSetupWizard__statNum">{$summary['error_records']}</span>
					<span class="gdSetupWizard__statLabel">With errors</span>
				</div>
				<div class="gdSetupWizard__stat {$values['warn_class']}">
					<span class="gdSetupWizard__statNum">{$summary['warning_records']}</span>
					<span class="gdSetupWizard__statLabel">With warnings</span>
				</div>
				<div class="gdSetupWizard__stat">
					<span class="gdSetupWizard__statNum">{$summary['total_records']}</span>
					<span class="gdSetupWizard__statLabel">Total sampled</span>
				</div>
			</div>
		</section>

		<section class="gdSetupWizard__card">
			<h4>Per-record results</h4>
			<table class="gdSetupWizard__resultsTable">
				<thead>
					<tr>
						<th class="gdSetupWizard__colNum">#</th>
						<th class="gdSetupWizard__colStatus">Status</th>
						<th class="gdSetupWizard__colUpc">UPC</th>
						<th class="gdSetupWizard__colDetails">Details</th>
					</tr>
				</thead>
				<tbody>
					{{foreach $values['rows'] as $row}}
						{{$rowNum = $row['row'];}}
						{{$upc = isset($row['canonical']['upc']) ? $row['canonical']['upc'] : '(no upc)';}}
						{{if $row['has_errors']}}
							{{$statusCls = 'gdSetupWizard__status--error';}}
							{{$statusLabel = 'Errors';}}
						{{elseif $row['has_warnings']}}
							{{$statusCls = 'gdSetupWizard__status--warn';}}
							{{$statusLabel = 'Warnings';}}
						{{else}}
							{{$statusCls = 'gdSetupWizard__status--ok';}}
							{{$statusLabel = 'Valid';}}
						{{endif}}
						{{$errorCount = count( $row['errors'] );}}
						{{$warningCount = count( $row['warnings'] );}}
						<tr class="gdSetupWizard__resultRow">
							<td class="gdSetupWizard__colNum">{$rowNum}</td>
							<td class="gdSetupWizard__colStatus">
								<span class="gdSetupWizard__statusBadge {$statusCls}">{$statusLabel}</span>
							</td>
							<td class="gdSetupWizard__colUpc"><code>{$upc}</code></td>
							<td class="gdSetupWizard__colDetails">
								{{if $row['has_errors']}}
									<details class="gdSetupWizard__details">
										<summary>{$errorCount} error(s)</summary>
										<ul class="gdSetupWizard__issuesList">
										{{foreach $row['errors'] as $err}}
											<li><code class="gdSetupWizard__issueField">{$err['field']}</code> {$err['message']}</li>
										{{endforeach}}
										</ul>
									</details>
								{{endif}}
								{{if $row['has_warnings']}}
									<details class="gdSetupWizard__details">
										<summary>{$warningCount} warning(s)</summary>
										<ul class="gdSetupWizard__issuesList">
										{{foreach $row['warnings'] as $warn}}
											<li><code class="gdSetupWizard__issueField">{$warn['field']}</code> {$warn['message']}</li>
										{{endforeach}}
										</ul>
									</details>
								{{endif}}
								{{if !$row['has_errors'] && !$row['has_warnings']}}
									<span class="gdSetupWizard__noIssues">All checks passed</span>
								{{endif}}
							</td>
						</tr>
					{{endforeach}}
				</tbody>
			</table>
		</section>

	{{endif}}

	<form method="post" action="{$values['urls']['save_step4']}" class="gdSetupWizard__form" data-step="4">
		<input type="hidden" name="csrfKey" value="{$values['csrfKey']}">

		<div class="gdSetupWizard__actions">
			<a href="{$values['urls']['step3']}" class="gdSetupWizard__btn gdSetupWizard__btn--ghost">&larr; Back to Step 3</a>
			<a href="{$values['urls']['step4_revalidate']}" class="gdSetupWizard__btn gdSetupWizard__btn--ghost">&#8635; Re-validate</a>
			{{if $values['continue_ready']}}
				<button type="submit" class="gdSetupWizard__btn gdSetupWizard__btn--primary">Continue to Step 5 &rarr;</button>
			{{else}}
				<button type="submit" class="gdSetupWizard__btn gdSetupWizard__btn--disabled" disabled title="At least one record must validate cleanly before continuing">Continue to Step 5 &rarr;</button>
			{{endif}}
		</div>
	</form>

</div>
TEMPLATE_EOT;
