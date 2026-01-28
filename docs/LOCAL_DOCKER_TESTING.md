# Local Docker Testing

This guide documents the Docker-based MCP integration testing environment for fa-wpmcp.

## Quick Start

**IMPORTANT:** Always run Docker commands from the `fa-wpmcp` project root directory. The docker-compose file uses relative paths (`../`) for volume mounts. Running from the wrong directory will cause the plugin mount to point to the wrong location.

```bash
# Ensure you're in the project root
cd /path/to/fa-wpmcp

# Start environment
composer docker:up

# View generated credentials
cat docker/wordpress-data/.mcp-test-credentials

# Run integration tests
composer test:integration

# Full workflow (start + test)
composer docker:test

# Cleanup
composer docker:down   # Stop (keep data)
composer docker:clean  # Full cleanup
```

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    Docker Test Environment                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐   ┌──────────────┐   ┌──────────────┐        │
│  │ MariaDB 11.4 │   │  Redis 7     │   │   Nginx      │        │
│  │ Port: 3306   │   │ Port: 6379   │   │ Port: 8080   │        │
│  │ (internal)   │   │ (internal)   │   │ (external)   │        │
│  └──────────────┘   └──────────────┘   └──────────────┘        │
│         │                   │                   │               │
│         └───────────────────┴───────────────────┘               │
│                             │                                   │
│                   ┌─────────▼─────────┐                         │
│                   │  WordPress 6.9+   │                         │
│                   │  PHP 8.3-FPM      │                         │
│                   │  + fa-wpmcp       │                         │
│                   │  + mcp-adapter    │                         │
│                   │  + WP-CLI         │                         │
│                   └───────────────────┘                         │
│                             │                                   │
│                   ┌─────────▼─────────┐                         │
│                   │  Setup Container  │                         │
│                   │  (one-time run)   │                         │
│                   └───────────────────┘                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  Host Machine                │
              │  - PHPUnit integration tests │
              │  - Manual curl testing       │
              │  - Port 8080 access          │
              └──────────────────────────────┘
```

## Docker vs Manual Testing

| Aspect | Manual (CLAUDE.md) | Docker |
|--------|-------------------|--------|
| **Port** | localhost:80 | localhost:8080 |
| **Credentials** | Static (`featherarms_admin`) | Dynamic (`test_admin` + generated app password) |
| **WordPress** | Pre-existing dev site | Fresh install each time |
| **MCP Adapter** | Manual activation | Auto-activated by setup script |
| **Permissions** | Manual config | Auto-enabled via mu-plugin |
| **Database** | Shared with dev | Isolated test database |
| **Repeatability** | Can vary | Identical every run |

## Setup Process

When you run `composer docker:up`, the setup container automatically:

1. Downloads WordPress core
2. Creates wp-config.php with Redis config
3. Installs WordPress (admin user: `test_admin`)
4. Enables pretty permalinks (required for REST API)
5. Creates mu-plugin to enable app passwords over HTTP
6. Activates fa-wpmcp plugin
7. Downloads and installs mcp-adapter from [official release](https://github.com/WordPress/mcp-adapter/releases)
8. Activates mcp-adapter plugin
9. Creates application password for MCP testing
10. Saves credentials to `docker/wordpress-data/.mcp-test-credentials`

## Testing Methods

### 1. PHPUnit Integration Tests

**Location:** `tests/integration/`

```bash
# Run all integration tests
composer test:integration

# Run specific test file
./vendor/bin/phpunit \
  --testsuite=integration \
  --bootstrap=tests/integration/bootstrap.php \
  tests/integration/Abilities/Posts/PostsAbilityTest.php
```

**Base class:** `McpIntegrationTestCase` provides:
- Auto-initializes MCP session in setUp()
- Tracks created resources for cleanup
- Helper methods: `createTestPost()`, `callTool()`, `assertToolSucceeds()`
- Auto-cleans up in tearDown()

### 2. Manual curl Testing

**Get credentials:**
```bash
cat docker/wordpress-data/.mcp-test-credentials
```

**Authenticate and get session:**
```bash
# Replace credentials from .mcp-test-credentials
SESSION=$(curl -s -X POST \
  -H "Authorization: Basic $(echo -n 'test_admin:YOUR_APP_PASSWORD' | base64)" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}' \
  "http://localhost:8080/wp-json/mcp/mcp-adapter-default-server" -i 2>/dev/null | \
  grep -i "Mcp-Session-Id" | cut -d' ' -f2 | tr -d '\r')

echo "Session: $SESSION"
```

**Call MCP tool:**
```bash
curl -s -X POST \
  -H "Authorization: Basic $(echo -n 'test_admin:YOUR_APP_PASSWORD' | base64)" \
  -H "Content-Type: application/json" \
  -H "Mcp-Session-Id: $SESSION" \
  -d '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"fa-wpmcp-list-posts","arguments":{}}}' \
  "http://localhost:8080/wp-json/mcp/mcp-adapter-default-server" | jq .
```

**List available tools:**
```bash
curl -s -X POST \
  -H "Authorization: Basic $(echo -n 'test_admin:YOUR_APP_PASSWORD' | base64)" \
  -H "Content-Type: application/json" \
  -H "Mcp-Session-Id: $SESSION" \
  -d '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}' \
  "http://localhost:8080/wp-json/mcp/mcp-adapter-default-server" | jq '.result.tools[].name'
```

## Docker Commands

```bash
# Start environment (build if needed)
composer docker:up

# Stop environment (keep data)
composer docker:down

# Clean up (remove volumes/data)
composer docker:clean

# View logs
composer docker:logs

# Full test workflow
composer docker:test

# Check container status
docker-compose -f docker/docker-compose.test.yml ps

# Exec into WordPress container
docker exec -it fa-wpmcp-wordpress-test bash

# Run WP-CLI commands
docker exec fa-wpmcp-wordpress-test wp plugin list --allow-root

# Reset entire environment
composer docker:clean && composer docker:up
```

## Troubleshooting

### fa-wpmcp plugin not found / empty plugin directory

If `wp plugin list` doesn't show fa-wpmcp or the plugin directory is empty, the containers were started from the wrong directory.

```bash
# Check the mount source
docker inspect fa-wpmcp-wordpress-test --format='{{range .Mounts}}{{if eq .Destination "/var/www/html/wp-content/plugins/fa-wpmcp"}}{{.Source}}{{end}}{{end}}'

# Should show: /path/to/fa-wpmcp
# If it shows a different path, recreate containers from correct directory:
cd /path/to/fa-wpmcp
docker-compose -f docker/docker-compose.test.yml up -d --force-recreate
```

### "Setup already completed" message

The setup script creates a marker file to prevent re-running. To force re-setup:

```bash
docker exec fa-wpmcp-wordpress-test rm /var/www/html/.wp-test-setup-complete
docker-compose -f docker/docker-compose.test.yml restart setup-test
```

### Credentials file missing

```bash
# Check setup container logs
docker-compose -f docker/docker-compose.test.yml logs setup-test

# Re-run setup
docker-compose -f docker/docker-compose.test.yml restart setup-test
```

### Port 8080 already in use

```bash
# Find what's using port 8080
lsof -i :8080

# Change port in docker/docker-compose.test.yml under webserver-test > ports
```

### Application password not working

```bash
# Create new app password manually
docker exec fa-wpmcp-wordpress-test \
  wp user application-password create test_admin "manual-test" \
  --path=/var/www/html --allow-root --porcelain
```

### MCP adapter not activated

```bash
# Check plugin status
docker exec fa-wpmcp-wordpress-test \
  wp plugin list --path=/var/www/html --allow-root

# Manually install from official release
docker exec fa-wpmcp-wordpress-test \
  wp plugin install https://github.com/WordPress/mcp-adapter/releases/download/v0.4.1/mcp-adapter.zip \
  --path=/var/www/html --allow-root

# Manually activate
docker exec fa-wpmcp-wordpress-test \
  wp plugin activate mcp-adapter --path=/var/www/html --allow-root
```

## File Structure

```
fa-wpmcp/
├── docker/
│   ├── docker-compose.test.yml    # Main orchestration file
│   ├── wordpress/
│   │   ├── Dockerfile            # Custom PHP 8.3 + WP-CLI image
│   │   └── zz-healthcheck.conf   # PHP-FPM ping endpoint config
│   ├── nginx-conf/
│   │   └── default.conf          # Nginx configuration
│   ├── scripts/
│   │   └── setup-wordpress.sh    # One-time WordPress setup
│   └── wordpress-data/
│       └── .mcp-test-credentials # Generated credentials (gitignored)
├── tests/integration/
│   ├── bootstrap.php             # PHPUnit bootstrap
│   ├── Support/
│   │   ├── McpClient.php         # JSON-RPC 2.0 MCP client
│   │   └── McpIntegrationTestCase.php  # Base test case
│   └── Abilities/
│       ├── Posts/PostsAbilityTest.php
│       ├── Comments/CommentsAbilityTest.php
│       └── ...
└── composer.json                 # Docker commands in scripts section
```

## Volume Mounts

| Host Path | Container Path | Mode | Purpose |
|-----------|----------------|------|---------|
| Project root | `/var/www/html/wp-content/plugins/fa-wpmcp` | ro | Plugin source |
| `docker/nginx-conf` | `/etc/nginx/conf.d` | ro | Nginx config |
| `docker/scripts` | `/scripts` | ro | Setup scripts |

## Related Documentation

- [MCP Ability Test Results](MCP_ABILITY_TESTS.md)
- [CLAUDE.md](../CLAUDE.md) - Manual testing instructions for dev environment
