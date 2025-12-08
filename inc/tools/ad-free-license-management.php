<?php
/**
 * Ad-Free License Management Tool
 *
 * Manage ad-free licenses for Extra Chill platform users.
 * Grant, revoke, and view license holders via REST API interface.
 * Integrates with extrachill-multisite ad-free license validation system.
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
        'ec-ad-free-management',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/css/ad-free-license-management.css',
        array('extrachill-admin-tools'),
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/css/ad-free-license-management.css')
    );

    wp_enqueue_script(
        'ec-ad-free-management',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/ad-free-license-management.js',
        array('jquery', 'extrachill-admin-tools'),
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/ad-free-license-management.js'),
        true
    );

    wp_localize_script('ec-ad-free-management', 'ecAdFree', array(
        'nonce' => wp_create_nonce('wp_rest'),
        'rest_url' => rest_url('extrachill/v1/')
    ));
});

add_filter('extrachill_admin_tools', function($tools) {
    $tools[] = array(
        'id' => 'ad-free-license-management',
        'title' => 'Ad-Free License Management',
        'description' => 'Grant, revoke, and manage ad-free licenses for platform users.',
        'callback' => 'ec_ad_free_license_management_page'
    );
    return $tools;
}, 30);

function ec_ad_free_license_management_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $search = isset($_GET['ec_license_search']) ? sanitize_text_field(wp_unslash($_GET['ec_license_search'])) : '';
    $paged = isset($_GET['ec_license_paged']) ? absint($_GET['ec_license_paged']) : 1;
    $per_page = 50;

    $user_args = array(
        'meta_key' => 'extrachill_ad_free_purchased',
        'number' => $per_page,
        'offset' => ($paged - 1) * $per_page,
    );

    if ($search) {
        $user_args['search'] = '*' . $search . '*';
        $user_args['search_columns'] = array('user_login', 'user_email');
    }

    $users_with_licenses = get_users($user_args);

    $total_count_args = array(
        'meta_key' => 'extrachill_ad_free_purchased',
        'fields' => 'ID',
    );

    if ($search) {
        $total_count_args['search'] = '*' . $search . '*';
        $total_count_args['search_columns'] = array('user_login', 'user_email');
    }

    $total_licenses = count(get_users($total_count_args));

    ?>
    <div class="ec-ad-free-wrap">
        <div class="ec-grant-section">
            <h3>Grant Ad-Free License</h3>
            <p>Grant an ad-free license to any user by entering their username or email.</p>

            <div class="ec-grant-form">
                <input type="text" id="ec-user-search" placeholder="Enter username or email..." style="width: 350px;">
                <button type="button" class="button button-primary" id="ec-grant-license-btn">
                    Grant License
                </button>
            </div>
            <div id="ec-grant-result"></div>
        </div>

        <div class="ec-license-holders-section">
            <h3>Current License Holders (<?php echo absint($total_licenses); ?> total)</h3>

            <form method="get" class="ec-search-form">
                <input type="hidden" name="page" value="extrachill-admin-tools">
                <input type="text" name="ec_license_search" value="<?php echo esc_attr($search); ?>" placeholder="Search license holders..." style="width: 300px;">
                <button type="submit" class="button">Search</button>
                <?php if ($search): ?>
                    <a href="<?php echo esc_url(admin_url('tools.php?page=extrachill-admin-tools')); ?>" class="button">Clear</a>
                <?php endif; ?>
            </form>

            <?php if (!empty($users_with_licenses)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Purchased Date</th>
                            <th>Order ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_with_licenses as $user):
                            $license_data = get_user_meta($user->ID, 'extrachill_ad_free_purchased', true);
                            $purchased_date = isset($license_data['purchased']) ? $license_data['purchased'] : 'N/A';
                            $order_id = isset($license_data['order_id']) && $license_data['order_id'] ? $license_data['order_id'] : 'Manual Grant';
                            ?>
                            <tr data-user-id="<?php echo absint($user->ID); ?>">
                                <td><strong><?php echo esc_html($user->user_login); ?></strong></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html($purchased_date); ?></td>
                                <td><?php echo esc_html($order_id); ?></td>
                                <td>
                                    <button class="button ec-revoke-btn" data-user-id="<?php echo absint($user->ID); ?>" data-username="<?php echo esc_attr($user->user_login); ?>">
                                        Revoke License
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                $total_pages = ceil($total_licenses / $per_page);
                if ($total_pages > 1):
                ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('ec_license_paged', '%#%'),
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
                    No ad-free licenses found<?php echo $search ? ' for search term "' . esc_html($search) . '"' : ''; ?>.
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
