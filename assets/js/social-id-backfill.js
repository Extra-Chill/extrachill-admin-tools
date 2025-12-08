(function() {
    const form = document.getElementById('ec-social-id-backfill-form');
    const output = document.getElementById('ec-social-id-backfill-output');
    const spinner = form ? form.querySelector('.spinner') : null;

    if (!form || !output || !window.ecSocialIdBackfill) {
        return;
    }

    const { restUrl, nonce, blogId } = window.ecSocialIdBackfill;

    const renderMessage = (message, isError = false) => {
        output.innerHTML = '';
        const div = document.createElement('div');
        div.className = isError ? 'notice notice-error' : 'notice notice-success';
        div.innerHTML = `<p>${ message }</p>`;
        output.appendChild(div);
    };

    const renderSummary = (data) => {
        const { dry_run, summary } = data;
        const rows = [
            ['Dry run', dry_run ? 'Yes' : 'No'],
            ['Artists scanned', summary.scanned_artists],
            ['Artists updated', summary.updated_artists],
            ['Skipped (empty/no link page)', summary.skipped_empty],
        ];

        let html = '<table class="widefat fixed" style="max-width:640px">';
        html += '<thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>';
        rows.forEach(([label, value]) => {
            html += `<tr><td>${ label }</td><td>${ value }</td></tr>`;
        });
        html += '</tbody></table>';

        if (summary.samples && summary.samples.length) {
            html += '<h4>Sample updates</h4><ul>';
            summary.samples.forEach((sample) => {
                html += `<li>Artist ${ sample.artist_id } / Link Page ${ sample.link_page_id }: ${ sample.social_ids.join(', ') }</li>`;
            });
            html += '</ul>';
        }

        if (summary.errors && summary.errors.length) {
            html += '<h4>Errors</h4><ul>';
            summary.errors.forEach((err) => {
                html += `<li>${ err }</li>`;
            });
            html += '</ul>';
        }

        output.innerHTML = html;
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (parseInt(blogId, 10) !== 4) {
            renderMessage('This tool must run on artist.extrachill.com (blog ID 4).', true);
            return;
        }

        const dryRun = !!form.querySelector('input[name="dry_run"]:checked');

        if (spinner) {
            spinner.style.visibility = 'visible';
        }
        renderMessage('Running...', false);

        try {
            const res = await fetch(restUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify({ dry_run: dryRun }),
            });

            const data = await res.json();

            if (!res.ok) {
                const msg = (data && data.message) ? data.message : 'Request failed';
                renderMessage(msg, true);
                return;
            }

            renderSummary(data);
        } catch (err) {
            renderMessage(err && err.message ? err.message : 'Request error', true);
        } finally {
            if (spinner) {
                spinner.style.visibility = 'hidden';
            }
        }
    });
})();
