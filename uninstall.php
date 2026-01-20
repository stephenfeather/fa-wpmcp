<?php
/**
 * Uninstall handler for FA WPMCP plugin.
 *
 * This file runs when the plugin is uninstalled (deleted) from WordPress.
 * It removes all plugin data according to user settings.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp;

// Exit if not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load Composer autoloader.
$autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $autoloader ) ) {
	require_once $autoloader;

	// Run uninstall process.
	if ( class_exists( 'FAWpmcp\\Plugin' ) ) {
		Plugin::get_instance()->uninstall();
	}
}
