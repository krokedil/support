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
	 * The gateway ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The gateway name (or title).
	 *
	 * @var string
	 */
	private $name;

	/**
	 * SystemReport constructor.
	 *
	 * @param string $id The gateway ID.
	 * @param string $name The gateway name (or title).
	 */
	public function __construct( $id, $name ) {
		$this->id   = $id;
		$this->name = $name;
		$this->register( $id );

		add_action( 'woocommerce_system_status_report', array( $this, 'add_status_page_box' ) );
		add_action( 'woocommerce_cleanup_logs', array( __CLASS__, 'remove_old_entries' ) );
	}

	/**
	 * Register gateway gateway system report.
	 *
	 * @param string $id The gateway ID.
	 */
	private function register( $id ) {
		$registry   = get_option( self::REGISTRY_OPTION, array() );
		$registry[] = $id;

		update_option( self::REGISTRY_OPTION, array_unique( $registry ) );
	}

	/**
	 * Displays the log entries on the System Report page.
	 *
	 * @return void
	 */
	public function add_status_page_box() {
		$id   = $this->id;
		$name = $this->name;
		include_once __DIR__ . '/includes/admin/views/status-report.php';
	}

	/**
	 * Add a log entry to the system report.
	 *
	 * @template T
	 * @param T     $response The API request that you want to report about.
	 * @param mixed $extra    Any extra information you want to include in the report.
	 *
	 * @return T
	 */
	public function request( $response, $extra = null ) {
		if ( ! is_wp_error( $response ) ) {
			return $response;
		}

		$logs   = json_decode( get_option( 'krokedil_support_' . $this->id, '[]' ), true );
		$logs[] = array(
			'timestamp' => current_time( 'mysql' ),
			'response'  => array(
				'code'    => $response->get_error_code(),
				'message' => $response->get_error_message(),
				'extra'   => $extra,
			),
		);

		update_option( 'krokedil_support_' . $this->id, wp_json_encode( $logs ) );
		return $response;
	}


	/**
	 * Remove old report entries.
	 *
	 * @hook woocommerce_cleanup_logs
	 */
	public static function remove_old_entries() {
		$registry = get_option( self::REGISTRY_OPTION, array() );
		foreach ( $registry as $id ) {
			$reports = json_decode( get_option( 'krokedil_support_' . $id, '[]' ), true );
			if ( empty( $reports ) ) {
				continue;
			}

			$retention_period = wc_get_container()->get( Settings::class )->get_retention_period();
			foreach ( $reports as $report ) {
				if ( strtotime( $report['timestamp'] ) < strtotime( "-{$retention_period} days" ) ) {
					unset( $reports[ $report ] );
				}
			}
			update_option( 'krokedil_support_' . $id, wp_json_encode( $reports ) );
		}
	}
}
