<?php
/**
 * QR Code Generator Tool
 *
 * Admin UI for generating QR codes. Calls the extrachill-api REST endpoint
 * for actual QR code generation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'tools_page_extrachill-admin-tools' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'ec-qr-code-generator',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/css/qr-code-generator.css',
        array(),
        filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/css/qr-code-generator.css' )
    );

    wp_enqueue_script(
        'ec-qr-code-generator',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/qr-code-generator.js',
        array(),
        filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/qr-code-generator.js' ),
        true
    );

    wp_localize_script(
        'ec-qr-code-generator',
        'ecQrCodeGen',
        array(
            'restUrl' => rest_url( 'extrachill/v1/tools/qr-code' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        )
    );
});

add_filter( 'extrachill_admin_tools', function ( $tools ) {
    $tools[] = array(
        'id'          => 'qr-code-generator',
        'title'       => 'QR Code Generator',
        'description' => 'Generate high-resolution print-ready QR codes for any URL. Works with both internal and external URLs.',
        'callback'    => 'ec_qr_code_generator_page',
    );
    return $tools;
}, 10 );

/**
 * Renders the QR code generator admin page.
 */
function ec_qr_code_generator_page() {
    ?>
    <div class="ec-qr-generator-wrapper">
        <form id="ec-qr-generator-form" class="ec-qr-form">
            <div class="ec-qr-input-group">
                <label for="ec-qr-url">
                    <strong>Enter URL:</strong>
                    <span style="display:block; margin-top:0.25em; font-weight:normal; color:#666;">
                        Paste any URL (extrachill.com pages, extrachill.link pages, external websites, etc.)
                    </span>
                </label>
                <input
                    type="url"
                    id="ec-qr-url"
                    name="url"
                    placeholder="https://example.com"
                    required
                    style="width:100%; max-width:600px;"
                >
            </div>
            <button type="submit" class="button button-primary" id="ec-qr-generate-btn">
                Generate QR Code
            </button>
            <span class="spinner" style="float:none; margin:0 0 0 10px;"></span>
        </form>

        <div id="ec-qr-result" style="display:none; margin-top:2em;">
            <h3>Generated QR Code</h3>
            <p style="color:#666; margin-bottom:1em;">
                Preview (scaled for display) - Download for full 1000x1000px print-ready resolution
            </p>
            <div id="ec-qr-preview" style="margin-bottom:1em;"></div>
            <button type="button" class="button button-primary" id="ec-qr-download-btn">
                Download PNG (1000x1000px)
            </button>
        </div>

        <div id="ec-qr-error" class="notice notice-error" style="display:none; margin-top:1em;">
            <p></p>
        </div>
    </div>
    <?php
}
