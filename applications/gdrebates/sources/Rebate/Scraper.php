<?php
/**
 * @brief       GD Rebates — Scraper engine
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Section 8.2 — nightly crawl of admin-registered manufacturer rebate
 * pages. Extraction is driven per-target by a JSON config (Section 8.2.2)
 * that maps page elements to rebate fields via CSS selectors.
 *
 * Contract (Section 8.2.3):
 *   1. Respect robots.txt — if crawl disallowed, skip + log robots_blocked.
 *   2. Fetch with the target's rate limit delay between requests.
 *   3. Extract all containers via config['rebate_container'] CSS selector.
 *   4. For each container, pull each configured field.
 *   5. Validate required fields (title, rebate_amount, end_date).
 *      Missing any of these = parse failure (do NOT create partial row).
 *   6. Compute dedup_hash and upsert into gd_rebates.
 *      - known manufacturer → status=active immediately
 *      - unknown manufacturer → status=pending (admin approval)
 *   7. Write a gd_scrape_log row summarising the run.
 *
 * IMPORTANT: per CLAUDE.md Rule #4, libxml_disable_entity_loader(true)
 * is called before every DOM parse. IPS 5.0.18 ships with libxml ≥2.9
 * which already disables entity loading, but the explicit call is the
 * audit-trail requirement.
 */

namespace IPS\gdrebates\Rebate;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Scraper
{
	/**
	 * Entry point called by tasks/scrapeRebates.php for a single target row.
	 *
	 * @param array<string,mixed> $target a gd_scrape_targets row
	 * @return array{status:string, found:int, created:int, updated:int, unchanged:int, failures:int, error:string}
	 */
	public static function runTarget( array $target ): array
	{
		$summary = [
			'status'    => 'success',
			'found'     => 0,
			'created'   => 0,
			'updated'   => 0,
			'unchanged' => 0,
			'failures'  => 0,
			'error'     => '',
		];

		$url  = (string) ( $target['scrape_url']  ?? '' );
		$mfr  = (string) ( $target['manufacturer'] ?? '' );
		if ( $url === '' || $mfr === '' )
		{
			$summary['status'] = 'failed';
			$summary['error']  = 'Target missing url or manufacturer';
			return $summary;
		}

		$settings  = \IPS\Settings::i();
		$userAgent = (string) ( $settings->gdr_scraper_user_agent ?? 'GunRackDealsBot/1.0' );
		$timeout   = max( 1, (int) ( $settings->gdr_scraper_timeout ?? 15 ) );
		$respect   = (int) ( $settings->gdr_scraper_respect_robots ?? 1 ) === 1;

		if ( $respect && !self::robotsAllow( $url, $userAgent, $timeout ) )
		{
			$summary['status'] = 'robots_blocked';
			$summary['error']  = 'robots.txt disallows crawling';
			return $summary;
		}

		$rateMs = max( 0, (int) ( $target['rate_limit_ms'] ?? 2000 ) );
		if ( $rateMs > 0 )
		{
			usleep( $rateMs * 1000 );
		}

		$html = self::fetch( $url, $userAgent, $timeout );
		if ( $html === null )
		{
			$summary['status'] = 'failed';
			$summary['error']  = 'HTTP fetch failed';
			return $summary;
		}

		$config = Target::decodeExtractionConfig( (string) ( $target['extraction_config'] ?? '' ) );
		if ( $config === null )
		{
			$summary['status'] = 'failed';
			$summary['error']  = 'Extraction config is not valid JSON';
			return $summary;
		}

		$candidates = self::extract( $html, $config );
		$summary['found'] = count( $candidates );

		$isKnown = (int) ( $target['is_known'] ?? 0 ) === 1;

		foreach ( $candidates as $c )
		{
			if ( !self::validate( $c ) )
			{
				$summary['failures']++;
				continue;
			}

			$action = self::upsert( $c, $mfr, (string) ( $target['brand'] ?? $mfr ), $isKnown );
			if ( $action === 'created' )        { $summary['created']++; }
			else if ( $action === 'updated' )   { $summary['updated']++; }
			else                                { $summary['unchanged']++; }
		}

		if ( $summary['failures'] > 0 && $summary['created'] + $summary['updated'] + $summary['unchanged'] > 0 )
		{
			$summary['status'] = 'partial';
		}
		else if ( $summary['failures'] > 0 )
		{
			$summary['status'] = 'failed';
		}

		return $summary;
	}

	/**
	 * Check robots.txt for the host of $url. Returns TRUE if crawling is
	 * allowed for this path + user agent, FALSE if explicitly disallowed.
	 * Absent/unreadable robots.txt is treated as allowed (standard RFC
	 * 9309 behaviour).
	 */
	private static function robotsAllow( string $url, string $userAgent, int $timeout ): bool
	{
		$p = parse_url( $url );
		if ( !is_array( $p ) || empty( $p['scheme'] ) || empty( $p['host'] ) )
		{
			return false;
		}
		$robotsUrl = $p['scheme'] . '://' . $p['host'] . '/robots.txt';
		$path      = $p['path'] ?? '/';

		$body = self::fetch( $robotsUrl, $userAgent, $timeout );
		if ( $body === null || $body === '' )
		{
			return true;
		}

		$uaLower   = strtolower( $userAgent );
		$lines     = preg_split( '/\r\n|\r|\n/', $body ) ?: [];
		$groupUAs  = [];
		$groupRules = [];
		$currentUAs = [];
		$currentRules = [];

		foreach ( $lines as $raw )
		{
			$line = trim( $raw );
			if ( $line === '' || $line[0] === '#' )
			{
				continue;
			}
			$parts = explode( ':', $line, 2 );
			if ( count( $parts ) !== 2 )
			{
				continue;
			}
			$key = strtolower( trim( $parts[0] ) );
			$val = trim( $parts[1] );

			if ( $key === 'user-agent' )
			{
				if ( !empty( $currentRules ) )
				{
					$groupUAs[]   = $currentUAs;
					$groupRules[] = $currentRules;
					$currentUAs   = [];
					$currentRules = [];
				}
				$currentUAs[] = strtolower( $val );
			}
			else if ( $key === 'disallow' || $key === 'allow' )
			{
				$currentRules[] = [ $key, $val ];
			}
		}
		if ( !empty( $currentUAs ) )
		{
			$groupUAs[]   = $currentUAs;
			$groupRules[] = $currentRules;
		}

		$matchedRules = [];
		foreach ( $groupUAs as $i => $uas )
		{
			foreach ( $uas as $ua )
			{
				if ( $ua === '*' || strpos( $uaLower, $ua ) !== false )
				{
					$matchedRules = array_merge( $matchedRules, $groupRules[ $i ] );
				}
			}
		}
		if ( empty( $matchedRules ) )
		{
			return true;
		}

		foreach ( $matchedRules as $rule )
		{
			[ $key, $val ] = $rule;
			if ( $val === '' )
			{
				continue;
			}
			if ( strpos( $path, $val ) === 0 )
			{
				return $key === 'allow';
			}
		}
		return true;
	}

	/**
	 * Simple HTTP GET via file_get_contents with a stream context so we
	 * get User-Agent + timeout. Returns body string or NULL on failure.
	 * Intentionally minimal — install-time doesn't have guaranteed curl.
	 */
	private static function fetch( string $url, string $userAgent, int $timeout ): ?string
	{
		$ctx = stream_context_create( [
			'http' => [
				'method'        => 'GET',
				'header'        => "User-Agent: {$userAgent}\r\nAccept: text/html,application/xhtml+xml\r\n",
				'timeout'       => $timeout,
				'ignore_errors' => true,
				'max_redirects' => 5,
			],
			'https' => [
				'method'        => 'GET',
				'header'        => "User-Agent: {$userAgent}\r\nAccept: text/html,application/xhtml+xml\r\n",
				'timeout'       => $timeout,
				'ignore_errors' => true,
				'max_redirects' => 5,
			],
		] );

		$body = @file_get_contents( $url, false, $ctx );
		if ( $body === false )
		{
			return null;
		}
		return $body;
	}

	/**
	 * Parse $html with DOMDocument and extract candidate rebate arrays
	 * per the extraction config. Supports CSS-style selectors via a
	 * small translator to XPath plus native XPath for advanced cases.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function extract( string $html, array $config ): array
	{
		$containerSel = (string) ( $config['rebate_container'] ?? '' );
		$fieldMap     = is_array( $config['fields'] ?? null ) ? $config['fields'] : [];
		if ( $containerSel === '' || empty( $fieldMap ) )
		{
			return [];
		}

		if ( function_exists( 'libxml_disable_entity_loader' ) )
		{
			libxml_disable_entity_loader( true );
		}
		$prev = libxml_use_internal_errors( true );

		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		$xp = new \DOMXPath( $dom );
		$containerXp = self::cssToXpath( $containerSel );
		if ( $containerXp === null )
		{
			return [];
		}
		$containers = $xp->query( $containerXp );
		if ( !$containers )
		{
			return [];
		}

		$out = [];
		foreach ( $containers as $container )
		{
			$row = [];
			foreach ( $fieldMap as $fieldName => $spec )
			{
				if ( !is_array( $spec ) )
				{
					continue;
				}
				$sel  = (string) ( $spec['selector'] ?? '' );
				$type = (string) ( $spec['type']     ?? 'text' );
				if ( $sel === '' )
				{
					continue;
				}
				$relativeXp = self::cssToXpath( $sel, true );
				if ( $relativeXp === null )
				{
					continue;
				}
				$nodes = $xp->query( $relativeXp, $container );
				if ( !$nodes || $nodes->length === 0 )
				{
					continue;
				}
				$node  = $nodes->item( 0 );
				$value = self::nodeValue( $node, $type );

				if ( $type === 'text' && isset( $spec['map'] ) && is_array( $spec['map'] ) )
				{
					$mapped = $spec['map'][ $value ] ?? null;
					if ( is_string( $mapped ) )
					{
						$value = $mapped;
					}
				}
				else if ( $type === 'currency' )
				{
					$value = self::parseCurrency( (string) $value );
				}
				else if ( $type === 'date' )
				{
					$value = self::parseDate( (string) $value, (string) ( $spec['format'] ?? 'Y-m-d' ) );
				}

				$row[ (string) $fieldName ] = $value;
			}
			if ( !empty( $row ) )
			{
				$out[] = $row;
			}
		}
		return $out;
	}

	/**
	 * Translate a subset of CSS selectors to XPath.
	 * Supports:  tag, .class, #id, tag.class, tag#id, descendant combinator (space).
	 * Returns NULL if the selector uses syntax outside that subset —
	 * extraction config should use supported selectors only.
	 */
	private static function cssToXpath( string $css, bool $relative = false ): ?string
	{
		$css = trim( $css );
		if ( $css === '' )
		{
			return null;
		}
		$parts = preg_split( '/\s+/', $css ) ?: [];
		$xpath = $relative ? './/' : '//';
		$first = true;
		foreach ( $parts as $p )
		{
			if ( !preg_match( '/^([a-zA-Z][a-zA-Z0-9_-]*)?(?:([.#])([a-zA-Z0-9_-]+))?$/', $p, $m ) )
			{
				return null;
			}
			$tag  = $m[1] !== '' ? $m[1] : '*';
			$pred = '';
			if ( isset( $m[2] ) && $m[2] !== '' )
			{
				$attr = $m[2] === '.' ? 'class' : 'id';
				$val  = $m[3];
				if ( $attr === 'class' )
				{
					$pred = "[contains(concat(' ', normalize-space(@class), ' '), ' {$val} ')]";
				}
				else
				{
					$pred = "[@id='{$val}']";
				}
			}
			$xpath .= ( $first ? '' : '//' ) . $tag . $pred;
			$first = false;
		}
		return $xpath;
	}

	/**
	 * Pull the requested representation out of a DOM node.
	 *
	 * @param \DOMNode|null $node
	 * @return string
	 */
	private static function nodeValue( ?\DOMNode $node, string $type ): string
	{
		if ( $node === null )
		{
			return '';
		}
		if ( $type === 'href' && $node instanceof \DOMElement )
		{
			return (string) $node->getAttribute( 'href' );
		}
		if ( $type === 'html_to_text' )
		{
			$txt = (string) $node->textContent;
			return trim( preg_replace( '/\s+/', ' ', $txt ) ?? '' );
		}
		return trim( (string) $node->textContent );
	}

	/**
	 * Parse a currency-ish string ("$25.00", "25 USD", "$1,250") → float.
	 * Returns 0.0 if nothing numeric found — callers decide if that is a
	 * parse failure (typical: rebate_amount=0 counts as a failure).
	 */
	private static function parseCurrency( string $raw ): float
	{
		if ( preg_match( '/([0-9]+(?:[.,][0-9]+)?)/', str_replace( ',', '', $raw ), $m ) )
		{
			return (float) $m[1];
		}
		return 0.0;
	}

	/**
	 * Parse a date using the configured format. Returns ISO (Y-m-d) on
	 * success or '' on failure. Supports the MM/DD/YYYY style from the
	 * spec sample config plus a handful of common variants.
	 */
	private static function parseDate( string $raw, string $format ): string
	{
		$raw = trim( $raw );
		if ( $raw === '' )
		{
			return '';
		}
		$phpFormat = str_replace(
			[ 'YYYY', 'MM', 'DD' ], [ 'Y', 'm', 'd' ], $format
		);
		$dt = \DateTime::createFromFormat( $phpFormat, $raw );
		if ( $dt instanceof \DateTime )
		{
			return $dt->format( 'Y-m-d' );
		}
		$ts = strtotime( $raw );
		if ( $ts !== false )
		{
			return date( 'Y-m-d', $ts );
		}
		return '';
	}

	/**
	 * Required fields are present and parseable (Section 8.2.3 contract).
	 *
	 * @param array<string,mixed> $c
	 */
	private static function validate( array $c ): bool
	{
		$title    = (string) ( $c['title']         ?? '' );
		$amount   =           $c['rebate_amount'] ?? null;
		$endDate  = (string) ( $c['end_date']      ?? '' );
		if ( $title === '' || $endDate === '' )
		{
			return false;
		}
		if ( !is_numeric( $amount ) || (float) $amount <= 0 )
		{
			return false;
		}
		return true;
	}

	/**
	 * Insert a new rebate row or update an existing one by dedup_hash.
	 * Returns 'created' | 'updated' | 'unchanged'.
	 */
	private static function upsert( array $c, string $manufacturer, string $brand, bool $isKnown ): string
	{
		$title     = (string) ( $c['title'] ?? '' );
		$amount    = isset( $c['rebate_amount'] ) ? (float) $c['rebate_amount'] : null;
		$endDate   = (string) ( $c['end_date']   ?? '' );
		$startDate = (string) ( $c['start_date'] ?? '' );
		$deadline  = (string) ( $c['submission_deadline'] ?? '' );

		$hash = Rebate::dedupHash( $manufacturer, $title, $endDate, $amount );

		$existing = Rebate::findByDedupHash( $hash );
		$now      = date( 'Y-m-d H:i:s' );

		$row = [
			'manufacturer'        => mb_substr( $manufacturer, 0, 150 ),
			'brand'               => mb_substr( $brand !== '' ? $brand : $manufacturer, 0, 150 ),
			'title'               => mb_substr( $title, 0, 255 ),
			'description'         => (string) ( $c['description'] ?? '' ),
			'rebate_amount'       => $amount,
			'rebate_type'         => in_array( (string) ( $c['rebate_type'] ?? '' ), Rebate::rebateTypes(), true )
				? (string) $c['rebate_type']
				: 'mail_in',
			'product_type'        => in_array( (string) ( $c['product_type'] ?? '' ), Rebate::productTypes(), true )
				? (string) $c['product_type']
				: 'any',
			'eligible_upcs'       => isset( $c['eligible_upcs'] )   ? (string) $c['eligible_upcs']   : null,
			'eligible_models'     => isset( $c['eligible_models'] ) ? (string) $c['eligible_models'] : null,
			'start_date'          => $startDate !== '' ? $startDate : null,
			'end_date'            => $endDate,
			'submission_deadline' => $deadline !== '' ? $deadline : null,
			'rebate_form_url'     => isset( $c['rebate_form_url'] ) ? mb_substr( (string) $c['rebate_form_url'], 0, 500 ) : null,
			'rebate_pdf_url'      => isset( $c['rebate_pdf_url'] )  ? mb_substr( (string) $c['rebate_pdf_url'], 0, 500 )  : null,
			'manufacturer_url'    => isset( $c['manufacturer_url'] ) ? mb_substr( (string) $c['manufacturer_url'], 0, 500 ) : null,
			'submission_steps'    => isset( $c['submission_steps'] ) ? (string) $c['submission_steps'] : null,
			'dedup_hash'          => $hash,
			'submitted_by'        => 0,
		];

		try
		{
			if ( $existing )
			{
				$changed = false;
				foreach ( $row as $k => $v )
				{
					$old = $existing[ $k ] ?? null;
					if ( (string) $old !== (string) $v )
					{
						$changed = true;
						break;
					}
				}
				if ( !$changed )
				{
					return 'unchanged';
				}
				if ( (string) ( $existing['source'] ?? '' ) === 'community' )
				{
					$row['source'] = 'scraped_community';
				}
				\IPS\Db::i()->update( 'gd_rebates', $row, [ 'id=?', (int) $existing['id'] ] );
				return 'updated';
			}

			$row['source']     = 'scraped';
			$row['status']     = $isKnown ? 'active' : 'pending';
			$row['created_at'] = $now;
			\IPS\Db::i()->insert( 'gd_rebates', $row );
			return 'created';
		}
		catch ( \Exception )
		{
			return 'unchanged';
		}
	}
}

class Scraper extends _Scraper {}
