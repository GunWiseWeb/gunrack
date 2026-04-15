<?php
/**
 * @brief       GD Master Catalog — XML Feed Parser
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Parses XML distributor feeds into arrays of key-value records.
 * SECURITY: libxml_disable_entity_loader(true) before every parse (Appendix C).
 */

namespace IPS\gdcatalog\Feed\Parser;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}
class XmlParser
{
	/**
	 * Parse an XML string into an array of associative records.
	 *
	 * The parser auto-detects the repeating element — it uses the children
	 * of the root element as individual product records.
	 *
	 * @param  string $xmlContent  Raw XML string
	 * @return array<int, array<string, string>>
	 * @throws \RuntimeException  On parse failure
	 */
	public static function parse( string $xmlContent ): array
	{
		/* SECURITY: Disable external entity loading before every XML parse (Appendix C) */
		if ( \function_exists( 'libxml_disable_entity_loader' ) )
		{
			libxml_disable_entity_loader( true );
		}
		libxml_use_internal_errors( true );

		$xml = simplexml_load_string( $xmlContent, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_NONET );

		if ( $xml === false )
		{
			$errors = libxml_get_errors();
			libxml_clear_errors();
			$msg = 'XML parse failed';
			if ( !empty( $errors ) )
			{
				$msg .= ': ' . $errors[0]->message;
			}
			throw new \RuntimeException( $msg );
		}

		$records = [];
		foreach ( $xml->children() as $node )
		{
			$record = static::nodeToArray( $node );
			if ( !empty( $record ) )
			{
				$records[] = $record;
			}
		}

		return $records;
	}

	/**
	 * Convert a SimpleXMLElement node to a flat associative array.
	 * Nested elements are flattened with underscore separation.
	 *
	 * @param  \SimpleXMLElement $node
	 * @param  string            $prefix
	 * @return array<string, string>
	 */
	protected static function nodeToArray( \SimpleXMLElement $node, string $prefix = '' ): array
	{
		$result = [];

		/* Attributes */
		foreach ( $node->attributes() as $attrName => $attrVal )
		{
			$key = $prefix !== '' ? $prefix . '_' . $attrName : $attrName;
			$result[$key] = (string) $attrVal;
		}

		/* Child elements */
		foreach ( $node->children() as $childName => $child )
		{
			$key = $prefix !== '' ? $prefix . '_' . $childName : $childName;

			if ( $child->count() > 0 )
			{
				/* Nested element — recurse */
				$result = array_merge( $result, static::nodeToArray( $child, $key ) );
			}
			else
			{
				$result[$key] = (string) $child;
			}
		}

		/* If node has no children and no attributes, use its text content */
		if ( empty( $result ) && $prefix !== '' )
		{
			$result[$prefix] = (string) $node;
		}

		return $result;
	}
}
