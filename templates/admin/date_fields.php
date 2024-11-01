<div class="options_group yith_wcevti_date_event show_if_ticket-event">
    <div class="date_panel">
        <div class="date_item">
            <p class="form-field">
                <label for="_start_date_picker_field"><?php echo __('Start date', 'yith-event-tickets-for-woocommerce');?></label>
                <input type="text" class="yith-wcevti-datepicker" name="_start_date_picker_field" id="_start_date_picker_field"
                       value="<?php echo get_post_meta( $thepostid, '_start_date_picker', true );?>" placeholder="yyyy-mm-dd">
            </p>
            <p class="form-field">
                <label for="_start_time_picker_field"><?php echo __('Start time', 'yith-event-tickets-for-woocommerce');?></label>
                <input type="text" class="yith-wceti-timepicker" name="_start_time_picker_field" id="_start_time_picker_field"
                       value="<?php echo get_post_meta($thepostid, '_start_time_picker', true); ?>" placeholder="HH:MM">
            </p>
        </div>
        <div class="date_item">
            <p class="form-field">
                <label for="_end_date_picker_field"><?php echo __('End date', 'yith-event-tickets-for-woocommerce');?></label>
                <input type="text" class="yith-wcevti-datepicker "  name="_end_date_picker_field" id="_end_date_picker_field"
                       value="<?php echo get_post_meta( $thepostid, '_end_date_picker', true );?>" placeholder="yyyy-mm-dd">

            </p>
            <p class="form-field">
                <label for="_end_time_picker_field"><?php echo __('End time', 'yith-event-tickets-for-woocommerce');?></label>
                <input type="text" class="yith-wceti-timepicker" name="_end_time_picker_field" id="_end_time_picker_field"
                       value="<?php echo get_post_meta($thepostid, '_end_time_picker', true); ?>" placeholder="HH:MM">
            </p>
        </div>
    </div>
</div>