<?php
/**
 * @brief       GD Dealer Manager — ACP Stock Replies Controller
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       19 Apr 2026
 *
 * CRUD for canned reply templates that staff can insert into ticket replies.
 */

namespace IPS\gddealer\modules\admin\dealers;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _stockreplies extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );

		$rows = [];
		try
		{
			foreach ( \IPS\Db::i()->select( 'sr.*, d.name AS dept_name',
				[ 'gd_dealer_support_stock_replies', 'sr' ],
				null,
				'sr.position ASC, sr.id ASC'
			) as $r )
			{
				try
				{
					$r = array_merge( $r, \IPS\Db::i()->select( 'd.name AS dept_name',
						[ 'gd_dealer_support_departments', 'd' ],
						[ 'd.id=?', (int) $r['department_id'] ]
					)->first() ? [] : [] );
				}
				catch ( \Exception ) {}

				$deptName = '';
				if ( !empty( $r['department_id'] ) )
				{
					try
					{
						$deptName = (string) \IPS\Db::i()->select( 'name', 'gd_dealer_support_departments',
							[ 'id=?', (int) $r['department_id'] ] )->first();
					}
					catch ( \Exception ) {}
				}

				$baseQS = [ 'id' => (int) $r['id'] ];
				$rows[] = [
					'id'              => (int) $r['id'],
					'title'           => (string) $r['title'],
					'department_name' => $deptName ?: 'All departments',
					'position'        => (int) $r['position'],
					'enabled'         => (bool) $r['enabled'],
					'edit_url'        => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=stockreplies&do=form', 'admin'
					)->setQueryString( $baseQS ),
					'delete_url'      => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=stockreplies&do=delete', 'admin'
					)->setQueryString( $baseQS )->csrf(),
					'toggle_url'      => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=stockreplies&do=toggle', 'admin'
					)->setQueryString( $baseQS )->csrf(),
					'move_up_url'     => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=stockreplies&do=moveUp', 'admin'
					)->setQueryString( $baseQS )->csrf(),
					'move_down_url'   => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=stockreplies&do=moveDown', 'admin'
					)->setQueryString( $baseQS )->csrf(),
				];
			}
		}
		catch ( \Exception ) {}

		$addUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=stockreplies&do=form', 'admin'
		);

		\IPS\Output::i()->title  = 'Stock Replies';
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers' )->supportStockReplies(
			$rows, $addUrl
		);
	}

	protected function form(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );

		$id     = (int) ( \IPS\Request::i()->id ?? 0 );
		$isEdit = $id > 0;

		$existing = null;
		if ( $isEdit )
		{
			try
			{
				$existing = \IPS\Db::i()->select( '*', 'gd_dealer_support_stock_replies', [ 'id=?', $id ] )->first();
			}
			catch ( \Exception ) {}

			if ( !$existing )
			{
				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=stockreplies', 'admin' ),
					'Stock reply not found.'
				);
				return;
			}
		}

		$editor = new \IPS\Helpers\Form\Editor(
			'gddealer_stock_reply_body',
			$isEdit ? (string) ( $existing['body'] ?? '' ) : '',
			FALSE,
			[
				'app'         => 'gddealer',
				'key'         => 'Responses',
				'autoSaveKey' => 'gddealer-stock-reply-' . $id,
				'attachIds'   => [ $id, 20 ],
			],
			NULL, NULL, NULL,
			'editor_stock_reply_' . $id
		);

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			$title        = trim( (string) ( \IPS\Request::i()->title ?? '' ) );
			$bodyRaw      = (string) ( \IPS\Request::i()->gddealer_stock_reply_body ?? '' );
			$departmentId = (int) ( \IPS\Request::i()->department_id ?? 0 );
			$enabled      = (int) ( !empty( \IPS\Request::i()->enabled ) ? 1 : 0 );

			if ( $title === '' )
			{
				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=stockreplies&do=form', 'admin' )
						->setQueryString( $isEdit ? [ 'id' => $id ] : [] ),
					'Title is required.'
				);
				return;
			}

			$data = [
				'title'         => mb_substr( $title, 0, 255 ),
				'body'          => $bodyRaw,
				'department_id' => $departmentId > 0 ? $departmentId : null,
				'enabled'       => $enabled,
			];

			if ( $isEdit )
			{
				try
				{
					\IPS\Db::i()->update( 'gd_dealer_support_stock_replies', $data, [ 'id=?', $id ] );
				}
				catch ( \Exception ) {}
			}
			else
			{
				$nextPos = 0;
				try
				{
					$nextPos = (int) \IPS\Db::i()->select( 'MAX(position)', 'gd_dealer_support_stock_replies' )->first();
				}
				catch ( \Exception ) {}
				$data['position']   = $nextPos + 1;
				$data['created_at'] = date( 'Y-m-d H:i:s' );

				try
				{
					\IPS\Db::i()->insert( 'gd_dealer_support_stock_replies', $data );
				}
				catch ( \Exception ) {}
			}

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=stockreplies', 'admin' ),
				$isEdit ? 'Stock reply updated.' : 'Stock reply created.'
			);
			return;
		}

		$departments = [ 0 => 'All departments (global)' ];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_departments', null, 'position ASC' ) as $d )
			{
				$departments[ (int) $d['id'] ] = (string) $d['name'];
			}
		}
		catch ( \Exception ) {}

		$formData = [
			'id'            => $id,
			'title'         => (string) ( $existing['title'] ?? '' ),
			'department_id' => (int) ( $existing['department_id'] ?? 0 ),
			'enabled'       => $isEdit ? (bool) $existing['enabled'] : true,
		];

		$editorHtml = (string) $editor;

		$submitUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=stockreplies&do=form', 'admin'
		);
		if ( $isEdit )
		{
			$submitUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=stockreplies&do=form', 'admin'
			)->setQueryString( [ 'id' => $id ] );
		}

		$backUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=stockreplies', 'admin'
		);

		\IPS\Output::i()->title  = $isEdit ? 'Edit Stock Reply' : 'Add Stock Reply';
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers' )->supportStockReplyForm(
			$formData, $isEdit, $editorHtml, $departments, $submitUrl, $backUrl, \IPS\Session::i()->csrfKey
		);
	}

	protected function delete(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$id = (int) ( \IPS\Request::i()->id ?? 0 );

		try
		{
			\IPS\Db::i()->delete( 'gd_dealer_support_stock_replies', [ 'id=?', $id ] );
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=stockreplies', 'admin' ),
			'Stock reply deleted.'
		);
	}

	protected function toggle(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$id = (int) ( \IPS\Request::i()->id ?? 0 );

		$current = null;
		try
		{
			$current = (int) \IPS\Db::i()->select( 'enabled', 'gd_dealer_support_stock_replies', [ 'id=?', $id ] )->first();
		}
		catch ( \Exception ) {}

		if ( $current === null )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=stockreplies', 'admin' )
			);
			return;
		}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_stock_replies', [
				'enabled' => $current ? 0 : 1,
			], [ 'id=?', $id ] );
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=stockreplies', 'admin' ),
			$current ? 'Stock reply disabled.' : 'Stock reply enabled.'
		);
	}

	protected function moveUp(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();
		$this->swapPosition( (int) ( \IPS\Request::i()->id ?? 0 ), 'up' );
	}

	protected function moveDown(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();
		$this->swapPosition( (int) ( \IPS\Request::i()->id ?? 0 ), 'down' );
	}

	private function swapPosition( int $id, string $direction ): void
	{
		$current = null;
		try
		{
			$current = \IPS\Db::i()->select( '*', 'gd_dealer_support_stock_replies', [ 'id=?', $id ] )->first();
		}
		catch ( \Exception ) {}

		if ( !$current )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=stockreplies', 'admin' )
			);
			return;
		}

		$op    = $direction === 'up' ? '<' : '>';
		$order = $direction === 'up' ? 'position DESC' : 'position ASC';

		$neighbor = null;
		try
		{
			$neighbor = \IPS\Db::i()->select( '*', 'gd_dealer_support_stock_replies',
				[ 'position' . $op . '?', (int) $current['position'] ],
				$order
			)->first();
		}
		catch ( \Exception ) {}

		if ( !$neighbor )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=stockreplies', 'admin' )
			);
			return;
		}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_stock_replies',
				[ 'position' => (int) $neighbor['position'] ],
				[ 'id=?', (int) $current['id'] ]
			);
			\IPS\Db::i()->update( 'gd_dealer_support_stock_replies',
				[ 'position' => (int) $current['position'] ],
				[ 'id=?', (int) $neighbor['id'] ]
			);
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=stockreplies', 'admin' )
		);
	}
}

class stockreplies extends _stockreplies {}
