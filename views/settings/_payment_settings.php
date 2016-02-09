<table class="form-table frm-no-margin">
	<tr><td class="frm-no-margin">
		<input type="hidden" value="<?php echo absint( $form_action->ID ) ?>" name="<?php echo esc_attr( $this->get_field_name( 'action_id' ) ) ?>" />
		<p><label class="frm_left_label"><?php _e( 'Item Name', 'frmpp' ) ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'paypal_item_name' ) ) ?>" id="paypal_item_name" value="<?php echo esc_attr( $form_action->post_content['paypal_item_name'] ); ?>" class="frm_not_email_subject frm_with_left_label" />
			<span class="clear"></span>
		</p>

		<p><label class="frm_left_label"><?php _e( 'PayPal Email', 'frmpp' ) ?></label>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'business_email' ) ) ?>" id="business_email" value="<?php echo esc_attr( $form_action->post_content['business_email'] ); ?>" class="frm_with_left_label" />
			<span class="clear"></span>
		</p>

		<p class="frm_pp_toggle_new">
			<label class="frm_left_label"><?php _e('Amount', 'frmpp') ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'paypal_amount_field' ) ) ?>" class="frm_cancelnew <?php echo $show_amount ? 'frm_hidden' : ''; ?>">
                <option value=""><?php _e( '&mdash; Select &mdash;' ) ?></option>
                <?php
                $selected = false;
				foreach ( $form_fields as $field ) {
                    if ( $form_action->post_content['paypal_amount_field'] == $field->id ) {
                        $selected = true;
                    }
                    ?>
					<option value="<?php echo esc_attr( $field->id ) ?>" <?php selected( $form_action->post_content['paypal_amount_field'], $field->id ) ?>><?php
					echo esc_attr( FrmAppHelper::truncate( $field->name, 50, 1 ) );
					unset( $field );
                    ?></option>
                    <?php
                }
                ?>
            </select>
			<input type="text" value="<?php echo esc_attr( $form_action->post_content['paypal_amount'] ) ?>" name="<?php echo esc_attr( $this->get_field_name( 'paypal_amount' ) ) ?>" class="frm_enternew <?php echo $show_amount ? '' : 'frm_hidden'; ?>" />
            <span class="clear"></span>
            <label class="frm_left_label">&nbsp;</label>
            <a class="hide-if-no-js frm_toggle_pp_opts">
				<span class="frm_enternew <?php echo $show_amount ? 'frm_hidden' : ''; ?>"><?php _e( 'Set Amount', 'frmpp' ); ?></span>
				<span class="frm_cancelnew <?php echo $show_amount ? '' : 'frm_hidden'; ?>"><?php _e( 'Select Field', 'frmpp' ); ?></span>
            </a>
			<span class="clear"></span>
		</p>

            <p>
				<label class="frm_left_label"><?php _e( 'Payment Type', 'frmpp' ) ?></label>
				<select name="<?php echo esc_attr( $this->get_field_name( 'paypal_type' ) ) ?>" class="frm_paypal_type" >
					<option value="_xclick" <?php selected( $form_action->post_content['paypal_type'], '_xclick' ) ?>><?php _e( 'One-time Payment', 'frmpp' ) ?></option>
					<option value="_donations" <?php selected( $form_action->post_content['paypal_type'], '_donations' ) ?>><?php _e( 'Donation', 'frmpp' ) ?></option>
					<option value="_xclick-subscriptions" <?php selected( $form_action->post_content['paypal_type'], '_xclick-subscriptions' ) ?>><?php _e( 'Subscription', 'frmpp' ) ?></option>
				</select>
            </p>
			<div class="frmpp_sub_opts <?php echo $form_action->post_content['paypal_type'] == '_xclick-subscriptions' ? '' : 'frm_hidden'; ?>">
				<p><label class="frm_left_label"><?php _e( 'Repeat Every', 'frmpp' ) ?></label>
					<input type="number" name="<?php echo esc_attr( $this->get_field_name( 'repeat_num' ) ) ?>" value="<?php echo esc_attr( $form_action->post_content['repeat_num'] ) ?>" max="90" min="1" step="1" />
					<select name="<?php echo esc_attr( $this->get_field_name( 'repeat_time' ) ) ?>">
					<?php foreach ( FrmPaymentsHelper::get_repeat_times() as $k => $v ) { ?>
						<option value="<?php echo esc_attr($k) ?>" <?php selected( $form_action->post_content['repeat_time'], $k ) ?>><?php echo $v ?></option>
					<?php } ?>
					</select>
				</p>
				<p>
					<label class="frm_left_label"><?php _e( 'Trial', 'frmpp' ) ?></label>
					<label for="<?php echo esc_attr( $this->get_field_id( 'trial' ) ) ?>">
						<input type="checkbox" value="1" name="<?php echo esc_attr( $this->get_field_name( 'trial' ) ) ?>" <?php checked( $form_action->post_content['trial'], 1 ) ?> id="<?php echo esc_attr( $this->get_field_id( 'trial' ) ) ?>" class="frmpp_trial" />
                    	<?php _e( 'Start this subscription with a trial', 'frmpp' ) ?>
                	</label>
				</p>

				<div class="clear"></div>
				<p class="frmpp_trial_opts <?php echo $form_action->post_content['trial'] ? '' : 'frm_hidden'; ?>">
					<label class="frm_left_label"><?php _e( 'Trial amount', 'frmpp' ) ?></label>
					<input type="text" value="<?php echo esc_attr( $form_action->post_content['trial_amount'] ) ?>" name="<?php echo esc_attr( $this->get_field_name( 'trial_amount' ) ) ?>" />
				</p>

				<p class="frmpp_trial_opts <?php echo $form_action->post_content['trial'] ? '' : 'frm_hidden'; ?>">
					<label class="frm_left_label"><?php _e( 'Trial Length', 'frmpp' ) ?></label>
					<input type="number" name="<?php echo esc_attr( $this->get_field_name( 'trial_num' ) ) ?>" value="<?php echo esc_attr( $form_action->post_content['trial_num'] ) ?>" />
					<select name="<?php echo esc_attr( $this->get_field_name( 'trial_time' ) ) ?>">
					<?php foreach ( FrmPaymentsHelper::get_repeat_times() as $k => $v ) { ?>
						<option value="<?php echo esc_attr($k) ?>" <?php selected( $form_action->post_content['trial_time'], $k ) ?>><?php echo $v ?></option>
					<?php } ?>
					</select>
				</p>

				<p>
					<label class="frm_left_label"><?php _e( 'Retry', 'frmpp' ) ?></label>
					<label for="<?php echo esc_attr( $this->get_field_id( 'retry' ) ) ?>">
						<input type="checkbox" value="1" name="<?php echo esc_attr( $this->get_field_name( 'retry' ) ) ?>" <?php checked( $form_action->post_content['retry'], 1 ) ?> id="<?php echo esc_attr( $this->get_field_id( 'retry' ) ) ?>" />
                    	<?php _e( 'Retry a failed payment for a subscription', 'frmpp' ) ?>
                	</label>
				</p>
			</div>
            <p>
                <label class="frm_left_label"><?php _e('Notifications', 'frmpp' ) ?></label>
				<label for="<?php echo esc_attr( $this->get_field_id( 'paypal_stop_email' ) ) ?>">
					<input type="checkbox" value="1" name="<?php echo esc_attr( $this->get_field_name( 'paypal_stop_email' ) ) ?>" <?php checked( $form_action->post_content['paypal_stop_email'], 1 ) ?> id="<?php echo esc_attr( $this->get_field_id( 'paypal_stop_email' ) ) ?>" />
                    <?php _e('Hold email notifications until payment is received.', 'frmpp') ?>
					<span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e( 'Stop all emails set up with this form, including the registration email if applicable. Send them when the successful payment notification is received from PayPal.', 'frmpp' ) ?>" ></span>
                </label>
            </p>

			<p><label class="frm_left_label"><?php _e( 'Currency', 'frmpp' ) ?></label>
				<select name="<?php echo esc_attr( $this->get_field_name( 'currency' ) ) ?>" id="frm_pay_currency">
					<?php foreach ( FrmPaymentsHelper::get_currencies() as $code => $currency ) { ?>
						<option value="<?php echo esc_attr( $code ) ?>" <?php selected( $form_action->post_content['currency'], $code ) ?>><?php echo esc_html( $currency['name'] . ' (' . $code . ')' ); ?></option>
						<?php
						unset( $currency, $code );
					}
					?>
				</select>
			</p>

			<p><label class="frm_left_label"><?php _e( 'Return URL', 'frmpp' ) ?></label>
				<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'return_url' ) ) ?>" id="paypal_return_url" value="<?php echo esc_attr( $form_action->post_content['return_url'] ); ?>" class="frm_not_email_subject frm_with_left_label" />
				<span class="clear"></span>
			</p>

			<p><label class="frm_left_label"><?php _e( 'Cancel URL', 'frmpp' ) ?></label>
				<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'cancel_url' ) ) ?>" id="paypal_cancel_url" value="<?php echo esc_attr( $form_action->post_content['cancel_url'] ); ?>" class="frm_not_email_subject frm_with_left_label" />
				<span class="clear"></span>
			</p>

			<h3><?php _e( 'After Payment', 'frmpp' ) ?>
				<span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e( 'Change a field value when the status of a payment changes.', 'frmpp' ) ?>" ></span>
			</h3>
			<div class="frm_add_remove">
				<p id="frmpp_after_pay_<?php echo absint( $form_action->ID ) ?>" <?php echo empty( $form_action->post_content['change_field'] ) ? '' : 'class="frm_hidden"'; ?>>
					<a href="#" class="frm_add_pp_logic button" data-emailkey="<?php echo absint( $form_action->ID ) ?>">+ <?php _e( 'Add', 'frmpp' ) ?></a>
				</p>
				<div id="postcustomstuff" class="frmpp_after_pay_rows <?php echo empty( $form_action->post_content['change_field'] ) ? 'frm_hidden' : ''; ?>">
					<table id="list-table">
						<thead>
							<tr>
								<th><?php _e( 'Payment Status' ) ?></th>
								<th><?php _e( 'Field', 'frmpp' ) ?></th>
								<th><?php _e( 'Value', 'frmpp' ) ?></th>
								<th style="max-width:60px;"></th>
							</tr>
						</thead>
						<tbody data-wp-lists="list:meta">
							<?php
							foreach ( $form_action->post_content['change_field'] as $row_num => $vals ) {
								$this->after_pay_row( array(
									'form_id' => $args['form']->id, 'row_num' => $row_num, 'form_action' => $form_action,
								) );
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
        </td></tr>
</table>
