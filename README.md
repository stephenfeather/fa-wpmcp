# FA WPMCP - WordPress MCP Plugin
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=stephenfeather_fa-wpmcp&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=stephenfeather_fa-wpmcp)

WordPress plugin that exposes WordPress functionality to AI agents via the Abilities API and MCP (Model Context Protocol) Adapter. Connect AI assistants like Claude, GPT, or Gemini to your WordPress site for intelligent content management.

## Requirements

- **PHP:** 8.1 or higher
- **WordPress:** 6.9 or higher (Abilities API)
- **Composer:** For dependency management

## Quick Installation

```bash
cd wp-content/plugins
git clone https://github.com/featherart/fa-wpmcp.git
cd fa-wpmcp
composer install --no-dev
wp plugin activate fa-wpmcp
```

For detailed setup including AI assistant configuration, see the **[Quick Start Guide](docs/QUICK_START.md)**.

## Features

### Core Framework

- **Ability Framework:** Extensible pipeline-based system for registering WordPress operations
  - Category-based organization (Posts, Pages, Users, etc.)
  - Operation types: READ, WRITE, DELETE
  - JSON Schema validation for inputs/outputs
  - Pipeline orchestration with middleware

### WordPress Abilities

The plugin provides comprehensive WordPress content management through the following ability categories:

- **Posts & Pages:** Full CRUD operations for posts, pages, and custom post types
  - Universal `post_type` parameter supports WordPress pages, WooCommerce products, and any custom post type
  - List, Get, Create, Update operations with filtering, pagination, and search

- **Comments:** Complete comment management system
  - List, Get, Create, Update, Delete operations
  - Support for comment moderation, threading, and metadata

- **Media Library:** Upload and manage media files
  - List, Get, Update, Upload operations
  - Base64 and URL upload support with automatic thumbnail generation
  - MIME type filtering and file size limits (10MB default)

- **Taxonomies:** Manage categories, tags, and custom taxonomies
  - List Terms, Get Term, Create Term, Update Term
  - Works with any taxonomy including hierarchical parent/child relationships
  - Full metadata support

- **User Management:** Complete user administration
  - List, Get, Create, Update operations
  - Role filtering, search, and flexible user lookup (ID, username, email)
  - Avatar URLs and profile metadata

- **Settings:** WordPress options management
  - Get, Update, Delete, List operations
  - Search filtering and pagination support
  - Safe option existence detection and serialization handling

- **Plugin Management:** WordPress plugin administration
  - List, Get, Install, Activate, Deactivate, Delete, Update operations
  - Matches wp-cli plugin command naming conventions
  - Full plugin lifecycle management

- **Theme Management:** WordPress theme administration
  - List, Get, Activate, Status, Install, Delete, Update operations
  - Matches wp-cli theme command naming conventions
  - Full theme lifecycle management

- **Privacy & GDPR:** Personal data management for compliance
  - Create Export Request, Create Erasure Request operations
  - List Privacy Requests, Get Privacy Request operations
  - Full integration with WordPress privacy tools
  - Email confirmation workflow for data requests

- **Cron:** Scheduled tasks management
  - List, Get, Schedule, Unschedule, Run cron events

- **Roles & Capabilities:** User role and capability management
  - List, Get, Create, Update, Delete roles
  - List, Add, Remove capabilities per role

- **Widgets:** Sidebar and widget management
  - List Sidebars, Get Sidebar, List Widget Types
  - List, Get, Add, Update, Delete, Move, Reset widgets

- **Dotenv:** Environment variable management (.env files)
  - List, Get, Set, Delete environment variables
  - Secure access policy with sensitive key filtering

### Permission System

- **Permission System:** Multi-level access control
  - Global permissions (read/write)
  - Category-level permissions (posts, pages, users)
  - Ability-level permissions (per-operation)
  - WordPress capability integration

### Security & Monitoring

- **Rate Limiting:** Dual-track protection
  - Per-user limits (WordPress user ID)
  - Per-IP limits (anonymous requests)
  - Configurable windows (minute, hour, day)
  - Transient storage with automatic expiration

- **Activity Logging:** Comprehensive audit trail
  - Correlation IDs for request tracking
  - User and IP address logging
  - Input/output capture (with PII redaction)
  - Execution time tracking
  - Database-backed persistence

- **Webhook System:** Event-driven notifications
  - Before/after/failed execution hooks
  - HMAC-SHA256 signature signing
  - Retry logic with exponential backoff
  - Queue-based processing (Action Scheduler or WP-Cron)

### Standards Compliance

- **Privacy & Security:** Privacy-first design
  - PII redaction in logs (via PrivacyRedactor)
  - HMAC-SHA256 webhook signing
  - Rate limiting protection
  - Activity audit trail
  - GDPR compliance (data export/erasure via WordPress privacy API)

- **Code Quality:** Professional-grade implementation
  - PSR-12 coding standards
  - PHPStan level 8 static analysis
  - 700+ unit tests
  - Type-safe with PHP 8.1 features

## Documentation

| Guide | Description |
|-------|-------------|
| **[Quick Start](docs/QUICK_START.md)** | Get connected in 5 minutes |
| **[MCP Client Configuration](docs/MCP_CLIENT_CONFIGURATION.md)** | Claude, GPT, Gemini setup examples |
| **[Configuration](docs/CONFIGURATION.md)** | Options, filters, and constants |
| **[Architecture](docs/ARCHITECTURE.md)** | System design and components |
| **[Development](docs/DEVELOPMENT.md)** | Adding abilities, testing, code standards |
| **[Security](docs/SECURITY.md)** | Security guidelines and reporting |
| **[REST API](docs/REST_API_ENDPOINTS.md)** | Complete API reference |
| **[MCP Documentation](docs/MCP_DOCUMENTATION.md)** | Full MCP integration reference |

## Troubleshooting

### Plugin won't activate

**Error:** "FA WPMCP requires WordPress Abilities API"
**Solution:** Ensure WordPress 6.9+ is installed.

### Composer autoloader not found

**Error:** Admin notice about missing autoloader
**Solution:** Run `composer install` in the plugin directory.

### Rate limiting too aggressive

**Symptom:** Requests denied with 429 status
**Solution:** Adjust via filter:

```php
add_filter('fa_wpmcp_rate_limit_config', function($config) {
    $config['default']['per_minute'] = 120;
    return $config;
});
```

For additional troubleshooting, see the [Configuration Guide](docs/CONFIGURATION.md).

## Development

**Quick commands:**

```bash
composer test          # Run all tests
composer phpcs         # Check code standards
composer phpcbf        # Auto-fix code standards
composer phpstan       # Static analysis (level 8)
composer test:coverage # Generate coverage report
```

For comprehensive development documentation including adding new abilities, testing patterns, and architecture details, see the **[Development Guide](docs/DEVELOPMENT.md)**.

## Roadmap

### Completed

- Core framework, permissions, rate limiting, logging, webhooks
- Posts, Comments, Media, Taxonomies, Users, Settings abilities (full CRUD)
- Plugin, Theme, Privacy, Cron, Roles, Menu abilities
- Widget abilities (sidebars, widget types, add/update/delete/move/reset widgets)
- Capability management abilities (list-caps, add-cap, remove-cap)
- Dotenv abilities (list, get, set, delete environment variables)
- MCP Server integration with OAuth and Application Password support
- Delete operations for posts, media, taxonomies, comments, users
- Activity logging with PII redaction and correlation IDs

### Planned

- Bulk operations (batch create/update/delete)
- Multisite support
- GraphQL endpoint
- Prometheus observability metrics

## Security

For security guidelines and vulnerability reporting, see the **[Security Guide](docs/SECURITY.md)**.

**Do NOT open public issues for security vulnerabilities.**

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Write tests first (TDD)
4. Ensure tests pass: `composer test && composer phpcs && composer phpstan`
5. Open a Pull Request

For coding guidelines and detailed contribution workflow, see the **[Development Guide](docs/DEVELOPMENT.md)**.

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

**Stephen Feather**
Website: https://stephenfeather.com
Email: stephen@feather.us

## Acknowledgments

- WordPress Abilities API team
- Model Context Protocol (MCP) specification
- Action Scheduler by Automattic
- Brain\Monkey testing library

---

**Version:** 1.0.0 | **93 Abilities Implemented** | Posts, Comments, Media, Taxonomy, User, Settings, Plugin, Theme, Privacy, Cache, Maintenance, Transients, PostTypes, Cron, Role, Menu, Widget, Capabilities, and Dotenv management complete
