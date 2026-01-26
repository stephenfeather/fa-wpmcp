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
	define( 'FA_WPMCP_VERSION', '1.0.0-alpha.2' );
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

// Define WordPress authentication keys and salts for testing.
// These are required by SodiumSecretEncryption and OpenSslSecretEncryption.
if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
	define( 'SECURE_AUTH_KEY', 'test-secure-auth-key-for-phpunit-testing-only-32chars!' );
}

if ( ! defined( 'LOGGED_IN_KEY' ) ) {
	define( 'LOGGED_IN_KEY', 'test-logged-in-key-for-phpunit-testing-only-32chars!' );
}

if ( ! defined( 'NONCE_SALT' ) ) {
	define( 'NONCE_SALT', 'test-nonce-salt-for-phpunit-testing-only-32characters!' );
}

if ( ! function_exists( 'user_can' ) ) {
	/**
	 * Test stub for user_can.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $capability Capability name.
	 * @return bool
	 */
	function user_can( int $user_id, string $capability ): bool {
		$overrides = $GLOBALS['fa_wpmcp_user_can'] ?? array();
		if ( isset( $overrides[ $user_id ] ) && array_key_exists( $capability, $overrides[ $user_id ] ) ) {
			return (bool) $overrides[ $user_id ][ $capability ];
		}

		return true;
	}
}
