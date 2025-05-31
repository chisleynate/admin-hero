<?php
/*
 * Plugin Name:       AdminHero Pro
 * Plugin URI:        https://adminhero.pro
 * EULA URI:          https://adminhero.pro/wp-content/eula/EULA.txt
 * Description:       Unlocks premium features for AdminHero.
 * Version:           1.0.12
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Nate Chisley
 * Author URI:        https://adminhero.pro
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       admin-hero-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// REDIRECT TO LICENSE SETTINGS AFTER ACTIVATION
// -----------------------------------------------------------------------------
register_activation_hook( __FILE__, 'admin_hero_pro_set_activation_redirect' );
function admin_hero_pro_set_activation_redirect() {
    add_option( 'admin_hero_pro_do_activation_redirect', true );
}
add_action( 'admin_init', 'admin_hero_pro_do_activation_redirect' );
function admin_hero_pro_do_activation_redirect() {
    if ( ! get_option( 'admin_hero_pro_do_activation_redirect', false ) ) {
        return;
    }
    delete_option( 'admin_hero_pro_do_activation_redirect' );
    if ( isset( $_GET['activate-multi'] ) ) {
        return;
    }
    wp_redirect( admin_url( 'admin.php?page=admin-hero-pro' ) );
    exit;
}

// -----------------------------------------------------------------------------
// REDIRECT TO LICENSE SETTINGS AFTER PLUGIN UPDATE
// -----------------------------------------------------------------------------
add_action( 'upgrader_process_complete', 'admin_hero_pro_set_update_redirect', 10, 2 );
function admin_hero_pro_set_update_redirect( $upgrader, $options ) {
    if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
        if ( ! empty( $options['plugins'] )
            && in_array( plugin_basename( __FILE__ ), $options['plugins'], true )
        ) {
            add_option( 'admin_hero_pro_do_activation_redirect', true );
        }
    }
}

// -----------------------------------------------------------------------------
// PLUGIN BASE CONSTANTS
// -----------------------------------------------------------------------------
define( 'ADMIN_HERO_PRO__FILE__',       __FILE__ );
define( 'ADMIN_HERO_PRO_PLUGIN_BASE',   plugin_basename( ADMIN_HERO_PRO__FILE__ ) );

// -----------------------------------------------------------------------------
// PLUGIN METADATA
// -----------------------------------------------------------------------------
$plugin_data = get_file_data( ADMIN_HERO_PRO__FILE__, [ 'Version' => 'Version' ] );
define( 'ADMIN_HERO_PRO_VERSION', $plugin_data['Version'] );
define( 'ADMIN_HERO_PRO_DIR',     plugin_dir_path( ADMIN_HERO_PRO__FILE__ ) );
define( 'ADMIN_HERO_PRO_URL',     plugin_dir_url( ADMIN_HERO_PRO__FILE__ ) );

// -----------------------------------------------------------------------------
// EDD SOFTWARE LICENSING CONSTANTS
// -----------------------------------------------------------------------------
define( 'AH_PRO_STORE_URL', 'https://adminhero.pro' );
define( 'AH_PRO_ITEM_ID',    34 );

// -----------------------------------------------------------------------------
// LICENSE STATUS HELPER
// -----------------------------------------------------------------------------
function admin_hero_pro_license_valid() {
    return get_option( 'admin_hero_pro_license_status', '' ) === 'valid';
}

// -----------------------------------------------------------------------------
// BOOTSTRAP EDD SELF-HOSTED UPDATER (runs on every page load)
// -----------------------------------------------------------------------------
if ( admin_hero_pro_license_valid() ) {
    $updater_file = ADMIN_HERO_PRO_DIR . 'includes/EDD_SL_Plugin_Updater.php';
    if ( file_exists( $updater_file ) ) {
        include_once $updater_file;
        new EDD_SL_Plugin_Updater(
            AH_PRO_STORE_URL,
            ADMIN_HERO_PRO__FILE__,
            [
                'version' => ADMIN_HERO_PRO_VERSION,
                'license' => get_option( 'admin_hero_pro_license_key', '' ),
                'item_id' => AH_PRO_ITEM_ID,
                'author'  => 'Nate Chisley',
            ]
        );
    }
}

// -----------------------------------------------------------------------------
// INCREASE HTTP TIMEOUT FOR LICENSE CALLS
// -----------------------------------------------------------------------------
add_filter( 'http_request_timeout', function( $timeout ) {
    return max( $timeout, 30 );
} );

// -----------------------------------------------------------------------------
// ENQUEUE ADMIN-STYLESHEET ON SETTINGS PAGE (dynamic filename)
// -----------------------------------------------------------------------------
add_action( 'admin_enqueue_scripts', 'admin_hero_pro_enqueue_admin_styles' );
function admin_hero_pro_enqueue_admin_styles( $hook ) {
    if ( $hook !== 'settings_page_admin-hero-pro' ) {
        return;
    }

    $css_files = glob( ADMIN_HERO_PRO_DIR . 'assets/css/admin-hero-pro*.css' );
    if ( ! empty( $css_files ) ) {
        rsort( $css_files );
        $file = basename( $css_files[0] );
    } else {
        $file = 'admin-hero-pro.css';
    }

    wp_enqueue_style(
        'admin-hero-pro-admin',
        ADMIN_HERO_PRO_URL . 'assets/css/' . $file,
        [],
        ADMIN_HERO_PRO_VERSION
    );
}

// -----------------------------------------------------------------------------
// REGISTER PRO FEATURES DIR (always register; each feature will gate itself)
// -----------------------------------------------------------------------------
add_filter( 'admin_hero_feature_dirs', function( $dirs ) {
    $dirs[] = ADMIN_HERO_PRO_DIR . 'features/';
    return $dirs;
}, 20 );

// -----------------------------------------------------------------------------
// OVERRIDE MODAL TEMPLATE (LICENSE-GATED)
// -----------------------------------------------------------------------------
add_filter( 'admin_hero_modal_template', function( $template ) {
    if ( admin_hero_pro_license_valid() ) {
        $tpls = glob( ADMIN_HERO_PRO_DIR . 'templates/modal-pro*.php' );
        rsort( $tpls );
        return $tpls[0] ?? ADMIN_HERO_PRO_DIR . 'templates/modal-pro.php';
    }
    return $template;
}, 20 );

// -----------------------------------------------------------------------------
// REQUIRE FREE PLUGIN
// -----------------------------------------------------------------------------
add_action( 'plugins_loaded', function() {
    if ( ! defined( 'ADMIN_HERO_VERSION' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>AdminHero Pro</strong> requires the free AdminHero plugin.</p></div>';
        } );
    }
}, 20 );

// -----------------------------------------------------------------------------
// SETTINGS & LICENSE ACTIVATION
// -----------------------------------------------------------------------------

// 1) Settings submenu
add_action( 'admin_menu', function() {
    add_options_page(
        'AdminHero Pro Settings',
        'AdminHero Pro',
        'manage_options',
        'admin-hero-pro',
        'admin_hero_pro_render_settings_page'
    );
} );

// 2) Register license key setting
add_action( 'admin_init', function() {
    register_setting( 'admin_hero_pro', 'admin_hero_pro_license_key', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ] );
    add_settings_section(
        'hero_pro_license',
        'License Key',
        function() {
            echo '<p>Enter your AdminHero Pro license key to enable Pro features and auto-updates.</p>';
        },
        'admin-hero-pro'
    );
    add_settings_field(
        'hero_pro_license_key',
        '',
        'admin_hero_pro_license_field_callback',
        'admin-hero-pro',
        'hero_pro_license'
    );
} );

// License field + inline status
function admin_hero_pro_license_field_callback() {
    $key    = get_option( 'admin_hero_pro_license_key', '' );
    $status = get_option( 'admin_hero_pro_license_status', '' );
    printf(
        '<input type="text" id="admin_hero_pro_license_key" name="admin_hero_pro_license_key" value="%s" class="regular-text admin-hero-pro-input" />',
        esc_attr( $key )
    );
    if ( $status ) {
        printf(
            ' <span class="admin-hero-pro-status %1$s">%2$s</span>',
            esc_attr( $status ),
            $status === 'valid'    ? '✔ License active'      :
            ( $status === 'expired' ? '⚠ License expired'    :
            ( $status === 'invalid' ? '✖ License invalid'    :
            ( $status === 'error'   ? '✖ License check error' : '' )))
        );
    }
}

// Render settings page
function admin_hero_pro_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap admin-hero-pro-wrap">
        <img src="<?php echo esc_url( ADMIN_HERO_PRO_URL . 'assets/img/logo-header-001.webp' ); ?>"
             alt="AdminHero Pro" class="admin-hero-pro-logo" />
        <h1>AdminHero Pro Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'admin_hero_pro' );
            do_settings_sections( 'admin-hero-pro' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}

// License activation/deactivation handler
function admin_hero_pro_handle_license_change( $old, $new ) {
    $endpoint = trailingslashit( AH_PRO_STORE_URL );
    $url      = home_url();

    if ( $old && $old !== $new ) {
        wp_remote_post( $endpoint, [
            'body'    => [
                'edd_action' => 'deactivate_license',
                'license'    => $old,
                'item_id'    => AH_PRO_ITEM_ID,
                'url'        => $url,
            ],
            'timeout' => 15,
        ] );
    }

    if ( $new ) {
        $resp = wp_remote_post( $endpoint, [
            'body'    => [
                'edd_action' => 'activate_license',
                'license'    => $new,
                'item_id'    => AH_PRO_ITEM_ID,
                'url'        => $url,
            ],
            'timeout' => 15,
        ] );
        if ( is_wp_error( $resp ) ) {
            $status = 'error';
        } else {
            $code = wp_remote_retrieve_response_code( $resp );
            $body = json_decode( wp_remote_retrieve_body( $resp ) );
            if ( 200 === $code && isset( $body->license ) ) {
                $status = in_array( $body->license, [ 'valid','expired','invalid' ], true )
                    ? $body->license
                    : 'invalid';
            } else {
                $status = 'error';
            }
        }
        update_option( 'admin_hero_pro_license_status', $status );
    } else {
        update_option( 'admin_hero_pro_license_status', '' );
    }
}

// Hook license CRUD
add_action( 'update_option_admin_hero_pro_license_key', 'admin_hero_pro_handle_license_change', 10, 2 );
add_action( 'add_option_admin_hero_pro_license_key', function( $opt, $val ) {
    admin_hero_pro_handle_license_change( '', $val );
}, 10, 2 );
add_action( 'delete_option_admin_hero_pro_license_key', function() {
    admin_hero_pro_handle_license_change( get_option( 'admin_hero_pro_license_key' ), '' );
} );

// On init, if key exists but no status, trigger a check
add_action( 'init', function() {
    $key    = get_option( 'admin_hero_pro_license_key', '' );
    $status = get_option( 'admin_hero_pro_license_status', '' );
    if ( $key && ! in_array( $status, [ 'valid','expired','invalid','error' ], true ) ) {
        admin_hero_pro_handle_license_change( '', $key );
    }
}, 5 );

// LICENSE RE-CHECK ON PLUGINS_LOADED
add_action( 'plugins_loaded', function() {
    $key    = get_option( 'admin_hero_pro_license_key', '' );
    $status = get_option( 'admin_hero_pro_license_status', '' );
    if ( $key && ! in_array( $status, [ 'valid','expired','invalid','error' ], true ) ) {
        admin_hero_pro_handle_license_change( '', $key );
    }
}, 20 );

// ADD "VIEW DETAILS" LINK TO PLUGIN ROW (point to our site)
add_filter( 'plugin_row_meta', function( $links, $file ) {
    if ( $file !== ADMIN_HERO_PRO_PLUGIN_BASE ) {
        return $links;
    }
    foreach ( $links as $key => $link_html ) {
        if ( strpos( $link_html, 'plugin-information' ) !== false ) {
            $links[ $key ] = sprintf(
                '<a href="%s" target="_blank" aria-label="%s">%s</a>',
                esc_url( 'https://adminhero.pro' ),
                esc_attr__( 'Learn more about AdminHero Pro on our site' ),
                esc_html__( 'View details' )
            );
        }
    }
    return $links;
}, 10, 2 );
