<?php
/**
 * v1.0.54: hotfix — stock replies/actions list SELECT referencing unjoined alias.
 * Cache clears only, no schema or template changes.
 */

namespace IPS\gddealer\setup\upg_10054;

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
		try { unset( \IPS\Data\Store::i()->applications ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }         catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->themes ); }          catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }               catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }               catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
