<?php
/**
 * @brief       GD Rebates — Scraper target ActiveRecord
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * One row per manufacturer URL the scraper visits (Section 8.2.1).
 * extraction_config is a JSON blob mapping page elements → rebate
 * fields (Section 8.2.2). is_known=1 means scraped rebates from this
 * target auto-approve to status=active (Section 8.2.3); is_known=0
 * parks them in the admin approval queue.
 */

namespace IPS\gdrebates\Rebate;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Target extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable    = 'gd_scrape_targets';
	public static string  $databaseColumnId = 'id';
	public static string  $databasePrefix   = '';

	/**
	 * Validate an extraction_config JSON blob. Returns the decoded array
	 * on success or null if the string is not valid JSON.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function decodeExtractionConfig( string $json ): ?array
	{
		if ( trim( $json ) === '' )
		{
			return [];
		}
		$d = json_decode( $json, true );
		return is_array( $d ) ? $d : null;
	}

	/**
	 * Fetch all enabled targets. Used by the scheduled scraper task.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function fetchEnabled(): array
	{
		$out = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_scrape_targets', [ 'enabled=?', 1 ], 'manufacturer ASC'
			) as $r )
			{
				if ( is_array( $r ) )
				{
					$out[] = $r;
				}
			}
		}
		catch ( \Exception ) {}
		return $out;
	}
}

class Target extends _Target {}
