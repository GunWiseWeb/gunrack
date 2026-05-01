<?php
/**
 * v1.0.153 patch script - applies field-mapping (step 3) changes to the
 * existing setupwizard.php controller in place. Avoids the timeout that
 * a full 32KB byte-for-byte file save would cause.
 *
 * Run from the repo root:
 *   php applications/gddealer/setup/upg_10153/patch_controller.php
 *
 * Idempotent - if patches are already applied, skips with a notice.
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

$original = $content;
$applied  = 0;

/* =====================================================================
 * Patch 1 - Add CanonicalFields use statement
 * ===================================================================== */

$old1 = "use IPS\\gddealer\\Feed\\FeedFetcher;\nuse IPS\\gddealer\\Feed\\Parser\\XmlParser;";
$new1 = "use IPS\\gddealer\\Feed\\FeedFetcher;\nuse IPS\\gddealer\\Feed\\CanonicalFields;\nuse IPS\\gddealer\\Feed\\Parser\\XmlParser;";

if ( str_contains( $content, "use IPS\\gddealer\\Feed\\CanonicalFields;" ) )
{
    echo "  patch 1 (CanonicalFields use): already applied\n";
}
elseif ( str_contains( $content, $old1 ) )
{
    $content = str_replace( $old1, $new1, $content );
    echo "  patch 1 (CanonicalFields use): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 1 anchor not found\n" );
    exit( 1 );
}

/* =====================================================================
 * Patch 2 - Add step3, save_step3, reset_step3 to wizardUrls()
 * ===================================================================== */

$old2 = "            'step2_refetch' => (string) \\IPS\\Http\\Url::internal( \$base . '&do=step2&refetch=1', 'front', \$seo ),\n            'save_step1'    => (string) \\IPS\\Http\\Url::internal( \$base . '&do=saveStep1', 'front', \$seo ),\n            'save_step2'    => (string) \\IPS\\Http\\Url::internal( \$base . '&do=saveStep2', 'front', \$seo ),\n            'dashboard'";

$new2 = "            'step2_refetch' => (string) \\IPS\\Http\\Url::internal( \$base . '&do=step2&refetch=1', 'front', \$seo ),\n            'step3'         => (string) \\IPS\\Http\\Url::internal( \$base . '&do=step3', 'front', \$seo ),\n            'save_step1'    => (string) \\IPS\\Http\\Url::internal( \$base . '&do=saveStep1', 'front', \$seo ),\n            'save_step2'    => (string) \\IPS\\Http\\Url::internal( \$base . '&do=saveStep2', 'front', \$seo ),\n            'save_step3'    => (string) \\IPS\\Http\\Url::internal( \$base . '&do=saveStep3', 'front', \$seo ),\n            'reset_step3'   => (string) \\IPS\\Http\\Url::internal( \$base . '&do=step3&reset=1', 'front', \$seo ),\n            'dashboard'";

if ( str_contains( $content, "'step3'         => (string) \\IPS\\Http\\Url::internal( \$base . '&do=step3'" ) )
{
    echo "  patch 2 (step3 URLs in wizardUrls): already applied\n";
}
elseif ( str_contains( $content, $old2 ) )
{
    $content = str_replace( $old2, $new2, $content );
    echo "  patch 2 (step3 URLs in wizardUrls): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 2 anchor not found\n" );
    exit( 1 );
}

/* =====================================================================
 * Patch 3 - Update manage() to route to step3 when wizard_step >= 2
 * ===================================================================== */

$old3 = "        if ( \$highest >= 1 ) { \$this->step2(); }\n        else                 { \$this->step1(); }";
$new3 = "        if ( \$highest >= 2 )      { \$this->step3(); }\n        elseif ( \$highest >= 1 )  { \$this->step2(); }\n        else                      { \$this->step1(); }";

if ( str_contains( $content, "if ( \$highest >= 2 )      { \$this->step3(); }" ) )
{
    echo "  patch 3 (manage route): already applied\n";
}
elseif ( str_contains( $content, $old3 ) )
{
    $content = str_replace( $old3, $new3, $content );
    echo "  patch 3 (manage route): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 3 anchor not found\n" );
    exit( 1 );
}

/* =====================================================================
 * Patch 4 - Update saveStep2 redirect to go to step3 (not back to step2)
 * ===================================================================== */

/* Original v152 redirected back to step2 with ?saved=1. v153 should
 * advance to step3. */
$old4 = "        \\IPS\\Output::i()->redirect(\n            \\IPS\\Http\\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step2', 'front', 'dealers_setup_wizard' )->setQueryString( 'saved', 1 )\n        );\n    }\n\n    protected function performStep2Fetch";

$new4 = "        \\IPS\\Output::i()->redirect(\n            \\IPS\\Http\\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step3', 'front', 'dealers_setup_wizard' )\n        );\n    }\n\n    protected function performStep2Fetch";

if ( str_contains( $content, "if ( \$highest >= 2 )      { \$this->step3(); }" )
     && str_contains( $content, "&do=step3', 'front', 'dealers_setup_wizard' )\n        );\n    }\n\n    protected function performStep2Fetch" ) )
{
    echo "  patch 4 (saveStep2 redirect): already applied\n";
}
elseif ( str_contains( $content, $old4 ) )
{
    $content = str_replace( $old4, $new4, $content );
    echo "  patch 4 (saveStep2 redirect): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 4 anchor not found\n" );
    exit( 1 );
}

/* =====================================================================
 * Patch 5 - Insert step3, saveStep3, buildStep3Values methods
 *           Inserted BEFORE the existing "Helpers" section comment block.
 * ===================================================================== */

$step3Block = <<<'PHPCODE'
    /* ============================================================
     * Step 3 - Field Mapping with Auto-Suggest
     * ============================================================ */

    /**
     * Render step 3. Reads cached discovered fields from
     * wizard_state_json and existing field_mapping from
     * gd_dealer_feed_config. Inverse-view mapping UI: one row per
     * canonical field, dropdown of all dealer fields per row.
     *
     * Gating: requires wizard_step >= 2.
     *
     * Query params:
     *   ?reset=1  - clear current mapping and re-run auto-suggest
     */
    protected function step3(): void
    {
        $cfg = $this->loadFeedConfig();
        $highest = isset( $cfg['wizard_step'] ) ? (int) $cfg['wizard_step'] : 0;
        if ( $highest < 2 )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step2', 'front', 'dealers_setup_wizard' )
            );
            return;
        }

        $state = $this->loadWizardState();

        if ( empty( $state['step2_fields'] ) )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step2&refetch=1', 'front', 'dealers_setup_wizard' )
            );
            return;
        }

        $reset = (int) ( \IPS\Request::i()->reset ?? 0 ) === 1;

        $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep3(
            $this->wizardData( 3 ),
            $this->buildStep3Values( $cfg, $state, $reset )
        );
        $this->output( 'setupWizard', $body );
    }

    protected function saveStep3(): void
    {
        \IPS\Session::i()->csrfCheck();

        $cfg = $this->loadFeedConfig();
        $highest = isset( $cfg['wizard_step'] ) ? (int) $cfg['wizard_step'] : 0;
        if ( $highest < 2 )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step2', 'front', 'dealers_setup_wizard' )
            );
            return;
        }

        $submitted = \IPS\Request::i()->mapping ?? [];
        if ( !is_array( $submitted ) ) { $submitted = []; }

        $state = $this->loadWizardState();
        $discoveredFieldNames = [];
        if ( !empty( $state['step2_fields'] ) && is_array( $state['step2_fields'] ) )
        {
            foreach ( $state['step2_fields'] as $f )
            {
                if ( isset( $f['field'] ) ) { $discoveredFieldNames[] = (string) $f['field']; }
            }
        }
        $discoveredSet = array_flip( $discoveredFieldNames );

        $allCanonical = CanonicalFields::all();

        $validated = [];
        $errors = [];

        foreach ( $submitted as $canonicalSlug => $dealerField )
        {
            $canonicalSlug = (string) $canonicalSlug;
            $dealerField   = trim( (string) $dealerField );

            if ( !isset( $allCanonical[ $canonicalSlug ] ) ) { continue; }
            if ( $dealerField === '' )                       { continue; }
            if ( !isset( $discoveredSet[ $dealerField ] ) )
            {
                $errors[] = "Invalid dealer field '{$dealerField}' for {$canonicalSlug}.";
                continue;
            }

            $validated[ $canonicalSlug ] = $dealerField;
        }

        if ( !empty( $errors ) )
        {
            $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep3(
                $this->wizardData( 3 ),
                $this->buildStep3Values( $cfg, $state, false, $submitted, $errors )
            );
            $this->output( 'setupWizard', $body );
            return;
        }

        $update = [
            'field_mapping' => json_encode( $validated, JSON_UNESCAPED_SLASHES ),
            'wizard_step'   => max( 3, (int) ( $cfg['wizard_step'] ?? 0 ) ),
        ];

        try
        {
            \IPS\Db::i()->update( 'gd_dealer_feed_config', $update,
                [ 'dealer_id=?', (int) $this->dealer->dealer_id ]
            );
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'wizard saveStep3 update failed: ' . $e->getMessage(), 'gddealer_setupwizard' ); } catch ( \Throwable ) {}
            $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep3(
                $this->wizardData( 3 ),
                $this->buildStep3Values( $cfg, $state, false, $submitted, [ 'Could not save your mapping. Please try again.' ] )
            );
            $this->output( 'setupWizard', $body );
            return;
        }

        \IPS\Output::i()->redirect(
            \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard&do=step3', 'front', 'dealers_setup_wizard' )
                ->setQueryString( 'saved', 1 )
        );
    }

    /**
     * Build the $values array for the step 3 template.
     *
     * @param array<string, mixed> $cfg
     * @param array<string, mixed> $state
     * @param bool $reset
     * @param array<string, string>|null $overrideMapping
     * @param array<int, string> $errors
     *
     * @return array<string, mixed>
     */
    protected function buildStep3Values( array $cfg, array $state, bool $reset, ?array $overrideMapping = null, array $errors = [] ): array
    {
        $discovered = [];
        $sampleFor  = [];
        if ( !empty( $state['step2_fields'] ) && is_array( $state['step2_fields'] ) )
        {
            foreach ( $state['step2_fields'] as $f )
            {
                if ( !isset( $f['field'] ) ) { continue; }
                $name = (string) $f['field'];
                $discovered[] = $name;
                $sampleFor[ $name ] = isset( $f['sample'] ) ? (string) $f['sample'] : '';
            }
        }

        $savedMapping = [];
        if ( !$reset && !empty( $cfg['field_mapping'] ) )
        {
            $decoded = json_decode( (string) $cfg['field_mapping'], true );
            if ( is_array( $decoded ) ) { $savedMapping = $decoded; }
        }

        $suggestions = CanonicalFields::buildSuggestionMap( $discovered );
        $autoMapping = [];
        foreach ( $suggestions as $dealerField => $canonicalSlug )
        {
            if ( !isset( $autoMapping[ $canonicalSlug ] ) )
            {
                $autoMapping[ $canonicalSlug ] = $dealerField;
            }
        }

        $currentMapping = [];
        foreach ( CanonicalFields::all() as $slug => $field )
        {
            if ( isset( $overrideMapping[ $slug ] ) )
            {
                $currentMapping[ $slug ] = (string) $overrideMapping[ $slug ];
            }
            elseif ( isset( $savedMapping[ $slug ] ) )
            {
                $currentMapping[ $slug ] = (string) $savedMapping[ $slug ];
            }
            elseif ( isset( $autoMapping[ $slug ] ) )
            {
                $currentMapping[ $slug ] = (string) $autoMapping[ $slug ];
            }
            else
            {
                $currentMapping[ $slug ] = '';
            }
        }

        $autoSuggested = [];
        foreach ( $autoMapping as $slug => $dealerField )
        {
            $isSaved   = !$reset && isset( $savedMapping[ $slug ] );
            $isPosted  = isset( $overrideMapping[ $slug ] );
            if ( !$isSaved && !$isPosted )
            {
                $autoSuggested[ $slug ] = $dealerField;
            }
        }

        $grouped = [];
        foreach ( CanonicalFields::grouped() as $groupKey => $group )
        {
            if ( empty( $group['fields'] ) ) { continue; }
            $grouped[] = [
                'key'    => $groupKey,
                'label'  => $group['label'],
                'fields' => $group['fields'],
            ];
        }

        $requiredTotal  = 0;
        $requiredMapped = 0;
        $requiredUnmapped = [];
        foreach ( CanonicalFields::all() as $slug => $field )
        {
            if ( ( $field['req'] ?? '' ) !== CanonicalFields::REQ_REQUIRED ) { continue; }
            $requiredTotal++;
            if ( !empty( $currentMapping[ $slug ] ) )
            {
                $requiredMapped++;
            }
            else
            {
                $requiredUnmapped[] = $field;
            }
        }

        $usedDealerFields = array_filter( array_values( $currentMapping ), fn( $v ) => $v !== '' );
        $usedDealerFields = array_unique( $usedDealerFields );

        return [
            'urls'              => $this->wizardUrls(),
            'csrfKey'           => \IPS\Session::i()->csrfKey,
            'discovered'        => $discovered,
            'discovered_count'  => count( $discovered ),
            'sample_for'        => $sampleFor,
            'sample_for_json'   => json_encode( $sampleFor, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS ),
            'grouped'           => $grouped,
            'current_mapping'   => $currentMapping,
            'auto_suggested'    => $autoSuggested,
            'auto_count'        => count( $autoSuggested ),
            'required_total'    => $requiredTotal,
            'required_mapped'   => $requiredMapped,
            'required_unmapped' => $requiredUnmapped,
            'used_dealer_count' => count( $usedDealerFields ),
            'errors'            => $errors,
        ];
    }

    /* ============================================================
     * Helpers
     * ============================================================ */
PHPCODE;

$old5 = "    /* ============================================================\n     * Helpers\n     * ============================================================ */";

if ( str_contains( $content, "function step3(): void" ) )
{
    echo "  patch 5 (step3/saveStep3/buildStep3Values methods): already applied\n";
}
elseif ( substr_count( $content, $old5 ) === 1 )
{
    $content = str_replace( $old5, $step3Block, $content );
    echo "  patch 5 (step3/saveStep3/buildStep3Values methods): applied\n";
    $applied++;
}
else
{
    fwrite( STDERR, "ERROR: patch 5 anchor not found or non-unique\n" );
    exit( 1 );
}

/* =====================================================================
 * Write back if anything changed
 * ===================================================================== */

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

echo "\n";
echo "Applied {$applied} patches.\n";
echo "Original size: " . strlen( $original ) . " bytes\n";
echo "New size:      " . strlen( $content ) . " bytes\n";

/* Lint check */
$lint = shell_exec( 'php -l ' . escapeshellarg( $path ) . ' 2>&1' );
echo "Lint: " . trim( (string) $lint ) . "\n";

if ( str_contains( (string) $lint, 'No syntax errors' ) )
{
    exit( 0 );
}
else
{
    fwrite( STDERR, "WARNING: lint failed - patch may have produced invalid PHP\n" );
    exit( 1 );
}
