<?php
namespace IPS\gddealer\extensions\core\MenuCounts;

use IPS\Db;
use IPS\Extensions\MenuCountsAbstract;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class MenuCounts extends MenuCountsAbstract
{
	public function getCounts(): array
	{
		$counts = [];

		try
		{
			$counts['support'] = (int) Db::i()->select( 'COUNT(*)', 'gd_dealer_support_tickets',
				[ "(status = ?) OR (status = ? AND last_reply_role = ?)",
				  'open', 'pending_customer', 'dealer' ]
			)->first();
		}
		catch ( \Exception ) { $counts['support'] = 0; }

		try
		{
			$counts['disputes'] = (int) Db::i()->select( 'COUNT(*)', 'gd_dealer_ratings',
				[ "dispute_status = ? OR (dispute_status = ? AND dispute_deadline IS NOT NULL AND dispute_deadline < ?)",
				  'pending_admin', 'pending_customer', date( 'Y-m-d H:i:s' ) ]
			)->first();
		}
		catch ( \Exception ) { $counts['disputes'] = 0; }

		return $counts;
	}
}
