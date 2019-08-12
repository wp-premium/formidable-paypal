<div class="postbox">
<h3 class="hndle"><span><?php _e('Payment Details', 'frmpp') ?></span></h3>
<div class="inside">

<input type="hidden" name="action" value="<?php echo esc_attr( $form_action ) ?>"/>

<table class="form-table"><tbody>
    <tr class="form-field">
        <th scope="row"><?php _e('Completed', 'frmpp') ?></th>
        <td><input type="checkbox" value="1" name="completed" <?php checked($payment['completed'], 1) ?> /></td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php _e('Entry ID', 'frmpp') ?></th>
        <td>
            #<input type="number" name="item_id" value="<?php echo esc_attr($payment['item_id']) ?>" />
        </td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php _e('Receipt', 'frmpp') ?></th>
        <td><input type="text" name="receipt_id" value="<?php echo esc_attr($payment['receipt_id']) ?>" /></td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php _e('Amount', 'frmpp') ?></th>
        <td><?php echo $currency['symbol_left'] ?><input type="text" name="amount" value="<?php echo esc_attr($payment['amount']) ?>" /><?php echo $currency['symbol_right'] ?></td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php _e('Date', 'frmpp') ?></th>
		<td><input type="text" name="begin_date" class="" value="<?php echo esc_attr(  $payment['begin_date'] ) ?>" /></td>
    </tr>

	<tr valign="top">
		<th scope="row"><?php _e('Expiration Date', 'frmpp') ?></th>
		<td><input type="text" name="expire_date" class="" value="<?php echo esc_attr(  $payment['expire_date'] ) ?>" /></td>
	</tr>

    <tr valign="top">
        <th scope="row"><?php _e('Payment Method', 'frmpp') ?></th>
        <td><select name="paysys">
                <option value="paypal" <?php selected($payment['paysys'], 'paypal') ?>><?php _e('PayPal', 'frmpp'); ?></option>
                <option value="manual" <?php selected($payment['paysys'], 'manual') ?>><?php _e('Manual', 'frmpp'); ?></option>
            </select>
    </tr>
</tbody></table>
</div>
</div>