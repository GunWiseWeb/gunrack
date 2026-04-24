<?php
/**
 * @brief  GD Dealer Manager — Verified Dealer Badge SVG serving
 *
 * Serves whitelisted SVG badges from interface/badges/ to dealers who want to
 * embed them on their websites. Pattern follows IPS core's
 * applications/core/interface/icons/icons.php — a standalone interface PHP
 * script (NOT a dispatched controller).
 *
 * Public URL:
 *   https://gunrack.deals/applications/gddealer/interface/badge/badge.php?id=chip-light-blue
 */

use IPS\Data\Store;
use IPS\Output;
use IPS\Request;

define( 'REPORT_EXCEPTIONS', TRUE );
require_once '../../../../init.php';

Output::i()->bypassDataLayer = true;

/* Whitelist — anything not in this list 404s. Defends against path traversal. */
$allowed = [
	'chip-light-blue', 'chip-light-green', 'chip-dark-blue', 'chip-dark-black',
	'bar-light-blue',  'bar-light-green',  'bar-dark-blue',  'bar-dark-black',
];

$id = (string) Request::i()->id;
/* Strip everything except a-z, 0-9, hyphen — defense in depth. */
$id = preg_replace( '/[^a-z0-9\-]/', '', $id );

/* 30-day cache headers. Same approach IPS uses for CustomBadge serving. */
$cacheHeaders = ( !\IPS\IN_DEV )
	? Output::getCacheHeaders( time(), 2592000 )
	: [];

if ( $id === '' || !in_array( $id, $allowed, TRUE ) )
{
	Output::i()->sendOutput( 'Badge not found', 404, 'text/plain', $cacheHeaders );
}

/* Serve from cache store if we previously read this file. */
$cacheKey = 'gddealer_badge_svg_' . $id;
try
{
	$cached = Store::i()->$cacheKey ?? null;
	if ( $cached !== null && $cached !== '' )
	{
		Output::i()->sendOutput( $cached, 200, 'image/svg+xml', $cacheHeaders );
	}
}
catch ( OutOfRangeException ) {}

/* Read from disk, cache, send. */
$path = \IPS\ROOT_PATH . '/applications/gddealer/interface/badges/' . $id . '.svg';
if ( !is_file( $path ) || !is_readable( $path ) )
{
	Output::i()->sendOutput( 'Badge file missing', 404, 'text/plain', $cacheHeaders );
}

$svg = (string) file_get_contents( $path );
if ( $svg === '' )
{
	Output::i()->sendOutput( 'Badge empty', 500, 'text/plain', $cacheHeaders );
}

try { Store::i()->$cacheKey = $svg; }
catch ( \Throwable ) {}

Output::i()->sendOutput( $svg, 200, 'image/svg+xml', $cacheHeaders );
