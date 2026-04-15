<?php
/**
 * @brief       GD Product Reviews — OpenSearch indexer (stub)
 * @package     IPS Community Suite
 * @subpackage  GD Product Reviews
 * @since       15 Apr 2026
 *
 * TODO (Section 7.7): the spec section "OpenSearch Index — Reviews" is
 * currently blank. Once the mapping is defined, this task should page
 * through gd_reviews rows with status = 'approved' that have been updated
 * since the last successful run and push them into the reviews index
 * alongside the catalog documents (Section 7.6 refers to this as the
 * backing store for hub filtering, brand pages, and category pages).
 *
 * Until then this task is a no-op. It exists so ACP > System > Scheduled
 * Tasks shows the entry at install time and the hourly slot is claimed.
 *
 * IMPORTANT: do NOT add live OpenSearch HTTP calls to this stub. Per
 * CLAUDE.md Rule #8, the same principle that bars synchronous OpenSearch
 * calls from dashboard controllers applies to install-time tasks that
 * may be queued on a fresh system where the cluster is not yet reachable.
 */

namespace IPS\gdreviews\tasks;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _indexReviews extends \IPS\Task
{
	/**
	 * Stub execute — see class-level TODO. Returns null to signal success
	 * so the scheduler does not record a failure against the task row.
	 */
	public function execute()
	{
		/* No-op stub. Implementation deferred until Section 7.7 is filled in. */
		return null;
	}
}

class indexReviews extends _indexReviews {}
