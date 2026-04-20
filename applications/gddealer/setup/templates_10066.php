<?php
/**
 * v1.0.66: Re-seed dealerReviews template with sub-nav tabs,
 * filter bar, and pagination.
 */

namespace IPS\gddealer\setup;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$installPath = \IPS\ROOT_PATH . '/applications/gddealer/setup/install.php';
$installCode = file_get_contents( $installPath );

preg_match_all(
	"/\\\$gddealerTemplates\[\]\s*=\s*\[.*?'template_name'\s*=>\s*'(\w+)'.*?\];\s*/s",
	$installCode,
	$matches,
	PREG_SET_ORDER
);

$targetNames = [ 'dealerReviews' ];

foreach ( $matches as $m )
{
	$tplName = $m[1];
	if ( !\in_array( $tplName, $targetNames, true ) )
	{
		continue;
	}

	$block = $m[0];

	$extract = static function ( string $key ) use ( $block ): string
	{
		if ( preg_match( "/'$key'\s*=>\s*'((?:[^'\\\\]|\\\\.)*)'/s", $block, $km ) )
		{
			return $km[1];
		}
		if ( preg_match( "/'$key'\s*=>\s*<<<'TEMPLATE_EOT'\n(.*?)\nTEMPLATE_EOT/s", $block, $km ) )
		{
			return $km[1];
		}
		return '';
	};

	\IPS\Db::i()->replace( 'core_theme_templates', [
		'template_set_id' => 1,
		'template_app'    => $extract( 'app' ),
		'template_location' => $extract( 'location' ),
		'template_group'  => $extract( 'group' ),
		'template_name'   => $tplName,
		'template_data'   => $extract( 'template_data' ),
		'template_content' => $extract( 'template_content' ),
	] );
}
