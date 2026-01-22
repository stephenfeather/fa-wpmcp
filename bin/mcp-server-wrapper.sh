#!/bin/bash
echo "[$(date)] MCP server wrapper started" >> /tmp/mcp-server-debug.log
echo "Args: $@" >> /tmp/mcp-server-debug.log
echo "Env: WORDPRESS_BASE_URL=$WORDPRESS_BASE_URL" >> /tmp/mcp-server-debug.log
echo "---" >> /tmp/mcp-server-debug.log
exec node "$(dirname "$0")/mcp-server.js" "$@" 2>> /tmp/mcp-server-debug.log
