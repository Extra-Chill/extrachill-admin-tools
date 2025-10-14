<?php
/**
 * Manages relationships between users and artist profiles (requires extrachill-artist-platform)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'tools_page_extrachill-admin-tools') {
        return;
    }

    if (!post_type_exists('artist_profile')) {
        return;
    }

    wp_enqueue_style(
        'ec-artist-user-relationships',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/css/artist-user-relationships.css',
        array('extrachill-admin-tools'),
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/css/artist-user-relationships.css')
    );

    wp_enqueue_script(
        'ec-artist-user-relationships',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/artist-user-relationships.js',
        array('extrachill-admin-tools'),
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/artist-user-relationships.js'),
        true
    );
});

add_filter('extrachill_admin_tools', function($tools) {
    // Only load if artist platform plugin is active (check for post type)
    if (!post_type_exists('artist_profile')) {
        return $tools;
    }

    $tools[] = array(
        'id' => 'artist-user-relationships',
        'title' => 'Artist-User Relationships',
        'description' => 'Manage relationships between users and artist profiles. Link users to artists, view all relationships, and detect orphaned data.',
        'callback' => 'ec_artist_user_relationships_page'
    );
    return $tools;
}, 20);

function ec_artist_user_relationships_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    $view = isset($_GET['ec_view']) ? sanitize_text_field(wp_unslash($_GET['ec_view'])) : 'artists';
    $search = isset($_GET['ec_search']) ? sanitize_text_field(wp_unslash($_GET['ec_search'])) : '';

    ?>
    <div class="ec-relationships-wrap" data-ec-nested-container>
        <!-- Nested Tab Navigation -->
        <div class="ec-nested-tabs">
            <a href="#" data-ec-nested-tab="artists" class="<?php echo $view === 'artists' ? 'active' : ''; ?>">
                Artists
            </a>
            <a href="#" data-ec-nested-tab="users" class="<?php echo $view === 'users' ? 'active' : ''; ?>">
                Users
            </a>
            <a href="#" data-ec-nested-tab="orphans" class="<?php echo $view === 'orphans' ? 'active' : ''; ?>">
                Orphans
            </a>
        </div>

        <div class="ec-search-box">
            <form method="get">
                <input type="hidden" name="page" value="extrachill-admin-tools">
                <input type="hidden" name="ec_view" value="<?php echo esc_attr($view); ?>">
                <input type="text" name="ec_search" value="<?php echo esc_attr($search); ?>" placeholder="Search...">
                <button type="submit" class="button">Search</button>
                <?php if ($search): ?>
                    <a href="<?php echo esc_url(add_query_arg('ec_view', $view, remove_query_arg('ec_search'))); ?>" class="button">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Artists View -->
        <div data-ec-nested-content="artists" style="display: <?php echo $view === 'artists' ? 'block' : 'none'; ?>;">
            <?php ec_render_artists_view($search); ?>
        </div>

        <!-- Users View -->
        <div data-ec-nested-content="users" style="display: <?php echo $view === 'users' ? 'block' : 'none'; ?>;">
            <?php ec_render_users_view($search); ?>
        </div>

        <!-- Orphans View -->
        <div data-ec-nested-content="orphans" style="display: <?php echo $view === 'orphans' ? 'block' : 'none'; ?>;">
            <?php ec_render_orphans_view(); ?>
        </div>
    </div>

    <!-- User Search Modal -->
    <div id="ec-user-search-modal" class="ec-user-search-modal">
        <div class="ec-user-search-content">
            <span class="ec-user-search-close">&times;</span>
            <h2>Add User to Artist</h2>
            <p>Search for a user by name, username, or email:</p>
            <input type="text" id="ec-user-search-input" class="ec-user-search-input" placeholder="Start typing to search...">
            <div id="ec-user-search-results" class="ec-user-search-results"></div>
        </div>
    </div>
    <?php
}

function ec_render_artists_view($search) {
    $args = array(
        'post_type' => 'artist_profile',
        'post_status' => 'any',
        'posts_per_page' => 50,
        'orderby' => 'title',
        'order' => 'ASC'
    );

    if ($search) {
        $args['s'] = $search;
    }

    $artists = get_posts($args);

    if (empty($artists)): ?>
        <div class="ec-empty-state">
            <p>No artist profiles found.</p>
        </div>
    <?php else: ?>
        <table class="ec-relationship-table">
            <thead>
                <tr>
                    <th>Artist Profile</th>
                    <th>Members</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($artists as $artist):
                    $members = function_exists('bp_get_linked_members') ? bp_get_linked_members($artist->ID) : array();
                    $member_count = count($members);
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($artist->post_title); ?></strong>
                        <br>
                        <small>ID: <?php echo esc_html($artist->ID); ?></small>
                    </td>
                    <td>
                        <span class="ec-member-count"><?php echo esc_html($member_count); ?> members</span>
                        <?php if ($member_count > 0): ?>
                            <ul class="ec-member-list">
                                <?php foreach ($members as $member): ?>
                                    <li>
                                        <?php echo esc_html($member->display_name); ?> (<?php echo esc_html($member->user_login); ?>)
                                        <a href="#" class="ec-remove-member ec-remove-link"
                                           data-user-id="<?php echo esc_attr($member->ID); ?>"
                                           data-artist-id="<?php echo esc_attr($artist->ID); ?>">
                                            Remove
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="#" class="button ec-link-user" data-artist-id="<?php echo esc_attr($artist->ID); ?>">Add User</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif;
}

function ec_render_users_view($search) {
    $args = array(
        'number' => 50,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'user_is_artist',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => 'user_is_professional',
                'value' => '1',
                'compare' => '='
            )
        )
    );

    if ($search) {
        $args['search'] = '*' . $search . '*';
        $args['search_columns'] = array('user_login', 'user_email', 'display_name');
    }

    $user_query = new WP_User_Query($args);
    $users = $user_query->get_results();

    if (empty($users)): ?>
        <div class="ec-empty-state">
            <p>No users found.</p>
        </div>
    <?php else: ?>
        <table class="ec-relationship-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Artist Profiles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user):
                    $artist_profiles = function_exists('bp_get_user_artist_memberships') ? bp_get_user_artist_memberships($user->ID) : array();
                    $profile_count = count($artist_profiles);
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($user->display_name); ?></strong>
                        <br>
                        <small><?php echo esc_html($user->user_login); ?> (<?php echo esc_html($user->user_email); ?>)</small>
                    </td>
                    <td>
                        <?php if ($profile_count > 0): ?>
                            <ul class="ec-member-list">
                                <?php foreach ($artist_profiles as $artist): ?>
                                    <li>
                                        <?php echo esc_html($artist->post_title); ?>
                                        <a href="#" class="ec-remove-member ec-remove-link"
                                           data-user-id="<?php echo esc_attr($user->ID); ?>"
                                           data-artist-id="<?php echo esc_attr($artist->ID); ?>">
                                            Remove
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span style="color: #646970;">No artist profiles</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif;
}

function ec_render_orphans_view() {
    echo '<h3>Orphaned Relationships</h3>';
    echo '<p>Users with artist profile IDs that no longer exist, or artist profiles with invalid user IDs.</p>';

    $all_users = get_users(array('meta_key' => '_artist_profile_ids'));
    $orphaned_users = array();

    foreach ($all_users as $user) {
        $artist_ids = get_user_meta($user->ID, '_artist_profile_ids', true);
        if (!is_array($artist_ids)) continue;

        foreach ($artist_ids as $artist_id) {
            if (!get_post($artist_id) || get_post_type($artist_id) !== 'artist_profile') {
                $orphaned_users[] = array(
                    'user' => $user,
                    'invalid_artist_id' => $artist_id
                );
            }
        }
    }

    if (empty($orphaned_users)): ?>
        <div class="ec-empty-state">
            <p>✓ No orphaned relationships found. All data is clean!</p>
        </div>
    <?php else: ?>
        <table class="ec-relationship-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Invalid Artist ID</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orphaned_users as $orphan): ?>
                <tr>
                    <td><?php echo esc_html($orphan['user']->display_name); ?> (<?php echo esc_html($orphan['user']->user_login); ?>)</td>
                    <td><?php echo esc_html($orphan['invalid_artist_id']); ?></td>
                    <td>
                        <button class="button ec-cleanup-orphan"
                                data-user-id="<?php echo esc_attr($orphan['user']->ID); ?>"
                                data-artist-id="<?php echo esc_attr($orphan['invalid_artist_id']); ?>">
                            Clean Up
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif;
}

add_action('wp_ajax_ec_remove_artist_user_relationship', 'ec_ajax_remove_artist_user_relationship');
function ec_ajax_remove_artist_user_relationship() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'ec_artist_user_relationships')) {
        wp_send_json_error('Invalid nonce');
    }

    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $artist_id = isset($_POST['artist_id']) ? absint($_POST['artist_id']) : 0;

    if (!$user_id || !$artist_id) {
        wp_send_json_error('Missing parameters');
    }

    if (function_exists('bp_remove_artist_membership')) {
        $result = bp_remove_artist_membership($user_id, $artist_id);
        if ($result) {
            wp_send_json_success('Relationship removed');
        } else {
            wp_send_json_error('Failed to remove relationship');
        }
    } else {
        wp_send_json_error('Artist platform functions not available');
    }
}

add_action('wp_ajax_ec_add_artist_user_relationship', 'ec_ajax_add_artist_user_relationship');
function ec_ajax_add_artist_user_relationship() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'ec_artist_user_relationships')) {
        wp_send_json_error('Invalid nonce');
    }

    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $artist_id = isset($_POST['artist_id']) ? absint($_POST['artist_id']) : 0;

    if (!$user_id || !$artist_id) {
        wp_send_json_error('Missing parameters');
    }

    if (function_exists('bp_add_artist_membership')) {
        $result = bp_add_artist_membership($user_id, $artist_id);
        if ($result) {
            wp_send_json_success('Relationship added');
        } else {
            wp_send_json_error('Failed to add relationship or already exists');
        }
    } else {
        wp_send_json_error('Artist platform functions not available');
    }
}

add_action('wp_ajax_ec_search_users_for_relationship', 'ec_ajax_search_users_for_relationship');
function ec_ajax_search_users_for_relationship() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'ec_artist_user_relationships')) {
        wp_send_json_error('Invalid nonce');
    }

    $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';

    if (strlen($search) < 2) {
        wp_send_json_success(array());
    }

    $args = array(
        'search' => '*' . $search . '*',
        'search_columns' => array('user_login', 'user_email', 'display_name'),
        'number' => 20,
        'orderby' => 'display_name',
        'order' => 'ASC'
    );

    $user_query = new WP_User_Query($args);
    $users = $user_query->get_results();

    $results = array();
    foreach ($users as $user) {
        $results[] = array(
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'avatar' => get_avatar_url($user->ID, array('size' => 32))
        );
    }

    wp_send_json_success($results);
}