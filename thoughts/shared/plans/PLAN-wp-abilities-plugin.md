# Implementation Plan: FA-WPMCP WordPress Plugin

**Generated:** 2026-01-20
**Revised:** 2026-01-20 (TDD + FP Emphasis)
**Phase:** 1 - Framework + 4 Example Post Abilities
**Target Coverage:** 85% minimum
**Specification:** `/thoughts/shared/specs/wp-abilities-plugin-spec.md`

---

## TDD + Functional Programming Approach

This plan follows Test-Driven Development with Functional Programming principles:

- **TDD:** Tests written first (Red), implementation follows (Green), then refactor
- **FP:** Pure functions, immutability, composition, type safety where possible
- **Pragmatic:** Balanced for WordPress context (hooks, DB, logging have side effects)

### TDD Cycle (Red-Green-Refactor)

Every implementation follows this pattern:

1. **RED:** Write failing tests that define expected behavior
2. **GREEN:** Write minimal code to make tests pass
3. **REFACTOR:** Clean up code while keeping tests green

### FP Patterns Applied

| Pattern | Application |
|---------|-------------|
| **Pure Functions** | Business logic: permission checks, rate limit calculations, validation |
| **Immutability** | Value objects with `readonly` properties (PHP 8.0+) |
| **Higher-Order Functions** | `array_map`, `array_filter`, `array_reduce` for transformations |
| **Type Safety** | `declare(strict_types=1)`, return type declarations, union types |
| **No Global State** | Dependency injection, explicit parameter passing |
| **Function Composition** | Pipeline pattern for request processing |

### WordPress Pragmatism

Side effects are acceptable in:
- Database operations (inherently stateful)
- WordPress hook integration (required for plugin architecture)
- Logging (side effect by nature)
- HTTP responses (external communication)

Focus FP principles on: calculations, transformations, validations, business rules.

---

## Goal

Build a WordPress plugin that exposes WordPress functionality to AI agents via the Abilities API and MCP Adapter. Phase 1 delivers the complete framework infrastructure plus 4 example Post abilities (list, get, create, update) as a reference implementation.

## Branching Strategy

Use classic git-flow branching and release/hotfix workflows (see `git-flow` skill). Standard branches: `main`, `develop`, `feature/*`, `release/*`, `hotfix/*`.

---

## Research Summary

### Key Findings from External Research

1. **Abilities API** (WordPress 6.9+)
   - Register abilities during `wp_abilities_api_init` hook
   - Register categories first during `wp_abilities_api_categories_init`
   - JSON Schema Draft 4 subset for validation
   - `show_in_rest: true` required for REST exposure

2. **MCP Adapter** (v0.3+)
   - Create server via `McpAdapter::get_instance()->create_server()`
   - Register tools with `$adapter->register_tools()`
   - HTTP Transport primary, Application Password auth
   - Respects permission callbacks automatically

3. **Technical Decisions (from spec)**
   - Plugin slug: `fa-wpmcp`
   - Namespace: `FAWpmcp\`
   - PHP 8.1+, WordPress 6.9+ (8.1 required for readonly properties)
   - Standard Composer autoloader (NOT Jetpack)
   - Action Scheduler for background jobs
   - Custom DB tables for activity log and webhook queue

---

## Value Objects (FP Foundation)

Before implementation phases, define immutable value objects used throughout:

### Core Value Objects

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\ValueObjects;

/**
 * Immutable permission settings.
 */
final readonly class PermissionSettings {
    public function __construct(
        public bool $globalReadEnabled,
        public bool $globalWriteEnabled,
        public array $categorySettings,
        public array $abilitySettings,
    ) {}

    public function withGlobalRead(bool $enabled): self {
        return new self($enabled, $this->globalWriteEnabled, $this->categorySettings, $this->abilitySettings);
    }
}

/**
 * Immutable rate limit configuration.
 */
final readonly class RateLimit {
    public function __construct(
        public int $requestsPerMinute,
        public int $requestsPerHour,
        public string $ability,
    ) {}
}

/**
 * Immutable rate limit check result.
 */
final readonly class RateLimitResult {
    public function __construct(
        public bool $allowed,
        public int $retryAfter,
        public string $limitType,
    ) {}

    public static function allowed(): self {
        return new self(true, 0, 'none');
    }

    public static function denied(int $retryAfter, string $limitType): self {
        return new self(false, $retryAfter, $limitType);
    }
}

/**
 * Immutable log entry.
 */
final readonly class LogEntry {
    public function __construct(
        public string $correlationId,
        public int $userId,
        public string $userLogin,
        public string $ipAddress,
        public string $abilityName,
        public string $abilityCategory,
        public string $operationType,
        public ?array $inputData,
        public ?array $outputData,
        public bool $success,
        public ?string $errorMessage,
        public int $executionTimeMs,
    ) {}
}

/**
 * Result container (Success or Failure).
 */
final readonly class Result {
    private function __construct(
        public bool $isSuccess,
        public mixed $value,
        public ?string $errorCode,
        public ?string $errorMessage,
    ) {}

    public static function success(mixed $value): self {
        return new self(true, $value, null, null);
    }

    public static function failure(string $code, string $message): self {
        return new self(false, null, $code, $message);
    }

    public function map(callable $fn): self {
        return $this->isSuccess ? self::success($fn($this->value)) : $this;
    }

    public function flatMap(callable $fn): self {
        return $this->isSuccess ? $fn($this->value) : $this;
    }
}
```

---

## Implementation Phases

### Phase 1.1: Project Scaffolding
**Duration:** 1-2 hours
**Dependencies:** None

**Files to create:**
- `fa-wpmcp.php` - Main plugin file
- `composer.json` - Dependencies and autoloading
- `uninstall.php` - Clean uninstall handler
- `.phpcs.xml` - WordPress coding standards config
- `phpunit.xml.dist` - PHPUnit configuration
- `tests/phpunit/bootstrap.php` - Test bootstrapping

#### Phase 1.1a: Write Scaffolding Tests (RED)

**Tests to create:**
- `tests/phpunit/PluginActivationTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class PluginActivationTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_plugin_defines_version_constant(): void {
        // After including main plugin file, FA_WPMCP_VERSION should be defined
        $this->assertTrue(defined('FA_WPMCP_VERSION'));
    }

    public function test_plugin_defines_path_constant(): void {
        $this->assertTrue(defined('FA_WPMCP_PATH'));
    }

    public function test_autoloader_resolves_plugin_class(): void {
        $this->assertTrue(class_exists('FAWpmcp\\Plugin'));
    }
}
```

#### Phase 1.1b: Implement Scaffolding (GREEN)

**Steps:**

1. Create main plugin file with header:
   ```php
   <?php
   declare(strict_types=1);
   /**
    * Plugin Name: FA WPMCP
    * Plugin URI: https://github.com/featherart/fa-wpmcp
    * Description: Exposes WordPress functionality to AI agents via Abilities API and MCP Adapter
    * Version: 1.0.0
    * Requires at least: 6.9
    * Requires PHP: 8.0
    * Author: Feather Art
    * Text Domain: fa-wpmcp
    * Domain Path: /languages
    */
   ```

2. Create composer.json with PSR-4 autoloading:
   ```json
   {
     "name": "featherart/fa-wpmcp",
     "type": "wordpress-plugin",
     "autoload": {
       "psr-4": {
         "FAWpmcp\\": "src/"
       }
     }
   }
   ```

3. Configure PHPCS for WordPress standards

4. Set up PHPUnit with Brain Monkey mocking

#### Phase 1.1c: Refactor Scaffolding (REFACTOR)

- Ensure all files follow WordPress coding standards
- Verify PHPCS passes without errors
- Confirm test bootstrapping works

**Acceptance criteria:**
- [ ] All scaffolding tests pass (GREEN)
- [ ] `composer install` succeeds
- [ ] Plugin activates in WordPress without errors
- [ ] Autoloading works (`FAWpmcp\Plugin` resolves)
- [ ] PHPCS runs without config errors

---

### Phase 1.2: Core Plugin Class
**Duration:** 1-2 hours
**Dependencies:** Phase 1.1

**Files to create:**
- `src/Plugin.php` - Main plugin orchestration
- `src/ValueObjects/Result.php` - Result container

#### Phase 1.2a: Write Plugin Class Tests (RED)

**Tests to create:**
- `tests/phpunit/PluginTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests;

use FAWpmcp\Plugin;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class PluginTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_get_instance_returns_singleton(): void {
        $instance1 = Plugin::get_instance();
        $instance2 = Plugin::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    public function test_init_registers_hooks(): void {
        Functions\expect('add_action')
            ->atLeast()->once();

        $plugin = Plugin::get_instance();
        $plugin->init();
    }

    public function test_activation_hook_fires_correctly(): void {
        Functions\expect('register_activation_hook')
            ->once();

        // Plugin bootstrap code registers activation hook
    }
}
```

#### Phase 1.2b: Implement Plugin Class (GREEN)

**Steps:**

1. Create Plugin class with dependency injection support:
   ```php
   <?php
   declare(strict_types=1);

   namespace FAWpmcp;

   final class Plugin {
       private static ?Plugin $instance = null;

       /** @var array<string, object> Service container */
       private array $services = [];

       public static function get_instance(): Plugin {
           if (self::$instance === null) {
               self::$instance = new self();
           }
           return self::$instance;
       }

       private function __construct() {}

       public function init(): void {
           // Hook registrations - services injected
       }

       public function register_service(string $name, object $service): void {
           $this->services[$name] = $service;
       }

       public function get_service(string $name): ?object {
           return $this->services[$name] ?? null;
       }
   }
   ```

2. Add a runtime Abilities API availability check and show an admin notice if unavailable

3. Wire Plugin to main plugin file

4. Add activation/deactivation hooks

5. Add version constants

#### Phase 1.2c: Refactor Plugin Class (REFACTOR)

- Extract hook registration to separate method
- Ensure no direct global state access
- Add PHPDoc blocks

**Acceptance criteria:**
- [ ] All Plugin tests pass (GREEN)
- [ ] Plugin class instantiates once (singleton)
- [ ] Dependency injection works
- [ ] Activation hook fires on plugin activation
- [ ] Deactivation hook fires on deactivation

---

### Phase 1.3: Database Schema & Migrations
**Duration:** 2-3 hours
**Dependencies:** Phase 1.2

**Files to create:**
- `src/Database/Schema.php` - Table creation (pure functions for SQL generation)
- `src/Database/Migrator.php` - Version migrations

#### Phase 1.3a: Write Database Tests (RED)

**Tests to create:**
- `tests/phpunit/Database/SchemaTest.php`
- `tests/phpunit/Database/MigratorTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Database;

use FAWpmcp\Database\Schema;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase {
    public function test_get_activity_log_schema_returns_valid_sql(): void {
        $sql = Schema::getActivityLogSchema('wp_');

        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('wp_fa_wpmcp_activity_log', $sql);
        $this->assertStringContainsString('correlation_id', $sql);
        $this->assertStringContainsString('PRIMARY KEY', $sql);
    }

    public function test_get_webhook_queue_schema_returns_valid_sql(): void {
        $sql = Schema::getWebhookQueueSchema('wp_');

        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('wp_fa_wpmcp_webhook_queue', $sql);
    }

    public function test_schema_uses_provided_prefix(): void {
        $sql = Schema::getActivityLogSchema('custom_prefix_');

        $this->assertStringContainsString('custom_prefix_fa_wpmcp_activity_log', $sql);
    }
}

class MigratorTest extends TestCase {
    public function test_should_migrate_returns_true_when_version_lower(): void {
        $result = Migrator::shouldMigrate('0.9.0', '1.0.0');
        $this->assertTrue($result);
    }

    public function test_should_migrate_returns_false_when_version_equal(): void {
        $result = Migrator::shouldMigrate('1.0.0', '1.0.0');
        $this->assertFalse($result);
    }

    public function test_get_migrations_returns_ordered_array(): void {
        $migrations = Migrator::getMigrations('0.9.0', '1.1.0');

        $this->assertIsArray($migrations);
        // Verify migrations are in version order
    }
}
```

#### Phase 1.3b: Implement Database Schema (GREEN)

**FP Approach: Pure functions for SQL generation**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Database;

/**
 * Pure functions for generating SQL schemas.
 * No side effects - returns SQL strings for dbDelta.
 */
final class Schema {
    /**
     * Generate activity log table SQL.
     * Pure function: same prefix always produces same SQL.
     */
    public static function getActivityLogSchema(string $prefix): string {
        $table = "{$prefix}fa_wpmcp_activity_log";

        return "CREATE TABLE {$table} (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    /**
     * Generate webhook queue table SQL.
     */
    public static function getWebhookQueueSchema(string $prefix): string {
        // Similar pure function approach
    }

    /**
     * Get all schema SQL statements.
     * Pure function: returns array of SQL strings.
     */
    public static function getAllSchemas(string $prefix): array {
        return [
            self::getActivityLogSchema($prefix),
            self::getWebhookQueueSchema($prefix),
        ];
    }
}
```

**Migrator with pure version comparison:**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Database;

final class Migrator {
    /**
     * Pure function: determine if migration needed.
     */
    public static function shouldMigrate(string $currentVersion, string $targetVersion): bool {
        return version_compare($currentVersion, $targetVersion, '<');
    }

    /**
     * Pure function: get migrations between versions.
     */
    public static function getMigrations(string $fromVersion, string $toVersion): array {
        $allMigrations = self::getAllMigrations();

        return array_filter(
            $allMigrations,
            fn(array $migration) =>
                version_compare($fromVersion, $migration['version'], '<') &&
                version_compare($migration['version'], $toVersion, '<=')
        );
    }

    private static function getAllMigrations(): array {
        return [
            ['version' => '1.0.0', 'callback' => 'migrate_1_0_0'],
            ['version' => '1.1.0', 'callback' => 'migrate_1_1_0'],
        ];
    }
}
```

#### Phase 1.3c: Refactor Database (REFACTOR)

- Extract common SQL patterns
- Add comprehensive PHPDoc
- Ensure all SQL generation is side-effect free

**Acceptance criteria:**
- [ ] All database tests pass (GREEN)
- [ ] Tables created on first activation
- [ ] Tables not recreated on reactivation
- [ ] Schema version stored in wp_options
- [ ] Future migrations can run sequentially

---

### Phase 1.4: Permission System
**Duration:** 3-4 hours
**Dependencies:** Phase 1.2

**Files to create:**
- `src/ValueObjects/PermissionSettings.php` - Immutable settings
- `src/Permissions/PermissionChecker.php` - Pure function permission logic
- `src/Permissions/PermissionManager.php` - Orchestration (side effects)
- `src/Permissions/Settings.php` - Get/set permission options
- `src/Permissions/Capabilities.php` - Custom capabilities registration

#### Phase 1.4a: Write Permission Tests (RED)

**Tests to create:**
- `tests/phpunit/ValueObjects/PermissionSettingsTest.php`
- `tests/phpunit/Permissions/PermissionCheckerTest.php`
- `tests/phpunit/Permissions/SettingsTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\ValueObjects;

use FAWpmcp\ValueObjects\PermissionSettings;
use PHPUnit\Framework\TestCase;

class PermissionSettingsTest extends TestCase {
    public function test_is_immutable(): void {
        $settings = new PermissionSettings(
            globalReadEnabled: true,
            globalWriteEnabled: false,
            categorySettings: [],
            abilitySettings: [],
        );

        // Attempting to modify should create new instance
        $newSettings = $settings->withGlobalRead(false);

        $this->assertTrue($settings->globalReadEnabled);
        $this->assertFalse($newSettings->globalReadEnabled);
        $this->assertNotSame($settings, $newSettings);
    }
}

namespace FAWpmcp\Tests\Permissions;

use FAWpmcp\Permissions\PermissionChecker;
use FAWpmcp\ValueObjects\PermissionSettings;
use FAWpmcp\ValueObjects\Result;
use PHPUnit\Framework\TestCase;

class PermissionCheckerTest extends TestCase {
    public function test_global_read_disabled_blocks_all_reads(): void {
        $settings = new PermissionSettings(
            globalReadEnabled: false,
            globalWriteEnabled: true,
            categorySettings: [],
            abilitySettings: [],
        );

        $result = PermissionChecker::check($settings, 'fa-wpmcp/list-posts', 'read');

        $this->assertFalse($result->isSuccess);
        $this->assertEquals('ability_disabled', $result->errorCode);
    }

    public function test_global_write_disabled_blocks_all_writes(): void {
        $settings = new PermissionSettings(
            globalReadEnabled: true,
            globalWriteEnabled: false,
            categorySettings: [],
            abilitySettings: [],
        );

        $result = PermissionChecker::check($settings, 'fa-wpmcp/create-post', 'write');

        $this->assertFalse($result->isSuccess);
    }

    public function test_category_write_disabled_blocks_category_writes(): void {
        $settings = new PermissionSettings(
            globalReadEnabled: true,
            globalWriteEnabled: true,
            categorySettings: ['posts-pages' => ['enable_read' => true, 'enable_write' => false]],
            abilitySettings: [],
        );

        $result = PermissionChecker::checkCategory($settings, 'posts-pages', 'write');

        $this->assertFalse($result->isSuccess);
    }

    public function test_ability_level_override_works(): void {
        $settings = new PermissionSettings(
            globalReadEnabled: true,
            globalWriteEnabled: true,
            categorySettings: ['posts-pages' => ['enable_read' => true, 'enable_write' => true]],
            abilitySettings: ['fa-wpmcp/create-post' => ['enabled' => false]],
        );

        $result = PermissionChecker::checkAbility($settings, 'fa-wpmcp/create-post');

        $this->assertFalse($result->isSuccess);
    }

    public function test_hierarchy_check_follows_correct_order(): void {
        // Global -> Category -> Ability
        $settings = new PermissionSettings(
            globalReadEnabled: true,
            globalWriteEnabled: true,
            categorySettings: [],
            abilitySettings: [],
        );

        $result = PermissionChecker::check($settings, 'fa-wpmcp/list-posts', 'read');

        $this->assertTrue($result->isSuccess);
    }
}
```

#### Phase 1.4b: Implement Permission System (GREEN)

**FP Approach: Pure functions for permission logic**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Permissions;

use FAWpmcp\ValueObjects\PermissionSettings;
use FAWpmcp\ValueObjects\Result;

/**
 * Pure functions for permission checking.
 * No side effects, no database access, no WordPress functions.
 */
final class PermissionChecker {
    /**
     * Check full permission hierarchy.
     * Pure function: same inputs always produce same result.
     */
    public static function check(
        PermissionSettings $settings,
        string $abilityName,
        string $operationType,
        ?string $category = null
    ): Result {
        // 1. Check global settings
        $globalResult = self::checkGlobal($settings, $operationType);
        if (!$globalResult->isSuccess) {
            return $globalResult;
        }

        // 2. Check category settings (if category provided)
        if ($category !== null) {
            $categoryResult = self::checkCategory($settings, $category, $operationType);
            if (!$categoryResult->isSuccess) {
                return $categoryResult;
            }
        }

        // 3. Check ability-specific settings
        return self::checkAbility($settings, $abilityName);
    }

    /**
     * Check global permission settings.
     */
    public static function checkGlobal(PermissionSettings $settings, string $operationType): Result {
        $enabled = match ($operationType) {
            'read' => $settings->globalReadEnabled,
            'write' => $settings->globalWriteEnabled,
            default => false,
        };

        return $enabled
            ? Result::success(true)
            : Result::failure('ability_disabled', "Global {$operationType} is disabled");
    }

    /**
     * Check category-level permissions.
     */
    public static function checkCategory(
        PermissionSettings $settings,
        string $category,
        string $operationType
    ): Result {
        $categorySettings = $settings->categorySettings[$category] ?? null;

        if ($categorySettings === null) {
            // No category override, inherit from global (allowed)
            return Result::success(true);
        }

        $key = "enable_{$operationType}";
        $enabled = $categorySettings[$key] ?? true;

        return $enabled
            ? Result::success(true)
            : Result::failure('ability_disabled', "Category {$category} {$operationType} is disabled");
    }

    /**
     * Check ability-level permissions.
     */
    public static function checkAbility(PermissionSettings $settings, string $abilityName): Result {
        $abilitySettings = $settings->abilitySettings[$abilityName] ?? null;

        if ($abilitySettings === null) {
            // No ability override, inherit (allowed)
            return Result::success(true);
        }

        $enabled = $abilitySettings['enabled'] ?? true;

        return $enabled
            ? Result::success(true)
            : Result::failure('ability_disabled', "Ability {$abilityName} is disabled");
    }
}
```

**PermissionManager (orchestration with side effects):**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Permissions;

use FAWpmcp\ValueObjects\PermissionSettings;
use FAWpmcp\ValueObjects\Result;

/**
 * Orchestrates permission checking with WordPress integration.
 * Contains side effects: WordPress function calls.
 */
final class PermissionManager {
    public function __construct(
        private readonly Settings $settings,
    ) {}

    /**
     * Full permission check including WordPress capabilities.
     */
    public function canExecute(string $abilityName, string $operationType, ?string $category = null): Result {
        // 1. Get current settings (side effect: database read)
        $permissionSettings = $this->settings->getAll();

        // 2. Pure function permission check
        $result = PermissionChecker::check($permissionSettings, $abilityName, $operationType, $category);

        if (!$result->isSuccess) {
            return $result;
        }

        // 3. WordPress capability check (side effect)
        return $this->checkWordPressCapability($operationType);
    }

    private function checkWordPressCapability(string $operationType): Result {
        $capability = match ($operationType) {
            'read' => 'fa_wpmcp_read_abilities',
            'write' => 'fa_wpmcp_write_abilities',
            'delete' => 'fa_wpmcp_delete_abilities',
            default => 'manage_options',
        };

        // Side effect: WordPress function call
        if (current_user_can($capability)) {
            return Result::success(true);
        }

        // Fallback capabilities
        $fallback = match ($operationType) {
            'read' => 'read',
            'write' => 'edit_posts',
            'delete' => 'delete_posts',
            default => 'manage_options',
        };

        return current_user_can($fallback)
            ? Result::success(true)
            : Result::failure('insufficient_permissions', 'User lacks required capability');
    }
}
```

#### Phase 1.4c: Refactor Permission System (REFACTOR)

- Ensure all pure functions have no hidden side effects
- Add Result type for all returns
- Extract common patterns
- Ensure permission tests pass with edge cases

**Acceptance criteria:**
- [ ] All permission tests pass (GREEN)
- [ ] Global disable blocks all abilities
- [ ] Category disable blocks category abilities
- [ ] Ability disable blocks specific ability
- [ ] WordPress capabilities checked last
- [ ] PermissionSettings is truly immutable
- [ ] PermissionChecker has no side effects

---

### Phase 1.5: Activity Logging System
**Duration:** 2-3 hours
**Dependencies:** Phase 1.3

**Files to create:**
- `src/ValueObjects/LogEntry.php` - Immutable log entry
- `src/Logging/LogEntryBuilder.php` - Builder pattern for log entries
- `src/Logging/LogRepository.php` - Database operations (side effects)
- `src/Logging/ActivityLogger.php` - Orchestration

#### Phase 1.5a: Write Logging Tests (RED)

**Tests to create:**
- `tests/phpunit/ValueObjects/LogEntryTest.php`
- `tests/phpunit/Logging/LogEntryBuilderTest.php`
- `tests/phpunit/Logging/ActivityLoggerTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\ValueObjects;

use FAWpmcp\ValueObjects\LogEntry;
use PHPUnit\Framework\TestCase;

class LogEntryTest extends TestCase {
    public function test_log_entry_is_immutable(): void {
        $entry = new LogEntry(
            correlationId: 'test-123',
            userId: 1,
            userLogin: 'admin',
            ipAddress: '127.0.0.1',
            abilityName: 'fa-wpmcp/list-posts',
            abilityCategory: 'posts-pages',
            operationType: 'read',
            inputData: ['page' => 1],
            outputData: ['posts' => []],
            success: true,
            errorMessage: null,
            executionTimeMs: 100,
        );

        // readonly properties cannot be modified
        $this->assertEquals('test-123', $entry->correlationId);
        $this->assertTrue($entry->success);
    }
}

namespace FAWpmcp\Tests\Logging;

use FAWpmcp\Logging\LogEntryBuilder;
use PHPUnit\Framework\TestCase;

class LogEntryBuilderTest extends TestCase {
    public function test_builds_complete_log_entry(): void {
        $entry = LogEntryBuilder::create()
            ->withCorrelationId('uuid-123')
            ->withUser(1, 'admin')
            ->withIpAddress('192.168.1.1')
            ->withAbility('fa-wpmcp/list-posts', 'posts-pages', 'read')
            ->withInput(['page' => 1])
            ->withOutput(['posts' => []])
            ->withSuccess(true)
            ->withExecutionTime(150)
            ->build();

        $this->assertEquals('uuid-123', $entry->correlationId);
        $this->assertEquals(150, $entry->executionTimeMs);
    }

    public function test_builder_is_immutable(): void {
        $builder1 = LogEntryBuilder::create()->withCorrelationId('id-1');
        $builder2 = $builder1->withCorrelationId('id-2');

        // Original builder unchanged
        $this->assertNotSame($builder1, $builder2);
    }
}

class ActivityLoggerTest extends TestCase {
    public function test_log_before_execute_returns_correlation_id(): void {
        // Mock repository
        $repository = $this->createMock(LogRepository::class);

        $logger = new ActivityLogger($repository, fn() => 'mock-uuid');
        $correlationId = $logger->logBeforeExecute('fa-wpmcp/list-posts', ['page' => 1]);

        $this->assertEquals('mock-uuid', $correlationId);
    }

    public function test_log_after_execute_updates_entry(): void {
        $repository = $this->createMock(LogRepository::class);
        $repository->expects($this->once())
            ->method('update')
            ->with($this->callback(fn($entry) => $entry->success === true));

        $logger = new ActivityLogger($repository, fn() => 'mock-uuid');
        $logger->logAfterExecute('mock-uuid', ['posts' => []], true);
    }
}
```

#### Phase 1.5b: Implement Logging System (GREEN)

**FP Approach: Immutable LogEntry, Builder pattern**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Logging;

use FAWpmcp\ValueObjects\LogEntry;

/**
 * Immutable builder for LogEntry.
 * Each method returns a new builder instance.
 */
final class LogEntryBuilder {
    private function __construct(
        private readonly ?string $correlationId = null,
        private readonly ?int $userId = null,
        private readonly ?string $userLogin = null,
        private readonly ?string $ipAddress = null,
        private readonly ?string $abilityName = null,
        private readonly ?string $abilityCategory = null,
        private readonly ?string $operationType = null,
        private readonly ?array $inputData = null,
        private readonly ?array $outputData = null,
        private readonly ?bool $success = null,
        private readonly ?string $errorMessage = null,
        private readonly ?int $executionTimeMs = null,
    ) {}

    public static function create(): self {
        return new self();
    }

    public function withCorrelationId(string $id): self {
        return new self(
            $id,
            $this->userId,
            $this->userLogin,
            $this->ipAddress,
            $this->abilityName,
            $this->abilityCategory,
            $this->operationType,
            $this->inputData,
            $this->outputData,
            $this->success,
            $this->errorMessage,
            $this->executionTimeMs,
        );
    }

    public function withUser(int $id, string $login): self {
        return new self(
            $this->correlationId,
            $id,
            $login,
            $this->ipAddress,
            $this->abilityName,
            $this->abilityCategory,
            $this->operationType,
            $this->inputData,
            $this->outputData,
            $this->success,
            $this->errorMessage,
            $this->executionTimeMs,
        );
    }

    // ... other with* methods follow same pattern

    public function build(): LogEntry {
        return new LogEntry(
            correlationId: $this->correlationId ?? '',
            userId: $this->userId ?? 0,
            userLogin: $this->userLogin ?? '',
            ipAddress: $this->ipAddress ?? '',
            abilityName: $this->abilityName ?? '',
            abilityCategory: $this->abilityCategory ?? '',
            operationType: $this->operationType ?? 'read',
            inputData: $this->inputData,
            outputData: $this->outputData,
            success: $this->success ?? false,
            errorMessage: $this->errorMessage,
            executionTimeMs: $this->executionTimeMs ?? 0,
        );
    }
}
```

**ActivityLogger (orchestration):**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Logging;

use FAWpmcp\ValueObjects\LogEntry;

final class ActivityLogger {
    /** @var callable():string UUID generator */
    private $uuidGenerator;

    public function __construct(
        private readonly LogRepository $repository,
        callable $uuidGenerator,
    ) {
        $this->uuidGenerator = $uuidGenerator;
    }

    public function logBeforeExecute(string $ability, array $input): string {
        $correlationId = ($this->uuidGenerator)();

        $entry = LogEntryBuilder::create()
            ->withCorrelationId($correlationId)
            ->withAbility($ability, $this->getCategory($ability), $this->getOperationType($ability))
            ->withInput($input)
            ->build();

        // Side effect: database write
        $this->repository->insert($entry);

        return $correlationId;
    }

    // ... other methods
}
```

#### Phase 1.5c: Refactor Logging System (REFACTOR)

- Ensure LogEntry is truly immutable
- Builder returns new instance on each call
- Extract common patterns
- Add comprehensive error handling

**Acceptance criteria:**
- [x] All logging tests pass (GREEN)
- [x] Every ability execution logged
- [x] Correlation ID unique per request
- [x] Input/output stored as JSON
- [x] Execution time measured in milliseconds
- [x] LogEntry is immutable
- [x] LogEntryBuilder is immutable

---

### Phase 1.6: Rate Limiting System
**Duration:** 2-3 hours
**Dependencies:** Phase 1.2

**Files to create:**
- `src/ValueObjects/RateLimit.php` - Immutable rate limit config
- `src/ValueObjects/RateLimitResult.php` - Immutable result
- `src/RateLimiting/RateLimitCalculator.php` - Pure calculation functions
- `src/RateLimiting/RateLimiter.php` - Orchestration
- `src/RateLimiting/RateLimitStore.php` - Transient-based storage

#### Phase 1.6a: Write Rate Limiting Tests (RED)

**Tests to create:**
- `tests/phpunit/ValueObjects/RateLimitTest.php`
- `tests/phpunit/RateLimiting/RateLimitCalculatorTest.php`
- `tests/phpunit/RateLimiting/RateLimiterTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\RateLimiting;

use FAWpmcp\RateLimiting\RateLimitCalculator;
use FAWpmcp\ValueObjects\RateLimit;
use FAWpmcp\ValueObjects\RateLimitResult;
use PHPUnit\Framework\TestCase;

class RateLimitCalculatorTest extends TestCase {
    public function test_under_limit_returns_allowed(): void {
        $limit = new RateLimit(
            requestsPerMinute: 60,
            requestsPerHour: 500,
            ability: 'fa-wpmcp/list-posts',
        );

        $result = RateLimitCalculator::check(
            limit: $limit,
            currentMinuteCount: 30,
            currentHourCount: 100,
        );

        $this->assertTrue($result->allowed);
        $this->assertEquals('none', $result->limitType);
    }

    public function test_at_minute_limit_returns_denied(): void {
        $limit = new RateLimit(
            requestsPerMinute: 60,
            requestsPerHour: 500,
            ability: 'fa-wpmcp/list-posts',
        );

        $result = RateLimitCalculator::check(
            limit: $limit,
            currentMinuteCount: 60,
            currentHourCount: 100,
        );

        $this->assertFalse($result->allowed);
        $this->assertEquals('minute', $result->limitType);
    }

    public function test_at_hour_limit_returns_denied(): void {
        $limit = new RateLimit(
            requestsPerMinute: 60,
            requestsPerHour: 500,
            ability: 'fa-wpmcp/list-posts',
        );

        $result = RateLimitCalculator::check(
            limit: $limit,
            currentMinuteCount: 30,
            currentHourCount: 500,
        );

        $this->assertFalse($result->allowed);
        $this->assertEquals('hour', $result->limitType);
    }

    public function test_retry_after_calculation_for_minute(): void {
        $retryAfter = RateLimitCalculator::calculateRetryAfter('minute', 45);

        // Should be seconds remaining in current minute window
        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(60, $retryAfter);
    }

    public function test_build_key_produces_consistent_output(): void {
        $key1 = RateLimitCalculator::buildKey(1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute');
        $key2 = RateLimitCalculator::buildKey(1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute');

        $this->assertEquals($key1, $key2);
    }

    public function test_ip_is_hashed_for_privacy(): void {
        $key = RateLimitCalculator::buildKey(1, '192.168.1.1', 'fa-wpmcp/list-posts', 'minute');

        $this->assertStringNotContainsString('192.168.1.1', $key);
    }
}
```

#### Phase 1.6b: Implement Rate Limiting System (GREEN)

**FP Approach: Pure calculation functions**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\RateLimiting;

use FAWpmcp\ValueObjects\RateLimit;
use FAWpmcp\ValueObjects\RateLimitResult;

/**
 * Pure functions for rate limit calculations.
 * No side effects, no external dependencies.
 */
final class RateLimitCalculator {
    /**
     * Check if request is within rate limits.
     * Pure function: same inputs always produce same result.
     */
    public static function check(
        RateLimit $limit,
        int $currentMinuteCount,
        int $currentHourCount,
    ): RateLimitResult {
        // Check minute limit first
        if ($currentMinuteCount >= $limit->requestsPerMinute) {
            $retryAfter = self::calculateRetryAfter('minute', time());
            return RateLimitResult::denied($retryAfter, 'minute');
        }

        // Check hour limit
        if ($currentHourCount >= $limit->requestsPerHour) {
            $retryAfter = self::calculateRetryAfter('hour', time());
            return RateLimitResult::denied($retryAfter, 'hour');
        }

        return RateLimitResult::allowed();
    }

    /**
     * Calculate seconds until limit window resets.
     * Pure function: deterministic based on current time.
     */
    public static function calculateRetryAfter(string $limitType, int $currentTime): int {
        return match ($limitType) {
            'minute' => 60 - ($currentTime % 60),
            'hour' => 3600 - ($currentTime % 3600),
            default => 60,
        };
    }

    /**
     * Build transient key for rate limit tracking.
     * Pure function: same inputs produce same key.
     */
    public static function buildKey(int $userId, string $ip, string $ability, string $window): string {
        $ipHash = substr(md5($ip), 0, 8);
        $abilitySlug = str_replace('/', '-', $ability);
        return "fa_wpmcp_ratelimit_{$userId}_{$ipHash}_{$abilitySlug}_{$window}";
    }

    /**
     * Determine which limits apply to an ability.
     * Pure function: returns limit configuration.
     */
    public static function getLimitsForAbility(string $ability, array $config): RateLimit {
        $defaults = ['requests_per_minute' => 60, 'requests_per_hour' => 500];
        $abilityConfig = $config[$ability] ?? $defaults;

        return new RateLimit(
            requestsPerMinute: $abilityConfig['requests_per_minute'] ?? 60,
            requestsPerHour: $abilityConfig['requests_per_hour'] ?? 500,
            ability: $ability,
        );
    }
}
```

**RateLimiter (orchestration with side effects):**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\RateLimiting;

use FAWpmcp\ValueObjects\RateLimitResult;

final class RateLimiter {
    public function __construct(
        private readonly RateLimitStore $store,
        private readonly RateLimitConfig $config,
    ) {}

    public function check(string $ability, int $userId, string $ip): RateLimitResult {
        // Get configuration (pure)
        $limit = RateLimitCalculator::getLimitsForAbility($ability, $this->config->getAll());

        // Get current counts (side effect: transient reads)
        $minuteKey = RateLimitCalculator::buildKey($userId, $ip, $ability, 'minute');
        $hourKey = RateLimitCalculator::buildKey($userId, $ip, $ability, 'hour');

        $minuteCount = $this->store->get($minuteKey);
        $hourCount = $this->store->get($hourKey);

        // Pure calculation
        return RateLimitCalculator::check($limit, $minuteCount, $hourCount);
    }

    public function record(string $ability, int $userId, string $ip): void {
        // Build keys (pure)
        $minuteKey = RateLimitCalculator::buildKey($userId, $ip, $ability, 'minute');
        $hourKey = RateLimitCalculator::buildKey($userId, $ip, $ability, 'hour');

        // Increment counters (side effect: transient writes)
        $this->store->increment($minuteKey, 60);
        $this->store->increment($hourKey, 3600);
    }
}
```

#### Phase 1.6c: Refactor Rate Limiting System (REFACTOR)

- Ensure all calculations are pure functions
- Value objects are immutable
- Extract common patterns
- Add comprehensive documentation

**Acceptance criteria:**
- [ ] All rate limiting tests pass (GREEN)
- [ ] Per-minute limits enforced
- [ ] Per-hour limits enforced
- [ ] Both user and IP tracked
- [ ] Returns retry_after seconds when exceeded
- [ ] RateLimitCalculator has no side effects
- [ ] RateLimit and RateLimitResult are immutable

---

### Phase 1.7: Webhook System
**Duration:** 3-4 hours
**Dependencies:** Phase 1.3, Phase 1.5

**Files to create:**
- `src/ValueObjects/WebhookPayload.php` - Immutable payload
- `src/Webhooks/PayloadBuilder.php` - Pure payload construction
- `src/Webhooks/SignatureGenerator.php` - Pure HMAC signing
- `src/Webhooks/WebhookQueue.php` - Queue management (side effects)
- `src/Webhooks/WebhookSender.php` - HTTP delivery (side effects)
- `src/Webhooks/WebhookManager.php` - Orchestration

#### Phase 1.7a: Write Webhook Tests (RED)

**Tests to create:**
- `tests/phpunit/ValueObjects/WebhookPayloadTest.php`
- `tests/phpunit/Webhooks/PayloadBuilderTest.php`
- `tests/phpunit/Webhooks/SignatureGeneratorTest.php`
- `tests/phpunit/Webhooks/WebhookManagerTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use FAWpmcp\Webhooks\PayloadBuilder;
use FAWpmcp\Webhooks\SignatureGenerator;
use PHPUnit\Framework\TestCase;

class PayloadBuilderTest extends TestCase {
    public function test_builds_complete_payload(): void {
        $payload = PayloadBuilder::build(
            event: 'ability.after_execute',
            abilityName: 'fa-wpmcp/create-post',
            category: 'posts-pages',
            operation: 'write',
            userId: 1,
            userLogin: 'admin',
            ip: '192.168.1.1',
            input: ['title' => 'Test'],
            output: ['post_id' => 42],
            success: true,
            executionTimeMs: 150,
        );

        $this->assertEquals('ability.after_execute', $payload->event);
        $this->assertEquals('fa-wpmcp/create-post', $payload->ability['name']);
        $this->assertTrue($payload->success);
    }

    public function test_payload_is_immutable(): void {
        $payload = PayloadBuilder::build(
            event: 'ability.after_execute',
            abilityName: 'fa-wpmcp/list-posts',
            category: 'posts-pages',
            operation: 'read',
            userId: 1,
            userLogin: 'admin',
            ip: '127.0.0.1',
            input: [],
            output: [],
            success: true,
            executionTimeMs: 100,
        );

        // readonly properties
        $this->assertEquals('ability.after_execute', $payload->event);
    }

    public function test_to_json_returns_valid_json(): void {
        $payload = PayloadBuilder::build(/* ... */);
        $json = $payload->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('ability.after_execute', $decoded['event']);
    }
}

class SignatureGeneratorTest extends TestCase {
    public function test_generates_hmac_signature(): void {
        $payload = '{"event":"test"}';
        $secret = 'test-secret';

        $signature = SignatureGenerator::generate($payload, $secret);

        $this->assertStringStartsWith('sha256=', $signature);
    }

    public function test_same_input_produces_same_signature(): void {
        $payload = '{"event":"test"}';
        $secret = 'test-secret';

        $sig1 = SignatureGenerator::generate($payload, $secret);
        $sig2 = SignatureGenerator::generate($payload, $secret);

        $this->assertEquals($sig1, $sig2);
    }

    public function test_different_secret_produces_different_signature(): void {
        $payload = '{"event":"test"}';

        $sig1 = SignatureGenerator::generate($payload, 'secret-1');
        $sig2 = SignatureGenerator::generate($payload, 'secret-2');

        $this->assertNotEquals($sig1, $sig2);
    }

    public function test_verify_returns_true_for_valid_signature(): void {
        $payload = '{"event":"test"}';
        $secret = 'test-secret';
        $signature = SignatureGenerator::generate($payload, $secret);

        $result = SignatureGenerator::verify($payload, $signature, $secret);

        $this->assertTrue($result);
    }
}
```

#### Phase 1.7b: Implement Webhook System (GREEN)

**FP Approach: Pure functions for payload and signature**

**Steps:**

1. Add Action Scheduler as a dependency and initialize it for webhook dispatch
2. Gate Action Scheduler usage with a runtime availability check
3. Implement WP-Cron fallback when Action Scheduler is unavailable

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\ValueObjects\WebhookPayload;
use DateTimeImmutable;

/**
 * Pure functions for building webhook payloads.
 */
final class PayloadBuilder {
    /**
     * Build complete webhook payload.
     * Pure function: same inputs produce same payload.
     */
    public static function build(
        string $event,
        string $abilityName,
        string $category,
        string $operation,
        int $userId,
        string $userLogin,
        string $ip,
        array $input,
        array $output,
        bool $success,
        int $executionTimeMs,
        ?DateTimeImmutable $timestamp = null,
    ): WebhookPayload {
        return new WebhookPayload(
            event: $event,
            timestamp: $timestamp ?? new DateTimeImmutable(),
            ability: [
                'name' => $abilityName,
                'category' => $category,
                'operation' => $operation,
            ],
            user: [
                'id' => $userId,
                'login' => $userLogin,
                'ip' => $ip,
            ],
            input: $input,
            output: $output,
            success: $success,
            executionTimeMs: $executionTimeMs,
        );
    }
}

/**
 * Pure functions for HMAC signature generation.
 */
final class SignatureGenerator {
    /**
     * Generate HMAC signature for payload.
     * Pure function: deterministic output.
     */
    public static function generate(string $payload, string $secret): string {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify signature matches payload.
     * Pure function: comparison only.
     */
    public static function verify(string $payload, string $signature, string $secret): bool {
        $expected = self::generate($payload, $secret);
        return hash_equals($expected, $signature);
    }
}
```

**WebhookManager (orchestration with side effects):**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Webhooks;

use FAWpmcp\ValueObjects\WebhookPayload;

final class WebhookManager {
    public function __construct(
        private readonly WebhookQueue $queue,
        private readonly WebhookSender $sender,
        private readonly WebhookConfig $config,
    ) {}

    public function trigger(string $event, array $context): void {
        // Build payload (pure)
        $payload = PayloadBuilder::build(
            event: $event,
            abilityName: $context['ability_name'],
            // ... other fields
        );

        // Get subscribed URLs (side effect: config read)
        $urls = $this->config->getSubscribedUrls($event);

        // Queue webhooks (side effect: database writes)
        foreach ($urls as $url) {
            $this->queue->enqueue($url, $payload);
        }
    }

    public function processQueue(): void {
        // Get pending webhooks (side effect: database read)
        $pending = $this->queue->getPending(10);

        foreach ($pending as $webhook) {
            $this->processWebhook($webhook);
        }
    }

    private function processWebhook(array $webhook): void {
        // Generate signature (pure)
        $signature = SignatureGenerator::generate($webhook['payload'], $this->config->getSecret());

        // Send HTTP request (side effect)
        $result = $this->sender->send($webhook['url'], $webhook['payload'], $signature);

        // Update queue status (side effect)
        if ($result->isSuccess) {
            $this->queue->markComplete($webhook['id']);
        } else {
            $this->handleFailure($webhook);
        }
    }

    private function handleFailure(array $webhook): void {
        $attempts = $webhook['attempt_count'] + 1;

        if ($attempts >= 3) {
            $this->queue->markFailed($webhook['id'], 'Max retries exceeded');
        } else {
            // Calculate next attempt (pure)
            $nextAttempt = RetryCalculator::calculateNextAttempt($attempts);
            $this->queue->scheduleRetry($webhook['id'], $nextAttempt);
        }
    }
}
```

#### Phase 1.7c: Refactor Webhook System (REFACTOR)

- Ensure signature generation is pure
- Payload building is pure
- Extract retry logic to pure calculator
- Add comprehensive error handling

**Acceptance criteria:**
- [ ] All webhook tests pass (GREEN)
- [ ] Webhooks fire on ability.before_execute
- [ ] Webhooks fire on ability.after_execute
- [ ] Webhooks fire on ability.failed
- [ ] HMAC signature in X-FA-WPMCP-Signature header
- [ ] Failed webhooks retry with exponential backoff
- [ ] PayloadBuilder and SignatureGenerator are pure functions

---

### Phase 1.8: Ability Registration Framework
**Duration:** 3-4 hours
**Dependencies:** Phase 1.4, Phase 1.5, Phase 1.6, Phase 1.7

**Files to create:**
- `src/Abilities/AbilityRegistry.php` - Central registration
- `src/Abilities/CategoryRegistry.php` - Category management
- `src/Abilities/AbstractAbility.php` - Base class for abilities
- `src/Abilities/AbilityExecutor.php` - Pipeline execution with logging/rate-limiting
- `src/Abilities/ExecutionPipeline.php` - Composable pipeline

#### Phase 1.8a: Write Ability Framework Tests (RED)

**Tests to create:**
- `tests/phpunit/Abilities/AbilityRegistryTest.php`
- `tests/phpunit/Abilities/ExecutionPipelineTest.php`
- `tests/phpunit/Abilities/AbilityExecutorTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities;

use FAWpmcp\Abilities\ExecutionPipeline;
use FAWpmcp\ValueObjects\Result;
use PHPUnit\Framework\TestCase;

class ExecutionPipelineTest extends TestCase {
    public function test_pipeline_executes_steps_in_order(): void {
        $executionOrder = [];

        $pipeline = ExecutionPipeline::create()
            ->pipe(function($input) use (&$executionOrder) {
                $executionOrder[] = 'step1';
                return Result::success($input);
            })
            ->pipe(function($input) use (&$executionOrder) {
                $executionOrder[] = 'step2';
                return Result::success($input);
            })
            ->pipe(function($input) use (&$executionOrder) {
                $executionOrder[] = 'step3';
                return Result::success($input);
            });

        $result = $pipeline->execute(['test' => 'data']);

        $this->assertEquals(['step1', 'step2', 'step3'], $executionOrder);
        $this->assertTrue($result->isSuccess);
    }

    public function test_pipeline_stops_on_failure(): void {
        $executionOrder = [];

        $pipeline = ExecutionPipeline::create()
            ->pipe(function($input) use (&$executionOrder) {
                $executionOrder[] = 'step1';
                return Result::success($input);
            })
            ->pipe(function($input) use (&$executionOrder) {
                $executionOrder[] = 'step2';
                return Result::failure('error', 'Step 2 failed');
            })
            ->pipe(function($input) use (&$executionOrder) {
                $executionOrder[] = 'step3';
                return Result::success($input);
            });

        $result = $pipeline->execute(['test' => 'data']);

        $this->assertEquals(['step1', 'step2'], $executionOrder);
        $this->assertFalse($result->isSuccess);
        $this->assertEquals('error', $result->errorCode);
    }

    public function test_pipeline_passes_transformed_data(): void {
        $pipeline = ExecutionPipeline::create()
            ->pipe(fn($input) => Result::success(array_merge($input, ['added' => 'value'])))
            ->pipe(fn($input) => Result::success($input));

        $result = $pipeline->execute(['original' => 'data']);

        $this->assertTrue($result->isSuccess);
        $this->assertEquals('value', $result->value['added']);
    }
}

class AbilityExecutorTest extends TestCase {
    public function test_executor_runs_full_pipeline(): void {
        // Mock dependencies
        $permissionManager = $this->createMock(PermissionManager::class);
        $permissionManager->method('canExecute')->willReturn(Result::success(true));

        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->method('check')->willReturn(RateLimitResult::allowed());

        $logger = $this->createMock(ActivityLogger::class);
        $logger->method('logBeforeExecute')->willReturn('correlation-id');

        $webhookManager = $this->createMock(WebhookManager::class);

        $executor = new AbilityExecutor(
            $permissionManager,
            $rateLimiter,
            $logger,
            $webhookManager,
        );

        $ability = $this->createTestAbility();
        $result = $executor->execute($ability, ['test' => 'input']);

        $this->assertTrue($result->isSuccess);
    }

    public function test_executor_returns_rate_limit_error(): void {
        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->method('check')->willReturn(
            RateLimitResult::denied(30, 'minute')
        );

        // ... setup other mocks

        $result = $executor->execute($ability, ['test' => 'input']);

        $this->assertFalse($result->isSuccess);
        $this->assertEquals('rate_limit_exceeded', $result->errorCode);
    }
}
```

#### Phase 1.8b: Implement Ability Framework (GREEN)

**FP Approach: Pipeline pattern for execution**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Abilities;

use FAWpmcp\ValueObjects\Result;

/**
 * Composable execution pipeline.
 * Each step is a function that takes input and returns Result.
 */
final class ExecutionPipeline {
    /** @var array<callable(mixed): Result> */
    private array $steps = [];

    private function __construct(array $steps = []) {
        $this->steps = $steps;
    }

    public static function create(): self {
        return new self();
    }

    /**
     * Add a step to the pipeline.
     * Returns new pipeline instance (immutable).
     */
    public function pipe(callable $step): self {
        return new self([...$this->steps, $step]);
    }

    /**
     * Execute the pipeline.
     * Stops on first failure, passes transformed data between steps.
     */
    public function execute(mixed $input): Result {
        return array_reduce(
            $this->steps,
            fn(Result $result, callable $step) =>
                $result->isSuccess ? $step($result->value) : $result,
            Result::success($input)
        );
    }
}

/**
 * Ability executor using pipeline composition.
 */
final class AbilityExecutor {
    public function __construct(
        private readonly PermissionManager $permissionManager,
        private readonly RateLimiter $rateLimiter,
        private readonly ActivityLogger $logger,
        private readonly WebhookManager $webhookManager,
    ) {}

    public function execute(AbstractAbility $ability, array $input): Result {
        $context = [
            'ability' => $ability,
            'input' => $input,
            'start_time' => microtime(true),
            'correlation_id' => null,
        ];

        $pipeline = ExecutionPipeline::create()
            ->pipe(fn($ctx) => $this->validateInput($ctx))
            ->pipe(fn($ctx) => $this->checkPermissions($ctx))
            ->pipe(fn($ctx) => $this->checkRateLimit($ctx))
            ->pipe(fn($ctx) => $this->logBefore($ctx))
            ->pipe(fn($ctx) => $this->fireBeforeWebhook($ctx))
            ->pipe(fn($ctx) => $this->executeAbility($ctx))
            ->pipe(fn($ctx) => $this->logAfter($ctx))
            ->pipe(fn($ctx) => $this->fireAfterWebhook($ctx));

        return $pipeline->execute($context);
    }

    private function checkPermissions(array $context): Result {
        $ability = $context['ability'];
        $result = $this->permissionManager->canExecute(
            $ability->getName(),
            $ability->getOperationType(),
            $ability->getCategory()
        );

        return $result->isSuccess
            ? Result::success($context)
            : $result;
    }

    private function checkRateLimit(array $context): Result {
        $ability = $context['ability'];
        $result = $this->rateLimiter->check(
            $ability->getName(),
            get_current_user_id(),
            $_SERVER['REMOTE_ADDR'] ?? ''
        );

        if (!$result->allowed) {
            return Result::failure(
                'rate_limit_exceeded',
                "Rate limit exceeded. Retry after {$result->retryAfter} seconds."
            );
        }

        return Result::success($context);
    }

    private function executeAbility(array $context): Result {
        try {
            $output = $context['ability']->doExecute($context['input']);
            return Result::success(array_merge($context, ['output' => $output]));
        } catch (\Throwable $e) {
            return Result::failure('internal_error', $e->getMessage());
        }
    }

    // ... other pipeline steps
}
```

**AbstractAbility base class:**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Abilities;

/**
 * Base class for all abilities.
 * Subclasses implement pure business logic in doExecute().
 */
abstract class AbstractAbility {
    abstract public function getName(): string;
    abstract public function getCategory(): string;
    abstract public function getLabel(): string;
    abstract public function getDescription(): string;
    abstract public function getInputSchema(): array;
    abstract public function getOutputSchema(): array;
    abstract public function getRequiredCapability(): string;

    /**
     * Execute the ability.
     * Should be as pure as possible - minimize side effects.
     */
    abstract protected function doExecute(array $input): array;

    public function getOperationType(): string {
        return 'read';
    }

    public function getAnnotations(): array {
        return [
            'readonly' => $this->getOperationType() === 'read',
            'destructive' => false,
            'idempotent' => $this->getOperationType() === 'read',
            'instructions' => '',
        ];
    }

    /**
     * Convert to Abilities API registration array.
     * Pure transformation function.
     */
    final public function toRegistrationArray(): array {
        return [
            'name' => $this->getName(),
            'category' => $this->getCategory(),
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'input_schema' => $this->getInputSchema(),
            'output_schema' => $this->getOutputSchema(),
            'execute_callback' => [$this, 'execute'],
            'permission_callback' => [$this, 'checkPermission'],
            'meta' => [
                'show_in_rest' => true,
                'annotations' => $this->getAnnotations(),
            ],
        ];
    }
}
```

#### Phase 1.8c: Refactor Ability Framework (REFACTOR)

- Ensure pipeline is immutable
- Extract common validation logic
- Add comprehensive error handling
- Document pipeline composition

**Acceptance criteria:**
- [ ] All ability framework tests pass (GREEN)
- [ ] Categories registered before abilities
- [ ] Abilities auto-discovered from subdirectories
- [ ] Execute flow includes rate-limit, permission, logging, webhooks
- [ ] Pipeline stops on first failure
- [ ] ExecutionPipeline is immutable

---

### Phase 1.9: MCP Server Integration
**Duration:** 2-3 hours
**Dependencies:** Phase 1.8

**Files to create:**
- `src/MCP/ServerConfig.php` - MCP server setup

#### Phase 1.9a: Write MCP Integration Tests (RED)

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\MCP;

use FAWpmcp\MCP\ServerConfig;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class ServerConfigTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    public function test_creates_server_with_correct_parameters(): void {
        // Mock MCP Adapter
        $adapter = Mockery::mock('overload:WP\MCP\McpAdapter');
        $adapter->shouldReceive('get_instance')->andReturnSelf();
        $adapter->shouldReceive('create_server')
            ->once()
            ->with(
                'fa-wpmcp',
                'fa-wpmcp',
                'v1/mcp',
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any()
            );

        $config = new ServerConfig();
        $config->register();
    }

    public function test_registers_tools_from_abilities(): void {
        $adapter = Mockery::mock('overload:WP\MCP\McpAdapter');
        $adapter->shouldReceive('get_instance')->andReturnSelf();
        $adapter->shouldReceive('register_tools')
            ->once()
            ->with('fa-wpmcp', Mockery::type('array'));

        $config = new ServerConfig();
        $config->registerTools(['fa-wpmcp/list-posts', 'fa-wpmcp/create-post']);
    }
}
```

#### Phase 1.9b: Implement MCP Integration (GREEN)

Implementation follows spec - hooks to WordPress MCP Adapter.

#### Phase 1.9c: Refactor MCP Integration (REFACTOR)

- Add HTTPS enforcement check
- Extract configuration to constants
- Add error handling for missing adapter

**Acceptance criteria:**
- [ ] All MCP tests pass (GREEN)
- [ ] MCP server created at /wp-json/fa-wpmcp/v1/mcp
- [ ] Abilities registered as MCP tools
- [ ] HTTPS enforced in production

---

### Phase 1.10: Post Abilities Implementation
**Duration:** 4-5 hours
**Dependencies:** Phase 1.8

**Files to create:**
- `src/Abilities/Posts/ListPosts.php`
- `src/Abilities/Posts/GetPost.php`
- `src/Abilities/Posts/CreatePost.php`
- `src/Abilities/Posts/UpdatePost.php`

#### Phase 1.10a: Write Post Abilities Tests (RED)

**Tests to create:**
- `tests/phpunit/Abilities/Posts/ListPostsTest.php`
- `tests/phpunit/Abilities/Posts/GetPostTest.php`
- `tests/phpunit/Abilities/Posts/CreatePostTest.php`
- `tests/phpunit/Abilities/Posts/UpdatePostTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Posts;

use FAWpmcp\Abilities\Posts\ListPosts;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class ListPostsTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    public function test_returns_paginated_results(): void {
        // Mock WP_Query
        Functions\expect('sanitize_text_field')->andReturnFirstArg();

        $mockQuery = Mockery::mock('WP_Query');
        $mockQuery->posts = [
            (object)['ID' => 1, 'post_title' => 'Test Post'],
        ];
        $mockQuery->found_posts = 1;
        $mockQuery->max_num_pages = 1;

        Functions\expect('get_permalink')->andReturn('https://example.com/test-post');

        $ability = new ListPosts();
        $result = $ability->doExecute(['page' => 1, 'per_page' => 10]);

        $this->assertArrayHasKey('posts', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('pages', $result);
    }

    public function test_respects_per_page_maximum(): void {
        $ability = new ListPosts();
        $input = ['per_page' => 500]; // Over limit

        $result = $ability->doExecute($input);

        // Should cap at 100
        // Verify by checking query args
    }

    public function test_filters_by_status(): void {
        $ability = new ListPosts();
        $input = ['status' => 'draft'];

        $result = $ability->doExecute($input);

        // Verify query used correct status
    }
}

class CreatePostTest extends TestCase {
    public function test_sanitizes_title(): void {
        Functions\expect('sanitize_text_field')
            ->once()
            ->with('<script>alert("xss")</script>Title')
            ->andReturn('Title');

        Functions\expect('wp_kses_post')->andReturnFirstArg();
        Functions\expect('wp_insert_post')->andReturn(42);
        Functions\expect('get_permalink')->andReturn('https://example.com/title');
        Functions\expect('get_edit_post_link')->andReturn('https://example.com/wp-admin/post.php?post=42');

        $ability = new CreatePost();
        $result = $ability->doExecute([
            'title' => '<script>alert("xss")</script>Title',
            'content' => 'Content',
        ]);

        $this->assertEquals(42, $result['post_id']);
    }

    public function test_sanitizes_content_with_wp_kses_post(): void {
        Functions\expect('sanitize_text_field')->andReturnFirstArg();
        Functions\expect('wp_kses_post')
            ->once()
            ->with('<script>bad</script><p>Good</p>')
            ->andReturn('<p>Good</p>');

        // ... rest of test
    }

    public function test_returns_correct_structure(): void {
        // ... mock setup

        $ability = new CreatePost();
        $result = $ability->doExecute([
            'title' => 'Test',
            'content' => 'Content',
        ]);

        $this->assertArrayHasKey('post_id', $result);
        $this->assertArrayHasKey('permalink', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('edit_url', $result);
    }
}
```

#### Phase 1.10b: Implement Post Abilities (GREEN)

**FP Approach: Minimize side effects, use transformations**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;

final class ListPosts extends AbstractAbility {
    public function getName(): string {
        return 'fa-wpmcp/list-posts';
    }

    public function getCategory(): string {
        return 'posts-pages';
    }

    public function getOperationType(): string {
        return 'read';
    }

    public function getRequiredCapability(): string {
        return 'read';
    }

    public function getInputSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string', 'enum' => ['publish', 'draft', 'pending', 'private', 'any'], 'default' => 'publish'],
                'author' => ['type' => 'integer'],
                'category' => ['type' => 'integer'],
                'search' => ['type' => 'string'],
                'per_page' => ['type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 100],
                'page' => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
            ],
        ];
    }

    protected function doExecute(array $input): array {
        // Transform input to WP_Query args (pure transformation)
        $queryArgs = $this->buildQueryArgs($input);

        // Side effect: database query
        $query = new \WP_Query($queryArgs);

        // Transform results (pure transformation)
        return $this->formatResults($query);
    }

    /**
     * Pure transformation: input to query args.
     */
    private function buildQueryArgs(array $input): array {
        return array_filter([
            'post_type' => 'post',
            'post_status' => $input['status'] ?? 'publish',
            'posts_per_page' => min($input['per_page'] ?? 10, 100),
            'paged' => max($input['page'] ?? 1, 1),
            'author' => $input['author'] ?? null,
            'cat' => $input['category'] ?? null,
            's' => $input['search'] ?? null,
        ], fn($v) => $v !== null);
    }

    /**
     * Pure transformation: WP_Query to output format.
     */
    private function formatResults(\WP_Query $query): array {
        $posts = array_map(
            fn($post) => $this->formatPost($post),
            $query->posts
        );

        return [
            'posts' => $posts,
            'total' => (int) $query->found_posts,
            'pages' => (int) $query->max_num_pages,
        ];
    }

    /**
     * Pure transformation: WP_Post to output array.
     */
    private function formatPost(\WP_Post $post): array {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'author' => (int) $post->post_author,
            'date' => $post->post_date,
            'permalink' => get_permalink($post->ID),
        ];
    }
}
```

**CreatePost with sanitization:**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;

final class CreatePost extends AbstractAbility {
    public function getName(): string {
        return 'fa-wpmcp/create-post';
    }

    public function getOperationType(): string {
        return 'write';
    }

    public function getRequiredCapability(): string {
        return 'edit_posts';
    }

    protected function doExecute(array $input): array {
        // Sanitize inputs (pure transformations using WordPress functions)
        $sanitized = $this->sanitizeInput($input);

        // Build post data (pure transformation)
        $postData = $this->buildPostData($sanitized);

        // Side effect: database insert
        $postId = wp_insert_post($postData, true);

        if (is_wp_error($postId)) {
            throw new \RuntimeException($postId->get_error_message());
        }

        // Return formatted result
        return $this->formatResult($postId);
    }

    /**
     * Sanitize all input fields.
     */
    private function sanitizeInput(array $input): array {
        return [
            'title' => sanitize_text_field($input['title']),
            'content' => wp_kses_post($input['content']),
            'status' => sanitize_key($input['status'] ?? 'draft'),
            'author' => isset($input['author']) ? absint($input['author']) : get_current_user_id(),
            'excerpt' => isset($input['excerpt']) ? sanitize_text_field($input['excerpt']) : '',
            'categories' => isset($input['categories']) ? array_map('absint', $input['categories']) : [],
            'tags' => isset($input['tags']) ? array_map('sanitize_text_field', $input['tags']) : [],
        ];
    }

    /**
     * Build wp_insert_post data array.
     */
    private function buildPostData(array $sanitized): array {
        return [
            'post_title' => $sanitized['title'],
            'post_content' => $sanitized['content'],
            'post_status' => $sanitized['status'],
            'post_author' => $sanitized['author'],
            'post_excerpt' => $sanitized['excerpt'],
            'post_category' => $sanitized['categories'],
            'tags_input' => $sanitized['tags'],
        ];
    }

    /**
     * Format the result for output.
     */
    private function formatResult(int $postId): array {
        return [
            'post_id' => $postId,
            'permalink' => esc_url(get_permalink($postId)),
            'status' => get_post_status($postId),
            'edit_url' => esc_url(get_edit_post_link($postId, 'raw')),
        ];
    }
}
```

#### Phase 1.10c: Refactor Post Abilities (REFACTOR)

- Extract common sanitization to utility class
- Ensure all transformations are pure functions
- Add comprehensive PHPDoc
- Verify AI-friendly descriptions

**Acceptance criteria:**
- [ ] All post ability tests pass (GREEN)
- [ ] ListPosts returns paginated results
- [ ] GetPost returns full post with meta
- [ ] CreatePost creates draft/publish posts
- [ ] UpdatePost modifies existing posts
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] Transformations are pure functions

---

### Phase 1.11: Admin Settings UI
**Duration:** 4-5 hours
**Dependencies:** Phase 1.4, Phase 1.5, Phase 1.6, Phase 1.7

#### Phase 1.11a: Write Admin UI Tests (RED)

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Admin;

use FAWpmcp\Admin\SettingsPage;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class SettingsPageTest extends TestCase {
    public function test_registers_admin_menu(): void {
        Functions\expect('add_menu_page')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                'manage_options',
                'fa-wpmcp',
                Mockery::any(),
                Mockery::any(),
                Mockery::any()
            );

        $page = new SettingsPage();
        $page->register();
    }

    public function test_enqueues_assets_only_on_plugin_pages(): void {
        // Test that CSS/JS only load on fa-wpmcp pages
    }

    public function test_saves_settings_with_nonce_verification(): void {
        Functions\expect('wp_verify_nonce')
            ->once()
            ->andReturn(true);

        Functions\expect('current_user_can')
            ->with('manage_options')
            ->andReturn(true);

        // ... test save logic
    }
}
```

#### Phase 1.11b: Implement Admin UI (GREEN)

Implementation follows spec - WordPress native components, multi-level permission toggles.

#### Phase 1.11c: Refactor Admin UI (REFACTOR)

- Extract render methods
- Add proper escaping
- Ensure accessibility compliance

**Acceptance criteria:**
- [ ] All admin UI tests pass (GREEN)
- [ ] Settings page accessible at FA WPMCP menu
- [ ] Multi-level permission toggles work
- [ ] Activity log displays with filters
- [ ] CSV export works
- [ ] Proper capability checks on all admin actions

---

### Phase 1.12: Error Handling & Response Contract
**Duration:** 2-3 hours
**Dependencies:** Phase 1.8

**Files to create:**
- `src/Http/ResponseFormatter.php` - Standardized responses
- `src/Http/ErrorCodes.php` - Error code constants
- `src/Http/PrivacyRedactor.php` - Sensitive field removal

#### Phase 1.12a: Write Error Handling Tests (RED)

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Http;

use FAWpmcp\Http\ResponseFormatter;
use FAWpmcp\Http\PrivacyRedactor;
use PHPUnit\Framework\TestCase;

class ResponseFormatterTest extends TestCase {
    public function test_success_format_includes_required_fields(): void {
        $response = ResponseFormatter::success(
            ['post_id' => 42],
            'correlation-123',
            150
        );

        $this->assertTrue($response['success']);
        $this->assertEquals(['post_id' => 42], $response['data']);
        $this->assertEquals('correlation-123', $response['meta']['correlation_id']);
        $this->assertEquals(150, $response['meta']['execution_time_ms']);
        $this->assertArrayHasKey('timestamp', $response['meta']);
    }

    public function test_error_format_includes_error_details(): void {
        $response = ResponseFormatter::error(
            'validation_error',
            'Invalid input',
            ['field' => 'title', 'error' => 'Required']
        );

        $this->assertFalse($response['success']);
        $this->assertEquals('validation_error', $response['error']['code']);
        $this->assertEquals('Invalid input', $response['error']['message']);
    }

    public function test_error_maps_to_correct_http_status(): void {
        $this->assertEquals(401, ResponseFormatter::getHttpStatus('authentication_required'));
        $this->assertEquals(403, ResponseFormatter::getHttpStatus('insufficient_permissions'));
        $this->assertEquals(429, ResponseFormatter::getHttpStatus('rate_limit_exceeded'));
        $this->assertEquals(500, ResponseFormatter::getHttpStatus('internal_error'));
    }
}

class PrivacyRedactorTest extends TestCase {
    public function test_redacts_password_fields(): void {
        $data = ['username' => 'admin', 'password' => 'secret123'];

        $redacted = PrivacyRedactor::redact($data);

        $this->assertEquals('admin', $redacted['username']);
        $this->assertEquals('[REDACTED]', $redacted['password']);
    }

    public function test_redacts_nested_sensitive_fields(): void {
        $data = [
            'user' => [
                'name' => 'John',
                'api_key' => 'sk-12345',
            ],
        ];

        $redacted = PrivacyRedactor::redact($data);

        $this->assertEquals('John', $redacted['user']['name']);
        $this->assertEquals('[REDACTED]', $redacted['user']['api_key']);
    }

    public function test_is_pure_function(): void {
        $original = ['password' => 'secret'];
        $redacted = PrivacyRedactor::redact($original);

        // Original unchanged
        $this->assertEquals('secret', $original['password']);
        $this->assertEquals('[REDACTED]', $redacted['password']);
    }
}
```

#### Phase 1.12b: Implement Error Handling (GREEN)

**FP Approach: Pure functions for formatting and redaction**

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Http;

/**
 * Pure functions for response formatting.
 */
final class ResponseFormatter {
    /**
     * Format success response.
     * Pure function: same inputs produce same output.
     */
    public static function success(
        array $data,
        string $correlationId,
        int $executionTimeMs
    ): array {
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'correlation_id' => $correlationId,
                'execution_time_ms' => $executionTimeMs,
                'timestamp' => gmdate('c'),
            ],
        ];
    }

    /**
     * Format error response.
     */
    public static function error(
        string $code,
        string $message,
        ?array $details = null,
        ?int $retryAfter = null
    ): array {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
            'meta' => [
                'timestamp' => gmdate('c'),
            ],
        ];

        if ($details !== null) {
            $response['error']['details'] = $details;
        }

        if ($retryAfter !== null) {
            $response['meta']['retry_after'] = $retryAfter;
        }

        return $response;
    }

    /**
     * Map error code to HTTP status.
     * Pure function: deterministic mapping.
     */
    public static function getHttpStatus(string $code): int {
        return ErrorCodes::HTTP_STATUS_MAP[$code] ?? 500;
    }
}

/**
 * Pure functions for privacy redaction.
 */
final class PrivacyRedactor {
    private const SENSITIVE_FIELDS = [
        'password', 'token', 'api_key', 'secret', 'user_pass',
        'apikey', 'access_token', 'refresh_token',
    ];

    /**
     * Redact sensitive fields from data.
     * Pure function: returns new array, does not modify input.
     */
    public static function redact(array $data): array {
        return self::redactRecursive($data);
    }

    private static function redactRecursive(array $data): array {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::redactRecursive($value);
            } elseif (self::isSensitiveField($key)) {
                $result[$key] = '[REDACTED]';
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private static function isSensitiveField(string $key): bool {
        return in_array(strtolower($key), self::SENSITIVE_FIELDS, true);
    }
}
```

#### Phase 1.12c: Refactor Error Handling (REFACTOR)

- Ensure all formatters are pure
- Add comprehensive error code coverage
- Document all error codes

**Acceptance criteria:**
- [ ] All error handling tests pass (GREEN)
- [ ] All responses follow standardized format
- [ ] Error codes map to correct HTTP status
- [ ] Sensitive fields redacted in logs/webhooks
- [ ] ResponseFormatter and PrivacyRedactor are pure functions

---

### Phase 1.13: GDPR Compliance & Privacy
**Duration:** 1-2 hours
**Dependencies:** Phase 1.5

#### Phase 1.13a: Write GDPR Tests (RED)

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Privacy;

use PHPUnit\Framework\TestCase;

class GDPRComplianceTest extends TestCase {
    public function test_registers_data_exporter(): void {
        // Verify exporter registered with WordPress
    }

    public function test_registers_data_eraser(): void {
        // Verify eraser registered with WordPress
    }

    public function test_exports_user_activity_logs(): void {
        // Verify export includes activity log data
    }

    public function test_erases_user_activity_logs(): void {
        // Verify erase removes user's logs
    }
}
```

#### Phase 1.13b: Implement GDPR Compliance (GREEN)

Implementation follows spec - register WordPress privacy hooks.

#### Phase 1.13c: Refactor GDPR (REFACTOR)

- Ensure export format is correct
- Add pagination for large exports

**Acceptance criteria:**
- [ ] All GDPR tests pass (GREEN)
- [ ] User data export includes activity logs
- [ ] User data erasure removes activity logs

---

### Phase 1.14: Uninstall Handler
**Duration:** 1 hour
**Dependencies:** Phase 1.3

#### Phase 1.14a: Write Uninstall Tests (RED)

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests;

use PHPUnit\Framework\TestCase;

class UninstallTest extends TestCase {
    public function test_drops_activity_log_table(): void {
        // Verify table dropped
    }

    public function test_drops_webhook_queue_table(): void {
        // Verify table dropped
    }

    public function test_deletes_all_options(): void {
        // Verify options deleted
    }

    public function test_removes_custom_capabilities(): void {
        // Verify capabilities removed from roles
    }

    public function test_removes_ai_agent_role(): void {
        // Verify role removed
    }
}
```

#### Phase 1.14b: Implement Uninstall Handler (GREEN)

Implementation follows spec - complete cleanup in uninstall.php.

#### Phase 1.14c: Refactor Uninstall (REFACTOR)

- Add safety checks
- Log uninstall for debugging

**Acceptance criteria:**
- [ ] All uninstall tests pass (GREEN)
- [ ] All tables dropped
- [ ] All options deleted
- [ ] Custom capabilities removed

---

### Phase 1.15: Integration Tests
**Duration:** 4-5 hours
**Dependencies:** All previous phases

This phase runs the full test suite and adds integration tests.

#### Phase 1.15a: Verify All Unit Tests Pass (RED/GREEN)

Run complete test suite:
```bash
vendor/bin/phpunit --coverage-html coverage/
```

Verify 85%+ coverage achieved.

#### Phase 1.15b: Add Integration Tests

**Files to create:**
- `tests/integration/AbilityExecutionTest.php`
- `tests/integration/MCP/ServerTest.php`

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Integration;

use WP_UnitTestCase;

class AbilityExecutionTest extends WP_UnitTestCase {
    public function test_full_ability_execution_flow(): void {
        // Create test user
        $userId = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($userId);

        // Execute ability
        $executor = Plugin::get_instance()->get_service('ability_executor');
        $ability = new ListPosts();

        $result = $executor->execute($ability, ['page' => 1]);

        // Verify success
        $this->assertTrue($result->isSuccess);

        // Verify logging occurred
        global $wpdb;
        $log = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}fa_wpmcp_activity_log ORDER BY id DESC LIMIT 1");
        $this->assertEquals('fa-wpmcp/list-posts', $log->ability_name);
    }
}
```

#### Phase 1.15c: Performance and Edge Case Testing

- Test rate limiting under load
- Test webhook retry mechanism
- Test permission edge cases

**Acceptance criteria:**
- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] 85%+ code coverage
- [ ] Critical paths tested
- [ ] Edge cases covered

---

### Phase 1.16: Documentation
**Duration:** 2-3 hours
**Dependencies:** All previous phases

**Files to create:**
- `README.md` - Plugin overview and quick start
- `docs/extension-guide.md` - How to add abilities
- `docs/security.md` - Security best practices
- `docs/api-reference.md` - Ability schemas
- `docs/functional-patterns.md` - FP patterns used

#### Phase 1.16a: Write Documentation Tests (RED)

```php
<?php
// Verify code examples in documentation compile
namespace FAWpmcp\Tests\Docs;

use PHPUnit\Framework\TestCase;

class DocumentationExamplesTest extends TestCase {
    public function test_extension_guide_example_compiles(): void {
        // Extract code blocks from docs/extension-guide.md
        // Verify they compile
    }
}
```

#### Phase 1.16b: Write Documentation (GREEN)

Create comprehensive documentation including FP patterns guide:

**docs/functional-patterns.md:**
```markdown
# Functional Programming Patterns

This plugin uses FP principles for maintainability and testability.

## Pure Functions

Business logic is implemented as pure functions - same inputs always
produce same outputs, with no side effects.

### Example: PermissionChecker

```php
// Pure function: no database access, no global state
$result = PermissionChecker::check($settings, $abilityName, $operationType);
```

## Immutable Value Objects

Data is represented as immutable value objects using PHP 8.0+
readonly properties.

### Example: RateLimitResult

```php
final readonly class RateLimitResult {
    public function __construct(
        public bool $allowed,
        public int $retryAfter,
        public string $limitType,
    ) {}
}
```

## Pipeline Composition

Complex operations are composed as pipelines where each step
transforms data or returns early on failure.

### Example: AbilityExecutor

```php
$pipeline = ExecutionPipeline::create()
    ->pipe(fn($ctx) => $this->validateInput($ctx))
    ->pipe(fn($ctx) => $this->checkPermissions($ctx))
    ->pipe(fn($ctx) => $this->checkRateLimit($ctx))
    ->pipe(fn($ctx) => $this->executeAbility($ctx));
```

## When Side Effects Are Acceptable

Side effects are isolated to:
- Database operations (read/write)
- WordPress hook integration
- Logging and HTTP responses
```

#### Phase 1.16c: Review and Finalize Documentation (REFACTOR)

- Verify all code examples work
- Add diagrams where helpful
- Ensure consistency

**Acceptance criteria:**
- [ ] README covers installation
- [ ] Extension guide is complete
- [ ] Security documented
- [ ] API reference accurate
- [ ] FP patterns documented

---

## Testing Strategy

### Unit Testing (PHPUnit + Brain Monkey)

| Component | Test Focus | Coverage Target |
|-----------|------------|-----------------|
| PermissionChecker | Pure function logic | 95% |
| RateLimitCalculator | Pure calculations | 95% |
| ExecutionPipeline | Composition, failure handling | 90% |
| Value Objects | Immutability, construction | 90% |
| Abilities | Input/output transformations | 85% |
| ResponseFormatter | Pure formatting | 95% |
| PrivacyRedactor | Pure redaction | 95% |

### Integration Testing (WP_UnitTestCase)

After unit tests pass, create integration tests for:
- Full ability execution flow
- Database operations
- REST API endpoints
- MCP server discovery

### TDD Metrics

Track red-green-refactor cycles:
- Tests written before implementation
- Minimal code to pass tests
- Refactoring improves code quality

---

## Risks & Considerations

### Technical Risks

1. **Abilities API Changes**
   - Risk: WordPress 6.9+ may change API
   - Mitigation: Pin to known working version, test with beta releases

2. **MCP Adapter Compatibility**
   - Risk: MCP spec evolves
   - Mitigation: Follow WP Core team releases, abstract adapter usage

3. **PHP 8.0+ readonly Properties**
   - Risk: Hosting environments may not support PHP 8.0
   - Mitigation: Require PHP 8.0+ in plugin header, document requirement

### FP Adoption Risks

1. **Team Familiarity**
   - Risk: Developers unfamiliar with FP patterns
   - Mitigation: Document patterns, provide examples

2. **Over-Engineering**
   - Risk: FP patterns add unnecessary complexity
   - Mitigation: Be pragmatic - use FP where it adds value

---

## Estimated Complexity (Updated for TDD)

| Phase | Effort | Complexity | Risk |
|-------|--------|------------|------|
| 1.1 Scaffolding | 1-2h | Low | Low |
| 1.2 Plugin Class | 1-2h | Low | Low |
| 1.3 Database | 2-3h | Medium | Low |
| 1.4 Permissions | 4-5h | Medium | Medium |
| 1.5 Logging | 3-4h | Medium | Low |
| 1.6 Rate Limiting | 3-4h | Medium | Medium |
| 1.7 Webhooks | 4-5h | High | Medium |
| 1.8 Ability Framework | 4-5h | High | Medium |
| 1.9 MCP Integration | 2-3h | Medium | Medium |
| 1.10 Post Abilities | 5-6h | Medium | Low |
| 1.11 Admin UI | 4-5h | High | Low |
| 1.12 Error Handling | 2-3h | Medium | Low |
| 1.13 GDPR | 1-2h | Low | Low |
| 1.14 Uninstall | 1h | Low | Low |
| 1.15 Integration Tests | 4-5h | High | Low |
| 1.16 Documentation | 3-4h | Low | Low |

**Total Estimated:** 45-60 hours (increased for TDD overhead)

---

## Dependency Graph

```
Phase 1.1 (Scaffolding)
    |
Phase 1.2 (Plugin Class + Value Objects)
    |
    +---> Phase 1.3 (Database) ----------------------+
    |         |                                       |
    |     Phase 1.5 (Logging)                        |
    |         |                                       |
    |     Phase 1.7 (Webhooks) <---------------------+
    |                                                 |
    +---> Phase 1.4 (Permissions - FP)               |
    |         |                                       |
    |     Phase 1.8 (Ability Framework - Pipeline) <-+
    |         |
    |     Phase 1.9 (MCP Integration)
    |         |
    |     Phase 1.10 (Post Abilities)
    |
    +---> Phase 1.6 (Rate Limiting - FP)
              |
          Phase 1.8 (Ability Framework) <------------+
              |
          Phase 1.11 (Admin UI)
              |
          Phase 1.12 (Error Handling - FP)
              |
          Phase 1.13 (GDPR)
              |
          Phase 1.14 (Uninstall)
              |
          Phase 1.15 (Integration Tests)
              |
          Phase 1.16 (Documentation)
```

---

## File Summary (Updated)

### New Files to Create

```
fa-wpmcp/
├── fa-wpmcp.php
├── composer.json
├── uninstall.php
├── .phpcs.xml
├── phpunit.xml.dist
├── README.md
├── src/
│   ├── Plugin.php
│   ├── ValueObjects/
│   │   ├── PermissionSettings.php
│   │   ├── RateLimit.php
│   │   ├── RateLimitResult.php
│   │   ├── LogEntry.php
│   │   ├── WebhookPayload.php
│   │   └── Result.php
│   ├── Abilities/
│   │   ├── AbilityRegistry.php
│   │   ├── CategoryRegistry.php
│   │   ├── AbstractAbility.php
│   │   ├── AbilityExecutor.php
│   │   ├── ExecutionPipeline.php
│   │   └── Posts/
│   │       ├── ListPosts.php
│   │       ├── GetPost.php
│   │       ├── CreatePost.php
│   │       └── UpdatePost.php
│   ├── Admin/
│   │   ├── SettingsPage.php
│   │   ├── AbilityTable.php
│   │   └── LogViewer.php
│   ├── Database/
│   │   ├── Schema.php
│   │   └── Migrator.php
│   ├── Http/
│   │   ├── ResponseFormatter.php
│   │   ├── ErrorCodes.php
│   │   └── PrivacyRedactor.php
│   ├── Logging/
│   │   ├── ActivityLogger.php
│   │   ├── LogEntryBuilder.php
│   │   └── LogRepository.php
│   ├── MCP/
│   │   └── ServerConfig.php
│   ├── Permissions/
│   │   ├── PermissionChecker.php
│   │   ├── PermissionManager.php
│   │   ├── Settings.php
│   │   └── Capabilities.php
│   ├── RateLimiting/
│   │   ├── RateLimitCalculator.php
│   │   ├── RateLimiter.php
│   │   ├── RateLimitStore.php
│   │   └── RateLimitConfig.php
│   └── Webhooks/
│       ├── PayloadBuilder.php
│       ├── SignatureGenerator.php
│       ├── WebhookManager.php
│       ├── WebhookQueue.php
│       └── WebhookSender.php
├── tests/
│   ├── phpunit/
│   │   ├── bootstrap.php
│   │   ├── ValueObjects/
│   │   │   ├── PermissionSettingsTest.php
│   │   │   ├── RateLimitTest.php
│   │   │   ├── LogEntryTest.php
│   │   │   └── ResultTest.php
│   │   ├── Abilities/
│   │   │   ├── AbilityRegistryTest.php
│   │   │   ├── ExecutionPipelineTest.php
│   │   │   ├── AbilityExecutorTest.php
│   │   │   └── Posts/
│   │   │       ├── ListPostsTest.php
│   │   │       ├── GetPostTest.php
│   │   │       ├── CreatePostTest.php
│   │   │       └── UpdatePostTest.php
│   │   ├── Database/
│   │   │   ├── SchemaTest.php
│   │   │   └── MigratorTest.php
│   │   ├── Http/
│   │   │   ├── ResponseFormatterTest.php
│   │   │   └── PrivacyRedactorTest.php
│   │   ├── Logging/
│   │   │   ├── LogEntryBuilderTest.php
│   │   │   └── ActivityLoggerTest.php
│   │   ├── Permissions/
│   │   │   ├── PermissionCheckerTest.php
│   │   │   └── SettingsTest.php
│   │   ├── RateLimiting/
│   │   │   ├── RateLimitCalculatorTest.php
│   │   │   └── RateLimiterTest.php
│   │   └── Webhooks/
│   │       ├── PayloadBuilderTest.php
│   │       ├── SignatureGeneratorTest.php
│   │       └── WebhookManagerTest.php
│   └── integration/
│       ├── AbilityExecutionTest.php
│       └── MCP/
│           └── ServerTest.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
└── docs/
    ├── extension-guide.md
    ├── security.md
    ├── api-reference.md
    └── functional-patterns.md
```

**Total: 60+ files** (increased for TDD test files and FP components)

---

## Next Steps

1. Begin with Phase 1.1a: Write Scaffolding Tests (RED)
2. Implement Phase 1.1b: Create Scaffolding (GREEN)
3. Refactor Phase 1.1c: Clean up (REFACTOR)
4. Proceed through phases following TDD cycle
5. Run tests after each phase
6. Update this plan if scope changes

---

*Plan generated by plan-agent on 2026-01-20*
*Revised for TDD + FP emphasis on 2026-01-20*
