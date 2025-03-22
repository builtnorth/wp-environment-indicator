# WP Environment Indicator

A simple WordPress environment indicator that shows the current environment (development, staging, production) in the admin bar.

## Installation

```bash
composer require builtnorth/wp-environment-indicator
```

## Usage

### Basic Usage

Simply initialize the plugin in your theme or plugin:

```php
use WPEnvironmentIndicator\App;

$indicator = new App();
$indicator->init();
```

### Custom Configuration

You can customize the environment colors and labels:

```php
use WPEnvironmentIndicator\Plugin;

$indicator = new App();

// Customize environment settings
$indicator->set_config([
    'development' => [
        'color' => '#00ff00',
        'text' => 'Local Dev'
    ],
    'staging' => [
        'color' => '#ffaa00',
        'text' => 'QA Environment'
    ]
]);

$indicator->init();
```

## Features

-   Automatically detects environment using `WP_ENVIRONMENT_TYPE`
-   Shows environment indicator in admin bar
-   Customizable colors and labels
-   Only visible to users with `manage_options` capability
-   Lightweight and efficient

## Requirements

-   PHP 8.0 or higher
-   WordPress 5.0 or higher

## License

GPL-2.0-or-later

## Disclaimer

This software is provided "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. In no event shall the authors or copyright holders be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software or the use or other dealings in the software.

Use of this library is at your own risk. The authors and contributors of this project are not responsible for any damage to your website or any loss of data that may result from the use of this library.

While we strive to keep this library up-to-date and secure, we make no guarantees about its performance, reliability, or suitability for any particular purpose. Users are advised to thoroughly test the library in a safe environment before deploying it to a live site.

By using this library, you acknowledge that you have read this disclaimer and agree to its terms.
