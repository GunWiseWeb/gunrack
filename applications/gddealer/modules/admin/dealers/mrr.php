<?php
/**
 * @brief       GD Dealer Manager — ACP MRR Dashboard
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Section 3.12: total MRR, breakdown by tier, new signups last 30 days,
 * churn last 30 days.
 */

namespace IPS\gddealer\modules\admin\dealers;

use IPS\gddealer\Dealer\Dealer;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _mrr extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		parent::execute();
	}

	protected function manage()
	{
		$byTier = [
			'basic'      => [ 'count' => 0, 'mrr' => 0.0, 'label' => 'Basic' ],
			'pro'        => [ 'count' => 0, 'mrr' => 0.0, 'label' => 'Pro' ],
			'enterprise' => [ 'count' => 0, 'mrr' => 0.0, 'label' => 'Enterprise' ],
			'founding'   => [ 'count' => 0, 'mrr' => 0.0, 'label' => 'Founding' ],
		];
		$totalMrr = 0.0;

		try
		{
			foreach ( \IPS\Db::i()->select( 'subscription_tier, COUNT(*) AS c', 'gd_dealer_feed_config', [ 'suspended=?', 0 ], null, null, 'subscription_tier' ) as $row )
			{
				$tier = (string) $row['subscription_tier'];
				if ( !isset( $byTier[ $tier ] ) ) { continue; }
				$byTier[ $tier ]['count'] = (int) $row['c'];
				$byTier[ $tier ]['mrr']   = (float) ( Dealer::$tierMrr[ $tier ] ?? 0.0 ) * (int) $row['c'];
				$totalMrr += $byTier[ $tier ]['mrr'];
			}
		}
		catch ( \Exception ) {}

		/* Present as formatted scalars ready for the template */
		$tierRows = [];
		foreach ( $byTier as $tier => $info )
		{
			$tierRows[] = [
				'tier'  => $tier,
				'label' => $info['label'],
				'count' => $info['count'],
				'mrr'   => '$' . number_format( $info['mrr'], 2 ),
			];
		}

		$newSignups = 0;
		$churn      = 0;
		try
		{
			$newSignups = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_dealer_feed_config',
				[ 'created_at >= ?', date( 'Y-m-d H:i:s', time() - 30 * 86400 ) ]
			)->first();
		}
		catch ( \Exception ) {}

		try
		{
			$churn = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_dealer_feed_config',
				[ 'suspended=? AND last_run >= ?', 1, date( 'Y-m-d H:i:s', time() - 30 * 86400 ) ]
			)->first();
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_mrr_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'admin' )->mrrDashboard(
			'$' . number_format( $totalMrr, 2 ),
			$tierRows, $newSignups, $churn
		);
	}
}

class mrr extends _mrr {}
