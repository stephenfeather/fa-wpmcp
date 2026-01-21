# Specification Fixes Applied

**Date:** 2026-01-20
**Session:** build-20260120-wp-abilities-plugin

## Issues Resolved

All 9 inconsistencies from `suggestions-2026-01-20-0909.md` have been fixed:

### 1. ✅ MCP Route Mismatch
**Issue:** Auth flow showed `/wp-json/fa-wpmcp/v1/mcp` but server config used `/wp-json/fa-wpmcp/mcp`
**Fix:** Standardized to versioned route `/wp-json/fa-wpmcp/v1/mcp` throughout spec
**Files:** Lines 829, 1766

### 2. ✅ MCP Server ID Inconsistency
**Issue:** Server ID alternated between `fa-wpmcp` and `fa-wpmcp-server`
**Fix:** Standardized to `fa-wpmcp` (matches plugin slug and namespace)
**Files:** Lines 1764, 1796

### 3. ✅ Activity Log Schema Missing correlation_id
**Issue:** Table schema didn't include `correlation_id` column required by later sections
**Fix:** Added `correlation_id VARCHAR(36) NOT NULL` with index
**Files:** Line 265

### 4. ✅ Permission Model Conflict
**Issue:** Permission flow mentioned `manage_options` while identity model defined `fa_wpmcp_*` capabilities
**Fix:** Clarified multi-tier capability checking with specific `fa_wpmcp_read_abilities`, `fa_wpmcp_write_abilities`, `fa_wpmcp_delete_abilities`
**Files:** Lines 217-223

### 5. ✅ Error Taxonomy Mismatch
**Issue:** Error taxonomy defined custom codes but examples returned `rest_forbidden`
**Fix:** Updated permission callback examples to use standardized codes (`authentication_required`, `insufficient_permissions`)
**Files:** Lines 849, 853, 864-868

### 6. ✅ Rate Limit Key Missing IP
**Issue:** Spec said "per user + IP" but transient key only used user ID
**Fix:** Added IP hash to transient key: `fa_wpmcp_ratelimit_{user_id}_{ip_hash}_{ability}_{window}`
**Files:** Lines 324-335

### 7. ✅ Action Scheduler Dependency Missing
**Issue:** Spec recommended Action Scheduler but `composer.json` didn't include it
**Fix:** Added `woocommerce/action-scheduler: ^3.7` to require section, plus mockery/brain-monkey to require-dev
**Files:** Lines 579, 586-587

### 8. ✅ WordPress Version Matrix Contradiction
**Issue:** Minimum version was 6.9 but "Tested Up To" was 6.5
**Fix:** Updated to "Tested Up To: WordPress 6.9+" with future 7.0 marked as "will test when released"
**Files:** Lines 1500-1511

### 9. ✅ Application Password Audit Logging Unspecified
**Issue:** Spec said passwords are logged but no mechanism described
**Fix:** Added detailed implementation using `application_password_did_authenticate` hook with code example
**Files:** Lines 838-870

## Open Questions Resolved

### Q1: Should MCP route be versioned?
**Answer:** Yes, versioned (`v1/mcp`) to allow future breaking changes

### Q2: Which MCP server ID is canonical?
**Answer:** `fa-wpmcp` - matches plugin slug and WordPress naming conventions

## Specification Status

**Status:** ✅ Complete and consistent
**Version:** 1.0 (refined)
**Ready for:** Implementation planning

All architectural decisions are documented, inconsistencies resolved, and technical specifications are implementation-ready.
