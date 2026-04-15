<?php
/**
 * @brief       GD Product Reviews — installOther template seeding
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 *
 * CLAUDE.md Rule #4: seed templates by direct INSERT into
 * core_theme_templates. Never use data/theme.xml — the XML importer
 * corrupts nowdoc comment syntax and breaks IPS template compilation.
 *
 * CLAUDE.md Rule #9: IPS templates have no comment syntax. Do not put
 * HTML or double-brace comments inside template_content. Any commentary
 * belongs in this PHP file (like this block), not inside the nowdoc body.
 *
 * CLAUDE.md Rule #12: only the proven-safe template patterns are used
 * below — {$var}, {lang="static_key"}, {{if}}, {{foreach}}, and flat
 * {expression="..."} calls. Every URL that requires interpolation is
 * pre-built in the controller and passed as a scalar variable.
 */

namespace IPS\gdreviews\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$templates = [

	/* ===== ADMIN: dashboard ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdreviews',
		'location'      => 'admin',
		'group'         => 'reviews',
		'template_name' => 'dashboard',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<div class="ipsBox_title" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
		<h1 style="margin:0">{lang="gdr_dash_title"}</h1>
		<a href="{$data['queue_url']}" class="ipsButton ipsButton_primary">{lang="gdr_queue_title"}</a>
	</div>
	<div class="ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['total'] )"}</div>
				<div>{lang="gdr_dash_total_reviews"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold;color:#d97706">{expression="number_format( $data['pending'] )"}</div>
				<div>{lang="gdr_dash_pending"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold;color:#dc2626">{expression="number_format( $data['flagged'] )"}</div>
				<div>{lang="gdr_dash_flagged"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold;color:#16a34a">{expression="number_format( $data['approved'] )"}</div>
				<div>{lang="gdr_dash_approved"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 180px;padding:16px;text-align:center">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['rejected'] )"}</div>
				<div>{lang="gdr_dash_rejected"}</div>
			</div>
		</div>

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div class="ipsBox" style="flex:1 1 260px;padding:16px">
				<div style="font-size:1.6em;font-weight:bold">{$data['avg_rating']} / 5</div>
				<div>{lang="gdr_dash_avg_rating"}</div>
			</div>
			<div class="ipsBox" style="flex:1 1 260px;padding:16px">
				<div style="font-size:1.6em;font-weight:bold">{$data['verified_pct']}%</div>
				<div>{lang="gdr_dash_verified_pct"}</div>
			</div>
		</div>

		<div style="display:flex;gap:24px;flex-wrap:wrap">
			<div style="flex:2 1 420px">
				<h3 class="ipsType_sectionHead" style="margin-top:0">{lang="gdr_dash_latest"}</h3>
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>{lang="gdr_queue_col_title"}</th><th>{lang="gdr_queue_col_product"}</th><th style="width:80px">{lang="gdr_queue_col_rating"}</th><th style="width:120px">{lang="gdr_queue_col_submitted"}</th></tr></thead>
					<tbody>
					{{foreach $data['latest'] as $row}}
						<tr>
							<td><strong>{$row['title']}</strong></td>
							<td><code>{$row['upc']}</code></td>
							<td>{$row['overall_rating']} &#9733;</td>
							<td>{$row['created_at']}</td>
						</tr>
					{{endforeach}}
					{{if count( $data['latest'] ) === 0}}
						<tr><td colspan="4" style="text-align:center;color:#999;padding:24px">{lang="gdr_dash_empty"}</td></tr>
					{{endif}}
					</tbody>
				</table>
			</div>

			<div style="flex:1 1 260px">
				<h3 class="ipsType_sectionHead" style="margin-top:0">{lang="gdr_dash_top_reviewers"}</h3>
				<table class="ipsTable ipsTable_zebra" style="width:100%">
					<thead><tr><th>Member ID</th><th style="width:80px">Count</th></tr></thead>
					<tbody>
					{{foreach $data['top_reviewers'] as $row}}
						<tr>
							<td>#{$row['member_id']}</td>
							<td>{$row['count']}</td>
						</tr>
					{{endforeach}}
					{{if count( $data['top_reviewers'] ) === 0}}
						<tr><td colspan="2" style="text-align:center;color:#999;padding:16px">&mdash;</td></tr>
					{{endif}}
					</tbody>
				</table>
			</div>
		</div>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: queue (pending / flagged tabs) ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdreviews',
		'location'      => 'admin',
		'group'         => 'reviews',
		'template_name' => 'queue',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gdr_queue_title"}</h1>
	<div class="ipsPad">

		<p class="ipsType_light">{lang="gdr_queue_intro"}</p>

		<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
			{{if $data['tab'] === 'pending'}}
				<a href="{$data['pending_url']}" class="ipsButton ipsButton_primary">{lang="gdr_queue_tab_pending"}</a>
				<a href="{$data['flagged_url']}" class="ipsButton ipsButton_medium">{lang="gdr_queue_tab_flagged"}</a>
			{{else}}
				<a href="{$data['pending_url']}" class="ipsButton ipsButton_medium">{lang="gdr_queue_tab_pending"}</a>
				<a href="{$data['flagged_url']}" class="ipsButton ipsButton_primary">{lang="gdr_queue_tab_flagged"}</a>
			{{endif}}
		</div>

		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th>{lang="gdr_queue_col_title"}</th>
					<th>{lang="gdr_queue_col_product"}</th>
					<th style="width:80px">{lang="gdr_queue_col_rating"}</th>
					<th style="width:100px">{lang="gdr_queue_col_verified"}</th>
					<th style="width:140px">{lang="gdr_queue_col_submitted"}</th>
					<th style="width:200px">{lang="gdr_queue_col_actions"}</th>
				</tr>
			</thead>
			<tbody>
			{{foreach $data['rows'] as $row}}
				<tr>
					<td><strong>{$row['title']}</strong></td>
					<td><code>{$row['upc']}</code></td>
					<td>{$row['overall_rating']} &#9733;</td>
					<td>
						{{if $row['verified_purchase']}}
							<span class="ipsBadge ipsBadge--positive">{lang="gdr_queue_verified_yes"}</span>
						{{else}}
							<span class="ipsBadge ipsBadge--neutral">{lang="gdr_queue_verified_no"}</span>
						{{endif}}
					</td>
					<td>{$row['created_at']}</td>
					<td>
						<a href="{$row['view_url']}" class="ipsButton ipsButton--small ipsButton--primary">{lang="gdr_queue_view"}</a>
						<a href="{$row['approve_url']}" class="ipsButton ipsButton--small ipsButton--positive">{lang="gdr_queue_approve"}</a>
					</td>
				</tr>
			{{endforeach}}
			{{if count( $data['rows'] ) === 0}}
				<tr><td colspan="6" style="text-align:center;color:#999;padding:24px">
					{{if $data['tab'] === 'pending'}}{lang="gdr_queue_empty_pending"}{{else}}{lang="gdr_queue_empty_flagged"}{{endif}}
				</td></tr>
			{{endif}}
			</tbody>
		</table>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: queue single view + reject form ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdreviews',
		'location'      => 'admin',
		'group'         => 'reviews',
		'template_name' => 'queueView',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox">
	<h1 class="ipsBox_title">{lang="gdr_queue_view_title"}</h1>
	<div class="ipsPad">

		<p><a href="{$data['back_url']}" class="ipsButton ipsButton--small ipsButton--normal">&larr; {lang="gdr_queue_back"}</a></p>

		<div class="ipsBox" style="padding:16px;margin-bottom:16px">
			<h2 style="margin-top:0">{$data['review']['title']}</h2>
			<p class="ipsType_light">
				<code>{$data['review']['upc']}</code> &middot;
				Member #{$data['review']['member_id']} &middot;
				{$data['review']['overall_rating']} &#9733; &middot;
				{$data['review']['created_at']}
				{{if $data['review']['verified_purchase']}} &middot; <span class="ipsBadge ipsBadge--positive">{lang="gdr_queue_verified_yes"}</span>{{endif}}
			</p>

			<h3>{lang="gdr_queue_body"}</h3>
			<p style="white-space:pre-wrap">{$data['review']['body']}</p>

			{{if $data['review']['pros']}}
				<h3>{lang="gdr_queue_pros"}</h3>
				<p>{$data['review']['pros']}</p>
			{{endif}}
			{{if $data['review']['cons']}}
				<h3>{lang="gdr_queue_cons"}</h3>
				<p>{$data['review']['cons']}</p>
			{{endif}}

			<dl>
				<dt>{lang="gdr_queue_recommend"}</dt>
				<dd>
					{{if $data['review']['would_recommend'] === 1}}{lang="gdr_queue_yes"}{{endif}}
					{{if $data['review']['would_recommend'] === 0}}{lang="gdr_queue_no"}{{endif}}
					{{if $data['review']['would_recommend'] === null}}&mdash;{{endif}}
				</dd>
				{{if $data['review']['usage_context']}}
					<dt>{lang="gdr_queue_context"}</dt>
					<dd>{$data['review']['usage_context']}</dd>
				{{endif}}
				{{if $data['review']['time_owned']}}
					<dt>{lang="gdr_queue_time_owned"}</dt>
					<dd>{$data['review']['time_owned']}</dd>
				{{endif}}
			</dl>
		</div>

		<div style="display:flex;gap:16px;flex-wrap:wrap">
			<a href="{$data['approve_url']}" class="ipsButton ipsButton_primary">{lang="gdr_queue_approve"}</a>

			<form method="post" action="{$data['reject_action']}" style="flex:1 1 400px;display:flex;gap:8px;align-items:flex-start">
				<input type="hidden" name="csrfKey" value="{$data['csrf_key']}" />
				<textarea name="rejection_reason" placeholder="{lang="gdr_queue_reject_reason"}" rows="2" style="flex:1"></textarea>
				<button type="submit" class="ipsButton ipsButton_medium ipsButton--negative">{lang="gdr_queue_reject"}</button>
			</form>
		</div>

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: reviews hub ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdreviews',
		'location'      => 'front',
		'group'         => 'reviews',
		'template_name' => 'hub',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox" style="padding:24px;margin-bottom:24px;text-align:center;background:#111;color:#fff">
	<h1 class="ipsType_pageTitle" style="color:#fff;margin:0 0 8px 0">{lang="gdr_front_hub_title"}</h1>
	<p style="margin:0;font-size:1.2em">{lang="gdr_front_hub_hero"}</p>
</div>

<div style="display:flex;gap:24px;flex-wrap:wrap">

	<section style="flex:2 1 520px">
		<h2 class="ipsType_sectionHead">{lang="gdr_front_hub_latest"}</h2>
		{{foreach $data['latest'] as $row}}
			<article class="ipsBox" style="padding:16px;margin-bottom:12px">
				<header style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap">
					<strong>{$row['title']}</strong>
					<span>{$row['overall_rating']} &#9733;</span>
				</header>
				<p class="ipsType_light" style="margin:4px 0 0 0">
					<code>{$row['upc']}</code> &middot; {lang="gdr_front_by"} #{$row['member_id']} &middot; {$row['created_at']}
					{{if $row['verified_purchase']}} &middot; <span class="ipsBadge ipsBadge_positive">{lang="gdr_front_verified_badge"}</span>{{endif}}
				</p>
				<p style="margin:8px 0 0 0">{$row['excerpt']}</p>
			</article>
		{{endforeach}}
		{{if count( $data['latest'] ) === 0}}
			<p class="ipsType_light">{lang="gdr_front_hub_empty_latest"}</p>
		{{endif}}
	</section>

	<aside style="flex:1 1 260px">
		<h2 class="ipsType_sectionHead">{lang="gdr_front_hub_featured"}</h2>
		{{if $data['featured']}}
			<article class="ipsBox" style="padding:16px;margin-bottom:24px">
				<strong>{$data['featured']['title']}</strong>
				<p class="ipsType_light" style="margin:4px 0 0 0"><code>{$data['featured']['upc']}</code> &middot; {$data['featured']['overall_rating']} &#9733;</p>
				<p style="margin:8px 0 0 0">{$data['featured']['excerpt']}</p>
			</article>
		{{else}}
			<p class="ipsType_light">{lang="gdr_front_hub_no_featured"}</p>
		{{endif}}

		<h2 class="ipsType_sectionHead">{lang="gdr_front_hub_top_rated"}</h2>
		<ol style="padding-left:20px">
		{{foreach $data['top_rated'] as $row}}
			<li><code>{$row['upc']}</code> &mdash; {$row['avg_rating']} &#9733; ({$row['count']} {lang="gdr_front_review_count"})</li>
		{{endforeach}}
		</ol>
		{{if count( $data['top_rated'] ) === 0}}
			<p class="ipsType_light">{lang="gdr_front_hub_empty_top"}</p>
		{{endif}}

		<h2 class="ipsType_sectionHead">{lang="gdr_front_hub_most_reviewed"}</h2>
		<ol style="padding-left:20px">
		{{foreach $data['most_reviewed'] as $row}}
			<li><code>{$row['upc']}</code> &mdash; {$row['count']} {lang="gdr_front_review_count"}</li>
		{{endforeach}}
		</ol>

		<h2 class="ipsType_sectionHead">{lang="gdr_front_hub_verified"}</h2>
		{{foreach $data['verified'] as $row}}
			<div style="margin-bottom:8px">
				<strong>{$row['title']}</strong><br/>
				<span class="ipsType_light"><code>{$row['upc']}</code> &middot; {$row['overall_rating']} &#9733;</span>
			</div>
		{{endforeach}}
		{{if count( $data['verified'] ) === 0}}
			<p class="ipsType_light">{lang="gdr_front_hub_empty_verified"}</p>
		{{endif}}
	</aside>

</div>
TEMPLATE_EOT,
	],

	/* ===== FRONT: review submission form ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdreviews',
		'location'      => 'front',
		'group'         => 'reviews',
		'template_name' => 'submit',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<h1 class="ipsType_pageTitle">{lang="gdr_front_submit_title"}</h1>
<p class="ipsType_light">{lang="gdr_front_submit_intro"}</p>

<div class="ipsBox" style="padding:16px;margin-bottom:16px">
	<strong>{lang="gdr_front_submit_product"}:</strong>
	{$data['product']['title']} <span class="ipsType_light">(<code>{$data['product']['upc']}</code>)</span>
</div>

{{if count( $data['errors'] ) > 0}}
	<div class="ipsMessage ipsMessage_error" style="margin-bottom:16px">
		<strong>{lang="gdr_front_submit_form_errors"}</strong>
		<ul style="margin:8px 0 0 20px">
		{{foreach $data['errors'] as $err}}
			<li>{$err}</li>
		{{endforeach}}
		</ul>
	</div>
{{endif}}

<form method="post" action="{$data['submit_url']}" class="ipsForm">
	<input type="hidden" name="csrfKey" value="{$data['csrf_key']}" />

	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label" for="overall_rating">{lang="gdr_front_submit_overall"}</label>
		<div class="ipsFieldRow_content">
			<select name="overall_rating" id="overall_rating" required>
				<option value="5" {{if $data['overall_rating'] === 5}}selected{{endif}}>5 &#9733; &#9733; &#9733; &#9733; &#9733;</option>
				<option value="4" {{if $data['overall_rating'] === 4}}selected{{endif}}>4 &#9733; &#9733; &#9733; &#9733;</option>
				<option value="3" {{if $data['overall_rating'] === 3}}selected{{endif}}>3 &#9733; &#9733; &#9733;</option>
				<option value="2" {{if $data['overall_rating'] === 2}}selected{{endif}}>2 &#9733; &#9733;</option>
				<option value="1" {{if $data['overall_rating'] === 1}}selected{{endif}}>1 &#9733;</option>
			</select>
			<p class="ipsType_light ipsType_small">{lang="gdr_front_submit_overall_desc"}</p>
		</div>
	</div>

	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label" for="title">{lang="gdr_front_submit_title_field"}</label>
		<div class="ipsFieldRow_content">
			<input type="text" name="title" id="title" value="{$data['title']}" maxlength="150" class="ipsField_text" style="width:100%" required />
			<p class="ipsType_light ipsType_small">{lang="gdr_front_submit_title_desc"}</p>
		</div>
	</div>

	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label" for="body">{lang="gdr_front_submit_body"}</label>
		<div class="ipsFieldRow_content">
			<textarea name="body" id="body" rows="8" class="ipsField_text" style="width:100%" required>{$data['body']}</textarea>
			<p class="ipsType_light ipsType_small">{lang="gdr_front_submit_body_desc"}</p>
		</div>
	</div>

	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label" for="pros">{lang="gdr_front_submit_pros"}</label>
		<div class="ipsFieldRow_content">
			<textarea name="pros" id="pros" rows="2" class="ipsField_text" style="width:100%">{$data['pros']}</textarea>
		</div>
	</div>

	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label" for="cons">{lang="gdr_front_submit_cons"}</label>
		<div class="ipsFieldRow_content">
			<textarea name="cons" id="cons" rows="2" class="ipsField_text" style="width:100%">{$data['cons']}</textarea>
		</div>
	</div>

	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label">{lang="gdr_front_submit_recommend"}</label>
		<div class="ipsFieldRow_content">
			<label><input type="radio" name="would_recommend" value="yes" {{if $data['would_recommend'] === 1}}checked{{endif}} /> {lang="gdr_front_submit_recommend_yes"}</label>
			<label style="margin-left:12px"><input type="radio" name="would_recommend" value="no" {{if $data['would_recommend'] === 0}}checked{{endif}} /> {lang="gdr_front_submit_recommend_no"}</label>
			<label style="margin-left:12px"><input type="radio" name="would_recommend" value="" {{if $data['would_recommend'] === null}}checked{{endif}} /> {lang="gdr_front_submit_recommend_skip"}</label>
		</div>
	</div>

	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label" for="usage_context">{lang="gdr_front_submit_context"}</label>
		<div class="ipsFieldRow_content">
			<input type="text" name="usage_context" id="usage_context" value="{$data['usage_context']}" maxlength="50" class="ipsField_text" style="width:100%" />
			<p class="ipsType_light ipsType_small">{lang="gdr_front_submit_context_desc"}</p>
		</div>
	</div>

	<div class="ipsFieldRow">
		<label class="ipsFieldRow_label" for="time_owned">{lang="gdr_front_submit_time_owned"}</label>
		<div class="ipsFieldRow_content">
			<input type="text" name="time_owned" id="time_owned" value="{$data['time_owned']}" maxlength="50" class="ipsField_text" style="width:100%" />
			<p class="ipsType_light ipsType_small">{lang="gdr_front_submit_time_owned_desc"}</p>
		</div>
	</div>

	<div class="ipsFieldRow" style="display:flex;gap:8px">
		<button type="submit" class="ipsButton ipsButton_primary">{lang="gdr_front_submit_submit"}</button>
		<a href="{$data['cancel_url']}" class="ipsButton ipsButton_medium">{lang="gdr_front_submit_cancel"}</a>
	</div>
</form>
TEMPLATE_EOT,
	],
];

foreach ( $templates as $tpl )
{
	try
	{
		\IPS\Db::i()->delete( 'core_theme_templates', [
			'template_set_id=? AND template_app=? AND template_location=? AND template_group=? AND template_name=?',
			(int) $tpl['set_id'], $tpl['app'], $tpl['location'], $tpl['group'], $tpl['template_name'],
		]);
	}
	catch ( \Exception ) {}

	try
	{
		\IPS\Db::i()->insert( 'core_theme_templates', [
			'template_set_id'     => (int) $tpl['set_id'],
			'template_app'        => $tpl['app'],
			'template_location'   => $tpl['location'],
			'template_group'      => $tpl['group'],
			'template_name'       => $tpl['template_name'],
			'template_data'       => $tpl['template_data'],
			'template_content'    => $tpl['template_content'],
		]);
	}
	catch ( \Exception ) {}
}

try
{
	\IPS\Theme::deleteCompiledTemplate( 'gdreviews' );
}
catch ( \Throwable ) {}
