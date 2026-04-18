<?php
/**
 * Upgrade steps for gddealer v1.0.2 (version integer 10002).
 *
 * Re-seeds the three front templates introduced during 1.0.1 development
 * (dashboardCustomize, dealerRegister, dealerDirectory) for any install
 * where they are still missing. Uses the shared setup/templates_10001.php
 * seeder which performs an existence check before INSERT, so it's safe
 * to run regardless of which state the install is in.
 */

namespace IPS\gddealer\setup\upg_10002;

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
		require \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10001.php';

		return TRUE;
	}
}

class upgrade extends _upgrade {}
