#!/bin/bash
#
# WordPress Test Environment Setup Script
#
# This script runs once to set up a fresh WordPress installation
# with the fa-wpmcp plugin activated and an application password
# configured for MCP integration testing.
#

set -e

WP_PATH="/var/www/html"
WP_URL="http://localhost:8080"
ADMIN_USER="test_admin"
ADMIN_PASSWORD="test_password_123"
ADMIN_EMAIL="test@example.com"
APP_PASSWORD_NAME="mcp-integration-tests"

# Marker file to prevent re-running setup
SETUP_MARKER="${WP_PATH}/.wp-test-setup-complete"

echo "=== FA-WPMCP Integration Test Setup ==="

# Check if setup already completed
if [ -f "$SETUP_MARKER" ]; then
    echo "Setup already completed. Reading existing credentials..."
    cat "${WP_PATH}/.mcp-test-credentials"
    exit 0
fi

# Wait for database to be ready
echo "Waiting for database connection..."
until mysqladmin ping -h "${WORDPRESS_DB_HOST%%:*}" -u"${WORDPRESS_DB_USER}" -p"${WORDPRESS_DB_PASSWORD}" --silent 2>/dev/null; do
    sleep 2
done
echo "Database is ready."

# Download WordPress if not present
if [ ! -f "${WP_PATH}/wp-includes/version.php" ]; then
    echo "Downloading WordPress..."
    wp core download --path="$WP_PATH" --allow-root
    # Set ownership, ignoring errors from read-only mounts (like the plugin mount)
    chown -R www-data:www-data "$WP_PATH" 2>/dev/null || true
fi

# Create wp-config.php if not present
if [ ! -f "${WP_PATH}/wp-config.php" ]; then
    echo "Creating wp-config.php..."
    wp config create \
        --path="$WP_PATH" \
        --dbname="${WORDPRESS_DB_NAME}" \
        --dbuser="${WORDPRESS_DB_USER}" \
        --dbpass="${WORDPRESS_DB_PASSWORD}" \
        --dbhost="${WORDPRESS_DB_HOST}" \
        --allow-root

    # Add Redis configuration
    wp config set WP_REDIS_HOST redis-test --path="$WP_PATH" --allow-root
    wp config set WP_REDIS_PORT 6379 --raw --path="$WP_PATH" --allow-root

    # Enable application passwords (required for MCP)
    wp config set WP_APPLICATION_PASSWORD_ENABLED true --raw --path="$WP_PATH" --allow-root
fi

# Install WordPress if not installed
if ! wp core is-installed --path="$WP_PATH" --allow-root 2>/dev/null; then
    echo "Installing WordPress..."
    wp core install \
        --path="$WP_PATH" \
        --url="$WP_URL" \
        --title="FA-WPMCP Test Site" \
        --admin_user="$ADMIN_USER" \
        --admin_password="$ADMIN_PASSWORD" \
        --admin_email="$ADMIN_EMAIL" \
        --skip-email \
        --allow-root
fi

# Set up permalinks (required for REST API)
echo "Setting up permalinks..."
wp rewrite structure '/%postname%/' --path="$WP_PATH" --allow-root
wp rewrite flush --path="$WP_PATH" --allow-root

# Create mu-plugin for test environment configuration
echo "Creating test environment mu-plugin..."
mkdir -p "${WP_PATH}/wp-content/mu-plugins"
cat > "${WP_PATH}/wp-content/mu-plugins/fa-wpmcp-test-setup.php" << 'MUPHP'
<?php
/**
 * FA-WPMCP Integration Test Setup
 *
 * This mu-plugin configures WordPress for integration testing:
 * - Enables Application Passwords over HTTP (normally requires HTTPS)
 * - Enables fa-wpmcp global read/write permissions
 */

// Allow Application Passwords over HTTP for testing
add_filter( 'wp_is_application_passwords_available', '__return_true' );
add_filter( 'wp_is_application_passwords_available_for_user', '__return_true' );

// Enable fa-wpmcp read/write operations
add_action( 'init', function() {
    $permissions = get_option( 'fa_wpmcp_permissions', array() );
    if ( empty( $permissions['global_write_enabled'] ) ) {
        update_option( 'fa_wpmcp_permissions', array(
            'global_read_enabled'  => true,
            'global_write_enabled' => true,
        ));
    }
}, 1 );
MUPHP
chown www-data:www-data "${WP_PATH}/wp-content/mu-plugins/fa-wpmcp-test-setup.php" 2>/dev/null || true

# Activate fa-wpmcp plugin
echo "Activating fa-wpmcp plugin..."
wp plugin activate fa-wpmcp --path="$WP_PATH" --allow-root || true

# Activate WordPress MCP adapter if available
wp plugin activate mcp-adapter --path="$WP_PATH" --allow-root 2>/dev/null || true

# Create application password for MCP testing
echo "Creating application password..."
APP_PASSWORD=$(wp user application-password create "$ADMIN_USER" "$APP_PASSWORD_NAME" \
    --path="$WP_PATH" \
    --porcelain \
    --allow-root 2>/dev/null || echo "")

if [ -z "$APP_PASSWORD" ]; then
    # Try to get existing password (can't retrieve, so create new with unique name)
    APP_PASSWORD_NAME="mcp-integration-tests-$(date +%s)"
    APP_PASSWORD=$(wp user application-password create "$ADMIN_USER" "$APP_PASSWORD_NAME" \
        --path="$WP_PATH" \
        --porcelain \
        --allow-root)
fi

# Save credentials for tests
CREDENTIALS_FILE="${WP_PATH}/.mcp-test-credentials"
cat > "$CREDENTIALS_FILE" << EOF
# MCP Integration Test Credentials
# Generated: $(date)
MCP_TEST_BASE_URL=http://localhost:8080
MCP_TEST_ENDPOINT=/wp-json/mcp/mcp-adapter-default-server
MCP_TEST_USERNAME=${ADMIN_USER}
MCP_TEST_PASSWORD=${APP_PASSWORD}
EOF

chmod 600 "$CREDENTIALS_FILE"

# Create setup marker
touch "$SETUP_MARKER"

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Test credentials saved to: $CREDENTIALS_FILE"
echo ""
cat "$CREDENTIALS_FILE"
echo ""
echo "To run integration tests:"
echo "  export \$(cat docker/wordpress-data/.mcp-test-credentials | grep -v '^#' | xargs)"
echo "  composer test:integration"
echo ""
