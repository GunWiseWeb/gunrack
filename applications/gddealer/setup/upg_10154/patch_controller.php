<?php
/**
 * v1.0.154 patch script - updates the Step 3 section of the setupwizard
 * controller to support default values for required canonical fields.
 *
 * Changes vs v153:
 *   - field_mapping JSON shape corrected: now {dealer_field: canonical}
 *     (was incorrectly {canonical: dealer_field} in v153 - the rest of
 *     the codebase reads {dealer_field: canonical}).
 *   - Defaults stored under a special "_defaults" sub-object inside
 *     field_mapping JSON: {"_defaults": {"condition": "new", ...}, ...}.
 *   - Wizard form now includes a per-field "use_default" radio for
 *     fields where CanonicalFields publishes a 'default' value.
 *
 * Run from the repo root:
 *   php applications/gddealer/setup/upg_10154/patch_controller.php
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
 * Replace the entire Step 3 section between the two comment banners.
 * ===================================================================== */

$startMarker = "    /* ============================================================\n     * Step 3 - Field Mapping with Auto-Suggest\n     * ============================================================ */";
$endMarker   = "    /* ============================================================\n     * Helpers\n     * ============================================================ */";

$newStep3Block = <<<'PHPCODE'
    /* ============================================================
     * Step 3 - Field Mapping with Auto-Suggest (v154 with defaults)
     * ============================================================ */

    /**
     * Render step 3. Reads cached discovered fields from
     * wizard_state_json and existing field_mapping from
     * gd_dealer_feed_config. Inverse-view mapping UI: one row per
     * canonical field, dropdown of all dealer fields per row,
     * plus an optional "use default" toggle for canonical fields
     * that publish a default value (condition, free_shipping,
     * in_stock as of v154).
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

        /* Form submits 3 inputs per canonical field:
         *   source[CANONICAL_SLUG]   - 'feed' | 'default' | 'none'
         *   mapping[CANONICAL_SLUG]  - dealer field name (when source=feed)
         *   default[CANONICAL_SLUG]  - override default value (when source=default)
         *
         * Stored shape (compatible with FieldMapper::apply):
         *   {
         *     "_defaults": { canonical: value, ... },
         *     dealerField: canonicalSlug,
         *     ...
         *   }
         */
        $sources    = \IPS\Request::i()->source ?? [];
        $mappingPos = \IPS\Request::i()->mapping ?? [];
        $defaultsIn = \IPS\Request::i()->{'default'} ?? [];

        if ( !is_array( $sources ) )    { $sources    = []; }
        if ( !is_array( $mappingPos ) ) { $mappingPos = []; }
        if ( !is_array( $defaultsIn ) ) { $defaultsIn = []; }

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

        $fieldMap = [];   /* dealerField => canonicalSlug */
        $defaults = [];   /* canonicalSlug => value */
        $errors   = [];

        foreach ( $allCanonical as $canonicalSlug => $fieldDef )
        {
            $source = isset( $sources[ $canonicalSlug ] ) ? (string) $sources[ $canonicalSlug ] : 'none';

            if ( $source === 'feed' )
            {
                $dealerField = trim( (string) ( $mappingPos[ $canonicalSlug ] ?? '' ) );
                if ( $dealerField === '' )
                {
                    /* Source said feed but no field picked - silently treat as unmapped. */
                    continue;
                }
                if ( !isset( $discoveredSet[ $dealerField ] ) )
                {
                    $errors[] = "Invalid dealer field '{$dealerField}' for {$canonicalSlug}.";
                    continue;
                }
                /* Storage shape is {dealerField: canonical} - if multiple
                 * canonicals point to the same dealer field, last write wins.
                 * That's a UX concern at form time, not here. */
                $fieldMap[ $dealerField ] = $canonicalSlug;
            }
            elseif ( $source === 'default' )
            {
                /* The dealer accepted the default, optionally with an override value. */
                if ( !isset( $fieldDef['default'] ) )
                {
                    /* Field doesn't publish a default - silently skip. */
                    continue;
                }
                $publishedDefault = (string) $fieldDef['default'];
                $userOverride     = isset( $defaultsIn[ $canonicalSlug ] ) ? trim( (string) $defaultsIn[ $canonicalSlug ] ) : '';
                $defaults[ $canonicalSlug ] = $userOverride !== '' ? $userOverride : $publishedDefault;
            }
            /* source === 'none' means dealer is leaving the field unmapped - do nothing. */
        }

        if ( !empty( $errors ) )
        {
            $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep3(
                $this->wizardData( 3 ),
                $this->buildStep3Values( $cfg, $state, false, [
                    'sources'  => $sources,
                    'mapping'  => $mappingPos,
                    'defaults' => $defaultsIn,
                ], $errors )
            );
            $this->output( 'setupWizard', $body );
            return;
        }

        /* Final stored shape. _defaults goes first only for human readability
         * in the admin TextArea; JSON ordering is irrelevant to readers. */
        $stored = [];
        if ( !empty( $defaults ) )
        {
            $stored['_defaults'] = $defaults;
        }
        foreach ( $fieldMap as $dealerField => $canonical )
        {
            $stored[ $dealerField ] = $canonical;
        }

        $update = [
            'field_mapping' => json_encode( $stored, JSON_UNESCAPED_SLASHES ),
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
                $this->buildStep3Values( $cfg, $state, false, [
                    'sources'  => $sources,
                    'mapping'  => $mappingPos,
                    'defaults' => $defaultsIn,
                ], [ 'Could not save your mapping. Please try again.' ] )
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
     * Build $values array for the step 3 template.
     *
     * Saved field_mapping shape (read at load):
     *   {
     *     "_defaults": { canonicalSlug: value },
     *     dealerField: canonicalSlug,
     *     ...
     *   }
     *
     * For each canonical field we compute:
     *   - source: 'feed' | 'default' | 'none'
     *   - selected_dealer_field: which dealer field is mapped (when source=feed)
     *   - default_value: published default (if any)
     *   - default_override: user's override for the default (when source=default)
     *
     * @param array<string, mixed> $cfg
     * @param array<string, mixed> $state
     * @param bool $reset
     * @param array<string, array<string,string>>|null $override Form POST that failed validation.
     *        Shape: ['sources' => [], 'mapping' => [], 'defaults' => []]
     * @param array<int, string> $errors
     *
     * @return array<string, mixed>
     */
    protected function buildStep3Values( array $cfg, array $state, bool $reset, ?array $override = null, array $errors = [] ): array
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

        /* Saved field_mapping (when revisiting). Inverted into
         * {canonical: dealerField} for easier per-row lookup, plus a
         * separate $savedDefaults map for canonicals served by defaults. */
        $savedFieldMap = [];   /* canonical => dealerField */
        $savedDefaults = [];   /* canonical => value */
        if ( !$reset && !empty( $cfg['field_mapping'] ) )
        {
            $decoded = json_decode( (string) $cfg['field_mapping'], true );
            if ( is_array( $decoded ) )
            {
                if ( isset( $decoded['_defaults'] ) && is_array( $decoded['_defaults'] ) )
                {
                    foreach ( $decoded['_defaults'] as $canonical => $value )
                    {
                        $savedDefaults[ (string) $canonical ] = (string) $value;
                    }
                }
                foreach ( $decoded as $key => $value )
                {
                    if ( $key === '_defaults' ) { continue; }
                    /* Saved shape is {dealerField: canonical}. */
                    $savedFieldMap[ (string) $value ] = (string) $key;
                }
            }
        }

        /* Auto-suggest for any canonical not already mapped. */
        $suggestions = CanonicalFields::buildSuggestionMap( $discovered );
        $autoMapping = [];   /* canonical => dealerField */
        foreach ( $suggestions as $dealerField => $canonicalSlug )
        {
            if ( !isset( $autoMapping[ $canonicalSlug ] ) )
            {
                $autoMapping[ $canonicalSlug ] = $dealerField;
            }
        }

        /* Decide source + values for each canonical field.
         * Priority: form override > saved > auto > default-if-published > none. */
        $rows = [];
        foreach ( CanonicalFields::all() as $slug => $field )
        {
            $hasDefault       = isset( $field['default'] );
            $publishedDefault = $hasDefault ? (string) $field['default'] : '';

            $source                = 'none';
            $selectedDealerField   = '';
            $defaultOverride       = '';
            $isAutoSuggested       = false;

            if ( $override !== null )
            {
                /* The form was just submitted with errors. Restore exactly
                 * what the user entered, even if invalid. */
                $source              = isset( $override['sources'][ $slug ] ) ? (string) $override['sources'][ $slug ] : 'none';
                $selectedDealerField = isset( $override['mapping'][ $slug ] ) ? (string) $override['mapping'][ $slug ] : '';
                $defaultOverride     = isset( $override['defaults'][ $slug ] ) ? (string) $override['defaults'][ $slug ] : '';
            }
            elseif ( isset( $savedFieldMap[ $slug ] ) )
            {
                $source              = 'feed';
                $selectedDealerField = $savedFieldMap[ $slug ];
            }
            elseif ( isset( $savedDefaults[ $slug ] ) )
            {
                $source          = 'default';
                $defaultOverride = $savedDefaults[ $slug ] === $publishedDefault ? '' : $savedDefaults[ $slug ];
            }
            elseif ( isset( $autoMapping[ $slug ] ) )
            {
                $source              = 'feed';
                $selectedDealerField = $autoMapping[ $slug ];
                $isAutoSuggested     = true;
            }
            elseif ( $hasDefault && ( $field['req'] ?? '' ) === CanonicalFields::REQ_REQUIRED )
            {
                /* Required field with no feed match - pre-select the default
                 * so the dealer doesn't have to do anything. */
                $source = 'default';
            }

            $rows[ $slug ] = [
                'slug'                => $slug,
                'label'               => $field['label'] ?? $slug,
                'group'               => $field['group'] ?? '',
                'req'                 => $field['req'] ?? '',
                'has_default'         => $hasDefault,
                'published_default'   => $publishedDefault,
                'source'              => $source,
                'selected_dealer_field' => $selectedDealerField,
                'default_override'    => $defaultOverride,
                'is_auto_suggested'   => $isAutoSuggested,
            ];
        }

        /* Build grouped structure with rows nested by group. */
        $grouped = [];
        foreach ( CanonicalFields::grouped() as $groupKey => $group )
        {
            if ( empty( $group['fields'] ) ) { continue; }
            $groupRows = [];
            foreach ( $group['fields'] as $field )
            {
                $slug = $field['slug'];
                if ( isset( $rows[ $slug ] ) ) { $groupRows[] = $rows[ $slug ]; }
            }
            $grouped[] = [
                'key'    => $groupKey,
                'label'  => $group['label'],
                'rows'   => $groupRows,
            ];
        }

        /* Required-field summary. A required field counts as "satisfied"
         * if source=feed (with valid dealer field) OR source=default. */
        $requiredTotal    = 0;
        $requiredMapped   = 0;
        $requiredUnmapped = [];
        foreach ( $rows as $slug => $row )
        {
            if ( $row['req'] !== CanonicalFields::REQ_REQUIRED ) { continue; }
            $requiredTotal++;
            $satisfied = ( $row['source'] === 'feed' && $row['selected_dealer_field'] !== '' )
                      || ( $row['source'] === 'default' );
            if ( $satisfied )
            {
                $requiredMapped++;
            }
            else
            {
                $requiredUnmapped[] = [ 'slug' => $slug, 'label' => $row['label'] ];
            }
        }

        /* Used dealer fields count (for the "X of Y of your fields used" stat). */
        $usedDealerFields = [];
        foreach ( $rows as $row )
        {
            if ( $row['source'] === 'feed' && $row['selected_dealer_field'] !== '' )
            {
                $usedDealerFields[ $row['selected_dealer_field'] ] = true;
            }
        }

        /* Auto-suggested count. */
        $autoCount = 0;
        foreach ( $rows as $row )
        {
            if ( $row['is_auto_suggested'] ) { $autoCount++; }
        }

        return [
            'urls'              => $this->wizardUrls(),
            'csrfKey'           => \IPS\Session::i()->csrfKey,
            'discovered'        => $discovered,
            'discovered_count'  => count( $discovered ),
            'sample_for'        => $sampleFor,
            'sample_for_json'   => json_encode( $sampleFor, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS ),
            'grouped'           => $grouped,
            'auto_count'        => $autoCount,
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

if ( str_contains( $content, "Step 3 - Field Mapping with Auto-Suggest (v154 with defaults)" ) )
{
    echo "  step3 section: already at v154\n";
}
else
{
    /* Find the start and end of the existing step 3 section. */
    $startPos = strpos( $content, $startMarker );
    if ( $startPos === false )
    {
        fwrite( STDERR, "ERROR: step 3 section start marker not found\n" );
        exit( 1 );
    }

    $endPos = strpos( $content, $endMarker, $startPos );
    if ( $endPos === false )
    {
        fwrite( STDERR, "ERROR: helpers section start marker not found after step 3\n" );
        exit( 1 );
    }

    /* Replace from $startMarker through (and including) $endMarker
     * with the new block (which itself ends with the same Helpers
     * marker so that section boundary is preserved). */
    $oldSection = substr( $content, $startPos, ( $endPos - $startPos ) + strlen( $endMarker ) );
    $content = str_replace( $oldSection, $newStep3Block, $content );

    echo "  step3 section: replaced with v154 block\n";
    $applied++;
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
    fwrite( STDERR, "WARNING: lint failed - patch may have produced invalid PHP\n" );
    exit( 1 );
}

exit( 0 );
