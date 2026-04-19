<?php
/**
 * v1.0.50: defense-in-depth re-seed of v1.0.49 templates.
 *
 * v1.0.49's upgrade.php shipped without the class _upgrade wrapper, so IPS
 * threw "Class ... could not be loaded" and never ran the template re-seed
 * for installs that had advanced past 10049 but needed the admin reply form
 * fix. This step re-requires templates_10049.php so the supportTicketView
 * (admin) and supportView (dealer) templates land regardless of upgrade path.
 */

namespace IPS\gddealer\setup\upg_10050;

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
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10049.php';

		try { unset( \IPS\Data\Store::i()->applications ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }         catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->themes ); }          catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->frontNavigation ); } catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }               catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }               catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
