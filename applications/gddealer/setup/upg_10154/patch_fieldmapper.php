<?php
/**
 * v1.0.154 patch script - updates FieldMapper::apply to honor the
 * _defaults sub-object in field_mapping JSON.
 *
 * The wizard saves field_mapping as:
 *   {
 *     "_defaults": { canonicalSlug: value, ... },
 *     dealerField: canonicalSlug,
 *     ...
 *   }
 *
 * apply() needs to:
 *   1. Extract and remove "_defaults" from the map BEFORE the
 *      foreach loop (currently the loop would treat "_defaults" as
 *      a dealer field name, which is harmless but wasteful).
 *   2. After the foreach, fill in any canonical slugs that were not
 *      mapped from a dealer field but DO have a default value.
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

$old = "    public static function apply( array \$record, array \$fieldMap ): array
    {
        \$validCanonical = CanonicalFields::allSlugs();
        \$aliases = self::legacyStorageAliases();
        \$storageMap = self::canonicalToStorage();

        \$out = [];
        foreach ( \$fieldMap as \$dealerField => \$canonical )
        {";

$new = "    public static function apply( array \$record, array \$fieldMap ): array
    {
        \$validCanonical = CanonicalFields::allSlugs();
        \$aliases = self::legacyStorageAliases();
        \$storageMap = self::canonicalToStorage();

        /* Pull _defaults out of the map before the loop. They're applied
         * AFTER the dealer-field map below, only to canonicals that
         * weren't otherwise populated (so a feed value beats a default). */
        \$defaults = [];
        if ( isset( \$fieldMap['_defaults'] ) && is_array( \$fieldMap['_defaults'] ) )
        {
            \$defaults = \$fieldMap['_defaults'];
            unset( \$fieldMap['_defaults'] );
        }

        \$out = [];
        foreach ( \$fieldMap as \$dealerField => \$canonical )
        {";

if ( str_contains( $content, "Pull _defaults out of the map" ) )
{
    echo "  patch 1 (extract _defaults): already applied\n";
}
elseif ( str_contains( $content, $old ) )
{
    $content = str_replace( $old, $new, $content );
    echo "  patch 1 (extract _defaults): applied\n";
}
else
{
    fwrite( STDERR, "ERROR: patch 1 anchor not found\n" );
    exit( 1 );
}

/* Now insert the defaults-application block right before the
 * `return self::normalize( $out );` line. */

$old2 = "            \$storageKey = \$storageMap[ \$canonical ] ?? \$canonical;
            \$out[ \$storageKey ] = \$record[ \$dealerField ];
        }

        return self::normalize( \$out );
    }";

$new2 = "            \$storageKey = \$storageMap[ \$canonical ] ?? \$canonical;
            \$out[ \$storageKey ] = \$record[ \$dealerField ];
        }

        /* Apply defaults for canonicals not populated by the feed. */
        foreach ( \$defaults as \$canonical => \$value )
        {
            \$canonical = isset( \$aliases[ \$canonical ] ) ? \$aliases[ \$canonical ] : \$canonical;
            if ( !in_array( \$canonical, \$validCanonical, true ) ) { continue; }
            \$storageKey = \$storageMap[ \$canonical ] ?? \$canonical;
            if ( !array_key_exists( \$storageKey, \$out ) )
            {
                \$out[ \$storageKey ] = \$value;
            }
        }

        return self::normalize( \$out );
    }";

if ( str_contains( $content, "Apply defaults for canonicals not populated by the feed" ) )
{
    echo "  patch 2 (apply defaults block): already applied\n";
}
elseif ( str_contains( $content, $old2 ) )
{
    $content = str_replace( $old2, $new2, $content );
    echo "  patch 2 (apply defaults block): applied\n";
}
else
{
    fwrite( STDERR, "ERROR: patch 2 anchor not found\n" );
    exit( 1 );
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
