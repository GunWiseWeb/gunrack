<?php

namespace IPS\gddealer\setup\upg_10017;

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
		try { unset( \IPS\Data\Store::i()->extensions ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }  catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
