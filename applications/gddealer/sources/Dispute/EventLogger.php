<?php
namespace IPS\gddealer\Dispute;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _EventLogger
{
	public static function log( int $reviewId, string $eventType, string $actorRole, ?int $actorId = null, ?string $note = null ): void
	{
		try
		{
			\IPS\Db::i()->insert( 'gd_dealer_dispute_events', [
				'review_id'  => $reviewId,
				'event_type' => $eventType,
				'actor_id'   => $actorId,
				'actor_role' => $actorRole,
				'note'       => $note,
				'created_at' => date( 'Y-m-d H:i:s' ),
			] );
		}
		catch ( \Exception ) {}
	}

	public static function getEvents( int $reviewId ): array
	{
		$events = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_dispute_events', [ 'review_id=?', $reviewId ], 'created_at ASC' ) as $e )
			{
				$ts = strtotime( (string) $e['created_at'] );
				$events[] = [
					'event_type' => (string) $e['event_type'],
					'actor_role' => (string) $e['actor_role'],
					'note'       => (string) ( $e['note'] ?? '' ),
					'verb'       => self::verb( (string) $e['event_type'] ),
					'when'       => $ts
						? (string) \IPS\DateTime::ts( $ts )->localeDate() . ' · ' . (string) \IPS\DateTime::ts( $ts )->localeTime()
						: (string) $e['created_at'],
				];
			}
		}
		catch ( \Exception ) {}
		return $events;
	}

	private static function verb( string $eventType ): string
	{
		return match ( $eventType )
		{
			'dispute_opened'       => 'opened the dispute',
			'customer_responded'   => 'submitted a response',
			'admin_edit_requested' => 'requested an update',
			'admin_upheld'         => 'upheld the dispute (review excluded from rating)',
			'admin_dismissed'      => 'dismissed the dispute (review stands)',
			default                => $eventType,
		};
	}
}

class EventLogger extends _EventLogger {}
