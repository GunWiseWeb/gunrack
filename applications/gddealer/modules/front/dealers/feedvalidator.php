<?php
/**
 * @brief       GD Dealer Manager - Feed Validator Endpoint
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       v1.0.139
 * @updated     v1.0.145 - Now requires dealer login and renders inside the
 *              dealer dashboard shell (sidebar + main area) instead of as
 *              a standalone page.
 *
 * GET  /dealers/feed-validator             - shows the upload form (in shell)
 * POST /dealers/feed-validator?do=check    - returns JSON validation report
 *
 * Both endpoints require the user to be a logged-in dealer. Public visitors
 * are redirected to /dealers/join.
 *
 * Request body (POST):
 *   format=xml|json|csv
 *   body=<raw feed contents>
 *
 * Response (JSON):
 *   { valid: bool, summary: {...}, errors: [...], warnings: [...] }
 */

namespace IPS\gddealer\modules\front\dealers;

use IPS\gddealer\Dealer\Dealer;
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
    use \IPS\gddealer\Traits\DealerShellTrait;

    public static bool $csrfProtected = TRUE;

    /** Current dealer loaded from the logged-in member */
    protected ?Dealer $dealer = null;

    public function execute(): void
    {
        $member = \IPS\Member::loggedIn();

        if ( !$member->member_id )
        {
            \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join' ) );
            return;
        }

        if ( $member->isAdmin() )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dealers', 'admin' )
            );
            return;
        }

        try
        {
            $this->dealer = Dealer::load( (int) $member->member_id );
        }
        catch ( \OutOfRangeException )
        {
            $this->dealer = null;
        }

        /* Member is in a dealer group but hasn't completed registration. */
        if ( $this->dealer === null && Dealer::isDealerMember( $member ) )
        {
            \IPS\Output::i()->redirect(
                \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join&do=register' )
            );
            return;
        }

        /* Not a dealer at all - kick to the join page. */
        if ( $this->dealer === null )
        {
            \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=join' ) );
            return;
        }

        parent::execute();
    }

    /**
     * GET /dealers/feed-validator - render the upload form inside the
     * dealer dashboard shell (sidebar + main area).
     */
    protected function manage(): void
    {
        $body = (string) \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->feedValidator();
        $this->output( 'feedValidator', $body );
    }

    /**
     * POST /dealers/feed-validator?do=check - validate a pasted feed body.
     * Returns application/json regardless of success or failure. This
     * endpoint does NOT render the dashboard shell - it's a pure JSON
     * response consumed by the validator page's fetch() call.
     */
    protected function check(): void
    {
        $format = strtolower( trim( (string) ( \IPS\Request::i()->format ?? '' ) ) );
        $body   = (string) ( \IPS\Request::i()->body ?? '' );
        $body   = trim( $body );

        if ( !in_array( $format, [ 'xml', 'json', 'csv' ], true ) )
        {
            \IPS\Output::i()->sendOutput( json_encode( [
                'valid'    => false,
                'summary'  => [ 'total_records' => 0, 'valid_records' => 0, 'error_records' => 0, 'warning_records' => 0 ],
                'errors'   => [ [ 'row' => 0, 'upc' => '', 'field' => '_format', 'message' => "Format must be one of: xml, json, csv (got '{$format}')" ] ],
                'warnings' => [],
            ] ), 200, 'application/json' );
            return;
        }

        if ( $body === '' )
        {
            \IPS\Output::i()->sendOutput( json_encode( [
                'valid'    => false,
                'summary'  => [ 'total_records' => 0, 'valid_records' => 0, 'error_records' => 0, 'warning_records' => 0 ],
                'errors'   => [ [ 'row' => 0, 'upc' => '', 'field' => '_body', 'message' => 'Feed body is empty.' ] ],
                'warnings' => [],
            ] ), 200, 'application/json' );
            return;
        }

        if ( strlen( $body ) > 10 * 1024 * 1024 )
        {
            \IPS\Output::i()->sendOutput( json_encode( [
                'valid'    => false,
                'summary'  => [ 'total_records' => 0, 'valid_records' => 0, 'error_records' => 0, 'warning_records' => 0 ],
                'errors'   => [ [ 'row' => 0, 'upc' => '', 'field' => '_body', 'message' => 'Feed body exceeds 10 MB limit.' ] ],
                'warnings' => [],
            ] ), 200, 'application/json' );
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
            \IPS\Output::i()->sendOutput( json_encode( [
                'valid'    => false,
                'summary'  => [ 'total_records' => 0, 'valid_records' => 0, 'error_records' => 0, 'warning_records' => 0 ],
                'errors'   => [ [ 'row' => 0, 'upc' => '', 'field' => '_parse', 'message' => 'Parse error: ' . $e->getMessage() ] ],
                'warnings' => [],
            ] ), 200, 'application/json' );
            return;
        }

        $report = Validator::validate( $records );
        \IPS\Output::i()->sendOutput( json_encode( $report, JSON_UNESCAPED_SLASHES ), 200, 'application/json' );
    }
}

class feedvalidator extends _feedvalidator {}
