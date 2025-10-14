/**
 * Generic tab system for Extra Chill Admin Tools
 * Handles both main tabs and nested tabs within tools
 */
(function($) {
    'use strict';

    /**
     * Main tab system using WordPress nav-tab classes
     * Hash-based navigation with URL preservation
     */
    function initMainTabs() {
        var tabs = document.querySelectorAll('.nav-tab[data-tab]');
        var panels = document.querySelectorAll('.tool-tab-content[data-tab]');

        if (tabs.length === 0) {
            return;
        }

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

        // Tab click handlers
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                var tabId = this.getAttribute('data-tab');
                activateTab(tabId);
                window.location.hash = tabId;
            });
        });

        // Activate tab from hash on page load
        var hash = window.location.hash.substring(1);
        if (hash && document.querySelector('.tool-tab-content[data-tab="' + hash + '"]')) {
            activateTab(hash);
        } else if (tabs.length > 0) {
            activateTab(tabs[0].getAttribute('data-tab'));
        }
    }

    /**
     * Nested tab system for tools with sub-navigation
     * JavaScript-only switching (no page reloads)
     * Uses data-ec-nested-tab and data-ec-nested-content attributes
     */
    function initNestedTabs() {
        $(document).on('click', '[data-ec-nested-tab]', function(e) {
            e.preventDefault();

            var $clickedTab = $(this);
            var viewName = $clickedTab.data('ec-nested-tab');
            var $container = $clickedTab.closest('[data-ec-nested-container]');

            // If no container specified, use document
            if ($container.length === 0) {
                $container = $(document);
            }

            // Update active tab styling
            $container.find('[data-ec-nested-tab]').removeClass('active');
            $clickedTab.addClass('active');

            // Show/hide content panels
            $container.find('[data-ec-nested-content]').hide();
            $container.find('[data-ec-nested-content="' + viewName + '"]').show();

            // Update URL hash (optional, for deep linking)
            var mainHash = window.location.hash.split('&')[0];
            window.location.hash = mainHash + '&view=' + viewName;
        });

        // Activate nested tab from URL hash
        var hash = window.location.hash;
        if (hash.indexOf('&view=') !== -1) {
            var viewName = hash.split('&view=')[1];
            var $targetTab = $('[data-ec-nested-tab="' + viewName + '"]');
            if ($targetTab.length > 0) {
                $targetTab.trigger('click');
            }
        }
    }

    // Initialize on DOM ready
    $(document).ready(function() {
        initMainTabs();
        initNestedTabs();
    });

})(jQuery);
