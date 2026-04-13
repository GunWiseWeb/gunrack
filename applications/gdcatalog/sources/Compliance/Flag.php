<?php
/**
 * @brief       GD Master Catalog — Compliance Flag Model
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * ActiveRecord for gd_compliance_flags.
 */

namespace IPS\gdcatalog\Compliance;

class _Flag extends \IPS\Patterns\ActiveRecord
{
	public static string $databaseTable    = 'gd_compliance_flags';
	public static string $databaseColumnId = 'id';
	public static string $databasePrefix   = '';
	protected static array $multitons      = [];

	/**
	 * Get all pending review flags.
	 *
	 * @return array<static>
	 */
	public static function loadPending(): array
	{
		$results = [];
		foreach (
			\IPS\Db::i()->select( '*', 'gd_compliance_flags', [ 'status=?', 'pending_review' ], 'first_seen_at ASC' ) as $row
		)
		{
			$results[] = static::constructFromData( $row );
		}
		return $results;
	}

	/**
	 * Get all active flags for a UPC.
	 *
	 * @param  string   $upc
	 * @param  int|null $listingId  NULL = product-level only
	 * @return array<static>
	 */
	public static function loadForProduct( string $upc, ?int $listingId = null ): array
	{
		$where = [ [ 'upc=? AND status=?', $upc, 'active' ] ];

		if ( $listingId === null )
		{
			$where[] = [ 'listing_id IS NULL' ];
		}
		else
		{
			$where[] = [ '(listing_id IS NULL OR listing_id=?)', $listingId ];
		}

		$results = [];
		foreach ( \IPS\Db::i()->select( '*', 'gd_compliance_flags', $where ) as $row )
		{
			$results[] = static::constructFromData( $row );
		}
		return $results;
	}

	/**
	 * Get all admin-set restrictions.
	 *
	 * @return array<static>
	 */
	public static function loadAdminSet(): array
	{
		$results = [];
		foreach (
			\IPS\Db::i()->select( '*', 'gd_compliance_flags', [ 'source IN(?,?)', 'admin_manual', 'admin_override' ], 'admin_reviewed_at DESC' ) as $row
		)
		{
			$results[] = static::constructFromData( $row );
		}
		return $results;
	}

	/**
	 * Whether this flag is admin-set.
	 *
	 * @return bool
	 */
	public function isAdminSet(): bool
	{
		return \in_array( $this->source, [ 'admin_manual', 'admin_override' ], true );
	}

	/**
	 * Get restricted states as array.
	 *
	 * @return array<string>
	 */
	public function getStates(): array
	{
		if ( $this->flag_type !== 'state_restriction' )
		{
			return [];
		}
		return array_filter( explode( ',', $this->flag_value ) );
	}
}
