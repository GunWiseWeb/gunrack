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
 * HTML or double-brace comments inside template_content.
 *
 * CLAUDE.md Rule #12: only the proven-safe template patterns are used.
 *
 * CLAUDE.md Rule #20: admin templates use IPS v5 BEM classes
 * (ipsButton--primary / --normal / --negative / --small) and the
 * ipsBox ipsPull wrapper. The page <h1> is rendered from
 * \IPS\Output::i()->title — no ipsBox_title divs in admin templates.
 */

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
<div class="ipsBox ipsPull">
	<div style="display:flex;justify-content:flex-end;padding:10px 16px;border-bottom:1px solid var(--i-border-color, #e0e0e0)">
		<a href="{$data['queue_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdr_queue_title"}</a>
	</div>
	<div class="ipsBox_body ipsPad">

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div style="flex:1 1 180px;padding:16px;text-align:center;border:1px solid var(--i-border-color, #e0e0e0);border-radius:4px">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['total'] )"}</div>
				<div>{lang="gdr_dash_total_reviews"}</div>
			</div>
			<div style="flex:1 1 180px;padding:16px;text-align:center;border:1px solid var(--i-border-color, #e0e0e0);border-radius:4px">
				<div style="font-size:2em;font-weight:bold;color:#d97706">{expression="number_format( $data['pending'] )"}</div>
				<div>{lang="gdr_dash_pending"}</div>
			</div>
			<div style="flex:1 1 180px;padding:16px;text-align:center;border:1px solid var(--i-border-color, #e0e0e0);border-radius:4px">
				<div style="font-size:2em;font-weight:bold;color:#dc2626">{expression="number_format( $data['flagged'] )"}</div>
				<div>{lang="gdr_dash_flagged"}</div>
			</div>
			<div style="flex:1 1 180px;padding:16px;text-align:center;border:1px solid var(--i-border-color, #e0e0e0);border-radius:4px">
				<div style="font-size:2em;font-weight:bold;color:#16a34a">{expression="number_format( $data['approved'] )"}</div>
				<div>{lang="gdr_dash_approved"}</div>
			</div>
			<div style="flex:1 1 180px;padding:16px;text-align:center;border:1px solid var(--i-border-color, #e0e0e0);border-radius:4px">
				<div style="font-size:2em;font-weight:bold">{expression="number_format( $data['rejected'] )"}</div>
				<div>{lang="gdr_dash_rejected"}</div>
			</div>
		</div>

		<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
			<div style="flex:1 1 260px;padding:16px;border:1px solid var(--i-border-color, #e0e0e0);border-radius:4px">
				<div style="font-size:1.6em;font-weight:bold">{$data['avg_rating']} / 5</div>
				<div>{lang="gdr_dash_avg_rating"}</div>
			</div>
			<div style="flex:1 1 260px;padding:16px;border:1px solid var(--i-border-color, #e0e0e0);border-radius:4px">
				<div style="font-size:1.6em;font-weight:bold">{$data['verified_pct']}%</div>
				<div>{lang="gdr_dash_verified_pct"}</div>
			</div>
		</div>

		<div style="display:flex;gap:24px;flex-wrap:wrap">
			<div style="flex:2 1 420px">
				<h2 class="ipsType_sectionHead" style="margin:0 0 12px">{lang="gdr_dash_latest"}</h2>
				{{if count($data['latest']) === 0}}
					<div class="ipsEmptyMessage"><p>{lang="gdr_dash_empty"}</p></div>
				{{else}}
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
						</tbody>
					</table>
				{{endif}}
			</div>

			<div style="flex:1 1 260px">
				<h2 class="ipsType_sectionHead" style="margin:0 0 12px">{lang="gdr_dash_top_reviewers"}</h2>
				{{if count($data['top_reviewers']) === 0}}
					<div class="ipsEmptyMessage"><p>&mdash;</p></div>
				{{else}}
					<table class="ipsTable ipsTable_zebra" style="width:100%">
						<thead><tr><th>Member ID</th><th style="width:80px">Count</th></tr></thead>
						<tbody>
						{{foreach $data['top_reviewers'] as $row}}
							<tr>
								<td>#{$row['member_id']}</td>
								<td>{$row['count']}</td>
							</tr>
						{{endforeach}}
						</tbody>
					</table>
				{{endif}}
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
<div class="ipsBox ipsPull">
	<div style="display:flex;justify-content:flex-end;gap:8px;padding:10px 16px;border-bottom:1px solid var(--i-border-color, #e0e0e0)">
		{{if $data['tab'] === 'pending'}}
			<a href="{$data['pending_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdr_queue_tab_pending"}</a>
			<a href="{$data['flagged_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_queue_tab_flagged"}</a>
		{{else}}
			<a href="{$data['pending_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_queue_tab_pending"}</a>
			<a href="{$data['flagged_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdr_queue_tab_flagged"}</a>
		{{endif}}
	</div>
	<div class="ipsBox_body ipsPad">

		<p class="ipsType_light">{lang="gdr_queue_intro"}</p>

		{{if count($data['rows']) === 0}}
			<div class="ipsEmptyMessage">
				<p>
					{{if $data['tab'] === 'pending'}}{lang="gdr_queue_empty_pending"}{{else}}{lang="gdr_queue_empty_flagged"}{{endif}}
				</p>
			</div>
		{{else}}
			<table class="ipsTable ipsTable_zebra" style="width:100%">
				<thead>
					<tr>
						<th>{lang="gdr_queue_col_title"}</th>
						<th>{lang="gdr_queue_col_product"}</th>
						<th style="width:80px">{lang="gdr_queue_col_rating"}</th>
						<th style="width:100px">{lang="gdr_queue_col_verified"}</th>
						<th style="width:140px">{lang="gdr_queue_col_submitted"}</th>
						<th style="width:220px">{lang="gdr_queue_col_actions"}</th>
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
							<a href="{$row['view_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_queue_view"}</a>
							<a href="{$row['approve_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdr_queue_approve"}</a>
						</td>
					</tr>
				{{endforeach}}
				</tbody>
			</table>
		{{endif}}

	</div>
</div>
TEMPLATE_EOT,
	],

	/* ===== ADMIN: queue single view (detail + approve/reject links) ===== */
	[
		'set_id'        => 1,
		'app'           => 'gdreviews',
		'location'      => 'admin',
		'group'         => 'reviews',
		'template_name' => 'queueView',
		'template_data' => '$data',
		'template_content' => <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div style="display:flex;justify-content:space-between;gap:8px;padding:10px 16px;border-bottom:1px solid var(--i-border-color, #e0e0e0)">
		<a href="{$data['back_url']}" class="ipsButton ipsButton--normal ipsButton--small">{lang="gdr_queue_back"}</a>
		<div style="display:flex;gap:8px">
			<a href="{$data['approve_url']}" class="ipsButton ipsButton--primary ipsButton--small">{lang="gdr_queue_approve"}</a>
			<a href="{$data['reject_url']}" class="ipsButton ipsButton--negative ipsButton--small">{lang="gdr_queue_reject"}</a>
		</div>
	</div>
	<div class="ipsBox_body ipsPad">

		<h2 class="ipsType_sectionHead" style="margin:0 0 12px">{$data['review']['title']}</h2>
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
<div class="ipsBox ipsPull" style="background:linear-gradient(135deg,#1e3a5f 0%,#2c5282 100%);color:#fff;padding:48px 32px;margin-bottom:24px;text-align:center;border-radius:4px">
	<h1 class="ipsType_pageTitle" style="color:#fff;margin:0 0 8px 0;font-size:2.2em">{lang="gdr_front_hub_title"}</h1>
	<p style="margin:0 0 24px 0;font-size:1.15em;opacity:0.92">{lang="gdr_front_hub_hero"}</p>

	<form method="get" action="{$data['search_url']}" style="max-width:560px;margin:0 auto 24px auto;display:flex;gap:8px">
		<input type="hidden" name="app" value="gdreviews" />
		<input type="hidden" name="module" value="reviews" />
		<input type="hidden" name="controller" value="hub" />
		<input type="text" name="q" placeholder="{lang='gdr_front_hub_search_ph'}" class="ipsField_text" style="flex:1;padding:10px 14px;border:none;border-radius:4px;font-size:1em" />
		<button type="submit" class="ipsButton ipsButton--primary">{lang="gdr_front_hub_search_btn"}</button>
	</form>

	<div style="display:flex;gap:32px;justify-content:center;flex-wrap:wrap;margin-top:8px">
		<div>
			<div style="font-size:2em;font-weight:bold;line-height:1">{expression="number_format( $data['total_reviews'] )"}</div>
			<div style="opacity:0.85;font-size:0.9em;margin-top:2px">{lang="gdr_front_hub_stat_reviews"}</div>
		</div>
		<div>
			<div style="font-size:2em;font-weight:bold;line-height:1">{expression="number_format( $data['total_products'] )"}</div>
			<div style="opacity:0.85;font-size:0.9em;margin-top:2px">{lang="gdr_front_hub_stat_products"}</div>
		</div>
		<div>
			<div style="font-size:2em;font-weight:bold;line-height:1">{expression="number_format( $data['total_verified'] )"}</div>
			<div style="opacity:0.85;font-size:0.9em;margin-top:2px">{lang="gdr_front_hub_stat_verified"}</div>
		</div>
	</div>
</div>

{{if $data['featured']}}
<div class="ipsBox ipsPull" style="padding:20px;margin-bottom:24px;border-left:4px solid #d97706;background:var(--i-color_highlighted, #fff8ec)">
	<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
		<span class="ipsBadge ipsBadge--warning">{lang="gdr_front_hub_featured"}</span>
		<strong style="font-size:1.1em">{$data['featured']['title']}</strong>
		<span style="margin-left:auto;color:#d97706;font-weight:bold">{$data['featured']['overall_rating']} &#9733;</span>
	</div>
	<p class="ipsType_light" style="margin:0 0 8px 0;font-size:0.9em">
		<code>{$data['featured']['upc']}</code> &middot; {lang="gdr_front_by"} #{$data['featured']['member_id']} &middot; {$data['featured']['created_at']}
		{{if $data['featured']['verified_purchase']}} &middot; <span class="ipsBadge ipsBadge--positive">{lang="gdr_front_verified_badge"}</span>{{endif}}
	</p>
	<p style="margin:0">{$data['featured']['excerpt']}</p>
</div>
{{endif}}

<div class="ipsGrid ipsGrid_collapsePhone" style="display:flex;gap:24px;flex-wrap:wrap">

	<section class="ipsGrid_span8" style="flex:2 1 520px">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
			<h2 class="ipsType_sectionHead" style="margin:0">{lang="gdr_front_hub_latest"}</h2>
			<span class="ipsType_light ipsType_small">{expression="count( $data['latest'] )"} {lang="gdr_front_review_count"}</span>
		</div>

		{{if count( $data['latest'] ) === 0}}
			<div class="ipsEmptyMessage"><p>{lang="gdr_front_hub_empty_latest"}</p></div>
		{{else}}
			{{foreach $data['latest'] as $row}}
				<article class="ipsBox ipsPull" style="padding:16px;margin-bottom:12px;border-left:3px solid var(--i-color_primary, #2c5282)">
					<header style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:baseline">
						<strong style="font-size:1.05em">{$row['title']}</strong>
						<span style="color:#d97706;font-weight:bold">{$row['overall_rating']} &#9733;</span>
					</header>
					<p class="ipsType_light ipsType_small" style="margin:4px 0 0 0">
						<code>{$row['upc']}</code> &middot; {lang="gdr_front_by"} #{$row['member_id']} &middot; {$row['created_at']}
						{{if $row['verified_purchase']}} &middot; <span class="ipsBadge ipsBadge--positive">{lang="gdr_front_verified_badge"}</span>{{endif}}
					</p>
					<p style="margin:8px 0 0 0;line-height:1.5">{$row['excerpt']}</p>
				</article>
			{{endforeach}}
		{{endif}}
	</section>

	<aside class="ipsGrid_span4" style="flex:1 1 260px">
		<div class="ipsBox ipsPull" style="padding:16px;margin-bottom:16px">
			<h3 class="ipsType_sectionHead" style="margin:0 0 12px 0;font-size:1.05em">{lang="gdr_front_hub_top_rated"}</h3>
			{{if count( $data['top_rated'] ) === 0}}
				<p class="ipsType_light ipsType_small">{lang="gdr_front_hub_empty_top"}</p>
			{{else}}
				<ol style="padding-left:20px;margin:0">
				{{foreach $data['top_rated'] as $row}}
					<li style="margin-bottom:6px"><code>{$row['upc']}</code><br/><span class="ipsType_light ipsType_small">{$row['avg_rating']} &#9733; &middot; {$row['count']} {lang="gdr_front_review_count"}</span></li>
				{{endforeach}}
				</ol>
			{{endif}}
		</div>

		<div class="ipsBox ipsPull" style="padding:16px;margin-bottom:16px">
			<h3 class="ipsType_sectionHead" style="margin:0 0 12px 0;font-size:1.05em">{lang="gdr_front_hub_most_reviewed"}</h3>
			{{if count( $data['most_reviewed'] ) === 0}}
				<p class="ipsType_light ipsType_small">{lang="gdr_front_hub_empty_most"}</p>
			{{else}}
				<ol style="padding-left:20px;margin:0">
				{{foreach $data['most_reviewed'] as $row}}
					<li style="margin-bottom:6px"><code>{$row['upc']}</code><br/><span class="ipsType_light ipsType_small">{$row['count']} {lang="gdr_front_review_count"}</span></li>
				{{endforeach}}
				</ol>
			{{endif}}
		</div>

		<div class="ipsBox ipsPull" style="padding:16px">
			<h3 class="ipsType_sectionHead" style="margin:0 0 12px 0;font-size:1.05em">{lang="gdr_front_hub_verified"}</h3>
			{{if count( $data['verified'] ) === 0}}
				<p class="ipsType_light ipsType_small">{lang="gdr_front_hub_empty_verified"}</p>
			{{else}}
				{{foreach $data['verified'] as $row}}
					<div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--i-border-color, #e0e0e0)">
						<strong style="font-size:0.95em">{$row['title']}</strong>
						<div class="ipsType_light ipsType_small"><code>{$row['upc']}</code> &middot; {$row['overall_rating']} &#9733;</div>
					</div>
				{{endforeach}}
			{{endif}}
		</div>
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
		<button type="submit" class="ipsButton ipsButton--primary">{lang="gdr_front_submit_submit"}</button>
		<a href="{$data['cancel_url']}" class="ipsButton ipsButton--normal">{lang="gdr_front_submit_cancel"}</a>
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
