<?php
/**
 * @brief       GD Price Comparison — Outbound click logger
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\Click;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class ClickLog
{
	/**
	 * Record a raw click. Writes to gd_click_log only — daily aggregation into
	 * gd_click_daily is handled by the scheduled task.
	 */
	public static function record( int $listingId, string $upc, int $dealerId, ?int $memberId, ?string $userState ): void
	{
		\IPS\Db::i()->insert( 'gd_click_log', [
			'listing_id' => $listingId,
			'upc'        => $upc,
			'dealer_id'  => $dealerId,
			'member_id'  => $memberId,
			'user_state' => $userState,
			'clicked_at' => date( 'Y-m-d H:i:s' ),
		]);
	}

	/**
	 * Total clicks today across all listings — used on the admin dashboard.
	 */
	public static function clicksToday(): int
	{
		try
		{
			return (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_click_log',
				[ 'clicked_at >= ?', date( 'Y-m-d 00:00:00' ) ]
			)->first();
		}
		catch ( \Exception )
		{
			return 0;
		}
	}

	/**
	 * Aggregate all un-aggregated raw rows up to the previous calendar day into
	 * gd_click_daily, then delete the raw rows. Called by the daily task.
	 *
	 * @return int Number of listing/date rows upserted.
	 */
	public static function aggregateYesterday(): int
	{
		$cutoff = date( 'Y-m-d 00:00:00' );
		$sums = \IPS\Db::i()->select(
			'listing_id, dealer_id, DATE(clicked_at) AS click_date, COUNT(*) AS click_count',
			'gd_click_log',
			[ 'clicked_at < ?', $cutoff ],
			null,
			null,
			[ 'listing_id', 'dealer_id', 'click_date' ]
		);

		$touched = 0;
		foreach ( $sums as $row )
		{
			try
			{
				$existing = \IPS\Db::i()->select(
					'id, click_count', 'gd_click_daily',
					[ 'listing_id=? AND click_date=?', (int) $row['listing_id'], (string) $row['click_date'] ]
				)->first();

				\IPS\Db::i()->update( 'gd_click_daily', [
					'click_count' => (int) $existing['click_count'] + (int) $row['click_count'],
				], [ 'id=?', (int) $existing['id'] ]);
			}
			catch ( \UnderflowException )
			{
				\IPS\Db::i()->insert( 'gd_click_daily', [
					'listing_id'  => (int) $row['listing_id'],
					'dealer_id'   => (int) $row['dealer_id'],
					'click_date'  => (string) $row['click_date'],
					'click_count' => (int) $row['click_count'],
				]);
			}
			$touched++;
		}

		\IPS\Db::i()->delete( 'gd_click_log', [ 'clicked_at < ?', $cutoff ]);
		return $touched;
	}
}
