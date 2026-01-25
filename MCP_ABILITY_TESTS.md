# FA-WPMCP Ability Test Checklist

Testing all fa-wpmcp abilities via MCP direct tool calls, verified with WP-CLI.

**Test Date**: 2026-01-25
**Server**: localhost (Docker)

---

## Posts

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 1 | `fa-wpmcp-create-post` | [FAIL] | Global write operations are disabled |
| 2 | `fa-wpmcp-list-posts` | [PASS] | |
| 3 | `fa-wpmcp-get-post` | [PASS] | Requires `post_id` not `id` |
| 4 | `fa-wpmcp-update-post` | [FAIL] | Invalid input - param name mismatch |

## Comments

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 5 | `fa-wpmcp-create-comment` | [FAIL] | Invalid input - param name mismatch |
| 6 | `fa-wpmcp-list-comments` | [PASS] | |
| 7 | `fa-wpmcp-get-comment` | [PASS] | Requires `comment_id` |
| 8 | `fa-wpmcp-update-comment` | [FAIL] | Invalid input - param name mismatch |

## Media

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 9 | `fa-wpmcp-upload-media` | [FAIL] | Invalid input - param name mismatch |
| 10 | `fa-wpmcp-list-media` | [PASS] | |
| 11 | `fa-wpmcp-get-media` | [PASS] | Requires `media_id` |
| 12 | `fa-wpmcp-update-media` | [FAIL] | Invalid input - param name mismatch |

## Terms (Taxonomies)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 13 | `fa-wpmcp-create-term` | [FAIL] | Global write operations are disabled |
| 14 | `fa-wpmcp-list-terms` | [PASS] | |
| 15 | `fa-wpmcp-get-term` | [PASS] | Requires `term_id` + `taxonomy` |
| 16 | `fa-wpmcp-update-term` | [FAIL] | Invalid input - param name mismatch |

## Users

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 17 | `fa-wpmcp-create-user` | [PASS] | Works despite global write disabled |
| 18 | `fa-wpmcp-list-users` | [PASS] | |
| 19 | `fa-wpmcp-get-user` | [PASS] | Requires `user_id` |
| 20 | `fa-wpmcp-update-user` | [FAIL] | Invalid input - param name mismatch |

## Options (Settings)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 21 | `fa-wpmcp-update-option` | [FAIL] | Invalid input - param name mismatch |
| 22 | `fa-wpmcp-list-options` | [PASS] | |
| 23 | `fa-wpmcp-get-option` | [PASS] | |
| 24 | `fa-wpmcp-delete-option` | [FAIL] | Global write operations are disabled |

## Plugins

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 25 | `fa-wpmcp-list-plugins` | [PASS] | |
| 26 | `fa-wpmcp-get-plugin` | [PASS] | Requires `plugin` (full path) |
| 27 | `fa-wpmcp-install-plugin` | [FAIL] | Global write operations are disabled |
| 28 | `fa-wpmcp-activate-plugin` | [FAIL] | Global write operations are disabled |
| 29 | `fa-wpmcp-deactivate-plugin` | [FAIL] | Global write operations are disabled |
| 30 | `fa-wpmcp-update-plugin` | [FAIL] | Global write operations are disabled |
| 31 | `fa-wpmcp-delete-plugin` | [FAIL] | Global write operations are disabled |

## Themes

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 32 | `fa-wpmcp-list-themes` | [PASS] | |
| 33 | `fa-wpmcp-get-theme` | [PASS] | Requires `stylesheet` |
| 34 | `fa-wpmcp-status-theme` | [FAIL] | Invalid output - schema validation error |
| 35 | `fa-wpmcp-install-theme` | [FAIL] | Global write operations are disabled |
| 36 | `fa-wpmcp-activate-theme` | [FAIL] | Global write operations are disabled |
| 37 | `fa-wpmcp-update-theme` | [FAIL] | Global write operations are disabled |
| 38 | `fa-wpmcp-delete-theme` | [FAIL] | Global write operations are disabled |

## Privacy (GDPR)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 39 | `fa-wpmcp-create-export-request` | [FAIL] | Global write operations are disabled |
| 40 | `fa-wpmcp-create-erasure-request` | [FAIL] | Global write operations are disabled |
| 41 | `fa-wpmcp-list-privacy-requests` | [PASS] | |
| 42 | `fa-wpmcp-get-privacy-request` | [PASS] | Requires `request_id` |

---

## Summary

**Passed**: 19/42 (45%)
**Failed**: 23/42 (55%)

### Failure Categories

| Category | Count | Abilities |
|----------|-------|-----------|
| Global write disabled | 16 | #1, #13, #24, #27-31, #35-40 |
| Invalid input (param mismatch) | 6 | #4-5, #8-9, #12, #16, #20-21 |
| Invalid output (schema error) | 1 | #34 |

### Issues Identified

1. **Global write operations disabled**: MCP adapter config issue - 16 write abilities blocked
2. **Parameter name mismatches**: Some create/update abilities need correct param names
3. **Schema validation error**: `status-theme` output doesn't match schema

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

## Test Data Created

| Type | ID | Name/Title | Notes |
|------|-----|------------|-------|
| User | ? | testuser | Created via #17 test |
