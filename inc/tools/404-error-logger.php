<?php
if (!defined('ABSPATH')) exit;

add_filter('extrachill_admin_tools', function($tools) {
    $tools[] = array(
        'id' => '404-error-logger',
        'title' => '404 Error Logger',
        'description' => 'Logs 404 errors and sends daily email reports. Automatically cleans up data after sending.',
        'callback' => 'error_404_logger_toggle'
    );
    return $tools;
}, 20);

function error_404_logger_toggle() {
    $enabled = get_site_option('extrachill_404_logger_enabled', 1);

    if (isset($_POST['toggle_404_logger']) && check_admin_referer('404_logger_toggle')) {
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        update_site_option('extrachill_404_logger_enabled', $enabled);
        echo '<div class="notice notice-success"><p>404 Logger ' . ($enabled ? 'enabled' : 'disabled') . ' network-wide.</p></div>';
    }

    echo '<form method="post">';
    wp_nonce_field('404_logger_toggle');
    echo '<p><label><input type="checkbox" name="enabled" value="1" ' . checked($enabled, 1, false) . '> Enable 404 Error Logging (Network-Wide)</label></p>';
    echo '<p><input type="submit" name="toggle_404_logger" class="button" value="Save Setting"></p>';
    echo '</form>';

    if ($enabled) {
        global $wpdb;
        $table_name = $wpdb->base_prefix . '404_log';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(time) = CURDATE()");
        echo '<p><strong>Today\'s 404 errors across all sites:</strong> ' . intval($count) . '</p>';
    }
}

if (get_site_option('extrachill_404_logger_enabled', 1)) {

    /**
     * Logs 404 errors, excluding /event/ URLs which are expected 404s from calendar plugin
     */
    function log_404_errors() {
        if (is_404()) {
            $url = esc_url($_SERVER['REQUEST_URI']);
            if (preg_match('/^\/event\//', $url)) {
                return;
            }

            global $wpdb;
            $table_name = $wpdb->base_prefix . '404_log';

            $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            $time = current_time('mysql');

            $result = $wpdb->insert(
                $table_name,
                array(
                    'blog_id' => get_current_blog_id(),
                    'time' => $time,
                    'url' => $url,
                    'referrer' => $referrer,
                    'user_agent' => $user_agent,
                    'ip_address' => $ip_address
                )
            );

            if ($result === false) {
                error_log("Error inserting 404 log: " . $wpdb->last_error);
            }
        }
    }
    add_action('template_redirect', 'log_404_errors');

    function schedule_404_log_email() {
        if (is_main_site() && !wp_next_scheduled('send_404_log_email')) {
            wp_schedule_event(time(), 'daily', 'send_404_log_email');
        }
    }
    add_action('wp', 'schedule_404_log_email');

    /**
     * Emails today's 404 errors across all network sites to admin, then deletes logged records
     * Groups duplicate URLs and orders by frequency
     */
    function send_404_log_email() {
        global $wpdb;
        $table_name = $wpdb->base_prefix . '404_log';

        // Get grouped error counts with most recent occurrence details
        $results = $wpdb->get_results("
            SELECT
                t1.blog_id,
                t1.url,
                COUNT(*) as error_count,
                MAX(t1.time) as last_occurrence,
                t2.referrer,
                t2.user_agent,
                t2.ip_address
            FROM {$table_name} t1
            LEFT JOIN {$table_name} t2 ON t1.blog_id = t2.blog_id
                AND t1.url = t2.url
                AND t1.time = (
                    SELECT MAX(time)
                    FROM {$table_name}
                    WHERE blog_id = t1.blog_id
                    AND url = t1.url
                )
            WHERE DATE(t1.time) = CURDATE()
            GROUP BY t1.blog_id, t1.url
            ORDER BY t1.blog_id, error_count DESC
        ");

        if ($results) {
            $message = "Here are the 404 errors logged today across all network sites:\n\n";
            $current_blog_id = null;
            $site_total = 0;
            $newest_time = '';

            foreach ($results as $row) {
                // Add site header when switching to a new site
                if ($current_blog_id !== $row->blog_id) {
                    // Show previous site total
                    if ($current_blog_id !== null) {
                        $message .= "Site Total: {$site_total} errors\n\n";
                    }

                    $current_blog_id = $row->blog_id;
                    $site_total = 0;
                    $blog_details = get_blog_details($row->blog_id);
                    $site_name = $blog_details ? $blog_details->blogname : "Site ID {$row->blog_id}";
                    $site_url = $blog_details ? $blog_details->siteurl : '';
                    $message .= "=== {$site_name} ({$site_url}) ===\n\n";
                }

                $site_total += intval($row->error_count);

                $message .= "[{$row->error_count} error" . ($row->error_count > 1 ? 's' : '') . "] {$row->url} (last: {$row->last_occurrence})\n";
                $message .= !empty($row->referrer) ? "  Referrer: {$row->referrer}\n" : "  Referrer: N/A\n";
                $message .= !empty($row->user_agent) ? "  User Agent: {$row->user_agent}\n" : "  User Agent: N/A\n";
                $message .= !empty($row->ip_address) ? "  IP Address: {$row->ip_address}\n" : "  IP Address: N/A\n";
                $message .= "\n";

                if ($newest_time < $row->last_occurrence) {
                    $newest_time = $row->last_occurrence;
                }
            }

            // Show final site total
            if ($site_total > 0) {
                $message .= "Site Total: {$site_total} errors\n\n";
            }

            $admin_email = get_option('admin_email');
            $subject = "Daily 404 Error Log - Network Wide";

            wp_mail($admin_email, $subject, $message);

            if ($newest_time) {
                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE time <= %s", $newest_time));
            }
        }
    }
    add_action('send_404_log_email', 'send_404_log_email');

}