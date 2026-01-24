# Development Guide

This guide provides detailed instructions for developers working on the FA-WPMCP plugin, with emphasis on WordPress 6.9 Abilities API requirements and best practices.

## Table of Contents

- [WordPress 6.9 Abilities API Requirements](#wordpress-69-abilities-api-requirements)
- [Adding New Abilities](#adding-new-abilities)
- [Category Management](#category-management)
- [Testing Guidelines](#testing-guidelines)
- [Debugging](#debugging)
- [Best Practices](#best-practices)

## WordPress 6.9 Abilities API Requirements

### Critical Requirements

The WordPress 6.9 Abilities API has strict ordering requirements that **must** be followed:

1. **Categories MUST be registered before abilities**
   - Registering an ability with an unregistered category triggers a `DoingItWrong` warning
   - The Abilities Registry checks category existence during ability registration

2. **Hook priorities matter**
   - Category registration: Use `wp_abilities_api_categories_init` hook with **priority 5**
   - Ability registration: Use `wp_abilities_api_init` hook with **priority 15**
   - McpAdapter uses default priority 10, so our hooks run before (categories) and after (abilities)

3. **McpAdapter initialization timing**
   - McpAdapter must be initialized AFTER registering the hooks
   - This ensures our category and ability registration callbacks are in place before the adapter fires the hooks

### Implementation in Plugin.php

Our implementation follows this pattern:

```php
// 1. Register hooks FIRST (in Plugin::init())
add_action(
    'wp_abilities_api_categories_init',
    function () {
        $this->registerAbilityCategories();
    },
    5  // Priority 5: Run before McpAdapter's default priority 10
);

add_action(
    'wp_abilities_api_init',
    function () use ( $ability_registry, $ability_executor ) {
        $this->registerAbilitiesWithWordPress( $ability_registry, $ability_executor );
    },
    15  // Priority 15: Run after McpAdapter's default priority 10
);

// 2. Initialize McpAdapter AFTER hooks are registered
McpAdapter::instance();
```

**Why this works:**
- McpAdapter fires `wp_abilities_api_categories_init` when initialized
- Our callback (priority 5) registers categories before McpAdapter's own category registration (priority 10)
- Then McpAdapter fires `wp_abilities_api_init`
- Our callback (priority 15) registers abilities after categories are guaranteed to exist

## Adding New Abilities

### Step 1: Determine Category

Check if an appropriate category exists in `Plugin::registerAbilityCategories()`:

**Existing categories:**
- `posts-pages` - Posts, pages, custom post types
- `comments` - Comment management
- `media` - Media library operations
- `taxonomies` - Categories, tags, custom taxonomies
- `users` - User management
- `settings` - WordPress options
- `plugins` - Plugin management
- `themes` - Theme management
- `privacy` - GDPR data export/erasure

**If you need a new category**, see [Category Management](#category-management) below.

### Step 2: Create Ability Class

Create a new class extending `AbstractAbility` in the appropriate namespace:

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Abilities\Posts;

use FAWpmcp\Abilities\AbstractAbility;

class GetPostAbility extends AbstractAbility {

    public function get_name(): string {
        return 'posts-pages.get';  // Format: category.action
    }

    public function get_category(): string {
        return 'posts-pages';  // Must match registered category
    }

    public function get_label(): string {
        return __('Get Post', 'fa-wpmcp');
    }

    public function get_description(): string {
        return __('Retrieve a single post by ID', 'fa-wpmcp');
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
        return 'read';  // WordPress capability required
    }

    public function get_operation_type(): string {
        return 'READ';  // READ, WRITE, or DELETE
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

### Step 3: Register the Ability

Add to the appropriate registration method in `Plugin.php`:

```php
private function registerPostAbilities(\FAWpmcp\Abilities\AbilityRegistry $registry): void {
    // ... existing abilities ...

    $registry->register(new \FAWpmcp\Abilities\Posts\GetPostAbility());
}
```

If creating a new category, create a new registration method:

```php
private function registerMyNewAbilities(\FAWpmcp\Abilities\AbilityRegistry $registry): void {
    $registry->register(new \FAWpmcp\Abilities\MyCategory\MyAbility());
}

// Call it in init()
public function init(): void {
    // ... existing code ...
    $this->registerMyNewAbilities($ability_registry);
}
```

### Step 4: Write Tests

Create test file in `tests/phpunit/Abilities/`:

```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Posts;

use Brain\Monkey\Functions;
use FAWpmcp\Abilities\Posts\GetPostAbility;
use FAWpmcp\Tests\TestCase;

class GetPostAbilityTest extends TestCase {

    public function test_executes_successfully(): void {
        Functions\when('get_post')->justReturn((object)[
            'ID' => 1,
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_status' => 'publish'
        ]);

        $ability = new GetPostAbility();
        $result = $ability->execute(['id' => 1]);

        $this->assertTrue($result->is_success());
        $this->assertEquals('Test Post', $result->data['title']);
    }

    public function test_throws_exception_when_post_not_found(): void {
        Functions\when('get_post')->justReturn(null);

        $ability = new GetPostAbility();
        $result = $ability->execute(['id' => 999]);

        $this->assertFalse($result->is_success());
        $this->assertStringContainsString('Post not found', $result->error_message);
    }
}
```

### Step 5: Verify Registration

Test the ability is properly registered:

```bash
# List all abilities
curl -s http://localhost/wp-json/wp/v2/abilities \
  --user username:app_password | jq '.[] | {name, category}'

# Test your specific ability
curl -s http://localhost/wp-json/wp-abilities/v1/abilities/posts-pages.get \
  --user username:app_password \
  -H "Content-Type: application/json" \
  -d '{"id": 1}' | jq
```

## Category Management

### Adding a New Category

If you need to add a completely new category:

#### 1. Add to `registerAbilityCategories()` in Plugin.php

```php
private function registerAbilityCategories(): void {
    if (!function_exists('wp_register_ability_category')) {
        return;
    }

    $categories = array(
        // ... existing categories ...

        'my-category' => array(
            'label'       => __('My Category', 'fa-wpmcp'),
            'description' => __('Abilities for my new feature', 'fa-wpmcp'),
        ),
    );

    foreach ($categories as $slug => $args) {
        wp_register_ability_category($slug, $args);
    }
}
```

#### 2. Verify Category Registration

During development, you can temporarily add debug logging to verify registration:

```php
// TEMPORARY: Remove before production
foreach ($categories as $slug => $args) {
    wp_register_ability_category($slug, $args);

    // Verify registration
    $registry = \WP_Ability_Categories_Registry::get_instance();
    if ($registry && method_exists($registry, 'is_registered')) {
        $is_registered = $registry->is_registered($slug);
        error_log(sprintf('Category %s registered: %s', $slug, $is_registered ? 'YES' : 'NO'));
    }
}
```

**Important:** Remove debug logging before committing to production!

#### 3. Update Documentation

Update the category list in:
- `docs/DEVELOPMENT.md` (this file)
- `README.md` (Features section)
- Any other relevant documentation

### Category Naming Conventions

- Use lowercase with hyphens: `my-category` not `MyCategory` or `my_category`
- Be descriptive but concise: `posts-pages` not `posts-and-pages-and-custom-post-types`
- Match WordPress conventions where possible: `users`, `plugins`, `themes`

## Testing Guidelines

### Test-Driven Development (TDD)

This project follows TDD principles:

1. **Write the test first** - Define expected behavior
2. **Watch it fail** - Verify test catches the missing feature
3. **Implement** - Write minimal code to pass
4. **Refactor** - Clean up while keeping tests green

### Running Tests

```bash
# All tests
composer test

# Specific test class
./vendor/bin/phpunit tests/phpunit/Abilities/Posts/GetPostAbilityTest.php

# With coverage
composer test:coverage
```

### Testing WordPress Functions

Use **Brain\Monkey** for mocking WordPress functions:

```php
use Brain\Monkey\Functions;

// Mock a function return value
Functions\when('get_post')->justReturn($post_object);

// Expect a function to be called
Functions\expect('wp_insert_post')
    ->once()
    ->with(['post_title' => 'Test'])
    ->andReturn(123);

// Stub a function
Functions\stubs([
    'current_user_can' => true,
    'get_current_user_id' => 42
]);
```

### Coverage Requirements

- **Target:** 80%+ coverage for new code
- **Current:** 74.33% overall (actively improving)
- Run `composer test:coverage` to generate HTML report at `tests/coverage/index.html`

## Debugging

### Common Issues

#### "DoingItWrong: Ability category not registered"

**Cause:** Ability registered before its category

**Solution:**
1. Verify category exists in `registerAbilityCategories()`
2. Check hook priorities (categories at 5, abilities at 15)
3. See [DEBUGGING_CATEGORIES.md](../DEBUGGING_CATEGORIES.md) for detailed troubleshooting

#### MCP Tools Not Appearing

**Cause:** Abilities not registered with WordPress API

**Solution:**
1. Check WordPress version (requires 6.9+)
2. Verify McpAdapter is initialized: `McpAdapter::instance()` called in `Plugin::init()`
3. Check REST API: `curl http://localhost/wp-json/wp/v2/abilities`

#### Abilities Registry Empty

**Cause:** Abilities not registered or hook fired at wrong time

**Solution:**
1. Verify ability registration in appropriate method (`registerPostAbilities()`, etc.)
2. Check that registration method is called in `Plugin::init()`
3. Verify hook callback is registered BEFORE `McpAdapter::instance()`

### Debug Logging During Development

When debugging complex integration issues, temporary debug logging can help:

```php
// TEMPORARY: Add during debugging, remove before committing
error_log('FA WPMCP: About to register categories');

foreach ($categories as $slug => $args) {
    wp_register_ability_category($slug, $args);
    error_log(sprintf('FA WPMCP: Registered category: %s', $slug));
}

error_log('FA WPMCP: Finished registering categories');
```

**Critical:** Always remove debug logging before production deployment!

**Workflow:**
1. Add debug logging to diagnose issue
2. Verify the fix works
3. Remove ALL debug logging
4. Commit clean code

See [DEBUGGING_CATEGORIES.md](../DEBUGGING_CATEGORIES.md) for detailed debugging procedures.

### Verification Commands

```bash
# Check WordPress version
wp core version

# List registered abilities
curl -s http://localhost/wp-json/wp/v2/abilities \
  --user username:app_password | jq 'length'

# Check specific category
curl -s http://localhost/wp-json/wp/v2/abilities \
  --user username:app_password | \
  jq '.[] | select(.category == "posts-pages") | .name'

# Verify categories are unique
curl -s http://localhost/wp-json/wp/v2/abilities \
  --user username:app_password | \
  jq -r '.[].category' | sort | uniq -c

# List all registered ability categories
curl -s http://localhost/wp-json/wp-abilities/v1/categories \
  --user username:app_password | \
  jq '.[] | {slug, label, description}'

# Check specific category details
curl -s http://localhost/wp-json/wp-abilities/v1/categories/posts-pages \
  --user username:app_password | jq '.'

# Verify all plugin categories are registered
curl -s http://localhost/wp-json/wp-abilities/v1/categories \
  --user username:app_password | \
  jq -r '.[].slug' | grep -E '^(posts-pages|comments|media|taxonomies|users|settings|plugins|themes|privacy)$'
```

## Best Practices

### Code Organization

1. **One ability per file** - `src/Abilities/Posts/GetPostAbility.php`
2. **Group by category** - All post abilities in `src/Abilities/Posts/`
3. **Consistent naming** - `{Action}{Category}Ability` (e.g., `GetPostAbility`)

### Hook Priorities

| Hook | Priority | Purpose |
|------|----------|---------|
| `plugins_loaded` | 1 | Initialize plugin |
| `wp_abilities_api_categories_init` | 5 | Register categories (before McpAdapter) |
| `wp_abilities_api_init` | 15 | Register abilities (after McpAdapter) |
| `rest_api_init` | 15 | Initialize REST endpoints |

**Why these priorities?**
- McpAdapter uses priority 10 for both hooks
- Categories at 5 ensures they exist before McpAdapter processes
- Abilities at 15 ensures they register after categories are verified

### Type Safety

This project uses PHP 8.1+ features for type safety:

```php
// Declare strict types in every file
declare(strict_types=1);

// Type all parameters and returns
public function execute(array $input): ExecutionResult

// Use readonly properties for immutability
public readonly string $name;

// Use union types where appropriate
public function getValue(): string|null
```

### Error Handling

```php
// Throw specific exceptions
throw new \InvalidArgumentException('Post ID is required');
throw new \RuntimeException('Database query failed');

// Return ExecutionResult for ability operations
return ExecutionResult::success(['data' => $value]);
return ExecutionResult::failure('Operation failed', 'ERROR_CODE');
```

### Documentation

- **PHPDoc blocks** on all public methods
- **Inline comments** for complex logic only (code should be self-documenting)
- **README updates** for new features
- **Test descriptions** that explain intent: `test_throws_exception_when_post_not_found()`

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/add-menu-abilities

# Make changes, write tests first
# Run tests continuously
composer test

# Check code standards
composer phpcs

# Fix code standards automatically
composer phpcbf

# Run static analysis
composer phpstan

# Commit with descriptive message
git commit -m "Add menu management abilities

- Create GetMenuAbility
- Create ListMenusAbility
- Add 'menus' category
- Include unit tests with 95% coverage"

# Push and create PR
git push origin feature/add-menu-abilities
```

### Performance Considerations

- **Cache expensive operations** - Use transients for API responses
- **Limit database queries** - Batch operations where possible
- **Validate early** - Reject invalid input before processing
- **Log selectively** - Don't log sensitive data (PII is auto-redacted)

## Code Standards

### PSR-12 Compliance

Follow PSR-12 coding standards:

```bash
# Check standards
composer phpcs

# Auto-fix most issues
composer phpcbf
```

### PHPStan Level 8

Maintain strict static analysis:

```bash
composer phpstan
```

Fix all errors before committing. No suppressions allowed except for WordPress core function issues.

### WordPress Coding Standards

Where PSR-12 conflicts with WordPress standards, PSR-12 takes precedence (this is a modern PHP project with WordPress integration, not a traditional WordPress plugin).

## Resources

- [WordPress Abilities API Documentation](https://developer.wordpress.org/apis/abilities/)
- [Model Context Protocol Specification](https://spec.modelcontextprotocol.io/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [Brain\Monkey Testing Library](https://giuseppe-mazzapica.gitbook.io/brain-monkey/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)

## Getting Help

- **Issues:** Check existing issues and debugging guides first
- **Questions:** Create a GitHub discussion
- **Bugs:** Include debug.log output and reproduction steps
- **Features:** Discuss architecture before implementing

---

**Remember:** Categories before abilities, hooks before McpAdapter, tests before code!
