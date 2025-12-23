/**
 * Taxonomy Sync Tool (REST)
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var button = document.getElementById('ec-sync-taxonomies');
        var report = document.getElementById('ec-taxonomy-sync-report');

        if (!button) {
            return;
        }

        button.addEventListener('click', async function () {
            var targetSites = Array.from(document.querySelectorAll('input[name="target_sites[]"]:checked')).map(function (input) {
                return input.value;
            });

            var taxonomies = Array.from(document.querySelectorAll('input[name="taxonomies[]"]:checked')).map(function (input) {
                return input.value;
            });

            if (targetSites.length === 0) {
                window.alert('Please select at least one target site');
                return;
            }

            if (taxonomies.length === 0) {
                window.alert('Please select at least one taxonomy');
                return;
            }

            if (!window.confirm('Sync ' + taxonomies.length + ' taxonomies to ' + targetSites.length + ' sites?')) {
                return;
            }

            button.disabled = true;
            button.textContent = 'Syncing...';

            if (report) {
                report.style.display = 'none';
            }

            try {
                var response = await fetch(ecAdminTools.restUrl + 'admin/taxonomies/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': ecAdminTools.nonce
                    },
                    body: JSON.stringify({
                        target_sites: targetSites,
                        taxonomies: taxonomies
                    })
                });

                var data = await response.json();

                if (!response.ok) {
                    throw new Error(data && data.message ? data.message : 'Request failed');
                }

                renderReport(data, report);
            } catch (error) {
                window.alert('Error: ' + error.message);
            } finally {
                button.disabled = false;
                button.textContent = 'Sync Taxonomies';
            }
        });
    });

    function renderReport(data, report) {
        if (!report) {
            return;
        }

        var html = '<div class="notice notice-success" style="padding:1em;">';
        html += '<h3 style="margin-top:0;">Sync Complete!</h3>';
        html += '<p><strong>Total Terms Processed:</strong> ' + (data.total_terms_processed || 0) + '</p>';
        html += '<p><strong>Total Terms Created:</strong> ' + (data.total_terms_created || 0) + '</p>';
        html += '<p><strong>Total Terms Skipped:</strong> ' + (data.total_terms_skipped || 0) + '</p>';

        if (data.breakdown) {
            html += '<h4>Breakdown by Taxonomy:</h4>';
            html += '<table class="widefat fixed striped" style="margin-top:1em;">';
            html += '<thead><tr><th>Taxonomy</th><th>Source Terms</th><th>Target</th><th>Created</th><th>Skipped</th><th>Failed</th></tr></thead>';
            html += '<tbody>';

            Object.keys(data.breakdown).forEach(function (taxonomy) {
                var taxData = data.breakdown[taxonomy];
                var sites = taxData.sites || {};
                var siteKeys = Object.keys(sites);

                siteKeys.forEach(function (siteSlug, index) {
                    var row = sites[siteSlug];
                    html += '<tr>';

                    if (index === 0) {
                        html += '<td rowspan="' + siteKeys.length + '"><strong>' + taxonomy + '</strong></td>';
                        html += '<td rowspan="' + siteKeys.length + '">' + taxData.source_terms + '</td>';
                    }

                    html += '<td>' + siteSlug + '</td>';
                    html += '<td>' + (row.created || 0) + '</td>';
                    html += '<td>' + (row.skipped || 0) + '</td>';
                    html += '<td>' + (row.failed || 0) + '</td>';
                    html += '</tr>';
                });
            });

            html += '</tbody></table>';
        }

        html += '</div>';

        report.innerHTML = html;
        report.style.display = 'block';
    }
})();
