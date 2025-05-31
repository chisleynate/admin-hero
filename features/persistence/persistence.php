<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'admin_hero_features', function( $features ) {
    $assets_dir = ADMIN_HERO_PRO_DIR . 'features/persistence/assets/';
    $assets_url = ADMIN_HERO_PRO_URL . 'features/persistence/assets/';

    $js_files  = glob( $assets_dir . 'persistence*.js' );
    $css_files = glob( $assets_dir . 'persistence*.css' );
    $js_file   = ! empty( $js_files )  ? basename( $js_files[0] )  : 'persistence.js';
    $css_file  = ! empty( $css_files ) ? basename( $css_files[0] ) : 'persistence.css';

    $features[] = [
        'id'                => 'persistence',
        'name'              => 'Persistence Mode',
        'enabled'           => false,
        'priority'          => 20,
        'sanitize_callback' => function( $value ) {
            return (bool) $value;
        },
        'enqueue_callback'  => function() use ( $assets_url, $js_file, $css_file ) {
            wp_enqueue_script(
                'admin-hero-persistence-js',
                $assets_url . $js_file,
                [ 'admin-hero-js' ],
                ADMIN_HERO_PRO_VERSION,
                true
            );
            wp_enqueue_style(
                'admin-hero-persistence-css',
                $assets_url . $css_file,
                [ 'admin-hero-css' ],
                ADMIN_HERO_PRO_VERSION
            );
        },
    ];

    return $features;
} );
