<div class="wrap">
    <div id="icon-options-general" class="icon32"><br></div>
    <h2><?php _e('Payments', 'frmpp') ?></h2>
    
    <?php include(FrmAppHelper::plugin_path() .'/classes/views/shared/errors.php'); ?>
    
    <div id="poststuff" class="metabox-holder has-right-sidebar">
        <div class="inner-sidebar">
        <div id="submitdiv" class="postbox ">
			<h3 class="hndle"><span><?php _e('Payment Details', 'frmpp') ?></span></h3>
            <div class="inside">
                <div class="submitbox">
            	<div id="major-publishing-actions">
            	    <div id="delete-action">                	    
						<a class="submitdelete deletion" href="<?php echo esc_url( add_query_arg( 'frm_action', 'destroy' ) ) ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete that payment?', 'frmpp' ) ?>');" title="<?php esc_attr_e( 'Delete' ) ?>"><?php _e( 'Delete' ) ?></a>
            	    </div>
            	    
            	    <div id="publishing-action">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=formidable-payments&action=edit&id=' . $payment->id ) ) ?>" class="button-primary"><?php _e( 'Edit' ) ?></a>
                    </div>
                    <div class="clear"></div>
                </div>
                </div>
            </div>
        </div>
        </div>
        
        <div id="post-body">
        <div id="post-body-content">

            <div class="postbox">
                <div class="handlediv"><br/></div><h3 class="hndle"><span><?php _e('Entry', 'frmpp') ?></span></h3>
                <div class="inside">
                    <table class="form-table"><tbody>
                        <tr valign="top">
                            <th scope="row"><?php _e('Completed', 'frmpp') ?>:</th>
                            <td><?php echo ($payment->completed) ? __('Yes', 'frmpp') : __('No', 'frmpp') ?></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('User', 'frmpp') ?>:</th>
                            <td><?php echo FrmProFieldsHelper::get_display_name($payment->user_id, 'display_name', array('link' => true)) ?></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Entry', 'frmpp') ?>:</th>
							<td><a href="?page=formidable-entries&amp;action=show&amp;frm_action=show&amp;id=<?php echo absint( $payment->item_id ) ?>"><?php echo absint( $payment->item_id ) ?></a></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Receipt', 'frmpp') ?>:</th>
							<td><?php echo sanitize_text_field( $payment->receipt_id ) ?></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Amount', 'frmpp') ?>:</th>
                            <td><?php echo FrmPaymentsHelper::formatted_amount( $payment ) ?></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Date', 'frmpp') ?>:</th>
							<td><?php echo sanitize_text_field( $payment->begin_date ) ?></td>
                        </tr>
                        
						<tr valign="top">
							<th scope="row"><?php _e('Expiration Date', 'frmpp') ?>:</th>
							<td><?php echo sanitize_text_field( $payment->expire_date ) ?></td>
						</tr>
                        
                        <?php
						if ( $payment->meta_value ) {
							$payment->meta_value = maybe_unserialize( $payment->meta_value );
                        ?>
                        <tr valign="top">
                            <th scope="row"><?php _e('Payment Status Updates', 'frmpp') ?>:</th>
                            <td>
                            
							<?php foreach ( $payment->meta_value as $k => $metas ) { ?>
                                <table class="widefat" style="border:none;">
                                <?php
								if ( ! is_array( $metas ) ) {
									// fix any payments received in 2.04.02
									$metas = array( $k => $metas );
								}

								foreach ( $metas as $key => $meta ) { ?>
                                <tr>
									<th><?php echo sanitize_text_field( $key ) ?></th>
									<td><?php echo sanitize_text_field( $meta ) ?></td>
                                </tr>
								<?php
								} ?>
                                </table><br/>
                            <?php } ?>
                            
                            </td>
                        </tr>
						<?php
						} ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        </div>
        
    </div>
</div>