<?php
/**
 * @brief       GD Price Comparison — State compliance admin
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\modules\admin\pricecompare;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _compliance extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'can_manage_compliance' );
		parent::execute();
	}

	protected function manage(): void
	{
		$rows = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_state_restrictions', null, 'state_code ASC, restriction_type ASC'
			) as $r )
			{
				$rows[] = [
					'id'    => (int) $r['id'],
					'state' => (string) $r['state_code'],
					'type'  => (string) $r['restriction_type'],
					'notes' => (string) ( $r['notes'] ?? '' ),
					'active'=> (int) ( $r['active'] ?? 0 ) === 1,
				];
			}
		}
		catch ( \Exception ) {}

		$data = [
			'rows'  => $rows,
			'count' => count( $rows ),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_compliance_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'admin' )
			->compliance( $data );
	}
}

class compliance extends _compliance {}
