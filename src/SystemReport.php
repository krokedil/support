<?php

namespace Krokedil\Support;

use Automattic\WooCommerce\Internal\Admin\Logging\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SystemReport
 *
 * @package Krokedil\Support
 */
class SystemReport {

	public const REGISTRY_OPTION = 'krokedil_support_registry';

	/**
	 * The plugin slug or gateway ID.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * The name of the plugin or gateway.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * SystemReport constructor.
	 *
	 * @param string $slug The plugin slug or gateway ID.
	 * @param string $name The name of the plugin or gateway.
	 */
	public function __construct( $slug, $name ) {
		$this->slug = $slug;
		$this->name = $name;
		$this->register( $slug );

		add_action( 'woocommerce_system_status_report', array( $this, 'add_status_page_box' ) );
		add_action( 'woocommerce_cleanup_logs', array( __CLASS__, 'remove_old_entries' ) );
	}

	/**
	 * Register the plugin or gateway for the system report.
	 *
	 * @param string $slug The plugin slug or gateway ID.
	 */
	private function register( $slug ) {
		$registry   = get_option( self::REGISTRY_OPTION, array() );
		$registry[] = $slug;

		update_option( self::REGISTRY_OPTION, array_unique( $registry ) );
	}

	/**
	 * Displays the log entries on the System Report page.
	 *
	 * @return void
	 */
	public function add_status_page_box() {
		$slug = $this->slug;
		$name = $this->name;
		include_once __DIR__ . '/includes/admin/views/status-report.php';
	}

	/**
	 * Add a log entry to the system report.
	 *
	 * @param \WP_Error|array $maybe_error The API request that you want to report about.
	 * @param mixed           $extra       Any extra information you want to include in the report.
	 *
	 * @return bool
	 */
	public function request( $maybe_error, $extra = null ) {
		if ( ! is_wp_error( $maybe_error ) ) {
			return false;
		}

		$logs   = json_decode( get_option( 'krokedil_support_' . $this->slug, '[]' ), true );
		$logs[] = array(
			'timestamp' => current_time( 'mysql' ),
			'response'  => array(
				'code'    => $maybe_error->get_error_code(),
				'message' => $maybe_error->get_error_message(),
				'extra'   => $extra,
			),
		);

		return update_option( 'krokedil_support_' . $this->slug, wp_json_encode( $logs ) );
	}


	/**
	 * Remove old report entries.
	 *
	 * @hook woocommerce_cleanup_logs
	 */
	public static function remove_old_entries() {
		$registry = get_option( self::REGISTRY_OPTION, array() );
		foreach ( $registry as $slug ) {
			$reports = json_decode( get_option( 'krokedil_support_' . $slug, '[]' ), true );
			if ( empty( $reports ) ) {
				continue;
			}

			$retention_period = wc_get_container()->get( Settings::class )->get_retention_period();
			foreach ( $reports as $report ) {
				if ( strtotime( $report['timestamp'] ) < strtotime( "-{$retention_period} days" ) ) {
					unset( $reports[ $report ] );
				}
			}
			update_option( 'krokedil_support_' . $slug, wp_json_encode( $reports ) );
		}
	}
}
