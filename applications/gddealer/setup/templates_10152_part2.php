<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.152 PART 2 of 3 - HTML body for setupWizardStep2.
 *
 * Initializes $step2Tpl with the HTML portion. Same as v151 hotfix
 * EXCEPT all {url=...} directives are replaced with $values['urls']['key']
 * string interpolation. Part 3 appends the CSS and writes to DB.
 */

$step2Tpl = <<<'TEMPLATE_EOT'
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
		<strong>Step 2 saved!</strong> Your feed parses correctly. Steps 3-5 (field mapping, validation, preview) ship in upcoming releases.
	</div>
	{{endif}}

	<section class="gdSetupWizard__card">
		<h3>Step 2 of {$wizardData['totalSteps']}: Test &amp; Parse</h3>
		<p>We attempted to fetch and parse your feed. Below are the fetch metadata, parse results, and discovered field names. If anything looks wrong, head back to step 1 and update your settings, then return here and click Re-fetch.</p>
		<div class="gdSetupWizard__step2Meta">
			<span class="gdSetupWizard__pill"><strong>Mode:</strong> {{if $values['mode'] === 'paste'}}Paste{{else}}URL{{endif}}</span>
			{{if $values['mode'] === 'url' && $values['feed_url']}}
				<span class="gdSetupWizard__pill gdSetupWizard__pill--mono">{$values['feed_url']}</span>
			{{endif}}
			<span class="gdSetupWizard__pill"><strong>Format:</strong> {$values['feed_format_label']}</span>
		</div>
	</section>

	{{if $values['fetch'] === null}}
		<section class="gdSetupWizard__card gdSetupWizard__card--warning">
			<p>No fetch results yet. <a href="{$values['urls']['step2_refetch']}">Click here to fetch your feed now.</a></p>
		</section>
	{{else}}

		{{$f = $values['fetch'];}}

		{{if $f['ok'] && !$values['parse_error']}}
			<section class="gdSetupWizard__card gdSetupWizard__card--success">
				<div class="gdSetupWizard__resultRow">
					<span class="gdSetupWizard__resultIcon">&#10003;</span>
					<div>
						<h4>Fetch &amp; parse successful</h4>
						<p>Your feed responded successfully and parsed cleanly. Field discovery picked up <strong>{$values['field_count']}</strong> unique fields across <strong>{$values['sample_count']}</strong> sample records.</p>
					</div>
				</div>
			</section>
		{{elseif !$f['ok']}}
			<section class="gdSetupWizard__card gdSetupWizard__card--error">
				<div class="gdSetupWizard__resultRow">
					<span class="gdSetupWizard__resultIcon">&#10007;</span>
					<div>
						<h4>Fetch failed</h4>
						<p>{{if $f['error']}}{$f['error']}{{else}}Could not fetch your feed.{{endif}}</p>
						<p class="gdSetupWizard__resultHint">Common fixes: check the URL is publicly reachable, verify auth credentials, and confirm your server isn't returning a 4xx/5xx error. Then click Re-fetch.</p>
					</div>
				</div>
			</section>
		{{elseif $values['parse_error']}}
			<section class="gdSetupWizard__card gdSetupWizard__card--error">
				<div class="gdSetupWizard__resultRow">
					<span class="gdSetupWizard__resultIcon">&#10007;</span>
					<div>
						<h4>Fetch worked, but parsing failed</h4>
						<p><strong>{$values['feed_format_label']} parser error:</strong> {$values['parse_error']}</p>
						<p class="gdSetupWizard__resultHint">Either your feed format is wrong (verify XML/JSON/CSV in step 1 matches your actual feed) or your feed body has a syntax error. Use the body preview below to inspect what we received.</p>
					</div>
				</div>
			</section>
		{{endif}}

		<section class="gdSetupWizard__card">
			<h4>Fetch metadata</h4>
			<table class="gdSetupWizard__metaTable">
				<tr><th>HTTP status</th><td>{{if $f['http_status']}}<code>{$f['http_status']}</code>{{else}}<em>n/a (paste mode)</em>{{endif}}</td></tr>
				<tr><th>Content-Type</th><td>{{if $f['content_type']}}<code>{$f['content_type']}</code>{{else}}<em>(empty)</em>{{endif}}</td></tr>
				<tr><th>Body size</th><td><code>{$values['body_bytes_fmt']}</code> bytes{{if $f['truncated']}} <span class="gdSetupWizard__warnInline">(truncated at 10 MB)</span>{{endif}}</td></tr>
				<tr><th>Fetch time</th><td>{{if $f['duration_ms']}}<code>{$f['duration_ms']}</code> ms{{else}}<em>n/a</em>{{endif}}</td></tr>
			</table>

			<h4>Body preview (first 800 chars)</h4>
			<pre class="gdSetupWizard__preview">{$f['preview']}</pre>
		</section>

		{{if $values['fields']}}
			<section class="gdSetupWizard__card">
				<h4>Discovered fields</h4>
				<p>Below are the unique field names found in your feed, sorted by how often they appear. In step 3 you'll map each of these to one of our canonical fields.</p>
				<table class="gdSetupWizard__fieldsTable">
					<thead>
						<tr><th>Field name</th><th>Records with value</th><th>Sample value</th></tr>
					</thead>
					<tbody>
						{{foreach $values['fields'] as $field}}
							<tr>
								<td><code>{$field['field']}</code></td>
								<td><span class="gdSetupWizard__count">{$field['count']}</span></td>
								<td>{{if $field['sample']}}<code class="gdSetupWizard__sample">{$field['sample']}</code>{{else}}<em>(no value in first {$field['count']} records)</em>{{endif}}</td>
							</tr>
						{{endforeach}}
					</tbody>
				</table>
			</section>
		{{endif}}
	{{endif}}

	<form method="post" action="{$values['urls']['save_step2']}" class="gdSetupWizard__form" data-step="2">
		<input type="hidden" name="csrfKey" value="{$values['csrfKey']}">

		<div class="gdSetupWizard__actions">
			<a href="{$values['urls']['step1']}" class="gdSetupWizard__btn gdSetupWizard__btn--ghost">&larr; Back to Step 1</a>
			<a href="{$values['urls']['step2_refetch']}" class="gdSetupWizard__btn gdSetupWizard__btn--ghost">&#8635; Re-fetch</a>
			{{if $values['fetch'] && $values['fetch']['ok'] && !$values['parse_error']}}
				<button type="submit" class="gdSetupWizard__btn gdSetupWizard__btn--primary">Continue to Step 3 &rarr;</button>
			{{else}}
				<button type="submit" class="gdSetupWizard__btn gdSetupWizard__btn--disabled" disabled title="Resolve the fetch or parse error before continuing">Continue to Step 3 &rarr;</button>
			{{endif}}
		</div>
	</form>

</div>

TEMPLATE_EOT;
