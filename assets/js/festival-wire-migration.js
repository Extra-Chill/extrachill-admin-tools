/**
 * Festival Wire Migration Tool
 */
(function () {
    'use strict';

    var isRunning = false;

    function $(id) {
        return document.getElementById(id);
    }

    function showOutput(html, type) {
        var output = $('ec-fwm-output');
        if (!output) return;
        var cls = type === 'error' ? 'notice-error' : 'notice-success';
        output.innerHTML = '<div class="notice ' + cls + '" style="padding:1em;"><pre style="white-space:pre-wrap;margin:0;">' + html + '</pre></div>';
        output.style.display = 'block';
    }

    function showProgress(text, percent) {
        var container = $('ec-fwm-progress');
        var textEl = $('ec-fwm-progress-text');
        var bar = $('ec-fwm-progress-bar');
        if (!container || !textEl || !bar) return;
        container.style.display = 'block';
        textEl.textContent = text;
        bar.style.width = Math.min(100, Math.max(0, percent)) + '%';
    }

    function hideProgress() {
        var container = $('ec-fwm-progress');
        if (container) container.style.display = 'none';
    }

    function setButtons(enabled) {
        var btns = ['ec-fwm-preflight', 'ec-fwm-migrate', 'ec-fwm-delete-source', 'ec-fwm-reset'];
        btns.forEach(function (id) {
            var btn = $(id);
            if (btn) btn.disabled = !enabled;
        });
    }

    async function ajax(action, data) {
        var formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', ecFwm.nonce);
        if (data) {
            Object.keys(data).forEach(function (key) {
                formData.append(key, data[key]);
            });
        }

        var response = await fetch(ecFwm.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        var result = await response.json();
        if (!result.success) {
            throw new Error(result.data || 'Request failed');
        }
        return result.data;
    }

    async function runMigration() {
        if (isRunning) return;
        isRunning = true;
        setButtons(false);

        var totalMigrated = 0;
        var totalSkipped = 0;

        var preflight;
        try {
            preflight = await ajax('ec_fwm_preflight');
        } catch (e) {
            showOutput('Preflight failed: ' + e.message, 'error');
            isRunning = false;
            setButtons(true);
            hideProgress();
            return;
        }

        var sourceCount = preflight.source_count;
        var startTarget = preflight.target_count;

        if (sourceCount === 0) {
            showOutput('No source posts found.', 'error');
            isRunning = false;
            setButtons(true);
            hideProgress();
            return;
        }

        showProgress('Starting migration... 0 / ' + sourceCount, 0);

        var lastId = 0;
        var done = false;

        while (!done) {
            try {
                var result = await ajax('ec_fwm_migrate_batch', { last_id: lastId });
                done = result.done;
                lastId = result.last_id;
                totalMigrated += result.migrated;
                totalSkipped += result.skipped;

                var processed = startTarget + totalMigrated + totalSkipped;
                var percent = Math.round((processed / sourceCount) * 100);
                showProgress(
                    'Migrated: ' + totalMigrated + ' | Skipped: ' + totalSkipped + ' | Progress: ' + processed + ' / ' + sourceCount,
                    percent
                );
            } catch (e) {
                showOutput('Migration error: ' + e.message, 'error');
                break;
            }
        }

        isRunning = false;
        setButtons(true);

        if (done) {
            showOutput('Migration complete!\n\nMigrated: ' + totalMigrated + '\nSkipped (already existed): ' + totalSkipped);
        }
    }

    async function runDeleteSource() {
        if (isRunning) return;
        isRunning = true;
        setButtons(false);

        var totalDeletedPosts = 0;
        var totalDeletedAttachments = 0;

        var preflight;
        try {
            preflight = await ajax('ec_fwm_preflight');
        } catch (e) {
            showOutput('Preflight failed: ' + e.message, 'error');
            isRunning = false;
            setButtons(true);
            hideProgress();
            return;
        }

        var sourceCount = preflight.source_count;

        if (sourceCount === 0) {
            showOutput('No source posts to delete.', 'error');
            isRunning = false;
            setButtons(true);
            hideProgress();
            return;
        }

        showProgress('Deleting source posts... 0 / ' + sourceCount, 0);

        var lastId = 0;
        var done = false;

        while (!done) {
            try {
                var result = await ajax('ec_fwm_delete_source_batch', { last_id: lastId });
                done = result.done;
                lastId = result.last_id;
                totalDeletedPosts += result.deleted_posts;
                totalDeletedAttachments += result.deleted_attachments;

                var percent = Math.round((totalDeletedPosts / sourceCount) * 100);
                showProgress(
                    'Deleted posts: ' + totalDeletedPosts + ' | Deleted attachments: ' + totalDeletedAttachments,
                    percent
                );
            } catch (e) {
                showOutput('Delete error: ' + e.message, 'error');
                break;
            }
        }

        isRunning = false;
        setButtons(true);

        if (done) {
            showOutput('Delete complete!\n\nDeleted posts: ' + totalDeletedPosts + '\nDeleted attachments: ' + totalDeletedAttachments);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var preflightBtn = $('ec-fwm-preflight');
        var migrateBtn = $('ec-fwm-migrate');
        var deleteSourceBtn = $('ec-fwm-delete-source');
        var resetBtn = $('ec-fwm-reset');

        if (!preflightBtn || !migrateBtn || !deleteSourceBtn || !resetBtn) return;

        preflightBtn.addEventListener('click', async function () {
            setButtons(false);
            hideProgress();
            try {
                var data = await ajax('ec_fwm_preflight');
                showOutput(
                    'Source posts (Blog 1): ' + data.source_count +
                    '\nTarget posts (Blog 11): ' + data.target_count +
                    '\nRemaining to migrate: ' + data.remaining
                );
            } catch (e) {
                showOutput(e.message, 'error');
            }
            setButtons(true);
        });

        migrateBtn.addEventListener('click', function () {
            if (!window.confirm('Start migration? This will copy all festival_wire posts from Blog 1 to Blog 11.')) {
                return;
            }
            hideProgress();
            runMigration();
        });

        deleteSourceBtn.addEventListener('click', function () {
            if (!window.confirm('DELETE ALL festival_wire posts and their attachments from Blog 1 (source)?')) {
                return;
            }
            if (!window.confirm('Are you absolutely sure? This cannot be undone.')) {
                return;
            }
            hideProgress();
            runDeleteSource();
        });

        resetBtn.addEventListener('click', async function () {
            if (!window.confirm('DELETE ALL festival_wire posts and their media on Blog 11 (target)?')) {
                return;
            }
            if (!window.confirm('Are you absolutely sure? This cannot be undone.')) {
                return;
            }

            setButtons(false);
            hideProgress();

            try {
                var data = await ajax('ec_fwm_reset');
                showOutput('Reset complete!\n\nDeleted posts: ' + data.deleted_posts + '\nDeleted attachments: ' + data.deleted_attachments);
            } catch (e) {
                showOutput(e.message, 'error');
            }

            setButtons(true);
        });
    });
})();
