<?php

namespace IPS\gddealer\setup\upg_10020;

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
		/* Seed notification default for updated_dealer_review. */
		try
		{
			\IPS\Db::i()->insert( 'core_notification_defaults', [
				'notification_key' => 'updated_dealer_review',
				'default'          => 'inline,email',
				'disabled'         => '',
			] );
		}
		catch ( \Exception ) {}

		/* Re-seed email templates so updatedDealerReview lands in
		   core_email_templates from the updated data/emails.xml. */
		try
		{
			\IPS\Application::load( 'gddealer' )->installEmailTemplates();
		}
		catch ( \Exception ) {}

		try { unset( \IPS\Data\Store::i()->extensions ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }  catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
