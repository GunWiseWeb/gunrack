<?php

namespace IPS\gddealer\setup\upg_10008;

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
		// 1.0.8 — Removed all IPS Notification and Messenger API calls
		// that were causing EX0 errors. All notifications now use email
		// only via IPS\Email::buildFromTemplate(). Added dealerResponded,
		// disputeUpheld, disputeOutcome, newDealerReview email templates.
		// Fixed dispute deadline display to use human-readable dates.
		// Updated dealerProfile template to use deadline_formatted.
		require \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10008.php';

		return TRUE;
	}
}

class upgrade extends _upgrade {}
