# Extra Chill Admin Tools

WordPress plugin providing centralized administrative tools for the Extra Chill platform multisite network.

## Current Implementation

**Status**: Production plugin with React-based tabbed tools (Version 2.0.0+)
**Location**: Network Admin > Tools > Admin Tools (network administrator-only access)
**Architecture**: React application (`src/admin-tools.js`) with shared components (`DataTable`, `Modal`, `UserSearch`) and tool-specific components. Interacts with the platform via REST API endpoints provided by `extrachill-api`.

## React Tool Components (`src/tools/`)

### ArtistUserRelationships.jsx
**Conditional**: Requires `extrachill-artist-platform` plugin. Comprehensive management interface using the shared `UserSearch` component for adding new relationships. Detects and cleans orphaned relationships.

### LifetimeMemberships.jsx
Grant, revoke, and manage Lifetime Extra Chill Memberships (ad-free) for platform users. Search users and manage memberships via the React interface.

### TeamMemberManagement.jsx
Synchronize and manage network team member status.

### ArtistAccessRequests.jsx
Manage artist access approval and rejection requests.

### TagMigration.jsx
Bulk migrate tags to festival, artist, or venue taxonomies with real-time progress tracking.

### TaxonomySync.jsx
Synchronize taxonomies from main site (`extrachill.com`) to other network sites.

### ErrorLogger.jsx
Administrative interface for the 404 Error Logger system.

### QRCodeGenerator.jsx
Universal QR code generator using the `extrachill-api` REST endpoint.

### ForumTopicMigration.jsx
Migrate forum topics between forums.

## Core Structure
- `extrachill-admin-tools.php` - Main plugin file, initializes 404 logger and React admin page
- `inc/admin/admin-settings.php` - Network admin menu registration and React mount point
- `inc/tools/404-error-logger.php` - Logging and cron functionality for 404 tracking
- `src/components/` - React components for the admin interface
  - `shared/` - Reusable UI components (`DataTable`, `Modal`, `UserSearch`, `Pagination`)
- `src/tools/` - Individual tool components (React)

## 404 Error Logger System (`inc/tools/404-error-logger.php`)
Logs 404 errors with daily email reports. Custom `404_log` table with varchar(2000) fields for url and referrer (supports long URLs with query parameters). Automatic cleanup after email sent. Excludes `/event/` URLs from logging. Safety truncation at 1990 characters prevents database errors. The administrative UI is provided by `ErrorLogger.jsx`.

## Architecture (Version 2.0.0+)

### Modern React/REST Pattern
Since version 2.0.0, the plugin has shifted from legacy PHP/AJAX patterns to a modern React-based architecture.

**Key Architectural Changes**:
- **Single Page Application**: The admin interface is a React SPA mounted on a single WordPress admin page.
- **REST API Consumption**: All data operations are performed via `extrachill-api` REST endpoints.
- **Shared UI Library**: Reusable components ensure visual and functional consistency across all tools.
- **Centralized API Client**: A shared client handles nonces, base URLs, and error states.

### Legacy Patterns (Deprecated)
Legacy PHP-based tool registration via the `extrachill_admin_tools` filter and individual AJAX handlers are deprecated in favor of React tool components and REST endpoints.


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

### Legacy AJAX Patterns (Deprecated)

This plugin’s current production implementation uses `extrachill-api` REST endpoints. Older `wp_ajax_*` patterns are deprecated and should not be used for new tooling.

### REST API Pattern

**QR Code Generator** uses the extrachill-api REST endpoint instead of admin-ajax:
```javascript
fetch(ecQrCodeGen.restUrl, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': ecQrCodeGen.nonce
    },
    body: JSON.stringify({ url: url })
})
```

## Development

### Commands
```bash
composer install                           # Install deps
homeboy lint extrachill-admin-tools        # Lint code
homeboy lint extrachill-admin-tools --fix  # Fix coding standards
homeboy test extrachill-admin-tools        # Run tests
homeboy build extrachill-admin-tools       # Create production ZIP
```

### Build System
- **Build System**: Use `homeboy build extrachill-admin-tools` for production builds
- **Production Build**: Creates `/build/extrachill-admin-tools.zip` file (non-versioned; unzip when directory access is needed)
- **File Exclusion**: `.buildignore` rsync patterns exclude development files
- **Composer Integration**: Uses `composer install --no-dev` for production, restores dev dependencies after

## Security

- Administrator-only access with `manage_options` capability checks throughout
- WordPress nonce system for CSRF protection on all requests
- Input sanitization with `sanitize_text_field()`, `absint()`, `esc_url_raw()`
- Output escaping with `esc_html()`, `esc_attr()`, `esc_url()`
- Prepared database statements for all dynamic queries
- Double confirmation prompts for destructive operations (migrations, cleanups)

## Data Relationships

### Table Dependency Graph

The plugin manages relationships across multiple database tables:

```
wp_posts (Posts)
  ├─ wp_postmeta (_artist_members, _artist_owner)
  │   └─ links to wp_users.ID
  │
  └─ wp_term_relationships
      └─ wp_term_taxonomy (artist, venue, festival taxonomies)

wp_users (Users)
  ├─ wp_usermeta (_artist_associations)
  │   └─ links to wp_posts.ID
  │
  └─ wp_posts (post_author field)
      └─ Artist profile posts

wp_404_log (Custom table)
  └─ blog_id, url, referrer tracking

wp_*_postmeta (per-site)
  └─ Ad-free license keys, subscriptions
```

### Data Integrity Constraints

**Artist-User Relationships**:
- `wp_postmeta._artist_members` contains serialized user ID array
- User IDs must exist in `wp_users` table
- Artist posts must exist in `wp_posts` with post_type='artist_profile'
- Orphans View detects and repairs violations

**Taxonomy Migrations**:
- `wp_term_taxonomy.taxonomy` column indicates current taxonomy
- `wp_term_relationships` links remain unchanged during migration
- `wp_postmeta` terms per post unaffected (taxonomy change only)

**404 Logging**:
- `wp_404_log.blog_id` must be valid network site
- `wp_404_log.time` indexed for daily cleanup queries
- `wp_404_log.url` truncated at 1990 chars for safety

## Testing Patterns for Complex Migrations

### Pre-Migration Checklist

```php
function validate_migration_integrity() {
    // 1. Count affected posts
    $post_count = get_posts(['post_type' => 'any', 'numberposts' => -1]);
    
    // 2. Verify database backup exists
    if (!function_exists('backup_database')) {
        error_log('No backup function available');
    }
    
    // 3. Test on single item first
    $test_item = array_slice($items, 0, 1);
    $result = perform_migration($test_item);
    
    if ($result === false) {
        error_log('Test migration failed');
        return false;
    }
    
    // 4. Verify results match expected
    verify_post_counts($post_count);
    verify_taxonomy_assignments();
    
    return true;
}
```

### Safe Migration Pattern

```php
// Always use atomic operations with try/finally
try {
    global $wpdb;
    
    // Log baseline state
    $before = get_posts(['post_type' => 'any', 'numberposts' => -1]);
    
    // Perform migration in batches
    foreach (array_chunk($items, 50) as $batch) {
        foreach ($batch as $item) {
            if (!perform_operation($item)) {
                throw new Exception('Operation failed on item ' . $item->id);
            }
        }
        // Flush cache after each batch
        wp_cache_flush();
    }
    
    // Verify post-migration state
    $after = get_posts(['post_type' => 'any', 'numberposts' => -1]);
    
    if (count($before) !== count($after)) {
        throw new Exception('Post count mismatch: before=' . count($before) . ', after=' . count($after));
    }
    
    return true;
} finally {
    // Cleanup temporary data
    delete_transient('migration_in_progress');
    wp_cache_flush();
}
```

### Post-Migration Verification

```php
function verify_migration_success() {
    global $wpdb;
    
    // 1. Check all tagged posts are accessible
    $orphaned_term_relationships = $wpdb->get_results(
        "SELECT tr.* FROM {$wpdb->term_relationships} tr
         LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
         WHERE p.ID IS NULL"
    );
    
    if (!empty($orphaned_term_relationships)) {
        error_log('Found orphaned term relationships: ' . count($orphaned_term_relationships));
        return false;
    }
    
    // 2. Verify taxonomy counts match
    $tags = get_tags(['hide_empty' => false]);
    foreach ($tags as $tag) {
        $count = get_option('extrachill_post_tag_count_' . $tag->term_id);
        if ($count !== $tag->count) {
            error_log('Tag count mismatch for tag ' . $tag->term_id);
        }
    }
    
    return true;
}
```

## How to Add New Tools to the System

### 1. Create Tool File

Create a new file in `inc/tools/tool-name.php`:

```php
<?php
if (!defined('ABSPATH')) {
    exit;
}

// Load conditional dependencies
if (!some_required_plugin_active()) {
    return; // Don't load this tool
}

// Register tool via filter
add_filter('extrachill_admin_tools', function($tools) {
    $tools[] = array(
        'id'          => 'tool-id',
        'title'       => 'Tool Title',
        'description' => 'What does this tool do?',
        'callback'    => 'ec_tool_render'
    );
    return $tools;
}, 20); // Priority: 20 loads before default tools (priority 10)

// Render function displays tool interface
function ec_tool_render() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    // Render HTML interface
    ?>
    <div class="ec-tool-wrapper">
        <!-- Tool content -->
    </div>
    <?php
}

// AJAX handler (if needed)
add_action('wp_ajax_tool_action', function() {
    check_ajax_referer('tool_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    // Process request
    wp_send_json_success(['message' => 'Done']);
});
```

### 2. Include in Main Plugin File

Add `require_once` in `extrachill-admin-tools.php`:

```php
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/tool-name.php';
```

### 3. Create Assets (if needed)

**CSS**: `assets/css/tool-name.css` (loaded via `admin_enqueue_scripts` hook)

**JavaScript**: `assets/js/tool-name.js` (uses `ecAdminTools.ajaxUrl` and `ecAdminTools.nonce`)

### 4. Follow These Patterns

- **Nonces**: Use `wp_create_nonce('tool_nonce')` and verify with `check_ajax_referer()`
- **Sanitization**: Always use `wp_unslash()` THEN type-specific sanitizers
- **Escaping**: Context-aware escaping (esc_html, esc_attr, esc_url)
- **Responses**: Return consistent JSON format with 'success' and 'message' keys
- **Errors**: Log to error_log, display user-friendly messages in UI
- **Database**: Use `$wpdb->prepare()` for all queries
- **Conditional Loading**: Check dependencies exist before registering
- **Capabilities**: Verify 'manage_options' or appropriate capability
- **Cache Busting**: Use `filemtime()` for CSS/JS versions