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
	/* Whitelist of badge IDs we will actually serve. Anything else 404s. */
	protected static array $allowedBadges = [
		'chip-light-blue', 'chip-light-green', 'chip-dark-blue', 'chip-dark-black',
		'bar-light-blue',  'bar-light-green',  'bar-dark-blue',  'bar-dark-black',
	];

	public function execute(): void
	{
		parent::execute();
	}

	/**
	 * Default action — serve the requested badge SVG.
	 */
	protected function manage(): void
	{
		$id = (string) ( \IPS\Request::i()->id ?? '' );
		/* Strip everything except a-z, 0-9, hyphen. Defeats path traversal early. */
		$id = preg_replace( '/[^a-z0-9\-]/', '', $id );

		if ( $id === '' || !in_array( $id, self::$allowedBadges, TRUE ) )
		{
			$this->sendNotFound();
			return;
		}

		$path = \IPS\ROOT_PATH . '/applications/gddealer/interface/badges/' . $id . '.svg';
		if ( !is_file( $path ) || !is_readable( $path ) )
		{
			$this->sendNotFound();
			return;
		}

		$svg = (string) file_get_contents( $path );
		if ( $svg === '' )
		{
			$this->sendNotFound();
			return;
		}

		/* Bypass IPS Output entirely. sendOutput() runs parseOutputForDisplay()
		   on non-JSON/JS/CSS bodies, which mangles SVG XML and empties our
		   response (verified by reading system/Output/Output.php:1053). Bare
		   header()+echo+exit is the cleanest path for static binary assets. */
		while ( ob_get_level() > 0 ) { @ob_end_clean(); }

		header_remove( 'Set-Cookie' );
		header( 'Content-Type: image/svg+xml; charset=utf-8' );
		header( 'Content-Length: ' . strlen( $svg ) );
		header( 'Cache-Control: public, max-age=2592000, immutable' );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'X-Content-Type-Options: nosniff' );
		echo $svg;
		exit;
	}

	/**
	 * Friendly URL action: /dealers/badge/{id}.svg routes here.
	 */
	protected function serve(): void
	{
		$this->manage();
	}

	/**
	 * Send a clean 404 without going through IPS error templates (which would
	 * also try to render HTML, doubling the response, etc).
	 */
	protected function sendNotFound(): void
	{
		while ( ob_get_level() > 0 ) { @ob_end_clean(); }
		header_remove( 'Set-Cookie' );
		header( 'HTTP/1.1 404 Not Found' );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'Badge not found';
		exit;
	}
}

class badge extends _badge {}
