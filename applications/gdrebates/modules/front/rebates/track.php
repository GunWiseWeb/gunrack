<?php
/**
 * @brief       GD Rebates — Member tracking update
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * POST-only endpoint that upserts the logged-in member's tracking row
 * (Section 8.5 User Tracking Block). uq_rebate_member UNIQUE KEY means
 * each member has at most one row per rebate; subsequent updates
 * replace the existing values.
 */

namespace IPS\gdrebates\modules\front\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _track extends \IPS\Dispatcher\Controller
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
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_err_guest' ), '2GDR/30', 403 );
			return;
		}

		$id = (int) ( \IPS\Request::i()->id ?? 0 );
		if ( $id <= 0 )
		{
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_err_rebate_not_found' ), '2GDR/31', 404 );
			return;
		}

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			$status = (string) ( \IPS\Request::i()->status ?? 'saved' );
			if ( !in_array( $status, \IPS\gdrebates\Rebate\Tracking::statuses(), true ) )
			{
				$status = 'saved';
			}

			$submitted = trim( (string) ( \IPS\Request::i()->submitted_date ?? '' ) );
			$received  = trim( (string) ( \IPS\Request::i()->received_date  ?? '' ) );
			$notes     = trim( (string) ( \IPS\Request::i()->notes          ?? '' ) );
			$now       = date( 'Y-m-d H:i:s' );

			$existing = \IPS\gdrebates\Rebate\Tracking::findForMember( $id, (int) $member->member_id );

			$payload = [
				'rebate_id'      => $id,
				'member_id'      => (int) $member->member_id,
				'status'         => $status,
				'submitted_date' => $submitted !== '' ? $submitted : null,
				'received_date'  => $received  !== '' ? $received  : null,
				'notes'          => $notes     !== '' ? $notes     : null,
				'updated_at'     => $now,
			];

			try
			{
				if ( $existing )
				{
					\IPS\Db::i()->update(
						'gd_rebate_tracking', $payload, [ 'id=?', (int) $existing['id'] ]
					);
				}
				else
				{
					$payload['created_at'] = $now;
					\IPS\Db::i()->insert( 'gd_rebate_tracking', $payload );
				}
			}
			catch ( \Exception ) {}

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=view&id=' . $id ),
				'gdr_front_view_track_saved_ok'
			);
			return;
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=view&id=' . $id )
		);
	}
}

class track extends _track {}
