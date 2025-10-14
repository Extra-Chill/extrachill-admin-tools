/**
 * Artist-User Relationships Tool
 * Handles AJAX for managing relationships between users and artist profiles
 */
(function($) {
    'use strict';

    var currentArtistId = null;
    var searchTimeout = null;

    $(document).ready(function() {
        initRemoveMemberHandlers();
        initAddMemberModal();
        initUserSearch();
        initUserSelection();
    });

    /**
     * Handle remove member links
     */
    function initRemoveMemberHandlers() {
        $(document).on('click', '.ec-remove-member', function(e) {
            e.preventDefault();

            if (!confirm('Remove this relationship?')) {
                return;
            }

            var $link = $(this);
            var userId = $link.data('user-id');
            var artistId = $link.data('artist-id');

            $.ajax({
                url: ecAdminTools.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ec_remove_artist_user_relationship',
                    user_id: userId,
                    artist_id: artistId,
                    nonce: ecAdminTools.nonces.artistUserRelationships
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                }
            });
        });
    }

    /**
     * Handle add member links - show user search modal
     */
    function initAddMemberModal() {
        $(document).on('click', '.ec-link-user', function(e) {
            e.preventDefault();
            currentArtistId = $(this).data('artist-id');
            $('#ec-user-search-modal').fadeIn();
            $('#ec-user-search-input').val('').focus();
            $('#ec-user-search-results').html('<div class="ec-user-search-empty">Start typing to search for users...</div>');
        });

        // Close modal
        $('.ec-user-search-close, #ec-user-search-modal').on('click', function(e) {
            if (e.target === this) {
                $('#ec-user-search-modal').fadeOut();
                currentArtistId = null;
            }
        });
    }

    /**
     * User search with debounce
     */
    function initUserSearch() {
        $('#ec-user-search-input').on('keyup', function() {
            clearTimeout(searchTimeout);
            var query = $(this).val().trim();

            if (query.length < 2) {
                $('#ec-user-search-results').html('<div class="ec-user-search-empty">Enter at least 2 characters to search...</div>');
                return;
            }

            $('#ec-user-search-results').html('<div class="ec-user-search-loading">Searching...</div>');

            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: ecAdminTools.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ec_search_users_for_relationship',
                        search: query,
                        nonce: ecAdminTools.nonces.artistUserRelationships
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            var html = '';
                            response.data.forEach(function(user) {
                                html += '<div class="ec-user-search-item" data-user-id="' + user.ID + '">' +
                                       '<img src="' + user.avatar + '" width="32" height="32">' +
                                       '<div class="ec-user-search-item-info">' +
                                       '<div class="ec-user-search-item-name">' + user.display_name + '</div>' +
                                       '<div class="ec-user-search-item-meta">' + user.user_login + ' (' + user.user_email + ')</div>' +
                                       '</div>' +
                                       '</div>';
                            });
                            $('#ec-user-search-results').html(html);
                        } else {
                            $('#ec-user-search-results').html('<div class="ec-user-search-empty">No users found</div>');
                        }
                    },
                    error: function() {
                        $('#ec-user-search-results').html('<div class="ec-user-search-empty">Search error occurred</div>');
                    }
                });
            }, 300);
        });
    }

    /**
     * Handle user selection from search results
     */
    function initUserSelection() {
        $(document).on('click', '.ec-user-search-item', function() {
            var userId = $(this).data('user-id');
            var artistId = currentArtistId;

            if (!userId || !artistId) return;

            $('#ec-user-search-results').html('<div class="ec-user-search-loading">Adding user...</div>');

            $.ajax({
                url: ecAdminTools.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ec_add_artist_user_relationship',
                    user_id: userId,
                    artist_id: artistId,
                    nonce: ecAdminTools.nonces.artistUserRelationships
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        $('#ec-user-search-modal').fadeOut();
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                    $('#ec-user-search-modal').fadeOut();
                }
            });
        });
    }

})(jQuery);
