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

**Connect AI Assistants:**
- **[Quick Start Guide](docs/QUICK_START.md)** - Get connected in 5 minutes
- **[Client Configuration](docs/MCP_CLIENT_CONFIGURATION.md)** - Claude, GPT, Gemini setup examples
- **[Full API Documentation](docs/MCP_DOCUMENTATION.md)** - Complete reference

## Features

### Core Framework

- **Ability Framework:** Extensible pipeline-based system for registering WordPress operations
  - Category-based organization (Posts, Pages, Users, etc.)
  - Operation types: READ, WRITE, DELETE
  - JSON Schema validation for inputs/outputs
  - Pipeline orchestration with middleware

### WordPress Abilities

The plugin provides comprehensive WordPress content management through the following ability categories:

- **Posts & Pages:** Full CRUD operations for posts, pages, and custom post types
  - Universal `post_type` parameter supports WordPress pages, WooCommerce products, and any custom post type
  - List, Get, Create, Update operations with filtering, pagination, and search
  
- **Comments:** Complete comment management system
  - List, Get, Create, Update, Delete operations
  - Support for comment moderation, threading, and metadata
  
- **Media Library:** Upload and manage media files
  - List, Get, Update, Upload operations
  - Base64 and URL upload support with automatic thumbnail generation
  - MIME type filtering and file size limits (10MB default)
  
- **Taxonomies:** Manage categories, tags, and custom taxonomies
  - List Terms, Get Term, Create Term, Update Term
  - Works with any taxonomy including hierarchical parent/child relationships
  - Full metadata support

- **User Management:** Complete user administration
  - List, Get, Create, Update operations
  - Role filtering, search, and flexible user lookup (ID, username, email)
  - Avatar URLs and profile metadata

- **Settings:** WordPress options management
  - Get, Update, Delete, List operations
  - Search filtering and pagination support
  - Safe option existence detection and serialization handling

- **Plugin Management:** WordPress plugin administration
  - List, Get, Install, Activate, Deactivate, Delete, Update operations
  - Matches wp-cli plugin command naming conventions
  - Full plugin lifecycle management

- **Theme Management:** WordPress theme administration
  - List, Get, Activate, Status, Install, Delete, Update operations
  - Matches wp-cli theme command naming conventions
  - Full theme lifecycle management

- **Privacy & GDPR:** Personal data management for compliance
  - Create Export Request, Create Erasure Request operations
  - List Privacy Requests, Get Privacy Request operations
  - Full integration with WordPress privacy tools
  - Email confirmation workflow for data requests

### Permission System

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

- **Privacy & Security:** Privacy-first design
  - PII redaction in logs (via PrivacyRedactor)
  - HMAC-SHA256 webhook signing
  - Rate limiting protection
  - Activity audit trail
  - GDPR compliance (data export/erasure via WordPress privacy API)

- **Code Quality:** Professional-grade implementation
  - PSR-12 coding standards
  - PHPStan level 8 static analysis
  - 645 unit tests, 79.41% coverage
  - Type-safe with PHP 8.1 features

## Architecture

The plugin follows professional software engineering practices: TDD, functional programming, SOLID principles, and strict type safety.

**For complete architecture documentation, see [Architecture Guide](docs/ARCHITECTURE.md).**

### Quick Overview

**Design Principles:**
- Test-Driven Development (TDD)
- Functional Programming (FP) with immutable value objects
- SOLID principles with dependency injection
- Type safety with PHP 8.1+ features

**Core Components:**
- **Ability Framework** - Registry, executor, pipeline orchestration
- **Permission System** - Multi-level access control (global/category/ability)
- **Rate Limiting** - Dual-track (per-user/per-IP) with transient storage
- **Activity Logging** - Audit trail with correlation IDs and PII redaction
- **Webhooks** - Event-driven notifications with HMAC signing

**Request Flow:**
```
Request → Permissions → Rate Limit → Execute → Log → Webhook
```

See the [Architecture Guide](docs/ARCHITECTURE.md) for detailed component documentation, database schema, design patterns, and extension points.

## Configuration

The plugin can be configured through WordPress options, filters, and constants.

**For complete configuration documentation, see [Configuration Guide](docs/CONFIGURATION.md).**

### Quick Configuration

**WordPress Options:**
- `fa_wpmcp_permissions` - Control global/category/ability-level access
- `fa_wpmcp_rate_limits` - Configure rate limiting thresholds
- `fa_wpmcp_webhooks` - Enable/configure webhook delivery

**Useful Filters:**
- `fa_wpmcp_permission_settings` - Modify permissions at runtime
- `fa_wpmcp_rate_limit_config` - Adjust rate limits dynamically
- `fa_wpmcp_webhook_payload` - Customize webhook payloads

**Constants (wp-config.php):**
- `FA_WPMCP_DISABLE_RATE_LIMITING` - Disable rate limiting
- `FA_WPMCP_DISABLE_WEBHOOKS` - Disable webhook delivery
- `FA_WPMCP_LOG_RETENTION_DAYS` - Set log retention period
- `FA_WPMCP_PRESERVE_DATA_ON_UNINSTALL` - Prevent data deletion on uninstall

See the [Configuration Guide](docs/CONFIGURATION.md) for detailed examples, best practices, and troubleshooting.

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

Current coverage: **74.33%** (703 tests, 1543 assertions)

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

### Completed Phases ✅
- [x] Phase 1.1-1.12: Core framework, permissions, rate limiting, logging, webhooks, error handling
- [x] Privacy redaction system (PrivacyRedactor)
- [x] HTTP response formatting utilities
- [x] Database schema and migrations
- [x] **Post Abilities** - Complete CRUD operations for posts, pages, and custom post types
  - Supports any post type via `post_type` parameter (posts, pages, WooCommerce products, etc.)
  - List, Get, Create, Update operations
- [x] **Comment Abilities** - Complete CRUD operations for comments
  - List, Get, Create, Update, Delete operations
- [x] **Media Abilities** - Media library management
  - List, Get, Update, Upload operations
  - Support for base64 and URL uploads
- [x] **Taxonomy Abilities** - Taxonomy and term management
  - List Terms, Get Term, Create Term, Update Term
  - Works with any taxonomy (categories, tags, custom taxonomies)
- [x] **User Abilities** - Complete user management system
  - List, Get, Create, Update operations
  - Role filtering, search, and flexible user lookup (ID, username, email)
- [x] **Settings Abilities** - WordPress options management
  - Get Option, Update Option, Delete Option, List Options
  - Search filtering and pagination
  - Safe serialization handling and option existence detection
- [x] **Plugin Abilities** - WordPress plugin management
  - List Plugins, Get Plugin, Install Plugin, Activate Plugin, Deactivate Plugin, Delete Plugin, Update Plugin
  - Follows wp-cli naming conventions
  - Full plugin lifecycle management
- [x] **Theme Abilities** - Plugin and theme management (complete)

### Future Enhancements
- [ ] **GDPR Compliance** - Data export/erasure hooks
- [ ] **Uninstall Handler** - Clean database on plugin removal
- [ ] **Integration Tests** - E2E testing with WordPress
- [ ] **Multisite Support** - Multi-site network compatibility
- [ ] **GraphQL Endpoint** - Optional GraphQL API
- [ ] **Delete Operations** - Delete abilities for posts, media, and taxonomies
- [ ] **Bulk Operations** - Bulk operations for terms and media

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

**Version:** 1.0.0 ✅ | **32 Abilities Implemented** | Posts, Comments, Media, Taxonomy, User, Settings, and Plugin management complete
