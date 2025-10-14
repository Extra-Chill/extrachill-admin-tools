<?php
/**
 * Two-step artist platform migration from community.extrachill.com to artist.extrachill.com
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('extrachill_admin_tools', function($tools) {
    $current_blog_id = get_current_blog_id();

    if ($current_blog_id === 4) { // artist.extrachill.com
        $tools[] = array(
            'id' => 'artist-platform-migration',
            'title' => 'Artist Platform Migration',
            'description' => 'Migrate artist platform from community.extrachill.com to artist.extrachill.com with single-click direct copy.',
            'callback' => 'artist_platform_migration_admin_page'
        );
    }

    return $tools;
}, 10);

add_action('wp_ajax_ap_migration_migrate', 'ap_migration_ajax_migrate');
add_action('wp_ajax_ap_migration_cleanup', 'ap_migration_ajax_cleanup');

function artist_platform_migration_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    // Check if migration has been completed
    $migration_completed = get_option('ap_migration_completed', false);
    ?>
    <div class="wrap">
        <h1>Artist Platform Migration</h1>

        <div class="migration-section" style="margin: 30px 0; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
            <h2>Step 1: Migrate Data</h2>
            <p>This will copy all artist platform data from community.extrachill.com to this site (artist.extrachill.com).</p>
            <p><strong>Important:</strong> Backup your database at the server level before proceeding.</p>

            <?php
            // Check what data exists before migration
            global $wpdb;
            $temp_community_id = 2;
            switch_to_blog($temp_community_id);
            $temp_profiles_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'artist_profile'");
            $temp_link_pages_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'artist_link_page'");
            $temp_forums_count = 0;
            if (function_exists('bbp_get_forum_post_type')) {
                $temp_forums_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type = %s AND pm.meta_key = '_is_artist_profile_forum' AND pm.meta_value = '1'",
                    bbp_get_forum_post_type()
                ));
            }
            restore_current_blog();
            ?>
            <div style="background: #f0f0f1; padding: 15px; margin-bottom: 20px; border-left: 4px solid #2271b1;">
                <h3>Site Configuration</h3>
                <ul>
                    <li><strong>Current Site ID:</strong> <?php echo get_current_blog_id(); ?></li>
                    <li><strong>Source Site ID (community.extrachill.com):</strong> <?php echo $temp_community_id; ?></li>
                </ul>
            </div>

            <h3>Data to be copied from community.extrachill.com (blog ID <?php echo $temp_community_id; ?>):</h3>
            <ul>
                <li>Artist Profiles: <strong><?php echo $temp_profiles_count; ?></strong></li>
                <li>Link Pages: <strong><?php echo $temp_link_pages_count; ?></strong></li>
                <li>Artist Forums: <strong><?php echo $temp_forums_count; ?></strong></li>
                <li>Topics, Replies, Analytics, Subscribers (counted during migration)</li>
            </ul>
            <?php if ($temp_profiles_count == 0 && $temp_link_pages_count == 0): ?>
                <p style="color: #dc3232;"><strong>Warning:</strong> No artist platform data found on community site. Is the plugin active there?</p>
            <?php endif; ?>

            <button type="button" class="button" id="migrate-button" style="margin-top: 20px;">
                Migrate Artist Platform to This Site
            </button>

            <div id="migration-progress" style="display: none; margin-top: 20px;">
                <p><strong>Migration in progress...</strong></p>
                <p id="migration-status">Initializing...</p>
                <progress id="migration-progress-bar" style="width: 100%; height: 30px;"></progress>
            </div>

            <div id="migration-result" style="display: none; margin-top: 20px; padding: 15px; border-left: 4px solid #46b450; background: #f7f7f7;"></div>
        </div>

        <?php if ($migration_completed): ?>
        <div class="cleanup-section" style="margin: 30px 0; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
            <h2>Step 2: Cleanup Source Site</h2>
            <p>After verifying the migration was successful, you can delete all artist platform data from community.extrachill.com.</p>
            <p><strong style="color: #dc3232;">Warning:</strong> This operation is IRREVERSIBLE. All artist platform data will be permanently deleted from the community site.</p>

            <button type="button" class="button button-2" id="cleanup-button" style="margin-top: 20px;">
                Delete Artist Platform from Community Site
            </button>

            <div id="cleanup-progress" style="display: none; margin-top: 20px;">
                <p><strong>Cleanup in progress...</strong></p>
                <p id="cleanup-status">Initializing...</p>
            </div>

            <div id="cleanup-result" style="display: none; margin-top: 20px; padding: 15px; border-left: 4px solid #46b450; background: #f7f7f7;"></div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#migrate-button').on('click', function() {
            if (!confirm('This will migrate all artist platform data from community.extrachill.com to this site.\n\nHave you backed up your database?\n\nClick OK to proceed.')) {
                return;
            }

            $('#migrate-button').prop('disabled', true);
            $('#migration-progress').show();
            $('#migration-result').hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ap_migration_migrate',
                    nonce: '<?php echo wp_create_nonce('ap_migration_nonce'); ?>'
                },
                success: function(response) {
                    $('#migration-progress').hide();

                    if (response.success) {
                        $('#migration-result')
                            .html('<h3>Migration Complete!</h3>' +
                                  '<p>' + response.data.message + '</p>' +
                                  '<h4>Imported:</h4>' +
                                  '<ul>' +
                                  '<li>Artist Profiles: ' + response.data.breakdown.profiles + '</li>' +
                                  '<li>Link Pages: ' + response.data.breakdown.link_pages + '</li>' +
                                  '<li>Forums: ' + response.data.breakdown.forums + '</li>' +
                                  '<li>Topics: ' + response.data.breakdown.topics + '</li>' +
                                  '<li>Replies: ' + response.data.breakdown.replies + '</li>' +
                                  '<li>Analytics Views: ' + response.data.breakdown.views + '</li>' +
                                  '<li>Analytics Clicks: ' + response.data.breakdown.clicks + '</li>' +
                                  '<li>Subscribers: ' + response.data.breakdown.subscribers + '</li>' +
                                  '<li>Roster Relationships: ' + response.data.breakdown.roster_relationships + '</li>' +
                                  '<li>Media Files: ' + response.data.breakdown.attachments + '</li>' +
                                  '</ul>' +
                                  '<p><strong>Please reload this page to access the cleanup option.</strong></p>')
                            .css('border-left-color', '#46b450')
                            .show();
                    } else {
                        $('#migration-result')
                            .html('<h3>Migration Failed</h3><p>' + response.data.message + '</p>')
                            .css('border-left-color', '#dc3232')
                            .show();
                        $('#migrate-button').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#migration-progress').hide();
                    $('#migration-result')
                        .html('<h3>Error</h3><p>An error occurred during migration.</p>')
                        .css('border-left-color', '#dc3232')
                        .show();
                    $('#migrate-button').prop('disabled', false);
                }
            });
        });

        $('#cleanup-button').on('click', function() {
            if (!confirm('THIS WILL PERMANENTLY DELETE ALL ARTIST PLATFORM DATA FROM COMMUNITY.EXTRACHILL.COM.\n\nThis includes:\n- All artist profiles\n- All link pages\n- All artist forums, topics, and replies\n- All analytics data\n- All subscriber data\n\nThis operation CANNOT be undone.\n\nAre you absolutely sure?')) {
                return;
            }

            if (!confirm('FINAL CONFIRMATION: Delete all artist platform data from community site?')) {
                return;
            }

            $('#cleanup-button').prop('disabled', true);
            $('#cleanup-progress').show();
            $('#cleanup-result').hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ap_migration_cleanup',
                    nonce: '<?php echo wp_create_nonce('ap_migration_nonce'); ?>'
                },
                success: function(response) {
                    $('#cleanup-progress').hide();

                    if (response.success) {
                        $('#cleanup-result')
                            .html('<h3>Cleanup Complete!</h3>' +
                                  '<p>' + response.data.message + '</p>' +
                                  '<h4>Deleted from community.extrachill.com:</h4>' +
                                  '<ul>' +
                                  '<li>Artist Profiles: ' + response.data.deleted.profiles + '</li>' +
                                  '<li>Link Pages: ' + response.data.deleted.link_pages + '</li>' +
                                  '<li>Forums: ' + response.data.deleted.forums + '</li>' +
                                  '<li>Topics: ' + response.data.deleted.topics + '</li>' +
                                  '<li>Replies: ' + response.data.deleted.replies + '</li>' +
                                  '<li>Analytics Records: ' + response.data.deleted.analytics + '</li>' +
                                  '<li>Subscribers: ' + response.data.deleted.subscribers + '</li>' +
                                  '<li>Roster Relationships: ' + response.data.deleted.roster_relationships + '</li>' +
                                  '</ul>')
                            .css('border-left-color', '#46b450')
                            .show();
                        $('#cleanup-button').hide();
                    } else {
                        $('#cleanup-result')
                            .html('<h3>Cleanup Failed</h3><p>' + response.data.message + '</p>')
                            .css('border-left-color', '#dc3232')
                            .show();
                        $('#cleanup-button').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#cleanup-progress').hide();
                    $('#cleanup-result')
                        .html('<h3>Error</h3><p>An error occurred during cleanup.</p>')
                        .css('border-left-color', '#dc3232')
                        .show();
                    $('#cleanup-button').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

function ap_migration_ajax_migrate() {
    check_ajax_referer('ap_migration_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }

    global $wpdb;
    $community_blog_id = 2; // community.extrachill.com
    $imported_counts = array(
        'profiles' => 0,
        'link_pages' => 0,
        'forums' => 0,
        'topics' => 0,
        'replies' => 0,
        'views' => 0,
        'clicks' => 0,
        'subscribers' => 0,
        'roster_relationships' => 0
    );

    // Switch to community site to read data
    switch_to_blog($community_blog_id);

    // Debug: Log what we're about to query
    error_log('AP Migration: Switched to blog ' . $community_blog_id);
    error_log('AP Migration: Current wpdb prefix: ' . $wpdb->prefix);
    error_log('AP Migration: Posts table: ' . $wpdb->posts);

    // Get artist profiles (direct query bypasses post type registration)
    $profiles = $wpdb->get_results("
        SELECT * FROM {$wpdb->posts}
        WHERE post_type = 'artist_profile'
    ");

    error_log('AP Migration: Found ' . count($profiles) . ' artist profiles');
    if (empty($profiles)) {
        error_log('AP Migration: Profile query: SELECT * FROM ' . $wpdb->posts . ' WHERE post_type = "artist_profile"');
    }

    $profiles_data = array();
    foreach ($profiles as $profile) {
        $profiles_data[] = array(
            'ID' => $profile->ID,
            'post_title' => $profile->post_title,
            'post_content' => $profile->post_content,
            'post_status' => $profile->post_status,
            'post_author' => $profile->post_author,
            'post_date' => $profile->post_date,
            'post_date_gmt' => $profile->post_date_gmt,
            'post_modified' => $profile->post_modified,
            'post_modified_gmt' => $profile->post_modified_gmt,
            'post_name' => $profile->post_name,
            'meta' => get_post_meta($profile->ID),
            'thumbnail_id' => get_post_thumbnail_id($profile->ID)
        );
    }

    // Get link pages (direct query bypasses post type registration)
    $link_pages = $wpdb->get_results("
        SELECT * FROM {$wpdb->posts}
        WHERE post_type = 'artist_link_page'
    ");

    error_log('AP Migration: Found ' . count($link_pages) . ' link pages');

    $pages_data = array();
    foreach ($link_pages as $page) {
        $pages_data[] = array(
            'ID' => $page->ID,
            'post_title' => $page->post_title,
            'post_content' => $page->post_content,
            'post_status' => $page->post_status,
            'post_author' => $page->post_author,
            'post_parent' => $page->post_parent,
            'post_date' => $page->post_date,
            'post_date_gmt' => $page->post_date_gmt,
            'post_modified' => $page->post_modified,
            'post_modified_gmt' => $page->post_modified_gmt,
            'post_name' => $page->post_name,
            'meta' => get_post_meta($page->ID),
            'thumbnail_id' => get_post_thumbnail_id($page->ID)
        );
    }

    // Get forums, topics, replies if bbPress active
    $forums_data = array();
    $topics_data = array();
    $replies_data = array();

    error_log('AP Migration: bbPress active: ' . (function_exists('bbp_get_forum_post_type') ? 'yes' : 'no'));

    if (function_exists('bbp_get_forum_post_type')) {
        $forum_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND pm.meta_key = '_is_artist_profile_forum'
            AND pm.meta_value = '1'",
            bbp_get_forum_post_type()
        ));

        foreach ($forum_ids as $forum_id) {
            $forum = get_post($forum_id);
            $forums_data[] = array(
                'ID' => $forum->ID,
                'post_title' => $forum->post_title,
                'post_content' => $forum->post_content,
                'post_status' => $forum->post_status,
                'post_author' => $forum->post_author,
                'post_parent' => $forum->post_parent,
                'post_date' => $forum->post_date,
                'post_date_gmt' => $forum->post_date_gmt,
                'post_name' => $forum->post_name,
                'meta' => get_post_meta($forum->ID)
            );

            // Get topics (direct query bypasses post type registration)
            $topics = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->posts}
                WHERE post_type = %s
                AND post_parent = %d
            ", bbp_get_topic_post_type(), $forum->ID));

            foreach ($topics as $topic) {
                $topics_data[] = array(
                    'ID' => $topic->ID,
                    'post_title' => $topic->post_title,
                    'post_content' => $topic->post_content,
                    'post_status' => $topic->post_status,
                    'post_author' => $topic->post_author,
                    'post_parent' => $topic->post_parent,
                    'post_date' => $topic->post_date,
                    'post_date_gmt' => $topic->post_date_gmt,
                    'post_name' => $topic->post_name,
                    'meta' => get_post_meta($topic->ID)
                );

                // Get replies (direct query bypasses post type registration)
                $replies = $wpdb->get_results($wpdb->prepare("
                    SELECT * FROM {$wpdb->posts}
                    WHERE post_type = %s
                    AND post_parent = %d
                ", bbp_get_reply_post_type(), $topic->ID));

                foreach ($replies as $reply) {
                    $replies_data[] = array(
                        'ID' => $reply->ID,
                        'post_content' => $reply->post_content,
                        'post_status' => $reply->post_status,
                        'post_author' => $reply->post_author,
                        'post_parent' => $reply->post_parent,
                        'post_date' => $reply->post_date,
                        'post_date_gmt' => $reply->post_date_gmt,
                        'meta' => get_post_meta($reply->ID)
                    );
                }
            }
        }
    }

    // Get analytics data
    $views_data = array();
    $clicks_data = array();
    $views_table = $wpdb->prefix . 'extrch_link_page_daily_views';
    $clicks_table = $wpdb->prefix . 'extrch_link_page_daily_link_clicks';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$views_table}'") === $views_table) {
        $views_data = $wpdb->get_results("SELECT * FROM {$views_table}", ARRAY_A);
    }
    if ($wpdb->get_var("SHOW TABLES LIKE '{$clicks_table}'") === $clicks_table) {
        $clicks_data = $wpdb->get_results("SELECT * FROM {$clicks_table}", ARRAY_A);
    }

    // Get subscribers
    $subscribers_data = array();
    $subscribers_table = $wpdb->prefix . 'artist_subscribers';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$subscribers_table}'") === $subscribers_table) {
        $subscribers_data = $wpdb->get_results("SELECT * FROM {$subscribers_table}", ARRAY_A);
    }

    // Get roster relationships (user meta linking users to artist profiles)
    $roster_relationships = array();
    $all_users = get_users(array('fields' => 'ID'));
    foreach ($all_users as $user_id) {
        $artist_profile_ids = get_user_meta($user_id, '_artist_profile_ids', true);
        if (!empty($artist_profile_ids) && is_array($artist_profile_ids)) {
            // Filter to only include artist profiles being migrated
            $migrated_ids = array_intersect($artist_profile_ids, wp_list_pluck($profiles_data, 'ID'));
            if (!empty($migrated_ids)) {
                $roster_relationships[$user_id] = $migrated_ids;
            }
        }
    }

    // Switch back to artist site for import
    restore_current_blog();

    error_log('AP Migration: Starting import phase on blog ' . get_current_blog_id());
    error_log('AP Migration: Will import ' . count($profiles_data) . ' profiles');
    error_log('AP Migration: Will import ' . count($pages_data) . ' link pages');

    // Import artist profiles via direct database insert (granular checking)
    foreach ($profiles_data as $profile_data) {
        // Check if post exists
        $post_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE ID = %d",
            $profile_data['ID']
        ));

        if (!$post_exists) {
            // Only import post if missing
            error_log('AP Migration: Importing profile ID ' . $profile_data['ID'] . ': ' . $profile_data['post_title']);

            $result = $wpdb->insert(
                $wpdb->posts,
                array(
                    'ID' => $profile_data['ID'],
                    'post_type' => 'artist_profile',
                    'post_title' => $profile_data['post_title'],
                    'post_content' => $profile_data['post_content'],
                    'post_status' => $profile_data['post_status'],
                    'post_author' => $profile_data['post_author'],
                    'post_date' => $profile_data['post_date'],
                    'post_date_gmt' => $profile_data['post_date_gmt'],
                    'post_modified' => $profile_data['post_modified'],
                    'post_modified_gmt' => $profile_data['post_modified_gmt'],
                    'post_name' => $profile_data['post_name'],
                    'guid' => ''
                ),
                array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result) {
                error_log('AP Migration: Successfully imported profile ' . $profile_data['ID']);
                $imported_counts['profiles']++;
            } else {
                error_log('AP Migration: Failed to import profile ' . $profile_data['ID'] . ': ' . $wpdb->last_error);
            }
        }

        // Import meta (independent of post import - check each key)
        foreach ($profile_data['meta'] as $key => $values) {
            $meta_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta}
                 WHERE post_id = %d AND meta_key = %s",
                $profile_data['ID'],
                $key
            ));

            if (!$meta_exists) {
                $wpdb->insert(
                    $wpdb->postmeta,
                    array(
                        'post_id' => $profile_data['ID'],
                        'meta_key' => $key,
                        'meta_value' => $values[0]
                    ),
                    array('%d', '%s', '%s')
                );
            }
        }

        // Import thumbnail (independent of post import)
        if ($profile_data['thumbnail_id']) {
            $thumbnail_meta_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta}
                 WHERE post_id = %d AND meta_key = '_thumbnail_id'",
                $profile_data['ID']
            ));

            if (!$thumbnail_meta_exists) {
                $wpdb->insert(
                    $wpdb->postmeta,
                    array(
                        'post_id' => $profile_data['ID'],
                        'meta_key' => '_thumbnail_id',
                        'meta_value' => $profile_data['thumbnail_id']
                    ),
                    array('%d', '%s', '%s')
                );
            }
        }
    }

    // Import link pages via direct database insert (granular checking)
    foreach ($pages_data as $page_data) {
        // Check if post exists
        $post_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE ID = %d",
            $page_data['ID']
        ));

        if (!$post_exists) {
            // Only import post if missing
            $result = $wpdb->insert(
                $wpdb->posts,
                array(
                    'ID' => $page_data['ID'],
                    'post_type' => 'artist_link_page',
                    'post_title' => $page_data['post_title'],
                    'post_content' => $page_data['post_content'],
                    'post_status' => $page_data['post_status'],
                    'post_author' => $page_data['post_author'],
                    'post_parent' => $page_data['post_parent'],
                    'post_date' => $page_data['post_date'],
                    'post_date_gmt' => $page_data['post_date_gmt'],
                    'post_modified' => $page_data['post_modified'],
                    'post_modified_gmt' => $page_data['post_modified_gmt'],
                    'post_name' => $page_data['post_name'],
                    'guid' => ''
                ),
                array('%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result) {
                $imported_counts['link_pages']++;
            }
        }

        // Import meta (independent of post import - check each key)
        foreach ($page_data['meta'] as $key => $values) {
            $meta_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta}
                 WHERE post_id = %d AND meta_key = %s",
                $page_data['ID'],
                $key
            ));

            if (!$meta_exists) {
                $wpdb->insert(
                    $wpdb->postmeta,
                    array(
                        'post_id' => $page_data['ID'],
                        'meta_key' => $key,
                        'meta_value' => $values[0]
                    ),
                    array('%d', '%s', '%s')
                );
            }
        }

        // Import thumbnail (independent of post import)
        if ($page_data['thumbnail_id']) {
            $thumbnail_meta_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta}
                 WHERE post_id = %d AND meta_key = '_thumbnail_id'",
                $page_data['ID']
            ));

            if (!$thumbnail_meta_exists) {
                $wpdb->insert(
                    $wpdb->postmeta,
                    array(
                        'post_id' => $page_data['ID'],
                        'meta_key' => '_thumbnail_id',
                        'meta_value' => $page_data['thumbnail_id']
                    ),
                    array('%d', '%s', '%s')
                );
            }
        }
    }

    // Import forums via direct database insert (granular checking)
    if (function_exists('bbp_get_forum_post_type')) {
        foreach ($forums_data as $forum_data) {
            $post_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE ID = %d",
                $forum_data['ID']
            ));

            if (!$post_exists) {
                $result = $wpdb->insert(
                    $wpdb->posts,
                    array(
                        'ID' => $forum_data['ID'],
                        'post_type' => bbp_get_forum_post_type(),
                        'post_title' => $forum_data['post_title'],
                        'post_content' => $forum_data['post_content'],
                        'post_status' => $forum_data['post_status'],
                        'post_author' => $forum_data['post_author'],
                        'post_parent' => $forum_data['post_parent'],
                        'post_date' => $forum_data['post_date'],
                        'post_date_gmt' => $forum_data['post_date_gmt'],
                        'post_name' => $forum_data['post_name'],
                        'guid' => ''
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
                );

                if ($result) {
                    $imported_counts['forums']++;
                }
            }

            // Import meta (independent of post import)
            foreach ($forum_data['meta'] as $key => $values) {
                $meta_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT meta_id FROM {$wpdb->postmeta}
                     WHERE post_id = %d AND meta_key = %s",
                    $forum_data['ID'],
                    $key
                ));

                if (!$meta_exists) {
                    $wpdb->insert(
                        $wpdb->postmeta,
                        array(
                            'post_id' => $forum_data['ID'],
                            'meta_key' => $key,
                            'meta_value' => is_array($values) ? $values[0] : $values
                        ),
                        array('%d', '%s', '%s')
                    );
                }
            }
        }

        // Import topics via direct database insert (granular checking)
        foreach ($topics_data as $topic_data) {
            $post_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE ID = %d",
                $topic_data['ID']
            ));

            if (!$post_exists) {
                $result = $wpdb->insert(
                    $wpdb->posts,
                    array(
                        'ID' => $topic_data['ID'],
                        'post_type' => bbp_get_topic_post_type(),
                        'post_title' => $topic_data['post_title'],
                        'post_content' => $topic_data['post_content'],
                        'post_status' => $topic_data['post_status'],
                        'post_author' => $topic_data['post_author'],
                        'post_parent' => $topic_data['post_parent'],
                        'post_date' => $topic_data['post_date'],
                        'post_date_gmt' => $topic_data['post_date_gmt'],
                        'post_name' => $topic_data['post_name'],
                        'guid' => ''
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
                );

                if ($result) {
                    $imported_counts['topics']++;
                }
            }

            // Import meta (independent of post import)
            foreach ($topic_data['meta'] as $key => $values) {
                $meta_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT meta_id FROM {$wpdb->postmeta}
                     WHERE post_id = %d AND meta_key = %s",
                    $topic_data['ID'],
                    $key
                ));

                if (!$meta_exists) {
                    $wpdb->insert(
                        $wpdb->postmeta,
                        array(
                            'post_id' => $topic_data['ID'],
                            'meta_key' => $key,
                            'meta_value' => is_array($values) ? $values[0] : $values
                        ),
                        array('%d', '%s', '%s')
                    );
                }
            }
        }

        // Import replies via direct database insert (granular checking)
        foreach ($replies_data as $reply_data) {
            $post_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE ID = %d",
                $reply_data['ID']
            ));

            if (!$post_exists) {
                $result = $wpdb->insert(
                    $wpdb->posts,
                    array(
                        'ID' => $reply_data['ID'],
                        'post_type' => bbp_get_reply_post_type(),
                        'post_content' => $reply_data['post_content'],
                        'post_status' => $reply_data['post_status'],
                        'post_author' => $reply_data['post_author'],
                        'post_parent' => $reply_data['post_parent'],
                        'post_date' => $reply_data['post_date'],
                        'post_date_gmt' => $reply_data['post_date_gmt'],
                        'guid' => ''
                    ),
                    array('%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
                );

                if ($result) {
                    $imported_counts['replies']++;
                }
            }

            // Import meta (independent of post import)
            foreach ($reply_data['meta'] as $key => $values) {
                $meta_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT meta_id FROM {$wpdb->postmeta}
                     WHERE post_id = %d AND meta_key = %s",
                    $reply_data['ID'],
                    $key
                ));

                if (!$meta_exists) {
                    $wpdb->insert(
                        $wpdb->postmeta,
                        array(
                            'post_id' => $reply_data['ID'],
                            'meta_key' => $key,
                            'meta_value' => is_array($values) ? $values[0] : $values
                        ),
                        array('%d', '%s', '%s')
                    );
                }
            }
        }
    }

    // Import analytics
    $dest_views_table = $wpdb->prefix . 'extrch_link_page_daily_views';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$dest_views_table}'") === $dest_views_table) {
        foreach ($views_data as $view) {
            unset($view['view_id']); // Let database auto-increment
            $wpdb->insert($dest_views_table, $view);
            $imported_counts['views']++;
        }
    }

    $dest_clicks_table = $wpdb->prefix . 'extrch_link_page_daily_link_clicks';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$dest_clicks_table}'") === $dest_clicks_table) {
        foreach ($clicks_data as $click) {
            unset($click['click_id']); // Let database auto-increment
            $wpdb->insert($dest_clicks_table, $click);
            $imported_counts['clicks']++;
        }
    }

    // Import subscribers
    $dest_subscribers_table = $wpdb->prefix . 'artist_subscribers';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$dest_subscribers_table}'") === $dest_subscribers_table) {
        foreach ($subscribers_data as $subscriber) {
            unset($subscriber['subscriber_id']); // Let database auto-increment
            $wpdb->insert($dest_subscribers_table, $subscriber);
            $imported_counts['subscribers']++;
        }
    }

    // Import roster relationships (user meta is network-wide, no blog switching needed)
    foreach ($roster_relationships as $user_id => $artist_profile_ids) {
        update_user_meta($user_id, '_artist_profile_ids', $artist_profile_ids);
        $imported_counts['roster_relationships']++;
    }

    // Migrate media files (attachments)
    $imported_counts['attachments'] = 0;

    // Collect all attachment IDs
    $attachment_ids = array();
    foreach ($profiles_data as $profile) {
        if ($profile['thumbnail_id']) {
            $attachment_ids[] = $profile['thumbnail_id'];
        }
        // Check for header images in meta
        if (isset($profile['meta']['_artist_profile_header_image_id'][0])) {
            $attachment_ids[] = $profile['meta']['_artist_profile_header_image_id'][0];
            error_log('AP Migration: Found header image ID ' . $profile['meta']['_artist_profile_header_image_id'][0] . ' for profile ' . $profile['ID']);
        }
    }
    foreach ($pages_data as $page) {
        if ($page['thumbnail_id']) {
            $attachment_ids[] = $page['thumbnail_id'];
        }
        // Check for background images in meta
        if (isset($page['meta']['_link_page_background_image_id'][0])) {
            $attachment_ids[] = $page['meta']['_link_page_background_image_id'][0];
        }
    }

    $attachment_ids = array_unique(array_filter($attachment_ids));

    error_log('AP Migration: Total unique attachment IDs to migrate: ' . count($attachment_ids));
    error_log('AP Migration: Attachment IDs: ' . implode(', ', $attachment_ids));

    if (!empty($attachment_ids)) {
        // Switch to community site to get attachment data
        switch_to_blog($community_blog_id);

        $upload_dir_source = wp_upload_dir();

        foreach ($attachment_ids as $attachment_id) {
            error_log('AP Migration: Processing attachment ID ' . $attachment_id);
            $attachment = get_post($attachment_id);
            if (!$attachment || $attachment->post_type !== 'attachment') {
                error_log('AP Migration: Attachment ID ' . $attachment_id . ' not found or not an attachment type');
                continue;
            }

            $attachment_data = array(
                'ID' => $attachment->ID,
                'post_title' => $attachment->post_title,
                'post_content' => $attachment->post_content,
                'post_excerpt' => $attachment->post_excerpt,
                'post_status' => $attachment->post_status,
                'post_author' => $attachment->post_author,
                'post_date' => $attachment->post_date,
                'post_date_gmt' => $attachment->post_date_gmt,
                'post_name' => $attachment->post_name,
                'post_mime_type' => $attachment->post_mime_type,
                'guid' => $attachment->guid,
                'meta' => get_post_meta($attachment->ID),
                'file_path' => get_attached_file($attachment->ID),
                'metadata' => wp_get_attachment_metadata($attachment->ID)
            );

            // Switch back to artist site
            restore_current_blog();

            // Check if attachment already exists
            if (get_post($attachment_data['ID'])) {
                error_log('AP Migration: Attachment ID ' . $attachment_data['ID'] . ' already exists, skipping');
                switch_to_blog($community_blog_id);
                continue;
            }

            error_log('AP Migration: Attachment ID ' . $attachment_data['ID'] . ' does not exist, will attempt copy');
            $upload_dir_dest = wp_upload_dir();

            // Copy physical file
            if ($attachment_data['file_path'] && file_exists($attachment_data['file_path'])) {
                // Get relative path from uploads directory
                $relative_path = str_replace($upload_dir_source['basedir'], '', $attachment_data['file_path']);
                $dest_file_path = $upload_dir_dest['basedir'] . $relative_path;

                // Create directory if needed
                $dest_dir = dirname($dest_file_path);
                if (!file_exists($dest_dir)) {
                    wp_mkdir_p($dest_dir);
                }

                // Copy file
                if (copy($attachment_data['file_path'], $dest_file_path)) {
                    error_log('AP Migration: Successfully copied file for attachment ID ' . $attachment_data['ID'] . ' from ' . $attachment_data['file_path'] . ' to ' . $dest_file_path);
                    // Direct database insert for attachment
                    $result = $wpdb->insert(
                        $wpdb->posts,
                        array(
                            'ID' => $attachment_data['ID'],
                            'post_type' => 'attachment',
                            'post_title' => $attachment_data['post_title'],
                            'post_content' => $attachment_data['post_content'],
                            'post_excerpt' => $attachment_data['post_excerpt'],
                            'post_status' => $attachment_data['post_status'],
                            'post_author' => $attachment_data['post_author'],
                            'post_date' => $attachment_data['post_date'],
                            'post_date_gmt' => $attachment_data['post_date_gmt'],
                            'post_name' => $attachment_data['post_name'],
                            'post_mime_type' => $attachment_data['post_mime_type'],
                            'guid' => $attachment_data['guid']
                        ),
                        array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
                    );

                    if ($result) {
                        // Copy attachment meta
                        foreach ($attachment_data['meta'] as $key => $values) {
                            $wpdb->insert(
                                $wpdb->postmeta,
                                array(
                                    'post_id' => $attachment_data['ID'],
                                    'meta_key' => $key,
                                    'meta_value' => $values[0]
                                ),
                                array('%d', '%s', '%s')
                            );
                        }

                        // Update attachment metadata
                        if ($attachment_data['metadata']) {
                            $wpdb->insert(
                                $wpdb->postmeta,
                                array(
                                    'post_id' => $attachment_data['ID'],
                                    'meta_key' => '_wp_attachment_metadata',
                                    'meta_value' => maybe_serialize($attachment_data['metadata'])
                                ),
                                array('%d', '%s', '%s')
                            );
                        }

                        // Update attached file path
                        $wpdb->insert(
                            $wpdb->postmeta,
                            array(
                                'post_id' => $attachment_data['ID'],
                                'meta_key' => '_wp_attached_file',
                                'meta_value' => str_replace($upload_dir_source['basedir'] . '/', '', $dest_file_path)
                            ),
                            array('%d', '%s', '%s')
                        );

                        $imported_counts['attachments']++;

                        // Copy thumbnail sizes if they exist
                        if (isset($attachment_data['metadata']['sizes']) && is_array($attachment_data['metadata']['sizes'])) {
                            $source_dir = dirname($attachment_data['file_path']);
                            $dest_dir = dirname($dest_file_path);

                            foreach ($attachment_data['metadata']['sizes'] as $size_data) {
                                $source_thumb = $source_dir . '/' . $size_data['file'];
                                $dest_thumb = $dest_dir . '/' . $size_data['file'];

                                if (file_exists($source_thumb)) {
                                    copy($source_thumb, $dest_thumb);
                                }
                            }
                        }
                    }
                }
            }

            // Switch back to community site for next iteration
            switch_to_blog($community_blog_id);
        }

        restore_current_blog();
    }

    // Import analytics views via direct database insert
    if (!empty($views_data)) {
        $views_table = $wpdb->prefix . 'extrch_link_page_daily_views';

        foreach ($views_data as $view_row) {
            // Check if record already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT view_id FROM {$views_table}
                 WHERE link_page_id = %d AND stat_date = %s",
                $view_row['link_page_id'],
                $view_row['stat_date']
            ));

            if (!$exists) {
                $wpdb->insert(
                    $views_table,
                    array(
                        'link_page_id' => $view_row['link_page_id'],
                        'stat_date' => $view_row['stat_date'],
                        'view_count' => $view_row['view_count']
                    ),
                    array('%d', '%s', '%d')
                );

                if ($wpdb->insert_id) {
                    $imported_counts['views']++;
                }
            }
        }
    }

    // Import analytics clicks via direct database insert
    if (!empty($clicks_data)) {
        $clicks_table = $wpdb->prefix . 'extrch_link_page_daily_link_clicks';

        foreach ($clicks_data as $click_row) {
            // Check if record already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT click_id FROM {$clicks_table}
                 WHERE link_page_id = %d AND stat_date = %s AND link_url = %s",
                $click_row['link_page_id'],
                $click_row['stat_date'],
                $click_row['link_url']
            ));

            if (!$exists) {
                $wpdb->insert(
                    $clicks_table,
                    array(
                        'link_page_id' => $click_row['link_page_id'],
                        'stat_date' => $click_row['stat_date'],
                        'link_url' => $click_row['link_url'],
                        'click_count' => $click_row['click_count']
                    ),
                    array('%d', '%s', '%s', '%d')
                );

                if ($wpdb->insert_id) {
                    $imported_counts['clicks']++;
                }
            }
        }
    }

    // Import subscribers via direct database insert
    if (!empty($subscribers_data)) {
        $subscribers_table = $wpdb->prefix . 'artist_subscribers';

        foreach ($subscribers_data as $subscriber_row) {
            // Check if record already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT subscriber_id FROM {$subscribers_table}
                 WHERE subscriber_email = %s AND artist_profile_id = %d",
                $subscriber_row['subscriber_email'],
                $subscriber_row['artist_profile_id']
            ));

            if (!$exists) {
                $insert_data = array(
                    'artist_profile_id' => $subscriber_row['artist_profile_id'],
                    'subscriber_email' => $subscriber_row['subscriber_email'],
                    'subscribed_at' => $subscriber_row['subscribed_at'],
                    'exported' => $subscriber_row['exported']
                );

                $insert_format = array('%d', '%s', '%s', '%d');

                // Optional fields (may not exist in all rows)
                if (isset($subscriber_row['user_id'])) {
                    $insert_data['user_id'] = $subscriber_row['user_id'];
                    $insert_format[] = '%d';
                }
                if (isset($subscriber_row['username'])) {
                    $insert_data['username'] = $subscriber_row['username'];
                    $insert_format[] = '%s';
                }
                if (isset($subscriber_row['source'])) {
                    $insert_data['source'] = $subscriber_row['source'];
                    $insert_format[] = '%s';
                }

                $wpdb->insert($subscribers_table, $insert_data, $insert_format);

                if ($wpdb->insert_id) {
                    $imported_counts['subscribers']++;
                }
            }
        }
    }

    // Import roster relationships (user meta is network-wide, no blog switching needed)
    foreach ($roster_relationships as $user_id => $artist_profile_ids) {
        // Get existing roster for this user
        $existing_roster = get_user_meta($user_id, '_artist_profile_ids', true);
        if (!is_array($existing_roster)) {
            $existing_roster = array();
        }

        // Merge with migrated artist IDs (avoid duplicates)
        $merged_roster = array_unique(array_merge($existing_roster, $artist_profile_ids));

        // Update user meta with merged roster
        update_user_meta($user_id, '_artist_profile_ids', $merged_roster);
        $imported_counts['roster_relationships']++;

        // Sync bidirectional relationship: update artist post meta for each artist
        foreach ($merged_roster as $artist_id) {
            $current_member_ids = get_post_meta($artist_id, '_artist_member_ids', true);
            if (!is_array($current_member_ids)) {
                $current_member_ids = array();
            }

            if (!in_array($user_id, $current_member_ids)) {
                $current_member_ids[] = $user_id;
                $current_member_ids = array_unique(array_map('absint', $current_member_ids));
                update_post_meta($artist_id, '_artist_member_ids', $current_member_ids);
            }
        }
    }

    error_log('AP Migration: Import phase complete');
    error_log('AP Migration: Final counts - Profiles: ' . $imported_counts['profiles'] . ', Link Pages: ' . $imported_counts['link_pages'] . ', Forums: ' . $imported_counts['forums']);

    // Mark migration as completed
    update_option('ap_migration_completed', true);

    $total = array_sum($imported_counts);

    wp_send_json_success(array(
        'message' => "Successfully migrated {$total} items from community.extrachill.com",
        'breakdown' => $imported_counts
    ));
}

function ap_migration_ajax_cleanup() {
    check_ajax_referer('ap_migration_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }

    global $wpdb;
    $community_blog_id = 2; // community.extrachill.com
    $deleted_counts = array(
        'profiles' => 0,
        'link_pages' => 0,
        'forums' => 0,
        'topics' => 0,
        'replies' => 0,
        'analytics' => 0,
        'subscribers' => 0,
        'roster_relationships' => 0
    );

    // Switch to community site
    switch_to_blog($community_blog_id);

    // Delete artist profiles (direct query bypasses post type registration)
    $profiles = $wpdb->get_results("
        SELECT * FROM {$wpdb->posts}
        WHERE post_type = 'artist_profile'
    ");
    foreach ($profiles as $profile) {
        wp_delete_post($profile->ID, true);
        $deleted_counts['profiles']++;
    }

    // Delete link pages (direct query bypasses post type registration)
    $link_pages = $wpdb->get_results("
        SELECT * FROM {$wpdb->posts}
        WHERE post_type = 'artist_link_page'
    ");
    foreach ($link_pages as $page) {
        wp_delete_post($page->ID, true);
        $deleted_counts['link_pages']++;
    }

    // Delete forums, topics, replies
    if (function_exists('bbp_get_forum_post_type')) {
        $forum_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND pm.meta_key = '_is_artist_profile_forum'
            AND pm.meta_value = '1'",
            bbp_get_forum_post_type()
        ));

        foreach ($forum_ids as $forum_id) {
            // Delete topics and replies first (direct query bypasses post type registration)
            $topics = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->posts}
                WHERE post_type = %s
                AND post_parent = %d
            ", bbp_get_topic_post_type(), $forum_id));

            foreach ($topics as $topic) {
                $replies = $wpdb->get_results($wpdb->prepare("
                    SELECT * FROM {$wpdb->posts}
                    WHERE post_type = %s
                    AND post_parent = %d
                ", bbp_get_reply_post_type(), $topic->ID));

                foreach ($replies as $reply) {
                    wp_delete_post($reply->ID, true);
                    $deleted_counts['replies']++;
                }

                wp_delete_post($topic->ID, true);
                $deleted_counts['topics']++;
            }

            wp_delete_post($forum_id, true);
            $deleted_counts['forums']++;
        }
    }

    // Clear analytics tables
    $views_table = $wpdb->prefix . 'extrch_link_page_daily_views';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$views_table}'") === $views_table) {
        $deleted_counts['analytics'] += $wpdb->query("DELETE FROM {$views_table}");
    }

    $clicks_table = $wpdb->prefix . 'extrch_link_page_daily_link_clicks';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$clicks_table}'") === $clicks_table) {
        $deleted_counts['analytics'] += $wpdb->query("DELETE FROM {$clicks_table}");
    }

    // Clear subscribers
    $subscribers_table = $wpdb->prefix . 'artist_subscribers';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$subscribers_table}'") === $subscribers_table) {
        $deleted_counts['subscribers'] = $wpdb->query("DELETE FROM {$subscribers_table}");
    }

    // Roster relationships are network-wide and still needed for artist.extrachill.com
    // DO NOT delete these - they are active production data for the live artist platform
    // After migration, artist.extrachill.com has the live profiles and users need roster access
    restore_current_blog();
    $deleted_counts['roster_relationships'] = 0;  // No deletion performed

    $total = array_sum($deleted_counts);

    wp_send_json_success(array(
        'message' => "Successfully deleted {$total} items from community.extrachill.com",
        'deleted' => $deleted_counts
    ));
}
