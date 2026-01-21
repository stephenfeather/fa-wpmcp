# MCP Client Configuration Examples

This guide shows how to configure AI assistants to connect to your WordPress MCP server powered by FA WPMCP.

---

## Prerequisites

Before connecting an AI client, ensure:

1. **WordPress 6.9+** is installed with FA WPMCP plugin activated
2. **Application Password** is generated (Users → Profile → Application Passwords)
3. **HTTPS is enabled** on your WordPress site (required for security)
4. **Global read permissions** are enabled (Settings → FA WPMCP)

Your WordPress MCP endpoint will be:
```
https://your-site.com/wp-json/abilities/v1/
```

---

## Claude Desktop Configuration

[Claude Desktop](https://claude.ai/download) natively supports MCP servers via JSON configuration.

### Configuration File Location

| OS | Path |
|----|------|
| **macOS** | `~/Library/Application Support/Claude/claude_desktop_config.json` |
| **Windows** | `%APPDATA%\Claude\claude_desktop_config.json` |
| **Linux** | `~/.config/Claude/claude_desktop_config.json` |

### Example Configuration

Create or edit `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-fetch"
      ],
      "env": {
        "WORDPRESS_BASE_URL": "https://your-site.com/wp-json/abilities/v1",
        "WORDPRESS_USERNAME": "your-username",
        "WORDPRESS_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

### Configuration Breakdown

| Field | Description |
|-------|-------------|
| `wordpress` | Unique identifier for this MCP server (can be any name) |
| `command` | Uses `npx` to run MCP fetch server (auto-installs if needed) |
| `WORDPRESS_BASE_URL` | Your WordPress MCP endpoint |
| `WORDPRESS_USERNAME` | Your WordPress username |
| `WORDPRESS_APP_PASSWORD` | Generated application password (with spaces) |

### Verify Connection

1. **Restart Claude Desktop** after saving configuration
2. **Start new conversation**
3. **Look for MCP icon** (🔌) in bottom-left corner
4. **Click MCP icon** to see available tools:
   - `fa-wpmcp/list-posts`
   - `fa-wpmcp/get-post`
   - `fa-wpmcp/create-post`
   - `fa-wpmcp/update-post`

### Example Usage in Claude

Once connected, you can use natural language:

```
User: "List the 5 most recent published posts on my WordPress site"

Claude: I'll query your WordPress site for recent posts.
[Uses fa-wpmcp/list-posts tool]

Here are your 5 most recent published posts:
1. "Getting Started with MCP" (Jan 21, 2026)
2. "WordPress Automation Guide" (Jan 20, 2026)
...
```

---

## Alternative: HTTP REST MCP Server

If you prefer not to use the fetch server, you can create a custom MCP server wrapper.

### Node.js MCP Server Example

Create `wordpress-mcp-server.js`:

```javascript
#!/usr/bin/env node
import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import fetch from "node-fetch";

const WORDPRESS_URL = process.env.WORDPRESS_BASE_URL;
const USERNAME = process.env.WORDPRESS_USERNAME;
const APP_PASSWORD = process.env.WORDPRESS_APP_PASSWORD;

const auth = Buffer.from(`${USERNAME}:${APP_PASSWORD}`).toString('base64');

const server = new Server(
  {
    name: "wordpress-mcp",
    version: "1.0.0",
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

// Register list-posts tool
server.setRequestHandler("tools/list", async () => {
  return {
    tools: [
      {
        name: "list-posts",
        description: "List WordPress posts with pagination and filtering",
        inputSchema: {
          type: "object",
          properties: {
            page: { type: "number", default: 1 },
            per_page: { type: "number", default: 10 },
            status: { type: "string", default: "publish" },
          },
        },
      },
      {
        name: "get-post",
        description: "Get a specific WordPress post by ID",
        inputSchema: {
          type: "object",
          properties: {
            post_id: { type: "number" },
          },
          required: ["post_id"],
        },
      },
      {
        name: "create-post",
        description: "Create a new WordPress post",
        inputSchema: {
          type: "object",
          properties: {
            title: { type: "string" },
            content: { type: "string" },
            status: { type: "string", default: "draft" },
          },
          required: ["title"],
        },
      },
      {
        name: "update-post",
        description: "Update an existing WordPress post",
        inputSchema: {
          type: "object",
          properties: {
            post_id: { type: "number" },
            title: { type: "string" },
            content: { type: "string" },
            status: { type: "string" },
          },
          required: ["post_id"],
        },
      },
    ],
  };
});

// Handle tool calls
server.setRequestHandler("tools/call", async (request) => {
  const { name, arguments: args } = request.params;

  const abilityName = `fa-wpmcp/${name}`;
  const url = `${WORDPRESS_URL}/execute/${abilityName}`;

  const response = await fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Basic ${auth}`,
    },
    body: JSON.stringify(args),
  });

  const data = await response.json();

  if (!response.ok) {
    return {
      content: [
        {
          type: "text",
          text: `Error: ${data.error?.message || "Unknown error"}`,
        },
      ],
      isError: true,
    };
  }

  return {
    content: [
      {
        type: "text",
        text: JSON.stringify(data.data, null, 2),
      },
    ],
  };
});

const transport = new StdioServerTransport();
server.connect(transport);
```

### Claude Desktop Config for Custom Server

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "node",
      "args": ["/path/to/wordpress-mcp-server.js"],
      "env": {
        "WORDPRESS_BASE_URL": "https://your-site.com/wp-json/abilities/v1",
        "WORDPRESS_USERNAME": "your-username",
        "WORDPRESS_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

---

## OpenAI GPT Configuration (Custom Actions)

OpenAI GPTs don't directly support MCP, but you can use Custom Actions with OpenAPI specs.

### Step 1: Create OpenAPI Specification

Save as `wordpress-openapi.json`:

```json
{
  "openapi": "3.1.0",
  "info": {
    "title": "WordPress MCP API",
    "description": "WordPress content management via FA WPMCP plugin",
    "version": "1.0.0"
  },
  "servers": [
    {
      "url": "https://your-site.com/wp-json/abilities/v1"
    }
  ],
  "paths": {
    "/execute/fa-wpmcp/list-posts": {
      "post": {
        "operationId": "listPosts",
        "summary": "List WordPress posts",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "properties": {
                  "page": { "type": "integer", "default": 1 },
                  "per_page": { "type": "integer", "default": 10 },
                  "status": { "type": "string", "default": "publish" }
                }
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Successful response",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "properties": {
                    "success": { "type": "boolean" },
                    "data": {
                      "type": "object",
                      "properties": {
                        "posts": { "type": "array" },
                        "total": { "type": "integer" }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    },
    "/execute/fa-wpmcp/get-post": {
      "post": {
        "operationId": "getPost",
        "summary": "Get a specific post by ID",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "properties": {
                  "post_id": { "type": "integer" }
                },
                "required": ["post_id"]
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Successful response"
          }
        }
      }
    },
    "/execute/fa-wpmcp/create-post": {
      "post": {
        "operationId": "createPost",
        "summary": "Create a new WordPress post",
        "requestBody": {
          "required": true,
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "properties": {
                  "title": { "type": "string" },
                  "content": { "type": "string" },
                  "status": { "type": "string", "default": "draft" }
                },
                "required": ["title"]
              }
            }
          }
        },
        "responses": {
          "200": {
            "description": "Successful response"
          }
        }
      }
    }
  },
  "components": {
    "securitySchemes": {
      "BasicAuth": {
        "type": "http",
        "scheme": "basic"
      }
    }
  },
  "security": [
    {
      "BasicAuth": []
    }
  ]
}
```

### Step 2: Configure GPT

1. Go to [ChatGPT](https://chat.openai.com/)
2. Click **Explore** → **Create a GPT**
3. Navigate to **Configure** tab
4. Scroll to **Actions** section
5. Click **Create new action**
6. Paste the OpenAPI spec
7. Set **Authentication** → **Basic**
8. Enter your WordPress **username** and **application password**
9. Click **Test** to verify connection
10. Save your GPT

### Example GPT Instructions

```
You are a WordPress content manager. You can:
- List and search WordPress posts
- Read full post details
- Create new posts (drafts or published)
- Update existing posts

Always confirm before creating or publishing posts. Use the listPosts action to search before creating duplicates.
```

---

## Google AI Studio / Gemini Configuration

Google AI Studio supports function calling, which can be configured to call your WordPress MCP endpoints.

### Using Gemini API with Python

```python
import google.generativeai as genai
import requests
import base64
import json

# Configure Gemini
genai.configure(api_key="YOUR_GEMINI_API_KEY")

# WordPress credentials
WP_URL = "https://your-site.com/wp-json/abilities/v1"
WP_USER = "your-username"
WP_PASS = "xxxx xxxx xxxx xxxx xxxx xxxx"
WP_AUTH = base64.b64encode(f"{WP_USER}:{WP_PASS}".encode()).decode()

# Define function declarations for Gemini
list_posts_func = genai.protos.FunctionDeclaration(
    name="list_posts",
    description="List WordPress posts with pagination",
    parameters=genai.protos.Schema(
        type=genai.protos.Type.OBJECT,
        properties={
            "page": genai.protos.Schema(type=genai.protos.Type.NUMBER),
            "per_page": genai.protos.Schema(type=genai.protos.Type.NUMBER),
            "status": genai.protos.Schema(type=genai.protos.Type.STRING),
        },
    ),
)

create_post_func = genai.protos.FunctionDeclaration(
    name="create_post",
    description="Create a new WordPress post",
    parameters=genai.protos.Schema(
        type=genai.protos.Type.OBJECT,
        properties={
            "title": genai.protos.Schema(type=genai.protos.Type.STRING),
            "content": genai.protos.Schema(type=genai.protos.Type.STRING),
            "status": genai.protos.Schema(type=genai.protos.Type.STRING),
        },
        required=["title"],
    ),
)

# Create tool
wordpress_tool = genai.protos.Tool(
    function_declarations=[list_posts_func, create_post_func]
)

# Initialize model with tools
model = genai.GenerativeModel(
    model_name="gemini-1.5-pro",
    tools=[wordpress_tool]
)

# Function to call WordPress API
def call_wordpress_ability(ability_name, params):
    url = f"{WP_URL}/execute/fa-wpmcp/{ability_name}"
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Basic {WP_AUTH}"
    }
    response = requests.post(url, json=params, headers=headers)
    return response.json()

# Chat with function calling
chat = model.start_chat()

response = chat.send_message("List the 3 most recent WordPress posts")

# Handle function calls
for part in response.parts:
    if fn := part.function_call:
        # Map function name to WordPress ability
        ability_map = {
            "list_posts": "list-posts",
            "create_post": "create-post",
        }

        ability = ability_map[fn.name]
        args = dict(fn.args)

        # Call WordPress
        result = call_wordpress_ability(ability, args)

        # Send result back to Gemini
        response = chat.send_message(
            genai.protos.Part(
                function_response=genai.protos.FunctionResponse(
                    name=fn.name,
                    response={"result": result}
                )
            )
        )

print(response.text)
```

### Using Gemini API with Node.js

```javascript
import { GoogleGenerativeAI } from "@google/generative-ai";
import fetch from "node-fetch";

const genAI = new GoogleGenerativeAI("YOUR_GEMINI_API_KEY");

const WP_URL = "https://your-site.com/wp-json/abilities/v1";
const WP_USER = "your-username";
const WP_PASS = "xxxx xxxx xxxx xxxx xxxx xxxx";
const WP_AUTH = Buffer.from(`${WP_USER}:${WP_PASS}`).toString('base64');

// Define functions
const functions = {
  list_posts: {
    description: "List WordPress posts with pagination",
    parameters: {
      type: "object",
      properties: {
        page: { type: "number" },
        per_page: { type: "number" },
        status: { type: "string" }
      }
    }
  },
  create_post: {
    description: "Create a new WordPress post",
    parameters: {
      type: "object",
      properties: {
        title: { type: "string" },
        content: { type: "string" },
        status: { type: "string" }
      },
      required: ["title"]
    }
  }
};

// Initialize model
const model = genAI.getGenerativeModel({
  model: "gemini-1.5-pro",
  tools: [{ functionDeclarations: Object.entries(functions).map(([name, decl]) => ({
    name,
    ...decl
  }))}]
});

// Call WordPress
async function callWordPress(abilityName, params) {
  const url = `${WP_URL}/execute/fa-wpmcp/${abilityName}`;
  const response = await fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Basic ${WP_AUTH}`
    },
    body: JSON.stringify(params)
  });
  return response.json();
}

// Chat with function calling
const chat = model.startChat();

const result = await chat.sendMessage("List my 3 most recent posts");

// Handle function calls
for (const part of result.response.functionCalls() || []) {
  const abilityName = part.name.replace('_', '-');
  const wpResult = await callWordPress(abilityName, part.args);

  await chat.sendMessage([{
    functionResponse: {
      name: part.name,
      response: wpResult
    }
  }]);
}

console.log(result.response.text());
```

---

## Cursor IDE Configuration

[Cursor](https://cursor.sh/) supports MCP servers via configuration file.

### Configuration File Location

Create `.cursorrules` in your project root or edit global config at:
- **macOS/Linux:** `~/.cursor/config.json`
- **Windows:** `%APPDATA%\Cursor\config.json`

### Example Configuration

```json
{
  "mcp": {
    "servers": {
      "wordpress": {
        "url": "https://your-site.com/wp-json/abilities/v1",
        "auth": {
          "type": "basic",
          "username": "your-username",
          "password": "xxxx xxxx xxxx xxxx xxxx xxxx"
        }
      }
    }
  }
}
```

---

## Cline VSCode Extension Configuration

[Cline](https://github.com/cline/cline) supports MCP servers.

### Configuration

1. Open VSCode Settings (`Cmd+,` or `Ctrl+,`)
2. Search for "Cline"
3. Find **MCP Servers** section
4. Add server configuration:

```json
{
  "cline.mcpServers": {
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

---

## Security Best Practices

### 1. Use Application Passwords (Not Your Main Password)
```
✅ Generate unique app password per AI client
✅ Revoke unused app passwords regularly
✅ Never share your main WordPress password
```

### 2. HTTPS Only
```
✅ Use HTTPS for all WordPress API calls
❌ HTTP exposes credentials in plain text
```

### 3. Limit Permissions
```
✅ Create dedicated WordPress user for AI access
✅ Assign minimal required capabilities
✅ Use role-based permissions (Editor vs Admin)
```

### 4. Monitor Activity
```
✅ Review activity logs regularly (Settings → FA WPMCP → Logs)
✅ Set up webhook notifications for suspicious activity
✅ Enable rate limiting to prevent abuse
```

### 5. IP Whitelisting (Advanced)
```php
// Add to wp-config.php or theme functions.php
add_filter('fa_wpmcp_allow_request', function($allowed, $ip) {
    $whitelist = ['203.0.113.1', '203.0.113.2'];
    return in_array($ip, $whitelist);
}, 10, 2);
```

---

## Troubleshooting

### Connection Issues

**Problem:** AI client can't connect
```
✓ Verify WordPress URL is correct (check /wp-json)
✓ Test with curl: curl https://your-site.com/wp-json/abilities/v1/
✓ Check SSL certificate is valid
✓ Verify app password doesn't have typos (spaces are OK)
```

**Problem:** 401 Unauthorized
```
✓ Regenerate application password
✓ Ensure username is exact (case-sensitive)
✓ Test credentials with curl
```

**Problem:** 403 Forbidden
```
✓ Enable global read permissions (Settings → FA WPMCP)
✓ Check user has required WordPress capabilities
✓ Verify ability isn't disabled for category
```

**Problem:** 429 Too Many Requests
```
✓ Reduce request frequency
✓ Increase rate limits (Settings → FA WPMCP → Rate Limiting)
✓ Check if IP is being rate-limited
```

### Testing Your Configuration

Use curl to test before configuring AI clients:

```bash
# Test authentication
curl -u "username:app-password" \
  https://your-site.com/wp-json/abilities/v1/

# Test list-posts ability
curl -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{"page": 1, "per_page": 5}' \
  https://your-site.com/wp-json/abilities/v1/execute/fa-wpmcp/list-posts
```

Expected response:
```json
{
  "success": true,
  "data": { ... },
  "timestamp": "2026-01-21T12:00:00Z"
}
```

---

## Additional Resources

- **FA WPMCP Documentation:** [Full API Reference](./MCP_DOCUMENTATION.md)
- **Model Context Protocol:** [https://modelcontextprotocol.io](https://modelcontextprotocol.io)
- **WordPress Application Passwords:** [https://make.wordpress.org/core/2020/11/05/application-passwords/](https://make.wordpress.org/core/2020/11/05/application-passwords/)
- **Claude Desktop:** [https://claude.ai/download](https://claude.ai/download)
- **OpenAI Custom GPTs:** [https://platform.openai.com/docs/actions](https://platform.openai.com/docs/actions)
- **Google Gemini API:** [https://ai.google.dev/docs](https://ai.google.dev/docs)

---

**Last Updated:** 2026-01-21
**Plugin Version:** 1.0.0
