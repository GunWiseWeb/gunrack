<?php
/**
 * v1.0.155 patch script - adds applyCanonical() to FieldMapper.
 *
 * Background:
 *   FieldMapper::apply() outputs records keyed by STORAGE column names
 *   (dealer_price, listing_url, dealer_sku). This is what Importer needs
 *   for direct database upserts.
 *
 *   Validator was written to accept records keyed by CANONICAL names
 *   (price, url, sku - matching the v1.1 feed spec). When Validator
 *   sees a FieldMapper-output record, it doesn't find the canonical
 *   keys it expects and reports false errors like "Missing required
 *   field: price" even when the record DOES have a dealer_price set.
 *
 * Fix: add applyCanonical() that outputs canonical-keyed records,
 * suitable for passing to Validator. apply() unchanged.
 *
 * Run from the repo root:
 *   php applications/gddealer/setup/upg_10155/patch_fieldmapper.php
 *
 * Idempotent.
 */

$path = dirname( __DIR__, 2 ) . '/sources/Feed/FieldMapper.php';

if ( !file_exists( $path ) )
{
    fwrite( STDERR, "ERROR: FieldMapper.php not found at {$path}\n" );
    exit( 1 );
}

$content = file_get_contents( $path );
if ( $content === false )
{
    fwrite( STDERR, "ERROR: could not read {$path}\n" );
    exit( 1 );
}

$applied = 0;

if ( str_contains( $content, "function applyCanonical" ) )
{
    echo "  patch (applyCanonical method): already applied\n";
}
else
{
    /* Find the end of apply() and insert applyCanonical() right after.
     * Anchor: the closing }, then a blank line, then the next method
     * declaration. apply() ends with "return self::normalize( $out );" */

    $newMethod = <<<'PHPCODE'
    /**
     * Like apply(), but outputs records keyed by CANONICAL field names
     * (price, url, sku) instead of STORAGE column names (dealer_price,
     * listing_url, dealer_sku). Suitable for passing to Validator,
     * which was written against the v1.1 canonical spec.
     *
     * @param array<string, mixed> $record
     * @param array<string, mixed> $fieldMap
     * @return array<string, mixed>
     */
    public static function applyCanonical( array $record, array $fieldMap ): array
    {
        $storage = self::apply( $record, $fieldMap );

        /* Reverse the storage mapping. {dealer_price=>price, ...} */
        $storageToCanonical = array_flip( self::canonicalToStorage() );

        $out = [];
        foreach ( $storage as $key => $value )
        {
            $canonicalKey = $storageToCanonical[ $key ] ?? $key;
            $out[ $canonicalKey ] = $value;
        }
        return $out;
    }


PHPCODE;

    $anchor = "    public static function canonicalToStorageColumn( string \$canonical ): string";

    if ( str_contains( $content, $anchor ) )
    {
        $content = str_replace( $anchor, $newMethod . $anchor, $content );
        echo "  patch (applyCanonical method): inserted before canonicalToStorageColumn\n";
        $applied++;
    }
    else
    {
        fwrite( STDERR, "ERROR: anchor 'canonicalToStorageColumn' not found\n" );
        exit( 1 );
    }
}

if ( $applied === 0 )
{
    echo "All patches already applied. No changes written.\n";
    exit( 0 );
}

if ( file_put_contents( $path, $content ) === false )
{
    fwrite( STDERR, "ERROR: failed to write {$path}\n" );
    exit( 1 );
}

$lint = shell_exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1' );
echo "Lint: " . trim( (string) $lint ) . "\n";

if ( !str_contains( (string) $lint, 'No syntax errors' ) )
{
    fwrite( STDERR, "WARNING: lint failed\n" );
    exit( 1 );
}

exit( 0 );
