<?php

namespace IPS\gddealer\setup\upg_10024;

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
		try
		{
			$columns = [];
			foreach ( \IPS\Db::i()->query( "SHOW COLUMNS FROM `gd_dealer_dispute_counts`" ) as $col )
			{
				$columns[] = $col['Field'];
			}

			if ( !in_array( 'bonus', $columns, true ) )
			{
				\IPS\Db::i()->query(
					"ALTER TABLE `gd_dealer_dispute_counts` ADD COLUMN `bonus` INT UNSIGNED NOT NULL DEFAULT 0"
				);
			}
		}
		catch ( \Exception ) {}

		try { \IPS\Application::load( 'gddealer' )->installEmailTemplates(); } catch ( \Exception ) {}

		try { unset( \IPS\Data\Store::i()->extensions ); }     catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }   catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->emailTemplates ); } catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }              catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }              catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
