<?php
class FrmPaymentSettingsController{
    public static function load_hooks(){
        add_action('frm_add_settings_section', 'FrmPaymentSettingsController::add_settings_section'); // global settings
        add_action('frm_after_duplicate_form', 'FrmPaymentSettingsController::duplicate', 15, 2);

        // < 2.0 fallback
		add_action( 'frm_entry_form', 'FrmPaymentsController::hidden_payment_fields' );

        // 2.0 hooks
		add_action( 'frm_registered_form_actions', 'FrmPaymentSettingsController::register_actions' );
		add_action( 'frm_add_form_option_section', 'FrmPaymentSettingsController::actions_js' );
		add_action( 'frm_before_list_actions', 'FrmPaymentSettingsController::migrate_to_2' );

		add_filter( 'frm_action_triggers', 'FrmPaymentSettingsController::add_payment_trigger' );
		add_filter( 'frm_email_action_options', 'FrmPaymentSettingsController::add_trigger_to_action' );
		add_filter( 'frm_twilio_action_options', 'FrmPaymentSettingsController::add_trigger_to_action' );
		add_filter( 'frm_mailchimp_action_options', 'FrmPaymentSettingsController::add_trigger_to_action' );
		add_filter( 'frm_register_action_options', 'FrmPaymentSettingsController::add_payment_trigger_to_register_user_action' );
		add_filter( 'frm_api_action_options', 'FrmPaymentSettingsController::add_trigger_to_action' );
    }

    public static function add_settings_section($sections){
        $sections['paypal'] = array(
			'class'    => 'FrmPaymentSettingsController',
			'function' => 'route',
			'icon'     => 'frmfont frm_paypal_icon',
		);
        return $sections;
    }
    
    public static function include_logic_row($meta_name, $form_id, $values) {
        if ( !is_callable('FrmProFormsController::include_logic_row') ) {
            return;
        }
        
        FrmProFormsController::include_logic_row(array(
            'meta_name' => $meta_name,
            'condition' => array(
                'hide_field'    => ( isset($values['hide_field']) && isset($values['hide_field'][$meta_name]) ) ? $values['hide_field'][$meta_name] : '',
                'hide_field_cond' => ( isset($values['hide_field_cond']) && isset($values['hide_field_cond'][$meta_name]) ) ? $values['hide_field_cond'][$meta_name] : '',
                'hide_opt'      => ( isset($values['hide_opt']) && isset($values['hide_opt'][$meta_name]) ) ? $values['hide_opt'][$meta_name] : '',
            ),
            'type' => 'paypal',
            'showlast' => '.frm_add_paypal_logic',
            'key' => 'paypal',
            'form_id' => $form_id,
            'id' => 'frm_logic_paypal_'. $meta_name,
            'names' => array(
                'hide_field'    => 'options[paypal_list][hide_field][]',
                'hide_field_cond' => 'options[paypal_list][hide_field_cond][]',
                'hide_opt'      => 'options[paypal_list][hide_opt][]',
            ),
        ));
    }
        
    public static function display_form($errors=array(), $message=''){
        $frm_payment_settings = new FrmPaymentSettings();

        require(FrmPaymentsController::path() .'/views/settings/form.php');
    }

    public static function process_form(){
        $frm_payment_settings = new FrmPaymentSettings();

        //$errors = $frm_payment_settings->validate($_POST,array());
        $errors = array();
        $frm_payment_settings->update($_POST);

        if( empty($errors) ){
            $frm_payment_settings->store();
            $message = __('Settings Saved', 'frmpp');
        }
        
        self::display_form($errors, $message);
    }

	public static function route(){
		$action = isset($_REQUEST['frm_action']) ? 'frm_action' : 'action';
		$action = FrmAppHelper::get_param( $action, '', 'get', 'sanitize_text_field' );
		if ( $action == 'process-form' ) {
			return self::process_form();
		} else {
			return self::display_form();
		}
	}
    
    /**
	 * switch field keys/ids after form is duplicated
	 */
    public static function duplicate($id, $values) {
        if ( is_callable( 'FrmProFieldsHelper::switch_field_ids' ) ) {
            // don't switch IDs unless running Formidabe version that does
            return;
        }
        
        $form = FrmForm::getOne($id);
        $new_opts = $values['options'] = $form->options;
        unset($form);
        
        if ( !isset($values['options']['paypal_item_name']) || empty($values['options']['paypal_item_name']) ) {
            // don't continue if there aren't paypal settings to switch
            return;
        }
        
        global $frm_duplicate_ids;
        
        if ( is_numeric($new_opts['paypal_amount_field']) && isset($frm_duplicate_ids[$new_opts['paypal_amount_field']]) ) {
            $new_opts['paypal_amount_field'] = $frm_duplicate_ids[$new_opts['paypal_amount_field']];
        }
        
        $new_opts['paypal_item_name'] = FrmProFieldsHelper::switch_field_ids($new_opts['paypal_item_name']);
        
        // switch conditional logic
        if ( is_array($new_opts['paypal_list']) && isset($new_opts['paypal_list']['hide_field']) ) {
            foreach ( (array) $new_opts['paypal_list']['hide_field'] as $ck => $cv ) {
                if ( is_numeric($cv) && isset($frm_duplicate_ids[$cv]) ) {
                    $new_opts['paypal_list']['hide_field'][$ck] = $frm_duplicate_ids[$cv];
                }
                
                unset($ck, $cv);
            }
        }
        
        if ( $new_opts != $values['options'] ) {
            global $wpdb;
			$wpdb->update( $wpdb->prefix . 'frm_forms', array( 'options' => maybe_serialize( $new_opts ) ), array( 'id' => absint( $id ) ) );
        }
    }

    public static function register_actions($actions) {
        $actions['paypal'] = 'FrmPaymentAction';
        return $actions;
    }

	public static function actions_js() {
		wp_enqueue_script( 'frmpp', FrmPaymentsHelper::get_file_url( 'frmpp.js' ) );
	}

    public static function migrate_to_2($form) {
        if ( ! isset($form->options['paypal']) || ! $form->options['paypal'] ) {
            return;
        }

        if ( FrmPaymentsHelper::is_below_2() ) {
            return;
        }

        $action_control = FrmFormActionsController::get_form_actions( 'paypal' );
		if ( isset( $form->options['paypal_list'] ) ) {
			$form->options['conditions'] = $form->options['paypal_list'];
			unset($form->options['paypal_list']);
		}

        $post_id = $action_control->migrate_to_2($form);

        return $post_id;
    }

	public static function add_payment_trigger( $triggers ) {
		$triggers['paypal'] = __( 'Successful PayPal payment', 'frmpp' );
		$triggers['paypal-failed'] = __( 'Failed PayPal payment', 'frmpp' );
		return $triggers;
	}

	public static function add_trigger_to_action( $options ) {
		$options['event'][] = 'paypal';
		$options['event'][] = 'paypal-failed';
		return $options;
	}

	/**
	 * Add the payment trigger to registration 2.0+
	 *
	 * @since 3.06
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public static function add_payment_trigger_to_register_user_action( $options ) {
		if ( is_callable( 'FrmRegUserController::register_user' ) ) {
			$options['event'][] = 'paypal';
		}

		return $options;
	}

}