# Architecture Guide

This guide provides a detailed overview of the FA WPMCP plugin architecture, design principles, and component interactions.

## Design Principles

The plugin is built following professional software engineering practices:

### Test-Driven Development (TDD)
- Tests written before implementation
- Red-Green-Refactor cycle
- 357 tests, 71.13% coverage
- Unit tests with Brain\Monkey for WordPress mocking

### Functional Programming (FP)
- Pure functions where possible
- Immutable value objects with `readonly` properties
- Separation of data and behavior
- Composable pipeline architecture

### SOLID Principles
- **Single Responsibility:** Each class has one clear purpose
- **Open/Closed:** Extensible through interfaces (AbilityInterface, RateLimitStore, WebhookQueue)
- **Liskov Substitution:** Implementations interchangeable via interfaces
- **Interface Segregation:** Small, focused interfaces
- **Dependency Injection:** Constructor injection, no globals in classes

### Type Safety
- PHP 8.1+ features (`readonly`, enums, union types)
- Strict types (`declare(strict_types=1)`) in all files
- Full type declarations on parameters and returns
- PHPStan level 8 static analysis

## System Overview

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      WordPress Core                          │
└────────────────────────────┬────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                   FA WPMCP Plugin Core                       │
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

### Request Flow

```
1. Request arrives (REST API, function call, etc.)
   ↓
2. AbilityRegistry lookups ability
   ↓
3. AbilityExecutor starts pipeline
   ↓
4. PermissionChecker validates access
   ↓
5. RateLimiter checks quota
   ↓
6. Ability executes (do_execute)
   ↓
7. ActivityLogger records result
   ↓
8. WebhookManager notifies subscribers
   ↓
9. Result returned to caller
```

### Event Flow (Webhooks)

```
Ability Execution
   ↓
   ├─ Before Execute Event → WebhookService
   ├─ After Execute Event  → WebhookService
   └─ Failed Execute Event → WebhookService
                                    ↓
                            WebhookManager
                                    ↓
                            DatabaseWebhookQueue
                                    ↓
                            WebhookScheduler
                                    ↓
                     [Action Scheduler or WP-Cron]
                                    ↓
                         HTTP POST to subscribers
```

## Core Components

### 1. Ability Framework

**Location:** `src/Abilities/`

The ability framework provides an extensible system for registering and executing WordPress operations.

#### AbilityRegistry

**Purpose:** Central registry for all abilities

**Responsibilities:**
- Register abilities
- Lookup abilities by name
- List all registered abilities
- Category-based organization

**Key Methods:**
```php
register(AbilityInterface $ability): void
get(string $name): ?AbilityInterface
all(): array
by_category(string $category): array
```

#### AbilityExecutor

**Purpose:** Pipeline orchestration with middleware

**Responsibilities:**
- Execute abilities through pipeline
- Coordinate middleware (permissions, rate limiting, logging)
- Handle execution results
- Trigger webhook events

**Execution Pipeline:**
1. Permission check
2. Rate limit check
3. Fire "before execute" webhook
4. Execute ability
5. Log activity
6. Fire "after execute" or "failed execute" webhook
7. Return result

**Key Methods:**
```php
execute(string $ability_name, array $input): ExecutionResult
```

#### AbstractAbility

**Purpose:** Base class for ability implementations

**Responsibilities:**
- Define ability metadata (name, category, label, description)
- Provide JSON Schema for input/output validation
- Specify required WordPress capability
- Implement execution logic in `do_execute()`

**Abstract Methods:**
```php
get_name(): string
get_category(): string
get_label(): string
get_description(): string
get_input_schema(): array
get_output_schema(): array
get_required_capability(): string
get_operation_type(): string  // READ, WRITE, DELETE
do_execute(array $input): array
```

#### ExecutionPipeline

**Purpose:** Composable middleware pipeline

**Responsibilities:**
- Chain middleware together
- Pass context through pipeline
- Handle early termination

**Pattern:** Chain of Responsibility

### 2. Permission System

**Location:** `src/Permissions/`

Multi-level access control with global, category, and ability-level permissions.

#### PermissionChecker

**Purpose:** Permission validation logic

**Responsibilities:**
- Check global permissions (read/write)
- Check category-level permissions
- Check ability-level permissions
- Verify WordPress user capabilities

**Permission Hierarchy:**
```
Ability-level (most specific)
    ↓
Category-level
    ↓
Global-level (least specific)
```

**Key Methods:**
```php
can_execute(string $ability_name, string $operation_type, string $required_capability): bool
```

#### PermissionSettings

**Purpose:** Immutable value object for permissions

**Characteristics:**
- `readonly` properties
- Immutable (return new instances on modification)
- Type-safe
- No WordPress dependencies

**Key Methods:**
```php
with_global_read(bool $read): self
with_global_write(bool $write): self
with_category_permission(string $category, bool $read, bool $write): self
with_ability_permission(string $ability, bool $allowed): self
```

#### OptionsPermissionSettings

**Purpose:** WordPress options persistence layer

**Responsibilities:**
- Load permissions from `fa_wpmcp_permissions` option
- Save permissions to WordPress options
- Bridge between WordPress and domain logic

### 3. Rate Limiting

**Location:** `src/RateLimiting/`

Dual-track rate limiting (per-user and per-IP) with configurable windows.

#### RateLimiter

**Purpose:** Rate limit enforcement

**Responsibilities:**
- Check if request exceeds limits
- Record request attempts
- Calculate remaining quota
- Track per-user and per-IP separately

**Tracking:**
- User ID (WordPress authenticated users)
- IP Address (anonymous requests)
- Time windows: minute, hour, day

**Key Methods:**
```php
check_limit(string $ability_name, ?int $user_id, string $ip_address): RateLimitResult
record_request(string $ability_name, ?int $user_id, string $ip_address): void
```

#### RateLimitStore Interface

**Purpose:** Abstraction for storage backends

**Implementations:**
- `TransientRateLimitStore` - WordPress transients

**Key Methods:**
```php
get_count(string $key): int
increment(string $key, int $ttl): void
```

#### RateLimitConfig Interface

**Purpose:** Configuration abstraction

**Implementations:**
- `OptionsRateLimitConfig` - WordPress options

**Key Methods:**
```php
get_limits(string $ability_name): array
```

#### RateLimitCalculator

**Purpose:** Pure function calculations

**Characteristics:**
- No state
- No side effects
- Testable in isolation

**Key Methods:**
```php
calculate_ttl(string $window): int
is_limit_exceeded(int $count, int $limit): bool
```

### 4. Activity Logging

**Location:** `src/Logging/`

Comprehensive audit trail with correlation IDs, PII redaction, and database persistence.

#### ActivityLogger

**Purpose:** Logging coordinator

**Responsibilities:**
- Build log entries
- Redact PII from input/output
- Persist to database
- Generate correlation IDs

**Key Methods:**
```php
log_execution(string $ability_name, array $input, ?array $output, bool $success, ?string $error, ?int $execution_time_ms): void
```

#### LogRepository

**Purpose:** Database persistence layer

**Responsibilities:**
- Insert log entries
- Query logs by correlation ID, ability, user, date range
- Delete old logs (retention policy)
- Handle database schema

**Key Methods:**
```php
save(LogEntry $entry): void
find_by_correlation_id(string $correlation_id): array
delete_older_than(int $days): int
```

#### LogEntryBuilder

**Purpose:** Fluent builder pattern

**Characteristics:**
- Fluent interface
- Immutable after build
- Type-safe construction

**Example:**
```php
$entry = LogEntryBuilder::create()
    ->with_ability_name('posts.get')
    ->with_input(['id' => 1])
    ->with_output(['title' => 'Hello'])
    ->with_success(true)
    ->build();
```

#### LogEntry

**Purpose:** Immutable value object

**Characteristics:**
- `readonly` properties
- Rich domain object
- No WordPress dependencies

**Properties:**
```php
readonly string $correlation_id;
readonly ?int $user_id;
readonly string $ip_address;
readonly string $ability_name;
readonly ?string $input_json;
readonly ?string $output_json;
readonly bool $success;
readonly ?string $error_message;
readonly ?int $execution_time_ms;
readonly string $created_at;
```

### 5. Webhooks

**Location:** `src/Webhooks/`

Event-driven notifications with HMAC signing, retry logic, and queue-based processing.

#### WebhookService

**Purpose:** WordPress hooks integration

**Responsibilities:**
- Register WordPress action hooks
- Listen for ability events
- Trigger webhook delivery

**Events:**
- `fa_wpmcp_ability_before_execute`
- `fa_wpmcp_ability_after_execute`
- `fa_wpmcp_ability_execution_failed`

#### WebhookManager

**Purpose:** Queue processing and delivery

**Responsibilities:**
- Queue webhooks for delivery
- Process queued webhooks
- Retry failed deliveries with exponential backoff
- Verify webhook signatures

**Retry Logic:**
- Max 3 attempts
- Exponential backoff: 5min, 15min, 30min
- Mark as failed after max attempts

**Key Methods:**
```php
queue_webhook(string $url, array $payload, string $signature): void
process_queue(): void
retry_failed_webhook(int $webhook_id): void
```

#### WebhookQueue Interface

**Purpose:** Queue storage abstraction

**Implementations:**
- `DatabaseWebhookQueue` - MySQL/MariaDB

**Key Methods:**
```php
enqueue(string $url, array $payload, string $signature): void
get_pending(): array
mark_sent(int $id): void
mark_failed(int $id, string $error): void
```

#### WebhookScheduler

**Purpose:** Action Scheduler/WP-Cron integration

**Responsibilities:**
- Schedule webhook processing
- Schedule retry attempts
- Fallback to WP-Cron if Action Scheduler unavailable

**Hooks:**
- `fa_wpmcp_process_webhook_queue` (recurring)
- `fa_wpmcp_cleanup_old_logs` (daily)

#### SignatureGenerator

**Purpose:** HMAC signing and verification

**Algorithm:** HMAC-SHA256

**Key Methods:**
```php
generate(string $payload, string $secret): string
verify(string $payload, string $signature, string $secret): bool
```

#### PayloadBuilder

**Purpose:** Webhook payload construction

**Responsibilities:**
- Build standardized payload structure
- Include correlation ID for tracing
- Add timestamp and event type
- Include user and IP context

**Payload Structure:**
```json
{
  "event": "ability.after_execute",
  "timestamp": "2026-01-21T12:00:00+00:00",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "ability": {
    "name": "posts.get",
    "category": "posts"
  },
  "input": { "id": 1 },
  "output": { "title": "Hello" },
  "user_id": 42,
  "ip_address": "203.0.113.1"
}
```

## Database Schema

### Activity Logs Table

**Table Name:** `{$wpdb->prefix}fa_wpmcp_activity_log`

```sql
CREATE TABLE fa_wpmcp_activity_log (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  correlation_id varchar(36) NOT NULL,
  user_id bigint(20) unsigned DEFAULT NULL,
  ip_address varchar(45) NOT NULL,
  ability_name varchar(255) NOT NULL,
  input longtext,
  output longtext,
  success tinyint(1) NOT NULL,
  error_message text DEFAULT NULL,
  execution_time_ms int(11) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY correlation_id (correlation_id),
  KEY ability_name (ability_name),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Indexes:**
- `correlation_id` - For tracing related operations
- `ability_name` - For filtering by ability
- `created_at` - For time-based queries and cleanup

**Retention:** Configurable via `FA_WPMCP_LOG_RETENTION_DAYS` constant

### Webhook Queue Table

**Table Name:** `{$wpdb->prefix}fa_wpmcp_webhook_queue`

```sql
CREATE TABLE fa_wpmcp_webhook_queue (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  url text NOT NULL,
  payload longtext NOT NULL,
  signature varchar(255) NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  attempts int(11) NOT NULL DEFAULT 0,
  next_attempt_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY status (status),
  KEY next_attempt_at (next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Indexes:**
- `status` - For fetching pending webhooks
- `next_attempt_at` - For retry scheduling

**Status Values:**
- `pending` - Awaiting delivery
- `sent` - Successfully delivered
- `failed` - Exceeded max retry attempts

## Design Patterns

### Repository Pattern

**Used In:** LogRepository, WebhookQueue

**Benefits:**
- Abstraction over data access
- Testable without database
- Swappable storage backends

### Builder Pattern

**Used In:** LogEntryBuilder, PayloadBuilder

**Benefits:**
- Fluent API for complex object construction
- Optional parameters without constructors
- Validation at build time

### Strategy Pattern

**Used In:** RateLimitStore, WebhookQueue

**Benefits:**
- Pluggable storage backends
- Easy to add new implementations
- Test with in-memory implementations

### Value Object Pattern

**Used In:** LogEntry, PermissionSettings, RateLimitResult

**Benefits:**
- Immutability guarantees
- Type safety
- No accidental modification

### Pipeline Pattern

**Used In:** ExecutionPipeline, AbilityExecutor

**Benefits:**
- Composable middleware
- Clear separation of concerns
- Easy to add/remove steps

## Dependency Graph

```
Plugin (root)
  ├─ AbilityRegistry
  │   └─ AbstractAbility (multiple instances)
  ├─ AbilityExecutor
  │   ├─ AbilityRegistry
  │   ├─ PermissionChecker
  │   ├─ RateLimiter
  │   ├─ ActivityLogger
  │   └─ WebhookManager
  ├─ PermissionChecker
  │   └─ PermissionSettings
  ├─ RateLimiter
  │   ├─ RateLimitStore
  │   └─ RateLimitConfig
  ├─ ActivityLogger
  │   └─ LogRepository
  └─ WebhookManager
      ├─ WebhookQueue
      └─ SignatureGenerator
```

**Dependency Direction:** Always inward (core has no dependencies on outer layers)

## Extension Points

### Adding New Abilities

1. Extend `AbstractAbility`
2. Implement required abstract methods
3. Register with `AbilityRegistry`

**Example:**
```php
class GetPostAbility extends AbstractAbility {
    public function get_name(): string {
        return 'posts.get';
    }

    protected function do_execute(array $input): array {
        $post = get_post($input['id']);
        return ['title' => $post->post_title];
    }
}

$registry->register(new GetPostAbility());
```

### Custom Storage Backends

Implement `RateLimitStore` or `WebhookQueue` interfaces:

```php
class RedisRateLimitStore implements RateLimitStore {
    public function get_count(string $key): int {
        return $this->redis->get($key) ?? 0;
    }

    public function increment(string $key, int $ttl): void {
        $this->redis->incr($key);
        $this->redis->expire($key, $ttl);
    }
}
```

### Custom Middleware

Add new pipeline steps in `AbilityExecutor`:

```php
// Before execution
do_action('fa_wpmcp_custom_check', $ability_name, $input);

// Execute ability
$result = $ability->execute($input);

// After execution
do_action('fa_wpmcp_custom_logging', $result);
```

### Webhook Event Subscribers

Add webhook URLs via options or filters:

```php
add_filter('fa_wpmcp_webhook_urls', function($urls) {
    $urls[] = 'https://my-service.com/webhook';
    return $urls;
});
```

## Testing Architecture

### Unit Tests

**Framework:** PHPUnit + Brain\Monkey

**Coverage:** 71.13% (357 tests, 794 assertions)

**Approach:**
- Mock WordPress functions with Brain\Monkey
- Test classes in isolation
- Pure functions tested without mocks

**Example:**
```php
use Brain\Monkey\Functions;

public function test_permission_check() {
    Functions\when('current_user_can')->justReturn(true);

    $checker = new PermissionChecker($settings);
    $this->assertTrue($checker->can_execute('posts.get', 'READ', 'read'));
}
```

### Test Organization

```
tests/
└── phpunit/
    ├── Abilities/
    ├── Permissions/
    ├── RateLimiting/
    ├── Logging/
    ├── Webhooks/
    └── ValueObjects/
```

### Mocking Strategy

**WordPress Functions:** Brain\Monkey
**Database ($wpdb):** PHPUnit mocks
**External HTTP:** Brain\Monkey\Functions\when

## Performance Considerations

### Rate Limiting

- Transient storage (in-memory cache)
- Efficient key generation
- Automatic expiration

### Activity Logging

- Async logging (after response sent)
- Indexed queries
- Automatic cleanup

### Webhooks

- Queue-based delivery (non-blocking)
- Batched processing
- Exponential backoff

### Database

- Prepared statements (SQL injection protection)
- Indexed columns for common queries
- JSON storage for flexible data

## Security Architecture

### Input Validation

- JSON Schema validation
- Type checking (strict types)
- WordPress sanitization

### Authentication

- WordPress capability checks
- User context in all operations
- IP address tracking

### Rate Limiting

- Per-user limits
- Per-IP limits (anonymous)
- Configurable thresholds

### Webhook Security

- HMAC-SHA256 signatures
- Secret key management
- Signature verification on receipt

### Data Privacy

- PII redaction in logs (via PrivacyRedactor)
- Configurable log retention
- Data preservation option on uninstall

## Related Documentation

- [Configuration Guide](CONFIGURATION.md) - Options, filters, and constants
- [MCP Integration Guide](MCP_DOCUMENTATION.md) - MCP server setup
- [README](../README.md) - Overview and quick start
