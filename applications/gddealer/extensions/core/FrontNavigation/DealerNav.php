<?php
/**
 * @brief       GD Dealer Manager — Front Navigation Extension
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       16 Apr 2026
 *
 * Adds a "Dealer Dashboard" link to the user dropdown menu for
 * members in the configured Dealers group.
 */

namespace IPS\gddealer\extensions\core\FrontNavigation;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class DealerNav extends \IPS\core\FrontNavigation\FrontNavigationAbstract
{
	public static function configuration(): array
	{
		return [];
	}

	public static function title(): string
	{
		return \IPS\Member::loggedIn()->language()->addToStack( 'gddealer_nav_dashboard' );
	}

	public static function link(): ?\IPS\Http\Url
	{
		return \IPS\Http\Url::internal( 'app=gddealer&module=dealers&controller=dashboard', 'front', 'dealers_dashboard' );
	}

	public static function showLink(): bool
	{
		$member = \IPS\Member::loggedIn();
		if ( !$member->member_id )
		{
			return false;
		}

		// Check by configured group ID first
		$dealerGroupId = (int) \IPS\Settings::i()->gddealer_member_group_id;
		if ( $dealerGroupId > 0 )
		{
			if ( (int) $member->member_group_id === $dealerGroupId )
			{
				return true;
			}
			$others = array_filter( array_map( 'intval', explode( ',', (string) $member->mgroup_others ) ) );
			if ( in_array( $dealerGroupId, $others, true ) )
			{
				return true;
			}
		}

		// Fallback — check if member has a row in gd_dealer_feed_config
		try
		{
			$count = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config',
				[ 'dealer_id=? AND active=?', (int) $member->member_id, 1 ] )->first();
			return $count > 0;
		}
		catch ( \Exception )
		{
			return false;
		}
	}

	public static function permissionCheck( \IPS\Member $member ): bool
	{
		return static::showLink();
	}

	public function children( bool $noStore = false ): ?array
	{
		return null;
	}
}
