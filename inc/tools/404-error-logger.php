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
    $enabled = get_option('extrachill_404_logger_enabled', 1);

    if (isset($_POST['toggle_404_logger']) && check_admin_referer('404_logger_toggle')) {
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        update_option('extrachill_404_logger_enabled', $enabled);
        echo '<div class="notice notice-success"><p>404 Logger ' . ($enabled ? 'enabled' : 'disabled') . '.</p></div>';
    }

    echo '<form method="post">';
    wp_nonce_field('404_logger_toggle');
    echo '<p><label><input type="checkbox" name="enabled" value="1" ' . checked($enabled, 1, false) . '> Enable 404 Error Logging</label></p>';
    echo '<p><input type="submit" name="toggle_404_logger" class="button button-primary" value="Save Setting"></p>';
    echo '</form>';

    if ($enabled) {
        global $wpdb;
        $table_name = $wpdb->prefix . '404_log';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(time) = CURDATE()");
        echo '<p><strong>Today\'s 404 errors:</strong> ' . intval($count) . '</p>';
    }
}

if (get_option('extrachill_404_logger_enabled', 1)) {

    /**
     * Excludes /event/ URLs to prevent logging expected 404s from calendar plugin
     */
    function log_404_errors() {
        if (is_404()) {
            $url = esc_url($_SERVER['REQUEST_URI']);
            if (preg_match('/^\/event\//', $url)) {
                return;
            }

            global $wpdb;
            $table_name = $wpdb->prefix . '404_log';

            $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            $time = current_time('mysql');

            $result = $wpdb->insert(
                $table_name,
                array(
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
        if (!wp_next_scheduled('send_404_log_email')) {
            wp_schedule_event(time(), 'daily', 'send_404_log_email');
        }
    }
    add_action('wp', 'schedule_404_log_email');

    /**
     * Compiles today's 404 errors into email report, then purges all data
     */
    function send_404_log_email() {
        global $wpdb;
        $table_name = $wpdb->prefix . '404_log';

        $results = $wpdb->get_results("SELECT * FROM $table_name WHERE DATE(time) = CURDATE()");

        if ($results) {
            $message = "Here are the 404 errors logged today:\n\n";
            $newest_time = '';

            foreach ($results as $row) {
                $message .= "{$row->time} - {$row->url}\n";
                $message .= !empty($row->referrer) ? "Referrer: {$row->referrer}\n" : "Referrer: N/A\n";
                $message .= !empty($row->user_agent) ? "User Agent: {$row->user_agent}\n" : "User Agent: N/A\n";
                $message .= !empty($row->ip_address) ? "IP Address: {$row->ip_address}\n" : "IP Address: N/A\n";
                $message .= "\n";

                if ($newest_time < $row->time) {
                    $newest_time = $row->time;
                }
            }

            $admin_email = get_option('admin_email');
            $subject = "Daily 404 Error Log";

            wp_mail($admin_email, $subject, $message);

            if ($newest_time) {
                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE time <= %s", $newest_time));
            }
        }
    }
    add_action('send_404_log_email', 'send_404_log_email');

}