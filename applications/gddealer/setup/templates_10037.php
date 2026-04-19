<?php
/**
 * v1.0.37 admin template re-seed: the admin Disputed Reviews page
 * (and allReviews / dealerDetail) rendered editor-stored HTML without
 * |raw, so IPS's template auto-escape turned <p>hello</p> into literal
 * text. v1.0.36 only re-seeded the FRONT templates; admin was missed.
 *
 * install.php's $gddealerTemplates array is the source of truth — it
 * has |raw applied everywhere. Extract the three admin templates that
 * render editor-backed fields and \IPS\Db::i()->replace() the rows in
 * core_theme_templates so existing installs pick up the corrected
 * markup without executing install.php (which would duplicate-insert
 * every template).
 */

namespace IPS\gddealer\setup\templates_10037;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$installPath = \IPS\ROOT_PATH . '/applications/gddealer/setup/install.php';
$installSrc  = @file_get_contents( $installPath );
if ( !$installSrc )
{
	return;
}

/* Extract every template definition literal from install.php. Matches
   the canonical [set_id,app,location,group,template_name,template_data,
   template_content <<<TEMPLATE_EOT...TEMPLATE_EOT] shape. */
preg_match_all(
	"/\[\s*\n\s*'set_id'\s*=>\s*(\d+),\s*\n\s*'app'\s*=>\s*'([^']+)',\s*\n\s*'location'\s*=>\s*'([^']+)',\s*\n\s*'group'\s*=>\s*'([^']+)',\s*\n\s*'template_name'\s*=>\s*'([^']+)',\s*\n\s*'template_data'\s*=>\s*'([^']*)',\s*\n\s*'template_content'\s*=>\s*<<<'TEMPLATE_EOT'\n(.*?)\nTEMPLATE_EOT/s",
	$installSrc,
	$matches,
	PREG_SET_ORDER
);

/* Only re-seed admin templates that render editor-backed fields.
   dashboard / dealerList / mrrDashboard / unmatchedList / disputeCounts
   don't touch review/dispute prose so they don't need the |raw fix. */
$targets = [ 'dealerDetail', 'allReviews', 'disputeQueue' ];

foreach ( $matches as $m )
{
	$location     = $m[3];
	$templateName = $m[5];

	if ( $location !== 'admin' )          { continue; }
	if ( !in_array( $templateName, $targets, TRUE ) ) { continue; }

	$row = [
		'template_set_id'   => (int) $m[1],
		'template_app'      => $m[2],
		'template_location' => $location,
		'template_group'    => $m[4],
		'template_name'     => $templateName,
		'template_data'     => $m[6],
		'template_content'  => $m[7],
	];

	/* Carry the existing template_id forward so replace() overwrites
	   the existing row rather than appending a duplicate. */
	try
	{
		$existingId = (int) \IPS\Db::i()->select( 'template_id', 'core_theme_templates', [
			'template_app=? AND template_location=? AND template_group=? AND template_name=? AND template_set_id=?',
			$row['template_app'], $row['template_location'], $row['template_group'],
			$row['template_name'], $row['template_set_id'],
		] )->first();
		if ( $existingId )
		{
			$row['template_id'] = $existingId;
		}
	}
	catch ( \Exception ) {}

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
				$row['template_app'], $row['template_location'], $row['template_group'], $row['template_name'],
			] )->first();

			if ( $exists > 0 )
			{
				\IPS\Db::i()->update( 'core_theme_templates', [
					'template_data'    => $row['template_data'],
					'template_content' => $row['template_content'],
				], [
					'template_app=? AND template_location=? AND template_group=? AND template_name=?',
					$row['template_app'], $row['template_location'], $row['template_group'], $row['template_name'],
				] );
			}
			else
			{
				\IPS\Db::i()->insert( 'core_theme_templates', $row );
			}
		}
		catch ( \Exception ) {}
	}
}
