<?php
/**
 * v1.0.155 patch script - adds step 4 (Validate Sample) to the
 * setupwizard controller.
 *
 * Adds:
 *   - step4(): renders the validation report
 *   - saveStep4(): advances wizard_step to 4
 *   - runStep4Validation(): applies field_map (with defaults) to cached
 *     step 2 records, runs through Validator, caches report in
 *     wizard_state_json
 *   - 'is_boolean_default' flag on each row in buildStep3Values to power
 *     the select-vs-text rendering decision in the v155 step 3 template
 *
 * Also updates:
 *   - manage() to route to step 4 when wizard_step >= 3
 *   - saveStep3() redirect to advance to step 4 (was looping back to step 3)
 *   - wizardUrls() to include step4 URLs
 *
 * Run from the repo root:
 *   php applications/gddealer/setup/upg_10155/patch_controller.php
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
 * Patch 1 - Add step 4 URLs to wizardUrls()
 * ===================================================================== */

$old1 = "            'reset_step3'   => (string) \\IPS\\Http\\Url::internal( \$base . '&do=step3&reset=1', 'front', \$seo ),
            'dashboard'";
$new1 = "            'reset_step3'   => (string) \\IPS\\Http\\Url::internal( \$base . '&do=step3&reset=1', 'front', \$seo ),
            'step4'         => (string) \\IPS\\Http\\Url::internal( \$base . '&do=step4', 'front', \$seo ),
            'step4_revalidate' => (string) \\IPS\\Http\\Url::internal( \$base . '&do=step4&revalidate=1', 'front', \$seo ),
            'save_step4'    => (string) \\IPS\\Http\\Url::internal( \$base . '&do=saveStep4', 'front', \$seo ),
            'dashboard'";

if ( str_contains( $content, "'step4'         => (string) \\IPS\\Http\\Url::internal" ) )
{
    echo "  patch 1 (step4 URLs): already applied\n";
}
elseif ( str_contains( $content, $old1 ) )
{
    $content = str_replace( $old1, $new1, $content );
    echo "  patch 1 (step4 URLs): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 1 anchor not found\n" );
    exit( 1 );
}

/* =====================================================================
 * Patch 2 - Update manage() to route to step 4 when wizard_step >= 3
 * ===================================================================== */

$old2 = "        if ( \$highest >= 2 )      { \$this->step3(); }
        elseif ( \$highest >= 1 )  { \$this->step2(); }
        else                      { \$this->step1(); }";
$new2 = "        if ( \$highest >= 3 )      { \$this->step4(); }
        elseif ( \$highest >= 2 )  { \$this->step3(); }
        elseif ( \$highest >= 1 )  { \$this->step2(); }
        else                      { \$this->step1(); }";

if ( str_contains( $content, "if ( \$highest >= 3 )      { \$this->step4(); }" ) )
{
    echo "  patch 2 (manage route): already applied\n";
}
elseif ( str_contains( $content, $old2 ) )
{
    $content = str_replace( $old2, $new2, $content );
    echo "  patch 2 (manage route): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 2 anchor not found\n" );
    exit( 1 );
}

/* =====================================================================
 * Patch 3 - saveStep3 should redirect to step 4 (not loop back to step 3)
 * ===================================================================== */

$old3 = "        \\IPS\\Output::i()->redirect(
            \\IPS\\Http\\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step3', 'front', 'dealers_setup_wizard' )
                ->setQueryString( 'saved', 1 )
        );
    }

    /**
     * Build \$values array for the step 3 template.";

$new3 = "        \\IPS\\Output::i()->redirect(
            \\IPS\\Http\\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step4', 'front', 'dealers_setup_wizard' )
        );
    }

    /**
     * Build \$values array for the step 3 template.";

if ( str_contains( $content, "controller=setupwizard&do=step4', 'front', 'dealers_setup_wizard' )\n        );\n    }\n\n    /**\n     * Build \$values array for the step 3 template." ) )
{
    echo "  patch 3 (saveStep3 redirect): already applied\n";
}
elseif ( str_contains( $content, $old3 ) )
{
    $content = str_replace( $old3, $new3, $content );
    echo "  patch 3 (saveStep3 redirect): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 3 anchor not found\n" );
    exit( 1 );
}

/* =====================================================================
 * Patch 4 - Add 'is_boolean_default' flag on rows in buildStep3Values
 * so the step 3 template can render select vs text input.
 * ===================================================================== */

$old4 = "            \$rows[ \$slug ] = [
                'slug'                => \$slug,
                'label'               => \$field['label'] ?? \$slug,
                'group'               => \$field['group'] ?? '',
                'req'                 => \$field['req'] ?? '',
                'has_default'         => \$hasDefault,
                'published_default'   => \$publishedDefault,
                'source'              => \$source,
                'selected_dealer_field' => \$selectedDealerField,
                'default_override'    => \$defaultOverride,
                'is_auto_suggested'   => \$isAutoSuggested,
            ];";
$new4 = "            \$isBooleanDefault = \$hasDefault && in_array( \$publishedDefault, [ 'true', 'false' ], true );

            \$rows[ \$slug ] = [
                'slug'                => \$slug,
                'label'               => \$field['label'] ?? \$slug,
                'group'               => \$field['group'] ?? '',
                'req'                 => \$field['req'] ?? '',
                'has_default'         => \$hasDefault,
                'published_default'   => \$publishedDefault,
                'is_boolean_default'  => \$isBooleanDefault,
                'source'              => \$source,
                'selected_dealer_field' => \$selectedDealerField,
                'default_override'    => \$defaultOverride,
                'is_auto_suggested'   => \$isAutoSuggested,
            ];";

if ( str_contains( $content, "'is_boolean_default'" ) )
{
    echo "  patch 4 (is_boolean_default flag): already applied\n";
}
elseif ( str_contains( $content, $old4 ) )
{
    $content = str_replace( $old4, $new4, $content );
    echo "  patch 4 (is_boolean_default flag): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 4 anchor not found\n" );
    exit( 1 );
}

/* =====================================================================
 * Patch 5 - Insert step4(), saveStep4(), runStep4Validation methods
 *           BEFORE the Helpers section comment.
 * ===================================================================== */

$step4Block = <<<'PHPCODE'
    /* ============================================================
     * Step 4 - Validate Sample (v155)
     * ============================================================ */

    /**
     * Render step 4. Reads cached step 2 records and saved field_mapping
     * from gd_dealer_feed_config. Applies the field map (with _defaults)
     * to each record producing canonical-shape records, then runs them
     * through Validator::validate() and renders the report.
     *
     * Validation results are cached in wizard_state_json so revisiting
     * the page doesn't re-run validation. Pass ?revalidate=1 to refresh.
     *
     * Gating: requires wizard_step >= 3.
     */
    protected function step4(): void
    {
        $cfg = $this->loadFeedConfig();
        $highest = isset( $cfg['wizard_step'] ) ? (int) $cfg['wizard_step'] : 0;
        if ( $highest < 3 )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step3', 'front', 'dealers_setup_wizard' )
            );
            return;
        }

        $state = $this->loadWizardState();

        if ( empty( $state['step2_records'] ) )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step2&refetch=1', 'front', 'dealers_setup_wizard' )
            );
            return;
        }

        $forceRevalidate = (int) ( \IPS\Request::i()->revalidate ?? 0 ) === 1;
        $hasCached = !empty( $state['step4_report'] );

        if ( !$hasCached || $forceRevalidate )
        {
            $this->runStep4Validation();
            $state = $this->loadWizardState();
        }

        $report = isset( $state['step4_report'] ) && is_array( $state['step4_report'] ) ? $state['step4_report'] : null;
        $rows   = isset( $state['step4_rows'] ) && is_array( $state['step4_rows'] ) ? $state['step4_rows'] : [];

        $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep4(
            $this->wizardData( 4 ),
            [
                'urls'    => $this->wizardUrls(),
                'csrfKey' => \IPS\Session::i()->csrfKey,
                'report'  => $report,
                'rows'    => $rows,
            ]
        );
        $this->output( 'setupWizard', $body );
    }

    protected function saveStep4(): void
    {
        \IPS\Session::i()->csrfCheck();

        $cfg = $this->loadFeedConfig();
        $highest = isset( $cfg['wizard_step'] ) ? (int) $cfg['wizard_step'] : 0;
        if ( $highest < 3 )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step3', 'front', 'dealers_setup_wizard' )
            );
            return;
        }

        $update = [ 'wizard_step' => max( 4, (int) ( $cfg['wizard_step'] ?? 0 ) ) ];
        try
        {
            \IPS\Db::i()->update( 'gd_dealer_feed_config', $update,
                [ 'dealer_id=?', (int) $this->dealer->dealer_id ]
            );
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'wizard saveStep4 update failed: ' . $e->getMessage(), 'gddealer_setupwizard' ); } catch ( \Throwable ) {}
        }

        /* v155 ships step 4 only - step 5 doesn't exist yet. Land back
         * on step 4 with a success flash. */
        \IPS\Output::i()->redirect(
            \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step4', 'front', 'dealers_setup_wizard' )
                ->setQueryString( 'saved', 1 )
        );
    }

    /**
     * Apply the field map to cached step 2 records (producing canonical
     * shape), pass through Validator, cache report + per-row data in
     * wizard_state_json.
     */
    protected function runStep4Validation(): void
    {
        $cfg = $this->loadFeedConfig();
        $state = $this->loadWizardState();

        $rawRecords = isset( $state['step2_records'] ) && is_array( $state['step2_records'] ) ? $state['step2_records'] : [];

        $fieldMap = [];
        if ( !empty( $cfg['field_mapping'] ) )
        {
            $decoded = json_decode( (string) $cfg['field_mapping'], true );
            if ( is_array( $decoded ) ) { $fieldMap = $decoded; }
        }

        /* Apply field map (canonical shape, suitable for Validator). */
        $canonical = [];
        foreach ( $rawRecords as $raw )
        {
            $canonical[] = FieldMapper::applyCanonical( $raw, $fieldMap );
        }

        /* Run validation. */
        $report = Validator::validate( $canonical );

        /* Build per-row data for the template. Each row gets its raw
         * record (for "your data" display), canonical record (for
         * "what we'd import"), and any errors/warnings tied to it. */
        $errorsByRow   = [];
        $warningsByRow = [];
        foreach ( $report['errors'] as $err )
        {
            $r = (int) ( $err['row'] ?? 0 );
            if ( $r > 0 ) { $errorsByRow[ $r ][] = $err; }
        }
        foreach ( $report['warnings'] as $warn )
        {
            $r = (int) ( $warn['row'] ?? 0 );
            if ( $r > 0 ) { $warningsByRow[ $r ][] = $warn; }
        }

        $rows = [];
        foreach ( $rawRecords as $i => $raw )
        {
            $rowNum = $i + 1;
            $rows[] = [
                'row'         => $rowNum,
                'raw'         => $raw,
                'canonical'   => $canonical[ $i ] ?? [],
                'errors'      => $errorsByRow[ $rowNum ] ?? [],
                'warnings'    => $warningsByRow[ $rowNum ] ?? [],
                'has_errors'  => !empty( $errorsByRow[ $rowNum ] ),
                'has_warnings' => !empty( $warningsByRow[ $rowNum ] ),
            ];
        }

        $state['step4_report'] = $report;
        $state['step4_rows']   = $rows;
        $this->saveWizardState( $state );
    }

    /* ============================================================
     * Helpers
     * ============================================================ */
PHPCODE;

if ( str_contains( $content, "function step4(): void" ) )
{
    echo "  patch 5 (step4 methods): already applied\n";
}
else
{
    $helpersMarker = "    /* ============================================================\n     * Helpers\n     * ============================================================ */";
    if ( substr_count( $content, $helpersMarker ) === 1 )
    {
        $content = str_replace( $helpersMarker, $step4Block, $content );
        echo "  patch 5 (step4 methods): applied\n";
        $applied++;
    }
    else
    {
        fwrite( STDERR, "ERROR: helpers marker not found or non-unique\n" );
        exit( 1 );
    }
}

/* =====================================================================
 * Patch 6 - Add Validator import alongside FieldMapper at top of file
 * ===================================================================== */

$old6 = "use IPS\\gddealer\\Feed\\FeedFetcher;
use IPS\\gddealer\\Feed\\CanonicalFields;";
$new6 = "use IPS\\gddealer\\Feed\\FeedFetcher;
use IPS\\gddealer\\Feed\\CanonicalFields;
use IPS\\gddealer\\Feed\\Validator;";

if ( str_contains( $content, "use IPS\\gddealer\\Feed\\Validator;" ) )
{
    echo "  patch 6 (Validator use): already applied\n";
}
elseif ( str_contains( $content, $old6 ) )
{
    $content = str_replace( $old6, $new6, $content );
    echo "  patch 6 (Validator use): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 6 anchor not found\n" );
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

echo "\nApplied {$applied} patch(es).\n";
$lint = shell_exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1' );
echo "Lint: " . trim( (string) $lint ) . "\n";

if ( !str_contains( (string) $lint, 'No syntax errors' ) )
{
    fwrite( STDERR, "WARNING: lint failed\n" );
    exit( 1 );
}

exit( 0 );
