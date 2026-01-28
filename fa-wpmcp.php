<?php

/**
 * Plugin Name: FA WPMCP
 * Plugin URI: https://github.com/featherart/fa-wpmcp
 * Description: Exposes WordPress functionality to AI agents via Abilities API and MCP Adapter
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 8.1
 * Author: Feather Art
 * Text Domain: fa-wpmcp
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package FAWpmcp
 */

declare(strict_types=1);

namespace FAWpmcp;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('FA_WPMCP_VERSION', '1.0.0');
define('FA_WPMCP_PATH', plugin_dir_path(__FILE__));
define('FA_WPMCP_URL', plugin_dir_url(__FILE__));
define('FA_WPMCP_BASENAME', plugin_basename(__FILE__));
define('FA_WPMCP_ACTIVATION_ERROR_TITLE', 'Plugin Activation Error');

// Load Composer autoloader.
$autoloader = FA_WPMCP_PATH . 'vendor/autoload.php';

if (! file_exists($autoloader)) {
    add_action(
        'admin_notices',
        function () {
            echo '<div class="error"><p>';
            echo esc_html__('FA WPMCP: Composer autoloader not found. Please run "composer install".', 'fa-wpmcp');
            echo '</p></div>';
        }
    );
    return;
}

require_once $autoloader;

// Initialize plugin early to hook into wp_abilities_api_init before it fires.
add_action(
    'plugins_loaded',
    function () {
        Plugin::getInstance()->init();
    },
    1  // Early priority to ensure we hook into wp_abilities_api_init before it fires
);

// Activation hook.
register_activation_hook(
    __FILE__,
    function () {
        // Check PHP version.
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            deactivate_plugins(FA_WPMCP_BASENAME);
            wp_die(
                esc_html__('FA WPMCP requires PHP 8.1 or higher.', 'fa-wpmcp'),
                esc_html__(FA_WPMCP_ACTIVATION_ERROR_TITLE, 'fa-wpmcp'),
                array( 'back_link' => true )
            );
        }

        // Check WordPress version.
        if (version_compare(get_bloginfo('version'), '6.9', '<')) {
            deactivate_plugins(FA_WPMCP_BASENAME);
            wp_die(
                esc_html__('FA WPMCP requires WordPress 6.9 or higher.', 'fa-wpmcp'),
                esc_html__(FA_WPMCP_ACTIVATION_ERROR_TITLE, 'fa-wpmcp'),
                array( 'back_link' => true )
            );
        }

        // Check for Abilities API.
        if (! function_exists('wp_register_ability')) {
            deactivate_plugins(FA_WPMCP_BASENAME);
            wp_die(
                esc_html__('FA WPMCP requires WordPress Abilities API (WordPress 6.9+).', 'fa-wpmcp'),
                esc_html__(FA_WPMCP_ACTIVATION_ERROR_TITLE, 'fa-wpmcp'),
                array( 'back_link' => true )
            );
        }

        // Run activation.
        Plugin::getInstance()->activate();
    }
);

// Deactivation hook.
register_deactivation_hook(
    __FILE__,
    function () {
        Plugin::getInstance()->deactivate();
    }
);
