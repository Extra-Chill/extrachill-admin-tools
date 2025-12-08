<?php
/**
 * Team Member Management Tool
 *
 * Sync team members from extrachill.com and manage manual overrides.
 * Uses REST API endpoints for all operations.
 *
 * @package ExtraChillAdminTools
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'tools_page_extrachill-admin-tools') {
        return;
    }

    if (!function_exists('ec_is_team_member')) {
        return;
    }

    wp_enqueue_style(
        'ec-team-member-management',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/css/team-member-management.css',
        array('extrachill-admin-tools'),
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/css/team-member-management.css')
    );

    wp_enqueue_script(
        'ec-team-member-management',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/team-member-management.js',
        array('extrachill-admin-tools'),
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/team-member-management.js'),
        true
    );
});

add_filter('extrachill_admin_tools', function($tools) {
    if (!function_exists('ec_is_team_member')) {
        return $tools;
    }

    $tools[] = array(
        'id' => 'team-member-management',
        'title' => 'Team Member Management',
        'description' => 'Sync team members from main site (extrachill.com) and manage manual overrides for fired staff or community moderators.',
        'callback' => 'ec_team_member_management_page'
    );
    return $tools;
}, 25);

function ec_team_member_management_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    $search = isset($_GET['ec_user_search']) ? sanitize_text_field(wp_unslash($_GET['ec_user_search'])) : '';
    $paged = isset($_GET['ec_paged']) ? absint($_GET['ec_paged']) : 1;
    $per_page = 50;

    ?>
    <div class="ec-team-management-wrap">
        <!-- Sync Section -->
        <div class="ec-sync-section">
            <h3>Sync Team Members</h3>
            <p>Automatically set team member status for all users with extrachill.com accounts. Manual overrides will be preserved.</p>
            <button type="button" class="button ec-sync-button" id="ec-sync-team-members">
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
        $user_args = array(
            'number' => $per_page,
            'offset' => ($paged - 1) * $per_page,
            'blog_id' => 0,
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
    <?php
}
