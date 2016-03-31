<?php
/*
Plugin Name: Formidable PayPal Standard
Description: Send Posted results to PayPal
Version: 3.03
Plugin URI: http://formidablepro.com/
Author URI: http://strategy11.com
Author: Strategy11
Text Domain: frmpp
*/

function frm_paypal_forms_autoloader($class_name) {
    // Only load FrmPayment classes here
	if ( ! preg_match( '/^FrmPayment.+$/', $class_name ) && $class_name != 'FrmPayment' ) {
        return;
    }

    $filepath = dirname(__FILE__);

    if ( preg_match('/^.+Helper$/', $class_name) ) {
        $filepath .= '/helpers';
    } else if ( preg_match('/^.+Controller$/', $class_name) ) {
        $filepath .= '/controllers';
    } else {
        $filepath .= '/models';
    }

    $filepath .= '/'. $class_name .'.php';

    if ( file_exists($filepath) ) {
        include($filepath);
    }
}

// if __autoload is active, put it on the spl_autoload stack
if ( is_array(spl_autoload_functions()) && in_array('__autoload', spl_autoload_functions()) ) {
    spl_autoload_register('__autoload');
}

// Add the autoloader
spl_autoload_register('frm_paypal_forms_autoloader');

FrmPaymentsController::load_hooks();
FrmPaymentSettingsController::load_hooks();

