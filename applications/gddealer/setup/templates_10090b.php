<?php
/**
 * GD Dealer Manager — v1.0.90b template bodies.
 *
 * Returns an array of template definitions keyed by template_name. Each entry
 * declares its parameter signature (`data` key, matching template_data in
 * core_theme_templates) and its rendered body (`content` key).
 *
 * Consumed by:
 *   - setup/upg_10091/queries.php   (upgrade step — applies via Db::i()->update())
 *   - tools/fix_templates_v10091.php (one-shot prod recovery)
 *
 * Rule 18 (CLAUDE.md): these modify templates that already exist in
 * core_theme_templates (seeded by setup/install.php at set_id=1), so they
 * MUST be applied with Db::i()->update() keyed on
 * (template_app, template_location, template_group, template_name).
 * Db::i()->replace() with template_set_id=0 creates a second row that gets
 * overwritten by the stale set_id=1 row during compilation, causing an
 * ArgumentCountError when the signature has changed.
 *
 * ─────────────────────────────────────────────────────────────────────────
 *  ⚠  PLACEHOLDER CONTENT — MUST BE REPLACED BEFORE SHIPPING v1.0.91
 * ─────────────────────────────────────────────────────────────────────────
 * The v1.0.90/90a/90b redesign was edited directly on production and was
 * never committed to this repo. The template `content` strings below are
 * marked with the sentinel "<style>
#ipsLayout_mainArea { max-width: 100% !important; }
#ipsLayout_main { max-width: 100% !important; }
.ipsLayout_container { max-width: 1446px !important; }
.gdDealerStats { display:flex; flex-wrap:wrap; border-top:1px solid var(--i-border-color,#e8e8e8); margin-top:16px; }
.gdDealerStats > div { flex:1 1 120px; padding:16px 20px; text-align:center; min-height:80px; display:flex; flex-direction:column; justify-content:center; }
.gdDealerStats > div + div { border-left:1px solid var(--i-border-color,#e8e8e8); }
@media (max-width: 768px) {
  .gdDealerStats > div { flex:1 1 45%; min-height:60px; }
  .gdDealerStats > div + div { border-left:none; }
  .gdDealerStats > div:nth-child(odd) { border-right:1px solid var(--i-border-color,#e8e8e8); }
  .gdDealerStats > div:nth-child(n+3) { border-top:1px solid var(--i-border-color,#e8e8e8); }
  .gdProfileSidebar { width:100% !important; flex-shrink:1 !important; }
  .gdProfileButtons { justify-content:center; }
  .gdTableWrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
}
@media (max-width: 480px) {
  .gdDealerStats > div { flex:1 1 100%; border-left:none !important; border-right:none !important; }
  .gdDealerStats > div + div { border-top:1px solid var(--i-border-color,#e8e8e8); }
}
</style>

<div class="gdDealerWrapper" style="width:100%;max-width:1446px;margin:0 auto;padding:0 24px;box-sizing:border-box;--gd-dealer-brand:{$data['dealer']['brand_color']}">

	<header class="gdHero">
		<div class="gdHero__cover">
			{{if $data['dealer']['cover_photo_url']}}
				<img src="{$data['dealer']['cover_photo_url']}" alt="" loading="lazy">
			{{else}}
				<div class="gdHero__coverFallback" style="background:linear-gradient(135deg,{$data['dealer']['brand_color']} 0%,#1e293b 100%)"></div>
			{{endif}}
		</div>
		<div class="gdHero__body">
			<div class="gdHero__logoWrap">
				{{if $data['dealer']['logo_url']}}
				<div class="gdHero__logo">
					<img src="{$data['dealer']['logo_url']}" alt="" loading="lazy">
				</div>
				{{elseif $data['dealer']['avatar_url']}}
				<div class="gdHero__avatar">
					<img src="{$data['dealer']['avatar_url']}" alt="" loading="lazy">
				</div>
				{{endif}}
			</div>
			<div class="gdHero__info">
				<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
					<h1 class="gdHero__name">{$data['dealer']['dealer_name']}</h1>
					<span class="gdHero__tier" style="background:{$data['dealer']['tier_color']}">{$data['dealer']['tier_label']}</span>
				</div>
				{{if $data['dealer']['tagline']}}
				<p class="gdHero__tagline">{$data['dealer']['tagline']}</p>
				{{endif}}
				<div class="gdHero__meta">
					{{if $data['dealer']['address_city_state']}}
					<span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {$data['dealer']['address_city_state']}</span>
					{{endif}}
					{{if $data['dealer']['website_url']}}
					<a href="{$data['dealer']['website_url']}" target="_blank" rel="noopener"><i class="fa-solid fa-globe" aria-hidden="true"></i> Website</a>
					{{endif}}
				</div>
				<div class="gdProfileButtons" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
					<a href="mailto:{$data['dealer']['contact_email']}" class="ipsButton ipsButton--primary ipsButton--small">
						<i class="fa-solid fa-envelope" aria-hidden="true"></i> Contact
					</a>
					<a href="{$data['guidelines_url']}" class="ipsButton ipsButton--inherit ipsButton--small">
						<i class="fa-solid fa-circle-info" aria-hidden="true"></i> Guidelines
					</a>
				</div>
			</div>
			<div class="gdHero__rating">
				<div class="gdHero__ratingValue" style='{expression="'color:' . ($data['stats']['rating_color'] ?? '#1E40AF')"}'>{$data['stats']['avg_overall']}</div>
				<div class="gdHero__ratingLabel" style='{expression="'color:' . ($data['stats']['rating_color'] ?? '#1E40AF')"}'>{$data['stats']['rating_label']}</div>
				<div class="gdHero__ratingCount">{expression="number_format($data['stats']['count'])"} reviews</div>
			</div>
		</div>
		<div class="gdDealerStats">
			<div>
				<div style="font-size:0.72em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Overall Rating</div>
				<div style='{expression="'font-size:1.6em;font-weight:800;color:' . ($data['stats']['rating_color'] ?? '#1E40AF') . ';line-height:1'"}'>{$data['stats']['avg_overall']}<span style="font-size:0.45em;color:#888;font-weight:400"> /5</span></div>
				<div style='{expression="'font-size:0.72em;font-weight:600;color:' . ($data['stats']['rating_color'] ?? '#1E40AF') . ';margin-top:4px'"}'>{$data['stats']['rating_label']}</div>
			</div>
			<div>
				<div style="font-size:0.75em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Reviews</div>
				<div style="font-size:1.6em;font-weight:800;line-height:1">{$data['stats']['total']}</div>
			</div>
			<div>
				<div style="font-size:0.75em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Member Since</div>
				<div style="font-size:1.6em;font-weight:800;line-height:1">{{if $data['dealer']['member_since']}}{$data['dealer']['member_since']}{{else}}&mdash;{{endif}}</div>
			</div>
			<div>
				<div style="font-size:0.75em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Active Listings</div>
				<div style="font-size:1.6em;font-weight:800;color:#16a34a;line-height:1">{$data['dealer']['listing_count']}</div>
			</div>
		</div>
	</header>

	{{if !$data['dealer']['is_active']}}
	<div style="background:#f8f9fa;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:14px 18px;margin-bottom:16px">
		<strong style="color:#374151">This dealer's listings are currently inactive.</strong>
		<p style="margin:4px 0 0;color:#6b7280;font-size:0.9em">Inventory and pricing are not being updated. Existing reviews and ratings are shown below for reference.</p>
	</div>
	{{endif}}

	{{if $data['customer_dispute']}}
	<div class="ipsBox i-margin-bottom_block" style="background:#fff8f0;border:1px solid #f59e0b;border-radius:8px;padding:20px;margin-bottom:24px">
		<h2 style="margin:0 0 8px;font-size:1.05em;font-weight:700;color:#92400e">{$data['dealer']['dealer_name']} has contested your review</h2>
		<p style="margin:0 0 12px;font-size:0.9em;color:#78350f">
			The dealer has submitted a contest against the review you left.
			{{if $data['customer_dispute']['dispute_deadline']}}You have until <strong>{$data['customer_dispute']['dispute_deadline']}</strong> to respond, or the contest will be automatically resolved in the dealer's favor.{{endif}}
		</p>
		{{if $data['customer_dispute']['dispute_reason']}}
		<div style="background:#fff;border-left:3px solid #f59e0b;padding:10px 14px;margin-bottom:12px;border-radius:0 4px 4px 0">
			<div style="font-size:0.8em;font-weight:700;color:#92400e;margin-bottom:4px">Dealer's reason</div>
			<p style="margin:0;font-size:0.9em;color:#333">{$data['customer_dispute']['dispute_reason']}</p>
		</div>
		{{endif}}
		{{if $data['customer_dispute']['dispute_evidence']}}
		<div style="background:#fff;border-left:3px solid #f59e0b;padding:10px 14px;margin-bottom:12px;border-radius:0 4px 4px 0">
			<div style="font-size:0.8em;font-weight:700;color:#92400e;margin-bottom:4px">Dealer's evidence</div>
			<p style="margin:0;font-size:0.9em;color:#333;white-space:pre-wrap">{$data['customer_dispute']['dispute_evidence']}</p>
		</div>
		{{endif}}
		<form method="post" action="{$data['customer_dispute']['respond_url']}">
			<input type="hidden" name="csrfKey" value="{$data['csrf_key']}">
			<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:4px;color:#78350f">Your response</label>
			<textarea name="customer_response" rows="4" required class="ipsInput ipsInput--text" style="width:100%;border:1px solid #f59e0b;border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Explain your side of the story. Admin will review both accounts."></textarea>
			<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:4px;color:#78350f">Supporting evidence (optional)</label>
			<textarea name="customer_evidence" rows="3" class="ipsInput ipsInput--text" style="width:100%;border:1px solid #f59e0b;border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Paste order numbers, links, receipts, or other evidence that supports your review..."></textarea>
			<button type="submit" class="ipsButton ipsButton--primary">Submit My Response</button>
		</form>
	</div>
	{{endif}}

	<div class="ipsProfile ipsProfile--profile" style="display:flex;gap:24px;flex-wrap:wrap">
		<aside class="ipsProfile__aside gdProfileSidebar" style="width:300px;flex-shrink:0">
			<div class="ipsProfile__sticky-outer">
				<div class="ipsProfile__sticky-inner">
					<div class="ipsWidget" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:16px">
						<h3 class="ipsWidget__title" style="margin:0;padding:12px 16px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:0.9em;font-weight:700">Rating Breakdown</h3>
						<div class="ipsWidget__content i-padding_2" style="padding:16px">
							<div style="margin-bottom:14px">
								<div style="display:flex;justify-content:space-between;margin-bottom:4px">
									<span style="font-size:0.85em">Pricing Accuracy</span>
									<strong style='{expression="'color:' . ($data['stats']['color_pricing'] ?? '#1E40AF')"}'>{$data['stats']['avg_pricing']}/5</strong>
								</div>
								<div style="background:var(--i-border-color,#e0e0e0);border-radius:4px;height:8px">
									<div style='{expression="'background:' . ($data['stats']['color_pricing'] ?? '#1E40AF') . ';border-radius:4px;height:8px;width:' . ($data['stats']['pct_pricing'] ?? 0) . '%;transition:width 0.3s ease'"}'></div>
								</div>
							</div>
							<div style="margin-bottom:14px">
								<div style="display:flex;justify-content:space-between;margin-bottom:4px">
									<span style="font-size:0.85em">Shipping Speed</span>
									<strong style='{expression="'color:' . ($data['stats']['color_shipping'] ?? '#1E40AF')"}'>{$data['stats']['avg_shipping']}/5</strong>
								</div>
								<div style="background:var(--i-border-color,#e0e0e0);border-radius:4px;height:8px">
									<div style='{expression="'background:' . ($data['stats']['color_shipping'] ?? '#1E40AF') . ';border-radius:4px;height:8px;width:' . ($data['stats']['pct_shipping'] ?? 0) . '%;transition:width 0.3s ease'"}'></div>
								</div>
							</div>
							<div>
								<div style="display:flex;justify-content:space-between;margin-bottom:4px">
									<span style="font-size:0.85em">Customer Service</span>
									<strong style='{expression="'color:' . ($data['stats']['color_service'] ?? '#1E40AF')"}'>{$data['stats']['avg_service']}/5</strong>
								</div>
								<div style="background:var(--i-border-color,#e0e0e0);border-radius:4px;height:8px">
									<div style='{expression="'background:' . ($data['stats']['color_service'] ?? '#1E40AF') . ';border-radius:4px;height:8px;width:' . ($data['stats']['pct_service'] ?? 0) . '%;transition:width 0.3s ease'"}'></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</aside>

		<div class="ipsProfile__main" style="flex:1 1 0;min-width:0">
			{{if $data['can_rate']}}
			<div class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:24px">
				<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">Leave a Review</h3>
				<div class="i-padding_2" style="padding:18px">
					<form method="post" action="{$data['rate_url']}">
						<input type="hidden" name="csrfKey" value="{$data['csrf_key']}">
						<div style="display:flex;gap:20px;margin-bottom:16px;flex-wrap:wrap">
							<div style="flex:1 1 140px">
								<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Pricing Accuracy</label>
								<select name="rating_pricing" class="ipsInput ipsInput--select" required>
									<option value="">Rate...</option>
									<option value="5">★★★★★ Excellent</option>
									<option value="4">★★★★☆ Good</option>
									<option value="3">★★★☆☆ Average</option>
									<option value="2">★★☆☆☆ Poor</option>
									<option value="1">★☆☆☆☆ Terrible</option>
								</select>
							</div>
							<div style="flex:1 1 140px">
								<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Shipping Speed</label>
								<select name="rating_shipping" class="ipsInput ipsInput--select" required>
									<option value="">Rate...</option>
									<option value="5">★★★★★ Excellent</option>
									<option value="4">★★★★☆ Good</option>
									<option value="3">★★★☆☆ Average</option>
									<option value="2">★★☆☆☆ Poor</option>
									<option value="1">★☆☆☆☆ Terrible</option>
								</select>
							</div>
							<div style="flex:1 1 140px">
								<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Customer Service</label>
								<select name="rating_service" class="ipsInput ipsInput--select" required>
									<option value="">Rate...</option>
									<option value="5">★★★★★ Excellent</option>
									<option value="4">★★★★☆ Good</option>
									<option value="3">★★★☆☆ Average</option>
									<option value="2">★★☆☆☆ Poor</option>
									<option value="1">★☆☆☆☆ Terrible</option>
								</select>
							</div>
						</div>
						<textarea name="review_body" rows="4" class="ipsInput ipsInput--text" style="width:100%;box-sizing:border-box;margin-bottom:12px;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em" placeholder="Share your experience (optional but helpful)..."></textarea>
						<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
							<span style="font-size:0.8em;color:#666">By submitting you agree to our <a href="{$data['guidelines_url']}" style="color:#2563eb">review guidelines</a>.</span>
							<button type="submit" class="ipsButton ipsButton--primary">Submit Review</button>
						</div>
					</form>
				</div>
			</div>
			{{elseif $data['already_rated']}}
			<div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 18px;margin-bottom:24px;color:#1e40af">
				You have already reviewed this dealer. Thank you for your feedback!
			</div>
			{{elseif $data['login_required']}}
			<div class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:24px">
				<div class="i-padding_2" style="padding:24px;text-align:center">
					<p style="margin:0 0 12px;color:#666">Sign in to leave a review for this dealer.</p>
					<a href="{$data['login_url']}" class="ipsButton ipsButton--primary">Sign In to Review</a>
				</div>
			</div>
			{{endif}}

			<div class="ipsBox" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
				<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">Customer Reviews <span style="font-size:0.75em;font-weight:400;color:#666">({expression="number_format($data['stats']['total'])"})</span></h3>
				{{if count($data['reviews']) === 0}}
				<div class="i-padding_2" style="padding:32px;text-align:center;color:#999">
					<i class="fa-regular fa-star" style="font-size:2em;display:block;margin-bottom:8px;opacity:0.4" aria-hidden="true"></i>
					No reviews yet. Be the first to review this dealer.
				</div>
				{{else}}
					{{foreach $data['reviews'] as $r}}
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
							<div style='{expression="'background:' . $r['avg_color'] . '18;border:1px solid ' . $r['avg_color'] . '40;border-radius:20px;padding:4px 12px;display:flex;align-items:center;gap:6px'"}'>
								<span style='{expression="'color:' . $r['avg_color'] . ';font-weight:800;font-size:1em'"}'>{$r['avg_score']}</span>
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

						{{if $r['dispute_status'] === 'pending_customer' || $r['dispute_status'] === 'pending_admin'}}
						<div style="background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:8px 12px;font-size:0.82em;color:#854d0e;margin-bottom:8px">
							⚠ This review is currently under admin review.
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

					</div>
					{{endforeach}}
				{{endif}}
			</div>
		</div>
	</div>

</div>". The v1.0.91 upgrade
 * step (setup/upg_10091/queries.php) detects that sentinel and will SKIP
 * the UPDATE rather than overwrite live prod template bodies with a stub.
 *
 * To finalise v1.0.91:
 *   1. SSH to gunrack.deals:2200
 *   2. Export the current dealerProfile body:
 *        SELECT template_content
 *        FROM   core_theme_templates
 *        WHERE  template_app='gddealer'
 *          AND  template_location='front'
 *          AND  template_group='dealers'
 *          AND  template_name='dealerProfile'
 *          AND  template_set_id=0
 *        INTO OUTFILE '/tmp/dealerProfile.tpl';
 *   3. Paste the file contents into the `content` nowdoc heredoc below,
 *      replacing everything between `<<<'TEMPLATE_EOT'` and `TEMPLATE_EOT;`
 *   4. Rebuild the tar and bump this note out of the file.
 *
 * Preserve nowdoc heredoc syntax (<<<'TEMPLATE_EOT') so real newlines/tabs
 * are stored and comment syntax is not mangled (Rules 4 and 9).
 */

return [

	/* ---- front/dealers/dealerProfile ---- */
	/* New signature: 1 param ($data) replacing the v1.0.0 19-param list.
	 * $data is an associative array assembled by the controller with keys
	 *   dealer, stats, reviews, canRate, editUrl, suspendUrl, importUrl,
	 *   backUrl, logs, listings, tierLabel, rebates, ...
	 * All data-flattening logic lives in modules/front/dealers/profile.php. */
	'dealerProfile' => [
		'data'    => '$data',
		'content' => <<<'TEMPLATE_EOT'
<style>
#ipsLayout_mainArea { max-width: 100% !important; }
#ipsLayout_main { max-width: 100% !important; }
.ipsLayout_container { max-width: 1446px !important; }
.gdDealerStats { display:flex; flex-wrap:wrap; border-top:1px solid var(--i-border-color,#e8e8e8); margin-top:16px; }
.gdDealerStats > div { flex:1 1 120px; padding:16px 20px; text-align:center; min-height:80px; display:flex; flex-direction:column; justify-content:center; }
.gdDealerStats > div + div { border-left:1px solid var(--i-border-color,#e8e8e8); }
@media (max-width: 768px) {
  .gdDealerStats > div { flex:1 1 45%; min-height:60px; }
  .gdDealerStats > div + div { border-left:none; }
  .gdDealerStats > div:nth-child(odd) { border-right:1px solid var(--i-border-color,#e8e8e8); }
  .gdDealerStats > div:nth-child(n+3) { border-top:1px solid var(--i-border-color,#e8e8e8); }
  .gdProfileSidebar { width:100% !important; flex-shrink:1 !important; }
  .gdProfileButtons { justify-content:center; }
  .gdTableWrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
}
@media (max-width: 480px) {
  .gdDealerStats > div { flex:1 1 100%; border-left:none !important; border-right:none !important; }
  .gdDealerStats > div + div { border-top:1px solid var(--i-border-color,#e8e8e8); }
}
</style>

<div class="gdDealerWrapper" style="width:100%;max-width:1446px;margin:0 auto;padding:0 24px;box-sizing:border-box;--gd-dealer-brand:{$data['dealer']['brand_color']}">

	<header class="gdHero">
		<div class="gdHero__cover">
			{{if $data['dealer']['cover_photo_url']}}
				<img src="{$data['dealer']['cover_photo_url']}" alt="" loading="lazy">
			{{else}}
				<div class="gdHero__coverFallback" style="background:linear-gradient(135deg,{$data['dealer']['brand_color']} 0%,#1e293b 100%)"></div>
			{{endif}}
		</div>
		<div class="gdHero__body">
			<div class="gdHero__logoWrap">
				{{if $data['dealer']['logo_url']}}
				<div class="gdHero__logo">
					<img src="{$data['dealer']['logo_url']}" alt="" loading="lazy">
				</div>
				{{elseif $data['dealer']['avatar_url']}}
				<div class="gdHero__avatar">
					<img src="{$data['dealer']['avatar_url']}" alt="" loading="lazy">
				</div>
				{{endif}}
			</div>
			<div class="gdHero__info">
				<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
					<h1 class="gdHero__name">{$data['dealer']['dealer_name']}</h1>
					<span class="gdHero__tier" style="background:{$data['dealer']['tier_color']}">{$data['dealer']['tier_label']}</span>
				</div>
				{{if $data['dealer']['tagline']}}
				<p class="gdHero__tagline">{$data['dealer']['tagline']}</p>
				{{endif}}
				<div class="gdHero__meta">
					{{if $data['dealer']['address_city_state']}}
					<span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {$data['dealer']['address_city_state']}</span>
					{{endif}}
					{{if $data['dealer']['website_url']}}
					<a href="{$data['dealer']['website_url']}" target="_blank" rel="noopener"><i class="fa-solid fa-globe" aria-hidden="true"></i> Website</a>
					{{endif}}
				</div>
				<div class="gdProfileButtons" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
					<a href="mailto:{$data['dealer']['contact_email']}" class="ipsButton ipsButton--primary ipsButton--small">
						<i class="fa-solid fa-envelope" aria-hidden="true"></i> Contact
					</a>
					<a href="{$data['guidelines_url']}" class="ipsButton ipsButton--inherit ipsButton--small">
						<i class="fa-solid fa-circle-info" aria-hidden="true"></i> Guidelines
					</a>
				</div>
			</div>
			<div class="gdHero__rating">
				<div class="gdHero__ratingValue" style='{expression="'color:' . ($data['stats']['rating_color'] ?? '#1E40AF')"}'>{$data['stats']['avg_overall']}</div>
				<div class="gdHero__ratingLabel" style='{expression="'color:' . ($data['stats']['rating_color'] ?? '#1E40AF')"}'>{$data['stats']['rating_label']}</div>
				<div class="gdHero__ratingCount">{expression="number_format($data['stats']['count'])"} reviews</div>
			</div>
		</div>
		<div class="gdDealerStats">
			<div>
				<div style="font-size:0.72em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Overall Rating</div>
				<div style='{expression="'font-size:1.6em;font-weight:800;color:' . ($data['stats']['rating_color'] ?? '#1E40AF') . ';line-height:1'"}'>{$data['stats']['avg_overall']}<span style="font-size:0.45em;color:#888;font-weight:400"> /5</span></div>
				<div style='{expression="'font-size:0.72em;font-weight:600;color:' . ($data['stats']['rating_color'] ?? '#1E40AF') . ';margin-top:4px'"}'>{$data['stats']['rating_label']}</div>
			</div>
			<div>
				<div style="font-size:0.75em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Reviews</div>
				<div style="font-size:1.6em;font-weight:800;line-height:1">{$data['stats']['total']}</div>
			</div>
			<div>
				<div style="font-size:0.75em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Member Since</div>
				<div style="font-size:1.6em;font-weight:800;line-height:1">{{if $data['dealer']['member_since']}}{$data['dealer']['member_since']}{{else}}&mdash;{{endif}}</div>
			</div>
			<div>
				<div style="font-size:0.75em;color:#888;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;white-space:nowrap">Active Listings</div>
				<div style="font-size:1.6em;font-weight:800;color:#16a34a;line-height:1">{$data['dealer']['listing_count']}</div>
			</div>
		</div>
	</header>

	{{if !$data['dealer']['is_active']}}
	<div style="background:#f8f9fa;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:14px 18px;margin-bottom:16px">
		<strong style="color:#374151">This dealer's listings are currently inactive.</strong>
		<p style="margin:4px 0 0;color:#6b7280;font-size:0.9em">Inventory and pricing are not being updated. Existing reviews and ratings are shown below for reference.</p>
	</div>
	{{endif}}

	{{if $data['customer_dispute']}}
	<div class="ipsBox i-margin-bottom_block" style="background:#fff8f0;border:1px solid #f59e0b;border-radius:8px;padding:20px;margin-bottom:24px">
		<h2 style="margin:0 0 8px;font-size:1.05em;font-weight:700;color:#92400e">{$data['dealer']['dealer_name']} has contested your review</h2>
		<p style="margin:0 0 12px;font-size:0.9em;color:#78350f">
			The dealer has submitted a contest against the review you left.
			{{if $data['customer_dispute']['dispute_deadline']}}You have until <strong>{$data['customer_dispute']['dispute_deadline']}</strong> to respond, or the contest will be automatically resolved in the dealer's favor.{{endif}}
		</p>
		{{if $data['customer_dispute']['dispute_reason']}}
		<div style="background:#fff;border-left:3px solid #f59e0b;padding:10px 14px;margin-bottom:12px;border-radius:0 4px 4px 0">
			<div style="font-size:0.8em;font-weight:700;color:#92400e;margin-bottom:4px">Dealer's reason</div>
			<p style="margin:0;font-size:0.9em;color:#333">{$data['customer_dispute']['dispute_reason']}</p>
		</div>
		{{endif}}
		{{if $data['customer_dispute']['dispute_evidence']}}
		<div style="background:#fff;border-left:3px solid #f59e0b;padding:10px 14px;margin-bottom:12px;border-radius:0 4px 4px 0">
			<div style="font-size:0.8em;font-weight:700;color:#92400e;margin-bottom:4px">Dealer's evidence</div>
			<p style="margin:0;font-size:0.9em;color:#333;white-space:pre-wrap">{$data['customer_dispute']['dispute_evidence']}</p>
		</div>
		{{endif}}
		<form method="post" action="{$data['customer_dispute']['respond_url']}">
			<input type="hidden" name="csrfKey" value="{$data['csrf_key']}">
			<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:4px;color:#78350f">Your response</label>
			<textarea name="customer_response" rows="4" required class="ipsInput ipsInput--text" style="width:100%;border:1px solid #f59e0b;border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Explain your side of the story. Admin will review both accounts."></textarea>
			<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:4px;color:#78350f">Supporting evidence (optional)</label>
			<textarea name="customer_evidence" rows="3" class="ipsInput ipsInput--text" style="width:100%;border:1px solid #f59e0b;border-radius:4px;padding:8px;font-size:0.9em;box-sizing:border-box;margin-bottom:8px" placeholder="Paste order numbers, links, receipts, or other evidence that supports your review..."></textarea>
			<button type="submit" class="ipsButton ipsButton--primary">Submit My Response</button>
		</form>
	</div>
	{{endif}}

	<div class="ipsProfile ipsProfile--profile" style="display:flex;gap:24px;flex-wrap:wrap">
		<aside class="ipsProfile__aside gdProfileSidebar" style="width:300px;flex-shrink:0">
			<div class="ipsProfile__sticky-outer">
				<div class="ipsProfile__sticky-inner">
					<div class="ipsWidget" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:16px">
						<h3 class="ipsWidget__title" style="margin:0;padding:12px 16px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:0.9em;font-weight:700">Rating Breakdown</h3>
						<div class="ipsWidget__content i-padding_2" style="padding:16px">
							<div style="margin-bottom:14px">
								<div style="display:flex;justify-content:space-between;margin-bottom:4px">
									<span style="font-size:0.85em">Pricing Accuracy</span>
									<strong style='{expression="'color:' . ($data['stats']['color_pricing'] ?? '#1E40AF')"}'>{$data['stats']['avg_pricing']}/5</strong>
								</div>
								<div style="background:var(--i-border-color,#e0e0e0);border-radius:4px;height:8px">
									<div style='{expression="'background:' . ($data['stats']['color_pricing'] ?? '#1E40AF') . ';border-radius:4px;height:8px;width:' . ($data['stats']['pct_pricing'] ?? 0) . '%;transition:width 0.3s ease'"}'></div>
								</div>
							</div>
							<div style="margin-bottom:14px">
								<div style="display:flex;justify-content:space-between;margin-bottom:4px">
									<span style="font-size:0.85em">Shipping Speed</span>
									<strong style='{expression="'color:' . ($data['stats']['color_shipping'] ?? '#1E40AF')"}'>{$data['stats']['avg_shipping']}/5</strong>
								</div>
								<div style="background:var(--i-border-color,#e0e0e0);border-radius:4px;height:8px">
									<div style='{expression="'background:' . ($data['stats']['color_shipping'] ?? '#1E40AF') . ';border-radius:4px;height:8px;width:' . ($data['stats']['pct_shipping'] ?? 0) . '%;transition:width 0.3s ease'"}'></div>
								</div>
							</div>
							<div>
								<div style="display:flex;justify-content:space-between;margin-bottom:4px">
									<span style="font-size:0.85em">Customer Service</span>
									<strong style='{expression="'color:' . ($data['stats']['color_service'] ?? '#1E40AF')"}'>{$data['stats']['avg_service']}/5</strong>
								</div>
								<div style="background:var(--i-border-color,#e0e0e0);border-radius:4px;height:8px">
									<div style='{expression="'background:' . ($data['stats']['color_service'] ?? '#1E40AF') . ';border-radius:4px;height:8px;width:' . ($data['stats']['pct_service'] ?? 0) . '%;transition:width 0.3s ease'"}'></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</aside>

		<div class="ipsProfile__main" style="flex:1 1 0;min-width:0">
			{{if $data['can_rate']}}
			<div class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:24px">
				<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">Leave a Review</h3>
				<div class="i-padding_2" style="padding:18px">
					<form method="post" action="{$data['rate_url']}">
						<input type="hidden" name="csrfKey" value="{$data['csrf_key']}">
						<div style="display:flex;gap:20px;margin-bottom:16px;flex-wrap:wrap">
							<div style="flex:1 1 140px">
								<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Pricing Accuracy</label>
								<select name="rating_pricing" class="ipsInput ipsInput--select" required>
									<option value="">Rate...</option>
									<option value="5">★★★★★ Excellent</option>
									<option value="4">★★★★☆ Good</option>
									<option value="3">★★★☆☆ Average</option>
									<option value="2">★★☆☆☆ Poor</option>
									<option value="1">★☆☆☆☆ Terrible</option>
								</select>
							</div>
							<div style="flex:1 1 140px">
								<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Shipping Speed</label>
								<select name="rating_shipping" class="ipsInput ipsInput--select" required>
									<option value="">Rate...</option>
									<option value="5">★★★★★ Excellent</option>
									<option value="4">★★★★☆ Good</option>
									<option value="3">★★★☆☆ Average</option>
									<option value="2">★★☆☆☆ Poor</option>
									<option value="1">★☆☆☆☆ Terrible</option>
								</select>
							</div>
							<div style="flex:1 1 140px">
								<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Customer Service</label>
								<select name="rating_service" class="ipsInput ipsInput--select" required>
									<option value="">Rate...</option>
									<option value="5">★★★★★ Excellent</option>
									<option value="4">★★★★☆ Good</option>
									<option value="3">★★★☆☆ Average</option>
									<option value="2">★★☆☆☆ Poor</option>
									<option value="1">★☆☆☆☆ Terrible</option>
								</select>
							</div>
						</div>
						<textarea name="review_body" rows="4" class="ipsInput ipsInput--text" style="width:100%;box-sizing:border-box;margin-bottom:12px;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em" placeholder="Share your experience (optional but helpful)..."></textarea>
						<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
							<span style="font-size:0.8em;color:#666">By submitting you agree to our <a href="{$data['guidelines_url']}" style="color:#2563eb">review guidelines</a>.</span>
							<button type="submit" class="ipsButton ipsButton--primary">Submit Review</button>
						</div>
					</form>
				</div>
			</div>
			{{elseif $data['already_rated']}}
			<div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 18px;margin-bottom:24px;color:#1e40af">
				You have already reviewed this dealer. Thank you for your feedback!
			</div>
			{{elseif $data['login_required']}}
			<div class="ipsBox i-margin-bottom_block" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;margin-bottom:24px">
				<div class="i-padding_2" style="padding:24px;text-align:center">
					<p style="margin:0 0 12px;color:#666">Sign in to leave a review for this dealer.</p>
					<a href="{$data['login_url']}" class="ipsButton ipsButton--primary">Sign In to Review</a>
				</div>
			</div>
			{{endif}}

			<div class="ipsBox" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px">
				<h3 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">Customer Reviews <span style="font-size:0.75em;font-weight:400;color:#666">({expression="number_format($data['stats']['total'])"})</span></h3>
				{{if count($data['reviews']) === 0}}
				<div class="i-padding_2" style="padding:32px;text-align:center;color:#999">
					<i class="fa-regular fa-star" style="font-size:2em;display:block;margin-bottom:8px;opacity:0.4" aria-hidden="true"></i>
					No reviews yet. Be the first to review this dealer.
				</div>
				{{else}}
					{{foreach $data['reviews'] as $r}}
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
							<div style='{expression="'background:' . $r['avg_color'] . '18;border:1px solid ' . $r['avg_color'] . '40;border-radius:20px;padding:4px 12px;display:flex;align-items:center;gap:6px'"}'>
								<span style='{expression="'color:' . $r['avg_color'] . ';font-weight:800;font-size:1em'"}'>{$r['avg_score']}</span>
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

						{{if $r['dispute_status'] === 'pending_customer' || $r['dispute_status'] === 'pending_admin'}}
						<div style="background:#fef9c3;border:1px solid #fde047;border-radius:6px;padding:8px 12px;font-size:0.82em;color:#854d0e;margin-bottom:8px">
							⚠ This review is currently under admin review.
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

					</div>
					{{endforeach}}
				{{endif}}
			</div>
		</div>
	</div>

</div>
TEMPLATE_EOT,
	],

];
