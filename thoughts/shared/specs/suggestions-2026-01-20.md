# Missing/Under-Specified Areas for WP Abilities Plugin Spec

## Lifecycle & Data Management
- Define activation/deactivation/uninstall behavior, including database schema creation, migrations, and data cleanup policy.
- Specify upgrade path for schema changes (e.g., versioned migrations, `dbDelta`, or custom migrator).

## Authentication & Identity Model
- Clarify how AI agents map to WordPress users (dedicated service account vs. per-user tokens).
- Specify required roles/capabilities per ability and where those are enforced.
- Document credential rotation, revocation, and auditing for Application Passwords.

## Auth Transport Details
- Define REST auth flow, nonce use (if any), and how MCP adapter authenticates.
- Document expected headers and error responses for auth failures.

## Error/Response Contract
- Standardize error schema across abilities (codes, messages, HTTP statuses, retry hints).
- Document how validation errors are returned and how partial failures are handled.

## Validation & Sanitization
- Specify JSON Schema validation library/strategy and server-side sanitization functions per field.
- Define size limits for payloads (input/output), and maximum field lengths.

## Webhook Security & Privacy
- Add webhook signing (HMAC) and replay protection guidance.
- Define secret storage and rotation for webhook signing keys.
- Specify PII redaction rules for logs and webhook payloads.

## Logging, Retention, and Compliance
- Define default retention, export format, and privacy controls for activity logs.
- Clarify whether logs are searchable/exportable by non-admins and what capabilities are required.

## Background Processing & Reliability
- Specify queue storage choice, worker mechanism (WP Cron or Action Scheduler), and retry strategy.
- Define failure alerting/visibility for webhook delivery.

## Admin UI & Configuration UX
- Detail settings flows, permissions for access to the admin UI, and safe defaults.
- Provide import/export of configuration for portability.

## Testing & Compatibility
- Add integration/e2e test expectations and the WordPress version matrix.
- Define how Abilities API and MCP adapter are mocked/stubbed in tests.

## Multisite & Compatibility Stance
- Explicitly state support or non-support for multisite and network admin behavior.
- Clarify how settings and logs behave per site vs. network.

## Observability & Performance
- Add correlation IDs for tracing across logs/webhooks.
- Specify query limits, pagination defaults, caching, and performance constraints.
- Define rate limit bypass prevention and cache invalidation rules.

## Security Hardening
- Add CSRF/REST permission boundary details.
- Define allowed file types and sizes for media-related abilities (future phase).
- Document strict HTTPS enforcement behavior and admin override policy (if any).
