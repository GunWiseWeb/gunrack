<?php
/**
 * @brief       GD Rebates — IPS Application entry
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Plugin 5 of 12 (Spec Section 8). Aggregates manufacturer rebates for
 * firearms, ammunition, optics, and accessories. Two data sources:
 * (1) nightly scraper of admin-registered manufacturer rebate pages
 * (Section 8.2) and (2) community submissions via /rebates/submit
 * (Section 8.4). Deduplication by SHA256 hash of
 * manufacturer + title + end_date + rebate_amount (Section 8.2.3).
 *
 * IMPORTANT (CLAUDE.md Rule #8): the admin dashboard does NOT make live
 * HTTP calls to manufacturer sites. Scraping runs exclusively inside the
 * scheduled task tasks/scrapeRebates.php.
 */

namespace IPS\gdrebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Application extends \IPS\Application
{
	/**
	 * ACP sidebar glyph. The actual tab icon in IPS v5 is driven by the
	 * language key `menutab__gdrebates_icon` in lang.xml (CLAUDE.md #14),
	 * but this method must still exist with matching visibility/return
	 * type (CLAUDE.md #11) because other IPS surfaces read it.
	 */
	public function get__icon(): string
	{
		return 'tag';
	}

	/**
	 * Delegate post-install work to setup/install.php (template seeding).
	 * IPS calls installOther() after schema + data JSON are imported.
	 */
	public function installOther(): void
	{
		$installer = \IPS\ROOT_PATH . '/applications/gdrebates/setup/install.php';
		if ( is_file( $installer ) )
		{
			require_once $installer;
		}
	}
}

class Application extends _Application {}
