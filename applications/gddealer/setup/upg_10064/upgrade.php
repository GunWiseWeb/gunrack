<?php
/**
 * v1.0.64: Fix front-side dealer reply claimAttachments — was passing
 * $replyId as id1 instead of $ticketId.  Migrates any existing
 * core_attachments_map rows whose id1 still points at a reply row
 * so they point at the ticket instead (matching attachmentLookup).
 */

namespace IPS\gddealer\setup\upg_10064;

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
		/* ── Build lookup maps ─────────────────────────────────── */

		$replyToTicket = [];
		try
		{
			foreach ( \IPS\Db::i()->select( 'id, ticket_id', 'gd_dealer_support_replies' ) as $r )
			{
				$replyToTicket[ (int) $r['id'] ] = (int) $r['ticket_id'];
			}
		}
		catch ( \Exception ) {}

		$validTicketIds = [];
		try
		{
			foreach ( \IPS\Db::i()->select( 'id', 'gd_dealer_support_tickets' ) as $t )
			{
				$validTicketIds[ (int) $t['id'] ] = TRUE;
			}
		}
		catch ( \Exception ) {}

		if ( empty( $replyToTicket ) )
		{
			$this->clearCaches();
			return TRUE;
		}

		/* ── Detect column names ───────────────────────────────── */

		$id1Col = 'id1';
		$id2Col = 'id2';

		try
		{
			$def = \IPS\Db::i()->getTableDefinition( 'core_attachments_map' );
			if ( isset( $def['columns'] ) )
			{
				foreach ( $def['columns'] as $colName => $_ )
				{
					if ( strtolower( $colName ) === 'id1' )
					{
						$id1Col = $colName;
					}
					if ( strtolower( $colName ) === 'id2' )
					{
						$id2Col = $colName;
					}
				}
			}
		}
		catch ( \Exception ) {}

		/* ── Migrate mis-claimed rows ──────────────────────────── */

		try
		{
			$rows = \IPS\Db::i()->select(
				'*',
				'core_attachments_map',
				[ "location_key=? AND {$id2Col}=?", 'gddealer_Responses', 11 ]
			);

			foreach ( $rows as $row )
			{
				$currentId1 = (int) $row[ $id1Col ];

				if ( isset( $validTicketIds[ $currentId1 ] ) )
				{
					continue;
				}

				if ( isset( $replyToTicket[ $currentId1 ] ) )
				{
					\IPS\Db::i()->update(
						'core_attachments_map',
						[ $id1Col => $replyToTicket[ $currentId1 ] ],
						[ "attachment_id=? AND location_key=? AND {$id1Col}=? AND {$id2Col}=?",
							(int) $row['attachment_id'], 'gddealer_Responses', $currentId1, 11 ]
					);
				}
			}
		}
		catch ( \Exception ) {}

		$this->clearCaches();
		return TRUE;
	}

	private function clearCaches(): void
	{
		try { unset( \IPS\Data\Store::i()->extensions ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }         catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->themes ); }          catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }               catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }               catch ( \Exception ) {}
	}
}

class upgrade extends _upgrade {}
