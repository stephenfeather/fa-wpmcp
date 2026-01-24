# FA WPMCP MCP Server

> **📌 Note:** For most users, we recommend using `@automattic/mcp-wordpress-remote` instead of this bundled server. The Automattic package provides automatic OAuth support, better authentication handling, and seamless integration. See the [Quick Start Guide](../docs/QUICK_START.md) for instructions.
>
> **Use this bundled server if you need:**
> - Custom MCP server modifications
> - Specific environment configurations
> - Learning how MCP servers work
> - Advanced debugging capabilities

Production-ready MCP server wrapper for the FA WPMCP WordPress plugin. This server bridges WordPress Abilities API to Claude Desktop's stdio transport using the Model Context Protocol (MCP).

## Features

- **Auto-discovery**: Automatically fetches all abilities from your WordPress installation
- **Zero configuration**: No need to manually update tool definitions when adding new abilities
- **Production-ready**: Includes error handling, validation, and proper MCP protocol implementation
- **Secure**: Uses WordPress Application Passwords for authentication

## Prerequisites

- Node.js 18.0.0 or higher
- FA WPMCP plugin installed and activated on WordPress 6.9+
- WordPress Application Password configured

## Installation

### 1. Install Dependencies

```bash
cd bin
npm install
```

### 2. Configure Environment Variables

Create a `.env` file in your home directory or set environment variables:

```bash
export WORDPRESS_BASE_URL="https://your-site.com/wp-json/abilities/v1"
export WORDPRESS_USERNAME="your-username"
export WORDPRESS_APP_PASSWORD="your-app-password"
```

**Note**: The `WORDPRESS_BASE_URL` should point to your WordPress Abilities API endpoint (typically `/wp-json/abilities/v1`).

### 3. Make Script Executable

```bash
chmod +x mcp-server.js
```

## Usage

### Standalone Execution

Run directly:

```bash
node mcp-server.js
```

Or if made executable:

```bash
./mcp-server.js
```

### Claude Desktop Integration

Add to your Claude Desktop configuration (`claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "fa-wpmcp": {
      "command": "node",
      "args": ["/absolute/path/to/fa-wpmcp/bin/mcp-server.js"],
      "env": {
        "WORDPRESS_BASE_URL": "https://your-site.com/wp-json/abilities/v1",
        "WORDPRESS_USERNAME": "your-username",
        "WORDPRESS_APP_PASSWORD": "your-app-password"
      }
    }
  }
}
```

**Important**: Replace `/absolute/path/to/fa-wpmcp` with the actual path to your plugin directory.

## Configuration

### Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `WORDPRESS_BASE_URL` | WordPress Abilities API endpoint | `https://example.com/wp-json/abilities/v1` |
| `WORDPRESS_USERNAME` | WordPress username | `admin` |
| `WORDPRESS_APP_PASSWORD` | WordPress Application Password | `xxxx xxxx xxxx xxxx xxxx xxxx` |

### WordPress Application Passwords

1. Log in to your WordPress admin
2. Go to **Users** → **Profile**
3. Scroll to **Application Passwords**
4. Enter a name (e.g., "Claude Desktop MCP")
5. Click **Add New Application Password**
6. Copy the generated password (format: `xxxx xxxx xxxx xxxx xxxx xxxx`)

## Available Tools

The server automatically discovers and exposes all registered abilities from your WordPress installation. Tools are prefixed with their ability name (e.g., `fa-wpmcp/list-posts` becomes `list-posts`).

### Default Abilities (25 tools)

- **Posts & Pages**: list-posts, get-post, create-post, update-post
- **Comments**: list-comments, get-comment, create-comment, update-comment, delete-comment
- **Media**: list-media, get-media, update-media, upload-media
- **Taxonomies**: list-terms, get-term, create-term, update-term
- **Settings**: get-option, update-option, delete-option, list-options
- **Users**: list-users, get-user, create-user, update-user

**Note**: The actual abilities available depend on your WordPress configuration and FA WPMCP plugin version.

## Troubleshooting

### "Missing required environment variables"

Ensure all three environment variables are set:
- `WORDPRESS_BASE_URL`
- `WORDPRESS_USERNAME`
- `WORDPRESS_APP_PASSWORD`

### "Failed to fetch WordPress abilities"

- Verify your WordPress URL is correct and accessible
- Check that the FA WPMCP plugin is activated
- Confirm your Application Password is valid
- Ensure your WordPress user has appropriate permissions

### "No abilities loaded from WordPress"

- Check that abilities are registered in the plugin
- Verify your user has permission to access the Abilities API
- Review WordPress debug logs for errors

### HTTP 401 Unauthorized

- Regenerate your Application Password
- Verify username and password are correct
- Check that Application Passwords are enabled in WordPress

## Development

### Testing the Server

```bash
# Run with debug output
node mcp-server.js 2>&1 | tee mcp-server.log
```

### Adding Custom Abilities

When you register new abilities in your WordPress plugin, they will automatically be discovered by this MCP server on the next startup. No code changes required!

## Architecture

```
Claude Desktop
     ↓
  stdio transport
     ↓
FA WPMCP MCP Server (this)
     ↓
  HTTP REST API
     ↓
WordPress Abilities API
     ↓
FA WPMCP Plugin
```

## License

GPL v2 or later

## Links

- [FA WPMCP Plugin](https://github.com/featherart/fa-wpmcp)
- [Model Context Protocol](https://modelcontextprotocol.io/)
- [Claude Desktop](https://claude.ai/download)
- [WordPress Abilities API](https://developer.wordpress.org/apis/abilities-api/)
