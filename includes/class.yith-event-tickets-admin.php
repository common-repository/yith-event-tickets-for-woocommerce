<?php
/*
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */
if ( ! defined( 'YITH_WCEVTI_PATH' ) ) {
	exit( 'Direct access forbidden.' );
}

/**
 *
 *
 * @class      YITH_Tickets_Admin
 * @package    Yithemes
 * @since      Version 1.0.0
 * @author     Francsico Mateo
 *
 */

if ( ! class_exists( 'YITH_Tickets_Admin' ) ) {
	/**
	 * Class YITH_Tickets_Admin
	 *
	 * @author Francsico Mateo
	 */
	class YITH_Tickets_Admin {

		/**
		 * @var Panel page
		 */
		protected $_panel_page = 'yith_wcevti_panel';

		/**
		 * @var bool Show the premium landing page
		 */
		public $show_premium_landing = true;

		/**
		 * @var doc_url
		 */
		protected $doc_url = 'https://docs.yithemes.com/yith-event-tickets-for-woocommerce';

		/**
		 * @var string Official plugin documentation
		 */
		protected $_official_documentation = 'https://docs.yithemes.com/yith-event-tickets-for-woocommerce';

		/**
		 * @var $_premium string Premium tab template file name
		 */
		protected $_premium = 'premium.php';

		/**
		 * @var string Premium version landing link
		 */
		protected $_premium_landing = 'https://yithemes.com/themes/plugins/yith-woocommerce-event-tickets';

		/**
		 * Construct
		 *
		 * @author Francsico Mateo
		 * @since  1.0
		 */
		public function __construct() {

			/* === Action links and meta === */
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_WCEVTI_PATH . 'init.php' ), array(
				$this,
				'action_links'
			) );

			/* === My custom general fields === */
			add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_date_fields' ), 10, 3 );
			add_action( 'woocommerce_product_options_general_product_data', array(
				$this,
				'add_custom_options'
			), 10, 3 );

			/* === Save my custom fields === */
			if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
				add_action( 'woocommerce_process_product_meta_ticket-event', array(
					$this,
					'save_custom_fields'
				), 10, 3 );
			} else {
				add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_custom_fields' ), 10, 3 );
			}
			/* === Add my custom scripts === */
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			/* === Register ajax actions for admin === */
			add_action( 'wp_ajax_print_field_row_action', array( $this, 'print_field_row_action' ) );

			add_action( 'wp_ajax_print_mail_template_action', array( $this, 'print_mail_template_action' ) );
			add_action( 'wp_ajax_nopriv_print_mail_template_action', array( $this, 'print_mail_template_action' ) );

			/* === Redo product data tabs === */
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'build_product_data_tabs' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'build_product_data_content' ) );

			//Meta-boxes
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10 );

			//Tickets table
			add_filter( 'manage_ticket_posts_columns', array( $this, 'add_columns_ticket' ), 10, 1 );
			add_filter( 'manage_edit-ticket_sortable_columns', array( $this, 'set_ticket_sortable_columns' ) );
			add_action( 'manage_ticket_posts_custom_column', array( $this, 'render_ticket_custom_columns' ), 10, 2 );

			add_filter( 'list_table_primary_column', array( $this, 'list_table_primary_column' ), 10, 2 );
			add_filter( 'post_row_actions', array( $this, 'row_actions' ), 100, 2 );

			add_action( 'pre_get_posts', array( $this, 'pre_get_ticket' ) );
			add_filter( 'posts_clauses', array( $this, 'manage_ticket_clauses' ), 10, 2 );

			//Register panel
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
			add_action( 'yith_wcevti_premium', array( $this, 'premium_tab' ) );

			/* === Orders Table === */
			add_action( 'woocommerce_order_edit_status', array( $this, 'when_status_changed' ), 10, 2 );

            /* === Show Plugin Information === */
            add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );



        }

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Francsico Mateo
		 * @use      /Yit_Plugin_Panel class
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->_panel ) ) {
				return;
			}

			$menu_title = _x( 'Event Tickets', 'shortened plugin name', 'yith-event-tickets-for-woocommerce' ); //@since 1.1.3
			$admin_tabs = array(
				'settings' => __( 'Settings', 'yith-event-tickets-for-woocommerce' ),
			);
			if ( $this->show_premium_landing ) {
				$admin_tabs['premium'] = __( 'Premium Version', 'yith-event-tickets-for-woocommerce' );
			}


			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => $menu_title,
				'menu_title'       => $menu_title,
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yit_plugin_panel',
				'page'             => $this->_panel_page,
				'admin-tabs'       => apply_filters( 'yith-wcevti-admin-tabs', $admin_tabs ),
				'options-path'     => YITH_WCEVTI_PATH . '/plugin-options'
			);

			/* === Fixed: not updated theme  === */
			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once( YITH_WACP_DIR . '/plugin-fw/lib/yit-plugin-panel-wc.php' );
			}

			$this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * Premium Tab Template
		 *
		 * Load the premium tab template on admin page
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Francsico Mateo
		 * @return void
		 */
		public function premium_tab() {
			$premium_tab_template = YITH_WCEVTI_PATH . 'templates/admin/' . $this->_premium;
			if ( file_exists( $premium_tab_template ) && $this->show_premium_landing ) {
				include_once( $premium_tab_template );
			}
		}

		/**
		 * Get the premium landing uri
		 *
		 * @since   1.0.0
		 * @author  @author Francsico Mateo
		 * @return  string The premium landing link
		 */
		public function get_premium_landing_uri() {
			return defined( 'YITH_REFER_ID' ) ? $this->_premium_landing . '?refer_id=' . YITH_REFER_ID : $this->_premium_landing . '?refer_id=1030585';
		}

		/**
		 * Sidebar links
		 *
		 * @return   array The links
		 * @since    1.2.1
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function get_sidebar_link() {
			$links = array(
				array(
					'title' => __( 'Plugin documentation', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.3
					'url'   => $this->_official_documentation,
				),
				array(
					'title' => __( 'Help Center', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.3
					'url'   => 'https://support.yithemes.com/hc/en-us/categories/202568518-Plugins',
				),
			);

			return $links;
		}

		/**
		 * Custom date start and end fields
		 *
		 * Add custom dates field on our Event Ticket product
		 *
		 * @author Francsico Mateo
		 * @since  1.0
		 * @return void
		 */
		public function add_date_fields() {
			global $thepostid;
			$args = array(
				'thepostid' => $thepostid
			);

			$print_date_fields = apply_filters( 'yith_wcevti_print_admin_date_fields', true );

			if ( $print_date_fields ) {
				wc_get_template( 'admin/date_fields.php', $args, '', YITH_WCEVTI_TEMPLATE_PATH );
			}
		}

		/**
		 * Custom date start and end fields
		 *
		 * Add custom dates field on our Event Ticket product
		 *
		 * @author Francsico Mateo
		 * @since  1.0
		 * @return void
		 */
		public function add_custom_options() {
			global $thepostid;
			$args = array(
				'disable_cookie_form' => ( 'on' == get_post_meta( $thepostid, '_disable_cookie_form', true ) ) ? true : false
			);
			$print_custom_options = apply_filters( 'yith_wcevti_print_admin_custom_options', true );
			if ( $print_custom_options ) {
				wc_get_template( 'admin/customs_options.php', $args, '', YITH_WCEVTI_TEMPLATE_PATH );
			}
		}

		/**
		 * Save Custom fields
		 *
		 * Save custom field variations on our Event Ticket product
		 *
		 * @author Francsico Mateo
		 * @since  1.0
		 *
		 * @param $post_id
		 */
		public function save_custom_fields( $post_id ) {

			$product = wc_get_product( $post_id );
			//$changes = array();
			//*** Save Start and End Event Data ***
			$start_date_picker_field = $_POST['_start_date_picker_field'];

			//$changes['_start_date_picker'] = esc_attr( $start_date_picker_field );

			yit_save_prop( $product, '_start_date_picker', esc_attr( $start_date_picker_field ) );


			$start_time_picker_field = ! empty( $_POST['_start_time_picker_field'] ) ? ( strlen( $_POST['_start_time_picker_field'] ) <= 4 ) ? '0' . $_POST['_start_time_picker_field'] : $_POST['_start_time_picker_field'] : '';
			//$changes['_start_time_picker'] = esc_attr( $start_time_picker_field );

			yit_save_prop( $product, '_start_time_picker', esc_attr( $start_time_picker_field ) );

			$end_date_picker_field = $_POST['_end_date_picker_field'];
			//$changes['_end_date_picker'] = esc_attr( $end_date_picker_field );

			yit_save_prop( $product, '_end_date_picker', esc_attr( $end_date_picker_field ) );


			$end_time_picker_field = ! empty( $_POST['_end_time_picker_field'] ) ? ( strlen( $_POST['_end_time_picker_field'] ) <= 4 ) ? '0' . $_POST['_end_time_picker_field'] : $_POST['_end_time_picker_field'] : '';
			//$changes['_end_time_picker'] = esc_attr( $end_time_picker_field );

			yit_save_prop( $product, '_end_time_picker', esc_attr( $end_time_picker_field ) );

			$disable_cookie_form = isset( $_POST['_disable_cookie_form'] ) ? $_POST['_disable_cookie_form'] : '';

			yit_save_prop( $product, '_disable_cookie_form', $disable_cookie_form );

			//*** Save fields ***
			if ( isset( $_POST['_fields'] ) && ! empty( $_POST['_fields'] ) ) {
				$fields_post = $_POST['_fields'];
				$fields      = array();

				foreach ( $fields_post as $field_item ) {
					if ( ! empty( $field_item['_label'] ) ) {
						$fields[] = $field_item;
					}
				}

				//$changes ['_fields'] = $fields;
				yit_save_prop( $product, '_fields', $fields );

			} else {
				//$changes ['_fields'] = '';
				yit_save_prop( $product, '_fields', '' );
			}

			//*** Save options for mail templates... ***
			if ( isset( $_POST['_template_type'] ) ) {
				$template_type = $_POST['_template_type'];

				switch ( $template_type ) {
					case 'default':
						$header_image          = '';
						$display_ticket_number = ( isset( $_POST['_display_ticket_number'] ) ) ? $_POST['_display_ticket_number'] : 'off';
						$background_image      = '';
						$footer_image          = '';
						$aditional_text        = '';
						if ( isset( $_POST['_header_image'] ) ) {
							$header_image = array(
								'id'  => $_POST['_header_image']['id'],
								'uri' => $_POST['_header_image']['uri']
							);
						}
						if ( isset( $_POST['_background_image'] ) ) {
							$background_image = array(
								'id'  => $_POST['_background_image']['id'],
								'uri' => $_POST['_background_image']['uri']
							);
						}
						if ( isset( $_POST['_footer_image'] ) ) {
							$footer_image = array(
								'id'  => $_POST['_footer_image']['id'],
								'uri' => $_POST['_footer_image']['uri']
							);
						}

						if ( isset( $_POST['_aditional_text'] ) ) {
							$aditional_text = $_POST['_aditional_text'];
						}

						$barcode_display = isset( $_POST['_barcode']['display'] ) ? $_POST['_barcode']['display'] : '';
						$barcode_type    = isset( $_POST['_barcode']['type'] ) ? $_POST['_barcode']['type'] : 'ticket';

						$barcode = array(
							'display' => $barcode_display,
							'type'    => $barcode_type
						);

						$data = array(
							'header_image'          => $header_image,
							'display_ticket_number' => $display_ticket_number,
							'barcode'               => $barcode,
							'background_image'      => $background_image,
							'footer_image'          => $footer_image,
							'aditional_text'        => $aditional_text
						);

						$mail_template = array(
							'type' => $template_type,
							'data' => $data
						);

						//$changes['_mail_template'] = $mail_template;

						yit_save_prop( $product, '_mail_template', $mail_template );

						break;
				}
			}
			//$changes = apply_filters('yith_wcevti_save_custom_fields', $changes, $product);
			//yit_save_prop($product, $changes);
			do_action( 'yith_wcevti_save_custom_fields', $post_id );

		}

		/**
		 * Enqueue Scripts
		 *
		 * Register and enqueue scripts for Admin
		 *
		 * @author Francsico Mateo
		 * @since  1.0
		 * @return void
		 */
		public function enqueue_scripts() {
			global $pagenow;

			$path   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '/unminified' : '';
			$prefix = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';

			//Common style for admin...
			wp_register_style( 'yith-wc-style-admin-common', YITH_WCEVTI_ASSETS_URL . 'css/style-admin-common.css', null, YITH_WCEVTI_VERSION );
			wp_enqueue_style( 'yith-wc-style-admin-common' );

			if ( $pagenow == 'post.php' && get_post_type( $_GET['post'] ) == 'product' || $pagenow == 'post-new.php' && $_GET['post_type'] == 'product' ) {
				$enable_location = get_option( 'yith_wcte_enable_location' );
				$api_key         = get_option( 'yith_wcte_api_key_gmaps' );

				//My Style
				wp_register_style( 'yith-wc-style-admin-tickets', YITH_WCEVTI_ASSETS_URL . 'css/style-admin.css', null, YITH_WCEVTI_VERSION );
				wp_enqueue_style( 'yith-wc-style-admin-tickets' );


				// My Scripts
				wp_register_script( 'yith-wc-script-admin-tickets', YITH_WCEVTI_ASSETS_URL . '/js' . $path . '/script-tickets-admin' . $prefix . '.js', array(
					'jquery',
					'jquery-ui-datepicker',
					'jquery-ui-sortable'
				), YITH_WCEVTI_VERSION, true );

				$data_to_js = array(
					'message' => array(
						'remove_service' => __( 'Remove this Service?', 'yith-event-tickets-for-woocommerce' )
						//@since 1.1.3
					),
					'remove' => array(
						'taxes_option' => apply_filters( 'yith_wcevti_remove_tax_options', false )
					),
                    'date_format' => apply_filters('yith_wcevti_date_format','yy-mm-dd'),
				);

				wp_localize_script( 'yith-wc-script-admin-tickets', 'yith_wcevti_admin_tickets', $data_to_js );

				if ( $enable_location == 'yes' && ! empty( $api_key ) ) {
					wp_register_script( 'yith-wc-script-gmaps', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places', array(), YITH_WCEVTI_VERSION, true );
					wp_enqueue_script( 'yith-wc-script-gmaps' );
				}
				wp_enqueue_script( 'yith-wc-script-admin-tickets' );
			}

			if ( $pagenow == 'post.php' && get_post_type( $_GET['post'] ) == 'ticket' ) {

				//My Style
				wp_register_style( 'yith-wc-style-admin-tickets-table', YITH_WCEVTI_ASSETS_URL . 'css/style-admin-tickets-table.css', null, YITH_WCEVTI_VERSION );
				wp_enqueue_style( 'yith-wc-style-admin-tickets-table' );

				//My Scripts
				do_action( 'yith_wcevti_enqueue_script_post_ticket', $path, $prefix );
			}

			$pos_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
			if ( $pagenow == 'edit.php' && $pos_type == 'ticket' ) {
				//My Style
				wp_register_style( 'yith-wc-style-admin-tickets-table', YITH_WCEVTI_ASSETS_URL . 'css/style-admin-tickets-table.css', null, YITH_WCEVTI_VERSION );
				wp_enqueue_style( 'yith-wc-style-admin-tickets-table' );

				wp_register_style( 'yith-wc-style-admin-fontawesome-tickets', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', null, YITH_WCEVTI_VERSION );
				wp_enqueue_style( 'yith-wc-style-admin-fontawesome-tickets' );

				//My Scripts
				do_action( 'yith_wcevti_enqueue_script_post-edit_ticket', $path, $prefix );
			}
		}

		/**
		 * action_links function.
		 *
		 * @access public
		 *
		 * @param mixed $links
		 *
		 * @return array
		 */
        public function action_links( $links ) {
            $links = yith_add_action_links( $links, $this->_panel_page, false );
            return $links;
        }

		public function print_field_row_action() {
			if ( isset( $_POST['index'] ) ) {
				$args = array(
					'index' => $_POST['index']
				);
				yith_wcevti_get_template( 'fields_row', $args, 'admin', YITH_WCEVTI_TEMPLATE_PATH );
			}
			die();
		}

		public function print_mail_template_action() {
			if ( isset( $_GET['id'] ) ) {
				$post = get_post( $_GET['id'] );
				yith_wecvti_print_mail_template_preview( $post );
			}
			die();
		}

		public function build_product_data_tabs( $tabs ) {

			array_push( $tabs['inventory']['class'], 'show_if_ticket-event' );


			$new_fields_tab = array(
				'event_fields' => array(
					'label'  => __( 'Fields', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.3
					'target' => 'fields_product_data',
					'class'  => array( 'show_if_ticket-event' )
				)
			);

			$mail_template_tab = array(
				'mail_template' => array(
					'label'  => __( 'Email template', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.3
					'target' => 'mail_template_product_data',
					'class'  => array( 'show_if_ticket-event' )
				)
			);

			return array_merge( $tabs, $new_fields_tab, $mail_template_tab );

		}

		public function build_product_data_content() {
			global $thepostid;
			$args = array(
				'thepostid' => $thepostid
			);
			yith_wcevti_get_template( 'product_data_content', $args, 'admin', YITH_WCEVTI_TEMPLATE_PATH );
		}

		public function add_columns_ticket( $columns ) {
			$columns = array(
				'cb'               => '<input type="checkbox" />',
				'ticket'           => __( 'Ticket', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.3
				'order'            => __( 'Order', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.3
				'purchased_status' => __( 'Purchase status', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.3
				'start_end'        => __( 'Start/End event', 'yith-event-tickets-for-woocommerce' ) //@since 1.1.3
			);

			$columns = apply_filters( 'yith_wcevti_end_columns_tickets', $columns );

			return $columns;
		}

		public function set_ticket_sortable_columns( $columns ) {
			$custom = array(
				'ticket'    => array( 'ID', false ),
				'order'     => 'order',
				'start_end' => 'start_end'
			);
			unset( $columns['comments'] );
			unset( $columns['date'] );

			return wp_parse_args( $custom, $columns );
		}

		public function render_ticket_custom_columns( $column, $post_id ) {
			switch ( $column ) {
				case 'ticket':
					$post = get_post( $post_id );
					?>
                    <a class="row-title" href="<?php echo get_edit_post_link( $post_id ); ?>"><strong><?php echo '#' . $post_id . ', ' . $post->post_title; ?></strong></a>
					<?php
					break;
				case 'order':
					$order_id = get_post_meta( $post_id, 'wc_order_id', true );
					$order = wc_get_order( $order_id );
					if ( is_a( $order, 'WC_Order' ) ) {
						$user_purchased = yith_wcevti_get_user_from_order( $order );
						?>
                        <a href="<?php echo get_edit_post_link( $order_id ); ?>"><?php echo '#' . $order_id; ?></a> <?php echo __( 'by', 'yith-event-tickets-for-woocommerce' ); ?>
                        <a href="<?php if ( ! empty( $user_purchased['user_id'] ) ) {
							echo get_edit_user_link( $user_purchased['user_id'] );
						} ?>"><?php echo $user_purchased['display_name']; ?></a>
                        <small class="meta email">
                            <a href="mailto:<?php echo $user_purchased['user_email']; ?>"><?php echo $user_purchased['user_email']; ?></a>
							<?php if ( empty( $user_purchased['user_id'] ) ) {
								echo __( 'Unregistered user', 'yith-event-tickets-for-woocommerce' );
							} //@since 1.1.3
							?>
                        </small>
						<?php
					} else {
						echo __( 'The order linked to this event has been deleted...', 'yith-event-tickets-for-woocommerce' ); //@since 1.1.3
					}
					break;
				case 'purchased_status':
					$order_id = get_post_meta( $post_id, 'wc_order_id', true );
					$order    = wc_get_order( $order_id );
					if ( is_a( $order, 'WC_Order' ) ) {
						$date_format    = get_option( 'date_format' );
						$order_date     = yit_get_prop( $order, 'order_date' );
						$purchased_date = date_i18n( $date_format, strtotime( $order_date ) );
						echo __( 'Purchased on ', 'yith-event-tickets-for-woocommerce' ) . ' <b>' . $purchased_date . '</b>'; //@since 1.1.3
					}
					break;
				case 'start_end':
					$product_id     = get_post_meta( $post_id, 'wc_event_id', true );
					$product_ticket = wc_get_product( $product_id );

					$date_format = get_option( 'date_format' );
					$start_date  = yit_get_prop( $product_ticket, '_start_date_picker', true );
					$end_date    = yit_get_prop( $product_ticket, '_end_date_picker', true );

					$start_date = strtotime( $start_date );
					$end_date   = strtotime( $end_date );

					$start_date = date_i18n( $date_format, $start_date );
					$end_date   = date_i18n( $date_format, $end_date );

					if ( ! $start_date | ! $end_date ) {
						echo __( 'No start or end date has been defined for this event', 'yith-event-tickets-for-woocommerce' ); //@since 1.1.3
					} else {
						echo $start_date . ' <b>' . __( 'to', 'yith-event-tickets-for-woocommerce' ) . '</b> ' . $end_date; //@since 1.1.3
					}

					break;
			}

			do_action( 'yith_wcevti_render_ticket_columns', $column, $post_id );
		}

		public function list_table_primary_column( $default, $screen_id ) {
			if ( 'edit-ticket' === $screen_id ) {
				return 'ticket';
			}

			return $default;
		}

		public function row_actions( $actions, $post ) {
			if ( 'ticket' == $post->post_type ) {
				$new_actions = array(
					'view' => '<a href="' . get_edit_post_link( $post->ID ) . '">' . __( 'View', 'yith-event-tickets-for-woocommerce' ) . '</a>'
					//@since 1.1.3
				);

				if ( isset( $actions['trash'] ) ) {
					$new_actions['trash'] = $actions['trash'];
				}
				if ( isset( $actions['untrash'] ) ) {
					$new_actions['untrash'] = $actions['untrash'];
					$new_actions['delete']  = $actions['delete'];
				}

				return $new_actions;
			}

			$actions = apply_filters( 'yith_wcevti_end_ticket_row_actions', $actions, $post );

			return $actions;
		}

		public function pre_get_ticket( $query ) {

			if ( isset( $_GET['post_type'] ) ) {
				if ( 'ticket' == $_GET['post_type'] ) {

					if ( isset( $_GET['s'] ) ) {
						if ( ! empty( $_GET['s'] ) ) {
							add_filter( 'posts_join', array( $this, 'search_ticket_join_for' ) );
							add_filter( 'posts_where', array( $this, 'search_ticket_where_for' ) );
							add_filter( 'posts_search', array( $this, 'search_ticket_item_for' ) );
						}
					}
					$orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : '';
					$order   = isset ( $_GET['order'] ) ? $_GET['order'] : '';

					$orderby = ! empty( $orderby ) ? $orderby : 'ID';
					$order   = ! empty( $order ) ? $order : 'desc';
					$query->set( 'orderby', $orderby );
					$query->set( 'order', $order );
				}
			}
		}

		public function search_ticket_join_for( $join ) {
			global $wpdb;

			$join .= 'left join ' . $wpdb->postmeta . ' as postmeta_tickets_0 on ' . $wpdb->posts . '.ID = postmeta_tickets_0.post_id ';
			$join .= 'left join ' . $wpdb->postmeta . ' as postmeta_tickets_1 on ' . $wpdb->posts . '.ID = postmeta_tickets_1.post_id ';

			$join = apply_filters( 'yith_wcevti_join_for', $join );
			return $join;
		}

		public function search_ticket_where_for( $where ) {
			global $wpdb;

            $where .= $wpdb->prepare( ' and postmeta_tickets_0.meta_key = %s and postmeta_tickets_1.meta_key = %s ', "wc_event_id", "wc_order_id" );

			$where = apply_filters( 'yith_wcevti_where_for', $where );

			return $where;
		}

		public function search_ticket_item_for( $search ) {
			global $wpdb;
			$text = $_GET['s'];
			$query = "%{$text}%";

			// retrieve products that whose location match search
            $matching_products = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID
                FROM {$wpdb->posts} AS p
                LEFT JOIN {$wpdb->postmeta} AS pm on p.ID = pm.post_id
                WHERE p.post_type = %s AND pm.meta_key = %s and pm.meta_value LIKE %s",
                'product',
                '_direction_event',
                $query
            ) );

			$query = $wpdb->prepare( ') or (postmeta_tickets_0.post_id like %s) or (postmeta_tickets_1.meta_value like %s) ', $query, $query );

			if( ! empty( $matching_products ) ){
			    $query .= ' or (postmeta_tickets_0.meta_value IN (' . implode( ',', $matching_products ) . '))';
            }

			$query = apply_filters( 'yith_wcevti_search_ticket_for', $query, $text );

			$search = str_replace( '))', $query . ')', $search );

			return $search;
		}

		public function manage_ticket_clauses( $pieces, $query ) {
			global $wpdb;

			if ( $query->is_main_query() && ( $orderby = $query->get( 'orderby' ) ) ) {
				$order = strtoupper( $query->get( 'order' ) );

				if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
					$order = 'ASC';
				}

				switch ( $orderby ) {

					case 'start_end':

						$pieces ['fields'] .= ', DATE_FORMAT (postmeta_product_0.meta_value, "%d-%m-%Y")';

						$pieces['join']  .= ' left join ' . $wpdb->postmeta . ' as postmeta_tickets on ' . $wpdb->posts . '.id = postmeta_tickets.post_id' .
						                    ' left join ' . $wpdb->postmeta . ' as postmeta_product_0 on postmeta_tickets.meta_value = postmeta_product_0.post_id';
						$pieces['where'] .= ' and postmeta_product_0.meta_key like "_start_date_picker"';

						$pieces['orderby'] = ' postmeta_product_0.meta_value ' . $order;

						break;
				}

			}

			return $pieces;
		}

		public function add_meta_boxes() {
			global $post;
			$title = sprintf( __( 'Event #%d %s details', 'yith-event-tickets-for-woocommerce' ), $post->ID, $post->post_title ); //@since 1.1.3

			add_meta_box( 'ticket-order', $title, array( $this, 'print_ticket_order_metabox' ), 'ticket' );

			add_meta_box( 'ticket-template', __( 'Ticket template', 'yith-event-tickets-for-woocommerce' ), array(
				$this,
				'print_ticket_template_metabox'
			), 'ticket' ); //@since 1.1.3
		}

		public function print_ticket_order_metabox( $post ) {

			$post_meta = get_post_meta( $post->ID, '', true );
			$fields    = array();

			foreach ( $post_meta as $key => $meta ) {
				if ( preg_match( '/field_/i', $key ) ) {
					$label = str_replace( array( '_field_' ), '', $key );
					$value = $meta[0];

					$fields[] = array(
						$label => $value
					);

				}
			}
			$args = array(
				'wc_event_id' => $post_meta['wc_event_id'][0],
				'post'        => $post,
				'fields'      => $fields,
			);

			yith_wcevti_get_template( 'ticket_order_meta_box', $args, 'admin' );
		}

		public function print_ticket_template_metabox( $post ) {

			?>
            <iframe class="iframe_template" src="<?php echo esc_url( add_query_arg( array(
				'action' => 'print_mail_template_action',
				'id'     => $post->ID
			), admin_url( 'admin-ajax.php' ) ) ) ?>">

            </iframe>
			<?php
		}

		public function when_status_changed( $id, $new_status ) {
			if ( 'completed' == $new_status | 'processing' == $new_status ) {
				yith_wcevti_add_order_ticket( $id );
			}
		}




        public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_WCEVTI_INIT' ) {
            if ( defined( $init_file ) && constant( $init_file ) == $plugin_file ) {
                $new_row_meta_args['slug'] = YITH_WCEVTI_SLUG;
            }

            return $new_row_meta_args;
        }


    }
}