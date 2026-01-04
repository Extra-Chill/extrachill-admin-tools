<?php
/**
 * Lifetime Membership Management Tool
 *
 * Manage lifetime memberships for Extra Chill platform users.
 * Grant, revoke, and view membership holders via REST API interface.
 * Integrates with extrachill-users lifetime membership validation system.
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

    wp_enqueue_style(
        'ec-lifetime-membership-management',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/css/lifetime-membership-management.css',
        array('extrachill-admin-tools'),
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/css/lifetime-membership-management.css')
    );

    wp_enqueue_script(
        'ec-lifetime-membership-management',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/lifetime-membership-management.js',
        array('jquery', 'extrachill-admin-tools'),
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/lifetime-membership-management.js'),
        true
    );

    wp_localize_script('ec-lifetime-membership-management', 'ecLifetimeMembership', array(
        'nonce' => wp_create_nonce('wp_rest'),
        'rest_url' => rest_url('extrachill/v1/')
    ));
});

add_filter('extrachill_admin_tools', function($tools) {
    $tools[] = array(
        'id' => 'lifetime-membership-management',
        'title' => 'Lifetime Membership Management',
        'description' => 'Grant, revoke, and manage lifetime memberships for platform users.',
        'callback' => 'ec_lifetime_membership_management_page'
    );
    return $tools;
}, 30);

function ec_lifetime_membership_management_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $search = isset($_GET['ec_membership_search']) ? sanitize_text_field(wp_unslash($_GET['ec_membership_search'])) : '';
    $paged = isset($_GET['ec_membership_paged']) ? absint($_GET['ec_membership_paged']) : 1;
    $per_page = 50;

    $user_args = array(
        'meta_key' => 'extrachill_lifetime_membership',
        'number' => $per_page,
        'offset' => ($paged - 1) * $per_page,
    );

    if ($search) {
        $user_args['search'] = '*' . $search . '*';
        $user_args['search_columns'] = array('user_login', 'user_email');
    }

    $users_with_memberships = get_users($user_args);

    $total_count_args = array(
        'meta_key' => 'extrachill_lifetime_membership',
        'fields' => 'ID',
    );

    if ($search) {
        $total_count_args['search'] = '*' . $search . '*';
        $total_count_args['search_columns'] = array('user_login', 'user_email');
    }

    $total_memberships = count(get_users($total_count_args));

    ?>
    <div class="ec-lifetime-membership-wrap">
        <div class="ec-grant-section">
            <h3>Grant Lifetime Membership</h3>
            <p>Grant a lifetime membership to any user by entering their username or email.</p>

            <div class="ec-grant-form">
                <input type="text" id="ec-user-search" placeholder="Enter username or email..." style="width: 350px;">
                <button type="button" class="button button-primary" id="ec-grant-membership-btn">
                    Grant Membership
                </button>
            </div>
            <div id="ec-grant-result"></div>
        </div>

        <div class="ec-membership-holders-section">
            <h3>Lifetime Members (<?php echo absint($total_memberships); ?> total)</h3>

            <form method="get" class="ec-search-form">
                <input type="hidden" name="page" value="extrachill-admin-tools">
                <input type="text" name="ec_membership_search" value="<?php echo esc_attr($search); ?>" placeholder="Search members..." style="width: 300px;">
                <button type="submit" class="button">Search</button>
                <?php if ($search): ?>
                    <a href="<?php echo esc_url(admin_url('tools.php?page=extrachill-admin-tools')); ?>" class="button">Clear</a>
                <?php endif; ?>
            </form>

            <?php if (!empty($users_with_memberships)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Member Since</th>
                            <th>Order ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_with_memberships as $user):
                            $membership_data = get_user_meta($user->ID, 'extrachill_lifetime_membership', true);
                            $purchased_date = isset($membership_data['purchased']) ? $membership_data['purchased'] : 'N/A';
                            $order_id = isset($membership_data['order_id']) && $membership_data['order_id'] ? $membership_data['order_id'] : 'Manual Grant';
                            ?>
                            <tr data-user-id="<?php echo absint($user->ID); ?>">
                                <td><strong><?php echo esc_html($user->user_login); ?></strong></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html($purchased_date); ?></td>
                                <td><?php echo esc_html($order_id); ?></td>
                                <td>
                                    <button class="button ec-revoke-btn" data-user-id="<?php echo absint($user->ID); ?>" data-username="<?php echo esc_attr($user->user_login); ?>">
                                        Revoke Membership
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                $total_pages = ceil($total_memberships / $per_page);
                if ($total_pages > 1):
                ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('ec_membership_paged', '%#%'),
                                'format' => '',
                                'current' => $paged,
                                'total' => $total_pages
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="padding: 20px; background: #f8f9fa; border: 1px solid var(--border-color); border-radius: 4px;">
                    No lifetime members found<?php echo $search ? ' for search term "' . esc_html($search) . '"' : ''; ?>.
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
