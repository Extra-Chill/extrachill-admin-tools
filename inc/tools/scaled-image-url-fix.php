<?php
/**
 * Fixes broken image URLs in post content by replacing with -scaled versions when available
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('extrachill_admin_tools', function($tools) {
    $tools[] = array(
        'id' => 'scaled-image-url-fix',
        'title' => 'Fix Scaled Image URLs',
        'description' => 'Fix broken image URLs (404s) in post content by replacing with -scaled versions when available.',
        'callback' => 'ec_scaled_image_url_fix_page'
    );
    return $tools;
}, 10);

function ec_scaled_image_url_fix_page() {
    $results = array();
    $mode = '';

    if (isset($_POST['scan_broken_images']) && check_admin_referer('scaled_image_url_fix')) {
        $results = ec_scan_broken_image_urls();
        $mode = 'scan';
    } elseif (isset($_POST['fix_broken_images']) && check_admin_referer('scaled_image_url_fix')) {
        $results = ec_fix_broken_image_urls();
        $mode = 'fix';
    }

    echo '<form method="post">';
    wp_nonce_field('scaled_image_url_fix');
    echo '<p>';
    echo '<input type="submit" name="scan_broken_images" class="button" value="Scan for Broken Images" style="margin-right:1em;"> ';
    echo '<input type="submit" name="fix_broken_images" class="button" value="Fix Broken Images" onclick="return confirm(\'This will update post content. Continue?\');">';
    echo '</p>';
    echo '</form>';

    if (!empty($results)) {
        if ($mode === 'fix') {
            echo '<div class="notice notice-success"><p>';
            echo '<strong>Fixed ' . intval($results['posts_updated']) . ' posts with ' . intval($results['images_fixed']) . ' broken images.</strong>';
            echo '</p></div>';
        }

        if (!empty($results['items'])) {
            echo '<table class="widefat fixed striped" style="margin-top:1em;">';
            echo '<thead><tr><th>Post Title</th><th>Broken URL</th><th>' . ($mode === 'scan' ? 'Will Replace With' : 'Replaced With') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($results['items'] as $item) {
                echo '<tr>';
                echo '<td><a href="' . esc_url(get_edit_post_link($item['post_id'])) . '">' . esc_html($item['post_title']) . '</a></td>';
                echo '<td style="font-size:11px;word-break:break-all;">' . esc_html($item['broken_url']) . '</td>';
                echo '<td style="font-size:11px;word-break:break-all;">' . esc_html($item['scaled_url']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } elseif ($mode === 'scan') {
            echo '<div class="notice notice-info"><p><strong>No broken images found!</strong> All image URLs in post content are working.</p></div>';
        }
    }
}

function ec_scan_broken_image_urls() {
    $upload_dir = wp_upload_dir();
    $items = array();

    $posts = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));

    foreach ($posts as $post_id) {
        $post = get_post($post_id);
        $content = $post->post_content;

        // Find all image URLs in content
        preg_match_all('/(https?:\/\/[^\s"\']+\/wp-content\/uploads\/[^\s"\']+\-\d+x\d+\.(jpg|jpeg|png|gif|webp))/i', $content, $matches);

        if (empty($matches[1])) {
            continue;
        }

        foreach ($matches[1] as $url) {
            // Skip if already has -scaled
            if (strpos($url, '-scaled') !== false) {
                continue;
            }

            // Check if file exists
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
            if (file_exists($file_path)) {
                continue;
            }

            // File is broken - check if -scaled version exists
            $scaled_url = ec_convert_to_scaled_image_url($url);
            if ($scaled_url === $url) {
                continue;
            }

            $scaled_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $scaled_url);
            if (!file_exists($scaled_path)) {
                continue;
            }

            // Found a fixable broken image
            $items[] = array(
                'post_id' => $post_id,
                'post_title' => $post->post_title,
                'broken_url' => $url,
                'scaled_url' => $scaled_url
            );
        }
    }

    return array('items' => $items);
}

function ec_fix_broken_image_urls() {
    $scan_results = ec_scan_broken_image_urls();
    $items = $scan_results['items'];
    $posts_updated = 0;
    $images_fixed = 0;

    if (empty($items)) {
        return array('posts_updated' => 0, 'images_fixed' => 0, 'items' => array());
    }

    // Group by post_id
    $posts_to_fix = array();
    foreach ($items as $item) {
        if (!isset($posts_to_fix[$item['post_id']])) {
            $posts_to_fix[$item['post_id']] = array();
        }
        $posts_to_fix[$item['post_id']][] = $item;
    }

    foreach ($posts_to_fix as $post_id => $post_items) {
        $post = get_post($post_id);
        $content = $post->post_content;
        $original_content = $content;

        foreach ($post_items as $item) {
            $content = str_replace($item['broken_url'], $item['scaled_url'], $content);
            $images_fixed++;
        }

        if ($content !== $original_content) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $content
            ));
            $posts_updated++;
        }
    }

    return array(
        'posts_updated' => $posts_updated,
        'images_fixed' => $images_fixed,
        'items' => $items
    );
}

/**
 * Converts image URL pattern from filename-1024x683.jpg to filename-scaled-1024x683.jpg
 */
function ec_convert_to_scaled_image_url($url) {
    if (preg_match('/^(.+)-(\d+)x(\d+)(\.[a-z]{3,4})$/i', $url, $matches)) {
        return $matches[1] . '-scaled-' . $matches[2] . 'x' . $matches[3] . $matches[4];
    }
    return $url;
}
