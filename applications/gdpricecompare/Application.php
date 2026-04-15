<?php
/**
 * @brief       GD Price Comparison Application Class
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare;

class _Application extends \IPS\Application
{
	/**
	 * ACP menu icon (Font Awesome name, no `fa-` prefix).
	 *
	 * The ACP sidebar tab glyph is driven by the language key
	 * menutab__gdpricecompare_icon in lang.xml (CLAUDE.md Rule #14);
	 * this method exists for other parts of IPS that query it.
	 */
	protected function get__icon(): string
	{
		return 'tags';
	}

	/**
	 * Install 'other' items — runs after schema.json tables are created.
	 * Seeds templates into core_theme_templates and loads compliance seed data.
	 */
	public function installOther()
	{
		require_once \IPS\ROOT_PATH . '/applications/gdpricecompare/setup/install.php';
	}
}

class Application extends _Application {}
