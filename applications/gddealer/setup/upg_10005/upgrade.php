<?php

namespace IPS\gddealer\setup\upg_10005;

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
		// 1.0.5 — IPS notification when a new review is submitted to a
		// dealer, plus an unread badge on the My Reviews dashboard tab.
		// queries.json adds gd_dealer_feed_config.last_review_check.
		// dealerShell template is updated via templates_10005.php so the
		// new $dealer['new_reviews'] badge renders on upgraded installs.
		require \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10005.php';

		return TRUE;
	}
}

class upgrade extends _upgrade {}
