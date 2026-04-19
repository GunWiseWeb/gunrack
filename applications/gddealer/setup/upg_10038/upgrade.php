<?php
/**
 * v1.0.38: re-seed admin disputeQueue template with status filter bar,
 * archive/collapse for resolved disputes, and updated template_data
 * signature ($rows, $filterStatus, $counts, $baseUrl).
 */

namespace IPS\gddealer\setup\upg_10038;

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
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10038.php';

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
