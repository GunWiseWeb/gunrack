<?php
/**
 * @brief       GD Dealer Manager — Template sync for v1.0.27 (10027)
 *
 * Replaces the Customer Reviews list block inside front/dealers/dealerProfile
 * with the redesigned review card:
 *   - Avatar: clean 40x40 circle, CSS gradient + initial fallback when the
 *     reviewer has no uploaded photo (instead of IPS's clipped default image)
 *   - Category ratings rendered as compact inline rows, not oversized boxes
 *   - Rating pill tint derived from the per-review avg_color
 *   - Response panel shows "Dealer response · {dealer_name}"
 *
 * Targeted str_replace so re-running is a no-op. If the v1.0.26 needle is
 * missing (template hand-edited, etc.), the row is left untouched.
 */

namespace IPS\gddealer\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$oldReviewList = <<<'NEEDLE_EOT'
			<div class="gdReviewList ipsBox" style="--gd-card-bg:#ffffff;--gd-card-border:#e5e7eb;--gd-row-border:#f1f5f9;--gd-text-primary:#111827;--gd-text-secondary:#374151;--gd-muted:#6b7280;--gd-subtle:#9ca3af;--gd-star:#f59e0b;--gd-response-bg:#f8fafc;--gd-response-accent:var(--gd-primary,#2563eb);--gd-warn-bg:#fef3c7;--gd-warn-border:#fde68a;--gd-warn-text:#92400e;--gd-alert-bg:#fef2f2;--gd-alert-border:#fecaca;--gd-alert-text:#991b1b;background:var(--gd-card-bg);border:1px solid var(--gd-card-border);border-radius:10px">
				<h3 class="ipsBox__header" style="margin:0;padding:16px 20px;border-bottom:1px solid var(--gd-row-border);font-size:1em;font-weight:700;color:var(--gd-text-primary)">Customer Reviews <span style="font-size:0.8em;font-weight:500;color:var(--gd-muted);margin-left:4px">({expression="number_format($stats['total'])"})</span></h3>
				{{if count($reviews) === 0}}
				<div class="i-padding_2" style="padding:40px 24px;text-align:center;color:var(--gd-subtle)">
					<i class="fa-regular fa-star" style="font-size:2em;display:block;margin-bottom:10px;opacity:0.4" aria-hidden="true"></i>
					No reviews yet. Be the first to review this dealer.
				</div>
				{{else}}
					{{foreach $reviews as $r}}
					<div class="gdReviewItem" style="padding:22px 20px;border-bottom:1px solid var(--gd-row-border)">

						<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px">
							<div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1 1 auto">
								<span class="ipsUserPhoto ipsUserPhoto--small" style="flex:0 0 auto">
									<img src="{$r['reviewer_avatar']}" alt="" loading="lazy" style="width:40px;height:40px;border-radius:50%">
								</span>
								<div style="min-width:0">
									<div style="font-weight:700;font-size:0.95em;color:var(--gd-text-primary);line-height:1.2">{$r['reviewer_name']}</div>
									<div style="font-size:0.78em;color:var(--gd-subtle);margin-top:2px">{$r['created_at_formatted']}</div>
								</div>
							</div>
							<div style="flex:0 0 auto;background:{$r['avg_color']}14;border:1px solid {$r['avg_color']}33;border-radius:999px;padding:6px 14px;display:inline-flex;align-items:baseline;gap:4px;line-height:1">
								<span style="color:{$r['avg_color']};font-weight:800;font-size:1.15em">{$r['avg_score']}</span>
								<span style="color:var(--gd-subtle);font-size:0.78em;font-weight:600">/ 5</span>
							</div>
						</div>

						<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:14px">
							<div style="background:#fafafa;border:1px solid var(--gd-row-border);border-radius:8px;padding:8px 12px">
								<div style="font-size:0.72em;color:var(--gd-muted);font-weight:600;margin-bottom:3px">Pricing</div>
								<div style="color:var(--gd-star);font-size:0.95em;letter-spacing:1.5px">{$r['stars_pricing']}</div>
							</div>
							<div style="background:#fafafa;border:1px solid var(--gd-row-border);border-radius:8px;padding:8px 12px">
								<div style="font-size:0.72em;color:var(--gd-muted);font-weight:600;margin-bottom:3px">Shipping</div>
								<div style="color:var(--gd-star);font-size:0.95em;letter-spacing:1.5px">{$r['stars_shipping']}</div>
							</div>
							<div style="background:#fafafa;border:1px solid var(--gd-row-border);border-radius:8px;padding:8px 12px">
								<div style="font-size:0.72em;color:var(--gd-muted);font-weight:600;margin-bottom:3px">Service</div>
								<div style="color:var(--gd-star);font-size:0.95em;letter-spacing:1.5px">{$r['stars_service']}</div>
							</div>
						</div>

						{{if $r['review_body']}}
						<p style="margin:0 0 14px;line-height:1.65;font-size:0.98em;color:var(--gd-text-secondary)">{$r['review_body']}</p>
						{{endif}}

						{{if $r['is_own_review'] and $r['dispute_status'] === 'pending_customer'}}
						<div style="background:var(--gd-alert-bg);border:1px solid var(--gd-alert-border);border-radius:8px;padding:12px 16px;font-size:0.88em;color:var(--gd-alert-text);margin-bottom:10px;display:flex;gap:10px;align-items:flex-start">
							<i class="fa-solid fa-circle-exclamation" style="margin-top:2px" aria-hidden="true"></i>
							<div style="flex:1 1 auto">
								<strong>Action Required:</strong> The dealer has contested this review. You must respond or the dispute will be resolved in their favor.
								{{if $r['dispute_respond_url']}}<a href="{$r['dispute_respond_url']}" style="display:inline-block;margin-top:4px;color:var(--gd-alert-text);font-weight:700;text-decoration:underline">Respond Now &rarr;</a>{{endif}}
							</div>
						</div>
						{{elseif $r['dispute_status'] === 'pending_customer' or $r['dispute_status'] === 'pending_admin'}}
						<div style="background:var(--gd-warn-bg);border:1px solid var(--gd-warn-border);border-radius:8px;padding:10px 14px;font-size:0.85em;color:var(--gd-warn-text);margin-bottom:10px;display:flex;gap:10px;align-items:center">
							<i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
							<span>This review is currently under dispute review.</span>
						</div>
						{{endif}}

						{{if $r['dealer_response']}}
						<div style="margin:12px 0 0 20px;position:relative">
							<div style="position:absolute;left:-14px;top:0;bottom:0;width:2px;background:var(--gd-response-accent);opacity:0.35;border-radius:2px"></div>
							<div style="background:var(--gd-response-bg);border:1px solid var(--gd-row-border);border-radius:8px;padding:12px 14px">
								<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;flex-wrap:wrap">
									<div style="font-size:0.78em;color:var(--gd-response-accent);font-weight:700;text-transform:uppercase;letter-spacing:0.05em">
										<i class="fa-solid fa-reply" aria-hidden="true"></i> Dealer Response
									</div>
									<span style="color:var(--gd-subtle);font-size:0.75em">{$r['response_at']}</span>
								</div>
								<p style="margin:0;font-size:0.92em;line-height:1.55;color:var(--gd-text-secondary)">{$r['dealer_response']}</p>
							</div>
						</div>
						{{endif}}

						{{if $r['edit_review_url']}}
						<div style="margin-top:12px;text-align:right">
							<a href="{$r['edit_review_url']}" style="display:inline-flex;align-items:center;gap:4px;font-size:0.82em;color:var(--gd-muted);font-weight:600;text-decoration:none">
								<i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit your review
							</a>
						</div>
						{{endif}}

					</div>
					{{endforeach}}
				{{endif}}
			</div>
NEEDLE_EOT;

$newReviewList = <<<'NEW_EOT'
			<div class="gdReviewList" style="padding:16px 4px 0">
				<h3 style="margin:0 0 14px;padding:0 4px;font-size:1em;font-weight:700;color:#111827">Customer Reviews <span style="font-size:0.8em;font-weight:500;color:#6b7280;margin-left:4px">({expression="number_format($stats['total'])"})</span></h3>
				{{if count($reviews) === 0}}
				<div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;padding:40px 24px;text-align:center;color:#9ca3af">
					<i class="fa-regular fa-star" style="font-size:2em;display:block;margin-bottom:10px;opacity:0.4" aria-hidden="true"></i>
					No reviews yet. Be the first to review this dealer.
				</div>
				{{else}}
					{{foreach $reviews as $r}}
					<div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;padding:20px 22px;margin:0 0 16px;color:#1f2937">

						<div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
							<div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#c9a24a,#b8862d);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:500;font-size:15px;flex-shrink:0;overflow:hidden">
								{{if $r['reviewer_avatar']}}
									<img src="{$r['reviewer_avatar']}" alt="" style="width:100%;height:100%;object-fit:cover" loading="lazy">
								{{else}}
									{expression="strtoupper(substr($r['reviewer_name'], 0, 1))"}
								{{endif}}
							</div>
							<div style="display:flex;flex-direction:column;min-width:0;flex:1">
								<span style="font-weight:500;font-size:14px;color:#111827;line-height:1.3">{$r['reviewer_name']}</span>
								<span style="font-size:12px;color:#6b7280;margin-top:2px">{$r['created_at_formatted']}</span>
							</div>
							<span style="display:inline-flex;align-items:center;gap:6px;background:{$r['avg_color']}18;border:0.5px solid {$r['avg_color']}60;border-radius:20px;padding:4px 12px;font-size:13px;color:{$r['avg_color']};font-weight:500;white-space:nowrap">
								<span style="font-size:14px">{$r['avg_score']}</span>
								<span style="opacity:0.6;font-weight:400">/ 5</span>
							</span>
						</div>

						<div style="display:flex;gap:20px;margin-bottom:14px;flex-wrap:wrap">
							<div style="display:flex;align-items:center;gap:8px">
								<span style="font-size:12px;color:#6b7280;font-weight:500">Pricing</span>
								<span style="letter-spacing:1px;font-size:13px;line-height:1;color:#f59e0b">{$r['stars_pricing']}</span>
							</div>
							<div style="display:flex;align-items:center;gap:8px">
								<span style="font-size:12px;color:#6b7280;font-weight:500">Shipping</span>
								<span style="letter-spacing:1px;font-size:13px;line-height:1;color:#f59e0b">{$r['stars_shipping']}</span>
							</div>
							<div style="display:flex;align-items:center;gap:8px">
								<span style="font-size:12px;color:#6b7280;font-weight:500">Service</span>
								<span style="letter-spacing:1px;font-size:13px;line-height:1;color:#f59e0b">{$r['stars_service']}</span>
							</div>
						</div>

						{{if $r['review_body']}}
						<p style="font-size:14px;line-height:1.6;color:#374151;margin:4px 0 0">{$r['review_body']}</p>
						{{endif}}

						{{if $r['is_own_review'] and $r['dispute_status'] === 'pending_customer'}}
						<div style="background:#fef2f2;border:0.5px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;color:#991b1b;margin-top:12px;display:flex;align-items:center;gap:8px">
							<span style="flex-shrink:0;color:#dc2626;font-size:14px;line-height:1">⚠</span>
							<span><strong>Action required:</strong> The dealer has contested this review. You must respond or the dispute will be resolved in their favor.
							{{if $r['dispute_respond_url']}}<a href="{$r['dispute_respond_url']}" style="color:#dc2626;font-weight:500;margin-left:6px">Respond now →</a>{{endif}}</span>
						</div>
						{{elseif $r['dispute_status'] === 'pending_customer' or $r['dispute_status'] === 'pending_admin'}}
						<div style="background:#fffbeb;border:0.5px solid #fcd34d;border-radius:8px;padding:10px 14px;font-size:12px;color:#78350f;margin-top:12px;display:flex;align-items:center;gap:8px">
							<span style="flex-shrink:0;color:#d97706;font-size:14px;line-height:1">⚠</span>
							<span>This review is currently under dispute review.</span>
						</div>
						{{endif}}

						{{if $r['dealer_response']}}
						<div style="margin-top:16px;background:#f9fafb;border-radius:10px;padding:14px 16px;border-left:3px solid #2563eb">
							<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;flex-wrap:wrap;gap:8px">
								<span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#2563eb;font-weight:500;text-transform:uppercase;letter-spacing:0.06em">
									<i class="fa-solid fa-reply" aria-hidden="true"></i> Dealer response{{if $r['dealer_name']}} · {$r['dealer_name']}{{endif}}
								</span>
								<span style="font-size:11px;color:#9ca3af">{$r['response_at']}</span>
							</div>
							<p style="font-size:13px;line-height:1.5;color:#374151;margin:0">{$r['dealer_response']}</p>
						</div>
						{{endif}}

						{{if $r['edit_review_url']}}
						<div style="display:flex;justify-content:flex-end;margin-top:12px">
							<a href="{$r['edit_review_url']}" style="font-size:12px;color:#2563eb;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;gap:4px">
								<i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit your review
							</a>
						</div>
						{{endif}}

					</div>
					{{endforeach}}
				{{endif}}
			</div>
NEW_EOT;

try
{
	$existing = \IPS\Db::i()->select( 'template_content', 'core_theme_templates', [
		'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		'gddealer', 'front', 'dealers', 'dealerProfile',
	] )->first();

	$updated = str_replace( $oldReviewList, $newReviewList, $existing );

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

try { unset( \IPS\Data\Store::i()->themes ); }   catch ( \Exception ) {}
try { \IPS\Data\Cache::i()->clearAll(); }        catch ( \Exception ) {}
