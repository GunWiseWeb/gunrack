<?php
/**
 * @brief       GD Dealer Manager - Server-side feed fetcher
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       v1.0.150
 *
 * Fetches a dealer's feed URL with proper auth handling, timeouts, and
 * size limits. Returns a structured response with HTTP metadata, body,
 * and any error encountered. The caller (the wizard step 2 handler)
 * decides what to display.
 *
 * Designed for the wizard's "test fetch" step - NOT for production
 * importer use. Production fetching is handled by the import task.
 */

namespace IPS\gddealer\Feed;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class FeedFetcher
{
    /**
     * Maximum response body we'll accept. Larger feeds get truncated
     * and a warning is set. The wizard's parse step is a sanity check,
     * not a full import - dealers don't need to test their entire 50MB
     * feed at this stage.
     */
    public const MAX_BODY_BYTES = 10 * 1024 * 1024;  /* 10 MB */

    /**
     * Connection timeout in seconds.
     */
    public const TIMEOUT_SECONDS = 15;

    /**
     * Fetch a URL and return a structured response array.
     *
     * @param string $url       Feed URL (must be http:// or https://)
     * @param string $authType  'none' | 'basic' | 'api_key'
     * @param string $authCreds Auth credentials JSON string. For basic:
     *                          {"username":"u","password":"p"}. For api_key:
     *                          {"api_key":"k"} or {"api_key":"k","header":"X-Custom"}.
     *                          Default header for api_key is "X-API-Key".
     *
     * @return array{
     *     ok: bool,
     *     http_status: int,
     *     content_type: string,
     *     body_bytes: int,
     *     body: string,
     *     truncated: bool,
     *     headers: array<string, string>,
     *     duration_ms: int,
     *     error: ?string,
     * }
     */
    public static function fetch( string $url, string $authType = 'none', string $authCreds = '' ): array
    {
        $result = [
            'ok'           => false,
            'http_status'  => 0,
            'content_type' => '',
            'body_bytes'   => 0,
            'body'         => '',
            'truncated'    => false,
            'headers'      => [],
            'duration_ms'  => 0,
            'error'        => null,
        ];

        if ( !preg_match( '#^https?://#i', $url ) )
        {
            $result['error'] = 'URL must start with http:// or https://';
            return $result;
        }

        if ( !function_exists( 'curl_init' ) )
        {
            $result['error'] = 'PHP cURL extension is not available on this server.';
            return $result;
        }

        $ch = curl_init();
        if ( $ch === false )
        {
            $result['error'] = 'Could not initialize cURL.';
            return $result;
        }

        /* Capture response headers separately. */
        $headerLines = [];

        curl_setopt_array( $ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => self::TIMEOUT_SECONDS,
            CURLOPT_USERAGENT      => 'GunRack-Dealer-Wizard/1.0 (+https://gunrack.deals)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_BUFFERSIZE     => 65536,
            CURLOPT_HEADERFUNCTION => function ( $ch, $line ) use ( &$headerLines )
            {
                $headerLines[] = $line;
                return strlen( $line );
            },
            /* Hard size cap during transfer. cURL aborts the transfer
             * when the callback returns less than expected. */
            CURLOPT_WRITEFUNCTION  => self::makeWriteCallback( $result ),
        ] );

        /* Configure auth. */
        $authError = self::applyAuth( $ch, $authType, $authCreds );
        if ( $authError !== null )
        {
            $result['error'] = $authError;
            curl_close( $ch );
            return $result;
        }

        $start = microtime( true );
        $execResult = curl_exec( $ch );
        $duration = (int) round( ( microtime( true ) - $start ) * 1000 );
        $errno = curl_errno( $ch );
        $errstr = curl_error( $ch );
        $httpStatus = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
        $contentType = (string) curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
        curl_close( $ch );

        $result['duration_ms']  = $duration;
        $result['http_status']  = $httpStatus;
        $result['content_type'] = $contentType;
        $result['body_bytes']   = strlen( $result['body'] );
        $result['headers']      = self::parseHeaderLines( $headerLines );

        /* WriteFunction-aborted transfers manifest as CURLE_WRITE_ERROR
         * (errno 23). When that's because we hit the size cap, treat
         * it as a successful-with-truncation, not a failure. */
        if ( $errno !== 0 && !( $errno === 23 && $result['truncated'] ) )
        {
            $result['error'] = 'Fetch error: ' . ( $errstr !== '' ? $errstr : 'cURL errno ' . $errno );
            return $result;
        }

        if ( $httpStatus < 200 || $httpStatus >= 300 )
        {
            $result['error'] = 'HTTP ' . $httpStatus . ' response from server.';
            /* Body is still populated - keep it so the dealer can see
             * the error page their server returned. */
            return $result;
        }

        if ( $result['body_bytes'] === 0 )
        {
            $result['error'] = 'Server returned an empty response body.';
            return $result;
        }

        $result['ok'] = true;
        return $result;
    }

    /**
     * Apply auth headers / userpwd to a cURL handle based on auth type.
     * Returns an error string on bad credentials, or null on success.
     */
    protected static function applyAuth( $ch, string $authType, string $authCreds ): ?string
    {
        $authType = strtolower( trim( $authType ) );
        if ( $authType === '' || $authType === 'none' )
        {
            return null;
        }

        $authCreds = trim( $authCreds );
        if ( $authCreds === '' )
        {
            return 'Auth credentials are empty but auth type is "' . $authType . '".';
        }

        $decoded = json_decode( $authCreds, true );
        if ( !is_array( $decoded ) )
        {
            return 'Auth credentials must be valid JSON, e.g. {"username":"u","password":"p"} or {"api_key":"k"}.';
        }

        if ( $authType === 'basic' )
        {
            $u = isset( $decoded['username'] ) ? (string) $decoded['username'] : '';
            $p = isset( $decoded['password'] ) ? (string) $decoded['password'] : '';
            if ( $u === '' || $p === '' )
            {
                return 'Basic auth requires both "username" and "password" in the credentials JSON.';
            }
            curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt( $ch, CURLOPT_USERPWD, $u . ':' . $p );
            return null;
        }

        if ( $authType === 'api_key' )
        {
            $k = isset( $decoded['api_key'] ) ? (string) $decoded['api_key'] : '';
            if ( $k === '' )
            {
                return 'API key auth requires an "api_key" in the credentials JSON.';
            }
            $header = isset( $decoded['header'] ) && (string) $decoded['header'] !== ''
                ? (string) $decoded['header']
                : 'X-API-Key';
            curl_setopt( $ch, CURLOPT_HTTPHEADER, [ $header . ': ' . $k ] );
            return null;
        }

        return 'Unknown auth type: "' . $authType . '". Use none, basic, or api_key.';
    }

    /**
     * Build a write callback that appends to $result['body'] until the
     * size cap is hit, then returns 0 to abort the transfer.
     */
    protected static function makeWriteCallback( array &$result ): \Closure
    {
        return function ( $ch, $chunk ) use ( &$result )
        {
            $remaining = self::MAX_BODY_BYTES - strlen( $result['body'] );
            if ( $remaining <= 0 )
            {
                $result['truncated'] = true;
                return 0;  /* abort */
            }

            if ( strlen( $chunk ) > $remaining )
            {
                $result['body']     .= substr( $chunk, 0, $remaining );
                $result['truncated'] = true;
                return 0;  /* abort - we have what we asked for */
            }

            $result['body'] .= $chunk;
            return strlen( $chunk );
        };
    }

    /**
     * Parse raw header lines into an associative array. Last-occurrence
     * wins for duplicate header names.
     *
     * @param array<int, string> $lines
     * @return array<string, string>
     */
    protected static function parseHeaderLines( array $lines ): array
    {
        $out = [];
        foreach ( $lines as $line )
        {
            $line = rtrim( $line );
            if ( $line === '' || str_starts_with( $line, 'HTTP/' ) ) { continue; }
            $colonPos = strpos( $line, ':' );
            if ( $colonPos === false ) { continue; }
            $name  = trim( substr( $line, 0, $colonPos ) );
            $value = trim( substr( $line, $colonPos + 1 ) );
            if ( $name === '' ) { continue; }
            $out[ $name ] = $value;
        }
        return $out;
    }

    /**
     * Discover unique field names from an array of parsed records.
     * Records are flat associative arrays (the parsers always produce
     * flat dotted-key records). Returns fields ordered by frequency
     * (most-common first), with frequency counts.
     *
     * @param array<int, array<string, mixed>> $records
     * @return array<int, array{field: string, count: int, sample: string}>
     */
    public static function discoverFields( array $records ): array
    {
        $counts  = [];
        $samples = [];
        foreach ( $records as $rec )
        {
            if ( !is_array( $rec ) ) { continue; }
            foreach ( $rec as $field => $value )
            {
                $field = (string) $field;
                if ( !isset( $counts[ $field ] ) )
                {
                    $counts[ $field ] = 0;
                    $samples[ $field ] = '';
                }
                $counts[ $field ]++;
                /* Capture first non-empty value as the sample. */
                if ( $samples[ $field ] === '' && $value !== null && (string) $value !== '' )
                {
                    $sample = (string) $value;
                    if ( strlen( $sample ) > 80 )
                    {
                        $sample = substr( $sample, 0, 77 ) . '...';
                    }
                    $samples[ $field ] = $sample;
                }
            }
        }

        arsort( $counts );  /* highest count first */

        $out = [];
        foreach ( $counts as $field => $count )
        {
            $out[] = [
                'field'  => $field,
                'count'  => $count,
                'sample' => $samples[ $field ] ?? '',
            ];
        }
        return $out;
    }
}
