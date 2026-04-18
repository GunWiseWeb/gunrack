<?php

namespace IPS\gddealer\setup\upg_10009;

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
		// 1.0.9 — Re-enabled IPS inline notifications and Messenger PMs
		// using the correct IPS 5 API patterns. Notifications extension
		// now extends NotificationsAbstract with configurationOptions()
		// and parse_KEY() methods. Messenger uses createItem(sender,
		// ipAddress, DateTime) and the commentClass::create() pattern.
		// Emails are still sent alongside as a fallback. No schema
		// changes.
		return TRUE;
	}
}

class upgrade extends _upgrade {}
