<?php
/**
 * Taxonomy Sync Tool
 *
 * UI for syncing shared taxonomies from the main site to other sites.
 * Server-side work is performed via extrachill-api REST.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'extra-chill-multisite_page_extrachill-admin-tools' !== $hook ) {
        return;
    }
    wp_enqueue_script(
        'ec-taxonomy-sync',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/taxonomy-sync.js',
        array( 'extrachill-admin-tools' ),
        filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/taxonomy-sync.js' ),
        true
    );
} );
add_filter( 'extrachill_admin_tools', function ( $tools ) {
    $tools[] = array(
        'id'          => 'taxonomy-sync',
        'title'       => 'Taxonomy Sync',
        'description' => 'Synchronize taxonomies from main site to other network sites.',
        'callback'    => 'ec_taxonomy_sync_admin_page',
    );
    return $tools;
}, 30 );
function ec_taxonomy_sync_admin_page() {
    if ( ! current_user_can( 'manage_network_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access', 'extrachill-admin-tools' ) );
    }
    $taxonomies = array(
        'location' => 'Location (Hierarchical)',
        'festival' => 'Festival',
        'artist'   => 'Artist',
        'venue'    => 'Venue',
    );
    $target_sites = array(
        'events' => 'events.extrachill.com',
        'wire'   => 'wire.extrachill.com',
    );
    if ( function_exists( 'ec_get_blog_id' ) ) {
        foreach ( array_keys( $target_sites ) as $site_slug ) {
            if ( ! ec_get_blog_id( $site_slug ) ) {
                unset( $target_sites[ $site_slug ] );
            }
        }
    }
    ?>
    <div class="ec-taxonomy-sync-wrap">
        <h3>Select Target Sites</h3>
        <p>Choose which sites should receive taxonomies from extrachill.com (Blog ID 1):</p>
        <div class="ec-site-selection" style="margin-bottom: 1.5em;">
            <?php foreach ( $target_sites as $site_slug => $site_name ) : ?>
                <?php $blog_id = function_exists( 'ec_get_blog_id' ) ? ec_get_blog_id( $site_slug ) : 0; ?>
                <label style="display: block; margin-bottom: 0.5em;">
                    <input type="checkbox" name="target_sites[]" value="<?php echo esc_attr( $site_slug ); ?>" checked>
                    <?php echo esc_html( $site_name ); ?> (Blog ID <?php echo absint( $blog_id ); ?>)
                </label>
            <?php endforeach; ?>
        </div>
        <h3>Select Taxonomies</h3>
        <p>Choose which taxonomies to sync (all selected by default):</p>
        <div class="ec-taxonomy-selection" style="margin-bottom: 1.5em;">
            <?php foreach ( $taxonomies as $slug => $label ) : ?>
                <label style="display: block; margin-bottom: 0.5em;">
                    <input type="checkbox" name="taxonomies[]" value="<?php echo esc_attr( $slug ); ?>" checked>
                    <?php echo esc_html( $label ); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button button-primary" id="ec-sync-taxonomies">
            Sync Taxonomies
        </button>
        <div id="ec-taxonomy-sync-report" style="display:none; margin-top: 1.5em;"></div>
    </div>
    <?php
}

