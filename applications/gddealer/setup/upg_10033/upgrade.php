<?php
/**
 * v1.0.33: self-heal extensions.json if IPS's datastore-to-disk
 * writeback stripped the EditorLocations registration during a
 * concurrent-request race with the v1.0.31/32 upgrade.
 */

namespace IPS\gddealer\setup\upg_10033;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _upgrade
{
	public function step1(): bool
	{
		$path = \IPS\ROOT_PATH . '/applications/gddealer/data/extensions.json';

		try
		{
			if ( file_exists( $path ) )
			{
				$current = json_decode( file_get_contents( $path ), TRUE );
				if ( !is_array( $current ) ) { $current = []; }
				$current['core'] = $current['core'] ?? [];

				$required = [
					'ContentRouter'    => [ 'DealerFollow'        => 'IPS\\gddealer\\extensions\\core\\ContentRouter\\DealerFollow' ],
					'Notifications'    => [ 'DealerNotifications' => 'IPS\\gddealer\\extensions\\core\\Notifications\\DealerNotifications' ],
					'EmailTemplates'   => [ 'DealerEmails'        => 'IPS\\gddealer\\extensions\\core\\EmailTemplates\\DealerEmails' ],
					'FrontNavigation'  => [ 'DealerNav'           => 'IPS\\gddealer\\extensions\\core\\FrontNavigation\\DealerNav' ],
					'EditorLocations'  => [ 'Responses'           => 'IPS\\gddealer\\extensions\\core\\EditorLocations\\Responses' ],
				];

				$changed = FALSE;
				foreach ( $required as $type => $entries )
				{
					if ( !isset( $current['core'][ $type ] ) )
					{
						$current['core'][ $type ] = [];
					}
					foreach ( $entries as $name => $class )
					{
						if ( ( $current['core'][ $type ][ $name ] ?? NULL ) !== $class )
						{
							$current['core'][ $type ][ $name ] = $class;
							$changed = TRUE;
						}
					}
				}

				if ( $changed )
				{
					file_put_contents(
						$path,
						json_encode( $current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
					);
				}
			}
		}
		catch ( \Exception ) {}

		try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
