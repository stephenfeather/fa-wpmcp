# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0-alpha.3] - 2026-01-23

### Summary
Alpha 3 release focusing on core functionality stabilization and production-ready webhook system.

### Fixed
- Removed debug logging from ability registration system for production use
- Webhook event naming consistency (ability.error renamed to ability.failed)

### Changed
- **BREAKING**: Webhook event `ability.error` renamed to `ability.failed` for consistency
  - **Migration**: Update any webhook consumers listening for `ability.error` to use `ability.failed` instead
  - Applies to: ability execution errors, validation failures, permission denials
  - See documentation: `docs/WEBHOOKS.md` for event specifications

### Security
- **Status: MEDIUM Risk** - 4 MEDIUM severity issues identified for beta resolution:
  - Remaining issues documented and planned for v1.0.0-beta release
  - Production deployment should await beta with full security fixes
  - See: `.claude/cache/agents/aegis/` for detailed audit reports

### Testing
- **Test Coverage: 94.2%** (670/711 tests passing)
- Core ability registration and execution fully validated
- Webhook system integration tested with Action Scheduler
- WordPress 6.9 Abilities API compliance verified

### Developer Documentation
- Comprehensive developer guides added to `docs/` directory
- Architecture documentation for ability framework
- MCP integration examples and configuration guides

## [1.0.0-beta.1] - 2026-01-22

### Added
- **Security Documentation** - Comprehensive `docs/SECURITY.md` with:
  - Current security features (10 implemented protections)
  - Intentionally unimplemented abilities with security considerations
  - Future security enhancements roadmap
  - Security audit history
  - Best practices and configuration examples
- PHPDoc security considerations for stub abilities (InstallPlugin, DeletePlugin, InstallTheme, DeleteTheme)
- Option name validation with `sanitize_key()` and 191-character length limit
- Destructive operation annotations for delete abilities (DeleteOption, DeletePlugin, DeleteTheme)

### Security
- **Risk Level Reduced: LOW** (down from MEDIUM in v1.0-alpha-2)
- **HIGH Severity Issues (2 fixed in v1.0-alpha-2):**
  - Missing per-ability capability enforcement in AbilityExecutor
  - Settings abilities allow unrestricted option access
- **MEDIUM Severity Issues (4 fixed in v1.0-alpha-2):**
  - Comment content not sanitized (potential XSS)
  - Incomplete PII redaction in activity logs (8 fields → 50+ fields)
  - Webhook secret displayed in admin form
  - Stub implementations return fake success
- **LOW Severity Issues (2 of 3 fixed in v1.0-beta-1):**
  - Option name format validation (✅ FIXED: sanitize_key + length validation)
  - Destructive annotations missing (✅ FIXED: Added to all delete abilities)
  - IP address anonymization (📋 PLANNED: v1.1.0 as admin option)
- Security audit report: `.claude/cache/agents/aegis/output-20260122-security-audit-v1beta1.md`
- All 707 tests passing with 1563 assertions, 74.19% code coverage

### Changed
- Updated README.md security section to reference comprehensive security documentation
- Enhanced PrivacyRedactor with 50+ sensitive field patterns (auth, secrets, PII)
- Settings abilities now validate option names before database operations

## [1.0.0-alpha.2] - 2026-01-22

### Added
- **Privacy Abilities** - GDPR compliance support for data export and deletion requests
- **Theme Abilities** - WordPress theme management (List, Get, Activate, Install, Delete)
- **Plugin Abilities** - WordPress plugin management (List, Get, Activate, Deactivate, Install, Delete)
- **Settings Abilities** - WordPress options management (Get, Update, Delete, List Options)
- **User Abilities** - WordPress user management (List, Get, Create, Update)
- **Taxonomy Abilities** - WordPress taxonomy management (List Terms, Get Term, Create Term, Update Term, Delete Term)
- **Media Abilities** - WordPress media library management (List, Get, Upload, Update, Delete)
- **Comment Abilities** - WordPress comment management (List, Get, Create, Update)
- Comprehensive ability documentation in markdown format
- GitHub Action workflow for automated release packaging
- Unit tests for uninstall.php
- XML coverage output for SonarCloud integration

### Changed
- **Code Quality Improvements** - Resolved 100+ SonarQube issues:
  - Fixed tab characters in 55 files (php:S105)
  - Renamed 200+ functions and fields to camelCase (php:S100, php:S116)
  - Reduced method complexity across multiple classes
  - Refactored SettingsPage to reduce class size
  - Reduced PayloadBuilder parameter count with semantic grouping
  - Replaced generic RuntimeException with dedicated exception classes
  - Eliminated string duplication with constants
  - Streamlined permission checks with null coalescing
- Enhanced test coverage for comment, post, and media abilities
- Updated README to reflect completed ability implementations
- Added post_type parameter support to post abilities

### Fixed
- Media abilities SonarQube issues (5 issues resolved)
- GetUser excessive return statements (php:S1142)
- SettingsPageTest method naming conventions (php:S100)
- check_category_permission return count accuracy
- Webhook scheduler test expectations for Action Scheduler

### Security
- Comprehensive security audit completed with SonarQube
- Identified and documented security considerations in ability implementations

## [1.0-alpha-1] - 2026-01-15

### Added
- Initial alpha release
- Post abilities (List, Get, Create, Update, Delete)
- Basic MCP Adapter integration
- WordPress Abilities API integration
- Settings page for configuration
- Webhook scheduling with Action Scheduler

[Unreleased]: https://github.com/featherart/fa-wpmcp/compare/v1.0.0-alpha.3...HEAD
[1.0.0-alpha.3]: https://github.com/featherart/fa-wpmcp/compare/v1.0.0-beta.1...v1.0.0-alpha.3
[1.0.0-beta.1]: https://github.com/featherart/fa-wpmcp/compare/v1.0-alpha-2...v1.0.0-beta.1
[1.0.0-alpha.2]: https://github.com/featherart/fa-wpmcp/compare/v1.0-alpha-1...v1.0-alpha-2
[1.0-alpha-1]: https://github.com/featherart/fa-wpmcp/releases/tag/v1.0-alpha-1
