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

		$dealerGroupId = (int) \IPS\Settings::i()->gddealer_member_group_id;
		if ( $dealerGroupId <= 0 )
		{
			return false;
		}

		$others = array_filter( array_map( 'intval', explode( ',', (string) $member->mgroup_others ) ) );
		return in_array( $dealerGroupId, $others, true )
			|| (int) $member->member_group_id === $dealerGroupId;
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
