<?php
/**
 * @brief       GD Product Reviews — Reviews Hub (/reviews)
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 *
 * Section 7.6 — the hub homepage. OpenSearch is the eventual backing
 * store (Section 7.7, still unspecified), so every query here runs
 * directly against the DB. Each query is wrapped in its own try/catch
 * so the hub still renders when a table is missing or empty.
 */

namespace IPS\gdreviews\modules\front\reviews;

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
		$settings = \IPS\Settings::i();
		$latestN  = max( 1, (int) ( $settings->gdr_hub_latest_limit ?? 20 ) );
		$minN     = max( 1, (int) ( $settings->gdr_top_rated_min_reviews ?? 5 ) );

		$latest        = $this->fetchLatest( $latestN );
		$topRated      = $this->fetchTopRated( $minN, 10 );
		$mostReviewed  = $this->fetchMostReviewed( 10 );
		$verified      = $this->fetchVerifiedLatest( 10 );
		$featured      = $this->fetchFeatured( (int) ( $settings->gdr_featured_review_id ?? 0 ) );

		$totalReviews        = 0;
		$totalProductsRated  = 0;
		$totalVerified       = 0;
		try { $totalReviews       = (int) \IPS\Db::i()->select( 'COUNT(*)',          'gd_reviews', [ 'status=?', 'approved' ] )->first(); } catch ( \Exception ) {}
		try { $totalProductsRated = (int) \IPS\Db::i()->select( 'COUNT(DISTINCT upc)','gd_reviews', [ 'status=?', 'approved' ] )->first(); } catch ( \Exception ) {}
		try { $totalVerified      = (int) \IPS\Db::i()->select( 'COUNT(*)',          'gd_reviews', [ 'status=? AND verified_purchase=?', 'approved', 1 ] )->first(); } catch ( \Exception ) {}

		$data = [
			'latest'              => $latest,
			'top_rated'           => $topRated,
			'most_reviewed'       => $mostReviewed,
			'verified'            => $verified,
			'featured'            => $featured,
			'total_reviews'       => $totalReviews,
			'total_products'      => $totalProductsRated,
			'total_verified'      => $totalVerified,
			'search_url'          => (string) \IPS\Http\Url::internal( 'app=gdreviews&module=reviews&controller=hub' ),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_front_hub_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'reviews', 'gdreviews', 'front' )
			->hub( $data );
	}

	/** @return array<int, array<string,mixed>> */
	private function fetchLatest( int $limit ): array
	{
		$out = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'id, upc, member_id, title, body, overall_rating, created_at, verified_purchase',
				'gd_reviews', [ 'status=? AND dispute_status!=?', 'approved', 'resolved' ],
				'created_at DESC', [ 0, $limit ]
			) as $r )
			{
				$out[] = self::shapeReviewRow( $r );
			}
		}
		catch ( \Exception ) {}
		return $out;
	}

	/** @return array<int, array<string,mixed>> */
	private function fetchTopRated( int $minReviews, int $limit ): array
	{
		$out = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'upc, AVG(overall_rating) AS avg_rating, COUNT(*) AS cnt',
				'gd_reviews', [ 'status=? AND dispute_status!=?', 'approved', 'resolved' ],
				'avg_rating DESC, cnt DESC', [ 0, $limit ],
				'upc', 'COUNT(*) >= ' . (int) $minReviews
			) as $r )
			{
				$out[] = [
					'upc'        => (string) $r['upc'],
					'avg_rating' => round( (float) $r['avg_rating'], 2 ),
					'count'      => (int) $r['cnt'],
				];
			}
		}
		catch ( \Exception ) {}
		return $out;
	}

	/** @return array<int, array<string,mixed>> */
	private function fetchMostReviewed( int $limit ): array
	{
		$out = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'upc, COUNT(*) AS cnt',
				'gd_reviews', [ 'status=?', 'approved' ],
				'cnt DESC', [ 0, $limit ],
				'upc'
			) as $r )
			{
				$out[] = [
					'upc'   => (string) $r['upc'],
					'count' => (int) $r['cnt'],
				];
			}
		}
		catch ( \Exception ) {}
		return $out;
	}

	/** @return array<int, array<string,mixed>> */
	private function fetchVerifiedLatest( int $limit ): array
	{
		$out = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'id, upc, member_id, title, body, overall_rating, created_at, verified_purchase',
				'gd_reviews',
				[ 'status=? AND verified_purchase=? AND dispute_status!=?', 'approved', 1, 'resolved' ],
				'created_at DESC', [ 0, $limit ]
			) as $r )
			{
				$out[] = self::shapeReviewRow( $r );
			}
		}
		catch ( \Exception ) {}
		return $out;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private function fetchFeatured( int $id ): ?array
	{
		if ( $id <= 0 )
		{
			return null;
		}
		try
		{
			$r = \IPS\Db::i()->select(
				'*', 'gd_reviews', [ 'id=? AND status=?', $id, 'approved' ]
			)->first();
			return self::shapeReviewRow( $r );
		}
		catch ( \Exception )
		{
			return null;
		}
	}

	/**
	 * @param array<string,mixed> $r
	 * @return array<string,mixed>
	 */
	private static function shapeReviewRow( array $r ): array
	{
		$body = (string) ( $r['body'] ?? '' );
		return [
			'id'                => (int) ( $r['id'] ?? 0 ),
			'upc'               => (string) ( $r['upc'] ?? '' ),
			'member_id'         => (int) ( $r['member_id'] ?? 0 ),
			'title'             => (string) ( $r['title'] ?? '' ),
			'excerpt'           => mb_substr( $body, 0, 200 ),
			'overall_rating'    => (int) ( $r['overall_rating'] ?? 0 ),
			'created_at'        => (string) ( $r['created_at'] ?? '' ),
			'verified_purchase' => (int) ( $r['verified_purchase'] ?? 0 ) === 1,
		];
	}
}

class hub extends _hub {}
