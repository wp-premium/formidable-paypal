<div class="wrap">
    <div id="icon-options-general" class="icon32"><br></div>
    <h2><?php _e('Payments', 'frmpp') ?> 
        <a href="?page=formidable-payments&amp;action=new" class="add-new-h2"><?php _e('Add New', 'frmpp'); ?></a>
    </h2>
    
    <?php include(FrmAppHelper::plugin_path() .'/classes/views/shared/errors.php'); ?>
    
    <form id="posts-filter" method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr( sanitize_title( $_GET['page'] ) ) ?>" />
        <input type="hidden" name="frm_action" value="list" />
    <?php $wp_list_table->display(); ?>
    </form>

</div>
