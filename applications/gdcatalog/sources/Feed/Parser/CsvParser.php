<?php
/**
 * @brief       GD Master Catalog — CSV Feed Parser
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Parses CSV distributor feeds into arrays of key-value records.
 * First row is treated as the header (field names).
 */

namespace IPS\gdcatalog\Feed\Parser;

class _CsvParser
{
	/**
	 * Parse a CSV string into an array of associative records.
	 *
	 * @param  string $csvContent   Raw CSV string
	 * @param  string $delimiter    Column delimiter (default comma)
	 * @param  string $enclosure    Field enclosure (default double-quote)
	 * @return array<int, array<string, string>>
	 * @throws \RuntimeException  On parse failure
	 */
	public static function parse( string $csvContent, string $delimiter = ',', string $enclosure = '"' ): array
	{
		$lines = str_getcsv_rows( $csvContent, $delimiter, $enclosure );

		if ( \count( $lines ) < 2 )
		{
			throw new \RuntimeException( 'CSV feed has no data rows (header only or empty)' );
		}

		$headers = array_shift( $lines );

		/* Trim whitespace from headers */
		$headers = array_map( 'trim', $headers );

		$records = [];
		foreach ( $lines as $row )
		{
			/* Skip rows with wrong column count */
			if ( \count( $row ) !== \count( $headers ) )
			{
				continue;
			}

			$record = [];
			foreach ( $headers as $i => $header )
			{
				if ( $header !== '' )
				{
					$record[$header] = trim( $row[$i] );
				}
			}

			if ( !empty( $record ) )
			{
				$records[] = $record;
			}
		}

		return $records;
	}
}

/**
 * Parse a full CSV string into an array of rows (each row is an array of fields).
 * Handles multi-line quoted fields correctly unlike explode + str_getcsv on individual lines.
 *
 * @param  string $content
 * @param  string $delimiter
 * @param  string $enclosure
 * @return array<int, array<string>>
 */
function str_getcsv_rows( string $content, string $delimiter = ',', string $enclosure = '"' ): array
{
	$stream = fopen( 'php://temp', 'r+' );
	fwrite( $stream, $content );
	rewind( $stream );

	$rows = [];
	while ( ( $row = fgetcsv( $stream, 0, $delimiter, $enclosure ) ) !== false )
	{
		$rows[] = $row;
	}

	fclose( $stream );
	return $rows;
}
