<?php
/**
 * @brief       GD Master Catalog — Field Lock Model
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * ActiveRecord for gd_field_locks. Protects fields against
 * distributor overwrites — distributor-specific or hard locks.
 */

namespace IPS\gdcatalog\Conflict;

class FieldLock extends \IPS\Patterns\ActiveRecord
{
	public static string $databaseTable    = 'gd_field_locks';
	public static string $databaseColumnId = 'id';
	public static string $databasePrefix   = '';
	protected static array $multitons      = [];

	/**
	 * Get all locks for a given UPC.
	 *
	 * @param  string $upc
	 * @return array<static>
	 */
	public static function loadForProduct( string $upc ): array
	{
		$results = [];
		foreach (
			\IPS\Db::i()->select( '*', 'gd_field_locks', [ 'upc=? AND listing_id IS NULL', $upc ] ) as $row
		)
		{
			$results[] = static::constructFromData( $row );
		}
		return $results;
	}

	/**
	 * Get all locks across all products, ordered by lock date.
	 *
	 * @return array<static>
	 */
	public static function loadAllLocks(): array
	{
		$results = [];
		foreach (
			\IPS\Db::i()->select( '*', 'gd_field_locks', null, 'locked_at DESC' ) as $row
		)
		{
			$results[] = static::constructFromData( $row );
		}
		return $results;
	}

	/**
	 * Whether this is a hard lock (blocks ALL distributors).
	 *
	 * @return bool
	 */
	public function isHardLock(): bool
	{
		return $this->lock_type === 'hard';
	}

	/**
	 * Unlock this field — deletes the lock record.
	 *
	 * @return void
	 */
	public function unlock(): void
	{
		\IPS\Db::i()->delete( 'gd_field_locks', [ 'id=?', $this->id ] );
	}
}
