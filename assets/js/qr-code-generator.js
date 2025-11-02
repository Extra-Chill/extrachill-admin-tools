/**
 * QR Code Generator Tool JavaScript
 */

(function($) {
    'use strict';

    let currentQrDataUri = null;

    $(document).ready(function() {
        const $form = $('#ec-qr-generator-form');
        const $generateBtn = $('#ec-qr-generate-btn');
        const $spinner = $form.find('.spinner');
        const $result = $('#ec-qr-result');
        const $preview = $('#ec-qr-preview');
        const $downloadBtn = $('#ec-qr-download-btn');
        const $error = $('#ec-qr-error');
        const $urlInput = $('#ec-qr-url');

        $form.on('submit', function(e) {
            e.preventDefault();

            const url = $urlInput.val().trim();

            if (!url) {
                showError('Please enter a URL.');
                return;
            }

            hideError();
            hideResult();

            $generateBtn.prop('disabled', true);
            $spinner.addClass('is-active');

            $.ajax({
                url: ecQrCodeGen.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ec_generate_qr_code',
                    nonce: ecQrCodeGen.nonce,
                    url: url
                },
                success: function(response) {
                    if (response.success && response.data.imageUrl) {
                        currentQrDataUri = response.data.imageUrl;
                        displayQrCode(response.data.imageUrl, response.data.url);
                    } else {
                        showError(response.data.message || 'Failed to generate QR code.');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                        ? xhr.responseJSON.data.message
                        : 'An error occurred while generating the QR code.';
                    showError(message);
                },
                complete: function() {
                    $generateBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });

        $downloadBtn.on('click', function() {
            if (!currentQrDataUri) {
                return;
            }

            const url = $urlInput.val().trim();
            const filename = generateFilename(url);

            const link = document.createElement('a');
            link.href = currentQrDataUri;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        function displayQrCode(dataUri, url) {
            $preview.html('<img src="' + dataUri + '" alt="QR Code for ' + escapeHtml(url) + '" />');
            $result.show();
        }

        function hideResult() {
            $result.hide();
            $preview.html('');
            currentQrDataUri = null;
        }

        function showError(message) {
            $error.find('p').text(message);
            $error.show();
        }

        function hideError() {
            $error.hide();
            $error.find('p').text('');
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

})(jQuery);
