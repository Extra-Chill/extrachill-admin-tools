/**
 * QR Code Generator Tool JavaScript
 *
 * Calls the extrachill-api REST endpoint for QR code generation.
 */

(function() {
    'use strict';

    let currentQrDataUri = null;

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('ec-qr-generator-form');
        const generateBtn = document.getElementById('ec-qr-generate-btn');
        const spinner = form ? form.querySelector('.spinner') : null;
        const result = document.getElementById('ec-qr-result');
        const preview = document.getElementById('ec-qr-preview');
        const downloadBtn = document.getElementById('ec-qr-download-btn');
        const error = document.getElementById('ec-qr-error');
        const urlInput = document.getElementById('ec-qr-url');

        if (!form || !urlInput) {
            return;
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const url = urlInput.value.trim();

            if (!url) {
                showError('Please enter a URL.');
                return;
            }

            hideError();
            hideResult();

            generateBtn.disabled = true;
            if (spinner) {
                spinner.classList.add('is-active');
            }

            fetch(ecQrCodeGen.restUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ecQrCodeGen.nonce
                },
                body: JSON.stringify({ url: url })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                if (result.ok && result.data.success && result.data.image_url) {
                    currentQrDataUri = result.data.image_url;
                    displayQrCode(result.data.image_url, result.data.url);
                } else {
                    const message = result.data.message || 'Failed to generate QR code.';
                    showError(message);
                }
            })
            .catch(function() {
                showError('An error occurred while generating the QR code.');
            })
            .finally(function() {
                generateBtn.disabled = false;
                if (spinner) {
                    spinner.classList.remove('is-active');
                }
            });
        });

        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                if (!currentQrDataUri) {
                    return;
                }

                const url = urlInput.value.trim();
                const filename = generateFilename(url);

                const link = document.createElement('a');
                link.href = currentQrDataUri;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }

        function displayQrCode(dataUri, url) {
            if (preview) {
                preview.innerHTML = '<img src="' + dataUri + '" alt="QR Code for ' + escapeHtml(url) + '" />';
            }
            if (result) {
                result.style.display = 'block';
            }
        }

        function hideResult() {
            if (result) {
                result.style.display = 'none';
            }
            if (preview) {
                preview.innerHTML = '';
            }
            currentQrDataUri = null;
        }

        function showError(message) {
            if (error) {
                const p = error.querySelector('p');
                if (p) {
                    p.textContent = message;
                }
                error.style.display = 'block';
            }
        }

        function hideError() {
            if (error) {
                error.style.display = 'none';
                const p = error.querySelector('p');
                if (p) {
                    p.textContent = '';
                }
            }
        }

        function generateFilename(url) {
            try {
                const urlObj = new URL(url);
                let host = urlObj.hostname.replace(/^www\./, '');
                let path = urlObj.pathname.replace(/^\//, '').replace(/\//g, '-');

                if (path && path.length > 0 && path !== '-') {
                    return 'qrcode-' + host + '-' + path + '.png';
                } else {
                    return 'qrcode-' + host + '.png';
                }
            } catch (e) {
                return 'qrcode.png';
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    });

})();
