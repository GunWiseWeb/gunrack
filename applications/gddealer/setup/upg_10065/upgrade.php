<?php
/**
 * v1.0.65: Stop re-parsing stored bodies at render. Parser::parseStatic
 * was being invoked on already-parsed content (with <fileStore> tokens),
 * which HTMLPurifier treated as broken tags and stripped the src.
 * Removed render-time parseStatic from support + profile controllers;
 * stored bodies now pass through as-is, and IPS's output layer expands
 * <fileStore.core_Attachment> tokens to real URLs.
 */

namespace IPS\gddealer\setup\upg_10065;

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
		try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
