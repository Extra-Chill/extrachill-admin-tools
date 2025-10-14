/**
 * Team Member Management Tool
 * Handles AJAX for syncing team members and managing user overrides
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

            $.ajax({
                url: ecAdminTools.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ec_sync_team_members',
                    nonce: ecAdminTools.nonces.syncTeamMembers
                },
                success: function(response) {
                    if (response.success) {
                        $report.html(
                            '<strong>Sync Complete!</strong><br>' +
                            'Total Users: ' + response.data.total_users + '<br>' +
                            'Users Updated: ' + response.data.users_updated + '<br>' +
                            'Users Skipped (Manual Override): ' + response.data.users_skipped_override + '<br>' +
                            'Users with Main Site Account: ' + response.data.users_with_main_site_account
                        ).show();

                        // Reload page after 2 seconds to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Sync Team Members from Main Site');
                }
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

            $.ajax({
                url: ecAdminTools.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ec_manage_team_member',
                    user_id: userId,
                    team_action: action,
                    nonce: ecAdminTools.nonces.manageTeamMember
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                        $select.val('');
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                    $select.val('');
                }
            });
        });
    }

})(jQuery);
