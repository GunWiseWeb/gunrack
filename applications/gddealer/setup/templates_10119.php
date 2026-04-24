<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

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
.gddealerFflTable__actions { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }
.gddealerFflTable__btn { padding: 6px 12px; font-size: 13px; font-weight: 500; border-radius: 5px; text-decoration: none; border: 1px solid; cursor: pointer; }
.gddealerFflTable__btn--view { background: #f8fafc; border-color: #e5e7eb; color: #334155; }
.gddealerFflTable__btn--verify { background: #10b981; border-color: #059669; color: #fff; }
.gddealerFflTable__btn--reject { background: #fff; border-color: #ef4444; color: #ef4444; }
.gddealerFflTable__btn--reset { background: #fff; border-color: #f59e0b; color: #b45309; }
.gddealerFflTable__status { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
.gddealerFflTable__status--pending { background: #fef3c7; color: #92400e; }
.gddealerFflTable__status--verified { background: #d1fae5; color: #065f46; }
.gddealerFflTable__status--rejected { background: #fee2e2; color: #991b1b; }
.gddealerFflTable__empty { padding: 40px; text-align: center; color: #94a3b8; font-size: 14px; }
.gddealerFflTable__rejectionReason { font-size: 12px; color: #991b1b; font-style: italic; margin-top: 4px; }
</style>

<div class="gddealerFflQueue">
	<div class="gddealerFflQueue__tabs">
		<a href="{$data['filter_urls']['pending']}"  class="gddealerFflQueue__tab {expression="$data['filter'] === 'pending'  ? 'is-active' : ''"}">Pending<span class="count">{$data['counts']['pending']}</span></a>
		<a href="{$data['filter_urls']['verified']}" class="gddealerFflQueue__tab {expression="$data['filter'] === 'verified' ? 'is-active' : ''"}">Verified<span class="count">{$data['counts']['verified']}</span></a>
		<a href="{$data['filter_urls']['rejected']}" class="gddealerFflQueue__tab {expression="$data['filter'] === 'rejected' ? 'is-active' : ''"}">Rejected<span class="count">{$data['counts']['rejected']}</span></a>
		<a href="{$data['filter_urls']['all']}"      class="gddealerFflQueue__tab {expression="$data['filter'] === 'all'      ? 'is-active' : ''"}">All<span class="count">{$data['counts']['all']}</span></a>
	</div>

	{{if empty( $data['rows'] )}}
	<div class="gddealerFflTable__empty">No FFL submissions in this view.</div>
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
						{{if $r['ffl_rejection_count'] > 0}}
						<a href="{$r['reset_url']}" class="gddealerFflTable__btn gddealerFflTable__btn--reset" onclick="return confirm('Reset this dealer\'s rejection counter to 0?');" title="Forgive past rejections so the dealer can re-submit">Reset attempts ({$r['ffl_rejection_count']})</a>
						{{endif}}
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

try
{
	\IPS\Db::i()->update( 'core_theme_templates',
		[
			'template_data'    => '$data',
			'template_content' => $fflQueueBody,
			'template_updated' => time(),
		],
		[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		  'gddealer', 'admin', 'dealers', 'fflVerifications' ]
	);
}
catch ( \Throwable ) {}
