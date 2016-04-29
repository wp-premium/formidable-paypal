<?php

class FrmPaymentsListHelper extends WP_List_Table {

	function __construct() {
	    parent::__construct( array(
			'plural' => 'payments',
			'singular' => 'payment',
		) );
	}

	function ajax_user_can() {
		return current_user_can( 'administrator' );
	}

    function prepare_items() {
        global $wpdb;
        
    	$orderby = ( isset( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'created_at';
		$order = ( isset( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'DESC';

    	$page = $this->get_pagenum();
        $per_page = $this->get_items_per_page( 'formidable_page_formidable_payments_per_page');
		$start = ( isset( $_REQUEST['start'] ) ) ? absint( $_REQUEST['start'] ) : (( $page - 1 ) * $per_page);
		$form_id = isset( $_REQUEST['form'] ) ? absint( $_REQUEST['form'] ) : 0;
		if ( $form_id ) {
			$query = $wpdb->prepare( "FROM {$wpdb->prefix}frm_payments p LEFT JOIN {$wpdb->prefix}frm_items i ON (p.item_id = i.id) WHERE i.form_id = %d", $form_id );
		} else {
			$query = "FROM {$wpdb->prefix}frm_payments p";
		}
		$this->items = $wpdb->get_results("SELECT * " . $query . " ORDER BY p.{$orderby} $order LIMIT $start, $per_page");
		$total_items = $wpdb->get_var( "SELECT COUNT(*) " . $query );

    	$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page
		) );
    }

    function no_items() {
    	_e( 'No payments found.', 'frmpp' );
    }

	function get_columns() {
	    return FrmPaymentsController::payment_columns();
	}

	function get_sortable_columns() {
		return array(
		    'item_id'   => 'item_id',
			'completed'  => 'completed',
			'amount'     => 'amount',
			'created_at' => 'created_at',
			'receipt_id' => 'receipt_id',
			'begin_date' => 'begin_date',
			'expire_date' => 'expire_date',
			'paysys'     => 'paysys',
		);
	}
	
	function get_bulk_actions(){
	    $actions = array('bulk_delete' => __('Delete'));
            
        return $actions;
    }

	function extra_tablenav( $which ) {
		$footer = ($which == 'top') ? false : true;
		if ( ! $footer ) {
			$form_id = isset( $_REQUEST['form'] ) ? absint( $_REQUEST['form'] ) : 0;
			echo FrmFormsHelper::forms_dropdown( 'form', $form_id, array( 'blank' => __( 'View all forms', 'formidable' ) ) );
			echo '<input id="post-query-submit" class="button" type="submit" value="Filter" name="filter_action">';
		}
	}

    function display_rows() {
        global $wpdb;

		// Get settings with < 2.0 fallback
		if ( is_callable( 'FrmProAppHelper::get_settings' ) ) {
			$frmpro_settings = FrmProAppHelper::get_settings();
		} else {
			global $frmpro_settings;
		}

		$date_format = 'm/d/Y';
		if ( $frmpro_settings ) {
			$date_format = $frmpro_settings->date_format;
		}

    	$alt = 0;
        $base_link = '?page=formidable-payments&action=';
        
        $entry_ids = array();
        foreach ( $this->items as $item ) {
			$entry_ids[] = absint( $item->item_id );
            unset($item);
        }
        
        $forms = $wpdb->get_results("SELECT fo.id as form_id, fo.name, e.id FROM {$wpdb->prefix}frm_items e LEFT JOIN {$wpdb->prefix}frm_forms fo ON (e.form_id = fo.id) WHERE e.id in (". implode(',', $entry_ids ).")");
        unset($entry_ids);
        
        $form_ids = array();
        foreach($forms as $form){
            $form_ids[$form->id] = $form;
            unset($form);
        }

		foreach ( $this->items as $item ) {
            $item->completed = ( $item->completed ) ? __( 'Yes', 'frmpp' ) : __( 'No', 'frmpp' );
			$item->amount = FrmPaymentsHelper::formatted_amount( $item );
			$style = ( $alt++ % 2 ) ? '' : ' class="alternate"';

			$edit_link = $base_link .'edit&id='.$item->id;
			$view_link = $base_link .'show&id='.$item->id;
			$delete_link = $base_link .'destroy&id='.$item->id;
?>
    	    <tr id="payment-<?php echo $item->id; ?>" valign="middle" <?php echo $style; ?>>
<?php

    		list( $columns, $hidden ) = $this->get_column_info();

    		foreach ( $columns as $column_name => $column_display_name ) {
    			$class = 'column-' . $column_name;

    			if ( in_array( $column_name, $hidden ) ) {
					$class .= ' frm_hidden';
				}

				$attributes = 'class="' . esc_attr( $class ) . '"';

    			switch ( $column_name ) {
    				case 'cb':
    					echo '<th scope="row" class="check-column"><input type="checkbox" name="item-action[]" value="'. esc_attr( $item->id ) .'" /></th>';
    				    break;

    				case 'receipt_id':
						$val = '<strong><a class="row-title" href="' . esc_url( $edit_link ) . ' title="' . esc_attr( __( 'Edit' ) ) . '">' . $item->receipt_id . '</a></strong><br />';

    					$actions = array();
						$actions['view'] = '<a href="' . esc_url( $view_link ) . '">' . __( 'View', 'frmpp' ) . '</a>';
						$actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Edit' ) . '</a>';
						$actions['delete'] = '<a href="' . esc_url( $delete_link ) . '">' . __( 'Delete' ) . '</a>';
    					$val .= $this->row_actions( $actions );

    					break;
    				case 'user_id':
    				    $val = FrmDb::get_var( $wpdb->prefix .'frm_items', array( 'id' => $item->item_id ), 'user_id' );
    				    if ( class_exists( 'FrmProFieldsHelper' ) ) {
							$val = FrmProFieldsHelper::get_display_name( $val, 'display_name', array( 'link' => true ) );
						}
                            
                        break;
    				case 'item_id':
						$val = '<a href="' . esc_url( '?page=formidable-entries&frm_action=show&action=show&id=' . $item->item_id ) . '">' . $item->item_id . '</a>';
    					break;
    				case 'form_id':
						$val = isset( $form_ids[ $item->item_id ] ) ? $form_ids[ $item->item_id ]->name : '';
    				    break;
    				case 'created_at':
    				case 'begin_date':
    				case 'expire_date':
						if ( empty( $item->$column_name ) || strpos( $item->$column_name, '0000-00-00' ) !== false ) {
							$val = '';
						} else {
							$date = FrmAppHelper::get_localized_date( $date_format, $item->$column_name );
							$date_title = FrmAppHelper::get_localized_date( $date_format . ' g:i:s A', $item->$column_name );
							$val = '<abbr title="' . esc_attr( $date_title ) . '">' . $date . '</abbr>';
						}
    				    break;
    				default:
						$val = $item->$column_name;
    					break;
    			}

				if ( isset( $val ) ) {
					echo '<td '. $attributes . '>' . $val . '</td>';
					unset( $val );
				}
    		}
    ?>
    		</tr>
    <?php
        unset($item);
    	}
    }
}
