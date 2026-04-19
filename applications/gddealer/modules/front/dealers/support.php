<?php
/**
 * @brief       GD Dealer Manager — Dealer Support Tickets (Front-End)
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       19 Apr 2026
 *
 * Dealer-facing support ticket UI. Gated to pro/enterprise/founding tiers.
 * Four actions: manage (list), new (create), view (detail + reply), close.
 */

namespace IPS\gddealer\modules\front\dealers;

use IPS\gddealer\Dealer\Dealer;
use IPS\gddealer\Support\EventLogger;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _support extends \IPS\Dispatcher\Controller
{
	use \IPS\gddealer\Traits\DealerShellTrait;

	public static bool $csrfProtected = TRUE;

	/** Current dealer loaded from the logged-in member (required by trait) */
	protected ?Dealer $dealer = null;

	public function execute(): void
	{
		$member = \IPS\Member::loggedIn();
		if ( !$member->member_id || !Dealer::canAccessSupport( $member ) )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD400/1', 403 );
			return;
		}

		try
		{
			$this->dealer = Dealer::load( (int) $member->member_id );
		}
		catch ( \OutOfRangeException )
		{
			$this->dealer = null;
		}

		if ( $this->dealer === null )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD400/6', 403 );
			return;
		}

		parent::execute();
	}

	protected function manage(): void
	{
		$member   = \IPS\Member::loggedIn();
		$dealerId = (int) $member->member_id;

		$filter = (string) ( \IPS\Request::i()->filter ?? 'open' );
		if ( !in_array( $filter, [ 'open', 'closed', 'all' ], TRUE ) )
		{
			$filter = 'open';
		}

		$whereSql    = 'dealer_id=?';
		$whereParams = [ $dealerId ];
		if ( $filter === 'open' )
		{
			$whereSql     .= ' AND status IN (?, ?, ?)';
			$whereParams[] = 'open';
			$whereParams[] = 'pending_staff';
			$whereParams[] = 'pending_customer';
		}
		elseif ( $filter === 'closed' )
		{
			$whereSql     .= ' AND status IN (?, ?)';
			$whereParams[] = 'resolved';
			$whereParams[] = 'closed';
		}

		$tickets = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_tickets',
				array_merge( [ $whereSql ], $whereParams ),
				'updated_at DESC', [ 0, 50 ]
			) as $t )
			{
				$deptName = '';
				try
				{
					$deptName = (string) \IPS\Db::i()->select( 'name', 'gd_dealer_support_departments',
						[ 'id=?', (int) $t['department_id'] ]
					)->first();
				}
				catch ( \Exception ) {}

				$createdTs = strtotime( (string) $t['created_at'] );
				$updatedTs = strtotime( (string) $t['updated_at'] );

				$tickets[] = [
					'id'                  => (int) $t['id'],
					'subject'             => (string) $t['subject'],
					'department_name'     => $deptName,
					'status'              => (string) $t['status'],
					'status_label'        => self::statusLabel( (string) $t['status'] ),
					'status_bg'           => self::statusBg( (string) $t['status'] ),
					'status_color'        => self::statusColor( (string) $t['status'] ),
					'priority'            => (string) $t['priority'],
					'priority_label'      => ucfirst( (string) $t['priority'] ),
					'priority_bg'         => self::priorityBg( (string) $t['priority'] ),
					'priority_color'      => self::priorityColor( (string) $t['priority'] ),
					'last_reply_role'     => (string) ( $t['last_reply_role'] ?? '' ),
					'created_at_short'    => $createdTs ? (string) \IPS\DateTime::ts( $createdTs )->localeDate() : (string) $t['created_at'],
					'updated_at_relative' => $updatedTs ? (string) \IPS\DateTime::ts( $updatedTs )->relative() : (string) $t['updated_at'],
					'view_url'            => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=support&do=view&id=' . (int) $t['id']
					),
					'needs_attention'     => ( (string) $t['status'] ) === 'pending_customer',
				];
			}
		}
		catch ( \Exception ) {}

		$openCount   = 0;
		$closedCount = 0;
		try
		{
			$openCount = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_support_tickets',
				[ 'dealer_id=? AND status IN (?, ?, ?)', $dealerId, 'open', 'pending_staff', 'pending_customer' ]
			)->first();
		}
		catch ( \Exception ) {}
		try
		{
			$closedCount = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_support_tickets',
				[ 'dealer_id=? AND status IN (?, ?)', $dealerId, 'resolved', 'closed' ]
			)->first();
		}
		catch ( \Exception ) {}

		$baseUrl = \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support' );

		$subNav = [
			'active'       => $filter,
			'open_url'     => (string) $baseUrl->setQueryString( [ 'filter' => 'open' ] ),
			'closed_url'   => (string) $baseUrl->setQueryString( [ 'filter' => 'closed' ] ),
			'all_url'      => (string) $baseUrl->setQueryString( [ 'filter' => 'all' ] ),
			'new_url'      => (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=new' ),
			'open_count'   => $openCount,
			'closed_count' => $closedCount,
		];

		$this->output( 'support', (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )
			->supportList( $tickets, $subNav ) );
	}

	protected function new(): void
	{
		$member   = \IPS\Member::loggedIn();
		$dealerId = (int) $member->member_id;

		$departments = [];
		try
		{
			$tier = Dealer::getTier( $member );
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_departments',
				[ 'enabled=?', 1 ], 'position ASC'
			) as $d )
			{
				$vis = (string) ( $d['visibility'] ?? 'pro' );
				if ( $vis === 'pro' || $vis === $tier || $tier === 'enterprise' || $tier === 'founding' )
				{
					$departments[] = [
						'id'   => (int) $d['id'],
						'name' => (string) $d['name'],
					];
				}
			}
		}
		catch ( \Exception ) {}

		$isEnterprise = Dealer::getTier( $member ) === 'enterprise';

		$bodyEditor = new \IPS\Helpers\Form\Editor(
			'gddealer_support_body',
			'',
			FALSE,
			[
				'app'         => 'gddealer',
				'key'         => 'Responses',
				'autoSaveKey' => 'gddealer-support-new-' . $dealerId,
				'attachIds'   => [ 0, 10 ],
			],
			NULL,
			NULL,
			NULL,
			'editor_support_new_' . $dealerId
		);

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			$subject      = trim( (string) ( \IPS\Request::i()->support_subject ?? '' ) );
			$departmentId = (int) ( \IPS\Request::i()->support_department ?? 0 );
			$priority     = (string) ( \IPS\Request::i()->support_priority ?? 'normal' );
			$bodyRaw      = (string) ( \IPS\Request::i()->gddealer_support_body ?? '' );

			if ( $subject === '' )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal(
					'app=gddealer&module=dealers&controller=support&do=new'
				) );
				return;
			}

			$validPriorities = [ 'low', 'normal', 'high' ];
			if ( $priority === 'urgent' && $isEnterprise )
			{
				$validPriorities[] = 'urgent';
			}
			if ( !in_array( $priority, $validPriorities, TRUE ) )
			{
				$priority = Dealer::defaultTicketPriority( $member );
			}

			$now = date( 'Y-m-d H:i:s' );
			$ticketId = 0;
			try
			{
				$ticketId = (int) \IPS\Db::i()->insert( 'gd_dealer_support_tickets', [
					'department_id' => $departmentId,
					'dealer_id'     => $dealerId,
					'member_id'     => (int) $member->member_id,
					'subject'       => $subject,
					'priority'      => $priority,
					'status'        => 'open',
					'body'          => '',
					'created_at'    => $now,
					'updated_at'    => $now,
				] );
			}
			catch ( \Exception ) {}

			if ( $ticketId > 0 )
			{
				$body = '';
				if ( trim( $bodyRaw ) !== '' )
				{
					try
					{
						$body = \IPS\Text\Parser::parseStatic(
							$bodyRaw,
							[ $ticketId, 10 ],
							\IPS\Member::loggedIn(),
							'gddealer_Responses'
						);
					}
					catch ( \Exception )
					{
						$body = $bodyRaw;
					}
				}

				try
				{
					\IPS\Db::i()->update( 'gd_dealer_support_tickets',
						[ 'body' => $body ],
						[ 'id=?', $ticketId ]
					);
				}
				catch ( \Exception ) {}

				try
				{
					\IPS\File::claimAttachments(
						'gddealer-support-new-' . $dealerId,
						$ticketId,
						10
					);
				}
				catch ( \Exception ) {}

				/* Notify admins — bell + email. Each channel gets its own
				   try/catch so one failure can't suppress the other. */
				$dealerName = '';
				try
				{
					$dealerName = (string) \IPS\Db::i()->select( 'dealer_name', 'gd_dealer_feed_config',
						[ 'dealer_id=?', $dealerId ]
					)->first();
				}
				catch ( \Exception ) {}

				$deptNameNotify = '';
				foreach ( $departments as $dept )
				{
					if ( (int) $dept['id'] === $departmentId )
					{
						$deptNameNotify = (string) $dept['name'];
						break;
					}
				}

				$adminTicketUrl = (string) \IPS\Http\Url::internal(
					'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin'
				);

				/* TODO: make admin group IDs configurable via a setting.
				   For MVP, notify all members in group_id=4 (Administrator). */
				$adminMembers = [];
				try
				{
					foreach ( \IPS\Db::i()->select( 'member_id', 'core_members',
						[ \IPS\Db::i()->in( 'member_group_id', [ 4 ] ) ],
						null, [ 0, 50 ]
					) as $mid )
					{
						$am = \IPS\Member::load( (int) $mid );
						if ( $am->member_id ) { $adminMembers[] = $am; }
					}
				}
				catch ( \Exception ) {}

				foreach ( $adminMembers as $admin )
				{
					try
					{
						$notification = new \IPS\Notification(
							\IPS\Application::load( 'gddealer' ),
							'support_ticket_new',
							$admin,
							[ $admin ],
							[
								'ticket_id'   => $ticketId,
								'subject'     => $subject,
								'dealer_name' => $dealerName,
							]
						);
						$notification->recipients->attach( $admin );
						$notification->send();
					}
					catch ( \Exception ) {}

					try
					{
						\IPS\Email::buildFromTemplate( 'gddealer', 'supportTicketNew', [
							'dealer_name' => $dealerName,
							'subject'     => $subject,
							'priority'    => ucfirst( $priority ),
							'department'  => $deptNameNotify,
							'view_url'    => $adminTicketUrl,
						], \IPS\Email::TYPE_TRANSACTIONAL )->send( $admin );
					}
					catch ( \Exception ) {}
				}

				try
				{
					EventLogger::log(
						$ticketId, 'ticket_opened', 'dealer', $dealerId, null
					);
				}
				catch ( \Exception ) {}
			}

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=support'
			) );
			return;
		}

		$bodyEditorHtml = (string) $bodyEditor;
		$csrfKey        = (string) \IPS\Session::i()->csrfKey;
		$submitUrl      = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support&do=new'
		)->csrf();
		$backUrl        = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support'
		);

		$this->output( 'support', (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )
			->supportNew( $departments, $isEnterprise, $bodyEditorHtml, $csrfKey, $submitUrl, $backUrl ) );
	}

	protected function view(): void
	{
		$member   = \IPS\Member::loggedIn();
		$dealerId = (int) $member->member_id;
		$ticketId = (int) ( \IPS\Request::i()->id ?? 0 );

		if ( $ticketId <= 0 )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD400/2', 404 );
			return;
		}

		$ticket = null;
		try
		{
			$row = \IPS\Db::i()->select( '*', 'gd_dealer_support_tickets',
				[ 'id=? AND dealer_id=?', $ticketId, $dealerId ]
			)->first();
			$ticket = $row;
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD400/3', 404 );
			return;
		}

		$replyEditor = new \IPS\Helpers\Form\Editor(
			'gddealer_support_reply',
			'',
			FALSE,
			[
				'app'         => 'gddealer',
				'key'         => 'Responses',
				'autoSaveKey' => 'gddealer-support-reply-' . $ticketId,
				'attachIds'   => [ 0, 11 ],
			],
			NULL,
			NULL,
			NULL,
			'editor_support_reply_' . $ticketId
		);

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			$replyRaw = (string) ( \IPS\Request::i()->gddealer_support_reply ?? '' );
			if ( trim( $replyRaw ) === '' )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal(
					'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId
				) );
				return;
			}

			$now     = date( 'Y-m-d H:i:s' );
			$replyId = 0;
			try
			{
				$replyId = (int) \IPS\Db::i()->insert( 'gd_dealer_support_replies', [
					'ticket_id'  => $ticketId,
					'member_id'  => (int) $member->member_id,
					'role'       => 'dealer',
					'body'       => '',
					'is_hidden_note' => 0,
					'created_at' => $now,
				] );
			}
			catch ( \Exception ) {}

			if ( $replyId > 0 )
			{
				$body = '';
				if ( trim( $replyRaw ) !== '' )
				{
					try
					{
						$body = \IPS\Text\Parser::parseStatic(
							$replyRaw,
							[ $replyId, 11 ],
							\IPS\Member::loggedIn(),
							'gddealer_Responses'
						);
					}
					catch ( \Exception )
					{
						$body = $replyRaw;
					}
				}

				try
				{
					\IPS\Db::i()->update( 'gd_dealer_support_replies',
						[ 'body' => $body ],
						[ 'id=?', $replyId ]
					);
				}
				catch ( \Exception ) {}

				try
				{
					\IPS\File::claimAttachments(
						'gddealer-support-reply-' . $ticketId,
						$replyId,
						11
					);
				}
				catch ( \Exception ) {}
			}

			$newStatus = (string) $ticket['status'];
			if ( in_array( $newStatus, [ 'pending_customer', 'resolved' ], TRUE ) )
			{
				$newStatus = 'pending_staff';
			}

			try
			{
				\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
					'updated_at'      => $now,
					'last_reply_at'   => $now,
					'last_reply_by'   => (int) $member->member_id,
					'last_reply_role' => 'dealer',
					'status'          => $newStatus,
				], [ 'id=?', $ticketId ] );
			}
			catch ( \Exception ) {}

			/* Notify admins (or the assigned admin) that the dealer replied. */
			$dealerName = '';
			try
			{
				$dealerName = (string) \IPS\Db::i()->select( 'dealer_name', 'gd_dealer_feed_config',
					[ 'dealer_id=?', $dealerId ]
				)->first();
			}
			catch ( \Exception ) {}

			$adminTicketUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin'
			);

			$targetMembers = [];
			if ( !empty( $ticket['assigned_to'] ) )
			{
				try
				{
					$assignee = \IPS\Member::load( (int) $ticket['assigned_to'] );
					if ( $assignee->member_id ) { $targetMembers[] = $assignee; }
				}
				catch ( \Exception ) {}
			}
			if ( empty( $targetMembers ) )
			{
				try
				{
					foreach ( \IPS\Db::i()->select( 'member_id', 'core_members',
						[ \IPS\Db::i()->in( 'member_group_id', [ 4 ] ) ],
						null, [ 0, 50 ]
					) as $mid )
					{
						$am = \IPS\Member::load( (int) $mid );
						if ( $am->member_id ) { $targetMembers[] = $am; }
					}
				}
				catch ( \Exception ) {}
			}

			foreach ( $targetMembers as $admin )
			{
				try
				{
					$notification = new \IPS\Notification(
						\IPS\Application::load( 'gddealer' ),
						'support_reply_to_admin',
						$admin,
						[ $admin ],
						[
							'ticket_id'   => $ticketId,
							'subject'     => (string) $ticket['subject'],
							'dealer_name' => $dealerName,
						]
					);
					$notification->recipients->attach( $admin );
					$notification->send();
				}
				catch ( \Exception ) {}

				try
				{
					\IPS\Email::buildFromTemplate( 'gddealer', 'supportReplyToAdmin', [
						'subject'     => (string) $ticket['subject'],
						'dealer_name' => $dealerName,
						'view_url'    => $adminTicketUrl,
					], \IPS\Email::TYPE_TRANSACTIONAL )->send( $admin );
				}
				catch ( \Exception ) {}
			}

			try
			{
				EventLogger::log(
					$ticketId, 'dealer_replied', 'dealer',
					(int) $member->member_id, null
				);
			}
			catch ( \Exception ) {}

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId
			) );
			return;
		}

		$deptName = '';
		try
		{
			$deptName = (string) \IPS\Db::i()->select( 'name', 'gd_dealer_support_departments',
				[ 'id=?', (int) $ticket['department_id'] ] )->first();
		}
		catch ( \Exception ) {}

		$ticketBody = '';
		if ( ( $ticket['body'] ?? '' ) !== '' )
		{
			try
			{
				$ticketBody = \IPS\Text\Parser::parseStatic(
					(string) $ticket['body'],
					[ $ticketId, 10 ],
					NULL,
					'gddealer_Responses'
				);
			}
			catch ( \Exception )
			{
				$ticketBody = (string) $ticket['body'];
			}
		}

		$replies = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_replies',
				[ 'ticket_id=? AND is_hidden_note=?', $ticketId, 0 ],
				'created_at ASC'
			) as $r )
			{
				$replyBody = '';
				if ( ( $r['body'] ?? '' ) !== '' )
				{
					try
					{
						$replyBody = \IPS\Text\Parser::parseStatic(
							(string) $r['body'],
							[ (int) $r['id'], 11 ],
							NULL,
							'gddealer_Responses'
						);
					}
					catch ( \Exception )
					{
						$replyBody = (string) $r['body'];
					}
				}

				$replyMemberName = 'Staff';
				try
				{
					$rm = \IPS\Member::load( (int) $r['member_id'] );
					if ( $rm->member_id )
					{
						$replyMemberName = (string) $rm->name;
					}
				}
				catch ( \Exception ) {}

				$createdTs = strtotime( (string) $r['created_at'] );
				$replies[] = [
					'id'           => (int) $r['id'],
					'role'         => (string) $r['role'],
					'role_label'   => (string) $r['role'] === 'dealer' ? 'You' : 'Staff',
					'role_bg'      => (string) $r['role'] === 'dealer' ? '#2563eb' : '#16a34a',
					'body'         => $replyBody,
					'member_name'  => $replyMemberName,
					'created_at'   => $createdTs ? (string) \IPS\DateTime::ts( $createdTs )->localeDate() : (string) $r['created_at'],
				];
			}
		}
		catch ( \Exception ) {}

		$createdTs = strtotime( (string) $ticket['created_at'] );
		$updatedTs = strtotime( (string) $ticket['updated_at'] );

		$ticketData = [
			'id'              => (int) $ticket['id'],
			'subject'         => (string) $ticket['subject'],
			'status'          => (string) $ticket['status'],
			'status_label'    => self::statusLabel( (string) $ticket['status'] ),
			'status_bg'       => self::statusBg( (string) $ticket['status'] ),
			'status_color'    => self::statusColor( (string) $ticket['status'] ),
			'priority'        => (string) $ticket['priority'],
			'priority_color'  => self::priorityColor( (string) $ticket['priority'] ),
			'department'      => $deptName,
			'body'            => $ticketBody,
			'created_at'      => $createdTs ? (string) \IPS\DateTime::ts( $createdTs )->localeDate() : (string) $ticket['created_at'],
			'updated_at'      => $updatedTs ? (string) \IPS\DateTime::ts( $updatedTs )->localeDate() : (string) $ticket['updated_at'],
		];

		$replyEditorHtml = (string) $replyEditor;
		$csrfKey         = (string) \IPS\Session::i()->csrfKey;
		$replyUrl        = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId
		)->csrf();
		$closeUrl        = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support&do=close&id=' . $ticketId
		)->csrf();
		$backUrl         = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support'
		);

		$canClose = !in_array( (string) $ticket['status'], [ 'closed' ], TRUE );

		$newTicketUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support&do=new'
		);

		$this->output( 'support', (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )
			->supportView( $ticketData, $replies, $replyEditorHtml, $csrfKey, $replyUrl, $closeUrl, $backUrl, $canClose, EventLogger::getEvents( $ticketId ), $newTicketUrl ) );
	}

	protected function close(): void
	{
		\IPS\Session::i()->csrfCheck();

		$member   = \IPS\Member::loggedIn();
		$dealerId = (int) $member->member_id;
		$ticketId = (int) ( \IPS\Request::i()->id ?? 0 );

		if ( $ticketId <= 0 )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD400/4', 404 );
			return;
		}

		try
		{
			\IPS\Db::i()->select( 'id', 'gd_dealer_support_tickets',
				[ 'id=? AND dealer_id=?', $ticketId, $dealerId ]
			)->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD400/5', 404 );
			return;
		}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
				'status'     => 'closed',
				'updated_at' => date( 'Y-m-d H:i:s' ),
			], [ 'id=? AND dealer_id=?', $ticketId, $dealerId ] );
		}
		catch ( \Exception ) {}

		try
		{
			EventLogger::log(
				$ticketId, 'ticket_closed', 'dealer',
				(int) $member->member_id, null
			);
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId
		) );
	}

	private static function statusLabel( string $s ): string
	{
		return match( $s ) {
			'open'              => 'Open',
			'pending_staff'     => 'Awaiting staff',
			'pending_customer'  => 'Awaiting your reply',
			'resolved'          => 'Resolved',
			'closed'            => 'Closed',
			default             => ucfirst( str_replace( '_', ' ', $s ) ),
		};
	}

	private static function statusBg( string $s ): string
	{
		return match( $s ) {
			'pending_staff'     => '#dbeafe',
			'open'              => '#dbeafe',
			'pending_customer'  => '#fef3c7',
			'resolved'          => '#dcfce7',
			'closed'            => '#f1f5f9',
			default             => '#f3f4f6',
		};
	}

	private static function statusColor( string $s ): string
	{
		return match( $s ) {
			'pending_staff'     => '#1e40af',
			'open'              => '#1e40af',
			'pending_customer'  => '#854d0e',
			'resolved'          => '#166534',
			'closed'            => '#334155',
			default             => '#374151',
		};
	}

	private static function priorityBg( string $p ): string
	{
		return match( $p ) {
			'urgent' => '#fee2e2',
			'high'   => '#fef3c7',
			'normal' => '#f3f4f6',
			'low'    => '#f9fafb',
			default  => '#f3f4f6',
		};
	}

	private static function priorityColor( string $p ): string
	{
		return match( $p ) {
			'urgent' => '#991b1b',
			'high'   => '#854d0e',
			'normal' => '#374151',
			'low'    => '#6b7280',
			default  => '#374151',
		};
	}
}

class support extends _support {}
