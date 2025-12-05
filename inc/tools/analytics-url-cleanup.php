<?php
/**
 * Analytics URL Cleanup Tool
 *
 * One-time migration to normalize historical link click URLs by removing
 * auto-generated Google Analytics parameters (_gl, _ga, _ga_*).
 * Merges duplicate rows and aggregates click counts.
 */

if (!defined('ABSPATH')) exit;

add_filter('extrachill_admin_tools', function($tools) {
    $tools[] = array(
        'id' => 'analytics-url-cleanup',
        'title' => 'Analytics URL Cleanup',
        'description' => 'Cleans up historical link click analytics by removing auto-generated Google Analytics parameters (_gl, _ga, _ga_*) and merging duplicate entries.',
        'callback' => 'ec_analytics_url_cleanup_admin_page'
    );
    return $tools;
}, 10);

/**
 * Normalizes a URL by removing Google Analytics auto-generated parameters.
 * Mirrors extrch_normalize_tracked_url() from extrachill-artist-platform.
 */
function ec_normalize_analytics_url($url) {
    if (empty($url)) {
        return $url;
    }

    $parsed = wp_parse_url($url);
    if (!isset($parsed['query']) || empty($parsed['query'])) {
        return $url;
    }

    parse_str($parsed['query'], $query_params);

    // Remove Google Analytics auto-generated parameters
    $params_to_strip = array('_gl', '_ga');
    foreach ($params_to_strip as $param) {
        unset($query_params[$param]);
    }

    // Remove any _ga_* parameters (e.g., _ga_L362LLL9KM)
    foreach (array_keys($query_params) as $key) {
        if (strpos($key, '_ga_') === 0) {
            unset($query_params[$key]);
        }
    }

    // Rebuild URL
    $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
    $host = isset($parsed['host']) ? $parsed['host'] : '';
    $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
    $path = isset($parsed['path']) ? $parsed['path'] : '';
    $query = !empty($query_params) ? '?' . http_build_query($query_params) : '';
    $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

    return $scheme . $host . $port . $path . $query . $fragment;
}

/**
 * Admin page callback for the Analytics URL Cleanup tool.
 */
function ec_analytics_url_cleanup_admin_page() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'extrch_link_page_daily_link_clicks';
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;

    if (!$table_exists) {
        echo '<div class="notice notice-error"><p>Analytics table not found. This tool requires the extrachill-artist-platform plugin to be active.</p></div>';
        return;
    }

    // Handle form submission
    if (isset($_POST['ec_analytics_cleanup_action']) && check_admin_referer('ec_analytics_url_cleanup')) {
        $action = sanitize_key($_POST['ec_analytics_cleanup_action']);

        if ($action === 'preview') {
            $results = ec_analytics_url_cleanup_preview();
            ec_analytics_url_cleanup_render_preview($results);
        } elseif ($action === 'execute') {
            $results = ec_analytics_url_cleanup_execute();
            ec_analytics_url_cleanup_render_results($results);
        }
    } else {
        // Show initial state with row counts
        $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $dirty_rows = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE link_url LIKE '%_gl=%' OR link_url LIKE '%_ga=%' OR link_url LIKE '%_ga_%'");

        echo '<div class="ec-analytics-cleanup-stats">';
        echo '<p><strong>Total click records:</strong> ' . number_format($total_rows) . '</p>';
        echo '<p><strong>Records with GA parameters:</strong> ' . number_format($dirty_rows) . '</p>';
        echo '</div>';

        if ($dirty_rows > 0) {
            echo '<form method="post">';
            wp_nonce_field('ec_analytics_url_cleanup');
            echo '<p>';
            echo '<button type="submit" name="ec_analytics_cleanup_action" value="preview" class="button">Preview Changes</button> ';
            echo '<button type="submit" name="ec_analytics_cleanup_action" value="execute" class="button button-primary" onclick="return confirm(\'This will permanently modify the analytics database. Continue?\');">Execute Cleanup</button>';
            echo '</p>';
            echo '</form>';
        } else {
            echo '<div class="notice notice-success inline"><p>No cleanup needed. All URLs are already normalized.</p></div>';
        }
    }
}

/**
 * Previews what the cleanup would do without making changes.
 */
function ec_analytics_url_cleanup_preview() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'extrch_link_page_daily_link_clicks';

    $dirty_rows = $wpdb->get_results("
        SELECT click_id, link_page_id, stat_date, link_url, click_count 
        FROM {$table_name} 
        WHERE link_url LIKE '%_gl=%' OR link_url LIKE '%_ga=%' OR link_url LIKE '%_ga_%'
        ORDER BY click_count DESC
        LIMIT 100
    ");

    $preview = array();
    foreach ($dirty_rows as $row) {
        $normalized = ec_normalize_analytics_url($row->link_url);
        $preview[] = array(
            'original' => $row->link_url,
            'normalized' => $normalized,
            'clicks' => $row->click_count,
            'date' => $row->stat_date
        );
    }

    return $preview;
}

/**
 * Executes the cleanup: normalizes URLs and merges duplicates.
 */
function ec_analytics_url_cleanup_execute() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'extrch_link_page_daily_link_clicks';

    // Get all dirty rows
    $dirty_rows = $wpdb->get_results("
        SELECT click_id, link_page_id, stat_date, link_url, click_count 
        FROM {$table_name} 
        WHERE link_url LIKE '%_gl=%' OR link_url LIKE '%_ga=%' OR link_url LIKE '%_ga_%'
    ");

    $processed = 0;
    $merged = 0;
    $deleted_ids = array();

    foreach ($dirty_rows as $row) {
        $normalized_url = ec_normalize_analytics_url($row->link_url);

        // Check if a row with the normalized URL already exists for the same page/date
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT click_id, click_count 
            FROM {$table_name} 
            WHERE link_page_id = %d 
            AND stat_date = %s 
            AND link_url = %s
        ", $row->link_page_id, $row->stat_date, $normalized_url));

        if ($existing && $existing->click_id != $row->click_id) {
            // Merge: add clicks to existing row, mark dirty row for deletion
            $wpdb->query($wpdb->prepare("
                UPDATE {$table_name} 
                SET click_count = click_count + %d 
                WHERE click_id = %d
            ", $row->click_count, $existing->click_id));

            $deleted_ids[] = $row->click_id;
            $merged++;
        } else {
            // No duplicate: just update the URL in place
            $wpdb->update(
                $table_name,
                array('link_url' => $normalized_url),
                array('click_id' => $row->click_id),
                array('%s'),
                array('%d')
            );
        }

        $processed++;
    }

    // Delete merged rows
    if (!empty($deleted_ids)) {
        $ids_placeholder = implode(',', array_map('intval', $deleted_ids));
        $wpdb->query("DELETE FROM {$table_name} WHERE click_id IN ({$ids_placeholder})");
    }

    return array(
        'processed' => $processed,
        'merged' => $merged,
        'deleted' => count($deleted_ids)
    );
}

/**
 * Renders the preview results table.
 */
function ec_analytics_url_cleanup_render_preview($results) {
    if (empty($results)) {
        echo '<div class="notice notice-info"><p>No dirty URLs found to preview.</p></div>';
        return;
    }

    echo '<h3>Preview (showing up to 100 rows)</h3>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Original URL</th><th>Normalized URL</th><th>Clicks</th><th>Date</th></tr></thead>';
    echo '<tbody>';

    foreach ($results as $row) {
        echo '<tr>';
        echo '<td style="word-break:break-all;font-size:11px;">' . esc_html($row['original']) . '</td>';
        echo '<td style="word-break:break-all;font-size:11px;"><strong>' . esc_html($row['normalized']) . '</strong></td>';
        echo '<td>' . intval($row['clicks']) . '</td>';
        echo '<td>' . esc_html($row['date']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    echo '<form method="post" style="margin-top:1em;">';
    wp_nonce_field('ec_analytics_url_cleanup');
    echo '<button type="submit" name="ec_analytics_cleanup_action" value="execute" class="button button-primary" onclick="return confirm(\'This will permanently modify the analytics database. Continue?\');">Execute Cleanup</button>';
    echo '</form>';
}

/**
 * Renders the execution results.
 */
function ec_analytics_url_cleanup_render_results($results) {
    echo '<div class="notice notice-success">';
    echo '<p><strong>Cleanup Complete</strong></p>';
    echo '<ul>';
    echo '<li>Rows processed: ' . number_format($results['processed']) . '</li>';
    echo '<li>Rows merged into existing: ' . number_format($results['merged']) . '</li>';
    echo '<li>Duplicate rows deleted: ' . number_format($results['deleted']) . '</li>';
    echo '</ul>';
    echo '</div>';
}
