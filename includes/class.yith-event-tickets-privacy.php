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

if ( ! class_exists( 'YITH_Tickets_Privacy' ) ) {
	/**
	 * Class YITH_Tickets_Privacy
	 */
	class YITH_Tickets_Privacy extends YITH_Privacy_Plugin_Abstract {
		/**
		 * Constructor method
		 *
		 * @return \YITH_Tickets_Privacy
		 */
		public function __construct() {
			parent::__construct( _x( 'YITH Event Tickets for WooCommerce', 'Privacy Policy Content', 'yith-event-tickets-for-woocommerce' ) );

			// set up tickets data exporter
			add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );

			// set up tickets data eraser
			add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
		}

		/**
		 * Retrieves privacy example text for wishlist plugin
		 *
		 * @return string Privacy message
		 * @since 2.2.2
		 */
		public function get_privacy_message( $section ) {
			$content = '';

			switch( $section ){
				case 'collect_and_store':
					$content =  '<p>' . __( 'When you purchase a ticket, we’ll track:', 'yith-event-ticket-for-woocommerce' ) . '</p>' .
					            '<ul>' .
					            '<li>' . __( 'Personal data of the owner: these data will be used by the event organizer to create a list of attendees.', 'yith-event-ticket-for-woocommerce' ) . '</li>' .
					            '<li>' . __( 'Service purchased: we’ll keep the selected service options set for you ticket, in order to help the staff organize the event.', 'yith-event-ticket-for-woocommerce' ) . '</li>' .
					            '</ul>' .
					            '<p>' . __( 'We’ll also use cookies to store preferences you set for your ticket and prevent you from entering them more than once during the same session.', 'yith-event-ticket-for-woocommerce' ) . '</p>';
					break;
				case 'has_access':
					$content =  '<p>' . __( 'Members of our team have access to the information you provide us. For example, both Administrators and Shop Managers can access:', 'yith-event-ticket-for-woocommerce' ) . '</p>' .
					            '<ul>' .
					            '<li>' . __( 'Ticket information, such as liked personal data or services', 'yith-event-ticket-for-woocommerce' ) . '</li>' .
					            '</ul>' .
					            '<p>' . __( 'Our team members have access to this information to manage the event and prepare the list of attendees.', 'yith-event-ticket-for-woocommerce' ) . '</p>';
					break;
				case 'share':
				case 'payments':
				default:
					break;
			}

			return apply_filters( 'yith_wcevti_privacy_policy_content', $content, $section );
		}

		/**
		 * Register exporters for event tickets plugin
		 *
		 * @param $exporters array Array of currently registered exporters
		 * @return array Array of filtered exporters
		 * @since 1.2.5
		 */
		public function register_exporter( $exporters ) {

			// exports data about tickets
			$exporters['yith_wcevti_tickets'] = array(
				'exporter_friendly_name' => __( 'Tickets Data', 'yith-event-tickets-for-woocommerce' ),
				'callback'               => array( $this, 'tickets_exporter' )
			);

			return $exporters;
		}

		/**
		 * Register eraser for event tickets plugin
		 *
		 * @param $erasers array Array of currently registered erasers
		 * @return array Array of filtered erasers
		 * @since 1.2.5
		 */
		public function register_eraser( $erasers ) {

			// erases data about tickets
			if( apply_filters( 'yith_wcevti_privacy_erase_tickets', false ) ) {
				$erasers['yith_wcevti_tickets'] = array(
					'eraser_friendly_name' => __( 'Tickets Data', 'yith-event-tickets-for-woocommerce' ),
					'callback'             => array( $this, 'tickets_eraser' )
				);
			}

			return $erasers;
		}

		/**
		 * Export user's tickets
		 *
		 * @param $email_address string Email of the users that requested export
		 * @param $page int Current page processed
		 * @return array Array of data to export
		 * @since 1.2.5
		 */
		public function tickets_exporter( $email_address, $page ) {
			$page           = (int) $page;
			$data_to_export = array();

			$tickets = $this->get_tickets_by_email_address( $email_address, $page );

			if ( 0 < count( $tickets ) ) {
				foreach ( $tickets as $ticket_id ) {
					$data_to_export[] = array(
						'group_id'    => 'yith_wcevti_tickets',
						'group_label' => __( 'Event Tickets', 'yith-event-tickets-for-woocommerce' ),
						'item_id'     => 'ticket-' . $ticket_id,
						'data'        => $this->get_ticket_data( $ticket_id ),
					);
				}
				$done = 10 > count( $tickets );
			} else {
				$done = true;
			}

			return array(
				'data' => $data_to_export,
				'done' => $done,
			);
		}

		/**
		 * Erase customer's tickets
		 *
		 * @param $email_address string Email of the users that requested export
		 * @param $page int Current page processed
		 * @return array Result of the operation
		 */
		public function tickets_eraser( $email_address, $page ) {
			$response        = array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);

			$tickets = $this->get_tickets_by_email_address( $email_address, $page );

			if ( 0 < count( $tickets ) ) {
				foreach ( $tickets as $ticket_id ) {
					if ( apply_filters( 'yith_wcevti_privacy_erase_ticket', true, $ticket_id ) ) {
						do_action( 'yith_wcevti_privacy_before_remove_ticket', $ticket_id );

						// retrieve fields entered by customer, and anonymize them
						$post = get_post( $ticket_id );
						$order_item_id = get_post_meta( $post->ID, 'wc_order_item_id', true );
						$args = yith_wcevti_set_args_mail_template( $post );

						if( isset( $args['fields'] ) && is_array( $args['fields'] ) ){
							foreach( $args['fields'] as $field ){
								$label = key( $field );
								$field = $field[ $label ];

								if ( function_exists( 'wp_privacy_anonymize_data' ) ) {
									$anon_value = wp_privacy_anonymize_data( 'text', $field );
								} else {
									$anon_value = '0';
								}

								$meta_key = '_field_' . $label;

								update_post_meta( $post->ID, $meta_key, $anon_value );
								wc_update_order_item_meta( $order_item_id, $meta_key, $anon_value );
							}
						}

						do_action( 'yith_wcevtu_privacy_remove_ticket', $ticket_id );

						/* Translators: %s Order number. */
						$response['messages'][]    = sprintf( __( 'Anonymized ticket %s.', 'yith-event-tickets-for-woocommerce' ), $ticket_id );
						$response['items_removed'] = true;
					} else {
						/* Translators: %s Order number. */
						$response['messages'][]     = sprintf( __( 'Ticket %s has been retained.', 'yith-event-tickets-for-woocommerce' ), $ticket_id );
						$response['items_retained'] = true;
					}
				}
				$response['done'] = 10 > count( $tickets );
			} else {
				$response['done'] = true;
			}

			return $response;
		}

		/**
		 * Retrieve tickets related to a specific email address
		 * (either customer registered with that email purchased tickets, or email matches order's billing email of a ticket purchase)
		 *
		 * @param $email_address string Email address
		 * @param $page int Page of tickets to retrieve
		 *
		 * @return array Array of matching ticket's IDs
		 */
		protected function get_tickets_by_email_address( $email_address, $page ) {
			global $wpdb;

			$page   = (int) $page;
			$offset = 50 * ( $page -1 );
			$user   = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored persona

			// retrieve tickets by customer email or customer ID
			$query_select = "SELECT DISTINCT p.ID FROM {$wpdb->posts} AS p";
			$query_joins  = "LEFT JOIN {$wpdb->postmeta} AS pm1 ON p.ID = pm1.post_id 
				 LEFT JOIN {$wpdb->postmeta} AS pm2 ON pm1.meta_value = pm2.post_id";
			$query_where = "WHERE p.post_type = %s AND
				 pm1.meta_key = %s AND ( (
				 	pm2.meta_key = %s AND
					pm2.meta_value = %s
				 ) )";
			$query_limit = " ORDER BY ID ASC LIMIT {$offset}, 10";

			$query_args = array(
				'ticket',
				'wc_order_id',
				'_billing_email',
				$email_address
			);

			if ( $user instanceof WP_User ) {
				$query_where = str_replace( ") )", ") OR (
				 	pm2.meta_key = %s AND
				 	pm2.meta_value = %s
				 ) )", $query_where );

				$query_args[] = '_customer_user';
				$query_args[] = $user->ID;
			}

			$query =  $query_select . ' ' . $query_joins . ' ' . $query_where . ' ' . $query_limit;
			$tickets = $wpdb->get_col( $wpdb->prepare( $query, $query_args ) );

			return $tickets;
		}

		/**
		 * Retrieve data to export for each ticket
		 *
		 * @param $ticket_id int Current ticket id
		 * @return array Data to export
		 */
		protected function get_ticket_data( $ticket_id ) {
			$personal_data   = array();
			$post = get_post( $ticket_id );
			$args = yith_wcevti_set_args_mail_template( $post );

			$props_to_export = apply_filters( 'yith_wcaevti_privacy_export_tickets_props', array(
				'ID'            => __( 'Ticket number', 'yith-event-tickets-for-woocommerce' ),
				'purchase_date' => __( 'Purchase date', 'yith-event-tickets-for-woocommerce' ),
				'post_title'    => __( 'Event', 'yith-event-tickets-for-woocommerce' ),
				'location'      => __( 'Location', 'yith-event-tickets-for-woocommerce' ),
				'event_date'    => __( 'Date', 'yith-event-tickets-for-woocommerce' ),
				'fields'        => __( 'Customer details', 'yith-event-tickets-for-woocommerce' ),
				'services'      => __( 'Ticket details', 'yith-event-tickets-for-woocommerce' ),
				'price'         => __( 'Price', 'yith-event-tickets-for-woocommerce ' ),
			), $ticket_id );

			foreach ( $props_to_export as $prop => $name ) {
				$value = '';

				switch ( $prop ) {
					case 'purchase_date':
						$value = date( wc_date_format(), strtotime( $post->post_date ) );
						break;
					case 'location':
						$value = ! empty( $args['location'] ) ? stripslashes( $args['location'] ) : __( 'N/D', 'yith-event-tickets-for-woocommerce ' );
						break;
					case 'event_date':
						$value = $args['date']['message_start'] . ' ' . $args['date']['message_end'];
						break;
					case 'fields':
						$items = array();
						if ( isset( $args['fields'] ) && is_array( $args['fields'] ) ) {
							foreach ( $args['fields'] as $field ) {
								if ( empty( $field ) ) {
									continue;
								}

								$label = key( $field );
								$field = $field[ $label ];

								$items[] = $label . ': ' . $field;
							}
						}

						$value = implode( ', ', $items );
						break;
					case 'services':
						$items = array();
						if ( isset( $args['services'] ) && is_array( $args['services'] ) ) {
							foreach ( $args['services'] as $service_item ) {
								if ( empty( $service_item ) ) {
									continue;
								}

								$label = key( $service_item );
								$field = $service_item[ $label ];

								$items[] = $label . ': ' . $field;
							}
						}

						$value = implode( ', ', $items );
						break;
					case 'price':
						$value = $args['price'];
						break;
					default:
						$value = isset( $post->$prop ) ? $post->$prop : '';
				}

				$value = apply_filters( 'yith_wcevti_privacy_export_tickets_prop', $value, $prop, $ticket_id );

				if ( $value ) {
					$personal_data[] = array(
						'name'  => $name,
						'value' => $value,
					);
				}
			}

			$personal_data = apply_filters( 'yith_wcevti_privacy_export_ticket', $personal_data, $ticket_id );

			return $personal_data;
		}
	}
}