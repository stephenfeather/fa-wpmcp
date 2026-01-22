#!/usr/bin/env node
/**
 * FA WPMCP MCP Server
 *
 * Production-ready MCP server wrapper that bridges WordPress Abilities API
 * to Claude Desktop's stdio transport via the Model Context Protocol (MCP).
 *
 * @see https://github.com/featherart/fa-wpmcp
 * @license GPL-2.0-or-later
 */

import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  ListToolsRequestSchema,
  CallToolRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";

/**
 * Configuration from environment variables
 */
const WORDPRESS_URL = process.env.WORDPRESS_BASE_URL;
const USERNAME = process.env.WORDPRESS_USERNAME;
const APP_PASSWORD = process.env.WORDPRESS_APP_PASSWORD;

/**
 * Validate required environment variables
 */
if (!WORDPRESS_URL || !USERNAME || !APP_PASSWORD) {
  console.error("Error: Missing required environment variables");
  console.error("Required: WORDPRESS_BASE_URL, WORDPRESS_USERNAME, WORDPRESS_APP_PASSWORD");
  process.exit(1);
}

/**
 * Create Basic Auth header
 */
const auth = Buffer.from(`${USERNAME}:${APP_PASSWORD}`).toString("base64");

/**
 * Fetch abilities from WordPress REST API
 *
 * @returns {Promise<Array>} Array of MCP tool definitions
 */
async function fetchAbilities() {
  try {
    const response = await fetch(WORDPRESS_URL, {
      headers: { Authorization: `Basic ${auth}` },
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();

    if (!data.success || !data.data || !data.data.abilities) {
      throw new Error("Invalid response format from WordPress");
    }

    // Convert WordPress abilities to MCP tool format
    return Object.entries(data.data.abilities).map(([name, ability]) => ({
      name: name.replace("fa-wpmcp/", ""),
      description: ability.description || ability.label || `Execute ${name}`,
      inputSchema: ability.input_schema || {
        type: "object",
        properties: {},
      },
    }));
  } catch (error) {
    console.error("Failed to fetch WordPress abilities:", error.message);
    return [];
  }
}

/**
 * Execute a WordPress ability
 *
 * @param {string} name - Tool name (without fa-wpmcp/ prefix)
 * @param {Object} args - Tool arguments
 * @returns {Promise<Object>} MCP tool response
 */
async function executeAbility(name, args) {
  const abilityName = `fa-wpmcp/${name}`;
  const url = `${WORDPRESS_URL}/execute/${abilityName}`;

  try {
    const response = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Basic ${auth}`,
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
  } catch (error) {
    return {
      content: [
        {
          type: "text",
          text: `Error calling WordPress: ${error.message}`,
        },
      ],
      isError: true,
    };
  }
}

/**
 * Initialize and start MCP server
 */
async function main() {
  // Create MCP server instance
  const server = new Server(
    {
      name: "fa-wpmcp",
      version: "1.0.0",
    },
    {
      capabilities: {
        tools: {},
      },
    }
  );

  // Fetch available abilities from WordPress
  const availableTools = await fetchAbilities();

  if (availableTools.length === 0) {
    console.error("Warning: No abilities loaded from WordPress");
  }

  // Register tool list handler
  server.setRequestHandler(ListToolsRequestSchema, async () => {
    return { tools: availableTools };
  });

  // Register tool execution handler
  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;
    return await executeAbility(name, args || {});
  });

  // Connect to stdio transport
  const transport = new StdioServerTransport();
  await server.connect(transport);

  console.error(`FA WPMCP MCP Server started with ${availableTools.length} abilities`);
}

// Start server
main().catch((error) => {
  console.error("Fatal error:", error);
  process.exit(1);
});
