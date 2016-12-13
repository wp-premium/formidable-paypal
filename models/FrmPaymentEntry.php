<?php

class FrmPaymentEntry {
	public static function get_completed_payments( $entry ) {
		global $wpdb;
		$payments = $wpdb->get_results( $wpdb->prepare( 'SELECT id, begin_date, amount, completed FROM ' . $wpdb->prefix . 'frm_payments WHERE item_id=%d AND completed=%d ORDER BY created_at DESC', $entry->id, 1 ) );
		return $payments;
	}

	public static function get_entry_expiration( $entry ) {
		global $wpdb;
		$expiration = $wpdb->get_var( $wpdb->prepare( 'SELECT expire_date FROM ' . $wpdb->prefix . 'frm_payments WHERE item_id=%d AND completed=%d ORDER BY created_at DESC', $entry->id, 1 ) );

		return $expiration;
	}

	public static function is_expired( $entry ) {
		$expiration = self::get_entry_expiration( $entry );
		$lifetime = empty( $expiration ) || $expiration == '0000-00-00';
		return ( ! $lifetime && $expiration <= date( 'Y-m-d' ) );
	}
}
