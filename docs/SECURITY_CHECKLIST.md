# Security Remediation Checklist

**Project:** fa-wpmcp
**Generated:** 2026-01-21
**Quality Gate Status:** ❌ FAILED
**Priority:** Complete Phase 1 before release

---

## Immediate Actions (Do First) ⚡

- [ ] **Review Security Hotspot** (30-60 min)
  - Visit: https://sonarcloud.io/project/security_hotspots?id=stephenfeather_fa-wpmcp
  - Review the 1 flagged security hotspot
  - Mark as "Safe" or "Fixed" with justification
  - Document findings in code comments

---

## Phase 1: Critical Issues (Week 1) 🔴

**Goal:** Address security-critical issues blocking release

### Day 1-2: Refactor High-Complexity Functions

- [ ] **Refactor AbilityExecutor::execute_with_validation()** (src/Abilities/AbilityExecutor.php:186)
  - Current complexity: 21 → Target: ≤15
  - Extract `validate_permissions()` method
  - Extract `check_rate_limit()` method
  - Extract `execute_ability()` method
  - Merge nested conditionals at lines 207, 216
  - Reduce return statements from 6 to 3 or less
  - **Success Criteria:** SonarQube complexity ≤15, all tests pass

- [ ] **Refactor SettingsPage::render_webhook_configuration()** (src/Admin/SettingsPage.php:900)
  - Current complexity: 18 → Target: ≤15
  - Extract rendering logic into separate methods
  - Simplify conditional branching
  - Ensure all output is properly escaped
  - **Success Criteria:** SonarQube complexity ≤15, XSS audit passed

### Day 3-5: Add Critical Test Coverage

- [ ] **Add AbilityExecutor tests** (Target: 90%+ coverage)
  - Test permission denial paths
  - Test rate limiting scenarios
  - Test error handling for each return path
  - Test nested conditional logic
  - **Files to update:** `tests/phpunit/Abilities/AbilityExecutorTest.php`

- [ ] **Add error path tests for Post abilities**
  - Test post not found scenarios (GetPost.php:170, UpdatePost.php:192)
  - Test post type mismatch (GetPost.php:175, UpdatePost.php:197)
  - Test post creation failures (CreatePost.php:191)
  - **Files:** `tests/phpunit/Abilities/Posts/*Test.php`

- [ ] **Add error path tests for Comment abilities**
  - Test comment not found (GetComment.php:131)
  - Test comment creation failure (CreateComment.php:158)
  - Test comment update failure (UpdateComment.php:136)
  - **Files:** `tests/phpunit/Abilities/Comments/*Test.php`

### Phase 1 Success Metrics

| Metric | Start | Target | Status |
|--------|-------|--------|--------|
| AbilityExecutor Complexity | 21 | ≤15 | ⬜ |
| SettingsPage Complexity | 18 | ≤15 | ⬜ |
| Test Coverage (New Code) | 67.9% | 75%+ | ⬜ |
| Security Hotspots Reviewed | 100% | 100% | ⬜ |

---

## Phase 2: High-Priority Issues (Week 2) 🟡

**Goal:** Eliminate medium-high severity security issues

### Week 2 Task 1: Create Exception Hierarchy (2-3 hours)

- [ ] **Create exception classes** in `src/Exceptions/`
  ```php
  namespace FeatherArms\WPMCP\Exceptions;

  class PostNotFoundException extends \Exception {}
  class PostTypeMismatchException extends \Exception {}
  class PostCreationFailedException extends \Exception {}
  class PostUpdateFailedException extends \Exception {}
  class CommentNotFoundException extends \Exception {}
  class CommentCreationFailedException extends \Exception {}
  class CommentUpdateFailedException extends \Exception {}
  class UnauthorizedException extends \Exception {}
  ```

- [ ] **Replace generic exceptions** (7 locations):
  - `src/Abilities/Posts/CreatePost.php:191` → `PostCreationFailedException`
  - `src/Abilities/Posts/GetPost.php:170` → `PostNotFoundException`
  - `src/Abilities/Posts/GetPost.php:175` → `PostTypeMismatchException`
  - `src/Abilities/Posts/UpdatePost.php:192` → `PostNotFoundException`
  - `src/Abilities/Posts/UpdatePost.php:197` → `PostTypeMismatchException`
  - `src/Abilities/Comments/CreateComment.php:158` → `CommentCreationFailedException`
  - `src/Abilities/Comments/GetComment.php:131` → `CommentNotFoundException`
  - `src/Abilities/Comments/UpdateComment.php:136` → `CommentUpdateFailedException`

- [ ] **Update exception handling** in AbilityExecutor
  - Catch specific exceptions
  - Log appropriate context for each exception type
  - Return proper HTTP status codes (404, 400, 500)

- [ ] **Update tests** to expect new exception types

### Week 2 Task 2: Simplify Multiple Return Paths (3-4 hours)

- [ ] **Refactor OptionsWebhookConfig::get_config()** (src/Webhooks/OptionsWebhookConfig.php:25)
  - Reduce from 4 returns to 1
  - Use single variable pattern
  - **Success Criteria:** SonarQube issue resolved

- [ ] **Refactor RateLimitCalculator::calculate_limit()** (src/RateLimiting/RateLimitCalculator.php:34)
  - Reduce from 4 returns to 1
  - Use guard clauses
  - **Success Criteria:** SonarQube issue resolved

### Week 2 Task 3: Refactor PayloadBuilder (2-3 hours)

- [ ] **Create PayloadData DTO**
  ```php
  namespace FeatherArms\WPMCP\ValueObjects;

  class PayloadData {
      public function __construct(
          public readonly string $ability_name,
          public readonly array $input,
          public readonly array $output,
          public readonly string $client_ip,
          public readonly int $user_id,
          public readonly string $timestamp,
          // ... other parameters
      ) {}
  }
  ```

- [ ] **Update PayloadBuilder** (src/Webhooks/PayloadBuilder.php:39-52)
  - Change method signature to accept PayloadData
  - Update all callers
  - **Success Criteria:** Parameter count ≤7

- [ ] **Update tests** for PayloadBuilder

### Week 2 Task 4: Increase Test Coverage (8-10 hours)

- [ ] **Add SettingsPage AJAX handler tests**
  - Test permission checks for each AJAX action
  - Test nonce validation
  - Test input sanitization
  - Test error responses

- [ ] **Add rate limiting edge case tests**
  - Test burst limits
  - Test time window boundaries
  - Test per-user vs global limits

- [ ] **Add webhook delivery tests**
  - Test signature generation
  - Test retry logic
  - Test failure handling

### Phase 2 Success Metrics

| Metric | Start | Target | Status |
|--------|-------|--------|--------|
| Generic Exceptions | 7 | 0 | ⬜ |
| Multiple Returns (>3) | 3 | 0 | ⬜ |
| Excessive Parameters | 1 | 0 | ⬜ |
| Test Coverage | 75% | 80%+ | ⬜ |

---

## Phase 3: Medium-Priority Issues (Week 3-4) 🟠

**Goal:** Reduce technical debt and code duplication

### Week 3 Task 1: Extract Security Message Constants (2-3 hours)

- [ ] **Create SecurityMessages class** in `src/Constants/SecurityMessages.php`
  ```php
  namespace FeatherArms\WPMCP\Constants;

  final class SecurityMessages {
      public const PERMISSION_DENIED = 'Permission Denied';
      public const PERMISSION_DENIED_ACCESS = 'You do not have permission to access this page.';
      public const PERMISSION_DENIED_ACTION = 'You do not have permission to perform this action.';
      public const SECURITY_CHECK_FAILED = 'Security check failed. Please try again.';
      public const SECURITY_ERROR = 'Security Error';
      public const PLUGIN_ACTIVATION_ERROR = 'Plugin Activation Error';
  }
  ```

- [ ] **Replace hardcoded strings in SettingsPage.php**
  - Line 202: "You do not have permission to access this page." (4 occurrences)
  - Line 203: "Permission Denied" (8 occurrences)
  - Line 585: "Security check failed. Please try again." (4 occurrences)
  - Line 586: "Security Error" (4 occurrences)
  - Line 594: "You do not have permission to perform this action." (4 occurrences)

- [ ] **Replace hardcoded strings in fa-wpmcp.php**
  - Line 67: "Plugin Activation Error" (3 occurrences)

- [ ] **Success Criteria:** SonarQube S1192 issues for security messages resolved

### Week 3 Task 2: Extract Date/Time Constants (1 hour)

- [ ] **Create DateTimeFormats class** in `src/Constants/DateTimeFormats.php`
  ```php
  namespace FeatherArms\WPMCP\Constants;

  final class DateTimeFormats {
      public const MYSQL_DATETIME = 'Y-m-d H:i:s';
      public const ISO8601 = 'c';
      public const DISPLAY_DATE = 'F j, Y';
  }
  ```

- [ ] **Replace in DatabaseWebhookQueue.php** (line 43, 4 occurrences)
  - Use `DateTimeFormats::MYSQL_DATETIME`
  - **Success Criteria:** Timing attack risk eliminated

### Week 3 Task 3: Split Large Classes (4-6 hours)

- [ ] **Extract WebhookSettingsRenderer** from SettingsPage
  - Move `render_webhook_configuration()`
  - Move `render_webhook_list()`
  - Move related private rendering methods
  - **Target:** Reduce SettingsPage from 23 to ≤20 methods

- [ ] **Extract PermissionManager** utility class
  - Move `check_admin_access()`
  - Move `check_nonce()`
  - Move permission-related helpers

- [ ] **Update SettingsPage** to use extracted classes
  - Inject dependencies in constructor
  - Update all method calls

- [ ] **Success Criteria:** SonarQube S1448 issue resolved

### Week 4 Task 1: Create Test Helper Classes (3-4 hours)

- [ ] **Create TestConstants class** in `tests/phpunit/TestConstants.php`
  ```php
  namespace FeatherArms\WPMCP\Tests;

  final class TestConstants {
      public const TEST_IP_LOCAL = '127.0.0.1';
      public const TEST_IP_PRIVATE = '192.168.1.1';
      public const ABILITY_LIST_POSTS = 'fa-wpmcp/list-posts';
      public const ABILITY_CREATE_POST = 'fa-wpmcp/create-post';
      public const ABILITY_GET_POST = 'fa-wpmcp/get-post';
      public const ABILITY_UPDATE_POST = 'fa-wpmcp/update-post';
  }
  ```

- [ ] **Create MockDataBuilder class**
  - Build common mock WP_Post objects
  - Build common mock WP_User objects
  - Build common mock WP_Comment objects

- [ ] **Replace hardcoded test strings** (61 closed issues)
  - Extract from AbilityExecutorTest.php
  - Extract from AbilityRegistryTest.php
  - Extract from CreatePostTest.php
  - Extract from RateLimitCalculatorTest.php
  - Extract from other test files

### Week 4 Task 2: Create Form Rendering Helpers (2-3 hours)

- [ ] **Create FormRenderer class** in `src/Admin/FormRenderer.php`
  - `render_text_input(string $name, string $value, array $attrs = []): void`
  - `render_textarea(string $name, string $value, array $attrs = []): void`
  - `render_select(string $name, array $options, string $selected): void`
  - Ensure all output is escaped via `esc_attr()`, `esc_html()`

- [ ] **Replace hardcoded HTML in SettingsPage.php**
  - Line 380: `value=""` (7 occurrences)
  - Other repeated form markup

- [ ] **Success Criteria:** HTML duplication reduced, XSS risk minimized

### Phase 3 Success Metrics

| Metric | Start | Target | Status |
|--------|-------|--------|--------|
| Code Duplication (New Code) | 9.9% | <5% | ⬜ |
| SettingsPage Method Count | 23 | ≤20 | ⬜ |
| S1192 Duplication Issues | 7 | 0 | ⬜ |
| Overall Project Duplication | 4.4% | <3% | ⬜ |

---

## Phase 4: Maintenance (Ongoing) 🔵

### Daily/Weekly Habits

- [ ] **Monitor SonarQube dashboard** for new issues
  - Check before each commit
  - Review weekly summary

- [ ] **Run local quality checks** before pushing
  ```bash
  composer phpcs        # Check coding standards
  composer test         # Run tests
  composer test:coverage # Check coverage
  ```

- [ ] **Maintain test coverage** for all new code
  - Minimum 80% coverage for new files
  - 100% coverage for security-critical code

### Monthly Review

- [ ] **Security hotspot review** (1st week of month)
  - Review all hotspots in SonarQube
  - Re-audit permission checks
  - Check for new WordPress security advisories

- [ ] **Dependency updates** (2nd week of month)
  - Run `composer update`
  - Check GitHub Dependabot alerts
  - Review CVE databases for PHP/WordPress

- [ ] **Code quality metrics** (3rd week of month)
  - Review duplication trends
  - Check complexity growth
  - Analyze coverage trends

---

## Quality Gate Targets

### Current Status vs Goals

| Condition | Current | Target | Gap | Status |
|-----------|---------|--------|-----|--------|
| New Code Coverage | 67.9% | 80% | -12.1% | ❌ |
| New Code Duplication | 9.9% | ≤3% | +6.9% | ❌ |
| Security Rating | A | A | 0 | ✅ |
| Reliability Rating | A | A | 0 | ✅ |
| Maintainability Rating | A | A | 0 | ✅ |
| Security Hotspots Reviewed | 100% | 100% | 0 | ✅ |

### Release Criteria

**Minimum Requirements for v1.0 Release:**
- ✅ Security Rating: A
- ✅ Reliability Rating: A
- ✅ Maintainability Rating: A
- ❌ Test Coverage: ≥75% (current: 67.9%)
- ❌ Code Duplication: <5% (current: 9.9%)
- ✅ Security Hotspots: 100% reviewed
- ⬜ Phase 1 complete
- ⬜ Phase 2 complete (50%+)

---

## Helper Commands

### Run Tests
```bash
# Run all tests
composer test

# Run tests with coverage
composer test:coverage

# Run specific test file
vendor/bin/phpunit tests/phpunit/Abilities/AbilityExecutorTest.php

# Run tests for specific class
vendor/bin/phpunit --filter AbilityExecutor
```

### Check Code Quality
```bash
# Check coding standards
composer phpcs

# Fix coding standards automatically
composer phpcbf

# Run static analysis
composer phpstan
```

### SonarQube Local Scan
```bash
# Install SonarScanner
brew install sonar-scanner  # macOS

# Run local scan
sonar-scanner \
  -Dsonar.projectKey=stephenfeather_fa-wpmcp \
  -Dsonar.organization=stephenfeather \
  -Dsonar.sources=src \
  -Dsonar.tests=tests \
  -Dsonar.php.coverage.reportPaths=tests/coverage/clover.xml \
  -Dsonar.host.url=https://sonarcloud.io \
  -Dsonar.token=$SONAR_TOKEN
```

### Generate Coverage Report
```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html tests/coverage

# Open in browser (macOS)
open tests/coverage/index.html
```

---

## Progress Tracking

### Phase Completion

- [ ] **Phase 1 (Week 1)** - Critical Issues
  - Started: ___________
  - Completed: ___________
  - Duration: ___ hours

- [ ] **Phase 2 (Week 2)** - High Priority
  - Started: ___________
  - Completed: ___________
  - Duration: ___ hours

- [ ] **Phase 3 (Week 3-4)** - Medium Priority
  - Started: ___________
  - Completed: ___________
  - Duration: ___ hours

### Issue Resolution Log

| Issue | Priority | Started | Completed | Notes |
|-------|----------|---------|-----------|-------|
| Security Hotspot Review | 🔴 | | | |
| AbilityExecutor Complexity | 🔴 | | | |
| SettingsPage Complexity | 🔴 | | | |
| Exception Hierarchy | 🟡 | | | |
| Multiple Returns | 🟡 | | | |
| PayloadBuilder Params | 🟡 | | | |
| Security Message Constants | 🟠 | | | |
| Split SettingsPage | 🟠 | | | |

---

## Success Criteria Summary

### Phase 1 Complete When:
- ✅ Security hotspot reviewed and marked safe
- ✅ AbilityExecutor complexity ≤15
- ✅ SettingsPage complexity ≤15
- ✅ Test coverage ≥75%
- ✅ All tests passing

### Phase 2 Complete When:
- ✅ All 7 generic exceptions replaced
- ✅ All methods have ≤3 return statements
- ✅ PayloadBuilder has ≤7 parameters
- ✅ Test coverage ≥80%
- ✅ All tests passing

### Phase 3 Complete When:
- ✅ Security message constants extracted
- ✅ SettingsPage has ≤20 methods
- ✅ Code duplication <5% (new code)
- ✅ Form rendering helpers implemented
- ✅ Test constants extracted

### Quality Gate PASS When:
- ✅ New code coverage ≥80%
- ✅ New code duplication ≤3%
- ✅ Security rating A
- ✅ All phases 1-2 complete

---

**Last Updated:** 2026-01-21
**Owner:** sfeather@gmail.com
**SonarQube Project:** https://sonarcloud.io/project/overview?id=stephenfeather_fa-wpmcp
