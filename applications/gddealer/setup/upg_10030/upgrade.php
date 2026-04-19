<?php

namespace IPS\gddealer\setup\upg_10030;

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
		/* v1.0.30: dealer response edit/delete + timezone-aware
		   timestamps. Overwrites dealerReviews + dealerProfile front
		   templates via \IPS\Db::i()->replace(). No schema changes —
		   dealer_response and response_at columns already allow NULL. */
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10030.php';

		try { unset( \IPS\Data\Store::i()->themes ); }       catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }      catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
