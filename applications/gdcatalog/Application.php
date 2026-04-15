<?php
/**
 * @brief       GD Master Catalog Application Class
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 */

namespace IPS\gdcatalog;

class _Application extends \IPS\Application
{
	/**
	 * ACP menu icon (Font Awesome name, no `fa-` prefix)
	 */
	protected function get__icon()
	{
		return 'database';
	}

	/**
	 * Install 'other' items — runs after schema.json tables are created
	 */
	public function installOther()
	{
		require_once \IPS\ROOT_PATH . '/applications/gdcatalog/setup/install.php';
	}
}

class Application extends _Application {}
