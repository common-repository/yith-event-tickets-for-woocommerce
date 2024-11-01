<?php
$total_rows = $row + $num_rows;

for($row; $row < $total_rows; $row++ ){
    ?>
    <div class="field_service_row">
        <div class="header_service_row">
            <a href="" class="remove" title="<?php echo __('Remove this ticket', 'yith-event-tickets-for-woocommerce')?>" >×</a>
            <h3><?php
                $event_title = apply_filters('yith_wcevti_event_title_form', $event_title . ' #'. ($row+1), $product_id);
                echo $event_title ?></h3>
            <p class="service_message"></p>
        </div>
        <div class="content_service_row">
            <?php

            $args = array(
                'fields' => $fields,
                'row' => $row
            );
            yith_wcevti_get_template('fields_row', $args, 'frontend');


            do_action('yith_wcevti_end_fields_row', $product_id ,$row);
            ?>
        </div>
    </div>
    <?php
}