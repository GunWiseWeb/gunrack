<?php
/**
 * @brief       GD Rebates — Flag queue
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Shows active rebates whose flag_count has reached the configured
 * threshold (Section 8.7, default 3). Admin can mark flags reviewed —
 * this zeroes the counter and marks all outstanding flag rows reviewed,
 * which means the rebate drops out of the queue until fresh flags
 * accumulate.
 */

namespace IPS\gdrebates\modules\admin\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _flags extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'rebates_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$threshold = max( 1, (int) ( \IPS\Settings::i()->gdr_flag_threshold ?? 3 ) );

		$rows = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_rebates',
				[ 'flag_count>=?', $threshold ],
				'flag_count DESC, id DESC', 100
			) as $r )
			{
				if ( !is_array( $r ) )
				{
					continue;
				}
				$id = (int) ( $r['id'] ?? 0 );
				$r['last_flag'] = null;
				try
				{
					$r['last_flag'] = (string) \IPS\Db::i()->select(
						'MAX(flagged_at)', 'gd_rebate_flags', [ 'rebate_id=?', $id ]
					)->first();
				}
				catch ( \Exception ) {}
				$r['clear_url'] = (string) \IPS\Http\Url::internal(
					'app=gdrebates&module=rebates&controller=flags&do=clear&id=' . $id
				)->csrf();
				$rows[] = $r;
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_flags_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'admin' )->flags( [
			'rows' => $rows,
		] );
	}

	protected function clear(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) \IPS\Request::i()->id;
		if ( $id > 0 )
		{
			try
			{
				\IPS\Db::i()->update( 'gd_rebate_flags', [ 'reviewed' => 1 ], [ 'rebate_id=?', $id ] );
				\IPS\gdrebates\Rebate\Flag::recountForRebate( $id );
			}
			catch ( \Exception ) {}
		}
		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=flags' ),
			'gdr_flags_cleared_ok'
		);
	}
}

class flags extends _flags {}
