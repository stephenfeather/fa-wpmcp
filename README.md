# FA WPMCP - WordPress MCP Plugin

WordPress plugin that exposes WordPress functionality to AI agents via the Abilities API and MCP Adapter.

## Requirements

- **PHP:** 8.1 or higher
- **WordPress:** 6.9 or higher (Abilities API)
- **Composer:** For dependency management

## Installation

1. Clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/featherart/fa-wpmcp.git
   cd fa-wpmcp
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Activate the plugin in WordPress admin.

## Development

### Running Tests

```bash
composer test
```

### Code Standards

Check code standards:
```bash
composer phpcs
```

Fix code standards automatically:
```bash
composer phpcbf
```

### Test Coverage

Generate coverage report:
```bash
composer test:coverage
```

Coverage report will be in `tests/coverage/index.html`.

## Features

- **Abilities Framework:** Extensible framework for registering WordPress abilities
- **MCP Integration:** Model Context Protocol adapter for AI agents
- **Permission System:** Multi-level permission controls (global → category → ability)
- **Activity Logging:** Comprehensive audit trail with correlation IDs
- **Rate Limiting:** Dual-track rate limiting (per user + IP)
- **Webhook System:** Event notifications with HMAC signing
- **GDPR Compliance:** Privacy controls and PII redaction

## Architecture

This plugin follows Test-Driven Development (TDD) and Functional Programming (FP) principles:

- **Pure functions** for business logic
- **Immutable value objects** using PHP 8.1 readonly properties
- **Type safety** with strict types and full type declarations
- **Function composition** via pipeline patterns

## License

GPL v2 or later

## Author

Feather Art
