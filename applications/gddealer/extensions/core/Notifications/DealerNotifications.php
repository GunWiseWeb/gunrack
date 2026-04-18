<?php

namespace IPS\gddealer\extensions\core\Notifications;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class DealerNotifications
{
	public function getNotifications(): array
	{
		return [
			'new_dealer_review' => [
				'default'          => [ 'inline' ],
				'disabled'         => [],
				'title'            => 'gddealer_notif_new_review_title',
				'text'             => 'gddealer_notif_new_review_text',
				'icon'             => 'fa-solid fa-star',
				'url'              => function( $data )
				{
					return \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=reviews'
					);
				},
				'title_callback'   => function( $data )
				{
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_new_review_title'
					);
				},
				'content_callback' => function( $data )
				{
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_new_review_text',
						FALSE,
						[ 'sprintf' => [ $data['reviewer_name'] ?? 'Someone' ] ]
					);
				},
			],
			'dispute_response' => [
				'default'  => [ 'inline' ],
				'disabled' => [],
				'title'    => 'gddealer_notif_dispute_title',
				'text'     => 'gddealer_notif_dispute_text',
				'icon'     => 'fa-solid fa-scale-balanced',
				'url'      => function( $data )
				{
					return \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=reviews'
					);
				},
			],
			'review_disputed' => [
				'default'          => [ 'inline' ],
				'disabled'         => [],
				'title'            => 'gddealer_notif_review_disputed_title',
				'text'             => 'gddealer_notif_review_disputed_text',
				'icon'             => 'fa-solid fa-flag',
				'url'              => function( $data )
				{
					return isset( $data['respond_url'] )
						? \IPS\Http\Url::external( $data['respond_url'] )
						: \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=profile' );
				},
				'title_callback'   => function( $data )
				{
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_review_disputed_title'
					);
				},
				'content_callback' => function( $data )
				{
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_review_disputed_text',
						FALSE,
						[ 'sprintf' => [ $data['dealer_name'] ?? 'A dealer' ] ]
					);
				},
			],
			'dispute_admin_review' => [
				'default'          => [ 'inline' ],
				'disabled'         => [],
				'title'            => 'gddealer_notif_dispute_admin_review_title',
				'text'             => 'gddealer_notif_dispute_admin_review_text',
				'icon'             => 'fa-solid fa-gavel',
				'url'              => function( $data )
				{
					return isset( $data['admin_url'] )
						? \IPS\Http\Url::external( $data['admin_url'] )
						: \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers&do=disputes', 'admin' );
				},
				'title_callback'   => function( $data )
				{
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_dispute_admin_review_title'
					);
				},
				'content_callback' => function( $data )
				{
					$reviewer = $data['reviewer_name'] ?? 'A customer';
					$dealer   = $data['dealer_name'] ?? 'a dealer';
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_dispute_admin_review_text',
						FALSE,
						[ 'sprintf' => [ $reviewer, $dealer ] ]
					);
				},
			],
			'dispute_upheld' => [
				'default'          => [ 'inline' ],
				'disabled'         => [],
				'title'            => 'gddealer_notif_dispute_upheld_title',
				'text'             => 'gddealer_notif_dispute_upheld_text',
				'icon'             => 'fa-solid fa-check-circle',
				'url'              => function( $data )
				{
					return \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=reviews'
					);
				},
				'title_callback'   => function( $data )
				{
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_dispute_upheld_title'
					);
				},
				'content_callback' => function( $data )
				{
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_dispute_upheld_text'
					);
				},
			],
			'dispute_dismissed' => [
				'default'          => [ 'inline' ],
				'disabled'         => [],
				'title'            => 'gddealer_notif_dispute_dismissed_title',
				'text'             => 'gddealer_notif_dispute_dismissed_text',
				'icon'             => 'fa-solid fa-times-circle',
				'url'              => function( $data )
				{
					return \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=dashboard&do=reviews'
					);
				},
				'title_callback'   => function( $data )
				{
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_dispute_dismissed_title'
					);
				},
				'content_callback' => function( $data )
				{
					return \IPS\Member::loggedIn()->language()->addToStack(
						'gddealer_notif_dispute_dismissed_text'
					);
				},
			],
			'dealer_responded' => [
				'default'          => [ 'inline', 'email' ],
				'disabled'         => [],
				'title'            => 'gddealer_notif_dealer_responded_title',
				'text'             => 'gddealer_notif_dealer_responded_text',
				'icon'             => 'fa-solid fa-reply',
				'url'              => function( $data )
				{
					$slug = (string) ( $data['dealer_slug'] ?? '' );
					return \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug )
					);
				},
				'title_callback'   => function( $data )
				{
					return ( $data['dealer_name'] ?? 'A dealer' ) . ' responded to your review';
				},
				'content_callback' => function( $data )
				{
					return 'The dealer has posted a public response to your review. Click to view it.';
				},
			],
		];
	}
}
