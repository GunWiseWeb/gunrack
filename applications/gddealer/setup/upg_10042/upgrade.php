<?php
/**
 * v1.0.42: Admin disputes page polish.
 *
 * - Strip legacy [Admin note: ...] appended text from dispute_reason.
 * - Re-seed disputeQueue template (dropdown filters, collapsible cards).
 */

namespace IPS\gddealer\setup\upg_10042;

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
		/* 1. Strip [Admin note: ...] from dispute_reason on existing rows.
		   These were appended by requestEdit() in v1.0.38–v1.0.41.
		   Notes already live in gd_dealer_dispute_events, so this is
		   safe to remove. */
		try
		{
			$rows = \IPS\Db::i()->select( 'id, dispute_reason', 'gd_dealer_ratings',
				[ 'dispute_reason LIKE ?', '%[Admin note:%' ]
			);
			foreach ( $rows as $row )
			{
				$cleaned = preg_replace( '/\s*\[Admin note:[^\]]*\]/', '', (string) $row['dispute_reason'] );
				$cleaned = trim( $cleaned );
				if ( $cleaned !== (string) $row['dispute_reason'] )
				{
					\IPS\Db::i()->update( 'gd_dealer_ratings', [
						'dispute_reason' => $cleaned,
					], [ 'id=?', (int) $row['id'] ] );
				}
			}
		}
		catch ( \Exception ) {}

		/* 2. Re-seed disputeQueue template (dropdown + collapsible cards). */
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10042.php';

		/* 3. Clear caches. */
		try { unset( \IPS\Data\Store::i()->applications ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }         catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->themes ); }          catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->frontNavigation ); } catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }               catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }               catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
