<?php
/**
 * Bulk Forum Topic Migration Tool
 *
 * Moves topics in bulk from one forum to another within the same site.
 * Generalized utility with no restrictions - works with any bbPress forums.
 *
 * @package ExtraChillAdminTools
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the migration tool
 */
add_filter('extrachill_admin_tools', function ($tools) {
    // Only show if bbPress is active
    if (!function_exists('bbp_insert_topic')) {
        return $tools;
    }

    $tools[] = [
        'id'          => 'forum-topic-migration',
        'title'       => 'Bulk Forum Topic Migration',
        'description' => 'Move topics in bulk from one forum to another. Select source forum, pick topics, choose destination.',
        'callback'    => 'ec_forum_topic_migration_admin_page',
    ];

    return $tools;
}, 10);

/**
 * Enqueue migration tool assets
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'extra-chill-multisite_page_extrachill-admin-tools') {
        return;
    }

    if (!function_exists('bbp_insert_topic')) {
        return;
    }

    wp_enqueue_style(
        'ec-forum-topic-migration',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/css/forum-topic-migration.css',
        [],
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/css/forum-topic-migration.css')
    );

    wp_enqueue_script(
        'ec-forum-topic-migration',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/forum-topic-migration.js',
        ['jquery'],
        filemtime(EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/forum-topic-migration.js'),
        true
    );

    wp_localize_script('ec-forum-topic-migration', 'ecTopicMigration', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ec_topic_migration_nonce'),
    ]);
});

/**
 * Get all forums with topic counts, including subforums with hierarchy
 *
 * @return array Forums with id, title, topic_count, parent_id, depth
 */
function ec_get_all_forums_with_counts() {
    $forums = [];

    $all_forums = get_posts([
        'post_type'      => 'forum',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    foreach ($all_forums as $forum) {
        $topic_count = absint(get_post_meta($forum->ID, '_bbp_topic_count', true));

        $forums[] = [
            'id'          => $forum->ID,
            'title'       => $forum->post_title,
            'topic_count' => $topic_count,
            'parent_id'   => $forum->post_parent,
        ];
    }

    // Sort hierarchically
    $forums = ec_sort_forums_hierarchically($forums);

    return $forums;
}

/**
 * Sort forums hierarchically with depth indicators
 *
 * @param array $forums Flat array of forums
 * @param int   $parent_id Parent ID to start from
 * @param int   $depth Current depth level
 * @return array Sorted forums with depth
 */
function ec_sort_forums_hierarchically($forums, $parent_id = 0, $depth = 0) {
    $sorted = [];

    foreach ($forums as $forum) {
        if ($forum['parent_id'] == $parent_id) {
            $forum['depth'] = $depth;
            $sorted[] = $forum;

            // Recursively add children
            $children = ec_sort_forums_hierarchically($forums, $forum['id'], $depth + 1);
            $sorted = array_merge($sorted, $children);
        }
    }

    return $sorted;
}

/**
 * Get topics from a specific forum with pagination
 *
 * @param int    $forum_id Forum ID (0 for all forums)
 * @param string $search Search term
 * @param int    $page Page number
 * @param int    $per_page Items per page
 * @return array Topics data with pagination info
 */
function ec_get_forum_topics_for_migration($forum_id = 0, $search = '', $page = 1, $per_page = 25) {
    $args = [
        'post_type'      => 'topic',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
    ];

    if ($forum_id > 0) {
        $args['post_parent'] = $forum_id;
    }

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $query  = new WP_Query($args);
    $topics = [];

    foreach ($query->posts as $topic) {
        $forum_id_meta = absint(get_post_meta($topic->ID, '_bbp_forum_id', true));
        $forum_title   = $forum_id_meta ? get_the_title($forum_id_meta) : 'Unknown Forum';
        $reply_count   = absint(get_post_meta($topic->ID, '_bbp_reply_count', true));
        $author        = get_user_by('ID', $topic->post_author);

        $topics[] = [
            'id'           => $topic->ID,
            'title'        => $topic->post_title,
            'forum_id'     => $forum_id_meta ?: $topic->post_parent,
            'forum_title'  => $forum_title,
            'author_id'    => $topic->post_author,
            'author_name'  => $author ? $author->display_name : 'Unknown',
            'reply_count'  => $reply_count,
            'date'         => $topic->post_date,
            'url'          => get_permalink($topic->ID),
        ];
    }

    return [
        'topics' => $topics,
        'total'  => $query->found_posts,
        'pages'  => $query->max_num_pages,
    ];
}

/**
 * Move a single topic to a different forum
 *
 * @param int $topic_id Topic ID to move
 * @param int $destination_forum_id Target forum ID
 * @return array|WP_Error Result array or error
 */
function ec_move_topic_to_forum($topic_id, $destination_forum_id) {
    $topic = get_post($topic_id);

    if (!$topic || $topic->post_type !== 'topic') {
        return new WP_Error('invalid_topic', 'Invalid topic ID');
    }

    $dest_forum = get_post($destination_forum_id);
    if (!$dest_forum || $dest_forum->post_type !== 'forum') {
        return new WP_Error('invalid_destination', 'Invalid destination forum');
    }

    $source_forum_id = absint(get_post_meta($topic_id, '_bbp_forum_id', true));
    if (!$source_forum_id) {
        $source_forum_id = $topic->post_parent;
    }

    // Don't move if already in destination
    if ($source_forum_id === $destination_forum_id) {
        return new WP_Error('same_forum', 'Topic is already in this forum');
    }

    // 1. Update topic post_parent
    wp_update_post([
        'ID'          => $topic_id,
        'post_parent' => $destination_forum_id,
    ]);

    // 2. Update topic _bbp_forum_id meta
    update_post_meta($topic_id, '_bbp_forum_id', $destination_forum_id);

    // 3. Update all replies _bbp_forum_id meta
    $replies = get_posts([
        'post_type'      => 'reply',
        'post_parent'    => $topic_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $reply_count = count($replies);
    foreach ($replies as $reply_id) {
        update_post_meta($reply_id, '_bbp_forum_id', $destination_forum_id);
    }

    // 4. Update forum counts for source forum
    if ($source_forum_id) {
        bbp_update_forum_topic_count($source_forum_id);
        bbp_update_forum_reply_count($source_forum_id);
        bbp_update_forum_last_active_time($source_forum_id);
    }

    // 5. Update forum counts for destination forum
    bbp_update_forum_topic_count($destination_forum_id);
    bbp_update_forum_reply_count($destination_forum_id);
    bbp_update_forum_last_active_time($destination_forum_id);

    return [
        'success'         => true,
        'topic_id'        => $topic_id,
        'title'           => $topic->post_title,
        'reply_count'     => $reply_count,
        'source_forum'    => $source_forum_id,
        'dest_forum'      => $destination_forum_id,
    ];
}

/**
 * AJAX handler for bulk topic migration
 */
add_action('wp_ajax_ec_move_forum_topics', 'ec_move_forum_topics_ajax');

function ec_move_forum_topics_ajax() {
    check_ajax_referer('ec_topic_migration_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }

    $topic_ids            = isset($_POST['topic_ids']) ? array_map('intval', (array) wp_unslash($_POST['topic_ids'])) : [];
    $destination_forum_id = isset($_POST['destination_forum_id']) ? intval($_POST['destination_forum_id']) : 0;

    if (empty($topic_ids)) {
        wp_send_json_error(['message' => 'No topics selected']);
    }

    if ($destination_forum_id <= 0) {
        wp_send_json_error(['message' => 'No destination forum selected']);
    }

    $moved  = [];
    $failed = [];

    foreach ($topic_ids as $topic_id) {
        $result = ec_move_topic_to_forum($topic_id, $destination_forum_id);

        if (is_wp_error($result)) {
            $topic = get_post($topic_id);
            $failed[] = [
                'id'    => $topic_id,
                'title' => $topic ? $topic->post_title : 'Unknown',
                'error' => $result->get_error_message(),
            ];
        } else {
            $moved[] = $result;
        }
    }

    wp_send_json_success([
        'moved'   => $moved,
        'failed'  => $failed,
        'message' => sprintf(
            'Moved %d topic(s). %d failed.',
            count($moved),
            count($failed)
        ),
    ]);
}

/**
 * AJAX handler for loading topics (pagination/filtering)
 */
add_action('wp_ajax_ec_get_forum_topics_for_migration', 'ec_get_forum_topics_for_migration_ajax');

function ec_get_forum_topics_for_migration_ajax() {
    check_ajax_referer('ec_topic_migration_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }

    $forum_id = isset($_POST['forum_id']) ? intval($_POST['forum_id']) : 0;
    $search   = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
    $page     = isset($_POST['page']) ? intval($_POST['page']) : 1;

    $result = ec_get_forum_topics_for_migration($forum_id, $search, $page, 25);

    wp_send_json_success($result);
}

/**
 * Render the admin page
 */
function ec_forum_topic_migration_admin_page() {
    $forums         = ec_get_all_forums_with_counts();
    $initial_topics = ec_get_forum_topics_for_migration(0, '', 1, 25);

    // Calculate total topics across all forums
    $total_topics = 0;
    foreach ($forums as $forum) {
        $total_topics += $forum['topic_count'];
    }
    ?>
    <div class="ec-topic-migration-wrap">
        <div class="ec-migration-stats">
            <h3>Bulk Forum Topic Migration</h3>
            <p>Move topics from one forum to another. Select a source forum to filter, then choose topics and a destination.</p>
            <p><strong>Total:</strong> <?php echo esc_html($total_topics); ?> topics across <?php echo esc_html(count($forums)); ?> forums</p>
        </div>

        <?php if (empty($forums)) : ?>
            <div class="notice notice-warning">
                <p>No forums found. Create some forums first.</p>
            </div>
        <?php else : ?>

        <div class="ec-migration-filters">
            <div class="filter-row">
                <label for="ec-source-forum">Source Forum:</label>
                <select id="ec-source-forum">
                    <option value="0">All Forums</option>
                    <?php foreach ($forums as $forum) : ?>
                        <option value="<?php echo esc_attr($forum['id']); ?>">
                            <?php echo esc_html(str_repeat('— ', $forum['depth']) . $forum['title']); ?> 
                            (<?php echo esc_html($forum['topic_count']); ?> topics)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="ec-topic-search">Search:</label>
                <input type="text" id="ec-topic-search" placeholder="Search topic titles...">
                <button type="button" id="ec-search-btn" class="button">Search</button>
            </div>
        </div>

        <div class="ec-migration-actions">
            <div class="bulk-actions">
                <label for="ec-destination-forum">Destination Forum:</label>
                <select id="ec-destination-forum">
                    <option value="">-- Select Destination --</option>
                    <?php foreach ($forums as $forum) : ?>
                        <option value="<?php echo esc_attr($forum['id']); ?>">
                            <?php echo esc_html(str_repeat('— ', $forum['depth']) . $forum['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="ec-move-selected" class="button button-primary" disabled>
                    Move Selected
                </button>
            </div>
        </div>

        <div class="ec-topics-table-wrap">
            <table class="wp-list-table widefat fixed striped" id="ec-topics-table">
                <thead>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" id="ec-select-all">
                        </th>
                        <th>Topic Title</th>
                        <th>Current Forum</th>
                        <th>Author</th>
                        <th>Replies</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="ec-topics-tbody">
                    <?php if (empty($initial_topics['topics'])) : ?>
                        <tr>
                            <td colspan="6">No topics found.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($initial_topics['topics'] as $topic) : ?>
                        <tr data-topic-id="<?php echo esc_attr($topic['id']); ?>">
                            <td class="check-column">
                                <input type="checkbox" class="ec-topic-checkbox" value="<?php echo esc_attr($topic['id']); ?>">
                            </td>
                            <td>
                                <a href="<?php echo esc_url($topic['url']); ?>" target="_blank">
                                    <?php echo esc_html($topic['title']); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($topic['forum_title']); ?></td>
                            <td><?php echo esc_html($topic['author_name']); ?></td>
                            <td><?php echo esc_html($topic['reply_count']); ?></td>
                            <td><?php echo esc_html(gmdate('Y-m-d', strtotime($topic['date']))); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="ec-pagination" id="ec-pagination">
            <?php if ($initial_topics['pages'] > 1) : ?>
                <span class="pagination-info">
                    Page 1 of <?php echo esc_html($initial_topics['pages']); ?> 
                    (<?php echo esc_html($initial_topics['total']); ?> topics)
                </span>
                <button type="button" class="button" id="ec-prev-page" disabled>&laquo; Prev</button>
                <button type="button" class="button" id="ec-next-page">Next &raquo;</button>
            <?php endif; ?>
        </div>

        <div class="ec-migration-result" id="ec-migration-result" style="display: none;"></div>

        <?php endif; ?>
    </div>
    <?php
}
