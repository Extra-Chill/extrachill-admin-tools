/**
 * Ad-Free License Management JavaScript
 *
 * Uses REST API endpoints for license management.
 *
 * @package ExtraChillAdminTools
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Grant License
        $('#ec-grant-license-btn').on('click', function() {
            var userIdentifier = $('#ec-user-search').val().trim();
            var $btn = $(this);
            var $result = $('#ec-grant-result');

            if (!userIdentifier) {
                $result.removeClass('success').addClass('error')
                    .text('Please enter a username or email')
                    .show();
                return;
            }

            if (!confirm('Grant ad-free license to "' + userIdentifier + '"?\n\nThis user will have ad-free access across the entire platform.')) {
                return;
            }

            $btn.prop('disabled', true).text('Granting...');
            $result.hide();

            fetch(ecAdFree.rest_url + 'admin/ad-free-license/grant', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ecAdFree.nonce
                },
                body: JSON.stringify({
                    user_identifier: userIdentifier
                })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                if (result.ok) {
                    $result.removeClass('error').addClass('success')
                        .text(result.data.message)
                        .show();
                    $('#ec-user-search').val('');

                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $result.removeClass('success').addClass('error')
                        .text('Error: ' + result.data.message)
                        .show();
                }
            })
            .catch(function() {
                $result.removeClass('success').addClass('error')
                    .text('Network error - please try again')
                    .show();
            })
            .finally(function() {
                $btn.prop('disabled', false).text('Grant License');
            });
        });

        // Revoke License
        $('.ec-revoke-btn').on('click', function() {
            var userId = $(this).data('user-id');
            var username = $(this).data('username');
            var $btn = $(this);
            var $row = $btn.closest('tr');

            if (!confirm('Revoke ad-free license for "' + username + '"?\n\nThis action will immediately remove ad-free access from this user.\n\nThis cannot be undone.')) {
                return;
            }

            $btn.prop('disabled', true).text('Revoking...');

            fetch(ecAdFree.rest_url + 'admin/ad-free-license/' + userId, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': ecAdFree.nonce
                }
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                if (result.ok) {
                    $row.css('background-color', '#ffcccc');
                    setTimeout(function() {
                        $row.fadeOut(400, function() {
                            $(this).remove();

                            var remainingRows = $('table.wp-list-table tbody tr').length;
                            if (remainingRows === 0) {
                                location.reload();
                            }
                        });
                    }, 300);
                } else {
                    alert('Error: ' + result.data.message);
                    $btn.prop('disabled', false).text('Revoke License');
                }
            })
            .catch(function() {
                alert('Network error - please try again');
                $btn.prop('disabled', false).text('Revoke License');
            });
        });

        // Enter key support for grant input
        $('#ec-user-search').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#ec-grant-license-btn').click();
            }
        });

    });

})(jQuery);
