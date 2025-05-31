<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'admin_hero_features', function( $features ) {
    $assets_dir = ADMIN_HERO_PRO_DIR  . 'features/floater-d/assets/';
    $assets_url = ADMIN_HERO_PRO_URL  . 'features/floater-d/assets/';

    $js_files  = glob( $assets_dir . 'floater-d*.js' );
    $css_files = glob( $assets_dir . 'floater-d*.css' );
    $js_file   = ! empty( $js_files )  ? basename( $js_files[0] )  : 'floater-d.js';
    $css_file  = ! empty( $css_files ) ? basename( $css_files[0] ) : 'floater-d.css';

    $features[] = [
        'id'                => 'floater-d',
        'name'              => __( 'Floater (Dashboard)', 'admin-hero-pro' ),
        'description'       => __( 'Show a floating notes button in the admin dashboard.', 'admin-hero-pro' ),
        'default'           => false,
        'priority'          => 40,
        'sanitize_callback' => function( $value ) {
            return (bool) $value;
        },
        'enqueue_callback'  => function() use ( $assets_url, $js_file, $css_file ) {
            // Always load our JS/CSS (itself decides whether to inject in admin or not)
            wp_enqueue_script(
                'admin-hero-floater-d-js',
                $assets_url . $js_file,
                [ 'admin-hero-js' ],
                ADMIN_HERO_PRO_VERSION,
                true
            );
            wp_enqueue_style(
                'admin-hero-floater-d-css',
                $assets_url . $css_file,
                [ 'admin-hero-css' ],
                ADMIN_HERO_PRO_VERSION
            );
        },
    ];

    return $features;
} );
