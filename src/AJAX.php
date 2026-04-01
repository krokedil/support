<?php

namespace Krokedil\Support;

/**
 * Support AJAX class file.
 *
 * @package Krokedil/Support
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Support AJAX class
 */
class AJAX extends \WC_AJAX {
	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = array(
			'krokedil_support_export_order' => true,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				// WC AJAX can be used for frontend ajax requests.
				add_action( 'wc_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Export an order for support.
	 *
	 * @return void
	 */
	public function krokedil_support_export_order() {
		check_ajax_referer( 'krokedil_support_export_order', 'nonce' );

		$order_id = filter_input( INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! current_user_can( 'edit_shop_orders' ) || ! $order_id ) {
			wp_send_json_error( __( 'Invalid order ID.', 'krokedil-support' ) );
		}

		$order = wc_get_order( $order_id );

		$exported_order = $this->export_orders( $order );

		wp_send_json_success( $exported_order );
	}
}
