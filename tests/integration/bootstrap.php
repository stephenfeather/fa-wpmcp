<?php

/**
 * PHPUnit bootstrap for FA WPMCP integration tests.
 *
 * Integration tests make real HTTP calls to the WordPress MCP endpoint.
 * They require a running WordPress instance with the fa-wpmcp plugin active.
 *
 * Configuration priority:
 *   1. Environment variables (MCP_TEST_*)
 *   2. Docker-generated credentials file
 *   3. Default values (localhost dev environment)
 *
 * @package FAWpmcp\Tests\Integration
 */

declare(strict_types=1);

// Load Composer autoloader.
$autoloader = dirname(__DIR__, 2) . '/vendor/autoload.php';

if (! file_exists($autoloader)) {
    die("Composer autoloader not found. Run 'composer install' first.\n");
}

require_once $autoloader;

// Load integration test support classes.
require_once __DIR__ . '/Support/McpClient.php';
require_once __DIR__ . '/Support/McpIntegrationTestCase.php';

// Try to load Docker-generated credentials if they exist.
$dockerCredentialsFile = dirname(__DIR__, 2) . '/docker/wordpress-data/.mcp-test-credentials';
if (file_exists($dockerCredentialsFile)) {
    $lines = file($dockerCredentialsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue; // Skip comments
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            if (! getenv($key)) {
                putenv("{$key}={$value}");
            }
        }
    }
}

// Environment configuration with defaults.
// Priority: env vars > Docker credentials > defaults (localhost dev)
if (! defined('MCP_TEST_BASE_URL')) {
    define('MCP_TEST_BASE_URL', getenv('MCP_TEST_BASE_URL') ?: 'http://localhost');
}

if (! defined('MCP_TEST_ENDPOINT')) {
    define('MCP_TEST_ENDPOINT', getenv('MCP_TEST_ENDPOINT') ?: '/wp-json/mcp/mcp-adapter-default-server');
}

if (! defined('MCP_TEST_USERNAME')) {
    define('MCP_TEST_USERNAME', getenv('MCP_TEST_USERNAME') ?: 'featherarms_admin');
}

if (! defined('MCP_TEST_PASSWORD')) {
    define('MCP_TEST_PASSWORD', getenv('MCP_TEST_PASSWORD') ?: 'uFNyTM2nrGx0qc84347wCAbI');
}
