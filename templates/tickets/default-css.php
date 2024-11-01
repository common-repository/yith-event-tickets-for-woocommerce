<?php
$header_title_width       = '100';
$header_image_margin_left = '5';
$header_title_left        = '6';
$header_title_left_border = YITH_WCEVTI_ASSETS_URL . '/images/triangle-pattern.png';

if ( 'on' == $barcode['display'] & defined( 'YITH_YWBC_PREMIUM' ) & ! empty( $barcode_rendered ) ) {
	$header_title_width       = '80';
	$header_image_margin_left = '1.75';
	$header_title_left        = '3';
	$header_title_left_border = YITH_WCEVTI_ASSETS_URL . '/images/circle-pattern.png';
}
?>
    body{
    font-family: "dejavusans";
    background: white;
    }
    .barcode_container, .yith_evti_mail_template_content{
    background-color: white;
    border: 1px solid #ddd;
    }
    .yith_evti_mail_template_panel{
    margin: auto;
    width: 604px;
    }
    #content_main {
    background-image: url(<?php echo $content_image[0]; ?>);
    background-repeat: no-repeat;
    border-radius: 7px;
    padding: 1em 2em 1em 2em;
    }
    .barcode_container{
    text-align: center;
    }
    .yith_evti_mail_template_footer{
    background-color: #545454;
    border: 1px solid #545454
    width: 100%;
    }
    .footer_table{
    padding: 1em;
    }
    .footer_logo{
    margin-right: 1em;
    }
    .footer_text{
    color: white;
    font-weight: bold;
    font-size: 0.7em;
    }
<?php
do_action( 'yith_wcevti_end_default_css' );