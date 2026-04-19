<?php

namespace IPS\gddealer\setup\upg_10029;

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
		/* No schema or template changes — front controller now does bare
		   redirects (no message argument) to skip IPS's interstitial
		   "Please wait while we transfer you..." page on save/rate/dispute
		   actions. Just clear caches. */
		try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }      catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
