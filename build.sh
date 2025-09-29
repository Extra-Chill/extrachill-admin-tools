#!/bin/bash

# Extra Chill Admin Tools - Production Build Script
# Creates optimized ZIP package for WordPress deployment

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

PLUGIN_FILE="extrachill-admin-tools.php"
BUILD_DIR="dist"

# Extract plugin info
if [[ ! -f "$PLUGIN_FILE" ]]; then
    echo -e "${RED}Error: Plugin file $PLUGIN_FILE not found${NC}"
    exit 1
fi

PLUGIN_NAME=$(grep "Plugin Name:" "$PLUGIN_FILE" | sed 's/.*Plugin Name: *\(.*\)/\1/' | tr -d '\r')
VERSION=$(grep "Version:" "$PLUGIN_FILE" | sed 's/.*Version: *\(.*\)/\1/' | tr -d '\r')
PLUGIN_SLUG="extrachill-admin-tools"

if [[ -z "$VERSION" ]]; then
    echo -e "${RED}Error: Could not extract version from $PLUGIN_FILE${NC}"
    exit 1
fi

echo -e "${GREEN}Building: $PLUGIN_NAME v$VERSION${NC}"

# Clean previous builds
echo "Cleaning previous builds..."
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# Install production dependencies
echo "Installing production dependencies..."
if [[ -f "composer.json" ]]; then
    composer install --no-dev --optimize-autoloader --quiet
    echo -e "${GREEN}✓ Composer dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠ No composer.json found, skipping Composer install${NC}"
fi

# Create plugin directory structure
PLUGIN_BUILD_DIR="$BUILD_DIR/$PLUGIN_SLUG"
mkdir -p "$PLUGIN_BUILD_DIR"

# Copy plugin files (exclude patterns from .buildignore)
echo "Copying plugin files..."

# Read .buildignore patterns
EXCLUDE_PATTERNS=""
if [[ -f ".buildignore" ]]; then
    while IFS= read -r line; do
        # Skip empty lines and comments
        if [[ -n "$line" && ! "$line" =~ ^[[:space:]]*# ]]; then
            EXCLUDE_PATTERNS="$EXCLUDE_PATTERNS --exclude=$line"
        fi
    done < ".buildignore"
fi

# Copy files with exclusions
rsync -av $EXCLUDE_PATTERNS \
    --exclude="$BUILD_DIR" \
    ./ "$PLUGIN_BUILD_DIR/"

# Validate essential plugin files
echo "Validating plugin structure..."
REQUIRED_FILES=(
    "$PLUGIN_FILE"
    "inc/admin/admin-settings.php"
    "inc/tools/tag-migration.php"
    "inc/tools/404-error-logger.php"
    "inc/tools/festival-wire-migration.php"
    "inc/tools/session-token-cleanup.php"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [[ ! -f "$PLUGIN_BUILD_DIR/$file" ]]; then
        echo -e "${RED}Error: Required file $file is missing from build${NC}"
        exit 1
    fi
done

echo -e "${GREEN}✓ Plugin structure validated${NC}"

# Create ZIP file
ZIP_NAME="$PLUGIN_SLUG-$VERSION.zip"
ZIP_PATH="$BUILD_DIR/$ZIP_NAME"

echo "Creating ZIP package: $ZIP_NAME"
cd "$BUILD_DIR"
zip -r -q "$ZIP_NAME" "$PLUGIN_SLUG"
cd "$SCRIPT_DIR"

# Get ZIP size
ZIP_SIZE=$(du -h "$ZIP_PATH" | cut -f1)

echo -e "${GREEN}✓ Build completed successfully!${NC}"
echo ""
echo "📦 Package: $ZIP_PATH"
echo "📏 Size: $ZIP_SIZE"
echo "🎯 Ready for WordPress deployment"

# Restore development dependencies
if [[ -f "composer.json" ]]; then
    echo ""
    echo "Restoring development dependencies..."
    composer install --quiet
    echo -e "${GREEN}✓ Development environment restored${NC}"
fi

echo ""
echo -e "${GREEN}Build process complete! 🚀${NC}"