<?php
/**
 * @brief       GD Master Catalog — ACP Compliance Review Panel
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Unified compliance review panel (Section 2.11.3) with 4 tabs:
 * New restrictions | Feed conflicts | Locked fields | Admin restrictions
 */

namespace IPS\gdcatalog\modules\admin\catalog;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\gdcatalog\Compliance\Flag;
use IPS\gdcatalog\Compliance\FlagProcessor;
use IPS\gdcatalog\Conflict\FeedConflict;
use IPS\gdcatalog\Conflict\FieldLock;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _compliance extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = TRUE;

	public function execute(): void
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'catalog_manage' );
		parent::execute();
	}

	/**
	 * Tabbed compliance review panel.
	 */
	protected function manage()
	{
		$tab = \IPS\Request::i()->tab ?? 'new';

		$pendingFlags     = Flag::loadPending();
		$pendingConflicts = FeedConflict::loadPending();
		$allLocks         = FieldLock::loadAllLocks();
		$adminFlags       = Flag::loadAdminSet();

		$counts = [
			'new'       => \count( $pendingFlags ),
			'conflicts' => \count( $pendingConflicts ),
			'locks'     => \count( $allLocks ),
			'admin'     => \count( $adminFlags ),
		];

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_compliance_title' );

		$baseUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance' );

		$html  = '<h2>Compliance Review</h2>';
		$html .= '<ul>';
		foreach ( [ 'new' => 'New restrictions', 'conflicts' => 'Feed conflicts', 'locks' => 'Locked fields', 'admin' => 'Admin restrictions' ] as $key => $label )
		{
			$tabUrl = (string) $baseUrl->setQueryString( 'tab', $key );
			$marker = $tab === $key ? ' <strong>(active)</strong>' : '';
			$html  .= '<li><a href="' . htmlspecialchars( $tabUrl ) . '">' . $label . ' (' . (int) $counts[ $key ] . ')</a>' . $marker . '</li>';
		}
		$html .= '</ul>';

		if ( $tab === 'new' )
		{
			$html .= '<h3>Pending Compliance Flags</h3>';
			$html .= '<table><tr><th>UPC</th><th>Type</th><th>Value</th><th>Source</th><th></th></tr>';
			foreach ( $pendingFlags as $flag )
			{
				$approve = (string) \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=approve&id=' . (int) $flag->id )->csrf();
				$reject  = (string) \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=reject&id=' . (int) $flag->id )->csrf();
				$html .= '<tr>';
				$html .= '<td>' . htmlspecialchars( $flag->upc ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $flag->flag_type ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $flag->flag_value ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $flag->source ?? '' ) . '</td>';
				$html .= '<td><a href="' . htmlspecialchars( $approve ) . '">Approve</a> | <a href="' . htmlspecialchars( $reject ) . '">Reject</a></td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
		}
		elseif ( $tab === 'conflicts' )
		{
			$html .= '<h3>Pending Feed Conflicts</h3>';
			$html .= '<table><tr><th>UPC</th><th>Field</th><th>Existing</th><th>Incoming</th><th>Source</th><th></th></tr>';
			foreach ( $pendingConflicts as $c )
			{
				$accept = (string) \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=acceptConflict&id=' . (int) $c->id )->csrf();
				$keep   = (string) \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=keepConflict&id=' . (int) $c->id )->csrf();
				$custom = (string) \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=customConflict&id=' . (int) $c->id )->csrf();
				$html .= '<tr>';
				$html .= '<td>' . htmlspecialchars( $c->upc ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $c->field_name ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $c->existing_value ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $c->incoming_value ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $c->incoming_source ?? '' ) . '</td>';
				$html .= '<td><a href="' . htmlspecialchars( $accept ) . '">Accept</a> | <a href="' . htmlspecialchars( $keep ) . '">Keep</a> | <a href="' . htmlspecialchars( $custom ) . '">Custom</a></td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
		}
		elseif ( $tab === 'locks' )
		{
			$html .= '<h3>Field Locks</h3>';
			$html .= '<table><tr><th>UPC</th><th>Field</th><th>Type</th><th>Distributor</th><th>Reason</th><th></th></tr>';
			foreach ( $allLocks as $lock )
			{
				$unlock = (string) \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=unlock&id=' . (int) $lock->id )->csrf();
				$html .= '<tr>';
				$html .= '<td>' . htmlspecialchars( $lock->upc ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $lock->field_name ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $lock->lock_type ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $lock->distributor ?? '—' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $lock->reason ?? '' ) . '</td>';
				$html .= '<td><a href="' . htmlspecialchars( $unlock ) . '">Unlock</a></td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
		}
		else
		{
			$addUrl = (string) \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=addRestriction' )->csrf();
			$html .= '<h3>Admin-Set Restrictions</h3>';
			$html .= '<p><a href="' . htmlspecialchars( $addUrl ) . '">+ Add State Restriction</a></p>';
			$html .= '<table><tr><th>UPC</th><th>Type</th><th>Value</th></tr>';
			foreach ( $adminFlags as $flag )
			{
				$html .= '<tr>';
				$html .= '<td>' . htmlspecialchars( $flag->upc ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $flag->flag_type ?? '' ) . '</td>';
				$html .= '<td>' . htmlspecialchars( $flag->flag_value ?? '' ) . '</td>';
				$html .= '</tr>';
			}
			$html .= '</table>';
		}

		\IPS\Output::i()->output = $html;
	}

	/**
	 * Approve a pending compliance flag.
	 */
	protected function approve()
	{
		\IPS\Session::i()->csrfCheck();
		$flagId = (int) \IPS\Request::i()->id;
		FlagProcessor::approve( $flagId, \IPS\Member::loggedIn()->member_id );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&tab=new' ),
			'Flag approved — badge is now live'
		);
	}

	/**
	 * Reject a pending compliance flag.
	 */
	protected function reject()
	{
		\IPS\Session::i()->csrfCheck();
		$flagId = (int) \IPS\Request::i()->id;
		FlagProcessor::reject( $flagId, \IPS\Member::loggedIn()->member_id );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&tab=new' ),
			'Flag rejected'
		);
	}

	/**
	 * Accept incoming value on a feed conflict.
	 */
	protected function acceptConflict()
	{
		\IPS\Session::i()->csrfCheck();
		$conflict = FeedConflict::load( (int) \IPS\Request::i()->id );
		$conflict->acceptIncoming( \IPS\Member::loggedIn()->member_id );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&tab=conflicts' ),
			'Incoming value accepted'
		);
	}

	/**
	 * Keep existing value on a feed conflict (creates distributor-specific lock).
	 */
	protected function keepConflict()
	{
		\IPS\Session::i()->csrfCheck();

		$conflict = FeedConflict::load( (int) \IPS\Request::i()->id );

		/* Lock reason is required */
		if ( empty( \IPS\Request::i()->reason ) )
		{
			/* Show a form to collect the reason */
			$form = new \IPS\Helpers\Form;
			$form->add( new \IPS\Helpers\Form\TextArea( 'gdcatalog_compliance_lock_reason', '', TRUE ) );

			if ( $values = $form->values() )
			{
				$conflict->keepExisting( \IPS\Member::loggedIn()->member_id, $values['gdcatalog_compliance_lock_reason'] );

				\IPS\Output::i()->redirect(
					\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&tab=conflicts' ),
					'Existing value kept — field locked for this distributor'
				);
				return;
			}

			\IPS\Output::i()->title  = 'Keep Existing — Lock Reason Required';
			\IPS\Output::i()->output = (string) $form;
			return;
		}

		$conflict->keepExisting( \IPS\Member::loggedIn()->member_id, \IPS\Request::i()->reason );

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&tab=conflicts' ),
			'Existing value kept — field locked for this distributor'
		);
	}

	/**
	 * Set a custom value on a feed conflict (creates hard lock).
	 */
	protected function customConflict()
	{
		\IPS\Session::i()->csrfCheck();

		$conflict = FeedConflict::load( (int) \IPS\Request::i()->id );

		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'gdcatalog_custom_value', '', TRUE ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'gdcatalog_compliance_lock_reason', '', TRUE ) );

		if ( $values = $form->values() )
		{
			$conflict->setCustom(
				\IPS\Member::loggedIn()->member_id,
				$values['gdcatalog_custom_value'],
				$values['gdcatalog_compliance_lock_reason']
			);

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&tab=conflicts' ),
				'Custom value applied — field hard locked'
			);
			return;
		}

		\IPS\Output::i()->title  = 'Set Custom Value';
		\IPS\Output::i()->output = (string) $form;
	}

	/**
	 * Unlock a field lock.
	 */
	protected function unlock()
	{
		\IPS\Session::i()->csrfCheck();
		$lock = FieldLock::load( (int) \IPS\Request::i()->id );
		$lock->unlock();

		\IPS\Output::i()->redirect(
			\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&tab=locks' ),
			'Field unlocked — next import will update normally'
		);
	}

	/**
	 * Create an admin-set state restriction.
	 */
	protected function addRestriction()
	{
		\IPS\Session::i()->csrfCheck();

		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'gdcatalog_product_upc', '', TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'gdcatalog_restriction_states', '', TRUE, [
			'placeholder' => 'CA,NY,MA',
		] ) );

		if ( $values = $form->values() )
		{
			FlagProcessor::createAdminFlag(
				trim( $values['gdcatalog_product_upc'] ),
				null,
				'state_restriction',
				strtoupper( trim( $values['gdcatalog_restriction_states'] ) ),
				\IPS\Member::loggedIn()->member_id
			);

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&tab=admin' ),
				'Admin restriction created'
			);
			return;
		}

		\IPS\Output::i()->title  = 'Add State Restriction';
		\IPS\Output::i()->output = (string) $form;
	}
}

class compliance extends _compliance {}
