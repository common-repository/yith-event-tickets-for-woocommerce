<?php
$upload_dir = wp_upload_dir();

?>
<div class="_view_and_pdf_row">
    <?php if(is_user_logged_in()) {?>
        <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'print_mail_template_action', 'id' => $event_id ), admin_url( 'admin-ajax.php' ) ) ); ?>" class="button wc-forward" target="_blank"><?php echo __('View', 'yith-event-tickets-for-woocommerce')?></a>
    <?php
    }

    $file_path = yith_wcevti_get_pdf_path($event_id);
    $file_url = yith_wcevti_get_pdf_url($event_id);

    if( !@fopen($file_path, 'r')){
     @yith_wcevti_create_pdf($event_id);

    }

    if( @fopen($file_path, 'r')){

        ?>
        <a href="<?php echo esc_url($file_url); ?>" class="button wc-forward" download><?php echo __('PDF', 'yith-event-tickets-for-woocommerce')?></a>
        <?php
    }?>
    <a href="<?php
    echo $url_google_calendar;?>" class="button wc-forward" target="_blank"><?php echo __('Export to Google Calendar', 'yith-event-tickets-for-woocommerce')?></a>
</div>
