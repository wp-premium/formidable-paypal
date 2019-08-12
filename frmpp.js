function frmPPBuildJS(){
	function toggle_amount(){
		var $link = jQuery(this).closest('.frm_pp_toggle_new');
		$link.find('.frm_enternew, .frm_cancelnew').toggle();
		$link.find('input.frm_enternew, select.frm_cancelnew').val('');
		return false;
	}

	function toggle_sub(){
		var val = this.value;
		var show = (val == '_xclick-subscriptions');
		toggle_opts(this, show, '.frmpp_sub_opts');
	}

	function toggle_trial(){
		var val = this.checked;
		toggle_opts(this, val, '.frmpp_trial_opts');
	}

	function toggle_opts(opt, show, c){
		var opts = jQuery(opt).closest('.frm_form_action_settings').find(c);
		if(show){
			opts.slideDown('fast');
		}else{
			opts.slideUp('fast');
		}
	}

	function addAfterPayRow(){
		var id = jQuery(this).data('emailkey');
		var rowNum = 0;
		var form_id = document.getElementById('form_id').value;
		if(jQuery('#frm_form_action_'+id+' .frmpp_after_pay_row').length){
			rowNum = 1 + parseInt(jQuery('#frm_form_action_'+id+' .frmpp_after_pay_row:last').attr('id').replace('frmpp_after_pay_row_'+id+'_', ''));	
		}
		jQuery.ajax({
			type:'POST',url:ajaxurl,
			data:{action:'frmpp_after_pay', email_id:id, form_id:form_id, row_num:rowNum, nonce:frmGlobal.nonce},
			success:function(html){
				var addButton = jQuery(document.getElementById('frmpp_after_pay_'+id));
				addButton.fadeOut('slow', function(){
					var $logicRow = addButton.next('.frmpp_after_pay_rows');
					$logicRow.find('tbody').append(html);
					$logicRow.fadeIn('slow');
				});
			}
		});
		return false;
	}

	return{
		init: function(){
			var actions = document.getElementById('frm_notification_settings');
			jQuery(actions).on('click', '.frm_toggle_pp_opts', toggle_amount);
			jQuery(actions).on('change', '.frm_paypal_type', toggle_sub);
			jQuery(actions).on('click', '.frmpp_trial', toggle_trial);
			jQuery('.frm_form_settings').on('click', '.frm_add_pp_logic', addAfterPayRow);
		}
	};
}

var frmPPBuild = frmPPBuildJS();

jQuery(document).ready(function($){
	frmPPBuild.init();
});

