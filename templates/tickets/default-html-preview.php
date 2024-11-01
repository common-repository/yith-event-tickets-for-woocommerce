<div class="yith_evti_mail_template_container">
    <div class="yith_evti_mail_template_panel">
        <?php if(!empty($header_image) ) { ?>
        <div class="yith_evti_mail_template_header">
            <div id="header_title">
                <div class="title_container">
                    <div class="header_image">
                        <div class="title-hover">
                            <h1><?php
                                echo $post->post_title;
                            ?></h1>
	                        <?php do_action('yith_wcevti_default_preview_after_title', $post) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if(false != $barcode){

                ?>
                <div id="header_barcode">
                    <div class="barcode_container">

                                    <?php echo $barcode_rendered;?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php } ?>
        <div class="yith_evti_mail_template_content">
            <?php
            if($content_image){
                ?>
                <img class="content_image" src="<?php echo $content_image[0]; ?>" width="<?php if(isset($content_image[1])){echo $content_image[1];} ?>" height="<?php if(isset($content_image[2])){ echo $content_image[2];} ?>">
            <?php }
            ?>
            <div id="content_main">
                <div id="content_title">
                    <h2><?php
                        if('on' == $display_ticket_number){
                            echo '#' . $post->ID . ' ';
                        }
                        echo $post->post_title; ?></h2>
                </div>
                <div id="content_fields">
                    <?php
                    if(isset($fields)){
                        if(is_array($fields)) {
                            foreach ($fields as $field) {
                                if (isset($field) & !empty($field)) {
                                    $label = key($field);
                                    $field = $field[$label];
                                    ?>
                                    <p class="form-field">
                                        <label
                                            for="_ticket_field_<?php echo esc_html($label) ?>"><?php echo esc_html($label) ?>
                                            : </label>
                                        <span
                                            id="_ticket_field_<?php echo esc_html($label) ?>"><?php echo esc_html($field) ?> </span>
                                    </p>
                                    <?php
                                }
                            }
                        }
                    }
                    ?>
                </div>
                <?php
                    do_action('yith_wcevti_default_html_preview_end_fields', $post);
                if(!empty($date['message_start']) & !empty($date['message_end'])){
                    ?>
                    <div id="content_date">
                        <p>
                            <?php echo $date['message_start'];?>
                        </p>
                        <p>
                            <?php echo $date['message_end'];?>
                        </p>
                    </div>
                    <?php
                }
                do_action('yith_wcevti_default_html_preview_end_date', $post);
                $formated_price_service =  sprintf( get_woocommerce_price_format(),  get_woocommerce_currency_symbol() , $price );
                ?>
                <div id="content_price">
                    <p class="form-field">
                        <label for="_content_price"><?php echo __('Price', 'yith-event-tickets-for-woocommerce'); ?>: </label>
                        <span id="_content_price"><?php echo $price?></span>
                    </p>
                </div>
	            <?php
	            do_action('yith_wcevti_default_html_preview_end_price', $post);
	            ?>
                <div id="content_aditional">
                    <p>
                        <?php echo nl2br( esc_html($mail_template['data']['aditional_text'] ) )?>
                    </p>
                </div>
                <?php
                do_action('yith_wcevti_default_html_preview_end_aditional', $post);
                ?>
            </div>
        </div>
        <div class="yith_evti_mail_template_footer">
            <div class="footer_logo">
                <?php
                if($footer_image){
                    ?>
                    <img class="footer_image" src="<?php echo $footer_image[0]; ?>" width="<?php if(isset($footer_image[1])){ echo $footer_image[1];} ?>" height="<?php if(isset($footer_image[2])){ echo $footer_image[2];} ?>">
                <?php }
                ?>
            </div>
            <div class="footer_text">
                <p>
                    <?php echo get_home_url();?>
                </p>
            </div>
        </div>
    </div>
</div>
