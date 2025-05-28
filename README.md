# Krokedil/Support
The support library allows you to extend your plugin with logging that can be optionally toggled through your plugin settings, generate an entry in the system report from your plugin settings, and report issues to the system report.

The library has two classes:
- `Logger`: The class that provides the logging functionality.
- `SystemReport`: The class that provides the system report functionality.

## Requirements

- PHP >=7.4

## Installation

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

## Logger

In your main plugin file, you can add the following code to initialize the logger:

```php
use Krokedil\Support\Logger;
class Plugin {

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Logger instance.
     *
     * @return Logger
     */
    public function logger() {
        return $this->logger;
    }

    public function __construct() {
        $logging_enabled = true;
        $this->logger = new Logger( 'krokedil_payments',  $logging_enabled );
    }
}
```
### The `log()` method and its variants

#### Method signature
You can either use the generic `log()` method or the more specific methods like `info()`, `error()`, etc.
```php
/**
 * Creates a log entry.
 *
 * @param string $message Log message.
 * @param string $level One of the following:
 *    - `emergency`: System is unusable.
 *    - `alert`: Action must be taken immediately.
 *    - `critical`: Critical conditions.
 *    - `error`: Error conditions.
 *    - `warning`: Warning conditions.
 *    - `notice`: Normal but significant condition.
 *    - `info`: Informational messages.
 *    - `debug`: Debug-level messages.
 * @param array  $args Additional context to log.
 */
public function log( $message, $level = WC_Log_Levels::INFO, ...$args );

/**
 * Log an info message.
 *
 * @param string $message Info message.
 * @param array  $args Additional context to log.
 */
public function info( $message, ...$args );
```
#### Example
```php
Plugin()->logger()->info( 'This is an info message', array( 'key' => 'value' ) );
```

### Plugin settings

You can extend your plugin settings with the following by calling `add_settings_fields($form_fields)` where you generate your plugin settings form. 

```php
$settings = array(
	'troubleshooting'  => array(
		'title' => __( 'Troubleshooting', 'krokedil-support' ),
		'type'  => 'title',
	),
	'logging'          => array(
		'title'       => __( 'Logging', 'krokedil-support' ),
		'label'       => 'Enable',
		'type'        => 'checkbox',
		'description' => __( 'Logging is required for troubleshooting any issues related to the plugin. It is recommended that you always have it enabled.', 'krokedil-support' ),
		'default'     => 'yes',
	),
	'extended_logging' => array(
		'title'       => __( 'Detailed logging', 'krokedil-support' ),
		'label'       => __( 'Enable', 'krokedil-support' ),
		'type'        => 'checkbox',
		'description' => __( 'Enable detailed logging to capture extra data. Use this only when needed for debugging hard-to-replicate issues, as it generates significantly more log entries.', 'krokedil-support' ),
		'default'     => 'no',
	),
);
```

#### Example

```php
return Plugin()->logger()->add_settings_fields( $plugin_settings );
```


## SystemReport
In your main plugin file, you can add the following code to initialize the system report:

```php
use Krokedil\Support\SystemReport;
class Plugin {

    /**
     * SystemReport instance.
     *
     * @var SystemReport
     */
    private $system_report;

    /**
     * System report.
     *
     * @return SystemReport
     */
    public function report() {
        return $this->system_report;
    }

    public function __construct() {
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
        $this->support = new Support( 'krokedil_payments', 'Krokedil Payments for WooCommerce', $included_settings );
    }
}
```

The settings must be an array of arrays, where each array contains the following keys:
- `type`: the type of the setting (e.g. checkbox, select, etc.)
- `id`: the ID of the setting.
- `class`: the class of the setting.
- `exclude`: an array of keys to exclude from the setting.
- `is_section`: whether the setting is a section or not.

The `exclude` array must be used together with a type, id or class.
- `isset`: if the setting is set, it will be excluded.
- `empty`: if the setting is empty, it will be excluded.
- `title`: if the setting title matches this exact string, it will be excluded.

### The `request()` method

#### Method signature
```php
/**
 * Add a log entry to the system report.
 *
 * @param array|object|\WP_Error $response The API request that you want to report about.
 * @param mixed                  $extra    Any extra information you want to include in the report.
 *
 * @return array|object|\WP_Error
 */
public function request( $response, $extra = null );
```

You can call the `request()` anywhere you want to report an error to the system report. The method will return the first argument that you're reporting about. The `$extra` argument is optional and can be used to include any additional information you want to report.

#### Example
```php
/**
 * Process the API request.
 *
 * @param array|WP_Error $response
 * @return array|WP_Error
 */
public function check_for_api_error( $response ) {
    return Plugin()->report()->request( $response );
}
```
