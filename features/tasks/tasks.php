<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ALWAYS enqueue Tasks JS and CSS for AdminHero Free.
 * (No “Settings checkbox”—Tasks is always “on,” just like Info.)
 */
add_action( 'admin_enqueue_scripts', function() {
    $assets_dir = ADMIN_HERO_DIR  . 'features/tasks/assets/';
    $assets_url = ADMIN_HERO_URL  . 'features/tasks/assets/';

    // Look for tasks*.js and tasks*.css in the assets folder
    $js_files  = glob( $assets_dir . 'tasks*.js' );
    $css_files = glob( $assets_dir . 'tasks*.css' );
    $js_file   = ! empty( $js_files )  ? basename( $js_files[0] )  : 'tasks5.js';
    $css_file  = ! empty( $css_files ) ? basename( $css_files[0] ) : 'tasks5.css';

    // Enqueue JS (depends on admin-hero-js) and CSS (depends on admin-hero-css)
    wp_enqueue_script(
        'admin-hero-tasks-js',
        $assets_url . $js_file,
        [ 'admin-hero-js' ],
        ADMIN_HERO_VERSION,
        true
    );
    wp_enqueue_style(
        'admin-hero-tasks-css',
        $assets_url . $css_file,
        [ 'admin-hero-css' ],
        ADMIN_HERO_VERSION
    );
} );
