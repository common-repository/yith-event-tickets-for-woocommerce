<div class="options_group yith_wcevti_customs_options show_if_ticket-event">
    <div class="customs_options_panel">
        <p class="form-field">
            <label for="_disable_cookie_form_field"><?php echo __('Disable cookies form', 'yith-event-tickets-for-woocommerce');?></label>
            <input type="checkbox" class="" name="_disable_cookie_form" id="_disable_cookie_form"
                   <?php if($disable_cookie_form){echo 'checked';};?>>
            <?php echo wc_help_tip(__('If you enable this option and refresh the ticket form, the fields will be emptied.', 'yith-event-tickets-for-woocommerce')); ?>
        </p>
    </div>
</div>