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

		$overview = [
			'tier'                 => (string) $dealer->subscription_tier,
			'tier_label'           => ucfirst( (string) $dealer->subscription_tier ),
			'active_listings'      => $active,
			'out_of_stock'         => $out,
			'unmatched_count'      => $unmatched,
			'clicks_7d'            => $clicks7,
			'clicks_30d'           => $clicks30,
			'onboarding_incomplete'=> empty( $dealer->feed_url ),
			'last_run_time'        => (string) ( $lastLogRow['run_start']       ?? '' ),
			'last_run_status'      => (string) ( $lastLogRow['status']          ?? '' ),
			'last_run_total'       => (int)    ( $lastLogRow['records_total']   ?? 0 ),
			'last_run_errors'      => !empty( $lastLogRow['error_log'] ),
		];

		$this->output( 'overview', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->overview(
			$this->dealerSummary(),
			$overview,
			$this->tabUrls()
		) );
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
		$this->output( 'help', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->help() );
	}

	/* ---------------- Tab: Subscription ---------------- */

	protected function subscription()
	{
		$dealer = $this->dealer;

		$sub = [
			'tier'       => (string) $dealer->subscription_tier,
			'tier_label' => ucfirst( (string) $dealer->subscription_tier ),
			'mrr'        => '$' . number_format( $dealer->mrrContribution(), 2 ),
			'active'     => (bool) $dealer->active,
			'suspended'  => (bool) $dealer->suspended,
		];

		$this->output( 'subscription', \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->subscription(
			$this->dealerSummary(),
			$sub,
			$this->tabUrls()
		) );
	}

	/* ---------------- Helpers ---------------- */

	/**
	 * Scalar summary of the dealer used by every tab template header.
	 */
	protected function dealerSummary(): array
	{
		$d = $this->dealer;
		return [
			'dealer_id'             => (int) $d->dealer_id,
			'dealer_name'           => (string) $d->dealer_name,
			'subscription_tier'     => (string) $d->subscription_tier,
			'tier_label'            => ucfirst( (string) $d->subscription_tier ),
			'onboarding_incomplete' => empty( $d->feed_url ),
			'suspended'             => (bool) $d->suspended,
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
