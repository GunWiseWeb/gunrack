<?php
/**
 * @brief       GD Dealer Manager — JSON Feed Parser
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

class JsonParser
{
	/**
	 * Parse a JSON array-of-objects feed into flat record arrays.
	 *
	 * Nested objects are flattened with dot-notation so field maps can refer
	 * to `images.primary` etc.
	 *
	 * @return array<int, array<string, mixed>>
	 * @throws \RuntimeException on parse failure
	 */
	public static function parse( string $body ): array
	{
		$decoded = json_decode( $body, true );
		if ( $decoded === null && json_last_error() !== JSON_ERROR_NONE )
		{
			throw new \RuntimeException( 'JSON parse error: ' . json_last_error_msg() );
		}
		if ( !is_array( $decoded ) )
		{
			return [];
		}

		/* Some feeds wrap in {"products":[...]} or {"data":[...]} — pick the
		 * first array value if the top level is a wrapper object. */
		if ( !array_is_list( $decoded ) )
		{
			foreach ( $decoded as $v )
			{
				if ( is_array( $v ) && array_is_list( $v ) )
				{
					$decoded = $v;
					break;
				}
			}
		}

		$out = [];
		foreach ( $decoded as $item )
		{
			if ( is_array( $item ) )
			{
				$out[] = self::flatten( $item );
			}
		}
		return $out;
	}

	protected static function flatten( array $arr, string $prefix = '' ): array
	{
		$out = [];
		foreach ( $arr as $k => $v )
		{
			$key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
			if ( is_array( $v ) && !array_is_list( $v ) )
			{
				foreach ( self::flatten( $v, $key ) as $kk => $vv )
				{
					$out[ $kk ] = $vv;
				}
			}
			else if ( is_array( $v ) )
			{
				/* list-style — join to string so simple mapping works */
				$out[ $key ] = implode( '|', array_map( 'strval', $v ) );
			}
			else
			{
				$out[ $key ] = is_scalar( $v ) ? (string) $v : '';
			}
		}
		return $out;
	}
}
