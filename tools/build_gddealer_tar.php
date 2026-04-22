<?php
/**
 * Build the gddealer-vX.Y.Z.tar IPS application package.
 *
 * Rule 10 (CLAUDE.md): `Application.php` must be the very first entry in
 * the tar. IPS's installer inspects the first header to identify the
 * application and will reject/misinstall if anything else precedes it.
 * We use PharData::addFromString (NOT addFile, which writes 0-byte
 * files) and add `Application.php` before any directory walk.
 *
 * Rule 6: every directory must contain a blank index.html.
 *
 * Usage: php tools/build_gddealer_tar.php [version]
 *   Default version is read from applications/gddealer/data/application.json.
 */

$repoRoot = realpath( __DIR__ . '/..' );
$appDir   = $repoRoot . '/applications/gddealer';
$appJson  = json_decode( file_get_contents( $appDir . '/data/application.json' ), true );
$version  = $argv[1] ?? $appJson['app_version'];
$tarPath  = $repoRoot . "/gddealer-v{$version}.tar";

if ( file_exists( $tarPath ) )
{
	unlink( $tarPath );
}

$phar = new PharData( $tarPath, 0, null, Phar::TAR );

/* ---- Application.php MUST be the first entry (Rule 10) ---- */
$phar->addFromString( 'Application.php', file_get_contents( $appDir . '/Application.php' ) );

/* ---- Walk the rest of the tree ---- */
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $appDir, FilesystemIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::SELF_FIRST
);

$added = 1; /* Application.php */
foreach ( $iterator as $file )
{
	if ( $file->isDir() )
	{
		continue;
	}

	$relativePath = str_replace( $appDir . '/', '', $file->getPathname() );

	if ( $relativePath === 'Application.php' )
	{
		continue; /* already added first */
	}

	$phar->addFromString( $relativePath, file_get_contents( $file->getPathname() ) );
	$added++;
}

/* ---- Verify ---- */
$firstEntry = null;
foreach ( new PharData( $tarPath ) as $entry )
{
	$firstEntry = str_replace( 'phar://' . $tarPath . '/', '', $entry->getPathname() );
	break;
}

$size = filesize( $tarPath );
printf( "Built %s\n  %d files, %s bytes\n  first entry: %s\n", basename( $tarPath ), $added, number_format( $size ), $firstEntry );

if ( $firstEntry !== 'Application.php' )
{
	fwrite( STDERR, "FAIL: first tar entry is '{$firstEntry}', expected 'Application.php' (Rule 10)\n" );
	exit( 1 );
}
