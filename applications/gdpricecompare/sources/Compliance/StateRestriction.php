<?php
/**
 * @brief       GD Price Comparison — State compliance rules
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\Compliance;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class StateRestriction
{
	/**
	 * Determine whether a product restricted-for-a-state. Runs through all
	 * active restrictions for the state, JSON-matches criteria against the
	 * product row, and returns the first match (if any).
	 *
	 * $product is a flat associative array of gd_catalog columns.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function matchProduct( string $stateCode, array $product ): ?array
	{
		$rows = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_state_restrictions',
				[ 'state_code=? AND active=?', strtoupper( $stateCode ), 1 ]
			) as $r )
			{
				$rows[] = $r;
			}
		}
		catch ( \Exception )
		{
			return null;
		}

		foreach ( $rows as $rule )
		{
			$criteria = json_decode( (string) ( $rule['criteria_json'] ?? '{}' ), true );
			if ( !is_array( $criteria ) )
			{
				continue;
			}

			if ( self::criteriaMatches( $criteria, $product ) )
			{
				return $rule;
			}
		}
		return null;
	}

	/**
	 * Every criteria key/value must match the product row. Comparison operators
	 * are expressed in the value — a scalar means equality, a two-element
	 * array with operator means relational check.
	 *
	 * Supported forms:
	 *   { "is_ammo": true }
	 *   { "nfa_item": true }
	 *   { "magazine_capacity": [ ">", 10 ] }
	 *   { "caliber": [ "in", ["223","556"] ] }
	 */
	private static function criteriaMatches( array $criteria, array $product ): bool
	{
		foreach ( $criteria as $field => $expected )
		{
			$actual = $product[$field] ?? null;

			if ( is_array( $expected ) && count( $expected ) === 2 )
			{
				$op  = $expected[0];
				$val = $expected[1];

				switch ( $op )
				{
					case '>':  if ( !( $actual > $val ) )  { return false; } break;
					case '>=': if ( !( $actual >= $val ) ) { return false; } break;
					case '<':  if ( !( $actual < $val ) )  { return false; } break;
					case '<=': if ( !( $actual <= $val ) ) { return false; } break;
					case '!=': if ( $actual == $val )       { return false; } break;
					case 'in':
						if ( !is_array( $val ) || !in_array( $actual, $val, false ) ) { return false; }
						break;
					default:
						if ( $actual != $val ) { return false; }
				}
			}
			else
			{
				if ( $actual != $expected ) { return false; }
			}
		}
		return true;
	}

	public static function count(): int
	{
		try
		{
			return (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_state_restrictions' )->first();
		}
		catch ( \Exception )
		{
			return 0;
		}
	}
}
