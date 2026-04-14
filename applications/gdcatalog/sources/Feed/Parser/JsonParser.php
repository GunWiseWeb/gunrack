<?php
/**
 * @brief       GD Master Catalog — JSON Feed Parser
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Parses JSON distributor feeds into arrays of key-value records.
 */

namespace IPS\gdcatalog\Feed\Parser;

class JsonParser
{
	/**
	 * Parse a JSON string into an array of associative records.
	 *
	 * Supports two common layouts:
	 *   1. Top-level array: [ {record}, {record}, ... ]
	 *   2. Wrapper object:  { "products": [ {record}, ... ] }
	 *      Auto-detects the first array-valued key.
	 *
	 * @param  string $jsonContent  Raw JSON string
	 * @return array<int, array<string, mixed>>
	 * @throws \RuntimeException  On parse failure
	 */
	public static function parse( string $jsonContent ): array
	{
		$data = json_decode( $jsonContent, true );

		if ( $data === null && json_last_error() !== JSON_ERROR_NONE )
		{
			throw new \RuntimeException( 'JSON parse failed: ' . json_last_error_msg() );
		}

		/* Top-level array */
		if ( \is_array( $data ) && isset( $data[0] ) )
		{
			return static::flatten( $data );
		}

		/* Wrapper object — find first array-valued key */
		if ( \is_array( $data ) )
		{
			foreach ( $data as $key => $value )
			{
				if ( \is_array( $value ) && isset( $value[0] ) )
				{
					return static::flatten( $value );
				}
			}
		}

		throw new \RuntimeException( 'JSON feed does not contain a recognisable product array' );
	}

	/**
	 * Flatten nested objects within each record to dot-separated keys.
	 *
	 * @param  array<int, array> $records
	 * @return array<int, array<string, string>>
	 */
	protected static function flatten( array $records ): array
	{
		$result = [];
		foreach ( $records as $record )
		{
			if ( \is_array( $record ) )
			{
				$result[] = static::flattenRecord( $record );
			}
		}
		return $result;
	}

	/**
	 * Flatten a single nested record.
	 *
	 * @param  array  $record
	 * @param  string $prefix
	 * @return array<string, string>
	 */
	protected static function flattenRecord( array $record, string $prefix = '' ): array
	{
		$flat = [];
		foreach ( $record as $key => $value )
		{
			$fullKey = $prefix !== '' ? $prefix . '_' . $key : $key;

			if ( \is_array( $value ) && !isset( $value[0] ) )
			{
				/* Nested object — recurse */
				$flat = array_merge( $flat, static::flattenRecord( $value, $fullKey ) );
			}
			elseif ( \is_array( $value ) )
			{
				/* Array of scalars — join with commas */
				$flat[$fullKey] = implode( ',', array_map( 'strval', $value ) );
			}
			else
			{
				$flat[$fullKey] = (string) $value;
			}
		}
		return $flat;
	}
}
