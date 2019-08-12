<?php

class FrmPaymentsHelper{

	/**
	 * Get the url of a file inside the plugin
	 * @since 3.0
	 */
	public static function get_file_url( $file = '' ) {
		return plugins_url( $file, dirname( __FILE__ ) );
	}

    public static function get_default_options(){
		$frm_payment_settings = new FrmPaymentSettings();

        return array(
            'paypal_item_name' => '', 'paypal_amount_field' => '',
            'paypal_amount' => '', 'paypal_list' => array(),
			'paypal_stop_email' => 0,
			'paypal_type' => '', 'repeat_num' => 1, 'repeat_time' => 'M',
			'retry' => 0, 'trial' => 0, 'trial_amount' => '',
			'trial_num' => 1, 'trial_time' => 'M',
			'change_field' => array(),
			'action_id' => 0,
			'business_email' => $frm_payment_settings->settings->business_email,
			'currency'       => $frm_payment_settings->settings->currency,
			'return_url'     => $frm_payment_settings->settings->return_url,
			'cancel_url'     => $frm_payment_settings->settings->cancel_url,
        );
    }

	public static function get_action_setting( $option, $atts = array() ) {
		$frm_payment_settings = new FrmPaymentSettings();
		if ( ! isset( $atts['settings'] ) && isset( $atts['payment'] ) ) {
			$atts['payment'] = (array) $atts['payment'];
			if ( isset( $atts['payment']['action_id'] ) && ! empty( $atts['payment']['action_id'] ) ) {
				$form_action = FrmPaymentAction::get_payment_action( $atts['payment']['action_id'] );
				$atts['settings'] = $form_action->post_content;
			}
		}

		$value = isset( $atts['settings'][ $option ] ) ? $atts['settings'][ $option ] : $frm_payment_settings->settings->{$option};

		return $value;
	}

    /*
    * Check if the version number of Formidable is below 2.0
    */
    public static function is_below_2() {
        $frm_version = is_callable('FrmAppHelper::plugin_version') ? FrmAppHelper::plugin_version() : 0;
        return version_compare( $frm_version, '1.07.19' ) == '-1';
    }

    /*
    * Check global $frm_settings as a 2.0 fallback
    */
    public static function get_settings() {
        global $frm_settings;
        if ( ! empty($frm_settings) ) {
            return $frm_settings;
        } else if ( is_callable('FrmAppHelper::get_settings') ) {
            return FrmAppHelper::get_settings();
        } else {
            return array();
        }
    }

	/**
	 * @since 2.04.02
	 */
	public static function stop_email_set( $form_id, $settings = array() ) {
		if ( empty ( $settings ) ) {
			$form = FrmForm::getOne( $form_id );
			if ( empty( $form ) ) {
				return false;
			}
			$settings = self::get_form_settings( $form );
		}

        return ( isset( $settings['paypal_stop_email'] ) && ! empty( $settings['paypal_stop_email'] ) );
	}

	/**
	 * @since 2.04.02
	 */
	public static function get_form_settings( $form ) {
		$form_settings = $form->options;
		if ( ( isset( $form->options['paypal'] ) && ! empty( $form->options['paypal'] ) ) || ! class_exists( 'FrmFormActionsHelper' ) ) {
			return $form_settings;
		}

		// get the 2.0 form action settings
		$action_control = FrmFormActionsHelper::get_action_for_form( $form->id, 'paypal', 1 );
		if ( ! $action_control ) {
			return;
		}
		$form_settings = $action_control->post_content;

		return $form_settings;
	}

	public static function after_payment_field_dropdown( $atts ) {
		$dropdown = '<select name="' . esc_attr( $atts['name'] ) . '[' . absint( $atts['row_num'] ) . '][id]" >';
		$dropdown .= '<option value="">' . __( '&mdash; Select Field &mdash;', 'frmpp' ) . '</option>';

		foreach ( $atts['form_fields'] as $field ) {
			$selected = selected( $atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['id'], $field->id, false );
			$label = FrmAppHelper::truncate( $field->name, 20 );
			$dropdown .= '<option value="' . esc_attr( $field->id ) . '" '. $selected . '>' . $label . '</option>';
		}
		$dropdown .= '</select>';
		return $dropdown;
	}

	public static function after_payment_status( $atts ) {
		$status = array(
			'complete' => __( 'Completed', 'frmpp' ),
			'failed' => __( 'Failed', 'frmpp' ),
		);
		$input = '<select name="' . esc_attr( $atts['name'] ) . '[' . absint( $atts['row_num'] ) . '][status]">';
		foreach ( $status as $value => $name ) {
			$selected = selected( $atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['status'], $value, false );
			$input .= '<option value="' . esc_attr( $value ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
		}
		$input .= '</select>';
		return $input;
	}

	public static function get_repeat_times() {
		return array(
			'D' => __( 'days', 'frmpp' ),
			'W' => __( 'weeks', 'frmpp' ),
			'M' => __( 'months', 'frmpp' ),
			'Y' => __( 'years', 'frmpp' ),
		);
	}

    public static function get_currencies($currency=false){
        $currencies = array(
            'AUD' => array(
                'name' => __('Australian Dollar', 'frmpp'),
                'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'BRL' => array(
                'name' => __('Brazilian Real', 'frmpp'),
                'symbol_left' => 'R$', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
            ),
            'CAD' => array(
                'name' => __('Canadian Dollar', 'frmpp'),
                'symbol_left' => '$', 'symbol_right' => 'CAD', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'CZK' => array(
                'name' => __('Czech Koruna', 'frmpp'),
                'symbol_left' => '', 'symbol_right' => '&#75;&#269;', 'symbol_padding' => ' ',
                'thousand_separator' => ' ', 'decimal_separator' => ',', 'decimals' => 2,
            ),
            'DKK' => array(
                'name' => __('Danish Krone', 'frmpp'),
                'symbol_left' => 'Kr', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
            ),
            'EUR' => array(
                'name' => __('Euro', 'frmpp'),
                'symbol_left' => '', 'symbol_right' => '&#8364;', 'symbol_padding' => ' ',
                'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
            ),
            'HKD' => array(
                'name' => __('Hong Kong Dollar', 'frmpp'),
                'symbol_left' => 'HK$', 'symbol_right' => '', 'symbol_padding' => '',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'HUF' => array(
                'name' => __('Hungarian Forint', 'frmpp'),
                'symbol_left' => '', 'symbol_right' => 'Ft', 'symbol_padding' => ' ',
                'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
            ),
            'ILS' => array(
                'name' => __('Israeli New Sheqel', 'frmpp'),
                'symbol_left' => '&#8362;', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'JPY' => array(
                'name' => __('Japanese Yen', 'frmpp'),
                'symbol_left' => '&#165;', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '', 'decimals' => 0,
            ),
            'MYR' => array(
                'name' => __('Malaysian Ringgit', 'frmpp'),
                'symbol_left' => '&#82;&#77;', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'MXN' => array(
                'name' => __('Mexican Peso', 'frmpp'),
                'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'NOK' => array(
                'name' => __('Norwegian Krone', 'frmpp'),
                'symbol_left' => 'Kr', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
            ),
            'NZD' => array(
                'name' => __('New Zealand Dollar', 'frmpp'),
                'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'PHP' => array(
                'name' => __('Philippine Peso', 'frmpp'),
                'symbol_left' => 'Php', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'PLN' => array(
                'name' => __('Polish Zloty', 'frmpp'),
                'symbol_left' => '&#122;&#322;', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
            ),
            'GBP' => array(
                'name' => __('Pound Sterling', 'frmpp'),
                'symbol_left' => '&#163;', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'SGD' => array(
                'name' => __('Singapore Dollar', 'frmpp'),
                'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'SEK' => array(
                'name' => __('Swedish Krona', 'frmpp'),
                'symbol_left' => '', 'symbol_right' => 'Kr', 'symbol_padding' => ' ',
                'thousand_separator' => ' ', 'decimal_separator' => ',', 'decimals' => 2,
            ),
            'CHF' => array(
                'name' => __('Swiss Franc', 'frmpp'),
                'symbol_left' => 'Fr.', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => "'", 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'TWD' => array(
                'name' => __('Taiwan New Dollar', 'frmpp'),
                'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'THB' => array(
                'name' => __('Thai Baht', 'frmpp'),
                'symbol_left' => '&#3647;', 'symbol_right' => '', 'symbol_padding' => ' ',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
            'TRY' => array(
                'name' => __('Turkish Liras', 'frmpp'),
                'symbol_left' => '', 'symbol_right' => '&#8364;', 'symbol_padding' => ' ',
                'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
            ),
            'USD' => array(
                'name' => __('U.S. Dollar', 'frmpp'),
                'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' =>  '',
                'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
            ),
			'UYU' => array(
				'name' => __('Uruguayan Peso', 'frmpp'),
				'symbol_left' => '$U', 'symbol_right' => '', 'symbol_padding' =>  '',
				'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 0,
			),
        );

		$currencies = apply_filters( 'frm_currencies', $currencies );
		if ( $currency ) {
			$currency = strtoupper( $currency );
			if ( isset( $currencies[ $currency ] ) ) {
				$currencies = $currencies[ $currency ];
			}
		}
            
        return $currencies;
    }
    
    public static function format_for_url($value){
        if ( seems_utf8($value) ) {
            $value = utf8_uri_encode($value, 200);
        } else {
            $value = strip_tags($value);
        }
        $value = urlencode($value);
        return $value;
    }
    
	public static function formatted_amount( $payment ) {
		$frm_payment_settings = new FrmPaymentSettings();
		$currency = $frm_payment_settings->settings->currency;
		$amount = $payment;

		if ( is_object( $payment ) || is_array( $payment ) ) {
			$payment = (array) $payment;
			$amount = $payment['amount'];
			$currency = self::get_action_setting( 'currency', array( 'payment' => $payment ) );
		}

		$currency = self::get_currencies( $currency );

		$formatted = $currency['symbol_left'] . $currency['symbol_padding'] .
			number_format( $amount, $currency['decimals'], $currency['decimal_separator'], $currency['thousand_separator'] ) .
			$currency['symbol_padding'] . $currency['symbol_right'];

		return $formatted;
	}

    public static function get_rand($length){
        $all_g = 'ABCDEFGHIJKLMNOPQRSTWXZ';
        $all_len = strlen($all_g) - 1;
        $pass = '';
        for ( $i=0; $i < $length; $i++ ) {
            $pass .= $all_g[ rand(0, $all_len) ];
        }
        return $pass;
    }

	public static function base_paypal_url() {
		$frm_payment_settings = new FrmPaymentSettings();
		$paypal_url = 'www.' . ( $frm_payment_settings->settings->environment == 'sandbox' ? 'sandbox.' : '' );
		$paypal_url .= 'paypal.com';
		return $paypal_url;
	}

	public static function paypal_url() {
		$paypal_url = 'https://' . self::base_paypal_url() . '/cgi-bin/webscr/';
		return $paypal_url;
	}

    public static function verify_ipn() {
		$paypal_url = self::paypal_url();

        $log_data = array('last_error' => '', 'ipn_response' => '', 'ipn_data' => array());     

        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        
        foreach ($_POST as $key => $value) { 
           $log_data['ipn_data'][$key] = $value;
           $value = urlencode(stripslashes($value));
           $req .= "&{$key}={$value}";
        }

		$remote_post_vars      = array(
			'timeout'          => 45,
			'httpversion'      => '1.1',
			'headers'          => array(
				'host'         => self::base_paypal_url(),
				'connection'   => 'close',
				'content-type' => 'application/x-www-form-urlencoded',
				'post'         => '/cgi-bin/webscr HTTP/1.1',
			),
			'sslverify'        => false,
			'body'             => $req,
		);

		// post back to PayPal system to validate
		$resp = wp_remote_post( $paypal_url, $remote_post_vars );
        $body = wp_remote_retrieve_body( $resp );
        
        if ( $resp == 'error' || is_wp_error($resp) ) {
            $log_data['ipn_response'] = __('You had an error communicating with the PayPal API.', 'frmpp');
            $log_data['ipn_response'] .= ' '. $resp->get_error_message();
        } else {
            $log_data['ipn_response'] = $body;
        }
        
        if ($log_data['ipn_response'] == 'VERIFIED'){
           // Valid IPN transaction
           self::log_ipn_results(true, $log_data);
           return true;
        }else{
           // Invalid IPN transaction.  Check the log for details.
           $log_data['last_error'] = 'IPN Validation Failed.';
           self::log_ipn_results(false, $log_data);   
           return false;
        }
    }
    
	public static function log_ipn_results( $success, $log_data ) {
		$text = '[' . date('m/d/Y g:i A') . '] - ';

		// Success or failure being logged?
		$text .= $success ? "SUCCESS!\n" : 'FAIL: ' . $log_data['last_error'] ."\n";

		// Log the POST variables
		$text .= "IPN POST Vars from Paypal:\n";
		foreach ( $log_data['ipn_data'] as $key => $value ) {
			$text .= $key . '=' . $value . ', ';
		}

		// Log the response from the paypal server
		$text .= "\nIPN Response from Paypal Server:\n ". $log_data['ipn_response'];

		// Write to log
		self::log_message( $text );
	}
    
	public static function log_message( $text ) {
		$frm_payment_settings = new FrmPaymentSettings();
		if ( ! $frm_payment_settings->settings->ipn_log ) {
			return;  // is logging turned off?
		}

		$logged = false;
		$access_type = get_filesystem_method();
		if ( $access_type === 'direct' ) {
			$creds = request_filesystem_credentials( site_url() .'/wp-admin/', '', false, false, array() );

			// initialize the API
			if ( WP_Filesystem( $creds ) ) {

				global $wp_filesystem;

				$chmod_dir = defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : ( fileperms( ABSPATH ) & 0777 | 0755 );

				$log = $wp_filesystem->get_contents( $frm_payment_settings->settings->ipn_log_file );
				$log .= $text . "\n\n";
				$wp_filesystem->put_contents( $frm_payment_settings->settings->ipn_log_file, $log, 0600 );
				$logged = true;
			}
		}

		if ( ! $logged ) {
			error_log( $text );
		}
	}

    /**
	 * Used for debugging, this function will output all the field/value pairs
	 * that are currently defined in the instance of the class using the
	 * add_field() function.
	 */
    public static function dump_fields($fields) {
        ksort($fields);
?>
<h3>FrmPaymentsHelper::dump_fields() Output:</h3>
<table width="95%" border="1" cellpadding="2" cellspacing="0">
    <tr>
        <td bgcolor="black"><b><font color="white">Field Name</font></b></td>
        <td bgcolor="black"><b><font color="white">Value</font></b></td>
    </tr> 

<?php foreach ($fields as $key => $value) { ?>
    <tr><td><?php echo $key ?></td>
        <td><?php echo urldecode($value) ?>&nbsp;</td>
    </tr>
<?php } ?>
</table>
<br/>
<?php
    }

}
