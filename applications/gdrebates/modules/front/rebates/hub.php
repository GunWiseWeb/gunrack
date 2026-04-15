<?php
/**
 * @brief       GD Rebates — Public hub (/rebates)
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Public browse surface for active rebates (Section 8.6). Pulls from
 * gd_rebates directly, per CLAUDE.md Rule #8 — no live OpenSearch HTTP
 * calls from the controller. Every query is wrapped in its own
 * try/catch so a single schema issue doesn't break the whole page.
 */

namespace IPS\gdrebates\modules\front\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _hub extends \IPS\Dispatcher\Controller
{
	public function execute(): void
	{
		parent::execute();
	}

	protected function manage(): void
	{
		$settings     = \IPS\Settings::i();
		$expiringDays = max( 1, (int) ( $settings->gdr_hub_expiring_days ?? 7 ) );
		$featuredId   = max( 0, (int) ( $settings->gdr_featured_rebate_id ?? 0 ) );

		$data = [
			'active_count'    => self::countActive(),
			'total_savings'   => \IPS\gdrebates\Rebate\Rebate::totalActiveSavings(),
			'featured'        => self::fetchFeatured( $featuredId ),
			'expiring'        => self::fetchExpiring( $expiringDays, 8 ),
			'newest'          => self::fetchNewest( 8 ),
			'top_mfrs'        => self::fetchTopMfrs( 6 ),
			'by_type_counts'  => self::fetchByTypeCounts(),
			'submit_url'      => (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=submit' ),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_front_hub_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'front' )->hub( $data );
	}

	private static function countActive(): int
	{
		try
		{
			return (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_rebates', [ 'status=?', 'active' ]
			)->first();
		}
		catch ( \Exception )
		{
			return 0;
		}
	}

	/**
	 * Resolve the featured rebate. Admin-chosen ID takes precedence;
	 * otherwise fall back to the active rebate with the highest amount.
	 *
	 * @return array<string,mixed>|null
	 */
	private static function fetchFeatured( int $id ): ?array
	{
		try
		{
			if ( $id > 0 )
			{
				$r = \IPS\Db::i()->select(
					'*', 'gd_rebates', [ 'id=? AND status=?', $id, 'active' ]
				)->first();
				if ( is_array( $r ) )
				{
					return self::decorate( $r );
				}
			}

			$r = \IPS\Db::i()->select(
				'*', 'gd_rebates', [ 'status=?', 'active' ],
				'rebate_amount DESC, end_date ASC', 1
			)->first();
			return is_array( $r ) ? self::decorate( $r ) : null;
		}
		catch ( \UnderflowException )
		{
			return null;
		}
		catch ( \Exception )
		{
			return null;
		}
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private static function fetchExpiring( int $days, int $limit ): array
	{
		$out = [];
		$cutoff = date( 'Y-m-d', strtotime( '+' . max( 1, $days ) . ' days' ) );
		try
		{
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_rebates',
				[ 'status=? AND end_date<=? AND end_date>=?', 'active', $cutoff, date( 'Y-m-d' ) ],
				'end_date ASC', $limit
			) as $r )
			{
				if ( is_array( $r ) )
				{
					$out[] = self::decorate( $r );
				}
			}
		}
		catch ( \Exception ) {}
		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private static function fetchNewest( int $limit ): array
	{
		$out = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_rebates', [ 'status=?', 'active' ], 'created_at DESC', $limit
			) as $r )
			{
				if ( is_array( $r ) )
				{
					$out[] = self::decorate( $r );
				}
			}
		}
		catch ( \Exception ) {}
		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private static function fetchTopMfrs( int $limit ): array
	{
		$out = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'manufacturer, COUNT(*) AS c', 'gd_rebates',
				[ 'status=?', 'active' ], 'c DESC', $limit, 'manufacturer'
			) as $r )
			{
				$out[] = [
					'manufacturer' => (string) ( $r['manufacturer'] ?? '' ),
					'count'        => (int)    ( $r['c']            ?? 0 ),
				];
			}
		}
		catch ( \Exception ) {}
		return $out;
	}

	/**
	 * @return array<string,int>
	 */
	private static function fetchByTypeCounts(): array
	{
		$out = [];
		foreach ( \IPS\gdrebates\Rebate\Rebate::productTypes() as $t )
		{
			$out[ $t ] = 0;
		}
		try
		{
			foreach ( \IPS\Db::i()->select(
				'product_type, COUNT(*) AS c', 'gd_rebates',
				[ 'status=?', 'active' ], null, null, 'product_type'
			) as $r )
			{
				$k = (string) ( $r['product_type'] ?? '' );
				if ( isset( $out[ $k ] ) )
				{
					$out[ $k ] = (int) $r['c'];
				}
			}
		}
		catch ( \Exception ) {}
		return $out;
	}

	/**
	 * Flatten a rebate row with display-time extras (URL + days-left).
	 *
	 * @param array<string,mixed> $r
	 * @return array<string,mixed>
	 */
	private static function decorate( array $r ): array
	{
		$id    = (int) ( $r['id'] ?? 0 );
		$title = (string) ( $r['title'] ?? '' );
		$slug  = \IPS\gdrebates\Rebate\Rebate::slug( $title );

		$daysLeft = 0;
		$end = (string) ( $r['end_date'] ?? '' );
		if ( $end !== '' )
		{
			$ts = strtotime( $end );
			if ( $ts !== false )
			{
				$daysLeft = (int) ceil( ( $ts - time() ) / 86400 );
			}
		}
		$r['days_left'] = $daysLeft;
		$r['view_url']  = (string) \IPS\Http\Url::internal(
			'app=gdrebates&module=rebates&controller=view&id=' . $id,
			'front',
			'rebates_view',
			[ $slug ]
		);
		return $r;
	}
}

class hub extends _hub {}
