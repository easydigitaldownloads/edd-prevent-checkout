<?php
/*
Plugin Name: Easy Digital Downloads - Prevent Checkout
Plugin URI: http://sumobi.com/shop/edd-prevent-checkout/
Description: Prevents customer from being able to checkout until a minimum cart total is reached
Version: 1.0
Author: Andrew Munro, Sumobi
Author URI: http://sumobi.com/
License: GPL-2.0+
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'EDD_Prevent_Checkout' ) ) {

	class EDD_Prevent_Checkout {

		private static $instance;

		/**
		 * Main Instance
		 *
		 * Ensures that only one instance exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0
		 *
		 */
		public static function instance() {
			if ( ! isset ( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}


		/**
		 * Start your engines
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		public function __construct() {
			$this->setup_actions();
		}

		/**
		 * Setup the default hooks and actions
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function setup_actions() {

			// text domain
			add_action( 'init', array( $this, 'textdomain' ) );

			// show error before purchase form
			add_action( 'edd_before_purchase_form', array( $this, 'set_error' ) );

			// prevent form from being loaded
			add_filter( 'edd_can_checkout', array( $this, 'can_checkout' ) );

			// add settings
			add_filter( 'edd_settings_extensions', array( $this, 'settings' ) );
			
			// sanitize settings
			add_filter( 'edd_settings_extensions_sanitize', array( $this, 'sanitize_settings' ) );

			do_action( 'edd_pc_setup_actions' );

		}

		/**
		 * Internationalization
		 *
		 * @since 1.0
		 */
		function textdomain() {
			load_plugin_textdomain( 'edd-prevent-checkout', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Minimum cart amount required to checkout
		 *
		 * @since 1.0
		*/
		function minimum_cart_amount_required() {
			global $edd_options;

			$minimum_cart_amount = isset( $edd_options['edd_pc_minimum_cart_total'] ) ? $edd_options['edd_pc_minimum_cart_total'] : '';

			return $minimum_cart_amount;
		}

		/**
		 * Set error message
		 *
		 * @since 1.0
		*/
		function set_error() {

			$cart_amount = edd_get_cart_total();
			$formatted_minimum_cart_amount = edd_currency_filter( edd_format_amount( $this->minimum_cart_amount_required() ) );

			if ( $cart_amount < $this->minimum_cart_amount_required() ) {
				edd_set_error( 'total_not_reached', apply_filters( 'edd_pc_error_message', sprintf( __( 'The cart\'s total must be at least %s to complete this purchase', 'edd-prevent-checkout' ), $formatted_minimum_cart_amount ) ) );
			}
			else {
				edd_unset_error( 'total_not_reached' );
			}

			edd_print_errors();
		}
		
		/**
		 * Can checkout?
		 * Prevents the form from being displayed at all until the minimum cart total has been reached
		 *
		 * @since 1.0
		*/
		function can_checkout( $can_checkout ) {
			
			$cart_amount = edd_get_cart_total();
		
			// if the cart amount is less than the minimum cart amount required we don't let the customer check out
			if ( $cart_amount < $this->minimum_cart_amount_required() ) {
				$can_checkout = false;
				return $can_checkout;
			}

			return $can_checkout;
		}

		/**
		 * Settings
		 *
		 * @since 1.0
		*/
		function settings( $settings ) {

		  $edd_pc_settings = array(
				array(
					'id' => 'edd_pc_header',
					'name' => '<strong>' . __( 'Prevent Checkout', 'edd-prevent-checkout' ) . '</strong>',
					'type' => 'header'
				),
				array(
					'id' => 'edd_pc_minimum_cart_total',
					'name' => __( 'Minimum Cart Total', 'edd-prevent-checkout' ),
					'desc' => __( 'The minimum cart total before checkout is allowed. eg 10, or 10.00', 'edd-prevent-checkout' ),
					'type' => 'text',
					'std' => ''
				),
			);

			return array_merge( $settings, $edd_pc_settings );
		}

		/**
		 * Sanitize settings
		 *
		 * @since 1.0
		*/
		function sanitize_settings( $input ) {

			// only allow number, eg 10 or 10.00
			$input['edd_pc_minimum_cart_total'] = is_numeric( $input['edd_pc_minimum_cart_total'] ) ? $input['edd_pc_minimum_cart_total'] : '';

			return $input;
		}
		
	}

}


/**
 * Get everything running
 *
 * @since 1.0
 *
 * @access private
 * @return void
 */
function edd_prevent_checkout_load() {
	$edd_prevent_checkout = new EDD_Prevent_Checkout();
}
add_action( 'plugins_loaded', 'edd_prevent_checkout_load' );