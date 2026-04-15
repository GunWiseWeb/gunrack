<?php
/**
 * @brief       GD Price Comparison — FFL locator front controller
 * @package     IPS Community Suite
 * @subpackage  GD Price Comparison
 * @since       15 Apr 2026
 */

namespace IPS\gdpricecompare\modules\front\ffl;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _locator extends \IPS\Dispatcher\Controller
{
	public function execute(): void
	{
		parent::execute();
	}

	protected function manage(): void
	{
		$zip     = (string) \IPS\Request::i()->zip;
		$radius  = (int) \IPS\Request::i()->radius;
		if ( $radius <= 0 )
		{
			$radius = (int) ( \IPS\Settings::i()->gdpc_ffl_radius_default ?? 25 );
		}

		$results = [];
		$center  = null;

		if ( $zip !== '' )
		{
			$center = self::centroidForZip( $zip );
			if ( $center !== null )
			{
				$results = \IPS\gdpricecompare\Ffl\FflDealer::nearby(
					$center['lat'], $center['lng'], $radius, 10
				);
			}
		}

		$data = [
			'zip'       => $zip,
			'radius'    => $radius,
			'center'    => $center,
			'results'   => $results,
			'gmaps_key' => (string) ( \IPS\Settings::i()->gdpc_google_maps_api_key ?? '' ),
		];

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdpc_front_ffl_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'pricecompare', 'gdpricecompare', 'front' )
			->ffl( $data );
	}

	/**
	 * Resolve a ZIP to an approximate lat/lng. Uses the first FFL record in
	 * that ZIP as a proxy centroid — avoids a third-party geocoding dependency
	 * at launch. Returns null if ZIP is unknown.
	 *
	 * @return array{lat:float, lng:float}|null
	 */
	private static function centroidForZip( string $zip ): ?array
	{
		$zip = preg_replace( '/[^0-9]/', '', $zip );
		if ( strlen( $zip ) < 5 )
		{
			return null;
		}
		$zip = substr( $zip, 0, 5 );

		try
		{
			$row = \IPS\Db::i()->select(
				'lat, lng', 'gd_ffl_dealers',
				[ 'premise_zip=? AND lat IS NOT NULL AND lng IS NOT NULL', $zip ]
			)->first();
			return [ 'lat' => (float) $row['lat'], 'lng' => (float) $row['lng'] ];
		}
		catch ( \Exception )
		{
			return null;
		}
	}
}

class locator extends _locator {}
