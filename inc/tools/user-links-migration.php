<?php
/**
 * Migrates legacy static social link fields to dynamic array system
 */

if (!defined('ABSPATH')) exit;

add_filter('extrachill_admin_tools', function($tools) {
    $tools[] = array(
        'id' => 'user-links-migration',
        'title' => 'User Links Migration',
        'description' => 'Migrate legacy static social link fields to dynamic array system. Converts instagram, twitter, facebook, spotify, soundcloud, bandcamp, and user_url to _user_profile_dynamic_links.',
        'callback' => 'user_links_migration_admin_page'
    );
    return $tools;
}, 10);

function user_links_migration_admin_page() {
    $results = array();

    if (isset($_POST['run_user_links_migration']) && check_admin_referer('user_links_migration_action')) {
        $results = user_links_migration_perform();
    }

    if (!empty($results)) {
        echo '<div class="notice notice-success"><h3>Migration Complete</h3><ul>';
        foreach ($results as $line) {
            echo '<li>' . esc_html($line) . '</li>';
        }
        echo '</ul></div>';
    }

    echo '<div class="card" style="max-width: 800px;">';
    echo '<h2>User Links Migration</h2>';
    echo '<p>This tool migrates legacy static social link fields to the new dynamic array system.</p>';

    echo '<h3>What This Does:</h3>';
    echo '<ul>';
    echo '<li>Scans all users for legacy link fields: <code>instagram</code>, <code>twitter</code>, <code>facebook</code>, <code>spotify</code>, <code>soundcloud</code>, <code>bandcamp</code>, <code>user_url</code></li>';
    echo '<li>Converts found links to <code>_user_profile_dynamic_links</code> array format</li>';
    echo '<li>Preserves all existing data - does not delete legacy fields</li>';
    echo '<li>Skips users who already have dynamic links configured</li>';
    echo '</ul>';

    echo '<h3>Before Running:</h3>';
    echo '<ul>';
    echo '<li>Backup your database</li>';
    echo '<li>Ensure <code>extrachill-community</code> plugin is active with user links system</li>';
    echo '<li>This migration can be run multiple times safely</li>';
    echo '</ul>';

    echo '<form method="post" onsubmit="return confirm(\'Are you sure you want to run the user links migration?\');">';
    wp_nonce_field('user_links_migration_action');
    echo '<p><input type="submit" name="run_user_links_migration" class="button" value="Run Migration"></p>';
    echo '</form>';
    echo '</div>';
}

function user_links_migration_perform() {
    $report = array();
    $users_migrated = 0;
    $users_skipped = 0;
    $links_migrated = 0;

    $static_fields = array(
        'user_url'   => 'website',
        'instagram'  => 'instagram',
        'twitter'    => 'twitter',
        'facebook'   => 'facebook',
        'spotify'    => 'spotify',
        'soundcloud' => 'soundcloud',
        'bandcamp'   => 'bandcamp',
    );

    $users = get_users(array('fields' => 'ID'));

    foreach ($users as $user_id) {
        $existing_dynamic = get_user_meta($user_id, '_user_profile_dynamic_links', true);

        if (is_array($existing_dynamic) && !empty($existing_dynamic)) {
            $users_skipped++;
            continue;
        }

        $dynamic_links = array();

        foreach ($static_fields as $meta_key => $type_key) {
            $url = get_user_meta($user_id, $meta_key, true);

            if (!empty($url)) {
                $dynamic_links[] = array(
                    'type_key' => $type_key,
                    'url' => esc_url_raw($url)
                );
                $links_migrated++;
            }
        }

        if (!empty($dynamic_links)) {
            update_user_meta($user_id, '_user_profile_dynamic_links', $dynamic_links);
            $users_migrated++;
        }
    }

    $report[] = "Total users processed: " . count($users);
    $report[] = "Users migrated: {$users_migrated}";
    $report[] = "Users skipped (already have dynamic links): {$users_skipped}";
    $report[] = "Total links migrated: {$links_migrated}";

    return $report;
}
