<?php
namespace IPS\gddealer\setup\upg_10077;
use function defined;
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

class _upgrade
{
    public function step1(): bool
    {
        $words = [
            'gddealer_front_activity_review_posted'              => 'Review posted by %s',
            'gddealer_front_activity_dealer_responded'           => 'You posted a public response',
            'gddealer_front_activity_event_dispute_opened'       => 'You contested the review',
            'gddealer_front_activity_event_customer_responded'   => 'Customer replied to your contest',
            'gddealer_front_activity_event_admin_edit_requested' => 'Our team requested edits from the customer',
            'gddealer_front_activity_event_dispute_resolved'     => 'Dispute resolved',
            'gddealer_front_activity_event_reopened'             => 'Dispute reopened',
            'gddealer_front_activity_event_admin_review_started' => 'Admin review started',
            'gddealer_front_fold_pending_customer'               => 'Awaiting customer reply',
            'gddealer_front_fold_pending_admin'                  => 'Under admin review',
            'gddealer_front_fold_resolved_dealer'                => 'Resolved in your favor',
            'gddealer_front_fold_resolved_customer'              => 'Resolved for customer',
            'gddealer_front_fold_dispute_details'                => 'Dispute details',
            'gddealer_front_fold_customer_replied'               => 'Customer replied',
            'gddealer_front_fold_deadline'                       => 'Deadline: %s',
            'gddealer_front_fold_days_left'                      => '%d days left',
            'gddealer_front_fold_activity_events'                => '%d events',
            'gddealer_front_fold_activity_event'                 => '%d event',
        ];

        try {
            foreach ( \IPS\Db::i()->select( 'lang_id', 'core_sys_lang' ) as $langId ) {
                foreach ( $words as $key => $default ) {
                    try {
                        \IPS\Db::i()->replace( 'core_sys_lang_words', [
                            'lang_id'      => (int) $langId,
                            'word_app'     => 'gddealer',
                            'word_key'     => $key,
                            'word_default' => $default,
                            'word_js'      => 0,
                            'word_export'  => 1,
                        ] );
                    } catch ( \Exception ) {}
                }
            }
        } catch ( \Exception ) {}

        return TRUE;
    }

    public function step2(): bool
    {
        require_once \IPS\ROOT_PATH . '/applications/gddealer/setup/templates_10077.php';

        try { unset( \IPS\Data\Store::i()->applications ); } catch ( \Throwable ) {}
        try { \IPS\Data\Cache::i()->clearAll(); }            catch ( \Throwable ) {}
        try { \IPS\Data\Store::i()->clearAll(); }            catch ( \Throwable ) {}

        return TRUE;
    }
}
class upgrade extends _upgrade {}
