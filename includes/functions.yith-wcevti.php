<?php
/*
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! function_exists( 'yith_wcevti_get_template' ) ) {
	/**
	 * Get template for Event Tickets plugin
	 *
	 * @param $filename string Template name (with or without extension)
	 * @param $args     mixed Array of params to use in the template
	 * @param $section  string Subdirectory where to search
	 */
	function yith_wcevti_get_template( $filename, $args = array(), $section = '' ) {
		$ext = strpos( $filename, '.php' ) === false ? '.php' : '';

		$template_name = $section . '/' . $filename . $ext;
		$template_path = WC()->template_path() . 'yith-wcevti/';
		$default_path  = YITH_WCEVTI_TEMPLATE_PATH;

		if ( defined( 'YITH_WCEVTI_PREMIUM' ) ) {
			$premium_template = str_replace( '.php', '-premium.php', $template_name );
			$located_premium  = wc_locate_template( $premium_template, $template_path, $default_path );
			$template_name    = file_exists( $located_premium ) ? $premium_template : $template_name;
		}

		wc_get_template( $template_name, $args, $template_path, $default_path );
	}
}
if ( ! function_exists( 'yith_wcevti_print_error' ) ) {
	/**
	 * Print on debug log messages
	 *
	 * @param $text
	 */
	function yith_wcevti_print_error( $text ) {
		error_log( print_r( $text, true ) );
	}
}
if ( ! function_exists( 'yith_wcevti_add_order_ticket' ) ) {

	/**
	 * Once ordered is processing we create tickets post with all information.
	 *
	 * @param $order_id
	 */
	function yith_wcevti_add_order_ticket( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( defined( 'YITH_WPV_PREMIUM' ) ) {
			if ( 0 != $order->get_parent_id() ) {
				$order = wc_get_order( $order->get_parent_id() );
			}
		}
		$order_items = $order->get_items();
		$order_post  = get_post( $order->get_id() );

		foreach ( $order_items as $order_item_id => $order_item ) {
			if ( isset( $order_item['product_type'] ) ) {

				if ( 'ticket-event' == $order_item['product_type'] ) {
					$event_order     = $order_item;
					$event_item_data = version_compare( WC()->version, '3.0.0', '<' ) ? $order_item['item_meta_array'] : $order_item->get_meta_data();

					$product       = wc_get_product( $event_order['product_id'] );
					$event_created = wc_get_order_item_meta( $order_item_id, '_event_id', true );

					if ( empty( $event_created ) ) {
						$event_post = array(
							'post_author'       => $order_post->post_author,
							'post_date'         => $order_post->post_date,
							'post_date_gmt'     => $order_post->post_date_gmt,
							'post_title'        => $product->get_title(),
							'post_status'       => apply_filters( 'yith_wcevti_on_create_ticket_status', 'publish' ),
							'post_type'         => 'ticket',
							'post_name'         => $order_post->post_name,
							'post_modified'     => $order_post->post_modified,
							'post_modified_gmt' => $order_post->post_modified_gmt,
							'guid'              => $order_post->guid,
							'filter'            => $order_post->filter
						);

						$post_id = wp_insert_post( $event_post );

						update_post_meta( $post_id, 'wc_event_id', yit_get_product_id( $product ) );
						update_post_meta( $post_id, 'wc_total', $order_item['line_total'] );
						update_post_meta( $post_id, 'wc_order_id', $order_id );
						update_post_meta( $post_id, 'wc_order_item_id', $order_item_id );

						do_action( 'yith_end_update_post_meta_ticket', $post_id );

						foreach ( $event_item_data as $key => $event_item ) {
							if ( preg_match( '/_field_/i', $event_item->key ) ) {
								update_post_meta( $post_id, $event_item->key, $event_item->value );
							}
							do_action( 'yith_add_order_custom_item', $post_id, $event_item->key, $event_item->value );
						}

						wc_add_order_item_meta( $order_item_id, '_event_id', $post_id );

						$create_pdf = apply_filters( 'yith_wcveti_create_pdf', true );

						// Create barcodes
						$mail_template = get_post_meta( yit_get_product_id( $product ), '_mail_template', true );
						$barcode       = ! empty( $mail_template['data']['barcode'] ) & 'on' == $mail_template['data']['barcode']['display'] ? $mail_template['data']['barcode'] : false;

						yith_wcevti_generate_barcode( $post_id, $barcode );

						// Create pdf
						if ( $create_pdf ) {
							yith_wcevti_create_pdf( $post_id );
						}
					} else {
						foreach ( $event_item_data as $key => $event_item ) {
							do_action( 'yith_update_order_custom_item', $event_created, $event_item->key, $event_item->value );
						}

					}
				}
			}
		}
	}
}
if ( ! function_exists( 'yith_wcevti_set_args_mail_template' ) ) {
	/**
	 * Define the args to mail template
	 *
	 * @param $post
	 *
	 * @return array|mixed|void
	 */
	function yith_wcevti_set_args_mail_template( $post ) {
		$args = array();

		$post_id = yit_get_prop( $post, 'ID' );

		$post_meta = get_post_meta( $post_id, '', true );

		$order         = wc_get_order( $post_meta['wc_order_id'][0] );
		$mail_template = get_post_meta( $post_meta['wc_event_id'][0], '_mail_template', true );

		$fields = array();
		$price  = __( 'Free', 'yith-event-tickets-for-woocommerce' ); //@since 1.1.3

		$location = get_post_meta( $post_meta['wc_event_id'][0], '_direction_event', true );
		$date     = yith_wecvti_get_date_message( $post_meta['wc_event_id'][0] );

		if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
			$item_meta   = $post_meta;
			$order_items = $order->get_items();
			$order_item  = $order_items[ $post_meta['wc_order_item_id'][0] ];
			$price       = $order->get_formatted_line_subtotal( $order_item );
		} else {
			$order_item = $order->get_item( $post_meta['wc_order_item_id'][0] );
			$item_meta  = wc_get_order_item_meta( $post_meta['wc_order_item_id'][0], '', $single = true );
			$price      = $order->get_formatted_line_subtotal( $order_item );
		}


		foreach ( $item_meta as $key => $meta ) {
			if ( preg_match( '/field_/i', $key ) ) {
				$label = str_replace( array( 'field_' ), '', $key );
				$label = str_replace( array( '_' ), '', $label );
				$value = $meta[0];

				$fields[] = array(
					$label => $value
				);

			}
		}

		$header_image          = ! empty( $mail_template['data']['header_image']['id'] ) ? wp_get_attachment_image_src( $mail_template['data']['header_image']['id'], 'default_header_mail' ) : array( YITH_WCEVTI_ASSETS_URL . 'images/header_image.png' );
		$display_ticket_number = isset( $mail_template['data']['display_ticket_number'] ) ? 'on' == $mail_template['data']['display_ticket_number'] ? $mail_template['data']['display_ticket_number'] : false : false;
		$barcode               = ! empty( $mail_template['data']['barcode'] ) & 'on' == $mail_template['data']['barcode']['display'] ? $mail_template['data']['barcode'] : false;
		$content_image         = ! empty( $mail_template['data']['background_image']['id'] ) ? wp_get_attachment_image_src( $mail_template['data']['background_image']['id'], 'default_content_mail' ) : array( YITH_WCEVTI_ASSETS_URL . 'images/background_image.png' );
		$footer_image          = ! empty( $mail_template['data']['footer_image']['id'] ) ? wp_get_attachment_image_src( $mail_template['data']['footer_image']['id'], 'default_footer_mail' ) : array( YITH_WCEVTI_ASSETS_URL . 'images/footer_image.png' );
		$barcode_rendered      = yith_wcevti_get_barcode_rendered( $post_id, $barcode );

		if ( isset( $mail_template['type'] ) ) {
			$args = array(
				'post'                  => $post,
				'fields'                => $fields,
				'location'              => $location,
				'date'                  => $date,
				'price'                 => $price,
				'mail_template'         => $mail_template,
				'header_image'          => $header_image,
				'display_ticket_number' => $display_ticket_number,
				'barcode'               => $barcode,
				'barcode_rendered'      => $barcode_rendered,
				'content_image'         => $content_image,
				'footer_image'          => $footer_image
			);
			$args = apply_filters( 'yith_wcevti_set_custom_mail_args', $args, $post_meta, $item_meta );
		}

		return $args;
	}
}
if ( ! function_exists( 'yith_wecvti_print_mail_template_preview' ) ) {
	/**
	 * Get template that print the ticket $post
	 *
	 * @param $post
	 */
	function yith_wecvti_print_mail_template_preview( $post ) {
		$args = yith_wcevti_set_args_mail_template( $post );

		return yith_wcevti_get_template( $args['mail_template']['type'], $args, 'tickets' );
	}
}
if ( ! function_exists( 'yith_wcevti_create_pdf' ) ) {
	/*
	 * Generate pdf file from ticket $id and save on YITH_WCEVTI_DOCUMENT_SAVE_PDF_DIR uploads folder
	 */
	function yith_wcevti_create_pdf( $id ) {
		$pdf_content = yith_wcevti_ticket_to_pdf( $id );
		$pdf_name    = yith_wecvti_generate_pdf_name( $id );

		return file_put_contents( YITH_WCEVTI_DOCUMENT_SAVE_PDF_DIR . '/' . $pdf_name . '.pdf', $pdf_content );
	}
}
if ( ! function_exists( 'yith_wecvti_generate_pdf_name' ) ) {
	/**
	 * Generate a random and unique pdf ticket name
	 *
	 * @param $id_ticket
	 *
	 * @return string
	 */
	function yith_wecvti_generate_pdf_name( $id_ticket ) {
		$name = uniqid( 'ticket_' );
		update_post_meta( $id_ticket, 'yith_ticket_pdf_name', $name );

		return $name;
	}
}
if ( ! function_exists( 'yith_wecvti_get_pdf_name' ) ) {
	/**
	 * Get the pdf ticket name
	 *
	 * @param $id_ticket
	 *
	 * @return string
	 */
	function yith_wecvti_get_pdf_name( $id_ticket ) {
		$name = get_post_meta( $id_ticket, 'yith_ticket_pdf_name', true );
		$name = ! empty( $name ) ? $name : $id_ticket;

		return $name;
	}
}
if ( ! function_exists( 'yith_wcevti_ticket_to_pdf' ) ) {
	/**
	 * Generate the pdf ticket from $id_ticket
	 *
	 * @param $id_ticket
	 *
	 * @return string
	 */
	function yith_wcevti_ticket_to_pdf( $id_ticket ) {
		$post = get_post( $id_ticket );

		ob_start();
		$args = yith_wcevti_set_args_mail_template( $post );
		yith_wcevti_get_template( 'default-css', $args, 'tickets' );

		$css = ob_get_clean();

		ob_start();
		$args = yith_wcevti_set_args_mail_template( $post );
		yith_wcevti_get_template( 'default-html', $args, 'tickets' );


		$html = ob_get_clean();

		$margin_top    = apply_filters( 'yith_wcevti_pdf_margin_top', '' );
		$margin_left   = apply_filters( 'yith_wcevti_pdf_margin_left', '' );
		$margin_bottom = apply_filters( 'yith_wcevti_pdf_margin_bottom', '' );
		$margin_right  = apply_filters( 'yith_wcevti_pdf_margin_right', '' );

		$margin_pdf = '
			<style>
				@page {
   					margin-top: ' . $margin_top . ';
    			    margin-left: ' . $margin_left . ';
    			    margin-bottom: ' . $margin_bottom . ';
    			    margin-right: ' . $margin_right . ';
    			}
			</style>';

        $config = apply_filters('yith_wcevti_mpdf_args',[]);

        $mpdf=new \Mpdf\Mpdf($config);


		$mpdf->WriteHTML( $margin_pdf );
		$mpdf->WriteHTML( '<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,800" rel="stylesheet">', 2 );
		$mpdf->WriteHTML( $css, 1 );
		$mpdf->WriteHTML( $html, 2 );

		$pdf = $mpdf->Output( 'ticket', 'S' );

		return $pdf;
	}
}
if ( ! function_exists( 'yith_wcevti_get_pdf_path' ) ) {
	/**
	 * Return the ticket basedir path from $id_ticket
	 *
	 * @param $id_ticket
	 *
	 * @return string
	 */
	function yith_wcevti_get_pdf_path( $id_ticket ) {
		$upload_dir = wp_upload_dir();

		return $upload_dir['basedir'] . '/ywcevti-pdf-tickets/' . yith_wecvti_get_pdf_name( $id_ticket ) . '.pdf';
	}
}
if ( ! function_exists( 'yith_wcevti_get_pdf_url' ) ) {
	/**
	 * Return the ticket baseurl path from $id_ticket
	 *
	 * @param $id_ticket
	 *
	 * @return string
	 */
	function yith_wcevti_get_pdf_url( $id_ticket ) {
		$upload_dir = wp_upload_dir();

		return $upload_dir['baseurl'] . '/ywcevti-pdf-tickets/' . yith_wecvti_get_pdf_name( $id_ticket ) . '.pdf';
	}
}
if ( ! function_exists( 'yith_wcevti_get_google_calendar_link' ) ) {
	/**
	 * Generates Google Calendar URL
	 *
	 * @param $id_product
	 *
	 * @return string
	 */
	function yith_wcevti_get_google_calendar_link( $id_product ) {
		$post  = get_post( $id_product );
		$title = urlencode( $post->post_title );

		$start_date  = str_replace( '-', '', get_post_meta( $id_product, '_start_date_picker', true ) );
		$start_time  = str_replace( ':', '', get_post_meta( $id_product, '_start_time_picker', true ) );
		$end_date    = str_replace( '-', '', get_post_meta( $id_product, '_end_date_picker', true ) );
		$end_time    = str_replace( ':', '', get_post_meta( $id_product, '_end_time_picker', true ) );
		$description = urlencode( wp_strip_all_tags( get_post_field( 'post_content', $id_product ) ) );
		$direction   = str_replace( ' ', '+', get_post_meta( $id_product, '_direction_event', true ) );
		$text        = '';
		if ( ! empty( $title ) ) {
			$text = '&text=' . $title;
		}

		$dates = '';
		if ( ! empty( $start_date ) & ! empty( $end_date ) ) {
			$start_time = ( strlen( $start_time ) <= 3 ) ? '0' . $start_time : $start_time;
			$end_time   = ( strlen( $end_time ) <= 3 ) ? '0' . $end_time : $end_time;

			if ( ! empty( $start_time ) & ! empty( $end_time ) ) {
				$dates = '&dates=' . $start_date . 'T' . $start_time . '00/' . $end_date . 'T' . $end_time . '00';
			} else {
				$dates = '&dates=' . $start_date . '/' . $end_date;
			}
		}

		$details = '';
		if ( ! empty( $description ) ) {
			$details = '&details=' . $description;
		}

		$location = '';
		if ( ! empty( $direction ) ) {
			$location = '&location=' . $direction;
		}

		$link = 'https://calendar.google.com/calendar/render?action=TEMPLATE' . $text . $dates . $details . $location . '&sf=true&output=xml';

		return $link;
	}
}
if ( ! function_exists( 'yith_wecvti_get_date_message' ) ) {
	/**
	 * Define the message when date we use this method
	 *
	 * @param $id
	 *
	 * @return array
	 */
	function yith_wecvti_get_date_message( $id ) {
		$product       = wc_get_product( $id );
		$message_start = '';
		$message_end   = '';

		$start_date = yit_get_prop( $product, '_start_date_picker', true );
		$start_time = yit_get_prop( $product, '_start_time_picker', true );
		$end_date   = yit_get_prop( $product, '_end_date_picker', true );
		$end_time   = yit_get_prop( $product, '_end_time_picker', true );

		if ( ! empty( $start_date ) & ! empty( $end_date ) ) {
			$start_text = __( 'Start', 'yith-event-tickets-for-woocommerce' ); //@since 1.1.3
			$at_text    = __( 'at', 'yith-event-tickets-for-woocommerce' ); //@since 1.1.3
			$end_text   = __( 'Finish', 'yith-event-tickets-for-woocommerce' ); //@since 1.1.3

			$from_text = __( 'From', 'yith-event-tickets-for-woocommerce' );//@since 1.1.8
			$to_text   = __( 'to', 'yith-event-tickets-for-woocommerce' );//@since 1.1.8

			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );

			$start_timestamp = strtotime( $start_date . ' ' . $start_time );
			$end_timestamp   = strtotime( $end_date . ' ' . $end_time );

			$start_date = date_i18n( $date_format, $start_timestamp );
			$end_date   = date_i18n( $date_format, $end_timestamp );
			$start_time = ! empty( $start_time ) ? date( $time_format, $start_timestamp ) : '';
			$end_time   = ! empty( $end_time ) ? date( $time_format, $end_timestamp ) : '';


			if ( ! empty( $start_time ) & ! empty( $end_time ) ) {
				if ( $start_date == $end_date ) {
					$message_start = $start_text . ': <b>' . $start_date . '</b>';
					$message_end   = $from_text . ': <b>' . $start_time . '</b> ' . $to_text . ' <b>' . $end_time . '</b>';
				} else {
					$message_start = $start_text . ': <b>' . $start_date . '</b> ' . $at_text . ' <b>' . $start_time . '</b> ';
					$message_end   = $end_text . ': <b>' . $end_date . '</b> ' . $at_text . ' <b>' . $end_time . '</b>';
				}
			} else {
				$message_start = $start_text . ': <b>' . $start_date . '</b> ';
				$message_end   = $end_text . ': <b>' . $end_date . '</b>';
			}
		}
		$time_attributes = array(
			'start_date' => $start_date,
			'start_time' => $start_time,
			'end_date'   => $end_date,
			'end_time'   => $end_time
		);
		$message_start   = apply_filters( 'yith_start_date_message', $message_start, $time_attributes );
		$message_end     = apply_filters( 'yith_end_date_message', $message_end, $time_attributes );

		return array( 'message_start' => $message_start, 'message_end' => $message_end );
	}
}
if ( ! function_exists( 'yith_wcevti_get_user_from_order' ) ) {
	/**
	 * Return $user_purchased from $order
	 *
	 * @param $order
	 *
	 * @return array
	 */
	function yith_wcevti_get_user_from_order( $order ) {
		$user_purchased = array();

		$user_id      = '';
		$display_name = '';
		$user_email   = '';
		if ( is_a( $order, 'WC_Order' ) ) {
			$user = $order->get_user();
			if ( is_a( $user, 'WP_User' ) ) {
				$user_id      = $user->ID;
				$display_name = $user->data->display_name;
				$user_email   = $user->data->user_email;
			} else {
				$display_name = yit_get_prop( $order, 'billing_first_name' );
				$user_email   = yit_get_prop( $order, 'billing_email' );
			}
		}

		$user_purchased['user_id']      = $user_id;
		$user_purchased['display_name'] = $display_name;
		$user_purchased['user_email']   = $user_email;

		return $user_purchased;
	}
}
if ( ! function_exists( 'is_user_owner' ) ) {
	/**
	 * Ask if the current logged user is owner for ticket
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	function is_user_owner( $id ) {
		$owner    = false;
		$order_id = get_post_meta( $id, 'wc_order_id', true );
		$order    = wc_get_order( $order_id );
		if ( get_current_user_id() == yit_get_prop( $order, 'user_id' ) ) {
			$owner = true;
		}

		return $owner;
	}
}
if ( ! function_exists( 'yith_wcevti_generate_barcode' ) ) {
	function yith_wcevti_generate_barcode( $id, $barcode_options ) {
		$barcode_rendered = '';

		if ( defined( 'YITH_YWBC_PREMIUM' ) & false != $barcode_options ) {
			$barcode_id = 0;
			switch ( $barcode_options['type'] ) {
				case 'ticket':
					$barcode_id = $id;
					break;
				case 'product':
					$barcode_id = get_post_meta( $id, 'wc_event_id', true );
					break;
				case 'order':
					$barcode_id = get_post_meta( $id, 'wc_order_id', true );
					break;
			}

			$barcode_object = new YITH_Barcode( $barcode_id );
			$protocol       = apply_filters( 'yith_wcevti_generate_barcode_protocol', 'EAN8' );
			$barcode_object->generate( $protocol, $barcode_id );
			update_post_meta( $id, '_barcode_display_value_' . $barcode_options['type'], $barcode_object->get_display_value() );
			$barcode_object->save();
			$barcode_rendered = do_shortcode( '[yith_render_barcode protocol="' . $protocol . '" value="' . $barcode_id . '" layout="<div class=\'barcode-image\'>{barcode_image}</div><div class=\'barcode-code\'>{barcode_code}</div>"]' );
			update_post_meta( $id, '_barcode_html', $barcode_rendered );
		}

		return $barcode_rendered;
	}
}

if ( ! function_exists( 'yith_wcevti_get_barcode_rendered' ) ) {
	function yith_wcevti_get_barcode_rendered( $ticket_id ) {
		$barcode_rendered = get_post_meta( $ticket_id, '_barcode_html', true );

		if ( empty( $barcode_rendered ) ) {

			$product_id    = get_post_meta( $ticket_id, 'wc_event_id', true );
			$mail_template = get_post_meta( $product_id, '_mail_template', true );
			$barcode       = ! empty( $mail_template['data']['barcode'] ) & 'on' == $mail_template['data']['barcode']['display'] ? $mail_template['data']['barcode'] : false;

			$barcode_rendered = yith_wcevti_generate_barcode( $ticket_id, $barcode );
		}
		return apply_filters('yith_ywet_barcode_rendered',$barcode_rendered,$ticket_id);
	}
}

if ( ! function_exists( 'yith_wcevti_get_barcode_value' ) ) {
	function yith_wcevti_get_barcode_value( $id, $barcode_options ) {
		$barcode_value = get_post_meta( $id, '_barcode_display_value_' . $barcode_options['type'], true );

		if ( ! empty( $barcode_value ) ) {
			return $barcode_value;
		} else {
			return yith_wcevti_generate_barcode( $id, $barcode_options );
		}
	}
}

if ( ! function_exists( 'yith_wcevti_get_fields' ) ) {
	/**
	 * Get the fields post meta from post
	 *
	 * @param $post
	 *
	 * @return array
	 */
	function yith_wcevti_get_fields( $id ) {
		$post_meta = get_post_meta( $id, '', true );

		if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
			$item_meta = $post_meta;
		} else {
			$item_meta = wc_get_order_item_meta( $post_meta['wc_order_item_id'][0], '', $single = true );
		}

		$fields = array();
		foreach ( $item_meta as $key => $meta ) {
			if ( preg_match( '/_field_/i', $key ) ) {
				$label = str_replace( array( '_field_' ), '', $key );
				$label = str_replace( array( '_' ), '', $label );

				//TODO future feature, display surcharge on ticket template...
				//$surcharge = yith_wcevti_get_service_surcharge($post_meta['wc_event_id'][0], $label);
				$value = $meta[0];

				$fields[] = array(
					$label => $value
				);
			}
		}

		return $fields;
	}
}
if ( ! function_exists( 'array_splice_assoc' ) ) {
	/**
	 * Similar behavior that php array_splice method but on this case we keep the keys.
	 *
	 * @param       $input
	 * @param       $offset
	 * @param int   $length
	 * @param array $replacement
	 *
	 * @return array
	 */
	function array_splice_assoc( &$input, $offset, $length = 0, $replacement = array() ) {
		$count = count( $input );
		if ( is_string( $offset ) ) {
			$offset = array_search( $offset, array_keys( $input ) );
			if ( $offset === false ) {
				$offset = $count;
			}
		} elseif ( $offset < 0 ) {
			$offset = max( $count + $offset, 0 );
		}
		$pre     = array_slice( $input, 0, $offset, true );
		$post    = array_slice( $input, $offset + $length, null, true );
		$removed = array_slice( $input, $offset, $offset + $length, true );
		$input   = $pre + $replacement + $post;

		return $removed;
	}
}

if ( ! function_exists( 'yith_check_if_expired_event' ) ) {
	function yith_check_if_expired_event( $start_date = '', $start_time = '', $context = 'purchasable' ) {
		$purchasable = true;

		if ( ! empty( $start_date ) ) {
			$date_event = strtotime( $start_date . ' ' . $start_time );
			$date_now   = strtotime( 'now' );

			if ( 'purchasable' == $context ) {
				$purchasable = ( $date_event >= $date_now ) ? true : false;
			}

			if ( 'expired' == $context ) {
				$delay_time     = get_option( 'yith_wcte_delay_expired', true );
				$seconds_passed = $date_now - $date_event;
				$days_passed    = ceil( $seconds_passed / DAY_IN_SECONDS );

				$purchasable = ( $days_passed > $delay_time ) ? false : true;
			}

		}

		return $purchasable;
	}
}
