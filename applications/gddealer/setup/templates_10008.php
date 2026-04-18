<?php
/**
 * Template patch for upg_10008.
 *
 * Updates the dealerProfile template to reference deadline_formatted
 * instead of the raw dispute_deadline column for human-readable dates.
 * Uses targeted string replacement instead of full template replacement
 * to avoid template content drift. Called from upg_10008/upgrade.php.
 */

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

try
{
	$content = (string) \IPS\Db::i()->select( 'template_content', 'core_theme_templates', [
		'template_app=? AND template_name=?', 'gddealer', 'dealerProfile'
	] )->first();

	$patched = str_replace(
		"customerDispute['dispute_deadline']",
		"customerDispute['deadline_formatted']",
		$content
	);

	if ( $patched !== $content )
	{
		\IPS\Db::i()->update( 'core_theme_templates', [
			'template_content' => $patched,
		], [ 'template_app=? AND template_name=?', 'gddealer', 'dealerProfile' ] );
	}
}
catch ( \Exception ) {}
