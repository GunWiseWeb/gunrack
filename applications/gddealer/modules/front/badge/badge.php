<?php
namespace IPS\gddealer\modules\front\badge;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _badge extends \IPS\Dispatcher\Controller
{
	protected static array $allowedBadges = [
		'chip-light-blue', 'chip-light-green', 'chip-dark-blue', 'chip-dark-black',
		'bar-light-blue',  'bar-light-green',  'bar-dark-blue',  'bar-dark-black',
	];

	public function execute(): void
	{
		parent::execute();
	}

	protected function manage(): void
	{
		$id = (string) ( \IPS\Request::i()->id ?? '' );
		$id = preg_replace( '/[^a-z0-9\-]/', '', $id );

		if ( $id === '' || !in_array( $id, self::$allowedBadges, TRUE ) )
		{
			\IPS\Output::i()->sendOutput( 'Badge not found', 404, 'text/plain' );
			return;
		}

		$path = \IPS\ROOT_PATH . '/applications/gddealer/interface/badges/' . $id . '.svg';
		if ( !is_file( $path ) || !is_readable( $path ) )
		{
			\IPS\Output::i()->sendOutput( 'Badge file missing', 404, 'text/plain' );
			return;
		}

		$svg = (string) file_get_contents( $path );
		if ( $svg === '' )
		{
			\IPS\Output::i()->sendOutput( 'Badge empty', 500, 'text/plain' );
			return;
		}

		\IPS\Output::i()->sendOutput(
			$svg,
			200,
			'image/svg+xml',
			[
				'Cache-Control: public, max-age=2592000, immutable',
				'Access-Control-Allow-Origin: *',
				'X-Content-Type-Options: nosniff',
			]
		);
	}

	protected function serve(): void
	{
		$this->manage();
	}
}

class badge extends _badge {}
