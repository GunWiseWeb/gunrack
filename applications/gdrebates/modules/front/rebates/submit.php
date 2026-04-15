<?php
/**
 * @brief       GD Rebates — Community submission form
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Section 8.4 — any registered (non-guest) member can submit a rebate.
 * All submissions land at status=pending; admin reviews via the
 * Submission Queue (modules/admin/rebates/submissions.php). If a
 * scraper has already captured the same rebate (matching dedup_hash),
 * we mark the existing row as scraped_community instead of creating a
 * duplicate (Section 8.2.4).
 */

namespace IPS\gdrebates\modules\front\rebates;

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
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_front_submit_err_guest' ), '2GDR/20', 403 );
			return;
		}

		$settings = \IPS\Settings::i();
		$minDesc  = max( 0, (int) ( $settings->gdr_submission_min_desc ?? 30 ) );

		$values = [
			'manufacturer'        => '',
			'brand'               => '',
			'title'               => '',
			'description'         => '',
			'rebate_amount'       => '',
			'rebate_type'         => 'mail_in',
			'product_type'        => 'firearm',
			'start_date'          => '',
			'end_date'            => '',
			'submission_deadline' => '',
			'rebate_form_url'     => '',
			'rebate_pdf_url'      => '',
			'manufacturer_url'    => '',
			'submission_steps'    => '',
			'eligible_models'     => '',
		];
		$errors = [];

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			foreach ( array_keys( $values ) as $k )
			{
				$values[ $k ] = trim( (string) ( \IPS\Request::i()->$k ?? '' ) );
			}

			$amount = $values['rebate_amount'] === '' ? null : (float) $values['rebate_amount'];

			if ( $values['manufacturer'] === '' )
			{
				$errors[] = 'gdr_front_submit_err_manufacturer';
			}
			if ( $values['title'] === '' || mb_strlen( $values['title'] ) > 255 )
			{
				$errors[] = 'gdr_front_submit_err_title';
			}
			if ( mb_strlen( $values['description'] ) < $minDesc )
			{
				$errors[] = 'gdr_front_submit_err_desc';
			}
			if ( $amount === null || $amount <= 0 )
			{
				$errors[] = 'gdr_front_submit_err_amount';
			}
			if ( !in_array( $values['rebate_type'], \IPS\gdrebates\Rebate\Rebate::rebateTypes(), true ) )
			{
				$errors[] = 'gdr_front_submit_err_type';
			}
			if ( !in_array( $values['product_type'], \IPS\gdrebates\Rebate\Rebate::productTypes(), true ) )
			{
				$errors[] = 'gdr_front_submit_err_product_type';
			}
			if ( $values['start_date'] !== '' && $values['end_date'] !== ''
				&& strtotime( $values['end_date'] ) < strtotime( $values['start_date'] ) )
			{
				$errors[] = 'gdr_front_submit_err_dates';
			}

			if ( count( $errors ) === 0 )
			{
				$hash     = \IPS\gdrebates\Rebate\Rebate::dedupHash(
					$values['manufacturer'], $values['title'],
					$values['end_date'] ?: null, $amount
				);
				$existing = \IPS\gdrebates\Rebate\Rebate::findByDedupHash( $hash );

				$now = date( 'Y-m-d H:i:s' );

				try
				{
					if ( $existing )
					{
						$merged = [
							'source' => 'scraped_community',
						];
						if ( ( $existing['description'] ?? '' ) === '' && $values['description'] !== '' )
						{
							$merged['description'] = $values['description'];
						}
						if ( ( $existing['submission_steps'] ?? '' ) === '' && $values['submission_steps'] !== '' )
						{
							$merged['submission_steps'] = $values['submission_steps'];
						}
						\IPS\Db::i()->update( 'gd_rebates', $merged, [ 'id=?', (int) $existing['id'] ] );
					}
					else
					{
						$payload = [
							'manufacturer'        => mb_substr( $values['manufacturer'], 0, 150 ),
							'brand'               => mb_substr( $values['brand'] !== '' ? $values['brand'] : $values['manufacturer'], 0, 150 ),
							'title'               => mb_substr( $values['title'], 0, 255 ),
							'description'         => $values['description'],
							'rebate_amount'       => $amount,
							'rebate_type'         => $values['rebate_type'],
							'product_type'        => $values['product_type'],
							'eligible_models'     => $values['eligible_models'] !== '' ? $values['eligible_models'] : null,
							'start_date'          => $values['start_date'] !== '' ? $values['start_date'] : null,
							'end_date'            => $values['end_date']   !== '' ? $values['end_date']   : null,
							'submission_deadline' => $values['submission_deadline'] !== '' ? $values['submission_deadline'] : null,
							'rebate_form_url'     => $values['rebate_form_url']  !== '' ? mb_substr( $values['rebate_form_url'],  0, 500 ) : null,
							'rebate_pdf_url'      => $values['rebate_pdf_url']   !== '' ? mb_substr( $values['rebate_pdf_url'],   0, 500 ) : null,
							'manufacturer_url'    => $values['manufacturer_url'] !== '' ? mb_substr( $values['manufacturer_url'], 0, 500 ) : null,
							'submission_steps'    => $values['submission_steps'] !== '' ? $values['submission_steps'] : null,
							'status'              => 'pending',
							'source'              => 'community',
							'dedup_hash'          => $hash,
							'submitted_by'        => (int) $member->member_id,
							'created_at'          => $now,
						];
						\IPS\Db::i()->insert( 'gd_rebates', $payload );
					}
				}
				catch ( \Exception ) {}

				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=hub' ),
					'gdr_front_submit_success'
				);
				return;
			}
		}

		$data = [
			'values'         => $values,
			'errors'         => self::resolveErrorLabels( $errors ),
			'rebate_types'   => \IPS\gdrebates\Rebate\Rebate::rebateTypes(),
			'product_types'  => \IPS\gdrebates\Rebate\Rebate::productTypes(),
			'submit_url'     => (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=submit' ),
			'cancel_url'     => (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=hub' ),
			'csrf_key'       => \IPS\Session::i()->csrfKey,
		];

		\IPS\Output::i()->title  = $lang->addToStack( 'gdr_front_submit_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'front' )->submit( $data );
	}

	/**
	 * @param array<int,string> $keys
	 * @return array<int,string>
	 */
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
