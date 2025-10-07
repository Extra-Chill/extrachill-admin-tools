<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if (is_plugin_active('extrachill-news-wire/extrachill-news-wire.php')) {
    add_filter('extrachill_admin_tools', function($tools) {
        $tools[] = array(
            'id' => 'festival-wire-migration',
            'title' => 'Festival Wire Migration',
            'description' => 'One-time migration utilities for converting tags to festival taxonomy and reassigning post authors.',
            'callback' => 'festival_wire_migration_admin_page'
        );
        return $tools;
    }, 30);
}

function festival_wire_migration_admin_page() {
    $tag_migration_done = get_option('festival_wire_migration_done');
    if (isset($_POST['festival_wire_migrate']) && check_admin_referer('festival_wire_migrate_action')) {
        $report = festival_wire_perform_tag_to_festival_migration();
        update_option('festival_wire_migration_done', 1);
        echo '<div class="notice notice-success"><p><strong>Tag Migration complete!</strong></p>';
        if (!empty($report)) {
            echo '<ul>';
            foreach ($report as $line) {
                echo '<li>' . esc_html($line) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        $tag_migration_done = true;
    }

    $author_migration_done = get_option('festival_wire_author_migration_done');
    if (isset($_POST['festival_wire_author_migrate']) && check_admin_referer('festival_wire_author_migrate_action')) {
        $new_author_id = intval($_POST['new_author_id']);
        if ($new_author_id > 0) {
            $report = festival_wire_perform_author_migration($new_author_id);
            update_option('festival_wire_author_migration_done', 1);
            echo '<div class="notice notice-success"><p><strong>Author Migration complete!</strong></p>';
            if (!empty($report)) {
                echo '<ul>';
                foreach ($report as $line) {
                    echo '<li>' . esc_html($line) . '</li>';
                }
                echo '</ul>';
            }
            echo '</div>';
            $author_migration_done = true;
        } else {
            echo '<div class="notice notice-error"><p><strong>Error:</strong> Please select a valid author.</p></div>';
        }
    }

    global $wpdb;
    $festival_wire_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'festival_wire'");
    ?>

    <h3>Tag to Festival Migration</h3>
    <?php if ($tag_migration_done): ?>
        <div class="notice notice-success"><p><strong>Tag migration already completed.</strong></p></div>
    <?php else: ?>
        <p>This will migrate all tags currently attached to any Festival Wire post to the new <strong>festival</strong> taxonomy. The tags will be removed from all posts and deleted if unused. This action is one-time and cannot be undone.</p>
        <form method="post">
            <?php wp_nonce_field('festival_wire_migrate_action'); ?>
            <input type="submit" name="festival_wire_migrate" class="button button-primary" value="Migrate Festival Wire Tags to Festivals" onclick="return confirm('Are you sure? This cannot be undone.');">
        </form>
    <?php endif; ?>

    <hr style="margin: 30px 0;">

    <h3>Festival Wire Author Migration</h3>
    <?php if ($author_migration_done): ?>
        <div class="notice notice-success"><p><strong>Author migration already completed.</strong></p></div>
    <?php else: ?>
        <p>This will reassign ALL Festival Wire posts (currently <strong><?php echo number_format($festival_wire_count); ?> posts</strong>) to a selected author. This action is one-time and cannot be undone.</p>
        <form method="post">
            <?php wp_nonce_field('festival_wire_author_migrate_action'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="new_author_id">Select New Author:</label></th>
                    <td>
                        <?php
                        wp_dropdown_users(array(
                            'name' => 'new_author_id',
                            'id' => 'new_author_id',
                            'show_option_none' => 'Select an author...',
                            'option_none_value' => 0
                        ));
                        ?>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="festival_wire_author_migrate" class="button button-primary" value="Migrate All Festival Wire Authors" onclick="return confirm('Are you sure you want to reassign all <?php echo number_format($festival_wire_count); ?> Festival Wire posts? This cannot be undone.');">
            </p>
        </form>
    <?php endif; ?>
    <?php
}

/**
 * Migrates festival_wire tags to festival taxonomy using SQL for performance with large datasets
 */
function festival_wire_perform_tag_to_festival_migration() {
    global $wpdb;
    $report = array();

    $tag_ids = $wpdb->get_col("
        SELECT DISTINCT tr.term_taxonomy_id
        FROM {$wpdb->term_relationships} tr
        JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        JOIN {$wpdb->posts} p ON tr.object_id = p.ID
        WHERE p.post_type = 'festival_wire' AND tt.taxonomy = 'post_tag'
    ");
    if (empty($tag_ids)) {
        $report[] = 'No tags found attached to festival_wire posts.';
        return $report;
    }
    foreach ($tag_ids as $tt_id) {
        $tag = $wpdb->get_row($wpdb->prepare(
            "SELECT t.term_id, t.name, t.slug FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.term_taxonomy_id = %d",
            $tt_id
        ));
        if (!$tag) continue;

        $festival_term = term_exists($tag->slug, 'festival');
        if (!$festival_term) {
            $festival_term = wp_insert_term($tag->name, 'festival', array('slug' => $tag->slug));
        }
        $festival_term_id = is_array($festival_term) ? $festival_term['term_id'] : $festival_term;
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT tr.object_id FROM {$wpdb->term_relationships} tr WHERE tr.term_taxonomy_id = %d",
            $tt_id
        ));
        if (empty($post_ids)) continue;

        foreach ($post_ids as $post_id) {
            wp_set_object_terms($post_id, intval($festival_term_id), 'festival', true);
            wp_remove_object_terms($post_id, intval($tag->term_id), 'post_tag');
        }

        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d",
            $tt_id
        ));
        if ($count === 0) {
            wp_delete_term($tag->term_id, 'post_tag');
            $report[] = sprintf('Migrated and deleted tag "%s" (slug: %s).', $tag->name, $tag->slug);
        } else {
            $report[] = sprintf('Migrated tag "%s" (slug: %s), but it is still used elsewhere.', $tag->name, $tag->slug);
        }
    }
    return $report;
}

function festival_wire_perform_author_migration($new_author_id) {
    global $wpdb;
    $report = array();

    $author = get_userdata($new_author_id);
    if (!$author) {
        $report[] = 'Error: Invalid author ID provided.';
        return $report;
    }

    $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'festival_wire'");

    if ($total_posts == 0) {
        $report[] = 'No Festival Wire posts found to migrate.';
        return $report;
    }

    $updated = $wpdb->update(
        $wpdb->posts,
        array('post_author' => $new_author_id),
        array('post_type' => 'festival_wire'),
        array('%d'),
        array('%s')
    );

    if ($updated === false) {
        $report[] = 'Error: Database update failed.';
    } else {
        $report[] = sprintf('Successfully migrated %d Festival Wire posts to author: %s (%s).',
            $updated,
            $author->display_name,
            $author->user_login
        );

        if ($updated != $total_posts) {
            $report[] = sprintf('Note: Expected %d posts but updated %d posts.', $total_posts, $updated);
        }
    }

    return $report;
}