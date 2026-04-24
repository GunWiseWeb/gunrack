<?php
namespace IPS\gddealer\extensions\core\FileStorage;

use UnderflowException;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _FeedUpload
{
	public function count(): int
	{
		return (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_uploads' )->first();
	}

	public function get( int $offset ): void
	{
		try
		{
			$row = \IPS\Db::i()->select( '*', 'gd_dealer_feed_uploads', NULL, 'upload_id ASC', [ $offset, 1 ] )->first();
			\IPS\File::get( 'gddealer_FeedUpload', $row['file_url'] )->move( 'gddealer_FeedUpload' );
		}
		catch ( \Exception $e )
		{
			throw new UnderflowException;
		}
	}

	public function isValidFile( string $file ): bool
	{
		try
		{
			$row = \IPS\Db::i()->select( '*', 'gd_dealer_feed_uploads', [ 'file_url=?', (string) $file ] )->first();
			return TRUE;
		}
		catch ( \Exception )
		{
			return FALSE;
		}
	}

	public function delete(): void
	{
		foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_feed_uploads' ) as $row )
		{
			try { \IPS\File::get( 'gddealer_FeedUpload', $row['file_url'] )->delete(); }
			catch ( \Exception ) {}
		}
	}
}
class FeedUpload extends _FeedUpload {}
