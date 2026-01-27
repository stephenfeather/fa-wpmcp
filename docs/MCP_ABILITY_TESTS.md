# FA-WPMCP Ability Test Checklist

Testing all fa-wpmcp abilities via REST (curl) and MCP (Claude Code `mcp__wordpress__*` tools).

**Test Date**: 2026-01-27
**Server**: localhost (Docker)

**Legend**:
- **REST**: Tested via curl to `http://localhost/wp-json/mcp/mcp-adapter-default-server`
- **MCP**: Tested via Claude Code's `mcp__wordpress__fa-wpmcp-*` tools

---

## Posts

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 1 | `fa-wpmcp-create-post` | ✅ | | Requires `title`, `content`, `status` |
| 2 | `fa-wpmcp-list-posts` | ✅ | | |
| 3 | `fa-wpmcp-get-post` | ✅ | | Requires `post_id` |
| 4 | `fa-wpmcp-update-post` | ✅ | | Requires `post_id` |
| 5 | `fa-wpmcp-delete-post` | ✅ | | Requires `post_id`; moves to trash |

## Comments

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 6 | `fa-wpmcp-create-comment` | ✅ | | Requires `post_id`, `author`, `email`, `content` |
| 7 | `fa-wpmcp-list-comments` | ✅ | | |
| 8 | `fa-wpmcp-get-comment` | ✅ | | Requires `comment_id` |
| 9 | `fa-wpmcp-update-comment` | ✅ | | Requires `comment_id`, `status` (approve/hold/spam/trash) |
| 10 | `fa-wpmcp-delete-comment` | ✅ | | Requires `comment_id`; moves to trash |

## Media

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 11 | `fa-wpmcp-upload-media` | ✅ | | Requires `url`, `title` |
| 12 | `fa-wpmcp-list-media` | ✅ | | |
| 13 | `fa-wpmcp-get-media` | ✅ | | Requires `media_id` |
| 14 | `fa-wpmcp-update-media` | ✅ | | Requires `media_id` |
| 15 | `fa-wpmcp-delete-media` | ✅ | | Requires `media_id`; moves to trash |

## Terms (Taxonomies)

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 16 | `fa-wpmcp-create-term` | ✅ | | Requires `taxonomy`, `name` |
| 17 | `fa-wpmcp-list-terms` | ✅ | | Requires `taxonomy` |
| 18 | `fa-wpmcp-get-term` | ✅ | | Requires `term_id`, `taxonomy` |
| 19 | `fa-wpmcp-update-term` | ✅ | | Requires `term_id`, `taxonomy` |
| 20 | `fa-wpmcp-delete-term` | ✅ | | Requires `term_id`, `taxonomy` |
| 21 | `fa-wpmcp-list-taxonomies` | ✅ | | Fixed: `rest_base` now returns empty string instead of false |
| 22 | `fa-wpmcp-get-taxonomy` | ✅ | | Requires `taxonomy` |

## Post Types

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 23 | `fa-wpmcp-list-post-types` | ✅ | | Lists all registered post types |
| 24 | `fa-wpmcp-get-post-type` | ✅ | | Requires `post_type` |

## Users

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 25 | `fa-wpmcp-create-user` | ✅ | | Requires `username`, `email`, `password` |
| 26 | `fa-wpmcp-list-users` | ✅ | | Fixed: count_users() returns array not object |
| 27 | `fa-wpmcp-get-user` | ✅ | | Requires `user_id` |
| 28 | `fa-wpmcp-update-user` | ✅ | | Requires `user_id` |
| 29 | `fa-wpmcp-delete-user` | ✅ | | Fixed: `reassigned` returns 0 instead of null |

## Options (Settings)

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 30 | `fa-wpmcp-update-option` | ✅ | | Requires `option_name`, `option_value` |
| 31 | `fa-wpmcp-list-options` | ✅ | | |
| 32 | `fa-wpmcp-get-option` | ✅ | | Requires `option_name` |
| 33 | `fa-wpmcp-delete-option` | ✅ | | Requires `option_name` |

## Plugins

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 34 | `fa-wpmcp-list-plugins` | ✅ | | |
| 35 | `fa-wpmcp-get-plugin` | ✅ | | Requires `plugin` (full path e.g. `fa-wpmcp/fa-wpmcp.php`) |
| 36 | `fa-wpmcp-install-plugin` | ✅ | | Requires `slug` |
| 37 | `fa-wpmcp-activate-plugin` | ✅ | | Requires `plugin` |
| 38 | `fa-wpmcp-deactivate-plugin` | ✅ | | Requires `plugin` |
| 39 | `fa-wpmcp-update-plugin` | ✅ | | Requires `plugin` |
| 40 | `fa-wpmcp-delete-plugin` | ✅ | | Requires `plugin` |

## Themes

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 41 | `fa-wpmcp-list-themes` | ✅ | | |
| 42 | `fa-wpmcp-get-theme` | ✅ | | Requires `stylesheet` |
| 43 | `fa-wpmcp-status-theme` | ✅ | | Requires `stylesheet` |
| 44 | `fa-wpmcp-install-theme` | ✅ | | Requires `slug` |
| 45 | `fa-wpmcp-activate-theme` | ✅* | | Requires `stylesheet`; *see warning below |
| 46 | `fa-wpmcp-update-theme` | ✅ | | Requires `stylesheet` |
| 47 | `fa-wpmcp-delete-theme` | ✅ | | Requires `stylesheet` |

## Privacy (GDPR)

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 48 | `fa-wpmcp-create-export-request` | ✅ | | Requires `email` |
| 49 | `fa-wpmcp-create-erasure-request` | ✅ | | Requires `email` |
| 50 | `fa-wpmcp-list-privacy-requests` | ✅ | | |
| 51 | `fa-wpmcp-get-privacy-request` | ✅ | | Requires `request_id` |

## Cache

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 52 | `fa-wpmcp-get-cache-type` | ✅ | | Returns cache type (e.g., redis), persistent flag |
| 53 | `fa-wpmcp-get-cache-status` | ✅ | | Returns supported features and groups |
| 54 | `fa-wpmcp-flush-cache` | ✅ | | Flushes entire object cache |

## Maintenance

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 55 | `fa-wpmcp-get-maintenance-mode-status` | ✅ | | Returns active status and timestamp |
| 56 | `fa-wpmcp-activate-maintenance-mode` | ✅ | | Optional `expire_seconds` (default 600, max 3600) |
| 57 | `fa-wpmcp-deactivate-maintenance-mode` | ✅ | | Removes .maintenance file |

## Transients

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 58 | `fa-wpmcp-set-transient` | ✅ | | Requires `key`, `value`, `expiration` |
| 59 | `fa-wpmcp-get-transient` | ✅ | | Requires `key` |
| 60 | `fa-wpmcp-list-transients` | ✅ | | Returns 0 when Redis is active (transients in Redis) |
| 61 | `fa-wpmcp-delete-transient` | ✅ | | Requires `key` |

## Cron

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 62 | `fa-wpmcp-list-cron-events` | ✅ | | Fixed: `schedule` returns empty string for single events |
| 63 | `fa-wpmcp-get-cron-event` | ✅ | | Requires `hook` |
| 64 | `fa-wpmcp-schedule-cron-event` | ✅ | | Requires `hook`, `timestamp`, `recurrence` |
| 65 | `fa-wpmcp-unschedule-cron-event` | ✅ | | Requires `hook` |
| 66 | `fa-wpmcp-run-cron-event` | ✅ | | Requires `hook` |
| 67 | `fa-wpmcp-list-cron-schedules` | ✅ | | Returns available recurrence schedules |

## Roles

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 68 | `fa-wpmcp-list-roles` | ✅ | | Returns all roles with capabilities |
| 69 | `fa-wpmcp-get-role` | ✅ | | Requires `role` |
| 70 | `fa-wpmcp-create-role` | ✅ | | Requires `role`, `display_name`, `capabilities` |
| 71 | `fa-wpmcp-update-role` | ✅ | | Requires `role`, optional `add_capabilities`, `remove_capabilities` |
| 72 | `fa-wpmcp-delete-role` | ✅ | | Requires `role` |
| 87 | `fa-wpmcp-list-caps` | ✅ | | Requires `role`; returns capability names as array |
| 88 | `fa-wpmcp-add-cap` | ✅ | | Requires `role`, `capabilities` array |
| 89 | `fa-wpmcp-remove-cap` | ✅ | | Requires `role`, `capabilities` array |

## Menus

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 73 | `fa-wpmcp-list-menus` | ✅ | | Returns menus with locations and item counts |
| 74 | `fa-wpmcp-create-menu` | ✅ | | Requires `name`, optional `location` |
| 75 | `fa-wpmcp-get-menu` | ✅ | | Requires `menu` (ID or slug) |
| 76 | `fa-wpmcp-delete-menu` | ✅ | | Requires `menu` (ID or slug) |

## Widgets

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 77 | `fa-wpmcp-list-sidebars` | ✅ | | Returns all registered sidebars |
| 78 | `fa-wpmcp-get-sidebar` | ✅ | | Requires `sidebar_id` |
| 79 | `fa-wpmcp-list-widget-types` | ✅ | | Returns available widget types (33 on test site) |
| 80 | `fa-wpmcp-list-widgets` | ✅ | | Optional `sidebar_id` filter |
| 81 | `fa-wpmcp-get-widget` | ✅ | | Requires `widget_id` (format: {id_base}-{instance}) |
| 82 | `fa-wpmcp-add-widget` | ✅ | | Requires `id_base`, `sidebar_id`; optional `settings` |
| 83 | `fa-wpmcp-update-widget` | ✅ | | Requires `widget_id`; optional `settings` |
| 84 | `fa-wpmcp-delete-widget` | ✅ | | Requires `widget_id`; destructive |
| 85 | `fa-wpmcp-move-widget` | ✅ | | Requires `widget_id`, `sidebar_id`; optional `position` |
| 86 | `fa-wpmcp-reset-widgets` | ✅ | | Requires `sidebar_id`; destructive - removes all widgets from sidebar |

## Dotenv

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 90 | `fa-wpmcp-list-env-vars` | ✅ | | Lists environment variables from .env file |
| 91 | `fa-wpmcp-get-env-var` | ✅ | | Requires `key`; returns variable value |
| 92 | `fa-wpmcp-set-env-var` | ✅ | | Requires `key`, `value`; creates or updates variable |
| 93 | `fa-wpmcp-delete-env-var` | ✅ | | Requires `key`; removes variable from .env |

## Rewrite

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 94 | `fa-wpmcp-list-rewrite-rules` | ✅ | | Lists all rewrite rules; 0 when plain permalinks, 187+ with pretty |
| 95 | `fa-wpmcp-flush-rewrite-rules` | ✅ | | Flushes/regenerates rewrite rules |
| 96 | `fa-wpmcp-get-permalink-structure` | ✅ | | Gets current permalink structure |
| 97 | `fa-wpmcp-update-permalink-structure` | ✅ | | Updates permalink structure + auto-flush |

## Core

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 98 | `fa-wpmcp-get-core-version` | ✅ | | Returns WP 6.9, PHP, MySQL versions |
| 99 | `fa-wpmcp-check-core-updates` | ✅ | | Checks for available core updates |
| 100 | `fa-wpmcp-verify-checksums` | ✅ | | Verifies 3349 files; reports missing bundled themes |
| 101 | `fa-wpmcp-is-installed` | ✅ | | Returns installed status, db_ready, has_admin |
| 102 | `fa-wpmcp-update-database` | ✅ | | Reports db version; supports dry_run |

## Config

| # | Ability | REST | MCP | Notes |
|---|---------|------|-----|-------|
| 103 | `fa-wpmcp-list-config-constants` | ✅ | | Lists WP config constants; filter by category; excludes sensitive |
| 104 | `fa-wpmcp-get-config-constant` | ✅ | | Requires `name`; blocks passwords/keys/salts |

---

## Summary

**Total**: 104 abilities registered

| Test Type | Passed | Failed | Not Tested |
|-----------|--------|--------|------------|
| REST (curl) | 104 | 0 | 0 |
| MCP (Claude Code) | 0 | 0 | 104 |

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
