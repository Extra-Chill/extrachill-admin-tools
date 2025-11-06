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
     * Logs 404 errors with varchar(2000) url/referrer fields and safety truncation at 1990 chars.
     * Excludes /event/ URLs (calendar plugin integration).
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

            // Safety truncation: 1990 char limit (10 char buffer below varchar(2000) max) prevents edge cases with URL encoding
            if (strlen($url) > 1990) {
                $url = substr($url, 0, 1990) . '...';
            }
            if (strlen($referrer) > 1990) {
                $referrer = substr($referrer, 0, 1990) . '...';
            }

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
    add_action('init', 'schedule_404_log_email');

    /**
     * Emails today's 404 errors across all network sites to admin, then deletes logged records
     * Groups duplicate URLs and orders by frequency
     */
    function send_404_log_email() {
        if (!is_main_site()) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->base_prefix . '404_log';

        $results = $wpdb->get_results("
            SELECT
                main.blog_id,
                main.url,
                main.error_count,
                main.last_occurrence,
                main.latest_user_agent,
                main.latest_ip_address,
                GROUP_CONCAT(
                    CONCAT(
                        CASE WHEN ref_counts.referrer = '' THEN 'direct' ELSE ref_counts.referrer END,
                        ' (', ref_counts.cnt, ')'
                    )
                    ORDER BY ref_counts.cnt DESC
                    SEPARATOR ', '
                ) as referrer_summary
            FROM (
                SELECT
                    blog_id,
                    url,
                    COUNT(*) as error_count,
                    MAX(time) as last_occurrence,
                    (SELECT user_agent FROM {$table_name} WHERE blog_id = t.blog_id AND url = t.url ORDER BY time DESC LIMIT 1) as latest_user_agent,
                    (SELECT ip_address FROM {$table_name} WHERE blog_id = t.blog_id AND url = t.url ORDER BY time DESC LIMIT 1) as latest_ip_address
                FROM {$table_name} t
                WHERE DATE(time) = CURDATE()
                GROUP BY blog_id, url
            ) main
            LEFT JOIN (
                SELECT
                    blog_id,
                    url,
                    referrer,
                    COUNT(*) as cnt
                FROM {$table_name}
                WHERE DATE(time) = CURDATE()
                GROUP BY blog_id, url, referrer
            ) ref_counts ON main.blog_id = ref_counts.blog_id AND main.url = ref_counts.url
            GROUP BY main.blog_id, main.url, main.error_count, main.last_occurrence, main.latest_user_agent, main.latest_ip_address
            ORDER BY main.blog_id, main.error_count DESC
        ");

        if ($results) {
            $message = "Here are the 404 errors logged today across all network sites:\n\n";
            $current_blog_id = null;
            $site_total = 0;
            $newest_time = '';

            foreach ($results as $row) {
                if ($current_blog_id !== $row->blog_id) {
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
                $message .= !empty($row->referrer_summary) ? "  Referrers: {$row->referrer_summary}\n" : "  Referrers: N/A\n";
                $message .= !empty($row->latest_user_agent) ? "  Most Recent User Agent: {$row->latest_user_agent}\n" : "  Most Recent User Agent: N/A\n";
                $message .= !empty($row->latest_ip_address) ? "  Most Recent IP: {$row->latest_ip_address}\n" : "  Most Recent IP: N/A\n";
                $message .= "\n";

                if ($newest_time < $row->last_occurrence) {
                    $newest_time = $row->last_occurrence;
                }
            }

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