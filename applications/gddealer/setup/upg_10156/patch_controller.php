<?php
/**
 * v1.0.156 patch script - hotfix for EX0 in step 4.
 *
 * Bug: the step 4 template had {{if $summary['error_records'] > 0}}
 * blocks INSIDE HTML class attributes. The IPS template parser
 * treats the > as the end of the HTML element opening tag (or
 * similar), causing a compile failure → EX0.
 *
 * Step 3 has a similar {{if ... === ...}} pattern that works fine,
 * because === doesn't collide with HTML syntax. Only > and < are
 * dangerous when inside attributes.
 *
 * Fix: pre-compute the 3 stat class strings (good/bad/warn) in the
 * controller, pass them as plain string values, and have the template
 * use {$values['error_class']} etc. instead of inline conditionals.
 *
 * Run from the repo root:
 *   php applications/gddealer/setup/upg_10156/patch_controller.php
 *
 * Idempotent.
 */

$path = dirname( __DIR__, 2 ) . '/modules/front/dealers/setupwizard.php';

if ( !file_exists( $path ) )
{
    fwrite( STDERR, "ERROR: setupwizard.php not found at {$path}\n" );
    exit( 1 );
}

$content = file_get_contents( $path );
if ( $content === false )
{
    fwrite( STDERR, "ERROR: could not read {$path}\n" );
    exit( 1 );
}

$applied = 0;

/* =====================================================================
 * Patch: add error_class / warn_class to the $values passed to the
 * step 4 template. Anchor on the existing 'rows' key.
 * ===================================================================== */

$old = "        \$body = (string) \\IPS\\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep4(
            \$this->wizardData( 4 ),
            [
                'urls'    => \$this->wizardUrls(),
                'csrfKey' => \\IPS\\Session::i()->csrfKey,
                'report'  => \$report,
                'rows'    => \$rows,
            ]
        );";

$new = "        /* Pre-compute the 3 stat-pill class strings. We can't use
         * {{if expr > 0}} inside an HTML class attribute - the IPS
         * template parser misreads the > as end-of-tag and produces
         * EX0. Computing here and passing as plain strings is safe. */
        \$errorRecords   = isset( \$report['summary']['error_records'] ) ? (int) \$report['summary']['error_records'] : 0;
        \$warningRecords = isset( \$report['summary']['warning_records'] ) ? (int) \$report['summary']['warning_records'] : 0;
        \$validRecords   = isset( \$report['summary']['valid_records'] ) ? (int) \$report['summary']['valid_records'] : 0;

        \$body = (string) \\IPS\\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep4(
            \$this->wizardData( 4 ),
            [
                'urls'           => \$this->wizardUrls(),
                'csrfKey'        => \\IPS\\Session::i()->csrfKey,
                'report'         => \$report,
                'rows'           => \$rows,
                'error_class'    => \$errorRecords > 0 ? 'gdSetupWizard__stat--bad'  : 'gdSetupWizard__stat--neutral',
                'warn_class'     => \$warningRecords > 0 ? 'gdSetupWizard__stat--warn' : 'gdSetupWizard__stat--neutral',
                'continue_ready' => \$validRecords > 0,
            ]
        );";

if ( str_contains( $content, "'error_class'    => \$errorRecords" ) )
{
    echo "  patch (step 4 pre-computed classes): already applied\n";
}
elseif ( str_contains( $content, $old ) )
{
    $content = str_replace( $old, $new, $content );
    echo "  patch (step 4 pre-computed classes): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: anchor not found - is the v155 controller patch applied?\n" );
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

$lint = shell_exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1' );
echo "Lint: " . trim( (string) $lint ) . "\n";

if ( !str_contains( (string) $lint, 'No syntax errors' ) )
{
    fwrite( STDERR, "WARNING: lint failed\n" );
    exit( 1 );
}

exit( 0 );
