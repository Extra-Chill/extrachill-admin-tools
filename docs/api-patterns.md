# Admin Tools - API Patterns & Database Schemas

## React-Based Architecture (Version 2.0.0+)

Since version 2.0.0, the admin tools have migrated to a modern React-based architecture. 

### Centralized API Client

All tool components utilize a shared `apiClient` for interacting with the `extrachill-api` REST endpoints. This ensures consistent authentication, error handling, and URL resolution across the interface.

### Shared UI Components

- **DataTable**: Standardized list view with pagination and sorting.
- **UserSearch**: Modal-based user lookup with real-time results.
- **Modal**: Consistent dialog interface for actions and confirmations.
- **Pagination**: Integrated with REST response headers (`X-WP-Total`, `X-WP-TotalPages`).

## REST API Patterns (extrachill-api)

Administrative tools consume endpoints under the `extrachill/v1/admin/` and `extrachill/v1/users/` namespaces.

### Standard Request Security
All requests include the `X-WP-Nonce` header for authentication and verification.

### List Endpoint Pattern
Admin listing endpoints support:
- `page`: Current page (1-based)
- `per_page`: Number of items per page (default 10)
- `search`: Keyword search across relevant fields

### Response Format
Responses use standard REST formats, with pagination metadata provided in response headers.

## Legacy AJAX Patterns (Deprecated)

Many tools have been migrated from the legacy AJAX patterns below to the React/REST architecture.

## Database Schemas

### 404_log Table

Already documented in [404-error-logger.md](tools/404-error-logger.md)

### Database scope note

This document only includes schemas that exist in this plugin/codebase.

- Network-wide tables use `$wpdb->base_prefix` (shared across all sites).
- Per-site tables use `$wpdb->prefix` (scoped to a single site).

**Custom tables this plugin creates**:
- `{base_prefix}404_log` (documented in [404-error-logger.md](tools/404-error-logger.md))

Other admin tools (lifetime membership, team members, taxonomy sync, etc.) use WordPress core tables and/or data owned by other plugins. This plugin does not create additional custom tables for those features.

## Common Database Queries

### Count Posts Affected by Migration

```php
// How many posts have a specific tag?
$tag_id = 123;
$count = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(DISTINCT p.ID)
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
         WHERE tr.term_taxonomy_id = %d",
        $tag_id
    )
);
```

### Find Posts with Specific Meta Value

```php
// Find all artists linked to a user
$user_id = 456;
$artists = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT p.ID FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'artist_profile'
         AND pm.meta_key = '_artist_members'
         AND pm.meta_value LIKE %s",
        '%"' . $wpdb->esc_like($user_id) . '"%'
    )
);
```

### Batch Update Posts

```php
// Reassign posts from deleted author to new author
$old_author_id = 123;
$new_author_id = 1; // admin

$wpdb->query(
    $wpdb->prepare(
        "UPDATE {$wpdb->posts}
         SET post_author = %d
         WHERE post_author = %d",
        $new_author_id,
        $old_author_id
    )
);
```

### Detect Orphaned Data

```php
// Find postmeta entries referencing non-existent posts
$orphaned_meta = $wpdb->get_results(
    "SELECT pm.meta_id, pm.post_id, pm.meta_key
     FROM {$wpdb->postmeta} pm
     LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
     WHERE p.ID IS NULL
     LIMIT 100"
);

// Delete orphaned meta
foreach ($orphaned_meta as $meta) {
    delete_metadata_by_mid('post', $meta->meta_id);
}
```

## Error Handling Patterns

### Try/Catch with Rollback

```php
try {
    // Start transaction-like pattern
    global $wpdb;
    $wpdb->query('START TRANSACTION');
    
    // Perform migrations
    foreach ($items as $item) {
        if (!perform_operation($item)) {
            throw new Exception('Operation failed on item: ' . $item->id);
        }
    }
    
    $wpdb->query('COMMIT');
    
    wp_send_json_success(array('message' => 'All operations completed'));
} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
    error_log('Migration error: ' . $e->getMessage());
    wp_send_json_error(array('message' => $e->getMessage()));
}
```

### Validation Before Operation

```php
// Always validate before destructive operations
function validate_migration_request($tag_ids, $target_taxonomy) {
    if (empty($tag_ids)) {
        return new WP_Error('empty_tags', 'No tags selected');
    }
    
    if (!taxonomy_exists($target_taxonomy)) {
        return new WP_Error('bad_taxonomy', 'Target taxonomy does not exist');
    }
    
    // Verify all tags exist
    foreach ($tag_ids as $tag_id) {
        $term = get_term($tag_id);
        if (is_wp_error($term) || !$term) {
            return new WP_Error('invalid_tag', 'Tag ' . $tag_id . ' does not exist');
        }
    }
    
    return true;
}
```

## Performance Optimization

### Batch Processing Large Datasets

```php
// Process 100 items per request to avoid timeout
$page = isset($_POST['page']) ? absint($_POST['page']) : 1;
$items_per_page = 100;
$offset = ($page - 1) * $items_per_page;

$items = $wpdb->get_results(
    "SELECT id FROM some_table LIMIT $items_per_page OFFSET $offset"
);

foreach ($items as $item) {
    process_item($item->id);
}

$total = $wpdb->get_var('SELECT COUNT(*) FROM some_table');
$pages = ceil($total / $items_per_page);

wp_send_json_success(array(
    'page' => $page,
    'total_pages' => $pages,
    'processed' => count($items)
));
```

### Cache Query Results

```php
// Cache frequently used queries
function get_artist_members($artist_id) {
    $cache_key = 'artist_members_' . $artist_id;
    $members = wp_cache_get($cache_key);
    
    if (false === $members) {
        $members = get_post_meta($artist_id, '_artist_members', true);
        wp_cache_set($cache_key, $members, '', 1 * HOUR_IN_SECONDS);
    }
    
    return $members;
}
```

## Security Best Practices

### Never Trust User Input

```php
// ❌ WRONG - Vulnerable to SQL injection
$wpdb->query("SELECT * FROM table WHERE id = {$_POST['id']}");

// ✅ CORRECT - Use prepared statements
$wpdb->prepare("SELECT * FROM table WHERE id = %d", absint($_POST['id']));
```

### Always Escape Output

```php
// ❌ WRONG - XSS vulnerability
echo '<div>' . $_POST['name'] . '</div>';

// ✅ CORRECT - Escape based on context
echo '<div>' . esc_html($_POST['name']) . '</div>';
echo '<input value="' . esc_attr($_POST['name']) . '">';
echo '<a href="' . esc_url($_POST['url']) . '">Link</a>';
```

### Verify Nonces & Capabilities

```php
// ❌ WRONG - No security checks
function handle_tool_action() {
    // Process request without verification
}

// ✅ CORRECT - Full security layer
function handle_tool_action() {
    check_ajax_referer('tool_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    // Process request safely
}
```
