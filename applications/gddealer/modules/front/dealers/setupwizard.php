<?php
/**
 * @brief       GD Dealer Manager - Feed Setup Wizard Controller
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       v1.0.149
 * @updated     v1.0.150 - Added step 2 (test fetch + parse + field
 *              discovery). Auto-fetch on first arrival; manual
 *              "Re-fetch" button on subsequent visits.
 *
 * Multi-step wizard that walks dealers through configuring their feed.
 * Replaces the raw JSON textarea on the legacy Feed Settings tab.
 *
 * Steps:
 *   1. Feed Input        - URL or paste, format, auth (v149)
 *   2. Test Fetch + Parse - HTTP fetch, format detection, field discovery (THIS SHIP)
 *   3. Field Mapping      - auto-suggest + manual override per field (v151)
 *   4. Validate Sample    - run validator on parsed records (v152)
 *   5. Preview + Save     - show 5-row preview, persist field map (v153)
 *
 * State is persisted in gd_dealer_feed_config.wizard_state_json. The
 * wizard_step column tracks the highest step completed; this is used
 * both to render the progress indicator and to gate the user from
 * jumping ahead to steps that depend on prior data.
 */

namespace IPS\gddealer\modules\front\dealers;

use IPS\gddealer\Dealer\Dealer;
use IPS\gddealer\Feed\FeedFetcher;
use IPS\gddealer\Feed\Parser\XmlParser;
use IPS\gddealer\Feed\Parser\JsonParser;
use IPS\gddealer\Feed\Parser\CsvParser;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _setupwizard extends \IPS\Dispatcher\Controller
{
    use \IPS\gddealer\Traits\DealerShellTrait;

    public static bool $csrfProtected = TRUE;

    /** Current dealer loaded from the logged-in member */
    protected ?Dealer $dealer = null;

    /**
     * Total number of wizard steps. Currently steps 1-2 have UIs.
     */
    public const TOTAL_STEPS = 5;

    public function execute(): void
    {
        $member = \IPS\Member::loggedIn();

        if ( !$member->member_id )
        {
            \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join' ) );
            return;
        }

        if ( $member->isAdmin() )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers', 'admin' )
            );
            return;
        }

        try
        {
            $this->dealer = Dealer::load( (int) $member->member_id );
        }
        catch ( \OutOfRangeException )
        {
            $this->dealer = null;
        }

        if ( $this->dealer === null && Dealer::isDealerMember( $member ) )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join&do=register' )
            );
            return;
        }

        if ( $this->dealer === null )
        {
            \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join' ) );
            return;
        }

        parent::execute();
    }

    /**
     * Default route. Routes the dealer to the highest unfinished step.
     */
    protected function manage(): void
    {
        $cfg = $this->loadFeedConfig();
        $highest = isset( $cfg['wizard_step'] ) ? (int) $cfg['wizard_step'] : 0;

        /* Land on step 2 if step 1 is done; otherwise step 1. */
        if ( $highest >= 1 )
        {
            $this->step2();
        }
        else
        {
            $this->step1();
        }
    }

    /* ============================================================
     * Step 1 - Feed Input
     * ============================================================ */

    protected function step1(): void
    {
        $cfg = $this->loadFeedConfig();

        $values = [
            'mode'             => 'url',
            'feed_url'         => isset( $cfg['feed_url'] ) ? (string) $cfg['feed_url'] : '',
            'feed_format'      => isset( $cfg['feed_format'] ) && (string) $cfg['feed_format'] !== '' ? (string) $cfg['feed_format'] : 'xml',
            'auth_type'        => isset( $cfg['auth_type'] ) && (string) $cfg['auth_type'] !== '' ? (string) $cfg['auth_type'] : 'none',
            'auth_credentials' => isset( $cfg['auth_credentials'] ) ? (string) $cfg['auth_credentials'] : '',
            'paste_body'       => '',
        ];

        $state = $this->loadWizardState();
        if ( !empty( $state['mode'] ) )
        {
            $values['mode'] = (string) $state['mode'];
        }
        if ( !empty( $state['paste_body'] ) )
        {
            $values['paste_body'] = (string) $state['paste_body'];
        }

        $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep1(
            $this->wizardData( 1 ),
            $values
        );
        $this->output( 'setupWizard', $body );
    }

    protected function saveStep1(): void
    {
        \IPS\Session::i()->csrfCheck();

        $mode             = strtolower( trim( (string) ( \IPS\Request::i()->mode ?? 'url' ) ) );
        $feedUrl          = trim( (string) ( \IPS\Request::i()->feed_url ?? '' ) );
        $feedFormat       = strtolower( trim( (string) ( \IPS\Request::i()->feed_format ?? '' ) ) );
        $authType         = strtolower( trim( (string) ( \IPS\Request::i()->auth_type ?? 'none' ) ) );
        $authCredentials  = trim( (string) ( \IPS\Request::i()->auth_credentials ?? '' ) );
        $pasteBody        = (string) ( \IPS\Request::i()->paste_body ?? '' );

        $errors = [];

        if ( !in_array( $mode, [ 'url', 'paste' ], true ) )
        {
            $errors[] = 'Please choose either "Fetch from URL" or "Paste feed body".';
            $mode = 'url';
        }

        if ( !in_array( $feedFormat, [ 'xml', 'json', 'csv' ], true ) )
        {
            $errors[] = 'Please choose a feed format (XML, JSON, or CSV).';
        }

        if ( $mode === 'url' )
        {
            if ( $feedUrl === '' )
            {
                $errors[] = 'Feed URL is required when fetching from a URL.';
            }
            elseif ( !preg_match( '#^https?://#i', $feedUrl ) )
            {
                $errors[] = 'Feed URL must start with http:// or https://.';
            }

            if ( !in_array( $authType, [ 'none', 'basic', 'api_key' ], true ) )
            {
                $errors[] = 'Invalid authentication type.';
                $authType = 'none';
            }

            if ( $authType !== 'none' && $authCredentials === '' )
            {
                $errors[] = 'Authentication credentials are required when auth type is set.';
            }
        }
        else
        {
            if ( $pasteBody === '' )
            {
                $errors[] = 'Please paste a feed body, or switch to "Fetch from URL".';
            }
            elseif ( strlen( $pasteBody ) > 10 * 1024 * 1024 )
            {
                $errors[] = 'Pasted feed body exceeds 10 MB. Use the URL fetch mode for large feeds.';
            }
        }

        if ( !empty( $errors ) )
        {
            $values = [
                'mode' => $mode, 'feed_url' => $feedUrl, 'feed_format' => $feedFormat,
                'auth_type' => $authType, 'auth_credentials' => $authCredentials,
                'paste_body' => $pasteBody,
            ];
            $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep1(
                $this->wizardData( 1 ),
                $values,
                $errors
            );
            $this->output( 'setupWizard', $body );
            return;
        }

        $update = [ 'feed_format' => $feedFormat ];
        if ( $mode === 'url' )
        {
            $update['feed_url']         = $feedUrl;
            $update['auth_type']        = $authType;
            $update['auth_credentials'] = $authCredentials;
        }
        $update['wizard_step'] = max( 1, (int) ( $this->loadFeedConfig()['wizard_step'] ?? 0 ) );

        try
        {
            \IPS\Db::i()->update( 'gd_dealer_feed_config', $update,
                [ 'dealer_id=?', (int) $this->dealer->dealer_id ]
            );
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'wizard saveStep1 update failed: ' . $e->getMessage(), 'gddealer_setupwizard' ); } catch ( \Throwable ) {}
            $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep1(
                $this->wizardData( 1 ),
                [ 'mode' => $mode, 'feed_url' => $feedUrl, 'feed_format' => $feedFormat,
                  'auth_type' => $authType, 'auth_credentials' => $authCredentials, 'paste_body' => $pasteBody ],
                [ 'Could not save your input. Please try again or contact support.' ]
            );
            $this->output( 'setupWizard', $body );
            return;
        }

        /* Cache mode + paste body in wizard_state_json. Step 2 reads this
         * to know whether to fetch from URL or use the cached paste body. */
        $state = $this->loadWizardState();
        $state['mode']       = $mode;
        $state['paste_body'] = $mode === 'paste' ? $pasteBody : '';
        /* Clear any prior step 2 results - the input changed. */
        unset( $state['step2_fetch'], $state['step2_records'], $state['step2_fields'] );
        $this->saveWizardState( $state );

        \IPS\Output::i()->redirect(
            \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard', 'front', 'dealers_setup_wizard' )
                ->setQueryString( 'do', 'step2' )
        );
    }

    /* ============================================================
     * Step 2 - Test Fetch + Parse + Field Discovery
     * ============================================================ */

    /**
     * Render step 2. On first arrival (no cached fetch results), runs
     * the fetch+parse automatically. On subsequent visits, shows cached
     * results with a "Re-fetch" button to refresh.
     *
     * Gating: requires wizard_step >= 1. Otherwise redirect to step 1.
     */
    protected function step2(): void
    {
        $cfg = $this->loadFeedConfig();
        $highest = isset( $cfg['wizard_step'] ) ? (int) $cfg['wizard_step'] : 0;
        if ( $highest < 1 )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard', 'front', 'dealers_setup_wizard' )
            );
            return;
        }

        $state = $this->loadWizardState();

        /* Auto-fetch on first arrival, or when explicitly requested. */
        $forceRefetch = (int) ( \IPS\Request::i()->refetch ?? 0 ) === 1;
        $hasCached = !empty( $state['step2_fetch'] );

        if ( !$hasCached || $forceRefetch )
        {
            $this->performStep2Fetch();
            $state = $this->loadWizardState();  /* reload after fetch */
        }

        $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep2(
            $this->wizardData( 2 ),
            [
                'mode'         => isset( $state['mode'] ) ? (string) $state['mode'] : 'url',
                'feed_url'     => isset( $cfg['feed_url'] ) ? (string) $cfg['feed_url'] : '',
                'feed_format'  => isset( $cfg['feed_format'] ) ? (string) $cfg['feed_format'] : '',
                'fetch'        => isset( $state['step2_fetch'] ) && is_array( $state['step2_fetch'] ) ? $state['step2_fetch'] : null,
                'records'      => isset( $state['step2_records'] ) && is_array( $state['step2_records'] ) ? $state['step2_records'] : [],
                'fields'       => isset( $state['step2_fields'] ) && is_array( $state['step2_fields'] ) ? $state['step2_fields'] : [],
                'parse_error'  => isset( $state['step2_parse_error'] ) ? (string) $state['step2_parse_error'] : '',
            ]
        );
        $this->output( 'setupWizard', $body );
    }

    /**
     * Save step 2 (advance wizard_step to 2). The actual fetch is done
     * inside step2() / performStep2Fetch(); this is just the "continue"
     * button handler that gates progression to step 3.
     */
    protected function saveStep2(): void
    {
        \IPS\Session::i()->csrfCheck();

        $state = $this->loadWizardState();

        /* Don't allow progressing if the fetch+parse never succeeded. */
        if ( empty( $state['step2_fetch'] ) || empty( $state['step2_fetch']['ok'] ) || !empty( $state['step2_parse_error'] ) )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard', 'front', 'dealers_setup_wizard' )
                    ->setQueryString( 'do', 'step2' )
            );
            return;
        }

        $cfg = $this->loadFeedConfig();
        $update = [ 'wizard_step' => max( 2, (int) ( $cfg['wizard_step'] ?? 0 ) ) ];

        try
        {
            \IPS\Db::i()->update( 'gd_dealer_feed_config', $update,
                [ 'dealer_id=?', (int) $this->dealer->dealer_id ]
            );
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'wizard saveStep2 update failed: ' . $e->getMessage(), 'gddealer_setupwizard' ); } catch ( \Throwable ) {}
        }

        /* v150 ships step 2 only - step 3 doesn't exist yet. Land back
         * on step 2 with a success flash so the dealer sees progress. */
        \IPS\Output::i()->redirect(
            \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard', 'front', 'dealers_setup_wizard' )
                ->setQueryString( [ 'do' => 'step2', 'saved' => 1 ] )
        );
    }

    /**
     * Run the actual fetch + parse + field discovery and cache results
     * to wizard_state_json. Called by step2() on auto-fetch or
     * explicit ?refetch=1.
     */
    protected function performStep2Fetch(): void
    {
        $cfg = $this->loadFeedConfig();
        $state = $this->loadWizardState();

        $mode       = isset( $state['mode'] ) ? (string) $state['mode'] : 'url';
        $feedFormat = isset( $cfg['feed_format'] ) ? (string) $cfg['feed_format'] : 'xml';
        $body       = '';
        $fetchMeta  = null;

        if ( $mode === 'url' )
        {
            $url       = isset( $cfg['feed_url'] ) ? (string) $cfg['feed_url'] : '';
            $authType  = isset( $cfg['auth_type'] ) ? (string) $cfg['auth_type'] : 'none';
            $authCreds = isset( $cfg['auth_credentials'] ) ? (string) $cfg['auth_credentials'] : '';

            $fetch = FeedFetcher::fetch( $url, $authType, $authCreds );
            $body  = (string) $fetch['body'];

            /* Don't cache the full body in state - just metadata + a
             * preview snippet. The body itself goes into step2_records
             * after parsing. */
            $fetchMeta = [
                'ok'             => $fetch['ok'],
                'http_status'    => $fetch['http_status'],
                'content_type'   => $fetch['content_type'],
                'body_bytes'     => $fetch['body_bytes'],
                'truncated'      => $fetch['truncated'],
                'duration_ms'    => $fetch['duration_ms'],
                'error'          => $fetch['error'],
                'preview'        => substr( $body, 0, 800 ),
            ];
        }
        else
        {
            /* Paste mode - fake a fetch result from the cached body. */
            $body = isset( $state['paste_body'] ) ? (string) $state['paste_body'] : '';
            $fetchMeta = [
                'ok'           => $body !== '',
                'http_status'  => 0,
                'content_type' => '(pasted)',
                'body_bytes'   => strlen( $body ),
                'truncated'    => false,
                'duration_ms'  => 0,
                'error'        => $body === '' ? 'No pasted feed body found.' : null,
                'preview'      => substr( $body, 0, 800 ),
            ];
        }

        /* If fetch failed, save the metadata and bail before parsing. */
        if ( !$fetchMeta['ok'] || $body === '' )
        {
            $state['step2_fetch']       = $fetchMeta;
            $state['step2_records']     = [];
            $state['step2_fields']      = [];
            $state['step2_parse_error'] = '';
            $this->saveWizardState( $state );
            return;
        }

        /* Parse the body. */
        $records = [];
        $parseError = '';
        try
        {
            $records = match ( strtolower( $feedFormat ) ) {
                'xml'  => XmlParser::parse( $body ),
                'json' => JsonParser::parse( $body ),
                'csv'  => CsvParser::parse( $body ),
                default => throw new \RuntimeException( "Unknown feed format: '{$feedFormat}'." ),
            };
        }
        catch ( \Throwable $e )
        {
            $parseError = $e->getMessage();
        }

        /* Cap records cached in state to first 10 for step 3 sample. */
        $sample = array_slice( $records, 0, 10 );
        $fields = FeedFetcher::discoverFields( $sample );

        $state['step2_fetch']       = $fetchMeta;
        $state['step2_records']     = $sample;
        $state['step2_fields']      = $fields;
        $state['step2_parse_error'] = $parseError;
        $state['step2_total_count'] = count( $records );
        $this->saveWizardState( $state );
    }

    /* ============================================================
     * Helpers
     * ============================================================ */

    /**
     * @return array<string, mixed>
     */
    protected function loadFeedConfig(): array
    {
        try
        {
            $row = \IPS\Db::i()->select( '*', 'gd_dealer_feed_config',
                [ 'dealer_id=?', (int) $this->dealer->dealer_id ]
            )->first();
            return is_array( $row ) ? $row : [];
        }
        catch ( \Throwable )
        {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadWizardState(): array
    {
        $cfg = $this->loadFeedConfig();
        if ( empty( $cfg['wizard_state_json'] ) ) { return []; }
        $decoded = json_decode( (string) $cfg['wizard_state_json'], true );
        return is_array( $decoded ) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $state
     */
    protected function saveWizardState( array $state ): void
    {
        try
        {
            \IPS\Db::i()->update( 'gd_dealer_feed_config',
                [ 'wizard_state_json' => json_encode( $state, JSON_UNESCAPED_SLASHES ) ],
                [ 'dealer_id=?', (int) $this->dealer->dealer_id ]
            );
        }
        catch ( \Throwable $e )
        {
            try { \IPS\Log::log( 'saveWizardState failed: ' . $e->getMessage(), 'gddealer_setupwizard' ); } catch ( \Throwable ) {}
        }
    }

    /**
     * Build the wizard meta data passed to every step template.
     *
     * @return array<string, mixed>
     */
    protected function wizardData( int $currentStep ): array
    {
        $cfg = $this->loadFeedConfig();
        $highestSaved = isset( $cfg['wizard_step'] ) ? (int) $cfg['wizard_step'] : 0;

        $steps = [
            [ 'num' => 1, 'key' => 'step1', 'label' => 'Feed Input',     'desc' => 'URL or paste your feed body' ],
            [ 'num' => 2, 'key' => 'step2', 'label' => 'Test & Parse',   'desc' => 'Fetch and discover fields' ],
            [ 'num' => 3, 'key' => 'step3', 'label' => 'Field Mapping',  'desc' => 'Match your fields to ours' ],
            [ 'num' => 4, 'key' => 'step4', 'label' => 'Validate',       'desc' => 'Check sample records' ],
            [ 'num' => 5, 'key' => 'step5', 'label' => 'Preview & Save', 'desc' => 'Confirm and finish' ],
        ];

        return [
            'totalSteps'     => self::TOTAL_STEPS,
            'currentStep'    => $currentStep,
            'highestSaved'   => $highestSaved,
            'completed'      => !empty( $cfg['wizard_completed_at'] ),
            'steps'          => $steps,
            'savedFlash'     => (bool) (int) ( \IPS\Request::i()->saved ?? 0 ),
        ];
    }
}

class setupwizard extends _setupwizard {}
