<?php
/**
 * @brief       GD Dealer Manager — Template sync for v1.0.28 (10028)
 *
 * Overwrites the front/dealers/editReview template to fix the black-page
 * rendering bug. The prior version relied on ipsBox/ipsButton/ipsInput
 * classes for the white surface + control chrome; themes that define a
 * dark body background leaked through, producing an unreadable form.
 *
 * The new template is self-contained: explicit background:#ffffff on the
 * outer card, explicit color + background on every input/select/textarea,
 * fully styled Cancel/Save buttons. Also drops the separate $dealer
 * parameter — dealer_name is nested inside $review now, so template_data
 * reverts to '$review, $editUrl, $cancelUrl, $csrfKey'.
 *
 * Uses \IPS\Db::replace() so the existing row is overwritten on upgrade
 * (the composite unique key on core_theme_templates matches the six key
 * columns below). Safe to re-run.
 */

namespace IPS\gddealer\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$editReviewContent = <<<'TEMPLATE_EOT'
<div class="gdDealerWrapper" style="width:100%;max-width:720px;margin:0 auto;padding:32px 16px;box-sizing:border-box">
	<div style="background:#ffffff;border:0.5px solid #e5e7eb;border-radius:12px;overflow:hidden">

		<div style="padding:18px 24px;border-bottom:0.5px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;background:#fafafa">
			<div>
				<h2 style="margin:0;font-size:18px;font-weight:500;color:#111827">Edit your review</h2>
				{{if $review['dealer_name']}}
				<p style="margin:2px 0 0;font-size:13px;color:#6b7280">for {$review['dealer_name']}</p>
				{{endif}}
			</div>
			<a href="{$cancelUrl}" style="font-size:13px;color:#6b7280;text-decoration:none">Cancel</a>
		</div>

		<form method="post" action="{$editUrl}" style="padding:24px">
			<input type="hidden" name="csrfKey" value="{$csrfKey}">

			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px">
				<div>
					<label style="display:block;font-size:12px;font-weight:500;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em">Pricing accuracy</label>
					<select name="rating_pricing" required style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#111827">
						<option value="5" {{if $review['rating_pricing'] === 5}}selected{{endif}}>★★★★★ Excellent</option>
						<option value="4" {{if $review['rating_pricing'] === 4}}selected{{endif}}>★★★★☆ Good</option>
						<option value="3" {{if $review['rating_pricing'] === 3}}selected{{endif}}>★★★☆☆ Average</option>
						<option value="2" {{if $review['rating_pricing'] === 2}}selected{{endif}}>★★☆☆☆ Poor</option>
						<option value="1" {{if $review['rating_pricing'] === 1}}selected{{endif}}>★☆☆☆☆ Terrible</option>
					</select>
				</div>
				<div>
					<label style="display:block;font-size:12px;font-weight:500;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em">Shipping speed</label>
					<select name="rating_shipping" required style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#111827">
						<option value="5" {{if $review['rating_shipping'] === 5}}selected{{endif}}>★★★★★ Excellent</option>
						<option value="4" {{if $review['rating_shipping'] === 4}}selected{{endif}}>★★★★☆ Good</option>
						<option value="3" {{if $review['rating_shipping'] === 3}}selected{{endif}}>★★★☆☆ Average</option>
						<option value="2" {{if $review['rating_shipping'] === 2}}selected{{endif}}>★★☆☆☆ Poor</option>
						<option value="1" {{if $review['rating_shipping'] === 1}}selected{{endif}}>★☆☆☆☆ Terrible</option>
					</select>
				</div>
				<div>
					<label style="display:block;font-size:12px;font-weight:500;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em">Customer service</label>
					<select name="rating_service" required style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#111827">
						<option value="5" {{if $review['rating_service'] === 5}}selected{{endif}}>★★★★★ Excellent</option>
						<option value="4" {{if $review['rating_service'] === 4}}selected{{endif}}>★★★★☆ Good</option>
						<option value="3" {{if $review['rating_service'] === 3}}selected{{endif}}>★★★☆☆ Average</option>
						<option value="2" {{if $review['rating_service'] === 2}}selected{{endif}}>★★☆☆☆ Poor</option>
						<option value="1" {{if $review['rating_service'] === 1}}selected{{endif}}>★☆☆☆☆ Terrible</option>
					</select>
				</div>
			</div>

			<div style="margin-bottom:24px">
				<label style="display:block;font-size:12px;font-weight:500;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em">Your review</label>
				<textarea name="review_body" rows="6" style="width:100%;box-sizing:border-box;border:1px solid #d1d5db;border-radius:8px;padding:12px;font-size:14px;line-height:1.6;color:#111827;background:#fff;font-family:inherit;resize:vertical">{$review['review_body']}</textarea>
				<p style="margin:6px 0 0;font-size:12px;color:#9ca3af">Be honest and constructive. Dealers may respond publicly.</p>
			</div>

			<div style="display:flex;gap:10px;justify-content:flex-end;border-top:0.5px solid #e5e7eb;padding-top:18px;margin-top:8px">
				<a href="{$cancelUrl}" style="display:inline-flex;align-items:center;padding:10px 18px;border-radius:8px;border:1px solid #d1d5db;color:#374151;text-decoration:none;font-size:14px;font-weight:500;background:#fff">Cancel</a>
				<button type="submit" style="display:inline-flex;align-items:center;padding:10px 20px;border-radius:8px;border:1px solid #2563eb;background:#2563eb;color:#fff;font-size:14px;font-weight:500;cursor:pointer">Save changes</button>
			</div>
		</form>

	</div>
</div>
TEMPLATE_EOT;

/* Pull the existing template_id (if any) so REPLACE INTO matches on the
   primary key and doesn't leave duplicate rows when the unique-index
   composite happens not to cover template_master_key. */
$existingId = NULL;
try
{
	$existingId = (int) \IPS\Db::i()->select( 'template_id', 'core_theme_templates', [
		'template_app=? AND template_location=? AND template_group=? AND template_name=? AND template_set_id=?',
		'gddealer', 'front', 'dealers', 'editReview', 1,
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
	'template_name'     => 'editReview',
	'template_data'     => '$review, $editUrl, $cancelUrl, $csrfKey',
	'template_content'  => $editReviewContent,
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
	/* Fall back to explicit update-or-insert if REPLACE hits a schema
	   variant where the unique-index composite isn't what we expect. */
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
				'template_set_id'   => 1,
				'template_app'      => 'gddealer',
				'template_location' => 'front',
				'template_group'    => 'dealers',
				'template_name'     => 'editReview',
				'template_data'     => '$review, $editUrl, $cancelUrl, $csrfKey',
				'template_content'  => $editReviewContent,
			] );
		}
	}
	catch ( \Exception ) {}
}

try { unset( \IPS\Data\Store::i()->themes ); }   catch ( \Exception ) {}
try { \IPS\Data\Cache::i()->clearAll(); }        catch ( \Exception ) {}
