<?php
/**
 * Plugin uninstall handler.
 *
 * Removes all plugin data from the database when the plugin is deleted.
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

// Exit if accessed directly or not during uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Exit if user requested data preservation.
if ( defined( 'FA_WPMCP_PRESERVE_DATA_ON_UNINSTALL' ) && FA_WPMCP_PRESERVE_DATA_ON_UNINSTALL ) {
	return;
}

global $wpdb;

/*
 * ========================================
 * Drop Database Tables
 * ========================================
 */

// Drop activity log table.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}fa_wpmcp_activity_log" );

// Drop webhook queue table.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}fa_wpmcp_webhook_queue" );

/*
 * ========================================
 * Delete WordPress Options
 * ========================================
 */

// Delete all plugin options.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'fa_wpmcp_%'" );

// Delete all transients (values and timeouts).
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_fa_wpmcp_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_fa_wpmcp_%'" );

/*
 * ========================================
 * Delete User Meta
 * ========================================
 */

// Delete all plugin user meta.
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'fa_wpmcp_%'" );

/*
 * ========================================
 * Delete Multisite Options
 * ========================================
 */

// Delete multisite options if in network.
if ( is_multisite() ) {
	$wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'fa_wpmcp_%'" );
}

/*
 * ========================================
 * Clear Scheduled Tasks
 * ========================================
 */

// Clear WP-Cron scheduled hooks.
wp_clear_scheduled_hook( 'fa_wpmcp_process_webhook_queue' );
wp_clear_scheduled_hook( 'fa_wpmcp_cleanup_old_logs' );

// Clear Action Scheduler actions if available.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'fa_wpmcp_process_webhook' );
	as_unschedule_all_actions( 'fa_wpmcp_retry_webhook' );
}
