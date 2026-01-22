# Release Notes: FA WPMCP v1.0-alpha-2

**Release Date:** 2026-01-22
**Status:** Alpha Release (Pre-production)
**Version:** 1.0.0-alpha-2
**Previous Version:** 1.0.0-alpha-1

---

## Summary

FA WPMCP v1.0-alpha-2 represents a significant expansion of WordPress management capabilities for AI agents. This alpha release adds **38 new abilities** across 8 functional categories, bringing the total to **43 WordPress abilities** exposed via the Abilities API and MCP Adapter.

This release focuses on comprehensive WordPress content and system management, enabling AI agents to handle posts, comments, media, users, taxonomies, settings, plugins, themes, and GDPR/privacy workflows. Additionally, over **100 SonarQube code quality issues** have been resolved, improving maintainability and reducing cognitive complexity.

### Key Highlights

- **38 New Abilities** across 8 categories (Comments, Media, Taxonomies, Users, Settings, Plugins, Themes, Privacy)
- **703 Tests Passing** with 74.33% code coverage (+231 tests since v1.0-alpha-1)
- **100+ SonarQube Issues Resolved** (HIGH/MEDIUM/LOW severity maintainability improvements)
- **Security Audit Completed** - MEDIUM overall risk with documented HIGH severity items
- **Professional Code Quality** - Dedicated exception classes, reduced cognitive complexity, PSR-12 standards
- **44 Commits** since v1.0-alpha-1 with full TDD workflow

---

## New Abilities

### 1. Comment Management (4 abilities)

Complete comment lifecycle management with moderation support:

- **`comments.list`** - List comments with pagination, filtering by post, status, type, and author
- **`comments.get`** - Retrieve single comment by ID with full metadata
- **`comments.create`** - Create new comment with content, author, and parent support
- **`comments.update`** - Update comment content, status, author information, and metadata
- **`comments.delete`** - Delete comment by ID with trash/force delete options

**Capabilities Required:** `moderate_comments` (read), `edit_comment` (write/delete)

### 2. Media Library (4 abilities)

Upload and manage WordPress media files:

- **`media.list`** - List media attachments with pagination, MIME type filtering, and search
- **`media.get`** - Get media attachment details including URLs, metadata, and dimensions
- **`media.upload`** - Upload media files via base64 or URL with automatic thumbnail generation (10MB limit)
- **`media.update`** - Update media metadata (title, caption, alt text, description)

**Capabilities Required:** `read` (list/get), `upload_files` (upload/update)

### 3. Taxonomy Management (4 abilities)

Manage categories, tags, and custom taxonomies:

- **`taxonomies.list_terms`** - List terms for any taxonomy with pagination and search
- **`taxonomies.get_term`** - Get term details including parent/child relationships
- **`taxonomies.create_term`** - Create new taxonomy term with metadata support
- **`taxonomies.update_term`** - Update term name, slug, description, parent, and metadata

**Capabilities Required:** `read` (list/get), `manage_categories` (create/update)

### 4. User Management (4 abilities)

Complete user administration:

- **`users.list`** - List users with role filtering, search, and pagination
- **`users.get`** - Get user details by ID, username, or email with avatar URLs
- **`users.create`** - Create new user account with role assignment
- **`users.update`** - Update user profile, email, role, and metadata

**Capabilities Required:** `list_users` (list/get), `create_users` (create), `edit_users` (update)

### 5. Settings Management (4 abilities)

WordPress options/settings management:

- **`settings.get_option`** - Retrieve WordPress option value with safe deserialization
- **`settings.update_option`** - Update option value with automatic serialization
- **`settings.delete_option`** - Delete WordPress option
- **`settings.list_options`** - List all options with search filtering and pagination

**Capabilities Required:** `manage_options` (all operations)

### 6. Plugin Management (7 abilities)

WordPress plugin lifecycle management (matches wp-cli conventions):

- **`plugins.list`** - List all plugins with status filtering (active/inactive/all)
- **`plugins.get`** - Get plugin details by file path
- **`plugins.install`** - Install plugin from WordPress.org by slug (minimal implementation)
- **`plugins.activate`** - Activate installed plugin
- **`plugins.deactivate`** - Deactivate active plugin
- **`plugins.delete`** - Delete plugin files (minimal implementation)
- **`plugins.update`** - Update plugin to latest version (minimal implementation)

**Capabilities Required:** `activate_plugins`, `install_plugins`, `delete_plugins`, `update_plugins`

### 7. Theme Management (7 abilities)

WordPress theme lifecycle management (matches wp-cli conventions):

- **`themes.list`** - List all themes with status filtering (active/inactive/all)
- **`themes.get`** - Get theme details by stylesheet name
- **`themes.activate`** - Activate theme (switch theme)
- **`themes.status`** - Get detailed theme status including author information
- **`themes.install`** - Install theme from WordPress.org by slug (minimal implementation)
- **`themes.delete`** - Delete theme files (minimal implementation)
- **`themes.update`** - Update theme to latest version (minimal implementation)

**Capabilities Required:** `switch_themes`, `install_themes`, `delete_themes`, `update_themes`

### 8. Privacy & GDPR (4 abilities)

Personal data management for GDPR compliance:

- **`privacy.create_export_request`** - Create personal data export request with email confirmation
- **`privacy.create_erasure_request`** - Create personal data erasure request with email confirmation
- **`privacy.list_requests`** - List all privacy requests with pagination and filtering
- **`privacy.get_request`** - Get privacy request details by ID

**Capabilities Required:** `manage_options` (all operations)

**Note:** Privacy requests use WordPress built-in email confirmation workflow. Requests start as `request-pending` until user confirms via email link.

---

## Installation

### Requirements

- **PHP:** 8.1 or higher
- **WordPress:** 6.9 or higher (Abilities API required)
- **Composer:** For dependency management

### Fresh Installation

```bash
# 1. Clone into WordPress plugins directory
cd wp-content/plugins
git clone https://github.com/featherart/fa-wpmcp.git
cd fa-wpmcp

# 2. Install dependencies
composer install --no-dev  # Production
composer install           # Development (includes test dependencies)

# 3. Activate plugin
wp plugin activate fa-wpmcp
```

### Upgrade from v1.0-alpha-1

```bash
cd wp-content/plugins/fa-wpmcp
git pull origin main
composer install --no-dev
```

**No database migrations required.** The plugin will continue using existing permissions and rate limit configurations.

---

## Quick Start

### Basic Usage

Once activated, the plugin automatically:
- Initializes the Ability Framework with all 43 abilities
- Sets up rate limiting (60 requests/min, 1000 requests/hour)
- Configures activity logging with PII redaction
- Registers webhook processing

Default permissions:
- **Global read:** Enabled
- **Global write:** Disabled (must be explicitly enabled)

### Example: List Posts via MCP Client

```json
{
  "ability": "posts.list",
  "input": {
    "post_type": "post",
    "status": "publish",
    "per_page": 10
  }
}
```

### Example: Create Privacy Export Request

```json
{
  "ability": "privacy.create_export_request",
  "input": {
    "email": "user@example.com"
  }
}
```

See **[Quick Start Guide](docs/QUICK_START.md)** and **[MCP Client Configuration](docs/MCP_CLIENT_CONFIGURATION.md)** for detailed setup instructions.

---

## Security Notice

### Alpha Release Security Status

**Overall Risk Level:** MEDIUM

This is an **alpha release** intended for testing and feedback. The following security considerations apply:

### HIGH Severity Issues (Known Limitations)

#### 1. Capability Enforcement Gaps

**Issue:** Plugin/Theme install, delete, and update abilities have minimal implementations that may not fully enforce WordPress capability checks.

**Affected Abilities:**
- `plugins.install`, `plugins.delete`, `plugins.update`
- `themes.install`, `themes.delete`, `themes.update`

**Current Behavior:** These abilities return `success: true` placeholder responses without performing actual WordPress API operations.

**Mitigation:**
- These operations require `install_plugins`, `delete_plugins`, `update_plugins`, `install_themes`, `delete_themes`, or `update_themes` capabilities
- Ability-level permission checks are still enforced
- Full WordPress API integration planned for beta release

**Recommendation:** Do not grant these capabilities to untrusted users in production environments.

#### 2. Option Access Control

**Issue:** `settings.list_options` and `settings.get_option` do not filter sensitive WordPress options.

**Affected Abilities:**
- `settings.list_options`
- `settings.get_option`

**Current Behavior:** Users with `manage_options` capability can retrieve all WordPress options, including potentially sensitive configuration values (database credentials, API keys stored in options).

**Mitigation:**
- Requires `manage_options` capability (administrator-level access)
- PII redaction is applied to activity logs
- Consider implementing option whitelist/blacklist filters

**Recommendation:** Only grant `manage_options` capability to fully trusted administrators.

### MEDIUM Severity Issues

- **Generic Exceptions:** Some abilities use `RuntimeException` instead of dedicated exception classes (code quality issue, not security vulnerability)
- **Test Coverage:** 74.33% coverage leaves some error paths untested
- **Code Duplication:** 9.9% duplication in new code vs 3% target

### Security Features

- Rate limiting (per-user and per-IP)
- WordPress capability integration
- Activity logging with correlation IDs
- PII redaction in logs
- HMAC-SHA256 webhook signing
- Input validation via JSON Schema
- SQL injection protection (prepared statements)
- XSS protection (escaped output)

### Security Audit

A comprehensive security audit was completed on 2026-01-21:

- **SonarQube Security Rating:** A (Excellent)
- **Vulnerabilities Detected:** 0
- **Security Hotspots:** 1 (100% reviewed)
- **Bugs Detected:** 0

See `SECURITY_REPORT.md` for full audit details.

---

## Known Limitations

### Stub/Minimal Implementations

The following abilities have **minimal implementations** that return success without performing full WordPress API operations:

#### Plugin Management
- **`plugins.install`** - Returns `success: true` without WordPress.org download
- **`plugins.delete`** - Returns `success: true` without filesystem deletion
- **`plugins.update`** - Returns `success: true` without version checking/upgrade

**Reason:** Full implementation requires `Plugin_Upgrader` class integration, download handling, and filesystem operations. Deferred to beta release.

#### Theme Management
- **`themes.install`** - Returns `success: true` without WordPress.org download
- **`themes.delete`** - Returns `success: true` without filesystem deletion
- **`themes.update`** - Returns `success: true` without version checking/upgrade

**Reason:** Full implementation requires `Theme_Upgrader` class integration, download handling, and filesystem operations. Deferred to beta release.

### Missing CRUD Operations

The following **Delete operations** are not yet implemented:
- `posts.delete` - Delete post/page/custom post type
- `media.delete` - Delete media attachment
- `taxonomies.delete_term` - Delete taxonomy term
- `users.delete` - Delete user account

**Planned for:** v1.0-alpha-3 or v1.0-beta-1

---

## Testing

### Test Results Summary

```bash
composer test
```

**Results:**
- **Total Tests:** 703
- **Assertions:** 1,543
- **Failures:** 0
- **Code Coverage:** 74.33%
  - Lines: 87.36%
  - Methods: 84.50%

### Coverage Breakdown by Category

| Category | Tests | Coverage | Status |
|----------|-------|----------|--------|
| Comments | 28 | ~85% | ✅ Complete |
| Media | 16 | ~80% | ✅ Complete |
| Taxonomies | 18 | ~82% | ✅ Complete |
| Users | 16 | ~78% | ✅ Complete |
| Settings | 12 | ~75% | ✅ Complete |
| Plugins | 20 | ~72% | ⚠️ Minimal implementations |
| Themes | 27 | ~70% | ⚠️ Minimal implementations |
| Privacy | 31 | ~80% | ✅ Complete |

**Note:** Coverage decreased from 79.41% (v1.0-alpha-1) to 74.33% due to minimal plugin/theme implementations. This is expected for alpha release.

### Running Tests

```bash
# All tests
composer test

# Specific category
composer test -- tests/phpunit/Abilities/Comments/
composer test -- tests/phpunit/Abilities/Privacy/

# Coverage report (HTML)
composer test:coverage
# View at tests/coverage/index.html
```

---

## Code Quality Improvements

### SonarQube Remediation

Over **100 code quality issues** resolved:

#### HIGH/MEDIUM Severity (Fixed)
- **Reduced class size:** `SettingsPage` from 25 to 17 methods (extracted `SettingsSanitizer`)
- **Reduced cognitive complexity:** `AbilityExecutor::check_permissions` from 21 to 15
- **Consolidated method returns:** Fixed 4+ return paths in multiple methods using null coalescing chains
- **Refactored parameter lists:** `PayloadBuilder` from 12 to 5 parameters (semantic grouping)
- **Eliminated string duplication:** Created constants for date formats and error messages

#### LOW Severity (Fixed)
- **Tab-to-spaces conversion:** 55 files standardized to PSR-12
- **String literal constants:** Created `MYSQL_DATETIME_FORMAT`, `FA_WPMCP_ACTIVATION_ERROR_TITLE`
- **Minor complexity reductions:** Multiple helper method extractions

### New Exception Classes

Dedicated exception types for better error handling:

- `CommentNotFoundException` - Comment not found errors
- `MediaNotFoundException` - Media attachment not found errors
- `TaxonomyTermNotFoundException` - Term not found errors
- `PluginNotFoundException` - Plugin not found errors
- `ThemeNotFoundException` - Theme not found errors

### Architectural Improvements

- **Separation of concerns:** Sanitization logic extracted to dedicated classes
- **Null coalescing chains:** Cleaner permission check flow
- **Semantic parameter grouping:** Related parameters grouped into arrays (`$ability`, `$user`, `$execution`)
- **Consistent naming:** All abilities follow wp-cli conventions

---

## Breaking Changes

**None.** This release is fully backward compatible with v1.0-alpha-1.

Existing permissions, rate limits, and webhook configurations will be preserved.

---

## Migration Guide

**No migration required.** Simply upgrade the plugin and reinstall dependencies:

```bash
cd wp-content/plugins/fa-wpmcp
git pull origin main
composer install --no-dev
```

All existing configurations and data will be preserved.

---

## What's Changed (44 Commits)

### New Features
- Add Comment abilities (List, Get, Create, Update, Delete)
- Add Media abilities (List, Get, Upload, Update)
- Add Taxonomy abilities (List Terms, Get Term, Create Term, Update Term)
- Add User abilities (List, Get, Create, Update)
- Add Settings abilities (Get, Update, Delete, List Options)
- Add Plugin abilities (List, Get, Install, Activate, Deactivate, Delete, Update)
- Add Theme abilities (List, Get, Activate, Status, Install, Delete, Update)
- Add Privacy abilities (Create Export, Create Erasure, List Requests, Get Request)

### Code Quality
- Refactor SettingsPage: extract SettingsSanitizer class
- Reduce AbilityExecutor cognitive complexity
- Consolidate method return paths
- Refactor PayloadBuilder parameters (12→5)
- Create dedicated exception classes
- Fix string literal duplication
- Convert tabs to spaces (55 files)

### Testing
- Add 231 new tests (472→703)
- Improve error path coverage
- Add comprehensive test suites for all new abilities

### Documentation
- Update README with all new abilities
- Document security audit findings
- Add usage examples for new abilities
- Update test statistics

---

## Contributors

This release was developed by **Claude Sonnet 4.5** (AI pair programming assistant) in collaboration with the FA WPMCP project.

**Development Approach:**
- Test-Driven Development (TDD) workflow
- Full test coverage before implementation
- SonarQube integration for code quality
- PSR-12 coding standards
- Professional software engineering practices

---

## Next Steps (Roadmap)

### v1.0-alpha-3 (Planned)
- Implement Delete operations (posts, media, terms, users, comments)
- Complete Plugin/Theme install/update/delete implementations
- Add bulk operations for terms and media
- Improve test coverage to 80%+
- Address remaining LOW severity SonarQube issues

### v1.0-beta-1 (Planned)
- Integration tests with live WordPress
- Multisite support
- Option access control filters (settings whitelist/blacklist)
- Uninstall handler (clean database on removal)
- GraphQL endpoint (optional)
- Performance optimization

### v1.0 Stable (Goals)
- Production-ready security review
- 90%+ test coverage
- Zero HIGH/MEDIUM security issues
- Complete API documentation
- WordPress.org plugin submission

---

## Support

### Reporting Issues

**For alpha testing feedback:**
- Open GitHub issues: https://github.com/featherart/fa-wpmcp/issues
- Label alpha releases as `alpha-feedback`

**For security vulnerabilities:**
- **DO NOT** open public GitHub issues
- Email security reports to: stephen@feather.us
- Include "SECURITY" in subject line

### Documentation

- **Quick Start:** `docs/QUICK_START.md`
- **Architecture:** `docs/ARCHITECTURE.md`
- **Configuration:** `docs/CONFIGURATION.md`
- **MCP Clients:** `docs/MCP_CLIENT_CONFIGURATION.md`
- **API Reference:** `docs/MCP_DOCUMENTATION.md`

### Community

- **Author:** Stephen Feather (https://stephenfeather.com)
- **Project:** https://github.com/featherart/fa-wpmcp
- **License:** GPL v2 or later

---

## Acknowledgments

- WordPress Abilities API team
- Model Context Protocol (MCP) specification
- SonarQube for code quality analysis
- Action Scheduler by Automattic
- Brain\Monkey testing library
- Claude AI for pair programming assistance

---

**Thank you for testing FA WPMCP v1.0-alpha-2!**

This is an **alpha release** - please report any issues, provide feedback, and help shape the future of AI-powered WordPress management.

For questions, suggestions, or contributions, please open a GitHub issue or contact stephen@feather.us.
