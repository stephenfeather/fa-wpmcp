# Security Guide

This document outlines the security features, considerations, and future enhancements for the FA WPMCP plugin.

## Current Security Features

### 1. Capability Enforcement
**Status:** ✅ Implemented (v1.0-alpha-2)

Every ability has a required WordPress capability that is enforced at execution time.

```php
// AbilityExecutor.php:280-308
private function checkCapability(): ?Result {
    if (!current_user_can($ability->getRequiredCapability())) {
        return Result::failure(
            'insufficient_capability',
            sprintf('User lacks required capability: %s', $capability)
        );
    }
    return null;
}
```

**Protection:** Prevents privilege escalation attacks by ensuring users can only execute abilities they have permission for.

### 2. Option Access Protection
**Status:** ✅ Implemented (v1.0-alpha-2)

Settings abilities (GetOption, UpdateOption, DeleteOption) use an allowlist/blocklist approach to protect sensitive WordPress options.

```php
// OptionAccessPolicy.php
protected const DEFAULT_PROTECTED_OPTIONS = [
    'siteurl', 'home', 'admin_email', 'users_can_register',
    'default_role', 'active_plugins', 'template', 'stylesheet',
    'auth_key', 'secure_auth_key', 'logged_in_key', 'nonce_key',
    'auth_salt', 'secure_auth_salt', 'logged_in_salt', 'nonce_salt',
    'db_version', 'db_host', 'db_user', 'db_password'
];
```

**Protection:** Prevents unauthorized access to critical WordPress configuration and security tokens.

**Configuration:** Use filters to customize protection:
- `fa_wpmcp_allowed_options` - Define allowlist (if set, only these options accessible)
- `fa_wpmcp_protected_options` - Extend blocklist (merged with defaults)

### 3. Input Sanitization
**Status:** ✅ Implemented (v1.0-alpha-2)

Comment content and other user inputs are sanitized using WordPress native functions:

```php
// CreateComment.php:144-151
$comment_data = array(
    'comment_author'       => sanitize_text_field($input['author']),
    'comment_author_email' => sanitize_email($input['email']),
    'comment_content'      => wp_kses_post($input['content']),
    'comment_author_url'   => esc_url_raw($input['url']),
);
```

**Protection:** Prevents XSS attacks via comment injection and other user-generated content.

### 4. PII Redaction in Logs
**Status:** ✅ Implemented (v1.0-alpha-2)

Activity logs automatically redact sensitive data using comprehensive field pattern matching:

```php
// PrivacyRedactor.php
private const SENSITIVE_FIELDS = [
    // Authentication (14 fields)
    'password', 'user_pass', 'passwd', 'pwd',
    'token', 'api_key', 'apikey', 'access_token', 'refresh_token',
    'bearer_token', 'session_id', 'session_token', 'auth_key', 'auth_token',

    // Secrets (13 fields)
    'secret', 'client_secret', 'webhook_secret', 'private_key', 'public_key',
    'encryption_key', 'nonce_key', 'nonce_salt', 'auth_salt',
    'secure_auth_key', 'secure_auth_salt', 'logged_in_key', 'logged_in_salt',

    // PII (23 fields)
    'email', 'user_email', 'email_address',
    'phone', 'phone_number', 'telephone', 'mobile',
    'ssn', 'social_security', 'social_security_number',
    'credit_card', 'card_number', 'cvv', 'card_cvv',
    'address', 'street_address', 'postal_code', 'zip_code', 'zipcode',
    'ip_address', 'ip',
    'date_of_birth', 'dob', 'drivers_license', 'passport', 'tax_id'
];
```

**Protection:** Prevents PII exposure in activity logs for GDPR compliance.

### 5. Webhook Secret Protection
**Status:** ✅ Implemented (v1.0-alpha-2)

Webhook secrets are never displayed in admin forms after initial entry:

```php
// SettingsPage.php:510-521
$has_secret = !empty($settings['webhook_secret']);
$output .= '<input type="password" id="webhook_secret" name="webhook_secret" ';
$output .= 'placeholder="' . esc_attr($has_secret ? '••••••••••••••••' : 'Enter secret') . '" ';
$output .= 'class="regular-text" />';

// Preserve existing secret if field left blank
if (empty($webhook_secret) && !empty($current_settings['webhook_secret'])) {
    $settings['webhook_secret'] = $current_settings['webhook_secret'];
}
```

**Protection:** Prevents secret exposure in browser dev tools and HTML source.

### 6. CSRF Protection
**Status:** ✅ Implemented

All admin forms use WordPress nonces:
- `wp_nonce_field()` for form generation
- `wp_verify_nonce()` for submission validation

### 7. SQL Injection Prevention
**Status:** ✅ Implemented

All direct database queries use prepared statements:
```php
$wpdb->prepare("SELECT ...", $param1, $param2);
```

### 8. Rate Limiting
**Status:** ✅ Implemented

Default limits:
- 60 requests per minute (per user)
- 1000 requests per hour (per user)

Configurable via `fa_wpmcp_rate_limit_config` filter.

---

## Intentionally Unimplemented Abilities

The following abilities are marked as **out-of-scope** and will throw `RuntimeException` when called:

### Plugin/Theme Installation & Deletion
- `InstallPlugin` - Install WordPress plugins
- `DeletePlugin` - Delete WordPress plugins
- `InstallTheme` - Install WordPress themes
- `DeleteTheme` - Delete WordPress themes

**Rationale:** These operations require careful security review and implementation. See inline PHPDoc in each ability class for detailed security considerations before implementing.

**Status:** Documented with comprehensive security requirements in code comments.

---

## Future Security Enhancements

### 1. IP Address Anonymization (GDPR Compliance)
**Status:** 📋 Planned
**Priority:** LOW
**Target Release:** v1.1.0

**Description:** Add admin option to anonymize IP addresses in activity logs by masking the last octet.

**Example:**
```
Before: 192.168.1.45
After:  192.168.1.0
```

**Implementation Details:**
- Add setting in WordPress admin: "Anonymize IP addresses in logs"
- Default: **disabled** (full IP logging)
- When enabled, modify `ActivityLogger.php:64` to mask last octet before storage
- Apply to all log entries (activity logs, rate limit tracking)
- Configuration option via filter: `fa_wpmcp_anonymize_ips`

**Privacy Impact:**
- Reduces PII in logs for GDPR compliance
- Trade-off: Less precise debugging for IP-based issues
- Recommended for EU installations or privacy-sensitive environments

**Configuration Example:**
```php
// Enable IP anonymization via filter
add_filter('fa_wpmcp_anonymize_ips', '__return_true');

// Or via admin settings UI (planned)
Settings → FA WPMCP → Privacy → [✓] Anonymize IP addresses in logs
```

### 2. Option Name Validation
**Status:** 📋 Planned
**Priority:** LOW
**Target Release:** v1.1.0

**Description:** Validate option names for format and length in Settings abilities.

**Implementation:**
```php
$option_name = sanitize_key($input['option_name']);
if (strlen($option_name) > 191) {
    throw new \RuntimeException('Option name exceeds maximum length.');
}
```

### 3. Destructive Operation Annotations
**Status:** 📋 Planned
**Priority:** LOW
**Target Release:** v1.1.0

**Description:** Override `getAnnotations()` in destructive abilities to set `destructive=true` and `idempotent=false`.

**Affects:**
- `DeletePlugin`
- `DeleteTheme`
- `DeleteOption`
- `DeletePost`
- `DeleteMedia`
- `DeleteComment`
- `DeleteUser`

**Purpose:** Allows AI agents and UI to warn users about destructive operations.

### 4. Content Security Policy Headers
**Status:** 🔮 Future Consideration
**Priority:** LOW

Add CSP headers to admin pages for additional XSS protection.

### 5. Per-IP Rate Limiting
**Status:** 🔮 Future Consideration
**Priority:** LOW

Currently rate limiting is per-user only. Consider adding per-IP limits to prevent abuse from unauthenticated endpoints.

### 6. Audit Log Rotation
**Status:** 🔮 Future Consideration
**Priority:** LOW

Implement automatic archival/deletion of old activity logs to prevent database bloat.

---

## Security Audit History

### v1.0-alpha-2 Security Audit (2026-01-22)
**Tool:** aegis agent
**Report:** `.claude/cache/agents/aegis/output-20260122-security-audit-v1alpha2.md`

**Findings:**
- 0 CRITICAL
- 2 HIGH (both fixed in commit 7d3314b)
- 4 MEDIUM (all fixed in commit 7057338)
- 3 LOW (documented as future enhancements)

**Current Risk Level:** LOW

**Completed Fixes:**
1. ✅ Capability enforcement in AbilityExecutor
2. ✅ Option access protection via OptionAccessPolicy
3. ✅ Comment content sanitization
4. ✅ PII redaction expanded to 50+ fields
5. ✅ Webhook secret hidden in admin forms
6. ✅ Stub implementations throw exceptions

**Remaining Items:**
- LOW: Option name validation
- LOW: Destructive annotations
- LOW: IP address anonymization (admin option)

---

## Reporting Security Issues

If you discover a security vulnerability in FA WPMCP, please email security@example.com (DO NOT open a public issue).

We will respond within 48 hours and work with you to address the issue.

---

## Security Best Practices

When using FA WPMCP:

1. **Least Privilege:** Only grant `manage_options` capability to trusted users
2. **Rate Limits:** Adjust rate limits based on your usage patterns
3. **Webhook Secrets:** Use strong, randomly-generated webhook secrets (32+ characters)
4. **Option Protection:** Review and customize protected options list for your environment
5. **Activity Logs:** Regularly review activity logs for suspicious behavior
6. **Updates:** Keep the plugin updated to receive security patches
7. **GDPR Compliance:** Enable IP anonymization if required by your jurisdiction (when available in v1.1.0)

---

## Security-Related Filters

```php
// Customize protected options
add_filter('fa_wpmcp_protected_options', function($protected) {
    $protected[] = 'my_custom_secret_option';
    return $protected;
});

// Define option allowlist (stricter than blocklist)
add_filter('fa_wpmcp_allowed_options', function() {
    return ['blogname', 'blogdescription', 'posts_per_page'];
});

// Adjust rate limits
add_filter('fa_wpmcp_rate_limit_config', function() {
    return [
        'requests_per_minute' => 30,
        'requests_per_hour' => 500
    ];
});

// Enable IP anonymization (future)
add_filter('fa_wpmcp_anonymize_ips', '__return_true');
```

---

## References

- [WordPress Security Best Practices](https://developer.wordpress.org/apis/security/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [GDPR Compliance](https://gdpr.eu/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
