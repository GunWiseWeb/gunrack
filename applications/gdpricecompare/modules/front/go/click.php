<?php
/**
 * @brief       GD Price Comparison — Outbound click redirector
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 *
 * /go/{listing-id} — logs the click then 302-redirects to the dealer URL.
 */

namespace IPS\gdpricecompare\modules\front\go;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _click extends \IPS\Dispatcher\Controller
{
	public function execute(): void
	{
		parent::execute();
	}

	protected function manage(): void
	{
		$listingId = (int) \IPS\Request::i()->id;
		if ( $listingId <= 0 )
		{
			\IPS\Output::i()->error( 'gdpc_err_no_listing', '2GDPC/3', 404, '' );
			return;
		}

		try
		{
			$listing = \IPS\Db::i()->select(
				'id, upc, dealer_id, dealer_url', 'gd_dealer_listings', [ 'id=?', $listingId ]
			)->first();
		}
		catch ( \Exception )
		{
			\IPS\Output::i()->error( 'gdpc_err_no_listing', '2GDPC/4', 404, '' );
			return;
		}

		$member    = \IPS\Member::loggedIn();
		$memberId  = $member->member_id ? (int) $member->member_id : null;
		$userState = null;
		if ( !empty( $_COOKIE['gdpc_state'] ) )
		{
			$userState = strtoupper( substr( (string) $_COOKIE['gdpc_state'], 0, 2 ) );
		}

		try
		{
			\IPS\gdpricecompare\Click\ClickLog::record(
				(int) $listing['id'],
				(string) $listing['upc'],
				(int) $listing['dealer_id'],
				$memberId,
				$userState
			);
		}
		catch ( \Exception ) {}

		$target = (string) ( $listing['dealer_url'] ?? '' );
		if ( $target === '' || !self::isSafeUrl( $target ) )
		{
			\IPS\Output::i()->error( 'gdpc_err_no_listing', '2GDPC/5', 404, '' );
			return;
		}

		\IPS\Output::i()->redirect( \IPS\Http\Url::external( $target ) );
	}

	private static function isSafeUrl( string $url ): bool
	{
		$parts = parse_url( $url );
		if ( !$parts || empty( $parts['scheme'] ) || empty( $parts['host'] ) )
		{
			return false;
		}
		$scheme = strtolower( (string) $parts['scheme'] );
		return $scheme === 'http' || $scheme === 'https';
	}
}

class click extends _click {}
