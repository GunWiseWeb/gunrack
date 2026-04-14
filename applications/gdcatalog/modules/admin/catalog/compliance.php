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

use IPS\gdcatalog\Compliance\Flag;
use IPS\gdcatalog\Compliance\FlagProcessor;
use IPS\gdcatalog\Conflict\FeedConflict;
use IPS\gdcatalog\Conflict\FieldLock;

class _compliance extends \IPS\Dispatcher\Controller
{
	protected static $csrfProtected = true;

	public function execute()
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

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack( 'gdcatalog_compliance_title' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'catalog', 'gdcatalog', 'admin' )->compliancePanel(
			$tab, $counts, $pendingFlags, $pendingConflicts, $allLocks, $adminFlags
		);
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
