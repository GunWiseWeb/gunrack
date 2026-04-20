<?php
/**
 * @brief       GD Rebates — Flag a rebate
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Section 8.7 — logged-in users flag a rebate with a reason code. The
 * flag_count denormalised counter on gd_rebates is recomputed from
 * gd_rebate_flags; a flag_count >= gdr_flag_threshold (default 3)
 * moves the rebate into the admin flag queue.
 */

namespace IPS\gdrebates\modules\front\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _flag extends \IPS\Dispatcher\Controller
{
	public function execute(): void
	{
		parent::execute();
	}

	protected function manage(): void
	{
		$member = \IPS\Member::loggedIn();
		$lang   = $member->language();

		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_err_guest' ), '2GDR/40', 403 );
			return;
		}

		$id = (int) ( \IPS\Request::i()->id ?? 0 );
		if ( $id <= 0 )
		{
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_err_rebate_not_found' ), '2GDR/41', 404 );
			return;
		}

		$memberId = (int) $member->member_id;

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			if ( \IPS\gdrebates\Rebate\Flag::alreadyFlagged( $id, $memberId ) )
			{
				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=view&id=' . $id ),
					'gdr_front_view_flag_already'
				);
				return;
			}

			$reason = (string) ( \IPS\Request::i()->reason ?? 'other' );
			if ( !in_array( $reason, \IPS\gdrebates\Rebate\Flag::reasons(), true ) )
			{
				$reason = 'other';
			}
			$notes = trim( (string) ( \IPS\Request::i()->notes ?? '' ) );

			try
			{
				\IPS\Db::i()->insert( 'gd_rebate_flags', [
					'rebate_id'  => $id,
					'member_id'  => $memberId,
					'reason'     => $reason,
					'notes'      => $notes !== '' ? $notes : null,
					'flagged_at' => date( 'Y-m-d H:i:s' ),
					'reviewed'   => 0,
				] );
				\IPS\gdrebates\Rebate\Flag::recountForRebate( $id );
			}
			catch ( \Exception ) {}

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=view&id=' . $id ),
				'gdr_front_view_flag_ok'
			);
			return;
		}

		$data = [
			'rebate_id'  => $id,
			'reasons'    => \IPS\gdrebates\Rebate\Flag::reasons(),
			'submit_url' => (string) \IPS\Http\Url::internal(
				'app=gdrebates&module=rebates&controller=flag&id=' . $id
			),
			'cancel_url' => (string) \IPS\Http\Url::internal(
				'app=gdrebates&module=rebates&controller=view&id=' . $id
			),
			'csrf_key'   => \IPS\Session::i()->csrfKey,
		];

		\IPS\Output::i()->title  = $lang->addToStack( 'gdr_front_flag_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'front' )->flag( $data );
	}
}

class flag extends _flag {}
