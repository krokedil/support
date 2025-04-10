# What is this?

The Support package exposes a simple API for adding features that will make the troubleshooting, and thus handling support requests, easier.

Features:
- Logging: Log messages to a file (using the underlying WC logging API), and injects setting fields to your plugin for managing logging.
- System reports: Reports `WP_Error` where needed to WC's system status.

# Initializing the package

Overview:
1. Include the package in your composer file.
2. Initialize the Support class in your plugin.

## The composer file

If the plugin makes use of scoping, add this to your `composer-dependencies.json` file instead of the `composer.json` file.

```json
    "require": {
        "krokedil/support": "^1.0.0"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:krokedil/support.git"
        }
    ]
```

Thereafter, run `composer update` to install the package.

## The Support class

Let us assume your gateway is declared as follows:

```php
    $this->id           = 'krokedil_payments';
    $this->method_title = 'Krokedil Payments for WooCommerce';
```

In your plugin file, wherever you initialize your plugin, add the following code:

```php
// If you use scoping, prefix with the scope.
use Krokedil\Support\Support;
use Krokedil\Support\Logger;
use Krokedil\Support\SystemReport;

class Plugin {

	/**
	 * Support instance.
	 *
	 * @var Support
	 */
	private $support = null;

    /**
     * Logger instance.
     *
     * @return Logger
     */
	public function logger() {
		return $this->support->logger();
	}

    /**
     * System report.
     *
     * @return SystemReport
     */
	public function report() {
		return $this->support->system_report();
	}

    public function __construct() {
        $this->support = new Support( 'krokedil_payments', 'Krokedil Payments for WooCommerce' );
    }
}
```

From now one, we'll assume that the `$support` property is accessible through a global helper function that we name `KPayments()`.

# Enabling logging

The logger instance can be access through the `$this->support->logger` property.

```php
$context = array(
    'key' => 'value',
    'key2' => 'value2',
);

KPayments()->logger()->info( 'This is an info message', $context );
```

Only the first argument is required. The second argument is an array of context data that will be serialized and stored in the log file along the message.

# Enabling system reports

Let us assume you have a method for processing API requests that you want to report errors from:

```php
/**
 * Process the API request.
 *
 * @param array|WP_Error $response
 * @return array|WP_Error
 */
public function check_for_api_error( $response ) {
    return KPayments()->report()->request( $response );
}
```

You can call the `request()` anywhere you want to report an error. The method will return the original response if it is an array, or a `WP_Error` object if the response is an error.

# System report

The `SystemReport` class allows you to include the plugin settings of your choice in the WooCommerce system report.

The new instance must include the plugin's ID (or gateway ID), the plugin name, and the settings you want to include in the system report. The plugin name will be used as the title of the system report.

```php
new SystemReport( 'krokedil_payments', 'Krokedil Payments for WooCommerce', $included_settings );
```

- the plugin (or gateway) id.
- the plugin name. This name will be used as the title of the system report.
- the settings you want to include in the system report. The settings must be an array of arrays, where each array contains the following keys:
    - type: the type of the setting (e.g. checkbox, select, etc.)
    - id: the ID of the setting (optional)
    - class: the class of the setting (optional)
    - exclude: an array of keys to exclude from the setting (optional)
    - is_section: whether the setting is a section or not (optional)

```php
$included_settings = array(
	array(
		'type'       => 'section_start',        // The 'type' to match against. Custom types such are also supported.
		'is_section' => true,                   // Marks this setting as a section. It will be highlighted as table heading in the system report.
	),
	array(
		'type'    => 'checkbox',
		'exclude' => array(
			'empty' => 'title',                // If checkbox, and the title is empty or not set, the setting will be excluded.
            // 'isset' => 'title',             // If checkbox, the title is set, the setting will be excluded.
            // 'title' => 'enabled/disabled'   // If checkbox, and a title matches this exact string, the setting will be excluded.
		),
	),
	array( 'type' => 'multiselect' ),
	array( 'type' => 'select' ),
	array( 'id' => 'enabled' ),
	array( 'class' => 'wc-enhanced-select' ),
);
```