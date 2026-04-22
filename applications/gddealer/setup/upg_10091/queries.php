<?php
/**
 * @brief       GD Dealer Manager — v1.0.91 upgrade step (10090 → 10091).
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 *
 * Fixes the v1.0.90/90a/90b dealerProfile ArgumentCountError permanently.
 *
 * Root cause: the v1.0.90 upgrade applied its rewritten dealerProfile
 * template via \IPS\Db::i()->replace() with template_set_id = 0, which
 * inserted a *second* row at set_id=0 alongside the original set_id=1 row
 * from install.php (19-parameter signature). Theme::getAllTemplates()
 * selects both rows and keys the result by
 * [app][location][group][name], so during compilation the stale set_id=1
 * row overwrote the new set_id=0 row and the compiled class was emitted
 * with the OLD 19-param signature. The controller calls it with 1 arg →
 * ArgumentCountError wrapped by SandboxedTemplate as "Template is throwing
 * an error".
 *
 * This upgrade:
 *   1. UPDATEs every core_theme_templates row for the affected template
 *      keyed on (template_app, template_location, template_group,
 *      template_name) — NO template_set_id in the WHERE clause, so both
 *      the set_id=0 and set_id=1 rows get patched in one statement.
 *   2. Rotates core_themes.set_cache_key so IPS recompiles all themes.
 *   3. Deletes on-disk compiled template files for the `dealers` group —
 *      deleteCompiledTemplate() only unsets the in-memory Store key; the
 *      default FileSystem Store leaves the .php files on disk.
 *   4. Clears Store + Cache.
 *   5. Logs an admin-visible notice to reload PHP-FPM (compiled template
 *      classes are eval'd into worker memory and won't pick up the new
 *      signature until workers restart).
 *
 * Rule 18 (CLAUDE.md): the update() pattern here is the canonical shape
 * for any future template-body change. Never regress to replace() with
 * template_set_id=0.
 */

class gddealer_hook_upg_10091
{
	public static function step1(): array
	{
		$definitions = require \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10090b.php';

		$affectedGroups = [ 'dealers' ];

		/* ---- 1. Apply each template definition ---- */
		foreach ( $definitions as $templateName => $tpl )
		{
			$content = (string) ( $tpl['content'] ?? '' );

			/* Safety: refuse to overwrite live prod template bodies with a
			 * placeholder. templates_10090b.php ships with
			 * __REQUIRES_PROD_CONTENT__ until the real v1.0.90b body is
			 * pasted in. Without this check, a half-finished upgrade would
			 * blank out the dealerProfile page on every site that installs
			 * the tar. */
			if ( str_contains( $content, '__REQUIRES_PROD_CONTENT__' ) )
			{
				\IPS\Log::log(
					"gddealer v1.0.91: skipping template UPDATE for '{$templateName}' — "
					. "templates_10090b.php still contains the __REQUIRES_PROD_CONTENT__ sentinel. "
					. "Paste the production template body into setup/templates_10090b.php and re-run "
					. "the upgrade, or run tools/fix_templates_v10091.php directly on the server.",
					'gddealer_upg_10091'
				);
				continue;
			}

			\IPS\Db::i()->update(
				'core_theme_templates',
				[
					'template_data'    => (string) $tpl['data'],
					'template_content' => $content,
					'template_updated' => time(),
				],
				[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
				  'gddealer', 'front', 'dealers', $templateName ]
			);
		}

		/* ---- 2. Rotate every theme's compiled-file lookup key ---- */
		\IPS\Db::i()->update( 'core_themes', [ 'set_cache_key' => md5( microtime() . mt_rand() ) ] );

		/* ---- 3. Delete on-disk compiled template files for affected groups ---- */
		foreach ( $affectedGroups as $group )
		{
			foreach ( glob( \IPS\ROOT_PATH . "/datastore/template_*_{$group}.*.php" ) ?: [] as $f )
			{
				@unlink( $f );
			}

			try { \IPS\Theme::deleteCompiledTemplate( 'gddealer', 'front', $group ); }
			catch ( \Throwable ) {}
		}

		/* ---- 4. Clear Store and Cache ---- */
		try { \IPS\Data\Store::i()->clearAll(); } catch ( \Throwable ) {}
		try { \IPS\Data\Cache::i()->clearAll(); } catch ( \Throwable ) {}

		/* ---- 5. Tell the admin to reload PHP-FPM ---- */
		return [
			'gddealer v1.0.91 upgrade complete. ⚠ You MUST reload PHP-FPM across every '
			. 'installed PHP version for the new template signature to take effect — '
			. 'compiled template classes are eval\'d into worker memory and will not pick '
			. 'up the new signature until workers restart. On AlmaLinux/DirectAdmin: '
			. 'systemctl reload php-fpm74 php-fpm80 php-fpm81 php-fpm82 php-fpm83'
		];
	}
}
