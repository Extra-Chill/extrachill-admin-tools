<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('extrachill_admin_tools', function ($tools) {
    $tools[] = array(
        'id'          => 'festival-wire-category-cleaner',
        'title'       => 'Festival Wire Category Cleaner',
        'description' => 'Remove legacy category assignments from Festival Wire posts, including the “Extra Chill Presents” category.',
        'callback'    => 'ec_festival_wire_category_cleaner_admin_page',
    );
    return $tools;
}, 40);

/**
 * Festival Wire Category Cleaner admin interface.
 *
 * Lists existing category assignments on Festival Wire posts and provides
 * a single action to detach all category terms.
 */
function ec_festival_wire_category_cleaner_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    $report = array();

    if (isset($_POST['ec_festival_wire_clean_categories']) && check_admin_referer('ec_festival_wire_clean_categories')) {
        $report = ec_festival_wire_clean_categories();
    }

    $posts_with_categories = ec_festival_wire_get_posts_with_categories();
    $total_posts           = count($posts_with_categories);
    $extra_chill_presents  = get_term_by('slug', 'extra-chill-presents', 'category');

    echo '<div class="ec-festival-wire-category-cleaner">';
    echo '<p><strong>Total Festival Wire posts with categories:</strong> ' . intval($total_posts) . '</p>';

    if (!empty($posts_with_categories)) {
        echo '<p>The following posts still have category terms assigned. Running the cleaner will remove all categories from each Festival Wire post.</p>';
        echo '<ol style="max-height: 280px; overflow:auto; padding-left:1.5em;">';
        foreach ($posts_with_categories as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $categories     = get_the_terms($post_id, 'category');
                $category_names = array();
                if (!empty($categories) && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        $category_names[] = $category->name;
                    }
                }
                echo '<li><a href="' . esc_url(get_edit_post_link($post_id)) . '" target="_blank">' . esc_html(get_the_title($post_id)) . '</a> – ' . esc_html(implode(', ', $category_names)) . '</li>';
            }
        }
        echo '</ol>';
    } else {
        echo '<p>All Festival Wire posts are already free of category assignments.</p>';
    }

    echo '<form method="post" style="margin-top:1.5em;">';
    wp_nonce_field('ec_festival_wire_clean_categories');
    echo '<p class="description">This operation removes all categories from Festival Wire posts in one action.</p>';
    $button_label = $total_posts ? 'Remove Categories from ' . $total_posts . ' Festival Wire Posts' : 'Remove Categories';
    echo '<button type="submit" name="ec_festival_wire_clean_categories" class="button button-primary" ' . ($total_posts === 0 ? 'disabled' : '') . ' onclick="return confirm(\'Remove all categories from Festival Wire posts? This cannot be undone.\');">' . esc_html($button_label) . '</button>';
    echo '</form>';

    if (!empty($extra_chill_presents)) {
        echo '<p style="margin-top:1em;">The “Extra Chill Presents” category currently has ' . intval($extra_chill_presents->count) . ' posts.</p>';
    }

    if (!empty($report)) {
        echo '<div class="notice notice-success" style="margin-top:1em;"><ul>';
        foreach ($report as $line) {
            echo '<li>' . esc_html($line) . '</li>';
        }
        echo '</ul></div>';
    }

    echo '</div>';
}

/**
 * Locate every festival_wire post still linked to categories.
 *
 * @return array Post IDs that currently have category terms.
 */
function ec_festival_wire_get_posts_with_categories() {
    $query = new WP_Query(array(
        'post_type'      => 'festival_wire',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy' => 'category',
                'operator' => 'EXISTS',
            ),
        ),
    ));

    return $query->posts;
}

/**
 * Detach category terms from all festival_wire posts.
 *
 * @return array Status messages for the admin UI.
 */
function ec_festival_wire_clean_categories() {
    $posts                      = ec_festival_wire_get_posts_with_categories();
    $total_processed            = 0;
    $extra_chill_presents_count = 0;

    foreach ($posts as $post_id) {
        $categories = get_the_terms($post_id, 'category');
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                if ($category->slug === 'extra-chill-presents') {
                    $extra_chill_presents_count++;
                    break;
                }
            }
        }

        wp_set_object_terms($post_id, array(), 'category');
        $total_processed++;
    }

    return array(
        sprintf('Removed categories from %d Festival Wire posts.', $total_processed),
        sprintf('Removed “Extra Chill Presents” from %d posts.', $extra_chill_presents_count),
    );
}
