<?php
/**
 * v1.0.41 template re-seed: admin disputeQueue + front dealerReviews +
 * front dealerProfile gain a "Dispute history" timeline block and
 * (for dealerProfile) a customer_response/evidence pre-fill in the
 * reply editor when the customer revisits after an admin edit-request.
 */

namespace IPS\gddealer\setup\templates_10041;

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

preg_match_all(
	"/\[\s*\n\s*'set_id'\s*=>\s*(\d+),\s*\n\s*'app'\s*=>\s*'([^']+)',\s*\n\s*'location'\s*=>\s*'([^']+)',\s*\n\s*'group'\s*=>\s*'([^']+)',\s*\n\s*'template_name'\s*=>\s*'([^']+)',\s*\n\s*'template_data'\s*=>\s*'([^']*)',\s*\n\s*'template_content'\s*=>\s*<<<'TEMPLATE_EOT'\n(.*?)\nTEMPLATE_EOT/s",
	$installSrc,
	$matches,
	PREG_SET_ORDER
);

$targets = [ 'disputeQueue', 'dealerReviews', 'dealerProfile' ];

foreach ( $matches as $m )
{
	$templateName = $m[5];
	if ( !in_array( $templateName, $targets, TRUE ) ) { continue; }

	$row = [
		'template_set_id'   => (int) $m[1],
		'template_app'      => $m[2],
		'template_location' => $m[3],
		'template_group'    => $m[4],
		'template_name'     => $templateName,
		'template_data'     => $m[6],
		'template_content'  => $m[7],
	];

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
