<?php
class FrmPaymentSettings{

    var $settings;

    function __construct(){
        $this->set_default_options();
    }
    
    function default_options(){
        $siteurl = get_option('siteurl');
        return array(
            'environment'       => 'live',
            'business_email'    => get_option('admin_email'),
            'currency'          => 'USD',
            'return_url'        => $siteurl,
            'cancel_url'        => $siteurl,
            'ipn_log'           => true,
            'ipn_log_file'      => dirname(dirname(__FILE__)) .'/log/ipn_results.log'
        );
    }
    

    function set_default_options($settings=false){
        $default_settings = $this->default_options();
        
        if ( $settings === true ) {
            $settings = new stdClass();
        } else if ( !$settings ) {
            $settings = $this->get_options();
        }
            
        if ( !isset($this->settings) ) {
            $this->settings = new stdClass();
        }
        
        foreach ( $default_settings as $setting => $default ) {
            if ( is_object($settings) && isset($settings->{$setting}) ) {
                $this->settings->{$setting} = $settings->{$setting};
            }
                
            if ( !isset($this->settings->{$setting}) ) {
                $this->settings->{$setting} = $default;
            }

            if ( 'ipn_log_file' == $setting ) {
				if ( $this->settings->{$setting} == '' ) {
					$this->settings->{$setting} = $default;
				}
                $this->settings->{$setting} = stripslashes($this->settings->{$setting});
            }
        }
        
        if ( $this->settings->ipn_log_file == '.ipn_results.log' ) {
            $this->settings->ipn_log_file = $default_settings['ipn_log_file'];
        }
        
        $this->settings = apply_filters('frm_paypal_settings', $this->settings);
    }
    
    function get_options(){
        $settings = get_option('frm_paypal_options');

        if(!is_object($settings)){
            if($settings){ //workaround for W3 total cache conflict
                $settings = unserialize(serialize($settings));
            }else{
                // If unserializing didn't work
                if(!is_object($settings)){
                    if($settings) //workaround for W3 total cache conflict
                        $settings = unserialize(serialize($settings));
                    else
                        $settings = $this->set_default_options(true);
                    $this->store();
                }
            }
        }else{
            $this->set_default_options($settings); 
        }
        
        return $this->settings;
    }

    function validate($params,$errors){
        if(empty($params[ 'frm_pay_business_email' ]) or !is_email($params[ 'frm_pay_business_email' ]))
            $errors[] = __('Please enter a valid email address', 'frmpp');
        return $errors;
    }

    function update($params){
        $settings = $this->default_options();
        
        foreach($settings as $setting => $default){
            if ( ! isset($params['frm_pay_'. $setting]) ) {
                continue;
            }
            if ( 'ipn_log_file' == $setting ) {
                // allow for Windows paths
                $this->settings->{$setting} = addslashes($params['frm_pay_'. $setting]);
            } else {
                $this->settings->{$setting} = $params['frm_pay_'. $setting];
            }
        }     
        
        $this->settings->ipn_log = isset($params['frm_pay_ipn_log']) ? $params['frm_pay_ipn_log'] : 0;
    }

    function store(){
        // Save the posted value in the database
        update_option( 'frm_paypal_options', $this->settings);
    }
}
