<?php
/**
 * @brief       GD Dealer Manager - Feed Validator Endpoint
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       v1.0.139
 *
 * Public endpoint at /dealers/feed-validator. Dealers paste a feed body and
 * format, the endpoint parses + validates against the GunRack v1 schema,
 * and returns a JSON report. Pure validation - does NOT save anything to
 * the database, does NOT mutate listings, does NOT require a dealer login.
 *
 * GET  /dealers/feed-validator             - shows the upload form
 * POST /dealers/feed-validator?do=check    - returns JSON validation report
 *
 * Request body (POST):
 *   format=xml|json|csv
 *   body=<raw feed contents>
 *
 * Response (JSON):
 *   { valid: bool, summary: {...}, errors: [...], warnings: [...] }
 */

namespace IPS\gddealer\modules\front\dealers;

use IPS\gddealer\Feed\Parser\XmlParser;
use IPS\gddealer\Feed\Parser\JsonParser;
use IPS\gddealer\Feed\Parser\CsvParser;
use IPS\gddealer\Feed\Validator;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _feedvalidator extends \IPS\Dispatcher\Controller
{
    public static bool $csrfProtected = TRUE;

    public function execute(): void
    {
        parent::execute();
    }

    /**
     * GET /dealers/feed-validator - render the upload form.
     */
    protected function manage(): void
    {
        \IPS\Output::i()->title  = 'Feed validator';
        \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->feedValidator();
    }

    /**
     * POST /dealers/feed-validator?do=check - validate a pasted feed body.
     * Returns application/json regardless of success or failure.
     */
    protected function check(): void
    {
        header( 'Content-Type: application/json; charset=utf-8' );

        $format = strtolower( trim( (string) ( \IPS\Request::i()->format ?? '' ) ) );
        $body   = (string) ( \IPS\Request::i()->body ?? '' );
        $body   = trim( $body );

        if ( !in_array( $format, [ 'xml', 'json', 'csv' ], true ) )
        {
            echo json_encode( [
                'valid'    => false,
                'summary'  => [ 'total_records' => 0, 'valid_records' => 0, 'error_records' => 0, 'warning_records' => 0 ],
                'errors'   => [ [ 'row' => 0, 'upc' => '', 'field' => '_format', 'message' => "Format must be one of: xml, json, csv (got '{$format}')" ] ],
                'warnings' => [],
            ] );
            \IPS\Output::i()->sendOutput( '', 200, 'application/json' );
            return;
        }

        if ( $body === '' )
        {
            echo json_encode( [
                'valid'    => false,
                'summary'  => [ 'total_records' => 0, 'valid_records' => 0, 'error_records' => 0, 'warning_records' => 0 ],
                'errors'   => [ [ 'row' => 0, 'upc' => '', 'field' => '_body', 'message' => 'Feed body is empty.' ] ],
                'warnings' => [],
            ] );
            \IPS\Output::i()->sendOutput( '', 200, 'application/json' );
            return;
        }

        /* Body size guard - reject anything over 10 MB to prevent
           runaway parses on a public endpoint. */
        if ( strlen( $body ) > 10 * 1024 * 1024 )
        {
            echo json_encode( [
                'valid'    => false,
                'summary'  => [ 'total_records' => 0, 'valid_records' => 0, 'error_records' => 0, 'warning_records' => 0 ],
                'errors'   => [ [ 'row' => 0, 'upc' => '', 'field' => '_body', 'message' => 'Feed body exceeds 10 MB limit.' ] ],
                'warnings' => [],
            ] );
            \IPS\Output::i()->sendOutput( '', 200, 'application/json' );
            return;
        }

        try
        {
            $records = match ( $format ) {
                'xml'  => XmlParser::parse( $body ),
                'json' => JsonParser::parse( $body ),
                'csv'  => CsvParser::parse( $body ),
            };
        }
        catch ( \Throwable $e )
        {
            echo json_encode( [
                'valid'    => false,
                'summary'  => [ 'total_records' => 0, 'valid_records' => 0, 'error_records' => 0, 'warning_records' => 0 ],
                'errors'   => [ [ 'row' => 0, 'upc' => '', 'field' => '_parse', 'message' => 'Parse error: ' . $e->getMessage() ] ],
                'warnings' => [],
            ] );
            \IPS\Output::i()->sendOutput( '', 200, 'application/json' );
            return;
        }

        $report = Validator::validate( $records );

        echo json_encode( $report, JSON_UNESCAPED_SLASHES );
        \IPS\Output::i()->sendOutput( '', 200, 'application/json' );
    }
}

class feedvalidator extends _feedvalidator {}