<?php
/**
 * @brief       GD Dealer Manager — ACP Support Departments Controller
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       19 Apr 2026
 *
 * Full CRUD for support departments: list, add/edit, delete (safety-gated),
 * toggle enabled, and move-up/move-down reorder.
 */

namespace IPS\gddealer\modules\admin\dealers;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _departments extends \IPS\Dispatcher\Controller
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

		$departments = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_support_departments', null, 'position ASC, id ASC' ) as $d )
			{
				$ticketCount = 0;
				try
				{
					$ticketCount = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_support_tickets',
						[ 'department_id=?', (int) $d['id'] ]
					)->first();
				}
				catch ( \Exception ) {}

				$baseQS = [ 'id' => (int) $d['id'] ];
				$departments[] = [
					'id'               => (int) $d['id'],
					'name'             => (string) $d['name'],
					'description'      => (string) ( $d['description'] ?? '' ),
					'email'            => (string) ( $d['email'] ?? '' ),
					'visibility'       => (string) $d['visibility'],
					'visibility_label' => self::visibilityLabel( (string) $d['visibility'] ),
					'visibility_bg'    => self::visibilityBg( (string) $d['visibility'] ),
					'visibility_color' => self::visibilityColor( (string) $d['visibility'] ),
					'position'         => (int) $d['position'],
					'enabled'          => (bool) $d['enabled'],
					'ticket_count'     => $ticketCount,
					'edit_url'         => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=departments&do=form', 'admin'
					)->setQueryString( $baseQS ),
					'delete_url'       => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=departments&do=delete', 'admin'
					)->setQueryString( $baseQS )->csrf(),
					'toggle_url'       => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=departments&do=toggle', 'admin'
					)->setQueryString( $baseQS )->csrf(),
					'move_up_url'      => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=departments&do=moveUp', 'admin'
					)->setQueryString( $baseQS )->csrf(),
					'move_down_url'    => (string) \IPS\Http\Url::internal(
						'app=gddealer&module=dealers&controller=departments&do=moveDown', 'admin'
					)->setQueryString( $baseQS )->csrf(),
				];
			}
		}
		catch ( \Exception ) {}

		$addUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=departments&do=form', 'admin'
		);

		\IPS\Output::i()->title  = 'Support Departments';
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers' )->supportDepartments(
			$departments, $addUrl
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
				$existing = \IPS\Db::i()->select( '*', 'gd_dealer_support_departments', [ 'id=?', $id ] )->first();
			}
			catch ( \Exception ) {}

			if ( !$existing )
			{
				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' ),
					'Department not found.'
				);
				return;
			}
		}

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			$name        = trim( (string) ( \IPS\Request::i()->name ?? '' ) );
			$description = trim( (string) ( \IPS\Request::i()->description ?? '' ) );
			$email       = trim( (string) ( \IPS\Request::i()->email ?? '' ) );
			$visibility  = (string) ( \IPS\Request::i()->visibility ?? 'pro' );
			$enabled     = (int) ( !empty( \IPS\Request::i()->enabled ) ? 1 : 0 );

			if ( !in_array( $visibility, [ 'public', 'pro', 'enterprise' ], true ) )
			{
				$visibility = 'pro';
			}

			if ( $name === '' )
			{
				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments&do=form', 'admin' )
						->setQueryString( $isEdit ? [ 'id' => $id ] : [] ),
					'Department name is required.'
				);
				return;
			}

			$data = [
				'name'        => mb_substr( $name, 0, 128 ),
				'description' => $description !== '' ? $description : null,
				'email'       => $email !== '' ? mb_substr( $email, 0, 255 ) : null,
				'visibility'  => $visibility,
				'enabled'     => $enabled,
			];

			if ( $isEdit )
			{
				try
				{
					\IPS\Db::i()->update( 'gd_dealer_support_departments', $data, [ 'id=?', $id ] );
				}
				catch ( \Exception ) {}
			}
			else
			{
				$nextPos = 0;
				try
				{
					$nextPos = (int) \IPS\Db::i()->select( 'MAX(position)', 'gd_dealer_support_departments' )->first();
				}
				catch ( \Exception ) {}
				$data['position']   = $nextPos + 1;
				$data['created_at'] = date( 'Y-m-d H:i:s' );

				try
				{
					\IPS\Db::i()->insert( 'gd_dealer_support_departments', $data );
				}
				catch ( \Exception ) {}
			}

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' ),
				$isEdit ? 'Department updated.' : 'Department created.'
			);
			return;
		}

		$formData = [
			'id'          => $id,
			'name'        => (string) ( $existing['name'] ?? '' ),
			'description' => (string) ( $existing['description'] ?? '' ),
			'email'       => (string) ( $existing['email'] ?? '' ),
			'visibility'  => (string) ( $existing['visibility'] ?? 'pro' ),
			'enabled'     => $isEdit ? (bool) $existing['enabled'] : true,
		];

		$submitUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=departments&do=form', 'admin'
		);
		if ( $isEdit )
		{
			$submitUrl = (string) \IPS\Http\Url::internal(
				'app=gddealer&module=dealers&controller=departments&do=form', 'admin'
			)->setQueryString( [ 'id' => $id ] );
		}

		$backUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=departments', 'admin'
		);

		\IPS\Output::i()->title  = $isEdit ? 'Edit Department' : 'Add Department';
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers' )->supportDepartmentForm(
			$formData, $isEdit, $submitUrl, $backUrl, \IPS\Session::i()->csrfKey
		);
	}

	protected function delete(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'dealer_manage' );
		\IPS\Session::i()->csrfCheck();

		$id = (int) ( \IPS\Request::i()->id ?? 0 );

		$ticketCount = 0;
		try
		{
			$ticketCount = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_support_tickets',
				[ 'department_id=?', $id ]
			)->first();
		}
		catch ( \Exception ) {}

		if ( $ticketCount > 0 )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' ),
				sprintf( 'Cannot delete: %d ticket(s) still reference this department. Disable it instead, or move the tickets first.', $ticketCount )
			);
			return;
		}

		$totalCount = 0;
		try
		{
			$totalCount = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_support_departments' )->first();
		}
		catch ( \Exception ) {}

		if ( $totalCount <= 1 )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' ),
				'Cannot delete the last remaining department. Create another first.'
			);
			return;
		}

		try
		{
			\IPS\Db::i()->delete( 'gd_dealer_support_departments', [ 'id=?', $id ] );
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' ),
			'Department deleted.'
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
			$current = (int) \IPS\Db::i()->select( 'enabled', 'gd_dealer_support_departments', [ 'id=?', $id ] )->first();
		}
		catch ( \Exception ) {}

		if ( $current === null )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' )
			);
			return;
		}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_departments', [
				'enabled' => $current ? 0 : 1,
			], [ 'id=?', $id ] );
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' ),
			$current ? 'Department disabled.' : 'Department enabled.'
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
			$current = \IPS\Db::i()->select( '*', 'gd_dealer_support_departments', [ 'id=?', $id ] )->first();
		}
		catch ( \Exception ) {}

		if ( !$current )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' )
			);
			return;
		}

		$op    = $direction === 'up' ? '<' : '>';
		$order = $direction === 'up' ? 'position DESC' : 'position ASC';

		$neighbor = null;
		try
		{
			$neighbor = \IPS\Db::i()->select( '*', 'gd_dealer_support_departments',
				[ 'position' . $op . '?', (int) $current['position'] ],
				$order
			)->first();
		}
		catch ( \Exception ) {}

		if ( !$neighbor )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' )
			);
			return;
		}

		try
		{
			\IPS\Db::i()->update( 'gd_dealer_support_departments',
				[ 'position' => (int) $neighbor['position'] ],
				[ 'id=?', (int) $current['id'] ]
			);
			\IPS\Db::i()->update( 'gd_dealer_support_departments',
				[ 'position' => (int) $current['position'] ],
				[ 'id=?', (int) $neighbor['id'] ]
			);
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=departments', 'admin' )
		);
	}

	private static function visibilityLabel( string $v ): string
	{
		return match ( $v ) {
			'public'     => 'Public (all dealers)',
			'pro'        => 'Pro+',
			'enterprise' => 'Enterprise only',
			default      => ucfirst( $v ),
		};
	}

	private static function visibilityBg( string $v ): string
	{
		return match ( $v ) {
			'public'     => '#dcfce7',
			'pro'        => '#dbeafe',
			'enterprise' => '#fef3c7',
			default      => '#f3f4f6',
		};
	}

	private static function visibilityColor( string $v ): string
	{
		return match ( $v ) {
			'public'     => '#166534',
			'pro'        => '#1e40af',
			'enterprise' => '#854d0e',
			default      => '#374151',
		};
	}
}

class departments extends _departments {}
