<?php
/**
 * @brief  GD Dealer Manager — Template sync for v1.0.31 (10031)
 *
 * Replaces rich-text textareas with IPS editor output across the
 * dealer dashboard and public profile. Each affected template is
 * overwritten via \IPS\Db::i()->replace() keyed on the existing
 * template_id so upgrades are idempotent.
 *
 * Commit 3 scope: dealerReviews only (respond + inline edit forms).
 * Subsequent commits extend this file with rate/editReview/dispute
 * templates.
 */

namespace IPS\gddealer\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$dealerReviewsContent = <<<'TEMPLATE_EOT'
<div style="margin-bottom:24px">

	<div style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap">
		<div style="flex:1 1 160px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:800;color:{$data['rating_color']};line-height:1">{$data['avg_overall']}</div>
			<div style="color:#666;font-size:0.85em;margin-top:4px">Overall Rating</div>
			<div style="font-size:0.72em;font-weight:600;color:{$data['rating_color']};margin-top:4px">{$data['rating_label']}</div>
			<div style="color:#999;font-size:0.8em;margin-top:4px">{$data['total']} reviews</div>
		</div>
		<div style="flex:1 1 160px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:800;color:{$data['color_pricing']}">{$data['avg_pricing']}</div>
			<div style="color:#666;font-size:0.85em">Pricing Accuracy</div>
		</div>
		<div style="flex:1 1 160px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:800;color:{$data['color_shipping']}">{$data['avg_shipping']}</div>
			<div style="color:#666;font-size:0.85em">Shipping Speed</div>
		</div>
		<div style="flex:1 1 160px;background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;text-align:center">
			<div style="font-size:2em;font-weight:800;color:{$data['color_service']}">{$data['avg_service']}</div>
			<div style="color:#666;font-size:0.85em">Customer Service</div>
		</div>
	</div>

	{{if $data['disputes_suspended']}}
	<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
		<div>
			<strong style="color:#991b1b">Disputes Suspended</strong>
			<span style="color:#7f1d1d;margin-left:6px">&mdash; Your ability to contest reviews has been suspended by a site administrator. Contact <a href="mailto:{$data['help_email']}" style="color:#2563eb">{$data['help_email']}</a> for more information.</span>
		</div>
	</div>
	{{endif}}

	<div style="background:#f8fafc;border:1px solid var(--i-border-color,#e0e0e0);border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:0.85em;color:#334155">
		{{if $data['disputes_unlimited']}}
			You have <strong>unlimited</strong> review contests this month (Enterprise plan).
		{{else}}
			You have <strong>{$data['disputes_remaining']}</strong> review contests remaining this month.
		{{endif}}
	</div>

	{{if count($data['rows']) === 0}}
		<div class="ipsEmptyMessage"><p>No reviews yet. Reviews appear here once customers rate your dealership.</p></div>
	{{else}}
		{{foreach $data['rows'] as $r}}
		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:20px;margin-bottom:12px">
			<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;flex-wrap:wrap;gap:8px">
				<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center">
					<span style="font-size:0.8em;font-weight:700;color:{$r['avg_color']};background:{$r['avg_color']}18;padding:2px 8px;border-radius:12px">{$r['avg_overall']} / 5</span>
					<span style="font-size:0.8em;color:#666">Pricing: <strong>{$r['rating_pricing']}/5</strong></span>
					<span style="font-size:0.8em;color:#666">Shipping: <strong>{$r['rating_shipping']}/5</strong></span>
					<span style="font-size:0.8em;color:#666">Service: <strong>{$r['rating_service']}/5</strong></span>
				</div>
				<span style="font-size:0.8em;color:#999">{$r['created_at']}</span>
			</div>

			{{if $r['review_body']}}
			<p style="margin:0 0 12px;color:#333">{$r['review_body']}</p>
			{{endif}}

			{{if $r['dealer_response']}}
				<div data-resp="{$r['id']}" style="background:#f0f7ff;border-left:3px solid #2563eb;padding:12px 16px;border-radius:0 6px 6px 0;margin-top:8px">
					<div class="gd-resp-head" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;gap:8px;flex-wrap:wrap">
						<div style="font-size:0.8em;color:#2563eb;font-weight:700">Your response &mdash; {$r['response_at']}</div>
						<div class="gd-resp-actions" style="display:flex;gap:8px;align-items:center">
							<a href="#" onclick="var w=this.closest('div[data-resp]');w.querySelector('.gd-resp-text').style.display='none';w.querySelector('.gd-resp-actions').style.display='none';w.querySelector('.gd-resp-edit').style.display='block';return false" style="font-size:0.75em;color:#2563eb;font-weight:600;text-decoration:none">Edit</a>
							<form method="post" action="{$r['delete_response_url']}" style="display:inline;margin:0" onsubmit="return confirm('Delete your response?')">
								<input type="hidden" name="csrfKey" value="{$csrfKey}">
								<button type="submit" style="background:none;border:none;padding:0;font-size:0.75em;color:#dc2626;font-weight:600;cursor:pointer">Delete</button>
							</form>
						</div>
					</div>
					<div class="gd-resp-text" style="margin:0;font-size:0.9em">{$r['dealer_response']|raw}</div>
					<form class="gd-resp-edit" method="post" action="{$r['respond_url']}" style="display:none;margin-top:8px">
						<input type="hidden" name="csrfKey" value="{$csrfKey}">
						{$r['edit_editor_html']|raw}
						<div style="display:flex;gap:6px;margin-top:8px">
							<button type="submit" class="ipsButton ipsButton--primary ipsButton--small">Save changes</button>
							<button type="button" onclick="var w=this.closest('div[data-resp]');w.querySelector('.gd-resp-text').style.display='block';w.querySelector('.gd-resp-actions').style.display='flex';w.querySelector('.gd-resp-edit').style.display='none';return false" class="ipsButton ipsButton--normal ipsButton--small">Cancel</button>
						</div>
					</form>
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'pending_customer'}}
				<div style="background:#fff8f0;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#92400e">
					<strong>Contest submitted &mdash; awaiting customer response.</strong>
					{{if $r['dispute_deadline']}}<div style="font-size:0.8em;margin-top:2px">Customer has until {$r['dispute_deadline']} to respond.</div>{{endif}}
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'pending_admin'}}
				<div style="background:#eff6ff;border-left:3px solid #2563eb;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#1e3a8a">
					<strong>Contest under admin review.</strong> The customer has responded and admin will resolve shortly.
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'resolved_dealer'}}
				<div style="background:#f0fdf4;border-left:3px solid #16a34a;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#14532d">
					<strong>Contest resolved in your favor.</strong> This review no longer affects your rating average.
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'resolved_customer'}}
				<div style="background:#fef2f2;border-left:3px solid #dc2626;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#7f1d1d">
					<strong>Contest resolved in the customer's favor.</strong> The review stands.
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'dismissed'}}
				<div style="background:#f1f5f9;border-left:3px solid #64748b;padding:10px 14px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.85em;color:#334155">
					<strong>Contest dismissed.</strong> This review cannot be contested again.
				</div>
			{{endif}}

			{{if $r['dispute_status'] === 'none'}}
				{{if $r['dealer_response'] === ''}}
				<details style="margin-top:8px">
					<summary style="cursor:pointer;font-size:0.85em;color:#2563eb;font-weight:600">Respond to this review</summary>
					<form method="post" action="{$r['respond_url']}" style="margin-top:8px">
						<input type="hidden" name="csrfKey" value="{$csrfKey}">
						{$r['respond_editor_html']|raw}
						<button type="submit" class="ipsButton ipsButton--primary ipsButton--small" style="margin-top:8px">Post Response</button>
					</form>
				</details>
				{{endif}}

				{{if $data['disputes_unlimited']}}
				<details style="margin-top:8px">
					<summary style="cursor:pointer;font-size:0.85em;color:#dc2626;font-weight:600">Contest this review</summary>
					<form method="post" action="{$r['dispute_url']}" style="margin-top:8px">
						<input type="hidden" name="csrfKey" value="{$csrfKey}">
						<p style="margin:0 0 8px;font-size:0.8em;color:#666">Read the <a href="{$data['guidelines_url']}" style="color:#2563eb" target="_blank">Dispute Guidelines</a> before contesting a review.</p>
						<label style="display:block;font-size:0.8em;font-weight:600;margin-bottom:4px">Reason for contest</label>
						<textarea name="dispute_reason" rows="3" required style="width:100%;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Explain why this review should be removed (e.g. never purchased from us, fraudulent, violates terms)..."></textarea>
						<label style="display:block;font-size:0.8em;font-weight:600;margin-bottom:4px">Supporting evidence (order numbers, screenshots, transaction IDs)</label>
						<textarea name="dispute_evidence" rows="3" style="width:100%;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box" placeholder="Paste order numbers, links, or evidence that supports your contest..."></textarea>
						<button type="submit" class="ipsButton ipsButton--negative ipsButton--small" style="margin-top:8px">Submit Contest</button>
					</form>
				</details>
				{{else}}
					{{if $data['disputes_remaining'] > 0}}
					<details style="margin-top:8px">
						<summary style="cursor:pointer;font-size:0.85em;color:#dc2626;font-weight:600">Contest this review</summary>
						<form method="post" action="{$r['dispute_url']}" style="margin-top:8px">
							<input type="hidden" name="csrfKey" value="{$csrfKey}">
							<p style="margin:0 0 8px;font-size:0.8em;color:#666">Read the <a href="{$data['guidelines_url']}" style="color:#2563eb" target="_blank">Dispute Guidelines</a> before contesting a review.</p>
							<label style="display:block;font-size:0.8em;font-weight:600;margin-bottom:4px">Reason for contest</label>
							<textarea name="dispute_reason" rows="3" required style="width:100%;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Explain why this review should be removed (e.g. never purchased from us, fraudulent, violates terms)..."></textarea>
							<label style="display:block;font-size:0.8em;font-weight:600;margin-bottom:4px">Supporting evidence (order numbers, screenshots, transaction IDs)</label>
							<textarea name="dispute_evidence" rows="3" style="width:100%;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box" placeholder="Paste order numbers, links, or evidence that supports your contest..."></textarea>
							<button type="submit" class="ipsButton ipsButton--negative ipsButton--small" style="margin-top:8px">Submit Contest</button>
						</form>
					</details>
					{{else}}
					<div style="background:#f1f5f9;border-left:3px solid #64748b;padding:8px 12px;border-radius:0 4px 4px 0;margin-top:8px;font-size:0.8em;color:#475569">
						You have reached your monthly contest limit. Upgrade your plan for more contests.
					</div>
					{{endif}}
				{{endif}}
			{{endif}}
		</div>
		{{endforeach}}
	{{endif}}

</div>
TEMPLATE_EOT;

$templates = [
	[
		'template_name' => 'dealerReviews',
		'template_data' => '$data, $csrfKey',
		'template_content' => $dealerReviewsContent,
	],
];

foreach ( $templates as $t )
{
	$existingId = NULL;
	try
	{
		$existingId = (int) \IPS\Db::i()->select( 'template_id', 'core_theme_templates', [
			'template_app=? AND template_location=? AND template_group=? AND template_name=? AND template_set_id=?',
			'gddealer', 'front', 'dealers', $t['template_name'], 1,
		] )->first();
	}
	catch ( \Exception )
	{
		$existingId = NULL;
	}

	$row = [
		'template_set_id'   => 1,
		'template_app'      => 'gddealer',
		'template_location' => 'front',
		'template_group'    => 'dealers',
		'template_name'     => $t['template_name'],
		'template_data'     => $t['template_data'],
		'template_content'  => $t['template_content'],
	];

	if ( $existingId )
	{
		$row['template_id'] = $existingId;
	}

	try
	{
		\IPS\Db::i()->replace( 'core_theme_templates', $row );
	}
	catch ( \Exception )
	{
		try
		{
			$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_theme_templates', [
				'template_app=? AND template_location=? AND template_group=? AND template_name=?',
				'gddealer', 'front', 'dealers', $t['template_name'],
			] )->first();

			if ( $exists > 0 )
			{
				\IPS\Db::i()->update( 'core_theme_templates', [
					'template_data'    => $t['template_data'],
					'template_content' => $t['template_content'],
				], [
					'template_app=? AND template_location=? AND template_group=? AND template_name=?',
					'gddealer', 'front', 'dealers', $t['template_name'],
				] );
			}
			else
			{
				\IPS\Db::i()->insert( 'core_theme_templates', [
					'template_set_id'   => 1,
					'template_app'      => 'gddealer',
					'template_location' => 'front',
					'template_group'    => 'dealers',
					'template_name'     => $t['template_name'],
					'template_data'     => $t['template_data'],
					'template_content'  => $t['template_content'],
				] );
			}
		}
		catch ( \Exception ) {}
	}
}

try { unset( \IPS\Data\Store::i()->themes ); }   catch ( \Exception ) {}
try { \IPS\Data\Cache::i()->clearAll(); }        catch ( \Exception ) {}
