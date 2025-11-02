/**
 * Ad-Free License Management JavaScript
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

            $.ajax({
                url: ecAdFree.ajax_url,
                type: 'POST',
                data: {
                    action: 'ec_grant_ad_free',
                    nonce: ecAdFree.nonce,
                    user_identifier: userIdentifier
                },
                success: function(response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success')
                            .text(response.data.message)
                            .show();
                        $('#ec-user-search').val('');

                        // Reload page after 1.5 seconds to show new license holder
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $result.removeClass('success').addClass('error')
                            .text('Error: ' + response.data)
                            .show();
                    }
                },
                error: function() {
                    $result.removeClass('success').addClass('error')
                        .text('AJAX error - please try again')
                        .show();
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Grant License');
                }
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

            $.ajax({
                url: ecAdFree.ajax_url,
                type: 'POST',
                data: {
                    action: 'ec_revoke_ad_free',
                    nonce: ecAdFree.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        // Fade out and remove row
                        $row.css('background-color', '#ffcccc');
                        setTimeout(function() {
                            $row.fadeOut(400, function() {
                                $(this).remove();

                                // Reload if no more rows
                                var remainingRows = $('table.wp-list-table tbody tr').length;
                                if (remainingRows === 0) {
                                    location.reload();
                                }
                            });
                        }, 300);
                    } else {
                        alert('Error: ' + response.data);
                        $btn.prop('disabled', false).text('Revoke License');
                    }
                },
                error: function() {
                    alert('AJAX error - please try again');
                    $btn.prop('disabled', false).text('Revoke License');
                }
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
