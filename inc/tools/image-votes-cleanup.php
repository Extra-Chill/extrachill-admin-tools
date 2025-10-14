<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_notices', 'ecat_image_votes_cleanup_notice');
function ecat_image_votes_cleanup_notice() {
    if (!current_user_can('administrator')) {
        return;
    }

    if (get_option('ecat_image_votes_cleanup_dismissed')) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'image_votes';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return;
    }

    ?>
    <div class="notice notice-info is-dismissible" data-dismiss-action="ecat_dismiss_image_votes_cleanup">
        <p><strong>Image Voting System Modernized:</strong> The image voting blocks have been updated to use WordPress native block attributes instead of a custom database table. The old <code>image_votes</code> database table can now be safely removed.</p>
        <p>
            <a href="#" class="button button-2" onclick="ecat_cleanup_image_votes()">Remove Old Table</a>
            <em>This will permanently delete the unused image votes database table.</em>
        </p>
    </div>
    <script>
    function ecat_cleanup_image_votes() {
        if (confirm('Are you sure you want to permanently delete the old image votes table? This cannot be undone.\n\nNote: All existing vote data has already been preserved in the block attributes system.')) {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=ecat_cleanup_image_votes&nonce=' + '<?php echo wp_create_nonce('ecat_cleanup_image_votes'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Image votes table removed successfully.');
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

add_action('wp_ajax_ecat_cleanup_image_votes', 'ecat_handle_image_votes_cleanup');
function ecat_handle_image_votes_cleanup() {
    if (!wp_verify_nonce($_POST['nonce'], 'ecat_cleanup_image_votes') || !current_user_can('administrator')) {
        wp_send_json_error('Unauthorized');
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'image_votes';

    $result = $wpdb->query("DROP TABLE IF EXISTS $table_name");

    if ($result !== false) {
        update_option('ecat_image_votes_cleanup_dismissed', true);
        wp_send_json_success('Image votes table removed successfully');
    } else {
        wp_send_json_error('Failed to remove table');
    }
}

add_action('wp_ajax_ecat_dismiss_image_votes_cleanup', 'ecat_handle_image_votes_notice_dismissal');
function ecat_handle_image_votes_notice_dismissal() {
    if (current_user_can('administrator')) {
        update_option('ecat_image_votes_cleanup_dismissed', true);
        wp_send_json_success();
    }
}