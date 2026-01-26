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
 * Cache for ability metadata (used to determine HTTP method)
 */
const abilityMetadata = new Map();

/**
 * Fetch abilities from WordPress REST API
 *
 * @returns {Promise<Array>} Array of MCP tool definitions
 */
async function fetchAbilities() {
	try {
		console.error(`Making request to: ${WORDPRESS_URL}`);
		const response = await fetch(WORDPRESS_URL, {
			headers: { Authorization: `Basic ${auth}` },
		});

		console.error(`Response status: ${response.status} ${response.statusText}`);

		if (!response.ok) {
			const errorText = await response.text();
			console.error(`Error response body: ${errorText.substring(0, 200)}`);
			throw new Error(`HTTP ${response.status}: ${response.statusText}`);
		}

		const data = await response.json();
		console.error(`Received data type: ${Array.isArray(data) ? 'array' : typeof data}`);
		console.error(`Data length/keys: ${Array.isArray(data) ? data.length : Object.keys(data).length}`);

		// WordPress REST API returns an array of abilities directly
		if (!Array.isArray(data)) {
			console.error(`Data structure: ${JSON.stringify(data).substring(0, 200)}`);
			throw new Error("Invalid response format from WordPress - expected array of abilities");
		}

		// Convert WordPress abilities to MCP tool format and cache metadata
		return data.map((ability) => {
			// Sanitize tool name: replace slashes with hyphens for MCP compatibility
			const mcpToolName = ability.name.replaceAll('/', '-');

			// Cache metadata for execution (use original name as key)
			abilityMetadata.set(mcpToolName, {
				wordpressName: ability.name,
				readonly: ability.meta?.annotations?.readonly || false,
			});

			// Normalize input schema - MCP requires an object, not an array
			let inputSchema = ability.input_schema;
			if (Array.isArray(inputSchema) || !inputSchema || typeof inputSchema !== 'object') {
				inputSchema = {
					type: "object",
					properties: {},
				};
			}

			return {
				name: mcpToolName,
				description: ability.description || ability.label || `Execute ${ability.name}`,
				inputSchema,
			};
		});
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
	// Get metadata and original WordPress ability name
	const metadata = abilityMetadata.get(name);
	const wordpressName = metadata?.wordpressName || name;

	// WordPress REST API endpoint: /wp-abilities/v1/abilities/{name}/run
	const baseUrl = WORDPRESS_URL.replace(/\/abilities$/, '');
	const url = `${baseUrl}/abilities/${wordpressName}/run`;

	// Determine HTTP method based on ability metadata
	const method = metadata?.readonly ? "GET" : "POST";

	try {
		const fetchOptions = {
			method,
			headers: {
				Authorization: `Basic ${auth}`,
			},
		};

		// Only add body for POST requests
		if (method === "POST") {
			fetchOptions.headers["Content-Type"] = "application/json";
			fetchOptions.body = JSON.stringify({ input: args });
		}

		const response = await fetch(url, fetchOptions);
		const data = await response.json();

		if (!response.ok) {
			return {
				content: [
					{
						type: "text",
						text: `Error: ${data.message || data.error?.message || "Unknown error"}`,
					},
				],
				isError: true,
			};
		}

		return {
			content: [
				{
					type: "text",
					text: JSON.stringify(data, null, 2),
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
	console.error(`Fetching abilities from: ${WORDPRESS_URL}`);
	const availableTools = await fetchAbilities();

	if (availableTools.length === 0) {
		console.error("ERROR: No abilities loaded from WordPress");
		console.error("Check that WordPress is accessible and the plugin is activated");
	} else {
		console.error(`Successfully loaded ${availableTools.length} abilities:`);
		availableTools.forEach(tool => console.error(`  - ${tool.name}`));
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
