<?php
/**
 * @brief  EditorLocations extension for dealer reviews, responses, and disputes.
 *
 * Shared extension covering every rich-text field stored in gd_dealer_ratings:
 *   id2 = 1  review_body
 *   id2 = 2  dealer_response
 *   id2 = 3  dispute_reason
 *   id2 = 4  dispute_evidence
 *   id2 = 5  customer_response
 *   id2 = 6  customer_evidence
 *
 * The editor $area string for Parser::parseStatic is "gddealer_Responses".
 * autoSaveKey convention: "gddealer-<field>-<reviewId>".
 * attachIds convention: [ (int) $reviewId, (int) $fieldHint ].
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
	 * build a back-link in the ACP attachment browser. We return the
	 * public dealer profile URL keyed off gd_dealer_ratings.id (id1).
	 */
	public function attachmentLookup( ?int $id1=NULL, ?int $id2=NULL, ?string $id3=NULL ): Model|Content|Url|Member|null
	{
		if ( !$id1 )
		{
			return NULL;
		}

		try
		{
			$row    = Db::i()->select( '*', 'gd_dealer_ratings', [ 'id=?', $id1 ] )->first();
			$slug   = (string) Db::i()->select( 'dealer_slug', 'gd_dealer_feed_config', [ 'dealer_id=?', (int) $row['dealer_id'] ] )->first();
			if ( $slug === '' )
			{
				return NULL;
			}
			return Url::internal( 'app=gddealer&module=dealers&controller=profile&dealer_slug=' . urlencode( $slug ) );
		}
		catch ( \Exception )
		{
			return NULL;
		}
	}
}
