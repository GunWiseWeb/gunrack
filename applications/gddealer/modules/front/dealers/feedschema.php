<?php
/**
 * @brief       GD Dealer Manager - Public Feed Schema Documentation
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       v1.0.146
 *
 * Public-facing schema reference at /dealers/feed-schema. No login required.
 * Shows the v1.1 feed schema with field tables, validation rules, and
 * working XML/JSON/CSV examples covering all 8 categories. Modeled on
 * the gunengine.com feed documentation page.
 *
 * This is documentation only - no forms, no state, no input. The validator
 * itself is at /dealers/feed-validator (dealer-only).
 */

namespace IPS\gddealer\modules\front\dealers;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _feedschema extends \IPS\Dispatcher\Controller
{
    public static bool $csrfProtected = TRUE;

    public function execute(): void
    {
        parent::execute();
    }

    /**
     * Render the schema documentation page.
     */
    protected function manage(): void
    {
        \IPS\Output::i()->title  = 'Feed Schema v1.1 - GunRack Dealer Documentation';
        \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'dealers', 'gddealer', 'front' )->feedSchema();
    }
}

class feedschema extends _feedschema {}
