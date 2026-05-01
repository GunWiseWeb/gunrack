<?php
/**
 * v1.0.154 patch script - adds 'default' key to specific REQ_REQUIRED
 * fields in CanonicalFields::all(). This lets the wizard offer
 * platform-sensible defaults for fields that dealer feeds typically
 * don't include (condition, free_shipping, in_stock).
 *
 * Run from the repo root:
 *   php applications/gddealer/setup/upg_10154/patch_canonical.php
 *
 * Idempotent.
 */

$path = dirname( __DIR__, 2 ) . '/sources/Feed/CanonicalFields.php';

if ( !file_exists( $path ) )
{
    fwrite( STDERR, "ERROR: CanonicalFields.php not found at {$path}\n" );
    exit( 1 );
}

$content = file_get_contents( $path );
if ( $content === false )
{
    fwrite( STDERR, "ERROR: could not read {$path}\n" );
    exit( 1 );
}

$applied = 0;

/* ---------------------------------------------------------------------
 * Patch 1: condition gets default "new"
 * --------------------------------------------------------------------- */
$old1 = "'condition'     => [ 'slug' => 'condition',     'label' => 'Condition',          'group' => self::GROUP_CORE_REQUIRED, 'req' => self::REQ_REQUIRED ],";
$new1 = "'condition'     => [ 'slug' => 'condition',     'label' => 'Condition',          'group' => self::GROUP_CORE_REQUIRED, 'req' => self::REQ_REQUIRED, 'default' => 'new' ],";

if ( str_contains( $content, "'slug' => 'condition',     'label' => 'Condition',          'group' => self::GROUP_CORE_REQUIRED, 'req' => self::REQ_REQUIRED, 'default' => 'new'" ) )
{
    echo "  patch 1 (condition default): already applied\n";
}
elseif ( str_contains( $content, $old1 ) )
{
    $content = str_replace( $old1, $new1, $content );
    echo "  patch 1 (condition default): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 1 anchor not found\n" );
    exit( 1 );
}

/* ---------------------------------------------------------------------
 * Patch 2: free_shipping gets default "false"
 * --------------------------------------------------------------------- */
$old2 = "'free_shipping' => [ 'slug' => 'free_shipping', 'label' => 'Free Shipping Flag', 'group' => self::GROUP_CORE_REQUIRED, 'req' => self::REQ_REQUIRED ],";
$new2 = "'free_shipping' => [ 'slug' => 'free_shipping', 'label' => 'Free Shipping Flag', 'group' => self::GROUP_CORE_REQUIRED, 'req' => self::REQ_REQUIRED, 'default' => 'false' ],";

if ( str_contains( $content, "'slug' => 'free_shipping', 'label' => 'Free Shipping Flag', 'group' => self::GROUP_CORE_REQUIRED, 'req' => self::REQ_REQUIRED, 'default' => 'false'" ) )
{
    echo "  patch 2 (free_shipping default): already applied\n";
}
elseif ( str_contains( $content, $old2 ) )
{
    $content = str_replace( $old2, $new2, $content );
    echo "  patch 2 (free_shipping default): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 2 anchor not found\n" );
    exit( 1 );
}

/* ---------------------------------------------------------------------
 * Patch 3: in_stock gets default "true"
 * --------------------------------------------------------------------- */
$old3 = "'in_stock'      => [ 'slug' => 'in_stock',      'label' => 'In Stock Flag',      'group' => self::GROUP_CORE_REQUIRED, 'req' => self::REQ_REQUIRED ],";
$new3 = "'in_stock'      => [ 'slug' => 'in_stock',      'label' => 'In Stock Flag',      'group' => self::GROUP_CORE_REQUIRED, 'req' => self::REQ_REQUIRED, 'default' => 'true' ],";

if ( str_contains( $content, "'slug' => 'in_stock',      'label' => 'In Stock Flag',      'group' => self::GROUP_CORE_REQUIRED, 'req' => self::REQ_REQUIRED, 'default' => 'true'" ) )
{
    echo "  patch 3 (in_stock default): already applied\n";
}
elseif ( str_contains( $content, $old3 ) )
{
    $content = str_replace( $old3, $new3, $content );
    echo "  patch 3 (in_stock default): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 3 anchor not found\n" );
    exit( 1 );
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

echo "\nApplied {$applied} patches.\n";
$lint = shell_exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1' );
echo "Lint: " . trim( (string) $lint ) . "\n";

if ( !str_contains( (string) $lint, 'No syntax errors' ) )
{
    fwrite( STDERR, "WARNING: lint failed\n" );
    exit( 1 );
}

exit( 0 );
