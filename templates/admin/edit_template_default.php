<div class="yith_wcevti_mail_template_default">
    <?php do_action('start_mail_template_default'); ?>
    <p class="form-field">
        <label for="header_image_button"><?php echo __('Header image' , 'yith-event-tickets-for-woocommerce');?> :</label>
        <input type="button" name="header_image_button" id="header_image_button" class="yith_wcevti_mail_button yith_wcevti_mail_header button" value="<?php echo __('Choose...' , 'yith-event-tickets-for-woocommerce');?>">
        <input type="text" name="_header_image[uri]" id="_header_image_uri" class="image_uri" value="<?php if(isset($header_image['uri'])){ echo esc_html($header_image['uri']); } ?>" readonly>
        <input type="text" name="_header_image[id]" id="_header_image_id" class="image_id" value="<?php if(isset($header_image['id'])){ echo esc_html($header_image['id']); } ?>" hidden>
        <input type="button" name="header_remove_image_button" id="header_remove_image_button" class="yith_wcevti_mail_remove_button
        yith_wcevti_mail_remove_header button" value="<?php echo __('Clear' , 'yith-event-tickets-for-woocommerce');?>">
    </p>
    <p class="form-field">
        <label for="_display_ticket_number"><?php echo __('Display ticket number', 'yith-event-tickets-for-woocommerce') ?> :</label>
        <input  id="_display_ticket_number"
                type="checkbox"
                class="yith-wceti-ticket-number"
                style="" name="_display_ticket_number"
            <?php if(isset($display_ticket_number)){if('on' == $display_ticket_number){ echo 'checked';}}?>
        >
        <?php echo wc_help_tip(__('Display ticket number just before title ticket', 'yith-event-tickets-for-woocommerce')); ?>
    </p>
    <?php if (defined('YITH_YWBC_PREMIUM')) {?>
        <p class="form-field">
            <label for="_display_barcode"><?php echo __('Display barcode', 'yith-event-tickets-for-woocommerce') ?> :</label>
            <input  id="_display_barcode"
                    type="checkbox"
                    class="yith-wceti-barcode"
                    style="" name="_barcode[display]"
                <?php if(isset($barcode['display'])){if('on' == $barcode['display']){ echo 'checked';}}?>
            >
        </p>
        <p class="form-field _type_barcode_field" hidden>
            <label for="_type_barcode"><?php echo __('Type of barcode' , 'yith-event-tickets-for-woocommerce');?></label>
            <select id="_type_barcode"
                    name="_barcode[type]"
                    class="yith-wceti-barcode">
                <option value="ticket" <?php if(isset($barcode['type'])){ selected( $barcode['type'], 'ticket' );} ?> ><?php echo __('Ticket' , 'yith-event-tickets-for-woocommerce');?></option>
                <option value="product" <?php if(isset($barcode['type'])){ selected( $barcode['type'], 'product' );} ?> ><?php echo __('Product' , 'yith-event-tickets-for-woocommerce');?></option>
                <option value="order" <?php if(isset($barcode['type'])){ selected( $barcode['type'], 'order' );} ?> ><?php echo __('Order' , 'yith-event-tickets-for-woocommerce');?></option>
            </select>
            <?php echo wc_help_tip(__('Tickets purchased will generate a barcode number if this option is enabled', 'yith-event-tickets-for-woocommerce'));?>
        </p>

        <?php
    } else {?>
        <p class="form-field">
           <a href="https://yithemes.com/themes/plugins/yith-woocommerce-barcodes-and-qr-codes/"> <?php echo __('If you want to display barcodes on your Event Tickets, please, install YITH WooCommerce Barcodes and Qr Codes plugin', 'yith-event-tickets-for-woocommerce'); ?></a>
        </p>
        <?php
    }?>

    <p class="form-field">
        <label for="adtitional_text"><?php echo __('Background image' , 'yith-event-tickets-for-woocommerce');?> :</label>
        <input type="button" name="background_image_button" id="background_image_button" class="yith_wcevti_mail_button yith_wcevti_mail_background button" value="<?php echo __('Choose...' , 'yith-event-tickets-for-woocommerce');?>">
        <input type="text" name="_background_image[uri]" id="_background_image_uri" class="image_uri" value="<?php if(isset($background_image['uri'])){ echo esc_html($background_image['uri']); } ?>" readonly>
        <input type="text" name="_background_image[id]" id="_background_image_id"  class="image_id" value="<?php if(isset($background_image['id'])){ echo esc_html($background_image['id']); } ?>" hidden>
        <input type="button" name="header_remove_image_button" id="header_remove_image_button" class="yith_wcevti_mail_remove_button
        yith_wcevti_mail_remove_header button" value="<?php echo __('Clear' , 'yith-event-tickets-for-woocommerce');?>">
    </p>
    <p class="form-field">
        <label for="footer_image_button"><?php echo __('Footer logo' , 'yith-event-tickets-for-woocommerce');?> :</label>
        <input type="button" name="footer_image_button" id="footer_image_button" class="yith_wcevti_mail_button yith_wcevti_mail_footer button" value="<?php echo __('Choose...' , 'yith-event-tickets-for-woocommerce');?>">
        <input type="text" name="_footer_image[uri]" id="_footer_image_uri" class="image_uri" value="<?php if(isset($footer_image['uri'])){ echo esc_html($footer_image['uri']); } ?>" readonly>
        <input type="text" name="_footer_image[id]" id="_footer_image_id" class="image_id" value="<?php if(isset($footer_image['id'])){ echo esc_html($footer_image['id']); } ?>" hidden >
        <input type="button" name="header_remove_image_button" id="header_remove_image_button" class="yith_wcevti_mail_remove_button
        yith_wcevti_mail_remove_header button" value="<?php echo __('Clear' , 'yith-event-tickets-for-woocommerce');?>">
    </p>
    <p class="form-field">
        <label for="adtitional_text"><?php echo __('Additional text' , 'yith-event-tickets-for-woocommerce');?> :</label>
        <textarea name="_aditional_text" id="_adtitional_text" class="yith_wcevti_adtional_text"><?php if(isset($aditional_text)){ echo esc_html($aditional_text); } ?></textarea>
    </p>
</div>