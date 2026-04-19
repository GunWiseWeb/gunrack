<?php
/**
 * @brief       GD Dealer Manager — Template sync for v1.0.26 (10026)
 *
 * - Replaces the front/dealers/editReview template (adds $dealer context,
 *   proper page chrome, dispute-locked warning, current-ratings preview).
 * - Patches front/dealers/dealerProfile: rewrites the Customer Reviews
 *   list (review card header, category chip grid, softer dispute banners,
 *   threaded dealer-response panel, subtle right-aligned edit link).
 *
 * Idempotent: editReview uses check-then-update-or-insert; dealerProfile
 * uses a targeted str_replace so re-running is a no-op once the new
 * content is already in place.
 */

namespace IPS\gddealer\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/* ── Full replacement of front/dealers/editReview ── */
$editReviewData    = '$dealer, $review, $editUrl, $cancelUrl, $csrfKey';
$editReviewContent = <<<'TEMPLATE_EOT'
<div class="gdEditReviewWrap" style="--gd-card-bg:#ffffff;--gd-card-border:#e5e7eb;--gd-text-primary:#111827;--gd-text-secondary:#374151;--gd-muted:#6b7280;--gd-accent:var(--gd-primary,#2563eb);--gd-star:#f59e0b;--gd-warn-bg:#fef3c7;--gd-warn-border:#fcd34d;--gd-warn-text:#92400e;background:transparent;padding:24px 16px;max-width:760px;margin:0 auto">

	<div style="margin-bottom:16px">
		<a href="{$cancelUrl}" style="font-size:0.85em;color:var(--gd-muted);text-decoration:none">
			<i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to {$dealer['dealer_name']}
		</a>
	</div>

	<div class="ipsBox" style="background:var(--gd-card-bg);border:1px solid var(--gd-card-border);border-radius:10px;overflow:hidden">

		<div style="padding:20px 22px;border-bottom:1px solid var(--gd-card-border);display:flex;align-items:center;gap:14px">
			{{if $dealer['avatar_url']}}
			<span class="ipsUserPhoto ipsUserPhoto--medium" style="flex:0 0 auto">
				<img src="{$dealer['avatar_url']}" alt="" loading="lazy" style="width:48px;height:48px;border-radius:50%">
			</span>
			{{endif}}
			<div style="flex:1 1 auto;min-width:0">
				<div style="font-size:0.78em;color:var(--gd-muted);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;margin-bottom:3px">Editing your review</div>
				<div style="font-size:1.15em;font-weight:700;color:var(--gd-text-primary);line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{$dealer['dealer_name']}</div>
			</div>
		</div>

		{{if $review['dispute_status'] === 'pending_customer' or $review['dispute_status'] === 'pending_admin'}}
		<div style="margin:16px 22px 0;padding:12px 14px;background:var(--gd-warn-bg);border:1px solid var(--gd-warn-border);border-radius:8px;color:var(--gd-warn-text);font-size:0.88em">
			<i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
			This review is currently under dispute. Editing is locked until the dispute is resolved.
		</div>
		{{endif}}

		<div style="padding:18px 22px 10px">
			<div style="font-size:0.78em;color:var(--gd-muted);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;margin-bottom:10px">Current ratings</div>
			<div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:4px">
				<div style="flex:1 1 140px">
					<div style="font-size:0.72em;color:var(--gd-muted);font-weight:600;margin-bottom:2px">Pricing</div>
					<div style="color:var(--gd-star);font-size:1em;letter-spacing:2px">{$review['stars_pricing']}</div>
				</div>
				<div style="flex:1 1 140px">
					<div style="font-size:0.72em;color:var(--gd-muted);font-weight:600;margin-bottom:2px">Shipping</div>
					<div style="color:var(--gd-star);font-size:1em;letter-spacing:2px">{$review['stars_shipping']}</div>
				</div>
				<div style="flex:1 1 140px">
					<div style="font-size:0.72em;color:var(--gd-muted);font-weight:600;margin-bottom:2px">Service</div>
					<div style="color:var(--gd-star);font-size:1em;letter-spacing:2px">{$review['stars_service']}</div>
				</div>
			</div>
		</div>

		<div style="padding:10px 22px 24px">
			<form method="post" action="{$editUrl}">
				<input type="hidden" name="csrfKey" value="{$csrfKey}">

				<div style="display:flex;gap:18px;margin-bottom:18px;flex-wrap:wrap">
					<div style="flex:1 1 180px">
						<label style="display:block;font-size:0.82em;font-weight:600;color:var(--gd-text-secondary);margin-bottom:6px">Pricing Accuracy</label>
						<select name="rating_pricing" class="ipsInput ipsInput--select" required style="width:100%">
							<option value="5" {{if $review['rating_pricing'] === 5}}selected{{endif}}>★★★★★ Excellent</option>
							<option value="4" {{if $review['rating_pricing'] === 4}}selected{{endif}}>★★★★☆ Good</option>
							<option value="3" {{if $review['rating_pricing'] === 3}}selected{{endif}}>★★★☆☆ Average</option>
							<option value="2" {{if $review['rating_pricing'] === 2}}selected{{endif}}>★★☆☆☆ Poor</option>
							<option value="1" {{if $review['rating_pricing'] === 1}}selected{{endif}}>★☆☆☆☆ Terrible</option>
						</select>
					</div>
					<div style="flex:1 1 180px">
						<label style="display:block;font-size:0.82em;font-weight:600;color:var(--gd-text-secondary);margin-bottom:6px">Shipping Speed</label>
						<select name="rating_shipping" class="ipsInput ipsInput--select" required style="width:100%">
							<option value="5" {{if $review['rating_shipping'] === 5}}selected{{endif}}>★★★★★ Excellent</option>
							<option value="4" {{if $review['rating_shipping'] === 4}}selected{{endif}}>★★★★☆ Good</option>
							<option value="3" {{if $review['rating_shipping'] === 3}}selected{{endif}}>★★★☆☆ Average</option>
							<option value="2" {{if $review['rating_shipping'] === 2}}selected{{endif}}>★★☆☆☆ Poor</option>
							<option value="1" {{if $review['rating_shipping'] === 1}}selected{{endif}}>★☆☆☆☆ Terrible</option>
						</select>
					</div>
					<div style="flex:1 1 180px">
						<label style="display:block;font-size:0.82em;font-weight:600;color:var(--gd-text-secondary);margin-bottom:6px">Customer Service</label>
						<select name="rating_service" class="ipsInput ipsInput--select" required style="width:100%">
							<option value="5" {{if $review['rating_service'] === 5}}selected{{endif}}>★★★★★ Excellent</option>
							<option value="4" {{if $review['rating_service'] === 4}}selected{{endif}}>★★★★☆ Good</option>
							<option value="3" {{if $review['rating_service'] === 3}}selected{{endif}}>★★★☆☆ Average</option>
							<option value="2" {{if $review['rating_service'] === 2}}selected{{endif}}>★★☆☆☆ Poor</option>
							<option value="1" {{if $review['rating_service'] === 1}}selected{{endif}}>★☆☆☆☆ Terrible</option>
						</select>
					</div>
				</div>

				<div style="margin-bottom:20px">
					<label style="display:block;font-size:0.82em;font-weight:600;color:var(--gd-text-secondary);margin-bottom:6px">Your Review</label>
					<textarea name="review_body" rows="6" class="ipsInput ipsInput--text" placeholder="Share your experience..." style="width:100%;box-sizing:border-box;border:1px solid var(--gd-card-border);border-radius:6px;padding:10px 12px;font-size:0.95em;line-height:1.5;color:var(--gd-text-primary);background:#fff;resize:vertical">{$review['review_body']}</textarea>
				</div>

				<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
					<button type="submit" class="ipsButton ipsButton--primary">
						<i class="fa-solid fa-check" aria-hidden="true"></i> Save Changes
					</button>
					<a href="{$cancelUrl}" class="ipsButton ipsButton--normal">Cancel</a>
				</div>
			</form>
		</div>
	</div>
</div>
TEMPLATE_EOT;

try
{
	$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_theme_templates', [
		'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		'gddealer', 'front', 'dealers', 'editReview',
	] )->first();

	if ( $exists > 0 )
	{
		\IPS\Db::i()->update( 'core_theme_templates', [
			'template_data'    => $editReviewData,
			'template_content' => $editReviewContent,
		], [
			'template_app=? AND template_location=? AND template_group=? AND template_name=?',
			'gddealer', 'front', 'dealers', 'editReview',
		] );
	}
	else
	{
		\IPS\Db::i()->insert( 'core_theme_templates', [
			'template_set_id'   => 1,
			'template_app'      => 'gddealer',
			'template_location' => 'front',
			'template_group'    => 'dealers',
			'template_name'     => 'editReview',
			'template_data'     => $editReviewData,
			'template_content'  => $editReviewContent,
		] );
	}
}
catch ( \Exception ) {}

/* ── Patch dealerProfile: rewrite the Customer Reviews list block ── */
$oldReviewList = <<<'NEEDLE_EOT'
			<div class="ipsBox" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
				<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">Customer Reviews <span style="font-size:0.75em;font-weight:400;color:#666">({expression="number_format($stats['total'])"})</span></h3>
				{{if count($reviews) === 0}}
				<div class="i-padding_2" style="padding:32px;text-align:center;color:#999">
					<i class="fa-regular fa-star" style="font-size:2em;display:block;margin-bottom:8px;opacity:0.4" aria-hidden="true"></i>
					No reviews yet. Be the first to review this dealer.
				</div>
				{{else}}
					{{foreach $reviews as $r}}
					<div style="padding:20px;border-bottom:1px solid var(--i-border-color,#f0f0f0)">

						<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
							<div style="display:flex;align-items:center;gap:10px">
								<span class="ipsUserPhoto ipsUserPhoto--small">
									<img src="{$r['reviewer_avatar']}" alt="" loading="lazy">
								</span>
								<div>
									<div style="font-weight:700;font-size:0.9em">{$r['reviewer_name']}</div>
									<div style="font-size:0.75em;color:#9ca3af">{$r['created_at_formatted']}</div>
								</div>
							</div>
							<div style="background:{$r['avg_color']}18;border:1px solid {$r['avg_color']}40;border-radius:20px;padding:4px 12px;display:flex;align-items:center;gap:6px">
								<span style="color:{$r['avg_color']};font-weight:800;font-size:1em">{$r['avg_score']}</span>
								<span style="color:#9ca3af;font-size:0.75em">/ 5</span>
							</div>
						</div>

						<div style="display:flex;gap:16px;margin-bottom:12px;flex-wrap:wrap">
							<div style="display:flex;align-items:center;gap:4px">
								<span style="font-size:0.75em;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Pricing</span>
								<span style="color:#f59e0b;font-size:0.9em">{$r['stars_pricing']}</span>
							</div>
							<div style="display:flex;align-items:center;gap:4px">
								<span style="font-size:0.75em;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Shipping</span>
								<span style="color:#f59e0b;font-size:0.9em">{$r['stars_shipping']}</span>
							</div>
							<div style="display:flex;align-items:center;gap:4px">
								<span style="font-size:0.75em;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.04em">Service</span>
								<span style="color:#f59e0b;font-size:0.9em">{$r['stars_service']}</span>
							</div>
						</div>

						{{if $r['review_body']}}
						<p style="margin:0 0 12px;line-height:1.6;color:#374151">{$r['review_body']}</p>
						{{endif}}

						{{if $r['is_own_review'] and $r['dispute_status'] === 'pending_customer'}}
						<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px 16px;font-size:0.85em;color:#991b1b;margin-bottom:8px">
							<strong>Action Required:</strong> The dealer has contested this review. You must respond or the dispute will be resolved in their favor.
							{{if $r['dispute_respond_url']}}<a href="{$r['dispute_respond_url']}" style="color:#dc2626;font-weight:700;margin-left:6px">Respond Now &rarr;</a>{{endif}}
						</div>
						{{elseif $r['dispute_status'] === 'pending_customer' or $r['dispute_status'] === 'pending_admin'}}
						<div style="background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:8px 12px;font-size:0.82em;color:#854d0e;margin-bottom:8px">
							This review is currently under dispute review.
						</div>
						{{endif}}

						{{if $r['dealer_response']}}
						<div style="background:#f0f7ff;border-left:3px solid var(--gd-primary,#2563eb);padding:12px 16px;border-radius:0 6px 6px 0;margin-top:8px">
							<div style="font-size:0.75em;color:var(--gd-primary,#2563eb);font-weight:700;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.05em">
								<i class="fa-solid fa-reply" aria-hidden="true"></i> Dealer Response
								<span style="color:#9ca3af;font-weight:400;text-transform:none;margin-left:6px">{$r['response_at']}</span>
							</div>
							<p style="margin:0;font-size:0.9em;line-height:1.5;color:#374151">{$r['dealer_response']}</p>
						</div>
						{{endif}}

						{{if $r['edit_review_url']}}
						<div style="margin-top:10px">
							<a href="{$r['edit_review_url']}" style="font-size:0.82em;color:var(--gd-primary,#2563eb);font-weight:600">
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
