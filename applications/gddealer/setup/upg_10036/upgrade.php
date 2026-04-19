<?php
/**
 * v1.0.36: re-seed front review templates so editor-stored HTML renders
 * through the |raw filter instead of being auto-escaped.
 *
 * The dashboard dealerReviews template had one last {$r['review_body']}
 * without |raw — IPS's auto-escape rendered <p>hello</p> as literal
 * text. Fixed at source and re-seeded here.
 */

namespace IPS\gddealer\setup\upg_10036;

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
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10036.php';

		try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->themes ); }       catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
