<?php

namespace IPS\gddealer\setup\upg_10027;

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
		/* Redesign the Customer Reviews card on dealerProfile: clean avatar
		   circle (gradient + initial when the reviewer has no uploaded
		   photo), compact inline category ratings, per-review tinted
		   rating pill, dealer-name in the response panel header. */
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10027.php';

		try { unset( \IPS\Data\Store::i()->themes ); }         catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }     catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }   catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }              catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }              catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
