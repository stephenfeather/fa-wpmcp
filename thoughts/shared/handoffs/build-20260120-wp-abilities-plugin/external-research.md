# Research Report: WordPress Abilities API and MCP Adapter
Generated: 2026-01-20

## Summary

The WordPress Abilities API (introduced in WordPress 6.9) provides a standardized system for plugins, themes, and core to register and expose capabilities in a machine-readable format. The MCP Adapter bridges this API to the Model Context Protocol (MCP), enabling AI assistants like Claude Desktop, VS Code, and Cursor to discover and invoke WordPress functionality programmatically. Together, these tools create a foundation for AI-powered WordPress interactions.

## Questions Answered

### Q1: How do these two projects work together?
**Answer:** The Abilities API creates a central registry of WordPress capabilities (called "Abilities"), while the MCP Adapter translates those registered Abilities into MCP-compliant tools, resources, and prompts that AI agents can discover and invoke. The adapter acts as a bridge - it reads from the Abilities registry and exposes that functionality via MCP protocol endpoints.
**Source:** [GitHub - WordPress/mcp-adapter](https://github.com/WordPress/mcp-adapter)
**Confidence:** High

### Q2: What's the format/structure for defining an Ability?
**Answer:** An Ability is defined using `wp_register_ability()` with the following structure:
- **name**: Namespaced string (e.g., `my-plugin/my-ability`)
- **label**: Human-readable name
- **description**: Detailed description (crucial for AI understanding)
- **category**: Registered category slug
- **input_schema**: JSON Schema for input validation (optional)
- **output_schema**: JSON Schema for output validation (required)
- **execute_callback**: PHP function to execute
- **permission_callback**: Function for access control
- **meta**: Additional metadata including annotations

**Source:** [Abilities API - Developer.WordPress.org](https://developer.wordpress.org/apis/abilities-api/)
**Confidence:** High

### Q3: How do you register custom Abilities?
**Answer:** Abilities must be registered during the `wp_abilities_api_init` action hook. Categories must be registered first during `wp_abilities_api_categories_init`. Registration outside these hooks triggers `_doing_it_wrong()` and fails.
**Source:** [GitHub - abilities-api PHP API docs](https://github.com/WordPress/abilities-api/blob/trunk/docs/php-api.md)
**Confidence:** High

### Q4: How does an AI agent consume these Abilities?
**Answer:** AI agents connect via MCP protocol (HTTP or STDIO transport). They authenticate using WordPress Application Passwords, then discover available tools via MCP's discovery protocol. The MCP Adapter converts Abilities to MCP tools that agents can invoke, with permission checks enforced per-request.
**Source:** [How to Create an MCP Server - WS Form](https://wsform.com/how-to-create-an-mcp-server-in-wordpress-with-the-abilities-api-and-mcp-adapter/)
**Confidence:** High

### Q5: Are there example Abilities we can learn from?
**Answer:** WordPress 6.9 includes core abilities like:
- `core/get-site-info` - Returns site configuration
- `core/get-user-info` - Returns user profile details
- `core/get-environment-info` - Returns environment details

Additionally, WooCommerce and WS Form have published example implementations.
**Source:** [WP-CLI ability command](https://developer.wordpress.org/cli/commands/ability/)
**Confidence:** High

---

## Detailed Findings

### Finding 1: Abilities API Architecture

**Source:** [Introducing the WordPress Abilities API](https://developer.wordpress.org/news/2025/11/introducing-the-wordpress-abilities-api/)

**Key Points:**
- Introduced in WordPress 6.9 as part of "AI Building Blocks for WordPress" initiative
- Provides unified registry of functionality that can be discovered, validated, and executed
- Uses JSON Schema Version 4 subset for input/output validation
- Supports REST API endpoints under `wp-abilities/v1` namespace
- Abilities are not exposed via REST by default (`show_in_rest: false`)

**Core REST Endpoints:**
```
GET  /wp-abilities/v1/categories         - List all categories
GET  /wp-abilities/v1/categories/{slug}  - Single category
GET  /wp-abilities/v1/abilities          - List all abilities
GET  /wp-abilities/v1/abilities/{name}   - Single ability
GET|POST|DELETE /wp-abilities/v1/abilities/{name}/run - Execute ability
```

### Finding 2: Ability Registration Code Example

**Source:** [Abilities API in WordPress 6.9](https://make.wordpress.org/core/2025/11/10/abilities-api-in-wordpress-6-9/)

**Complete Registration Example:**

```php
<?php
/**
 * Plugin Name: My Abilities Plugin
 * Description: Example Abilities API implementation
 */

// Step 1: Register Category
add_action( 'wp_abilities_api_categories_init', 'my_plugin_register_category' );

function my_plugin_register_category() {
    wp_register_ability_category( 'site-information', [
        'label'       => __( 'Site Information', 'my-plugin' ),
        'description' => __( 'Abilities that provide information about the WordPress site.', 'my-plugin' ),
    ] );
}

// Step 2: Register Ability
add_action( 'wp_abilities_api_init', 'my_plugin_register_ability' );

function my_plugin_register_ability() {
    wp_register_ability( 'my-plugin/site-info', [
        'category'            => 'site-information',
        'label'               => __( 'Site Info', 'my-plugin' ),
        'description'         => __( 'Returns information about this WordPress site. Use this to get the site name, URL, and other configuration details.', 'my-plugin' ),
        'execute_callback'    => 'my_plugin_get_siteinfo',
        'permission_callback' => function( $input ) {
            return current_user_can( 'manage_options' );
        },
        'input_schema'        => [
            'type'       => 'object',
            'properties' => [
                'fields' => [
                    'type'        => 'array',
                    'description' => 'Specific fields to retrieve (optional)',
                    'items'       => [
                        'type' => 'string',
                        'enum' => [ 'name', 'url', 'description', 'admin_email' ],
                    ],
                ],
            ],
        ],
        'output_schema'       => [
            'type'       => 'object',
            'properties' => [
                'site_name' => [
                    'type'        => 'string',
                    'description' => 'The name of the WordPress site',
                ],
                'site_url' => [
                    'type'        => 'string',
                    'description' => 'The URL of the WordPress site',
                ],
                'site_description' => [
                    'type'        => 'string',
                    'description' => 'The tagline/description of the site',
                ],
                'admin_email' => [
                    'type'        => 'string',
                    'description' => 'The administrator email address',
                ],
            ],
        ],
        'meta' => [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
                'instructions' => 'Use this to learn about the WordPress site configuration.',
            ],
        ],
    ] );
}

// Step 3: Execute Callback
function my_plugin_get_siteinfo( $input ) {
    $fields = $input['fields'] ?? [ 'name', 'url', 'description', 'admin_email' ];
    
    $data = [];
    
    if ( in_array( 'name', $fields, true ) ) {
        $data['site_name'] = get_bloginfo( 'name' );
    }
    if ( in_array( 'url', $fields, true ) ) {
        $data['site_url'] = get_bloginfo( 'url' );
    }
    if ( in_array( 'description', $fields, true ) ) {
        $data['site_description'] = get_bloginfo( 'description' );
    }
    if ( in_array( 'admin_email', $fields, true ) ) {
        $data['admin_email'] = get_bloginfo( 'admin_email' );
    }
    
    return $data;
}
```

### Finding 3: MCP Adapter Integration

**Source:** [MCP Adapter v0.3.0 Release](https://make.wordpress.org/ai/2025/11/24/release-announcement-mcp-adapter-v0-3-0/)

**Key Points:**
- Bridges Abilities API to Model Context Protocol
- Supports HTTP Transport (MCP 2025-06-18 spec) and STDIO Transport
- Converts Abilities into MCP tools, resources, and prompts
- Respects permission callbacks from Abilities API
- Can run multiple MCP servers with different configurations

**Server Creation Example:**

```php
<?php
use WP\MCP\McpAdapter;
use WP\MCP\Transport\HttpTransport;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;

add_action( 'plugins_loaded', function() {
    $adapter = McpAdapter::get_instance();
    
    // Create MCP server
    $adapter->create_server(
        'my-mcp-server',           // Server ID
        'my-plugin',               // Namespace
        'mcp',                     // Route
        'My MCP Server',           // Name
        'Exposes my plugin abilities to AI agents', // Description
        '1.0.0',                   // Version
        [ HttpTransport::class ],  // Transport classes
        ErrorLogMcpErrorHandler::class
    );
    
    // Register abilities as MCP tools
    $adapter->register_tools( 'my-mcp-server', [
        'my-plugin/site-info',
        'my-plugin/create-content',
        'my-plugin/update-settings',
    ] );
    
    // Optionally register as resources (read-only data)
    $adapter->register_resources( 'my-mcp-server', [
        'my-plugin/get-posts',
        'my-plugin/get-categories',
    ] );
} );
```

### Finding 4: Meta Annotations for AI Behavior

**Source:** [REST API endpoints documentation](https://developer.wordpress.org/apis/abilities-api/rest-api-endpoints/)

**Annotations Control HTTP Methods and AI Hints:**

| Annotation | Type | Default | Description |
|------------|------|---------|-------------|
| `readonly` | boolean | false | If true, uses GET; ability only reads data |
| `destructive` | boolean | true | If true, uses DELETE; may delete data |
| `idempotent` | boolean | false | If true, repeated calls have same effect |
| `instructions` | string | '' | Guidance for AI on when/how to use |

**HTTP Method Mapping:**
- `readonly: true` → GET method
- `readonly: false` (with input) → POST method
- `destructive: true` → DELETE method

```php
'meta' => [
    'show_in_rest' => true,
    'annotations'  => [
        'readonly'     => true,   // Safe to call repeatedly
        'destructive'  => false,  // Won't delete anything
        'idempotent'   => true,   // Same input = same output
        'instructions' => 'Call this to get current site settings before making changes.',
    ],
],
```

### Finding 5: Authentication & Security

**Source:** [Application Passwords Integration Guide](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/)

**Key Points:**
- All Abilities REST API endpoints require authenticated user
- Application Passwords recommended for external access (AI agents)
- Permission callbacks provide fine-grained access control
- Abilities with `show_in_rest: false` (default) cannot be accessed via REST
- MCP Adapter respects all permission checks from Abilities API

**Security Best Practices:**
1. Always implement `permission_callback` - never leave empty
2. Use `current_user_can()` checks appropriate to the action
3. Create dedicated WordPress user with minimal required capabilities
4. Use short-lived Application Passwords (1-24 hours recommended)
5. Only expose abilities via REST that are intended for external use

```php
'permission_callback' => function( $input ) {
    // Require manage_options for admin-level abilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to access this ability.', 'my-plugin' ),
            [ 'status' => 403 ]
        );
    }
    return true;
},
```

### Finding 6: MCP Client Configuration (Claude Desktop)

**Source:** [How To: Connect Claude to WordPress with MCP](https://meowapps.com/claude-wordpress-mcp/)

**Claude Desktop Configuration (`claude_desktop_config.json`):**

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote"],
      "env": {
        "WP_API_URL": "https://your-site.com/wp-json",
        "WP_API_USERNAME": "your-username",
        "WP_API_PASSWORD": "your-application-password"
      }
    }
  }
}
```

**Config File Locations:**
- macOS: `~/Library/Application Support/Claude/claude_desktop_config.json`
- Linux: `~/.config/Claude/claude_desktop_config.json`
- Windows: `%APPDATA%\Claude\claude_desktop_config.json`

---

## Comparison Matrix

### MCP Component Types

| Component | Purpose | Use Case | Recommendation |
|-----------|---------|----------|----------------|
| **Tools** | Interactive functions AI can invoke | CRUD operations, actions | Use for most abilities |
| **Resources** | Read-only data queries | Lists, lookups, config | Use for pure reads |
| **Prompts** | Instruction templates | Pre-defined workflows | Skip - clients rarely use |

### Transport Options

| Transport | Use Case | Pros | Cons |
|-----------|----------|------|------|
| **HTTP** | Production, remote access | Stateless, scalable, secure | Requires HTTPS |
| **STDIO** | Local dev, CLI | Fast, no network | Single machine only |
| **Custom** | Special integrations | Full control | Must implement interface |

### Installation Methods

| Method | Best For | Notes |
|--------|----------|-------|
| **Composer** | Plugin/theme developers | Recommended; auto-resolves deps |
| **Plugin ZIP** | Site administrators | Download from GitHub releases |
| **Git Clone** | Contributors | Requires build steps |

---

## Recommendations

### For This Codebase

1. **Use Composer Installation**
   - Add both packages as dependencies:
     ```bash
     composer require wordpress/abilities-api wordpress/mcp-adapter
     ```
   - Use Jetpack Autoloader to prevent version conflicts when multiple plugins use these packages

2. **Follow Naming Conventions**
   - Prefix all abilities with plugin slug: `fa-wpmcp/ability-name`
   - Use lowercase, hyphens, action-oriented names
   - Example: `fa-wpmcp/get-post-analytics`, `fa-wpmcp/create-redirect`

3. **Implement Proper Security**
   - Always set `permission_callback` with appropriate capability checks
   - Default `show_in_rest` to `false`; only expose intentionally
   - Use `readonly: true` annotation for read-only abilities

4. **Write AI-Friendly Descriptions**
   - Descriptions are crucial - AI uses them to understand when to invoke
   - Include: what it does, when to use it, what inputs mean
   - Use `instructions` annotation for additional guidance

### Implementation Notes

- **Hook Timing**: Categories must be registered before abilities
- **Validation Separation**: `validate_input()` must be called explicitly (not part of `check_permissions()`)
- **REST Default**: Abilities are NOT exposed via REST by default - must set `show_in_rest: true`
- **Input Schema Optional**: But highly recommended for AI understanding
- **Output Schema Required**: Must define expected return structure
- **Error Handling**: Use `WP_Error` consistently; MCP Adapter converts to MCP error format

---

## Sources

1. [GitHub - WordPress/abilities-api](https://github.com/WordPress/abilities-api) - Official Abilities API repository
2. [GitHub - WordPress/mcp-adapter](https://github.com/WordPress/mcp-adapter) - Official MCP Adapter repository
3. [Introducing the WordPress Abilities API](https://developer.wordpress.org/news/2025/11/introducing-the-wordpress-abilities-api/) - Developer Blog announcement
4. [Abilities API in WordPress 6.9](https://make.wordpress.org/core/2025/11/10/abilities-api-in-wordpress-6-9/) - Core team dev note
5. [Abilities API Documentation](https://developer.wordpress.org/apis/abilities-api/) - Official handbook
6. [PHP API Documentation](https://github.com/WordPress/abilities-api/blob/trunk/docs/php-api.md) - Detailed PHP reference
7. [REST API Endpoints](https://developer.wordpress.org/apis/abilities-api/rest-api-endpoints/) - REST documentation
8. [MCP Adapter v0.3.0 Release](https://make.wordpress.org/ai/2025/11/24/release-announcement-mcp-adapter-v0-3-0/) - Latest release notes
9. [How to Create an MCP Server - WS Form](https://wsform.com/how-to-create-an-mcp-server-in-wordpress-with-the-abilities-api-and-mcp-adapter/) - Practical tutorial
10. [WooCommerce MCP Integration](https://developer.woocommerce.com/docs/features/mcp/) - WooCommerce implementation example
11. [How to Register Custom WooCommerce Abilities](https://wprobo.com/how-to-register-custom-woocommerce-abilities-for-the-wordpress-mcp-adapter/) - Custom ability tutorial
12. [Connect Claude to WordPress](https://meowapps.com/claude-wordpress-mcp/) - Claude Desktop setup guide

---

## Open Questions

- **Rate Limiting**: How does the MCP Adapter handle rate limiting for AI requests? (Not documented)
- **Streaming Support**: HTTP streaming mentioned as "coming later" - current status unclear
- **Multi-site**: How do Abilities work in WordPress multi-site environments?
- **Caching**: Are ability responses cached, and how to control cache behavior?
- **Versioning**: How to handle breaking changes in ability schemas over time?
