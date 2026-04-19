<?php
/**
 * v1.0.37: re-seed admin templates that render editor-stored HTML so
 * the |raw filter is applied. v1.0.36 only covered FRONT templates;
 * the admin Disputed Reviews / All Reviews / Dealer Detail pages
 * still showed literal <p> tags.
 *
 * Controller side (admin/dealers/dealers.php) was also updated to
 * pass stored editor HTML through \IPS\Text\Parser::parseStatic so
 * embedded attachments hydrate on admin views.
 */

namespace IPS\gddealer\setup\upg_10037;

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
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10037.php';

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
