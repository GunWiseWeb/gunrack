<?php
/**
 * Builder for gddealer-v1.0.0.tar.
 *
 * Rule #10: Application.php must be the FIRST entry in the tar.
 * Rule #6:  files at the root (no parent directory). Use addFromString
 *           because addFile produces 0-byte entries for some versions.
 */

$root    = __DIR__ . '/applications/gddealer';
$tarOut  = __DIR__ . '/gddealer-v1.0.0.tar';

if ( file_exists( $tarOut ) ) { unlink( $tarOut ); }

$tar = new PharData( $tarOut );

/* Application.php MUST be first. */
$tar->addFromString( 'Application.php', file_get_contents( "{$root}/Application.php" ) );

/* Then index.html. */
$tar->addFromString( 'index.html', file_get_contents( "{$root}/index.html" ) );

/* Then every other file, depth-first. */
$iter = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::SELF_FIRST
);

$entries = [];
foreach ( $iter as $file )
{
	if ( !$file->isFile() ) { continue; }
	$rel = ltrim( str_replace( $root, '', $file->getPathname() ), '/' );
	if ( $rel === 'Application.php' || $rel === 'index.html' ) { continue; }
	$entries[] = $rel;
}
sort( $entries );

foreach ( $entries as $rel )
{
	$tar->addFromString( $rel, file_get_contents( "{$root}/{$rel}" ) );
}

echo "Wrote {$tarOut}\n";
echo "First entry: ";
$list = `tar -tf {$tarOut} | head -1`;
echo $list;
echo "Total entries: " . trim( `tar -tf {$tarOut} | wc -l` ) . "\n";
