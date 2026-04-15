<?php
/**
 * @brief       GD Master Catalog — Product Model
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * ActiveRecord model for the gd_catalog table.
 * One row per UPC — the single canonical product record.
 */

namespace IPS\gdcatalog\Catalog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}
class Product extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief [ActiveRecord] Database table
	 */
	public static ?string $databaseTable = 'gd_catalog';

	/**
	 * @brief [ActiveRecord] ID column — UPC is the primary key (VARCHAR, not auto-increment)
	 */
	public static string $databaseColumnId = 'upc';

	/**
	 * @brief [ActiveRecord] No column prefix
	 */
	public static string $databasePrefix = '';

	/**
	 * @brief [ActiveRecord] Multiton store
	 */
	protected static array $multitons = [];

	/* ── Status constants ── */
	public const STATUS_ACTIVE       = 'active';
	public const STATUS_DISCONTINUED = 'discontinued';
	public const STATUS_ADMIN_REVIEW = 'admin_review';
	public const STATUS_PENDING      = 'pending';

	/* ── Conflict rule constants ── */
	public const RULE_PRIORITY          = 'priority';
	public const RULE_LONGEST           = 'longest';
	public const RULE_HIGHEST_RES       = 'highest_res';
	public const RULE_HIGHEST_VAL       = 'highest_val';
	public const RULE_FLAGGED_FOR_REVIEW = 'flagged_for_review';
	public const RULE_ADMIN_OVERRIDE    = 'admin_override';
	public const RULE_ANY_TRUE          = 'any_true';
	public const RULE_MERGE_ALL         = 'merge_all';

	/**
	 * Field-to-conflict-rule mapping per Section 2.2.2
	 * Fields not listed here use RULE_PRIORITY (standard hierarchy).
	 */
	public static array $fieldRules = [
		'image_url'          => self::RULE_HIGHEST_RES,
		'additional_images'  => self::RULE_MERGE_ALL,
		'description'        => self::RULE_LONGEST,
		'msrp'               => self::RULE_HIGHEST_VAL,
		'rounds_per_box'     => self::RULE_FLAGGED_FOR_REVIEW,
		'nfa_item'           => self::RULE_ANY_TRUE,
		'requires_ffl'       => self::RULE_ANY_TRUE,
	];

	/**
	 * Priority order — index 0 is highest priority.
	 * Per Section 2.2.1: RSR > Sports South > Davidson's > Lipsey's > Zanders > Bill Hicks
	 */
	public static array $distributorPriority = [
		'rsr_group',
		'sports_south',
		'davidsons',
		'lipseys',
		'zanders',
		'bill_hicks',
	];

	/**
	 * Get the conflict resolution rule for a given field.
	 *
	 * @param  string $field
	 * @return string One of the RULE_* constants
	 */
	public static function ruleForField( string $field ): string
	{
		return static::$fieldRules[$field] ?? self::RULE_PRIORITY;
	}

	/* ================================================================
	 *  JSON helpers — locked_fields, additional_images, distributor_last_seen
	 * ================================================================ */

	/**
	 * Get locked fields as array.
	 *
	 * @return array<string>
	 */
	public function getLockedFields(): array
	{
		$raw = $this->locked_fields;
		if ( empty( $raw ) )
		{
			return [];
		}
		$decoded = json_decode( $raw, true );
		return \is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Check whether a specific field is admin-locked.
	 *
	 * @param  string $field
	 * @return bool
	 */
	public function isFieldLocked( string $field ): bool
	{
		return \in_array( $field, $this->getLockedFields(), true );
	}

	/**
	 * Lock a field against future feed imports.
	 *
	 * @param  string $field
	 * @return void
	 */
	public function lockField( string $field ): void
	{
		$locked = $this->getLockedFields();
		if ( !\in_array( $field, $locked, true ) )
		{
			$locked[] = $field;
			$this->locked_fields = json_encode( array_values( $locked ) );
		}
	}

	/**
	 * Unlock a field to re-enable automatic conflict resolution.
	 *
	 * @param  string $field
	 * @return void
	 */
	public function unlockField( string $field ): void
	{
		$locked = $this->getLockedFields();
		$locked = array_filter( $locked, static fn( $f ) => $f !== $field );
		$this->locked_fields = json_encode( array_values( $locked ) );
	}

	/**
	 * Get additional images as array of URLs.
	 *
	 * @return array<string>
	 */
	public function getAdditionalImages(): array
	{
		$raw = $this->additional_images;
		if ( empty( $raw ) )
		{
			return [];
		}
		$decoded = json_decode( $raw, true );
		return \is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Merge new image URLs into additional_images (deduplicating).
	 *
	 * @param  array<string> $urls
	 * @return void
	 */
	public function mergeAdditionalImages( array $urls ): void
	{
		$existing = $this->getAdditionalImages();
		$merged   = array_values( array_unique( array_merge( $existing, $urls ) ) );
		$this->additional_images = json_encode( $merged );
	}

	/* ── Distributor tracking (per-distributor miss counts) ── */

	/**
	 * Get the distributor_last_seen JSON as array.
	 *
	 * @return array<string, array{last_seen_run_id: int, consecutive_misses: int}>
	 */
	public function getDistributorTracking(): array
	{
		$raw = $this->distributor_last_seen;
		if ( empty( $raw ) )
		{
			return [];
		}
		$decoded = json_decode( $raw, true );
		return \is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Mark a distributor as having seen this product in a run.
	 *
	 * @param  string $distributor
	 * @param  int    $runId
	 * @return void
	 */
	public function markSeenByDistributor( string $distributor, int $runId ): void
	{
		$tracking = $this->getDistributorTracking();
		$tracking[$distributor] = [
			'last_seen_run_id'   => $runId,
			'consecutive_misses' => 0,
		];
		$this->distributor_last_seen = json_encode( $tracking );
	}

	/**
	 * Increment consecutive miss count for a distributor.
	 *
	 * @param  string $distributor
	 * @return int    The new miss count
	 */
	public function incrementMiss( string $distributor ): int
	{
		$tracking = $this->getDistributorTracking();
		if ( !isset( $tracking[$distributor] ) )
		{
			return 0;
		}
		$tracking[$distributor]['consecutive_misses']++;
		$this->distributor_last_seen = json_encode( $tracking );
		return $tracking[$distributor]['consecutive_misses'];
	}

	/* ── Distributor sources helpers ── */

	/**
	 * Get distributor_sources as array.
	 *
	 * @return array<string>
	 */
	public function getDistributorSources(): array
	{
		$raw = $this->distributor_sources;
		return $raw !== '' ? explode( ',', $raw ) : [];
	}

	/**
	 * Add a distributor to the sources list.
	 *
	 * @param  string $distributor
	 * @return void
	 */
	public function addDistributorSource( string $distributor ): void
	{
		$sources = $this->getDistributorSources();
		if ( !\in_array( $distributor, $sources, true ) )
		{
			$sources[] = $distributor;
			$this->distributor_sources = implode( ',', $sources );
		}
	}

	/**
	 * Remove a distributor from the sources list.
	 *
	 * @param  string $distributor
	 * @return void
	 */
	public function removeDistributorSource( string $distributor ): void
	{
		$sources = array_filter(
			$this->getDistributorSources(),
			static fn( $s ) => $s !== $distributor
		);
		$this->distributor_sources = implode( ',', array_values( $sources ) );
	}

	/**
	 * Check whether any distributor still carries this product.
	 *
	 * @return bool
	 */
	public function hasActiveDistributors(): bool
	{
		return $this->distributor_sources !== '';
	}

	/* ── Category helper ── */

	/**
	 * Get the category model for this product.
	 *
	 * @return \IPS\gdcatalog\Catalog\Category|null
	 */
	public function category(): ?\IPS\gdcatalog\Catalog\Category
	{
		if ( !$this->category_id )
		{
			return null;
		}

		try
		{
			return \IPS\gdcatalog\Catalog\Category::load( $this->category_id );
		}
		catch ( \OutOfRangeException )
		{
			return null;
		}
	}

	/* ── Ammo helpers ── */

	/**
	 * Whether this product is ammunition.
	 *
	 * @return bool
	 */
	public function isAmmo(): bool
	{
		return (bool) $this->is_ammo;
	}

	/**
	 * Whether this product is an NFA item.
	 *
	 * @return bool
	 */
	public function isNfa(): bool
	{
		return (bool) $this->nfa_item;
	}

	/**
	 * Whether this product requires FFL transfer.
	 *
	 * @return bool
	 */
	public function requiresFfl(): bool
	{
		return (bool) $this->requires_ffl;
	}
}
