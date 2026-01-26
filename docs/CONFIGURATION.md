# Configuration Guide

This guide covers all configuration options for the FA WPMCP plugin.

## Overview

The plugin can be configured through:
- **WordPress Options** - Database-backed settings
- **Filters** - Runtime customization via hooks
- **Constants** - Compile-time configuration in `wp-config.php`

## WordPress Options

The plugin stores configuration in WordPress options that can be modified programmatically or through the database.

### Permission Settings

Controls access to abilities at multiple levels (global, category, and individual ability).

**Option Name:** `fa_wpmcp_permissions`

**Structure:**
```php
[
    'global_read' => true,   // Allow all read operations
    'global_write' => false, // Deny all write operations
    'categories' => [],      // Category-level overrides
    'abilities' => []        // Ability-level overrides
]
```

**Example - Enable global write access:**
```php
update_option('fa_wpmcp_permissions', [
    'global_read' => true,
    'global_write' => true,
    'categories' => [],
    'abilities' => []
]);
```

**Example - Restrict specific category:**
```php
update_option('fa_wpmcp_permissions', [
    'global_read' => true,
    'global_write' => false,
    'categories' => [
        'posts' => [
            'read' => true,
            'write' => false
        ]
    ],
    'abilities' => []
]);
```

**Example - Restrict specific ability:**
```php
update_option('fa_wpmcp_permissions', [
    'global_read' => true,
    'global_write' => false,
    'categories' => [],
    'abilities' => [
        'posts.delete' => false
    ]
]);
```

### Rate Limit Configuration

Configures rate limiting thresholds for API requests.

**Option Name:** `fa_wpmcp_rate_limits`

**Structure:**
```php
[
    'default' => [
        'per_minute' => 60,
        'per_hour' => 1000,
        'per_day' => 10000
    ],
    'abilities' => []  // Per-ability overrides
]
```

**Default Limits:**
- 60 requests per minute
- 1000 requests per hour
- 10000 requests per day

**Example - Increase default limits:**
```php
update_option('fa_wpmcp_rate_limits', [
    'default' => [
        'per_minute' => 120,
        'per_hour' => 2000,
        'per_day' => 20000
    ],
    'abilities' => []
]);
```

**Example - Set per-ability limits:**
```php
update_option('fa_wpmcp_rate_limits', [
    'default' => [
        'per_minute' => 60,
        'per_hour' => 1000,
        'per_day' => 10000
    ],
    'abilities' => [
        'posts.create' => [
            'per_minute' => 10,
            'per_hour' => 100,
            'per_day' => 500
        ]
    ]
]);
```

### Webhook Configuration

Configures webhook delivery and event notifications.

**Option Name:** `fa_wpmcp_webhooks`

**Structure:**
```php
[
    'enabled' => true,
    'secret' => 'your-webhook-secret',
    'urls' => [
        'https://example.com/webhook'
    ]
]
```

**Example - Configure webhooks:**
```php
update_option('fa_wpmcp_webhooks', [
    'enabled' => true,
    'secret' => 'my_secure_secret_key_here',
    'urls' => [
        'https://api.example.com/webhook',
        'https://backup.example.com/webhook'
    ]
]);
```

**Example - Disable webhooks:**
```php
update_option('fa_wpmcp_webhooks', [
    'enabled' => false,
    'secret' => '',
    'urls' => []
]);
```

### Webhook Secret Encryption

**Status:** ✅ Implemented (v1.0.0-alpha.4)

Webhook secrets are automatically encrypted at rest using authenticated encryption (AEAD) for both confidentiality and integrity protection.

**Encryption Method:**
- **Preferred:** libsodium XChaCha20-Poly1305 (when `sodium` extension available)
- **Fallback:** OpenSSL AES-256-GCM (when only OpenSSL available)
- **Key Derivation:** HKDF-SHA256 from WordPress authentication salts

**Automatic Migration:**
Existing plaintext secrets are automatically encrypted on first read (lazy migration). No manual intervention required.

**Storage Format:**
```
sodium:v1:base64(nonce||ciphertext||tag)
openssl:v1:base64(iv||tag||ciphertext)
```

**Example - Check if encryption is working:**
```php
$secret = get_option('fa_wpmcp_webhook_secret');

// Encrypted secrets have prefix
if (str_starts_with($secret, 'sodium:v1:') || str_starts_with($secret, 'openssl:v1:')) {
    echo 'Secret is encrypted';
} else {
    echo 'Secret is plaintext (will auto-encrypt on next webhook delivery)';
}
```

**Requirements:**
- WordPress authentication salts must be properly configured in `wp-config.php`
- Salts must not be default "put your unique phrase here" values
- Recommended: Use libsodium for better security (install `php-sodium` package)

**Security Features:**
- AEAD encryption (confidentiality + integrity)
- Random nonces/IVs prevent deterministic encryption
- HKDF key derivation ensures proper key separation
- Tamper detection via Poly1305/GCM authentication tags
- Memory safety with `sodium_memzero()` (when using libsodium)

### Observability Configuration

**Status:** ✅ Enabled by default

FA WPMCP configures the MCP Adapter to use `ErrorLogMcpObservabilityHandler`, which logs all MCP events to the PHP error log with structured formatting.

**Log Format:**
```
[MCP Observability] EVENT mcp.request 45.23ms [status=success,method=tools/call,site_id=1,user_id=123,...]
```

**Events Tracked:**
- `mcp.request` - All MCP tool/resource/prompt calls with timing
- `mcp.server.created` - Server initialization with component counts

**Log Location:**
- Check your PHP error log location (typically nginx error.log or php-fpm error.log)
- For Docker setups, check container logs

**Example Log Entries:**
```
# Successful tool call
[MCP Observability] EVENT mcp.request 0.26ms [site_id=1,user_id=1,method=tools/call,tool_name=core-get-site-info,status=success]

# Server startup
[MCP Observability] EVENT mcp.server.created [site_id=1,tools_count=64,resources_count=0,prompts_count=0,status=success]
```

**Viewing Logs:**
```bash
# Nginx error log
grep "MCP Observability" /var/log/nginx/error.log | tail -20

# Docker container
docker logs <container-name> 2>&1 | grep "MCP Observability"
```

**Customizing Observability:**

To disable observability or use a different handler, use the `mcp_adapter_default_server_config` filter:

```php
// Disable observability
add_filter('mcp_adapter_default_server_config', function($config) {
    $config['observability_handler'] = \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class;
    return $config;
}, 20);  // Priority 20 to run after FA WPMCP's filter
```

**Available Handlers:**
- `ErrorLogMcpObservabilityHandler` - Logs to PHP error_log (default)
- `NullMcpObservabilityHandler` - No-op, disables logging
- `ConsoleObservabilityHandler` - Console output (for CLI/debugging)

**Creating Custom Handlers:**

Implement `McpObservabilityHandlerInterface` to send events to external systems:

```php
use WP\MCP\Infrastructure\Observability\Contracts\McpObservabilityHandlerInterface;
use WP\MCP\Infrastructure\Observability\McpObservabilityHelperTrait;

class MyCustomHandler implements McpObservabilityHandlerInterface {
    use McpObservabilityHelperTrait;

    public function record_event(string $event, array $tags = [], ?float $duration_ms = null): void {
        // Send to StatsD, Prometheus, DataDog, etc.
        $formatted_event = self::format_metric_name($event);
        $merged_tags = self::merge_tags($tags);

        // Your external service call here
    }
}
```

### File Error Logging

**Status:** ✅ Implemented (v1.0.0-alpha.5)

FA WPMCP includes optional file-based error logging that writes MCP errors to a dedicated log file for debugging.

**Option Name:** `fa_wpmcp_settings`

**Key:** `file_error_logging_enabled`

**Log Location:** `wp-content/mcp-errors.log`

**Enabling via Admin UI:**

1. Go to **Settings > FA WPMCP**
2. Check **Enable File Error Logging**
3. Click **Save Settings**

**Enabling Programmatically:**
```php
$settings = get_option('fa_wpmcp_settings', []);
$settings['file_error_logging_enabled'] = true;
update_option('fa_wpmcp_settings', $settings);
```

**Log Format:**
```
[2026-01-25 12:34:56] [ERROR] Ability returned WP_Error object | Context: {"ability":"fa-wpmcp/get-post","error_code":"ability_execution_failed","error_message":"Post not found"}
```

Each log entry contains:
- **Timestamp** - UTC time in Y-m-d H:i:s format
- **Type** - Log level (ERROR, INFO, DEBUG)
- **Message** - Description of the error
- **Context** - JSON-encoded metadata (ability name, error codes, etc.)

**Viewing Logs:**
```bash
# View recent errors
tail -20 /path/to/wordpress/wp-content/mcp-errors.log

# Watch in real-time
tail -f /path/to/wordpress/wp-content/mcp-errors.log

# Search for specific ability errors
grep "get-post" /path/to/wordpress/wp-content/mcp-errors.log
```

**Log Rotation:**

The log file grows unbounded by default. For production, configure log rotation:

```bash
# /etc/logrotate.d/fa-wpmcp
/path/to/wordpress/wp-content/mcp-errors.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
}
```

**Security Considerations:**
- The log file is created in `wp-content/`, which should not be web-accessible
- Context data may contain ability parameters; sensitive data is not logged
- Consider disabling in production once debugging is complete

## Filters

Customize plugin behavior at runtime using WordPress filters.

### Permission Settings Filter

**Filter:** `fa_wpmcp_permission_settings`

Modify permission settings before checking.

**Example - Force global write access:**
```php
add_filter('fa_wpmcp_permission_settings', function($settings) {
    return $settings->with_global_write(true);
});
```

**Example - Add category-specific permission:**
```php
add_filter('fa_wpmcp_permission_settings', function($settings) {
    return $settings->with_category_permission('posts', true, false);
});
```

### Rate Limit Configuration Filter

**Filter:** `fa_wpmcp_rate_limit_config`

Modify rate limit configuration before checking.

**Example - Increase rate limits:**
```php
add_filter('fa_wpmcp_rate_limit_config', function($config) {
    return array_merge($config, [
        'default' => [
            'per_minute' => 120,
            'per_hour' => 2000
        ]
    ]);
});
```

**Example - Disable rate limiting for specific ability:**
```php
add_filter('fa_wpmcp_rate_limit_config', function($config) {
    $config['abilities']['posts.get'] = [
        'per_minute' => PHP_INT_MAX,
        'per_hour' => PHP_INT_MAX,
        'per_day' => PHP_INT_MAX
    ];
    return $config;
});
```

### Webhook Payload Filter

**Filter:** `fa_wpmcp_webhook_payload`

Customize webhook payload before delivery.

**Parameters:**
- `$payload` (array) - The webhook payload
- `$event` (string) - The event type

**Example - Add custom field:**
```php
add_filter('fa_wpmcp_webhook_payload', function($payload, $event) {
    $payload['custom_field'] = 'value';
    $payload['server_time'] = time();
    return $payload;
}, 10, 2);
```

**Example - Add environment context:**
```php
add_filter('fa_wpmcp_webhook_payload', function($payload, $event) {
    $payload['environment'] = wp_get_environment_type();
    $payload['site_url'] = get_site_url();
    return $payload;
}, 10, 2);
```

### Webhook URL Filter

**Filter:** `fa_wpmcp_webhook_urls`

Modify webhook subscriber URLs at runtime.

**Example - Add dynamic URL:**
```php
add_filter('fa_wpmcp_webhook_urls', function($urls) {
    $urls[] = 'https://dynamic-service.example.com/webhook';
    return $urls;
});
```

## Constants

Define constants in `wp-config.php` for compile-time configuration.

### Available Constants

#### Disable Rate Limiting

Completely disable rate limiting checks.

```php
define('FA_WPMCP_DISABLE_RATE_LIMITING', true);
```

#### Disable Webhooks

Completely disable webhook delivery.

```php
define('FA_WPMCP_DISABLE_WEBHOOKS', true);
```

#### Log Retention

Set how many days to retain activity logs before cleanup.

```php
define('FA_WPMCP_LOG_RETENTION_DAYS', 30);  // Default: 30 days
```

#### Preserve Data on Uninstall

Prevent data deletion when plugin is uninstalled.

```php
define('FA_WPMCP_PRESERVE_DATA_ON_UNINSTALL', true);
```

**Note:** This constant prevents the uninstall handler from deleting:
- Database tables (`fa_wpmcp_activity_log`, `fa_wpmcp_webhook_queue`)
- WordPress options (all `fa_wpmcp_*` options)
- Transients and user meta

### Example wp-config.php

```php
// FA WPMCP Plugin Configuration

// Performance: Disable rate limiting in development
if (wp_get_environment_type() === 'local') {
    define('FA_WPMCP_DISABLE_RATE_LIMITING', true);
}

// Webhooks: Disable in staging
if (wp_get_environment_type() === 'staging') {
    define('FA_WPMCP_DISABLE_WEBHOOKS', true);
}

// Logging: Shorter retention in production
define('FA_WPMCP_LOG_RETENTION_DAYS', 7);

// Data: Preserve data during testing
if (defined('WP_DEBUG') && WP_DEBUG) {
    define('FA_WPMCP_PRESERVE_DATA_ON_UNINSTALL', true);
}
```

## Configuration Best Practices

### Security

1. **Never hardcode secrets** - Use options or environment variables
2. **Restrict write permissions** - Keep `global_write` disabled by default
3. **Use capability checks** - Leverage WordPress roles and capabilities
4. **Validate webhook signatures** - Always verify HMAC signatures

### Performance

1. **Tune rate limits** - Adjust based on your server capacity
2. **Use Action Scheduler** - Install for better webhook delivery
3. **Configure log cleanup** - Set reasonable retention periods
4. **Monitor queue size** - Check webhook queue table regularly

### Development vs Production

**Development:**
```php
// wp-config.php (development)
define('FA_WPMCP_DISABLE_RATE_LIMITING', true);
define('FA_WPMCP_DISABLE_WEBHOOKS', true);
define('FA_WPMCP_LOG_RETENTION_DAYS', 1);
```

**Production:**
```php
// wp-config.php (production)
define('FA_WPMCP_LOG_RETENTION_DAYS', 30);
// Enable all protections (rate limiting, webhooks)
```

### Multisite Considerations

When running in multisite, settings are **per-site**:
- Each site has its own `fa_wpmcp_permissions` option
- Rate limits are tracked per-site
- Webhook configurations are per-site

To configure network-wide defaults, use a mu-plugin:

```php
// wp-content/mu-plugins/fa-wpmcp-network-defaults.php
add_filter('fa_wpmcp_permission_settings', function($settings) {
    // Apply network-wide restrictions
    return $settings->with_global_write(false);
}, 5);  // Run early (priority 5)
```

## Troubleshooting Configuration

### Changes Not Taking Effect

**Problem:** Modified options but behavior unchanged

**Solutions:**
1. Clear object cache: `wp cache flush`
2. Check for filter overrides that run later
3. Verify option name spelling

### Rate Limiting Too Aggressive

**Problem:** Legitimate requests getting 429 errors

**Solutions:**
1. Increase limits via filter (immediate):
   ```php
   add_filter('fa_wpmcp_rate_limit_config', function($config) {
       $config['default']['per_minute'] = 120;
       return $config;
   });
   ```

2. Update options (persistent):
   ```php
   update_option('fa_wpmcp_rate_limits', [
       'default' => ['per_minute' => 120, 'per_hour' => 2000]
   ]);
   ```

3. Disable for development:
   ```php
   define('FA_WPMCP_DISABLE_RATE_LIMITING', true);
   ```

### Webhooks Not Firing

**Problem:** Webhooks configured but not delivered

**Check:**
1. Webhooks enabled: `get_option('fa_wpmcp_webhooks')['enabled']`
2. URLs configured: `get_option('fa_wpmcp_webhooks')['urls']`
3. Queue processing: `SELECT * FROM {$wpdb->prefix}fa_wpmcp_webhook_queue WHERE status = 'pending'`
4. Cron running: `wp cron event list`

### Permission Denied

**Problem:** Requests denied despite permissions set

**Check:**
1. Global permissions: `get_option('fa_wpmcp_permissions')['global_read']`
2. Category permissions: Check `categories` array in option
3. Ability permissions: Check `abilities` array in option
4. Filter overrides: Look for `fa_wpmcp_permission_settings` filters
5. WordPress capabilities: Verify user has required capability

## Configuration Examples

### Locked Down Production

```php
// Strict security for production
update_option('fa_wpmcp_permissions', [
    'global_read' => false,
    'global_write' => false,
    'categories' => [
        'posts' => ['read' => true, 'write' => false]
    ],
    'abilities' => [
        'posts.get' => true,
        'posts.list' => true
    ]
]);

update_option('fa_wpmcp_rate_limits', [
    'default' => [
        'per_minute' => 30,
        'per_hour' => 500,
        'per_day' => 5000
    ]
]);
```

### Open Development

```php
// Permissive settings for local development
define('FA_WPMCP_DISABLE_RATE_LIMITING', true);

update_option('fa_wpmcp_permissions', [
    'global_read' => true,
    'global_write' => true,
    'categories' => [],
    'abilities' => []
]);
```

### API-First WordPress

```php
// High-traffic API server
update_option('fa_wpmcp_rate_limits', [
    'default' => [
        'per_minute' => 300,
        'per_hour' => 10000,
        'per_day' => 100000
    ]
]);

update_option('fa_wpmcp_permissions', [
    'global_read' => true,
    'global_write' => true
]);

// Install Action Scheduler for reliable webhook delivery
// https://actionscheduler.org/
```

## Related Documentation

- [MCP Integration Guide](MCP_DOCUMENTATION.md) - MCP server setup and API usage
- [README](../README.md) - Overview and quick start
- [Plugin Architecture](../README.md#architecture) - Understanding the component structure
