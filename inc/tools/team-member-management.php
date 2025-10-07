<?php
/**
 * Syncs team members from extrachill.com and manages manual overrides (requires extrachill-multisite)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (is_plugin_active('extrachill-multisite/extrachill-multisite.php')) {
    add_filter('extrachill_admin_tools', function($tools) {
        $tools[] = array(
            'id' => 'team-member-management',
            'title' => 'Team Member Management',
            'description' => 'Sync team members from main site (extrachill.com) and manage manual overrides for fired staff or community moderators.',
            'callback' => 'ec_team_member_management_page'
        );
        return $tools;
    }, 25);
}

function ec_team_member_management_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    // Get search query
    $search = isset($_GET['ec_user_search']) ? sanitize_text_field(wp_unslash($_GET['ec_user_search'])) : '';
    $paged = isset($_GET['ec_paged']) ? absint($_GET['ec_paged']) : 1;
    $per_page = 50;

    ?>
    <style>
        .ec-sync-section {
            background: #f0f0f1;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .ec-sync-button {
            font-size: 16px;
            height: auto;
            padding: 12px 24px;
        }
        .ec-sync-report {
            margin-top: 15px;
            padding: 12px;
            background: white;
            border-left: 4px solid #00a32a;
        }
        .ec-user-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .ec-user-table th,
        .ec-user-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .ec-user-table th {
            background: #f0f0f1;
            font-weight: 600;
        }
        .ec-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .ec-badge-yes {
            background: #00a32a;
            color: white;
        }
        .ec-badge-no {
            background: #dba617;
            color: white;
        }
        .ec-source {
            font-size: 12px;
            color: #646970;
        }
        .ec-search-form {
            margin-bottom: 20px;
        }
        .ec-pagination {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
    </style>

    <div class="ec-team-management-wrap">
        <!-- Sync Section -->
        <div class="ec-sync-section">
            <h3>Sync Team Members</h3>
            <p>Automatically set team member status for all users with extrachill.com accounts. Manual overrides will be preserved.</p>
            <button type="button" class="button button-primary ec-sync-button" id="ec-sync-team-members">
                Sync Team Members from Main Site
            </button>
            <div id="ec-sync-report" style="display:none;"></div>
        </div>

        <!-- Search Section -->
        <div class="ec-search-section">
            <h3>User Management</h3>
            <form method="get" class="ec-search-form">
                <input type="hidden" name="page" value="extrachill-admin-tools">
                <input type="text" name="ec_user_search" value="<?php echo esc_attr($search); ?>" placeholder="Search by username or email..." style="width: 300px;">
                <button type="submit" class="button">Search</button>
                <?php if ($search): ?>
                    <a href="<?php echo esc_url(admin_url('tools.php?page=extrachill-admin-tools')); ?>" class="button">Clear Search</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- User Table -->
        <?php
        // Get users
        $user_args = array(
            'number' => $per_page,
            'offset' => ($paged - 1) * $per_page,
            'blog_id' => 0, // All network users
        );

        if ($search) {
            $user_args['search'] = '*' . $search . '*';
            $user_args['search_columns'] = array('user_login', 'user_email');
        }

        $user_query = new WP_User_Query($user_args);
        $users = $user_query->get_results();
        $total_users = $user_query->get_total();
        $total_pages = ceil($total_users / $per_page);

        if ($users):
        ?>
        <table class="ec-user-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Team Member</th>
                    <th>Source</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user):
                    $is_team_member = function_exists('ec_is_team_member') ? ec_is_team_member($user->ID) : false;
                    $manual_override = get_user_meta($user->ID, 'extrachill_team_manual_override', true);

                    // Determine source
                    if ($manual_override === 'add') {
                        $source = 'Manual: Add';
                    } elseif ($manual_override === 'remove') {
                        $source = 'Manual: Remove';
                    } else {
                        $source = 'Auto';
                    }
                ?>
                <tr data-user-id="<?php echo esc_attr($user->ID); ?>">
                    <td><?php echo esc_html($user->user_login); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td>
                        <span class="ec-badge <?php echo $is_team_member ? 'ec-badge-yes' : 'ec-badge-no'; ?>">
                            <?php echo $is_team_member ? 'Yes' : 'No'; ?>
                        </span>
                    </td>
                    <td><span class="ec-source"><?php echo esc_html($source); ?></span></td>
                    <td>
                        <select class="ec-user-action" data-user-id="<?php echo esc_attr($user->ID); ?>">
                            <option value="">-- Select Action --</option>
                            <option value="force_add">Force Add</option>
                            <option value="force_remove">Force Remove</option>
                            <option value="reset_auto">Reset to Auto</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <div class="ec-pagination">
            <?php if ($paged > 1): ?>
                <a href="<?php echo esc_url(add_query_arg(array('ec_paged' => $paged - 1, 'ec_user_search' => $search))); ?>" class="button">Previous</a>
            <?php endif; ?>
            <span>Page <?php echo esc_html($paged); ?> of <?php echo esc_html($total_pages); ?></span>
            <?php if ($paged < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg(array('ec_paged' => $paged + 1, 'ec_user_search' => $search))); ?>" class="button">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <p>No users found.</p>
        <?php endif; ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Sync button handler
        $('#ec-sync-team-members').on('click', function() {
            var $button = $(this);
            var $report = $('#ec-sync-report');

            $button.prop('disabled', true).text('Syncing...');
            $report.hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ec_sync_team_members',
                    nonce: '<?php echo wp_create_nonce('ec_sync_team_members_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $report.html(
                            '<strong>Sync Complete!</strong><br>' +
                            'Total Users: ' + response.data.total_users + '<br>' +
                            'Users Updated: ' + response.data.users_updated + '<br>' +
                            'Users Skipped (Manual Override): ' + response.data.users_skipped_override + '<br>' +
                            'Users with Main Site Account: ' + response.data.users_with_main_site_account
                        ).show();

                        // Reload page after 2 seconds to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Sync Team Members from Main Site');
                }
            });
        });

        // User action handler
        $('.ec-user-action').on('change', function() {
            var $select = $(this);
            var action = $select.val();
            var userId = $select.data('user-id');

            if (!action) return;

            if (!confirm('Are you sure you want to perform this action?')) {
                $select.val('');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ec_manage_team_member',
                    user_id: userId,
                    team_action: action,
                    nonce: '<?php echo wp_create_nonce('ec_manage_team_member_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        $select.val('');
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                    $select.val('');
                }
            });
        });
    });
    </script>
    <?php
}

add_action('wp_ajax_ec_sync_team_members', 'ec_ajax_sync_team_members');
function ec_ajax_sync_team_members() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'ec_sync_team_members_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    if (!function_exists('ec_has_main_site_account')) {
        wp_send_json_error('Multisite plugin functions not available');
    }

    $report = ec_sync_team_members();

    wp_send_json_success($report);
}

function ec_sync_team_members() {
    $report = array(
        'total_users' => 0,
        'users_updated' => 0,
        'users_skipped_override' => 0,
        'users_with_main_site_account' => 0,
    );

    // Get all network users
    $users = get_users(array(
        'blog_id' => 0,
        'fields' => 'ID'
    ));

    $report['total_users'] = count($users);

    foreach ($users as $user_id) {
        // Check for manual override
        $manual_override = get_user_meta($user_id, 'extrachill_team_manual_override', true);

        if ($manual_override === 'add' || $manual_override === 'remove') {
            $report['users_skipped_override']++;
            continue;
        }

        // Check main site account
        $has_main_account = ec_has_main_site_account($user_id);

        if ($has_main_account) {
            $report['users_with_main_site_account']++;

            $current_status = get_user_meta($user_id, 'extrachill_team', true);
            if ($current_status != 1) {
                update_user_meta($user_id, 'extrachill_team', 1);
                $report['users_updated']++;
            }
        } else {
            $current_status = get_user_meta($user_id, 'extrachill_team', true);
            if ($current_status == 1) {
                update_user_meta($user_id, 'extrachill_team', 0);
                $report['users_updated']++;
            }
        }
    }

    return $report;
}

add_action('wp_ajax_ec_manage_team_member', 'ec_ajax_manage_team_member');
function ec_ajax_manage_team_member() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'ec_manage_team_member_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $action = isset($_POST['team_action']) ? sanitize_text_field(wp_unslash($_POST['team_action'])) : '';

    if (!$user_id || !$action) {
        wp_send_json_error('Missing required parameters');
    }

    switch ($action) {
        case 'force_add':
            update_user_meta($user_id, 'extrachill_team_manual_override', 'add');
            update_user_meta($user_id, 'extrachill_team', 1);
            wp_send_json_success('User forced to team member');
            break;

        case 'force_remove':
            update_user_meta($user_id, 'extrachill_team_manual_override', 'remove');
            update_user_meta($user_id, 'extrachill_team', 0);
            wp_send_json_success('User forced to non-team member');
            break;

        case 'reset_auto':
            delete_user_meta($user_id, 'extrachill_team_manual_override');
            $has_main_account = function_exists('ec_has_main_site_account') ? ec_has_main_site_account($user_id) : false;
            update_user_meta($user_id, 'extrachill_team', $has_main_account ? 1 : 0);
            wp_send_json_success('User reset to auto sync');
            break;

        default:
            wp_send_json_error('Invalid action');
    }
}