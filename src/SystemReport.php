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

	private $included = array();
	private $excluded = array();

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
	 * Get the current settings for the gateway.
	 *
	 * @return array|false An associative title:value array of the current settings. False if plugin settings is cannot be retrieved.
	 */
	private function get_current_settings() {
		$tracked = array(
			'text',
			'textarea',
			'select',
			'multiselect',
			'radio',
			'checkbox',
			'number',
			'email',
			'tel',
			'url',
			'color',
			'date',
			'file',
		);

		$skipped = array_merge( array( 'title' => 'enable/disable' ), $this->excluded );

		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		$gateway          = $payment_gateways[ $this->id ] ?? null;
		$form_fields      = $gateway ? $gateway->get_form_fields() : array();

		if ( empty( $form_fields ) ) {
			return false;
		}

		$output   = array();
		$settings = get_option( 'woocommerce_' . $this->id . '_settings', array() );
		foreach ( $settings as $setting_key => $value ) {
			$form_field = $form_fields[ $setting_key ];

			if ( ! empty( $this->included ) ) {
				foreach ( $this->included as $setting ) {
					if ( is_array( $setting ) ) {
						if ( $setting['value'] !== $form_field[ $setting['key'] ] ) {
							continue;
						}
					} elseif ( $setting !== $setting_key ) {
							continue;
					}
				}
			} else {

				if ( ! in_array( $form_field['type'], $tracked, true ) ) {
					continue;
				}

				if ( ! isset( $form_field['title'] ) ) {
					continue;
				}

				if ( in_array( strtolower( $form_field['title'] ), $skipped, true ) ) {
					continue;
				}

				foreach ( $skipped as $setting ) {
					// Skip based on specific form field key, and value.
					if ( is_array( $setting ) ) {
						if ( $setting['value'] === $form_field[ $setting['key'] ] ) {
							continue;
						}
						// Skip based on setting name.
					} elseif ( $skipped === $setting_key ) {
						continue;
					}
				}
			}

			if ( empty( $value ) ) {
				$value = $form_field['default'] ?? $value;
			}

			$output[ $setting_key ] = array(
				'title' => rtrim( $form_field['title'], ':' ),
				'value' => $value,
			);

		}

		return $output;
	}

	/**
	 * Exclude specific settings from the system report.
	 *
	 * - if you pass an array, the 'key' is the form field key, and the 'value' is the value of that form field who you want to match against.
	 * - if you pass a string, it will match the setting option name.
	 * - you may mix both strings and arrays.
	 *
	 * @example `'key' => 'title', 'value' => 'enable/disable'` will exclude all form fields whose title is 'enable/disable'.
	 *
	 * @param array $settings The settings to exclude.
	 */
	public function exclude( $settings ) {
		$this->excluded = $settings;
	}

	/**
	 * Include ONLY specific settings in the system report.
	 *
	 * - if you pass an array, the 'key' is the form field key, and the 'value' is the value of that form field who you want to match against.
	 * - if you pass a string, it will match the setting option name.
	 * - you may mix both strings and arrays.
	 *
	 * @example `'key' => 'type', 'value' => 'checkbox'` mean include all checkbox settings.
	 *
	 * @param array $settings The settings to include.
	 */
	public function include( $settings ) {
		$this->included = $settings;
	}

	/**
	 * Displays the log entries on the System Report page.
	 *
	 * @return void
	 */
	public function add_status_page_box() {
		$settings = $this->get_current_settings();

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
