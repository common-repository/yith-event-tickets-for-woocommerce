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
 * @class      YITH_Tickets_Event
 * @package    Yithemes
 * @since      Version 1.0.0
 * @author     Francsico Mateo
 *
 */

if ( ! class_exists( 'WC_Product_Ticket_Event' ) ) {
	/**
	 * Class WC_Product_Ticket_Event
	 * Name class must be called 'WC_Product_Product_Type' where 'Product_Type' its the name our type product.
	 * This is necesary for WC_Product_Factory can handle class and insert magically.
	 *
	 * @author Francsico Mateo
	 */
	class WC_Product_Ticket_Event extends WC_Product_Simple {

		/**
		 * Construct
		 *
		 * @author Francsico Mateo
		 * @since  1.0
		 */
		public function __construct( $product ) {
			/* === Here create my Event product extending WC_Product_Simple === */

			parent::__construct( $product );
			yit_set_prop( $this, 'product_type', $this->get_type() );

			$this->supports[] = 'add_to_cart';

			$item = array_diff( $this->supports, array( 'add_to_cart' ) );
			unset( $this->supports[ key( $item ) ] );

			$price = (double) parent::get_price() + $this->get_stock_overcharge() + $this->get_time_overcharge();
			yit_set_prop( $this, 'price', $price );
		}

		/**
		 * Returns the product's active price.
		 *
		 * @return double price
		 * @overwrite
		 */
		public function get_price( $context = 'view' ) {
			return (double) parent::get_price(); // + $this->get_stock_overcharge() + $this->get_time_overcharge();
		}

		/**
		 * @return increase_stock_value
		 */
		public function get_stock_overcharge() {
			$increase_by_stock = yit_get_prop( $this, '_increase_by_stock', true );
			$increase_value    = 0;

			$manage_stock = yit_get_prop( $this, '_manage_stock', true );
			$stock_enable = gettype( $manage_stock ) == 'string' ? ( 'yes' == $manage_stock ) ? true : false : $manage_stock;

			if ( $stock_enable ) {
				$current_stock  = $this->get_stock_quantity();
				$increase_value = $this->get_overcharge( $increase_by_stock, $current_stock );
			}

			return apply_filters('yith_wcevti_time_overcharge', $increase_value);
		}

		/**
		 * @return increase_time_value
		 */
		public function get_time_overcharge() {
			$increase_by_time      = yit_get_prop( $this, '_increase_by_time', true );
			$start_event_date      = yit_get_prop( $this, '_start_date_picker', true );
			$start_event_timestamp = strtotime( $start_event_date );
			$current_timestamp     = time();

			$seconds_left = $start_event_timestamp - $current_timestamp;
			$days_left    = ceil( $seconds_left / DAY_IN_SECONDS );


			$increase_value = 0;
			if ( isset( $increase_by_time ) && ! empty( $increase_by_time ) ) {
				$increase_value = $this->get_overcharge( $increase_by_time, $days_left );
			}

			return apply_filters('yith_wcevti_time_overcharge', $increase_value);
		}

		/***
		 * @param $increase_by   arrays rules
		 * @param $current_value the time or stock number
		 *
		 * @return float|int price to overcharge
		 */
		public function get_overcharge( $increase_by, $current_value ) {
			$increase_value = 0;
			//Compare current and if is it lower or equal keep rule.
			$current_rule = null;
			if ( is_array( $increase_by ) ) {
				$increase_by_index = array();
				//Loop increase by to keep threshold on index
				foreach ( $increase_by as $increase ) {
					if ( isset( $increase['_threshold'] ) ) {
						$increase_by_index [] = $increase['_threshold'];
					}
				}
				//Short increase index more to less
				arsort( $increase_by_index );

				// Loop index and we get the less value coincidence to $current_value.
				foreach ( $increase_by_index as $key => $index ) {
					if ( $index >= $current_value ) {
						$current_rule = $increase_by[ $key ];
					}
				}

			}
			if ( is_null( $current_rule ) ) {
				return 0;
			}
			if ( 'fixed' == $current_rule['_increase_ticket_event_type'] ) {
				$increase_value = $current_rule['_increase_fixed_amount'];
			}
			if ( 'percentage' == $current_rule['_increase_ticket_event_type'] ) {
				$current_rule['_increase_percentage_amount'] = floatval( $current_rule['_increase_percentage_amount'] );
				if ( 100 >= $current_rule['_increase_percentage_amount'] ) {
					$increase_value = ( parent::get_price() * $current_rule['_increase_percentage_amount'] ) / 100;
				}
			}

			return $increase_value;
		}

		/**
		 * @return string
		 */
		public function add_to_cart_url() {
			return apply_filters( 'woocommerce_product_add_to_cart_url', get_permalink( $this->get_id() ), $this );
		}

		/**
		 * Get internal type.
		 * @return string
		 */
		public function get_type() {
			return 'ticket-event';
		}
	}
}