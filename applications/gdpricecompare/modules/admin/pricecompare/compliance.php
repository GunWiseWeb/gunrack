<?php
/**
 * @brief       GD Price Comparison — State compliance admin
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

class _compliance extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'pricecompare_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$rows = [];
		try
		{
			foreach ( \IPS\Db::i()->select(
				'*', 'gd_state_restrictions', null, 'state_code ASC, restriction_type ASC'
			) as $r )
			{
				$id = (int) $r['id'];
				$rows[] = [
					'id'        => $id,
					'state'     => (string) $r['state_code'],
					'type'      => (string) $r['restriction_type'],
					'criteria'  => (string) ( $r['criteria_json'] ?? '' ),
					'notes'     => (string) ( $r['notes'] ?? '' ),
					'active'    => (int) ( $r['active'] ?? 0 ) === 1,
					'edit_url'  => (string) \IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=compliance&do=form&id=' . $id ),
					'delete_url'=> (string) \IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=compliance&do=delete&id=' . $id )->csrf(),
				];
			}
		}
		catch ( \Exception ) {}

		$data = [
			'rows'    => $rows,
			'count'   => count( $rows ),
			'add_url' => (string) \IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=compliance&do=form' ),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_compliance_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'admin' )
			->compliance( $data );
	}

	protected function form(): void
	{
		$id  = (int) ( \IPS\Request::i()->id ?? 0 );
		$row = null;
		if ( $id > 0 )
		{
			try
			{
				$row = \IPS\Db::i()->select( '*', 'gd_state_restrictions', [ 'id=?', $id ] )->first();
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

		$form->add( new \IPS\Helpers\Form\Select( 'state_code',
			$row ? (string) $row['state_code'] : '', TRUE, [ 'options' => $stateOptions ] ) );
		$form->add( new \IPS\Helpers\Form\Text( 'restriction_type',
			$row ? (string) $row['restriction_type'] : '', TRUE, [ 'maxLength' => 40,
				'placeholder' => 'nfa, magazine_capacity, assault_weapon, handgun, shipping_prohibited, silencer, sbr, sbs' ] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'criteria_json',
			$row ? (string) ( $row['criteria_json'] ?? '' ) : '', FALSE, [ 'rows' => 4,
				'placeholder' => '{"magazine_capacity":[">",10]}' ] ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'notes',
			$row ? (string) ( $row['notes'] ?? '' ) : '', FALSE, [ 'rows' => 2 ] ) );
		$form->add( new \IPS\Helpers\Form\Checkbox( 'active',
			$row ? ( (int) ( $row['active'] ?? 0 ) === 1 ) : TRUE, FALSE ) );

		if ( $values = $form->values() )
		{
			$state    = strtoupper( trim( (string) $values['state_code'] ) );
			$type     = trim( (string) $values['restriction_type'] );
			$criteria = trim( (string) $values['criteria_json'] );
			$notes    = trim( (string) $values['notes'] );
			$active   = $values['active'] ? 1 : 0;

			$errors = [];
			if ( !preg_match( '/^[A-Z]{2}$/', $state ) )
			{
				$errors[] = 'gdpc_compliance_err_state';
			}
			if ( $type === '' || !preg_match( '/^[a-z0-9_]{1,40}$/', $type ) )
			{
				$errors[] = 'gdpc_compliance_err_type';
			}
			if ( $criteria === '' )
			{
				$criteria = '{}';
			}
			$decoded = json_decode( $criteria, true );
			if ( !is_array( $decoded ) )
			{
				$errors[] = 'gdpc_compliance_err_criteria';
			}

			if ( count( $errors ) === 0 )
			{
				$payload = [
					'state_code'       => $state,
					'restriction_type' => $type,
					'criteria_json'    => $criteria,
					'notes'            => $notes,
					'active'           => $active,
				];

				if ( $isEdit )
				{
					\IPS\Db::i()->update( 'gd_state_restrictions', $payload, [ 'id=?', $id ] );
					$msg = 'gdpc_compliance_updated';
				}
				else
				{
					\IPS\Db::i()->insert( 'gd_state_restrictions', $payload );
					$msg = 'gdpc_compliance_added';
				}

				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=compliance' ),
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
			$isEdit ? 'gdpc_compliance_edit_title' : 'gdpc_compliance_add_title'
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
				\IPS\Db::i()->delete( 'gd_state_restrictions', [ 'id=?', $id ] );
			}
			catch ( \Exception ) {}
		}

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdpricecompare&module=pricecompare&controller=compliance' ),
			'gdpc_compliance_deleted'
		);
	}

	/**
	 * Resolve a list of lang keys into pre-translated strings so the template
	 * can render them with {$err} — IPS `{lang="..."}` tags require static
	 * keys, so variable keys must be translated in the controller.
	 *
	 * @param array<int,string> $keys
	 * @return array<int,string>
	 */
	private static function resolveErrorLabels( array $keys ): array
	{
		$out = [];
		$lang = \IPS\Member::loggedIn()->language();
		foreach ( $keys as $k )
		{
			$out[] = (string) $lang->addToStack( $k );
		}
		return $out;
	}

	/**
	 * Returns the 50 states + DC as a list of {code,name} dicts so IPS
	 * templates can iterate without the key=>value foreach form.
	 *
	 * @return array<int, array{code:string,name:string}>
	 */
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

class compliance extends _compliance {}
