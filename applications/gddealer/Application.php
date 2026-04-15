<?php
/**
 * @brief       GD Dealer Manager Application Class
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 */

namespace IPS\gddealer;

class _Application extends \IPS\Application
{
	/**
	 * ACP menu icon (Font Awesome name, no `fa-` prefix).
	 *
	 * The ACP sidebar tab glyph is actually driven by the language key
	 * menutab__gddealer_icon in lang.xml (Rule #14); this method exists
	 * for other parts of IPS that query it.
	 */
	protected function get__icon(): string
	{
		return 'store';
	}

	/**
	 * Install 'other' items — runs after schema.json tables are created.
	 * Seeds templates into core_theme_templates and creates default subscription
	 * tier records.
	 */
	public function installOther()
	{
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/install.php';
	}
}

class Application extends _Application {}
