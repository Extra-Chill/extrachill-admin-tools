# Extra Chill Admin Tools

WordPress plugin providing centralized administrative tools for the Extra Chill platform multisite network.

## Current Implementation

**Status**: Production plugin with 9 tabbed tools
**Location**: Tools > Admin Tools (administrator-only access)
**Architecture**: Filter-based tool registration with tabbed navigation and conditional loading

### Core Structure
- `extrachill-admin-tools.php` - Main plugin file loading 9 tool files
- `inc/admin/admin-settings.php` - Tabbed admin interface with JavaScript tab switching
- `inc/tools/` - Individual tool implementations with filter and admin notice patterns

## Tabbed Tools

### Tag Migration (`inc/tools/tag-migration.php`)
Bulk migrate tags to festival, artist, or venue taxonomies with search, pagination, and reporting.

### 404 Error Logger (`inc/tools/404-error-logger.php`)
Logs 404 errors with daily email reports. Custom `404_log` table with varchar(2000) fields for url and referrer (supports long URLs with query parameters). Automatic cleanup after email sent. Excludes `/event/` URLs from logging. Safety truncation at 1990 characters prevents database errors.

### Artist-User Relationships (`inc/tools/artist-user-relationships.php`)
**Conditional**: Requires extrachill-artist-platform plugin. Comprehensive management interface with three views (Artists, Users, Orphans). AJAX-powered relationship management with add/remove capabilities. Detects and cleans orphaned relationships.

### Team Member Management (`inc/tools/team-member-management.php`)
**Conditional**: Requires extrachill-users plugin. Syncs team member status from main site (extrachill.com) with manual override support. Search and pagination for user management. AJAX sync with real-time reporting.

### Artist Ownership Repair (`inc/tools/artist-ownership-repair.php`)
Repairs ownership relationships between artists and users. Ensures data integrity in the artist platform ecosystem.

### Artist Forum Repair (`inc/tools/artist-forum-repair.php`)
Repairs and synchronizes artist forum relationships. Maintains forum data consistency for artist profiles.

### QR Code Generator (`inc/tools/qr-code-generator.php`)
Universal QR code generator for any URL (internal or external). Generates high-resolution print-ready QR codes using Endroid QR Code library. AJAX-powered with real-time preview and download functionality.

### Ad-Free License Management (`inc/tools/ad-free-license-management.php`)
Grant, revoke, and manage ad-free licenses for platform users. Search users, view current license holders, and manage licenses with AJAX interface. Integrates with extrachill-multisite ad-free license system.

### Taxonomy Sync (`inc/tools/taxonomy-sync.php`)
Synchronize taxonomies from main site (extrachill.com) to other network sites. Preserves existing terms and their metadata while syncing new additions. Supports location, festival, artist, and venue taxonomies with selective site targeting.

## Architecture

### Tool Registration Patterns

**Filter-Based Tabbed Tools** (`extrachill_admin_tools` filter):
```php
add_filter('extrachill_admin_tools', function($tools) {
    $tools[] = array(
        'id' => 'tool-id',
        'title' => 'Tool Title',
        'description' => 'Tool description text',
        'callback' => 'tool_callback_function'
    );
    return $tools;
}, 10);
```

### Conditional Loading

**Site-Specific Loading Pattern**:
```php
// Hardcoded blog IDs for performance
$artist_blog_id = 4; // artist.extrachill.com
if (get_current_blog_id() === $artist_blog_id) {
    // Register tool
}
```

**Plugin Dependency Loading** (artist-user-relationships, team-member-management):
```php
if (is_plugin_active('plugin-name/plugin-name.php')) {
    add_filter('extrachill_admin_tools', function($tools) {
        // Register tool
    });
}
```

### Tabbed Navigation System

Admin interface (`inc/admin/admin-settings.php`) implements tabbed navigation:
- Vanilla JavaScript tab switching (no jQuery)
- URL hash support for deep linking
- Active tab state management
- Automatic first-tab activation on load
- Each tab renders via registered callback function

### AJAX Patterns

**Migration Tools AJAX Flow**:
```php
// AJAX handler registration
add_action('wp_ajax_tool_action', 'tool_ajax_handler');

// Security checks
check_ajax_referer('tool_nonce', 'nonce');
if (!current_user_can('manage_options')) {
    wp_send_json_error('Unauthorized');
}

// Return structured JSON response
wp_send_json_success(array(
    'message' => 'Operation complete',
    'breakdown' => $counts
));
```

**Used in**: artist-user-relationships (add/remove), team-member-management (sync/manage), qr-code-generator (generate), ad-free-license-management (grant/revoke), taxonomy-sync (sync)

## Development

### Commands
```bash
composer install && composer run lint:php  # Install deps and lint
composer run lint:fix                      # Fix coding standards
composer test                              # Run tests
./build.sh                                 # Create production ZIP
```

### Build System
- **Universal Build Script**: Symlinked to shared build script at `../../.github/build.sh`
- **Auto-Detection**: Script auto-detects plugin from `Plugin Name:` header
- **Production Build**: Creates `/build/extrachill-admin-tools/` directory and `/build/extrachill-admin-tools.zip` file (non-versioned)
- **File Exclusion**: `.buildignore` rsync patterns exclude development files
- **Composer Integration**: Uses `composer install --no-dev` for production, restores dev dependencies after

## Security

- Administrator-only access with `manage_options` capability checks throughout
- WordPress nonce system for CSRF protection on all forms and AJAX requests
- Input sanitization with `sanitize_text_field()`, `absint()`, `esc_url_raw()`
- Output escaping with `esc_html()`, `esc_attr()`, `esc_url()`
- Prepared database statements for all dynamic queries
- AJAX handlers with nonce verification via `check_ajax_referer()` and `wp_verify_nonce()`
- Double confirmation prompts for destructive operations (migrations, cleanups)