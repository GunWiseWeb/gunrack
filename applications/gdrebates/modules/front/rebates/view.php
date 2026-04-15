<?php
/**
 * @brief       GD Rebates — Rebate detail page
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Section 8.5 detail page: header block, submission steps, user
 * tracking block (logged-in members only), community success-rate
 * block, and linked-products block (lookup against gd_catalog by UPC
 * if eligible_upcs is populated — kept intentionally lightweight
 * because gd_catalog may not exist at install time).
 */

namespace IPS\gdrebates\modules\front\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _view extends \IPS\Dispatcher\Controller
{
	public function execute(): void
	{
		parent::execute();
	}

	protected function manage(): void
	{
		$member = \IPS\Member::loggedIn();
		$lang   = $member->language();

		$id = (int) ( \IPS\Request::i()->id ?? 0 );
		if ( $id <= 0 )
		{
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_err_rebate_not_found' ), '2GDR/10', 404 );
			return;
		}

		$rebate = null;
		try
		{
			$r = \IPS\Db::i()->select( '*', 'gd_rebates', [ 'id=?', $id ] )->first();
			if ( is_array( $r ) )
			{
				$rebate = $r;
			}
		}
		catch ( \Exception ) {}

		if ( $rebate === null )
		{
			\IPS\Output::i()->error( $lang->addToStack( 'gdr_err_rebate_not_found' ), '2GDR/11', 404 );
			return;
		}

		$rebate['days_left']    = self::daysLeft( (string) ( $rebate['end_date'] ?? '' ) );
		$rebate['status_label'] = self::statusLabel( $rebate );
		$rebate['steps_array']  = self::splitSteps( (string) ( $rebate['submission_steps'] ?? '' ) );

		$tracking = null;
		if ( $member->member_id )
		{
			$tracking = \IPS\gdrebates\Rebate\Tracking::findForMember( $id, (int) $member->member_id );
		}
		$metrics = \IPS\gdrebates\Rebate\Tracking::successMetrics( $id );

		$linkedProducts = [];
		$upcs = self::parseUpcs( (string) ( $rebate['eligible_upcs'] ?? '' ) );
		if ( count( $upcs ) > 0 )
		{
			try
			{
				foreach ( \IPS\Db::i()->select(
					'upc, title', 'gd_catalog',
					\IPS\Db::i()->in( 'upc', $upcs ), null, 20
				) as $p )
				{
					$linkedProducts[] = [
						'upc'   => (string) ( $p['upc'] ?? '' ),
						'title' => (string) ( $p['title'] ?? '' ),
					];
				}
			}
			catch ( \Exception ) {}
		}

		$data = [
			'rebate'          => $rebate,
			'tracking'        => $tracking,
			'metrics'         => $metrics,
			'linked_products' => $linkedProducts,
			'logged_in'       => (int) $member->member_id > 0,
			'track_url'       => (string) \IPS\Http\Url::internal(
				'app=gdrebates&module=rebates&controller=track&id=' . $id
			),
			'flag_url'        => (string) \IPS\Http\Url::internal(
				'app=gdrebates&module=rebates&controller=flag&id=' . $id
			),
			'hub_url'         => (string) \IPS\Http\Url::internal(
				'app=gdrebates&module=rebates&controller=hub'
			),
			'csrf_key'        => \IPS\Session::i()->csrfKey,
		];

		\IPS\Output::i()->title  = (string) ( $rebate['title'] ?? $lang->addToStack( 'gdr_front_view_title' ) );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'front' )->view( $data );
	}

	private static function daysLeft( string $end ): int
	{
		if ( $end === '' )
		{
			return 0;
		}
		$ts = strtotime( $end );
		if ( $ts === false )
		{
			return 0;
		}
		return (int) ceil( ( $ts - time() ) / 86400 );
	}

	/**
	 * @param array<string,mixed> $rebate
	 */
	private static function statusLabel( array $rebate ): string
	{
		$settings = \IPS\Settings::i();
		$status   = (string) ( $rebate['status'] ?? '' );
		if ( $status !== 'active' )
		{
			return $status;
		}
		$daysLeft = self::daysLeft( (string) ( $rebate['end_date'] ?? '' ) );
		$threshold = max( 1, (int) ( $settings->gdr_hub_expiring_days ?? 7 ) );
		if ( $daysLeft <= $threshold && $daysLeft >= 0 )
		{
			return 'expiring';
		}
		return 'active';
	}

	/**
	 * Split the submitter-entered steps string (one per line) into an
	 * array for ordered rendering.
	 *
	 * @return array<int,string>
	 */
	private static function splitSteps( string $raw ): array
	{
		if ( $raw === '' )
		{
			return [];
		}
		$lines = preg_split( '/\r\n|\r|\n/', $raw ) ?: [];
		$out   = [];
		foreach ( $lines as $line )
		{
			$l = trim( $line );
			if ( $l !== '' )
			{
				$out[] = $l;
			}
		}
		return $out;
	}

	/**
	 * @return array<int,string>
	 */
	private static function parseUpcs( string $raw ): array
	{
		if ( $raw === '' )
		{
			return [];
		}
		$parts = preg_split( '/[\s,;]+/', $raw ) ?: [];
		$out   = [];
		foreach ( $parts as $p )
		{
			$p = trim( $p );
			if ( $p !== '' && preg_match( '/^[0-9]{6,14}$/', $p ) )
			{
				$out[] = $p;
			}
		}
		return array_slice( array_unique( $out ), 0, 50 );
	}
}

class view extends _view {}
