# Extra Chill Admin Tools

WordPress plugin providing centralized administrative tools for the Extra Chill platform multisite network.

## Current Implementation

**Status**: Production plugin with 10 tools (7 tabbed + 2 admin notices + 1 admin interface)
**Location**: Tools > Admin Tools (administrator-only access)
**Architecture**: Filter-based tool registration with tabbed navigation and conditional loading

### Core Structure
- `extrachill-admin-tools.php` - Main plugin file loading 10 tool files
- `inc/admin/admin-settings.php` - Tabbed admin interface with JavaScript tab switching
- `inc/tools/` - Individual tool implementations with filter and admin notice patterns

## Tabbed Tools

### Tag Migration (`inc/tools/tag-migration.php`)
Bulk migrate tags to festival, artist, or venue taxonomies with search, pagination, and reporting.

### 404 Error Logger (`inc/tools/404-error-logger.php`)
Logs 404 errors with daily email reports. Custom `404_log` table created on activation with automatic cleanup. Excludes `/event/` URLs from logging.

### Artist-User Relationships (`inc/tools/artist-user-relationships.php`)
**Conditional**: Requires extrachill-artist-platform plugin. Comprehensive management interface with three views (Artists, Users, Orphans). AJAX-powered relationship management with add/remove capabilities. Detects and cleans orphaned relationships.

### Team Member Management (`inc/tools/team-member-management.php`)
**Conditional**: Requires extrachill-users plugin. Syncs team member status from main site (extrachill.com) with manual override support. Search and pagination for user management. AJAX sync with real-time reporting.

### Artist Ownership Repair (`inc/tools/artist-ownership-repair.php`)
Repairs ownership relationships between artists and users. Ensures data integrity in the artist platform ecosystem.

### Artist Forum Repair (`inc/tools/artist-forum-repair.php`)
Repairs and synchronizes artist forum relationships. Maintains forum data consistency for artist profiles.

### Migrate Avatars (`inc/tools/migrate-avatars.php`)
Migrates user avatars to the custom avatar system. One-time migration tool for transitioning to the centralized avatar management.

## Admin Notice Tools

### Session Token Cleanup (`inc/tools/session-token-cleanup.php`)
Admin notice displaying when legacy session token tables exist. AJAX table removal with double confirmation and dismissible notice.

### Image Votes Cleanup (`inc/tools/image-votes-cleanup.php`)
Admin notice for removing legacy `image_votes` database table after migration to WordPress native block attributes system. Dismissible with AJAX cleanup handler.

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

**Admin Notice Tools** (no filter registration):
```php
add_action('admin_notices', 'tool_notice_function');
add_action('wp_ajax_tool_action', 'tool_ajax_handler');
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

**Used in**: artist-platform-migration (migrate/cleanup), artist-user-relationships (add/remove), team-member-management (sync/manage), image-votes-cleanup (cleanup)

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