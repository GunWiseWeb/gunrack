<?php
/**
 * @brief       GD Dealer Manager — Template sync for v1.0.11 (10011)
 *
 * Updates:
 * - Seeds the new front/dealers/editReview template
 * - Patches front/dealers/dealerProfile to add the "Edit your review" link
 *   to each review card when the logged-in viewer is the author and the
 *   review is not under active dispute.
 */

namespace IPS\gddealer\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/* ── Seed front/dealers/editReview ── */
$editReviewContent = <<<'TEMPLATE_EOT'
<div style="max-width:700px;margin:0 auto;padding:24px 16px">
	<div class="ipsBox">
		<h2 class="ipsBox__header" style="margin:0;padding:14px 18px;border-bottom:1px solid var(--i-border-color,#f0f0f0);font-size:1em;font-weight:700">Edit Your Review</h2>
		<div style="padding:24px">
			<form method="post" action="{$editUrl}">
				<input type="hidden" name="csrfKey" value="{$csrfKey}">

				<div style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap">
					<div style="flex:1 1 140px">
						<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Pricing Accuracy</label>
						<select name="rating_pricing" class="ipsInput ipsInput--select" required>
							<option value="5" {{if $review['rating_pricing'] === 5}}selected{{endif}}>★★★★★ Excellent</option>
							<option value="4" {{if $review['rating_pricing'] === 4}}selected{{endif}}>★★★★☆ Good</option>
							<option value="3" {{if $review['rating_pricing'] === 3}}selected{{endif}}>★★★☆☆ Average</option>
							<option value="2" {{if $review['rating_pricing'] === 2}}selected{{endif}}>★★☆☆☆ Poor</option>
							<option value="1" {{if $review['rating_pricing'] === 1}}selected{{endif}}>★☆☆☆☆ Terrible</option>
						</select>
					</div>
					<div style="flex:1 1 140px">
						<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Shipping Speed</label>
						<select name="rating_shipping" class="ipsInput ipsInput--select" required>
							<option value="5" {{if $review['rating_shipping'] === 5}}selected{{endif}}>★★★★★ Excellent</option>
							<option value="4" {{if $review['rating_shipping'] === 4}}selected{{endif}}>★★★★☆ Good</option>
							<option value="3" {{if $review['rating_shipping'] === 3}}selected{{endif}}>★★★☆☆ Average</option>
							<option value="2" {{if $review['rating_shipping'] === 2}}selected{{endif}}>★★☆☆☆ Poor</option>
							<option value="1" {{if $review['rating_shipping'] === 1}}selected{{endif}}>★☆☆☆☆ Terrible</option>
						</select>
					</div>
					<div style="flex:1 1 140px">
						<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Customer Service</label>
						<select name="rating_service" class="ipsInput ipsInput--select" required>
							<option value="5" {{if $review['rating_service'] === 5}}selected{{endif}}>★★★★★ Excellent</option>
							<option value="4" {{if $review['rating_service'] === 4}}selected{{endif}}>★★★★☆ Good</option>
							<option value="3" {{if $review['rating_service'] === 3}}selected{{endif}}>★★★☆☆ Average</option>
							<option value="2" {{if $review['rating_service'] === 2}}selected{{endif}}>★★☆☆☆ Poor</option>
							<option value="1" {{if $review['rating_service'] === 1}}selected{{endif}}>★☆☆☆☆ Terrible</option>
						</select>
					</div>
				</div>

				<div style="margin-bottom:20px">
					<label style="display:block;font-size:0.85em;font-weight:600;margin-bottom:6px">Your Review</label>
					<textarea name="review_body" rows="5" class="ipsInput ipsInput--text" style="width:100%;box-sizing:border-box;border:1px solid var(--i-border-color,#ccc);border-radius:4px;padding:8px;font-size:0.9em">{$review['review_body']}</textarea>
				</div>

				<div style="display:flex;gap:8px">
					<button type="submit" class="ipsButton ipsButton--primary">Save Changes</button>
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
			'template_data'    => '$review, $editUrl, $cancelUrl, $csrfKey',
			'template_content' => $editReviewContent,
		], [
			'template_app=? AND template_location=? AND template_group=? AND template_name=?',
			'gddealer', 'front', 'dealers', 'editReview',
		] );
	}
	else
	{
		\IPS\Db::i()->insert( 'core_theme_templates', [
			'template_set_id'  => 1,
			'template_app'     => 'gddealer',
			'template_location'=> 'front',
			'template_group'   => 'dealers',
			'template_name'    => 'editReview',
			'template_data'    => '$review, $editUrl, $cancelUrl, $csrfKey',
			'template_content' => $editReviewContent,
		] );
	}
}
catch ( \Exception ) {}

/* ── Patch dealerProfile: add "Edit your review" link after dealer response block ── */
try
{
	$existing = \IPS\Db::i()->select( 'template_content', 'core_theme_templates', [
		'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		'gddealer', 'front', 'dealers', 'dealerProfile',
	] )->first();

	$needle = "<p style=\"margin:0;font-size:0.9em;line-height:1.5;color:#374151\">{\$r['dealer_response']}</p>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t{{endif}}\n\n\t\t\t\t\t</div>\n\t\t\t\t\t{{endforeach}}";

	$replacement = "<p style=\"margin:0;font-size:0.9em;line-height:1.5;color:#374151\">{\$r['dealer_response']}</p>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t{{endif}}\n\n\t\t\t\t\t\t{{if \$r['edit_review_url']}}\n\t\t\t\t\t\t<div style=\"margin-top:10px\">\n\t\t\t\t\t\t\t<a href=\"{\$r['edit_review_url']}\" style=\"font-size:0.82em;color:var(--gd-primary,#2563eb);font-weight:600\">\n\t\t\t\t\t\t\t\t<i class=\"fa-solid fa-pen-to-square\" aria-hidden=\"true\"></i> Edit your review\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t{{endif}}\n\n\t\t\t\t\t</div>\n\t\t\t\t\t{{endforeach}}";

	$updated = str_replace( $needle, $replacement, $existing );

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
