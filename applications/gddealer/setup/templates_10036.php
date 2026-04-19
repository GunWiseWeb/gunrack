<?php
/**
 * v1.0.36 template re-seed: the dashboard reviews template (and sibling
 * profile templates) were outputting editor-stored HTML without the
 * |raw filter, so IPS's auto-escape turned <p> tags into literal text.
 *
 * templates_10031.php is the source of truth for the three front
 * dealers/* review-rendering templates (dealerReviews, dealerProfile,
 * editReview). It was updated in v1.0.36 to use |raw on the last
 * remaining escaped render. Re-running it with \IPS\Db::i()->replace()
 * guarantees existing installs pick up the fix.
 */

namespace IPS\gddealer\setup\templates_10036;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10031.php';
