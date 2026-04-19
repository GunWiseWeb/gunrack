<?php
/**
 * @brief       GD Dealer Manager — Template sync for v1.0.30 (10030)
 *
 * Overwrites two front templates on upgrade:
 *   1. dealerReviews — dashboard review list. Adds inline edit/delete
 *      controls on each dealer response block and a bare-redirect
 *      deleteResponse flow. Response timestamps are already rendered
 *      timezone-aware by the controller (\IPS\DateTime::ts()).
 *   2. dealerProfile — public dealer profile. Refreshed so the
 *      response_at timestamp under the dealer response card reflects
 *      the viewer's timezone (controller now formats via DateTime).
 *
 * Uses \IPS\Db::i()->replace() keyed on the existing template_id so
 * the upgrade overwrites the row in place. Safe to re-run.
 */

namespace IPS\gddealer\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$dealerReviewsContent = <<<'TEMPLATE_EOT'
TEMPLATE_EOT;

$dealerProfileContent = <<<'TEMPLATE_EOT'
TEMPLATE_EOT;

$templates = [
	[
		'template_name' => 'dealerReviews',
		'template_data' => '$data, $csrfKey',
		'template_content' => $dealerReviewsContent,
	],
	[
		'template_name' => 'dealerProfile',
		'template_data' => '$dealer, $stats, $reviews, $canRate, $alreadyRated, $loginRequired, $rateUrl, $csrfKey, $loginUrl, $customerDispute, $guidelinesUrl',
		'template_content' => $dealerProfileContent,
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
		/* Fallback explicit update/insert if the unique-index composite
		   doesn't match the REPLACE semantics on this schema variant. */
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
