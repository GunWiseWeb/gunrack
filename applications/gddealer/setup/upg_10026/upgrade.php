<?php

namespace IPS\gddealer\setup\upg_10026;

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
		/* Seed new dispute_outcome_reviewer notification default so admin
		   dispute resolutions (uphold/dismiss) push an inline bell
		   notification to the reviewer in addition to the email. */
		try
		{
			\IPS\Db::i()->insert( 'core_notification_defaults', [
				'notification_key' => 'dispute_outcome_reviewer',
				'default'          => 'inline,email',
				'disabled'         => '',
			] );
		}
		catch ( \Exception ) {}

		/* Defensive re-run: ensures the full email template set is present
		   in core_email_templates (no-op if already seeded). */
		try { \IPS\Application::load( 'gddealer' )->installEmailTemplates(); } catch ( \Exception ) {}

		/* Template sync: rewrites the editReview template and patches
		   dealerProfile's review card list with the redesigned layout. */
		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10026.php';

		try { unset( \IPS\Data\Store::i()->themes ); }         catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }     catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }   catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }              catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }              catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
