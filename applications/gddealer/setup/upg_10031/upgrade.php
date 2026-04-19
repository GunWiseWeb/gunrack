<?php
/**
 * v1.0.31 step 1: widen six text columns from TEXT (64KB) to MEDIUMTEXT
 * (16MB) so the IPS rich-text editor can store HTML with inline images
 * / embedded media without truncation. The column widen is done via
 * queries.json (changeColumn with silence_errors) so IPS's installer
 * runs it before this PHP step. Here we just clear caches so the new
 * editor config and template changes pick up cleanly.
 */

namespace IPS\gddealer\setup\upg_10031;

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
		try { unset( \IPS\Data\Store::i()->modules ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
