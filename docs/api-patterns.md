# Admin Tools - API Patterns & Database Schemas

## Common AJAX Patterns

All admin tools using AJAX follow consistent security and data handling patterns.

### AJAX Request Security

**Nonce Verification** (required on all AJAX requests):
```javascript
// Frontend: Send nonce in request
fetch(ecAdminTools.ajaxUrl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
        'action': 'tool_action_name',
        'nonce': ecAdminTools.nonce,
        'data': JSON.stringify(toolData)
    })
});
```

**Backend: Verify nonce before processing**:
```php
add_action('wp_ajax_tool_action_name', function() {
    check_ajax_referer('tool_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    // Process request
});
```

### Standard Response Format

**Success Response**:
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        "count": 42,
        "affected_items": ["item1", "item2"]
    }
}
```

**Error Response**:
```json
{
    "success": false,
    "message": "Detailed error message for admin",
    "code": "error_identifier"
}
```

### Input Sanitization Pattern

```php
// Always sanitize and unslash user input FIRST
$search_term = sanitize_text_field(wp_unslash($_POST['search'] ?? ''));
$page = absint($_POST['page'] ?? 1);
$taxonomy = sanitize_key($_POST['taxonomy'] ?? 'post_tag');
$ids = array_map('absint', $_POST['ids'] ?? []);

// Use sanitized values in operations
$results = get_terms(array(
    'taxonomy' => $taxonomy,
    'search' => $search_term,
    'paged' => $page
));
```

## REST API Patterns

The QR Code Generator uses REST API instead of admin-ajax for modern endpoint handling.

### REST Endpoint Registration

**Endpoint**: `POST /wp-json/extrachill/v1/tools/qr-code`

**Handler** (in extrachill-api plugin):
```php
register_rest_route('extrachill/v1', '/tools/qr-code', array(
    'methods' => 'POST',
    'callback' => 'ec_qr_code_generate',
    'permission_callback' => function() {
        return current_user_can('manage_options');
    }
));
```

### REST Request/Response

**Request Body**:
```json
{
    "url": "https://extrachill.link/artist-name"
}
```

**Response** (Success):
```json
{
    "success": true,
    "qr_code": "data:image/png;base64,iVBORw0KGgo...",
    "size": "1000x1000"
}
```

**Response** (Error):
```json
{
    "success": false,
    "message": "Invalid URL format"
}
```

## Database Schemas

### 404_log Table

Already documented in [404-error-logger.md](tools/404-error-logger.md)

### Ad-Free License Table (if custom)

If licenses stored in custom table rather than post meta:

```sql
CREATE TABLE wp_ad_free_licenses (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    license_key varchar(100) NOT NULL UNIQUE,
    purchase_date datetime DEFAULT NULL,
    expiration_date datetime DEFAULT NULL,
    status varchar(20) DEFAULT 'active',
    PRIMARY KEY (id),
    INDEX user_id_idx (user_id),
    INDEX license_key_idx (license_key),
    INDEX status_idx (status),
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
)
```

### Subscriptions Table (if used)

For email subscription tracking:

```sql
CREATE TABLE wp_ec_subscriptions (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20),
    email varchar(100) NOT NULL,
    subscription_type varchar(50) NOT NULL,
    subscribed_date datetime DEFAULT CURRENT_TIMESTAMP,
    status varchar(20) DEFAULT 'subscribed',
    PRIMARY KEY (id),
    INDEX email_idx (email),
    INDEX status_idx (status),
    UNIQUE KEY user_subscription (user_id, subscription_type)
)
```

### Helper Functions for Querying

**Get license status for user**:
```php
function ec_user_has_ad_free_license($user_id) {
    $license = get_user_meta($user_id, '_ad_free_license', true);
    
    if (!$license) {
        return false;
    }
    
    if (isset($license['expiration']) && strtotime($license['expiration']) < time()) {
        return false; // Expired
    }
    
    return true;
}
```

**Get all active licenses**:
```php
global $wpdb;
$licenses = $wpdb->get_results(
    "SELECT user_id, license_key, expiration_date
     FROM {$wpdb->prefix}ad_free_licenses
     WHERE status = 'active'
     AND (expiration_date IS NULL OR expiration_date > NOW())"
);
```

**Get subscription emails by type**:
```php
global $wpdb;
$newsletter_emails = $wpdb->get_col(
    "SELECT email FROM {$wpdb->prefix}ec_subscriptions
     WHERE subscription_type = 'newsletter'
     AND status = 'subscribed'
     ORDER BY subscribed_date DESC"
);
```

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
