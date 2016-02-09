<?php

class FrmPayment {
    
	function create( $values ) {
		global $wpdb;

		$new_values = array();
		$new_values['receipt_id'] = isset( $values['receipt_id'] ) ? sanitize_text_field( $values['receipt_id'] ) : '';
		$new_values['item_id'] =  isset( $values['item_id'] ) ? absint( $values['item_id'] ) : '';
		$new_values['amount'] = isset( $values['amount'] ) ? (float) $values['amount'] : '';
		$new_values['completed'] = isset( $values['completed'] ) ? 1 : 0;
		$new_values['begin_date'] = isset( $values['begin_date'] ) ? sanitize_text_field( $values['begin_date'] ) : current_time( 'mysql', 1 );
		$new_values['action_id'] = isset( $values['action_id'] ) ? absint( $values['action_id'] ) : 0;
		$new_values['paysys'] = isset( $values['paysys'] ) ? sanitize_text_field( $values['paysys'] ) : 'paypal';
		$new_values['created_at'] = current_time( 'mysql', 1 );
		$new_values['expire_date'] = isset( $values['expire_date'] ) ? sanitize_text_field( $values['expire_date'] ) : '0000-00-00';

		$query_results = $wpdb->insert( $wpdb->prefix .'frm_payments', $new_values );

		return $wpdb->insert_id;
	}
    
	function update( $id, $values ) {
		global $wpdb;

		$new_values = array();

		$new_values['receipt_id'] = isset( $values['receipt_id'] ) ? sanitize_text_field( $values['receipt_id'] ) : '';
        
		if ( isset( $values['item_id'] ) ) {
			$new_values['item_id'] =  absint( $values['item_id'] );
		}
            
		if ( isset( $values['paysys'] ) ) {
			$new_values['paysys'] = sanitize_text_field( $values['paysys'] );
		}
            
		$new_values['amount'] = isset( $values['amount'] ) ? (float) $values['amount'] : '';
		$new_values['completed'] = isset( $values['completed'] ) ? 1 : 0;
		$new_values['begin_date'] = isset( $values['begin_date'] ) ? sanitize_text_field( $values['begin_date'] ) : '';
		if ( isset( $values['expire_date'] ) ) {
			$new_values['expire_date'] = sanitize_text_field( $values['expire_date'] );
		}
		//$new_values['updated_at'] = current_time('mysql', 1);
        
		return $wpdb->update( $wpdb->prefix .'frm_payments', $new_values, compact('id') );
	}
   
    function &destroy( $id ) {
        if ( ! current_user_can( 'administrator' ) ) {
            $frm_settings = FrmPaymentsHelper::get_settings();
            wp_die( $frm_settings->admin_permission );
        }
            
        global $wpdb;
        $id = absint( $id );

        do_action( 'frm_before_destroy_payment', $id );

        $result = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'frm_payments WHERE id=%d', $id ) );
        return $result;
    }

	function get_one( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix .'frm_payments WHERE id=%d', $id ) );
	}
}