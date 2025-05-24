<?php
// Free Info feature — always on, no Settings toggle
add_filter( 'admin_hero_features', function( $features ) {
    $features[] = [
        'id'                => 'info',
        'name'              => 'Info',
        'enabled'           => true,
        'priority'          => 50,

        // Prevent a checkbox for this feature in Settings
        'settings_ui_callback' => function() {},

        // Render the newest info-panel*.php
        'render_callback'   => function() {
            $tpls = glob( ADMIN_HERO_DIR . 'features/info/templates/info-panel*.php' );
            rsort( $tpls );
            $file = $tpls[0] ?? ADMIN_HERO_DIR . 'features/info/templates/info-panel.php';
            if ( file_exists( $file ) ) {
                include $file;
            }
        },

        // Save & sanitize all four fields
        'sanitize_callback' => function( $input ) {
            return [
                'name'    => sanitize_text_field( $input['name']    ?? '' ),
                'company' => sanitize_text_field( $input['company'] ?? '' ),
                'email'   => sanitize_email(     $input['email']   ?? '' ),
                'phone'   => sanitize_text_field( $input['phone']   ?? '' ),
            ];
        },
    ];
    return $features;
}, 20 );

// AJAX handler: saves all four info fields
add_action( 'wp_ajax_admin_hero_save_info', function() {
    check_ajax_referer( 'admin_hero_action', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized' ] );
    }

    $uid = get_current_user_id();
    $map = [
        'name'    => 'admin_hero_info_name',
        'company' => 'admin_hero_info_company',
        'email'   => 'admin_hero_info_email',
        'phone'   => 'admin_hero_info_phone',
    ];

    foreach ( $map as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_user_meta(
                $uid,
                $meta_key,
                sanitize_text_field( wp_unslash( $_POST[ $field ] ) )
            );
        }
    }

    wp_send_json_success( [ 'message' => 'Info updated' ] );
} );
