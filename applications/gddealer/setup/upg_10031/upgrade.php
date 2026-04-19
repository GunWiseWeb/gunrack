<?php
/**
 * v1.0.31 step 1: widen six text columns from TEXT (64KB) to MEDIUMTEXT
 * (16MB) so the IPS rich-text editor can store HTML with inline images
 * / embedded media without truncation. The column widen is done via
 * queries.json (changeColumn with silence_errors) so IPS's installer
 * runs it before this PHP step. Here we just clear caches so the new
 * editor config and template changes pick up cleanly.
 */

namespace IPS\gddealer\setup\upg_10031;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _upgrade
{
	public function step1(): bool
	{
		/* Overwrite every template whose controller now pipes rich-text
		   through the IPS editor so existing installs pick up the |raw
		   + editor-HTML substitutions. */
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10031.php';

		try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->themes ); }       catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}

	/**
	 * step2: Migrate existing plaintext content to basic HTML paragraphs.
	 * Idempotent — rows already containing HTML (starting with '<') are skipped.
	 */
	public function step2(): bool
	{
		$columns = array(
			'review_body',
			'dealer_response',
			'dispute_reason',
			'dispute_evidence',
			'customer_response',
			'customer_evidence',
		);

		foreach ( $columns as $col )
		{
			try
			{
				\IPS\Db::i()->query(
					"UPDATE `" . \IPS\Db::i()->prefix . "gd_dealer_ratings`
					 SET `{$col}` = CONCAT( '<p>', REPLACE( REPLACE( `{$col}`, CHAR(13), '' ), CHAR(10), '</p><p>' ), '</p>' )
					 WHERE `{$col}` IS NOT NULL
					   AND `{$col}` != ''
					   AND `{$col}` NOT LIKE '<%'"
				);
			}
			catch ( \Exception ) {}
		}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
