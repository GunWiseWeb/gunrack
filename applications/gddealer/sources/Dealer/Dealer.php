<?php
/**
 * @brief       GD Dealer Manager — Dealer ActiveRecord
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       15 Apr 2026
 *
 * Wraps gd_dealer_feed_config — one row per dealer, keyed by dealer_id.
 * dealer_id maps 1:1 to \IPS\Member::$member_id of the dealer's primary
 * contact account. IPS Commerce group promotion controls subscription_tier;
 * the Dealer record is created on first successful checkout.
 */

namespace IPS\gddealer\Dealer;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Dealer extends \IPS\Patterns\ActiveRecord
{
	public static ?string $databaseTable   = 'gd_dealer_feed_config';
	public static string $databasePrefix   = '';
	public static string $databaseColumnId = 'dealer_id';

	const TIER_BASIC      = 'basic';
	const TIER_PRO        = 'pro';
	const TIER_ENTERPRISE = 'enterprise';
	const TIER_FOUNDING   = 'founding';

	/** Scheduling per tier — used by DealerImportFeeds task */
	public static array $tierSchedules = [
		'basic'      => '6hr',
		'pro'        => '30min',
		'enterprise' => '15min',
		'founding'   => '6hr',
	];

	/** Monthly prices for MRR calculation */
	public static array $tierMrr = [
		'basic'      => 39.0,
		'pro'        => 99.0,
		'enterprise' => 249.0,
		'founding'   => 0.0,
	];

	/**
	 * Load every dealer row ordered by dealer name.
	 *
	 * @return static[]
	 */
	public static function loadAll(): array
	{
		$out = [];
		foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_feed_config', null, 'dealer_name ASC' ) as $row )
		{
			$out[] = static::constructFromData( $row );
		}
		return $out;
	}

	/**
	 * Only the dealers whose current time slot matches their subscription tier.
	 *
	 * @return static[]
	 */
	public static function loadDueForImport(): array
	{
		$now = new \DateTime();

		$out = [];
		foreach ( \IPS\Db::i()->select( '*', 'gd_dealer_feed_config', [ 'active=? AND suspended=?', 1, 0 ] ) as $row )
		{
			$dealer = static::constructFromData( $row );
			if ( $dealer->isDueForImport( $now ) )
			{
				$out[] = $dealer;
			}
		}
		return $out;
	}

	/**
	 * Returns TRUE if this dealer's feed should run now given their schedule.
	 */
	public function isDueForImport( \DateTime $now ): bool
	{
		if ( !$this->active || $this->suspended )
		{
			return false;
		}
		if ( $this->last_run === null )
		{
			return true;
		}

		$schedule = $this->import_schedule ?: 'daily';
		$intervals = [
			'15min' => 15 * 60,
			'30min' => 30 * 60,
			'1hr'   => 3600,
			'6hr'   => 6 * 3600,
			'daily' => 86400,
		];
		$seconds = $intervals[ $schedule ] ?? 86400;

		$last = new \DateTime( (string) $this->last_run );
		return ( $now->getTimestamp() - $last->getTimestamp() ) >= $seconds;
	}

	/**
	 * Decrypt auth credentials JSON — null if not configured.
	 */
	public function getCredentials(): ?string
	{
		if ( empty( $this->auth_credentials ) )
		{
			return null;
		}
		try
		{
			return \IPS\Text\Encrypt::fromCipher( (string) $this->auth_credentials )->decrypt();
		}
		catch ( \Exception )
		{
			return (string) $this->auth_credentials;
		}
	}

	public function setCredentials( ?string $plain ): void
	{
		if ( $plain === null || $plain === '' )
		{
			$this->auth_credentials = null;
			return;
		}
		$this->auth_credentials = (string) \IPS\Text\Encrypt::fromPlaintext( $plain )->cipher;
	}

	/**
	 * Generate and persist a new API key for this dealer.
	 */
	public function rotateApiKey(): string
	{
		$key = bin2hex( random_bytes( 24 ) );
		$this->api_key = $key;
		$this->save();
		return $key;
	}

	/**
	 * MRR contribution for this dealer based on their current tier.
	 */
	public function mrrContribution(): float
	{
		return (float) ( static::$tierMrr[ $this->subscription_tier ] ?? 0.0 );
	}
}

class Dealer extends _Dealer {}
