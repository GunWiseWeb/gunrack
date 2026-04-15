<?php
/**
 * @brief       GD Price Comparison — FFL data admin (list + CRUD + ATF refresh)
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\modules\admin\pricecompare;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _ffldata extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'pricecompare_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$count      = \IPS\gdpricecompare\Ffl\FflDealer::totalCount();
		$last       = \IPS\gdpricecompare\Ffl\FflDealer::lastRefreshedAt();
		$activeCnt  = 0;
		try
		{
			$activeCnt = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_ffl_dealers', [ 'active=?', 1 ] )->first();
		}
		catch ( \Exception ) {}

		$search = trim( (string) ( \IPS\Request::i()->q ?? '' ) );

		$rows = [];
		try
		{
			$where = null;
			if ( $search !== '' )
			{
				$needle = '%' . $search . '%';
				$where  = [
					'business_name LIKE ? OR licensee_name LIKE ? OR premise_city LIKE ? OR premise_zip LIKE ? OR lic_seqn LIKE ?',
					$needle, $needle, $needle, $needle, $needle,
				];
			}

			foreach ( \IPS\Db::i()->select(
				'*', 'gd_ffl_dealers', $where, 'premise_state ASC, business_name ASC', [ 0, 200 ]
			) as $r )
			{
				$id = (int) $r['id'];
				$rows[] = [
					'id'          => $id,
					'license'     => (string) ( $r['lic_seqn'] ?? '' ),
					'business'    => (string) ( $r['business_name'] ?? $r['licensee_name'] ?? '' ),
					'licensee'    => (string) ( $r['licensee_name'] ?? '' ),
					'street'      => (string) ( $r['premise_street'] ?? '' ),
					'city'        => (string) ( $r['premise_city'] ?? '' ),
					'state'       => (string) ( $r['premise_state'] ?? '' ),
					'zip'         => (string) ( $r['premise_zip'] ?? '' ),
					'phone'       => (string) ( $r['voice_phone'] ?? '' ),
					'lic_type'    => (string) ( $r['lic_type'] ?? '' ),
					'lic_xprdte'  => (string) ( $r['lic_xprdte'] ?? '' ),
					'active'      => (int) ( $r['active'] ?? 0 ) === 1,
					'edit_url'    => (string) \IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=ffldata&do=form&id=' . $id ),
					'delete_url'  => (string) \IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=ffldata&do=delete&id=' . $id )->csrf(),
				];
			}
		}
		catch ( \Exception ) {}

		$data = [
			'count'       => $count,
			'active_cnt'  => $activeCnt,
			'last'        => $last,
			'rows'        => $rows,
			'search'      => $search,
			'refresh_url' => (string) \IPS\Http\Url::internal(
				'app=gdpricecompare&module=pricecompare&controller=ffldata&do=refresh'
			)->csrf(),
			'add_url'     => (string) \IPS\Http\Url::internal(
				'app=gdpricecompare&module=pricecompare&controller=ffldata&do=form'
			),
			'form_action' => (string) \IPS\Http\Url::internal(
				'app=gdpricecompare&module=pricecompare&controller=ffldata'
			),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_ffldata_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'admin' )
			->ffldata( $data );
	}

	protected function refresh(): void
	{
		\IPS\Session::i()->csrfCheck();

		try
		{
			\IPS\Db::i()->update( 'core_tasks', [ 'next_run' => time() ], [ 'app=? AND `key`=?', 'gdpricecompare', 'refreshFflData' ]);
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=ffldata' ),
			'gdpc_ffldata_refresh_queued'
		);
	}

	protected function form(): void
	{
		$id  = (int) ( \IPS\Request::i()->id ?? 0 );
		$row = null;
		if ( $id > 0 )
		{
			try
			{
				$row = \IPS\Db::i()->select( '*', 'gd_ffl_dealers', [ 'id=?', $id ] )->first();
			}
			catch ( \Exception ) {}
		}
		$isEdit = ( $id > 0 && $row );

		$stateOptions = [ '' => '— Select a state —' ];
		foreach ( self::stateList() as $st )
		{
			$stateOptions[ $st['code'] ] = $st['code'] . ' — ' . $st['name'];
		}

		$form = new \IPS\Helpers\Form;

		$form->addHeader( 'gdpc_ffldata_section_license' );
		$form->add( new \IPS\Helpers\Form\Text( 'lic_seqn',
			$row ? (string) ( $row['lic_seqn'] ?? '' ) : '', FALSE, [ 'maxLength' => 32, 'placeholder' => '1-23-456-78-9X-12345' ] ) );
		$form->add( new \IPS\Helpers\Form\Text( 'lic_type',
			$row ? (string) ( $row['lic_type'] ?? '' ) : '', FALSE, [ 'maxLength' => 5, 'placeholder' => '01, 07, 09' ] ) );
		$form->add( new \IPS\Helpers\Form\Date( 'lic_xprdte',
			( $row && !empty( $row['lic_xprdte'] ) ) ? \IPS\DateTime::ts( strtotime( (string) $row['lic_xprdte'] ) ) : NULL, FALSE ) );

		$form->addHeader( 'gdpc_ffldata_section_business' );
		$form->add( new \IPS\Helpers\Form\Text( 'business_name',
			$row ? (string) ( $row['business_name'] ?? '' ) : '', FALSE, [ 'maxLength' => 200 ] ) );
		$form->add( new \IPS\Helpers\Form\Text( 'licensee_name',
			$row ? (string) ( $row['licensee_name'] ?? '' ) : '', FALSE, [ 'maxLength' => 200 ] ) );
		$form->add( new \IPS\Helpers\Form\Text( 'voice_phone',
			$row ? (string) ( $row['voice_phone'] ?? '' ) : '', FALSE, [ 'maxLength' => 20 ] ) );

		$form->addHeader( 'gdpc_ffldata_section_address' );
		$form->add( new \IPS\Helpers\Form\Text( 'premise_street',
			$row ? (string) ( $row['premise_street'] ?? '' ) : '', FALSE, [ 'maxLength' => 200 ] ) );
		$form->add( new \IPS\Helpers\Form\Text( 'premise_city',
			$row ? (string) ( $row['premise_city'] ?? '' ) : '', FALSE, [ 'maxLength' => 100 ] ) );
		$form->add( new \IPS\Helpers\Form\Select( 'premise_state',
			$row ? (string) ( $row['premise_state'] ?? '' ) : '', FALSE, [ 'options' => $stateOptions ] ) );
		$form->add( new \IPS\Helpers\Form\Text( 'premise_zip',
			$row ? (string) ( $row['premise_zip'] ?? '' ) : '', FALSE, [ 'maxLength' => 10, 'placeholder' => '12345 or 12345-6789' ] ) );

		$form->addHeader( 'gdpc_ffldata_section_status' );
		$form->add( new \IPS\Helpers\Form\Checkbox( 'active',
			$row ? ( (int) ( $row['active'] ?? 0 ) === 1 ) : TRUE, FALSE ) );

		if ( $values = $form->values() )
		{
			$state = strtoupper( trim( (string) $values['premise_state'] ) );
			$zip   = trim( (string) $values['premise_zip'] );
			$bn    = trim( (string) $values['business_name'] );
			$ln    = trim( (string) $values['licensee_name'] );
			$expRaw = $values['lic_xprdte'];

			$errors = [];
			if ( $bn === '' && $ln === '' )
			{
				$errors[] = 'gdpc_ffldata_err_name';
			}
			if ( $state !== '' && !preg_match( '/^[A-Z]{2}$/', $state ) )
			{
				$errors[] = 'gdpc_ffldata_err_state';
			}
			if ( $zip !== '' && !preg_match( '/^[0-9]{5}(-[0-9]{4})?$/', $zip ) )
			{
				$errors[] = 'gdpc_ffldata_err_zip';
			}

			$expiry = '';
			if ( $expRaw instanceof \IPS\DateTime )
			{
				$expiry = $expRaw->format( 'Y-m-d' );
			}
			elseif ( is_string( $expRaw ) && $expRaw !== '' )
			{
				if ( !preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expRaw ) )
				{
					$errors[] = 'gdpc_ffldata_err_expiry';
				}
				else
				{
					$expiry = $expRaw;
				}
			}

			if ( count( $errors ) === 0 )
			{
				$payload = [
					'lic_seqn'       => trim( (string) $values['lic_seqn'] ),
					'business_name'  => $bn,
					'licensee_name'  => $ln,
					'premise_street' => trim( (string) $values['premise_street'] ),
					'premise_city'   => trim( (string) $values['premise_city'] ),
					'premise_state'  => $state,
					'premise_zip'    => $zip,
					'voice_phone'    => trim( (string) $values['voice_phone'] ),
					'lic_type'       => trim( (string) $values['lic_type'] ),
					'lic_xprdte'     => $expiry,
					'active'         => $values['active'] ? 1 : 0,
					'last_updated'   => date( 'Y-m-d H:i:s' ),
				];

				if ( $isEdit )
				{
					\IPS\Db::i()->update( 'gd_ffl_dealers', $payload, [ 'id=?', $id ] );
					$msg = 'gdpc_ffldata_updated';
				}
				else
				{
					\IPS\Db::i()->insert( 'gd_ffl_dealers', $payload );
					$msg = 'gdpc_ffldata_added';
				}

				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=ffldata' ),
					$msg
				);
				return;
			}

			$lang = \IPS\Member::loggedIn()->language();
			foreach ( $errors as $k )
			{
				$form->error = (string) $lang->addToStack( $k );
			}
		}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack(
			$isEdit ? 'gdpc_ffldata_edit_title' : 'gdpc_ffldata_add_title'
		);
		\IPS\Output::i()->output = (string) $form;
	}

	protected function delete(): void
	{
		\IPS\Session::i()->csrfCheck();

		$id = (int) ( \IPS\Request::i()->id ?? 0 );
		if ( $id > 0 )
		{
			try
			{
				\IPS\Db::i()->delete( 'gd_ffl_dealers', [ 'id=?', $id ] );
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=ffldata' ),
			'gdpc_ffldata_deleted'
		);
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

	/** @return array<int, array{code:string,name:string}> */
	private static function stateList(): array
	{
		$states = [
			'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
			'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
			'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
			'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
			'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
			'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
			'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
			'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
			'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
			'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
			'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
			'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
			'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia',
		];

		$out = [];
		foreach ( $states as $code => $name )
		{
			$out[] = [ 'code' => $code, 'name' => $name ];
		}
		return $out;
	}
}

class ffldata extends _ffldata {}
