<?php
/**
 * @brief       GD Dealer Manager - Feed Setup Wizard Controller
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       v1.0.149
 *
 * Multi-step wizard that walks dealers through configuring their feed.
 * Replaces the raw JSON textarea on the legacy Feed Settings tab.
 *
 * Steps (v1.0.149 ships ONLY step 1 - subsequent steps come in
 * v1.0.150-v1.0.153):
 *   1. Feed Input        - URL or paste, format, auth (THIS SHIP)
 *   2. Test Fetch + Parse - HTTP fetch, format detection, field discovery (v150)
 *   3. Field Mapping      - auto-suggest + manual override per field (v151)
 *   4. Validate Sample    - run validator on parsed records (v152)
 *   5. Preview + Save     - show 5-row preview, persist field map (v153)
 *
 * State for the entire wizard is persisted in
 * gd_dealer_feed_config.wizard_state_json (added in v148). The
 * wizard_step column tracks the highest step completed; this is used
 * both to render the progress indicator and to gate the user from
 * jumping ahead to steps that depend on prior data.
 *
 * Routes:
 *   GET  /dealers/setup-wizard            - render current step
 *   GET  /dealers/setup-wizard?do=step1   - render step 1 explicitly
 *   POST /dealers/setup-wizard?do=saveStep1 - persist step 1 input
 */

namespace IPS\gddealer\modules\front\dealers;

use IPS\gddealer\Dealer\Dealer;
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
     * Total number of wizard steps. Currently only step 1 has a UI; the
     * remaining steps are stubbed and will be filled in by future ships.
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
     * Default route - render step 1 (or the highest unfinished step
     * once later ships add steps 2-5).
     */
    protected function manage(): void
    {
        $this->step1();
    }

    /**
     * Step 1: Feed Input.
     *
     * Renders a form letting the dealer choose between fetching the feed
     * from a URL or pasting the body directly. Captures format, auth type,
     * and credentials. On submit (do=saveStep1), persists to feed_url,
     * feed_format, auth_type, auth_credentials, and updates wizard_step.
     */
    protected function step1(): void
    {
        $cfg = $this->loadFeedConfig();

        /* Pre-populate from existing feed config if any. */
        $values = [
            'mode'             => 'url',  /* 'url' or 'paste' */
            'feed_url'         => isset( $cfg['feed_url'] ) ? (string) $cfg['feed_url'] : '',
            'feed_format'      => isset( $cfg['feed_format'] ) && (string) $cfg['feed_format'] !== '' ? (string) $cfg['feed_format'] : 'xml',
            'auth_type'        => isset( $cfg['auth_type'] ) && (string) $cfg['auth_type'] !== '' ? (string) $cfg['auth_type'] : 'none',
            'auth_credentials' => isset( $cfg['auth_credentials'] ) ? (string) $cfg['auth_credentials'] : '',
            'paste_body'       => '',
        ];

        /* If wizard_state_json has cached paste body, restore it. */
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
            $this->wizardData(),
            $values
        );
        $this->output( 'setupWizard', $body );
    }

    /**
     * POST handler for step 1.
     */
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
            /* Re-render step 1 with errors and the user's input preserved. */
            $values = [
                'mode' => $mode, 'feed_url' => $feedUrl, 'feed_format' => $feedFormat,
                'auth_type' => $authType, 'auth_credentials' => $authCredentials,
                'paste_body' => $pasteBody,
            ];
            $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->setupWizardStep1(
                $this->wizardData(),
                $values,
                $errors
            );
            $this->output( 'setupWizard', $body );
            return;
        }

        /* Persist to gd_dealer_feed_config. Only the URL-mode fields go
         * to permanent columns; paste body lives in wizard_state_json
         * (it'll be re-parsed at step 2 to pull out the field list). */
        $update = [
            'feed_format' => $feedFormat,
        ];
        if ( $mode === 'url' )
        {
            $update['feed_url']         = $feedUrl;
            $update['auth_type']        = $authType;
            $update['auth_credentials'] = $authCredentials;
        }
        $update['wizard_step'] = 1;

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
                $this->wizardData(),
                [ 'mode' => $mode, 'feed_url' => $feedUrl, 'feed_format' => $feedFormat,
                  'auth_type' => $authType, 'auth_credentials' => $authCredentials, 'paste_body' => $pasteBody ],
                [ 'Could not save your input. Please try again or contact support.' ]
            );
            $this->output( 'setupWizard', $body );
            return;
        }

        /* Cache mode + paste body in wizard_state_json. */
        $state = $this->loadWizardState();
        $state['mode']       = $mode;
        $state['paste_body'] = $mode === 'paste' ? $pasteBody : '';
        $this->saveWizardState( $state );

        /* Step 1 saved. Future ships will redirect to step 2; for v149
         * we redirect back to step 1 with a success indicator so the
         * dealer sees confirmation. */
        \IPS\Output::i()->redirect(
            \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=setupwizard', 'front', 'dealers_setup_wizard' )->setQueryString( 'saved', 1 )
        );
    }

    /* ============================================================
     * Helpers
     * ============================================================ */

    /**
     * Load the dealer's gd_dealer_feed_config row as an array.
     *
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
     * Decode the wizard_state_json blob to an array.
     *
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
     * Persist a wizard state array back to wizard_state_json.
     *
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
     * Includes step list, current step, completion state, and the
     * "saved successfully" flag pulled from query string.
     *
     * @return array<string, mixed>
     */
    protected function wizardData(): array
    {
        $cfg = $this->loadFeedConfig();
        $currentStep = isset( $cfg['wizard_step'] ) ? (int) $cfg['wizard_step'] : 0;

        $steps = [
            [ 'num' => 1, 'key' => 'step1', 'label' => 'Feed Input',     'desc' => 'URL or paste your feed body' ],
            [ 'num' => 2, 'key' => 'step2', 'label' => 'Test & Parse',   'desc' => 'Fetch and discover fields' ],
            [ 'num' => 3, 'key' => 'step3', 'label' => 'Field Mapping',  'desc' => 'Match your fields to ours' ],
            [ 'num' => 4, 'key' => 'step4', 'label' => 'Validate',       'desc' => 'Check sample records' ],
            [ 'num' => 5, 'key' => 'step5', 'label' => 'Preview & Save', 'desc' => 'Confirm and finish' ],
        ];

        return [
            'totalSteps'     => self::TOTAL_STEPS,
            'currentStep'    => 1,                 /* v149 only renders step 1 */
            'highestSaved'   => $currentStep,
            'completed'      => !empty( $cfg['wizard_completed_at'] ),
            'steps'          => $steps,
            'savedFlash'     => (bool) (int) ( \IPS\Request::i()->saved ?? 0 ),
        ];
    }
}

class setupwizard extends _setupwizard {}
