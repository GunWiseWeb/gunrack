<?php
/**
 * @brief EditorLocations extension for all gddealer rich-text fields.
 *
 * The editor $area string for Parser::parseStatic is "gddealer_Responses".
 *
 * id2 field hint conventions:
 *   1  review_body            -> gd_dealer_ratings
 *   2  dealer_response        -> gd_dealer_ratings
 *   3  dispute_reason         -> gd_dealer_ratings
 *   4  dispute_evidence       -> gd_dealer_ratings
 *   5  customer_response      -> gd_dealer_ratings
 *   6  customer_evidence      -> gd_dealer_ratings
 *   10 ticket_body            -> gd_dealer_support_tickets
 *   11 ticket_reply           -> gd_dealer_support_tickets (shared by dealer + admin + stock-posted replies)
 *   12 hidden_staff_note      -> gd_dealer_support_tickets
 *   20 stock_reply_body       -> gd_dealer_support_stock_replies
 *   21 stock_action_reply     -> gd_dealer_support_stock_actions
 *
 * autoSaveKey convention: "gddealer-<field>-<rowId>"
 * attachIds convention:  [ (int) $rowId, (int) $fieldHint ]
 */

namespace IPS\gddealer\extensions\core\EditorLocations;

use IPS\Content;
use IPS\Db;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Responses extends EditorLocationsAbstract
{
	/**
	 * Whether this member may attach files in this editor. NULL defers
	 * to the member's group permissions (core_groups.g_attach_max).
	 */
	public function canAttach( Member $member, Editor $field ): ?bool
	{
		return NULL;
	}

	/**
	 * All attachments on dealer reviews / responses / disputes are shown
	 * publicly on the dealer profile, so anyone who can load the page can
	 * view them.
	 */
	public function attachmentPermissionCheck( Member $member, ?int $id1, ?int $id2, ?string $id3, array $attachment, bool $viewOnly=FALSE ): bool
	{
		return TRUE;
	}

	/**
	 * Return an object representing the attached-to content so IPS can
	 * build a back-link in the ACP attachment browser AND so the Parser
	 * can resolve <fileStore.core_Attachment> tokens at render time.
	 *
	 * Branch on id2 because the id1 row lives in different tables for
	 * reviews vs. support tickets vs. stock replies/actions.
	 */
	public function attachmentLookup( ?int $id1=NULL, ?int $id2=NULL, ?string $id3=NULL ): Model|Content|Url|Member|null
	{
		if ( !$id1 )
		{
			return NULL;
		}

		try
		{
			/* Reviews / responses / disputes — id1 is a gd_dealer_ratings row. */
			if ( $id2 >= 1 && $id2 <= 6 )
			{
				$row  = Db::i()->select( '*', 'gd_dealer_ratings', [ 'id=?', $id1 ] )->first();
				$slug = (string) Db::i()->select( 'dealer_slug', 'gd_dealer_feed_config',
					[ 'dealer_id=?', (int) $row['dealer_id'] ]
				)->first();
				if ( $slug === '' )
				{
					return NULL;
				}
				return Url::internal( 'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ) );
			}

			/* Support tickets — id1 is a gd_dealer_support_tickets row. */
			if ( $id2 >= 10 && $id2 <= 12 )
			{
				try
				{
					Db::i()->select( 'id', 'gd_dealer_support_tickets', [ 'id=?', $id1 ] )->first();
				}
				catch ( \Exception )
				{
					return NULL;
				}
				return Url::internal( 'app=gddealer&module=dealers&controller=support&do=view&id=' . $id1 );
			}

			/* Stock replies — id1 is a gd_dealer_support_stock_replies row. */
			if ( $id2 === 20 )
			{
				try
				{
					Db::i()->select( 'id', 'gd_dealer_support_stock_replies', [ 'id=?', $id1 ] )->first();
				}
				catch ( \Exception )
				{
					return NULL;
				}
				return Url::internal( 'app=gddealer&module=dealers&controller=stockreplies&do=form&id=' . $id1, 'admin' );
			}

			/* Stock actions — id1 is a gd_dealer_support_stock_actions row. */
			if ( $id2 === 21 )
			{
				try
				{
					Db::i()->select( 'id', 'gd_dealer_support_stock_actions', [ 'id=?', $id1 ] )->first();
				}
				catch ( \Exception )
				{
					return NULL;
				}
				return Url::internal( 'app=gddealer&module=dealers&controller=stockactions&do=form&id=' . $id1, 'admin' );
			}
		}
		catch ( \Exception )
		{
		}

		return NULL;
	}
}
