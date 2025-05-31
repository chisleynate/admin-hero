<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'admin_hero_features', function( $features ) {
    $assets_dir = ADMIN_HERO_PRO_DIR  . 'features/frontend/assets/';
    $assets_url = ADMIN_HERO_PRO_URL  . 'features/frontend/assets/';

    $js_files  = glob( $assets_dir . 'frontend*.js' );
    $css_files = glob( $assets_dir . 'frontend*.css' );
    $js_file   = ! empty( $js_files )  ? basename( $js_files[0] )  : 'frontend.js';
    $css_file  = ! empty( $css_files ) ? basename( $css_files[0] )  : 'frontend.css';

    //
    // 1) Frontend Mode toggle (now also injects/removes the floating button on the front end)
    //
    $features[] = [
        'id'                => 'frontend',
        'name'              => __( 'Frontend Mode', 'admin-hero-pro' ),
        'description'       => __( 'Enable the notes modal (and floating button) on the front end for users with edit_posts capability.', 'admin-hero-pro' ),
        'default'           => false,
        'priority'          => 50,
        'sanitize_callback' => function( $value ) {
            return (bool) $value;
        },
        'enqueue_callback'  => function() use ( $assets_url, $js_file, $css_file ) {
            // 1) Make sure Font Awesome is loaded (for the globe icon)
            wp_enqueue_style(
                'admin-hero-fa',
                ADMIN_HERO_URL . 'assets/fontawesome/css/all.min.css',
                [],
                '6.7.2'
            );
            // 2) Our feature’s CSS
            wp_enqueue_style(
                'admin-hero-frontend-css',
                $assets_url . $css_file,
                [ 'admin-hero-css', 'admin-hero-fa' ],
                ADMIN_HERO_PRO_VERSION
            );
            // 3) Our feature’s JS
            wp_enqueue_script(
                'admin-hero-frontend-js',
                $assets_url . $js_file,
                [ 'admin-hero-js' ],
                ADMIN_HERO_PRO_VERSION,
                true
            );
        },
        'initialize' => function() {
            add_action( 'wp', function() {
                if (
                    ! is_user_logged_in()
                    || ! current_user_can( 'edit_posts' )
                    || ! get_user_meta( get_current_user_id(), 'admin_hero_feature_frontend', true )
                ) {
                    return;
                }

                // Enqueue “core + Quill + FA” on the front end
                add_action( 'wp_enqueue_scripts', function() {
                    // Core CSS
                    $css_files = glob( ADMIN_HERO_DIR . 'assets/css/admin-hero*.css' );
                    $css_file  = ! empty( $css_files ) ? basename( $css_files[0] ) : 'admin-hero.css';
                    wp_enqueue_style(
                        'admin-hero-css',
                        ADMIN_HERO_URL . 'assets/css/' . $css_file,
                        [],
                        ADMIN_HERO_VERSION
                    );

                    // Core JS
                    $js_files = glob( ADMIN_HERO_DIR . 'assets/js/admin-hero*.js' );
                    $js_file  = ! empty( $js_files ) ? basename( $js_files[0] ) : 'admin-hero.js';
                    wp_enqueue_script(
                        'admin-hero-js',
                        ADMIN_HERO_URL . 'assets/js/' . $js_file,
                        [ 'jquery' ],
                        ADMIN_HERO_VERSION,
                        true
                    );

                    // Quill Editor
                    wp_enqueue_style( 'quill-css', ADMIN_HERO_URL . 'assets/quilleditor/quill.snow.css', [], '2.0.3' );
                    wp_enqueue_script( 'quill-js', ADMIN_HERO_URL . 'assets/quilleditor/quill.min.js', [], '2.0.3', true );

                    // Font Awesome (already registered above)
                    wp_enqueue_style( 'admin-hero-fa' );

                    // Localize: expose “frontend” flag to frontend.js
                    wp_localize_script( 'admin-hero-js', 'AdminHero', [
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'nonce'    => wp_create_nonce( 'admin_hero_action' ),
                        'version'  => ADMIN_HERO_VERSION,
                        'is_admin' => false,
                        'features' => ( new Admin_Hero_Feature_Loader() )->get_features(),
                    ] );
                }, 11 );

                // Render the modal in the footer
                add_action( 'wp_footer', function() {
                    $user       = wp_get_current_user();
                    $note       = get_user_meta( $user->ID, 'admin_hero_text', true );
                    $last_saved = get_user_meta( $user->ID, 'admin_hero_last_saved', true );
                    $timestamp  = 'Last saved: ' . (
                        $last_saved
                            ? date_i18n( ADMIN_HERO_TIMESTAMP_FORMAT, strtotime( $last_saved ) )
                            : 'Never saved'
                    );
                    $nonce      = wp_create_nonce( 'admin_hero_action' );

                    do_action( 'admin_hero_render_modal_before', $user, $note, $timestamp, $nonce );

                    $modal_file = apply_filters(
                        'admin_hero_modal_template',
                        glob( ADMIN_HERO_DIR . 'templates/modal*.php' )[0]
                        ?? ADMIN_HERO_DIR . 'templates/modal.php'
                    );

                    if ( file_exists( $modal_file ) ) {
                        include $modal_file;
                    }

                    do_action( 'admin_hero_render_modal_after' );
                }, 11 );
            } );
        },
    ];

    return $features;
} );
