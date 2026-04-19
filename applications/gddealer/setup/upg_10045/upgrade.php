<?php
/**
 * v1.0.45: support ticket system — schema foundation.
 *
 * Creates three tables and seeds one default department. No UI.
 *
 * - gd_dealer_support_departments — routing buckets (General Support, etc.)
 * - gd_dealer_support_tickets     — one row per submitted ticket
 * - gd_dealer_support_replies     — one row per reply (dealer or admin)
 *
 * Dealer-side UI lands in v1.0.46, admin UI in v1.0.47, notifications
 * in v1.0.48.
 */

namespace IPS\gddealer\setup\upg_10045;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _upgrade
{
	public function step1(): bool
	{
		/* 1. gd_dealer_support_departments */
		try
		{
			if ( !\IPS\Db::i()->checkForTable( 'gd_dealer_support_departments' ) )
			{
				\IPS\Db::i()->createTable( [
					'name'    => 'gd_dealer_support_departments',
					'columns' => [
						[ 'name' => 'id',          'type' => 'INT',      'length' => 10,  'unsigned' => TRUE, 'allow_null' => FALSE, 'auto_increment' => TRUE ],
						[ 'name' => 'name',        'type' => 'VARCHAR',  'length' => 128, 'allow_null' => FALSE ],
						[ 'name' => 'description', 'type' => 'TEXT',     'allow_null' => TRUE,  'default' => NULL ],
						[ 'name' => 'email',       'type' => 'VARCHAR',  'length' => 255, 'allow_null' => TRUE,  'default' => NULL ],
						[ 'name' => 'visibility',  'type' => 'VARCHAR',  'length' => 16,  'allow_null' => FALSE, 'default' => 'pro' ],
						[ 'name' => 'position',    'type' => 'INT',      'length' => 10,  'allow_null' => FALSE, 'default' => 0 ],
						[ 'name' => 'enabled',     'type' => 'TINYINT',  'length' => 1,   'allow_null' => FALSE, 'default' => 1 ],
						[ 'name' => 'created_at',  'type' => 'DATETIME', 'allow_null' => FALSE ],
					],
					'indexes' => [
						[ 'type' => 'primary', 'name' => 'PRIMARY',  'columns' => [ 'id' ] ],
						[ 'type' => 'key',     'name' => 'position', 'columns' => [ 'position' ] ],
						[ 'type' => 'key',     'name' => 'enabled',  'columns' => [ 'enabled' ] ],
					],
				] );
			}
		}
		catch ( \Exception ) {}

		/* 2. gd_dealer_support_tickets */
		try
		{
			if ( !\IPS\Db::i()->checkForTable( 'gd_dealer_support_tickets' ) )
			{
				\IPS\Db::i()->createTable( [
					'name'    => 'gd_dealer_support_tickets',
					'columns' => [
						[ 'name' => 'id',              'type' => 'INT',        'length' => 10,  'unsigned' => TRUE, 'allow_null' => FALSE, 'auto_increment' => TRUE ],
						[ 'name' => 'department_id',   'type' => 'INT',        'length' => 10,  'unsigned' => TRUE, 'allow_null' => FALSE ],
						[ 'name' => 'dealer_id',       'type' => 'INT',        'length' => 10,  'unsigned' => TRUE, 'allow_null' => FALSE ],
						[ 'name' => 'member_id',       'type' => 'BIGINT',     'length' => 20,  'unsigned' => TRUE, 'allow_null' => FALSE ],
						[ 'name' => 'subject',         'type' => 'VARCHAR',    'length' => 255, 'allow_null' => FALSE ],
						[ 'name' => 'priority',        'type' => 'VARCHAR',    'length' => 16,  'allow_null' => FALSE, 'default' => 'normal' ],
						[ 'name' => 'status',          'type' => 'VARCHAR',    'length' => 32,  'allow_null' => FALSE, 'default' => 'open' ],
						[ 'name' => 'body',            'type' => 'MEDIUMTEXT', 'allow_null' => FALSE ],
						[ 'name' => 'assigned_to',     'type' => 'BIGINT',     'length' => 20,  'unsigned' => TRUE, 'allow_null' => TRUE, 'default' => NULL ],
						[ 'name' => 'created_at',      'type' => 'DATETIME',   'allow_null' => FALSE ],
						[ 'name' => 'updated_at',      'type' => 'DATETIME',   'allow_null' => FALSE ],
						[ 'name' => 'last_reply_at',   'type' => 'DATETIME',   'allow_null' => TRUE, 'default' => NULL ],
						[ 'name' => 'last_reply_by',   'type' => 'BIGINT',     'length' => 20,  'unsigned' => TRUE, 'allow_null' => TRUE, 'default' => NULL ],
						[ 'name' => 'last_reply_role', 'type' => 'VARCHAR',    'length' => 16,  'allow_null' => TRUE, 'default' => NULL ],
					],
					'indexes' => [
						[ 'type' => 'primary', 'name' => 'PRIMARY',       'columns' => [ 'id' ] ],
						[ 'type' => 'key',     'name' => 'department_id', 'columns' => [ 'department_id' ] ],
						[ 'type' => 'key',     'name' => 'dealer_id',     'columns' => [ 'dealer_id' ] ],
						[ 'type' => 'key',     'name' => 'member_id',     'columns' => [ 'member_id' ] ],
						[ 'type' => 'key',     'name' => 'status',        'columns' => [ 'status' ] ],
						[ 'type' => 'key',     'name' => 'updated_at',    'columns' => [ 'updated_at' ] ],
					],
				] );
			}
		}
		catch ( \Exception ) {}

		/* 3. gd_dealer_support_replies */
		try
		{
			if ( !\IPS\Db::i()->checkForTable( 'gd_dealer_support_replies' ) )
			{
				\IPS\Db::i()->createTable( [
					'name'    => 'gd_dealer_support_replies',
					'columns' => [
						[ 'name' => 'id',             'type' => 'INT',        'length' => 10, 'unsigned' => TRUE, 'allow_null' => FALSE, 'auto_increment' => TRUE ],
						[ 'name' => 'ticket_id',      'type' => 'INT',        'length' => 10, 'unsigned' => TRUE, 'allow_null' => FALSE ],
						[ 'name' => 'member_id',      'type' => 'BIGINT',     'length' => 20, 'unsigned' => TRUE, 'allow_null' => FALSE ],
						[ 'name' => 'role',           'type' => 'VARCHAR',    'length' => 16, 'allow_null' => FALSE ],
						[ 'name' => 'body',           'type' => 'MEDIUMTEXT', 'allow_null' => FALSE ],
						[ 'name' => 'is_hidden_note', 'type' => 'TINYINT',    'length' => 1,  'allow_null' => FALSE, 'default' => 0 ],
						[ 'name' => 'created_at',     'type' => 'DATETIME',   'allow_null' => FALSE ],
					],
					'indexes' => [
						[ 'type' => 'primary', 'name' => 'PRIMARY',    'columns' => [ 'id' ] ],
						[ 'type' => 'key',     'name' => 'ticket_id',  'columns' => [ 'ticket_id' ] ],
						[ 'type' => 'key',     'name' => 'created_at', 'columns' => [ 'created_at' ] ],
					],
				] );
			}
		}
		catch ( \Exception ) {}

		/* 4. Seed default department — idempotent via COUNT(*). */
		try
		{
			$existing = (int) \IPS\Db::i()->select( 'COUNT(*)', 'gd_dealer_support_departments' )->first();
			if ( $existing === 0 )
			{
				\IPS\Db::i()->insert( 'gd_dealer_support_departments', [
					'name'        => 'General Support',
					'description' => 'General questions, account help, feature requests',
					'email'       => 'dealers@gunrack.deals',
					'visibility'  => 'pro',
					'position'    => 1,
					'enabled'     => 1,
					'created_at'  => date( 'Y-m-d H:i:s' ),
				] );
			}
		}
		catch ( \Exception ) {}

		/* 5. Cache clears. */
		try { unset( \IPS\Data\Store::i()->extensions ); }   catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
