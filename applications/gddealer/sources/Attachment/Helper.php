<?php
namespace IPS\gddealer\Attachment;

use IPS\Db;
use IPS\File;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Helper
{
	/**
	 * Get all attachments linked to a given gddealer field.
	 *
	 * @param int $reviewId  gd_dealer_ratings.id
	 * @param int $fieldHint 1=review_body, 2=dealer_response, 3=dispute_reason,
	 *                       4=dispute_evidence, 5=customer_response, 6=customer_evidence
	 * @return array of [ id, file_name, file_size, is_image, url, thumb_url ]
	 */
	public static function getAttachments( int $reviewId, int $fieldHint ): array
	{
		$rows = [];
		try
		{
			$select = Db::i()->select(
				'a.attach_id, a.attach_file, a.attach_filesize, a.attach_is_image, a.attach_location, a.attach_thumb_location',
				[ 'core_attachments', 'a' ],
				[
					'm.location_key=? AND m.id1=? AND m.id2=?',
					'gddealer_Responses', $reviewId, $fieldHint
				]
			);
			$select->join(
				[ 'core_attachments_map', 'm' ],
				'a.attach_id = m.attachment_id'
			);

			foreach ( $select as $att )
			{
				$url = '';
				try
				{
					$url = (string) File::get( 'core_Attachment', $att['attach_location'] )->url;
				}
				catch ( \Exception ) {}

				$thumbUrl = '';
				if ( (int) $att['attach_is_image'] && $att['attach_thumb_location'] )
				{
					try
					{
						$thumbUrl = (string) File::get( 'core_Attachment', $att['attach_thumb_location'] )->url;
					}
					catch ( \Exception )
					{
						$thumbUrl = $url;
					}
				}

				if ( $url === '' )
				{
					continue;
				}

				$rows[] = [
					'id'        => (int) $att['attach_id'],
					'file_name' => (string) $att['attach_file'],
					'file_size' => (int) $att['attach_filesize'],
					'is_image'  => (bool) $att['attach_is_image'],
					'url'       => $url,
					'thumb_url' => $thumbUrl,
				];
			}
		}
		catch ( \Exception ) {}

		return $rows;
	}
}

class Helper extends _Helper {}
