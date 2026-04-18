<?php
/**
 * Upgrade steps for gddealer v1.0.0 (version integer 10000).
 *
 * Baseline — initial install, no migrations needed. setup/install.php seeds
 * the full schema from data/schema.json on fresh installs, and upg_10001 (and
 * later) run on upgrades from this baseline.
 *
 * To add a new version:
 *   1. Add to data/versions.json: "XXXXX": "X.X.X"
 *   2. Create setup/upg_XXXXX/queries.json with ALTER TABLE statements
 *   3. Create setup/upg_XXXXX/upgrade.php with data migrations
 *   4. Bump app_version and app_long_version in data/application.json
 */

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}
