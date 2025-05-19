<?php
/*
 * Plugin Name:       Admin Hero
 * Plugin URI:        https://NateChisley.com/wordpress-plugins/
 * Description:       A button in the WordPress admin bar that opens a modal for taking notes.
 * Version:           1.0.13
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Nate Chisley
 * Author URI:        https://NateChisley.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       admin-hero
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// TIMESTAMP FORMAT
// -----------------------------------------------------------------------------
if ( ! defined( 'ADMIN_HERO_TIMESTAMP_FORMAT' ) ) {
    define( 'ADMIN_HERO_TIMESTAMP_FORMAT', 'F j, Y \\a\\t g:i A' );
}

// -----------------------------------------------------------------------------
// PLUGIN VERSION, PATHS & URL
// -----------------------------------------------------------------------------
$plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version' ] );
define( 'ADMIN_HERO_VERSION', $plugin_data['Version'] );
define( 'ADMIN_HERO_DIR',     plugin_dir_path( __FILE__ ) );
define( 'ADMIN_HERO_URL',     plugin_dir_url( __FILE__ ) );

// -----------------------------------------------------------------------------
// LOAD FEATURE LOADER
// -----------------------------------------------------------------------------
$loader_files = glob( ADMIN_HERO_DIR . 'includes/class-feature-loader*.php' );
if ( ! empty( $loader_files ) ) {
    rsort( $loader_files );
    require_once $loader_files[0];
} elseif ( file_exists( ADMIN_HERO_DIR . 'includes/class-feature-loader.php' ) ) {
    require_once ADMIN_HERO_DIR . 'includes/class-feature-loader.php';
} else {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Admin Hero</strong> error: feature-loader file is missing.</p></div>';
    } );
    return;
}

class Admin_Hero {

    /** @var Admin_Hero_Feature_Loader */
    private $feature_loader;

    public function __construct() {
        $this->feature_loader = new Admin_Hero_Feature_Loader();
        $this->setup_hooks();
    }

    private function setup_hooks() {
        // 1) Load all features (free + pro)
        add_action( 'plugins_loaded', [ $this, 'load_features' ] );

        // 2) Enqueue & render modal in Dashboard **and** front‐end (when allowed)
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_enqueue_scripts',     [ $this, 'enqueue_assets' ] );
        add_action( 'admin_footer',           [ $this, 'render_modal' ] );
        add_action( 'wp_footer',              [ $this, 'render_modal' ] );

        // 3) AJAX handlers (work anywhere)
        add_action( 'wp_ajax_admin_hero_save_note',     [ $this, 'save_note' ] );
        add_action( 'wp_ajax_admin_hero_refresh_nonce', [ $this, 'refresh_nonce' ] );
        add_action( 'wp_ajax_admin_hero_save_settings', [ $this, 'save_settings' ] );
    }

    public function load_features() {
        $this->feature_loader->load_features( ADMIN_HERO_DIR . 'features/' );
    }

    public function enqueue_assets() {
        // only editors
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        // detect admin vs front-end + “frontend” toggle
        $is_admin = is_admin();
        $frontend_feature = $this->feature_loader->get_feature( 'frontend' );
        $is_frontend      = ! $is_admin
                        && $frontend_feature
                        && ! empty( $frontend_feature['enabled'] );

        // bail if neither
        if ( ! ( $is_admin || $is_frontend ) ) {
            return;
        }

        // --- core CSS/JS + FA + Quill ---
        $css_files = glob( ADMIN_HERO_DIR . 'assets/css/admin-hero*.css' );
        $css_file  = ! empty( $css_files ) ? basename( $css_files[0] ) : 'admin-hero.css';
        wp_enqueue_style( 'admin-hero-css', ADMIN_HERO_URL . 'assets/css/' . $css_file, [], ADMIN_HERO_VERSION );

        $js_files = glob( ADMIN_HERO_DIR . 'assets/js/admin-hero*.js' );
        $js_file  = ! empty( $js_files ) ? basename( $js_files[0] ) : 'admin-hero.js';
        wp_enqueue_script( 'admin-hero-js', ADMIN_HERO_URL . 'assets/js/' . $js_file, [ 'jquery' ], ADMIN_HERO_VERSION, true );

        wp_enqueue_style( 'font-awesome', ADMIN_HERO_URL . 'assets/fontawesome/css/all.min.css', [], '6.7.2' );
        wp_enqueue_style( 'quill-css', ADMIN_HERO_URL . 'assets/quilleditor/quill.snow.css', [], '2.0.3' );
        wp_enqueue_script( 'quill-js', ADMIN_HERO_URL . 'assets/quilleditor/quill.min.js', [], '2.0.3', true );

        wp_localize_script( 'admin-hero-js', 'AdminHero', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'admin_hero_action' ),
            'version'  => ADMIN_HERO_VERSION,
            'is_admin' => $is_admin,
            'features' => $this->feature_loader->get_features(),
        ] );

        // ──────────────────────────────────────────────────────────────────────────
        // **new**: now also fire every feature’s own enqueue_callback() so
        // persistence.css, frontend.css, autosave.css (etc) get loaded
        // even on the front-end when enabled.
        // ──────────────────────────────────────────────────────────────────────────
        foreach ( $this->feature_loader->get_features() as $feature ) {
            if ( ! empty( $feature['enabled'] )
            && ! empty( $feature['enqueue_callback'] )
            && is_callable( $feature['enqueue_callback'] ) ) {
                call_user_func( $feature['enqueue_callback'] );
            }
        }
    }

    public function render_modal() {
        // Only for editors
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        // Determine context
        $is_admin = is_admin();
        $frontend_feature = $this->feature_loader->get_feature( 'frontend' );
        $is_frontend      = ! $is_admin
                          && $frontend_feature
                          && ! empty( $frontend_feature['enabled'] );

        // Bail if neither admin nor allowed front‐end
        if ( ! ( $is_admin || $is_frontend ) ) {
            return;
        }

        $user       = wp_get_current_user();
        $note       = get_user_meta( $user->ID, 'admin_hero_text', true );
        $last_saved = get_user_meta( $user->ID, 'admin_hero_last_saved', true );
        $timestamp  = 'Last saved: ' . (
            $last_saved
                ? date_i18n( ADMIN_HERO_TIMESTAMP_FORMAT, strtotime( $last_saved ) )
                : 'Never saved'
        );
        $nonce      = wp_create_nonce( 'admin_hero_action' );

        // Allow features (including Pro frontend) to hook before
        do_action( 'admin_hero_render_modal_before', $user, $note, $timestamp, $nonce );

        // Pick template (Free or Pro override)
        $modal_files = glob( ADMIN_HERO_DIR . 'templates/modal*.php' );
        $modal_file  = ! empty( $modal_files ) ? $modal_files[0] : ADMIN_HERO_DIR . 'templates/modal.php';
        $modal_file  = apply_filters( 'admin_hero_modal_template', $modal_file );

        if ( file_exists( $modal_file ) ) {
            include $modal_file;
        }

        // After hook
        do_action( 'admin_hero_render_modal_after' );
    }

    public function save_note() {
        check_ajax_referer( 'admin_hero_action', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ] );
        }

        $user_id = get_current_user_id();
        $note    = wp_kses_post( wp_unslash( $_POST['note'] ?? '' ) );
        update_user_meta( $user_id, 'admin_hero_text',       $note );
        update_user_meta( $user_id, 'admin_hero_last_saved', current_time( 'mysql' ) );

        wp_send_json_success( [
            'message'   => 'Note saved',
            'timestamp' => date_i18n( ADMIN_HERO_TIMESTAMP_FORMAT, current_time( 'timestamp' ) ),
        ] );
    }

    public function refresh_nonce() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ] );
        }
        wp_send_json_success( [ 'nonce' => wp_create_nonce( 'admin_hero_action' ) ] );
    }

    public function save_settings() {
        check_ajax_referer( 'admin_hero_action', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ] );
        }

        $raw_settings = filter_input( INPUT_POST, 'settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
        $raw_settings = is_array( $raw_settings ) ? wp_unslash( $raw_settings ) : [];

        $settings = [];
        foreach ( $raw_settings as $feature_id => $value ) {
            $feature_id = sanitize_key( $feature_id );
            if ( is_array( $value ) ) {
                $value = array_map( 'sanitize_text_field', $value );
            } else {
                $value = sanitize_text_field( $value );
            }
            $settings[ $feature_id ] = $value;
        }

        $user_id = get_current_user_id();
        foreach ( $settings as $feature_id => $value ) {
            $feature = $this->feature_loader->get_feature( $feature_id );
            if ( $feature && isset( $feature['sanitize_callback'] ) ) {
                $value = call_user_func( $feature['sanitize_callback'], $value );
            }
            update_user_meta( $user_id, "admin_hero_feature_{$feature_id}", $value );
        }

        wp_send_json_success( [ 'message' => 'Settings saved' ] );
    }
}

new Admin_Hero();
