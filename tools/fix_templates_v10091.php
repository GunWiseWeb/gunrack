<?php
/**
 * gddealer v1.0.91 template recovery script.
 *
 * Reproduces the exact manual recovery that was run on production to restore
 * the dealerProfile page after the v1.0.90/90a/90b ArgumentCountError.
 *
 * Root cause (see CLAUDE.md Rule 18): the v1.0.90 upgrade step used
 * `\IPS\Db::i()->replace()` with `template_set_id = 0`, which inserted a
 * second row at set_id=0 while the original set_id=1 row from install.php
 * (with the stale 19-parameter signature) was still present. IPS's
 * Theme::getAllTemplates() selects both rows and keys by
 * [app][location][group][name], so during compilation the stale row
 * overwrote the new one and the compiled class was emitted with the OLD
 * signature. Calling it with the new 1-param signature threw
 * ArgumentCountError, wrapped by SandboxedTemplate as the generic
 * "Template is throwing an error" message.
 *
 * Run:
 *   cd /home/gunrack/domains/gunrack.deals/public_html
 *   php ./tools/fix_templates_v10091.php
 *   systemctl reload php-fpm74 php-fpm80 php-fpm81 php-fpm82 php-fpm83
 *
 * Idempotent — safe to re-run.
 */

define( 'SUITE_UNIQUE_KEY', 'recovery' );
chdir( '/home/gunrack/domains/gunrack.deals/public_html' );
require_once 'init.php';
\IPS\IPS::init();

$app      = 'gddealer';
$location = 'front';
$group    = 'dealers';
$name     = 'dealerProfile';

echo "gddealer v1.0.91 template recovery\n";
echo "==================================\n\n";

/* ---- 1. Sync every core_theme_templates row for this template to the 1-param $data signature ---- */

$rows = \IPS\Db::i()->select(
	'template_set_id, template_data, LENGTH(template_content) AS len',
	'core_theme_templates',
	[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
	  $app, $location, $group, $name ]
)->setKeyField( 'template_set_id' );

echo "Rows found for {$app}/{$location}/{$group}/{$name}:\n";
foreach ( $rows as $setId => $row )
{
	echo "  set_id={$setId}  data='{$row['template_data']}'  content_len={$row['len']}\n";
}

if ( iterator_count( $rows ) === 0 )
{
	fwrite( STDERR, "ERROR: no rows found for {$app}/{$location}/{$group}/{$name}. Has install.php ever seeded this template?\n" );
	exit( 1 );
}

/*
 * The NEW v1.0.90b content must be available to this script. On production,
 * it lives in applications/gddealer/setup/templates_10090b.php as a
 * PHP file returning the content string. If the upgrade file was never
 * committed, paste the current on-disk template content here before running.
 */
$templateFile = \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10090b.php';
if ( !file_exists( $templateFile ) )
{
	fwrite( STDERR, "ERROR: {$templateFile} not found. Cannot recover without the v1.0.90b template body.\n" );
	exit( 2 );
}

$templates = require $templateFile;
if ( !is_array( $templates ) || empty( $templates[ $name ]['content'] ) )
{
	fwrite( STDERR, "ERROR: {$templateFile} did not return a valid template definition for '{$name}'.\n" );
	exit( 3 );
}

$newData    = $templates[ $name ]['data'];     /* e.g. '$data' */
$newContent = $templates[ $name ]['content'];

$updated = \IPS\Db::i()->update(
	'core_theme_templates',
	[
		'template_data'    => $newData,
		'template_content' => $newContent,
		'template_updated' => time(),
	],
	[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
	  $app, $location, $group, $name ]
);

echo "\nUpdated {$updated} row(s) in core_theme_templates to template_data='{$newData}'.\n";

/* ---- 2. Delete on-disk compiled template files for this group ---- */

$deleted = 0;
foreach ( glob( \IPS\ROOT_PATH . "/datastore/template_*_{$group}.*.php" ) ?: [] as $f )
{
	if ( @unlink( $f ) )
	{
		$deleted++;
	}
}
echo "Deleted {$deleted} compiled template file(s) from /datastore for group '{$group}'.\n";

/* ---- 3. Rotate core_themes.set_cache_key so IPS recompiles ---- */

\IPS\Db::i()->update( 'core_themes', [ 'set_cache_key' => md5( microtime() . mt_rand() ) ] );
echo "Rotated core_themes.set_cache_key for every theme.\n";

/* ---- 4. Invalidate Theme compiled-template lookup, Store, and Cache ---- */

try { \IPS\Theme::deleteCompiledTemplate( $app, $location, $group ); echo "Theme::deleteCompiledTemplate OK.\n"; }
catch ( \Throwable $e ) { echo "Theme::deleteCompiledTemplate skipped: " . $e->getMessage() . "\n"; }

try { \IPS\Data\Store::i()->clearAll(); echo "Store::clearAll OK.\n"; }
catch ( \Throwable $e ) { echo "Store::clearAll skipped: " . $e->getMessage() . "\n"; }

try { \IPS\Data\Cache::i()->clearAll(); echo "Cache::clearAll OK.\n"; }
catch ( \Throwable $e ) { echo "Cache::clearAll skipped: " . $e->getMessage() . "\n"; }

echo "\nDone. Now reload PHP-FPM to evict eval'd template classes from opcache:\n";
echo "  systemctl reload php-fpm74 php-fpm80 php-fpm81 php-fpm82 php-fpm83\n";
