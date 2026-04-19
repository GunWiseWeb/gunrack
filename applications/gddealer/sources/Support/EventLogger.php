<?php
/**
 * @brief  GD Dealer Manager — Support ticket audit event logger.
 * @since  19 Apr 2026
 *
 * Mirrors sources/Dispute/EventLogger.php. Rows land in
 * gd_dealer_support_events and are rendered as a chronological
 * timeline on both the dealer and admin ticket view pages.
 */

namespace IPS\gddealer\Support;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _EventLogger
{
	public static function log(
		int $ticketId,
		string $eventType,
		string $actorRole,
		?int $actorId = null,
		?string $note = null,
		?string $oldValue = null,
		?string $newValue = null
	): void
	{
		try
		{
			\IPS\Db::i()->insert( 'gd_dealer_support_events', [
				'ticket_id'  => $ticketId,
				'event_type' => $eventType,
				'actor_id'   => $actorId,
				'actor_role' => $actorRole,
				'note'       => $note,
				'old_value'  => $oldValue,
				'new_value'  => $newValue,
				'created_at' => date( 'Y-m-d H:i:s' ),
			] );
		}
		catch ( \Exception ) {}
	}

	public static function getEvents( int $ticketId ): array
	{
		$events = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_events',
				[ 'ticket_id=?', $ticketId ], 'created_at ASC'
			) as $e )
			{
				$actorName = 'System';
				if ( !empty( $e['actor_id'] ) )
				{
					try
					{
						$actor = \IPS\Member::load( (int) $e['actor_id'] );
						if ( $actor->member_id ) { $actorName = (string) $actor->name; }
					}
					catch ( \Exception ) {}
				}

				$ts = strtotime( (string) $e['created_at'] );

				$events[] = [
					'event_type' => (string) $e['event_type'],
					'actor_role' => (string) $e['actor_role'],
					'actor_name' => $actorName,
					'note'       => (string) ( $e['note'] ?? '' ),
					'old_value'  => (string) ( $e['old_value'] ?? '' ),
					'new_value'  => (string) ( $e['new_value'] ?? '' ),
					'verb'       => self::verb(
						(string) $e['event_type'],
						(string) ( $e['old_value'] ?? '' ),
						(string) ( $e['new_value'] ?? '' )
					),
					'when'       => $ts
						? (string) \IPS\DateTime::ts( $ts )->localeDate()
							. ' · '
							. (string) \IPS\DateTime::ts( $ts )->localeTime()
						: (string) $e['created_at'],
				];
			}
		}
		catch ( \Exception ) {}
		return $events;
	}

	private static function verb( string $eventType, string $oldValue, string $newValue ): string
	{
		return match ( $eventType ) {
			'ticket_opened'    => 'opened the ticket',
			'dealer_replied'   => 'replied',
			'admin_replied'    => 'replied',
			'admin_reopened'   => 'reopened the ticket with a reply',
			'ticket_closed'    => 'closed the ticket',
			'status_changed'   => sprintf(
				'changed status from %s to %s',
				str_replace( '_', ' ', $oldValue ),
				str_replace( '_', ' ', $newValue )
			),
			'priority_changed' => sprintf( 'changed priority from %s to %s', $oldValue, $newValue ),
			'assigned'         => 'assigned the ticket',
			'unassigned'       => 'removed the assignment',
			default            => $eventType,
		};
	}
}

class EventLogger extends _EventLogger {}
