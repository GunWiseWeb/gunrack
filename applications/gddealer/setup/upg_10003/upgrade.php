<?php

namespace IPS\gddealer\setup\upg_10003;

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
		// 1.0.3 — code-only fix: rate() in modules/front/dealers/profile.php
		// was inserting a non-existent 'disputed' column on gd_dealer_ratings.
		// Replaced with the correct 'dispute_status' => 'none'. No schema or
		// data migration required.
		return TRUE;
	}
}

class upgrade extends _upgrade {}
