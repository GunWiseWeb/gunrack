<?php
namespace IPS\gddealer\extensions\core\ContentRouter;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class DealerFollow
{
	public array $classes = [];

	public function __construct()
	{
		$this->classes = [ 'IPS\gddealer\Dealer\Dealer' ];
	}
}
