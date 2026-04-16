<?php
/**
 * @brief       GD Dealer Manager — Public Dealer Profile Page
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       16 Apr 2026
 *
 * Public-facing dealer profile at /dealers/{slug}. Renders the dealer
 * header, aggregated ratings, all approved reviews, and — for logged-in
 * members who have not yet rated this dealer — an inline rating form.
 */

namespace IPS\gddealer\modules\front\dealers;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _profile extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		parent::execute();
	}

	/**
	 * Default: render the public profile for the dealer identified by
	 * the {dealer_slug} URL segment.
	 */
	protected function manage(): void
	{
		$slug   = trim( (string) ( \IPS\Request::i()->dealer_slug ?? '' ) );
		$member = \IPS\Member::loggedIn();

		if ( $slug === '' )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/1', 404 );
			return;
		}

		try
		{
			$dealerRow = \IPS\Db::i()->select( '*', 'gd_dealer_feed_config',
				[ 'dealer_slug=? AND active=?', $slug, 1 ] )->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/2', 404 );
			return;
		}

		$dealerId = (int) $dealerRow['dealer_id'];

		/* Aggregated ratings (approved reviews only contribute to averages). */
		$stats = [
			'total'        => 0,
			'avg_pricing'  => 0.0,
			'avg_shipping' => 0.0,
			'avg_service'  => 0.0,
			'avg_overall'  => 0.0,
		];
		try
		{
			$agg = \IPS\Db::i()->select(
				'COUNT(*) as c, AVG(rating_pricing) as p, AVG(rating_shipping) as s, AVG(rating_service) as sv',
				'gd_dealer_ratings', [ 'dealer_id=? AND status=?', $dealerId, 'approved' ]
			)->first();
			$stats['total']        = (int) $agg['c'];
			$stats['avg_pricing']  = round( (float) $agg['p'], 1 );
			$stats['avg_shipping'] = round( (float) $agg['s'], 1 );
			$stats['avg_service']  = round( (float) $agg['sv'], 1 );
			$stats['avg_overall']  = $stats['total'] > 0
				? round( ( $stats['avg_pricing'] + $stats['avg_shipping'] + $stats['avg_service'] ) / 3, 1 )
				: 0.0;
		}
		catch ( \Exception ) {}

		/* Reviews list — only approved. Disputed reviews are hidden from
		   the public profile until admin resolves them. */
		$reviews = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_ratings',
				[ 'dealer_id=? AND status=?', $dealerId, 'approved' ],
				'created_at DESC', [ 0, 50 ]
			) as $r )
			{
				$reviews[] = [
					'id'              => (int) $r['id'],
					'rating_pricing'  => (int) $r['rating_pricing'],
					'rating_shipping' => (int) $r['rating_shipping'],
					'rating_service'  => (int) $r['rating_service'],
					'review_body'     => (string) ( $r['review_body'] ?? '' ),
					'dealer_response' => (string) ( $r['dealer_response'] ?? '' ),
					'created_at'      => (string) $r['created_at'],
					'disputed'        => (int) ( $r['disputed'] ?? 0 ),
				];
			}
		}
		catch ( \Exception ) {}

		$alreadyRated = false;
		if ( $member->member_id )
		{
			try
			{
				$alreadyRated = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_ratings',
					[ 'dealer_id=? AND member_id=?', $dealerId, (int) $member->member_id ] )->first() > 0;
			}
			catch ( \Exception ) {}
		}

		/* A member cannot review their own dealership. */
		$isOwnDealer   = $member->member_id && (int) $member->member_id === $dealerId;
		$canRate       = $member->member_id && !$alreadyRated && !$isOwnDealer;
		$loginRequired = !$member->member_id;

		$rateUrl = (string) \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=profile&do=rate&dealer_slug=' . urlencode( $slug )
		)->csrf();

		$loginUrl = (string) \IPS\Http\Url::internal(
			'app=core&module=system&controller=login',
			'front', 'login'
		);

		$createdAtRaw = (string) ( $dealerRow['created_at'] ?? '' );
		$memberSince  = $createdAtRaw ? substr( $createdAtRaw, 0, 7 ) : '';

		$dealer = [
			'dealer_id'    => $dealerId,
			'dealer_name'  => (string) $dealerRow['dealer_name'],
			'dealer_slug'  => (string) $dealerRow['dealer_slug'],
			'member_since' => $memberSince,
		];

		$csrfKey = (string) \IPS\Session::i()->csrfKey;

		\IPS\Output::i()->title  = $dealer['dealer_name'];
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )
			->dealerProfile( $dealer, $stats, $reviews, $canRate, $alreadyRated, $loginRequired, $rateUrl, $csrfKey, $loginUrl );
	}

	/**
	 * POST — create or update a member's rating for this dealer.
	 * Uniqueness is enforced by the uq_dealer_member index: each member
	 * can only rate a given dealer once (subsequent submits are updates).
	 */
	protected function rate(): void
	{
		\IPS\Session::i()->csrfCheck();

		$member = \IPS\Member::loggedIn();
		if ( !$member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD300/3', 403 );
			return;
		}

		$slug = trim( (string) ( \IPS\Request::i()->dealer_slug ?? '' ) );
		if ( $slug === '' )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/4', 404 );
			return;
		}

		try
		{
			$dealerRow = \IPS\Db::i()->select( '*', 'gd_dealer_feed_config',
				[ 'dealer_slug=? AND active=?', $slug, 1 ] )->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->error( 'dealer_not_found', '2GDD300/5', 404 );
			return;
		}

		$dealerId = (int) $dealerRow['dealer_id'];
		if ( (int) $member->member_id === $dealerId )
		{
			\IPS\Output::i()->error( 'node_error', '2GDD300/6', 403 );
			return;
		}

		$pricing  = max( 1, min( 5, (int) ( \IPS\Request::i()->rating_pricing ?? 0 ) ) );
		$shipping = max( 1, min( 5, (int) ( \IPS\Request::i()->rating_shipping ?? 0 ) ) );
		$service  = max( 1, min( 5, (int) ( \IPS\Request::i()->rating_service ?? 0 ) ) );
		$body     = trim( (string) ( \IPS\Request::i()->review_body ?? '' ) );

		$profileUrl = \IPS\Http\Url::internal(
			'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug )
		);

		if ( $pricing < 1 || $shipping < 1 || $service < 1 )
		{
			\IPS\Output::i()->redirect( $profileUrl, 'gddealer_profile_rating_invalid' );
			return;
		}

		try
		{
			$existing = (int) \IPS\Db::i()->select( 'id', 'gd_dealer_ratings',
				[ 'dealer_id=? AND member_id=?', $dealerId, (int) $member->member_id ] )->first();
		}
		catch ( \Exception )
		{
			$existing = 0;
		}

		try
		{
			if ( $existing > 0 )
			{
				\IPS\Db::i()->update( 'gd_dealer_ratings', [
					'rating_pricing'  => $pricing,
					'rating_shipping' => $shipping,
					'rating_service'  => $service,
					'review_body'     => $body !== '' ? $body : null,
				], [ 'id=?', $existing ] );
			}
			else
			{
				\IPS\Db::i()->insert( 'gd_dealer_ratings', [
					'dealer_id'       => $dealerId,
					'member_id'       => (int) $member->member_id,
					'rating_pricing'  => $pricing,
					'rating_shipping' => $shipping,
					'rating_service'  => $service,
					'review_body'     => $body !== '' ? $body : null,
					'created_at'      => date( 'Y-m-d H:i:s' ),
					'status'          => 'approved',
					'disputed'        => 0,
				]);
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect( $profileUrl, 'gddealer_profile_rating_saved' );
	}
}

class profile extends _profile {}
