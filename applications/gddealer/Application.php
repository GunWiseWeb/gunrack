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
	 * Matches \IPS\Application::get__icon() signature — public visibility
	 * and `: string` return type. Anything else is silently ignored by the
	 * menu renderer and the tab shows with no glyph. The ACP sidebar also
	 * reads menutab__gddealer_icon from lang.xml (Rule #14); both pathways
	 * are present to be safe.
	 */
	public function get__icon(): string
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
