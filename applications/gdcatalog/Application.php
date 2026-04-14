<?php
/**
 * @brief       GD Master Catalog Application Class
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 */

namespace IPS\gdcatalog;

/**
 * GD Master Catalog Application Class
 *
 * Plugin 1 — Data foundation for the GunRack platform.
 * Ingests product data from six wholesale distributors, resolves conflicts
 * using a strict priority hierarchy, and maintains a single canonical
 * product record per UPC. No pricing data is stored here.
 */
class _Application extends \IPS\Application
{
	/**
	 * @brief Application directory
	 */
	protected static $applicationDirectory = 'gdcatalog';

	/**
	 * @brief Application version
	 */
	public static $version = '1.0.0';

	/**
	 * @brief Application long version (numeric)
	 */
	public static $long_version = 10001;

	/**
	 * @brief Application author
	 */
	public static $author = 'GunRack';

	/**
	 * @brief Application website
	 */
	public static $website = 'https://gunrack.deals';

	/**
	 * @brief Whether this application assigns badges
	 */
	public static $assignBadges = false;

	/**
	 * @brief Default module
	 */
	public $defaultModule = 'catalog';

	/**
	 * Install 'other' items — runs after schema.json tables are created
	 */
	public function installOther()
	{
		/* Seed default distributor feed records and categories via setup/install.php */
		require_once \IPS\ROOT_PATH . '/applications/gdcatalog/setup/install.php';
	}
}
