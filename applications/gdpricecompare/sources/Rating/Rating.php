<?php
/**
 * @brief       GD Price Comparison — Dealer rating ActiveRecord
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\Rating;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Rating extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable    = 'gd_dealer_ratings';
	public static string  $databasePrefix   = '';
	public static string  $databaseColumnId = 'id';

	const STATUS_PENDING  = 'pending';
	const STATUS_APPROVED = 'approved';
	const STATUS_REJECTED = 'rejected';

	/**
	 * Insert or update a member's rating for a dealer. Star ratings show
	 * immediately; written reviews are flagged pending for moderation.
	 */
	public static function submit( int $dealerId, int $memberId, int $pricing, int $shipping, int $service, ?string $reviewText ): void
	{
		$pricing  = max( 1, min( 5, $pricing ) );
		$shipping = max( 1, min( 5, $shipping ) );
		$service  = max( 1, min( 5, $service ) );
		$status   = $reviewText !== null && trim( $reviewText ) !== '' ? self::STATUS_PENDING : self::STATUS_APPROVED;
		$now      = date( 'Y-m-d H:i:s' );

		try
		{
			$existing = \IPS\Db::i()->select(
				'*', 'gd_dealer_ratings',
				[ 'dealer_id=? AND member_id=?', $dealerId, $memberId ]
			)->first();

			\IPS\Db::i()->update( 'gd_dealer_ratings', [
				'rating_pricing'  => $pricing,
				'rating_shipping' => $shipping,
				'rating_service'  => $service,
				'review_text'     => $reviewText,
				'status'          => $status,
				'updated_at'      => $now,
			], [ 'id=?', (int) $existing['id'] ]);
		}
		catch ( \UnderflowException )
		{
			\IPS\Db::i()->insert( 'gd_dealer_ratings', [
				'dealer_id'       => $dealerId,
				'member_id'       => $memberId,
				'rating_pricing'  => $pricing,
				'rating_shipping' => $shipping,
				'rating_service'  => $service,
				'review_text'     => $reviewText,
				'status'          => $status,
				'created_at'      => $now,
				'updated_at'      => null,
			]);
		}
	}

	/**
	 * Aggregate scores for a dealer. Returns null if fewer than $minCount
	 * ratings exist — caller suppresses the rating UI in that case.
	 *
	 * @return array{pricing:float, shipping:float, service:float, overall:float, count:int}|null
	 */
	public static function aggregateFor( int $dealerId, int $minCount = 3 ): ?array
	{
		try
		{
			$row = \IPS\Db::i()->select(
				'COUNT(*) AS c, AVG(rating_pricing) AS p, AVG(rating_shipping) AS s, AVG(rating_service) AS v',
				'gd_dealer_ratings',
				[ 'dealer_id=?', $dealerId ]
			)->first();
		}
		catch ( \Exception )
		{
			return null;
		}

		$count = (int) $row['c'];
		if ( $count < $minCount )
		{
			return null;
		}

		$pricing  = (float) $row['p'];
		$shipping = (float) $row['s'];
		$service  = (float) $row['v'];
		$overall  = ( $pricing + $shipping + $service ) / 3.0;

		return [
			'pricing'  => round( $pricing, 2 ),
			'shipping' => round( $shipping, 2 ),
			'service'  => round( $service, 2 ),
			'overall'  => round( $overall, 2 ),
			'count'    => $count,
		];
	}
}

class Rating extends _Rating {}
