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

		$defaults = [
			'manufacturer'       => '',
			'brand'              => '',
			'scrape_url'         => '',
			'rate_limit_ms'      => (int) ( \IPS\Settings::i()->gdr_scraper_default_rate_ms ?? 2000 ),
			'is_known'           => 0,
			'enabled'            => 1,
			'extraction_config'  => '',
		];
		$cur = $existing ?: $defaults;

		$form = new \IPS\Helpers\Form( 'gdr_target', 'gdr_targets_save' );

		$form->add( new \IPS\Helpers\Form\Text(   'target_manufacturer',      (string) $cur['manufacturer'],        TRUE,  [ 'maxLength' => 150 ] ) );
		$form->add( new \IPS\Helpers\Form\Text(   'target_brand',             (string) $cur['brand'],               FALSE, [ 'maxLength' => 150 ] ) );
		$form->add( new \IPS\Helpers\Form\Url(    'target_scrape_url',        (string) $cur['scrape_url'],          TRUE,  [ 'allowedProtocols' => [ 'http', 'https' ] ] ) );
		$form->add( new \IPS\Helpers\Form\Number( 'target_rate_limit_ms',     max( 0, (int) $cur['rate_limit_ms'] ), TRUE,  [ 'min' => 0 ] ) );
		$form->add( new \IPS\Helpers\Form\YesNo(  'target_is_known',          (int) $cur['is_known'] === 1 ) );
		$form->add( new \IPS\Helpers\Form\YesNo(  'target_enabled',           (int) $cur['enabled'] === 1 ) );
		$form->add( new \IPS\Helpers\Form\TextArea(
			'target_extraction_config',
			(string) $cur['extraction_config'],
			FALSE,
			[ 'rows' => 14 ],
			function ( $val )
			{
				if ( $val !== '' && \IPS\gdrebates\Rebate\Target::decodeExtractionConfig( (string) $val ) === null )
				{
					throw new \InvalidArgumentException( 'gdr_targets_err_config' );
				}
			}
		) );

		if ( $values = $form->values() )
		{
			$manufacturer = trim( (string) $values['target_manufacturer'] );
			$brand        = trim( (string) $values['target_brand'] );
			$payload = [
				'manufacturer'      => mb_substr( $manufacturer, 0, 150 ),
				'brand'             => $brand !== '' ? mb_substr( $brand, 0, 150 ) : $manufacturer,
				'scrape_url'        => mb_substr( (string) $values['target_scrape_url'], 0, 500 ),
				'rate_limit_ms'     => max( 0, (int) $values['target_rate_limit_ms'] ),
				'is_known'          => (int) $values['target_is_known'] === 1 ? 1 : 0,
				'enabled'           => (int) $values['target_enabled'] === 1 ? 1 : 0,
				'extraction_config' => (string) $values['target_extraction_config'],
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

		\IPS\Output::i()->title  = $lang->addToStack( $existing ? 'gdr_targets_form_title_edit' : 'gdr_targets_form_title_add' );
		\IPS\Output::i()->output = (string) $form;
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
