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
	 *
	 * Matches \IPS\Application::get__icon() signature — public visibility
	 * and `: string` return type. Anything else is silently ignored by the
	 * menu renderer and the tab shows with no glyph.
	 */
	public function get__icon(): string
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
