<?php
/**
 * @brief       GD Master Catalog — Feed Conflict Model
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * ActiveRecord for gd_feed_conflicts. Incoming values that disagree
 * with the database, held pending admin resolution or 48-hour auto-accept.
 */

namespace IPS\gdcatalog\Conflict;

class FeedConflict extends \IPS\Patterns\ActiveRecord
{
	public static string $databaseTable    = 'gd_feed_conflicts';
	public static string $databaseColumnId = 'id';
	public static string $databasePrefix   = '';
	protected static array $multitons      = [];

	/**
	 * Get all pending conflicts.
	 *
	 * @return array<static>
	 */
	public static function loadPending(): array
	{
		$results = [];
		foreach (
			\IPS\Db::i()->select( '*', 'gd_feed_conflicts', [ 'status=?', 'pending' ], 'detected_at ASC' ) as $row
		)
		{
			$results[] = static::constructFromData( $row );
		}
		return $results;
	}

	/**
	 * Get conflicts that are past their auto-resolve deadline.
	 *
	 * @return array<static>
	 */
	public static function loadExpired(): array
	{
		$results = [];
		foreach (
			\IPS\Db::i()->select(
				'*', 'gd_feed_conflicts',
				[ 'status=? AND auto_resolve_at <= ?', 'pending', date( 'Y-m-d H:i:s' ) ]
			) as $row
		)
		{
			$results[] = static::constructFromData( $row );
		}
		return $results;
	}

	/**
	 * Admin accepts the incoming value.
	 *
	 * @param  int $adminId
	 * @return void
	 */
	public function acceptIncoming( int $adminId ): void
	{
		$this->applyIncomingValue();

		$this->status          = 'accepted';
		$this->resolved_by     = $adminId;
		$this->resolved_at     = date( 'Y-m-d H:i:s' );
		$this->save();
	}

	/**
	 * Admin keeps the existing value and creates a distributor-specific lock.
	 *
	 * @param  int    $adminId
	 * @param  string $reason  Required lock reason note
	 * @return void
	 */
	public function keepExisting( int $adminId, string $reason ): void
	{
		$this->status          = 'kept';
		$this->resolved_by     = $adminId;
		$this->resolved_at     = date( 'Y-m-d H:i:s' );
		$this->resolution_note = $reason;
		$this->save();

		/* Create distributor-specific lock */
		\IPS\Db::i()->insert( 'gd_field_locks', [
			'upc'                   => $this->upc,
			'listing_id'            => $this->listing_id,
			'field_name'            => $this->field_name,
			'locked_value'          => $this->current_value,
			'lock_type'             => 'distributor_specific',
			'locked_distributor_id' => $this->distributor_id,
			'locked_by'             => $adminId,
			'locked_at'             => date( 'Y-m-d H:i:s' ),
			'lock_reason'           => $reason,
		]);
	}

	/**
	 * Admin sets a custom value and creates a hard lock.
	 *
	 * @param  int    $adminId
	 * @param  string $customValue
	 * @param  string $reason      Required lock reason note
	 * @return void
	 */
	public function setCustom( int $adminId, string $customValue, string $reason ): void
	{
		$this->applyValue( $customValue );

		$this->status          = 'custom';
		$this->resolved_by     = $adminId;
		$this->resolved_at     = date( 'Y-m-d H:i:s' );
		$this->resolution_note = $reason;
		$this->save();

		/* Create hard lock */
		\IPS\Db::i()->insert( 'gd_field_locks', [
			'upc'                   => $this->upc,
			'listing_id'            => $this->listing_id,
			'field_name'            => $this->field_name,
			'locked_value'          => $customValue,
			'lock_type'             => 'hard',
			'locked_distributor_id' => null,
			'locked_by'             => $adminId,
			'locked_at'             => date( 'Y-m-d H:i:s' ),
			'lock_reason'           => $reason,
		]);
	}

	/**
	 * Auto-resolve: apply incoming value after 48-hour timer elapses.
	 *
	 * @return void
	 */
	public function autoResolve(): void
	{
		$this->applyIncomingValue();

		$this->status      = 'auto_accepted';
		$this->resolved_by = null;
		$this->resolved_at = date( 'Y-m-d H:i:s' );
		$this->save();
	}

	/**
	 * Apply the incoming value to the product record.
	 *
	 * @return void
	 */
	protected function applyIncomingValue(): void
	{
		$this->applyValue( $this->incoming_value );
	}

	/**
	 * Apply a value to the product record field.
	 *
	 * @param  string $value
	 * @return void
	 */
	protected function applyValue( string $value ): void
	{
		try
		{
			$product = \IPS\gdcatalog\Catalog\Product::load( $this->upc );
			$field   = $this->field_name;
			$product->$field      = $value;
			$product->last_updated = date( 'Y-m-d H:i:s' );
			$product->save();

			/* Queue reindex */
			\IPS\Db::i()->replace( 'gd_reindex_queue', [
				'upc'       => $this->upc,
				'queued_at' => date( 'Y-m-d H:i:s' ),
			]);
		}
		catch ( \OutOfRangeException )
		{
			/* Product deleted — nothing to update */
		}
	}
}
