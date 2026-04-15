<?php
/**
 * @brief       GD Rebates — Scraper target CRUD
 * @package     IPS Community Suite
 * @subpackage  GD Rebates
 * @since       15 Apr 2026
 *
 * Admin CRUD for manufacturer scraper targets (Section 8.2.1 + 8.8).
 * Each row has:
 *   - manufacturer + brand + URL
 *   - JSON extraction_config (validated to decode cleanly)
 *   - rate_limit_ms between requests during crawl
 *   - is_known — if 1, scraped rebates auto-approve to active;
 *                if 0, scraped rebates park in status=pending.
 *   - enabled — scraper task skips disabled targets.
 */

namespace IPS\gdrebates\modules\admin\rebates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _targets extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'rebates_manage' );
		parent::execute();
	}

	protected function manage(): void
	{
		$rows = [];
		try
		{
			foreach ( \IPS\Db::i()->select( '*', 'gd_scrape_targets', null, 'manufacturer ASC' ) as $r )
			{
				if ( !is_array( $r ) )
				{
					continue;
				}
				$id = (int) ( $r['id'] ?? 0 );
				$r['edit_url']   = (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=targets&do=form&id=' . $id );
				$r['delete_url'] = (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=targets&do=delete&id=' . $id )->csrf();
				$r['toggle_url'] = (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=targets&do=toggle&id=' . $id )->csrf();
				$rows[] = $r;
			}
		}
		catch ( \Exception ) {}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdr_targets_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'admin' )->targets( [
			'rows'    => $rows,
			'add_url' => (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=targets&do=form' ),
		] );
	}

	protected function form(): void
	{
		$member = \IPS\Member::loggedIn();
		$lang   = $member->language();
		$id     = (int) ( \IPS\Request::i()->id ?? 0 );

		$existing = null;
		if ( $id > 0 )
		{
			try
			{
				$row = \IPS\Db::i()->select( '*', 'gd_scrape_targets', [ 'id=?', $id ] )->first();
				if ( is_array( $row ) )
				{
					$existing = $row;
				}
			}
			catch ( \Exception ) {}
		}

		$values = $existing ?: [
			'manufacturer'       => '',
			'brand'              => '',
			'scrape_url'         => '',
			'rate_limit_ms'      => (int) ( \IPS\Settings::i()->gdr_scraper_default_rate_ms ?? 2000 ),
			'is_known'           => 0,
			'enabled'            => 1,
			'extraction_config'  => '',
		];
		$errors = [];

		if ( \IPS\Request::i()->requestMethod() === 'POST' )
		{
			\IPS\Session::i()->csrfCheck();

			$values['manufacturer']      = trim( (string) \IPS\Request::i()->manufacturer );
			$values['brand']             = trim( (string) \IPS\Request::i()->brand );
			$values['scrape_url']        = trim( (string) \IPS\Request::i()->scrape_url );
			$values['rate_limit_ms']     = max( 0, (int) \IPS\Request::i()->rate_limit_ms );
			$values['is_known']          = (int) \IPS\Request::i()->is_known === 1 ? 1 : 0;
			$values['enabled']           = (int) \IPS\Request::i()->enabled === 1 ? 1 : 0;
			$values['extraction_config'] = (string) \IPS\Request::i()->extraction_config;

			if ( $values['manufacturer'] === '' )
			{
				$errors[] = 'gdr_targets_err_manufacturer';
			}
			if ( !filter_var( $values['scrape_url'], FILTER_VALIDATE_URL )
				|| !preg_match( '#^https?://#i', $values['scrape_url'] ) )
			{
				$errors[] = 'gdr_targets_err_url';
			}
			if ( \IPS\gdrebates\Rebate\Target::decodeExtractionConfig( $values['extraction_config'] ) === null )
			{
				$errors[] = 'gdr_targets_err_config';
			}

			if ( count( $errors ) === 0 )
			{
				$payload = [
					'manufacturer'      => mb_substr( $values['manufacturer'], 0, 150 ),
					'brand'             => $values['brand'] !== '' ? mb_substr( $values['brand'], 0, 150 ) : $values['manufacturer'],
					'scrape_url'        => mb_substr( $values['scrape_url'], 0, 500 ),
					'rate_limit_ms'     => $values['rate_limit_ms'],
					'is_known'          => $values['is_known'],
					'enabled'           => $values['enabled'],
					'extraction_config' => $values['extraction_config'],
				];

				try
				{
					if ( $existing )
					{
						\IPS\Db::i()->update( 'gd_scrape_targets', $payload, [ 'id=?', (int) $existing['id'] ] );
					}
					else
					{
						$payload['created_at'] = date( 'Y-m-d H:i:s' );
						\IPS\Db::i()->insert( 'gd_scrape_targets', $payload );
					}
				}
				catch ( \Exception ) {}

				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=targets' ),
					'gdr_targets_saved'
				);
				return;
			}
		}

		$errorsResolved = [];
		foreach ( $errors as $k )
		{
			$errorsResolved[] = (string) $lang->addToStack( $k );
		}

		\IPS\Output::i()->title  = $lang->addToStack( $existing ? 'gdr_targets_form_title_edit' : 'gdr_targets_form_title_add' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'rebates', 'gdrebates', 'admin' )->targetForm( [
			'values'     => $values,
			'errors'     => $errorsResolved,
			'is_edit'    => $existing !== null,
			'submit_url' => (string) \IPS\Http\Url::internal(
				'app=gdrebates&module=rebates&controller=targets&do=form' . ( $existing ? '&id=' . (int) $existing['id'] : '' )
			),
			'cancel_url' => (string) \IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=targets' ),
			'csrf_key'   => \IPS\Session::i()->csrfKey,
		] );
	}

	protected function delete(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) \IPS\Request::i()->id;
		if ( $id > 0 )
		{
			try
			{
				\IPS\Db::i()->delete( 'gd_scrape_targets', [ 'id=?', $id ] );
			}
			catch ( \Exception ) {}
		}
		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=targets' ),
			'gdr_targets_deleted'
		);
	}

	protected function toggle(): void
	{
		\IPS\Session::i()->csrfCheck();
		$id = (int) \IPS\Request::i()->id;
		if ( $id > 0 )
		{
			try
			{
				$cur = (int) \IPS\Db::i()->select( 'enabled', 'gd_scrape_targets', [ 'id=?', $id ] )->first();
				\IPS\Db::i()->update(
					'gd_scrape_targets',
					[ 'enabled' => $cur === 1 ? 0 : 1 ],
					[ 'id=?', $id ]
				);
			}
			catch ( \Exception ) {}
		}
		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdrebates&module=rebates&controller=targets' ),
			'gdr_targets_saved'
		);
	}
}

class targets extends _targets {}
