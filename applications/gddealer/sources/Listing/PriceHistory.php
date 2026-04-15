<?php
/**
 * @brief       GD Dealer Manager — Price History writer
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Writes to gd_price_history only when price or stock status actually
 * changes. This keeps the table manageable and preserves meaningful chart
 * data points.
 */

namespace IPS\gddealer\Listing;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class PriceHistory
{
	/**
	 * Insert a new history row for a dealer+UPC snapshot.
	 */
	public static function record( int $dealerId, string $upc, float $price, bool $inStock ): void
	{
		\IPS\Db::i()->insert( 'gd_price_history', [
			'upc'         => $upc,
			'dealer_id'   => $dealerId,
			'price'       => $price,
			'in_stock'    => $inStock ? 1 : 0,
			'recorded_at' => date( 'Y-m-d H:i:s' ),
		]);
	}

	/**
	 * Fetch the price history for a UPC, optionally narrowed to one dealer,
	 * for chart rendering on the product detail page.
	 *
	 * @return array<int, array{dealer_id:int, price:float, in_stock:int, recorded_at:string}>
	 */
	public static function seriesFor( string $upc, ?int $dealerId = null, int $days = 90 ): array
	{
		$where = [
			[ 'upc=?', $upc ],
			[ 'recorded_at >= ?', date( 'Y-m-d H:i:s', time() - ( $days * 86400 ) ) ],
		];
		if ( $dealerId !== null )
		{
			$where[] = [ 'dealer_id=?', $dealerId ];
		}

		$rows = [];
		foreach ( \IPS\Db::i()->select( '*', 'gd_price_history', $where, 'recorded_at ASC' ) as $r )
		{
			$rows[] = [
				'dealer_id'   => (int) $r['dealer_id'],
				'price'       => (float) $r['price'],
				'in_stock'    => (int) $r['in_stock'],
				'recorded_at' => (string) $r['recorded_at'],
			];
		}
		return $rows;
	}

	/**
	 * Lowest price ever recorded for a UPC.
	 */
	public static function allTimeLow( string $upc ): ?float
	{
		try
		{
			$val = \IPS\Db::i()->select( 'MIN(price)', 'gd_price_history', [ 'upc=?', $upc ] )->first();
			return $val !== null ? (float) $val : null;
		}
		catch ( \UnderflowException )
		{
			return null;
		}
	}
}
