/**
 * Team Member Management Tool
 *
 * Uses REST API for syncing team members and managing user overrides.
 *
 * @package ExtraChillAdminTools
 * @since 1.0.0
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initSyncButton();
        initUserActions();
    });

    /**
     * Sync button handler
     */
    function initSyncButton() {
        $('#ec-sync-team-members').on('click', function() {
            var $button = $(this);
            var $report = $('#ec-sync-report');

            $button.prop('disabled', true).text('Syncing...');
            $report.hide();

            fetch(ecAdminTools.restUrl + 'admin/team-members/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ecAdminTools.nonce
                }
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                if (result.ok) {
                    $report.html(
                        '<strong>Sync Complete!</strong><br>' +
                        'Total Users: ' + result.data.total_users + '<br>' +
                        'Users Updated: ' + result.data.users_updated + '<br>' +
                        'Users Skipped (Manual Override): ' + result.data.users_skipped_override + '<br>' +
                        'Users with Main Site Account: ' + result.data.users_with_main_site_account
                    ).show();

                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + (result.data.message || 'Unknown error'));
                }
            })
            .catch(function() {
                alert('Network error - please try again');
            })
            .finally(function() {
                $button.prop('disabled', false).text('Sync Team Members from Main Site');
            });
        });
    }

    /**
     * User action dropdown handler
     */
    function initUserActions() {
        $('.ec-user-action').on('change', function() {
            var $select = $(this);
            var action = $select.val();
            var userId = $select.data('user-id');

            if (!action) return;

            if (!confirm('Are you sure you want to perform this action?')) {
                $select.val('');
                return;
            }

            fetch(ecAdminTools.restUrl + 'admin/team-members/' + userId, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ecAdminTools.nonce
                },
                body: JSON.stringify({
                    action: action
                })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                if (result.ok) {
                    location.reload();
                } else {
                    alert('Error: ' + (result.data.message || 'Unknown error'));
                    $select.val('');
                }
            })
            .catch(function() {
                alert('Network error - please try again');
                $select.val('');
            });
        });
    }

})(jQuery);
