<?php
/**
 * v1.0.49: admin reply form visibility patch — cache clears only.
 */

namespace IPS\gddealer\setup\upg_10049;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

function step1(): bool
{
	try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Exception ) {}
	try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
	try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}

	return TRUE;
}
