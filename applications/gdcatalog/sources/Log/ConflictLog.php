<?php
/**
 * @brief       GD Master Catalog — Conflict Log Model
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * ActiveRecord for gd_conflict_log. One row per field conflict detected
 * and resolved during import.
 */

namespace IPS\gdcatalog\Log;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}
class ConflictLog extends \IPS\Patterns\ActiveRecord
{
	public static string $databaseTable    = 'gd_conflict_log';
	public static string $databaseColumnId = 'id';
	public static string $databasePrefix   = '';
	protected static array $multitons      = [];

	/**
	 * Write a conflict log entry.
	 *
	 * @param  string      $upc
	 * @param  string      $fieldName
	 * @param  string      $winningSource
	 * @param  string|null $winningValue
	 * @param  string      $losingSource
	 * @param  string|null $losingValue
	 * @param  string      $ruleApplied   One of Product::RULE_* constants
	 * @param  bool        $adminOverride
	 * @return static
	 */
	public static function record(
		string  $upc,
		string  $fieldName,
		string  $winningSource,
		?string $winningValue,
		string  $losingSource,
		?string $losingValue,
		string  $ruleApplied,
		bool    $adminOverride = false
	): static
	{
		$entry = new static;
		$entry->upc            = $upc;
		$entry->field_name     = $fieldName;
		$entry->winning_source = $winningSource;
		$entry->winning_value  = $winningValue;
		$entry->losing_source  = $losingSource;
		$entry->losing_value   = $losingValue;
		$entry->rule_applied   = $ruleApplied;
		$entry->resolved_at    = date( 'Y-m-d H:i:s' );
		$entry->admin_override = $adminOverride ? 1 : 0;
		$entry->save();
		return $entry;
	}
}
