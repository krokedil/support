<?php

namespace Krokedil\Support;

/**
 * Class to get the system report from WooCommerce.
 */
class SystemReport {
	/**
	 * Get the WooCommerce system report as an array.
	 *
	 * @return string
	 */
	public static function get_report() {
		$legacy_wc     = version_compare( WC_VERSION, '9.0', '<=' );
		$system_report = '';
		if ( $legacy_wc ) {
			$system_report = WC()->api->get_endpoint_data( '/wc/v3/system_status' );
		} elseif ( class_exists( \Automattic\WooCommerce\Utilities\RestApiUtil::class ) ) {
			$system_report = wc_get_container()->get( \Automattic\WooCommerce\Utilities\RestApiUtil::class )->get_endpoint_data( '/wc/v3/system_status' );
		}

		return $system_report;
	}
}
