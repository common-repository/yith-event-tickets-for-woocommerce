<?php
/*
 * This file belongs to the YITH Framework.
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
 * @class      YITH_Tickets
 * @package    Yithemes
 * @since      Version 1.0.0
 * @author     Your Inspiration Themes
 *
 */
if ( ! class_exists( 'YITH_Tickets' ) ) {
	/**
	 * Class YITH_Tickets
	 *
	 * @author Francisco Mateo
	 */
	class YITH_Tickets {
		/**
		 * Plugin version
		 *
		 * @var string
		 * @since 1.0
		 */
		public $version = YITH_WCEVTI_VERSION;

		/**
		 * Main Instance
		 *
		 * @var YITH_Tickets
		 * @since  1.0
		 * @access protected
		 */
		protected static $_instance = null;

		/**
		 * Main Admin Instance
		 *
		 * @var YITH_Tickets_Admin
		 * @since 1.0
		 */
		public $admin = null;

		/**
		 * Main Frontpage Instance
		 *
		 * @var YITH_Tickets_Frontend
		 * @since 1.0
		 */
		public $frontend = null;

		public $widget_calendar = null;

		/**
		 * Construct
		 *
		 * @author Francisco Mateo
		 * @since  1.0
		 */
		public function __construct() {

			/* === Require Main Files === */
			$require = apply_filters( 'yith_wcevti_require_class',
				array(
					'common'   => array(
						'includes/functions.yith-wcevti.php',
						'includes/class.yith-event-tickets-event.php'
					),
					'frontend' => array(
						'includes/class.yith-event-tickets-frontend.php'
					),
					'admin'    => array(
						'includes/class.yith-event-tickets-admin.php'
					)

				)
			);

			$this->_require( $require );

			/* === Load Plugin Framework === */
			add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );
			add_action( 'plugins_loaded', array( $this, 'privacy_loader' ), 20 );

			/* === Product Event Ticket === */
			add_action( 'product_type_selector', array( $this, 'define_product_type' ) );
			add_filter( 'woocommerce_email_attachments', array( $this, 'attach_items_pdf_mail' ), 10, 3 );

			/* === Plugins Init === */
			add_action( 'init', array( $this, 'init' ) );

			add_action( 'init', array( $this, 'order_event_ticket_init' ) );
			add_action( 'init', array( $this, 'add_custom_image_sizes' ) );
			add_action( 'init', array( $this, 'update_postmeta_pdf_security_tickets' ) );
			add_action( 'init', array( $this, 'update_postmeta_fields' ) );
			add_action( 'init', array( $this, 'update_postmeta_barcode_tickets' ) );

			add_action( 'woocommerce_order_status_completed', array( $this, 'add_order_ticket' ), 10, 1 );
			add_action( 'woocommerce_order_status_processing', array( $this, 'add_order_ticket' ), 10, 1 );
		}

		/**
		 * Main plugin Instance
		 *
		 * @return YITH_Tickets Main instance
		 * @author Francisco Mateo
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Add the main classes file
		 *
		 * Include the admin and frontend classes
		 *
		 * @param $main_classes array The require classes file path
		 *
		 * @author Francisco Mateo
		 * @since  1.0
		 *
		 * @return void
		 * @access protected
		 */
		protected function _require( $main_classes ) {
			foreach ( $main_classes as $section => $classes ) {
				foreach ( $classes as $class ) {
					if ( 'common' == $section || ( 'frontend' == $section && ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) || ( 'admin' == $section && is_admin() ) && file_exists( YITH_WCEVTI_PATH . $class ) ) {
						require_once( YITH_WCEVTI_PATH . $class );
					}
				}
			}
			do_action( 'yith_wcevti_require' );
		}

		/**
		 * Load plugin framework
		 *
		 * @author Francisco Mateo
		 * @since  1.0
		 * @return void
		 */
		public function plugin_fw_loader() {
			if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if ( ! empty( $plugin_fw_data ) ) {
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once( $plugin_fw_file );
				}
			}
		}

		/**
		 * Load privacy class
		 *
		 * @author Francisco Mateo
		 * @since  1.0
		 * @return void
		 */
		public function privacy_loader() {
			if( class_exists( 'YITH_Privacy_Plugin_Abstract' ) ) {
				require_once( YITH_WCEVTI_PATH . 'includes/class.yith-event-tickets-privacy.php' );
				new YITH_Tickets_Privacy();
			}
		}

		/**
		 * This function define type product to display on WooCommerce type products selector
		 *
		 * @author Francisco Mateo
		 * @since  1.0
		 * @return $types
		 * @access public
		 */
		public function define_product_type( $types ) {

			$types['ticket-event'] = _x( 'Event Ticket', 'product type in Edit product page', 'yith-event-tickets-for-woocommerce' ); //@since 1.1.3

			return $types;
		}

		/**
		 * Class Initialization
		 *
		 * Instance the admin class
		 *
		 * @author Francisco Mateo
		 * @since  1.0
		 * @return void
		 * @access public
		 */
		public function init() {
			global $wp_query;

			if ( is_admin() ) {
				$this->admin = new YITH_Tickets_Admin();
			}

			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				$this->frontend = new YITH_Tickets_Frontend();
			}
		}

		/**
		 * Register new post type for tickets calles 'ticket'
		 * @since 1.0.0
		 */
		public function order_event_ticket_init() {
			$labels = array(
				'name'          => _x( 'Purchased tickets', 'Handle purchased tickets on your shop', 'yith-event-tickets-for-woocommerce' ),
				//@since 1.1.3
				'singular_name' => _x( 'Purchased tickets', 'Purchased tickets', 'yith-event-tickets-for-woocommerce' ),
				//@since 1.1.3
				'menu_name'     => _x( 'Tickets', 'Purchased tickets', 'yith-event-tickets-for-woocommerce' ),
				//@since 1.1.3
				'edit_item'     => __( 'Edit Ticket', 'yith-event-tickets-for-woocommerce' ),
				//@since 1.1.3
				'search_items'  => __( 'Search tickets', 'yith-event-tickets-for-woocommerce' )
				//@since 1.1.3
			);

			$args = array(
				'labels'        => $labels,
				'public'        => false,
				'show_ui'       => true,
				'query_var'     => true,
				'rewrite'       => array( 'slug' => 'order' ),
				'hierarchical'  => false,
				'menu_position' => null,
				'supports'      => array(),
				'menu_icon'     => 'dashicons-tickets-alt',
				'capabilities'  => array(
					'edit_post'          => 'edit_ticket',
					'edit_posts'         => 'edit_tickets',
					'edit_others_posts'  => 'edit_other_tickets',
					'publish_posts'      => 'publish_tickets',
					'read_post'          => 'read_ticket',
					'read_private_posts' => 'read_private_tickets',
					'delete_post'        => 'delete_ticket'
				),
				'map_meta_cap'  => true
			);

			register_post_type( 'ticket', $args );

            global $wp_roles;

            foreach ( $args[ 'capabilities' ] as $capability ){

                if ( apply_filters( 'yith_wc_event_tickets_administrator_capabilities', true, $capability ) )
                    $wp_roles->add_cap( 'administrator', $capability );

                if ( apply_filters( 'yith_wc_event_tickets_shop_manager_capabilities', true, $capability ) )
                    $wp_roles->add_cap( 'shop_manager', $capability );
            }

            do_action( 'yith_wc_event_tickets_after_register_post_type_and_capabilities', $args );

		}

		/**
		 * Define new image sizes that Event Tickets will be handle
		 */
		public function add_custom_image_sizes() {

			add_image_size( 'default_header_mail', 420, 203, true );
			add_image_size( 'default_content_mail', 601, 340, true );
			add_image_size( 'default_footer_mail', 66, 43, true );
		}

		/**
		 * Define pdf item to attach on emails
		 *
		 * @param $attachments
		 * @param $email_id
		 * @param $object
		 *
		 * @return array
		 * @since 1.0.0
		 */
		public function attach_items_pdf_mail( $attachments, $email_id, $object ) {
			$allowed_emails = apply_filters( 'yith_wcevti_allowed_emails', array( 'customer_processing_order', 'customer_completed_order' ) );
			$attach_items   = apply_filters( 'yith_wcevti_attach_items', true, $object );



			if ( $attach_items ) {
				if ( in_array( $email_id, $allowed_emails ) && is_a( $object, 'WC_Order' ) ) {
					$order_items = $object->get_items();

					foreach ( $order_items as $key => $item ) {
						if ( 'ticket-event' == $item['product_type'] ) {

							$event_id = wc_get_order_item_meta( $key, '_event_id' );

							$file_path = yith_wcevti_get_pdf_path( $event_id );

							if ( ! @fopen( $file_path, 'r' ) ) {
								@yith_wcevti_create_pdf( $event_id );
								$file_path = yith_wcevti_get_pdf_path( $event_id );
							}

							$pdf_path = $file_path;

							$attachments[] = $pdf_path;
						}
					}
				}
			}

			return $attachments;
		}


		public function update_postmeta_pdf_security_tickets() {
			if ( ! get_option( 'yith_wcevti_barcode_pdf_security', false ) ) {

				$htaccess_file = fopen( YITH_WCEVTI_DOCUMENT_SAVE_PDF_DIR . '.htaccess', 'w' );
				$rule          = "Options -Indexes \n";
				fwrite( $htaccess_file, $rule );
				fclose( $htaccess_file );

				update_option( 'yith_wcevti_barcode_pdf_security', true );
			}
		}

		public function update_postmeta_fields() {
			if ( ! get_option( 'yith_wcevti_postmeta_fields', false ) ) {
				global $wpdb;

				$posts_to_update = $wpdb->get_col( $wpdb->prepare( 'select id from ' . $wpdb->posts . ' where id not in ( select post_id from ' . $wpdb->postmeta . ' where meta_key like %s ) and post_type = %s', array(
					'_field_%',
					'ticket'
				) ) );

				foreach ( $posts_to_update as $id ) {
					$fields = yith_wcevti_get_fields( $id );
					foreach ( $fields as $field_item ) {
						foreach ( $field_item as $key => $item ) {
							update_post_meta( $id, '_field_' . $key, $item );
						}

					}
				}

				update_option( 'yith_wcevti_postmeta_fields', true );
			}
		}

		public function update_postmeta_barcode_tickets() {
			if ( ! get_option( 'yith_wcevti_barcode_tickets', false ) ) {
				global $wpdb;

				$posts_to_update = $wpdb->get_col( $wpdb->prepare( 'select id from ' . $wpdb->posts . ' where id not in ( select post_id from ' . $wpdb->postmeta . ' where meta_key like %s ) and post_type = %s', array(
					'_barcode_html',
					'ticket'
				) ) );
				foreach ( $posts_to_update as $id ) {
					// Create barcodes
					$product_id    = get_post_meta( $id, 'wc_event_id', true );
					$mail_template = get_post_meta( $product_id, '_mail_template', true );
					$barcode       = ! empty( $mail_template['data']['barcode'] ) & 'on' == $mail_template['data']['barcode']['display'] ? $mail_template['data']['barcode'] : false;

					yith_wcevti_generate_barcode( $id, $barcode );
				}

				update_option( 'yith_wcevti_barcode_tickets', true );
			}
		}

		/**
		 * Once ordered is processing we create tickets post with all information.
		 *
		 * @param $order_id
		 */
		public function add_order_ticket( $order_id ) {
			yith_wcevti_add_order_ticket( $order_id );
		}
	}
}