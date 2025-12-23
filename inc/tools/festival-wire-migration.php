<?php
/**
 * Festival Wire Migration Tool
 *
 * Admin UI shell that calls extrachill-api REST endpoints to migrate
 * festival_wire posts from the main site to wire.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'tools_page_extrachill-admin-tools' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'ec-festival-wire-migration',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/festival-wire-migration.js',
        array( 'extrachill-admin-tools' ),
        filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/festival-wire-migration.js' ),
        true
    );
} );

add_filter( 'extrachill_admin_tools', function ( $tools ) {
    $tools[] = array(
        'id'          => 'festival-wire-migration',
        'title'       => 'Festival Wire Migration',
        'description' => 'Migrate festival_wire posts from extrachill.com to wire.extrachill.com. Includes featured images and embedded attachments.',
        'callback'    => 'ec_festival_wire_migration_admin_page',
    );

    return $tools;
}, 40 );

function ec_festival_wire_migration_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized', 'extrachill-admin-tools' ) );
    }

    ?>
    <div class="ec-festival-wire-migration" id="ec-fwm" style="max-width: 900px;">
        <p><strong>Source:</strong> extrachill.com (Blog 1) → <strong>Target:</strong> wire.extrachill.com (Blog 11)</p>

        <div style="margin: 1rem 0;">
            <button type="button" class="button" id="ec-fwm-preflight">Run Preflight</button>
            <button type="button" class="button button-primary" id="ec-fwm-migrate">Migrate Next Batch</button>
            <button type="button" class="button" id="ec-fwm-validate">Validate</button>
            <button type="button" class="button button-secondary" id="ec-fwm-delete" style="margin-left: 1rem;">Delete Source Batch</button>
        </div>

        <div style="margin-bottom: 1rem;">
            <label for="ec-fwm-batch-size"><strong>Batch size</strong></label>
            <input type="number" id="ec-fwm-batch-size" value="25" min="1" max="200" style="width: 90px;">
        </div>

        <div id="ec-fwm-output" style="display:none;"></div>
    </div>
    <?php
}
