<?php
/**
 * @brief       GD Rebates — Install-time template seeder
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Seeds all admin + front templates directly into core_theme_templates
 * (CLAUDE.md Rule #4 — data/theme.xml corrupts nowdoc comments). Every
 * template uses safe IPS template syntax only (CLAUDE.md Rule #12):
 * {$var}, {expression="..."}, {{if}}, {{foreach}}, {lang="..."}. No
 * comments inside templates (Rule #9).
 *
 * Invoked from Application::installOther() after schema + data JSON
 * imports. Safe to re-run — existing rows with matching
 * (template_set_id, template_app, template_location, template_group,
 * template_name) are replaced.
 */

$templates = [];

$templates[] = [
	'location' => 'admin',
	'group'    => 'rebates',
	'name'     => 'dashboard',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_title">
		<h1 class="ipsType_sectionHead">{lang="gdr_dash_title"}</h1>
	</div>
	<div class="ipsBox_body ipsPad">
		<div class="ipsGrid ipsGrid_collapsePhone" style="margin-bottom:16px">
			<div class="ipsGrid_span3">
				<div class="ipsBox ipsPad" style="text-align:center">
					<p style="font-size:2.5em;font-weight:700;margin:0;color:#2d8c2d">{$data['counts']['active']}</p>
					<p style="margin:4px 0 0;color:#666">{lang="gdr_dash_active"}</p>
				</div>
			</div>
			<div class="ipsGrid_span3">
				<div class="ipsBox ipsPad" style="text-align:center">
					<p style="font-size:2.5em;font-weight:700;margin:0;color:#d07e1a">{$data['counts']['pending']}</p>
					<p style="margin:4px 0 0;color:#666">{lang="gdr_dash_pending"}</p>
				</div>
			</div>
			<div class="ipsGrid_span3">
				<div class="ipsBox ipsPad" style="text-align:center">
					<p style="font-size:2.5em;font-weight:700;margin:0;color:#c0392b">{$data['expiring_soon']}</p>
					<p style="margin:4px 0 0;color:#666">{lang="gdr_dash_expiring"}</p>
				</div>
			</div>
			<div class="ipsGrid_span3">
				<div class="ipsBox ipsPad" style="text-align:center">
					<p style="font-size:2.5em;font-weight:700;margin:0;color:#7d3c98">{$data['flagged']}</p>
					<p style="margin:4px 0 0;color:#666">{lang="gdr_dash_flagged"}</p>
				</div>
			</div>
		</div>
		<p><strong>{lang="gdr_dash_total_savings"}:</strong> &#36;{expression="number_format($data['total_savings'], 2)"}</p>
	</div>
</div>
<br>
<div class="ipsBox ipsPull">
	<div class="ipsBox_title">
		<h1 class="ipsType_sectionHead">{lang="gdr_dash_by_type"}</h1>
	</div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['by_type']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_dash_no_rebates"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr><th>{lang="gdr_front_submit_product_type"}</th><th>{lang="gdr_front_hub_active_count"}</th></tr></thead>
				<tbody>
				{{foreach $data['by_type'] as $row}}
					<tr><td>{$row['type']}</td><td>{$row['count']}</td></tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>
<br>
<div class="ipsBox ipsPull">
	<div class="ipsBox_title">
		<h1 class="ipsType_sectionHead">{lang="gdr_dash_top_mfrs"}</h1>
	</div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['top_mfrs']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_dash_no_rebates"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr><th>{lang="gdr_submissions_manufacturer"}</th><th>{lang="gdr_front_hub_active_count"}</th></tr></thead>
				<tbody>
				{{foreach $data['top_mfrs'] as $row}}
					<tr><td>{$row['manufacturer']}</td><td>{$row['count']}</td></tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>
<br>
<div class="ipsBox ipsPull">
	<div class="ipsBox_title">
		<h1 class="ipsType_sectionHead">{lang="gdr_dash_recent_scrapes"}</h1>
	</div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['recent_scrapes']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_dash_no_scrapes"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr>
					<th>{lang="gdr_scrapelog_run_at"}</th>
					<th>{lang="gdr_submissions_manufacturer"}</th>
					<th>{lang="gdr_scrapelog_found"}</th>
					<th>{lang="gdr_scrapelog_created"}</th>
					<th>{lang="gdr_scrapelog_updated"}</th>
					<th>{lang="gdr_scrapelog_failures"}</th>
					<th>{lang="gdr_scrapelog_status"}</th>
				</tr></thead>
				<tbody>
				{{foreach $data['recent_scrapes'] as $r}}
					<tr>
						<td>{$r['run_at']}</td>
						<td>{$r['manufacturer']}</td>
						<td>{$r['rebates_found']}</td>
						<td>{$r['rebates_created']}</td>
						<td>{$r['rebates_updated']}</td>
						<td>{$r['parse_failures']}</td>
						<td>{$r['status']}</td>
					</tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'admin',
	'group'    => 'rebates',
	'name'     => 'submissions',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_title">
		<h1 class="ipsType_sectionHead">{lang="gdr_submissions_title"}</h1>
	</div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['rows']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_submissions_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr>
					<th>{lang="gdr_submissions_manufacturer"}</th>
					<th>{lang="gdr_submissions_title_col"}</th>
					<th>{lang="gdr_submissions_amount"}</th>
					<th>{lang="gdr_submissions_end"}</th>
					<th>{lang="gdr_submissions_submitter"}</th>
					<th>{lang="gdr_submissions_submitted_at"}</th>
					<th>{lang="gdr_submissions_actions"}</th>
				</tr></thead>
				<tbody>
				{{foreach $data['rows'] as $r}}
					<tr>
						<td>{$r['manufacturer']}</td>
						<td>{$r['title']}</td>
						<td>&#36;{expression="number_format((float) ($r['rebate_amount'] ?? 0), 2)"}</td>
						<td>{$r['end_date']}</td>
						<td>{$r['submitted_by']}</td>
						<td>{$r['created_at']}</td>
						<td>
							<a href="{$r['approve_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdr_submissions_approve"}</a>
							<a href="{$r['reject_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_submissions_reject"}</a>
						</td>
					</tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'admin',
	'group'    => 'rebates',
	'name'     => 'targets',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_title" style="display:flex;align-items:center;justify-content:space-between">
		<h1 class="ipsType_sectionHead" style="margin:0">{lang="gdr_targets_title"}</h1>
		<a href="{$data['add_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdr_targets_add"}</a>
	</div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['rows']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_targets_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr>
					<th>{lang="gdr_targets_manufacturer"}</th>
					<th>{lang="gdr_targets_brand"}</th>
					<th>{lang="gdr_targets_url"}</th>
					<th>{lang="gdr_targets_rate"}</th>
					<th>{lang="gdr_targets_known"}</th>
					<th>{lang="gdr_targets_enabled"}</th>
					<th>{lang="gdr_targets_last_run"}</th>
					<th>{lang="gdr_targets_last_status"}</th>
					<th>{lang="gdr_targets_actions"}</th>
				</tr></thead>
				<tbody>
				{{foreach $data['rows'] as $r}}
					<tr>
						<td>{$r['manufacturer']}</td>
						<td>{$r['brand']}</td>
						<td><a href="{$r['scrape_url']}" target="_blank" rel="noopener">{$r['scrape_url']}</a></td>
						<td>{$r['rate_limit_ms']}</td>
						<td>{expression="(int) $r['is_known'] === 1 ? 'Yes' : 'No'"}</td>
						<td>{expression="(int) $r['enabled'] === 1 ? 'Yes' : 'No'"}</td>
						<td>{$r['last_run']}</td>
						<td>{$r['last_status']}</td>
						<td>
							<a href="{$r['edit_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_targets_edit"}</a>
							<a href="{$r['toggle_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_targets_toggle"}</a>
							<a href="{$r['delete_url']}" class="ipsButton ipsButton--negative ipsButton--small" data-confirm>{lang="gdr_targets_delete"}</a>
						</td>
					</tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'admin',
	'group'    => 'rebates',
	'name'     => 'targetForm',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_title"><h1>{{if $data['is_edit']}}{lang="gdr_targets_form_title_edit"}{{else}}{lang="gdr_targets_form_title_add"}{{endif}}</h1></div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['errors']) > 0}}
			<div class="ipsMessage ipsMessage_error">
				<ul>
				{{foreach $data['errors'] as $err}}
					<li>{$err}</li>
				{{endforeach}}
				</ul>
			</div>
		{{endif}}
		<form method="post" action="{$data['submit_url']}">
			<input type="hidden" name="csrfKey" value="{$data['csrf_key']}">
			<ul class="ipsForm ipsForm_vertical">
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_targets_field_manufacturer"}</label>
					<div class="ipsFieldRow_content"><input type="text" name="manufacturer" class="ipsInput_text" value="{$data['values']['manufacturer']}" required></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_targets_field_brand"}</label>
					<div class="ipsFieldRow_content"><input type="text" name="brand" class="ipsInput_text" value="{$data['values']['brand']}"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_targets_field_url"}</label>
					<div class="ipsFieldRow_content"><input type="url" name="scrape_url" class="ipsInput_text" value="{$data['values']['scrape_url']}" required></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_targets_field_rate"}</label>
					<div class="ipsFieldRow_content"><input type="number" name="rate_limit_ms" class="ipsInput_text" value="{$data['values']['rate_limit_ms']}" min="0"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_targets_field_known"}</label>
					<div class="ipsFieldRow_content">
						<label><input type="radio" name="is_known" value="1"{expression="(int) $data['values']['is_known'] === 1 ? ' checked' : ''"}> Yes</label>
						<label><input type="radio" name="is_known" value="0"{expression="(int) $data['values']['is_known'] === 0 ? ' checked' : ''"}> No</label>
					</div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_targets_field_enabled"}</label>
					<div class="ipsFieldRow_content">
						<label><input type="radio" name="enabled" value="1"{expression="(int) $data['values']['enabled'] === 1 ? ' checked' : ''"}> Yes</label>
						<label><input type="radio" name="enabled" value="0"{expression="(int) $data['values']['enabled'] === 0 ? ' checked' : ''"}> No</label>
					</div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_targets_field_config"}</label>
					<div class="ipsFieldRow_content"><textarea name="extraction_config" rows="14" class="ipsInput_text" style="font-family:monospace">{$data['values']['extraction_config']}</textarea></div>
				</li>
			</ul>
			<p class="ipsSpacer_top">
				<button type="submit" class="ipsButton ipsButton--primary">{lang="gdr_targets_save"}</button>
				<a href="{$data['cancel_url']}" class="ipsButton ipsButton--normal">{lang="gdr_targets_cancel"}</a>
			</p>
		</form>
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'admin',
	'group'    => 'rebates',
	'name'     => 'scraperqueue',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_title">
		<h1 class="ipsType_sectionHead">{lang="gdr_scraperq_title"}</h1>
	</div>
	<div class="ipsBox_body ipsPad">
		<p class="ipsType_light" style="margin-bottom:16px">{lang="gdr_scraperq_help"}</p>
		{{if count($data['rows']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_scraperq_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr>
					<th>{lang="gdr_submissions_manufacturer"}</th>
					<th>{lang="gdr_submissions_title_col"}</th>
					<th>{lang="gdr_submissions_amount"}</th>
					<th>{lang="gdr_submissions_end"}</th>
					<th>{lang="gdr_submissions_submitted_at"}</th>
					<th>{lang="gdr_submissions_actions"}</th>
				</tr></thead>
				<tbody>
				{{foreach $data['rows'] as $r}}
					<tr>
						<td>{$r['manufacturer']}</td>
						<td>{$r['title']}</td>
						<td>&#36;{expression="number_format((float) ($r['rebate_amount'] ?? 0), 2)"}</td>
						<td>{$r['end_date']}</td>
						<td>{$r['created_at']}</td>
						<td>
							<a href="{$r['approve_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdr_submissions_approve"}</a>
							<a href="{$r['reject_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_submissions_reject"}</a>
						</td>
					</tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'admin',
	'group'    => 'rebates',
	'name'     => 'scrapelog',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_title">
		<h1 class="ipsType_sectionHead">{lang="gdr_scrapelog_title"}</h1>
	</div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['rows']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_scrapelog_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr>
					<th>{lang="gdr_scrapelog_run_at"}</th>
					<th>{lang="gdr_submissions_manufacturer"}</th>
					<th>{lang="gdr_targets_url"}</th>
					<th>{lang="gdr_scrapelog_found"}</th>
					<th>{lang="gdr_scrapelog_created"}</th>
					<th>{lang="gdr_scrapelog_updated"}</th>
					<th>{lang="gdr_scrapelog_unchanged"}</th>
					<th>{lang="gdr_scrapelog_failures"}</th>
					<th>{lang="gdr_scrapelog_status"}</th>
				</tr></thead>
				<tbody>
				{{foreach $data['rows'] as $r}}
					<tr>
						<td>{$r['run_at']}</td>
						<td>{$r['manufacturer']}</td>
						<td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><a href="{$r['scrape_url']}" target="_blank" rel="noopener">{$r['scrape_url']}</a></td>
						<td>{$r['rebates_found']}</td>
						<td>{$r['rebates_created']}</td>
						<td>{$r['rebates_updated']}</td>
						<td>{$r['rebates_unchanged']}</td>
						<td>{$r['parse_failures']}</td>
						<td>{$r['status']}</td>
					</tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'admin',
	'group'    => 'rebates',
	'name'     => 'flags',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_title">
		<h1 class="ipsType_sectionHead">{lang="gdr_flags_title"}</h1>
	</div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['rows']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_flags_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr>
					<th>{lang="gdr_submissions_manufacturer"}</th>
					<th>{lang="gdr_submissions_title_col"}</th>
					<th>{lang="gdr_flags_count"}</th>
					<th>{lang="gdr_flags_last"}</th>
					<th>{lang="gdr_submissions_actions"}</th>
				</tr></thead>
				<tbody>
				{{foreach $data['rows'] as $r}}
					<tr>
						<td>{$r['manufacturer']}</td>
						<td>{$r['title']}</td>
						<td>{$r['flag_count']}</td>
						<td>{$r['last_flag']}</td>
						<td><a href="{$r['clear_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_flags_clear"}</a></td>
					</tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'admin',
	'group'    => 'rebates',
	'name'     => 'active',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_title">
		<h1 class="ipsType_sectionHead">{lang="gdr_active_title"}</h1>
	</div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['rows']) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_active_empty"}</p></div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead><tr>
					<th>{lang="gdr_submissions_manufacturer"}</th>
					<th>{lang="gdr_submissions_title_col"}</th>
					<th>{lang="gdr_submissions_amount"}</th>
					<th>{lang="gdr_submissions_end"}</th>
					<th>{lang="gdr_active_source"}</th>
					<th>{lang="gdr_submissions_actions"}</th>
				</tr></thead>
				<tbody>
				{{foreach $data['rows'] as $r}}
					<tr>
						<td>{$r['manufacturer']}</td>
						<td>{$r['title']}</td>
						<td>&#36;{expression="number_format((float) ($r['rebate_amount'] ?? 0), 2)"}</td>
						<td>{$r['end_date']}</td>
						<td>{$r['source']}</td>
						<td>
							<a href="{$r['expire_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_active_expire"}</a>
							<a href="{$r['archive_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_active_archive"}</a>
						</td>
					</tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'front',
	'group'    => 'rebates',
	'name'     => 'hub',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<div class="ipsBox_body ipsPad" style="text-align:center">
		<h1 style="margin:0">{lang="gdr_front_hub_title"}</h1>
		<p class="ipsType_light">{lang="gdr_front_hub_intro"}</p>
		<p class="ipsSpacer_top">
			<strong>{$data['active_count']}</strong> {lang="gdr_front_hub_active_count"}
			&nbsp;&middot;&nbsp;
			<strong>&#36;{expression="number_format($data['total_savings'], 0)"}</strong> {lang="gdr_front_hub_savings"}
		</p>
		<p><a href="{$data['submit_url']}" class="ipsButton ipsButton--primary">{lang="gdr_front_hub_submit_cta"}</a></p>
	</div>
</div>
<br>
{{if $data['featured']}}
<div class="ipsBox">
	<div class="ipsBox_title"><h2>{lang="gdr_front_hub_featured"}</h2></div>
	<div class="ipsBox_body ipsPad">
		<h3><a href="{$data['featured']['view_url']}">{$data['featured']['title']}</a></h3>
		<p><strong>{$data['featured']['manufacturer']}</strong> &mdash; &#36;{expression="number_format((float) ($data['featured']['rebate_amount'] ?? 0), 2)"}</p>
		<p class="ipsType_light">{$data['featured']['description']}</p>
	</div>
</div>
<br>
{{endif}}
<div class="ipsBox">
	<div class="ipsBox_title"><h2>{lang="gdr_front_hub_expiring"}</h2></div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['expiring']) > 0}}
			<ul class="ipsList_reset">
			{{foreach $data['expiring'] as $r}}
				<li><a href="{$r['view_url']}">{$r['title']}</a> &mdash; {$r['manufacturer']} &mdash; {$r['days_left']} days</li>
			{{endforeach}}
			</ul>
		{{else}}
			<p class="ipsType_light">{lang="gdr_front_hub_empty"}</p>
		{{endif}}
	</div>
</div>
<br>
<div class="ipsBox">
	<div class="ipsBox_title"><h2>{lang="gdr_front_hub_newest"}</h2></div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['newest']) > 0}}
			<ul class="ipsList_reset">
			{{foreach $data['newest'] as $r}}
				<li><a href="{$r['view_url']}">{$r['title']}</a> &mdash; {$r['manufacturer']} &mdash; &#36;{expression="number_format((float) ($r['rebate_amount'] ?? 0), 2)"}</li>
			{{endforeach}}
			</ul>
		{{else}}
			<p class="ipsType_light">{lang="gdr_front_hub_empty"}</p>
		{{endif}}
	</div>
</div>
<br>
<div class="ipsBox">
	<div class="ipsBox_title"><h2>{lang="gdr_front_hub_top_mfrs"}</h2></div>
	<div class="ipsBox_body ipsPad">
		{{if count($data['top_mfrs']) > 0}}
			<ul class="ipsList_reset">
			{{foreach $data['top_mfrs'] as $m}}
				<li>{$m['manufacturer']} ({$m['count']})</li>
			{{endforeach}}
			</ul>
		{{else}}
			<p class="ipsType_light">{lang="gdr_front_hub_empty"}</p>
		{{endif}}
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'front',
	'group'    => 'rebates',
	'name'     => 'view',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<div class="ipsBox_title">
		<h1 style="margin:0">{$data['rebate']['title']}</h1>
		<p class="ipsType_light" style="margin:0">{$data['rebate']['manufacturer']}{{if $data['rebate']['brand'] !== $data['rebate']['manufacturer']}} &mdash; {$data['rebate']['brand']}{{endif}}</p>
	</div>
	<div class="ipsBox_body ipsPad">
		<p>
			<strong>&#36;{expression="number_format((float) ($data['rebate']['rebate_amount'] ?? 0), 2)"}</strong>
			&middot; {$data['rebate']['rebate_type']}
			&middot; {$data['rebate']['product_type']}
			&middot; {$data['rebate']['status_label']}
			{{if $data['rebate']['days_left'] > 0}} &middot; {$data['rebate']['days_left']} days left{{endif}}
		</p>
		<p>
			<strong>{lang="gdr_front_view_start"}:</strong> {$data['rebate']['start_date']} &middot;
			<strong>{lang="gdr_front_view_end"}:</strong> {$data['rebate']['end_date']}
			{{if $data['rebate']['submission_deadline']}} &middot; <strong>{lang="gdr_front_view_deadline"}:</strong> {$data['rebate']['submission_deadline']}{{endif}}
		</p>
		{{if $data['rebate']['description']}}
			<h3>{lang="gdr_front_view_description"}</h3>
			<p>{$data['rebate']['description']}</p>
		{{endif}}
		{{if count($data['rebate']['steps_array']) > 0}}
			<h3>{lang="gdr_front_view_steps"}</h3>
			<ol>
			{{foreach $data['rebate']['steps_array'] as $step}}
				<li>{$step}</li>
			{{endforeach}}
			</ol>
		{{endif}}
		<p>
			{{if $data['rebate']['rebate_form_url']}}<a href="{$data['rebate']['rebate_form_url']}" class="ipsButton ipsButton--primary" target="_blank" rel="noopener">{lang="gdr_front_view_form"}</a>{{endif}}
			{{if $data['rebate']['rebate_pdf_url']}}<a href="{$data['rebate']['rebate_pdf_url']}" class="ipsButton ipsButton--normal" target="_blank" rel="noopener">{lang="gdr_front_view_pdf"}</a>{{endif}}
			{{if $data['rebate']['manufacturer_url']}}<a href="{$data['rebate']['manufacturer_url']}" class="ipsButton ipsButton--normal" target="_blank" rel="noopener">{lang="gdr_front_view_mfr_page"}</a>{{endif}}
		</p>
	</div>
</div>
<br>
<div class="ipsBox">
	<div class="ipsBox_title"><h2>{lang="gdr_front_view_tracker_header"}</h2></div>
	<div class="ipsBox_body ipsPad">
		{{if $data['logged_in']}}
			<form method="post" action="{$data['track_url']}">
				<input type="hidden" name="csrfKey" value="{$data['csrf_key']}">
				<ul class="ipsForm ipsForm_vertical">
					<li class="ipsFieldRow">
						<label class="ipsFieldRow_label">Status</label>
						<div class="ipsFieldRow_content">
							<select name="status">
								<option value="saved">{lang="gdr_front_view_track_saved"}</option>
								<option value="submitted">{lang="gdr_front_view_track_submitted"}</option>
								<option value="received">{lang="gdr_front_view_track_received"}</option>
								<option value="rejected">{lang="gdr_front_view_track_rejected"}</option>
							</select>
						</div>
					</li>
					<li class="ipsFieldRow">
						<label class="ipsFieldRow_label">{lang="gdr_front_view_track_submitted_on"}</label>
						<div class="ipsFieldRow_content"><input type="date" name="submitted_date" value="{$data['tracking']['submitted_date']}"></div>
					</li>
					<li class="ipsFieldRow">
						<label class="ipsFieldRow_label">{lang="gdr_front_view_track_received_on"}</label>
						<div class="ipsFieldRow_content"><input type="date" name="received_date" value="{$data['tracking']['received_date']}"></div>
					</li>
					<li class="ipsFieldRow">
						<label class="ipsFieldRow_label">{lang="gdr_front_view_track_notes"}</label>
						<div class="ipsFieldRow_content"><textarea name="notes" rows="3">{$data['tracking']['notes']}</textarea></div>
					</li>
				</ul>
				<p><button type="submit" class="ipsButton ipsButton--primary">{lang="gdr_front_view_track_save"}</button></p>
			</form>
		{{else}}
			<p class="ipsType_light">{lang="gdr_front_view_track_login"}</p>
		{{endif}}
	</div>
</div>
<br>
<div class="ipsBox">
	<div class="ipsBox_title"><h2>{lang="gdr_front_view_success_header"}</h2></div>
	<div class="ipsBox_body ipsPad">
		<p>
			<strong>{lang="gdr_front_view_success_total"}:</strong> {$data['metrics']['total']}
			&middot; <strong>{lang="gdr_front_view_success_rate"}:</strong> {$data['metrics']['success_pct']}%
			&middot; <strong>{lang="gdr_front_view_success_avg_days"}:</strong> {$data['metrics']['avg_days']}
		</p>
	</div>
</div>
<br>
<div class="ipsBox">
	<div class="ipsBox_title"><h2>{lang="gdr_front_view_flag_header"}</h2></div>
	<div class="ipsBox_body ipsPad">
		<p><a href="{$data['flag_url']}" class="ipsButton ipsButton--normal">{lang="gdr_front_view_flag_link"}</a></p>
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'front',
	'group'    => 'rebates',
	'name'     => 'submit',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<div class="ipsBox_title"><h1>{lang="gdr_front_submit_title"}</h1></div>
	<div class="ipsBox_body ipsPad">
		<p class="ipsType_light">{lang="gdr_front_submit_intro"}</p>
		{{if count($data['errors']) > 0}}
			<div class="ipsMessage ipsMessage_error">
				<ul>
				{{foreach $data['errors'] as $err}}
					<li>{$err}</li>
				{{endforeach}}
				</ul>
			</div>
		{{endif}}
		<form method="post" action="{$data['submit_url']}">
			<input type="hidden" name="csrfKey" value="{$data['csrf_key']}">
			<ul class="ipsForm ipsForm_vertical">
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_manufacturer"}</label>
					<div class="ipsFieldRow_content"><input type="text" name="manufacturer" class="ipsInput_text" value="{$data['values']['manufacturer']}" required></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_brand"}</label>
					<div class="ipsFieldRow_content"><input type="text" name="brand" class="ipsInput_text" value="{$data['values']['brand']}"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_rebate_title"}</label>
					<div class="ipsFieldRow_content"><input type="text" name="title" class="ipsInput_text" value="{$data['values']['title']}" required maxlength="255"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_rebate_type"}</label>
					<div class="ipsFieldRow_content">
						<select name="rebate_type">
						{{foreach $data['rebate_types'] as $t}}
							<option value="{$t}"{expression="$data['values']['rebate_type'] === $t ? ' selected' : ''"}>{$t}</option>
						{{endforeach}}
						</select>
					</div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_product_type"}</label>
					<div class="ipsFieldRow_content">
						<select name="product_type">
						{{foreach $data['product_types'] as $t}}
							<option value="{$t}"{expression="$data['values']['product_type'] === $t ? ' selected' : ''"}>{$t}</option>
						{{endforeach}}
						</select>
					</div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_amount"}</label>
					<div class="ipsFieldRow_content"><input type="number" step="0.01" min="0" name="rebate_amount" class="ipsInput_text" value="{$data['values']['rebate_amount']}" required></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_start"}</label>
					<div class="ipsFieldRow_content"><input type="date" name="start_date" value="{$data['values']['start_date']}"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_end"}</label>
					<div class="ipsFieldRow_content"><input type="date" name="end_date" value="{$data['values']['end_date']}"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_deadline"}</label>
					<div class="ipsFieldRow_content"><input type="date" name="submission_deadline" value="{$data['values']['submission_deadline']}"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_form_url"}</label>
					<div class="ipsFieldRow_content"><input type="url" name="rebate_form_url" class="ipsInput_text" value="{$data['values']['rebate_form_url']}"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_pdf_url"}</label>
					<div class="ipsFieldRow_content"><input type="url" name="rebate_pdf_url" class="ipsInput_text" value="{$data['values']['rebate_pdf_url']}"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_mfr_url"}</label>
					<div class="ipsFieldRow_content"><input type="url" name="manufacturer_url" class="ipsInput_text" value="{$data['values']['manufacturer_url']}"></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_eligible"}</label>
					<div class="ipsFieldRow_content"><textarea name="eligible_models" rows="2">{$data['values']['eligible_models']}</textarea></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_steps"}</label>
					<div class="ipsFieldRow_content"><textarea name="submission_steps" rows="5">{$data['values']['submission_steps']}</textarea></div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_submit_desc"}</label>
					<div class="ipsFieldRow_content"><textarea name="description" rows="6" required>{$data['values']['description']}</textarea></div>
				</li>
			</ul>
			<p class="ipsSpacer_top">
				<button type="submit" class="ipsButton ipsButton--primary">{lang="gdr_front_submit_save"}</button>
				<a href="{$data['cancel_url']}" class="ipsButton ipsButton--normal">{lang="gdr_front_submit_cancel"}</a>
			</p>
		</form>
	</div>
</div>
TEMPLATE_EOT
];

$templates[] = [
	'location' => 'front',
	'group'    => 'rebates',
	'name'     => 'flag',
	'params'   => 'data',
	'content'  => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<div class="ipsBox_title"><h1>{lang="gdr_front_flag_title"}</h1></div>
	<div class="ipsBox_body ipsPad">
		<form method="post" action="{$data['submit_url']}">
			<input type="hidden" name="csrfKey" value="{$data['csrf_key']}">
			<ul class="ipsForm ipsForm_vertical">
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_flag_reason"}</label>
					<div class="ipsFieldRow_content">
						<select name="reason">
						{{foreach $data['reasons'] as $r}}
							<option value="{$r}">{$r}</option>
						{{endforeach}}
						</select>
					</div>
				</li>
				<li class="ipsFieldRow">
					<label class="ipsFieldRow_label">{lang="gdr_front_flag_notes"}</label>
					<div class="ipsFieldRow_content"><textarea name="notes" rows="4"></textarea></div>
				</li>
			</ul>
			<p class="ipsSpacer_top">
				<button type="submit" class="ipsButton ipsButton--primary">{lang="gdr_front_flag_submit"}</button>
				<a href="{$data['cancel_url']}" class="ipsButton ipsButton--normal">{lang="gdr_front_submit_cancel"}</a>
			</p>
		</form>
	</div>
</div>
TEMPLATE_EOT
];

foreach ( $templates as $t )
{
	try
	{
		\IPS\Db::i()->delete( 'core_theme_templates', [
			'template_set_id=? AND template_app=? AND template_location=? AND template_group=? AND template_name=?',
			1, 'gdrebates', $t['location'], $t['group'], $t['name']
		] );
		\IPS\Db::i()->insert( 'core_theme_templates', [
			'template_set_id'  => 1,
			'template_app'     => 'gdrebates',
			'template_location' => $t['location'],
			'template_group'   => $t['group'],
			'template_name'    => $t['name'],
			'template_data'    => '$' . $t['params'],
			'template_content' => $t['content'],
		] );
	}
	catch ( \Exception ) {}
}

try
{
	\IPS\Theme::deleteCompiledTemplate( 'gdrebates' );
}
catch ( \Exception ) {}
