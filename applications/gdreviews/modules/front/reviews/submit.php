<?php
/**
 * @brief       GD Product Reviews — Review submission form
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 *
 * Business rules (Section 7.2 & 7.3):
 *   - Any registered (non-guest) member can submit a review.
 *   - One review per member per UPC — editing updates the existing row
 *     (uq_upc_member UNIQUE KEY), which also returns status to pending.
 *   - All submissions land at status = pending, regardless of verified
 *     purchase (the auto-approve toggle is intentionally opt-in).
 *   - product_type is snapshotted from gd_catalog at submission time
 *     (Section 7.2 — form is uniform, column exists for future use).
 *   - Review body must meet the configured minimum length (spec floor 50).
 */

namespace IPS\gdreviews\modules\front\reviews;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _submit extends \IPS\Dispatcher\Controller
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
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_err_guest' ), '2GDR/1', 403 );
			return;
		}

		$upc = trim( (string) ( \IPS\Request::i()->upc ?? '' ) );
		if ( $upc === '' )
		{
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_err_no_product' ), '2GDR/2', 404 );
			return;
		}

		$product = self::loadProduct( $upc );
		if ( $product === null )
		{
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_err_no_product' ), '2GDR/3', 404 );
			return;
		}

		$settings    = \IPS\Settings::i();
		$minBody     = max( 50, (int) ( $settings->gdr_min_body_chars ?? 50 ) );
		$autoApprove = (int) ( $settings->gdr_auto_approve_verified ?? 0 ) === 1;

		$existing = \IPS\gdreviews\Review\Review::findByMemberUpc( (int) $member->member_id, $upc );

		$formData = self::defaultFormData( $existing, $product );
		$errors   = [];

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			$overall   = (int) \IPS\Request::i()->overall_rating;
			$title     = trim( (string) \IPS\Request::i()->title );
			$body      = trim( (string) \IPS\Request::i()->body );
			$pros      = trim( (string) \IPS\Request::i()->pros );
			$cons      = trim( (string) \IPS\Request::i()->cons );
			$context   = trim( (string) \IPS\Request::i()->usage_context );
			$timeOwned = trim( (string) \IPS\Request::i()->time_owned );

			$recRaw = (string) \IPS\Request::i()->would_recommend;
			$recommend = null;
			if ( $recRaw === 'yes' )  { $recommend = 1; }
			if ( $recRaw === 'no' )   { $recommend = 0; }

			if ( $overall < 1 || $overall > 5 )
			{
				$errors[] = 'gdr_err_overall_range';
			}
			if ( $title === '' || mb_strlen( $title ) > 150 )
			{
				$errors[] = 'gdr_err_title_required';
			}
			if ( mb_strlen( $body ) < $minBody )
			{
				$errors[] = 'gdr_err_body_short';
			}
			if ( mb_strlen( $body ) > 60000 )
			{
				$errors[] = 'gdr_err_body_long';
			}

			if ( count( $errors ) === 0 )
			{
				$meta = \IPS\gdreviews\Review\Review::buildSubmissionMeta(
					$upc, (int) $member->member_id
				);

				$now        = date( 'Y-m-d H:i:s' );
				$verified   = (int) $meta['verified_purchase'] === 1;
				$finalStatus = ( $autoApprove && $verified ) ? 'approved' : 'pending';

				$payload = [
					'upc'               => $upc,
					'member_id'         => (int) $member->member_id,
					'verified_purchase' => $verified ? 1 : 0,
					'status'            => $finalStatus,
					'overall_rating'    => $overall,
					'product_type'      => (string) $meta['product_type'],
					'title'             => mb_substr( $title, 0, 150 ),
					'body'              => $body,
					'pros'              => $pros !== '' ? mb_substr( $pros, 0, 500 ) : null,
					'cons'              => $cons !== '' ? mb_substr( $cons, 0, 500 ) : null,
					'would_recommend'   => $recommend,
					'usage_context'     => $context !== '' ? mb_substr( $context, 0, 50 ) : null,
					'time_owned'        => $timeOwned !== '' ? mb_substr( $timeOwned, 0, 50 ) : null,
				];

				try
				{
					if ( $existing )
					{
						$payload['updated_at'] = $now;
						\IPS\Db::i()->update( 'gd_reviews', $payload, [ 'id=?', (int) $existing['id'] ] );
						$flash = 'gdr_front_submit_update_success';
					}
					else
					{
						$payload['created_at'] = $now;
						\IPS\Db::i()->insert( 'gd_reviews', $payload );
						$flash = 'gdr_front_submit_success';
					}

					\IPS\Output::i()->redirect(
						\IPS\Http\Url::internal( 'app=gdreviews&module=reviews&controller=hub' ),
						$flash
					);
					return;
				}
				catch ( \Exception ) {}
			}

			$formData = array_merge( $formData, [
				'overall_rating'  => $overall,
				'title'           => $title,
				'body'            => $body,
				'pros'            => $pros,
				'cons'            => $cons,
				'usage_context'   => $context,
				'time_owned'      => $timeOwned,
				'would_recommend' => $recommend,
			] );
		}

		$formData['errors']      = self::resolveErrorLabels( $errors );
		$formData['product']     = $product;
		$formData['submit_url']  = (string) \IPS\Http\Url::internal(
			'app=gdreviews&module=reviews&controller=submit&upc=' . rawurlencode( $upc )
		);
		$formData['cancel_url']  = (string) \IPS\Http\Url::internal(
			'app=gdreviews&module=reviews&controller=hub'
		);
		$formData['csrf_key']    = \IPS\Session::i()->csrfKey;
		$formData['is_edit']     = $existing !== null;

		\IPS\Output::i()->title  = $lang->addToStack( 'gdr_front_submit_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'reviews', 'gdreviews', 'front' )
			->submit( $formData );
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private static function loadProduct( string $upc ): ?array
	{
		try
		{
			$r = \IPS\Db::i()->select(
				'upc, title, product_type, brand, category',
				'gd_catalog', [ 'upc=?', $upc ]
			)->first();
			return [
				'upc'          => (string) ( $r['upc'] ?? $upc ),
				'title'        => (string) ( $r['title'] ?? '' ),
				'product_type' => (string) ( $r['product_type'] ?? '' ),
				'brand'        => (string) ( $r['brand'] ?? '' ),
				'category'     => (string) ( $r['category'] ?? '' ),
			];
		}
		catch ( \Exception )
		{
			return null;
		}
	}

	/**
	 * @param array<string,mixed>|null $existing
	 * @param array<string,mixed> $product
	 * @return array<string,mixed>
	 */
	private static function defaultFormData( ?array $existing, array $product ): array
	{
		if ( $existing )
		{
			return [
				'overall_rating'  => (int) ( $existing['overall_rating'] ?? 0 ),
				'title'           => (string) ( $existing['title'] ?? '' ),
				'body'            => (string) ( $existing['body'] ?? '' ),
				'pros'            => (string) ( $existing['pros'] ?? '' ),
				'cons'            => (string) ( $existing['cons'] ?? '' ),
				'usage_context'   => (string) ( $existing['usage_context'] ?? '' ),
				'time_owned'      => (string) ( $existing['time_owned'] ?? '' ),
				'would_recommend' => $existing['would_recommend'] === null ? null : (int) $existing['would_recommend'],
			];
		}
		return [
			'overall_rating'  => 0,
			'title'           => '',
			'body'            => '',
			'pros'            => '',
			'cons'            => '',
			'usage_context'   => '',
			'time_owned'      => '',
			'would_recommend' => null,
		];
	}

	/** @param array<int,string> $keys
	 *  @return array<int,string> */
	private static function resolveErrorLabels( array $keys ): array
	{
		$out  = [];
		$lang = \IPS\Member::loggedIn()->language();
		foreach ( $keys as $k )
		{
			$out[] = (string) $lang->addToStack( $k );
		}
		return $out;
	}
}

class submit extends _submit {}
