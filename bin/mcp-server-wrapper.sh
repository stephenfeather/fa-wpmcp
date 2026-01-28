#!/bin/bash

LOG_FILE="/tmp/mcp-server-debug.log"

echo "[$(date)] MCP server wrapper starting" >> "$LOG_FILE"
echo "Args: $@" >> "$LOG_FILE"
echo "Env: WORDPRESS_BASE_URL=$WORDPRESS_BASE_URL" >> "$LOG_FILE"

# Get the server script path
SCRIPT_DIR="$(dirname "$0")"
SERVER_SCRIPT="$SCRIPT_DIR/mcp-server.js"

# Check if node is available
if ! command -v node &> /dev/null; then
    echo "[$(date)] ERROR: node command not found" >> "$LOG_FILE"
    echo "ERROR: node is not installed or not in PATH" >&2
    exit 1
fi

# Check if the server script exists
if [ ! -f "$SERVER_SCRIPT" ]; then
    echo "[$(date)] ERROR: Server script not found: $SERVER_SCRIPT" >> "$LOG_FILE"
    echo "ERROR: MCP server script not found at $SERVER_SCRIPT" >&2
    exit 1
fi

echo "---" >> "$LOG_FILE"

# Run the node server and capture exit code
node "$SERVER_SCRIPT" "$@" 2>> "$LOG_FILE"
EXIT_CODE=$?

# Log the exit
if [ $EXIT_CODE -ne 0 ]; then
    echo "[$(date)] MCP server exited with error code: $EXIT_CODE" >> "$LOG_FILE"
else
    echo "[$(date)] MCP server exited normally" >> "$LOG_FILE"
fi

exit $EXIT_CODE