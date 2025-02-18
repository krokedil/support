<?php

namespace Krokedil\Support;

/**
 * Class to handle assets.
 */
class Assets {
	/**
	 * Get the URL to the assets directory.
	 *
	 * @return string
	 */
	public static function get_assets_url() {
		return plugin_dir_url( __DIR__ ) . 'assets/';
	}

	/**
	 * Get the URL to the assets directory.
	 *
	 * @return string
	 */
	public static function get_assets_path() {
		return plugin_dir_path( __DIR__ ) . 'assets/';
	}

	/**
	 * Enqueue scripts and styles for the admin order page.
	 *
	 * @param \WC_Order $order The order object.
	 *
	 * @return void
	 */
	public static function enqueue_admin_order_scripts( $order ) {
		$params = array(
			'order_id' => $order->get_id(),
			'ajax'    => array(
				'export_order' => array(
					'url'    => \WC_AJAX::get_endpoint( 'krokedil_support_export_order' ),
					'nonce'  => wp_create_nonce( 'krokedil_support_export_order' ),
				)
			)
		);

		wp_register_script( 'krokedil-support-admin-order', self::get_assets_url() . 'js/admin-order.js', array( 'jquery' ), '1.0.0', true );
		wp_localize_script( 'krokedil-support-admin-order', 'krokedil_support_admin_order_params', $params );

		wp_enqueue_script( 'krokedil-support-admin-order' );
	}
}
