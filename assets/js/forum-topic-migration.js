/**
 * Bulk Forum Topic Migration Tool JavaScript
 */
(function($) {
    'use strict';

    var currentPage = 1;
    var totalPages = 1;
    var currentForumId = 0;
    var currentSearch = '';

    /**
     * Initialize the migration tool
     */
    function init() {
        bindEvents();
        updateMoveButtonState();
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Source forum change
        $('#ec-source-forum').on('change', function() {
            currentForumId = parseInt($(this).val(), 10);
            currentPage = 1;
            loadTopics();
        });

        // Search button
        $('#ec-search-btn').on('click', function() {
            currentSearch = $('#ec-topic-search').val();
            currentPage = 1;
            loadTopics();
        });

        // Search on enter
        $('#ec-topic-search').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#ec-search-btn').click();
            }
        });

        // Select all checkbox
        $('#ec-select-all').on('change', function() {
            $('.ec-topic-checkbox').prop('checked', $(this).is(':checked'));
            updateMoveButtonState();
        });

        // Individual checkbox
        $(document).on('change', '.ec-topic-checkbox', function() {
            updateMoveButtonState();
            updateSelectAllState();
        });

        // Destination forum change
        $('#ec-destination-forum').on('change', function() {
            updateMoveButtonState();
        });

        // Move button
        $('#ec-move-selected').on('click', function() {
            moveSelected();
        });

        // Pagination
        $('#ec-prev-page').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadTopics();
            }
        });

        $('#ec-next-page').on('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                loadTopics();
            }
        });
    }

    /**
     * Update move button state based on selections
     */
    function updateMoveButtonState() {
        var hasSelection = $('.ec-topic-checkbox:checked').length > 0;
        var hasDestination = $('#ec-destination-forum').val() !== '';
        $('#ec-move-selected').prop('disabled', !(hasSelection && hasDestination));
    }

    /**
     * Update select all checkbox state
     */
    function updateSelectAllState() {
        var total = $('.ec-topic-checkbox').length;
        var checked = $('.ec-topic-checkbox:checked').length;
        $('#ec-select-all').prop('checked', total > 0 && total === checked);
    }

    /**
     * Load topics via AJAX
     */
    function loadTopics() {
        var $tbody = $('#ec-topics-tbody');
        var $table = $('#ec-topics-table');

        $table.addClass('ec-loading');

        $.ajax({
            url: ecTopicMigration.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ec_get_forum_topics_for_migration',
                nonce: ecTopicMigration.nonce,
                forum_id: currentForumId,
                search: currentSearch,
                page: currentPage
            },
            success: function(response) {
                $table.removeClass('ec-loading');

                if (response.success) {
                    renderTopics(response.data.topics);
                    totalPages = response.data.pages;
                    updatePagination(response.data.total, response.data.pages);
                } else {
                    alert('Error loading topics: ' + response.data.message);
                }
            },
            error: function() {
                $table.removeClass('ec-loading');
                alert('Error loading topics. Please try again.');
            }
        });
    }

    /**
     * Render topics in table
     */
    function renderTopics(topics) {
        var $tbody = $('#ec-topics-tbody');
        $tbody.empty();

        if (topics.length === 0) {
            $tbody.append('<tr><td colspan="6">No topics found.</td></tr>');
            return;
        }

        topics.forEach(function(topic) {
            var row = '<tr data-topic-id="' + topic.id + '">' +
                '<td class="check-column">' +
                    '<input type="checkbox" class="ec-topic-checkbox" value="' + topic.id + '">' +
                '</td>' +
                '<td>' +
                    '<a href="' + topic.url + '" target="_blank">' + escapeHtml(topic.title) + '</a>' +
                '</td>' +
                '<td>' + escapeHtml(topic.forum_title) + '</td>' +
                '<td>' + escapeHtml(topic.author_name) + '</td>' +
                '<td>' + topic.reply_count + '</td>' +
                '<td>' + topic.date.substring(0, 10) + '</td>' +
            '</tr>';
            $tbody.append(row);
        });

        $('#ec-select-all').prop('checked', false);
        updateMoveButtonState();
    }

    /**
     * Update pagination controls
     */
    function updatePagination(total, pages) {
        var $pagination = $('#ec-pagination');
        $pagination.empty();

        if (pages <= 1) {
            return;
        }

        var html = '<span class="pagination-info">Page ' + currentPage + ' of ' + pages + ' (' + total + ' topics)</span>';
        html += '<button type="button" class="button" id="ec-prev-page"' + (currentPage <= 1 ? ' disabled' : '') + '>&laquo; Prev</button>';
        html += '<button type="button" class="button" id="ec-next-page"' + (currentPage >= pages ? ' disabled' : '') + '>Next &raquo;</button>';

        $pagination.html(html);

        // Rebind pagination events
        $('#ec-prev-page').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadTopics();
            }
        });

        $('#ec-next-page').on('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                loadTopics();
            }
        });
    }

    /**
     * Move selected topics
     */
    function moveSelected() {
        var topicIds = [];
        $('.ec-topic-checkbox:checked').each(function() {
            topicIds.push(parseInt($(this).val(), 10));
        });

        var destinationForumId = parseInt($('#ec-destination-forum').val(), 10);
        var destinationForumName = $('#ec-destination-forum option:selected').text().trim();

        if (topicIds.length === 0) {
            alert('Please select at least one topic to move.');
            return;
        }

        if (!destinationForumId) {
            alert('Please select a destination forum.');
            return;
        }

        var confirmMsg = 'Move ' + topicIds.length + ' topic(s) to "' + destinationForumName + '"?\n\n' +
            'This will update the forum assignment for the selected topics and all their replies.';

        if (!confirm(confirmMsg)) {
            return;
        }

        var $button = $('#ec-move-selected');
        var $result = $('#ec-migration-result');

        $button.prop('disabled', true).text('Moving...');
        $result.hide();

        $.ajax({
            url: ecTopicMigration.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ec_move_forum_topics',
                nonce: ecTopicMigration.nonce,
                topic_ids: topicIds,
                destination_forum_id: destinationForumId
            },
            success: function(response) {
                $button.text('Move Selected');

                if (response.success) {
                    renderMoveResult(response.data);
                    // Reload topics to reflect changes
                    loadTopics();
                } else {
                    alert('Move failed: ' + response.data.message);
                    updateMoveButtonState();
                }
            },
            error: function() {
                $button.text('Move Selected');
                alert('Move request failed. Please try again.');
                updateMoveButtonState();
            }
        });
    }

    /**
     * Render move result
     */
    function renderMoveResult(data) {
        var $result = $('#ec-migration-result');
        var html = '<h4>Move Complete</h4>';
        html += '<p>' + data.message + '</p>';

        if (data.moved && data.moved.length > 0) {
            html += '<h4>Successfully Moved:</h4>';
            html += '<ul class="success-list">';
            data.moved.forEach(function(item) {
                html += '<li>"' + escapeHtml(item.title) + '" (' + item.reply_count + ' replies)</li>';
            });
            html += '</ul>';
        }

        if (data.failed && data.failed.length > 0) {
            html += '<h4>Failed:</h4>';
            html += '<ul class="error-list">';
            data.failed.forEach(function(item) {
                html += '<li>"' + escapeHtml(item.title) + '": ' + escapeHtml(item.error) + '</li>';
            });
            html += '</ul>';
            $result.addClass('has-errors');
        } else {
            $result.removeClass('has-errors');
        }

        $result.html(html).show();
    }

    /**
     * Escape HTML entities
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
