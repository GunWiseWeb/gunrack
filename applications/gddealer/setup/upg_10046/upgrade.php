<?php
/**
 * v1.0.46: dealer-side support ticket UI.
 *
 * - Re-seeds dealerShell template (new Support tab).
 * - Seeds three new front templates: supportList, supportNew, supportView.
 */

namespace IPS\gddealer\setup\upg_10046;

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
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10046.php';

		try { unset( \IPS\Data\Store::i()->applications ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }         catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->themes ); }          catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->frontNavigation ); } catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->furl_configuration ); } catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }               catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }               catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
