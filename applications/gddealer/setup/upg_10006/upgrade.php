<?php

namespace IPS\gddealer\setup\upg_10006;

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
		// 1.0.6 — Full dispute workflow notifications. No schema changes.
		// Adds IPS notifications for review_disputed, dispute_admin_review,
		// dispute_upheld, and dispute_dismissed. Code-only change.
		return TRUE;
	}
}

class upgrade extends _upgrade {}
