    <table class="form-table">
        <tr class="form-field" valign="top">
            <td width="200px"><label><?php _e('PayPal Email', 'frmpp') ?></label></td>
        	<td>
				<input type="text" name="frm_pay_business_email" id="frm_pay_business_email" value="<?php echo esc_attr( $frm_payment_settings->settings->business_email ) ?>" class="frm_long_input" />
    				
        	</td>
        </tr>
        
        <tr class="form-field" valign="top">
            <td><label><?php _e('PayPal Environment', 'frmpp') ?></label></td>
        	<td>
                <select name="frm_pay_environment" id="frm_pay_environment">
                    <option value="live" <?php selected($frm_payment_settings->settings->environment, 'live') ?>><?php _e('Live', 'frmpp') ?></option>
                    <option value="sandbox" <?php selected($frm_payment_settings->settings->environment, 'sandbox') ?>><?php _e('Testing', 'frmpp') ?></option>
    				
        	</td>
        </tr>
        
        <tr class="form-field" valign="top">
            <td><label><?php _e('Currency', 'frmpp') ?></label></td>
        	<td>
        	    <select name="frm_pay_currency" id="frm_pay_currency">
        	    <?php foreach (FrmPaymentsHelper::get_currencies() as $code => $currency){ ?>
				<option value="<?php echo esc_attr( $code ) ?>" <?php selected( $frm_payment_settings->settings->currency, $code ) ?>><?php echo esc_html( $currency['name'] . ' (' . $code . ')' ); ?></option>
                <?php 
                    unset($currency);
                    unset($code);
                    } 
                ?>
				</select>
        	</td>
        </tr>
        
        <tr class="form-field" valign="top">
            <td><label><?php _e('Return URL', 'frmpp') ?></label></td>
        	<td>
                <input type="text" name="frm_pay_return_url" id="frm_pay_return_url" value="<?php echo esc_attr($frm_payment_settings->settings->return_url) ?>" class="frm_long_input"  />
    			<div class="howto"><?php _e('The URL for PayPal to send users after purchase', 'frmpp') ?></div>
        	</td>
        </tr>
        
        <tr class="form-field" valign="top">
            <td><label><?php _e('Cancel URL', 'frmpp') ?></label></td>
        	<td>
                <input type="text" name="frm_pay_cancel_url" id="frm_pay_cancel_url" value="<?php echo esc_attr($frm_payment_settings->settings->cancel_url) ?>" class="frm_long_input"  />
    			<div class="howto"><?php _e('The URL for PayPal to send users if they cancel the transaction', 'frmpp') ?></div>
        	</td>
        </tr>
        
        <tr class="form-field" valign="top">
            <td><label><?php _e('Log Results', 'frmpp') ?></label></td>
        	<td>
                <p><label for="frm_pay_ipn_log"><input type="checkbox" name="frm_pay_ipn_log" id="frm_pay_ipn_log" value="1" <?php checked($frm_payment_settings->settings->ipn_log, 1) ?> /> <?php _e('Log results from IPN notifications', 'frmpp') ?></label></p>
    			<p><input type="text" name="frm_pay_ipn_log_file" value="<?php echo esc_attr($frm_payment_settings->settings->ipn_log_file) ?>" class="frm_long_input" /><br/>
    			    <span class="howto"><?php _e('The location of the error log', 'frmpp') ?></span>
    			</p>
        	</td>
        </tr>
    </table>