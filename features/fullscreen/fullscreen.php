<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'admin_hero_features', function( $features ) {
    $assets_dir = ADMIN_HERO_DIR  . 'features/fullscreen/assets/';
    $assets_url = ADMIN_HERO_URL  . 'features/fullscreen/assets/';

    $js_files  = glob( $assets_dir . 'fullscreen*.js' );
    $css_files = glob( $assets_dir . 'fullscreen*.css' );
    $js_file   = ! empty( $js_files )  ? basename( $js_files[0] )  : 'fullscreen.js';
    $css_file  = ! empty( $css_files ) ? basename( $css_files[0] ) : 'fullscreen.css';

    $features[] = [
        'id'                => 'fullscreen',
        'name'              => 'Fullscreen Mode',
        'enabled'           => false,
        'priority'          => 1,
        'sanitize_callback' => function( $value ) {
            return (bool) $value;
        },
        'enqueue_callback'  => function() use ( $assets_url, $js_file, $css_file ) {
            wp_enqueue_script(
                'admin-hero-fullscreen-js',
                $assets_url . $js_file,
                [ 'admin-hero-js' ],
                ADMIN_HERO_VERSION,
                true
            );
            wp_enqueue_style(
                'admin-hero-fullscreen-css',
                $assets_url . $css_file,
                [ 'admin-hero-css' ],
                ADMIN_HERO_VERSION
            );
        },
    ];

    return $features;
} );
