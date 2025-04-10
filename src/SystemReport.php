<?php
namespace Krokedil\Support;

use Automattic\WooCommerce\Utilities\LoggingUtil;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SystemReport
 *
 * @package Krokedil\Support
 */
class SystemReport {
	/**
	 * Plugin ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The plugin name (or title).
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The included settings for the system report.
	 *
	 * @var array
	 */
	private $included_settings = array();

	/**
	 * The excluded settings for the system report.
	 *
	 * @var array
	 */
	private $excluded = array();

	/**
	 * The tracked field types for the system report.
	 *
	 * @var array
	 */
	private $tracked_field_types = array(
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

	/**
	 * List of skipped settings.
	 *
	 * @var array
	 */
	private $skipped_settings = array(
		'title' => 'enable/disable',
	);

	/**
	 * SystemReport constructor.
	 *
	 * @param string $id The plugin ID.
	 * @param string $name The plugin name (or title).
	 */
	public function __construct( $id, $name ) {
		$this->id   = $id;
		$this->name = $name;

		add_action( 'woocommerce_system_status_report', array( $this, 'add_status_page_box' ) );
		add_action( 'woocommerce_cleanup_logs', array( $this, 'remove_old_entries' ) );
	}

	/**
	 * Get the current settings for the gateway.
	 *
	 * @return array|false An associative title:value array of the current settings. False if plugin settings is cannot be retrieved.
	 */
	private function get_current_settings() {
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		$gateway          = $payment_gateways[ $this->id ] ?? null;
		$form_fields      = $gateway ? $gateway->get_form_fields() : array();

		if ( empty( $form_fields ) ) {
			return false;
		}

		$output   = array();
		$settings = get_option( 'woocommerce_' . $this->id . '_settings', array() );
		foreach ( $settings as $setting_key => $value ) {
			$form_field = $form_fields[ $setting_key ]  ?? array();

			// Check if the form field is valid for the system report output.
			if ( ! $this->is_form_field_valid( $form_field, $setting_key ) ) {
				continue;
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
	 * Check if a form field is valid for the system report output.
	 *
	 * @param array $form_field The form field to check.
	 * @param string $setting_key The setting key to check against.
	 *
	 * @return bool True if the form field is valid, false otherwise.
	 */
	private function is_form_field_valid( $form_field, $setting_key ) {
		// If the form field is empty, return false.
		if ( empty( $form_field ) ) {
			return false;
		}

		if ( ! empty( $this->included_settings ) ) { // If there are included settings, check if the form field is valid in the included settings. And skip any other checks.
			return $this->is_form_field_included( $form_field, $setting_key );
		}

		if ( ! in_array( $form_field['type'], $this->tracked_field_types, true ) ) { // Skip any form field types that are not in the list of tracked field types.
			return false;
		}

		if ( ! isset( $form_field['title'] ) ) { // Skip any form fields that do not have a title.
			return false;
		}

		if ( in_array( strtolower( $form_field['title'] ), $this->skipped_settings, true ) ) { // Skip any form fields that have a title that is in the skipped array.
			return false;
		}

		return $this->skip_setting( $form_field, $setting_key ); // Check if the setting should be skipped or not.
	}

	/**
	 * Check if the form field is valid in the included settings.
	 *
	 * @param array $form_field The form field to check.
	 * @param string $setting_key The setting key to check against.
	 *
	 * @return bool True if the form field is valid, false otherwise.
	 */
	private function is_form_field_included( $form_field, $setting_key ) {
		// Loop the included settings and check if the form field is valid.
		foreach ( $this->included_settings as $included_setting ) {
			if ( is_array( $included_setting ) ) {
				return $included_setting['value'] === $form_field[ $included_setting['key'] ];
			}

			return $included_setting === $setting_key;
		}

		// Default to false if no match is found.
		return false;
	}

	/**
	 * Check if the setting should be skipped or not.
	 *
	 * @param array $form_field The form field to check.
	 * @param string $setting_key The setting key to check against.
	 *
	 * @return bool True if the setting should be skipped, false otherwise.
	 */
	private function skip_setting( $form_field, $setting_key ) {
		foreach ( $this->skipped_settings as $skipped_setting ) {
			// Skip based on specific form field key, and value.
			if ( is_array( $skipped_setting ) ) {
				return $skipped_setting['value'] !== $form_field[ $skipped_setting['key'] ];
			}

			// Skip the setting if the setting key matches the skipped setting key.
			return $skipped_setting !== $setting_key;
		}
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
		$this->included_settings = $settings;
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
		include_once __DIR__ . '/Views/Admin/status-report.php';
	}

	/**
	 * Add a log entry to the system report.
	 *
	 * @param array|object|\WP_Error     $response The API request that you want to report about.
	 * @param mixed $extra    Any extra information you want to include in the report.
	 *
	 * @return array|object|\WP_Error
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
	public function remove_old_entries() {
		$retention_period = LoggingUtil::get_retention_period();

		$reports = json_decode( get_option( 'krokedil_support_' . $this->id, '[]' ), true );
		foreach ( $reports as $report ) {
			if ( strtotime( $report['timestamp'] ) < strtotime( "-{$retention_period} days" ) ) {
				unset( $reports[ $report ] );
			}
		}
		update_option( 'krokedil_support_' . $this->id, wp_json_encode( $reports ) );
	}
}
