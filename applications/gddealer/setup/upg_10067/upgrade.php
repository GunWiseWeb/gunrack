<?php
/**
 * v1.0.67: Fix AdminCP menu badges. Replaces dead MenuCounts extension
 * with Application::acpMenuNumber() — the actual IPS 5 mechanism.
 */

namespace IPS\gddealer\setup\upg_10067;

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
		try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
