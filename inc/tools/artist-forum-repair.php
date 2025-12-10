<?php
/**
 * Artist Forum Repair Tool
 *
 * Detects and repairs artist profiles that are missing their associated bbPress forums.
 * Creates forums for any published artist profiles that should have forums but don't.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('extrachill_admin_tools', function($tools) {
    $current_blog_id = get_current_blog_id();
    $artist_blog_id   = function_exists('ec_get_blog_id') ? ec_get_blog_id('artist') : null;

    if ($artist_blog_id && $current_blog_id === $artist_blog_id) {
        if (!function_exists('bbp_insert_forum')) {
            return $tools;
        }

        if (!function_exists('bp_create_artist_forum_on_save')) {
            return $tools;
        }

        $tools[] = array(
            'id' => 'artist-forum-repair',
            'title' => 'Artist Forum Repair',
            'description' => 'Detects and creates missing artist forums for published artist profiles.',
            'callback' => 'artist_forum_repair_admin_page'
        );
    }

    return $tools;
}, 10);

add_action('wp_ajax_artist_forum_repair', 'artist_forum_repair_ajax_handler');

function artist_forum_repair_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    // Get current statistics
    $stats = get_artist_forum_stats();

    ?>
    <div class="wrap">
        <h1>Artist Forum Repair</h1>

        <div class="status-section" style="margin: 30px 0; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
            <h2>Current Status</h2>
            <p>This tool detects and creates missing bbPress forums for published artist profiles.</p>

            <div style="background: #f0f0f1; padding: 15px; margin-bottom: 20px; border-left: 4px solid #2271b1;">
                <h3>Forum Statistics</h3>
                <ul>
                    <li><strong>Total Published Artist Profiles:</strong> <?php echo esc_html($stats['total_profiles']); ?></li>
                    <li><strong>With Forums:</strong> <?php echo esc_html($stats['with_forums']); ?></li>
                    <li><strong style="color: #dc3232;">Without Forums (Orphaned):</strong> <?php echo esc_html($stats['without_forums']); ?></li>
                </ul>
            </div>

            <div style="background: #fff3cd; padding: 15px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                <h3>What This Tool Does</h3>
                <p><strong>For each orphaned artist profile:</strong></p>
                <ol>
                    <li>Verifies the profile is published and doesn't already have a forum</li>
                    <li>Calls the artist platform's forum creation function</li>
                    <li>Creates a new bbPress forum linked to the artist profile</li>
                    <li>Sets proper metadata for forum-artist association</li>
                    <li>Logs all results to PHP error log for debugging</li>
                </ol>
            </div>

            <?php if ($stats['without_forums'] > 0): ?>
                <button type="button" class="button button-primary" id="repair-forums-button" style="margin-top: 20px;">
                    Create Missing Forums (<?php echo esc_html($stats['without_forums']); ?>)
                </button>
            <?php else: ?>
                <div style="background: #d4edda; padding: 15px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                    <p style="margin: 0; color: #155724;"><strong>✓ All published artist profiles have forums!</strong></p>
                </div>
            <?php endif; ?>

            <div id="repair-forums-progress" style="display: none; margin-top: 20px;">
                <p><strong>Creating missing forums...</strong></p>
                <p id="repair-forums-status">Initializing...</p>
                <progress id="repair-forums-progress-bar" style="width: 100%; height: 30px;"></progress>
            </div>

            <div id="repair-forums-result" style="display: none; margin-top: 20px; padding: 15px; border-left: 4px solid #46b450; background: #f7f7f7;"></div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#repair-forums-button').on('click', function() {
            if (!confirm('This will create missing bbPress forums for <?php echo esc_js($stats['without_forums']); ?> artist profile(s).\n\nEach forum will be automatically linked to its artist profile.\n\nProceed?')) {
                return;
            }

            $('#repair-forums-button').prop('disabled', true);
            $('#repair-forums-progress').show();
            $('#repair-forums-result').hide();
            $('#repair-forums-status').text('Creating missing forums...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'artist_forum_repair',
                    nonce: '<?php echo wp_create_nonce('artist_forum_repair_nonce'); ?>'
                },
                success: function(response) {
                    $('#repair-forums-progress').hide();

                    if (response.success) {
                        var html = '<h3>Forum Repair Complete!</h3>';
                        html += '<p>' + response.data.message + '</p>';
                        html += '<h4>Results:</h4>';
                        html += '<ul>';
                        html += '<li>Profiles Checked: <strong>' + response.data.stats.total_checked + '</strong></li>';
                        html += '<li>Forums Created Successfully: <strong>' + response.data.stats.created + '</strong></li>';
                        html += '<li>Already Had Forums: <strong>' + response.data.stats.already_had_forum + '</strong></li>';
                        html += '<li>Errors: <strong>' + response.data.stats.errors + '</strong></li>';
                        html += '</ul>';

                        if (response.data.created_list && response.data.created_list.length > 0) {
                            html += '<h4 style="color: #46b450;">Successfully Created Forums:</h4>';
                            html += '<ul>';
                            response.data.created_list.forEach(function(profile) {
                                html += '<li>' + profile.title + ' (Artist ID: ' + profile.artist_id + ', Forum ID: ' + profile.forum_id + ')</li>';
                            });
                            html += '</ul>';
                        }

                        if (response.data.error_list && response.data.error_list.length > 0) {
                            html += '<h4 style="color: #dc3232;">Errors (Check Error Log):</h4>';
                            html += '<ul>';
                            response.data.error_list.forEach(function(profile) {
                                html += '<li>' + profile.title + ' (ID: ' + profile.id + '): ' + profile.error + '</li>';
                            });
                            html += '</ul>';
                        }

                        $('#repair-forums-result')
                            .html(html)
                            .css('border-left-color', '#46b450')
                            .show();
                        $('#repair-forums-button').hide();
                    } else {
                        $('#repair-forums-result')
                            .html('<h3>Repair Failed</h3><p>' + response.data.message + '</p>')
                            .css('border-left-color', '#dc3232')
                            .show();
                        $('#repair-forums-button').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#repair-forums-progress').hide();
                    $('#repair-forums-result')
                        .html('<h3>Error</h3><p>An error occurred during forum repair.</p>')
                        .css('border-left-color', '#dc3232')
                        .show();
                    $('#repair-forums-button').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

function get_artist_forum_stats() {
    $stats = array(
        'total_profiles' => 0,
        'with_forums' => 0,
        'without_forums' => 0
    );

    // Get all published artist profiles
    $artists = get_posts(array(
        'post_type' => 'artist_profile',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));

    $stats['total_profiles'] = count($artists);

    foreach ($artists as $artist_id) {
        $forum_id = get_post_meta($artist_id, '_artist_forum_id', true);

        if (!empty($forum_id)) {
            $stats['with_forums']++;
        } else {
            $stats['without_forums']++;
        }
    }

    return $stats;
}

function artist_forum_repair_ajax_handler() {
    check_ajax_referer('artist_forum_repair_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }

    // Check if bbPress is active
    if (!function_exists('bbp_insert_forum')) {
        wp_send_json_error(array('message' => 'bbPress plugin is not active'));
    }

    // Check if artist platform forum creation function exists
    if (!function_exists('bp_create_artist_forum_on_save')) {
        wp_send_json_error(array('message' => 'Artist platform plugin not active or bp_create_artist_forum_on_save function not found'));
    }

    $stats = array(
        'total_checked' => 0,
        'created' => 0,
        'already_had_forum' => 0,
        'errors' => 0
    );

    $created_list = array();
    $error_list = array();

    // Get all published artist profiles
    $artists = get_posts(array(
        'post_type' => 'artist_profile',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ));

    $stats['total_checked'] = count($artists);

    foreach ($artists as $artist) {
        $artist_id = $artist->ID;

        // Check if forum already exists
        $existing_forum_id = get_post_meta($artist_id, '_artist_forum_id', true);

        if (!empty($existing_forum_id)) {
            $stats['already_had_forum']++;
            continue;
        }

        // Forum doesn't exist - create it
        // Call the forum creation function from artist platform plugin
        bp_create_artist_forum_on_save($artist_id, $artist, false);

        // Check if forum was created successfully
        $new_forum_id = get_post_meta($artist_id, '_artist_forum_id', true);

        if (!empty($new_forum_id)) {
            $stats['created']++;
            $created_list[] = array(
                'artist_id' => $artist_id,
                'forum_id' => $new_forum_id,
                'title' => $artist->post_title
            );
            error_log("[Artist Forum Repair] Successfully created forum ID {$new_forum_id} for artist profile ID {$artist_id} ({$artist->post_title})");
        } else {
            $stats['errors']++;
            $error_list[] = array(
                'id' => $artist_id,
                'title' => $artist->post_title,
                'error' => 'Forum creation function completed but no forum ID was set'
            );
            error_log("[Artist Forum Repair] Failed to create forum for artist profile ID {$artist_id} ({$artist->post_title}) - check error log for details from bp_create_artist_forum_on_save()");
        }
    }

    $message = "Successfully created {$stats['created']} forum(s).";
    if ($stats['already_had_forum'] > 0) {
        $message .= " {$stats['already_had_forum']} profile(s) already had forums.";
    }
    if ($stats['errors'] > 0) {
        $message .= " {$stats['errors']} error(s) occurred (check error log).";
    }

    wp_send_json_success(array(
        'message' => $message,
        'stats' => $stats,
        'created_list' => $created_list,
        'error_list' => $error_list
    ));
}
