<?php
/**
 * @brief       GD Dealer Manager - Feed Validator
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       v1.0.139
 *
 * Validates a parsed GunRack feed (XML / JSON / CSV after going through the
 * format-specific parser) against the v1 schema. Returns a structured report
 * with errors (block import) and warnings (allow with caution).
 *
 * Schema reference: see _design/feed-schema-v1.md in the repo for the full
 * documented schema. This class is the authoritative implementation; if the
 * doc and this class disagree, this class wins.
 *
 * Required per-listing fields:
 *   upc       - 12 or 13 digit numeric (UPC-A or EAN-13)
 *   category  - one of: firearm, ammo, part, accessory, optic, reloading, knife, apparel
 *   price     - positive decimal, USD
 *   condition - one of: new, used, refurbished
 *   url       - absolute https URL
 *   shipping_cost - present when free_shipping is not 1
 *   free_shipping - 0 or 1 (truthy parseable)
 *   in_stock  - 0 or 1 (truthy parseable)
 *
 * Optional:
 *   sku       - dealer SKU, max 100 chars
 *   map_price - positive decimal, must be > price when present
 *   stock_qty - non-negative integer
 */

namespace IPS\gddealer\Feed;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class Validator
{
    public const VALID_CATEGORIES = [
        'firearm', 'ammo', 'part', 'accessory',
        'optic', 'reloading', 'knife', 'apparel',
    ];

    public const VALID_CONDITIONS = [ 'new', 'used', 'refurbished' ];

    public const REQUIRED_FIELDS = [ 'upc', 'category', 'price', 'condition', 'url' ];

    /**
     * Validate a list of parsed records (from XmlParser/JsonParser/CsvParser).
     *
     * @param array<int, array<string, mixed>> $records
     * @return array{
     *   valid: bool,
     *   summary: array{total_records:int, valid_records:int, error_records:int, warning_records:int},
     *   errors: array<int, array{row:int, upc:string, field:string, message:string}>,
     *   warnings: array<int, array{row:int, upc:string, field:string, message:string}>
     * }
     */
    public static function validate( array $records ): array
    {
        $errors   = [];
        $warnings = [];
        $errorRows   = [];
        $warningRows = [];

        if ( count( $records ) === 0 )
        {
            return [
                'valid'   => false,
                'summary' => [ 'total_records' => 0, 'valid_records' => 0, 'error_records' => 0, 'warning_records' => 0 ],
                'errors'  => [ [ 'row' => 0, 'upc' => '', 'field' => '_root', 'message' => 'Feed contains zero listings.' ] ],
                'warnings' => [],
            ];
        }

        $seenUpcs = [];

        foreach ( $records as $idx => $record )
        {
            $row = $idx + 1;
            $upc = isset( $record['upc'] ) ? (string) $record['upc'] : '';
            $rowHadError   = false;
            $rowHadWarning = false;

            /* required field presence */
            foreach ( self::REQUIRED_FIELDS as $field )
            {
                if ( !array_key_exists( $field, $record ) || (string) $record[ $field ] === '' )
                {
                    $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => $field, 'message' => "Missing required field: {$field}" ];
                    $rowHadError = true;
                }
            }

            /* upc format: 12 (UPC-A) or 13 (EAN-13) digits, no separators */
            if ( $upc !== '' )
            {
                $cleaned = preg_replace( '/[^0-9]/', '', $upc );
                if ( $cleaned !== $upc )
                {
                    $warnings[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'upc', 'message' => 'UPC contains non-digit characters; will be stripped on import.' ];
                    $rowHadWarning = true;
                }
                $len = strlen( $cleaned );
                if ( $len !== 12 && $len !== 13 )
                {
                    $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'upc', 'message' => "UPC must be 12 or 13 digits (got {$len})." ];
                    $rowHadError = true;
                }
                if ( isset( $seenUpcs[ $cleaned ] ) )
                {
                    $warnings[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'upc', 'message' => "Duplicate UPC; previously seen on row {$seenUpcs[$cleaned]}. Last occurrence wins on import." ];
                    $rowHadWarning = true;
                }
                else
                {
                    $seenUpcs[ $cleaned ] = $row;
                }
            }

            /* category */
            if ( isset( $record['category'] ) && (string) $record['category'] !== '' )
            {
                $cat = strtolower( trim( (string) $record['category'] ) );
                if ( !in_array( $cat, self::VALID_CATEGORIES, true ) )
                {
                    $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'category', 'message' => "Invalid category '{$cat}'. Must be one of: " . implode( ', ', self::VALID_CATEGORIES ) ];
                    $rowHadError = true;
                }
            }

            /* price */
            if ( isset( $record['price'] ) && (string) $record['price'] !== '' )
            {
                $rawPrice = (string) $record['price'];
                $price    = (float) preg_replace( '/[^0-9.]/', '', $rawPrice );
                if ( $price <= 0 )
                {
                    $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'price', 'message' => "Price must be greater than 0 (got '{$rawPrice}')." ];
                    $rowHadError = true;
                }
                if ( $price > 100000 )
                {
                    $warnings[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'price', 'message' => "Price '{$rawPrice}' exceeds \$100,000 sanity check." ];
                    $rowHadWarning = true;
                }
            }

            /* map_price (optional) */
            if ( isset( $record['map_price'] ) && (string) $record['map_price'] !== '' )
            {
                $rawMap = (string) $record['map_price'];
                $map    = (float) preg_replace( '/[^0-9.]/', '', $rawMap );
                if ( $map <= 0 )
                {
                    $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'map_price', 'message' => "MAP price must be greater than 0 (got '{$rawMap}')." ];
                    $rowHadError = true;
                }
                if ( isset( $price ) && $price > 0 && $map <= $price )
                {
                    $warnings[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'map_price', 'message' => "MAP price ({$map}) is not greater than price ({$price}); MAP will be ignored on import." ];
                    $rowHadWarning = true;
                }
            }

            /* condition */
            if ( isset( $record['condition'] ) && (string) $record['condition'] !== '' )
            {
                $cond = strtolower( trim( (string) $record['condition'] ) );
                if ( !in_array( $cond, self::VALID_CONDITIONS, true ) )
                {
                    $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'condition', 'message' => "Invalid condition '{$cond}'. Must be one of: " . implode( ', ', self::VALID_CONDITIONS ) ];
                    $rowHadError = true;
                }
            }

            /* url - https only, must be absolute */
            if ( isset( $record['url'] ) && (string) $record['url'] !== '' )
            {
                $url = (string) $record['url'];
                if ( !preg_match( '#^https://#i', $url ) )
                {
                    $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'url', 'message' => "URL must start with https:// (got '{$url}')." ];
                    $rowHadError = true;
                }
                elseif ( filter_var( $url, FILTER_VALIDATE_URL ) === false )
                {
                    $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'url', 'message' => "URL is malformed: '{$url}'" ];
                    $rowHadError = true;
                }
            }

            /* free_shipping + shipping_cost */
            $free = isset( $record['free_shipping'] ) ? self::truthy( $record['free_shipping'] ) : false;
            if ( !$free )
            {
                if ( !isset( $record['shipping_cost'] ) || (string) $record['shipping_cost'] === '' )
                {
                    $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'shipping_cost', 'message' => 'shipping_cost is required when free_shipping is not 1/true.' ];
                    $rowHadError = true;
                }
                else
                {
                    $rawShip = (string) $record['shipping_cost'];
                    $ship    = (float) preg_replace( '/[^0-9.]/', '', $rawShip );
                    if ( $ship < 0 )
                    {
                        $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'shipping_cost', 'message' => "shipping_cost must be >= 0 (got '{$rawShip}')." ];
                        $rowHadError = true;
                    }
                }
            }

            /* in_stock */
            if ( isset( $record['in_stock'] ) && (string) $record['in_stock'] !== '' )
            {
                /* truthy() accepts 1/0/true/false/yes/no/in_stock/out_of_stock - if it can't parse, that's a warning */
                if ( !self::truthyParseable( $record['in_stock'] ) )
                {
                    $warnings[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'in_stock', 'message' => "Ambiguous in_stock value '" . $record['in_stock'] . "'; will be treated as truthy on import." ];
                    $rowHadWarning = true;
                }
            }

            /* stock_qty (optional) */
            if ( isset( $record['stock_qty'] ) && (string) $record['stock_qty'] !== '' )
            {
                $qty = (string) $record['stock_qty'];
                if ( !ctype_digit( $qty ) )
                {
                    $warnings[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'stock_qty', 'message' => "stock_qty should be a non-negative integer (got '{$qty}')." ];
                    $rowHadWarning = true;
                }
            }

            /* sku length */
            if ( isset( $record['sku'] ) && strlen( (string) $record['sku'] ) > 100 )
            {
                $errors[] = [ 'row' => $row, 'upc' => $upc, 'field' => 'sku', 'message' => 'sku must be 100 characters or fewer.' ];
                $rowHadError = true;
            }

            if ( $rowHadError )   { $errorRows[ $row ]   = true; }
            if ( $rowHadWarning ) { $warningRows[ $row ] = true; }
        }

        $errorCount   = count( $errorRows );
        $warningCount = count( $warningRows );
        $total        = count( $records );

        return [
            'valid'   => $errorCount === 0,
            'summary' => [
                'total_records'   => $total,
                'valid_records'   => $total - $errorCount,
                'error_records'   => $errorCount,
                'warning_records' => $warningCount,
            ],
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parse a known truthy/falsy string. Returns true if value indicates
     * presence/availability/yes/1/in_stock.
     */
    protected static function truthy( mixed $v ): bool
    {
        if ( is_bool( $v ) ) { return $v; }
        if ( is_int( $v ) )  { return $v > 0; }
        $s = strtolower( trim( (string) $v ) );
        return in_array( $s, [ '1', 'true', 'yes', 'y', 'in_stock', 'instock', 'available', 'on' ], true );
    }

    /**
     * Returns true if value is one of our recognized truthy/falsy strings.
     * Used to flag ambiguous values like '3' or 'maybe' as warnings.
     */
    protected static function truthyParseable( mixed $v ): bool
    {
        if ( is_bool( $v ) || is_int( $v ) ) { return true; }
        $s = strtolower( trim( (string) $v ) );
        return in_array( $s, [
            '1', '0', 'true', 'false', 'yes', 'no', 'y', 'n',
            'in_stock', 'out_of_stock', 'instock', 'outofstock',
            'available', 'unavailable', 'on', 'off', '',
        ], true );
    }
}
