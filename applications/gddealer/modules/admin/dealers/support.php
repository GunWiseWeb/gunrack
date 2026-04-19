<?php
/**
 * @brief       GD Dealer Manager — ACP Support Tickets Controller
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       19 Apr 2026
 *
 * Admin-side support ticket queue, detail view, reply, and management.
 * Gated by dealer_manage permission. Enterprise tickets sort first.
 */

namespace IPS\gddealer\modules\admin\dealers;

use IPS\gddealer\Attachment\Helper as AttachHelper;
use IPS\gddealer\Support\EventLogger;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _support extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		parent::execute();
	}

	protected function manage()
	{
		$this->tickets();
	}

	protected function tickets(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );

		$statusFilter     = (string) ( \IPS\Request::i()->status ?? 'active' );
		$priorityFilter   = (string) ( \IPS\Request::i()->priority ?? 'all' );
		$departmentFilter = (int) ( \IPS\Request::i()->department ?? 0 );

		$validStatuses = [ 'active', 'open', 'pending_staff', 'pending_customer', 'resolved', 'closed', 'all' ];
		if ( !in_array( $statusFilter, $validStatuses, true ) ) { $statusFilter = 'active'; }

		$validPriorities = [ 'all', 'low', 'normal', 'high', 'urgent' ];
		if ( !in_array( $priorityFilter, $validPriorities, true ) ) { $priorityFilter = 'all'; }

		$whereSql    = '1=1';
		$whereParams = [];

		switch ( $statusFilter )
		{
			case 'active':
				$whereSql     .= ' AND t.status IN (?, ?, ?)';
				$whereParams[] = 'open';
				$whereParams[] = 'pending_staff';
				$whereParams[] = 'pending_customer';
				break;
			case 'all':
				break;
			default:
				$whereSql     .= ' AND t.status=?';
				$whereParams[] = $statusFilter;
		}

		if ( $priorityFilter !== 'all' )
		{
			$whereSql     .= ' AND t.priority=?';
			$whereParams[] = $priorityFilter;
		}

		if ( $departmentFilter > 0 )
		{
			$whereSql     .= ' AND t.department_id=?';
			$whereParams[] = $departmentFilter;
		}

		$orderBy = "
			CASE WHEN dfc.subscription_tier = 'enterprise' THEN 0 ELSE 1 END ASC,
			CASE t.priority
				WHEN 'urgent' THEN 4
				WHEN 'high' THEN 3
				WHEN 'normal' THEN 2
				WHEN 'low' THEN 1
				ELSE 0
			END DESC,
			CASE WHEN t.status = 'pending_staff' THEN 0 ELSE 1 END ASC,
			t.updated_at DESC
		";

		$whereArg = array_merge( [ $whereSql ], $whereParams );

		$rows = [];
		try
		{
			$select = \IPS\Db::i()->select(
				't.*, dfc.subscription_tier AS dealer_tier, dfc.dealer_name',
				[ 'gd_dealer_support_tickets', 't' ],
				$whereArg,
				$orderBy,
				[ 0, 200 ]
			);
			$select->join(
				[ 'gd_dealer_feed_config', 'dfc' ],
				't.dealer_id = dfc.dealer_id'
			);

			foreach ( $select as $t )
			{
				$submitterName = 'Unknown';
				try
				{
					$submitter = \IPS\Member::load( (int) $t['member_id'] );
					if ( $submitter->member_id ) { $submitterName = (string) $submitter->name; }
				}
				catch ( \Exception ) {}

				$assigneeName = '';
				if ( !empty( $t['assigned_to'] ) )
				{
					try
					{
						$assignee = \IPS\Member::load( (int) $t['assigned_to'] );
						if ( $assignee->member_id ) { $assigneeName = (string) $assignee->name; }
					}
					catch ( \Exception ) {}
				}

				$deptName = '';
				try
				{
					$deptName = (string) \IPS\Db::i()->select( 'name', 'gd_dealer_support_departments',
						[ 'id=?', (int) $t['department_id'] ] )->first();
				}
				catch ( \Exception ) {}

				$updatedTs = strtotime( (string) $t['updated_at'] );

				$rows[] = [
					'id'                => (int) $t['id'],
					'subject'           => (string) $t['subject'],
					'department_name'   => $deptName,
					'priority'          => (string) $t['priority'],
					'priority_label'    => ucfirst( (string) $t['priority'] ),
					'priority_bg'       => self::priorityBg( (string) $t['priority'] ),
					'priority_color'    => self::priorityColor( (string) $t['priority'] ),
					'status'            => (string) $t['status'],
					'status_label'      => self::statusLabel( (string) $t['status'] ),
					'status_bg'         => self::statusBg( (string) $t['status'] ),
					'status_color'      => self::statusColor( (string) $t['status'] ),
					'submitter_name'    => $submitterName,
					'dealer_name'       => (string) ( $t['dealer_name'] ?? '' ),
					'dealer_tier'       => (string) ( $t['dealer_tier'] ?? '' ),
					'is_enterprise'     => ( $t['dealer_tier'] ?? '' ) === 'enterprise',
					'assignee_name'     => $assigneeName,
					'updated_at_short'  => $updatedTs
						? (string) \IPS\DateTime::ts( $updatedTs )->localeDate() . ' ' . date( 'H:i', $updatedTs )
						: (string) $t['updated_at'],
					'last_reply_role'   => (string) ( $t['last_reply_role'] ?? '' ),
					'needs_attention'   => ( $t['status'] ?? '' ) === 'pending_staff',
					'view_url'          => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=support&do=view&id=' . (int) $t['id'], 'admin'
					),
				];
			}
		}
		catch ( \Exception ) {}

		$counts = $this->computeCounts( $priorityFilter, $departmentFilter );

		$departments = [ 0 => 'All departments' ];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_departments', null, 'position ASC' ) as $d )
			{
				$departments[ (int) $d['id'] ] = (string) $d['name'];
			}
		}
		catch ( \Exception ) {}

		$baseUrl = \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support&do=tickets', 'admin'
		);

		$statusOptions = [];
		foreach ( $validStatuses as $s )
		{
			$statusOptions[ $s ] = (string) $baseUrl->setQueryString( [
				'status'     => $s,
				'priority'   => $priorityFilter,
				'department' => $departmentFilter,
			] );
		}

		$priorityOptions = [];
		foreach ( $validPriorities as $p )
		{
			$priorityOptions[ $p ] = (string) $baseUrl->setQueryString( [
				'status'     => $statusFilter,
				'priority'   => $p,
				'department' => $departmentFilter,
			] );
		}

		$departmentOptions = [];
		foreach ( $departments as $id => $name )
		{
			$departmentOptions[ $id ] = (string) $baseUrl->setQueryString( [
				'status'     => $statusFilter,
				'priority'   => $priorityFilter,
				'department' => $id,
			] );
		}

		\IPS\Output::i()->title  = 'Support Tickets';
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers' )->supportTickets(
			$rows, $statusFilter, $priorityFilter, $departmentFilter, $counts,
			$statusOptions, $priorityOptions, $departmentOptions, $departments
		);
	}

	protected function computeCounts( string $priorityFilter, int $departmentFilter ): array
	{
		$counts    = [];
		$extraWhere  = '';
		$extraParams = [];

		if ( $priorityFilter !== 'all' )
		{
			$extraWhere   .= ' AND priority=?';
			$extraParams[] = $priorityFilter;
		}
		if ( $departmentFilter > 0 )
		{
			$extraWhere   .= ' AND department_id=?';
			$extraParams[] = $departmentFilter;
		}

		foreach ( [ 'active', 'open', 'pending_staff', 'pending_customer', 'resolved', 'closed', 'all' ] as $s )
		{
			try
			{
				if ( $s === 'active' )
				{
					$whereArg = array_merge(
						[ 'status IN (?, ?, ?)' . $extraWhere, 'open', 'pending_staff', 'pending_customer' ],
						$extraParams
					);
				}
				elseif ( $s === 'all' )
				{
					$whereArg = $extraWhere
						? array_merge( [ '1=1' . $extraWhere ], $extraParams )
						: null;
				}
				else
				{
					$whereArg = array_merge( [ 'status=?' . $extraWhere, $s ], $extraParams );
				}
				$counts[ $s ] = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_support_tickets', $whereArg )->first();
			}
			catch ( \Exception ) { $counts[ $s ] = 0; }
		}

		return $counts;
	}

	protected function view(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );

		$ticketId = (int) ( \IPS\Request::i()->id ?? 0 );
		if ( $ticketId <= 0 )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=tickets', 'admin' )
			);
			return;
		}

		$replyEditor = new \IPS\Helpers\Form\Editor(
			'gddealer_support_admin_reply',
			'',
			FALSE,
			[
				'app'         => 'gddealer',
				'key'         => 'Responses',
				'autoSaveKey' => 'gddealer-support-admin-reply-' . $ticketId,
				'attachIds'   => [ $ticketId, 11 ],
			],
			NULL, NULL, NULL,
			'editor_support_admin_reply_' . $ticketId
		);

		$noteEditor = new \IPS\Helpers\Form\Editor(
			'gddealer_support_admin_note',
			'',
			FALSE,
			[
				'app'         => 'gddealer',
				'key'         => 'Responses',
				'autoSaveKey' => 'gddealer-support-admin-note-' . $ticketId,
				'attachIds'   => [ $ticketId, 12 ],
			],
			NULL, NULL, NULL,
			'editor_support_admin_note_' . $ticketId
		);

		$ticket = null;
		try
		{
			$ticket = \IPS\Db::i()->select( '*', 'gd_dealer_support_tickets', [ 'id=?', $ticketId ] )->first();
		}
		catch ( \Exception ) {}

		if ( !$ticket )
		{
			\IPS\Output::i()->error( 'Ticket not found.', '2GDD500/1', 404 );
			return;
		}

		$submitterName  = 'Unknown';
		$submitterEmail = '';
		try
		{
			$submitter = \IPS\Member::load( (int) $ticket['member_id'] );
			if ( $submitter->member_id )
			{
				$submitterName  = (string) $submitter->name;
				$submitterEmail = (string) $submitter->email;
			}
		}
		catch ( \Exception ) {}

		$dealer = null;
		try
		{
			$dealer = \IPS\Db::i()->select( '*', 'gd_dealer_feed_config',
				[ 'dealer_id=?', (int) $ticket['dealer_id'] ] )->first();
		}
		catch ( \Exception ) {}

		$deptName = '';
		try
		{
			$deptName = (string) \IPS\Db::i()->select( 'name', 'gd_dealer_support_departments',
				[ 'id=?', (int) $ticket['department_id'] ] )->first();
		}
		catch ( \Exception ) {}

		$replies = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_replies',
				[ 'ticket_id=?', $ticketId ], 'created_at ASC'
			) as $r )
			{
				$authorName = 'Unknown';
				try
				{
					$author = \IPS\Member::load( (int) $r['member_id'] );
					if ( $author->member_id ) { $authorName = (string) $author->name; }
				}
				catch ( \Exception ) {}

				$parsedBody = '';
				try
				{
					$parsedBody = \IPS\Text\Parser::parseStatic(
						(string) $r['body'], [ (int) $r['id'], 11 ], null, 'gddealer_Responses'
					);
				}
				catch ( \Exception ) { $parsedBody = (string) $r['body']; }

				$replyTs = strtotime( (string) $r['created_at'] );

				$isNote = (bool) $r['is_hidden_note'];
				$replies[] = [
					'id'             => (int) $r['id'],
					'author_name'    => $authorName,
					'role'           => (string) $r['role'],
					'role_label'     => $isNote
						? 'Staff note'
						: ( $r['role'] === 'admin' ? 'Staff' : 'Dealer' ),
					'role_bg'        => $isNote
						? '#fef3c7'
						: ( $r['role'] === 'admin' ? '#dbeafe' : '#f3f4f6' ),
					'role_color'     => $isNote
						? '#854d0e'
						: ( $r['role'] === 'admin' ? '#1e40af' : '#374151' ),
					'body'           => $parsedBody,
					'created_at'     => $replyTs
						? (string) \IPS\DateTime::ts( $replyTs )->localeDate() . ' ' . date( 'H:i', $replyTs )
						: (string) $r['created_at'],
					'is_hidden_note' => $isNote,
				];
			}
		}
		catch ( \Exception ) {}

		$parsedTicketBody = '';
		try
		{
			$parsedTicketBody = \IPS\Text\Parser::parseStatic(
				(string) $ticket['body'], [ $ticketId, 10 ], null, 'gddealer_Responses'
			);
		}
		catch ( \Exception ) { $parsedTicketBody = (string) $ticket['body']; }

		$ticketAttachments = AttachHelper::getAttachments( $ticketId, 10 );

		$baseQS = [ 'id' => $ticketId ];
		$replyUrl          = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=reply', 'admin' )
			->setQueryString( $baseQS )->csrf();
		$addNoteUrl        = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=addNote', 'admin' )
			->setQueryString( $baseQS )->csrf();
		$updateStatusUrl   = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=updateStatus', 'admin' )
			->setQueryString( $baseQS )->csrf();
		$updatePriorityUrl = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=updatePriority', 'admin' )
			->setQueryString( $baseQS )->csrf();
		$assignUrl         = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=assign', 'admin' )
			->setQueryString( $baseQS )->csrf();
		$deleteUrl         = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=delete', 'admin' )
			->setQueryString( $baseQS )->csrf();
		$backUrl           = (string) \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=tickets', 'admin' );

		$replyEditorHtml = (string) $replyEditor;
		$noteEditorHtml  = (string) $noteEditor;

		$createdTs = strtotime( (string) $ticket['created_at'] );
		$assigneeName = '';
		if ( !empty( $ticket['assigned_to'] ) )
		{
			try
			{
				$a = \IPS\Member::load( (int) $ticket['assigned_to'] );
				if ( $a->member_id ) { $assigneeName = (string) $a->name; }
			}
			catch ( \Exception ) {}
		}

		$ticketDisplay = [
			'id'              => $ticketId,
			'subject'         => (string) $ticket['subject'],
			'status'          => (string) $ticket['status'],
			'status_label'    => self::statusLabel( (string) $ticket['status'] ),
			'status_bg'       => self::statusBg( (string) $ticket['status'] ),
			'status_color'    => self::statusColor( (string) $ticket['status'] ),
			'priority'        => (string) $ticket['priority'],
			'priority_label'  => ucfirst( (string) $ticket['priority'] ),
			'priority_bg'     => self::priorityBg( (string) $ticket['priority'] ),
			'priority_color'  => self::priorityColor( (string) $ticket['priority'] ),
			'created_at'      => $createdTs
				? (string) \IPS\DateTime::ts( $createdTs )->localeDate() . ' ' . date( 'H:i', $createdTs )
				: (string) $ticket['created_at'],
			'submitter_name'  => $submitterName,
			'submitter_email' => $submitterEmail,
			'submitter_id'    => (int) $ticket['member_id'],
			'dealer_name'     => (string) ( $dealer['dealer_name'] ?? '' ),
			'dealer_tier'     => (string) ( $dealer['subscription_tier'] ?? '' ),
			'department_name' => $deptName,
			'assignee_name'   => $assigneeName,
			'assignee_id'     => (int) ( $ticket['assigned_to'] ?? 0 ),
			'can_reply'       => true,
		];

		$events = EventLogger::getEvents( $ticketId );

		$stockReplies = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_stock_replies',
				[ 'enabled=? AND (department_id IS NULL OR department_id=?)', 1, (int) $ticket['department_id'] ],
				'position ASC'
			) as $sr )
			{
				$stockReplies[] = [
					'id'    => (int) $sr['id'],
					'title' => (string) $sr['title'],
					'body'  => (string) $sr['body'],
				];
			}
		}
		catch ( \Exception ) {}

		$stockActions = [];
		$applyActionBaseUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support&do=applyStockAction', 'admin'
		)->setQueryString( $baseQS )->csrf();
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_stock_actions',
				[ 'enabled=? AND (department_id IS NULL OR department_id=?)', 1, (int) $ticket['department_id'] ],
				'position ASC'
			) as $sa )
			{
				$stockActions[] = [
					'id'    => (int) $sa['id'],
					'title' => (string) $sa['title'],
					'url'   => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=support&do=applyStockAction', 'admin'
					)->setQueryString( [ 'id' => $ticketId, 'action_id' => (int) $sa['id'] ] )->csrf(),
				];
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title  = 'Ticket #' . $ticketId . ' — ' . $ticketDisplay['subject'];
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers' )->supportTicketView(
			$ticketDisplay, $parsedTicketBody, $ticketAttachments, $replies, $replyEditorHtml,
			$replyUrl, $updateStatusUrl, $updatePriorityUrl, $assignUrl, $deleteUrl, $backUrl,
			$events, $noteEditorHtml, $addNoteUrl, $stockReplies, $stockActions
		);
	}

	protected function reply(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$ticketId = (int) ( \IPS\Request::i()->id ?? 0 );
		$me       = \IPS\Member::loggedIn();

		$ticket = null;
		try
		{
			$ticket = \IPS\Db::i()->select( '*', 'gd_dealer_support_tickets', [ 'id=?', $ticketId ] )->first();
		}
		catch ( \Exception ) {}

		if ( !$ticket )
		{
			\IPS\Output::i()->error( 'Ticket not found.', '2GDD500/2', 404 );
			return;
		}

		new \IPS\Helpers\Form\Editor(
			'gddealer_support_admin_reply',
			'',
			FALSE,
			[
				'app'         => 'gddealer',
				'key'         => 'Responses',
				'autoSaveKey' => 'gddealer-support-admin-reply-' . $ticketId,
				'attachIds'   => [ $ticketId, 11 ],
			],
			NULL, NULL, NULL,
			'editor_support_admin_reply_' . $ticketId
		);

		$replyRaw = (string) ( \IPS\Request::i()->gddealer_support_admin_reply ?? '' );
		if ( trim( $replyRaw ) === '' )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' )
			);
			return;
		}

		$body = '';
		try
		{
			$body = \IPS\Text\Parser::parseStatic( $replyRaw, [ $ticketId, 11 ], $me, 'gddealer_Responses' );
		}
		catch ( \Exception ) { $body = $replyRaw; }

		$now = date( 'Y-m-d H:i:s' );

		$replyId = 0;
		try
		{
			$replyId = (int) \IPS\Db::i()->insert( 'gd_dealer_support_replies', [
				'ticket_id'      => $ticketId,
				'member_id'      => (int) $me->member_id,
				'role'           => 'admin',
				'body'           => $body,
				'is_hidden_note' => 0,
				'created_at'     => $now,
			] );
		}
		catch ( \Exception ) {}

		$currentStatus = (string) $ticket['status'];
		$wasReopened   = in_array( $currentStatus, [ 'closed', 'resolved' ], true );
		$newStatus     = 'pending_customer';

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
				'status'          => $newStatus,
				'updated_at'      => $now,
				'last_reply_at'   => $now,
				'last_reply_by'   => (int) $me->member_id,
				'last_reply_role' => 'admin',
			], [ 'id=?', $ticketId ] );
		}
		catch ( \Exception ) {}

		try
		{
			\IPS\File::claimAttachments(
				'gddealer-support-admin-reply-' . $ticketId,
				$ticketId,
				11
			);
		}
		catch ( \Exception ) {}

		/* Notify the dealer — bell + email + PM, each in its own try/catch so
		   one channel can't suppress another (landmine #25). */
		$dealer  = \IPS\Member::load( (int) $ticket['member_id'] );
		$subject = (string) $ticket['subject'];
		$ticketUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId
		);

		if ( $dealer && $dealer->member_id )
		{
			try
			{
				$notification = new \IPS\Notification(
					\IPS\Application::load( 'gddealer' ),
					'support_reply_to_dealer',
					$dealer,
					[ $dealer ],
					[
						'ticket_id' => $ticketId,
						'subject'   => $subject,
					]
				);
				$notification->recipients->attach( $dealer );
				$notification->send();
			}
			catch ( \Exception ) {}

			try
			{
				\IPS\Email::buildFromTemplate( 'gddealer', 'supportReplyToDealer', [
					'name'     => (string) $dealer->name,
					'subject'  => $subject,
					'view_url' => $ticketUrl,
				], \IPS\Email::TYPE_TRANSACTIONAL )->send( $dealer );
			}
			catch ( \Exception ) {}

			try
			{
				$sender = $me;
				if ( \IPS\core\Messenger\Conversation::memberCanReceiveNewMessage( $dealer, $sender ) )
				{
					$pmBody = "Staff have replied to your support ticket: " . $subject . "\n\n"
						. "View the reply: " . $ticketUrl;
					$conversation = \IPS\core\Messenger\Conversation::createItem(
						$sender, \IPS\Request::i()->ipAddress(), \IPS\DateTime::create()
					);
					$conversation->title    = 'Re: ' . $subject;
					$conversation->to_count = 1;
					$conversation->save();

					$commentClass = $conversation::$commentClass;
					$post = $commentClass::create(
						$conversation, $pmBody, TRUE, NULL, NULL, $sender, \IPS\DateTime::create()
					);
					$conversation->first_msg_id = $post->id;
					$conversation->save();
					$conversation->authorize( [ $sender->member_id, $dealer->member_id ] );
					$post->sendNotifications();
				}
			}
			catch ( \Exception ) {}
		}

		try
		{
			EventLogger::log(
				$ticketId,
				$wasReopened ? 'admin_reopened' : 'admin_replied',
				'admin',
				(int) $me->member_id,
				null
			);
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' ),
			'Reply posted.'
		);
	}

	protected function addNote(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$ticketId = (int) ( \IPS\Request::i()->id ?? 0 );
		$me       = \IPS\Member::loggedIn();

		$ticket = null;
		try
		{
			$ticket = \IPS\Db::i()->select( '*', 'gd_dealer_support_tickets', [ 'id=?', $ticketId ] )->first();
		}
		catch ( \Exception ) {}

		if ( !$ticket )
		{
			\IPS\Output::i()->error( 'Ticket not found.', '2GDD500/3', 404 );
			return;
		}

		new \IPS\Helpers\Form\Editor(
			'gddealer_support_admin_note',
			'',
			FALSE,
			[
				'app'         => 'gddealer',
				'key'         => 'Responses',
				'autoSaveKey' => 'gddealer-support-admin-note-' . $ticketId,
				'attachIds'   => [ $ticketId, 12 ],
			],
			NULL, NULL, NULL,
			'editor_support_admin_note_' . $ticketId
		);

		$noteRaw = (string) ( \IPS\Request::i()->gddealer_support_admin_note ?? '' );
		if ( trim( $noteRaw ) === '' )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' )
			);
			return;
		}

		$body = '';
		try
		{
			$body = \IPS\Text\Parser::parseStatic( $noteRaw, [ $ticketId, 12 ], $me, 'gddealer_Responses' );
		}
		catch ( \Exception ) { $body = $noteRaw; }

		$now = date( 'Y-m-d H:i:s' );

		try
		{
			\IPS\Db::i()->insert( 'gd_dealer_support_replies', [
				'ticket_id'      => $ticketId,
				'member_id'      => (int) $me->member_id,
				'role'           => 'admin',
				'body'           => $body,
				'is_hidden_note' => 1,
				'created_at'     => $now,
			] );
		}
		catch ( \Exception ) {}

		/* Bump updated_at only. Do NOT touch status or last_reply_* — internal
		   notes are not visible to the dealer and must not affect which side
		   is shown as the last replier or the ticket's workflow state. */
		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
				'updated_at' => $now,
			], [ 'id=?', $ticketId ] );
		}
		catch ( \Exception ) {}

		try
		{
			\IPS\File::claimAttachments(
				'gddealer-support-admin-note-' . $ticketId,
				$ticketId,
				12
			);
		}
		catch ( \Exception ) {}

		try
		{
			EventLogger::log(
				$ticketId, 'admin_note_added', 'admin', (int) $me->member_id, null
			);
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' ),
			'Note added.'
		);
	}

	protected function applyStockAction(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$ticketId = (int) ( \IPS\Request::i()->id ?? 0 );
		$actionId = (int) ( \IPS\Request::i()->action_id ?? 0 );
		$me       = \IPS\Member::loggedIn();

		$ticket = null;
		try
		{
			$ticket = \IPS\Db::i()->select( '*', 'gd_dealer_support_tickets', [ 'id=?', $ticketId ] )->first();
		}
		catch ( \Exception ) {}

		if ( !$ticket )
		{
			\IPS\Output::i()->error( 'Ticket not found.', '2GDD500/4', 404 );
			return;
		}

		$action = null;
		try
		{
			$action = \IPS\Db::i()->select( '*', 'gd_dealer_support_stock_actions', [ 'id=?', $actionId ] )->first();
		}
		catch ( \Exception ) {}

		if ( !$action )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' ),
				'Stock action not found.'
			);
			return;
		}

		$now           = date( 'Y-m-d H:i:s' );
		$hasReply      = !empty( trim( (string) ( $action['reply_body'] ?? '' ) ) );
		$hasStatus     = !empty( $action['new_status'] );
		$hasPriority   = !empty( $action['new_priority'] );
		$hasAssignee   = $action['new_assignee'] !== null && (string) $action['new_assignee'] !== '';
		$currentStatus = (string) $ticket['status'];

		if ( $hasReply )
		{
			$body = '';
			try
			{
				$body = \IPS\Text\Parser::parseStatic(
					(string) $action['reply_body'], [ $ticketId, 11 ], $me, 'gddealer_Responses'
				);
			}
			catch ( \Exception ) { $body = (string) $action['reply_body']; }

			try
			{
				\IPS\Db::i()->insert( 'gd_dealer_support_replies', [
					'ticket_id'      => $ticketId,
					'member_id'      => (int) $me->member_id,
					'role'           => 'admin',
					'body'           => $body,
					'is_hidden_note' => 0,
					'created_at'     => $now,
				] );
			}
			catch ( \Exception ) {}

			$newStatus = $hasStatus ? (string) $action['new_status'] : 'pending_customer';

			try
			{
				\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
					'status'          => $newStatus,
					'updated_at'      => $now,
					'last_reply_at'   => $now,
					'last_reply_by'   => (int) $me->member_id,
					'last_reply_role' => 'admin',
				], [ 'id=?', $ticketId ] );
			}
			catch ( \Exception ) {}

			$dealer  = \IPS\Member::load( (int) $ticket['member_id'] );
			$subject = (string) $ticket['subject'];
			$ticketUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId
			);

			if ( $dealer && $dealer->member_id )
			{
				try
				{
					$notification = new \IPS\Notification(
						\IPS\Application::load( 'gddealer' ),
						'support_reply_to_dealer',
						$dealer,
						[ $dealer ],
						[
							'ticket_id' => $ticketId,
							'subject'   => $subject,
						]
					);
					$notification->recipients->attach( $dealer );
					$notification->send();
				}
				catch ( \Exception ) {}

				try
				{
					\IPS\Email::buildFromTemplate( 'gddealer', 'supportReplyToDealer', [
						'name'     => (string) $dealer->name,
						'subject'  => $subject,
						'view_url' => $ticketUrl,
					], \IPS\Email::TYPE_TRANSACTIONAL )->send( $dealer );
				}
				catch ( \Exception ) {}
			}

			if ( $currentStatus !== $newStatus )
			{
				try
				{
					EventLogger::log(
						$ticketId, 'status_changed', 'admin',
						(int) $me->member_id, null, $currentStatus, $newStatus
					);
				}
				catch ( \Exception ) {}
			}
			$currentStatus = $newStatus;

			try
			{
				EventLogger::log(
					$ticketId, 'admin_replied', 'admin', (int) $me->member_id, null
				);
			}
			catch ( \Exception ) {}
		}
		elseif ( $hasStatus )
		{
			$newStatus = (string) $action['new_status'];
			try
			{
				\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
					'status'     => $newStatus,
					'updated_at' => $now,
				], [ 'id=?', $ticketId ] );
			}
			catch ( \Exception ) {}

			if ( $currentStatus !== $newStatus )
			{
				try
				{
					EventLogger::log(
						$ticketId,
						$newStatus === 'closed' ? 'ticket_closed' : 'status_changed',
						'admin', (int) $me->member_id, null, $currentStatus, $newStatus
					);
				}
				catch ( \Exception ) {}
			}
			$currentStatus = $newStatus;
		}

		if ( $hasPriority )
		{
			$currentPriority = (string) $ticket['priority'];
			$newPriority     = (string) $action['new_priority'];

			try
			{
				\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
					'priority'   => $newPriority,
					'updated_at' => $now,
				], [ 'id=?', $ticketId ] );
			}
			catch ( \Exception ) {}

			if ( $currentPriority !== $newPriority )
			{
				try
				{
					EventLogger::log(
						$ticketId, 'priority_changed', 'admin',
						(int) $me->member_id, null, $currentPriority, $newPriority
					);
				}
				catch ( \Exception ) {}
			}
		}

		if ( $hasAssignee )
		{
			$newAssigneeId = (int) $action['new_assignee'];
			$value = $newAssigneeId > 0 ? $newAssigneeId : null;

			try
			{
				\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
					'assigned_to' => $value,
					'updated_at'  => $now,
				], [ 'id=?', $ticketId ] );
			}
			catch ( \Exception ) {}

			try
			{
				EventLogger::log(
					$ticketId,
					$value === null ? 'unassigned' : 'assigned',
					'admin', (int) $me->member_id, null
				);
			}
			catch ( \Exception ) {}
		}

		try
		{
			EventLogger::log(
				$ticketId, 'stock_action_applied', 'admin',
				(int) $me->member_id, (string) $action['title']
			);
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' ),
			'Stock action applied: ' . (string) $action['title']
		);
	}

	protected function updateStatus(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$ticketId  = (int) ( \IPS\Request::i()->id ?? 0 );
		$newStatus = (string) ( \IPS\Request::i()->status ?? '' );

		$valid = [ 'open', 'pending_staff', 'pending_customer', 'resolved', 'closed' ];
		if ( !in_array( $newStatus, $valid, true ) )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' )
			);
			return;
		}

		$currentStatus = '';
		try
		{
			$currentStatus = (string) \IPS\Db::i()->select( 'status', 'gd_dealer_support_tickets',
				[ 'id=?', $ticketId ]
			)->first();
		}
		catch ( \Exception ) {}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
				'status'     => $newStatus,
				'updated_at' => date( 'Y-m-d H:i:s' ),
			], [ 'id=?', $ticketId ] );
		}
		catch ( \Exception ) {}

		if ( $currentStatus !== '' && $currentStatus !== $newStatus )
		{
			try
			{
				EventLogger::log(
					$ticketId,
					$newStatus === 'closed' ? 'ticket_closed' : 'status_changed',
					'admin',
					(int) \IPS\Member::loggedIn()->member_id,
					null,
					$currentStatus,
					$newStatus
				);
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' ),
			'Status updated.'
		);
	}

	protected function updatePriority(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$ticketId    = (int) ( \IPS\Request::i()->id ?? 0 );
		$newPriority = (string) ( \IPS\Request::i()->priority ?? '' );

		if ( !in_array( $newPriority, [ 'low', 'normal', 'high', 'urgent' ], true ) )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' )
			);
			return;
		}

		$currentPriority = '';
		try
		{
			$currentPriority = (string) \IPS\Db::i()->select( 'priority', 'gd_dealer_support_tickets',
				[ 'id=?', $ticketId ]
			)->first();
		}
		catch ( \Exception ) {}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
				'priority'   => $newPriority,
				'updated_at' => date( 'Y-m-d H:i:s' ),
			], [ 'id=?', $ticketId ] );
		}
		catch ( \Exception ) {}

		if ( $currentPriority !== '' && $currentPriority !== $newPriority )
		{
			try
			{
				EventLogger::log(
					$ticketId, 'priority_changed', 'admin',
					(int) \IPS\Member::loggedIn()->member_id,
					null, $currentPriority, $newPriority
				);
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' ),
			'Priority updated.'
		);
	}

	protected function assign(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$ticketId   = (int) ( \IPS\Request::i()->id ?? 0 );
		$assigneeId = (int) ( \IPS\Request::i()->assignee ?? 0 );

		$value = null;
		if ( $assigneeId > 0 )
		{
			try
			{
				$m = \IPS\Member::load( $assigneeId );
				if ( $m->member_id ) { $value = $assigneeId; }
			}
			catch ( \Exception ) {}
		}

		$currentAssignee = 0;
		try
		{
			$currentAssignee = (int) \IPS\Db::i()->select( 'assigned_to', 'gd_dealer_support_tickets',
				[ 'id=?', $ticketId ]
			)->first();
		}
		catch ( \Exception ) {}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_tickets', [
				'assigned_to' => $value,
				'updated_at'  => date( 'Y-m-d H:i:s' ),
			], [ 'id=?', $ticketId ] );
		}
		catch ( \Exception ) {}

		if ( $currentAssignee !== (int) $value )
		{
			try
			{
				EventLogger::log(
					$ticketId,
					$value === null ? 'unassigned' : 'assigned',
					'admin',
					(int) \IPS\Member::loggedIn()->member_id,
					null,
					$currentAssignee > 0 ? (string) $currentAssignee : null,
					$value !== null ? (string) $value : null
				);
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $ticketId, 'admin' ),
			'Assignment updated.'
		);
	}

	protected function delete(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$ticketId = (int) ( \IPS\Request::i()->id ?? 0 );

		try
		{
			\IPS\Db::i()->delete( 'gd_dealer_support_replies', [ 'ticket_id=?', $ticketId ] );
			\IPS\Db::i()->delete( 'gd_dealer_support_tickets', [ 'id=?', $ticketId ] );
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=support&do=tickets', 'admin' ),
			'Ticket deleted.'
		);
	}

	private static function statusLabel( string $s ): string
	{
		return match( $s ) {
			'open'              => 'Open',
			'pending_staff'     => 'Awaiting staff',
			'pending_customer'  => 'Awaiting customer',
			'resolved'          => 'Resolved',
			'closed'            => 'Closed',
			default             => ucfirst( str_replace( '_', ' ', $s ) ),
		};
	}

	private static function statusBg( string $s ): string
	{
		return match( $s ) {
			'open'              => '#dbeafe',
			'pending_staff'     => '#fef3c7',
			'pending_customer'  => '#e0e7ff',
			'resolved'          => '#dcfce7',
			'closed'            => '#f1f5f9',
			default             => '#f3f4f6',
		};
	}

	private static function statusColor( string $s ): string
	{
		return match( $s ) {
			'open'              => '#1e40af',
			'pending_staff'     => '#854d0e',
			'pending_customer'  => '#3730a3',
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
