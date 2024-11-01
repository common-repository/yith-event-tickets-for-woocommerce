<!doctype html>
<html lang="en">
<?php
    if( ! ( is_user_logged_in() && ( is_user_owner( $post->ID ) || current_user_can('manage_woocommerce') ) ) ) {
        wp_redirect( wc_get_page_permalink( 'myaccount'));
    }
?>
<head>
    <meta charset="utf-8">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,800" rel="stylesheet">
    <style type="text/css">
    <?php
    $args = array(
        'post' => $post,
        'fields' => $fields,
        'date' => $date,
        'price' => $price,
        'mail_template' => $mail_template,
        'header_image' => $header_image,
        'display_ticket_number' => isset($mail_template['data']['display_ticket_number']) ? 'on' : 'off',
        'barcode' => $barcode,
        'barcode_rendered' => $barcode_rendered,
        'content_image' => $content_image,
        'footer_image' => $footer_image
    );

    yith_wcevti_get_template('default-css-preview', $args, 'tickets');
    ?>
    </style>
</head>
<body>
<?php
yith_wcevti_get_template('default-html-preview', $args, 'tickets');
?>
</body>
</html>