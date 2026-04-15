<?php
/**
 * @brief       GD Dealer Manager — CSV Feed Parser
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 */

namespace IPS\gddealer\Feed\Parser;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class CsvParser
{
	/**
	 * Parse a CSV/TSV body into flat record arrays keyed by header row.
	 *
	 * Delimiter is auto-detected: if the first line contains more tabs than
	 * commas we treat it as TSV.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function parse( string $body ): array
	{
		$body = self::normalizeEncoding( $body );
		$firstNewline = strpos( $body, "\n" );
		$firstLine    = $firstNewline === false ? $body : substr( $body, 0, $firstNewline );
		$delimiter    = substr_count( $firstLine, "\t" ) > substr_count( $firstLine, ',' ) ? "\t" : ',';

		$fh = fopen( 'php://temp', 'w+' );
		fwrite( $fh, $body );
		rewind( $fh );

		$headers = fgetcsv( $fh, 0, $delimiter, '"', '\\' );
		if ( $headers === false || $headers === null )
		{
			fclose( $fh );
			return [];
		}
		$headers = array_map( static fn( $h ) => trim( (string) $h ), $headers );

		$out = [];
		while ( ( $row = fgetcsv( $fh, 0, $delimiter, '"', '\\' ) ) !== false )
		{
			if ( $row === [ null ] ) { continue; }
			$assoc = [];
			foreach ( $headers as $i => $h )
			{
				$assoc[ $h ] = $row[ $i ] ?? '';
			}
			$out[] = $assoc;
		}
		fclose( $fh );
		return $out;
	}

	/**
	 * Convert Latin-1 to UTF-8 if the body is not valid UTF-8.
	 */
	protected static function normalizeEncoding( string $body ): string
	{
		if ( mb_check_encoding( $body, 'UTF-8' ) )
		{
			return $body;
		}
		return mb_convert_encoding( $body, 'UTF-8', 'Windows-1252,ISO-8859-1' );
	}
}
