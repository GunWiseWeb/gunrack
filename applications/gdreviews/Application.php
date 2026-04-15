<?php
/**
 * @brief       GD Product Reviews Application Class
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 */

namespace IPS\gdreviews;

class _Application extends \IPS\Application
{
	/**
	 * ACP menu icon (FontAwesome name, no `fa-` prefix).
	 *
	 * The ACP sidebar tab glyph is actually driven by the language key
	 * menutab__gdreviews_icon in lang.xml (CLAUDE.md Rule #14). This method
	 * exists for other parts of IPS core that read get__icon(), and matches
	 * the signature of \IPS\Application::get__icon() exactly (public,
	 * string return — CLAUDE.md Rule #11).
	 */
	public function get__icon(): string
	{
		return 'star';
	}

	/**
	 * Install 'other' items — runs after schema.json tables are created.
	 * Seeds ACP and front-end templates into core_theme_templates directly
	 * (CLAUDE.md Rule #4 — never use data/theme.xml, the XML importer
	 * mangles nowdoc comment syntax).
	 */
	public function installOther()
	{
		require_once \IPS\ROOT_PATH . '/applications/gdreviews/setup/install.php';
	}
}

class Application extends _Application {}
