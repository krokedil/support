<?php

namespace Krokedil\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure support AJAX endpoints are registered for every request lifecycle.
add_action(
	'init',
	static function () {
		static $initialized = false;

		if ( $initialized ) {
			return;
		}

		$initialized = true;
		new AJAX();
	}
);
