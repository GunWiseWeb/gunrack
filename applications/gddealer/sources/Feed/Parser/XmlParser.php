<?php
/**
 * @brief       GD Dealer Manager — XML Feed Parser
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Safe XML feed parsing. libxml_disable_entity_loader(true) is invoked
 * before every parse per CLAUDE.md security rule #4 and Appendix C.
 */

namespace IPS\gddealer\Feed\Parser;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class XmlParser
{
	/**
	 * Parse a raw XML feed body into a flat list of record arrays.
	 *
	 * @return array<int, array<string, mixed>>
	 * @throws \RuntimeException on parse failure
	 */
	public static function parse( string $body ): array
	{
		/* Disable external entity loading to prevent XXE. PHP 8+ ignores the
		 * call but LIBXML_NONET + LIBXML_NOENT-off behavior is the effective
		 * guard; we set both for defense in depth. */
		if ( function_exists( 'libxml_disable_entity_loader' ) )
		{
			@libxml_disable_entity_loader( true );
		}
		libxml_use_internal_errors( true );

		$sx = simplexml_load_string( $body, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA );
		if ( $sx === false )
		{
			$err = libxml_get_last_error();
			throw new \RuntimeException( 'XML parse error: ' . ( $err ? $err->message : 'unknown' ) );
		}

		/* Find the repeating child element — assume first child of root is
		 * the product element and all siblings are records. */
		$children = $sx->children();
		if ( !count( $children ) )
		{
			return [];
		}

		$records = [];
		foreach ( $children as $node )
		{
			$records[] = self::flatten( $node );
		}
		return $records;
	}

	/**
	 * Flatten a SimpleXMLElement to a flat associative array of string values.
	 * Attributes are included with an `@attr` prefix so field mapping can
	 * target either element or attribute values.
	 */
	protected static function flatten( \SimpleXMLElement $node, string $prefix = '' ): array
	{
		$out = [];

		foreach ( $node->attributes() as $aName => $aVal )
		{
			$out[ ( $prefix === '' ? '' : $prefix . '.' ) . '@' . $aName ] = (string) $aVal;
		}

		foreach ( $node->children() as $k => $child )
		{
			$key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
			if ( count( $child->children() ) || count( $child->attributes() ) )
			{
				foreach ( self::flatten( $child, $key ) as $kk => $vv )
				{
					$out[ $kk ] = $vv;
				}
				if ( trim( (string) $child ) !== '' )
				{
					$out[ $key ] = (string) $child;
				}
			}
			else
			{
				$out[ $key ] = (string) $child;
			}
		}

		return $out;
	}
}
