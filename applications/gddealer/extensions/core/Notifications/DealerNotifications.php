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
		];
	}
}
