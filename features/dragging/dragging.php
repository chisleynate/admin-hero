<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'admin_hero_features', function( $features ) {
    $assets_dir = ADMIN_HERO_PRO_DIR  . 'features/dragging/assets/';
    $assets_url = ADMIN_HERO_PRO_URL  . 'features/dragging/assets/';

    $js_files  = glob( $assets_dir . 'dragging*.js' );
    $css_files = glob( $assets_dir . 'dragging*.css' );
    $js_file   = ! empty( $js_files )  ? basename( $js_files[0])  : 'dragging.js';
    $css_file  = ! empty( $css_files ) ? basename($css_files[0])  : 'dragging.css';

    $features[] = [
        'id'                => 'dragging',
        'name'              => 'Dragging Modal',
        'enabled'           => false,
        'priority'          => 30,
        'sanitize_callback' => function( $value ) {
            return (bool) $value;
        },
        'enqueue_callback'  => function() use ( $assets_url, $js_file, $css_file ) {
            wp_enqueue_script(
                'admin-hero-dragging-js',
                $assets_url . $js_file,
                [ 'admin-hero-js' ],
                ADMIN_HERO_PRO_VERSION,
                true
            );
            wp_enqueue_style(
                'admin-hero-dragging-css',
                $assets_url . $css_file,
                [ 'admin-hero-css' ],
                ADMIN_HERO_PRO_VERSION
            );
        },
    ];

    return $features;
} );
