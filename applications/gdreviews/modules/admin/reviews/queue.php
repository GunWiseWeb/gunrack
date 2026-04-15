<?php
/**
 * @brief       GD Product Reviews — Moderation queue (pending + flagged)
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 *
 * Covers Section 7.8 admin moderation: pending review queue, flagged
 * review queue, and per-review Approve / Reject with reason. All
 * state-changing actions require a CSRF token — GET approve/reject
 * URLs use ->csrf(), POST rejections go through csrfCheck().
 */

namespace IPS\gdreviews\modules\admin\reviews;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _queue extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'reviews_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$tab = (string) ( \IPS\Request::i()->tab ?? 'pending' );
		if ( !in_array( $tab, [ 'pending', 'flagged' ], true ) )
		{
			$tab = 'pending';
		}

		$rows = self::loadQueue( $tab );

		$data = [
			'tab'          => $tab,
			'rows'         => $rows,
			'pending_url'  => (string) \IPS\Http\Url::internal(
				'app=gdreviews&module=reviews&controller=queue&tab=pending'
			),
			'flagged_url'  => (string) \IPS\Http\Url::internal(
				'app=gdreviews&module=reviews&controller=queue&tab=flagged'
			),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_queue_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'reviews', 'gdreviews', 'admin' )
			->queue( $data );
	}

	/**
	 * Approve a single review. GET action with CSRF token baked into the URL.
	 */
	protected function approve(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) ( \IPS\Request::i()->id ?? 0 );
		if ( $id > 0 )
		{
			try
			{
				\IPS\Db::i()->update( 'gd_reviews', [
					'status'       => 'approved',
					'moderated_at' => date( 'Y-m-d H:i:s' ),
					'moderated_by' => (int) \IPS\Member::loggedIn()->member_id,
				], [ 'id=?', $id ] );
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdreviews&module=reviews&controller=queue' ),
			'gdr_queue_approved'
		);
	}

	/**
	 * View a single review plus a reject form.
	 */
	protected function view(): void
	{
		$id  = (int) ( \IPS\Request::i()->id ?? 0 );
		$row = null;
		if ( $id > 0 )
		{
			try
			{
				$row = \IPS\Db::i()->select( '*', 'gd_reviews', [ 'id=?', $id ] )->first();
			}
			catch ( \Exception ) {}
		}

		if ( !$row )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdreviews&module=reviews&controller=queue' ),
				'gdr_queue_not_found'
			);
			return;
		}

		$data = [
			'review'        => [
				'id'                => (int) $row['id'],
				'upc'               => (string) $row['upc'],
				'member_id'         => (int) $row['member_id'],
				'status'            => (string) $row['status'],
				'overall_rating'    => (int) $row['overall_rating'],
				'product_type'      => (string) ( $row['product_type'] ?? '' ),
				'title'             => (string) $row['title'],
				'body'              => (string) $row['body'],
				'pros'              => (string) ( $row['pros'] ?? '' ),
				'cons'              => (string) ( $row['cons'] ?? '' ),
				'would_recommend'   => $row['would_recommend'] === null ? null : (int) $row['would_recommend'],
				'usage_context'     => (string) ( $row['usage_context'] ?? '' ),
				'time_owned'        => (string) ( $row['time_owned'] ?? '' ),
				'verified_purchase' => (int) $row['verified_purchase'] === 1,
				'created_at'        => (string) $row['created_at'],
			],
			'approve_url'   => (string) \IPS\Http\Url::internal(
				'app=gdreviews&module=reviews&controller=queue&do=approve&id=' . (int) $row['id']
			)->csrf(),
			'reject_url'    => (string) \IPS\Http\Url::internal(
				'app=gdreviews&module=reviews&controller=queue&do=reject&id=' . (int) $row['id']
			),
			'back_url'      => (string) \IPS\Http\Url::internal(
				'app=gdreviews&module=reviews&controller=queue'
			),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_queue_view_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'reviews', 'gdreviews', 'admin' )
			->queueView( $data );
	}

	/**
	 * Reject with reason — renders an IPS\Helpers\Form for the moderator to
	 * supply a rejection reason. The form posts back to itself; IPS handles
	 * CSRF, validation, and the Save button automatically.
	 */
	protected function reject(): void
	{
		$id = (int) ( \IPS\Request::i()->id ?? 0 );
		if ( $id <= 0 )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdreviews&module=reviews&controller=queue' ),
				'gdr_queue_not_found'
			);
			return;
		}

		$exists = FALSE;
		try
		{
			$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_reviews', [ 'id=?', $id ] )->first() > 0;
		}
		catch ( \Exception ) {}

		if ( !$exists )
		{
			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdreviews&module=reviews&controller=queue' ),
				'gdr_queue_not_found'
			);
			return;
		}

		$form = new \IPS\Helpers\Form( 'gdr_reject', 'gdr_queue_reject' );
		$form->add( new \IPS\Helpers\Form\TextArea(
			'gdr_queue_reject_reason', '', TRUE, [ 'rows' => 4 ]
		) );

		if ( $values = $form->values() )
		{
			$reason = trim( (string) $values['gdr_queue_reject_reason'] );

			try
			{
				\IPS\Db::i()->update( 'gd_reviews', [
					'status'           => 'rejected',
					'rejection_reason' => $reason,
					'moderated_at'     => date( 'Y-m-d H:i:s' ),
					'moderated_by'     => (int) \IPS\Member::loggedIn()->member_id,
				], [ 'id=?', $id ] );
			}
			catch ( \Exception ) {}

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdreviews&module=reviews&controller=queue' ),
				'gdr_queue_rejected'
			);
			return;
		}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_queue_reject' );
		\IPS\Output::i()->output = (string) $form;
	}

	/**
	 * @return array<int, array<string,mixed>>
	 */
	private static function loadQueue( string $tab ): array
	{
		$out = [];
		$status = $tab === 'flagged' ? 'flagged' : 'pending';

		try
		{
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_reviews', [ 'status=?', $status ], 'created_at ASC', [ 0, 200 ]
			) as $r )
			{
				$id = (int) $r['id'];
				$out[] = [
					'id'                => $id,
					'upc'               => (string) $r['upc'],
					'member_id'         => (int) $r['member_id'],
					'title'             => (string) $r['title'],
					'overall_rating'    => (int) $r['overall_rating'],
					'created_at'        => (string) $r['created_at'],
					'verified_purchase' => (int) $r['verified_purchase'] === 1,
					'view_url'          => (string) \IPS\Http\Url::internal(
						'app=gdreviews&module=reviews&controller=queue&do=view&id=' . $id
					),
					'approve_url'       => (string) \IPS\Http\Url::internal(
						'app=gdreviews&module=reviews&controller=queue&do=approve&id=' . $id
					)->csrf(),
				];
			}
		}
		catch ( \Exception ) {}

		return $out;
	}
}

class queue extends _queue {}
