/**
 * Artist-User Relationships Tool
 * Handles REST API calls for managing relationships between users and artist profiles
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
                url: ecAdminTools.restUrl + 'users/' + userId + '/artists/' + artistId,
                type: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', ecAdminTools.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    var message = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    alert('Error: ' + message);
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
                    url: ecAdminTools.restUrl + 'users/search',
                    type: 'GET',
                    data: {
                        term: query,
                        context: 'admin'
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', ecAdminTools.nonce);
                    },
                    success: function(response) {
                        if (response && response.length > 0) {
                            var html = '';
                            response.forEach(function(user) {
                                html += '<div class="ec-user-search-item" data-user-id="' + user.id + '">' +
                                       '<img src="' + user.avatar_url + '" width="32" height="32">' +
                                       '<div class="ec-user-search-item-info">' +
                                       '<div class="ec-user-search-item-name">' + user.display_name + '</div>' +
                                       '<div class="ec-user-search-item-meta">' + user.username + ' (' + user.email + ')</div>' +
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
                url: ecAdminTools.restUrl + 'users/' + userId + '/artists',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    artist_id: artistId
                }),
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', ecAdminTools.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Unknown error'));
                        $('#ec-user-search-modal').fadeOut();
                    }
                },
                error: function(xhr) {
                    var message = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    alert('Error: ' + message);
                    $('#ec-user-search-modal').fadeOut();
                }
            });
        });
    }

})(jQuery);
