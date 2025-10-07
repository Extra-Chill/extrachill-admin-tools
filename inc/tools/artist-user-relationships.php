<?php
/**
 * Manages relationships between users and artist profiles (requires extrachill-artist-platform)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (is_plugin_active('extrachill-artist-platform/extrachill-artist-platform.php')) {
    add_filter('extrachill_admin_tools', function($tools) {
        $tools[] = array(
            'id' => 'artist-user-relationships',
            'title' => 'Artist-User Relationships',
            'description' => 'Manage relationships between users and artist profiles. Link users to artists, view all relationships, and detect orphaned data.',
            'callback' => 'ec_artist_user_relationships_page'
        );
        return $tools;
    }, 20);
}

function ec_artist_user_relationships_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    $view = isset($_GET['ec_view']) ? sanitize_text_field(wp_unslash($_GET['ec_view'])) : 'artists';
    $search = isset($_GET['ec_search']) ? sanitize_text_field(wp_unslash($_GET['ec_search'])) : '';

    ?>
    <style>
        .ec-relationships-tabs {
            margin: 20px 0;
            border-bottom: 1px solid #ccc;
        }
        .ec-relationships-tabs a {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            border: 1px solid #ccc;
            border-bottom: none;
            background: #f0f0f1;
            margin-right: 5px;
        }
        .ec-relationships-tabs a.active {
            background: white;
            font-weight: 600;
        }
        .ec-relationship-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .ec-relationship-table th,
        .ec-relationship-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .ec-relationship-table th {
            background: #f0f0f1;
            font-weight: 600;
        }
        .ec-relationship-table tr:hover {
            background: #f9f9f9;
        }
        .ec-member-count {
            display: inline-block;
            background: #2271b1;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
        .ec-member-list {
            list-style: none;
            margin: 10px 0;
            padding: 0;
        }
        .ec-member-list li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .ec-remove-link {
            color: #b32d2e;
            text-decoration: none;
            margin-left: 10px;
        }
        .ec-remove-link:hover {
            text-decoration: underline;
        }
        .ec-search-box {
            margin: 20px 0;
        }
        .ec-search-box input[type="text"] {
            width: 300px;
        }
        .ec-empty-state {
            padding: 40px;
            text-align: center;
            color: #646970;
        }
    </style>

    <div class="ec-relationships-wrap">
        <!-- Tab Navigation -->
        <div class="ec-relationships-tabs">
            <a href="<?php echo esc_url(add_query_arg('ec_view', 'artists')); ?>" class="<?php echo $view === 'artists' ? 'active' : ''; ?>">
                Artists
            </a>
            <a href="<?php echo esc_url(add_query_arg('ec_view', 'users')); ?>" class="<?php echo $view === 'users' ? 'active' : ''; ?>">
                Users
            </a>
            <a href="<?php echo esc_url(add_query_arg('ec_view', 'orphans')); ?>" class="<?php echo $view === 'orphans' ? 'active' : ''; ?>">
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

        <?php
        // Render appropriate view
        switch ($view) {
            case 'users':
                ec_render_users_view($search);
                break;
            case 'orphans':
                ec_render_orphans_view();
                break;
            case 'artists':
            default:
                ec_render_artists_view($search);
                break;
        }
        ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Handle remove member links
        $('.ec-remove-member').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Remove this relationship?')) {
                return;
            }

            var $link = $(this);
            var userId = $link.data('user-id');
            var artistId = $link.data('artist-id');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ec_remove_artist_user_relationship',
                    user_id: userId,
                    artist_id: artistId,
                    nonce: '<?php echo wp_create_nonce('ec_artist_user_relationships'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                }
            });
        });

        // Handle add member links
        $('.ec-link-user').on('click', function(e) {
            e.preventDefault();

            var artistId = $(this).data('artist-id');
            var userId = prompt('Enter User ID to link:');

            if (!userId) return;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ec_add_artist_user_relationship',
                    user_id: userId,
                    artist_id: artistId,
                    nonce: '<?php echo wp_create_nonce('ec_artist_user_relationships'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                }
            });
        });
    });
    </script>
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