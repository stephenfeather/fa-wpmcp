# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/featherart/fa-wpmcp/compare/v1.0-alpha-2...HEAD
[1.0.0-alpha.2]: https://github.com/featherart/fa-wpmcp/compare/v1.0-alpha-1...v1.0-alpha-2
[1.0-alpha-1]: https://github.com/featherart/fa-wpmcp/releases/tag/v1.0-alpha-1
