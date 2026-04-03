<?php

namespace Krokedil\Support;

use Krokedil\Support\Api\ApiRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstraps support package runtime hooks.
 */
class Bootstrap {
	/**
	 * Whether support hooks have already been registered.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Register AJAX and REST handlers once per request.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		new AJAX();
		new ApiRegistry();
	}
}
