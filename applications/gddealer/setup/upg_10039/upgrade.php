<?php
/**
 * v1.0.39: rewrite DealerNav FrontNavigation extension to match the
 * \IPS\core\FrontNavigation\FrontNavigationAbstract signature exactly.
 * The previous version made title()/link() static (LSP violation against
 * the non-static parent), missed the required typeTitle() and active()
 * methods, and used a custom showLink() instead of canView(). Loading
 * the AdminCP "Create new menu item" form 500'd because IPS instantiated
 * the broken child class.
 *
 * No DB schema change. Just need to bust the extensions datastore so
 * IPS reloads the new class definition on the next request.
 */

namespace IPS\gddealer\setup\upg_10039;

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
		try { unset( \IPS\Data\Store::i()->frontNavigation ); } catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
