<?php

class FrmPaymentsController{
	public static $min_version = '3.0';
	public static $db_version = 2;
	public static $db_opt_name = 'frm_pay_db_version';

	public static function load_hooks() {
		add_action( 'plugins_loaded', 'FrmPaymentsController::load_lang' );
		register_activation_hook( dirname( dirname( __FILE__ ) ) . '/formidable-paypal.php', 'FrmPaymentsController::install' );

		if ( is_admin() ) {
			add_action( 'admin_menu', 'FrmPaymentsController::menu', 26 );
			add_filter( 'frm_nav_array', 'FrmPaymentsController::frm_nav', 30 );
			add_filter( 'plugin_action_links_formidable-paypal/formidable-paypal.php', 'FrmPaymentsController::settings_link', 10, 2 );
			add_action( 'after_plugin_row_formidable-paypal/formidable-paypal.php', 'FrmPaymentsController::min_version_notice' );
			add_action( 'admin_notices', 'FrmPaymentsController::get_started_headline' );
			add_action( 'admin_init', 'FrmPaymentsController::load_updater' );
			add_action( 'wp_ajax_frmpay_install', 'FrmPaymentsController::install' );
			add_filter( 'set-screen-option', 'FrmPaymentsController::save_per_page', 10, 3 );
			add_action( 'wp_ajax_frm_payments_paypal_ipn', 'FrmPaymentsController::paypal_ipn' );
			add_action( 'wp_ajax_nopriv_frm_payments_paypal_ipn', 'FrmPaymentsController::paypal_ipn' );

			add_filter( 'frm_form_options_before_update', 'FrmPaymentsController::update_options', 15, 2 );
			add_action( 'frm_show_entry_sidebar', 'FrmPaymentsController::sidebar_list' );
		}

		// 2.0 hook
		add_action( 'frm_trigger_paypal_create_action', 'FrmPaymentsController::create_payment_trigger', 10, 3 );

		// < 2.0 hook
		add_action( 'frm_after_create_entry', 'FrmPaymentsController::pre_v2_maybe_redirect', 30, 2 );

		add_filter( 'frm_csv_columns', 'FrmPaymentsController::add_payment_to_csv', 20, 2 );
	}

	public static function path() {
		return dirname( dirname( __FILE__ ) );
	}

	public static function load_lang() {
		load_plugin_textdomain( 'frmpp', false, 'formidable-paypal/languages/' );
	}

	public static function menu() {
		$frm_settings = FrmPaymentsHelper::get_settings();
		$menu = $frm_settings ? $frm_settings->menu : 'Formidable';
		add_submenu_page( 'formidable', $menu . ' | PayPal', 'PayPal', 'frm_view_entries', 'formidable-payments', 'FrmPaymentsController::route' );

		add_filter( 'manage_' . sanitize_title( $menu ) . '_page_formidable-payments_columns', 'FrmPaymentsController::payment_columns' );
		add_filter( 'manage_' . sanitize_title( $menu ) . '_page_formidable-entries_columns', 'FrmPaymentsController::entry_columns', 20 );
		add_filter( 'frm_entries_payments_column', 'FrmPaymentsController::entry_payment_column', 10, 2 );
		add_filter( 'frm_entries_current_payment_column', 'FrmPaymentsController::entry_current_payment_column', 10, 2 );
		add_filter( 'frm_entries_payment_expiration_column', 'FrmPaymentsController::entry_payment_expiration_column', 10, 2 );
	}

	public static function frm_nav( $nav ) {
		if ( current_user_can( 'frm_view_entries' ) ) {
			$nav['formidable-payments'] = 'PayPal';
		}

		return $nav;
	}

	public static function payment_columns( $cols = array() ) {
		add_screen_option( 'per_page', array(
			'label' => __( 'Payments', 'frmpp' ), 'default' => 20,
			'option' => 'formidable_page_formidable_payments_per_page',
		) );

		return array(
			'cb'         => '<input type="checkbox" />',
			'receipt_id' => __( 'Receipt ID', 'frmpp' ),
			'user_id'    => __( 'User', 'frmpp' ),
			'item_id'    => __( 'Entry', 'frmpp' ),
			'form_id'    => __( 'Form', 'frmpp' ),
			'completed'  => __( 'Completed', 'frmpp' ),
			'amount'     => __( 'Amount', 'frmpp' ),
			'created_at' => __( 'Date', 'frmpp' ),
			'begin_date' => __( 'Begin Date', 'frmpp' ),
			'expire_date' => __( 'Expire Date', 'frmpp' ),
			'paysys'     => __( 'Processor', 'frmpp' ),
		);
	}

	// Adds a settings link to the plugins page
	public static function settings_link( $links, $file ) {
		$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=formidable-settings' ) ) . '">' . __( 'Settings', 'frmpp' ) . '</a>';
		array_unshift( $links, $settings );

		return $links;
	}

	public static function min_version_notice(){
		$frm_version = is_callable( 'FrmAppHelper::plugin_version' ) ? FrmAppHelper::plugin_version() : 0;

		// check if Formidable meets minimum requirements
		if ( version_compare( $frm_version, self::$min_version, '>=' ) ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		echo '<tr class="plugin-update-tr active"><th colspan="' . esc_attr( $wp_list_table->get_column_count() ) . '" class="check-column plugin-update colspanchange"><div class="update-message">' .
		__('You are running an outdated version of Formidable. This plugin may not work correctly if you do not update Formidable.', 'frmpp') .
		'</div></td></tr>';
	}

	public static function get_started_headline(){
		// Don't display this error as we're upgrading
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		if ( $action == 'upgrade-plugin' && ! isset( $_GET['activate'] ) ) {
			return;
		}

		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		$db_version = get_option( self::$db_opt_name );
		if ( (int) $db_version < self::$db_version ) {
			if ( is_callable( 'FrmAppHelper::plugin_url' ) ) {
				$url = FrmAppHelper::plugin_url();
			} else if ( defined( 'FRM_URL' ) ) {
				$url = FRM_URL;
			} else {
				return;
			}
			include( self::path() . '/views/notices/update_database.php' );
		}
	}

	public static function load_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			FrmPaymentUpdate::load_hooks();
		}
	}
	public static function install( $old_db_version = false ) {
		$frm_payment_db = new FrmPaymentDb();
		$frm_payment_db->upgrade( $old_db_version );
	}

	private static function show( $id ) {
		if ( ! $id ) {
			die( __( 'Please select a payment to view', 'frmpp' ) );
		}
        
		global $wpdb;
		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_payments p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id ) );

		$user_name = '';
		if ( $payment->user_id ) {
			$user = get_userdata( $payment->user_id );
			if ( $user ) {
				$user_name = '<a href="' . esc_url( admin_url('user-edit.php?user_id=' . $payment->user_id ) ) . '">' . $user->display_name . '</a>';
			}
		}
        
		include( self::path() .'/views/payments/show.php' );
	}

	private static function display_list( $message = '', $errors = array() ) {
		$title = __( 'Downloads', 'frmpp' );
		$wp_list_table = new FrmPaymentsListHelper();
        
		$pagenum = $wp_list_table->get_pagenum();
        
		$wp_list_table->prepare_items();

		$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );
		if ( $pagenum > $total_pages && $total_pages > 0 ) {
			// if the current page is higher than the total pages,
			// reset it and prepare again to get the right entries
			$_GET['paged'] = $_REQUEST['paged'] = $total_pages;
			$pagenum = $wp_list_table->get_pagenum();
			$wp_list_table->prepare_items();
		}

		include( self::path() . '/views/payments/list.php' );
	}

	public static function save_per_page( $save, $option, $value ) {
		if ( $option == 'formidable_page_formidable_payments_per_page' ) {
			$save = absint( $value );
		}
		return $save;
	}

	public static function create_payment_trigger( $action, $entry, $form ) {
		if ( ! isset( $action->v2 ) ) {
			// 2.0 fallback - prevent extra processing
			remove_action( 'frm_after_create_entry', 'FrmPaymentsController::pre_v2_maybe_redirect', 30 );
		}

		return self::maybe_redirect_for_payment( $action->post_content, $entry, $form );
	}

	public static function pre_v2_maybe_redirect( $entry_id, $form_id ) {
		if ( ! $_POST || ! isset( $_POST['frm_payment'] ) || ( is_admin() && ! defined( 'DOING_AJAX' ) ) ) {
			return;
		}

		$form = FrmForm::getOne( $form_id );
        
		// make sure PayPal is enabled
		if ( ! isset( $form->options['paypal'] ) || ! $form->options['paypal'] ) {
			return;
		}

		_deprecated_function( __FUNCTION__, '2.04', 'Please update your payment settings for this form' );

		if ( is_callable( 'FrmProEntriesHelper::get_field' ) && FrmProEntriesHelper::get_field( 'is_draft', $entry_id ) ) {
			// don't send to PayPal if this is a draft
			return;
		}

		//check conditions
		$redirect = true;
		if ( isset( $form->options['paypal_list']['hide_field']) && is_array( $form->options['paypal_list']['hide_field']) && class_exists( 'FrmProFieldsHelper' ) ) {
			foreach ( $form->options['paypal_list']['hide_field'] as $hide_key => $hide_field ) {
				$observed_value = ( isset( $_POST['item_meta'][ $hide_field ] ) ) ? sanitize_text_field( $_POST['item_meta'][ $hide_field ] ) : '';

				if ( is_callable( 'FrmFieldsHelper::value_meets_condition' ) ) {
					// 2.0+
					$class_name = 'FrmFieldsHelper';
				} else {
					// < 2.0 falback
					$class_name = 'FrmProFieldsHelper';
				}
				$redirect = call_user_func_array( array( $class_name, 'value_meets_condition' ), array( $observed_value, $form->options['paypal_list']['hide_field_cond'][ $hide_key ], $form->options['paypal_list']['hide_opt'][ $hide_key ] ) );
				if ( ! $redirect ) {
					break;
				}
			}
		}

        if ( ! $redirect ) {
            // don't pay if conditional logic is not met
            return;
        }

        // turn into an object to match with 2.0
        $entry = new stdClass();
        $entry->id = $entry_id;

        return self::maybe_redirect_for_payment( $form->options, $entry, $form );
    }

	public static function maybe_redirect_for_payment( $settings, $entry, $form ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			// don't send to PayPal if submitting from the back-end
			return;
		}

		$amount = self::get_amount( $form, $settings, $entry );
        if ( empty($amount) ) {
            return;
        }

		// stop the emails if payment is required
		if ( FrmPaymentsHelper::stop_email_set( $form, $settings ) ) {
			self::stop_form_emails();
			add_filter( 'frm_to_email', 'FrmPaymentsController::stop_the_email', 20, 4 );
			add_filter( 'frm_send_new_user_notification', 'FrmPaymentsController::stop_registration_email', 10, 3 );
		}

		// save in global for use building redirect url
		global $frm_pay_form_settings;
		$frm_pay_form_settings = $settings;

		// trigger payment redirect after other functions have a chance to complete
		add_action( 'frm_after_create_entry', 'FrmPaymentsController::redirect_for_payment', 50, 2 );

		return true;
	}

	/**
	 * Disable the admin notification emails since most users won't need these
	 *
	 * @since 3.07
	 */
	private static function stop_form_emails() {
		if ( is_callable( 'FrmNotification::stop_emails') ) {
			FrmNotification::stop_emails();
		} else {
			remove_action( 'frm_trigger_email_action', 'FrmNotification::trigger_email', 10 );
		}

	}

	public static function redirect_for_payment( $entry_id, $form_id ) {
		global $frm_pay_form_settings;

		$form = FrmForm::getOne( $form_id );

		$atts = array(
			'action_settings' => $frm_pay_form_settings,
			'form'     => $form,
			'entry_id' => $entry_id,
			'entry'    => FrmEntry::getOne( $entry_id, true ),
		);

		$atts['amount'] = self::get_amount( $atts['form'], $atts['action_settings'], $atts['entry'] );
		if ( empty( $atts['amount'] ) ) {
			return;
		}

		$paypal_url = self::get_paypal_url( $atts );

		if ( is_callable( 'FrmAppHelper::plugin_version' ) ) {
			$frm_version = FrmAppHelper::plugin_version();
		} else {
			global $frm_version; //global fallback
		}
        
		add_filter( 'frm_redirect_url', 'FrmPaymentsController::redirect_url', 9, 3 );
		$conf_args = array( 'paypal_url' => $paypal_url );
		if ( defined( 'DOING_AJAX' ) && isset( $form->options['ajax_submit'] ) && $form->options['ajax_submit'] && $_POST && isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'frm_entries_create', 'frm_entries_update' ) ) ) {
			$conf_args['ajax'] = true;
		}

		if ( is_callable('FrmProEntriesController::confirmation') ) {
			FrmProEntriesController::confirmation( 'redirect', $form, $form->options, $entry_id, $conf_args );
		} else {
			$conf_args['id'] = $entry_id;
			self::confirmation( $form, $conf_args );
		}
	}

	/**
	* Redirect to PayPal when the pro version isn't installed
	*/
	public static function confirmation( $form, $args ) {
		global $frm_vars;

		add_filter( 'frm_use_wpautop', '__return_false' );

		$success_url = apply_filters( 'frm_redirect_url', '', $form, $args );
		$success_url = str_replace( array( ' ', '[', ']', '|', '@' ), array( '%20', '%5B', '%5D', '%7C', '%40' ), $success_url );

		if ( ! headers_sent() ) {
			wp_redirect( esc_url_raw( $success_url ) );
			die();
		} else {
			add_filter('frm_use_wpautop', '__return_true');

			$success_msg = isset( $form->options['success_msg'] ) ? $form->options['success_msg'] : __( 'Please wait while you are redirected.', 'formidable' );

			$redirect_msg = '<div class="' . esc_attr( FrmFormsHelper::get_form_style_class( $form ) ) . '"><div class="frm-redirect-msg frm_message">' . $success_msg . '<br/>' .
				sprintf(__( '%1$sClick here%2$s if you are not automatically redirected.', 'formidable' ), '<a href="'. esc_url($success_url) .'">', '</a>') .
				'</div></div>';

			$redirect_msg = apply_filters( 'frm_redirect_msg', $redirect_msg, array(
				'entry_id' => $args['id'], 'form_id' => $form->id, 'form' => $form
			) );

			echo $redirect_msg;
			echo "<script type='text/javascript'>jQuery(document).ready(function(){ setTimeout(window.location='" . esc_url_raw( $success_url ) . "', 8000); });</script>";
		}
	}

	public static function get_amount( $form, $settings = array(), $entry = array() ) {
		if ( empty( $settings ) ) {
			// for reverse compatibility
			$settings = $form->options;
		}
		$amount_field = isset( $settings['paypal_amount_field'] ) ? $settings['paypal_amount_field'] : '';
		$amount = 0;
		if ( empty( $entry ) && ! empty( $amount_field ) && isset( $_POST['item_meta'][ $amount_field ] ) ) {
			// for reverse compatibility for custom code
			$amount = sanitize_text_field( $_POST['item_meta'][ $amount_field ] );
		} else if ( ! empty( $amount_field ) && isset( $entry->metas[ $amount_field ] ) ) {
			$amount = $entry->metas[ $amount_field ];
		} else if ( isset( $settings['paypal_amount'] ) ) {
			$amount = $settings['paypal_amount'];
		}

		if ( empty( $amount ) ) {
			// no amount has been set
			return 0;
		}

		return self::prepare_amount( $amount, $settings );
	}

	/**
	 * @since 3.08
	 */
	private static function prepare_amount( $amount, $settings ) {
		$currency = FrmPaymentsHelper::get_action_setting( 'currency', array( 'settings' => $settings ) );
		$currencies = FrmPaymentsHelper::get_currencies( $currency );

		$total = 0;
		foreach ( (array) $amount as $a ) {
			$this_amount = trim( $a );
			preg_match_all( '/[0-9,.]*\.?\,?[0-9]+/', $this_amount, $matches );
			$this_amount = $matches ? end( $matches[0] ) : 0;
			$this_amount = self::maybe_use_decimal( $this_amount, $currencies );
			$this_amount = str_replace( $currencies['decimal_separator'], '.', str_replace( $currencies['thousand_separator'], '', $this_amount ) );
			$this_amount = round( (float) $this_amount, $currencies['decimals'] );
			$total += $this_amount;
			unset( $a, $this_amount, $matches );
		}

		return $total;
	}

	private static function maybe_use_decimal( $amount, $currencies ) {
		if ( $currencies['thousand_separator'] == '.' ) {
			$amount_parts = explode( '.', $amount );
			$used_for_decimal = ( count( $amount_parts ) == 2 && strlen( $amount_parts[1] ) == 2 );
			if ( $used_for_decimal ) {
				$amount = str_replace( '.', $currencies['decimal_separator'], $amount );
			}
		}
		return $amount;
	}

	public static function get_paypal_url( $atts ) {

		$paypal_params = self::prepare_paypal_params( $atts );
		self::add_invoice_to_url( $atts, $paypal_params );

		return self::convert_array_to_paypal_url( $paypal_params, $atts );
	}

	/**
	 * @since 3.08
	 */
	private static function prepare_paypal_params( $atts ) {
		$paypal_params = array(
			'cmd'           => ( ( isset( $atts['action_settings']['paypal_type'] ) && ! empty( $atts['action_settings']['paypal_type'] ) ) ? $atts['action_settings']['paypal_type'] : '_xclick' ),
			'notify_url'    => admin_url( 'admin-ajax.php?action=frm_payments_paypal_ipn' ),
			'custom'        => $atts['entry_id'] . '|' . wp_hash( $atts['entry_id'] ),
			'amount'        => $atts['amount'],
			'bn'            => 'FormidablePro_SP',
		);

		if ( defined('ICL_LANGUAGE_CODE') ) {
			$paypal_params['lc'] = ICL_LANGUAGE_CODE;
		}

		self::check_global_fallbacks( $atts['action_settings'] );

		$atts['mapping'] = self::get_base_url_map();
		self::add_mapping_to_url( $atts, $paypal_params );

		self::prevent_insecure_data_message( $paypal_params );
		self::add_subscription_params( $atts, $paypal_params );

		return $paypal_params;
	}

	/**
	 * @since 3.08
	 */
	private static function get_base_url_map() {
		return array(
			'business'      => array( 'name' => 'business_email', 'allow' => true ),
			'currency_code' => array( 'name' => 'currency', 'allow' => false ),
			'item_name'     => array( 'name' => 'paypal_item_name', 'allow' => true ),
			'return'        => array( 'name' => 'return_url', 'allow' => true ),
			'cancel_return' => array( 'name' => 'cancel_url', 'allow' => true ),
		);
	}

	/**
	 * @since 3.08
	 */
	private static function check_global_fallbacks( &$settings ) {
		$globals = array( 'business_email', 'currency', 'return_url', 'cancel_url' );
		foreach ( $globals as $name ) {
			$settings[ $name ] = FrmPaymentsHelper::get_action_setting( $name, array( 'settings' => $settings ) );
		}
	}

	/**
	 * @since 3.08
	 */
	private static function convert_array_to_paypal_url( $paypal_params, $atts ) {
		$paypal_url = FrmPaymentsHelper::paypal_url();
		$paypal_url .= '?' . http_build_query( $paypal_params, '', '&' );

		return apply_filters( 'formidable_paypal_url', $paypal_url, $atts['entry_id'], $atts['form']->id );
	}

	public static function prevent_insecure_data_message( &$paypal_params ) {
		if ( strpos( $paypal_params['return'], 'https://' ) !== 0 ) {
			$paypal_params['rm'] = 1;
		}
	}

	public static function add_subscription_params( $atts, &$paypal_params ) {
		if ( $paypal_params['cmd'] == '_xclick-subscriptions' ) {

			$paypal_params['a3'] = $atts['amount'];
			$paypal_params['src'] = 1;

			$atts['mapping'] = self::get_subscription_map( $atts['action_settings']['trial'] );

			self::add_mapping_to_url( $atts, $paypal_params );
		}
	}

	/**
	 * @since 3.08
	 */
	private static function get_subscription_map( $has_trail ) {
		$mapping = array(
			'p3'  => array( 'name' => 'repeat_num', 'sanitize' => 'absint', 'allow' => true ),
			't3'  => array( 'name' => 'repeat_time', 'allow' => false ),
			'sra' => array( 'name' => 'retry', 'allow' => false ),
		);

		if ( $has_trail ) {
			$mapping['a1'] = array( 'name' => 'trial_amount', 'sanitize' => 'amount', 'allow' => true );
			$mapping['p1'] = array( 'name' => 'trial_num', 'sanitize' => 'absint', 'allow' => true );
			$mapping['t1'] = array( 'name' => 'trial_time', 'allow' => false );
		}

		return $mapping;
	}

	/**
	 * @since 3.08
	 */
	private static function add_mapping_to_url( $atts, &$paypal_params ) {
		foreach ( $atts['mapping'] as $param => $setting ) {
			$paypal_params[ $param ] = $atts['action_settings'][ $setting['name'] ];
			if ( $setting['allow'] ) {
				$paypal_params[ $param ] = self::process_shortcodes( array(
					'value' => $paypal_params[ $param ],
					'form'  => $atts['form'],
					'entry' => $atts['entry'],
				) );
			}

			if ( isset( $setting['sanitize'] ) ) {
				if ( $setting['sanitize'] == 'amount' ) {
					$paypal_params[ $param ] = self::prepare_amount( $paypal_params[ $param ], $atts['action_settings'] );
				} else {
					$paypal_params[ $param ] = call_user_func( $setting['sanitize'], $paypal_params[ $param ] );
				}
			}
		}
	}

	public static function add_invoice_to_url( $atts, &$paypal_params ) {
		global $wpdb;
		$invoice = self::create_invoice_for_payment( $atts, $paypal_params );
		$invoice = $invoice ? $wpdb->insert_id . '-' . FrmPaymentsHelper::get_rand(3) : $atts['form']->id . '_' . $atts['entry_id'];
		$paypal_params['invoice'] = $invoice;
	}

	public static function create_invoice_for_payment( $atts, $paypal_params ) {
		if ( $paypal_params['cmd'] == '_xclick-subscriptions' && isset( $paypal_params['p1'] ) && $paypal_params['p1'] ) {
			$amount = $paypal_params['a1'];
		} else {
			$amount = $atts['amount'];
		}

		$frm_payment = new FrmPayment();
		$invoice = $frm_payment->create( array(
			'item_id'     => $atts['entry_id'],
			'amount'      => $amount,
			'expire_date' => self::calcuate_next_payment_date( $atts['action_settings'], array( 'first_payment' => true ) ),
			'action_id'   => isset( $atts['action_settings']['action_id'] ) ? $atts['action_settings']['action_id'] : 0,
		) );

		return $invoice;
	}

	public static function redirect_url( $url, $form, $args = array( )) {
		if ( isset( $args['paypal_url'] ) ) {
			//only change it if it came from this plugin
			$url = $args['paypal_url'];
		}

		return $url;
	}
    
	public static function stop_registration_email( $send_it, $form, $entry_id ) {
		if ( ! is_callable( 'FrmRegAppController::send_paid_user_notification' ) ) {
			// don't stop the registration email unless the function exists to send it later
			return $send_it;
		}
        
		if ( ! isset( $_POST['payment_completed'] ) || empty( $_POST['payment_completed'] ) ) {
			// stop the email if payment is not completed
			$send_it = false;
		}
        
		return $send_it;
	}
    
    public static function stop_the_email($emails, $values, $form_id, $args = array()) {
		if ( isset( $_POST['payment_completed'] ) && absint( $_POST['payment_completed'] ) ) {
			// always send the email if the payment was just completed
			return $emails;
		}

		$action = FrmAppHelper::get_post_param( 'action', '', 'sanitize_title' );
		$frm_action = FrmAppHelper::get_post_param( 'frm_action', '', 'sanitize_title' );
		if ( isset($args['entry']) && $action == 'frm_entries_send_email' ) {
            // if resending, make sure the payment is complete first
			global $wpdb;
			$complete = FrmDb::get_var( $wpdb->prefix .'frm_payments', array( 'item_id' => $args['entry']->id, 'completed' => 1 ), 'completed' );

		} else {
			// send the email when resending the email, and we don't know if the payment is complete
			$complete = ( ! isset( $args['entry'] ) && ( $frm_action == 'send_email' || $action == 'frm_entries_send_email' ) );
        }
            
        //do not send if payment is not complete
		if ( ! $complete ) {
            $emails = array();
        }
        
        return $emails;
    }

    //Trigger the email to send after a payment is completed:
    public static function send_email_now($vars, $payment, $entry) {
        if ( !isset($vars['completed']) || !$vars['completed']){
            //only send the email if payment is completed
            return;
        }
        
        $_POST['payment_completed'] = true; //to let the other function know to send the email
		if ( is_callable( 'FrmFormActionsController::trigger_actions' ) ) {
			// 2.0
			FrmFormActionsController::trigger_actions( 'create', $entry->form_id, $entry->id, 'email' );
		} else {
			// < 2.0
			FrmProNotification::entry_created( $entry->id, $entry->form_id );
		}

        // trigger registration email
		if ( is_callable( 'FrmRegNotification::send_paid_user_notification' ) ) {
			FrmRegNotification::send_paid_user_notification( $entry );
		} else if ( is_callable( 'FrmRegAppController::send_paid_user_notification' ) ) {
			FrmRegAppController::send_paid_user_notification( $entry );
		}
    }
    
    public static function paypal_ipn(){
		if ( ! FrmPaymentsHelper::verify_ipn() ) {
			FrmPaymentsHelper::log_message( __( 'The payment notification could not be verified.', 'frmpp' ) );
			wp_die();
		}

		$custom = FrmAppHelper::get_post_param( 'custom', '', 'sanitize_text_field' );
		if ( empty( $custom ) ) {
			FrmPaymentsHelper::log_message( __( 'The custom value from PayPal is empty.', 'frmpp' ) );
			wp_die();
		}

        //get entry associated with this payment
		list( $entry_id, $hash ) = explode( '|', $custom );
		$entry_id = absint( $entry_id );

		if ( ! self::is_valid_entry_id( $entry_id, $hash ) ) {
			FrmPaymentsHelper::log_message( __( 'The IPN appears to have been tampered with.', 'frmpp' ) );
			wp_die();
		}

		$entry = FrmEntry::getOne( $entry_id );
		if ( ! $entry ) {
			FrmPaymentsHelper::log_message( __( 'The IPN does not match an existing entry.', 'frmpp' ) );
			wp_die();
		}

		$invoice = FrmAppHelper::get_post_param( 'invoice', '', 'absint' );
		$txn_id = FrmAppHelper::get_post_param( 'txn_id', '', 'sanitize_text_field' );

        //mark as paid
        global $wpdb;
		if ( $invoice ) {
			$payment = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'frm_payments WHERE ( id=%d AND item_id=%d AND (receipt_id = %s OR receipt_id = %s) ) OR receipt_id = %s', $invoice, $entry_id, $txn_id, '', $txn_id ) );
		} else {
			$payment = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'frm_payments WHERE item_id=%d ORDER BY id ASC', $entry_id ) );
		}

		$payment_gross = FrmAppHelper::get_post_param( 'payment_gross', '', 'sanitize_text_field' );
		$mc_gross = FrmAppHelper::get_post_param( 'mc_gross', '', 'sanitize_text_field' );

		$amt = ( $payment_gross != '' && $payment_gross > 0.0 ) ? $payment_gross : $mc_gross;

		if ( ! $payment ) {
			self::maybe_create_payment( compact( 'entry', 'amt', 'txn_id' ), $payment );
			if ( ! $payment ) {
				FrmPaymentsHelper::log_message( __( 'The IPN does not match an existing payment.', 'frmpp' ) );
				wp_die();
			}
		}

		if ( ! self::email_addresses_match( $payment ) ) {
			FrmPaymentsHelper::log_message( __( 'The receiving email address in the IPN does not match the settings.', 'frmpp' ) );
			wp_die();
		}

		$pay_vars = (array) $payment;
		if ( ! $payment->receipt_id ) {
            $pay_vars['receipt_id'] = $txn_id;
		}
        
		if ( ! empty( $pay_vars['meta_value'] ) && ! empty( $pay_vars['meta_value'] ) ) {
			$pay_vars['meta_value'] = maybe_unserialize( $pay_vars['meta_value'] );
		} else {
            $pay_vars['meta_value'] = array();
		}

		$ipn_track_id = FrmAppHelper::get_post_param( 'ipn_track_id', '', 'sanitize_text_field' );

		$pay_vars['meta_value'][ $ipn_track_id ] = array();
		foreach ( $_POST as $k => $v ) {
			$pay_vars['meta_value'][ $ipn_track_id ][ sanitize_text_field( $k ) ] = sanitize_text_field( $v );
		}
        $pay_vars['meta_value'] = maybe_serialize( $pay_vars['meta_value'] );

		$payment_status = FrmAppHelper::get_post_param( 'payment_status', '', 'sanitize_text_field' );
		$pay_vars['completed'] = ( $payment_status == 'Completed' );
        
        do_action( 'frm_payment_paypal_ipn', compact( 'pay_vars', 'payment', 'entry' ) );

		self::compare_payment_amount( $amt, $payment );

        $u = $wpdb->update( $wpdb->prefix .'frm_payments', $pay_vars, array( 'id' => $payment->id ) );
        if ( ! $u ) {
            FrmPaymentsHelper::log_message( sprintf( __( 'Payment %d was complete, but failed to update.', 'frmpp' ), $payment->id ) );
            wp_die();
        }
        
		FrmPaymentsHelper::log_message( __( 'Payment successfully updated.', 'frmpp' ) );
		self::actions_after_ipn( $pay_vars, $entry );

		if ( FrmPaymentsHelper::stop_email_set( $entry->form_id ) ) {
			self::send_email_now( $pay_vars, $payment, $entry );
        }
        
        wp_die();
    }

	/**
	 * If the amount doesn't match, check if it matches without tax
	 * @since 3.09
	 *
	 * @param string $amt
	 * @param object $payment
	 */
	private static function compare_payment_amount( $amt, $payment ) {
		if ( $amt == $payment->amount ) {
			return;
		}

		$tax = (float) FrmAppHelper::get_post_param( 'tax', '', 'sanitize_text_field' );
		if ( $tax ) {
			$amt = (float) $amt - $tax;
		}
		if ( $amt != $payment->amount ) {
			FrmPaymentsHelper::log_message( esc_html__( 'Payment amounts do not match.', 'frmpp' ) );
			wp_die();
		}
	}

	public static function email_addresses_match( $payment ) {
		$business	= strtolower( FrmAppHelper::get_post_param( 'business', '', 'sanitize_text_field' ) );
		$receiver_email = strtolower( FrmAppHelper::get_post_param( 'receiver_email', '', 'sanitize_text_field' ) );

        $frm_payment_settings = new FrmPaymentSettings();

		$email_setting = trim( strtolower( $frm_payment_settings->settings->business_email ) );
		$business_email_match = ( $business == $email_setting );
		$receiver_email_match = ( $receiver_email == $email_setting );

		$match = ( $business_email_match || $receiver_email_match );

		$form_action = array();
		if ( ! $match ) {
			// get the action for this entry
			if ( ! empty( $payment->action_id ) ) {
				$form_action = FrmPaymentAction::get_payment_action( $payment->action_id );
				if ( $form_action && isset( $form_action->post_content['business_email'] ) && ! empty( $form_action->post_content['business_email'] ) ) {
					$email_setting = trim( strtolower( $form_action->post_content['business_email'] ) );
					$business_email_match = ( $business == $email_setting );
					$receiver_email_match = ( $receiver_email == $email_setting );
					$match = ( $business_email_match || $receiver_email_match );
				}
			}

			if ( ! $match ) {
				$match = apply_filters( 'frm_paypal_match_email', $match, compact( 'business', 'receiver_email', 'form_action', 'email_setting', 'payment' ) );

				if ( ! $match ) {
					FrmPaymentsHelper::log_message( 'Setting '. $email_setting .' is not the same as payment '. $business .' or '. $receiver_email );
				}
			}
		}

		return $match;
	}

	public static function is_valid_entry_id( $entry_id, $hash ) {
		$test_ipn = FrmAppHelper::get_post_param( 'test_ipn', '', 'sanitize_text_field' );
		return ( ! empty( $test_ipn ) || wp_hash( $entry_id ) == $hash );
	}

	public static function maybe_create_payment( $atts, &$payment ) {
		$txn_type = FrmAppHelper::get_post_param( 'txn_type', '', 'sanitize_text_field' );
		if ( ! in_array( $txn_type, array( 'subscr_failed', 'subscr_payment' ) ) ) {
			return;
		}

		global $wpdb;
		$action_id = $wpdb->get_var( $wpdb->prepare( 'SELECT action_id FROM ' . $wpdb->prefix .'frm_payments WHERE item_id=%d AND completed=%d AND expire_date > %s', $atts['entry']->id, 1, '0000-00-0000' ) );
		$payment_date = date('Y-m-d', strtotime( FrmAppHelper::get_post_param( 'payment_date', '', 'sanitize_text_field' ) ) );

		$new_payment = array(
			'amount'      => $atts['amt'],
			'receipt_id'  => $atts['txn_id'],
			'item_id'     => $atts['entry']->id,
			'action_id'   => $action_id,
			'begin_date'  => $payment_date,
			'expire_date' => self::get_next_payment_date( $action_id, $payment_date ),
		);

		$frm_payment = new FrmPayment();
		$payment_id = $frm_payment->create( $new_payment );
		$payment = $frm_payment->get_one( $payment_id );
	}

	private static function get_next_payment_date( $action_id, $last_payment_date ) {
		$expire_date = '0000-00-00';
		if ( $action_id ) {
			$action = FrmPaymentAction::get_payment_action( $action_id );
			$expire_date = self::calcuate_next_payment_date( $action->post_content, array( 'last_date' => $last_payment_date ) );
		} else {
			$next_payment_date = FrmAppHelper::get_post_param( 'next_payment_date', '', 'sanitize_text_field' );
			if ( ! empty( $next_payment_date ) ) {
				$expire_date = date( 'Y-m-d', strtotime( $next_payment_date ) );
			}
		}
		return $expire_date;
	}

	private static function calcuate_next_payment_date( $paypal_params, $atts = array() ) {
		$defaults = array( 'first_payment' => false, 'last_date' => time() );
		$atts = array_merge( $defaults, $atts );
		$repeat_times = FrmPaymentsHelper::get_repeat_times();

		$expire_date = '0000-00-00';
		if ( $paypal_params['paypal_type'] == '_xclick-subscriptions' ) {
			if ( $atts['first_payment'] && isset( $paypal_params['trial'] ) && $paypal_params['trial'] ) {
				$payment_type = 'trial';
			} else {
				$payment_type = 'repeat';
			}

			$time_to_next = '+' . $paypal_params[ $payment_type . '_num'] .' '. $repeat_times[ $paypal_params[ $payment_type . '_time'] ];
			$next_date = strtotime( $time_to_next, $atts['last_date'] );
			$expire_date = date( 'Y-m-d', $next_date );
		}

		return $expire_date;
	}

	private static function actions_after_ipn( $pay_vars, $entry ) {
		$trigger_actions = is_callable( 'FrmFormActionsController::trigger_actions' );
		if ( ! $trigger_actions ) {
			return;
		}

		$form_action = false;
		if ( isset( $pay_vars['action_id'] ) && $pay_vars['action_id'] ) {
			$form_action = FrmPaymentAction::get_payment_action( $pay_vars['action_id'] );
		}

		self::set_fields_after_payment( $form_action, $pay_vars, $entry );

		$trigger_event = ( $pay_vars['completed'] ) ? 'paypal' : 'paypal-failed';
		FrmFormActionsController::trigger_actions( $trigger_event, $entry->form_id, $entry->id );
	}

	public static function set_fields_after_payment( $form_action, $pay_vars, $entry = array() ) {
		if ( ! is_callable( 'FrmProEntryMeta::update_single_field' ) || empty( $form_action ) || empty( $form_action->post_content['change_field'] ) ) {
			return;
		}

		foreach ( $form_action->post_content['change_field'] as $change_field ) {
			$completed = ( $change_field['status'] == 'complete' && $pay_vars['completed'] );
			$failed = ( $change_field['status'] == 'failed' && ! $pay_vars['completed'] );
			if ( $completed || $failed ) {
				$value = self::process_shortcodes( array(
					'value' => $change_field['value'],
					'form'  => $form_action->menu_order,
					'entry' => $entry,
				) );

				FrmProEntryMeta::update_single_field( array(
					'entry_id' => $pay_vars['item_id'],
					'field_id' => $change_field['id'],
					'value'    => $value,
				) );
			}
		}
	}

	/**
	 * Allow entry values, default values, and other shortcodes
	 * in the after payment settings
	 *
	 * @since 3.07
	 */
	private static function process_shortcodes( $atts ) {
		$value = $atts['value'];
		if ( strpos( $value, '[' ) === false ) {
			// if there are no shortcodes, we don't need to filter
			return $value;
		}

		if ( is_callable('FrmProFieldsHelper::replace_non_standard_formidable_shortcodes' ) ){
			FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $value );
		}

		if ( isset( $atts['entry'] ) && ! empty( $atts['entry'] ) ) {
			$value = apply_filters( 'frm_content', $value, $atts['form'], $atts['entry'] );
		}

		return do_shortcode( $value );
	}

	public static function hidden_payment_fields( $form ) {
		//_deprecated_function( __FUNCTION__, '2.04', 'Please update your payment settings for this form' );
		if ( isset( $form->options['paypal'] ) && $form->options['paypal'] ) {
			echo '<input type="hidden" name="frm_payment[item_name]" value="' . esc_attr( $form->options['paypal_item_name'] ) . '"/>' . "\n";
		}
	}

    public static function update_options($options, $values){
        $defaults = FrmPaymentsHelper::get_default_options();
        
		foreach( $defaults as $opt => $default ) {
			$options[ $opt ] = isset( $values['options'][ $opt ] ) ? $values['options'][ $opt ] : $default;
			unset( $default, $opt );
		}

        return $options;
    }
    
    public static function sidebar_list($entry){
        global $wpdb;
        
        $payments = $wpdb->get_results($wpdb->prepare("SELECT id,begin_date,amount,completed FROM {$wpdb->prefix}frm_payments WHERE item_id=%d ORDER BY created_at DESC", $entry->id));
        
        if(!$payments)
            return;
        
        $date_format = get_option('date_format');    
        $currencies = FrmPaymentsHelper::get_currencies();
        
        include(self::path() .'/views/payments/sidebar_list.php');
    }

	public static function entry_columns( $columns ) {
		if ( is_callable( 'FrmForm::get_current_form_id' ) ) {
			$form_id = FrmForm::get_current_form_id();
		} else {
			$form_id = FrmEntriesHelper::get_current_form_id();
		}

		if ( $form_id ) {
			$columns[ $form_id . '_payments' ] = __( 'Payments', 'frmpp' );
			$columns[ $form_id . '_current_payment' ] = __( 'Paid', 'frmpp' );
			$columns[ $form_id . '_payment_expiration' ] = __( 'Expiration', 'frmpp' );
		}

		return $columns;
	}

	public static function entry_payment_column( $value, $atts ) {
		$value = '';

		$payments = FrmPaymentEntry::get_completed_payments( $atts['item'] );
		foreach ( $payments as $payment ) {
			$value .= '<a href="' . esc_url( admin_url( 'admin.php?page=formidable-payments&action=show&id=' . $payment->id ) ) . '">' . FrmPaymentsHelper::formatted_amount( $payment ) . '</a><br/>';
			unset( $payment );
		}
		return $value;
	}

	public static function entry_current_payment_column( $value, $atts ) {
		$payments = FrmPaymentEntry::get_completed_payments( $atts['item'] );
		$is_current = ! empty( $payments ) && ! FrmPaymentEntry::is_expired( $atts['item'] );
		$value = $is_current ? __( 'Paid', 'frmpp' ) : __( 'Not Paid', 'frmpp' );
		return $value;
	}

	public static function entry_payment_expiration_column( $value, $atts ) {
		$expiration = FrmPaymentEntry::get_entry_expiration( $atts['item'] );
		return $expiration ? $expiration : '';
	}

	public static function add_payment_to_csv( $headings, $form_id ) {
		if ( FrmPaymentAction::form_has_payment_action( $form_id ) ) {
			$headings['paypal'] = __( 'Payments', 'frmpp' );
			$headings['paypal_expiration'] = __( 'Expiration Date', 'frmpp' );
			$headings['paypal_complete'] = __( 'Paid', 'frmpp' );
			add_filter( 'frm_csv_row', 'FrmPaymentsController::add_payment_to_csv_row', 20, 2 );
		}
		return $headings;
	}

	public static function add_payment_to_csv_row( $row, $atts ) {
		$row['paypal'] = 0;
		$atts['item'] = $atts['entry'];
		$row['paypal_expiration'] = self::entry_payment_expiration_column( '', $atts );

		$payments = FrmPaymentEntry::get_completed_payments( $atts['entry'] );
		foreach ( $payments as $payment ) {
			$row['paypal'] += $payment->amount;
		}

		$row['paypal_complete'] = ! empty( $payments ) && ! FrmPaymentEntry::is_expired( $atts['entry'] );

		return $row;
	}

    private static function new_payment(){
        self::get_new_vars();
    }
    
    private static function create(){
        $message = $error = '';

        $frm_payment = new FrmPayment();
        if( $id = $frm_payment->create( $_POST )){
            $message = __('Payment was Successfully Created', 'frmpp');
            self::get_edit_vars($id, '', $message);
        }else{
            $error = __('There was a problem creating that payment', 'frmpp');
            return self::get_new_vars($error);
        }
    }
        
    private static function edit(){
        $id = FrmAppHelper::get_param('id');
        return self::get_edit_vars($id);
    }
    
    private static function update(){
        $frm_payment = new FrmPayment();
        $id = FrmAppHelper::get_param('id');
        $message = $error = '';
        if( $frm_payment->update( $id, $_POST ))
            $message = __('Payment was Successfully Updated', 'frmpp');
        else
            $error = __('There was a problem updating that payment', 'frmpp');
        return self::get_edit_vars($id, $error, $message);
    }
    
    private static function destroy(){
        if(!current_user_can('administrator')){
            $frm_settings = FrmPaymentsHelper::get_settings();
            wp_die($frm_settings->admin_permission);
        }

        $frm_payment = new FrmPayment();
        $message = '';
        if ($frm_payment->destroy( FrmAppHelper::get_param('id') ))
            $message = __('Payment was Successfully Deleted', 'frmpp');
            
        self::display_list($message);
    }
    
    private static function bulk_actions($action){
        $errors = array();
        $message = '';
        $bulkaction = str_replace('bulk_', '', $action);

        $items = FrmAppHelper::get_param('item-action', '');
        if (empty($items)){
            $errors[] = __('No payments were selected', 'frmpp');
        }else{
            if(!is_array($items))
                $items = explode(',', $items);
                
            if($bulkaction == 'delete'){
                if(!current_user_can('frm_delete_entries')){
                    $frm_settings = FrmPaymentsHelper::get_settings();
                    $errors[] = $frm_settings->admin_permission;
                }else{
                    if(is_array($items)){
                        $frm_payment = new FrmPayment();
                        foreach($items as $item_id){
                            if($frm_payment->destroy($item_id))
                                $message = __('Payments were Successfully Deleted', 'frmpp');
                        }
                    }
                }
            }
        }
        self::display_list($message, $errors);
    }
    
    private static function get_new_vars($error=''){
        global $wpdb;
        
        $defaults = array('completed' => 0, 'item_id' => '', 'receipt_id' => '', 'amount' => '', 'begin_date' => date('Y-m-d'), 'paysys' => 'manual');
        $payment = array();
        foreach($defaults as $var => $default)
            $payment[$var] = FrmAppHelper::get_param($var, $default); 
        
        $frm_payment_settings = new FrmPaymentSettings();
        $currency = FrmPaymentsHelper::get_currencies($frm_payment_settings->settings->currency);

        require(self::path() .'/views/payments/new.php');
    }
    
    private static function get_edit_vars($id, $errors = '', $message= ''){
        if ( ! $id ) {
            die( __( 'Please select a payment to view', 'frmpp' ) );
		}
            
        if(!current_user_can('frm_edit_entries'))
            return self::show($id);
            
        global $wpdb;
        $payment = $wpdb->get_row($wpdb->prepare("SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_payments p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id), ARRAY_A);

        $frm_payment_settings = new FrmPaymentSettings();
		$currency = FrmPaymentsHelper::get_action_setting( 'currency', array( 'payment' => $payment ) );
		$currency = FrmPaymentsHelper::get_currencies( $currency );

        if(isset($_POST) and isset($_POST['receipt_id'])){
            foreach($payment as $var => $val){
                if($var == 'id') continue;
                $payment[$var] = FrmAppHelper::get_param($var, $val);
            }
        }
        
        require(self::path() .'/views/payments/edit.php');
    }
    
	public static function route(){
		$action = isset( $_REQUEST['frm_action'] ) ? 'frm_action' : 'action';
		$action = FrmAppHelper::get_param( $action, '', 'get', 'sanitize_title' );

		if ( $action == 'show' ) {
			return self::show( FrmAppHelper::get_param( 'id', false, 'get', 'sanitize_text_field' ) );
		} else if ( $action == 'new' ) {
			return self::new_payment();
		} else if ( $action == 'create' ) {
			return self::create();
		} else if ( $action == 'edit' ) {
			return self::edit();
		} else if ( $action == 'update' ) {
			return self::update();
		} else if ( $action == 'destroy' ) {
			return self::destroy();
		} else {
			$action = FrmAppHelper::get_param( 'action', '', 'get', 'sanitize_text_field' );
			if ( $action == -1 ) {
				$action = FrmAppHelper::get_param( 'action2', '', 'get', 'sanitize_text_field' );
			}
            
			if ( strpos( $action, 'bulk_' ) === 0 ) {
				if ( $_GET && $action ) {
					$_SERVER['REQUEST_URI'] = str_replace( '&action=' . $action, '', $_SERVER['REQUEST_URI'] );
				}

				return self::bulk_actions( $action );
			} else {
				return self::display_list();
			}
		}
	}
}
