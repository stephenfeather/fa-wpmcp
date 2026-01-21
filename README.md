# FA WPMCP - WordPress MCP Plugin

WordPress plugin that exposes WordPress functionality to AI agents via the Abilities API and MCP (Model Context Protocol) Adapter.

## Requirements

- **PHP:** 8.1 or higher
- **WordPress:** 6.9 or higher (Abilities API)
- **Composer:** For dependency management

## Installation

1. Clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/featherart/fa-wpmcp.git
   cd fa-wpmcp
   ```

2. Install dependencies:
   ```bash
   composer install --no-dev  # Production
   composer install           # Development (includes test dependencies)
   ```

3. Activate the plugin in WordPress admin or via WP-CLI:
   ```bash
   wp plugin activate fa-wpmcp
   ```

## Quick Start

Once activated, the plugin automatically:
- Initializes the Ability Framework
- Sets up rate limiting (60 requests/min, 1000 requests/hour)
- Configures activity logging
- Registers webhook processing

Default permissions:
- Global read: **Enabled**
- Global write: **Disabled**

Configure via WordPress options or filters (see [Configuration](#configuration)).

## Features

### Core Framework

- **Ability Framework:** Extensible pipeline-based system for registering WordPress operations
  - Category-based organization (Posts, Pages, Users, etc.)
  - Operation types: READ, WRITE, DELETE
  - JSON Schema validation for inputs/outputs
  - Pipeline orchestration with middleware

- **Permission System:** Multi-level access control
  - Global permissions (read/write)
  - Category-level permissions (posts, pages, users)
  - Ability-level permissions (per-operation)
  - WordPress capability integration

### Security & Monitoring

- **Rate Limiting:** Dual-track protection
  - Per-user limits (WordPress user ID)
  - Per-IP limits (anonymous requests)
  - Configurable windows (minute, hour, day)
  - Transient storage with automatic expiration

- **Activity Logging:** Comprehensive audit trail
  - Correlation IDs for request tracking
  - User and IP address logging
  - Input/output capture (with PII redaction)
  - Execution time tracking
  - Database-backed persistence

- **Webhook System:** Event-driven notifications
  - Before/after/failed execution hooks
  - HMAC-SHA256 signature signing
  - Retry logic with exponential backoff
  - Queue-based processing (Action Scheduler or WP-Cron)
  - Configurable subscribers via WordPress options

### Standards Compliance

- **GDPR Ready:** Privacy-first design
  - PII redaction in logs
  - User consent tracking
  - Data retention policies
  - Right to erasure support

- **Code Quality:** Professional-grade implementation
  - PSR-12 coding standards
  - PHPStan level 8 static analysis
  - 174 unit tests, 64%+ coverage
  - Type-safe with PHP 8.1 features

## Architecture

### Design Principles

This plugin follows:
- **Test-Driven Development (TDD):** Tests written before implementation
- **Functional Programming (FP):** Pure functions, immutable value objects
- **SOLID Principles:** Single responsibility, dependency injection
- **Type Safety:** Strict types, readonly properties, interface contracts

### Component Overview

```
┌─────────────────────────────────────────────────────────────┐
│                         Plugin Core                          │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────────┐      ┌──────────────────┐              │
│  │ AbilityRegistry │◄─────┤ AbstractAbility  │              │
│  └────────┬────────┘      └──────────────────┘              │
│           │                                                   │
│           ▼                                                   │
│  ┌─────────────────┐                                         │
│  │ AbilityExecutor │                                         │
│  └────────┬────────┘                                         │
│           │                                                   │
│  ┌────────┴─────────┬─────────────┬──────────────┐          │
│  ▼                  ▼             ▼              ▼          │
│ PermissionChecker RateLimiter  ActivityLogger WebhookManager│
└─────────────────────────────────────────────────────────────┘

Flow: Request → Permissions → Rate Limit → Execute → Log → Webhook
```

### Key Components

#### 1. Ability Framework (src/Abilities/)

- **AbilityRegistry:** Central registry for all abilities
- **AbilityExecutor:** Pipeline orchestration with middleware
- **AbstractAbility:** Base class for ability implementations
- **ExecutionPipeline:** Composable middleware pipeline

#### 2. Permissions (src/Permissions/)

- **PermissionChecker:** Multi-level permission validation
- **PermissionSettings:** Immutable value object (readonly)
- **OptionsPermissionSettings:** WordPress options persistence

#### 3. Rate Limiting (src/RateLimiting/)

- **RateLimiter:** Main rate limiting logic
- **RateLimitStore:** Interface for storage backends
- **TransientRateLimitStore:** WordPress transients implementation
- **RateLimitConfig:** Configuration interface
- **OptionsRateLimitConfig:** WordPress options implementation
- **RateLimitCalculator:** Pure function calculations

#### 4. Activity Logging (src/Logging/)

- **ActivityLogger:** Main logging coordinator
- **LogRepository:** Database persistence layer
- **LogEntryBuilder:** Fluent builder for log entries
- **LogEntry:** Immutable value object

#### 5. Webhooks (src/Webhooks/)

- **WebhookService:** WordPress hooks integration
- **WebhookManager:** Queue processing and delivery
- **WebhookQueue:** Interface for queue backends
- **DatabaseWebhookQueue:** Database implementation
- **WebhookScheduler:** Action Scheduler/WP-Cron integration
- **SignatureGenerator:** HMAC signing/verification
- **PayloadBuilder:** Webhook payload construction

### Database Schema

#### Activity Logs Table

```sql
fa_wpmcp_activity_log:
  - id (bigint, auto)
  - correlation_id (varchar 36, indexed)
  - user_id (bigint, nullable)
  - ip_address (varchar 45)
  - ability_name (varchar 255, indexed)
  - input (longtext, JSON)
  - output (longtext, JSON, nullable)
  - success (tinyint)
  - error_message (text, nullable)
  - execution_time_ms (int, nullable)
  - created_at (datetime, indexed)
```

#### Webhook Queue Table

```sql
fa_wpmcp_webhook_queue:
  - id (bigint, auto)
  - url (text)
  - payload (longtext, JSON)
  - signature (varchar 255)
  - status (varchar 20, indexed)
  - attempts (int, default 0)
  - next_attempt_at (datetime, indexed, nullable)
  - created_at (datetime)
  - updated_at (datetime)
```

## Configuration

### WordPress Options

The plugin stores configuration in WordPress options:

#### Permission Settings

```php
// Option: fa_wpmcp_permissions
[
    'global_read' => true,   // Allow all read operations
    'global_write' => false, // Deny all write operations
    'categories' => [],      // Category-level overrides
    'abilities' => []        // Ability-level overrides
]
```

#### Rate Limit Configuration

```php
// Option: fa_wpmcp_rate_limits
[
    'default' => [
        'per_minute' => 60,
        'per_hour' => 1000,
        'per_day' => 10000
    ],
    'abilities' => []  // Per-ability overrides
]
```

#### Webhook Configuration

```php
// Option: fa_wpmcp_webhooks
[
    'enabled' => true,
    'secret' => 'your-webhook-secret',
    'urls' => [
        'https://example.com/webhook'
    ]
]
```

### Filters

Customize behavior via WordPress filters:

```php
// Modify permission settings before checking
add_filter('fa_wpmcp_permission_settings', function($settings) {
    return $settings->with_global_write(true);
});

// Modify rate limits
add_filter('fa_wpmcp_rate_limit_config', function($config) {
    return array_merge($config, [
        'default' => ['per_minute' => 120]
    ]);
});

// Customize webhook payload
add_filter('fa_wpmcp_webhook_payload', function($payload, $event) {
    $payload['custom_field'] = 'value';
    return $payload;
}, 10, 2);
```

### Constants

```php
// Define in wp-config.php
define('FA_WPMCP_DISABLE_RATE_LIMITING', true);
define('FA_WPMCP_DISABLE_WEBHOOKS', true);
define('FA_WPMCP_LOG_RETENTION_DAYS', 30);
```

## Development

### Project Structure

```
fa-wpmcp/
├── src/
│   ├── Abilities/          # Ability framework
│   ├── Database/           # Migrations and schema
│   ├── Logging/            # Activity logging
│   ├── Permissions/        # Permission system
│   ├── RateLimiting/       # Rate limiting
│   ├── ValueObjects/       # Immutable value objects
│   ├── Webhooks/           # Webhook system
│   └── Plugin.php          # Main plugin class
├── tests/
│   └── phpunit/            # Unit tests
├── .phpcs.xml              # Code standards config
├── phpstan.neon            # Static analysis config
├── phpunit.xml             # Test configuration
└── composer.json           # Dependencies
```

### Code Standards

This project follows **PSR-12** and **WordPress Coding Standards**.

Check code standards:
```bash
composer phpcs
```

Auto-fix code standards:
```bash
composer phpcbf
```

### Static Analysis

Run PHPStan (level 8):
```bash
composer phpstan
```

### Test Coverage

Generate HTML coverage report:
```bash
composer test:coverage
```

View report at `tests/coverage/index.html`.

Current coverage: **64.33%** (606/942 lines)

### Creating New Abilities

Extend `AbstractAbility` to create custom abilities:

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;

class GetPostAbility extends AbstractAbility {
    
    public function get_name(): string {
        return 'posts.get';
    }
    
    public function get_category(): string {
        return 'posts';
    }
    
    public function get_label(): string {
        return 'Get Post';
    }
    
    public function get_description(): string {
        return 'Retrieve a single post by ID';
    }
    
    public function get_input_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Post ID'
                ]
            ],
            'required' => ['id']
        ];
    }
    
    public function get_output_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'content' => ['type' => 'string'],
                'status' => ['type' => 'string']
            ]
        ];
    }
    
    public function get_required_capability(): string {
        return 'read';
    }
    
    public function get_operation_type(): string {
        return 'READ';
    }
    
    protected function do_execute(array $input): array {
        $post = get_post($input['id']);
        
        if (!$post) {
            throw new \RuntimeException('Post not found');
        }
        
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'status' => $post->post_status
        ];
    }
}
```

Register in `Plugin::init()`:

```php
$ability_registry->register(new GetPostAbility());
```

### Testing Patterns

Use **Brain\Monkey** for WordPress function mocking:

```php
use Brain\Monkey\Functions;

public function test_get_post() {
    Functions\when('get_post')->justReturn((object)[
        'ID' => 1,
        'post_title' => 'Test Post',
        'post_content' => 'Content',
        'post_status' => 'publish'
    ]);
    
    $ability = new GetPostAbility();
    $result = $ability->execute(['id' => 1]);
    
    $this->assertTrue($result->is_success());
    $this->assertEquals('Test Post', $result->data['title']);
}
```

### Continuous Integration

The project includes test commands suitable for CI pipelines:

```bash
# Full CI check
composer install --no-dev --prefer-dist
composer test
composer phpcs
composer phpstan
```

## API Documentation

### Ability Registration

Register abilities with WordPress:

```php
// In your plugin or theme
add_action('init', function() {
    if (function_exists('wp_register_ability')) {
        wp_register_ability([
            'name' => 'posts.get',
            'category' => 'posts',
            'label' => 'Get Post',
            'description' => 'Retrieve a single post',
            'input_schema' => [...],
            'output_schema' => [...],
            'required_capability' => 'read',
            'operation_type' => 'READ',
            'callback' => [new GetPostAbility(), 'execute']
        ]);
    }
});
```

### Execution Flow

```php
// Internal execution flow
$executor = $plugin->get_service('ability_executor');
$result = $executor->execute('posts.get', ['id' => 1]);

if ($result->is_success()) {
    echo $result->data['title'];
} else {
    echo $result->error_message;
}
```

### Webhook Payloads

Webhook events send this payload structure:

```json
{
  "event": "ability.before_execute",
  "timestamp": "2026-01-20T12:00:00+00:00",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "ability": {
    "name": "posts.get",
    "category": "posts"
  },
  "input": {
    "id": 1
  },
  "user_id": 42,
  "ip_address": "203.0.113.1"
}
```

Verify signatures:

```php
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');
$secret = get_option('fa_wpmcp_webhooks')['secret'] ?? '';

$expected = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

## Roadmap

### Phase 1.10 (Next) - Concrete Abilities
- [ ] GetPostAbility - Read single post
- [ ] CreatePostAbility - Create new post
- [ ] UpdatePostAbility - Modify existing post
- [ ] ListPostsAbility - Query posts with pagination

### Phase 1.11 - WordPress Registration
- [ ] Integrate with WordPress Abilities API
- [ ] Register abilities via `wp_register_ability()`
- [ ] REST API endpoints

### Future Enhancements
- [ ] Media/attachment abilities
- [ ] User management abilities
- [ ] Taxonomy (categories/tags) abilities
- [ ] Comment management abilities
- [ ] Plugin/theme abilities
- [ ] Multisite support
- [ ] GraphQL endpoint option

## Troubleshooting

### Plugin won't activate

**Error:** "FA WPMCP requires WordPress Abilities API"

**Solution:** Ensure WordPress 6.9+ is installed. The Abilities API is built into WP 6.9+.

### Composer autoloader not found

**Error:** Admin notice about missing autoloader

**Solution:** Run `composer install` in the plugin directory.

### Rate limiting too aggressive

**Symptom:** Requests denied with 429 status

**Solution:** Adjust rate limits via filter:

```php
add_filter('fa_wpmcp_rate_limit_config', function($config) {
    $config['default']['per_minute'] = 120;
    return $config;
});
```

### Webhooks not firing

**Check:**
1. Verify webhooks are enabled: `get_option('fa_wpmcp_webhooks')['enabled']`
2. Check webhook queue: `SELECT * FROM {$wpdb->prefix}fa_wpmcp_webhook_queue`
3. Ensure Action Scheduler or WP-Cron is running

### Activity log growing too large

**Solution:** Configure automatic cleanup via cron:

```php
add_action('init', function() {
    if (!wp_next_scheduled('fa_wpmcp_cleanup_logs')) {
        wp_schedule_event(time(), 'daily', 'fa_wpmcp_cleanup_logs');
    }
});

add_action('fa_wpmcp_cleanup_logs', function() {
    global $wpdb;
    $days = defined('FA_WPMCP_LOG_RETENTION_DAYS') 
        ? FA_WPMCP_LOG_RETENTION_DAYS 
        : 30;
    
    $repo = new \FAWpmcp\Logging\LogRepository($wpdb);
    $repo->delete_older_than($days);
});
```

## Security

### Reporting Vulnerabilities

Please do NOT open public issues for security vulnerabilities.

### Security Features

- HMAC-SHA256 webhook signing
- Rate limiting (DDoS protection)
- WordPress capability checks
- Input validation via JSON Schema
- SQL injection protection (prepared statements)
- XSS protection (escaped output)
- CSRF protection (WordPress nonces where applicable)

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Write tests first (TDD)
4. Implement feature
5. Ensure tests pass: `composer test`
6. Check code standards: `composer phpcs`
7. Run static analysis: `composer phpstan`
8. Commit: `git commit -m "Add feature"`
9. Push: `git push origin feature/my-feature`
10. Open a Pull Request

### Coding Guidelines

- **PSR-12** coding standard
- **Type declarations** on all parameters and returns
- **Strict types** (`declare(strict_types=1)`) in all files
- **Readonly properties** for immutable value objects
- **Pure functions** where possible
- **Dependency injection** over globals
- **Interface-based** design
- **100% test coverage** for new code

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

**Stephen Feather**  
Website: https://stephenfeather.com  
Email: stephen@feather.us

## Acknowledgments

- WordPress Abilities API team
- Model Context Protocol (MCP) specification
- Action Scheduler by Automattic
- Brain\Monkey testing library

---

**Status:** Phase 1.9 Complete ✅ | Framework ready for concrete ability implementations
