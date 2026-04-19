<?php

namespace IPS\gddealer\setup\upg_10028;

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
		/* Overwrite the front/dealers/editReview template: fixes the
		   black-page bug by giving the form an explicit white surface
		   and explicit field colors (no reliance on ipsBox/ipsInput/
		   ipsButton chrome). */
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10028.php';

		try { unset( \IPS\Data\Store::i()->themes ); }         catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }     catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }   catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }              catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }              catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
