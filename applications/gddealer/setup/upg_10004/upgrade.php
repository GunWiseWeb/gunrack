<?php

namespace IPS\gddealer\setup\upg_10004;

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
		// 1.0.4 — Redesigned dealerProfile review card. Updates the
		// template content in core_theme_templates so upgraded installs
		// pick up the new layout. Controller manage() in
		// modules/front/dealers/profile.php was updated to populate the
		// new per-review fields (reviewer_name, reviewer_avatar,
		// avg_score, created_at_formatted, response_at, stars_*).
		require \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10004.php';

		return TRUE;
	}
}

class upgrade extends _upgrade {}
