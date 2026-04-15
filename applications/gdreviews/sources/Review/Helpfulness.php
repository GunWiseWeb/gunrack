<?php
/**
 * @brief       GD Product Reviews — Helpfulness vote ActiveRecord
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 *
 * A row per member+review vote. uq_review_member UNIQUE KEY enforces
 * one vote per member per review — changing a vote updates the same row.
 * The gd_reviews.helpful_yes / helpful_no counters are denormalised
 * aggregates and should be recomputed from this table, not mutated
 * in isolation.
 */

namespace IPS\gdreviews\Review;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Helpfulness extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable    = 'gd_review_helpfulness';
	public static string  $databaseColumnId = 'id';
	public static string  $databasePrefix   = '';

	/**
	 * Recompute helpful_yes / helpful_no denormalised counters on a review
	 * from the actual gd_review_helpfulness rows.
	 */
	public static function recountForReview( int $reviewId ): void
	{
		if ( $reviewId <= 0 )
		{
			return;
		}
		try
		{
			$yes = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_review_helpfulness', [ 'review_id=? AND helpful=?', $reviewId, 1 ]
			)->first();
			$no = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_review_helpfulness', [ 'review_id=? AND helpful=?', $reviewId, 0 ]
			)->first();
			\IPS\Db::i()->update( 'gd_reviews', [
				'helpful_yes' => $yes,
				'helpful_no'  => $no,
			], [ 'id=?', $reviewId ] );
		}
		catch ( \Exception ) {}
	}
}

class Helpfulness extends _Helpfulness {}
