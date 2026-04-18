<?php
/**
 * Upgrade steps for gddealer v1.0.1 (version integer 10001).
 *
 * Schema additions (via queries.json):
 *   - gd_dealer_feed_config:  dealer_slug, trial_expires_at, billing_note,
 *                             dealer_dashboard_prefs, disputes_suspended
 *                             + uq_dealer_slug unique index
 *   - gd_dealer_ratings:      full dispute workflow columns (status, reason,
 *                             evidence, deadlines, resolution) + idx_dispute_status
 *   - gd_dealer_dispute_counts: new monthly per-dealer dispute counter table
 *
 * Data migration here: backfill dealer_slug for any dealer row missing one.
 * Uses the same slug algorithm as manualOnboard() in the ACP controller.
 */

namespace IPS\gddealer\setup\upg_10001;

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
		try
		{
			foreach ( \IPS\Db::i()->select( 'dealer_id, dealer_name', 'gd_dealer_feed_config',
				[ 'dealer_slug IS NULL OR dealer_slug=?', '' ]
			) as $row )
			{
				$slug = strtolower( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $row['dealer_name'] ) ) );
				$slug = trim( $slug, '-' ) ?: ( 'dealer-' . (int) $row['dealer_id'] );

				$base = $slug;
				$i    = 1;
				while ( (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config',
					[ 'dealer_slug=? AND dealer_id<>?', $slug, (int) $row['dealer_id'] ]
				)->first() > 0 )
				{
					$slug = $base . '-' . $i++;
				}

				\IPS\Db::i()->update( 'gd_dealer_feed_config',
					[ 'dealer_slug' => $slug ],
					[ 'dealer_id=?', (int) $row['dealer_id'] ]
				);
			}
		}
		catch ( \Exception ) {}

		return TRUE;
	}

	public function step2(): bool
	{
		require \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10001.php';

		return TRUE;
	}
}

class upgrade extends _upgrade {}
