# FA-WPMCP Ability Test Checklist

Testing all fa-wpmcp abilities via MCP direct tool calls, verified with WP-CLI.

**Test Date**: 2026-01-25
**Server**: localhost (Docker)

---

## Posts

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 1 | `fa-wpmcp-create-post` | [ ] | |
| 2 | `fa-wpmcp-list-posts` | [ ] | |
| 3 | `fa-wpmcp-get-post` | [ ] | |
| 4 | `fa-wpmcp-update-post` | [ ] | |

## Comments

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 5 | `fa-wpmcp-create-comment` | [ ] | |
| 6 | `fa-wpmcp-list-comments` | [ ] | |
| 7 | `fa-wpmcp-get-comment` | [ ] | |
| 8 | `fa-wpmcp-update-comment` | [ ] | |

## Media

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 9 | `fa-wpmcp-upload-media` | [ ] | |
| 10 | `fa-wpmcp-list-media` | [ ] | |
| 11 | `fa-wpmcp-get-media` | [ ] | |
| 12 | `fa-wpmcp-update-media` | [ ] | |

## Terms (Taxonomies)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 13 | `fa-wpmcp-create-term` | [ ] | |
| 14 | `fa-wpmcp-list-terms` | [ ] | |
| 15 | `fa-wpmcp-get-term` | [ ] | |
| 16 | `fa-wpmcp-update-term` | [ ] | |

## Users

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 17 | `fa-wpmcp-create-user` | [ ] | |
| 18 | `fa-wpmcp-list-users` | [ ] | |
| 19 | `fa-wpmcp-get-user` | [ ] | |
| 20 | `fa-wpmcp-update-user` | [ ] | |

## Options (Settings)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 21 | `fa-wpmcp-update-option` | [ ] | Creates or updates |
| 22 | `fa-wpmcp-list-options` | [ ] | |
| 23 | `fa-wpmcp-get-option` | [ ] | |
| 24 | `fa-wpmcp-delete-option` | [ ] | |

## Plugins

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 25 | `fa-wpmcp-list-plugins` | [ ] | |
| 26 | `fa-wpmcp-get-plugin` | [ ] | |
| 27 | `fa-wpmcp-install-plugin` | [ ] | |
| 28 | `fa-wpmcp-activate-plugin` | [ ] | |
| 29 | `fa-wpmcp-deactivate-plugin` | [ ] | |
| 30 | `fa-wpmcp-update-plugin` | [ ] | |
| 31 | `fa-wpmcp-delete-plugin` | [ ] | |

## Themes

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 32 | `fa-wpmcp-list-themes` | [ ] | |
| 33 | `fa-wpmcp-get-theme` | [ ] | |
| 34 | `fa-wpmcp-status-theme` | [ ] | |
| 35 | `fa-wpmcp-install-theme` | [ ] | |
| 36 | `fa-wpmcp-activate-theme` | [ ] | |
| 37 | `fa-wpmcp-update-theme` | [ ] | |
| 38 | `fa-wpmcp-delete-theme` | [ ] | |

## Privacy (GDPR)

| # | Ability | Status | Notes |
|---|---------|--------|-------|
| 39 | `fa-wpmcp-create-export-request` | [ ] | |
| 40 | `fa-wpmcp-create-erasure-request` | [ ] | |
| 41 | `fa-wpmcp-list-privacy-requests` | [ ] | |
| 42 | `fa-wpmcp-get-privacy-request` | [ ] | |

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
| Post | | | |
| Comment | | | |
| Media | | | |
| Term | | | |
| User | | | |
| Option | | | |
| Plugin | | | |
| Theme | | | |
| Privacy Request | | | |
