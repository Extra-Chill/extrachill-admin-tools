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
        // Tab Navigation
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tools as $tool) {
            if (isset($tool['title']) && isset($tool['id']) && isset($tool['callback']) && function_exists($tool['callback'])) {
                $tab_id = esc_attr($tool['id']);
                echo '<a href="#' . $tab_id . '" class="nav-tab" data-tab="' . $tab_id . '">' . esc_html($tool['title']) . '</a>';
            }
        }
        echo '</h2>';

        // Tab Panels
        foreach ($tools as $tool) {
            if (isset($tool['title']) && isset($tool['id']) && isset($tool['callback']) && function_exists($tool['callback'])) {
                $tab_id = esc_attr($tool['id']);
                echo '<div class="tool-tab-content" data-tab="' . $tab_id . '" style="display:none; margin-top:20px;">';
                echo '<div style="padding: 1em; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
                if (isset($tool['description'])) {
                    echo '<p style="margin-top:0;">' . esc_html($tool['description']) . '</p>';
                    echo '<hr style="margin: 1em 0;">';
                }
                call_user_func($tool['callback']);
                echo '</div>';
                echo '</div>';
            }
        }

        // Tab Switching JavaScript
        ?>
        <script>
        (function() {
            var tabs = document.querySelectorAll('.nav-tab[data-tab]');
            var panels = document.querySelectorAll('.tool-tab-content[data-tab]');

            function activateTab(tabId) {
                tabs.forEach(function(tab) {
                    if (tab.getAttribute('data-tab') === tabId) {
                        tab.classList.add('nav-tab-active');
                    } else {
                        tab.classList.remove('nav-tab-active');
                    }
                });

                panels.forEach(function(panel) {
                    if (panel.getAttribute('data-tab') === tabId) {
                        panel.style.display = 'block';
                    } else {
                        panel.style.display = 'none';
                    }
                });
            }

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    var tabId = this.getAttribute('data-tab');
                    activateTab(tabId);
                    window.location.hash = tabId;
                });
            });

            // Activate tab from URL hash or default to first
            var hash = window.location.hash.substring(1);
            if (hash && document.querySelector('.tool-tab-content[data-tab="' + hash + '"]')) {
                activateTab(hash);
            } else if (tabs.length > 0) {
                activateTab(tabs[0].getAttribute('data-tab'));
            }
        })();
        </script>
        <?php
    }

    echo '</div>';
}