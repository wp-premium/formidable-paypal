<div class="error" id="frmpay_install_message">
	<p><?php
		printf( __( 'Your Formidable Payments database needs to be updated.%1$sPlease deactivate and reactivate the plugin or %2$sUpdate Now%3$s.', 'frmpp' ),
		'<br/>',
		'<a id="frmpay_install_link" href="javascript:frmpay_install_now()">',
		'</a>' );?>
	</p>
</div>

<script type="text/javascript">
	function frmpay_install_now(){
		jQuery('#frmpay_install_link').replaceWith('<img src="<?php echo esc_url_raw( $url ) ?>/images/wpspin_light.gif" alt="<?php esc_attr_e( 'Loading&hellip;' ); ?>" />');
		jQuery.ajax({type:'POST',url:"<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ) ?>",data:'action=frmpay_install',
			success:function(msg){jQuery("#frmpay_install_message").fadeOut('slow');}
		});
	}
</script>