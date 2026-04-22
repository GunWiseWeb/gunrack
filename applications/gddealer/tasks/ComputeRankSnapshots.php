<?php
namespace IPS\gddealer\tasks;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _ComputeRankSnapshots extends \IPS\Task
{
    public function execute(): ?string
    {
        $today  = date( 'Y-m-d' );
        $prefix = \IPS\Db::i()->prefix;

        try {
            \IPS\Db::i()->preparedQuery(
                "INSERT INTO {$prefix}gd_dealer_rank_snapshot
                    (dealer_id, upc, rank_position, competitor_ct, dealer_price, min_price, price_delta_pct, tier, snapshot_date)
                SELECT
                    l.dealer_id,
                    l.upc,
                    (
                        SELECT COUNT(DISTINCT l2.dealer_id)
                        FROM {$prefix}gd_dealer_listings l2
                        WHERE l2.upc = l.upc
                          AND l2.listing_status = 'active'
                          AND l2.dealer_price < l.dealer_price
                    ) + 1,
                    (
                        SELECT COUNT(DISTINCT l3.dealer_id)
                        FROM {$prefix}gd_dealer_listings l3
                        WHERE l3.upc = l.upc AND l3.listing_status = 'active'
                    ),
                    l.dealer_price,
                    (
                        SELECT MIN(l4.dealer_price)
                        FROM {$prefix}gd_dealer_listings l4
                        WHERE l4.upc = l.upc AND l4.listing_status = 'active'
                    ),
                    0,
                    'unknown',
                    ?
                FROM {$prefix}gd_dealer_listings l
                WHERE l.listing_status = 'active'
                ON DUPLICATE KEY UPDATE
                    rank_position = VALUES(rank_position),
                    competitor_ct = VALUES(competitor_ct),
                    dealer_price  = VALUES(dealer_price),
                    min_price     = VALUES(min_price)",
                [ $today ]
            );
        } catch ( \Throwable $e ) {
            return 'Rank snapshot insert failed: ' . $e->getMessage();
        }

        try {
            \IPS\Db::i()->preparedQuery(
                "UPDATE {$prefix}gd_dealer_rank_snapshot
                 SET price_delta_pct = CASE WHEN min_price > 0 THEN ROUND((dealer_price - min_price) / min_price * 100, 2) ELSE 0 END
                 WHERE snapshot_date = ?",
                [ $today ]
            );

            \IPS\Db::i()->preparedQuery(
                "UPDATE {$prefix}gd_dealer_rank_snapshot
                 SET tier = CASE
                     WHEN competitor_ct = 1                               THEN 'only'
                     WHEN rank_position = 1 AND competitor_ct >= 2        THEN 'lowest'
                     WHEN rank_position > 1 AND price_delta_pct <= 10     THEN 'close'
                     WHEN rank_position > 1 AND price_delta_pct > 10      THEN 'overpriced'
                     ELSE 'unknown'
                 END
                 WHERE snapshot_date = ?",
                [ $today ]
            );
        } catch ( \Throwable $e ) {
            return 'Tier computation failed: ' . $e->getMessage();
        }

        try {
            \IPS\Db::i()->delete( 'gd_dealer_rank_snapshot',
                [ 'snapshot_date < ?', date( 'Y-m-d', strtotime( '-90 days' ) ) ]
            );
        } catch ( \Throwable ) {}

        return null;
    }
}
class ComputeRankSnapshots extends _ComputeRankSnapshots {}
