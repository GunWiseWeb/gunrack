<?php
/**
 * v1.0.32: bug-fix release for v1.0.31 rich-text editor conversion.
 *
 *  1. profile.php rate() called \IPS\Db::i()->insertId(), which does not
 *     exist in IPS 5 — insert() itself returns the new row id. That was
 *     causing a fatal on every new-review submission.
 *
 *  2. Every editor constructor was wrapped in try/catch. If the
 *     EditorLocations datastore was stale the Editor constructor throws
 *     OutOfBoundsException and the catch silently swallowed it, so the
 *     rich-text field rendered as an empty string instead of the editor
 *     toolbar. All try/catch wrappers around editor instantiation were
 *     removed so any misconfiguration surfaces loudly.
 *
 *  This step only clears the extension / template datastores so 1.0.31
 *  installs that cached the pre-fix state pick up the new extensions
 *  registration and the editor renders correctly on first page load.
 */

namespace IPS\gddealer\setup\upg_10032;

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
		try { unset( \IPS\Data\Store::i()->themes ); }       catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
