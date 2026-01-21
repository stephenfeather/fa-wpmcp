# Phase 1.12 - Error Handling Infrastructure

## Overview
Create error handling infrastructure with ResponseFormatter, PrivacyRedactor, and ErrorCodes classes using TDD approach.

## Checkpoints
<!-- Resumable state for kraken agent -->
**Task:** Phase 1.12 - Error Handling & Response Contract
**Started:** 2026-01-21T07:00:00Z
**Last Updated:** 2026-01-21T10:30:00Z

### Phase Status
- Phase 1.12a (Tests Written): VALIDATED (47 tests, all fail with Class not found)
- Phase 1.12b (ErrorCodes): VALIDATED (implemented)
- Phase 1.12c (ResponseFormatter): VALIDATED (24 tests passing)
- Phase 1.12d (PrivacyRedactor): VALIDATED (23 tests passing)
- Phase 1.12e (Refactoring): PENDING (optional)

### Validation State
```json
{
  "test_count": 47,
  "tests_passing": 47,
  "tests_failing": 0,
  "files_created": [
    "tests/phpunit/Http/ResponseFormatterTest.php",
    "tests/phpunit/Http/PrivacyRedactorTest.php",
    "src/Http/ErrorCodes.php",
    "src/Http/ResponseFormatter.php",
    "src/Http/PrivacyRedactor.php"
  ],
  "last_test_command": "composer test -- --filter Http",
  "last_test_exit_code": 0,
  "phpcs_status": "PASS",
  "total_suite_tests": 335,
  "total_suite_passing": 335
}
```

### Resume Context
- Current focus: GREEN phase complete
- Next action: Optional REFACTOR phase or proceed to Phase 1.13
- Blockers: None

## Test Files Created

### ResponseFormatterTest.php (24 tests)
Path: `tests/phpunit/Http/ResponseFormatterTest.php`

Tests success/error response formatting and HTTP status mapping:
- Success format with required fields (success, data, meta)
- Error format with code, message, optional details
- HTTP status code mapping (401, 403, 404, 429, 500, etc.)
- Pure function behavior verification

### PrivacyRedactorTest.php (23 tests)
Path: `tests/phpunit/Http/PrivacyRedactorTest.php`

Tests sensitive field redaction:
- Basic field redaction (password, token, api_key, secret, etc.)
- Nested structure handling (3+ levels deep)
- Case-insensitive matching
- Pure function behavior (immutability)
- Edge cases (empty arrays, null values)

## Implementation Files (To Create in GREEN Phase)

### src/Http/ResponseFormatter.php
```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Http;

final class ResponseFormatter {
    public static function success(array $data, string $correlationId, int $executionTimeMs): array;
    public static function error(string $code, string $message, ?array $details = null, ?int $retryAfter = null): array;
    public static function getHttpStatus(string $errorCode): int;
}
```

### src/Http/PrivacyRedactor.php
```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Http;

final class PrivacyRedactor {
    public static function redact(array $data): array;
}
```

### src/Http/ErrorCodes.php
```php
<?php
declare(strict_types=1);

namespace FAWpmcp\Http;

final class ErrorCodes {
    public const AUTHENTICATION_REQUIRED = 'authentication_required';
    public const INSUFFICIENT_PERMISSIONS = 'insufficient_permissions';
    public const RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    public const VALIDATION_ERROR = 'validation_error';
    public const NOT_FOUND = 'not_found';
    public const INTERNAL_ERROR = 'internal_error';
    public const METHOD_NOT_ALLOWED = 'method_not_allowed';
    public const CONFLICT = 'conflict';

    public const HTTP_STATUS_MAP = [
        self::AUTHENTICATION_REQUIRED => 401,
        self::INSUFFICIENT_PERMISSIONS => 403,
        self::RATE_LIMIT_EXCEEDED => 429,
        self::VALIDATION_ERROR => 400,
        self::NOT_FOUND => 404,
        self::INTERNAL_ERROR => 500,
        self::METHOD_NOT_ALLOWED => 405,
        self::CONFLICT => 409,
    ];
}
```

## Sensitive Fields to Redact
- password
- user_pass
- token
- access_token
- refresh_token
- api_key
- apikey
- secret

## Error Code to HTTP Status Mapping
| Error Code | HTTP Status |
|------------|-------------|
| authentication_required | 401 |
| insufficient_permissions | 403 |
| rate_limit_exceeded | 429 |
| validation_error | 400 |
| not_found | 404 |
| internal_error | 500 |
| method_not_allowed | 405 |
| conflict | 409 |
| (unknown) | 500 (default) |
