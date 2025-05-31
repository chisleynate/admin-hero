<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'admin_hero_features', function( $features ) {
    $assets_dir = ADMIN_HERO_PRO_DIR  . 'features/autosave/assets/';
    $assets_url = ADMIN_HERO_PRO_URL  . 'features/autosave/assets/';

    $js_files  = glob( $assets_dir . 'autosave*.js' );
    $css_files = glob( $assets_dir . 'autosave*.css' );
    $js_file   = ! empty( $js_files )  ? basename( $js_files[0] )  : 'autosave.js';
    $css_file  = ! empty( $css_files ) ? basename( $css_files[0] ) : 'autosave.css';

    $features[] = [
        'id'                => 'autosave',
        'name'              => 'Autosave Notes',
        'enabled'           => false,
        'priority'          => 10,
        'sanitize_callback' => function( $value ) {
            return (bool) $value;
        },
        'enqueue_callback'  => function() use ( $assets_url, $js_file, $css_file ) {
            wp_enqueue_script(
                'admin-hero-autosave-js',
                $assets_url . $js_file,
                [ 'admin-hero-js' ],
                ADMIN_HERO_PRO_VERSION,
                true
            );
            wp_enqueue_style(
                'admin-hero-autosave-css',
                $assets_url . $css_file,
                [ 'admin-hero-css' ],
                ADMIN_HERO_PRO_VERSION
            );
        },
    ];

    return $features;
} );
