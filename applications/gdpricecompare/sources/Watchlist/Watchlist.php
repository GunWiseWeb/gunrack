<?php
/**
 * @brief       GD Price Comparison — Watchlist ActiveRecord
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\Watchlist;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Watchlist extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable    = 'gd_watchlist';
	public static string  $databasePrefix   = '';
	public static string  $databaseColumnId = 'id';

	/**
	 * Add or update a watch record for member+UPC. Idempotent — a second call
	 * for the same member+UPC updates target_price and notify_in_stock.
	 */
	public static function watch( int $memberId, string $upc, ?float $targetPrice = null, bool $notifyInStock = false ): void
	{
		$now = date( 'Y-m-d H:i:s' );
		try
		{
			$existing = \IPS\Db::i()->select(
				'*', 'gd_watchlist', [ 'member_id=? AND upc=?', $memberId, $upc ]
			)->first();

			\IPS\Db::i()->update( 'gd_watchlist', [
				'target_price'    => $targetPrice,
				'notify_in_stock' => $notifyInStock ? 1 : 0,
			], [ 'id=?', (int) $existing['id'] ]);
		}
		catch ( \UnderflowException )
		{
			\IPS\Db::i()->insert( 'gd_watchlist', [
				'member_id'       => $memberId,
				'upc'             => $upc,
				'target_price'    => $targetPrice,
				'notify_in_stock' => $notifyInStock ? 1 : 0,
				'created_at'      => $now,
				'last_alerted'    => null,
			]);
		}
	}

	public static function unwatch( int $memberId, string $upc ): void
	{
		\IPS\Db::i()->delete( 'gd_watchlist', [ 'member_id=? AND upc=?', $memberId, $upc ]);
	}

	/**
	 * All watchlist rows for a member, paginated.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function forMember( int $memberId, int $offset = 0, int $limit = 50 ): array
	{
		$rows = [];
		foreach ( \IPS\Db::i()->select(
			'*', 'gd_watchlist', [ 'member_id=?', $memberId ], 'created_at DESC', [ $offset, $limit ]
		) as $r )
		{
			$rows[] = $r;
		}
		return $rows;
	}

	/**
	 * All members watching a given UPC — used by the alert dispatcher.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function watchersFor( string $upc ): array
	{
		$rows = [];
		foreach ( \IPS\Db::i()->select( '*', 'gd_watchlist', [ 'upc=?', $upc ] ) as $r )
		{
			$rows[] = $r;
		}
		return $rows;
	}

	public static function markAlerted( int $id ): void
	{
		\IPS\Db::i()->update( 'gd_watchlist', [
			'last_alerted' => date( 'Y-m-d H:i:s' ),
		], [ 'id=?', $id ]);
	}
}

class Watchlist extends _Watchlist {}
