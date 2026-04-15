<?php
/**
 * @brief       GD Product Reviews — Admin dashboard
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 *
 * Per CLAUDE.md Rule #8: every DB query is wrapped in its own try/catch
 * so a missing table cannot break the page. No live OpenSearch calls
 * anywhere in this plugin (Section 7.7 is unwritten — see the task stub).
 */

namespace IPS\gdreviews\modules\admin\reviews;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _dashboard extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'reviews_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$counts = \IPS\gdreviews\Review\Review::countByStatus();
		$total  = (int) ( $counts['pending'] + $counts['approved'] + $counts['rejected'] + $counts['flagged'] );

		$avgRating    = 0.0;
		$verifiedPct  = 0.0;
		try
		{
			$avgRating = (float) \IPS\Db::i()->select(
				'AVG(overall_rating)', 'gd_reviews', [ 'status=?', 'approved' ]
			)->first();
		}
		catch ( \Exception ) {}

		try
		{
			$approved = max( 1, (int) $counts['approved'] );
			$verified = (int) \IPS\Db::i()->select(
				'COUNT(*)', 'gd_reviews', [ 'status=? AND verified_purchase=?', 'approved', 1 ]
			)->first();
			$verifiedPct = ( $verified / $approved ) * 100.0;
		}
		catch ( \Exception ) {}

		$latest = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'id, upc, member_id, title, overall_rating, status, created_at, verified_purchase',
				'gd_reviews', null, 'created_at DESC', [ 0, 10 ]
			) as $r )
			{
				$latest[] = [
					'id'                => (int) $r['id'],
					'upc'               => (string) $r['upc'],
					'member_id'         => (int) $r['member_id'],
					'title'             => (string) $r['title'],
					'overall_rating'    => (int) $r['overall_rating'],
					'status'            => (string) $r['status'],
					'created_at'        => (string) $r['created_at'],
					'verified_purchase' => (int) $r['verified_purchase'] === 1,
				];
			}
		}
		catch ( \Exception ) {}

		$topReviewers = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'member_id, COUNT(*) AS c', 'gd_reviews',
				[ 'status=?', 'approved' ],
				'c DESC', [ 0, 10 ],
				'member_id'
			) as $r )
			{
				$topReviewers[] = [
					'member_id' => (int) $r['member_id'],
					'count'     => (int) $r['c'],
				];
			}
		}
		catch ( \Exception ) {}

		$data = [
			'total'          => $total,
			'pending'        => (int) $counts['pending'],
			'approved'       => (int) $counts['approved'],
			'rejected'       => (int) $counts['rejected'],
			'flagged'        => (int) $counts['flagged'],
			'avg_rating'     => round( $avgRating, 2 ),
			'verified_pct'   => round( $verifiedPct, 1 ),
			'latest'         => $latest,
			'top_reviewers'  => $topReviewers,
			'queue_url'      => (string) \IPS\Http\Url::internal(
				'app=gdreviews&module=reviews&controller=queue'
			),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_dash_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'reviews', 'gdreviews', 'admin' )
			->dashboard( $data );
	}
}

class dashboard extends _dashboard {}
