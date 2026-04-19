<?php

namespace IPS\gddealer\setup\upg_10023;

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
		try { \IPS\Application::load( 'gddealer' )->installEmailTemplates(); } catch ( \Exception ) {}

		$keys = [
			'dispute_customer_responded',
		];

		foreach ( $keys as $key )
		{
			try
			{
				\IPS\Db::i()->select( 'notification_key', 'core_notification_defaults',
					[ 'notification_key=?', $key ] )->first();
			}
			catch ( \UnderflowException )
			{
				try
				{
					\IPS\Db::i()->insert( 'core_notification_defaults', [
						'notification_key' => $key,
						'default'          => 'inline,email',
						'disabled'         => '',
					] );
				}
				catch ( \Exception ) {}
			}
		}

		try { unset( \IPS\Data\Store::i()->extensions ); }     catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }   catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->emailTemplates ); } catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }              catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }              catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
