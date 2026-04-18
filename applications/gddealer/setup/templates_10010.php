<?php
/**
 * @brief       GD Dealer Manager — Template sync for v1.0.10 (10010)
 *
 * Updates:
 * - disputeQueue: adds days_remaining display
 * - dealerProfile: adds "Action Required" banner on own reviews in pending_customer status
 */

namespace IPS\gddealer\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/* ── disputeQueue: replace deadline badge with days-remaining countdown ── */
try
{
	$existing = \IPS\Db::i()->select( 'template_content', 'core_theme_templates', [
		'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		'gddealer', 'admin', 'dealers', 'disputeQueue',
	] )->first();

	$updated = str_replace(
		'<span class="ipsBadge ipsBadge--warning">Awaiting customer (deadline {$r[\'dispute_deadline\']})</span>',
		'<span class="ipsBadge ipsBadge--warning">Awaiting customer &mdash; {$r[\'days_remaining\']} days left (deadline {$r[\'dispute_deadline\']})</span>',
		$existing
	);

	$updated = str_replace(
		'<span class="ipsBadge ipsBadge--style1">Awaiting admin decision</span>',
		'<span class="ipsBadge ipsBadge--style1">Awaiting admin decision (disputed {$r[\'dispute_at\']})</span>',
		$updated
	);

	if ( $updated !== $existing )
	{
		\IPS\Db::i()->update( 'core_theme_templates', [
			'template_content' => $updated,
			'template_data'    => '$rows',
		], [
			'template_app=? AND template_location=? AND template_group=? AND template_name=?',
			'gddealer', 'admin', 'dealers', 'disputeQueue',
		] );
	}
}
catch ( \Exception ) {}

/* ── dealerProfile: add "Action Required" banner on own reviews in pending_customer ── */
try
{
	$existing = \IPS\Db::i()->select( 'template_content', 'core_theme_templates', [
		'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		'gddealer', 'front', 'dealers', 'dealerProfile',
	] )->first();

	$oldDisputeBadge = '{{if $r[\'dispute_status\'] === \'pending_customer\' or $r[\'dispute_status\'] === \'pending_admin\'}}
						<div style="background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:8px 12px;font-size:0.82em;color:#854d0e;margin-bottom:8px">
							⚠ This review is currently under admin review.
						</div>
						{{endif}}';

	$newDisputeBadge = '{{if $r[\'is_own_review\'] and $r[\'dispute_status\'] === \'pending_customer\'}}
						<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px 16px;font-size:0.85em;color:#991b1b;margin-bottom:8px">
							<strong>Action Required:</strong> The dealer has contested this review. You must respond or the dispute will be resolved in their favor.
							{{if $r[\'dispute_respond_url\']}}<a href="{$r[\'dispute_respond_url\']}" style="color:#dc2626;font-weight:700;margin-left:6px">Respond Now &rarr;</a>{{endif}}
						</div>
						{{elseif $r[\'dispute_status\'] === \'pending_customer\' or $r[\'dispute_status\'] === \'pending_admin\'}}
						<div style="background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:8px 12px;font-size:0.82em;color:#854d0e;margin-bottom:8px">
							This review is currently under dispute review.
						</div>
						{{endif}}';

	$updated = str_replace( $oldDisputeBadge, $newDisputeBadge, $existing );

	if ( $updated !== $existing )
	{
		\IPS\Db::i()->update( 'core_theme_templates', [
			'template_content' => $updated,
		], [
			'template_app=? AND template_location=? AND template_group=? AND template_name=?',
			'gddealer', 'front', 'dealers', 'dealerProfile',
		] );
	}
}
catch ( \Exception ) {}
