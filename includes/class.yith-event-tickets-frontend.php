<?php
/*
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */
if ( ! defined( 'YITH_WCEVTI_VERSION' ) ) {
	exit( 'Direct access forbidden.' );
}

/**
 *
 *
 * @class      YITH_Tickets_Frontend
 * @package    Yithemes
 * @since      Version 1.0.0
 * @author     Francisco Mateo
 *
 */

if ( ! class_exists( 'YITH_Tickets_Frontend' ) ) {
	/**
	 * Class YITH_Tickets_Frontend
	 *
	 * @author Francisco Mateo
	 */
	class YITH_Tickets_Frontend {

		/**
		 * Construct
		 *
		 * @author Francisco Mateo
		 * @since  1.0
		 */

		protected static $instance = null;

		public function __construct() {

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_filter( 'woocommerce_is_purchasable', array( $this, 'set_purchasable' ) );
			add_filter( 'woocommerce_product_is_visible', array( $this, 'set_visible' ) );

			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'load_fields_event' ) );

			add_action( 'woocommerce_ticket-event_add_to_cart', array( $this, 'event_add_to_cart' ) );
			add_action( 'woocommerce_add_to_cart_handler_ticket-event', array( $this, 'add_event_to_cart' ), 10, 1 );

			add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
			add_filter( 'woocommerce_add_cart_item', array( $this, 'set_cart_item' ), 10, 1 );
			add_filter( 'woocommerce_cart_id', array( $this, 'set_cart_id' ), 10, 5 );

			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'update_cart_item_session' ), 10, 1 );
			add_filter( 'woocommerce_cart_item_quantity', array( $this, 'set_quantity_on_sold_individually' ), 10, 3 );

			add_action( 'wp_ajax_load_fields_event_action', array( $this, 'load_fields_event_action' ) );
			add_action( 'wp_ajax_nopriv_load_fields_event_action', array( $this, 'load_fields_event_action' ) );

			//Validation actions...
			add_action( 'yith_wcevti_passed_require_fields', array( $this, 'check_validation_fields' ), 10, 2 );

			//Order actions...

			if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
				add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_order_item_meta' ), 10, 3 );
			} else {
				add_action( 'woocommerce_new_order_item', array( $this, 'add_order_item_meta' ), 10, 3 );
			}

			//add_action( 'woocommerce_order_status_completed', array( $this, 'add_order_ticket' ), 10, 1 );
			//add_action( 'woocommerce_order_status_processing', array( $this, 'add_order_ticket' ), 10, 1 );

			add_action( 'woocommerce_order_item_meta_start', array( $this, 'add_dates_on_order_item' ), 10, 3 );
			add_action( 'woocommerce_order_item_meta_end', array( $this, 'add_order_item_meta_display' ), 10, 3 );
			add_action( 'yith_order_item_meta_end', array( $this, 'add_view_pdf_and_gcalendar_button' ), 10, 3 );
		}

		/**
		 * Enqueue Scripts
		 *
		 * Register and enqueue scripts for Frontend
		 *
		 * @author Francisco Mateo
		 * @since  1.0
		 * @return void
		 */
		public function enqueue_scripts() {
			global $post, $wp_scripts;

			$api_key        = get_option( 'yith_wcte_api_key_gmaps' );
			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';

			//Register external maps script...
			wp_register_script( 'yith-wc-script-gmaps', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places', null, YITH_WCEVTI_VERSION, true );

			// Register frontend style
			wp_register_style( 'yith-wc-style-frontend-fontawesome-tickets', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', null, YITH_WCEVTI_VERSION );

			// Register frontend style
			wp_register_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.min.css', array(), $jquery_version );
			wp_register_style( 'yith-wc-style-frontend-event-tickets', YITH_WCEVTI_ASSETS_URL . 'css/style-frontend.css', array( 'jquery-ui-style' ), YITH_WCEVTI_VERSION );

			do_action( 'yith_wcevti_register_scripts' );

			if ( is_product() ) { //Only load script if we are on single product.
				$product = wc_get_product( $post->ID );

				if ( $product && 'ticket-event' == $product->get_type() ) {

					$path   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '/unminified' : '';
					$prefix = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';

					// Register the script (our handler, our src, our dependencies, our version)
					wp_register_script( 'yith-wcevti-script-cookie', YITH_WCEVTI_ASSETS_URL . 'js-cookie/src/js.cookie.js', array(), YITH_WCEVTI_VERSION );
					wp_register_script( 'yith-wcevti-script-tickets-frontend', apply_filters( 'yith_wcevti_script_tickets_frontend_register_url' ,YITH_WCEVTI_ASSETS_URL . '/js' . $path . '/script-tickets-frontend' . $prefix . '.js' ), array(
						'jquery',
						'yith-wcevti-script-cookie',
						'jquery-ui-datepicker',
						'accounting'
					), YITH_WCEVTI_VERSION );


					// Create array will be printed depends our language
					$data_to_js = array(
						'product'           => array(
							'id'                 => $post->ID,
							'price'              => yit_get_prop( $product, 'price' ),
							'price_decimal_sep'  => wc_get_price_decimal_separator(),
							'price_thousand_sep' => wc_get_price_thousand_separator(),
							'price_num_decimals' => wc_get_price_decimals()
						),
						'labels'            => array(
							'start_date' => __( 'Start date', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.3
							'end_date'   => __( 'End date', 'yith-event-tickets-for-woocommerce' ) //@since 1.1.3
						),
						'messages'          => array(
							'complete_field_service'   => __( 'Complete', 'yith-event-tickets-for-woocommerce' ),
							//@since 1.1.3
							'incomplete_field_service' => __( 'Need to fill one or more of the above fields', 'yith-event-tickets-for-woocommerce' ),
							//@since 1.1.3
							'complete_required_item'   => __( 'Complete', 'yith-event-tickets-for-woocommerce' ),
							//@since 1.1.3
							'incomplete_required_item' => __( 'Incomplete', 'yith-event-tickets-for-woocommerce' ),
							//@since 1.1.3
							'wrong_mail_field'         => __( 'Incorrect email format', 'yith-event-tickets-for-woocommerce' ),
							//@since 1.1.3
							'wrong_number_field'       => __( 'Incorrect number format', 'yith-event-tickets-for-woocommerce' ),
							//@since 1.1.3
							'wrong_date_field'         => __( 'Incorrect date format', 'yith-event-tickets-for-woocommerce' ),
							//@since 1.1.3
							'ask_for_delete_ticket'    => __( 'Are you sure you want to delete', 'yith-event-tickets-for-woocommerce' ),
							//@since 1.1.3
							'tickets'                  => __( 'tickets', 'yith-event-tickets-for-woocommerce' ),
							//@since 1.1.3
							'ticket'                   => __( 'ticket', 'yith-event-tickets-for-woocommerce' )
							//@since 1.1.3
						),
						'date_format'       => get_option( 'date_format' ),
						'ajax_enable'       => apply_filters( 'yith_wcevti_load_ticket_form_via_ajax', false ),
						'sold_individually' => apply_filters( 'yith_wcevti_sold_individually_js', true ),
						'disable_cookie'    => yit_get_prop( $product, '_disable_cookie_form' ),
                        'format_date'       => apply_filters('yith_wcevti_date_format_frontend_js','mm/dd/yy'),
					);
					$data_to_js = apply_filters( 'yith_wcevti_data_to_js_custom', $data_to_js );

					//add translation array on our handler (our handler, name variable will contain the data on our javascript, array translation)
					wp_localize_script( 'yith-wcevti-script-tickets-frontend', 'yith_wcevti_tickets', $data_to_js );

					/*
					* Localize script to bind with our admin-ajax.php
					*
					* wp_localize_script('our tag script', 'our name object', 'array to bind files or variables')
					* See file assets/js/script-tickets-frontend.js to look how to load call ajax.
					* If we use shortcode [event_calendar] that will ve enqueue on class.yith-event-tickets-shortcodes.php::event_calendar()
					*/
					wp_localize_script( 'yith-wcevti-script-tickets-frontend', 'event_tickets_frontend', array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
					) );

					wp_enqueue_script( 'jquery-ui-accordion' );
					wp_enqueue_script( 'yith-wcevti-script-tickets-frontend' );

					//Enqueue style if is product
					wp_enqueue_style( 'yith-wc-style-frontend-fontawesome-tickets' );
					wp_enqueue_style( 'yith-wc-style-frontend-event-tickets' );
				}

			}
		}

		/**
		 * Set Purchasable
		 *
		 * Define purchasable event ticket
		 *
		 * @author  Francisco Mateo
		 * @sice    1.1.4
		 *
		 * @param   $purchasable
		 *
		 * @return  mixed
		 */
		public function set_purchasable( $purchasable ) {
			global $product;
			$purchasable_option = get_option( 'yith_wcte_purchasable_expired' );

			if ( $purchasable_option == 'yes' & ! empty( $product ) ) {
				$purchasable = $this->expired_date( $product );
			}

			if ( ! $purchasable ) {
				add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'set_add_to_cart_text' ) );
			}

			return $purchasable;
		}

		public function set_add_to_cart_text( $text ) {
			global $product;
			$type = yit_get_prop($product, 'type');

			if('ticket-event' == $type) {
				$text = get_option( 'yith_wcte_text_expired' );
				$text = !empty($text) ? $text : __( 'Expired Event', 'yith-stripe-connect-for-woocommerce' );
			}

			return $text;
		}

		/**
		 * Set Visible
		 *
		 * Define visibility event ticket
		 *
		 * @author  Francisco Mateo
		 * @since   1.1.4
		 *
		 * @param   $visible
		 *
		 * @return mixed
		 */
		public function set_visible( $visible ) {
			global $product;
			$visible_option = get_option( 'yith_wcte_visible_expired' );

			if ( $visible_option == 'yes' & ! empty( $product ) ) {
				$visible = $this->expired_date( $product );
			}

			return $visible;
		}

		/**
		 * Expired date
		 *
		 * Ask if Event date is expired.
		 *
		 * @author  Francisco Mateo
		 * @since   1.1.4
		 *
		 * @param $product
		 *
		 * @return bool
		 */
		public function expired_date( $product ) {
			$start_date_event = '';
			$start_time_event = '';
			if ( is_object( $product ) ) {
				$start_date_event = yit_get_prop( $product, '_start_date_picker', true );
				$start_time_event = yit_get_prop( $product, '_start_time_picker', true );
			}
			$expired_date_time = yith_check_if_expired_event( $start_date_event, $start_time_event, 'expired');

			return $expired_date_time;
		}

		public function load_fields_event() {
			global $product;
			if ( 'ticket-event' == $product->get_type() ) {

                $already_sold = yit_get_prop( $product, '_already_sold', true );

                if ( isset( $already_sold[ '_enable' ] ) && $already_sold[ '_enable' ]  == 'on' ){
                    $args = array(
                        'product_id'       => $product->get_id(),
                    );
                    yith_wcevti_get_template( 'show_tickets_already_sold', $args, 'frontend' );
                }

				$date_message = yith_wecvti_get_date_message( yit_get_product_id( $product ) );
				yith_wcevti_get_template( 'date_panel', $date_message, 'frontend' );

				$args = array(
					'price'       => $product->get_price(),
					'event_title' => $product->get_title()
				);
				yith_wcevti_get_template( 'fields_panel', $args, 'frontend' );
			}
		}

		public function load_fields_event_action() {
			// If is ajax call ask load num rows field.
			if ( isset( $_POST['num_rows'] ) ) {
				$product       = wc_get_product( $_POST['product_id'] );
				$num_rows      = $_POST['num_rows'];
				$current_index = $_POST['current_index'];

				//TODO for future improves see minimum and maximun quantities plugin YITH, check its compatible.
				//$current_stock = yit_get_prop($product, '_stock', true);
				//$num_tickets = $num_rows + $current_index;

				$fields = apply_filters( 'yith_wcevti_product_fields', yit_get_prop( $product, '_fields', true ), $product );

				$reduce_ticket = yit_get_prop( $product, '_reduce_ticket', true );
				$price         = yit_get_prop( $product, 'price', true );

				$event_title = $_POST['event_title'];

				$args = array(
					'product_id'    => $_POST['product_id'],
					'num_rows'      => $num_rows,
					'row'           => $current_index,
					'fields'        => $fields,
					'event_title'   => $event_title,
					'reduce_ticket' => $reduce_ticket,
					'price'         => $price
				);

				yith_wcevti_get_template( 'fields_services_row', $args, 'frontend' );

				die();

				// If we load the page only load one row.
			} else {
				global $product;


				$fields        = apply_filters( 'yith_wcevti_product_fields', yit_get_prop( $product, '_fields', true ), $product );
				$event_title   = $product->get_title();
				$reduce_ticket = yit_get_prop( $product, '_reduce_ticket', true );
				$price         = yit_get_prop( $product, 'price', true );

				$args = array(
					'product_id'    => yit_get_product_id( $product ),
					'num_rows'      => 1,
					'row'           => 0,
					'fields'        => $fields,
					'event_title'   => $event_title,
					'reduce_ticket' => $reduce_ticket,
					'price'         => $price
				);
				yith_wcevti_get_template( 'fields_services_row', $args, 'frontend' );
			}
		}

		public function event_add_to_cart() {
			yith_wcevti_get_template( 'simple-ticket', array(), 'frontend' );
		}

		public function add_event_to_cart( $url = false ) {

			$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
			$quantity   = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_REQUEST['quantity'] );

			$fields_customer = ! empty( $_REQUEST['_fields_customer'] ) ? $_REQUEST['_fields_customer'] : array();

			$passed_validation = apply_filters( 'yith_wcevti_passed_require_fields', true, $product_id );

			$passed_validation = apply_filters( 'yith_wcevti_passed_custom', $passed_validation, $product_id, $quantity );

			if ( $passed_validation ) {
				$sold_individually = apply_filters( 'yith_wcevti_event_to_cart_individually', true );
				if ( $sold_individually ) {
					for ( $i = 0; $i < $quantity; $i ++ ) {
						$product_data['_field_service'] = array(
							'_index'  => $i,
							'_fields' => ( isset( $fields_customer[ $i ] ) ) ? $fields_customer[ $i ] : array()
						);

						$product_data = apply_filters( 'yith_wcevti_before_add_to_cart', $product_data, $product_id );
						if ( WC()->cart->add_to_cart( $product_id, 1, 0, array(), $product_data ) ) {
							wc_add_to_cart_message( array( $product_id => $i ), false );
						}
					}
				} else {
					$product_data['_field_service'] = array(
						'_index'  => 0,
						'_fields' => ( isset( $fields_customer[0] ) ) ? $fields_customer[0] : array()
					);

					if ( WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $product_data ) ) {
						wc_add_to_cart_message( array( $product_id => $quantity ), false );
					}
				}
			}
			if ( wc_notice_count( 'error' ) === 0 ) {
				// If has custom URL redirect there
				if ( $url = apply_filters( 'woocommerce_add_to_cart_redirect', $url ) ) {
					wp_safe_redirect( $url );
					exit;
				} elseif ( get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes' ) {
					wp_safe_redirect( wc_get_cart_url() );
					exit;
				}
			}

		}

		public function get_item_data( $item_data, $cart_item ) {
			$set_field_service = isset( $cart_item['_field_service'] ) ? is_array( $cart_item['_field_service']['_fields'] ) ? true : false : false;
			if ( $set_field_service ) {
				$product     = wc_get_product( $cart_item['product_id'] );
				$fields      = $cart_item['_field_service']['_fields'];
				$fields_data = apply_filters( 'yith_wcevti_product_fields', yit_get_prop( $product, '_fields', true ), $product );

				foreach ( $fields as $field ) {
					if ( ! empty( $field['_value'] ) ) {
						foreach ( $fields_data as $field_data ) {
							$label = apply_filters( 'yith_wcevti_field_label', sanitize_title( $field_data['_label'] ), $field_data );
							if ( $label == $field['_key'] ) {
								switch ( $field_data['_type'] ) {
									case 'date':
										$format      = get_option( 'date_format' );
										$item_data[] = array(
											'key'   => $field_data['_label'],
											'value' => date_i18n( $format, strtotime( $field['_value'] ) )
										);
										break;
									case 'check':
										$item_data[] = array(
											'key'   => $field_data['_label'],
											'value' => ( 'on' == $field['_value'] ) ? __( 'Yes', 'yith-event-tickets-for-woocommerce' ) : __( 'No', 'yith-event-tickets-for-woocommerce' )
											//@since 1.1.3
										);
										break;
									case 'yes-no':
										$item_data[] = array(
											'key'   => $field_data['_label'],
											'value' => ( 'yes' == $field['_value'] ) ? __( 'Yes', 'yith-event-tickets-for-woocommerce' ) : __( 'No', 'yith-event-tickets-for-woocommerce' )
											//@since 1.1.3
										);
										break;
									default:
										$item_data[] = array(
											'key'   => $field_data['_label'],
											'value' => $field['_value']
										);
										break;
								}
							}
						}
					}
				}
				$item_data = apply_filters( 'yith_wcevti_get_item_data', $item_data, $cart_item );

			}

			return $item_data;
		}

		public function set_cart_item( $cart_item_data ) {
			$sold_individually = apply_filters( 'yith_wcevti_sold_individually', 'yes' );
			$_product          = $cart_item_data['data'];
			if ( 'ticket-event' == $_product->get_type() ) {
				if ( isset( $cart_item_data['_field_service'] ) ) {
					if ( is_array( $cart_item_data['_field_service']['_fields'] ) ) {
						foreach ( $cart_item_data['_field_service']['_fields'] as $field ) {
							if ( ! empty( $field['_value'] ) ) {
								yit_set_prop( $cart_item_data['data'], 'sold_individually', $sold_individually );
							}
						}
					}
				}
				$cart_item_data = apply_filters( 'yith_wcevti_set_cart_item', $cart_item_data );
			}

			return $cart_item_data;
		}

		public function set_cart_id( $cart_id, $product_id, $variation_id, $variation, $cart_item_data ) {
			$change_cart_id = apply_filters( 'yith_wcevti_change_cart_id', true );

			$product      = wc_get_product( $product_id );
			$product_type = yit_get_prop( $product, 'type' );

			if ( 'ticket-event' == $product_type ) {
				return ( $change_cart_id ) ? md5( $cart_id . rand() ) : $cart_id;
			}

			return $cart_id;
		}

		function set_quantity_on_sold_individually( $product_quantity, $cart_item_key, $cart_item = null ) {
			if ( isset( $cart_item ) ) {
				$product = $cart_item['data'];

                if ( $product->get_type() == 'ticket-event' ){
                    $product_quantity = apply_filters( 'yith_ywevti_cart_product_quantity', '1' );
                }
			}

			return $product_quantity;
		}

		public function update_cart_item_session( $session_data ) {

			$session_data = self::set_cart_item( $session_data );

			return $session_data;
		}

		public function check_validation_fields( $passed_required_fields, $product_id ) {
			$product = wc_get_product( $product_id );
			$fields  = apply_filters( 'yith_wcevti_product_fields', yit_get_prop( $product, '_fields', true ), $product );
			if ( isset( $_POST['_fields_customer'] ) ) {
				$fields_customer = $_POST['_fields_customer'];
				foreach ( $fields_customer as $index_panel => $field_panel ) {
					foreach ( $field_panel as $index_item => $field_item ) {
						$value                  = isset( $field_item['_value'] ) ? $field_item['_value'] : '';
						$passed_required_fields = $this->passed_item_field( $field_item['_key'], $value, $fields ) ? $passed_required_fields : false;
					}
				}
			}

			return $passed_required_fields;
		}

		public function add_order_item_meta( $item_id, $values, $cart_item_key ) {

			$meta_data    = isset( $values->legacy_values ) ? $values->legacy_values : $values;
			$product_type = isset( $meta_data['data'] ) ? $meta_data['data']->get_type() : '';
			if ( 'ticket-event' == $product_type ) {

				wc_add_order_item_meta( $item_id, '_product_type', $product_type );
				if ( is_array( $meta_data['_field_service']['_fields'] ) ) {
					foreach ( $meta_data['_field_service']['_fields'] as $i => $field ) {
						if ( ! empty( $field['_value'] ) ) {
							wc_add_order_item_meta( $item_id, '_field_' . $field['_label'], $field['_value'] );
						}
					}
				}
				do_action( 'yith_wcevti_add_order_item_meta', $item_id, $meta_data );
			}
		}

		public function if_field_exist( $key, $fields ) {
			$exist = false;

			foreach ( $fields as $field ) {
				$label = sanitize_title( $field['_label'] );
				if ( $key == $label ) {
					$exist = true;
				}
			}

			return $exist;
		}

		public function passed_item_field( $key, $value, $fields ) {
			$passed = true;

			foreach ( $fields as $field ) {
				$label = sanitize_title( $field['_label'] );
				if ( $key == $label ) {
					if ( isset( $field['_required'] ) ) {
						switch ( $field['_type'] ) {
							case 'text':
								if ( 0 == strlen( $value ) ) {
									$passed = false;
									wc_add_notice( sprintf( __( 'The required text field "%s" is empty.', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.3
										$field['_label'] ), 'error' );
								}
								break;
							case 'textarea':
								if ( 0 == strlen( $value ) ) {
									$passed = false;
									wc_add_notice( sprintf( __( 'The required textarea field "%s" is empty.',
										'yith-event-tickets-for-woocommerce' ), $field['_label'] ), 'error' ); //@since 1.1.3
								}
								break;
							case 'email':
								if ( 0 == strlen( $value ) ) {
									$passed = false;
									wc_add_notice( sprintf( __( 'The required email field "%s" is empty.',
										'yith-event-tickets-for-woocommerce' ), $field['_label'] ), 'error' ); //@since 1.1.3


								} elseif ( ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
									$passed = false;
									wc_add_notice( sprintf( __( 'The format of the email address entered for "%s" is not correct.',
										'yith-event-tickets-for-woocommerce' ), $field['_label'] ), 'error' ); //@since 1.1.3

								}
								break;
							case 'number':
								if ( 0 == strlen( $value ) ) {
									$passed = false;
									wc_add_notice( sprintf( __( 'The required field "%s" is empty.',
										'yith-event-tickets-for-woocommerce' ), $field['_label'] ), 'error' ); //@since 1.1.3
								} elseif ( ! filter_var( $value, FILTER_VALIDATE_INT ) ) {
									$passed = false;
									wc_add_notice( sprintf( __( 'The required field "%s" must be a number.', 'yith-event-tickets-for-woocommerce' ),
										$field['_label'] ), 'error' ); //@since 1.1.3
								}
								break;
							case 'date':
								if ( 0 == strlen( $value ) ) {
									$passed = false;
									wc_add_notice( sprintf( __( 'The required date "%s" is empty.',
										'yith-event-tickets-for-woocommerce' ), $field['_label'] ), 'error' ); //@since 1.1.3
								} elseif ( ! strtotime( $value ) ) {
									$date_splited = explode( '/', $value );
									$aux_date     = count( $date_splited ) == 3 ? $date_splited[2] . '-' . $date_splited[1] . '-' . $date_splited[0] : '';
									if ( ! strtotime( $aux_date ) ) {
										$passed = false;
										wc_add_notice( sprintf( __( 'The date format entered for "%s" is not correct.',
											'yith-event-tickets-for-woocommerce' ), $field['_label'] ), 'error' ); //@since 1.1.3
									}
								}
								break;
							case 'yes-no':
								if ( 0 == strlen( $value ) ) {
									$passed = false;
									wc_add_notice( sprintf( __( 'You must choose an option, "%s" field.',
										'yith-event-tickets-for-woocommerce' ), $field['_label'] ), 'error' ); //@since 1.1.3
								}
							default:
								do_action( 'yith_wcevti_validate_field', $key, $value, $fields );
								break;
						}
					}
				}
			}

			return $passed;
		}

		/**
		 * Once ordered is processing we create tickets post with all information.
		 *
		 * @param $order_id
		 */
		public function add_order_ticket( $order_id ) {
			yith_wcevti_add_order_ticket( $order_id );
		}

		public function add_dates_on_order_item( $item_id, $item, $order ) {

			$date_message = yith_wecvti_get_date_message( $item['product_id'] );

			yith_wcevti_get_template( 'date_panel', $date_message, 'frontend' );
		}

		public function add_order_item_meta_display( $item_id, $item_meta, $order ) {
			$meta_data = isset( $item_meta['item_meta_array'] ) ? $item_meta['item_meta_array'] : yit_get_prop( $item_meta, 'meta_data' );

			$meta_items = array();

			foreach ( $meta_data as $key => $item ) {

				if ( preg_match( '/field_/i', $item->key ) ) {

					$meta_items[] = array(
						'label' => str_replace( '_field_', '', $item->key ),
						'value' => $item->value
					);
				}
				$meta_items = apply_filters( 'yith_wcevti_add_order_item_meta_display_output', $meta_items, $item->key, $item );
			}

			yith_wcevti_get_template( 'order_item_meta_display', array( 'meta_items' => $meta_items ), 'frontend' );

			do_action( 'yith_order_item_meta_end', $item_id, $item_meta, $order, $meta_items );
		}

		public function add_view_pdf_and_gcalendar_button( $item_id, $item, $order ) {
			$post_status = yit_get_prop( $order, 'post_status' );
            $has_deposit = yit_get_prop( $order, '_has_deposit' );
            if ( ('wc-completed' == $post_status | 'wc-processing' == $post_status | 'processing' == $post_status | 'completed' == $post_status) && !$has_deposit  ) {

				if ( isset( $item['event_id'] ) ) {
					$wc_event_id  = get_post_meta( $item['event_id'], 'wc_event_id', true );
					$url_calendar = yith_wcevti_get_google_calendar_link( $wc_event_id );
					$args         = array(
						'event_id'            => $item['event_id'],
						'url_google_calendar' => $url_calendar,
						'wc_event_id'         => $wc_event_id
					);

					wp_enqueue_style( 'yith-wc-style-frontend-fontawesome-tickets' );
					wp_enqueue_style( 'yith-wc-style-frontend-address-tickets' );

					$print = apply_filters( 'yith_wcevti_print_pdf_and_gcalendar_button', ( is_view_order_page() | is_order_received_page() ) );
					if ( $print ) {
						yith_wcevti_get_template( 'view_pdf_and_gcalendar_button', $args, 'frontend' );
					}
				}
			}
		}

		/*** Returns single instance of the class
		 *
		 * @return instance
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}

/**
 * Unique access to instance of YITH_Tickets_Frontend class
 *
 * @return \YITH_Tickets_Frontend
 * @since 1.0.0
 */
function YITH_Tickets_Frontend() {
	return YITH_Tickets_Frontend::get_instance();
}