<?php
/**
 * Artist Profile Ownership Repair Tool
 *
 * Comprehensive repair tool that fixes artist profile ownership at three levels:
 * 1. WordPress native (post_author field)
 * 2. User meta (_artist_profile_ids)
 * 3. Post meta (_artist_member_ids)
 *
 * Also sets ownership on child link pages.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('extrachill_admin_tools', function($tools) {
    $current_blog_id = get_current_blog_id();
    $artist_blog_id   = function_exists('ec_get_blog_id') ? ec_get_blog_id('artist') : null;

    if ($artist_blog_id && $current_blog_id === $artist_blog_id) {
        $tools[] = array(
            'id' => 'artist-ownership-repair',
            'title' => 'Artist Profile Ownership Repair',
            'description' => 'Comprehensive ownership repair: sets post_author, syncs bidirectional relationships, and updates link pages. Run AFTER adding users to platform.',
            'callback' => 'artist_ownership_repair_admin_page'
        );
    }

    return $tools;
}, 10);

add_action('wp_ajax_artist_ownership_repair', 'artist_ownership_repair_ajax_handler');

function artist_ownership_repair_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    // Get current statistics
    $stats = get_artist_ownership_stats();

    ?>
    <div class="wrap">
        <h1>Artist Profile Ownership Repair</h1>

        <div class="status-section" style="margin: 30px 0; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
            <h2>Current Status</h2>
            <p>This tool comprehensively repairs artist profile ownership at all three levels.</p>

            <div style="background: #f0f0f1; padding: 15px; margin-bottom: 20px; border-left: 4px solid #2271b1;">
                <h3>Ownership Statistics</h3>
                <ul>
                    <li><strong>Total Artist Profiles:</strong> <?php echo esc_html($stats['total_profiles']); ?></li>
                    <li><strong>With Members (working):</strong> <?php echo esc_html($stats['with_members']); ?></li>
                    <li><strong>With Post Author:</strong> <?php echo esc_html($stats['with_author']); ?></li>
                    <li><strong style="color: #dc3232;">Without Post Author:</strong> <?php echo esc_html($stats['without_author']); ?></li>
                    <li><strong style="color: #dc3232;">Without Members:</strong> <?php echo esc_html($stats['without_members']); ?></li>
                </ul>
            </div>

            <div style="background: #fff3cd; padding: 15px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                <h3>What This Tool Does</h3>
                <p><strong>For each artist profile:</strong></p>
                <ol>
                    <li><strong>Determine Owner</strong>:
                        <ul>
                            <li>Priority 1: Use existing <code>_artist_member_ids</code> if populated</li>
                            <li>Priority 2: Use <code>post_author</code> if > 0</li>
                            <li>Priority 3: Match by slug (post_name → user_login)</li>
                        </ul>
                    </li>
                    <li><strong>Set WordPress Native Ownership</strong>:
                        <ul>
                            <li>Set <code>post_author</code> on artist profile</li>
                            <li>Set <code>post_author</code> on all child link pages</li>
                        </ul>
                    </li>
                    <li><strong>Sync Bidirectional Relationship</strong>:
                        <ul>
                            <li>Update user meta <code>_artist_profile_ids</code></li>
                            <li>Update post meta <code>_artist_member_ids</code></li>
                        </ul>
                    </li>
                </ol>
            </div>

            <?php if ($stats['without_members'] > 0 || $stats['without_author'] > 0): ?>
                <button type="button" class="button button-primary" id="repair-ownership-button" style="margin-top: 20px;">
                    Repair Artist Profile Ownership
                </button>
            <?php else: ?>
                <div style="background: #d4edda; padding: 15px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                    <p style="margin: 0; color: #155724;"><strong>✓ All artist profiles have proper ownership!</strong></p>
                </div>
            <?php endif; ?>

            <div id="repair-ownership-progress" style="display: none; margin-top: 20px;">
                <p><strong>Repairing ownership...</strong></p>
                <p id="repair-ownership-status">Initializing...</p>
                <progress id="repair-ownership-progress-bar" style="width: 100%; height: 30px;"></progress>
            </div>

            <div id="repair-ownership-result" style="display: none; margin-top: 20px; padding: 15px; border-left: 4px solid #46b450; background: #f7f7f7;"></div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#repair-ownership-button').on('click', function() {
            if (!confirm('This will repair artist profile ownership comprehensively:\n\n- Set post_author on profiles and link pages\n- Sync bidirectional relationships\n- Update all ownership meta\n\nProceed?')) {
                return;
            }

            $('#repair-ownership-button').prop('disabled', true);
            $('#repair-ownership-progress').show();
            $('#repair-ownership-result').hide();
            $('#repair-ownership-status').text('Scanning artist profiles and repairing ownership...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'artist_ownership_repair',
                    nonce: '<?php echo wp_create_nonce('artist_ownership_repair_nonce'); ?>'
                },
                success: function(response) {
                    $('#repair-ownership-progress').hide();

                    if (response.success) {
                        var html = '<h3>Ownership Repair Complete!</h3>';
                        html += '<p>' + response.data.message + '</p>';
                        html += '<h4>Results:</h4>';
                        html += '<ul>';
                        html += '<li>Total Profiles Processed: <strong>' + response.data.stats.total_profiles + '</strong></li>';
                        html += '<li>Already Had Members: <strong>' + response.data.stats.already_had_members + '</strong></li>';
                        html += '<li>Repaired from post_author: <strong>' + response.data.stats.repaired_from_author + '</strong></li>';
                        html += '<li>Repaired from Slug Matching: <strong>' + response.data.stats.repaired_from_slug + '</strong></li>';
                        html += '<li>Members Synced: <strong>' + response.data.stats.synced_members + '</strong></li>';
                        html += '<li>Link Pages Updated: <strong>' + response.data.stats.link_pages_updated + '</strong></li>';
                        html += '<li>Unmatched Profiles: <strong>' + response.data.stats.unmatched + '</strong></li>';
                        html += '<li>Errors: <strong>' + response.data.stats.errors + '</strong></li>';
                        html += '</ul>';

                        if (response.data.unmatched_list && response.data.unmatched_list.length > 0) {
                            html += '<h4 style="color: #dc3232;">Unmatched Profiles (Manual Review Needed):</h4>';
                            html += '<ul>';
                            response.data.unmatched_list.forEach(function(profile) {
                                html += '<li>' + profile.title + ' (ID: ' + profile.id + ', Slug: ' + profile.slug + ')</li>';
                            });
                            html += '</ul>';
                            html += '<p>Use the "Artist-User Relationships" tool to manually assign owners for these profiles.</p>';
                        }

                        $('#repair-ownership-result')
                            .html(html)
                            .css('border-left-color', '#46b450')
                            .show();
                        $('#repair-ownership-button').hide();
                    } else {
                        $('#repair-ownership-result')
                            .html('<h3>Repair Failed</h3><p>' + response.data.message + '</p>')
                            .css('border-left-color', '#dc3232')
                            .show();
                        $('#repair-ownership-button').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#repair-ownership-progress').hide();
                    $('#repair-ownership-result')
                        .html('<h3>Error</h3><p>An error occurred during ownership repair.</p>')
                        .css('border-left-color', '#dc3232')
                        .show();
                    $('#repair-ownership-button').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

function get_artist_ownership_stats() {
    $stats = array(
        'total_profiles' => 0,
        'with_members' => 0,
        'without_members' => 0,
        'with_author' => 0,
        'without_author' => 0
    );

    $artists = get_posts(array(
        'post_type' => 'artist_profile',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));

    $stats['total_profiles'] = count($artists);

    foreach ($artists as $artist_id) {
        $artist = get_post($artist_id);

        // Check post_author
        if ($artist->post_author > 0) {
            $stats['with_author']++;
        } else {
            $stats['without_author']++;
        }

        // Check _artist_member_ids
        $member_ids = get_post_meta($artist_id, '_artist_member_ids', true);
        if (is_array($member_ids) && !empty($member_ids)) {
            $stats['with_members']++;
        } else {
            $stats['without_members']++;
        }
    }

    return $stats;
}

function match_user_by_slug($post_name) {
    // Try exact username match
    $user = get_user_by('login', $post_name);
    if ($user) {
        return $user->ID;
    }

    // Try lowercase
    $user = get_user_by('login', strtolower($post_name));
    if ($user) {
        return $user->ID;
    }

    // Try with hyphens replaced by spaces
    $user = get_user_by('login', str_replace('-', ' ', $post_name));
    if ($user) {
        return $user->ID;
    }

    // Try with spaces replaced by hyphens
    $user = get_user_by('login', str_replace(' ', '-', $post_name));
    if ($user) {
        return $user->ID;
    }

    return 0; // No match
}

function artist_ownership_repair_ajax_handler() {
    check_ajax_referer('artist_ownership_repair_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }

    // Check if artist platform plugin is active
    if (!function_exists('bp_add_artist_membership')) {
        wp_send_json_error(array('message' => 'Artist platform plugin not active or bp_add_artist_membership function not found'));
    }

    $stats = array(
        'total_profiles' => 0,
        'already_had_members' => 0,
        'repaired_from_author' => 0,
        'repaired_from_slug' => 0,
        'synced_members' => 0,
        'link_pages_updated' => 0,
        'unmatched' => 0,
        'errors' => 0
    );

    $unmatched_list = array();

    // Get all artist profiles
    $artists = get_posts(array(
        'post_type' => 'artist_profile',
        'post_status' => 'any',
        'posts_per_page' => -1
    ));

    $stats['total_profiles'] = count($artists);

    foreach ($artists as $artist) {
        $artist_id = $artist->ID;
        $owner_id = 0;
        $repair_method = '';

        // Step 1: Get ALL users who have this artist in their user meta (SOURCE OF TRUTH)
        $users_with_artist = function_exists('bp_get_linked_members') ? bp_get_linked_members($artist_id) : array();
        $correct_member_ids = array();

        foreach ($users_with_artist as $user) {
            $correct_member_ids[] = (int) $user->ID;
        }

        // Remove duplicates and ensure integers
        $correct_member_ids = array_unique(array_map('intval', $correct_member_ids));
        $correct_member_ids = array_values($correct_member_ids); // Re-index

        // Step 2: Determine owner based on user meta, post_author, or slug
        if (!empty($correct_member_ids)) {
            // Priority 1: Use first member from user meta (source of truth)
            $owner_id = $correct_member_ids[0];
            $stats['already_had_members']++;
            $repair_method = 'user_meta';
        } else if ($artist->post_author > 0) {
            // Priority 2: Use post_author if no user meta exists
            $owner_id = $artist->post_author;
            $correct_member_ids = array($owner_id);
            $stats['repaired_from_author']++;
            $repair_method = 'post_author';
        } else {
            // Priority 3: Match by slug
            $owner_id = match_user_by_slug($artist->post_name);
            if ($owner_id > 0) {
                $correct_member_ids = array($owner_id);
                $stats['repaired_from_slug']++;
                $repair_method = 'slug_match';
            } else {
                $stats['unmatched']++;
                $unmatched_list[] = array(
                    'id' => $artist_id,
                    'title' => $artist->post_title,
                    'slug' => $artist->post_name
                );
                error_log("Artist Ownership Repair: Could not match owner for artist {$artist_id} ({$artist->post_title}, slug: {$artist->post_name})");
                continue; // Skip to next artist
            }
        }

        // Set post_author on artist profile
        $update_result = wp_update_post(array(
            'ID' => $artist_id,
            'post_author' => $owner_id
        ), true);

        if (is_wp_error($update_result)) {
            $stats['errors']++;
            error_log("Artist Ownership Repair: Failed to update post_author for artist {$artist_id}: " . $update_result->get_error_message());
            continue;
        }

        // Set post_author on child link pages
        $link_pages = get_posts(array(
            'post_type' => 'artist_link_page',
            'post_parent' => $artist_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        foreach ($link_pages as $link_page_id) {
            $lp_result = wp_update_post(array(
                'ID' => $link_page_id,
                'post_author' => $owner_id
            ), true);

            if (!is_wp_error($lp_result)) {
                $stats['link_pages_updated']++;
            }
        }

        // Step 5: Sync artist meta to match user meta (REBUILD from source of truth)
        $current_artist_members = get_post_meta($artist_id, '_artist_member_ids', true);
        if (!is_array($current_artist_members)) {
            $current_artist_members = array();
        }

        // Compare and update if different
        sort($correct_member_ids);
        sort($current_artist_members);

        if ($correct_member_ids !== $current_artist_members) {
            $update_result = update_post_meta($artist_id, '_artist_member_ids', $correct_member_ids);
            if ($update_result) {
                $stats['synced_members']++;
                error_log("Artist Ownership Repair: Synced members for artist {$artist_id} ({$artist->post_title}) via {$repair_method} - members: [" . implode(', ', $correct_member_ids) . "]");
            } else {
                $stats['errors']++;
                error_log("Artist Ownership Repair: Failed to sync members for artist {$artist_id}");
            }
        } else {
            error_log("Artist Ownership Repair: Artist {$artist_id} ({$artist->post_title}) already in sync via {$repair_method} - owner: {$owner_id}");
        }

        // Step 6: Sync user meta for each member (rebuild bidirectional relationship)
        foreach ($correct_member_ids as $member_user_id) {
            $user_artist_ids = get_user_meta($member_user_id, '_artist_profile_ids', true);
            if (!is_array($user_artist_ids)) {
                $user_artist_ids = array();
            }

            // Add this artist ID if not already present
            if (!in_array($artist_id, $user_artist_ids)) {
                $user_artist_ids[] = $artist_id;
                $user_artist_ids = array_unique(array_map('intval', $user_artist_ids));
                update_user_meta($member_user_id, '_artist_profile_ids', $user_artist_ids);
                error_log("Artist Ownership Repair: Updated user meta for user {$member_user_id} to include artist {$artist_id}");
            }
        }
    }

    $message = "Successfully repaired ownership for " . ($stats['total_profiles'] - $stats['unmatched'] - $stats['errors']) . " artist profiles.";
    if ($stats['unmatched'] > 0) {
        $message .= " {$stats['unmatched']} profiles could not be matched automatically.";
    }
    if ($stats['errors'] > 0) {
        $message .= " {$stats['errors']} errors occurred (check error log).";
    }

    wp_send_json_success(array(
        'message' => $message,
        'stats' => $stats,
        'unmatched_list' => $unmatched_list
    ));
}
