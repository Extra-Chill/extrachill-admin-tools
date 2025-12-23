/**
 * Festival Wire Migration Tool (REST)
 */

(function () {
    'use strict';

    function $(id) {
        return document.getElementById(id);
    }

    function showOutput(html, type) {
        var output = $('ec-fwm-output');
        if (!output) {
            return;
        }

        var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        output.innerHTML = '<div class="notice ' + noticeClass + '" style="padding:1em;"><pre style="white-space:pre-wrap;margin:0;">' + html + '</pre></div>';
        output.style.display = 'block';
    }

    async function call(endpoint, payload) {
        var response = await fetch(ecAdminTools.restUrl + endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': ecAdminTools.nonce
            },
            body: JSON.stringify(payload || {})
        });

        var data = await response.json();
        if (!response.ok) {
            throw new Error(data && data.message ? data.message : 'Request failed');
        }

        return data;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var preflightBtn = $('ec-fwm-preflight');
        var migrateBtn = $('ec-fwm-migrate');
        var validateBtn = $('ec-fwm-validate');
        var deleteBtn = $('ec-fwm-delete');
        var batchSizeInput = $('ec-fwm-batch-size');

        if (!preflightBtn || !migrateBtn || !validateBtn || !deleteBtn || !batchSizeInput) {
            return;
        }

        function batchSize() {
            var size = parseInt(batchSizeInput.value, 10);
            if (Number.isNaN(size) || size < 1) {
                return 25;
            }
            return Math.min(size, 200);
        }

        preflightBtn.addEventListener('click', async function () {
            try {
                var data = await call('admin/festival-wire/preflight', {});
                showOutput(JSON.stringify(data, null, 2));
            } catch (e) {
                showOutput(e.message, 'error');
            }
        });

        migrateBtn.addEventListener('click', async function () {
            if (!window.confirm('Migrate next batch (including attachments)?')) {
                return;
            }

            migrateBtn.disabled = true;

            try {
                var data = await call('admin/festival-wire/migrate', { batch_size: batchSize() });
                showOutput(JSON.stringify(data, null, 2));
            } catch (e) {
                showOutput(e.message, 'error');
            } finally {
                migrateBtn.disabled = false;
            }
        });

        validateBtn.addEventListener('click', async function () {
            try {
                var data = await call('admin/festival-wire/validate', {});
                showOutput(JSON.stringify(data, null, 2));
            } catch (e) {
                showOutput(e.message, 'error');
            }
        });

        deleteBtn.addEventListener('click', async function () {
            if (!window.confirm('Delete SOURCE posts + attachments for next batch?')) {
                return;
            }

            deleteBtn.disabled = true;

            try {
                var data = await call('admin/festival-wire/delete', { batch_size: batchSize() });
                showOutput(JSON.stringify(data, null, 2));
            } catch (e) {
                showOutput(e.message, 'error');
            } finally {
                deleteBtn.disabled = false;
            }
        });
    });
})();
