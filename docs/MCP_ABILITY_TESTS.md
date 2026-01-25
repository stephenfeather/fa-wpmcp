# FA-WPMCP Ability Test Checklist

Testing all fa-wpmcp abilities via MCP direct tool calls, verified with WP-CLI.

**Test Date**: 2026-01-25 (Final)
**Server**: localhost (Docker)

---

## Posts

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 1 | `fa-wpmcp-create-post` | [PASS] | Requires `title`, `content`, `status` |
| 2 | `fa-wpmcp-list-posts` | [PASS] | |
| 3 | `fa-wpmcp-get-post` | [PASS] | Requires `post_id` |
| 4 | `fa-wpmcp-update-post` | [PASS] | Requires `post_id` |

## Comments

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 5 | `fa-wpmcp-create-comment` | [PASS] | Requires `post_id`, `author`, `email`, `content` |
| 6 | `fa-wpmcp-list-comments` | [PASS] | |
| 7 | `fa-wpmcp-get-comment` | [PASS] | Requires `comment_id` |
| 8 | `fa-wpmcp-update-comment` | [PASS] | Requires `comment_id`, `status` (approve/hold/spam/trash) |

## Media

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 9 | `fa-wpmcp-upload-media` | [PASS] | Requires `url`, `title` |
| 10 | `fa-wpmcp-list-media` | [PASS] | |
| 11 | `fa-wpmcp-get-media` | [PASS] | Requires `media_id` |
| 12 | `fa-wpmcp-update-media` | [PASS] | Requires `media_id` |

## Terms (Taxonomies)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 13 | `fa-wpmcp-create-term` | [PASS] | Requires `taxonomy`, `name` |
| 14 | `fa-wpmcp-list-terms` | [PASS] | Requires `taxonomy` |
| 15 | `fa-wpmcp-get-term` | [PASS] | Requires `term_id`, `taxonomy` |
| 16 | `fa-wpmcp-update-term` | [PASS] | Requires `term_id`, `taxonomy` |

## Users

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 17 | `fa-wpmcp-create-user` | [PASS] | Requires `username`, `email`, `password` |
| 18 | `fa-wpmcp-list-users` | [PASS] | Fixed: count_users() returns array not object |
| 19 | `fa-wpmcp-get-user` | [PASS] | Requires `user_id` |
| 20 | `fa-wpmcp-update-user` | [PASS] | Requires `user_id` |

## Options (Settings)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 21 | `fa-wpmcp-update-option` | [PASS] | Requires `option_name`, `option_value` |
| 22 | `fa-wpmcp-list-options` | [PASS] | |
| 23 | `fa-wpmcp-get-option` | [PASS] | Requires `option_name` |
| 24 | `fa-wpmcp-delete-option` | [PASS] | Requires `option_name` |

## Plugins

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 25 | `fa-wpmcp-list-plugins` | [PASS] | |
| 26 | `fa-wpmcp-get-plugin` | [PASS] | Requires `plugin` (full path e.g. `fa-wpmcp/fa-wpmcp.php`) |
| 27 | `fa-wpmcp-install-plugin` | [PASS] | Requires `slug` |
| 28 | `fa-wpmcp-activate-plugin` | [PASS] | Requires `plugin` |
| 29 | `fa-wpmcp-deactivate-plugin` | [PASS] | Requires `plugin` |
| 30 | `fa-wpmcp-update-plugin` | [PASS] | Requires `plugin` |
| 31 | `fa-wpmcp-delete-plugin` | [PASS] | Requires `plugin` |

## Themes

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 32 | `fa-wpmcp-list-themes` | [PASS] | |
| 33 | `fa-wpmcp-get-theme` | [PASS] | Requires `stylesheet` |
| 34 | `fa-wpmcp-status-theme` | [PASS] | Requires `stylesheet` |
| 35 | `fa-wpmcp-install-theme` | [PASS] | Requires `slug` |
| 36 | `fa-wpmcp-activate-theme` | [PASS]* | Requires `stylesheet`; *see warning below |
| 37 | `fa-wpmcp-update-theme` | [PASS] | Requires `stylesheet` |
| 38 | `fa-wpmcp-delete-theme` | [PASS] | Requires `stylesheet` |

## Privacy (GDPR)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 39 | `fa-wpmcp-create-export-request` | [PASS] | Requires `email` |
| 40 | `fa-wpmcp-create-erasure-request` | [PASS] | Requires `email` |
| 41 | `fa-wpmcp-list-privacy-requests` | [PASS] | |
| 42 | `fa-wpmcp-get-privacy-request` | [PASS] | Requires `request_id` |

---

## Summary

**Passed**: 42/42 (100%)

### Warnings

1. **activate-theme**: Activating a theme that lacks `add_filter('wp_is_application_passwords_available', '__return_true')` in its functions.php will break MCP authentication for subsequent requests.

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
