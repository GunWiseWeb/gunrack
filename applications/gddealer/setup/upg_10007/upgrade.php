<?php

namespace IPS\gddealer\setup\upg_10007;

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
		// 1.0.7 — Admin review deletion, standalone All Reviews ACP page,
		// and PM-to-reviewer when dealer responds or admin resolves a
		// dispute. Syncs the updated dealerDetail template (adds Recent
		// Reviews section with delete buttons) and inserts the new
		// allReviews template. No schema changes.
		require \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10007.php';

		return TRUE;
	}
}

class upgrade extends _upgrade {}
