<?php

namespace IPS\gddealer\setup\upg_10012;

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
		/* Seed notification defaults for gddealer notification types.
		   Safe on re-run — duplicate notification_key inserts are
		   swallowed per-row so one conflict doesn't abort the whole step. */
		$notificationDefaults = [
			'new_dealer_review'    => [ 'default' => 'inline,email', 'disabled' => '' ],
			'review_disputed'      => [ 'default' => 'inline,email', 'disabled' => '' ],
			'dealer_responded'     => [ 'default' => 'inline',       'disabled' => '' ],
			'dispute_admin_review' => [ 'default' => 'inline,email', 'disabled' => '' ],
			'dispute_upheld'       => [ 'default' => 'inline,email', 'disabled' => '' ],
			'dispute_dismissed'    => [ 'default' => 'inline,email', 'disabled' => '' ],
		];

		foreach ( $notificationDefaults as $key => $data )
		{
			try
			{
				\IPS\Db::i()->insert( 'core_notification_defaults', [
					'notification_key' => $key,
					'default'          => $data['default'],
					'disabled'         => $data['disabled'],
				] );
			}
			catch ( \Exception ) {}
		}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
