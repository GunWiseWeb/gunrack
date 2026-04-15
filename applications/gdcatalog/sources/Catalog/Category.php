<?php
/**
 * @brief       GD Master Catalog — Category Model
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * ActiveRecord model for the gd_categories table.
 * Simple parent/child taxonomy — 13 top-level categories with subcategories.
 */

namespace IPS\gdcatalog\Catalog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}
class Category extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief [ActiveRecord] Database table
	 */
	public static ?string $databaseTable = 'gd_categories';

	/**
	 * @brief [ActiveRecord] ID column
	 */
	public static string $databaseColumnId = 'id';

	/**
	 * @brief [ActiveRecord] No column prefix
	 */
	public static string $databasePrefix = '';

	/**
	 * @brief [ActiveRecord] Multiton store
	 */
	protected static array $multitons = [];

	/**
	 * @brief In-memory cache of slug-to-id lookups
	 */
	protected static array $slugMap = [];

	/**
	 * Get the parent category, or NULL if this is a top-level category.
	 *
	 * @return static|null
	 */
	public function parent(): ?static
	{
		if ( !$this->parent_id )
		{
			return null;
		}

		try
		{
			return static::load( $this->parent_id );
		}
		catch ( \OutOfRangeException )
		{
			return null;
		}
	}

	/**
	 * Whether this is a top-level category.
	 *
	 * @return bool
	 */
	public function isTopLevel(): bool
	{
		return (int) $this->parent_id === 0;
	}

	/**
	 * Get direct child categories.
	 *
	 * @return array<static>
	 */
	public function children(): array
	{
		$results = [];
		foreach (
			\IPS\Db::i()->select( '*', 'gd_categories', [ 'parent_id=?', $this->id ], 'position ASC' ) as $row
		)
		{
			$results[] = static::constructFromData( $row );
		}
		return $results;
	}

	/**
	 * Load a category by its slug.
	 *
	 * @param  string $slug
	 * @return static
	 * @throws \OutOfRangeException
	 */
	public static function loadBySlug( string $slug ): static
	{
		if ( isset( static::$slugMap[$slug] ) )
		{
			return static::load( static::$slugMap[$slug] );
		}

		$row = \IPS\Db::i()->select( '*', 'gd_categories', [ 'slug=?', $slug ] )->first();
		$obj = static::constructFromData( $row );
		static::$slugMap[$slug] = $obj->id;
		return $obj;
	}

	/**
	 * Get all top-level categories ordered by position.
	 *
	 * @return array<static>
	 */
	public static function roots(): array
	{
		$results = [];
		foreach (
			\IPS\Db::i()->select( '*', 'gd_categories', [ 'parent_id=?', 0 ], 'position ASC' ) as $row
		)
		{
			$results[] = static::constructFromData( $row );
		}
		return $results;
	}

	/**
	 * Get the full path label: "Handguns > Pistols"
	 *
	 * @return string
	 */
	public function breadcrumb(): string
	{
		$parent = $this->parent();
		if ( $parent )
		{
			return $parent->name . ' > ' . $this->name;
		}
		return $this->name;
	}

	/**
	 * Increment the product count for this category (and its parent).
	 *
	 * @param  int $delta  Positive or negative
	 * @return void
	 */
	public function adjustProductCount( int $delta ): void
	{
		\IPS\Db::i()->update(
			'gd_categories',
			'product_count = GREATEST(0, product_count + ' . (int) $delta . ')',
			[ 'id=?', $this->id ]
		);

		if ( $this->parent_id )
		{
			\IPS\Db::i()->update(
				'gd_categories',
				'product_count = GREATEST(0, product_count + ' . (int) $delta . ')',
				[ 'id=?', $this->parent_id ]
			);
		}
	}
}
