<?php
/**
 * @brief  GD Dealer Manager — FrontNavigation extension
 *
 * Adds an optional menu item that links to the Dealer Dashboard.
 * Visible only to members who are active dealers.
 */

namespace IPS\gddealer\extensions\core\FrontNavigation;

use IPS\Http\Url;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class DealerNav extends \IPS\core\FrontNavigation\FrontNavigationAbstract
{
	/**
	 * The label that appears in the AdminCP "Create menu item" type dropdown.
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack( 'gddealer_nav_dashboard' );
	}

	/**
	 * Configuration form for this menu type. We have no custom config —
	 * return empty defaults so the form has something to render.
	 */
	public static function configuration( array $existingConfiguration, ?int $id = NULL ): array
	{
		return [];
	}

	/**
	 * Display label of the rendered menu item.
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack( 'gddealer_nav_dashboard' );
	}

	/**
	 * URL the menu item links to.
	 */
	public function link(): Url|string|null
	{
		return Url::internal( 'app=gddealer&module=dealers&controller=dashboard', 'front', 'dealers_dashboard' );
	}

	/**
	 * Whether this menu item should appear "active" on the current page.
	 */
	public function active(): bool
	{
		try
		{
			return \IPS\Dispatcher::hasInstance()
				&& \IPS\Dispatcher::i()->application
				&& \IPS\Dispatcher::i()->application->directory === 'gddealer';
		}
		catch ( \Exception )
		{
			return false;
		}
	}

	/**
	 * Visibility rule. IPS calls this per-request to decide whether to
	 * render the menu item for the current member. Hide it for members
	 * who aren't dealers.
	 */
	public function canView(): bool
	{
		$member = Member::loggedIn();
		if ( !$member->member_id )
		{
			return false;
		}

		try
		{
			if ( class_exists( '\\IPS\\gddealer\\Dealer\\Dealer' )
				&& \IPS\gddealer\Dealer\Dealer::isDealerMember( $member ) )
			{
				return true;
			}
		}
		catch ( \Exception ) {}

		try
		{
			$count = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_feed_config',
				[ 'dealer_id=? AND active=?', (int) $member->member_id, 1 ]
			)->first();
			return $count > 0;
		}
		catch ( \Exception )
		{
			return false;
		}
	}
}
