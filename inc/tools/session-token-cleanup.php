<?php
/**
 * Session Token Table Cleanup
 *
 * One-time cleanup utility for removing legacy session token table.
 * Provides admin notice with button to safely remove the old database table
 * after migration to WordPress multisite authentication.
 *
 * @package ExtraChillAdminTools
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One-time admin notice to clean up legacy session token table
 * Displays dismissible notice for administrators to remove old database table
 */
add_action('admin_notices', 'ecat_session_token_cleanup_notice');
function ecat_session_token_cleanup_notice() {
    // Only show to administrators
    if (!current_user_can('administrator')) {
        return;
    }

    // Only show if notice hasn't been dismissed
    if (get_option('ecat_session_token_cleanup_dismissed')) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_session_tokens';

    // Only show if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return;
    }

    ?>
    <div class="notice notice-info is-dismissible" data-dismiss-action="ecat_dismiss_session_token_cleanup">
        <p><strong>Session Token Migration Complete:</strong> The legacy session token system has been replaced with WordPress multisite authentication. The old <code>user_session_tokens</code> database table can now be safely removed.</p>
        <p>
            <a href="#" class="button button-secondary" onclick="ecat_cleanup_session_tokens()">Remove Old Table</a>
            <em>This will permanently delete the unused session token database table.</em>
        </p>
    </div>
    <script>
    function ecat_cleanup_session_tokens() {
        if (confirm('Are you sure you want to permanently delete the old session token table? This cannot be undone.')) {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=ecat_cleanup_session_tokens&nonce=' + '<?php echo wp_create_nonce('ecat_cleanup_session_tokens'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Session token table removed successfully.');
                    location.reload();
                } else {
                    alert('Error: ' + data.data);
                }
            });
        }
    }
    </script>
    <?php
}

/**
 * AJAX handler to clean up session token table
 */
add_action('wp_ajax_ecat_cleanup_session_tokens', 'ecat_handle_session_token_cleanup');
function ecat_handle_session_token_cleanup() {
    // Verify nonce and permissions
    if (!wp_verify_nonce($_POST['nonce'], 'ecat_cleanup_session_tokens') || !current_user_can('administrator')) {
        wp_send_json_error('Unauthorized');
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_session_tokens';

    // Drop the table
    $result = $wpdb->query("DROP TABLE IF EXISTS $table_name");

    if ($result !== false) {
        // Mark notice as dismissed
        update_option('ecat_session_token_cleanup_dismissed', true);
        wp_send_json_success('Session token table removed successfully');
    } else {
        wp_send_json_error('Failed to remove table');
    }
}

/**
 * Handle notice dismissal
 */
add_action('wp_ajax_ecat_dismiss_session_token_cleanup', 'ecat_handle_notice_dismissal');
function ecat_handle_notice_dismissal() {
    if (current_user_can('administrator')) {
        update_option('ecat_session_token_cleanup_dismissed', true);
        wp_send_json_success();
    }
}