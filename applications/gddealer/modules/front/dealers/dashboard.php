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
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join' ) );
			return;
		}

		try
		{
			$this->dealer = Dealer::load( (int) \IPS\Member::loggedIn()->member_id );
		}
		catch ( \OutOfRangeException )
		{
			$this->dealer = null;
		}

		if ( $this->dealer === null )
		{
			\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_frontend_dashboard_title' );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->notSubscribed(
				(string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join' )
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
				'card_bg'     => '#0f172a',
				'card_border' => '#1e293b',
				'card_color'  => '#f1f5f9',
				'card_label'  => '#94a3b8',
			],
			'accent' => [
				'card_bg'     => '#eff6ff',
				'card_border' => '#bfdbfe',
				'card_color'  => '#1e3a8a',
				'card_label'  => '#1e40af',
			],
			default  => [
				'card_bg'     => '#ffffff',
				'card_border' => 'var(--i-border-color,#e0e0e0)',
				'card_color'  => '#111827',
				'card_label'  => '#6b7280',
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

	protected function reviews(): void
	{
		$dealerId = (int) $this->dealer->dealer_id;
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
				$rows[] = [
					'id'               => (int) $r['id'],
					'member_id'        => (int) $r['member_id'],
					'rating_pricing'   => (int) $r['rating_pricing'],
					'rating_shipping'  => (int) $r['rating_shipping'],
					'rating_service'   => (int) $r['rating_service'],
					'review_body'      => (string) ( $r['review_body'] ?? '' ),
					'dealer_response'  => (string) ( $r['dealer_response'] ?? '' ),
					'response_at'      => (string) ( $r['response_at'] ?? '' ),
					'created_at'       => (string) $r['created_at'],
					'dispute_status'   => (string) ( $r['dispute_status'] ?? 'none' ),
					'dispute_outcome'  => (string) ( $r['dispute_outcome'] ?? '' ),
					'dispute_deadline' => (string) ( $r['dispute_deadline'] ?? '' ),
					'respond_url'      => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=respond&id=' . (int) $r['id']
					)->csrf(),
					'dispute_url'      => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=dispute&id=' . (int) $r['id']
					)->csrf(),
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
		try
		{
			$used = (int) \IPS\Db::i()->select( 'count', 'gd_dealer_dispute_counts',
				[ 'dealer_id=? AND month_key=?', $dealerId, $monthKey ]
			)->first();
		}
		catch ( \Exception )
		{
			$used = 0;
		}
		$remaining = $limit === PHP_INT_MAX ? -1 : max( 0, $limit - $used );

		$data = [
			'rows'               => $rows,
			'total'              => $total,
			'avg_pricing'        => $avgPricing,
			'avg_shipping'       => $avgShipping,
			'avg_service'        => $avgService,
			'avg_overall'        => $total > 0 ? round( ( $avgPricing + $avgShipping + $avgService ) / 3, 1 ) : 0.0,
			'disputes_remaining' => $remaining,
			'disputes_unlimited' => $remaining === -1,
			'guidelines_url'     => (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=profile&do=guidelines',
				'front', 'dealers_review_guidelines'
			),
		];

		$csrfKey = (string) \IPS\Session::i()->csrfKey;

		$this->output( 'reviews', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->dealerReviews( $data, $csrfKey ) );
	}

	/** Post a dealer response to a review. */
	protected function respond(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id       = (int) ( \IPS\Request::i()->id ?? 0 );
		$response = trim( (string) \IPS\Request::i()->response );

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
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=reviews' ),
			'gddealer_front_response_saved'
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

		$id       = (int) ( \IPS\Request::i()->id ?? 0 );
		$reason   = trim( (string) \IPS\Request::i()->dispute_reason );
		$evidence = trim( (string) \IPS\Request::i()->dispute_evidence );
		$tier     = (string) $this->dealer->subscription_tier;
		$dealerId = (int) $this->dealer->dealer_id;

		$redirectUrl = \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=reviews' );

		if ( $id <= 0 || $reason === '' )
		{
			\IPS\Output::i()->redirect( $redirectUrl );
			return;
		}

		$limit    = self::$disputeLimits[ $tier ] ?? 2;
		$monthKey = date( 'Y-m' );

		try
		{
			$used = (int) \IPS\Db::i()->select( 'count', 'gd_dealer_dispute_counts',
				[ 'dealer_id=? AND month_key=?', $dealerId, $monthKey ]
			)->first();
		}
		catch ( \Exception )
		{
			$used = 0;
		}

		if ( $used >= $limit )
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

		/* Notify the customer via transactional email. */
		if ( (int) ( $review['member_id'] ?? 0 ) > 0 )
		{
			try
			{
				$customer = \IPS\Member::load( (int) $review['member_id'] );
				if ( $customer->member_id )
				{
					$slug       = (string) ( $this->dealer->dealer_slug ?? '' );
					$respondUrl = (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ) . '&dispute=' . $id
					);
					\IPS\Email::buildFromTemplate( 'gddealer', 'disputeNotify', [
						'name'        => $customer->name,
						'dealer_name' => (string) $this->dealer->dealer_name,
						'reason'      => $reason,
						'deadline'    => date( 'F j, Y', strtotime( $deadline ) ),
						'respond_url' => $respondUrl,
					], \IPS\Email::TYPE_TRANSACTIONAL )->send( $customer );
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

		$avatar = '';
		try
		{
			$avatar = (string) \IPS\Member::loggedIn()->photo;
		}
		catch ( \Exception ) {}

		return [
			'dealer_id'             => (int) $d->dealer_id,
			'dealer_name'           => (string) $d->dealer_name,
			'subscription_tier'     => $tier,
			'tier_label'            => ucfirst( $tier ),
			'tier_color'            => match( $tier ) {
				'founding'   => '#b45309',
				'pro'        => '#2563eb',
				'enterprise' => '#7c3aed',
				default      => '#6b7280',
			},
			'onboarding_incomplete' => empty( $d->feed_url ),
			'suspended'             => (bool) $d->suspended,
			'avatar'                => $avatar,
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
	 * Wrap a tab body in the dealerShell template and push to output.
	 */
	protected function output( string $activeTab, string $body ): void
	{
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_frontend_dashboard_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->dealerShell(
			$this->dealerSummary(),
			$activeTab,
			$this->tabUrls(),
			$body
		);
	}
}

class dashboard extends _dashboard {}
