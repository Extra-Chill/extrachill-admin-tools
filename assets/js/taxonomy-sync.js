/**
 * Taxonomy Sync Tool
 * Handles AJAX for synchronizing taxonomies across multisite network
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initSyncButton();
    });

    /**
     * Sync button handler
     */
    function initSyncButton() {
        $('#ec-sync-taxonomies').on('click', function() {
            var $button = $(this);
            var $report = $('#ec-taxonomy-sync-report');

            // Collect selected target sites
            var targetSites = [];
            $('input[name="target_sites[]"]:checked').each(function() {
                targetSites.push($(this).val());
            });

            // Collect selected taxonomies
            var taxonomies = [];
            $('input[name="taxonomies[]"]:checked').each(function() {
                taxonomies.push($(this).val());
            });

            // Validate selections
            if (targetSites.length === 0) {
                alert('Please select at least one target site');
                return;
            }

            if (taxonomies.length === 0) {
                alert('Please select at least one taxonomy');
                return;
            }

            // Confirm action
            if (!confirm('Sync ' + taxonomies.length + ' taxonomies to ' + targetSites.length + ' sites?')) {
                return;
            }

            $button.prop('disabled', true).text('Syncing...');
            $report.hide();

            $.ajax({
                url: ecAdminTools.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ec_sync_taxonomies',
                    target_sites: targetSites,
                    taxonomies: taxonomies,
                    nonce: ecAdminTools.nonces.taxonomySync
                },
                success: function(response) {
                    if (response.success) {
                        displayReport(response.data, $report);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Sync Taxonomies');
                }
            });
        });
    }

    /**
     * Display structured sync report
     */
    function displayReport(data, $report) {
        var html = '<div class="notice notice-success" style="padding: 1em;">';
        html += '<h3 style="margin-top: 0;">Sync Complete!</h3>';
        html += '<p><strong>Total Terms Processed:</strong> ' + data.total_terms_processed + '</p>';
        html += '<p><strong>Total Terms Created:</strong> ' + data.total_terms_created + '</p>';
        html += '<p><strong>Total Terms Skipped:</strong> ' + data.total_terms_skipped + '</p>';

        if (data.breakdown) {
            html += '<h4>Breakdown by Taxonomy:</h4>';
            html += '<table class="widefat fixed striped" style="margin-top: 1em;">';
            html += '<thead><tr><th>Taxonomy</th><th>Source Terms</th><th>Blog ID</th><th>Created</th><th>Skipped</th><th>Failed</th></tr></thead>';
            html += '<tbody>';

            for (var taxonomy in data.breakdown) {
                var taxData = data.breakdown[taxonomy];
                var firstRow = true;

                for (var blogId in taxData.sites) {
                    var siteData = taxData.sites[blogId];
                    html += '<tr>';

                    if (firstRow) {
                        html += '<td rowspan="' + Object.keys(taxData.sites).length + '"><strong>' + taxonomy + '</strong></td>';
                        html += '<td rowspan="' + Object.keys(taxData.sites).length + '">' + taxData.source_terms + '</td>';
                        firstRow = false;
                    }

                    html += '<td>' + blogId + '</td>';
                    html += '<td>' + siteData.created + '</td>';
                    html += '<td>' + siteData.skipped + '</td>';
                    html += '<td>' + (siteData.failed || 0) + '</td>';
                    html += '</tr>';
                }
            }

            html += '</tbody></table>';
        }

        html += '</div>';
        $report.html(html).show();
    }

})(jQuery);
