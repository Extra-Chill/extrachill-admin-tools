<?php
/**
 * Taxonomy Sync Tool
 *
 * Syncs taxonomies from extrachill.com (Blog ID 1) to other network sites,
 * preserving existing terms and their metadata.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'tools_page_extrachill-admin-tools') {
        return;
    }

    wp_enqueue_script(
        'ec-taxonomy-sync',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/taxonomy-sync.js',
        array('extrachill-admin-tools'),
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/taxonomy-sync.js'),
        true
    );
});

add_filter('extrachill_admin_tools', function($tools) {
    $tools[] = array(
        'id' => 'taxonomy-sync',
        'title' => 'Taxonomy Sync',
        'description' => 'Synchronize taxonomies from main site (extrachill.com) to other network sites. Existing terms are preserved.',
        'callback' => 'ec_taxonomy_sync_admin_page'
    );
    return $tools;
}, 30);

function ec_taxonomy_sync_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    // Available taxonomies (registered in theme)
    $taxonomies = array(
        'location' => 'Location (Hierarchical)',
        'festival' => 'Festival',
        'artist' => 'Artist',
        'venue' => 'Venue',
    );

    $target_sites = array();

    $events_blog_id = function_exists('ec_get_blog_id') ? ec_get_blog_id('events') : null;
    if ($events_blog_id) {
        $target_sites[$events_blog_id] = 'events.extrachill.com';
    }

    ?>
    <div class="ec-taxonomy-sync-wrap">
        <h3>Select Target Sites</h3>
        <p>Choose which sites should receive taxonomies from extrachill.com (Blog ID 1):</p>
        <div class="ec-site-selection" style="margin-bottom: 1.5em;">
            <?php foreach ($target_sites as $blog_id => $site_name): ?>
                <label style="display: block; margin-bottom: 0.5em;">
                    <input type="checkbox" name="target_sites[]" value="<?php echo absint($blog_id); ?>" checked>
                    <?php echo esc_html($site_name); ?> (Blog ID <?php echo absint($blog_id); ?>)
                </label>
            <?php endforeach; ?>
        </div>

        <h3>Select Taxonomies</h3>
        <p>Choose which taxonomies to sync (all selected by default):</p>
        <div class="ec-taxonomy-selection" style="margin-bottom: 1.5em;">
            <?php foreach ($taxonomies as $slug => $label): ?>
                <label style="display: block; margin-bottom: 0.5em;">
                    <input type="checkbox" name="taxonomies[]" value="<?php echo esc_attr($slug); ?>" checked>
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <button type="button" class="button button-primary" id="ec-sync-taxonomies">
            Sync Taxonomies
        </button>

        <div id="ec-taxonomy-sync-report" style="display:none; margin-top: 1.5em;"></div>
    </div>
    <?php
}

add_action('wp_ajax_ec_sync_taxonomies', 'ec_ajax_sync_taxonomies');
function ec_ajax_sync_taxonomies() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access');
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'ec_taxonomy_sync_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    $target_sites = isset($_POST['target_sites']) ? array_map('absint', $_POST['target_sites']) : array();
    $taxonomies = isset($_POST['taxonomies']) ? array_map('sanitize_key', $_POST['taxonomies']) : array();

    if (empty($target_sites) || empty($taxonomies)) {
        wp_send_json_error('Please select at least one target site and one taxonomy');
    }

    // Validate taxonomies
    $valid_taxonomies = array('location', 'festival', 'artist', 'venue');
    $taxonomies = array_filter($taxonomies, function($tax) use ($valid_taxonomies) {
        return in_array($tax, $valid_taxonomies, true);
    });

    if (empty($taxonomies)) {
        wp_send_json_error('No valid taxonomies selected');
    }

    $report = ec_perform_taxonomy_sync($target_sites, $taxonomies);

    wp_send_json_success($report);
}

/**
 * Organizes terms into hierarchical structure for recursive syncing
 *
 * @param array $terms Array of term objects
 * @return array Associative array mapping parent IDs to child term arrays
 */
function ec_organize_terms_by_parent($terms) {
    $hierarchy = array();
    foreach ($terms as $term) {
        $parent_id = (int) $term->parent;
        if (!isset($hierarchy[$parent_id])) {
            $hierarchy[$parent_id] = array();
        }
        $hierarchy[$parent_id][] = $term;
    }
    return $hierarchy;
}

/**
 * Recursively syncs a term and its children to target site
 *
 * @param object $term Term object from source site
 * @param string $taxonomy Taxonomy slug
 * @param array $hierarchy Hierarchical term organization
 * @param int $parent_id_on_target Parent term ID on target site (0 for root)
 * @param array &$site_report Reference to site report counters
 * @param array &$report Reference to main report counters
 * @return void
 */
function ec_sync_term_recursive($term, $taxonomy, $hierarchy, $parent_id_on_target, &$site_report, &$report) {
    $report['total_terms_processed']++;

    // Check if term already exists
    $existing_term = term_exists($term->slug, $taxonomy);
    if ($existing_term) {
        $site_report['skipped']++;
        $report['total_terms_skipped']++;
        $synced_term_id = is_array($existing_term) ? $existing_term['term_id'] : $existing_term;
    } else {
        // Prepare term arguments
        $term_args = array(
            'slug' => $term->slug,
            'description' => $term->description,
        );

        // Set parent if this is a child term
        if ($parent_id_on_target > 0) {
            $term_args['parent'] = $parent_id_on_target;
        }

        // Insert term
        $result = wp_insert_term($term->name, $taxonomy, $term_args);

        if (is_wp_error($result)) {
            $site_report['failed']++;
            return; // Don't sync children if parent failed
        } else {
            $site_report['created']++;
            $report['total_terms_created']++;
            $synced_term_id = $result['term_id'];
        }
    }

    // Recursively sync children
    if (isset($hierarchy[$term->term_id])) {
        foreach ($hierarchy[$term->term_id] as $child_term) {
            ec_sync_term_recursive($child_term, $taxonomy, $hierarchy, $synced_term_id, $site_report, $report);
        }
    }
}

/**
 * Performs taxonomy synchronization from main site to target sites
 *
 * @param array $target_blog_ids Array of target blog IDs
 * @param array $taxonomies Array of taxonomy slugs to sync
 * @return array Report with breakdown by taxonomy and site
 */
function ec_perform_taxonomy_sync($target_blog_ids, $taxonomies) {
    $source_blog_id = function_exists('ec_get_blog_id') ? ec_get_blog_id('main') : null;
    if (!$source_blog_id) {
        return array(
            'total_terms_processed' => 0,
            'total_terms_created' => 0,
            'total_terms_skipped' => 0,
            'breakdown' => array(),
        );
    }

    $report = array(
        'total_terms_processed' => 0,
        'total_terms_created' => 0,
        'total_terms_skipped' => 0,
        'breakdown' => array(),
    );

    foreach ($taxonomies as $taxonomy) {
        // Get terms from source site
        $source_terms = array();
        try {
            switch_to_blog($source_blog_id);
            $source_terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ));
        } finally {
            restore_current_blog();
        }

        if (is_wp_error($source_terms) || empty($source_terms)) {
            continue;
        }

        $report['breakdown'][$taxonomy] = array(
            'source_terms' => count($source_terms),
            'sites' => array(),
        );

        // Check if taxonomy is hierarchical
        $is_hierarchical = is_taxonomy_hierarchical($taxonomy);

        // Sync to each target site
        foreach ($target_blog_ids as $target_blog_id) {
            $site_report = array(
                'created' => 0,
                'skipped' => 0,
                'failed' => 0,
            );

            try {
                switch_to_blog($target_blog_id);

                if ($is_hierarchical) {
                    // Organize terms by parent for hierarchical sync
                    $hierarchy = ec_organize_terms_by_parent($source_terms);

                    // Sync root terms (parent = 0) recursively
                    if (isset($hierarchy[0])) {
                        foreach ($hierarchy[0] as $root_term) {
                            ec_sync_term_recursive($root_term, $taxonomy, $hierarchy, 0, $site_report, $report);
                        }
                    }
                } else {
                    // Flat taxonomy sync (non-hierarchical)
                    foreach ($source_terms as $term) {
                        $report['total_terms_processed']++;

                        // Check if term already exists
                        $existing_term = term_exists($term->slug, $taxonomy);
                        if ($existing_term) {
                            $site_report['skipped']++;
                            $report['total_terms_skipped']++;
                            continue;
                        }

                        // Prepare term arguments
                        $term_args = array(
                            'slug' => $term->slug,
                            'description' => $term->description,
                        );

                        // Insert term
                        $result = wp_insert_term($term->name, $taxonomy, $term_args);

                        if (is_wp_error($result)) {
                            $site_report['failed']++;
                        } else {
                            $site_report['created']++;
                            $report['total_terms_created']++;
                        }
                    }
                }
            } finally {
                restore_current_blog();
            }

            $report['breakdown'][$taxonomy]['sites'][$target_blog_id] = $site_report;
        }
    }

    return $report;
}
