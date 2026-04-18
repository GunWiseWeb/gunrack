<?php

namespace IPS\gddealer\setup\upg_10013;

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
		/* v1.0.13: force IPS to rescan the extensions directory so the
		   gddealer Notifications / EmailTemplates / FrontNavigation /
		   ContentRouter extensions register on upgraded installs that
		   cached the extension list before data/extensions.json existed. */
		try { unset( \IPS\Data\Store::i()->extensions ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }  catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
