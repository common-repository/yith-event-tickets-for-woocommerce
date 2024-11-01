<tr class="yith_wcevti_field_row">
    <td class="drag-icon">
        <i class="dashicons dashicons-move"></i>
    </td>
    <td class="form-field _fields_<?php echo $index ?>_label_field option-label">
        <input type="text" class="yith-wceti-field-label" style=""
               name="_fields[<?php echo $index ?>][_label]"
               id="_fields_<?php echo $index ?>_label"
               value="<?php echo isset( $field['_label'] ) ? $field['_label'] : '' ?>"
               placeholder="">
        <?php echo wc_help_tip(__( 'Placeholder for type number only works with numeric values.', 'yith-event-tickets-for-woocommerce' ));?>
    </td>
    <td class="form-field _fields_<?php echo $index ?>_required_field option-placeholder">
        <?php
        $type = isset($field['_type']) ? $field['_type'] : '';
        if('check' != $type){ ?>
            <input type="checkbox" class="checkbox" style="" name="_fields[<?php echo $index ?>][_placeholder]"
                   id="_fields_<?php echo $index ?>_placeholder" <?php checked( isset( $field['_placeholder'] ) && 'on' == $field['_placeholder'] ) ?> >
            <?php } ?>
    </td>
    <td class="form-field _fields_<?php echo $index ?>_type_field option-type">
        <select id="_fields_<?php echo $index ?>_type" name="_fields[<?php echo $index ?>][_type]"
                class="yith-wceti-field-type">
            <option value="text" <?php selected( isset( $field['_type'] ) && $field['_type'] == 'text' ) ?> ><?php echo __( 'Text', 'yith-event-tickets-for-woocommerce' ); ?></option>
            <option value="textarea" <?php selected( isset( $field['_type'] ) && $field['_type'] == 'textarea' ) ?> ><?php echo __( 'Textarea', 'yith-event-tickets-for-woocommerce' ); ?></option>
            <option value="email" <?php selected( isset( $field['_type'] ) && $field['_type'] == 'email' ) ?> ><?php echo __( 'Email', 'yith-event-tickets-for-woocommerce' ); ?></option>
            <option value="number" <?php selected( isset( $field['_type'] ) && $field['_type'] == 'number' ) ?> ><?php echo __( 'Number', 'yith-event-tickets-for-woocommerce' ); ?></option>
            <option value="date" <?php selected( isset( $field['_type'] ) && $field['_type'] == 'date' ) ?> ><?php echo __( 'Date', 'yith-event-tickets-for-woocommerce' ); ?></option>
            <option value="yes-no" <?php selected( isset( $field['_type'] ) && $field['_type'] == 'yes-no' ) ?> ><?php echo __( 'Yes/No', 'yith-event-tickets-for-woocommerce' ); ?></option>
            <?php do_action('yith_wcevti_custom_option', isset($field) ? $field : '', $index); ?>
        </select>
    </td>

    <td class="form-field _fields_<?php echo $index ?>_required_field option-required">
        <?php
        $type = isset($field['_type']) ? $field['_type'] : '';
        if('check' != $type){ ?>
            <input type="checkbox" class="checkbox" style="" name="_fields[<?php echo $index ?>][_required]"
                   id="_fields_<?php echo $index ?>_required" <?php checked( isset( $field['_required'] ) && 'on' == $field['_required'] ) ?> >
        <?php } ?>
    </td>
    <td class="yith-wceti-remove-field-row option-actions">
        <button class="button"><?php echo __( 'Remove', 'yith-event-tickets-for-woocommerce' ) ?></button>
    </td>
</tr>