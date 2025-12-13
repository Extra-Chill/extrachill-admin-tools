<?php
/**
 * Link Page Font Migration Tool (single-use)
 *
 * Migrates stored link page font values from legacy "WilcoLoftSans" (or stacks
 * that start with it) to the theme-aligned font family name "Loft Sans".
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'tools_page_extrachill-admin-tools' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'ec-link-page-font-migration',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/link-page-font-migration.js',
        array(),
        filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/link-page-font-migration.js' ),
        true
    );

    wp_localize_script(
        'ec-link-page-font-migration',
        'ecLinkPageFontMigration',
        array(
            'restUrl' => rest_url( 'extrachill-admin-tools/v1/link-page-font-migration/run' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        )
    );
} );

add_filter( 'extrachill_admin_tools', function ( $tools ) {
    $tools[] = array(
        'id'          => 'link-page-font-migration',
        'title'       => 'Link Page Font Migration',
        'description' => 'One-time migration: normalize link page font values to match the theme (Loft Sans).',
        'callback'    => 'ec_link_page_font_migration_tool_page',
    );

    return $tools;
}, 99 );

function ec_link_page_font_migration_tool_page() {
    echo '<div class="notice notice-info">';
    echo '<p><strong>One-time tool:</strong> This updates <code>_link_page_custom_css_vars</code> for <code>artist_link_page</code> posts on the current site only.</p>';
    echo '<p>It rewrites <code>WilcoLoftSans</code> (or stacks starting with it) to <code>Loft Sans</code> for title/body font-family.</p>';
    echo '</div>';

    echo '<p><button type="button" class="button button-primary" id="ec-link-page-font-migration-run">Run migration</button></p>';
    echo '<div id="ec-link-page-font-migration-status"></div>';
}

add_action( 'rest_api_init', function () {
    register_rest_route(
        'extrachill-admin-tools/v1',
        '/link-page-font-migration/run',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'ec_link_page_font_migration_run',
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'offset'   => array(
                    'type'              => 'integer',
                    'required'          => false,
                    'sanitize_callback' => 'absint',
                    'default'           => 0,
                ),
                'per_page' => array(
                    'type'              => 'integer',
                    'required'          => false,
                    'sanitize_callback' => 'absint',
                    'default'           => 50,
                ),
            ),
        )
    );
} );

function ec_link_page_font_migration_run( WP_REST_Request $request ) {
    $offset   = absint( $request->get_param( 'offset' ) );
    $per_page = absint( $request->get_param( 'per_page' ) );

    if ( $per_page < 1 ) {
        $per_page = 50;
    }

    $post_ids = get_posts(
        array(
            'post_type'      => 'artist_link_page',
            'post_status'    => 'any',
            'posts_per_page' => $per_page,
            'offset'         => $offset,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
        )
    );

    $scanned         = count( $post_ids );
    $updated_posts   = 0;
    $updated_fields  = 0;

    foreach ( $post_ids as $post_id ) {
        $vars = get_post_meta( $post_id, '_link_page_custom_css_vars', true );
        if ( ! is_array( $vars ) ) {
            continue;
        }

        $changed = false;

        foreach ( array( '--link-page-title-font-family', '--link-page-body-font-family' ) as $key ) {
            if ( empty( $vars[ $key ] ) || ! is_string( $vars[ $key ] ) ) {
                continue;
            }

            $font_value = $vars[ $key ];

            $primary = $font_value;
            if ( strpos( $primary, ',' ) !== false ) {
                $primary = explode( ',', $primary, 2 )[0];
            }

            $primary = trim( $primary, " \t\n\r\0\x0B\"'" );

            if ( 'WilcoLoftSans' !== $primary ) {
                continue;
            }

            $vars[ $key ] = 'Loft Sans';
            $changed      = true;
            $updated_fields++;
        }

        if ( $changed ) {
            update_post_meta( $post_id, '_link_page_custom_css_vars', $vars );
            $updated_posts++;
        }
    }

    $next_offset = $offset + $scanned;

    return rest_ensure_response(
        array(
            'scanned'        => $scanned,
            'offset'         => $offset,
            'next_offset'    => $next_offset,
            'updated_posts'  => $updated_posts,
            'updated_fields' => $updated_fields,
            'done'           => $scanned < $per_page,
        )
    );
}
