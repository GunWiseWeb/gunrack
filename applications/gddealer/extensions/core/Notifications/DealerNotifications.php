<?php
/**
 * GD Dealer Manager — Notifications extension (IPS 5).
 *
 * Extends NotificationsAbstract and exposes notification types via
 * configurationOptions(). Each notification key gets a parse_KEY()
 * method that returns title/url/content (plus optional author) to
 * render the inline notification card.
 */

namespace IPS\gddealer\extensions\core\Notifications;

use IPS\Extensions\NotificationsAbstract;
use IPS\Notification\Inline;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class DealerNotifications extends NotificationsAbstract
{
	public static function configurationOptions( ?Member $member = NULL ): array
	{
		return [
			'new_dealer_review' => [
				'type'              => 'standard',
				'notificationTypes' => [ 'new_dealer_review' ],
				'title'             => 'gddealer_notif_new_review',
				'showTitle'         => true,
				'description'       => 'gddealer_notif_new_review_desc',
				'default'           => [ 'inline', 'email' ],
				'disabled'          => [],
			],
			'review_disputed' => [
				'type'              => 'standard',
				'notificationTypes' => [ 'review_disputed' ],
				'title'             => 'gddealer_notif_review_disputed',
				'showTitle'         => true,
				'description'       => 'gddealer_notif_review_disputed_desc',
				'default'           => [ 'inline', 'email' ],
				'disabled'          => [],
			],
			'dispute_admin_review' => [
				'type'              => 'standard',
				'notificationTypes' => [ 'dispute_admin_review' ],
				'title'             => 'gddealer_notif_dispute_admin_review',
				'showTitle'         => true,
				'description'       => 'gddealer_notif_dispute_admin_review_desc',
				'default'           => [ 'inline', 'email' ],
				'disabled'          => [],
			],
			'dispute_upheld' => [
				'type'              => 'standard',
				'notificationTypes' => [ 'dispute_upheld' ],
				'title'             => 'gddealer_notif_dispute_upheld',
				'showTitle'         => true,
				'description'       => 'gddealer_notif_dispute_upheld_desc',
				'default'           => [ 'inline', 'email' ],
				'disabled'          => [],
			],
			'dispute_dismissed' => [
				'type'              => 'standard',
				'notificationTypes' => [ 'dispute_dismissed' ],
				'title'             => 'gddealer_notif_dispute_dismissed',
				'showTitle'         => true,
				'description'       => 'gddealer_notif_dispute_dismissed_desc',
				'default'           => [ 'inline', 'email' ],
				'disabled'          => [],
			],
			'dealer_responded' => [
				'type'              => 'standard',
				'notificationTypes' => [ 'dealer_responded' ],
				'title'             => 'gddealer_notif_dealer_responded',
				'showTitle'         => true,
				'description'       => 'gddealer_notif_dealer_responded_desc',
				'default'           => [ 'inline' ],
				'disabled'          => [],
			],
		];
	}

	public function parse_new_dealer_review( Inline $notification, bool $htmlEscape = TRUE ): array
	{
		$extra = $notification->extra ?: [];
		return [
			'title'   => ( $extra['reviewer_name'] ?? 'A member' ) . ' left a review on your dealer profile',
			'url'     => \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=reviews' ),
			'content' => 'Visit your dashboard to view the review and post a response.',
			'author'  => NULL,
		];
	}

	public function parse_review_disputed( Inline $notification, bool $htmlEscape = TRUE ): array
	{
		$extra = $notification->extra ?: [];
		$slug  = (string) ( $extra['dealer_slug'] ?? '' );
		$id    = (int) ( $extra['review_id'] ?? 0 );
		$url   = $id > 0
			? \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ) . '&dispute=' . $id )
			: \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ) );
		return [
			'title'   => ( $extra['dealer_name'] ?? 'A dealer' ) . ' has disputed your review',
			'url'     => $url,
			'content' => 'You have 30 days to respond or the dispute will be resolved automatically.',
			'author'  => NULL,
		];
	}

	public function parse_dispute_admin_review( Inline $notification, bool $htmlEscape = TRUE ): array
	{
		$extra = $notification->extra ?: [];
		return [
			'title'   => 'A review dispute is ready for admin review',
			'url'     => \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=disputes', 'admin' ),
			'content' => ( $extra['reviewer_name'] ?? 'A customer' ) . ' responded to a dispute on ' . ( $extra['dealer_name'] ?? 'a dealer' ) . '. Admin action required.',
			'author'  => NULL,
		];
	}

	public function parse_dispute_upheld( Inline $notification, bool $htmlEscape = TRUE ): array
	{
		return [
			'title'   => 'Your review contest was upheld',
			'url'     => \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=reviews' ),
			'content' => 'An admin has ruled in your favor. The review no longer affects your rating average.',
			'author'  => NULL,
		];
	}

	public function parse_dispute_dismissed( Inline $notification, bool $htmlEscape = TRUE ): array
	{
		return [
			'title'   => 'Your review contest was dismissed',
			'url'     => \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard&do=reviews' ),
			'content' => 'An admin has dismissed your contest. The review remains visible.',
			'author'  => NULL,
		];
	}

	public function parse_dealer_responded( Inline $notification, bool $htmlEscape = TRUE ): array
	{
		$extra = $notification->extra ?: [];
		$slug  = (string) ( $extra['dealer_slug'] ?? '' );
		return [
			'title'   => ( $extra['dealer_name'] ?? 'A dealer' ) . ' responded to your review',
			'url'     => \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ) ),
			'content' => 'The dealer has posted a public response to your review. Click to view it.',
			'author'  => NULL,
		];
	}
}
