# REST API Endpoints Reference

Complete reference for all REST API endpoints created by the FA-WPMCP plugin.

## Overview

The plugin creates **87 REST API endpoints** organized into two main systems:

1. **WordPress Abilities API** (86 endpoints) - Direct WordPress capability access
2. **MCP Adapter** (1 endpoint) - AI agent integration via Model Context Protocol

## Base URLs

| System | Base URL | Purpose |
|--------|----------|---------|
| Abilities API | `/wp-json/wp-abilities/v1/` | WordPress operations |
| MCP Adapter | `/wp-json/mcp/` | AI agent integration |

---

## WordPress Abilities API Endpoints

### Discovery & Metadata

#### List All Abilities
```
GET /wp-json/wp-abilities/v1/abilities
```

Returns all registered WordPress abilities with metadata, schemas, and capabilities.

**Query Parameters:**
- `category` (optional) - Filter by ability category (e.g., `posts-pages`, `users`, `settings`)
- `per_page` (optional) - Number of results per page
- `page` (optional) - Page number for pagination

**Response:**
```json
[
  {
    "name": "fa-wpmcp/get-post",
    "label": "Get Post",
    "description": "Retrieve a single WordPress post by ID",
    "category": "posts-pages",
    "input_schema": { ... },
    "output_schema": { ... },
    "required_capability": "read"
  }
]
```

#### List Ability Categories
```
GET /wp-json/wp-abilities/v1/categories
```

Returns all ability categories with counts.

**Response:**
```json
[
  {
    "slug": "posts-pages",
    "name": "Posts & Pages",
    "count": 4
  }
]
```

#### Get Single Ability Info
```
GET /wp-json/wp-abilities/v1/abilities/{ability-name}
```

Returns detailed information about a specific ability including schema and annotations.

**Example:**
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-post
```

#### Execute Ability
```
POST /wp-json/wp-abilities/v1/abilities/{ability-name}/run
```

Executes a specific ability with provided input.

**Request Body:**
```json
{
  "id": 123
}
```

**Response:**
```json
{
  "id": 123,
  "title": "Sample Post",
  "content": "Post content here",
  "status": "publish"
}
```

---

## Ability Endpoints by Category

### Posts & Pages (4 abilities)

All post abilities support any WordPress post type via the `post_type` parameter (posts, pages, custom post types, WooCommerce products, etc.).

#### Create Post
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/create-post/run
```

**Capability Required:** `edit_posts`

**Input:**
```json
{
  "post_type": "post",
  "title": "New Post Title",
  "content": "Post content",
  "status": "draft",
  "author_id": 1
}
```

#### Get Post
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-post/run?id=123
```

**Capability Required:** `read`

**Input:**
```json
{
  "id": 123,
  "post_type": "post"
}
```

#### List Posts
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-posts/run
```

**Capability Required:** `read`

**Input:**
```json
{
  "post_type": "post",
  "posts_per_page": 10,
  "page": 1,
  "post_status": "publish",
  "orderby": "date",
  "order": "DESC"
}
```

#### Update Post
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/update-post/run
```

**Capability Required:** `edit_posts`

**Input:**
```json
{
  "id": 123,
  "post_type": "post",
  "title": "Updated Title",
  "content": "Updated content",
  "status": "publish"
}
```

---

### Comments (4 abilities)

#### Create Comment
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/create-comment/run
```

**Capability Required:** `edit_posts`

#### Get Comment
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-comment/run?comment_id=456
```

**Capability Required:** `moderate_comments`

#### List Comments
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-comments/run
```

**Capability Required:** `moderate_comments`

#### Update Comment
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/update-comment/run
```

**Capability Required:** `moderate_comments`

---

### Media Library (4 abilities)

#### Get Media
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-media/run?id=789
```

**Capability Required:** `upload_files`

#### List Media
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-media/run
```

**Capability Required:** `upload_files`

**Input:**
```json
{
  "posts_per_page": 20,
  "page": 1,
  "mime_type": "image/jpeg"
}
```

#### Update Media
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/update-media/run
```

**Capability Required:** `upload_files`

#### Upload Media
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/upload-media/run
```

**Capability Required:** `upload_files`

**Input (base64):**
```json
{
  "file": "data:image/png;base64,iVBORw0KG...",
  "title": "Image Title",
  "alt_text": "Image description",
  "caption": "Image caption"
}
```

**Input (URL):**
```json
{
  "url": "https://example.com/image.jpg",
  "title": "Downloaded Image"
}
```

---

### Taxonomies (4 abilities)

Works with any WordPress taxonomy: categories, tags, custom taxonomies.

#### Create Term
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/create-term/run
```

**Capability Required:** `manage_categories`

**Input:**
```json
{
  "taxonomy": "category",
  "name": "New Category",
  "slug": "new-category",
  "description": "Category description",
  "parent": 0
}
```

#### Get Term
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-term/run?term_id=42&taxonomy=category
```

**Capability Required:** `read`

#### List Terms
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-terms/run?taxonomy=category
```

**Capability Required:** `read`

**Input:**
```json
{
  "taxonomy": "category",
  "hide_empty": false,
  "number": 50,
  "orderby": "name",
  "order": "ASC"
}
```

#### Update Term
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/update-term/run
```

**Capability Required:** `manage_categories`

---

### Users (4 abilities)

#### Create User
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/create-user/run
```

**Capability Required:** `create_users`

**Input:**
```json
{
  "username": "newuser",
  "email": "user@example.com",
  "password": "secure_password",
  "role": "subscriber",
  "first_name": "John",
  "last_name": "Doe"
}
```

#### Get User
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-user/run?user_id=5
```

**Capability Required:** `list_users`

**Lookup Options:**
```json
{
  "user_id": 5
}
```
OR
```json
{
  "username": "john"
}
```
OR
```json
{
  "email": "user@example.com"
}
```

#### List Users
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-users/run
```

**Capability Required:** `list_users`

**Input:**
```json
{
  "number": 20,
  "offset": 0,
  "role": "subscriber",
  "orderby": "registered",
  "order": "DESC",
  "search": "john"
}
```

#### Update User
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/update-user/run
```

**Capability Required:** `edit_users`

---

### Settings (4 abilities)

WordPress options management with protection for critical system options.

#### Get Option
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-option/run?option_name=blogname
```

**Capability Required:** `manage_options`

#### List Options
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-options/run
```

**Capability Required:** `manage_options`

**Input:**
```json
{
  "search": "site",
  "limit": 50,
  "offset": 0
}
```

#### Update Option
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/update-option/run
```

**Capability Required:** `manage_options`

**Input:**
```json
{
  "option_name": "blogdescription",
  "value": "My new site description",
  "autoload": true
}
```

#### Delete Option
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/delete-option/run
```

**Capability Required:** `manage_options`

**Input:**
```json
{
  "option_name": "custom_option"
}
```

---

### Plugins (7 abilities)

WordPress plugin lifecycle management following wp-cli conventions.

#### Activate Plugin
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/activate-plugin/run
```

**Capability Required:** `activate_plugins`

**Input:**
```json
{
  "plugin": "akismet/akismet.php"
}
```

#### Deactivate Plugin
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/deactivate-plugin/run
```

**Capability Required:** `activate_plugins`

#### Delete Plugin
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/delete-plugin/run
```

**Capability Required:** `delete_plugins`

#### Get Plugin
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-plugin/run?plugin=akismet/akismet.php
```

**Capability Required:** `activate_plugins`

#### Install Plugin
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/install-plugin/run
```

**Capability Required:** `install_plugins`

**Input:**
```json
{
  "slug": "akismet"
}
```

#### List Plugins
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-plugins/run
```

**Capability Required:** `activate_plugins`

**Input:**
```json
{
  "status": "active"
}
```

**Status Options:** `active`, `inactive`, `all`

#### Update Plugin
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/update-plugin/run
```

**Capability Required:** `update_plugins`

---

### Themes (7 abilities)

WordPress theme lifecycle management following wp-cli conventions.

#### Activate Theme
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/activate-theme/run
```

**Capability Required:** `switch_themes`

**Input:**
```json
{
  "stylesheet": "twentytwentyfour"
}
```

#### Delete Theme
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/delete-theme/run
```

**Capability Required:** `delete_themes`

#### Get Theme
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-theme/run?stylesheet=twentytwentyfour
```

**Capability Required:** `switch_themes`

#### Install Theme
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/install-theme/run
```

**Capability Required:** `install_themes`

**Input:**
```json
{
  "slug": "twentytwentyfour"
}
```

#### List Themes
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-themes/run
```

**Capability Required:** `switch_themes`

#### Status Theme
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/status-theme/run?stylesheet=twentytwentyfour
```

**Capability Required:** `switch_themes`

#### Update Theme
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/update-theme/run
```

**Capability Required:** `update_themes`

---

### Privacy & GDPR (4 abilities)

Personal data management for GDPR compliance.

#### Create Export Request
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/create-export-request/run
```

**Capability Required:** `manage_privacy_options`

**Input:**
```json
{
  "email": "user@example.com"
}
```

Creates a personal data export request. WordPress will email the user for confirmation.

#### Create Erasure Request
```
POST /wp-json/wp-abilities/v1/abilities/fa-wpmcp/create-erasure-request/run
```

**Capability Required:** `manage_privacy_options`

**Input:**
```json
{
  "email": "user@example.com"
}
```

Creates a personal data erasure request. WordPress will email the user for confirmation.

#### Get Privacy Request
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-privacy-request/run?request_id=123
```

**Capability Required:** `manage_privacy_options`

#### List Privacy Requests
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-privacy-requests/run
```

**Capability Required:** `manage_privacy_options`

**Input:**
```json
{
  "type": "export_personal_data",
  "status": "request-pending",
  "number": 50,
  "page": 1
}
```

**Request Types:**
- `export_personal_data` - Data export requests
- `remove_personal_data` - Data erasure requests

**Request Status:**
- `request-pending` - Awaiting user confirmation
- `request-confirmed` - User confirmed request
- `request-completed` - Request processed
- `request-failed` - Request failed

---

## MCP Adapter Endpoint

### MCP Server (AI Agent Integration)
```
POST /wp-json/mcp/mcp-adapter-default-server
```

**Protocol:** JSON-RPC 2.0 (Model Context Protocol)

**Purpose:** Exposes all WordPress abilities as MCP tools for AI agents.

**Usage:** Connect AI assistants (Claude Desktop, GPT, etc.) via:
- STDIO transport (local): `wp mcp-adapter serve --server=mcp-adapter-default-server`
- HTTP transport (remote): Use proxy like `@automattic/mcp-wordpress-remote`

**Example Request:**
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/list",
  "params": {}
}
```

**Example Response:**
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "tools": [
      {
        "name": "fa-wpmcp-get-post",
        "description": "Retrieve a single WordPress post by ID",
        "inputSchema": { ... }
      }
    ]
  }
}
```

See [MCP Documentation](MCP_DOCUMENTATION.md) for complete integration guide.

---

## Authentication

All endpoints require WordPress authentication:

### Cookie Authentication
For browser-based requests, use standard WordPress session cookies.

### Application Passwords
For API clients, use WordPress Application Passwords:

1. Navigate to **Users → Profile** in WordPress admin
2. Scroll to **Application Passwords**
3. Create a new application password
4. Use with HTTP Basic Auth:

```bash
curl -X POST https://example.com/wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-post/run \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{"id": 123}'
```

### OAuth (Future)
OAuth 2.0 support is planned for future releases.

---

## Rate Limiting

All endpoints are subject to rate limiting:

| Window | Default Limit |
|--------|---------------|
| Per Minute | 60 requests |
| Per Hour | 1000 requests |
| Per Day | 10000 requests |

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1642867200
```

**429 Response:**
```json
{
  "code": "rate_limit_exceeded",
  "message": "Rate limit exceeded. Try again in 30 seconds.",
  "data": {
    "status": 429,
    "retry_after": 30
  }
}
```

Configure rate limits via filter:
```php
add_filter('fa_wpmcp_rate_limit_config', function($config) {
    $config['default']['per_minute'] = 120;
    return $config;
});
```

---

## Error Responses

All endpoints follow WordPress REST API error format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400,
    "params": {
      "field": "Additional error details"
    }
  }
}
```

### Common Error Codes

| Code | Status | Description |
|------|--------|-------------|
| `rest_forbidden` | 403 | Insufficient permissions |
| `rest_ability_not_found` | 404 | Ability does not exist |
| `rate_limit_exceeded` | 429 | Too many requests |
| `invalid_input` | 400 | Input validation failed |
| `execution_failed` | 500 | Ability execution error |

---

## Pagination

List endpoints support pagination:

**Query Parameters:**
- `per_page` - Items per page (default: 20, max: 100)
- `page` - Page number (default: 1)

**Response Headers:**
```
X-WP-Total: 250
X-WP-TotalPages: 13
```

**Response:**
```json
[
  { "id": 1, "title": "Item 1" },
  { "id": 2, "title": "Item 2" }
]
```

---

## Filtering & Searching

Many endpoints support filtering:

### Posts
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-posts/run?post_status=publish&author=5
```

### Comments
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-comments/run?status=approved&post=123
```

### Users
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-users/run?role=subscriber&search=john
```

### Media
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-media/run?mime_type=image/jpeg
```

### Options
```
GET /wp-json/wp-abilities/v1/abilities/fa-wpmcp/list-options/run?search=site
```

---

## Testing Endpoints

### Using cURL

```bash
# List all abilities
curl https://example.com/wp-json/wp-abilities/v1/abilities

# Get post with authentication
curl -X POST https://example.com/wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-post/run \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{"id": 123}'

# Create post
curl -X POST https://example.com/wp-json/wp-abilities/v1/abilities/fa-wpmcp/create-post/run \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{"title": "New Post", "content": "Post content", "status": "draft"}'
```

### Using WP-CLI

```bash
# List all abilities via MCP
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | \
  wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin

# Execute ability via MCP
echo '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"fa-wpmcp-get-post","arguments":{"id":123}}}' | \
  wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin
```

### Using JavaScript

```javascript
const response = await fetch('/wp-json/wp-abilities/v1/abilities/fa-wpmcp/get-post/run', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Basic ' + btoa('username:app-password')
  },
  body: JSON.stringify({ id: 123 })
});

const post = await response.json();
console.log(post.title);
```

---

## Webhooks

The plugin fires webhooks for ability execution events:

### Webhook Events

| Event | Trigger |
|-------|---------|
| `ability.before_execute` | Before ability executes |
| `ability.after_execute` | After successful execution |
| `ability.failed` | Execution failed |

### Webhook Payload

```json
{
  "event": "ability.after_execute",
  "timestamp": "2026-01-22T12:00:00+00:00",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "ability": {
    "name": "fa-wpmcp/get-post",
    "category": "posts-pages"
  },
  "input": {
    "id": 123
  },
  "output": {
    "id": 123,
    "title": "Sample Post"
  },
  "user_id": 5,
  "ip_address": "203.0.113.1",
  "execution_time_ms": 45
}
```

### Webhook Signature Verification

All webhooks include HMAC-SHA256 signature in headers:

```
X-Webhook-Signature: a3f2b8c...
```

Verify signature:
```php
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');
$secret = get_option('fa_wpmcp_webhooks')['secret'] ?? '';

$expected = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

---

## Activity Logging

All ability executions are logged to the database:

**Database Table:** `{$wpdb->prefix}fa_wpmcp_activity_logs`

**Log Fields:**
- `correlation_id` - Unique request identifier (UUID)
- `ability_name` - Executed ability
- `user_id` - WordPress user ID
- `ip_address` - Client IP address
- `input` - Sanitized input data (PII redacted)
- `output` - Sanitized output data (PII redacted)
- `execution_time_ms` - Execution duration
- `status` - `success` or `failed`
- `error_message` - Error details (if failed)
- `created_at` - Timestamp

Query logs programmatically:
```php
global $wpdb;
$repo = new \FAWpmcp\Logging\LogRepository($wpdb);

// Get recent logs
$logs = $repo->findRecent(50);

// Find by correlation ID
$log = $repo->findByCorrelationId('550e8400-e29b-41d4-a716-446655440000');

// Find by ability
$logs = $repo->findByAbility('fa-wpmcp/get-post', 20);
```

---

## Performance Considerations

### Caching

The plugin does not implement response caching by default. Consider:

- **Browser caching:** Set appropriate `Cache-Control` headers for read-only endpoints
- **Object caching:** Use WordPress object cache (Redis, Memcached)
- **Page caching:** Cache rendered output for list endpoints

### Optimization Tips

1. **Use pagination:** Always paginate list endpoints
2. **Filter early:** Use query parameters instead of fetching all data
3. **Limit fields:** Request only needed data (future feature)
4. **Batch requests:** Group related requests when possible
5. **Monitor rate limits:** Track `X-RateLimit-Remaining` header

---

## Version History

| Version | Abilities | Endpoints | Changes |
|---------|-----------|-----------|---------|
| 1.0.0 | 42 | 87 | Initial release with full CRUD support |

---

## Related Documentation

- [Quick Start Guide](QUICK_START.md) - Get started in 5 minutes
- [MCP Documentation](MCP_DOCUMENTATION.md) - AI agent integration
- [Configuration Guide](CONFIGURATION.md) - Detailed configuration options
- [Security Guide](SECURITY.md) - Security best practices
- [Architecture Guide](ARCHITECTURE.md) - Internal architecture

---

## Support

For issues, questions, or contributions:

- **GitHub Issues:** https://github.com/featherart/fa-wpmcp/issues
- **Documentation:** https://github.com/featherart/fa-wpmcp/tree/main/docs
- **Email:** stephen@feather.us

---

**Last Updated:** 2026-01-22
**Plugin Version:** 1.0.0
**Total Endpoints:** 87
