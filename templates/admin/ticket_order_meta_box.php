<style type="text/css">
    #post-body-content, #titlediv { display:none }
</style>
<div class="ticket_general_data">
    <label for="ticket_general_date"><?php _e( 'Purchased date', 'yith-event-tickets-for-woocommerce' );?>: </label>
    <span id="ticket_general_date" class="date yith_wcevti_meta_span"><?php echo esc_html($post->post_date)?>  </span>
</div>

<div class="ticket_data_column">
    <h3><?php _e( 'Field details', 'yith-event-tickets-for-woocommerce' ); ?></h3>
    <?php
    if(isset($fields)){
        if(is_array($fields)){
            foreach ($fields as  $field){
                $label = key($field);
                $field = $field[$label];
                ?>
                <p class="form-field ticket_field">
                    <label for="_ticket_field_<?php echo esc_html($label)?>"><?php echo esc_html($label)?>: </label>
                    <span id="_ticket_field_<?php echo esc_html($label)?>" class="yith_wcevti_meta_span"><?php echo esc_html($field)?> </span>
                </p>

                <?php
            }
        }
    }
    do_action('yith_wcevti_order_metabox_end_fields', $post);
    ?>
</div>
<?php

