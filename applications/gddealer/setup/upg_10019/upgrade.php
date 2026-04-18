<?php

namespace IPS\gddealer\setup\upg_10019;

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
		/* Re-seed email templates from data/emails.xml — in case a prior
		   upgrade ran before emails.xml landed on disk. */
		try
		{
			\IPS\Application::load( 'gddealer' )->installEmailTemplates();
		}
		catch ( \Exception ) {}

		try { unset( \IPS\Data\Store::i()->extensions ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }  catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
