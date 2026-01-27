# FA-WPMCP Ability Test Checklist

Testing all fa-wpmcp abilities via MCP direct tool calls, verified with WP-CLI.

**Test Date**: 2026-01-27
**Server**: localhost (Docker)

---

## Posts

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 1 | `fa-wpmcp-create-post` | [PASS] | Requires `title`, `content`, `status` |
| 2 | `fa-wpmcp-list-posts` | [PASS] | |
| 3 | `fa-wpmcp-get-post` | [PASS] | Requires `post_id` |
| 4 | `fa-wpmcp-update-post` | [PASS] | Requires `post_id` |
| 5 | `fa-wpmcp-delete-post` | [PASS] | Requires `post_id`; moves to trash |

## Comments

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 6 | `fa-wpmcp-create-comment` | [PASS] | Requires `post_id`, `author`, `email`, `content` |
| 7 | `fa-wpmcp-list-comments` | [PASS] | |
| 8 | `fa-wpmcp-get-comment` | [PASS] | Requires `comment_id` |
| 9 | `fa-wpmcp-update-comment` | [PASS] | Requires `comment_id`, `status` (approve/hold/spam/trash) |
| 10 | `fa-wpmcp-delete-comment` | [PASS] | Requires `comment_id`; moves to trash |

## Media

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 11 | `fa-wpmcp-upload-media` | [PASS] | Requires `url`, `title` |
| 12 | `fa-wpmcp-list-media` | [PASS] | |
| 13 | `fa-wpmcp-get-media` | [PASS] | Requires `media_id` |
| 14 | `fa-wpmcp-update-media` | [PASS] | Requires `media_id` |
| 15 | `fa-wpmcp-delete-media` | [PASS] | Requires `media_id`; moves to trash |

## Terms (Taxonomies)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 16 | `fa-wpmcp-create-term` | [PASS] | Requires `taxonomy`, `name` |
| 17 | `fa-wpmcp-list-terms` | [PASS] | Requires `taxonomy` |
| 18 | `fa-wpmcp-get-term` | [PASS] | Requires `term_id`, `taxonomy` |
| 19 | `fa-wpmcp-update-term` | [PASS] | Requires `term_id`, `taxonomy` |
| 20 | `fa-wpmcp-delete-term` | [PASS] | Requires `term_id`, `taxonomy` |
| 21 | `fa-wpmcp-list-taxonomies` | [PASS] | Fixed: `rest_base` now returns empty string instead of false |
| 22 | `fa-wpmcp-get-taxonomy` | [PASS] | Requires `taxonomy` |

## Post Types

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 23 | `fa-wpmcp-list-post-types` | [PASS] | Lists all registered post types |
| 24 | `fa-wpmcp-get-post-type` | [PASS] | Requires `post_type` |

## Users

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 25 | `fa-wpmcp-create-user` | [PASS] | Requires `username`, `email`, `password` |
| 26 | `fa-wpmcp-list-users` | [PASS] | Fixed: count_users() returns array not object |
| 27 | `fa-wpmcp-get-user` | [PASS] | Requires `user_id` |
| 28 | `fa-wpmcp-update-user` | [PASS] | Requires `user_id` |
| 29 | `fa-wpmcp-delete-user` | [PASS] | Fixed: `reassigned` returns 0 instead of null |

## Options (Settings)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 30 | `fa-wpmcp-update-option` | [PASS] | Requires `option_name`, `option_value` |
| 31 | `fa-wpmcp-list-options` | [PASS] | |
| 32 | `fa-wpmcp-get-option` | [PASS] | Requires `option_name` |
| 33 | `fa-wpmcp-delete-option` | [PASS] | Requires `option_name` |

## Plugins

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 34 | `fa-wpmcp-list-plugins` | [PASS] | |
| 35 | `fa-wpmcp-get-plugin` | [PASS] | Requires `plugin` (full path e.g. `fa-wpmcp/fa-wpmcp.php`) |
| 36 | `fa-wpmcp-install-plugin` | [PASS] | Requires `slug` |
| 37 | `fa-wpmcp-activate-plugin` | [PASS] | Requires `plugin` |
| 38 | `fa-wpmcp-deactivate-plugin` | [PASS] | Requires `plugin` |
| 39 | `fa-wpmcp-update-plugin` | [PASS] | Requires `plugin` |
| 40 | `fa-wpmcp-delete-plugin` | [PASS] | Requires `plugin` |

## Themes

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 41 | `fa-wpmcp-list-themes` | [PASS] | |
| 42 | `fa-wpmcp-get-theme` | [PASS] | Requires `stylesheet` |
| 43 | `fa-wpmcp-status-theme` | [PASS] | Requires `stylesheet` |
| 44 | `fa-wpmcp-install-theme` | [PASS] | Requires `slug` |
| 45 | `fa-wpmcp-activate-theme` | [PASS]* | Requires `stylesheet`; *see warning below |
| 46 | `fa-wpmcp-update-theme` | [PASS] | Requires `stylesheet` |
| 47 | `fa-wpmcp-delete-theme` | [PASS] | Requires `stylesheet` |

## Privacy (GDPR)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 48 | `fa-wpmcp-create-export-request` | [PASS] | Requires `email` |
| 49 | `fa-wpmcp-create-erasure-request` | [PASS] | Requires `email` |
| 50 | `fa-wpmcp-list-privacy-requests` | [PASS] | |
| 51 | `fa-wpmcp-get-privacy-request` | [PASS] | Requires `request_id` |

## Cache

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 52 | `fa-wpmcp-get-cache-type` | [PASS] | Returns cache type (e.g., redis), persistent flag |
| 53 | `fa-wpmcp-get-cache-status` | [PASS] | Returns supported features and groups |
| 54 | `fa-wpmcp-flush-cache` | [PASS] | Flushes entire object cache |

## Maintenance

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 55 | `fa-wpmcp-get-maintenance-mode-status` | [PASS] | Returns active status and timestamp |
| 56 | `fa-wpmcp-activate-maintenance-mode` | [PASS] | Optional `expire_seconds` (default 600, max 3600) |
| 57 | `fa-wpmcp-deactivate-maintenance-mode` | [PASS] | Removes .maintenance file |

## Transients

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 58 | `fa-wpmcp-set-transient` | [PASS] | Requires `key`, `value`, `expiration` |
| 59 | `fa-wpmcp-get-transient` | [PASS] | Requires `key` |
| 60 | `fa-wpmcp-list-transients` | [PASS] | Returns 0 when Redis is active (transients in Redis) |
| 61 | `fa-wpmcp-delete-transient` | [PASS] | Requires `key` |

## Cron

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 62 | `fa-wpmcp-list-cron-events` | [PASS] | Fixed: `schedule` returns empty string for single events |
| 63 | `fa-wpmcp-get-cron-event` | [PASS] | Requires `hook` |
| 64 | `fa-wpmcp-schedule-cron-event` | [PASS] | Requires `hook`, `timestamp`, `recurrence` |
| 65 | `fa-wpmcp-unschedule-cron-event` | [PASS] | Requires `hook` |
| 66 | `fa-wpmcp-run-cron-event` | [PASS] | Requires `hook` |
| 67 | `fa-wpmcp-list-cron-schedules` | [PASS] | Returns available recurrence schedules |

## Roles

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 68 | `fa-wpmcp-list-roles` | [PASS] | Returns all roles with capabilities |
| 69 | `fa-wpmcp-get-role` | [PASS] | Requires `role` |
| 70 | `fa-wpmcp-create-role` | [PASS] | Requires `role`, `display_name`, `capabilities` |
| 71 | `fa-wpmcp-update-role` | [PASS] | Requires `role`, optional `add_capabilities`, `remove_capabilities` |
| 72 | `fa-wpmcp-delete-role` | [PASS] | Requires `role` |
| 87 | `fa-wpmcp-list-caps` | [PASS] | Requires `role`; returns capability names as array |
| 88 | `fa-wpmcp-add-cap` | [PASS] | Requires `role`, `capabilities` array |
| 89 | `fa-wpmcp-remove-cap` | [PASS] | Requires `role`, `capabilities` array |

## Menus

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 73 | `fa-wpmcp-list-menus` | [PASS] | Returns menus with locations and item counts |
| 74 | `fa-wpmcp-create-menu` | [PASS] | Requires `name`, optional `location` |
| 75 | `fa-wpmcp-get-menu` | [PASS] | Requires `menu` (ID or slug) |
| 76 | `fa-wpmcp-delete-menu` | [PASS] | Requires `menu` (ID or slug) |

## Widgets

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 77 | `fa-wpmcp-list-sidebars` | [PASS] | Returns all registered sidebars |
| 78 | `fa-wpmcp-get-sidebar` | [PASS] | Requires `sidebar_id` |
| 79 | `fa-wpmcp-list-widget-types` | [PASS] | Returns available widget types (33 on test site) |
| 80 | `fa-wpmcp-list-widgets` | [PASS] | Optional `sidebar_id` filter |
| 81 | `fa-wpmcp-get-widget` | [PASS] | Requires `widget_id` (format: {id_base}-{instance}) |
| 82 | `fa-wpmcp-add-widget` | [PASS] | Requires `id_base`, `sidebar_id`; optional `settings` |
| 83 | `fa-wpmcp-update-widget` | [PASS] | Requires `widget_id`; optional `settings` |
| 84 | `fa-wpmcp-delete-widget` | [PASS] | Requires `widget_id`; destructive |
| 85 | `fa-wpmcp-move-widget` | [PASS] | Requires `widget_id`, `sidebar_id`; optional `position` |
| 86 | `fa-wpmcp-reset-widgets` | [PASS] | Requires `sidebar_id`; destructive - removes all widgets from sidebar |

## Dotenv

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 90 | `fa-wpmcp-list-env-vars` | [PASS] | Lists environment variables from .env file |
| 91 | `fa-wpmcp-get-env-var` | [PASS] | Requires `key`; returns variable value |
| 92 | `fa-wpmcp-set-env-var` | [PASS] | Requires `key`, `value`; creates or updates variable |
| 93 | `fa-wpmcp-delete-env-var` | [PASS] | Requires `key`; removes variable from .env |

## Rewrite

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 94 | `fa-wpmcp-list-rewrite-rules` | [PASS] | Lists all rewrite rules; 0 when plain permalinks, 187+ with pretty |
| 95 | `fa-wpmcp-flush-rewrite-rules` | [PASS] | Flushes/regenerates rewrite rules |
| 96 | `fa-wpmcp-get-permalink-structure` | [PASS] | Gets current permalink structure |
| 97 | `fa-wpmcp-update-permalink-structure` | [PASS] | Updates permalink structure + auto-flush |

## Core

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 98 | `fa-wpmcp-get-core-version` | [PASS] | Returns WP 6.9, PHP, MySQL versions |
| 99 | `fa-wpmcp-check-core-updates` | [PASS] | Checks for available core updates |
| 100 | `fa-wpmcp-verify-checksums` | [PASS] | Verifies 3349 files; reports missing bundled themes |
| 101 | `fa-wpmcp-is-installed` | [PASS] | Returns installed status, db_ready, has_admin |
| 102 | `fa-wpmcp-update-database` | [PASS] | Reports db version; supports dry_run |

---

## Summary

**Total**: 102 abilities registered
**Exposed as MCP Tools**: 102
**Passed**: 102/102 (100%)
**Failed**: 0
**Not Exposed**: 0

### Warnings

1. **activate-theme**: Activating a theme that lacks `add_filter('wp_is_application_passwords_available', '__return_true')` in its functions.php will break MCP authentication for subsequent requests.

2. **activate-maintenance-mode**: Creates `.maintenance` file in ABSPATH. If this locks you out, remove it manually: `docker exec wordpress rm -f /var/www/html/web/wp/.maintenance`

### Bugs Fixed (2026-01-27)

1. **list-taxonomies**: Output validation failed - `rest_base` returned `false` for some taxonomies. Fixed to return empty string.

2. **delete-user**: Output validation failed - `reassigned` returned `null` when no reassignment. Fixed to return `0`.

3. **list-cron-events**: Output validation failed - `schedule` was `false` for single events. Fixed to return empty string.

4. **list-post-types / get-post-type**: Not exposed as MCP tools - `post-types` category was missing from MCP adapter filter. Added to exposed categories list and fixed `rest_base` type normalization.

### Bugs Fixed (2026-01-25)

1. **CreateComment/UpdateComment missing getOperationType()**: Both were missing the override, defaulting to 'read' and bypassing `global_write_enabled` check. Fixed in commit `c599b4b`.

2. **ListUsers type error**: `getTotalUsers()` expected `object` but WordPress's `count_users()` returns `array`. Fixed in commit `bfd8ef6`.

---

## Test Commands

### MCP Session Setup
```bash
SESSION=$(curl -s -X POST \
  -H "Authorization: Basic $(echo -n 'featherarms_admin:uFNyTM2nrGx0qc84347wCAbI' | base64)" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}' \
  "http://localhost/wp-json/mcp/mcp-adapter-default-server" -i 2>/dev/null | \
  grep -i "Mcp-Session-Id" | cut -d' ' -f2 | tr -d '\r')
echo "Session: $SESSION"
```

### MCP Tool Call Template
```bash
curl -s -X POST \
  -H "Authorization: Basic $(echo -n 'featherarms_admin:uFNyTM2nrGx0qc84347wCAbI' | base64)" \
  -H "Content-Type: application/json" \
  -H "Mcp-Session-Id: $SESSION" \
  -d '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"TOOL_NAME","arguments":{}}}' \
  "http://localhost/wp-json/mcp/mcp-adapter-default-server" | jq .
```

### WP-CLI Verification
```bash
docker exec wordpress wp --allow-root post list
docker exec wordpress wp --allow-root user list
docker exec wordpress wp --allow-root plugin list
docker exec wordpress wp --allow-root theme list
docker exec wordpress wp --allow-root option get OPTION_NAME
```

---

## Automated Integration Test Suite

A PHPUnit-based integration test suite tests core CRUD abilities via actual MCP protocol calls.

### Test Infrastructure

| File | Purpose |
|------|---------|
| `tests/integration/bootstrap.php` | PHPUnit bootstrap, autoloader setup |
| `tests/integration/Support/McpClient.php` | MCP protocol client (HTTP, JSON-RPC 2.0) |
| `tests/integration/Support/McpIntegrationTestCase.php` | Base test case with session management & cleanup |

### Test Coverage (52 tests, 176 assertions)

| Ability Category | Test File | Tests | Key Coverage |
|------------------|-----------|-------|--------------|
| **Posts** | `Abilities/Posts/PostsAbilityTest.php` | 10 | CRUD, status filter, lifecycle |
| **Comments** | `Abilities/Comments/CommentsAbilityTest.php` | 11 | CRUD, moderation status, post filter |
| **Users** | `Abilities/Users/UsersAbilityTest.php` | 15 | CRUD, roles, profile fields, lookup by ID/username/email |
| **Terms** | `Abilities/Terms/TermsAbilityTest.php` | 16 | CRUD for categories/tags, hierarchical parent-child, search |

### Running Integration Tests

```bash
# Load credentials and run all integration tests
export $(cat docker/wordpress-data/.mcp-test-credentials | grep -v '^#' | xargs)
./vendor/bin/phpunit --bootstrap tests/integration/bootstrap.php --testsuite integration

# Run specific ability tests
./vendor/bin/phpunit --bootstrap tests/integration/bootstrap.php --filter PostsAbilityTest
./vendor/bin/phpunit --bootstrap tests/integration/bootstrap.php --filter CommentsAbilityTest
./vendor/bin/phpunit --bootstrap tests/integration/bootstrap.php --filter UsersAbilityTest
./vendor/bin/phpunit --bootstrap tests/integration/bootstrap.php --filter TermsAbilityTest
```

### Test Credentials

Credentials are stored in `docker/wordpress-data/.mcp-test-credentials`:
```
MCP_TEST_BASE_URL=http://localhost/wp-json/mcp/mcp-adapter-default-server
MCP_TEST_USERNAME=<username>
MCP_TEST_PASSWORD=<app-password>
```

### Key Learnings from Integration Tests

1. **ID Normalization**: MCP responses use type-specific IDs (`post_id`, `comment_id`, `user_id`, `term_id`). The test base class normalizes these to generic `id` field.

2. **Nested Responses**: `GetComment` and `GetTerm` return nested objects (`{comment: {...}}`, `{term: {...}}`). Tests extract the inner object.

3. **UpdateComment is Moderation-Only**: Only supports status changes (`approve`, `hold`, `spam`, `trash`), not content/author updates.

4. **Comment Status Values**: WordPress returns mixed types (`'1'`, `1`, `'approved'`). Tests use `assertContains()` for flexibility.

5. **Terms Require Taxonomy**: All term operations require `taxonomy` parameter. Cleanup stores both `id` and `taxonomy`.

6. **No Trash for Users/Terms**: Deletion is always permanent (no trash support in WordPress for these types).

7. **Global Namespace Constants**: `McpClient.php` constants need backslash prefix (`\MCP_TEST_BASE_URL`) in namespaced classes.
