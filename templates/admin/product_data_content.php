<?php
global $thepostid;
$product = wc_get_product( $thepostid );
$fields = apply_filters( 'yith_wcevti_product_fields', yit_get_prop($product, '_fields', true), $product );
?>
<div id="fields_product_data" class="panel woocommerce_options_panel hidden">
    <div class="yith_evti_fields_title">
        <h2><strong class="attribute_name"><?php echo __('Add custom fields on the tickets for this event', 'yith-event-tickets-for-woocommerce')
                ?></strong></h2>
    </div>
    <div class="yith_evti_add_fields_data">
        <div class="fields_panel" >
            <table class="field-options wp-list-table widefat">
                <thead>
                <tr>
                    <th></th>
                    <th class="option-label"><?php _e( 'Name', 'yith-event-tickets-for-woocommerce' ) ?></th>
                    <th class="option-placeholder"><?php _e( 'Placeholder', 'yith-event-tickets-for-woocommerce' ); echo wc_help_tip(__( 'The name will be appear like input placeholder', 'yith-event-tickets-for-woocommerce' )) ?></th>
                    <th class="option-type"><?php _e( 'Type', 'yith-event-tickets-for-woocommerce' ) ?></th>
                    <th class="option-required"><?php _e( 'Required', 'yith-event-tickets-for-woocommerce' ) ?></th>
                    <th class="option-actions"><?php _e( 'Actions', 'yith-event-tickets-for-woocommerce' ) ?></th>
                </tr>
                </thead>

                <tbody>
                <?php
                if(!empty($fields)) {
	                foreach ($fields as $index => $field_item) {

		                if(isset($field_item)) {
			                $args = array(
				                'index' => $index,
				                'field' => $field_item
			                );
                            yith_wcevti_get_template('admin/fields_row.php', $args, '', YITH_WCEVTI_TEMPLATE_PATH);
		                }
	                }
                }
                ?>
                </tbody>

                <tfoot>
                <tr>
                    <td colspan="6">
                        <button id="_add_fields_row" class="button add_fields_button">
                            <i class="dashicons dashicons-plus"></i>
					        <?php _e('Add option', 'yith-event-tickets-for-woocommerce')?>
                        </button>
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php
$mail_template = get_post_meta($thepostid, '_mail_template', true);

$mail_template = is_array($mail_template) ? $mail_template : array();

if(!isset($mail_template['type']) | !isset($mail_template['data'])){
    $mail_template['type'] = 'default';
}
if(!isset($mail_template['data'])){
    $mail_template['data'] = array();
}
?>
<div id="mail_template_product_data" class="panel woocommerce_options_panel hidde">
    <div class="yith_evti_mail_template_title">
        <h2><strong class="attribute_name"><?php echo __('Email template for your event', 'yith-event-tickets-for-woocommerce')?></strong></h2>
    </div>
    <div class="yith_evti_mail_template_select_type">
        <p class="form-field ">
            <label for="_template_type"><?php echo __('Type of template' , 'yith-event-tickets-for-woocommerce');?></label>
            <select id="_template_type"
                    name="_template_type"
                    class="yith-wceti-mail-template-type">
                <option value="default" <?php if('default' == $mail_template['type']){ echo 'selected';} ?>><?php echo __('Default' , 'yith-event-tickets-for-woocommerce');?></option>

            </select>
        </p>
    </div>
    <div class="yith_wcevti_mail_template_content">
        <?php
        $mail_template = apply_filters('yith_wcevit_mail_template_options', $mail_template);
         yith_wcevti_get_template('edit_template_'.$mail_template['type'], $mail_template['data'], 'admin');
        ?>
    </div>
</div>
<?php do_action('yith_wcevti_product_data_content');
