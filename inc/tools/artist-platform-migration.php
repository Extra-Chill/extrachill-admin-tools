<?php
/**
 * Artist Platform Migration Tool
 *
 * Simple two-button migration:
 * 1. Migrate: Direct site-to-site copy from community to artist
 * 2. Cleanup: Delete all data from community site after successful migration
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register tool with admin tools system (only on artist.extrachill.com)
add_filter('extrachill_admin_tools', function($tools) {
    $artist_blog_id = get_blog_id_from_url('artist.extrachill.com', '/');
    $current_blog_id = get_current_blog_id();

    if ($current_blog_id === $artist_blog_id) {
        $tools[] = array(
            'id' => 'artist-platform-migration',
            'title' => 'Artist Platform Migration',
            'description' => 'Migrate artist platform from community.extrachill.com to artist.extrachill.com with single-click direct copy.',
            'callback' => 'artist_platform_migration_admin_page'
        );
    }

    return $tools;
}, 10);

// Register AJAX handlers
add_action('wp_ajax_ap_migration_migrate', 'ap_migration_ajax_migrate');
add_action('wp_ajax_ap_migration_cleanup', 'ap_migration_ajax_cleanup');

/**
 * Main admin page
 */
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

            <h3>Data to be copied:</h3>
            <ul>
                <li>Artist Profiles (<?php echo wp_count_posts('artist_profile')->publish ?? 0; ?> currently on this site)</li>
                <li>Link Pages</li>
                <li>Artist Forums, Topics, Replies</li>
                <li>Analytics (views and clicks)</li>
                <li>Subscriber data</li>
            </ul>

            <button type="button" class="button button-primary button-hero" id="migrate-button" style="margin-top: 20px;">
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

            <button type="button" class="button button-secondary" id="cleanup-button" style="margin-top: 20px;">
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

/**
 * AJAX handler: Direct site-to-site migration
 */
function ap_migration_ajax_migrate() {
    check_ajax_referer('ap_migration_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }

    global $wpdb;
    $community_blog_id = get_blog_id_from_url('community.extrachill.com', '/');
    $imported_counts = array(
        'profiles' => 0,
        'link_pages' => 0,
        'forums' => 0,
        'topics' => 0,
        'replies' => 0,
        'views' => 0,
        'clicks' => 0,
        'subscribers' => 0
    );

    // Switch to community site to read data
    switch_to_blog($community_blog_id);

    // Get artist profiles
    $profiles = get_posts(array(
        'post_type' => 'artist_profile',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));

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

    // Get link pages
    $link_pages = get_posts(array(
        'post_type' => 'artist_link_page',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));

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

            // Get topics
            $topics = get_posts(array(
                'post_type' => bbp_get_topic_post_type(),
                'post_parent' => $forum->ID,
                'posts_per_page' => -1,
                'post_status' => 'any'
            ));

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

                // Get replies
                $replies = get_posts(array(
                    'post_type' => bbp_get_reply_post_type(),
                    'post_parent' => $topic->ID,
                    'posts_per_page' => -1,
                    'post_status' => 'any'
                ));

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

    // Switch back to artist site for import
    restore_current_blog();

    // Import artist profiles
    foreach ($profiles_data as $profile_data) {
        if (get_post($profile_data['ID'])) {
            continue; // Skip if exists
        }

        $post_data = array(
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
            'post_name' => $profile_data['post_name']
        );

        $result = wp_insert_post($post_data, true);
        if (!is_wp_error($result)) {
            foreach ($profile_data['meta'] as $key => $values) {
                update_post_meta($result, $key, maybe_unserialize($values[0]));
            }
            if ($profile_data['thumbnail_id']) {
                set_post_thumbnail($result, $profile_data['thumbnail_id']);
            }
            $imported_counts['profiles']++;
        }
    }

    // Import link pages
    foreach ($pages_data as $page_data) {
        if (get_post($page_data['ID'])) {
            continue;
        }

        $post_data = array(
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
            'post_name' => $page_data['post_name']
        );

        $result = wp_insert_post($post_data, true);
        if (!is_wp_error($result)) {
            foreach ($page_data['meta'] as $key => $values) {
                update_post_meta($result, $key, maybe_unserialize($values[0]));
            }
            if ($page_data['thumbnail_id']) {
                set_post_thumbnail($result, $page_data['thumbnail_id']);
            }
            $imported_counts['link_pages']++;
        }
    }

    // Import forums
    if (function_exists('bbp_get_forum_post_type')) {
        foreach ($forums_data as $forum_data) {
            if (get_post($forum_data['ID'])) {
                continue;
            }

            $post_data = array(
                'ID' => $forum_data['ID'],
                'post_type' => bbp_get_forum_post_type(),
                'post_title' => $forum_data['post_title'],
                'post_content' => $forum_data['post_content'],
                'post_status' => $forum_data['post_status'],
                'post_author' => $forum_data['post_author'],
                'post_parent' => $forum_data['post_parent'],
                'post_date' => $forum_data['post_date'],
                'post_date_gmt' => $forum_data['post_date_gmt'],
                'post_name' => $forum_data['post_name']
            );

            $result = wp_insert_post($post_data, true);
            if (!is_wp_error($result)) {
                foreach ($forum_data['meta'] as $key => $values) {
                    update_post_meta($result, $key, is_array($values) ? $values[0] : $values);
                }
                $imported_counts['forums']++;
            }
        }

        // Import topics
        foreach ($topics_data as $topic_data) {
            if (get_post($topic_data['ID'])) {
                continue;
            }

            $post_data = array(
                'ID' => $topic_data['ID'],
                'post_type' => bbp_get_topic_post_type(),
                'post_title' => $topic_data['post_title'],
                'post_content' => $topic_data['post_content'],
                'post_status' => $topic_data['post_status'],
                'post_author' => $topic_data['post_author'],
                'post_parent' => $topic_data['post_parent'],
                'post_date' => $topic_data['post_date'],
                'post_date_gmt' => $topic_data['post_date_gmt'],
                'post_name' => $topic_data['post_name']
            );

            $result = wp_insert_post($post_data, true);
            if (!is_wp_error($result)) {
                foreach ($topic_data['meta'] as $key => $values) {
                    update_post_meta($result, $key, is_array($values) ? $values[0] : $values);
                }
                $imported_counts['topics']++;
            }
        }

        // Import replies
        foreach ($replies_data as $reply_data) {
            if (get_post($reply_data['ID'])) {
                continue;
            }

            $post_data = array(
                'ID' => $reply_data['ID'],
                'post_type' => bbp_get_reply_post_type(),
                'post_content' => $reply_data['post_content'],
                'post_status' => $reply_data['post_status'],
                'post_author' => $reply_data['post_author'],
                'post_parent' => $reply_data['post_parent'],
                'post_date' => $reply_data['post_date'],
                'post_date_gmt' => $reply_data['post_date_gmt']
            );

            $result = wp_insert_post($post_data, true);
            if (!is_wp_error($result)) {
                foreach ($reply_data['meta'] as $key => $values) {
                    update_post_meta($result, $key, is_array($values) ? $values[0] : $values);
                }
                $imported_counts['replies']++;
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

    // Migrate media files (attachments)
    $imported_counts['attachments'] = 0;

    // Collect all attachment IDs
    $attachment_ids = array();
    foreach ($profiles_data as $profile) {
        if ($profile['thumbnail_id']) {
            $attachment_ids[] = $profile['thumbnail_id'];
        }
    }
    foreach ($pages_data as $page) {
        if ($page['thumbnail_id']) {
            $attachment_ids[] = $page['thumbnail_id'];
        }
        // Check for background images in meta
        if (isset($page['meta']['_ec_background_image_id'][0])) {
            $attachment_ids[] = $page['meta']['_ec_background_image_id'][0];
        }
    }

    $attachment_ids = array_unique(array_filter($attachment_ids));

    if (!empty($attachment_ids)) {
        // Switch to community site to get attachment data
        switch_to_blog($community_blog_id);

        $upload_dir_source = wp_upload_dir();

        foreach ($attachment_ids as $attachment_id) {
            $attachment = get_post($attachment_id);
            if (!$attachment || $attachment->post_type !== 'attachment') {
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
                switch_to_blog($community_blog_id);
                continue;
            }

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
                    // Insert attachment post
                    $attachment_post = array(
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
                    );

                    $result = wp_insert_post($attachment_post, true);

                    if (!is_wp_error($result)) {
                        // Update attachment meta
                        foreach ($attachment_data['meta'] as $key => $values) {
                            update_post_meta($result, $key, maybe_unserialize($values[0]));
                        }

                        // Update attachment metadata
                        if ($attachment_data['metadata']) {
                            wp_update_attachment_metadata($result, $attachment_data['metadata']);
                        }

                        // Update attached file path
                        update_post_meta($result, '_wp_attached_file', str_replace($upload_dir_source['basedir'] . '/', '', $dest_file_path));

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

    // Mark migration as completed
    update_option('ap_migration_completed', true);

    $total = array_sum($imported_counts);

    wp_send_json_success(array(
        'message' => "Successfully migrated {$total} items from community.extrachill.com",
        'breakdown' => $imported_counts
    ));
}

/**
 * AJAX handler: Cleanup source site
 */
function ap_migration_ajax_cleanup() {
    check_ajax_referer('ap_migration_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }

    global $wpdb;
    $community_blog_id = get_blog_id_from_url('community.extrachill.com', '/');
    $deleted_counts = array(
        'profiles' => 0,
        'link_pages' => 0,
        'forums' => 0,
        'topics' => 0,
        'replies' => 0,
        'analytics' => 0,
        'subscribers' => 0
    );

    // Switch to community site
    switch_to_blog($community_blog_id);

    // Delete artist profiles
    $profiles = get_posts(array(
        'post_type' => 'artist_profile',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));
    foreach ($profiles as $profile) {
        wp_delete_post($profile->ID, true);
        $deleted_counts['profiles']++;
    }

    // Delete link pages
    $link_pages = get_posts(array(
        'post_type' => 'artist_link_page',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));
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
            // Delete topics and replies first
            $topics = get_posts(array(
                'post_type' => bbp_get_topic_post_type(),
                'post_parent' => $forum_id,
                'posts_per_page' => -1,
                'post_status' => 'any'
            ));

            foreach ($topics as $topic) {
                $replies = get_posts(array(
                    'post_type' => bbp_get_reply_post_type(),
                    'post_parent' => $topic->ID,
                    'posts_per_page' => -1,
                    'post_status' => 'any'
                ));

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

    restore_current_blog();

    $total = array_sum($deleted_counts);

    wp_send_json_success(array(
        'message' => "Successfully deleted {$total} items from community.extrachill.com",
        'deleted' => $deleted_counts
    ));
}
