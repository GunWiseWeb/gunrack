<?php

namespace IPS\gddealer\setup\upg_10017;

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
		$appPath = \IPS\Application::load( 'gddealer' )->getApplicationPath();

		/* Fix extensions.json — values must be fully qualified class names. */
		$extensionsJson = [
			'core' => [
				'ContentRouter' => [
					'DealerFollow' => 'IPS\\gddealer\\extensions\\core\\ContentRouter\\DealerFollow',
				],
				'Notifications' => [
					'DealerNotifications' => 'IPS\\gddealer\\extensions\\core\\Notifications\\DealerNotifications',
				],
				'EmailTemplates' => [
					'DealerEmails' => 'IPS\\gddealer\\extensions\\core\\EmailTemplates\\DealerEmails',
				],
				'FrontNavigation' => [
					'DealerNav' => 'IPS\\gddealer\\extensions\\core\\FrontNavigation\\DealerNav',
				],
			],
		];
		@file_put_contents(
			$appPath . '/data/extensions.json',
			json_encode( $extensionsJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
		);

		/* Fix DealerNotifications.php — add missing `use IPS\Member` and
		   replace any lingering \IPS\Member::load(...) author with NULL. */
		$notifFile = $appPath . '/extensions/core/Notifications/DealerNotifications.php';
		if ( file_exists( $notifFile ) )
		{
			$content = file_get_contents( $notifFile );

			if ( strpos( $content, 'use IPS\\Member;' ) === FALSE )
			{
				$content = str_replace(
					'use IPS\\Extensions\\NotificationsAbstract;',
					"use IPS\\Extensions\\NotificationsAbstract;\nuse IPS\\Member;",
					$content
				);
			}

			$content = preg_replace(
				"/'author'\s*=>\s*\\\\IPS\\\\Member::load\([^)]+\),/",
				"'author'  => NULL,",
				$content
			);

			@file_put_contents( $notifFile, $content );
		}

		/* Clear extension cache so IPS re-scans. */
		try { unset( \IPS\Data\Store::i()->extensions ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }  catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
