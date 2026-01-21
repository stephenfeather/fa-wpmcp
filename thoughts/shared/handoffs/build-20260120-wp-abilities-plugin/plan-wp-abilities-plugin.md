# Handoff: Implementation Plan Complete (TDD + FP Revision)

**Session:** build-20260120-wp-abilities-plugin
**Date:** 2026-01-20
**Phase:** Planning Complete, Ready for Implementation
**Agent:** plan-agent
**Revision:** TDD + Functional Programming Emphasis

---

## Summary

Created comprehensive implementation plan for FA-WPMCP WordPress plugin Phase 1. The plan has been revised to emphasize **Test-Driven Development (TDD)** and **Functional Programming (FP)** principles throughout all 16 phases.

---

## Artifacts Created

| File | Purpose |
|------|---------|
| `thoughts/shared/plans/PLAN-wp-abilities-plugin.md` | Full implementation plan with 16 phases (TDD + FP) |

---

## TDD + FP Approach

### Test-Driven Development

Every phase now follows the Red-Green-Refactor cycle:
- **Phase X.Ya (RED):** Write failing tests first
- **Phase X.Yb (GREEN):** Implement minimal code to pass tests
- **Phase X.Yc (REFACTOR):** Clean up while keeping tests green

### Functional Programming Patterns

| Pattern | Where Applied |
|---------|---------------|
| **Pure Functions** | PermissionChecker, RateLimitCalculator, SignatureGenerator, PayloadBuilder |
| **Immutability** | PermissionSettings, RateLimit, RateLimitResult, LogEntry, WebhookPayload, Result |
| **Pipeline Composition** | ExecutionPipeline in AbilityExecutor |
| **Higher-Order Functions** | Data transformations with array_map, array_filter, array_reduce |
| **Type Safety** | `declare(strict_types=1)`, readonly properties, return types |

### WordPress Pragmatism

Side effects are acceptable and isolated to:
- Database operations (inherently stateful)
- WordPress hook integration (required for plugin architecture)
- Logging and HTTP responses
- External API calls

---

## Plan Overview

### Scope (Phase 1)

- Core plugin scaffolding with Composer/PSR-4
- **Value Objects:** Immutable data containers (PermissionSettings, RateLimit, Result, etc.)
- **Permission System:** Pure function PermissionChecker + orchestrating PermissionManager
- **Rate Limiting:** Pure function RateLimitCalculator + orchestrating RateLimiter
- **Logging:** Immutable LogEntry + LogEntryBuilder pattern
- **Webhooks:** Pure PayloadBuilder + SignatureGenerator
- **Ability Framework:** ExecutionPipeline for composable execution flow
- **Post Abilities:** 4 example abilities with pure transformations
- Admin settings UI with multi-level controls
- Standardized error handling with pure ResponseFormatter
- Unit tests targeting 70%+ coverage (written first per TDD)

### Phases (TDD Structure)

| Phase | Name | Sub-phases | Effort |
|-------|------|------------|--------|
| 1.1 | Scaffolding | 1.1a (RED), 1.1b (GREEN), 1.1c (REFACTOR) | 1-2h |
| 1.2 | Plugin Class | 1.2a (RED), 1.2b (GREEN), 1.2c (REFACTOR) | 1-2h |
| 1.3 | Database | 1.3a (RED), 1.3b (GREEN), 1.3c (REFACTOR) | 2-3h |
| 1.4 | Permissions | 1.4a (RED), 1.4b (GREEN), 1.4c (REFACTOR) | 4-5h |
| 1.5 | Logging | 1.5a (RED), 1.5b (GREEN), 1.5c (REFACTOR) | 3-4h |
| 1.6 | Rate Limiting | 1.6a (RED), 1.6b (GREEN), 1.6c (REFACTOR) | 3-4h |
| 1.7 | Webhooks | 1.7a (RED), 1.7b (GREEN), 1.7c (REFACTOR) | 4-5h |
| 1.8 | Ability Framework | 1.8a (RED), 1.8b (GREEN), 1.8c (REFACTOR) | 4-5h |
| 1.9 | MCP Integration | 1.9a (RED), 1.9b (GREEN), 1.9c (REFACTOR) | 2-3h |
| 1.10 | Post Abilities | 1.10a (RED), 1.10b (GREEN), 1.10c (REFACTOR) | 5-6h |
| 1.11 | Admin UI | 1.11a (RED), 1.11b (GREEN), 1.11c (REFACTOR) | 4-5h |
| 1.12 | Error Handling | 1.12a (RED), 1.12b (GREEN), 1.12c (REFACTOR) | 2-3h |
| 1.13 | GDPR | 1.13a (RED), 1.13b (GREEN), 1.13c (REFACTOR) | 1-2h |
| 1.14 | Uninstall | 1.14a (RED), 1.14b (GREEN), 1.14c (REFACTOR) | 1h |
| 1.15 | Integration Tests | Full suite verification + integration tests | 4-5h |
| 1.16 | Documentation | Including FP patterns guide | 3-4h |

**Total Estimate:** 45-60 hours (increased for TDD overhead)

### Key FP Components

| Component | Type | Pure? |
|-----------|------|-------|
| `PermissionChecker` | Static class | Yes - pure functions |
| `RateLimitCalculator` | Static class | Yes - pure functions |
| `SignatureGenerator` | Static class | Yes - pure functions |
| `PayloadBuilder` | Static class | Yes - pure functions |
| `ResponseFormatter` | Static class | Yes - pure functions |
| `PrivacyRedactor` | Static class | Yes - pure functions |
| `ExecutionPipeline` | Class | Yes - immutable composition |
| `PermissionSettings` | Value Object | Yes - readonly, immutable |
| `RateLimit` | Value Object | Yes - readonly, immutable |
| `RateLimitResult` | Value Object | Yes - readonly, immutable |
| `LogEntry` | Value Object | Yes - readonly, immutable |
| `WebhookPayload` | Value Object | Yes - readonly, immutable |
| `Result` | Value Object | Yes - Success/Failure container |

---

## What's Next

### Immediate (Implementation Start)

1. Begin Phase 1.1a: Write Scaffolding Tests (RED)
   - Create `tests/phpunit/PluginActivationTest.php`
   - Tests define expected behavior for plugin bootstrap

2. Phase 1.1b: Implement Scaffolding (GREEN)
   - Create `fa-wpmcp.php` main plugin file
   - Set up `composer.json` with dependencies
   - Make tests pass

3. Phase 1.1c: Refactor (REFACTOR)
   - Clean up code while tests stay green

### TDD Workflow for Each Phase

```
Write Test (RED)
    |
    v
Run Test - Should FAIL
    |
    v
Write Implementation (GREEN)
    |
    v
Run Test - Should PASS
    |
    v
Refactor (REFACTOR)
    |
    v
Run Test - Should still PASS
```

### Parallel Work Possible

After Phase 1.2 completes, these can run in parallel:
- Phase 1.3 (Database) + Phase 1.4 (Permissions) + Phase 1.6 (Rate Limiting)

### Testing Checkpoints

- Unit tests written BEFORE implementation (TDD)
- Run tests after each sub-phase
- Integration tests after Phase 1.10
- 70%+ coverage verified in Phase 1.15

---

## Risks Identified

| Risk | Mitigation |
|------|------------|
| Abilities API changes in WP 6.9+ | Pin versions, test with betas |
| MCP Adapter compatibility | Follow WP Core releases |
| PHP 8.0+ readonly requirements | Document in plugin requirements |
| TDD learning curve | Document patterns, provide examples |
| FP over-engineering | Be pragmatic, use where it adds value |

---

## New File Structure (FP Additions)

```
src/
├── ValueObjects/           # NEW - Immutable data containers
│   ├── PermissionSettings.php
│   ├── RateLimit.php
│   ├── RateLimitResult.php
│   ├── LogEntry.php
│   ├── WebhookPayload.php
│   └── Result.php
├── Permissions/
│   ├── PermissionChecker.php   # NEW - Pure functions
│   └── PermissionManager.php   # Orchestration (side effects)
├── RateLimiting/
│   ├── RateLimitCalculator.php # NEW - Pure functions
│   └── RateLimiter.php         # Orchestration (side effects)
├── Logging/
│   ├── LogEntryBuilder.php     # NEW - Immutable builder
│   └── ActivityLogger.php
├── Webhooks/
│   ├── PayloadBuilder.php      # NEW - Pure functions
│   ├── SignatureGenerator.php  # NEW - Pure functions
│   └── WebhookManager.php
├── Abilities/
│   ├── ExecutionPipeline.php   # NEW - Composable pipeline
│   └── AbilityExecutor.php     # Uses pipeline
└── Http/
    ├── ResponseFormatter.php   # Pure functions
    └── PrivacyRedactor.php     # NEW - Pure functions
```

---

## References

- Specification: `thoughts/shared/specs/wp-abilities-plugin-spec.md`
- External Research: `thoughts/shared/handoffs/build-20260120-wp-abilities-plugin/external-research.md`
- Plan: `thoughts/shared/plans/PLAN-wp-abilities-plugin.md`

---

## Status

| Stage | Status |
|-------|--------|
| External Research | COMPLETE |
| Discovery Interview | COMPLETE |
| Specification | COMPLETE |
| Specification Refinement | COMPLETE |
| Implementation Plan | COMPLETE |
| TDD + FP Revision | COMPLETE |
| Implementation | READY TO START |

---

*Handoff created by plan-agent on 2026-01-20*
*Revised for TDD + FP emphasis on 2026-01-20*
