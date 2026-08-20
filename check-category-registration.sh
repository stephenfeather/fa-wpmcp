#!/bin/bash

# Script to check if category registration is working
# Usage: ./check-category-registration.sh [wordpress-debug-log-path]

set -e

echo "=== FA WPMCP Category Registration Checker ==="
echo ""

# Default debug log location
DEBUG_LOG="${1:-wp-content/debug.log}"

if [ ! -f "$DEBUG_LOG" ]; then
    echo "❌ Debug log not found at: $DEBUG_LOG"
    echo ""
    echo "To enable WordPress debug logging, add to wp-config.php:"
    echo ""
    echo "define('WP_DEBUG', true);"
    echo "define('WP_DEBUG_LOG', true);"
    echo "define('WP_DEBUG_DISPLAY', false);"
    echo ""
    exit 1
fi

echo "📋 Checking debug log: $DEBUG_LOG"
echo ""

echo "=== Plugin Load Sequence ==="
grep "FA WPMCP:" "$DEBUG_LOG" | tail -50
echo ""

echo "=== Category Registration Check ==="
if grep -q "Finished registering 9 categories" "$DEBUG_LOG"; then
    echo "✅ Category registration completed"
else
    echo "❌ Category registration did NOT complete"
fi
echo ""

echo "=== Category Verification ==="
if grep -q "privacy: YES" "$DEBUG_LOG"; then
    echo "✅ Privacy category IS registered"
else
    echo "❌ Privacy category NOT registered"
fi
echo ""

echo "=== DoingItWrong Warnings ==="
if grep -i "doingit.*privacy" "$DEBUG_LOG" | tail -5; then
    echo "❌ FOUND DoingItWrong warnings for privacy category"
else
    echo "✅ No DoingItWrong warnings found"
fi
echo ""

echo "=== Recent Log Entries (last 20) ==="
grep "FA WPMCP:" "$DEBUG_LOG" | tail -20
