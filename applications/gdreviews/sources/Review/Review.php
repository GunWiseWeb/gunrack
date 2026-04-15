<?php
/**
 * @brief       GD Product Reviews — Review ActiveRecord
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 *
 * Stores a single review authored by a member against a catalog UPC.
 * Business rules enforced at submission time (see Section 7.3):
 *   - One review per member per UPC (uq_upc_member UNIQUE KEY).
 *   - All new submissions land at status = pending.
 *   - product_type is copied from gd_catalog at submission — the review
 *     form itself is uniform across all product types (Section 7.2).
 *   - verified_purchase is set to 1 when a gd_click_log row exists for
 *     the same UPC + member_id.
 */

namespace IPS\gdreviews\Review;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Review extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable    = 'gd_reviews';
	public static string  $databaseColumnId = 'id';
	public static string  $databasePrefix   = '';

	/**
	 * Total reviews by status — used by the admin dashboard.
	 *
	 * @return array<string,int> map of status => count
	 */
	public static function countByStatus(): array
	{
		$out = [ 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'flagged' => 0 ];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'status, COUNT(*) AS c', 'gd_reviews', null, null, null, 'status'
			) as $r )
			{
				$k = (string) ( $r['status'] ?? '' );
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
	 * Determine whether a member has a verified-purchase footprint for a UPC.
	 * Verified = at least one row in gd_click_log with matching member+UPC.
	 */
	public static function isVerifiedPurchase( string $upc, int $memberId ): bool
	{
		if ( $upc === '' || $memberId <= 0 )
		{
			return false;
		}
		try
		{
			$c = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_click_log', [ 'upc=? AND member_id=?', $upc, $memberId ]
			)->first();
			return $c > 0;
		}
		catch ( \Exception )
		{
			return false;
		}
	}

	/**
	 * Returns the existing review row for this member+UPC, or null if none.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function findByMemberUpc( int $memberId, string $upc ): ?array
	{
		if ( $memberId <= 0 || $upc === '' )
		{
			return null;
		}
		try
		{
			$row = \IPS\Db::i()->select(
				'*', 'gd_reviews', [ 'upc=? AND member_id=?', $upc, $memberId ]
			)->first();
			return is_array( $row ) ? $row : null;
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
	 * Mean overall rating across approved, non-resolved reviews for a UPC.
	 */
	public static function averageForUpc( string $upc ): float
	{
		if ( $upc === '' )
		{
			return 0.0;
		}
		try
		{
			$v = \IPS\Db::i()->select(
				'AVG(overall_rating)', 'gd_reviews',
				[ 'upc=? AND status=? AND dispute_status!=?', $upc, 'approved', 'resolved' ]
			)->first();
			return (float) $v;
		}
		catch ( \Exception )
		{
			return 0.0;
		}
	}

	/**
	 * Approved review count for a UPC.
	 */
	public static function approvedCountForUpc( string $upc ): int
	{
		if ( $upc === '' )
		{
			return 0;
		}
		try
		{
			return (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_reviews',
				[ 'upc=? AND status=? AND dispute_status!=?', $upc, 'approved', 'resolved' ]
			)->first();
		}
		catch ( \Exception )
		{
			return 0;
		}
	}

	/**
	 * Build the verified_purchase flag for a new submission and snapshot the
	 * product_type from gd_catalog. Returns an associative payload the
	 * controller can insert/update directly.
	 *
	 * @return array{verified_purchase:int, product_type:string}
	 */
	public static function buildSubmissionMeta( string $upc, int $memberId ): array
	{
		$productType = '';
		try
		{
			$productType = (string) \IPS\Db::i()->select(
				'product_type', 'gd_catalog', [ 'upc=?', $upc ]
			)->first();
		}
		catch ( \Exception ) {}

		return [
			'verified_purchase' => self::isVerifiedPurchase( $upc, $memberId ) ? 1 : 0,
			'product_type'      => $productType,
		];
	}
}

class Review extends _Review {}
