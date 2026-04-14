<?php
/**
 * @brief       GD Master Catalog — Compliance Flag Processor
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Processes compliance fields detected in distributor feeds (Section 2.10).
 * All distributor-sourced flags are queued as pending_review — none go live
 * without admin approval. Handles conflict between distributor flags and
 * admin-set restrictions.
 */

namespace IPS\gdcatalog\Compliance;

class FlagProcessor
{
	/**
	 * Process compliance fields detected in a feed record.
	 *
	 * @param  string $upc
	 * @param  int    $distributorId  gd_distributor_feeds.id
	 * @param  array  $fields         field_name => raw value (e.g. 'restricted_states' => 'CA,NY,MA')
	 * @param  int    $importId       gd_import_log.id
	 * @return void
	 */
	public static function processFromFeed( string $upc, int $distributorId, array $fields, int $importId ): void
	{
		$now = date( 'Y-m-d H:i:s' );

		foreach ( $fields as $fieldName => $rawValue )
		{
			$flagType  = static::detectFlagType( $fieldName );
			$flagValue = static::normaliseValue( $flagType, $rawValue );

			if ( $flagValue === '' )
			{
				continue;
			}

			/* Check if an identical flag already exists for this UPC + distributor + type */
			$existing = static::findExisting( $upc, $distributorId, $flagType, $flagValue );

			if ( $existing !== null )
			{
				/* Flag already exists — update last_confirmed_at */
				\IPS\Db::i()->update( 'gd_compliance_flags', [
					'last_confirmed_at' => $now,
					'removed_by_dist_at' => null,
				], [ 'id=?', $existing['id'] ] );
				continue;
			}

			/* Check for conflict with admin-set restrictions */
			$adminFlag = static::findAdminFlag( $upc, $flagType );

			if ( $adminFlag !== null && $adminFlag['flag_value'] !== $flagValue )
			{
				/* Distributor disagrees with admin-set restriction — write a feed conflict
				   but do NOT change the admin flag (Section 2.11.1) */
				\IPS\Db::i()->insert( 'gd_feed_conflicts', [
					'upc'             => $upc,
					'listing_id'      => null,
					'distributor_id'  => $distributorId,
					'field_name'      => $fieldName,
					'current_value'   => $adminFlag['flag_value'],
					'incoming_value'  => $flagValue,
					'import_id'       => $importId,
					'detected_at'     => $now,
					'status'          => 'pending',
					'auto_resolve_at' => date( 'Y-m-d H:i:s', time() + ( (int) \IPS\Settings::i()->gdcatalog_auto_resolve_hours * 3600 ) ),
				]);
				continue;
			}

			/* New flag — queue for admin review (Section 2.10.2) */
			\IPS\Db::i()->insert( 'gd_compliance_flags', [
				'upc'               => $upc,
				'listing_id'        => null,
				'distributor_id'    => $distributorId,
				'flag_type'         => $flagType,
				'flag_value'        => $flagValue,
				'source'            => 'distributor',
				'status'            => 'pending_review',
				'first_seen_at'     => $now,
				'last_confirmed_at' => $now,
				'removed_by_dist_at' => null,
				'admin_reviewed_by' => null,
				'admin_reviewed_at' => null,
			]);
		}
	}

	/**
	 * Handle a distributor REMOVING a restriction that was previously sent.
	 * Called when a field that was previously present is absent from the feed.
	 *
	 * @param  string $upc
	 * @param  int    $distributorId
	 * @param  string $flagType
	 * @return void
	 */
	public static function markRemovedByDistributor( string $upc, int $distributorId, string $flagType ): void
	{
		$now = date( 'Y-m-d H:i:s' );

		$flags = \IPS\Db::i()->select(
			'*', 'gd_compliance_flags',
			[
				'upc=? AND distributor_id=? AND flag_type=? AND source=? AND removed_by_dist_at IS NULL',
				$upc, $distributorId, $flagType, 'distributor'
			]
		);

		foreach ( $flags as $flag )
		{
			\IPS\Db::i()->update( 'gd_compliance_flags', [
				'removed_by_dist_at' => $now,
			], [ 'id=?', $flag['id'] ] );
		}
	}

	/**
	 * Admin approves a pending flag.
	 *
	 * @param  int $flagId
	 * @param  int $adminId  IPS member ID
	 * @return void
	 */
	public static function approve( int $flagId, int $adminId ): void
	{
		\IPS\Db::i()->update( 'gd_compliance_flags', [
			'status'            => 'active',
			'admin_reviewed_by' => $adminId,
			'admin_reviewed_at' => date( 'Y-m-d H:i:s' ),
		], [ 'id=?', $flagId ] );
	}

	/**
	 * Admin rejects a pending flag.
	 *
	 * @param  int $flagId
	 * @param  int $adminId
	 * @return void
	 */
	public static function reject( int $flagId, int $adminId ): void
	{
		\IPS\Db::i()->update( 'gd_compliance_flags', [
			'status'            => 'rejected',
			'admin_reviewed_by' => $adminId,
			'admin_reviewed_at' => date( 'Y-m-d H:i:s' ),
		], [ 'id=?', $flagId ] );
	}

	/**
	 * Admin creates a manual restriction.
	 *
	 * @param  string   $upc
	 * @param  int|null $listingId   NULL = product-level, set = listing-level
	 * @param  string   $flagType
	 * @param  string   $flagValue
	 * @param  int      $adminId
	 * @return void
	 */
	public static function createAdminFlag(
		string $upc, ?int $listingId, string $flagType, string $flagValue, int $adminId
	): void
	{
		$now = date( 'Y-m-d H:i:s' );

		\IPS\Db::i()->insert( 'gd_compliance_flags', [
			'upc'               => $upc,
			'listing_id'        => $listingId,
			'distributor_id'    => 0,
			'flag_type'         => $flagType,
			'flag_value'        => $flagValue,
			'source'            => 'admin_manual',
			'status'            => 'active',
			'first_seen_at'     => $now,
			'last_confirmed_at' => $now,
			'admin_reviewed_by' => $adminId,
			'admin_reviewed_at' => $now,
		]);
	}

	/* ── Lookup helpers ── */

	/**
	 * Find an existing compliance flag matching UPC + distributor + type + value.
	 *
	 * @return array|null
	 */
	protected static function findExisting( string $upc, int $distributorId, string $flagType, string $flagValue ): ?array
	{
		try
		{
			return \IPS\Db::i()->select(
				'*', 'gd_compliance_flags',
				[
					'upc=? AND distributor_id=? AND flag_type=? AND flag_value=? AND source=?',
					$upc, $distributorId, $flagType, $flagValue, 'distributor'
				]
			)->first();
		}
		catch ( \UnderflowException )
		{
			return null;
		}
	}

	/**
	 * Find an admin-set flag for this UPC + type.
	 *
	 * @return array|null
	 */
	protected static function findAdminFlag( string $upc, string $flagType ): ?array
	{
		try
		{
			return \IPS\Db::i()->select(
				'*', 'gd_compliance_flags',
				[
					'upc=? AND flag_type=? AND source IN(?,?) AND status=?',
					$upc, $flagType, 'admin_manual', 'admin_override', 'active'
				]
			)->first();
		}
		catch ( \UnderflowException )
		{
			return null;
		}
	}

	/**
	 * Map a raw field name to the compliance flag_type enum.
	 *
	 * @param  string $fieldName
	 * @return string
	 */
	protected static function detectFlagType( string $fieldName ): string
	{
		return match ( $fieldName )
		{
			'restricted_states', 'no_ship_to', 'blocked_states',
			'ship_restriction', 'state_restrictions' => 'state_restriction',
			'ffl_required'                           => 'ffl_required',
			'age_verification'                       => 'age_verification',
			'hazmat'                                 => 'hazmat',
			'consumer_prohibited'                    => 'consumer_prohibited',
			default                                  => 'state_restriction',
		};
	}

	/**
	 * Normalise a compliance flag value.
	 * For state restrictions: uppercase, sort, deduplicate state codes.
	 *
	 * @param  string $flagType
	 * @param  string $rawValue
	 * @return string
	 */
	protected static function normaliseValue( string $flagType, string $rawValue ): string
	{
		if ( $flagType === 'state_restriction' )
		{
			$states = array_filter( array_map(
				fn( $s ) => strtoupper( trim( $s ) ),
				preg_split( '/[,;|]+/', $rawValue )
			));
			$states = array_unique( $states );
			sort( $states );
			return implode( ',', $states );
		}

		return trim( $rawValue );
	}
}
