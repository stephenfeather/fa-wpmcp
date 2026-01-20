<?php
/**
 * PHPUnit bootstrap for FA WPMCP plugin tests.
 *
 * @package FAWpmcp\Tests
 */

declare(strict_types=1);

// Load Composer autoloader.
$autoloader = dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	die( "Composer autoloader not found. Run 'composer install' first.\n" );
}

require_once $autoloader;

// Define WordPress constants for testing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

// Define plugin constants manually for testing (instead of loading the plugin file which has hooks).
if ( ! defined( 'FA_WPMCP_VERSION' ) ) {
	define( 'FA_WPMCP_VERSION', '1.0.0' );
}

if ( ! defined( 'FA_WPMCP_PATH' ) ) {
	define( 'FA_WPMCP_PATH', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'FA_WPMCP_URL' ) ) {
	define( 'FA_WPMCP_URL', 'http://localhost/wp-content/plugins/fa-wpmcp/' );
}

if ( ! defined( 'FA_WPMCP_BASENAME' ) ) {
	define( 'FA_WPMCP_BASENAME', 'fa-wpmcp/fa-wpmcp.php' );
}
