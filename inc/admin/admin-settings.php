<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'extrachill_admin_tools_menu');

function extrachill_admin_tools_menu() {
    add_management_page(
        'Admin Tools',
        'Admin Tools',
        'manage_options',
        'extrachill-admin-tools',
        'extrachill_admin_tools_page'
    );
}

function extrachill_admin_tools_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $tools = apply_filters('extrachill_admin_tools', array());

    echo '<div class="wrap">';
    echo '<h1>Extra Chill Admin Tools</h1>';

    if (empty($tools)) {
        echo '<p>No admin tools are currently registered.</p>';
    } else {
        foreach ($tools as $tool) {
            if (isset($tool['title']) && isset($tool['callback']) && function_exists($tool['callback'])) {
                echo '<div class="admin-tool-section" style="margin-bottom: 2em; padding: 1em; border: 1px solid #ddd; border-radius: 4px;">';
                echo '<h2>' . esc_html($tool['title']) . '</h2>';
                if (isset($tool['description'])) {
                    echo '<p>' . esc_html($tool['description']) . '</p>';
                }
                echo '<hr style="margin: 1em 0;">';
                call_user_func($tool['callback']);
                echo '</div>';
            }
        }
    }

    echo '</div>';
}