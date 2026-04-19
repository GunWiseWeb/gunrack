<?php
/**
 * v1.0.53: stock replies & stock actions — two new tables + template re-seed.
 */

namespace IPS\gddealer\setup\upg_10053;

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
		if ( !\IPS\Db::i()->checkForTable( 'gd_dealer_support_stock_replies' ) )
		{
			\IPS\Db::i()->createTable( [
				'name'    => 'gd_dealer_support_stock_replies',
				'columns' => [
					[
						'name'           => 'id',
						'type'           => 'INT',
						'length'         => 10,
						'unsigned'       => true,
						'auto_increment' => true,
					],
					[
						'name'   => 'title',
						'type'   => 'VARCHAR',
						'length' => 255,
					],
					[
						'name'   => 'body',
						'type'   => 'MEDIUMTEXT',
					],
					[
						'name'     => 'department_id',
						'type'     => 'INT',
						'length'   => 10,
						'unsigned' => true,
						'allow_null' => true,
						'default'  => null,
					],
					[
						'name'     => 'position',
						'type'     => 'INT',
						'length'   => 10,
						'unsigned' => true,
						'default'  => 0,
					],
					[
						'name'     => 'enabled',
						'type'     => 'TINYINT',
						'length'   => 1,
						'unsigned' => true,
						'default'  => 1,
					],
					[
						'name' => 'created_at',
						'type' => 'DATETIME',
						'allow_null' => true,
						'default'    => null,
					],
				],
				'indexes' => [
					[
						'type'    => 'primary',
						'columns' => [ 'id' ],
					],
				],
			] );
		}

		if ( !\IPS\Db::i()->checkForTable( 'gd_dealer_support_stock_actions' ) )
		{
			\IPS\Db::i()->createTable( [
				'name'    => 'gd_dealer_support_stock_actions',
				'columns' => [
					[
						'name'           => 'id',
						'type'           => 'INT',
						'length'         => 10,
						'unsigned'       => true,
						'auto_increment' => true,
					],
					[
						'name'   => 'title',
						'type'   => 'VARCHAR',
						'length' => 255,
					],
					[
						'name'       => 'reply_body',
						'type'       => 'MEDIUMTEXT',
						'allow_null' => true,
						'default'    => null,
					],
					[
						'name'       => 'new_status',
						'type'       => 'VARCHAR',
						'length'     => 32,
						'allow_null' => true,
						'default'    => null,
					],
					[
						'name'       => 'new_priority',
						'type'       => 'VARCHAR',
						'length'     => 16,
						'allow_null' => true,
						'default'    => null,
					],
					[
						'name'       => 'new_assignee',
						'type'       => 'INT',
						'length'     => 10,
						'unsigned'   => true,
						'allow_null' => true,
						'default'    => null,
					],
					[
						'name'       => 'department_id',
						'type'       => 'INT',
						'length'     => 10,
						'unsigned'   => true,
						'allow_null' => true,
						'default'    => null,
					],
					[
						'name'     => 'position',
						'type'     => 'INT',
						'length'   => 10,
						'unsigned' => true,
						'default'  => 0,
					],
					[
						'name'     => 'enabled',
						'type'     => 'TINYINT',
						'length'   => 1,
						'unsigned' => true,
						'default'  => 1,
					],
					[
						'name'       => 'created_at',
						'type'       => 'DATETIME',
						'allow_null' => true,
						'default'    => null,
					],
				],
				'indexes' => [
					[
						'type'    => 'primary',
						'columns' => [ 'id' ],
					],
				],
			] );
		}

		require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10053.php';

		try { unset( \IPS\Data\Store::i()->applications ); }    catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->modules ); }         catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->extensions ); }      catch ( \Exception ) {}
		try { unset( \IPS\Data\Store::i()->themes ); }          catch ( \Exception ) {}
		try { \IPS\Data\Cache::i()->clearAll(); }               catch ( \Exception ) {}
		try { \IPS\Data\Store::i()->clearAll(); }               catch ( \Exception ) {}

		return TRUE;
	}
}

class upgrade extends _upgrade {}
