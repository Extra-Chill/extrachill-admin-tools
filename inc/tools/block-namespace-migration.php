<?php
/**
 * Block Namespace Migration Tool
 *
 * Migrates all Gutenberg blocks from plugin-specific namespaces to the unified 'extrachill' namespace.
 * This tool performs a clean migration without backward compatibility.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the block namespace migration tool
 */
function extrachill_admin_tools_register_block_namespace_migration() {
    add_submenu_page(
        'extrachill-admin-tools',
        __('Block Namespace Migration', 'extrachill-admin-tools'),
        __('Block Namespace Migration', 'extrachill-admin-tools'),
        'manage_options',
        'block-namespace-migration',
        'extrachill_admin_tools_block_namespace_migration_page'
    );
}
add_action('admin_menu', 'extrachill_admin_tools_register_block_namespace_migration', 20);

/**
 * Admin page for block namespace migration
 */
function extrachill_admin_tools_block_namespace_migration_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $migration_complete = false;
    $posts_processed = 0;
    $blocks_updated = 0;

    if (isset($_POST['run_migration']) && wp_verify_nonce($_POST['block_migration_nonce'], 'run_block_migration')) {
        $result = extrachill_admin_tools_run_block_namespace_migration();
        $migration_complete = true;
        $posts_processed = $result['posts_processed'];
        $blocks_updated = $result['blocks_updated'];
    }

    ?>
    <div class="wrap">
        <h1><?php _e('Block Namespace Migration', 'extrachill-admin-tools'); ?></h1>

        <div class="notice notice-info">
            <p><?php _e('This tool migrates all Gutenberg blocks from plugin-specific namespaces to the unified "extrachill" namespace. This is a one-way migration with no rollback capability.', 'extrachill-admin-tools'); ?></p>
        </div>

        <?php if ($migration_complete): ?>
            <div class="notice notice-success">
                <p><?php printf(__('Migration completed! Processed %d posts and updated %d block references.', 'extrachill-admin-tools'), $posts_processed, $blocks_updated); ?></p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><?php _e('Migration Details', 'extrachill-admin-tools'); ?></h2>
            <p><?php _e('The following block namespaces will be updated:', 'extrachill-admin-tools'); ?></p>

            <h3><?php _e('extrachill-artist-platform blocks:', 'extrachill-admin-tools'); ?></h3>
            <ul>
                <li><code>extrachill-artist-platform/artist-creator</code> → <code>extrachill/artist-creator</code></li>
                <li><code>extrachill-artist-platform/link-page-editor</code> → <code>extrachill/link-page-editor</code></li>
                <li><code>extrachill-artist-platform/artist-profile-manager</code> → <code>extrachill/artist-profile-manager</code></li>
                <li><code>extrachill-artist-platform/link-page-analytics</code> → <code>extrachill/link-page-analytics</code></li>
            </ul>

            <h3><?php _e('extrachill-blocks blocks:', 'extrachill-admin-tools'); ?></h3>
            <ul>
                <li><code>extrachill-blocks/band-name-generator</code> → <code>extrachill/band-name-generator</code></li>
                <li><code>extrachill-blocks/rapper-name-generator</code> → <code>extrachill/rapper-name-generator</code></li>
                <li><code>extrachill-blocks/image-voting</code> → <code>extrachill/image-voting</code></li>
                <li><code>extrachill-blocks/ai-adventure-step</code> → <code>extrachill/ai-adventure-step</code></li>
                <li><code>extrachill-blocks/ai-adventure-path</code> → <code>extrachill/ai-adventure-path</code></li>
                <li><code>extrachill-blocks/ai-adventure</code> → <code>extrachill/ai-adventure</code></li>
                <li><code>extrachill-blocks/trivia</code> → <code>extrachill/trivia</code></li>
            </ul>

            <form method="post">
                <?php wp_nonce_field('run_block_migration', 'block_migration_nonce'); ?>
                <p>
                    <input type="submit" name="run_migration" class="button button-primary" value="<?php _e('Run Migration', 'extrachill-admin-tools'); ?>" onclick="return confirm('<?php _e('Are you sure you want to run the block namespace migration? This action cannot be undone.', 'extrachill-admin-tools'); ?>');">
                </p>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Run the block namespace migration
 *
 * @return array Migration results
 */
function extrachill_admin_tools_run_block_namespace_migration() {
    global $wpdb;

    $posts_processed = 0;
    $blocks_updated = 0;

    // Define the namespace mappings
    $namespace_mappings = [
        'extrachill-artist-platform/artist-creator' => 'extrachill/artist-creator',
        'extrachill-artist-platform/link-page-editor' => 'extrachill/link-page-editor',
        'extrachill-artist-platform/artist-profile-manager' => 'extrachill/artist-profile-manager',
        'extrachill-artist-platform/link-page-analytics' => 'extrachill/link-page-analytics',
        'extrachill-blocks/band-name-generator' => 'extrachill/band-name-generator',
        'extrachill-blocks/rapper-name-generator' => 'extrachill/rapper-name-generator',
        'extrachill-blocks/image-voting' => 'extrachill/image-voting',
        'extrachill-blocks/ai-adventure-step' => 'extrachill/ai-adventure-step',
        'extrachill-blocks/ai-adventure-path' => 'extrachill/ai-adventure-path',
        'extrachill-blocks/ai-adventure' => 'extrachill/ai-adventure',
        'extrachill-blocks/trivia' => 'extrachill/trivia',
    ];

    // Get all posts that might contain blocks
    $post_types = get_post_types(['public' => true], 'names');
    $post_types = array_diff($post_types, ['attachment']); // Exclude attachments

    $posts = get_posts([
        'post_type' => $post_types,
        'posts_per_page' => -1,
        'post_status' => 'any',
    ]);

    foreach ($posts as $post) {
        $original_content = $post->post_content;
        $updated_content = $original_content;
        $post_updated = false;

        // Replace block comments in content
        foreach ($namespace_mappings as $old_namespace => $new_namespace) {
            $old_pattern = '<!-- wp:' . preg_quote($old_namespace, '/') . ' ';
            $new_pattern = '<!-- wp:' . $new_namespace . ' ';

            if (strpos($updated_content, $old_pattern) !== false) {
                $updated_content = str_replace($old_pattern, $new_pattern, $updated_content);
                $post_updated = true;
                $blocks_updated++;
            }
        }

        // Update the post if content changed
        if ($post_updated) {
            wp_update_post([
                'ID' => $post->ID,
                'post_content' => $updated_content,
            ]);
            $posts_processed++;
        }
    }

    return [
        'posts_processed' => $posts_processed,
        'blocks_updated' => $blocks_updated,
    ];
}