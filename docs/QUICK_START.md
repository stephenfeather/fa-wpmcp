# Quick Start Guide

Get your WordPress site connected to AI assistants in under 5 minutes.

---

## Step 1: Install & Activate Plugin (2 min)

1. **Upload plugin** to `wp-content/plugins/fa-wpmcp/`
2. **Install dependencies:**
   ```bash
   cd wp-content/plugins/fa-wpmcp
   composer install --no-dev
   ```
3. **Activate** in WordPress Admin → Plugins

---

## Step 2: Generate Application Password (1 min)

1. Go to **WordPress Admin → Users → Your Profile**
2. Scroll to **Application Passwords** section
3. Enter name: **"Claude Desktop"**
4. Click **Add New Application Password**
5. **Copy the password** (format: `xxxx xxxx xxxx xxxx xxxx xxxx`)

---

## Step 3: Enable Permissions (1 min)

1. Go to **Settings → FA WPMCP**
2. Enable **"Enable All Read Operations"** ✓
3. Enable **"Enable All Write Operations"** ✓ (optional)
4. Click **Save Changes**

---

## Step 4: Configure Claude Desktop (1 min)

### Find Config File

| OS | Path |
|----|------|
| **macOS** | `~/Library/Application Support/Claude/claude_desktop_config.json` |
| **Windows** | `%APPDATA%\Claude\claude_desktop_config.json` |
| **Linux** | `~/.config/Claude/claude_desktop_config.json` |

### Add Configuration

Create or edit the file:

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-fetch"],
      "env": {
        "WORDPRESS_BASE_URL": "https://your-site.com/wp-json/abilities/v1",
        "WORDPRESS_USERNAME": "your-username",
        "WORDPRESS_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

**Replace:**
- `your-site.com` with your WordPress domain
- `your-username` with your WordPress username
- `xxxx xxxx...` with the application password from Step 2

### Restart Claude Desktop

Close and reopen Claude Desktop for changes to take effect.

---

## Step 5: Test Connection

1. **Start new conversation** in Claude Desktop
2. **Look for MCP icon** (🔌) in bottom-left corner
3. **Click the icon** to see available tools:
   - ✓ fa-wpmcp/list-posts
   - ✓ fa-wpmcp/get-post
   - ✓ fa-wpmcp/create-post
   - ✓ fa-wpmcp/update-post

4. **Test with a prompt:**
   ```
   "List the 3 most recent posts on my WordPress site"
   ```

Claude should connect to your WordPress site and return your posts!

---

## What You Can Do

### Read Operations
```
"Show me my 10 most recent draft posts"
"Get the full content of post ID 42"
"Search for posts containing 'WordPress'"
"List all posts by author John"
```

### Write Operations
```
"Create a draft post titled 'Hello World' with content 'This is a test'"
"Update post 42 to change the title to 'Updated Title'"
"Publish post 42"
"Create a post in the 'Technology' category"
```

---

## Troubleshooting

### Can't See MCP Icon in Claude

**Solution:**
1. Verify config file is in correct location
2. Check JSON syntax is valid (use https://jsonlint.com)
3. Restart Claude Desktop completely (Quit, not just close window)

### Connection Errors

**Test your credentials with curl:**
```bash
curl -u "your-username:your-app-password" \
  https://your-site.com/wp-json/abilities/v1/
```

Should return: `{"success":true,"data":...}`

If you get 401: Check username/password
If you get 403: Enable permissions in Settings → FA WPMCP
If timeout: Check WordPress URL is correct and HTTPS works

---

## Next Steps

- **[Full API Reference](./MCP_DOCUMENTATION.md)** - All available abilities and schemas
- **[Client Configuration](./MCP_CLIENT_CONFIGURATION.md)** - Setup for GPT, Gemini, Cursor, etc.
- **[Configuration Guide](./CONFIGURATION.md)** - Advanced settings and customization
- **[Architecture Overview](./ARCHITECTURE.md)** - How the plugin works internally

---

## Security Notes

✅ **Do This:**
- Use HTTPS (not HTTP)
- Generate unique app password per AI client
- Revoke unused app passwords
- Monitor activity logs (Settings → FA WPMCP → Activity Log)

❌ **Don't Do This:**
- Share your main WordPress password
- Use HTTP (credentials exposed)
- Give admin access if not needed
- Ignore suspicious activity logs

---

**Need Help?**
- Check [Troubleshooting Guide](./MCP_CLIENT_CONFIGURATION.md#troubleshooting)
- Review [Security Best Practices](./MCP_CLIENT_CONFIGURATION.md#security-best-practices)
- Open issue on [GitHub](https://github.com/featherart/fa-wpmcp/issues)

---

**Last Updated:** 2026-01-21
