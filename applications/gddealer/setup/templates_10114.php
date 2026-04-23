<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/* ─── fflVerifications queue template ─── */
$fflQueueBody = <<<'TEMPLATE_EOT'
<style>
.gddealerFflQueue { padding: 0 4px; }
.gddealerFflQueue__tabs { display: flex; gap: 4px; border-bottom: 1px solid #e5e7eb; margin-bottom: 16px; }
.gddealerFflQueue__tab { padding: 10px 14px; font-size: 14px; font-weight: 500; color: #475569; text-decoration: none; border-bottom: 2px solid transparent; }
.gddealerFflQueue__tab.is-active { color: #1e40af; border-bottom-color: #1e40af; }
.gddealerFflQueue__tab .count { display: inline-block; background: #e5e7eb; color: #475569; font-size: 11px; padding: 1px 7px; border-radius: 999px; margin-left: 6px; font-weight: 600; }
.gddealerFflQueue__tab.is-active .count { background: #dbeafe; color: #1e40af; }
.gddealerFflTable { width: 100%; border-collapse: collapse; }
.gddealerFflTable th { text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; padding: 10px 12px; border-bottom: 2px solid #e5e7eb; }
.gddealerFflTable td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 14px; }
.gddealerFflTable__ffl { font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size: 13px; color: #0f172a; }
.gddealerFflTable__actions { display: flex; gap: 6px; justify-content: flex-end; }
.gddealerFflTable__btn { padding: 6px 12px; font-size: 13px; font-weight: 500; border-radius: 5px; text-decoration: none; border: 1px solid; cursor: pointer; }
.gddealerFflTable__btn--view { background: #f8fafc; border-color: #e5e7eb; color: #334155; }
.gddealerFflTable__btn--verify { background: #10b981; border-color: #059669; color: #fff; }
.gddealerFflTable__btn--reject { background: #fff; border-color: #ef4444; color: #ef4444; }
.gddealerFflTable__status { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
.gddealerFflTable__status--pending { background: #fef3c7; color: #92400e; }
.gddealerFflTable__status--verified { background: #d1fae5; color: #065f46; }
.gddealerFflTable__status--rejected { background: #fee2e2; color: #991b1b; }
.gddealerFflTable__empty { padding: 40px; text-align: center; color: #94a3b8; font-size: 14px; }
.gddealerFflTable__rejectionReason { font-size: 12px; color: #991b1b; font-style: italic; margin-top: 4px; }
</style>

<div class="gddealerFflQueue">
	<div class="gddealerFflQueue__tabs">
		<a href="{$data['filter_urls']['pending']}"  class="gddealerFflQueue__tab {expression="$data['filter'] === 'pending'  ? 'is-active' : ''"}">{lang="gddealer_acp_ffl_filter_pending"}<span class="count">{$data['counts']['pending']}</span></a>
		<a href="{$data['filter_urls']['verified']}" class="gddealerFflQueue__tab {expression="$data['filter'] === 'verified' ? 'is-active' : ''"}">{lang="gddealer_acp_ffl_filter_verified"}<span class="count">{$data['counts']['verified']}</span></a>
		<a href="{$data['filter_urls']['rejected']}" class="gddealerFflQueue__tab {expression="$data['filter'] === 'rejected' ? 'is-active' : ''"}">{lang="gddealer_acp_ffl_filter_rejected"}<span class="count">{$data['counts']['rejected']}</span></a>
		<a href="{$data['filter_urls']['all']}"      class="gddealerFflQueue__tab {expression="$data['filter'] === 'all'      ? 'is-active' : ''"}">{lang="gddealer_acp_ffl_filter_all"}<span class="count">{$data['counts']['all']}</span></a>
	</div>

	{{if empty( $data['rows'] )}}
	<div class="gddealerFflTable__empty">{lang="gddealer_acp_ffl_empty_pending"}</div>
	{{else}}
	<table class="gddealerFflTable">
		<thead>
			<tr>
				<th>Dealer</th>
				<th>FFL #</th>
				<th>Submitted</th>
				<th>License</th>
				<th>Status</th>
				<th style="text-align:right;">Actions</th>
			</tr>
		</thead>
		<tbody>
			{{foreach $data['rows'] as $r}}
			<tr>
				<td>
					<div style="font-weight:600;color:#0f172a;">{$r['dealer_name']}</div>
					<div style="font-size:12px;color:#64748b;">ID {$r['dealer_id']} · {$r['dealer_slug']}</div>
					{{if $r['status'] === 'rejected' && $r['ffl_rejection_reason']}}
					<div class="gddealerFflTable__rejectionReason">Last rejection: {$r['ffl_rejection_reason']} (attempt {$r['ffl_rejection_count']} of 3)</div>
					{{endif}}
				</td>
				<td class="gddealerFflTable__ffl">{$r['ffl_number']}</td>
				<td>{$r['ffl_submitted_label']}</td>
				<td>
					{{if $r['ffl_license_url']}}
					<a href="{$r['ffl_license_url']}" target="_blank" rel="nofollow noopener" class="gddealerFflTable__btn gddealerFflTable__btn--view">View PDF</a>
					{{else}}
					<span style="color:#94a3b8;font-size:12px;">No URL</span>
					{{endif}}
				</td>
				<td>
					{{if $r['status'] === 'pending'}}
					<span class="gddealerFflTable__status gddealerFflTable__status--pending">Pending</span>
					{{elseif $r['status'] === 'verified'}}
					<span class="gddealerFflTable__status gddealerFflTable__status--verified">Verified {$r['ffl_verified_label']}</span>
					{{elseif $r['status'] === 'rejected'}}
					<span class="gddealerFflTable__status gddealerFflTable__status--rejected">Rejected</span>
					{{endif}}
				</td>
				<td>
					<div class="gddealerFflTable__actions">
						{{if $r['status'] !== 'verified'}}
						<a href="{$r['verify_url']}" class="gddealerFflTable__btn gddealerFflTable__btn--verify" onclick="return confirm('Mark this FFL as verified?');">Verify</a>
						{{endif}}
						{{if $r['status'] !== 'rejected' || $r['ffl_rejection_count'] < 3}}
						<a href="{$r['reject_url']}" class="gddealerFflTable__btn gddealerFflTable__btn--reject">Reject</a>
						{{endif}}
					</div>
				</td>
			</tr>
			{{endforeach}}
		</tbody>
	</table>
	{{endif}}
</div>
TEMPLATE_EOT;

/* ─── fflRejectForm template ─── */
$fflRejectFormBody = <<<'TEMPLATE_EOT'
<style>
.gddealerFflReject { max-width: 560px; margin: 0 auto; padding: 24px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
.gddealerFflReject__title { margin: 0 0 4px; font-size: 18px; font-weight: 600; color: #0f172a; }
.gddealerFflReject__sub { margin: 0 0 20px; font-size: 13px; color: #64748b; }
.gddealerFflReject__reasonOption { display: block; padding: 12px 14px; margin-bottom: 8px; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; font-size: 14px; color: #334155; }
.gddealerFflReject__reasonOption:hover { background: #f8fafc; }
.gddealerFflReject__reasonOption input { margin-right: 10px; }
.gddealerFflReject__otherBox { margin-top: 8px; display: none; }
.gddealerFflReject__otherBox.is-visible { display: block; }
.gddealerFflReject__otherTextarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font: inherit; resize: vertical; min-height: 80px; box-sizing: border-box; }
.gddealerFflReject__actions { margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end; }
.gddealerFflReject__btn { padding: 8px 16px; font-size: 14px; font-weight: 500; border-radius: 6px; cursor: pointer; text-decoration: none; }
.gddealerFflReject__btn--cancel { background: #fff; border: 1px solid #e5e7eb; color: #475569; }
.gddealerFflReject__btn--submit { background: #ef4444; border: 1px solid #dc2626; color: #fff; }
</style>

<div class="gddealerFflReject">
	<h2 class="gddealerFflReject__title">Reject FFL submission</h2>
	<p class="gddealerFflReject__sub">Dealer: <strong>{$data['dealer']['dealer_name']}</strong> · FFL # {$data['dealer']['ffl_number']}</p>

	<form method="post" action="{$data['post_url']}" id="gddealerFflRejectForm">
		<label class="gddealerFflReject__reasonOption"><input type="radio" name="reason_key" value="illegible" required> {lang="gddealer_ffl_rejection_illegible"}</label>
		<label class="gddealerFflReject__reasonOption"><input type="radio" name="reason_key" value="expired"> {lang="gddealer_ffl_rejection_expired"}</label>
		<label class="gddealerFflReject__reasonOption"><input type="radio" name="reason_key" value="mismatch"> {lang="gddealer_ffl_rejection_mismatch"}</label>
		<label class="gddealerFflReject__reasonOption"><input type="radio" name="reason_key" value="other" id="gddealerFflRejectOther"> Other (specify below)</label>

		<div class="gddealerFflReject__otherBox" id="gddealerFflRejectOtherBox">
			<textarea name="reason_other" class="gddealerFflReject__otherTextarea" placeholder="Enter the specific reason the dealer will receive..."></textarea>
		</div>

		<div class="gddealerFflReject__actions">
			<a href="{$data['cancel_url']}" class="gddealerFflReject__btn gddealerFflReject__btn--cancel">Cancel</a>
			<button type="submit" class="gddealerFflReject__btn gddealerFflReject__btn--submit">Reject &amp; notify dealer</button>
		</div>
	</form>
</div>

<script>
(function() {
	var otherRadio = document.getElementById( 'gddealerFflRejectOther' );
	var otherBox   = document.getElementById( 'gddealerFflRejectOtherBox' );
	var form       = document.getElementById( 'gddealerFflRejectForm' );
	if ( !form ) return;
	form.addEventListener( 'change', function() {
		if ( otherRadio && otherRadio.checked ) { otherBox.classList.add( 'is-visible' ); }
		else { otherBox.classList.remove( 'is-visible' ); }
	} );
})();
</script>
TEMPLATE_EOT;

$templates = [
	[
		'template_name'    => 'fflVerifications',
		'template_data'    => '$data',
		'template_content' => $fflQueueBody,
	],
	[
		'template_name'    => 'fflRejectForm',
		'template_data'    => '$data',
		'template_content' => $fflRejectFormBody,
	],
];

foreach ( $templates as $tpl )
{
	$masterKey = md5( 'gddealer;admin;dealers;' . $tpl['template_name'] );

	/* Check if row exists already (at either set_id). If yes, update. If no, insert. */
	try
	{
		$existing = \IPS\Db::i()->select( 'COUNT(*)', 'core_theme_templates',
			[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
			  'gddealer', 'admin', 'dealers', $tpl['template_name'] ]
		)->first();
	}
	catch ( \Throwable ) { $existing = 0; }

	if ( (int) $existing > 0 )
	{
		/* Rule 18: update keyed on natural key, no set_id. Patches every row. */
		try
		{
			\IPS\Db::i()->update( 'core_theme_templates',
				[
					'template_data'    => $tpl['template_data'],
					'template_content' => $tpl['template_content'],
					'template_updated' => time(),
				],
				[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
				  'gddealer', 'admin', 'dealers', $tpl['template_name'] ]
			);
		}
		catch ( \Throwable ) {}
	}
	else
	{
		/* First install — insert at set_id=1 (authoritative default). */
		try
		{
			\IPS\Db::i()->insert( 'core_theme_templates', [
				'template_set_id'     => 1,
				'template_app'        => 'gddealer',
				'template_location'   => 'admin',
				'template_group'      => 'dealers',
				'template_name'       => $tpl['template_name'],
				'template_data'       => $tpl['template_data'],
				'template_content'    => $tpl['template_content'],
				'template_master_key' => $masterKey,
				'template_updated'    => time(),
			] );
		}
		catch ( \Throwable ) {}
	}
}
