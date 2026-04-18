<?php

namespace IPS\gddealer\setup\upg_10021;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _upgrade
{
	/**
	 * step1 — Force-seed every email template from data/emails.xml
	 * directly into core_email_templates, bypassing IPS's
	 * installEmailTemplates() which has been silently failing. Each
	 * template is inserted in its own try/catch so one failure does
	 * not block the rest, and a row is only inserted if a matching
	 * (template_app, template_name) row does not already exist, so
	 * the step is safe to re-run.
	 */
	public function step1(): bool
	{
		$appPath = \IPS\Application::load( 'gddealer' )->getApplicationPath();
		$xmlPath = $appPath . '/data/emails.xml';

		if ( file_exists( $xmlPath ) )
		{
			$prev = libxml_disable_entity_loader( TRUE );
			$xml  = @simplexml_load_file( $xmlPath );
			libxml_disable_entity_loader( $prev );

			if ( $xml instanceof \SimpleXMLElement )
			{
				foreach ( $xml->template as $t )
				{
					$templateName = trim( (string) $t->template_name );
					if ( $templateName === '' ) { continue; }

					try
					{
						$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_email_templates',
							[ 'template_app=? AND template_name=?', 'gddealer', $templateName ]
						)->first();

						if ( $exists > 0 ) { continue; }

						\IPS\Db::i()->insert( 'core_email_templates', [
							'template_app'               => 'gddealer',
							'template_name'              => $templateName,
							'template_data'              => (string) $t->template_data,
							'template_content_html'      => (string) $t->template_content_html,
							'template_content_plaintext' => (string) $t->template_content_plaintext,
							'template_key'               => md5( 'gddealer;' . $templateName ),
							'template_parent'            => 0,
							'template_edited'            => 0,
							'template_pinned'            => 0,
						] );
					}
					catch ( \Exception )
					{
						/* Fall back to a minimal column set for schema variants that
						   don't have template_key / template_parent / etc. */
						try
						{
							\IPS\Db::i()->insert( 'core_email_templates', [
								'template_app'               => 'gddealer',
								'template_name'              => $templateName,
								'template_content_html'      => (string) $t->template_content_html,
								'template_content_plaintext' => (string) $t->template_content_plaintext,
							] );
						}
						catch ( \Exception ) {}
					}
				}
			}
		}

		/* Belt-and-suspenders: also ask IPS to run its own installer. Harmless
		   if it no-ops or if rows already exist. */
		try { \IPS\Application::load( 'gddealer' )->installEmailTemplates(); } catch ( \Exception ) {}

		return TRUE;
	}

	/**
	 * step2 — Idempotently seed notification defaults in
	 * core_notification_defaults for every gddealer notification key.
	 */
	public function step2(): bool
	{
		$notificationDefaults = [
			'new_dealer_review'     => [ 'default' => 'inline,email', 'disabled' => '' ],
			'updated_dealer_review' => [ 'default' => 'inline,email', 'disabled' => '' ],
			'review_disputed'       => [ 'default' => 'inline,email', 'disabled' => '' ],
			'dispute_admin_review'  => [ 'default' => 'inline,email', 'disabled' => '' ],
			'dispute_upheld'        => [ 'default' => 'inline,email', 'disabled' => '' ],
			'dispute_dismissed'     => [ 'default' => 'inline,email', 'disabled' => '' ],
			'dealer_responded'      => [ 'default' => 'inline,email', 'disabled' => '' ],
		];

		foreach ( $notificationDefaults as $key => $data )
		{
			try
			{
				$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_notification_defaults',
					[ 'notification_key=?', $key ]
				)->first();

				if ( $exists > 0 ) { continue; }

				\IPS\Db::i()->insert( 'core_notification_defaults', [
					'notification_key' => $key,
					'default'          => $data['default'],
					'disabled'         => $data['disabled'],
				] );
			}
			catch ( \Exception ) {}
		}

		return TRUE;
	}

	/**
	 * step3 — Clear datastore caches so IPS re-scans extensions,
	 * applications, and email template cache on next request.
	 */
	public function step3(): bool
	{
		try { unset( \IPS\Data\Store::i()->extensions ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->emailTemplates ); }  catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
