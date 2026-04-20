<?php
/**
 * v1.0.70: Rewrite Help page template with mobile-first CSS Grid layout.
 */

namespace IPS\gddealer\setup\upg_10070;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _upgrade
{
	public function step1(): bool
	{
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10070.php';

		try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
