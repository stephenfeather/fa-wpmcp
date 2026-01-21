# WordPress Abilities Plugin - Specification

**Version:** 1.0
**Date:** 2026-01-20
**Status:** Discovery Complete

---

## Executive Summary

A WordPress plugin that exposes comprehensive WordPress functionality to AI agents via the Abilities API and MCP Adapter. The plugin provides a framework for registering abilities with granular permission controls, activity logging, rate limiting, and webhook notifications.

**Approach:** Framework-first implementation with example Post abilities, designed for easy extensibility.

---

## Goals

### Primary Goal
Enable AI agents to perform any action a WordPress user could perform, with fine-grained permission controls managed through a WordPress admin interface.

### Secondary Goals
1. Provide audit trail of AI agent actions
2. Protect against abuse via rate limiting
3. Enable integration with external systems via webhooks
4. Create extensible architecture for adding new abilities
5. Maintain security best practices (public GitHub repo, no secrets)

---

## Scope

### In Scope - Functionality Areas

The plugin will provide a framework to expose abilities across these WordPress areas:

| Area | Read Abilities | Write Abilities |
|------|----------------|-----------------|
| **Posts & Pages** | List, get, search content | Create, update, delete, publish |
| **Media Library** | List, get, search media | Upload, update metadata, delete |
| **Users** | List, get user profiles | Create, update, delete users |
| **Settings** | Get site/plugin options | Update site/plugin options |
| **Plugins** | List installed plugins | Install, activate, deactivate, configure |
| **Themes** | List installed themes | Install, activate, customize |
| **Menus & Widgets** | Get menu/widget structure | Create, update, delete menus/widgets |

### Initial Implementation (Phase 1)

**Framework Components:**
- Core plugin architecture
- Admin settings UI with permission controls
- Permission checking system
- Activity logging system
- Rate limiting system
- Webhook notification system
- MCP server registration
- Ability registration helper classes

**Example Abilities (Posts):**
1. `fa-wpmcp/list-posts` - List posts with filters (READ)
2. `fa-wpmcp/get-post` - Get single post by ID (READ)
3. `fa-wpmcp/create-post` - Create new post (WRITE)
4. `fa-wpmcp/update-post` - Update existing post (WRITE)

### Out of Scope (Phase 1)

- Custom post type abilities (framework supports, but not implemented)
- Taxonomy management abilities
- Comment management abilities
- Database query abilities
- File system access beyond media uploads
- Multi-site specific abilities

---

## Architecture

### Plugin Structure

```
fa-wpmcp/
├── fa-wpmcp.php                 # Main plugin file
├── composer.json                # Composer dependencies
├── src/
│   ├── Plugin.php               # Main plugin class
│   ├── Abilities/
│   │   ├── AbilityRegistry.php  # Central ability registration
│   │   ├── CategoryRegistry.php # Category management
│   │   ├── AbstractAbility.php  # Base class for abilities
│   │   └── Posts/
│   │       ├── ListPosts.php
│   │       ├── GetPost.php
│   │       ├── CreatePost.php
│   │       └── UpdatePost.php
│   ├── Permissions/
│   │   ├── PermissionManager.php # Check settings + capabilities
│   │   └── Settings.php          # Get/set permission options
│   ├── Logging/
│   │   ├── ActivityLogger.php    # Log AI actions
│   │   └── LogViewer.php         # Admin UI for logs
│   ├── RateLimiting/
│   │   ├── RateLimiter.php       # Throttle requests
│   │   └── RateLimitStore.php    # Store rate limit data
│   ├── Webhooks/
│   │   ├── WebhookManager.php    # Send notifications
│   │   └── WebhookQueue.php      # Queue webhook calls
│   ├── Admin/
│   │   ├── SettingsPage.php      # Admin UI
│   │   └── AbilityTable.php      # List table of abilities
│   └── MCP/
│       └── ServerConfig.php      # MCP server setup
├── tests/
│   └── phpunit/
│       ├── Abilities/
│       ├── Permissions/
│       ├── Logging/
│       └── RateLimiting/
└── assets/
    ├── css/
    │   └── admin.css
    └── js/
        └── admin.js
```

### Component Responsibilities

#### 1. Ability Registry
- Register categories during `wp_abilities_api_categories_init`
- Register abilities during `wp_abilities_api_init`
- Auto-discover ability classes in `src/Abilities/` subdirectories
- Pass each ability through permission, logging, rate limiting checks

#### 2. Permission Manager
- Read settings from `wp_options` (`fa_wpmcp_permissions`)
- Multi-level checking: global → category → ability
- Integration with Abilities API `permission_callback`
- Respect WordPress capabilities (`current_user_can()`)

#### 3. Activity Logger
- Log every ability execution: user, ability, input, output, timestamp
- Store in custom table: `wp_fa_wpmcp_activity_log`
- Provide admin UI to view/export logs
- Optional log retention settings (auto-delete after N days)

#### 4. Rate Limiter
- Track requests per user + IP using transients
- Configurable limits: requests per minute/hour
- Return 429 Too Many Requests when exceeded
- Admin settings to configure limits per ability/category

#### 5. Webhook Manager
- Send HTTP POST to configured URLs on ability execution
- Payload: `{ ability, user, input, output, timestamp, success }`
- Queue failed webhooks for retry (3 attempts)
- Admin UI to configure webhook URLs per event type

#### 6. Admin Settings UI
- Multi-level permission toggles:
  - Global: "Enable All Read", "Enable All Write"
  - Category: Enable/disable read/write per category
  - Ability: Enable/disable read/write per ability
- Rate limiting configuration
- Webhook URL management
- Activity log viewer
- MCP server connection info

#### 7. MCP Server
- HTTP Transport (primary)
- Application Password authentication
- Server ID: `fa-wpmcp`
- Namespace: `fa-wpmcp`
- Register abilities as tools (write) and resources (read-only)

---

## Permission System

### Multi-Level Hierarchy

```
Global Settings
├── Enable All Read: ON/OFF
├── Enable All Write: ON/OFF
└── Categories
    ├── Posts & Pages
    │   ├── Enable Category Read: ON/OFF
    │   ├── Enable Category Write: ON/OFF
    │   └── Abilities
    │       ├── fa-wpmcp/list-posts: Enabled, READ
    │       ├── fa-wpmcp/get-post: Enabled, READ
    │       ├── fa-wpmcp/create-post: Disabled
    │       └── fa-wpmcp/update-post: Enabled, WRITE
    └── [Other categories...]
```

### Permission Check Flow

```
1. Is global read/write disabled?
   → YES: Deny immediately
   → NO: Continue

2. Is category read/write disabled?
   → YES: Deny immediately
   → NO: Continue

3. Is specific ability disabled?
   → YES: Deny immediately
   → NO: Continue

4. Does ability permission match operation type?
   → List-posts (READ) requires "read" or "read+write"
   → Create-post (WRITE) requires "write" or "read+write"
   → NO: Deny
   → YES: Continue

5. Check WordPress capability (ability-specific)
   → READ operations: current_user_can('fa_wpmcp_read_abilities')
   → WRITE operations: current_user_can('fa_wpmcp_write_abilities')
   → DELETE operations: current_user_can('fa_wpmcp_delete_abilities')
   → Fallback for backwards compat: manage_options, edit_posts, etc.
   → NO: Deny
   → YES: Allow
```

### Settings Storage

Stored in `wp_options` as JSON:

```json
{
  "global": {
    "enable_all_read": true,
    "enable_all_write": false
  },
  "categories": {
    "posts-pages": {
      "enable_read": true,
      "enable_write": true
    },
    "media": {
      "enable_read": true,
      "enable_write": false
    }
  },
  "abilities": {
    "fa-wpmcp/list-posts": {
      "enabled": true,
      "mode": "read"
    },
    "fa-wpmcp/create-post": {
      "enabled": false,
      "mode": "write"
    }
  }
}
```

---

## Activity Logging

### Database Schema

```sql
CREATE TABLE wp_fa_wpmcp_activity_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  correlation_id VARCHAR(36) NOT NULL,
  timestamp DATETIME NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  user_login VARCHAR(60) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  ability_name VARCHAR(255) NOT NULL,
  ability_category VARCHAR(100) NOT NULL,
  operation_type ENUM('read', 'write') NOT NULL,
  input_data LONGTEXT,
  output_data LONGTEXT,
  success BOOLEAN NOT NULL,
  error_message TEXT,
  execution_time_ms INT UNSIGNED,
  PRIMARY KEY (id),
  INDEX idx_correlation_id (correlation_id),
  INDEX idx_timestamp (timestamp),
  INDEX idx_user_id (user_id),
  INDEX idx_ability_name (ability_name),
  INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Log Viewer Features

- Filter by: date range, user, ability, success/failure
- Export to CSV
- Auto-delete logs older than N days (configurable)
- Show execution time statistics
- Highlight failures

---

## Rate Limiting

### Configuration

Per-ability or per-category limits:

```php
[
  'fa-wpmcp/list-posts' => [
    'requests_per_minute' => 60,
    'requests_per_hour' => 500,
  ],
  'fa-wpmcp/create-post' => [
    'requests_per_minute' => 10,
    'requests_per_hour' => 100,
  ],
]
```

### Storage

Use WordPress transients (per user + per IP):

```
Transient key: fa_wpmcp_ratelimit_{user_id}_{ip_hash}_{ability}_{window}
Value: request_count
Expiration: 60s (minute) or 3600s (hour)

IP hash: First 8 chars of md5(ip_address) for privacy
Example: fa_wpmcp_ratelimit_1_a3f8d9e2_fa-wpmcp/create-post_minute
```

**Dual Tracking:**
- Track by user ID (prevents single user abuse)
- Track by IP hash (prevents distributed abuse from same user)
- Both limits must pass for request to proceed

### Response

When limit exceeded:

```json
{
  "error": "rate_limit_exceeded",
  "message": "Too many requests. Try again in 30 seconds.",
  "retry_after": 30
}
```

HTTP Status: `429 Too Many Requests`

---

## Webhook System

### Event Types

- `ability.before_execute` - Before ability runs
- `ability.after_execute` - After ability completes
- `ability.failed` - Ability execution failed

### Payload Format

```json
{
  "event": "ability.after_execute",
  "timestamp": "2026-01-20T12:34:56Z",
  "ability": {
    "name": "fa-wpmcp/create-post",
    "category": "posts-pages",
    "operation": "write"
  },
  "user": {
    "id": 1,
    "login": "admin",
    "ip": "192.168.1.100"
  },
  "input": {
    "title": "New Post Title",
    "content": "Post content..."
  },
  "output": {
    "post_id": 42,
    "permalink": "https://example.com/new-post-title"
  },
  "success": true,
  "execution_time_ms": 145
}
```

### Configuration

Admin UI to add webhook URLs:

```
Event: ability.after_execute
URL: https://example.com/webhooks/wordpress-ai
Filter: Only posts-pages category
```

### Retry Logic

- Failed webhooks (non-200 response) queued for retry
- 3 retry attempts with exponential backoff: 1m, 5m, 15m
- After 3 failures, mark as failed and alert admin

---

## Ability Specification

### Example: List Posts (Read)

```php
[
  'name' => 'fa-wpmcp/list-posts',
  'category' => 'posts-pages',
  'label' => __('List Posts', 'fa-wpmcp'),
  'description' => __('Retrieves a list of WordPress posts with optional filtering. Use this to query posts by status, author, category, or search terms.', 'fa-wpmcp'),
  'input_schema' => [
    'type' => 'object',
    'properties' => [
      'status' => [
        'type' => 'string',
        'enum' => ['publish', 'draft', 'pending', 'private', 'any'],
        'default' => 'publish',
        'description' => 'Post status to filter by',
      ],
      'author' => [
        'type' => 'integer',
        'description' => 'Filter by author ID',
      ],
      'category' => [
        'type' => 'integer',
        'description' => 'Filter by category ID',
      ],
      'search' => [
        'type' => 'string',
        'description' => 'Search term for post title/content',
      ],
      'per_page' => [
        'type' => 'integer',
        'default' => 10,
        'minimum' => 1,
        'maximum' => 100,
        'description' => 'Number of posts to return',
      ],
      'page' => [
        'type' => 'integer',
        'default' => 1,
        'minimum' => 1,
        'description' => 'Page number for pagination',
      ],
    ],
  ],
  'output_schema' => [
    'type' => 'object',
    'properties' => [
      'posts' => [
        'type' => 'array',
        'items' => [
          'type' => 'object',
          'properties' => [
            'id' => ['type' => 'integer'],
            'title' => ['type' => 'string'],
            'excerpt' => ['type' => 'string'],
            'status' => ['type' => 'string'],
            'author' => ['type' => 'integer'],
            'date' => ['type' => 'string', 'format' => 'date-time'],
            'permalink' => ['type' => 'string'],
          ],
        ],
      ],
      'total' => ['type' => 'integer'],
      'pages' => ['type' => 'integer'],
    ],
  ],
  'execute_callback' => [ListPosts::class, 'execute'],
  'permission_callback' => [ListPosts::class, 'check_permission'],
  'meta' => [
    'show_in_rest' => true,
    'annotations' => [
      'readonly' => true,
      'destructive' => false,
      'idempotent' => true,
      'instructions' => 'Use this to query posts before creating or updating content.',
    ],
  ],
]
```

### Example: Create Post (Write)

```php
[
  'name' => 'fa-wpmcp/create-post',
  'category' => 'posts-pages',
  'label' => __('Create Post', 'fa-wpmcp'),
  'description' => __('Creates a new WordPress post. Use this to publish or draft new content on the site.', 'fa-wpmcp'),
  'input_schema' => [
    'type' => 'object',
    'required' => ['title', 'content'],
    'properties' => [
      'title' => [
        'type' => 'string',
        'description' => 'Post title',
      ],
      'content' => [
        'type' => 'string',
        'description' => 'Post content (HTML allowed)',
      ],
      'status' => [
        'type' => 'string',
        'enum' => ['publish', 'draft', 'pending'],
        'default' => 'draft',
        'description' => 'Post status',
      ],
      'author' => [
        'type' => 'integer',
        'description' => 'Author ID (defaults to current user)',
      ],
      'excerpt' => [
        'type' => 'string',
        'description' => 'Post excerpt',
      ],
      'categories' => [
        'type' => 'array',
        'items' => ['type' => 'integer'],
        'description' => 'Category IDs to assign',
      ],
      'tags' => [
        'type' => 'array',
        'items' => ['type' => 'string'],
        'description' => 'Tag names to assign',
      ],
    ],
  ],
  'output_schema' => [
    'type' => 'object',
    'properties' => [
      'post_id' => ['type' => 'integer'],
      'permalink' => ['type' => 'string'],
      'status' => ['type' => 'string'],
      'edit_url' => ['type' => 'string'],
    ],
  ],
  'execute_callback' => [CreatePost::class, 'execute'],
  'permission_callback' => [CreatePost::class, 'check_permission'],
  'meta' => [
    'show_in_rest' => true,
    'annotations' => [
      'readonly' => false,
      'destructive' => false,
      'idempotent' => false,
      'instructions' => 'Always use list-posts to check for duplicates before creating.',
    ],
  ],
]
```

---

## Technical Requirements

### PHP Standards

- **Namespace:** `FAWpmcp\`
- **PSR-4 Autoloading:** Via Composer
- **PHP Version:** >= 8.1 (requires readonly properties)
- **WordPress Version:** >= 6.9 (requires Abilities API)
- **Coding Standards:** WordPress Coding Standards (PHPCS)
- **Type Declarations:** Strict types enabled in all files

### Composer Dependencies

```json
{
  "require": {
    "wordpress/abilities-api": "^1.0",
    "wordpress/mcp-adapter": "^0.3",
    "woocommerce/action-scheduler": "^3.7",
    "php": ">=8.1"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.0",
    "squizlabs/php_codesniffer": "^3.7",
    "wp-coding-standards/wpcs": "^3.0",
    "mockery/mockery": "^1.5",
    "brain/monkey": "^2.6"
  },
  "autoload": {
    "psr-4": {
      "FAWpmcp\\": "src/"
    }
  }
}
```

**IMPORTANT:** Do NOT use Jetpack Autoloader. Use standard Composer autoloader.

### Testing Requirements

- PHPUnit tests for all core classes
- Test coverage for:
  - Permission checking logic
  - Rate limiting calculations
  - Ability registration
  - Webhook payload generation
- Mock WordPress functions using Brain Monkey or similar
- Minimum 70% code coverage

---

## Security Considerations

### Public GitHub Repository

Since this will be public:

1. **No Hardcoded Secrets**
   - No API keys, passwords, or tokens in code
   - Use WordPress options for configuration
   - Provide `.env.example` for local development

2. **Capability Checks**
   - Every ability requires `current_user_can()` check
   - Default to strictest permissions (admin only)
   - Document required capabilities in ability annotations

3. **Input Validation**
   - Use JSON Schema for input validation
   - Sanitize all inputs before use
   - Escape all outputs

4. **Application Passwords**
   - Document how to create Application Passwords
   - Recommend short-lived passwords (1-24 hours)
   - Provide revocation instructions

5. **Rate Limiting**
   - Default to conservative limits
   - Prevent abuse of write operations
   - Log suspicious activity

6. **HTTPS Required**
   - Enforce HTTPS for HTTP Transport
   - Reject connections over HTTP

---

## Lifecycle & Data Management

### Plugin Activation

**On Activation (`register_activation_hook`):**

1. **Database Schema Creation**
   - Create `wp_fa_wpmcp_activity_log` table using `dbDelta()`
   - Create `wp_fa_wpmcp_webhook_queue` table using `dbDelta()`
   - Store schema version in options: `fa_wpmcp_db_version`

2. **Default Settings**
   - Initialize `fa_wpmcp_permissions` with safe defaults:
     - Global read: enabled
     - Global write: disabled
     - All categories: inherit global
     - All abilities: inherit category

3. **Create Default Indexes**
   - Add database indexes for performance (included in `dbDelta`)

**Schema Version Example:**
```php
define('FA_WPMCP_DB_VERSION', '1.0.0');

function fa_wpmcp_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'fa_wpmcp_activity_log';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        -- ... full schema
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('fa_wpmcp_db_version', FA_WPMCP_DB_VERSION);
}
```

### Plugin Deactivation

**On Deactivation (`register_deactivation_hook`):**

1. **Preserve Data**
   - Do NOT drop tables or delete options
   - Data persists for potential reactivation

2. **Clear Transients**
   - Delete all rate limit transients
   - Clear any cached data

3. **Notification**
   - Display admin notice: "Plugin deactivated. Data preserved for reactivation."

### Plugin Uninstall

**On Uninstall (`register_uninstall_hook` or `uninstall.php`):**

1. **Admin Confirmation Required**
   - WordPress shows default uninstall warning

2. **Data Cleanup**
   - Drop `wp_fa_wpmcp_activity_log` table
   - Drop `wp_fa_wpmcp_webhook_queue` table
   - Delete all options: `fa_wpmcp_*`
   - Delete all transients: `fa_wpmcp_*`
   - Delete all user meta: `fa_wpmcp_*`

3. **Uninstall Script (`uninstall.php`):**
```php
<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fa_wpmcp_activity_log");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fa_wpmcp_webhook_queue");

// Delete options
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'fa_wpmcp_%'");

// Delete transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_fa_wpmcp_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_fa_wpmcp_%'");
```

### Schema Migrations

**Versioned Migration Strategy:**

1. **Check Version on Admin Init**
   ```php
   add_action('admin_init', 'fa_wpmcp_check_db_version');

   function fa_wpmcp_check_db_version() {
       $current = get_option('fa_wpmcp_db_version', '0.0.0');
       if (version_compare($current, FA_WPMCP_DB_VERSION, '<')) {
           fa_wpmcp_run_migrations($current, FA_WPMCP_DB_VERSION);
       }
   }
   ```

2. **Migration Runner**
   ```php
   function fa_wpmcp_run_migrations($from, $to) {
       // Example: upgrading from 1.0.0 to 1.1.0
       if (version_compare($from, '1.1.0', '<')) {
           fa_wpmcp_migrate_1_1_0();
       }

       update_option('fa_wpmcp_db_version', $to);
   }

   function fa_wpmcp_migrate_1_1_0() {
       global $wpdb;
       $table = $wpdb->prefix . 'fa_wpmcp_activity_log';

       // Add new column if doesn't exist
       $wpdb->query("ALTER TABLE $table ADD COLUMN correlation_id VARCHAR(36) AFTER id");
   }
   ```

3. **Safe Migration Principles**
   - Never drop columns (can break rollbacks)
   - Add columns as nullable initially
   - Backfill data in background task if needed
   - Log migration errors to `error_log()`

---

## Authentication & Identity Model

### AI Agent Identity Mapping

**Approach: Dedicated Service Accounts**

1. **Create WordPress User for Each AI Agent**
   - Username format: `ai-agent-{identifier}` (e.g., `ai-agent-claude`)
   - Role: Custom role `ai_agent` with specific capabilities
   - No password authentication (Application Password only)

2. **Custom AI Agent Role**
   ```php
   add_role('ai_agent', __('AI Agent', 'fa-wpmcp'), [
       'read' => true,
       'fa_wpmcp_read_abilities' => true,
       'fa_wpmcp_write_abilities' => false, // Grant explicitly if needed
   ]);
   ```

3. **Capability Mapping**

| Ability Type | Required Capability | Fallback |
|--------------|---------------------|----------|
| Read (list, get) | `fa_wpmcp_read_abilities` | `read` |
| Write (create, update) | `fa_wpmcp_write_abilities` | `edit_posts` |
| Delete | `fa_wpmcp_delete_abilities` | `delete_posts` |
| Admin (settings) | `fa_wpmcp_manage_settings` | `manage_options` |

### Application Password Management

**Creation:**
1. Admin creates Application Password for AI agent user
2. Password format: `xxxx xxxx xxxx xxxx xxxx xxxx` (24 chars, space-separated)
3. Store in password manager or MCP client config

**Rotation:**
- Recommend: Rotate every 30 days
- Process:
  1. Generate new Application Password
  2. Update MCP client config
  3. Test connection
  4. Revoke old Application Password

**Revocation:**
- Admin → Users → {AI Agent User} → Application Passwords → Revoke
- Immediate effect (invalidates active sessions)

**Auditing:**

Plugin automatically logs all Application Password authentication:

1. **Hook into WordPress authentication:**
   ```php
   add_action('application_password_did_authenticate', 'fa_wpmcp_log_app_password_auth', 10, 2);

   function fa_wpmcp_log_app_password_auth($user, $app_password) {
       global $wpdb;
       $table = $wpdb->prefix . 'fa_wpmcp_activity_log';

       $wpdb->insert($table, [
           'correlation_id' => wp_generate_uuid4(),
           'timestamp' => current_time('mysql'),
           'user_id' => $user->ID,
           'user_login' => $user->user_login,
           'ip_address' => $_SERVER['REMOTE_ADDR'],
           'ability_name' => 'auth/application-password',
           'ability_category' => 'authentication',
           'operation_type' => 'read',
           'input_data' => json_encode(['app_password_name' => $app_password['name']]),
           'output_data' => json_encode(['authenticated' => true]),
           'success' => true,
           'execution_time_ms' => 0,
       ]);
   }
   ```

2. **Track last used date:**
   - WordPress core tracks `last_used` in `wp_usermeta` for each Application Password
   - Plugin can query this via `get_user_meta($user_id, '_application_passwords')`

3. **Alert on unused passwords:**
   - Daily cron checks Application Passwords `last_used` > 90 days
   - Send admin email with list of stale passwords
   - Recommend revocation for security

### Authentication Flow Details

**REST API Authentication:**

1. **Client Sends Request**
   ```http
   POST /wp-json/fa-wpmcp/v1/mcp HTTP/1.1
   Host: example.com
   Authorization: Basic base64(username:application_password)
   Content-Type: application/json
   ```

2. **WordPress Authenticates**
   - Core WordPress REST API authentication handles Application Passwords
   - Sets `$current_user` global
   - Plugin checks if user has required capability

3. **Permission Check**
   ```php
   public function check_permission($input) {
       if (!is_user_logged_in()) {
           return new WP_Error('authentication_required', 'Authentication required', ['status' => 401]);
       }

       if (!current_user_can('fa_wpmcp_read_abilities')) {
           return new WP_Error('insufficient_permissions', 'Insufficient permissions', ['status' => 403]);
       }

       return true;
   }
   ```

4. **Error Responses**

| Status | Error Code | Message | Retry |
|--------|------------|---------|-------|
| 401 | `authentication_required` | Authentication required | No - fix credentials |
| 403 | `insufficient_permissions` | Insufficient permissions | No - grant capabilities |
| 401 | `authentication_required` | Invalid Application Password | No - regenerate password |

**Note:** WordPress REST API may also return `rest_forbidden` for compatibility. Plugin errors use standardized codes from Error Code Taxonomy.

**No Nonces Required:**
- REST API uses Application Password authentication
- Nonces are for cookie-based auth only
- MCP adapter uses stateless HTTP auth

---

## Error & Response Contract

### Standardized Error Schema

**All Abilities Return Consistent Format:**

**Success Response:**
```json
{
  "success": true,
  "data": {
    // Ability-specific output matching output_schema
  },
  "meta": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "execution_time_ms": 145,
    "timestamp": "2026-01-20T12:34:56Z"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "Invalid input parameters",
    "details": {
      "field": "title",
      "error": "Title is required"
    }
  },
  "meta": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "timestamp": "2026-01-20T12:34:56Z",
    "retry_after": null
  }
}
```

### Error Code Taxonomy

| Code | HTTP Status | Meaning | Retry? |
|------|-------------|---------|--------|
| `authentication_required` | 401 | No auth provided | No |
| `insufficient_permissions` | 403 | User lacks capability | No |
| `ability_disabled` | 403 | Ability disabled in settings | No |
| `validation_error` | 400 | Input schema validation failed | No |
| `rate_limit_exceeded` | 429 | Too many requests | Yes (after delay) |
| `internal_error` | 500 | Unexpected server error | Yes (exponential backoff) |
| `upstream_error` | 502 | WordPress core error | Yes (after delay) |
| `timeout` | 504 | Execution took too long | Yes (retry once) |
| `conflict` | 409 | Resource conflict (e.g., duplicate slug) | No |
| `not_found` | 404 | Resource doesn't exist | No |

### Validation Error Details

**When JSON Schema Validation Fails:**

```json
{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "Input validation failed",
    "details": {
      "fields": [
        {
          "field": "title",
          "error": "Required field missing"
        },
        {
          "field": "status",
          "error": "Must be one of: publish, draft, pending"
        }
      ]
    }
  }
}
```

### Partial Failure Handling

**Batch Operations:**

If an ability processes multiple items and some fail:

```json
{
  "success": true,
  "data": {
    "processed": 10,
    "succeeded": 8,
    "failed": 2,
    "results": [
      {"id": 1, "success": true, "post_id": 42},
      {"id": 2, "success": false, "error": "Duplicate slug"}
    ]
  }
}
```

**Policy:** Mark overall request as `success: true` if ANY items succeeded, include individual failures in results.

---

## Validation & Sanitization

### JSON Schema Validation

**Library:** WordPress Abilities API built-in (uses JSON Schema Draft 4 subset)

**Validation Flow:**

1. **Abilities API validates input against `input_schema` before executing**
2. **Plugin adds additional sanitization in execute callback**

```php
public static function execute(array $input): array {
    // Input already validated by Abilities API

    // Additional sanitization
    $title = sanitize_text_field($input['title']);
    $content = wp_kses_post($input['content']); // Allow safe HTML
    $status = sanitize_key($input['status']);

    // Execute with sanitized data
    $post_id = wp_insert_post([
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => $status,
    ]);

    return [
        'post_id' => (int) $post_id,
        'permalink' => esc_url(get_permalink($post_id)),
    ];
}
```

### Field-Level Sanitization

| Field Type | Sanitization Function | Max Length |
|------------|----------------------|------------|
| Text (single-line) | `sanitize_text_field()` | 255 chars |
| HTML content | `wp_kses_post()` | 65,535 chars (TEXT) |
| URL | `esc_url_raw()` | 2048 chars |
| Email | `sanitize_email()` | 320 chars |
| Integer | `absint()` or `intval()` | N/A |
| Boolean | `(bool)` | N/A |
| Key/slug | `sanitize_key()` | 255 chars |

### Payload Size Limits

**Input Limits:**
- Maximum request body: 10 MB (configurable via `fa_wpmcp_max_input_size`)
- Maximum array length: 1000 items (prevent DoS)
- Maximum string length: 65,535 chars (TEXT field limit)

**Output Limits:**
- Maximum response size: 10 MB
- Paginate large result sets (default: 100 items per page)

**Configuration:**
```php
add_filter('fa_wpmcp_max_input_size', function($size) {
    return 5 * MB_IN_BYTES; // 5 MB
});

add_filter('fa_wpmcp_max_array_items', function($count) {
    return 500; // Reduce from 1000 default
});
```

### Validation in AbstractAbility

```php
abstract class AbstractAbility {

    protected function validate_input(array $input): array {
        // Check payload size
        $size = strlen(json_encode($input));
        $max = apply_filters('fa_wpmcp_max_input_size', 10 * MB_IN_BYTES);

        if ($size > $max) {
            throw new \RuntimeException('Input payload too large');
        }

        // Check array sizes
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $max_items = apply_filters('fa_wpmcp_max_array_items', 1000);
                if (count($value) > $max_items) {
                    throw new \RuntimeException("Array '$key' exceeds maximum size");
                }
            }
        }

        return $input;
    }
}
```

---

## Webhook Security & Privacy

### Webhook Signing (HMAC)

**Generate Signature:**

```php
$secret = get_option('fa_wpmcp_webhook_secret');
$payload = json_encode($webhook_data);
$signature = hash_hmac('sha256', $payload, $secret);
```

**Send in Header:**
```http
POST /webhook/endpoint HTTP/1.1
X-FA-WPMCP-Signature: sha256=abc123...
X-FA-WPMCP-Timestamp: 1642684496
Content-Type: application/json

{...}
```

**Receiver Validation:**
```php
$received_sig = $_SERVER['HTTP_X_FA_WPMCP_SIGNATURE'];
$timestamp = $_SERVER['HTTP_X_FA_WPMCP_TIMESTAMP'];
$payload = file_get_contents('php://input');

// Prevent replay attacks (reject if >5 min old)
if (abs(time() - $timestamp) > 300) {
    die('Request too old');
}

$expected_sig = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected_sig, $received_sig)) {
    die('Invalid signature');
}
```

### Secret Storage & Rotation

**Generation:**
```php
// On first webhook URL save, generate secret
if (!get_option('fa_wpmcp_webhook_secret')) {
    $secret = bin2hex(random_bytes(32)); // 64-char hex string
    update_option('fa_wpmcp_webhook_secret', $secret);
}
```

**Rotation:**
- Admin UI button: "Rotate Webhook Secret"
- Generates new secret, stores as `fa_wpmcp_webhook_secret_new`
- For 24 hours, accepts both old and new signatures
- After 24 hours, deletes old secret

**Admin UI Display:**
```
Webhook Secret: •••••••••••• (hidden)
[Show Secret] [Rotate Secret]

Last Rotated: 2026-01-15 (5 days ago)
Recommended: Rotate every 90 days
```

### PII Redaction

**Redact Sensitive Fields in Logs/Webhooks:**

```php
class PrivacyRedactor {

    private static $sensitive_fields = [
        'password', 'token', 'api_key', 'secret',
        'ssn', 'credit_card', 'user_pass'
    ];

    public static function redact(array $data): array {
        array_walk_recursive($data, function(&$value, $key) {
            if (in_array($key, self::$sensitive_fields, true)) {
                $value = '[REDACTED]';
            }

            // Redact email addresses
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $value = '[EMAIL_REDACTED]';
            }

            // Redact IP addresses (if configured)
            if (get_option('fa_wpmcp_redact_ips') && self::is_ip($value)) {
                $value = '[IP_REDACTED]';
            }
        });

        return $data;
    }
}
```

**Configuration:**
- Admin setting: "Redact PII in logs" (default: enabled)
- Admin setting: "Redact IP addresses" (default: disabled for audit trail)

---

## Logging, Retention & Compliance

### Log Retention Policy

**Default Settings:**
- Retention period: 30 days
- Auto-delete: Enabled
- Export before delete: Optional

**Admin Configuration:**
```
Activity Log Retention
├─ Keep logs for: [30] days
├─ Auto-delete old logs: [✓]
├─ Email export before deletion: [✓]
└─ Export email: admin@example.com
```

**Cron Job:**
```php
add_action('fa_wpmcp_daily_cleanup', 'fa_wpmcp_delete_old_logs');

function fa_wpmcp_delete_old_logs() {
    global $wpdb;
    $table = $wpdb->prefix . 'fa_wpmcp_activity_log';
    $retention_days = get_option('fa_wpmcp_log_retention_days', 30);

    // Export if configured
    if (get_option('fa_wpmcp_export_before_delete')) {
        fa_wpmcp_export_old_logs($retention_days);
    }

    // Delete
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
        $retention_days
    ));
}
```

### Log Export Format

**CSV Export:**
```csv
Timestamp,User ID,User Login,IP Address,Ability,Category,Operation,Success,Error,Execution Time (ms)
2026-01-20 12:34:56,1,admin,192.168.1.100,fa-wpmcp/create-post,posts-pages,write,1,,145
```

**JSON Export:**
```json
{
  "export_date": "2026-01-20T12:34:56Z",
  "logs": [
    {
      "timestamp": "2026-01-20T12:34:56Z",
      "user": {"id": 1, "login": "admin"},
      "ip": "192.168.1.100",
      "ability": "fa-wpmcp/create-post",
      "success": true,
      "execution_time_ms": 145
    }
  ]
}
```

### Privacy Controls

**Who Can View Logs:**
- Capability required: `fa_wpmcp_view_logs` (default: `manage_options`)
- Filter logs by user:
  - Admins: See all logs
  - Non-admins: See only their own logs (if granted `fa_wpmcp_view_own_logs`)

**GDPR Compliance:**
- Export user's AI activity data (GDPR Article 15 - Right to Access)
- Delete user's AI activity data (GDPR Article 17 - Right to Erasure)

```php
add_filter('wp_privacy_personal_data_exporters', function($exporters) {
    $exporters['fa-wpmcp'] = [
        'exporter_friendly_name' => 'AI Activity Logs',
        'callback' => 'fa_wpmcp_export_user_data',
    ];
    return $exporters;
});

add_filter('wp_privacy_personal_data_erasers', function($erasers) {
    $erasers['fa-wpmcp'] = [
        'eraser_friendly_name' => 'AI Activity Logs',
        'callback' => 'fa_wpmcp_erase_user_data',
    ];
    return $erasers;
});
```

---

## Background Processing & Reliability

### Queue Storage

**Use Custom Database Table (Not Transients):**

```sql
CREATE TABLE wp_fa_wpmcp_webhook_queue (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  webhook_url VARCHAR(2048) NOT NULL,
  payload LONGTEXT NOT NULL,
  signature VARCHAR(71) NOT NULL,
  attempt_count TINYINT UNSIGNED DEFAULT 0,
  max_attempts TINYINT UNSIGNED DEFAULT 3,
  next_attempt_at DATETIME NOT NULL,
  status ENUM('pending', 'processing', 'failed', 'completed') DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  completed_at DATETIME,
  error_message TEXT,
  PRIMARY KEY (id),
  INDEX idx_status_next_attempt (status, next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Worker Mechanism

**Use Action Scheduler (Recommended):**

```php
// Require Action Scheduler via Composer
composer require woocommerce/action-scheduler

// Enqueue webhook
function fa_wpmcp_enqueue_webhook($url, $payload) {
    as_enqueue_async_action(
        'fa_wpmcp_send_webhook',
        ['url' => $url, 'payload' => $payload],
        'fa-wpmcp-webhooks'
    );
}

// Worker
add_action('fa_wpmcp_send_webhook', function($url, $payload) {
    fa_wpmcp_send_webhook_http($url, $payload);
}, 10, 2);
```

**Fallback: WP-Cron (If Action Scheduler not available):**

```php
// Schedule every 5 minutes
if (!wp_next_scheduled('fa_wpmcp_process_webhook_queue')) {
    wp_schedule_event(time(), 'five_minutes', 'fa_wpmcp_process_webhook_queue');
}

add_action('fa_wpmcp_process_webhook_queue', function() {
    fa_wpmcp_process_pending_webhooks();
});

// Add custom interval
add_filter('cron_schedules', function($schedules) {
    $schedules['five_minutes'] = [
        'interval' => 300,
        'display' => __('Every 5 Minutes', 'fa-wpmcp'),
    ];
    return $schedules;
});
```

### Retry Strategy

**Exponential Backoff:**

| Attempt | Wait Time | Total Elapsed |
|---------|-----------|---------------|
| 1 (immediate) | 0 seconds | 0 |
| 2 | 5 minutes | 5 min |
| 3 | 15 minutes | 20 min |
| 4 (final) | 60 minutes | 80 min |

```php
function fa_wpmcp_calculate_next_attempt($attempt_count) {
    $delays = [0, 300, 900, 3600]; // seconds
    return time() + ($delays[$attempt_count] ?? 3600);
}
```

### Failure Alerting

**Admin Notice:**
```php
add_action('admin_notices', function() {
    $failed = fa_wpmcp_get_failed_webhook_count();
    if ($failed > 0) {
        printf(
            '<div class="notice notice-warning"><p>%s <a href="%s">View failed webhooks</a></p></div>',
            sprintf(_n('%d webhook delivery failed.', '%d webhook deliveries failed.', $failed, 'fa-wpmcp'), $failed),
            admin_url('admin.php?page=fa-wpmcp-webhooks&status=failed')
        );
    }
});
```

**Email Alert (Optional):**
```php
// After 3 failed attempts
if ($attempt_count >= 3 && get_option('fa_wpmcp_webhook_alert_email')) {
    wp_mail(
        get_option('admin_email'),
        'Webhook Delivery Failed - FA WPMCP',
        sprintf('Webhook to %s failed after 3 attempts.', $webhook_url)
    );
}
```

---

## Admin UI & Configuration UX

### Settings Page Structure

**Menu Location:**
- Settings → FA WPMCP
- Or top-level menu: FA WPMCP (if extensive settings)

**Tabs:**
1. **Permissions** - Multi-level ability controls
2. **Activity Log** - View recent activity
3. **Rate Limiting** - Configure limits
4. **Webhooks** - Manage webhook URLs
5. **Advanced** - Retention, secrets, debug mode

### Safe Defaults

**On First Install:**
```php
[
  'global' => [
    'enable_all_read' => true,   // Safe: read-only
    'enable_all_write' => false, // Safe: no modifications
  ],
  'rate_limits' => [
    'default_read_per_minute' => 60,
    'default_write_per_minute' => 10,
  ],
  'logging' => [
    'enabled' => true,
    'retention_days' => 30,
    'redact_pii' => true,
  ],
  'webhooks' => [
    'enabled' => false, // Require explicit opt-in
    'urls' => [],
  ],
]
```

### Permissions for Admin UI Access

| Page | Capability Required | Notes |
|------|---------------------|-------|
| Permissions Settings | `manage_options` | Site admins only |
| Activity Log | `fa_wpmcp_view_logs` | Defaults to `manage_options` |
| Rate Limiting | `manage_options` | Site admins only |
| Webhooks | `manage_options` | Site admins only |
| Advanced | `manage_options` | Site admins only |

### Configuration Import/Export

**Export:**
```php
// Button: "Export Configuration"
function fa_wpmcp_export_config() {
    $config = [
        'version' => FA_WPMCP_DB_VERSION,
        'exported_at' => current_time('mysql'),
        'permissions' => get_option('fa_wpmcp_permissions'),
        'rate_limits' => get_option('fa_wpmcp_rate_limits'),
        'webhooks' => get_option('fa_wpmcp_webhooks'),
        // Exclude secrets
    ];

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="fa-wpmcp-config.json"');
    echo json_encode($config, JSON_PRETTY_PRINT);
    exit;
}
```

**Import:**
```php
// Upload JSON file
// Validate version compatibility
// Merge or replace settings (user choice)
// Show preview before applying
```

---

## Testing & Compatibility

### WordPress Version Matrix

**Minimum:** WordPress 6.9 (requires Abilities API)
**Tested Up To:** WordPress 6.9+ (update as new versions release)

**Testing Matrix:**

| WP Version | PHP Version | Status |
|------------|-------------|--------|
| 6.9 | 8.0 | ✓ Supported |
| 6.9 | 8.1 | ✓ Supported |
| 6.9 | 8.2 | ✓ Supported |
| 6.9 | 8.3 | ✓ Supported |
| 7.0 (future) | 8.1+ | ⚠️ Will test when released |

### Test Coverage

**Unit Tests (PHPUnit):**
- Target: 70% code coverage minimum
- Mock WordPress functions using Brain Monkey
- Test in isolation (no WordPress required)

```php
// Example test structure
tests/phpunit/
├── Abilities/
│   ├── AbilityRegistryTest.php
│   └── Posts/
│       └── ListPostsTest.php
├── Permissions/
│   └── PermissionManagerTest.php
├── RateLimiting/
│   └── RateLimiterTest.php
└── bootstrap.php
```

**Integration Tests:**
- Require full WordPress install
- Use WP_UnitTestCase
- Test REST API endpoints end-to-end
- Verify Abilities API integration

**E2E Tests (Optional):**
- Playwright or Cypress
- Test admin UI interactions
- Verify AI agent workflow (create post via MCP)

### Mocking Strategies

**Abilities API:**
```php
// Mock wp_register_ability
\Brain\Monkey\Functions\expect('wp_register_ability')
    ->once()
    ->with('fa-wpmcp/test-ability', \Mockery::type('array'))
    ->andReturn(true);
```

**MCP Adapter:**
```php
// Mock adapter instance
$adapter = Mockery::mock('overload:WP\MCP\McpAdapter');
$adapter->shouldReceive('create_server')->once();
$adapter->shouldReceive('register_tools')->once();
```

---

## Multisite & Compatibility Stance

### Phase 1: Single-Site Only

**Explicit Limitation:**
- Plugin will NOT activate on multisite networks
- Check in activation hook:

```php
register_activation_hook(__FILE__, function() {
    if (is_multisite()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('FA WPMCP does not support WordPress Multisite in this version.', 'fa-wpmcp'),
            __('Plugin Activation Error', 'fa-wpmcp'),
            ['back_link' => true]
        );
    }
});
```

### Future: Multisite Support (Phase 2+)

**When Implemented:**

1. **Network Admin Settings**
   - Central configuration at network level
   - Per-site overrides allowed

2. **Separate Tables Per Site**
   - `{prefix}_fa_wpmcp_activity_log` per site
   - Network-wide logs optional

3. **Activation:**
   - Network activate: Install on all sites
   - Per-site activate: Install on specific sites only

---

## Observability & Performance

### Correlation IDs

**Generate UUID for Each Request:**

```php
function fa_wpmcp_get_correlation_id() {
    static $correlation_id = null;

    if ($correlation_id === null) {
        // Check if client provided ID
        $correlation_id = $_SERVER['HTTP_X_CORRELATION_ID'] ?? wp_generate_uuid4();
    }

    return $correlation_id;
}
```

**Include in:**
- Activity log: `correlation_id` column
- Webhook payloads: `meta.correlation_id`
- Error responses: `meta.correlation_id`
- PHP error logs: Add to context

**Benefits:**
- Trace a request across logs, webhooks, and external systems
- Debug complex issues

### Query & Pagination Limits

**Default Pagination:**
- Default `per_page`: 10
- Maximum `per_page`: 100
- Maximum offset: 10,000 (prevent deep pagination)

```php
// In list abilities
$per_page = min((int) ($input['per_page'] ?? 10), 100);
$page = max(1, (int) ($input['page'] ?? 1));

if (($page - 1) * $per_page > 10000) {
    throw new \RuntimeException('Pagination offset too large. Use search/filters instead.');
}
```

### Caching Strategy

**WordPress Object Cache:**

```php
// Cache ability registry
$abilities = wp_cache_get('fa_wpmcp_registered_abilities');
if ($abilities === false) {
    $abilities = fa_wpmcp_get_registered_abilities();
    wp_cache_set('fa_wpmcp_registered_abilities', $abilities, '', 3600);
}
```

**Cache Invalidation:**
- On ability registration/deregistration
- On settings update
- Manual: "Clear Cache" button in admin

**Persistent Object Cache Recommendation:**
- Document Redis/Memcached setup for high-traffic sites
- Not required for Phase 1

### Performance Constraints

**Execution Time Limits:**
- Default: 30 seconds (PHP `max_execution_time`)
- Long-running operations: Use background jobs

**Memory Limits:**
- Default: 256 MB (WordPress recommendation)
- Large exports: Stream to file, don't load into memory

**Database Query Optimization:**
- All tables have proper indexes
- Use `$wpdb->prepare()` for security and query cache
- EXPLAIN queries during development

### Rate Limit Bypass Prevention

**No Bypass Exceptions:**
- Even admins are rate-limited
- Prevents compromised admin accounts from abuse

**Admin Override (Emergency Only):**
```php
// Temporary bypass filter (use with caution)
add_filter('fa_wpmcp_rate_limit_check', '__return_false');
```

**Monitoring:**
- Log when rate limits are hit: `rate_limit_exceeded` events
- Alert if single user hits limit 10+ times in 1 hour (potential attack)

---

## User Stories

### Story 1: Enable AI to Create Content

**As a** site administrator
**I want** an AI agent to draft blog posts
**So that** I can publish content faster

**Acceptance Criteria:**
- Admin enables `fa-wpmcp/create-post` with write permission
- AI agent authenticates via Application Password
- AI invokes `create-post` with title and content
- Post is created as draft
- Activity is logged with user, timestamp, and input
- Admin reviews draft and publishes manually

### Story 2: Query Site Content

**As an** AI agent
**I want** to search existing posts
**So that** I can avoid creating duplicate content

**Acceptance Criteria:**
- AI invokes `fa-wpmcp/list-posts` with search term
- Ability returns matching posts with title and excerpt
- Response includes pagination info
- Activity is logged as read operation
- Rate limiter allows up to 60 requests/minute

### Story 3: Bulk Permission Control

**As a** site administrator
**I want** to quickly disable all write operations
**So that** I can prevent AI changes during maintenance

**Acceptance Criteria:**
- Admin clicks "Disable All Write" in settings
- All write abilities immediately blocked
- Read abilities continue to work
- AI receives permission denied error for writes
- Admin can re-enable with one click

### Story 4: Audit Trail

**As a** site administrator
**I want** to see what AI agents have done
**So that** I can review and audit actions

**Acceptance Criteria:**
- Admin visits Activity Log page
- Sees list of all ability invocations
- Can filter by date, user, ability, success/failure
- Can export to CSV for external analysis
- Log includes input/output for each action

### Story 5: Webhook Integration

**As a** developer
**I want** to receive notifications when AI creates content
**So that** my external system can process new posts

**Acceptance Criteria:**
- Admin adds webhook URL in settings
- Configures to fire on `ability.after_execute` for posts
- AI creates post → webhook fires immediately
- External system receives JSON payload
- Failed webhooks retry 3 times

---

## MCP Server Configuration

### Server Details

```php
McpAdapter::get_instance()->create_server(
    'fa-wpmcp',                     // Server ID
    'fa-wpmcp',                     // Namespace
    'v1/mcp',                       // Route (/wp-json/fa-wpmcp/v1/mcp)
    'FA WPMCP Server',              // Display name
    'WordPress Abilities for AI Agents', // Description
    '1.0.0',                        // Version
    [HttpTransport::class],         // Transports
    ErrorLogMcpErrorHandler::class  // Error handler
);
```

### Client Configuration (Claude Desktop)

```json
{
  "mcpServers": {
    "wordpress-fa-wpmcp": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote"],
      "env": {
        "WP_API_URL": "https://your-site.com/wp-json",
        "WP_API_USERNAME": "mcp-user",
        "WP_API_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

### Tool Registration

```php
$adapter->register_tools('fa-wpmcp', [
    'fa-wpmcp/list-posts',
    'fa-wpmcp/get-post',
    'fa-wpmcp/create-post',
    'fa-wpmcp/update-post',
]);
```

---

## Implementation Phases

### Phase 1: Framework + Post Abilities (This Spec)

**Deliverables:**
- ✓ Core plugin structure
- ✓ Admin settings UI with multi-level permissions
- ✓ Permission checking system
- ✓ Activity logging system
- ✓ Rate limiting system
- ✓ Webhook notification system
- ✓ MCP server setup
- ✓ 4 example abilities: list-posts, get-post, create-post, update-post
- ✓ Unit tests for core classes
- ✓ Documentation for extending with new abilities

### Phase 2: Media & User Abilities (Future)

- Upload media ability
- List media ability
- List users ability
- Create user ability
- Get current user info ability

### Phase 3: Settings & Configuration (Future)

- Get site settings ability
- Update site options ability
- Get plugin settings ability
- Update plugin configuration ability

### Phase 4: Advanced Abilities (Future)

- Plugin management abilities
- Theme management abilities
- Menu/widget management abilities
- Advanced search across all content types

---

## Extension Pattern

Developers can add new abilities by:

1. **Create Ability Class**

```php
namespace FAWpmcp\Abilities\CustomArea;

use FAWpmcp\Abilities\AbstractAbility;

class MyCustomAbility extends AbstractAbility {

    public function get_name(): string {
        return 'fa-wpmcp/my-custom-ability';
    }

    public function get_category(): string {
        return 'custom-area';
    }

    public function get_label(): string {
        return __('My Custom Ability', 'fa-wpmcp');
    }

    // ... implement other abstract methods

    public static function execute(array $input): array {
        // Implementation
    }

    public static function check_permission(array $input): bool {
        return current_user_can('manage_options');
    }
}
```

2. **Register Category (if new)**

Hook: `wp_abilities_api_categories_init`

3. **Ability Auto-Discovery**

Place class in `src/Abilities/<Category>/` → automatically registered

4. **Configure Permissions**

Admin can enable/disable via settings UI

---

## Open Questions

1. **Database Table Creation:** Should activity log table be created on plugin activation or first use?
   - **Recommendation:** On activation for clarity

2. **Webhook Queue Storage:** Transients or custom table?
   - **Recommendation:** Custom table for reliability

3. **Rate Limit Granularity:** Per user only, or per user + per IP?
   - **Recommendation:** Both (configurable)

4. **Log Retention:** Default days before auto-delete?
   - **Recommendation:** 30 days default, configurable

5. **Admin UI Library:** Use WordPress native components or React?
   - **Recommendation:** WordPress native for simplicity

---

## Success Metrics

### Phase 1 Complete When:

- ✓ Admin can enable/disable abilities at global, category, and individual levels
- ✓ AI agent can successfully list and create posts via MCP
- ✓ All AI actions are logged in activity log
- ✓ Rate limiting prevents excessive requests
- ✓ Webhooks fire on configured events
- ✓ Unit tests achieve 70%+ coverage
- ✓ Documentation explains how to add new abilities
- ✓ Plugin passes WordPress coding standards (PHPCS)

---

## References

- [Abilities API Documentation](https://developer.wordpress.org/apis/abilities-api/)
- [MCP Adapter Repository](https://github.com/WordPress/mcp-adapter)
- [External Research Document](../handoffs/build-20260120-wp-abilities-plugin/external-research.md)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)

---

**Next Steps:**
1. Create implementation plan from this spec
2. Validate technical choices against best practices
3. Begin framework implementation
4. Implement example Post abilities
5. Write unit tests
6. Document extension pattern
