<?php
namespace IPS\gddealer\setup\upg_10078;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        if ( !\IPS\Db::i()->checkForTable( 'gd_dealer_rank_snapshot' ) )
        {
            \IPS\Db::i()->createTable( [
                'name'    => 'gd_dealer_rank_snapshot',
                'columns' => [
                    [ 'name' => 'id',              'type' => 'BIGINT',   'length' => 20, 'unsigned' => true, 'allow_null' => false, 'auto_increment' => true ],
                    [ 'name' => 'dealer_id',       'type' => 'INT',      'length' => 10, 'unsigned' => true, 'allow_null' => false, 'default' => 0 ],
                    [ 'name' => 'upc',             'type' => 'VARCHAR',  'length' => 20, 'allow_null' => false, 'default' => '' ],
                    [ 'name' => 'rank_position',   'type' => 'SMALLINT', 'length' => 5,  'unsigned' => true, 'allow_null' => false, 'default' => 0 ],
                    [ 'name' => 'competitor_ct',   'type' => 'SMALLINT', 'length' => 5,  'unsigned' => true, 'allow_null' => false, 'default' => 0 ],
                    [ 'name' => 'dealer_price',    'type' => 'DECIMAL',  'length' => '10,2', 'allow_null' => false, 'default' => '0.00' ],
                    [ 'name' => 'min_price',       'type' => 'DECIMAL',  'length' => '10,2', 'allow_null' => false, 'default' => '0.00' ],
                    [ 'name' => 'price_delta_pct', 'type' => 'DECIMAL',  'length' => '6,2',  'allow_null' => false, 'default' => '0.00' ],
                    [ 'name' => 'tier',            'type' => 'VARCHAR',  'length' => 20, 'allow_null' => false, 'default' => 'unknown' ],
                    [ 'name' => 'snapshot_date',   'type' => 'DATE',     'allow_null' => false ],
                ],
                'indexes' => [
                    [ 'type' => 'primary', 'name' => 'PRIMARY',            'columns' => [ 'id' ] ],
                    [ 'type' => 'key',     'name' => 'idx_dealer_date',    'columns' => [ 'dealer_id', 'snapshot_date' ] ],
                    [ 'type' => 'key',     'name' => 'idx_upc_date',       'columns' => [ 'upc', 'snapshot_date' ] ],
                    [ 'type' => 'unique',  'name' => 'uq_dealer_upc_date', 'columns' => [ 'dealer_id', 'upc', 'snapshot_date' ] ],
                ],
            ] );
        }

        return TRUE;
    }

    public function step2(): bool
    {
        try {
            $existing = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_tasks',
                [ 'app=? AND `key`=?', 'gddealer', 'ComputeRankSnapshots' ]
            )->first();
            if ( $existing === 0 ) {
                \IPS\Db::i()->insert( 'core_tasks', [
                    'app'       => 'gddealer',
                    'key'       => 'ComputeRankSnapshots',
                    'frequency' => 'P1D',
                    'next_run'  => time() + 60,
                    'enabled'   => 1,
                ] );
            }
        } catch ( \Exception ) {}

        return TRUE;
    }

    public function step3(): bool
    {
        require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10078.php';

        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Throwable ) {}
        try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Throwable ) {}

        return TRUE;
    }
}
class upgrade extends _upgrade {}
