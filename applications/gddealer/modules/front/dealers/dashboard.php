<?php
/**
 * @brief       GD Dealer Manager — Frontend Dealer Dashboard
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       16 Apr 2026
 *
 * Section 3.11 of the spec. Accessible at /dealers/dashboard. Visible
 * only to members with an active gd_dealer_feed_config row. Six tabs:
 * Overview, Feed Settings, Listings, Unmatched UPCs, Analytics,
 * Subscription.
 *
 * Until IPS Commerce + Stripe is live the dealer row is created via
 * the ACP "Manual Onboard" action; when feed_url is NULL or empty we
 * render an "onboarding incomplete" banner with a Feed Settings CTA.
 */

namespace IPS\gddealer\modules\front\dealers;

use IPS\gddealer\Dealer\Dealer;
use IPS\gddealer\Feed\Importer;
use IPS\gddealer\Listing\Listing;
use IPS\gddealer\Log\ImportLog;
use IPS\gddealer\Unmatched\UnmatchedUpc;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _dashboard extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	/** Current dealer loaded from the logged-in member */
	protected ?Dealer $dealer = null;

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

		/* Member is in a dealer group (via Commerce subscription) but has
		   not yet completed the one-time self-service registration form. */
		if ( $this->dealer === null && Dealer::isDealerMember( $member ) )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join&do=register' )
			);
			return;
		}

		if ( $this->dealer === null )
		{
			$contactEmail = (string) ( \IPS\Settings::i()->gddealer_help_contact ?: 'dealers@gunrack.deals' );
			\IPS\Output::i()->title  = $member->language()->addToStack( 'gddealer_frontend_dashboard_title' );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->notSubscribed(
				(string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join' ),
				$contactEmail
			);
			return;
		}

		parent::execute();
	}

	/** Default tab = overview */
	protected function manage()
	{
		$this->overview();
	}

	/* ---------------- Tab: Overview ---------------- */

	protected function overview()
	{
		$dealer = $this->dealer;

		$active = $out = $unmatched = $clicks7 = $clicks30 = 0;
		try
		{
			$active = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings', [ 'dealer_id=? AND listing_status=?', (int) $dealer->dealer_id, Listing::STATUS_ACTIVE ] )->first();
		}
		catch ( \Exception ) {}
		try
		{
			$out = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings', [ 'dealer_id=? AND listing_status=?', (int) $dealer->dealer_id, Listing::STATUS_OUT_OF_STOCK ] )->first();
		}
		catch ( \Exception ) {}
		try
		{
			$unmatched = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_unmatched_upcs', [ 'dealer_id=? AND admin_excluded=?', (int) $dealer->dealer_id, 0 ] )->first();
		}
		catch ( \Exception ) {}
		try
		{
			$clicks7 = (int) \IPS\Db::i()->select( 'SUM(click_count_7d)', 'gd_dealer_listings', [ 'dealer_id=?', (int) $dealer->dealer_id ] )->first();
		}
		catch ( \Exception ) {}
		try
		{
			$clicks30 = (int) \IPS\Db::i()->select( 'SUM(click_count_30d)', 'gd_dealer_listings', [ 'dealer_id=?', (int) $dealer->dealer_id ] )->first();
		}
		catch ( \Exception ) {}

		$lastLogRow = [];
		try
		{
			$lastLogRow = \IPS\Db::i()->select( '*', 'gd_dealer_import_log', [ 'dealer_id=?', (int) $dealer->dealer_id ], 'run_start DESC', [ 0, 1 ] )->first();
		}
		catch ( \Exception ) {}

		$lastImport = null;
		if ( !empty( $lastLogRow ) )
		{
			$lastImport = [
				'run_start'         => (string) ( $lastLogRow['run_start']         ?? '' ),
				'status'            => (string) ( $lastLogRow['status']            ?? '' ),
				'records_total'     => (int)    ( $lastLogRow['records_total']     ?? 0 ),
				'records_created'   => (int)    ( $lastLogRow['records_created']   ?? 0 ),
				'records_updated'   => (int)    ( $lastLogRow['records_updated']   ?? 0 ),
				'records_unchanged' => (int)    ( $lastLogRow['records_unchanged'] ?? 0 ),
				'records_unmatched' => (int)    ( $lastLogRow['records_unmatched'] ?? 0 ),
				'has_errors'        => !empty( $lastLogRow['error_log'] ),
			];
		}

		$overview = [
			'tier'                 => (string) $dealer->subscription_tier,
			'tier_label'           => ucfirst( (string) $dealer->subscription_tier ),
			'active_listings'      => $active,
			'out_of_stock'         => $out,
			'unmatched'            => $unmatched,
			'unmatched_count'      => $unmatched,
			'clicks_7d'            => $clicks7,
			'clicks_30d'           => $clicks30,
			'onboarding_incomplete'=> empty( $dealer->feed_url ),
			'last_import'          => $lastImport,
			'profile_url'          => (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( (string) $dealer->dealer_slug )
			),
			'customize_url'        => (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=dashboard&do=customize'
			),
		];

		/* Dealer's personal card theme. Overrides admin global card colors
		   for their own dashboard — we apply styles inline on each stat
		   card rather than through the global CSS variable so the admin
		   settings remain the default for dealers who pick "default". */
		$prefsRaw = json_decode( (string) ( $dealer->dealer_dashboard_prefs ?? '{}' ), true );
		if ( !is_array( $prefsRaw ) ) { $prefsRaw = []; }
		$cardTheme = (string) ( $prefsRaw['card_theme'] ?? 'default' );

		$s = \IPS\Settings::i();
		$cardStyles = match( $cardTheme ) {
			'dark'   => [ 'bg' => '#1e2d3d', 'color' => '#ffffff', 'border' => '#2d4a6b', 'label' => '#94a3b8' ],
			'accent' => [ 'bg' => (string) ( $s->gddealer_color_primary ?: '#2563eb' ), 'color' => '#ffffff', 'border' => 'transparent', 'label' => 'rgba(255,255,255,0.8)' ],
			default  => [
				'bg'     => (string) ( $s->gddealer_color_card_bg     ?: '#ffffff' ),
				'color'  => 'inherit',
				'border' => (string) ( $s->gddealer_color_card_border ?: '#e0e0e0' ),
				'label'  => '#6b7280',
			],
		};
		$overview['card_styles']   = $cardStyles;
		$overview['numbers_light'] = in_array( $cardTheme, [ 'dark', 'accent' ], true );

		/* Admin-configurable quick-link list with fallback defaults. */
		$rawLinks = json_decode( (string) ( $s->gddealer_quicklinks ?: '[]' ), true );
		if ( !is_array( $rawLinks ) || empty( $rawLinks ) )
		{
			$rawLinks = [
				[ 'icon' => 'fa-solid fa-user',           'label' => 'View Public Profile',  'url_type' => 'profile',       'custom_url' => '' ],
				[ 'icon' => 'fa-solid fa-rss',            'label' => 'Feed Settings',         'url_type' => 'feed_settings', 'custom_url' => '' ],
				[ 'icon' => 'fa-solid fa-circle-question','label' => 'Help & Setup Guide',    'url_type' => 'help',          'custom_url' => '' ],
				[ 'icon' => 'fa-solid fa-sliders',        'label' => 'Customize Dashboard',   'url_type' => 'customize',     'custom_url' => '' ],
			];
		}

		$resolvedLinks = [];
		foreach ( $rawLinks as $link )
		{
			$type = (string) ( $link['url_type'] ?? 'custom' );
			$url  = match( $type ) {
				'profile'       => $overview['profile_url'],
				'feed_settings' => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=feedSettings' ),
				'listings'      => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=listings' ),
				'unmatched'     => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=unmatched' ),
				'analytics'     => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=analytics' ),
				'reviews'       => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=reviews' ),
				'help'          => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=help' ),
				'subscription'  => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=subscription' ),
				'customize'     => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=customize' ),
				default         => (string) ( $link['custom_url'] ?? '#' ),
			};

			$resolvedLinks[] = [
				'icon'     => htmlspecialchars( (string) ( $link['icon']  ?? 'fa-solid fa-link' ), ENT_QUOTES ),
				'label'    => htmlspecialchars( (string) ( $link['label'] ?? 'Link' ),             ENT_QUOTES ),
				'url'      => $url,
				'external' => ( $type === 'custom' ),
			];
		}
		$overview['quick_links'] = $resolvedLinks;

		$this->output( 'overview', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->overview(
			$this->dealerSummary(),
			$overview,
			$this->tabUrls(),
			$this->dashboardPrefs()
		) );
	}

	/* ---------------- Tab: Customize Dashboard ---------------- */

	protected function customize(): void
	{
		$prefs = $this->dashboardPrefs();
		$saveUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=dashboard&do=saveCustomize'
		)->csrf();
		$cancelUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=dashboard&do=overview'
		);
		$csrfKey = (string) \IPS\Session::i()->csrfKey;

		$this->output( 'overview', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->dashboardCustomize(
			$prefs, $saveUrl, $cancelUrl, $csrfKey
		) );
	}

	protected function saveCustomize(): void
	{
		\IPS\Session::i()->csrfCheck();

		$req      = \IPS\Request::i();
		$theme    = (string) ( $req->card_theme ?? 'default' );
		$validTh  = [ 'default', 'dark', 'accent' ];
		if ( !in_array( $theme, $validTh, true ) ) { $theme = 'default'; }

		$prefs = [
			'show_active'      => (bool) ( $req->show_active      ?? false ),
			'show_outofstock'  => (bool) ( $req->show_outofstock  ?? false ),
			'show_unmatched'   => (bool) ( $req->show_unmatched   ?? false ),
			'show_clicks_7d'   => (bool) ( $req->show_clicks_7d   ?? false ),
			'show_clicks_30d'  => (bool) ( $req->show_clicks_30d  ?? false ),
			'show_last_import' => (bool) ( $req->show_last_import ?? false ),
			'show_profile_url' => (bool) ( $req->show_profile_url ?? false ),
			'card_theme'       => $theme,
		];

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_feed_config',
				[ 'dealer_dashboard_prefs' => json_encode( $prefs ) ],
				[ 'dealer_id=?', (int) $this->dealer->dealer_id ]
			);
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=overview' ),
			'gddealer_front_customize_saved'
		);
	}

	/**
	 * Load dashboard preferences for the current dealer. Returns the
	 * stored JSON merged over the defaults so missing keys never break
	 * the template.
	 */
	protected function dashboardPrefs(): array
	{
		$defaults = [
			'show_active'      => true,
			'show_outofstock'  => true,
			'show_unmatched'   => true,
			'show_clicks_7d'   => true,
			'show_clicks_30d'  => true,
			'show_last_import' => true,
			'show_profile_url' => true,
			'card_theme'       => 'default',
		];

		$raw = (string) ( $this->dealer->dealer_dashboard_prefs ?? '' );
		if ( $raw !== '' )
		{
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) )
			{
				$defaults = array_merge( $defaults, $decoded );
			}
		}

		$theme = $defaults['card_theme'];
		$themeStyles = match( $theme ) {
			'dark'   => [
				'card_bg'     => '#1e2d3d',
				'card_border' => '#334155',
				'card_color'  => '#ffffff',
				'card_label'  => '#94a3b8',
				'num_success' => '#86efac',
				'num_danger'  => '#fca5a5',
				'num_warning' => '#fcd34d',
				'num_default' => '#ffffff',
			],
			'accent' => [
				'card_bg'     => '#1e40af',
				'card_border' => '#1e3a8a',
				'card_color'  => '#ffffff',
				'card_label'  => '#bfdbfe',
				'num_success' => '#bbf7d0',
				'num_danger'  => '#fecaca',
				'num_warning' => '#fef3c7',
				'num_default' => '#ffffff',
			],
			default  => [
				'card_bg'     => '#ffffff',
				'card_border' => 'var(--i-border-color,#e0e0e0)',
				'card_color'  => '#111827',
				'card_label'  => '#6b7280',
				'num_success' => '#16a34a',
				'num_danger'  => '#dc2626',
				'num_warning' => '#f59e0b',
				'num_default' => 'inherit',
			],
		};
		$defaults = array_merge( $defaults, $themeStyles );

		return $defaults;
	}

	/* ---------------- Tab: Feed Settings ---------------- */

	protected function feedSettings()
	{
		$dealer = $this->dealer;

		$form = new \IPS\Helpers\Form( 'form', 'gddealer_front_feed_save' );
		$form->add( new \IPS\Helpers\Form\Url( 'gddealer_front_feed_url', $dealer->feed_url, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Select( 'gddealer_front_feed_format', $dealer->feed_format, TRUE, [
			'options' => [ 'xml' => 'XML', 'json' => 'JSON', 'csv' => 'CSV' ],
		] ) );
		$form->add( new \IPS\Helpers\Form\Select( 'gddealer_front_auth_type', $dealer->auth_type, TRUE, [
			'options' => [ 'none' => 'None', 'basic' => 'Basic Auth', 'apikey' => 'API Key', 'ftp' => 'FTP' ],
		] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_front_auth_credentials', $dealer->getCredentials() ?? '', FALSE, [
			'placeholder' => 'JSON: {"username":"...","password":"..."} or {"api_key":"..."}',
		] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gddealer_front_field_mapping', $dealer->field_mapping ?? '', FALSE, [
			'rows' => 10, 'placeholder' => '{"DEALER_FIELD":"canonical_field", "UPC":"upc", "PRICE":"dealer_price"}',
		] ) );

		if ( $values = $form->values() )
		{
			$dealer->feed_url    = (string) $values['gddealer_front_feed_url'];
			$dealer->feed_format = $values['gddealer_front_feed_format'];
			$dealer->auth_type   = $values['gddealer_front_auth_type'];

			$creds = trim( (string) $values['gddealer_front_auth_credentials'] );
			$dealer->setCredentials( $creds !== '' ? $creds : null );

			$mapJson = trim( (string) $values['gddealer_front_field_mapping'] );
			$dealer->field_mapping = ( $mapJson !== '' && json_decode( $mapJson ) !== null ) ? $mapJson : null;

			if ( !$dealer->active )
			{
				$dealer->active = 1;
			}

			$dealer->save();

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=feedSettings' ),
				'gddealer_front_feed_saved'
			);
			return;
		}

		$logs = [];
		foreach ( ImportLog::loadForDealer( (int) $dealer->dealer_id, 50 ) as $log )
		{
			$logs[] = [
				'run_start'        => (string) $log->run_start,
				'status'           => (string) $log->status,
				'records_total'    => (int) $log->records_total,
				'records_created'  => (int) $log->records_created,
				'records_updated'  => (int) $log->records_updated,
				'records_unchanged'=> (int) $log->records_unchanged,
				'records_unmatched'=> (int) $log->records_unmatched,
				'price_drops'      => (int) $log->price_drops,
				'error_log'        => (string) ( $log->error_log ?? '' ),
			];
		}

		$importUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=dashboard&do=manualImport'
		)->csrf();

		$this->output( 'feedSettings', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->feedSettings(
			$this->dealerSummary(),
			(string) $form,
			$logs,
			$importUrl,
			$this->tabUrls()
		) );
	}

	/** Runs a feed import on demand from the Feed Settings tab. */
	protected function manualImport()
	{
		\IPS\Session::i()->csrfCheck();

		$dealer = $this->dealer;
		if ( empty( $dealer->feed_url ) )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=feedSettings' ),
				'gddealer_front_feed_url_required'
			);
			return;
		}

		$log = Importer::run( $dealer );

		$msg = $log->status === 'completed'
			? sprintf( 'Import complete: %d total, %d new, %d updated, %d unmatched',
				(int) $log->records_total, (int) $log->records_created, (int) $log->records_updated, (int) $log->records_unmatched )
			: 'Import failed: ' . ( $log->error_log ?? 'unknown error' );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=feedSettings' ),
			$msg
		);
	}

	/* ---------------- Tab: Listings ---------------- */

	protected function listings()
	{
		$dealer = $this->dealer;

		$filter = (string) ( \IPS\Request::i()->filter ?? '' );
		$search = trim( (string) ( \IPS\Request::i()->q ?? '' ) );

		$where = [ [ 'dealer_id=?', (int) $dealer->dealer_id ] ];

		if ( in_array( $filter, [ Listing::STATUS_ACTIVE, Listing::STATUS_OUT_OF_STOCK, Listing::STATUS_SUSPENDED, Listing::STATUS_DISCONTINUED ], true ) )
		{
			$where[] = [ 'listing_status=?', $filter ];
		}
		elseif ( $filter === 'in_stock' )
		{
			$where[] = [ 'in_stock=?', 1 ];
		}
		elseif ( $filter === 'out_of_stock' )
		{
			$where[] = [ 'in_stock=?', 0 ];
		}

		if ( $search !== '' )
		{
			$where[] = [ 'upc LIKE ?', '%' . $search . '%' ];
		}

		$page    = max( 1, (int) ( \IPS\Request::i()->page ?? 1 ) );
		$perPage = 50;
		$offset  = ( $page - 1 ) * $perPage;

		$total = 0;
		try
		{
			$total = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_listings', $where )->first();
		}
		catch ( \Exception ) {}

		$rows = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_listings', $where, 'last_seen_in_feed DESC', [ $offset, $perPage ] ) as $r )
			{
				$rows[] = [
					'upc'            => (string) $r['upc'],
					'dealer_price'   => '$' . number_format( (float) $r['dealer_price'], 2 ),
					'in_stock'       => (bool) $r['in_stock'],
					'condition'      => (string) $r['condition'],
					'listing_status' => (string) $r['listing_status'],
					'last_updated'   => (string) ( $r['last_seen_in_feed'] ?? '' ),
				];
			}
		}
		catch ( \Exception ) {}

		if ( isset( \IPS\Request::i()->export ) )
		{
			$this->exportListingsCsv( $where );
			return;
		}

		$exportUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=dashboard&do=listings&export=1&filter=' . rawurlencode( $filter ) . '&q=' . rawurlencode( $search )
		);

		$pages = (int) ceil( $total / $perPage );
		$baseUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=dashboard&do=listings&filter=' . rawurlencode( $filter ) . '&q=' . rawurlencode( $search )
		);

		$this->output( 'listings', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->listings(
			$this->dealerSummary(),
			$rows,
			$total,
			$page,
			$pages,
			$baseUrl,
			$filter,
			$search,
			$exportUrl,
			$this->tabUrls()
		) );
	}

	/** Stream a CSV export of the current listing query. */
	protected function exportListingsCsv( array $where ): void
	{
		$fh = fopen( 'php://temp', 'r+' );
		fputcsv( $fh, [ 'upc', 'dealer_price', 'in_stock', 'condition', 'listing_status', 'last_seen_in_feed' ] );
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_listings', $where, 'last_seen_in_feed DESC' ) as $r )
			{
				fputcsv( $fh, [
					(string) $r['upc'],
					number_format( (float) $r['dealer_price'], 2, '.', '' ),
					(int) $r['in_stock'],
					(string) $r['condition'],
					(string) $r['listing_status'],
					(string) ( $r['last_seen_in_feed'] ?? '' ),
				] );
			}
		}
		catch ( \Exception ) {}

		rewind( $fh );
		$csv = stream_get_contents( $fh );
		fclose( $fh );

		\IPS\Output::i()->sendOutput( $csv, 200, 'text/csv', [
			'Content-Disposition' => 'attachment; filename="dealer-listings-' . (int) $this->dealer->dealer_id . '.csv"',
		]);
	}

	/* ---------------- Tab: Unmatched UPCs ---------------- */

	protected function unmatched()
	{
		$dealer = $this->dealer;

		$rows = UnmatchedUpc::loadForDealer( (int) $dealer->dealer_id, 0, 200 );

		$out = [];
		foreach ( $rows as $r )
		{
			$excludeUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=dashboard&do=excludeUnmatched&unmatched_id=' . (int) $r['id']
			)->csrf();

			$out[] = [
				'upc'              => (string) $r['upc'],
				'first_seen'       => (string) $r['first_seen'],
				'last_seen'        => (string) $r['last_seen'],
				'occurrence_count' => (int) $r['occurrence_count'],
				'exclude_url'      => $excludeUrl,
			];
		}

		if ( isset( \IPS\Request::i()->export ) )
		{
			$this->exportUnmatchedCsv( $rows );
			return;
		}

		$exportUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=dashboard&do=unmatched&export=1'
		);

		$this->output( 'unmatched', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->unmatched(
			$this->dealerSummary(),
			$out,
			$exportUrl,
			$this->tabUrls()
		) );
	}

	protected function excludeUnmatched()
	{
		\IPS\Session::i()->csrfCheck();

		$id = (int) \IPS\Request::i()->unmatched_id;
		try
		{
			$row = \IPS\Db::i()->select( '*', 'gd_unmatched_upcs', [ 'id=? AND dealer_id=?', $id, (int) $this->dealer->dealer_id ] )->first();
		}
		catch ( \UnderflowException )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD220/1', 404 );
			return;
		}

		UnmatchedUpc::exclude( (int) $row['id'] );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=unmatched' ),
			'gddealer_front_unmatched_excluded'
		);
	}

	/** Stream unmatched UPCs as CSV. */
	protected function exportUnmatchedCsv( array $rows ): void
	{
		$fh = fopen( 'php://temp', 'r+' );
		fputcsv( $fh, [ 'upc', 'first_seen', 'last_seen', 'occurrence_count' ] );
		foreach ( $rows as $r )
		{
			fputcsv( $fh, [
				(string) $r['upc'],
				(string) $r['first_seen'],
				(string) $r['last_seen'],
				(int) $r['occurrence_count'],
			] );
		}
		rewind( $fh );
		$csv = stream_get_contents( $fh );
		fclose( $fh );

		\IPS\Output::i()->sendOutput( $csv, 200, 'text/csv', [
			'Content-Disposition' => 'attachment; filename="unmatched-upcs-' . (int) $this->dealer->dealer_id . '.csv"',
		]);
	}

	/* ---------------- Tab: Analytics (Pro / Enterprise) ---------------- */

	protected function analytics()
	{
		$dealer = $this->dealer;
		$tier   = (string) $dealer->subscription_tier;
		$gated  = !in_array( $tier, [ Dealer::TIER_PRO, Dealer::TIER_ENTERPRISE, Dealer::TIER_FOUNDING ], true );

		$analytics    = [ 'comp_lowest' => 0, 'comp_mid' => 0, 'comp_high' => 0, 'comp_only' => 0, 'price_drop_count' => 0 ];
		$topClicked   = [];
		$opportunities = [];

		if ( !$gated )
		{
			/* ---- 1. Load this dealer's active listings ---- */
			$myListings = [];
			try
			{
				foreach ( \IPS\Db::i()->select(
					'upc, dealer_price, shipping_cost, click_count_30d, click_count_7d, in_stock',
					'gd_dealer_listings',
					[ 'dealer_id=? AND listing_status=?', (int) $dealer->dealer_id, Listing::STATUS_ACTIVE ]
				) as $r ) {
					$myListings[ (string) $r['upc'] ] = $r;
				}
			}
			catch ( \Exception ) {}

			/* ---- 2. Price competitiveness — min price from OTHER dealers per UPC ---- */
			$otherMins = [];
			if ( !empty( $myListings ) )
			{
				try
				{
					foreach ( \IPS\Db::i()->select(
						'upc, MIN(dealer_price + COALESCE(shipping_cost, 0)) as min_total',
						'gd_dealer_listings',
						[
							\IPS\Db::i()->in( 'upc', array_keys( $myListings ) ) . ' AND dealer_id!=? AND listing_status=?',
							(int) $dealer->dealer_id,
							Listing::STATUS_ACTIVE,
						],
						null, null, 'upc'
					) as $r ) {
						$otherMins[ (string) $r['upc'] ] = (float) $r['min_total'];
					}
				}
				catch ( \Exception ) {}

				$rawOpportunities = [];
				foreach ( $myListings as $upc => $mine )
				{
					$myTotal = (float) $mine['dealer_price'] + (float) ( $mine['shipping_cost'] ?? 0 );

					if ( !isset( $otherMins[ $upc ] ) )
					{
						$analytics['comp_only']++;
					}
					elseif ( $myTotal <= $otherMins[ $upc ] )
					{
						$analytics['comp_lowest']++;
					}
					elseif ( $myTotal <= $otherMins[ $upc ] * 1.10 )
					{
						$analytics['comp_mid']++;
						$rawOpportunities[] = [
							'upc'            => (string) $upc,
							'your_price'     => $myTotal,
							'lowest_price'   => $otherMins[ $upc ],
							'gap'            => $myTotal - $otherMins[ $upc ],
							'click_count_30d'=> (int) $mine['click_count_30d'],
						];
					}
					else
					{
						$analytics['comp_high']++;
						$rawOpportunities[] = [
							'upc'            => (string) $upc,
							'your_price'     => $myTotal,
							'lowest_price'   => $otherMins[ $upc ],
							'gap'            => $myTotal - $otherMins[ $upc ],
							'click_count_30d'=> (int) $mine['click_count_30d'],
						];
					}
				}

				usort( $rawOpportunities, function ( $a, $b ) { return $b['gap'] <=> $a['gap']; } );
				$opportunities = array_slice( $rawOpportunities, 0, 20 );
			}

			/* ---- 3. Top 20 most-clicked ---- */
			try
			{
				foreach ( \IPS\Db::i()->select(
					'upc, dealer_price, click_count_30d, click_count_7d, in_stock',
					'gd_dealer_listings',
					[ 'dealer_id=? AND click_count_30d>? AND listing_status=?', (int) $dealer->dealer_id, 0, Listing::STATUS_ACTIVE ],
					'click_count_30d DESC',
					[ 0, 20 ]
				) as $r ) {
					$topClicked[] = [
						'upc'            => (string) $r['upc'],
						'dealer_price'   => (float) $r['dealer_price'],
						'click_count_30d'=> (int) $r['click_count_30d'],
						'click_count_7d' => (int) $r['click_count_7d'],
						'in_stock'       => (bool) $r['in_stock'],
					];
				}
			}
			catch ( \Exception ) {}

			/* ---- 4. Price-drop count (last 30 days of imports) ---- */
			try
			{
				$thirtyDaysAgo = date( 'Y-m-d H:i:s', time() - ( 30 * 86400 ) );
				$analytics['price_drop_count'] = (int) \IPS\Db::i()->select(
					'COALESCE(SUM(price_drops), 0)',
					'gd_dealer_import_log',
					[ 'dealer_id=? AND run_start>?', (int) $dealer->dealer_id, $thirtyDaysAgo ]
				)->first();
			}
			catch ( \Exception ) {}
		}

		$this->output( 'analytics', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->analytics(
			$this->dealerSummary(),
			$gated,
			$analytics,
			$topClicked,
			$opportunities,
			$this->tabUrls()
		) );
	}

	/* ---------------- Tab: Help ---------------- */

	protected function help(): void
	{
		$s = \IPS\Settings::i();
		$requirements = array_filter( array_map( 'trim',
			explode( "\n", (string) ( $s->gddealer_help_requirements ?? '' ) )
		) );

		$helpData = [
			'intro'        => (string) ( $s->gddealer_help_intro ?? '' ),
			'step1'        => (string) ( $s->gddealer_help_step1 ?? '' ),
			'step2'        => (string) ( $s->gddealer_help_step2 ?? '' ),
			'step3'        => (string) ( $s->gddealer_help_step3 ?? '' ),
			'step4'        => (string) ( $s->gddealer_help_step4 ?? '' ),
			'step5'        => (string) ( $s->gddealer_help_step5 ?? '' ),
			'requirements' => array_values( $requirements ),
			'contact'      => (string) ( $s->gddealer_help_contact ?? '' ),
		];

		$this->output( 'help', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->help( $helpData ) );
	}

	/* ---------------- Tab: Subscription ---------------- */

	protected function subscription()
	{
		$dealer = $this->dealer;

		$trialExpiresAt        = (string) ( $dealer->trial_expires_at ?? '' );
		$trialDaysLeft         = null;
		$trialExpiringSoon     = false;
		$trialExpiresFormatted = '';

		if ( $trialExpiresAt && $trialExpiresAt !== '0000-00-00 00:00:00' )
		{
			$expiryTs              = strtotime( $trialExpiresAt );
			$trialDaysLeft         = (int) ceil( ( $expiryTs - time() ) / 86400 );
			$trialExpiringSoon     = $trialDaysLeft <= 30;
			$trialExpiresFormatted = date( 'F j, Y', $expiryTs );
		}

		$subscribeUrl = (string) ( \IPS\Settings::i()->gddealer_subscribe_url ?? '' );

		$sub = [
			'tier'                    => (string) $dealer->subscription_tier,
			'tier_label'              => ucfirst( (string) $dealer->subscription_tier ),
			'mrr'                     => '$' . number_format( $dealer->mrrContribution(), 2 ),
			'active'                  => (bool) $dealer->active,
			'suspended'               => (bool) $dealer->suspended,
			'trial_expires_at'        => $trialExpiresAt ?: '',
			'trial_expires_formatted' => $trialExpiresFormatted,
			'trial_days_left'         => $trialDaysLeft,
			'trial_expiring_soon'     => $trialExpiringSoon,
			'subscribe_url'           => $subscribeUrl ?: '#',
		];

		$billingNote = (string) ( \IPS\Settings::i()->gddealer_subscription_billing_note ?? '' );

		$this->output( 'subscription', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->subscription(
			$this->dealerSummary(),
			$sub,
			$billingNote,
			$this->tabUrls()
		) );
	}

	/* ---------------- Tab: Reviews ---------------- */

	/** Monthly dispute limit by subscription tier. */
	protected static array $disputeLimits = [
		'basic'      => 2,
		'pro'        => 5,
		'founding'   => 5,
		'enterprise' => PHP_INT_MAX,
	];

	/** Map a 1–5 rating to a color on the shared rating scale. */
	private static function ratingColor( float $r ): string
	{
		return match( true ) {
			$r >= 4.0 => '#16a34a',
			$r >= 3.0 => '#d97706',
			$r > 0    => '#dc2626',
			default   => '#9ca3af',
		};
	}

	/** Map a 1–5 rating to a human-readable label on the shared scale. */
	private static function ratingLabel( float $r ): string
	{
		return match( true ) {
			$r >= 4.0 => 'Excellent',
			$r >= 3.0 => 'Good',
			$r > 0    => 'Poor',
			default   => 'No ratings yet',
		};
	}

	protected function reviews(): void
	{
		$dealerId = (int) $this->dealer->dealer_id;

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_feed_config',
				[ 'last_review_check' => date( 'Y-m-d H:i:s' ) ],
				[ 'dealer_id=?', $dealerId ]
			);
		}
		catch ( \Exception ) {}

		$rows = [];
		$avgPricing = 0.0;
		$avgShipping = 0.0;
		$avgService = 0.0;
		$total = 0;

		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_ratings',
				[ 'dealer_id=? AND status=?', $dealerId, 'approved' ],
				'created_at DESC', [ 0, 50 ]
			) as $r )
			{
				$reviewAvg = ( (int) $r['rating_pricing'] + (int) $r['rating_shipping'] + (int) $r['rating_service'] ) / 3;

				/* Timestamps stored as server-local Y-m-d H:i:s DATETIME.
				   strtotime() returns a Unix timestamp; \IPS\DateTime::ts()
				   then renders it in the viewing member's timezone. */
				$createdTs  = $r['created_at'] ? strtotime( (string) $r['created_at'] ) : 0;
				$responseTs = $r['response_at'] ? strtotime( (string) $r['response_at'] ) : 0;
				$deadlineTs = $r['dispute_deadline'] ? strtotime( (string) $r['dispute_deadline'] ) : 0;

				/* Per-row IPS rich-text editor for the dealer response.
				   Only one editor is ever rendered per row: the new-response
				   editor for empty rows, the edit-response editor for rows
				   that already have a response. autoSaveKey is unique per
				   reviewId so drafts don't cross-contaminate. attachIds=
				   [reviewId,2] pegs attachments to dealer_response. */
				$respondEditorHtml = '';
				$editEditorHtml    = '';
				$dealerResp        = (string) ( $r['dealer_response'] ?? '' );

				if ( $dealerResp === '' && (string) ( $r['dispute_status'] ?? 'none' ) === 'none' )
				{
					$editor = new \IPS\Helpers\Form\Editor(
						'gddealer_response',
						'',
						FALSE,
						[
							'app'         => 'gddealer',
							'key'         => 'Responses',
							'autoSaveKey' => 'gddealer-response-' . (int) $r['id'],
							'attachIds'   => [ (int) $r['id'], 2 ],
						],
						NULL,
						NULL,
						NULL,
						'editor_respond_' . (int) $r['id']
					);
					$respondEditorHtml = (string) $editor;
				}
				elseif ( $dealerResp !== '' )
				{
					$editEditor = new \IPS\Helpers\Form\Editor(
						'gddealer_response',
						$dealerResp,
						FALSE,
						[
							'app'         => 'gddealer',
							'key'         => 'Responses',
							'autoSaveKey' => 'gddealer-response-' . (int) $r['id'],
							'attachIds'   => [ (int) $r['id'], 2 ],
						],
						NULL,
						NULL,
						NULL,
						'editor_respond_edit_' . (int) $r['id']
					);
					$editEditorHtml = (string) $editEditor;
				}

				/* Dispute editors — only built for rows that can actually be
				   disputed (status=none). Two editors per eligible row:
				   reason (id2=3) and evidence (id2=4). */
				$disputeReasonEditorHtml   = '';
				$disputeEvidenceEditorHtml = '';
				if ( (string) ( $r['dispute_status'] ?? 'none' ) === 'none' )
				{
					$rEditor = new \IPS\Helpers\Form\Editor(
						'gddealer_dispute_reason',
						'',
						FALSE,
						[
							'app'         => 'gddealer',
							'key'         => 'Responses',
							'autoSaveKey' => 'gddealer-dispute-reason-' . (int) $r['id'],
							'attachIds'   => [ (int) $r['id'], 3 ],
						],
						NULL,
						NULL,
						NULL,
						'editor_dispute_reason_' . (int) $r['id']
					);
					$disputeReasonEditorHtml = (string) $rEditor;

					$eEditor = new \IPS\Helpers\Form\Editor(
						'gddealer_dispute_evidence',
						'',
						FALSE,
						[
							'app'         => 'gddealer',
							'key'         => 'Responses',
							'autoSaveKey' => 'gddealer-dispute-evidence-' . (int) $r['id'],
							'attachIds'   => [ (int) $r['id'], 4 ],
						],
						NULL,
						NULL,
						NULL,
						'editor_dispute_evidence_' . (int) $r['id']
					);
					$disputeEvidenceEditorHtml = (string) $eEditor;
				}

				$rows[] = [
					'id'               => (int) $r['id'],
					'member_id'        => (int) $r['member_id'],
					'rating_pricing'   => (int) $r['rating_pricing'],
					'rating_shipping'  => (int) $r['rating_shipping'],
					'rating_service'   => (int) $r['rating_service'],
					'review_body'      => ( $r['review_body'] ?? '' ) !== '' ? \IPS\Text\Parser::parseStatic( (string) $r['review_body'], [ (int) $r['id'], 1 ], NULL, 'gddealer_Responses' ) : '',
					'dealer_response'  => ( $r['dealer_response'] ?? '' ) !== '' ? \IPS\Text\Parser::parseStatic( (string) $r['dealer_response'], [ (int) $r['id'], 2 ], NULL, 'gddealer_Responses' ) : '',
					'response_at'      => $responseTs
						? (string) \IPS\DateTime::ts( $responseTs )->localeDate() . ' · ' . (string) \IPS\DateTime::ts( $responseTs )->localeTime()
						: '',
					'created_at'       => $createdTs
						? (string) \IPS\DateTime::ts( $createdTs )->localeDate()
						: '',
					'dispute_status'   => (string) ( $r['dispute_status'] ?? 'none' ),
					'dispute_outcome'  => (string) ( $r['dispute_outcome'] ?? '' ),
					'dispute_deadline' => $deadlineTs
						? (string) \IPS\DateTime::ts( $deadlineTs )->localeDate()
						: '',
					'avg_color'        => self::ratingColor( (float) $reviewAvg ),
					'avg_overall'      => round( $reviewAvg, 1 ),
					'respond_url'      => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=respond&id=' . (int) $r['id']
					)->csrf(),
					'delete_response_url' => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=deleteResponse&id=' . (int) $r['id']
					)->csrf(),
					'dispute_url'      => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=dispute&id=' . (int) $r['id']
					)->csrf(),
					'respond_editor_html' => $respondEditorHtml,
					'edit_editor_html'    => $editEditorHtml,
					'dispute_reason_editor_html'   => $disputeReasonEditorHtml,
					'dispute_evidence_editor_html' => $disputeEvidenceEditorHtml,
				];
			}
		}
		catch ( \Exception ) {}

		/* Averages exclude reviews resolved in the dealer's favor. Pending
		   and dismissed reviews continue to count. */
		try
		{
			$agg = \IPS\Db::i()->select(
				'COUNT(*) as c, AVG(rating_pricing) as p, AVG(rating_shipping) as s, AVG(rating_service) as sv',
				'gd_dealer_ratings',
				[ 'dealer_id=? AND status=? AND dispute_status<>?', $dealerId, 'approved', 'resolved_dealer' ]
			)->first();
			$total       = (int) $agg['c'];
			$avgPricing  = round( (float) $agg['p'], 1 );
			$avgShipping = round( (float) $agg['s'], 1 );
			$avgService  = round( (float) $agg['sv'], 1 );
		}
		catch ( \Exception ) {}

		/* Compute monthly dispute usage for this dealer. */
		$tier     = (string) $this->dealer->subscription_tier;
		$limit    = self::$disputeLimits[ $tier ] ?? 2;
		$monthKey = date( 'Y-m' );
		$bonus    = 0;
		try
		{
			$row = \IPS\Db::i()->select( 'count, bonus', 'gd_dealer_dispute_counts',
				[ 'dealer_id=? AND month_key=?', $dealerId, $monthKey ]
			)->first();
			$used  = (int) $row['count'];
			$bonus = (int) ( $row['bonus'] ?? 0 );
		}
		catch ( \Exception )
		{
			$used = 0;
		}
		$effectiveLimit = $limit === PHP_INT_MAX ? PHP_INT_MAX : $limit + $bonus;
		$remaining = $effectiveLimit === PHP_INT_MAX ? -1 : max( 0, $effectiveLimit - $used );

		$avgOverall = $total > 0 ? round( ( $avgPricing + $avgShipping + $avgService ) / 3, 1 ) : 0.0;

		$data = [
			'rows'               => $rows,
			'total'              => $total,
			'avg_pricing'        => $avgPricing,
			'avg_shipping'       => $avgShipping,
			'avg_service'        => $avgService,
			'avg_overall'        => $avgOverall,
			'rating_color'       => self::ratingColor( (float) $avgOverall ),
			'rating_label'       => self::ratingLabel( (float) $avgOverall ),
			'color_pricing'      => self::ratingColor( (float) $avgPricing ),
			'color_shipping'     => self::ratingColor( (float) $avgShipping ),
			'color_service'      => self::ratingColor( (float) $avgService ),
			'disputes_remaining' => $remaining,
			'disputes_unlimited' => $remaining === -1,
			'guidelines_url'     => (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=profile&do=guidelines',
				'front', 'dealers_review_guidelines'
			),
			'disputes_suspended' => (bool) ( $this->dealer->disputes_suspended ?? 0 ),
			'help_email'         => (string) ( \IPS\Settings::i()->gddealer_help_contact ?: 'dealers@gunrack.deals' ),
		];

		$csrfKey = (string) \IPS\Session::i()->csrfKey;

		$this->output( 'reviews', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->dealerReviews( $data, $csrfKey ) );
	}

	/** Post a dealer response to a review. */
	protected function respond(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id  = (int) ( \IPS\Request::i()->id ?? 0 );

		/* Construct editor before save logic so upload POSTs are intercepted.
		   autoSaveKey must match the render-side editor in reviews(). */
		if ( $id > 0 )
		{
			new \IPS\Helpers\Form\Editor(
				'gddealer_response',
				'',
				FALSE,
				[
					'app'         => 'gddealer',
					'key'         => 'Responses',
					'autoSaveKey' => 'gddealer-response-' . $id,
					'attachIds'   => [ $id, 2 ],
				],
				NULL,
				NULL,
				NULL,
				'editor_respond_' . $id
			);
		}

		$raw = (string) \IPS\Request::i()->gddealer_response;

		/* Editor output is HTML. parseStatic runs IPS's HTMLPurifier against
		   it (strips unsafe tags/attrs, converts pasted embeds, resolves
		   attachment placeholders). The $area string must match the editor
		   location extension: gddealer_Responses. attachIds=[reviewId,2]
		   identifies the dealer_response field for attachment bookkeeping. */
		$response = '';
		if ( $raw !== '' && $id > 0 )
		{
			try
			{
				$response = \IPS\Text\Parser::parseStatic(
					$raw,
					[ (int) $id, 2 ],
					\IPS\Member::loggedIn(),
					'gddealer_Responses'
				);
			}
			catch ( \Exception )
			{
				$response = $raw;
			}
		}
		$response = trim( $response );

		if ( $id > 0 && $response !== '' )
		{
			try
			{
				\IPS\Db::i()->update( 'gd_dealer_ratings',
					[ 'dealer_response' => $response, 'response_at' => date( 'Y-m-d H:i:s' ) ],
					[ 'id=? AND dealer_id=? AND dispute_status=?', $id, (int) $this->dealer->dealer_id, 'none' ]
				);
			}
			catch ( \Exception ) {}

			/* Claim any attachments uploaded via the editor so they're
			   linked to this review+field and survive garbage collection.
			   autoSaveKey matches the editor instantiation in reviews(). */
			try
			{
				\IPS\File::claimAttachments(
					'gddealer-response-' . (int) $id,
					(int) $id,
					2
				);
			}
			catch ( \Exception ) {}

			/* Look up the reviewer once; each side-effect below gets its
			   own fully independent try/catch so a failure in one channel
			   cannot suppress the others. */
			$reviewerMember = NULL;
			$dealerName     = (string) $this->dealer->dealer_name;
			$slug           = (string) ( $this->dealer->dealer_slug ?? '' );
			$profileUrl     = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug )
			);

			try
			{
				$review         = \IPS\Db::i()->select( '*', 'gd_dealer_ratings', [ 'id=?', $id ] )->first();
				$reviewerMember = \IPS\Member::load( (int) $review['member_id'] );
			}
			catch ( \Exception ) {}

			/* Email to the reviewer — own try/catch. */
			try
			{
				if ( $reviewerMember && $reviewerMember->member_id )
				{
					\IPS\Email::buildFromTemplate( 'gddealer', 'dealerResponded', [
						'name'        => $reviewerMember->name,
						'dealer_name' => $dealerName,
						'response'    => $response,
						'profile_url' => $profileUrl,
					], \IPS\Email::TYPE_TRANSACTIONAL )->send( $reviewerMember );
				}
			}
			catch ( \Exception ) {}

			/* IPS inline notification to the reviewer — own try/catch. */
			try
			{
				if ( $reviewerMember && $reviewerMember->member_id )
				{
					$notification = new \IPS\Notification(
						\IPS\Application::load( 'gddealer' ),
						'dealer_responded',
						$reviewerMember,
						[ $reviewerMember ],
						[
							'dealer_name' => $dealerName,
							'dealer_slug' => $slug,
						]
					);
					$notification->recipients->attach( $reviewerMember );
					$notification->send();
				}
			}
			catch ( \Exception ) {}

			/* PM to the reviewer — own try/catch. */
			try
			{
				if ( $reviewerMember && $reviewerMember->member_id )
				{
					$dealerMember = \IPS\Member::load( (int) $this->dealer->dealer_id );
					$sender       = $dealerMember->member_id ? $dealerMember : \IPS\Member::loggedIn();
					if ( \IPS\core\Messenger\Conversation::memberCanReceiveNewMessage( $reviewerMember, $sender ) )
					{
						$conversation = \IPS\core\Messenger\Conversation::createItem( $sender, \IPS\Request::i()->ipAddress(), \IPS\DateTime::create() );
						$conversation->title    = $dealerName . ' responded to your review';
						$conversation->to_count = 1;
						$conversation->save();

						$commentClass = $conversation::$commentClass;
						$post = $commentClass::create(
							$conversation,
							$dealerName . ' has posted a public response to your review on GunRack.deals. View it here: ' . $profileUrl,
							TRUE, NULL, NULL, $sender, \IPS\DateTime::create()
						);

						$conversation->first_msg_id = $post->id;
						$conversation->save();
						$conversation->authorize( [ $sender->member_id, $reviewerMember->member_id ] );
						$post->sendNotifications();
					}
				}
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=reviews' ),
			'gddealer_front_response_saved'
		);
	}

	/** Remove a dealer response. Dealer-scoped; CSRF-protected. */
	protected function deleteResponse(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) ( \IPS\Request::i()->id ?? 0 );

		if ( $id > 0 )
		{
			try
			{
				\IPS\Db::i()->update( 'gd_dealer_ratings',
					[ 'dealer_response' => NULL, 'response_at' => NULL ],
					[ 'id=? AND dealer_id=?', $id, (int) $this->dealer->dealer_id ]
				);
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=reviews' )
		);
	}

	/**
	 * Contest a review. Opens the pending_customer stage of the dispute
	 * flow: customer is given 30 days to respond, a dispute count is
	 * incremented for the month, customer gets an email notification.
	 * Monthly disputes are capped by subscription tier.
	 */
	protected function dispute(): void
	{
		\IPS\Session::i()->csrfCheck();

		$id = (int) ( \IPS\Request::i()->id ?? 0 );

		/* Construct editors before save logic so upload POSTs are intercepted.
		   autoSaveKeys must match the render-side editors in reviews(). */
		if ( $id > 0 )
		{
			new \IPS\Helpers\Form\Editor(
				'gddealer_dispute_reason',
				'',
				FALSE,
				[
					'app'         => 'gddealer',
					'key'         => 'Responses',
					'autoSaveKey' => 'gddealer-dispute-reason-' . $id,
					'attachIds'   => [ $id, 3 ],
				],
				NULL,
				NULL,
				NULL,
				'editor_dispute_reason_' . $id
			);
			new \IPS\Helpers\Form\Editor(
				'gddealer_dispute_evidence',
				'',
				FALSE,
				[
					'app'         => 'gddealer',
					'key'         => 'Responses',
					'autoSaveKey' => 'gddealer-dispute-evidence-' . $id,
					'attachIds'   => [ $id, 4 ],
				],
				NULL,
				NULL,
				NULL,
				'editor_dispute_evidence_' . $id
			);
		}

		$reasonRaw   = (string) \IPS\Request::i()->gddealer_dispute_reason;
		$evidenceRaw = (string) \IPS\Request::i()->gddealer_dispute_evidence;

		/* Both fields arrive as editor HTML — parse through IPS sanitizer
		   (area='gddealer_Responses', attachIds tag the review + the
		   specific field: 3=dispute_reason, 4=dispute_evidence). */
		$reason = '';
		if ( trim( $reasonRaw ) !== '' && $id > 0 )
		{
			try
			{
				$reason = \IPS\Text\Parser::parseStatic(
					$reasonRaw,
					[ $id, 3 ],
					\IPS\Member::loggedIn(),
					'gddealer_Responses'
				);
			}
			catch ( \Exception )
			{
				$reason = $reasonRaw;
			}
		}
		$reason = trim( $reason );

		$evidence = '';
		if ( trim( $evidenceRaw ) !== '' && $id > 0 )
		{
			try
			{
				$evidence = \IPS\Text\Parser::parseStatic(
					$evidenceRaw,
					[ $id, 4 ],
					\IPS\Member::loggedIn(),
					'gddealer_Responses'
				);
			}
			catch ( \Exception )
			{
				$evidence = $evidenceRaw;
			}
		}
		$evidence = trim( $evidence );
		$tier     = (string) $this->dealer->subscription_tier;
		$dealerId = (int) $this->dealer->dealer_id;

		$redirectUrl = \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=reviews' );

		if ( (int) ( $this->dealer->disputes_suspended ?? 0 ) )
		{
			\IPS\Output::i()->redirect( $redirectUrl, 'gddealer_front_disputes_suspended' );
			return;
		}

		if ( $id <= 0 || $reason === '' )
		{
			\IPS\Output::i()->redirect( $redirectUrl );
			return;
		}

		$limit    = self::$disputeLimits[ $tier ] ?? 2;
		$monthKey = date( 'Y-m' );
		$bonus    = 0;

		try
		{
			$row = \IPS\Db::i()->select( 'count, bonus', 'gd_dealer_dispute_counts',
				[ 'dealer_id=? AND month_key=?', $dealerId, $monthKey ]
			)->first();
			$used  = (int) $row['count'];
			$bonus = (int) ( $row['bonus'] ?? 0 );
		}
		catch ( \Exception )
		{
			$used = 0;
		}

		$effectiveLimit = $limit === PHP_INT_MAX ? PHP_INT_MAX : $limit + $bonus;
		if ( $used >= $effectiveLimit )
		{
			\IPS\Output::i()->redirect( $redirectUrl, 'gddealer_front_dispute_limit_reached' );
			return;
		}

		/* Verify review belongs to this dealer and has no active dispute.
		   A review previously dismissed by admin cannot be contested a
		   second time — dispute_status must equal 'none'. */
		try
		{
			$review = \IPS\Db::i()->select( '*', 'gd_dealer_ratings',
				[ 'id=? AND dealer_id=? AND dispute_status=?', $id, $dealerId, 'none' ]
			)->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->redirect( $redirectUrl );
			return;
		}

		$deadline = date( 'Y-m-d H:i:s', strtotime( '+30 days' ) );

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_ratings', [
				'dispute_status'   => 'pending_customer',
				'dispute_reason'   => $reason,
				'dispute_evidence' => $evidence !== '' ? $evidence : null,
				'dispute_at'       => date( 'Y-m-d H:i:s' ),
				'dispute_deadline' => $deadline,
			], [ 'id=?', $id ] );
		}
		catch ( \Exception ) {}

		/* Claim any editor attachments for each dispute field. Same
		   autoSaveKey the editor was rendered with. */
		try
		{
			\IPS\File::claimAttachments(
				'gddealer-dispute-reason-' . (int) $id,
				(int) $id,
				3
			);
		}
		catch ( \Exception ) {}

		try
		{
			\IPS\File::claimAttachments(
				'gddealer-dispute-evidence-' . (int) $id,
				(int) $id,
				4
			);
		}
		catch ( \Exception ) {}

		/* Increment monthly dispute count. Insert-or-update without any
		   raw SQL string interpolation (CLAUDE.md Rule #2). */
		try
		{
			$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_dispute_counts',
				[ 'dealer_id=? AND month_key=?', $dealerId, $monthKey ]
			)->first();
		}
		catch ( \Exception )
		{
			$exists = 0;
		}

		try
		{
			if ( $exists === 0 )
			{
				\IPS\Db::i()->insert( 'gd_dealer_dispute_counts', [
					'dealer_id' => $dealerId,
					'month_key' => $monthKey,
					'count'     => 1,
				] );
			}
			else
			{
				\IPS\Db::i()->update( 'gd_dealer_dispute_counts',
					'count = count + 1',
					[ 'dealer_id=? AND month_key=?', $dealerId, $monthKey ]
				);
			}
		}
		catch ( \Exception ) {}

		/* Side-effects to the customer — each channel in its own independent
		   try/catch so a failure in one does not suppress the others. */
		if ( (int) ( $review['member_id'] ?? 0 ) > 0 )
		{
			$customer     = NULL;
			$slug         = (string) ( $this->dealer->dealer_slug ?? '' );
			$respondUrl   = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ) . '&dispute=' . $id
			);
			$dealerName   = (string) $this->dealer->dealer_name;
			$contactEmail = (string) ( \IPS\Settings::i()->gddealer_help_contact ?: 'dealers@gunrack.deals' );

			try { $customer = \IPS\Member::load( (int) $review['member_id'] ); } catch ( \Exception ) {}

			/* Email to the customer. */
			try
			{
				if ( $customer && $customer->member_id )
				{
					\IPS\Email::buildFromTemplate( 'gddealer', 'disputeNotify', [
						'name'          => $customer->name,
						'dealer_name'   => $dealerName,
						'reason'        => $reason,
						'deadline'      => date( 'F j, Y', strtotime( $deadline ) ),
						'respond_url'   => $respondUrl,
						'contact_email' => $contactEmail,
					], \IPS\Email::TYPE_TRANSACTIONAL )->send( $customer );
				}
			}
			catch ( \Exception ) {}

			/* IPS inline notification to the customer. */
			try
			{
				if ( $customer && $customer->member_id )
				{
					$notification = new \IPS\Notification(
						\IPS\Application::load( 'gddealer' ),
						'review_disputed',
						$customer,
						[ $customer ],
						[
							'dealer_name' => $dealerName,
							'dealer_slug' => $slug,
							'review_id'   => $id,
						]
					);
					$notification->recipients->attach( $customer );
					$notification->send();
				}
			}
			catch ( \Exception ) {}

			/* PM to the customer. */
			try
			{
				if ( $customer && $customer->member_id )
				{
					$dealerMember = \IPS\Member::load( (int) $this->dealer->dealer_id );
					$sender       = $dealerMember->member_id ? $dealerMember : \IPS\Member::loggedIn();
					if ( \IPS\core\Messenger\Conversation::memberCanReceiveNewMessage( $customer, $sender ) )
					{
						$conversation = \IPS\core\Messenger\Conversation::createItem( $sender, \IPS\Request::i()->ipAddress(), \IPS\DateTime::create() );
						$conversation->title    = $dealerName . ' has disputed your review';
						$conversation->to_count = 1;
						$conversation->save();

						$commentClass = $conversation::$commentClass;
						$post = $commentClass::create(
							$conversation,
							"Hi,\n\n" . $dealerName . " has contested the review you left on GunRack.deals and provided the following evidence:\n\n" . $reason . "\n\nYou have 30 days to respond. Visit the dealer profile to submit your response:\n\n" . $respondUrl,
							TRUE, NULL, NULL, $sender, \IPS\DateTime::create()
						);

						$conversation->first_msg_id = $post->id;
						$conversation->save();
						$conversation->authorize( [ $sender->member_id, $customer->member_id ] );
						$post->sendNotifications();
					}
				}
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect( $redirectUrl, 'gddealer_front_dispute_submitted' );
	}

	/* ---------------- Helpers ---------------- */

	/**
	 * Scalar summary of the dealer used by every tab template header.
	 */
	protected function dealerSummary(): array
	{
		$d    = $this->dealer;
		$tier = (string) $d->subscription_tier;

		$avatarUrl       = '';
		$coverPhotoUrl   = '';
		$coverOffset     = 0;
		try
		{
			$ipsMember = \IPS\Member::loggedIn();
			$avatarUrl = (string) ( $ipsMember->get_photo( true, false ) ?? '' );
			$cp        = $ipsMember->coverPhoto();
			if ( $cp->file )
			{
				$coverPhotoUrl = (string) $cp->file->url;
			}
			$coverOffset = (int) ( $cp->offset ?? 0 );
		}
		catch ( \Exception ) {}

		$lastVisit  = (string) ( $d->last_review_check ?? '2000-01-01 00:00:00' );
		$newReviews = 0;
		try
		{
			$newReviews = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_ratings', [
				'dealer_id=? AND created_at>? AND status=?',
				(int) $d->dealer_id, $lastVisit, 'approved'
			] )->first();
		}
		catch ( \Exception ) {}

		return [
			'dealer_id'             => (int) $d->dealer_id,
			'dealer_name'           => (string) $d->dealer_name,
			'dealer_slug'           => (string) ( $d->dealer_slug ?? '' ),
			'subscription_tier'     => $tier,
			'tier_label'            => ucfirst( $tier ),
			'tier_color'            => match( $tier ) {
				'founding'   => (string) ( \IPS\Settings::i()->gddealer_founding_badge_color   ?: '#b45309' ),
				'pro'        => (string) ( \IPS\Settings::i()->gddealer_pro_badge_color        ?: '#2563eb' ),
				'enterprise' => (string) ( \IPS\Settings::i()->gddealer_enterprise_badge_color ?: '#7c3aed' ),
				default      => (string) ( \IPS\Settings::i()->gddealer_basic_badge_color      ?: '#6b7280' ),
			},
			'onboarding_incomplete' => empty( $d->feed_url ),
			'suspended'             => (bool) $d->suspended,
			'disputes_suspended'    => (bool) ( $d->disputes_suspended ?? 0 ),
			'help_email'            => (string) ( \IPS\Settings::i()->gddealer_help_contact ?: 'dealers@gunrack.deals' ),
			'avatar_url'            => $avatarUrl,
			'cover_photo_url'       => $coverPhotoUrl,
			'cover_offset'          => $coverOffset,
			'new_reviews'           => $newReviews,
		];
	}

	/**
	 * URLs for the six dashboard tabs — pre-built in the controller so
	 * templates never nest {url=...} inside conditionals (Rule #12.6).
	 */
	protected function tabUrls(): array
	{
		$base = 'app=gddealer&module=dealers&controller=dashboard&do=';
		return [
			'overview'     => (string) \IPS\Http\Url::internal( $base . 'overview' ),
			'feedSettings' => (string) \IPS\Http\Url::internal( $base . 'feedSettings' ),
			'listings'     => (string) \IPS\Http\Url::internal( $base . 'listings' ),
			'unmatched'    => (string) \IPS\Http\Url::internal( $base . 'unmatched' ),
			'analytics'    => (string) \IPS\Http\Url::internal( $base . 'analytics' ),
			'reviews'      => (string) \IPS\Http\Url::internal( $base . 'reviews' ),
			'subscription' => (string) \IPS\Http\Url::internal( $base . 'subscription' ),
			'help'         => (string) \IPS\Http\Url::internal( $base . 'help' ),
		];
	}

	/**
	 * Admin-configurable theme variables injected at the top of every
	 * dealer page. CSS rules target the gdDealerWrapper / gdDealerTabs /
	 * gdStatCard / gdDealerCoverFallback classes to override any inline
	 * color fallbacks left over in the templates.
	 */
	protected function themeVars(): string
	{
		$s = \IPS\Settings::i();
		return '<style>
:root {
	--gd-primary:            ' . ( $s->gddealer_color_primary ?: '#2563eb' ) . ';
	--gd-primary-text:       ' . ( $s->gddealer_color_primary_text ?: '#ffffff' ) . ';
	--gd-tab-active-bg:      ' . ( $s->gddealer_color_active_tab_bg ?: '#1e3a5f' ) . ';
	--gd-tab-active-text:    ' . ( $s->gddealer_color_active_tab_text ?: '#ffffff' ) . ';
	--gd-tab-inactive-text:  ' . ( $s->gddealer_color_inactive_tab_text ?: '#374151' ) . ';
	--gd-accent:             ' . ( $s->gddealer_color_accent ?: '#16a34a' ) . ';
	--gd-warning:            ' . ( $s->gddealer_color_warning ?: '#d97706' ) . ';
	--gd-danger:             ' . ( $s->gddealer_color_danger ?: '#dc2626' ) . ';
	--gd-header-bg:          ' . ( $s->gddealer_color_header_bg ?: '#1e3a5f' ) . ';
}
.gdDealerTabs .ipsTabs__tab[aria-selected="true"] {
	background: var(--gd-tab-active-bg) !important;
	color: var(--gd-tab-active-text) !important;
	border-color: var(--gd-tab-active-bg) !important;
}
.gdDealerTabs .ipsTabs__tab[aria-selected="false"] {
	color: var(--gd-tab-inactive-text) !important;
}
.gdDealerWrapper .ipsButton--primary {
	background: var(--gd-primary) !important;
	color: var(--gd-primary-text) !important;
	border-color: var(--gd-primary) !important;
}
.gdDealerCoverFallback {
	background: var(--gd-header-bg) !important;
}
</style>';
	}

	/**
	 * Wrap a tab body in the dealerShell template and push to output.
	 */
	protected function output( string $activeTab, string $body ): void
	{
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_frontend_dashboard_title' );
		\IPS\Output::i()->output = $this->themeVars() . \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->dealerShell(
			$this->dealerSummary(),
			$activeTab,
			$this->tabUrls(),
			$body
		);
	}
}

class dashboard extends _dashboard {}
