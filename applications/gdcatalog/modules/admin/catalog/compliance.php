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

class compliance extends \IPS\Dispatcher\Controller
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

		$html = '<div class="ipsBox"><h2 class="ipsBox_title">Compliance Review</h2>';

		/* Tab navigation */
		$html .= '<div class="ipsTabs" data-ipsTabBar>';
		foreach ( [ 'new' => 'New Restrictions', 'conflicts' => 'Feed Conflicts', 'locks' => 'Locked Fields', 'admin' => 'Admin Restrictions' ] as $tk => $tl )
		{
			$active = ( $tab === $tk ) ? ' ipsTabs_activeItem' : '';
			$tabUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&tab=' . $tk );
			$html .= '<a href="' . $tabUrl . '" class="ipsTabs_item' . $active . '">' . $tl . ' (' . (int) $counts[$tk] . ')</a>';
		}
		$html .= '</div><div class="ipsBox_content">';

		/* Tab: New Restrictions */
		if ( $tab === 'new' )
		{
			$html .= '<div class="ipsTable ipsTable_zebra"><div class="ipsTable_header"><div class="ipsTable_row">';
			$html .= '<div class="ipsTable_cell" style="width:12%">UPC</div>';
			$html .= '<div class="ipsTable_cell" style="width:15%">Type</div>';
			$html .= '<div class="ipsTable_cell" style="width:20%">Value</div>';
			$html .= '<div class="ipsTable_cell" style="width:15%">Distributor</div>';
			$html .= '<div class="ipsTable_cell" style="width:15%">First Seen</div>';
			$html .= '<div class="ipsTable_cell" style="width:23%">Actions</div>';
			$html .= '</div></div>';
			foreach ( $pendingFlags as $flag )
			{
				$html .= '<div class="ipsTable_row">';
				$html .= '<div class="ipsTable_cell"><code>' . htmlspecialchars( $flag->upc ) . '</code></div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $flag->flag_type ) . '</div>';
				$html .= '<div class="ipsTable_cell"><strong>' . htmlspecialchars( $flag->flag_value ) . '</strong></div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $flag->distributor_id ) . '</div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $flag->first_seen_at ) . '</div>';
				$html .= '<div class="ipsTable_cell">';
				$approveUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=approve&id=' . (int) $flag->id )->csrf();
				$rejectUrl  = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=reject&id=' . (int) $flag->id )->csrf();
				$html .= '<a href="' . $approveUrl . '" class="ipsButton ipsButton--small ipsButton--positive">Approve</a> ';
				$html .= '<a href="' . $rejectUrl . '" class="ipsButton ipsButton--small ipsButton--negative">Reject</a>';
				$html .= '</div></div>';
			}
			if ( \count( $pendingFlags ) === 0 )
			{
				$html .= '<div class="ipsTable_row"><div class="ipsTable_cell" colspan="6">No pending restrictions.</div></div>';
			}
			$html .= '</div>';
		}

		/* Tab: Feed Conflicts */
		if ( $tab === 'conflicts' )
		{
			$html .= '<div class="ipsTable ipsTable_zebra"><div class="ipsTable_header"><div class="ipsTable_row">';
			$html .= '<div class="ipsTable_cell" style="width:10%">UPC</div>';
			$html .= '<div class="ipsTable_cell" style="width:12%">Field</div>';
			$html .= '<div class="ipsTable_cell" style="width:15%">Current</div>';
			$html .= '<div class="ipsTable_cell" style="width:15%">Incoming</div>';
			$html .= '<div class="ipsTable_cell" style="width:10%">Auto-resolve</div>';
			$html .= '<div class="ipsTable_cell" style="width:38%">Actions</div>';
			$html .= '</div></div>';
			foreach ( $pendingConflicts as $conflict )
			{
				$html .= '<div class="ipsTable_row">';
				$html .= '<div class="ipsTable_cell"><code>' . htmlspecialchars( $conflict->upc ) . '</code></div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $conflict->field_name ) . '</div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( mb_substr( $conflict->current_value, 0, 60 ) ) . '</div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( mb_substr( $conflict->incoming_value, 0, 60 ) ) . '</div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $conflict->auto_resolve_at ) . '</div>';
				$html .= '<div class="ipsTable_cell">';
				$acceptUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=acceptConflict&id=' . (int) $conflict->id )->csrf();
				$keepUrl   = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=keepConflict&id=' . (int) $conflict->id )->csrf();
				$customUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=customConflict&id=' . (int) $conflict->id )->csrf();
				$html .= '<a href="' . $acceptUrl . '" class="ipsButton ipsButton--small ipsButton--positive">Accept Incoming</a> ';
				$html .= '<a href="' . $keepUrl . '" class="ipsButton ipsButton--small ipsButton--warning">Keep Existing</a> ';
				$html .= '<a href="' . $customUrl . '" class="ipsButton ipsButton--small ipsButton--normal">Set Custom</a>';
				$html .= '</div></div>';
			}
			if ( \count( $pendingConflicts ) === 0 )
			{
				$html .= '<div class="ipsTable_row"><div class="ipsTable_cell" colspan="6">No pending feed conflicts.</div></div>';
			}
			$html .= '</div>';
		}

		/* Tab: Locked Fields */
		if ( $tab === 'locks' )
		{
			$html .= '<div class="ipsTable ipsTable_zebra"><div class="ipsTable_header"><div class="ipsTable_row">';
			$html .= '<div class="ipsTable_cell" style="width:10%">UPC</div>';
			$html .= '<div class="ipsTable_cell" style="width:12%">Field</div>';
			$html .= '<div class="ipsTable_cell" style="width:15%">Locked Value</div>';
			$html .= '<div class="ipsTable_cell" style="width:10%">Type</div>';
			$html .= '<div class="ipsTable_cell" style="width:20%">Reason</div>';
			$html .= '<div class="ipsTable_cell" style="width:12%">Locked At</div>';
			$html .= '<div class="ipsTable_cell" style="width:11%">Actions</div>';
			$html .= '</div></div>';
			foreach ( $allLocks as $lock )
			{
				$html .= '<div class="ipsTable_row">';
				$html .= '<div class="ipsTable_cell"><code>' . htmlspecialchars( $lock->upc ) . '</code></div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $lock->field_name ) . '</div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( mb_substr( $lock->locked_value, 0, 60 ) ) . '</div>';
				$lockBadge = $lock->isHardLock() ? 'ipsBadge--negative' : 'ipsBadge--warning';
				$lockType  = $lock->isHardLock() ? 'Hard Lock' : 'Distributor Lock';
				$html .= '<div class="ipsTable_cell"><span class="ipsBadge ' . $lockBadge . '">' . $lockType . '</span></div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( mb_substr( $lock->lock_reason, 0, 80 ) ) . '</div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $lock->locked_at ) . '</div>';
				$unlockUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=unlock&id=' . (int) $lock->id )->csrf();
				$html .= '<div class="ipsTable_cell"><a href="' . $unlockUrl . '" class="ipsButton ipsButton--small ipsButton--negative" data-confirm>Unlock</a></div>';
				$html .= '</div>';
			}
			if ( \count( $allLocks ) === 0 )
			{
				$html .= '<div class="ipsTable_row"><div class="ipsTable_cell" colspan="7">No locked fields.</div></div>';
			}
			$html .= '</div>';
		}

		/* Tab: Admin Restrictions */
		if ( $tab === 'admin' )
		{
			$addUrl = \IPS\Http\Url::internal( 'app=gdcatalog&module=catalog&controller=compliance&do=addRestriction' )->csrf();
			$html .= '<div class="ipsPad"><a href="' . $addUrl . '" class="ipsButton ipsButton--primary ipsButton--small">Add State Restriction</a></div>';
			$html .= '<div class="ipsTable ipsTable_zebra"><div class="ipsTable_header"><div class="ipsTable_row">';
			$html .= '<div class="ipsTable_cell" style="width:12%">UPC</div>';
			$html .= '<div class="ipsTable_cell" style="width:12%">Scope</div>';
			$html .= '<div class="ipsTable_cell" style="width:12%">Type</div>';
			$html .= '<div class="ipsTable_cell" style="width:20%">Value</div>';
			$html .= '<div class="ipsTable_cell" style="width:12%">Set By</div>';
			$html .= '<div class="ipsTable_cell" style="width:15%">Date</div>';
			$html .= '<div class="ipsTable_cell" style="width:17%">Source</div>';
			$html .= '</div></div>';
			foreach ( $adminFlags as $flag )
			{
				$html .= '<div class="ipsTable_row">';
				$html .= '<div class="ipsTable_cell"><code>' . htmlspecialchars( $flag->upc ) . '</code></div>';
				$html .= '<div class="ipsTable_cell">' . ( $flag->listing_id ? 'Listing' : 'Product' ) . '</div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $flag->flag_type ) . '</div>';
				$html .= '<div class="ipsTable_cell"><strong>' . htmlspecialchars( $flag->flag_value ) . '</strong></div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $flag->admin_reviewed_by ) . '</div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $flag->admin_reviewed_at ) . '</div>';
				$html .= '<div class="ipsTable_cell">' . htmlspecialchars( $flag->source ) . '</div>';
				$html .= '</div>';
			}
			if ( \count( $adminFlags ) === 0 )
			{
				$html .= '<div class="ipsTable_row"><div class="ipsTable_cell" colspan="7">No admin-set restrictions.</div></div>';
			}
			$html .= '</div>';
		}

		$html .= '</div></div>';

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
