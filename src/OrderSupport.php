<?php

namespace Krokedil\Support;

/**
 * Class to generate support information for WooCommerce orders and order information so it can be sent to Krokedil.
 *
 * @package Krokedil/Support
 */
class OrderSupport {
	/**
	 * The slug for the log files to search in.
	 *
	 * @var string|null
	 */
	private $log_context = null;

	/**
	 * The metadata key from the order to search for the value in the log files.
	 *
	 * @var string|null
	 */
	private $log_metadata = null;

	/**
	 * Class constructor.
	 *
	 * @param string $log_context The log file slug.
	 * @param string $log_metadata The metadata key for the log file to search for.
	 *
	 * @return void
	 */
	public function __construct( $log_context = null, $log_metadata = null ) {
		$this->log_context  = $log_context;
		$this->log_metadata = $log_metadata;
	}

	/**
	 * Export the order.
	 *
	 * @param \WC_Order[]|\WC_Order $orders The order IDs to export.
	 *
	 * @return array
	 */
	public function export_orders( $orders ) {
		// If we are passed a single order, convert it to a single element array to make it easier to handle the different cases.
		if ( ! is_array( $orders ) ) {
			$orders = array( $orders );
		}

		// Filter out any orders that are not instances of WC_Order.
		$orders = array_filter(
			$orders,
			function ( $order ) {
				return $order instanceof \WC_Order;
			}
		);

		// Get the log lines and order data for the orders.
		if ( empty( $orders ) ) {
			return array();
		}

		$log_data   = LogParser::get_log_data( $orders, $this->log_context, $this->log_metadata );
		$order_data = $this->get_order_data( $orders );

		/*
		 *  Combine the log and order data into a single array like this:
		 * [
		 *     '[order_id]' => [
		 *         'data' => [order data],
		 *         'log'  => [log data],
		 *     ],
		 * ]
		 */
		$combined_data = array();
		foreach ( $orders as $order ) {
			$order_id = $order->get_id();

			$combined_data[ $order_id ] = array(
				'data' => $order_data[ $order_id ] ?? array(),
				'logs' => $log_data[ $order_id ] ?? array(),
			);
		}

		return $combined_data;
	}

	/**
	 * Get order data.
	 *
	 * @param \WC_Order[] $orders The order object.
	 * @return array
	 */
	public function get_order_data( $orders ) {
		$order_data = array();

		foreach ( $orders as $order ) {
			$data = $order->get_data();

			// Empty the line_items, shipping_lines, fee_lines, coupon_lines and tax_lines arrays.
			$data['line_items']     = array();
			$data['shipping_lines'] = array();
			$data['fee_lines']      = array();
			$data['coupon_lines']   = array();

			// Get the formatted line, shipping, fee, coupon and tax items too.
			foreach ( $order->get_items() as $item_id => $item ) {
				$data['line_items'][ $item_id ] = $item->get_data();
			}

			foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
				$data['shipping_lines'][ $item_id ] = $item->get_data();
			}

			foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
				$data['fee_lines'][ $item_id ] = $item->get_data();
			}

			foreach ( $order->get_items( 'coupon' ) as $item_id => $item ) {
				$data['coupon_lines'][ $item_id ] = $item->get_data();
			}

			foreach ( $order->get_items( 'tax' ) as $item_id => $item ) {
				$data['tax_lines'][ $item_id ] = $item->get_data();
			}

			// Get all order comments for the order as well.
			$data['order_comments'] = wc_get_order_notes(
				array(
					'order_id' => $order->get_id(),
				)
			);

			$order_data[ $order->get_id() ] = $data;
		}

		return $order_data;
	}
}
