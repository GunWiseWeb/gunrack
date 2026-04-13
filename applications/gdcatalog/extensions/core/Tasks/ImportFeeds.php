<?php
/**
 * @brief       GD Master Catalog — ImportFeeds Scheduled Task
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Runs every 15 minutes. Checks each active feed's schedule and
 * executes the import for any feeds that are due.
 */

namespace IPS\gdcatalog\extensions\core\Tasks;

use IPS\gdcatalog\Feed\Distributor;
use IPS\gdcatalog\Feed\Importer;

class _ImportFeeds extends \IPS\Task
{
	/**
	 * Execute the task.
	 *
	 * @return string|null  Message to log, or NULL
	 */
	public function execute(): ?string
	{
		$feeds   = Distributor::loadActive();
		$ran     = 0;
		$errors  = [];

		foreach ( $feeds as $feed )
		{
			/* Skip feeds that aren't due or are already running */
			if ( !$feed->isDue() || $feed->isRunning() )
			{
				continue;
			}

			try
			{
				$log = Importer::run( $feed );
				$ran++;

				if ( $log->status === 'failed' )
				{
					$errors[] = $feed->feed_name . ': ' . ( $log->error_log ?? 'unknown error' );
				}
			}
			catch ( \Exception $e )
			{
				$errors[] = $feed->feed_name . ': ' . $e->getMessage();
				$feed->markFailed();
			}
		}

		if ( !empty( $errors ) )
		{
			\IPS\Log::log( implode( "\n", $errors ), 'gdcatalog_import' );
		}

		return $ran > 0
			? "Ran {$ran} feed import(s)" . ( !empty( $errors ) ? ' with ' . \count( $errors ) . ' error(s)' : '' )
			: null;
	}
}
