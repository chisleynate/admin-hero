<?php
/*
* Plugin Name:       WebPro Admin Notes
* Plugin URI:        https://NateChisley.com/wordpress-plugins/
* Description:       A floating, draggable notes tool for WordPress admins to jot down personal notes, autosave them, and keep them pinned right where you want them.
* Version:           1.0.0
* Requires at least: 5.0
* Requires PHP:      7.2
* Author:            Nate Chisley (WebPro)
* Author URI:        https://NateChisley.com
* License:           GPLv2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       admin-notes
* Domain Path:       /languages
*/

if (!defined('ABSPATH')) exit;

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', function() {
    $plugin_data = get_file_data(__FILE__, ['Version' => 'Version'], 'plugin');
    $version = $plugin_data['Version'];
    wp_enqueue_style('admin-notes-style', plugin_dir_url(__FILE__) . 'admin/css/style60.css', [], $version);
    wp_enqueue_script('admin-notes-script', plugin_dir_url(__FILE__) . 'admin/js/script60.js', ['jquery'], $version, true);


    $user_id = get_current_user_id();
    $modal_position = get_user_meta($user_id, 'admin_notes_modal_position', true);
    $button_position = get_user_meta($user_id, 'admin_notes_button_position', true);

    wp_localize_script('admin-notes-script', 'AdminNotes', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('admin_notes_nonce'),
        'modal_position' => $modal_position ?: null,
        'button_position' => $button_position ?: null,
    ]);
});

// Admin settings page (minimal)
add_action('admin_menu', function() {
    add_options_page(
        'Admin Notes Settings',
        'Admin Notes',
        'manage_options',
        'admin-notes-settings',
        function() {
            ?>
            <div class="wrap">
                <h1>Admin Notes</h1>
                <p>This plugin adds a draggable floating button and notes modal. All positions and content are saved per admin user.</p>
                <form method="post">
                    <?php wp_nonce_field('admin_notes_settings_reset', 'admin_notes_settings_nonce'); ?>
    <?php submit_button('Reset Positions', 'delete', 'reset_admin_notes_positions'); ?>
                </form>
            </div>
            <?php
        }
    );
});

add_action('admin_init', function() {
    if (
        isset($_POST['reset_admin_notes_positions']) &&
        current_user_can('manage_options') &&
        isset($_POST['admin_notes_settings_nonce']) &&
        wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['admin_notes_settings_nonce'])),
            'admin_notes_settings_reset'
        )        
    ) {    
        $user_id = get_current_user_id();
        delete_user_meta($user_id, 'admin_notes_modal_position');
        delete_user_meta($user_id, 'admin_notes_button_position');
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Admin Notes positions have been reset.</p></div>';
        });
    }
});

// Add button + modal to footer
add_action('admin_footer', function() {
    if (!current_user_can('manage_options')) return;

    $user_id = get_current_user_id();
    $note = get_user_meta($user_id, 'admin_notes_text', true);
    $last_saved = get_user_meta($user_id, 'admin_notes_saved_time', true);
    $user = wp_get_current_user();
    $display_name = $user->display_name;

    $timestamp_text = $last_saved
        ? 'Last saved: ' . date_i18n('F j, Y \a\t g:i A', strtotime($last_saved))
        : 'Last saved: never';
    ?>
    <div id="admin-notes-button" class="hidden">📝</div>

    <div id="admin-notes-modal" class="hidden">
        <div id="admin-notes-overlay" class="hidden">
            <span>NOTES SAVED</span>
        </div>
        <div id="admin-notes-gear" title="Settings">⚙️</div>
        <div id="admin-notes-close" title="Close">❌</div>
        <div id="admin-notes-header">📝 <?php echo esc_html($display_name); ?>'s Notes</div>

        <textarea id="admin-notes-text"><?php echo esc_textarea($note); ?></textarea>
        <button id="admin-notes-save">SAVE</button>
        <div id="admin-notes-timestamp"><?php echo esc_html($timestamp_text); ?></div>

        <div id="admin-notes-settings" class="hidden">
            <h3>Settings</h3>
            <p>Use the button below to reset the modal and button positions.</p>
            <button id="admin-notes-reset">Reset Positions</button>
            <br>
            <button id="admin-notes-back">← Back to Notes</button>
        </div>
    </div>
    <?php
});

// Save note + timestamp
add_action('wp_ajax_save_admin_note', function() {
    check_ajax_referer('admin_notes_nonce', 'nonce');
    $user_id = get_current_user_id();
    if (!current_user_can('manage_options')) wp_send_json_error();

    $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
    update_user_meta($user_id, 'admin_notes_text', $note);
    update_user_meta($user_id, 'admin_notes_saved_time', current_time('mysql'));

    wp_send_json_success(['message' => 'Note saved.']);
});

// Save UI positions
add_action('wp_ajax_save_admin_notes_position', function() {
    check_ajax_referer('admin_notes_nonce', 'nonce');
    $user_id = get_current_user_id();
    if (!current_user_can('manage_options')) wp_send_json_error();

    $target = isset($_POST['target']) ? sanitize_text_field(wp_unslash($_POST['target'])) : '';
    $position = [
        'top' => isset($_POST['top']) ? sanitize_text_field(wp_unslash($_POST['top'])) : 'auto',
        'left' => isset($_POST['left']) ? sanitize_text_field(wp_unslash($_POST['left'])) : 'auto',
    ];

    if ($target === 'modal') {
        update_user_meta($user_id, 'admin_notes_modal_position', $position);
    } elseif ($target === 'button') {
        update_user_meta($user_id, 'admin_notes_button_position', $position);
    }

    wp_send_json_success();
});

// AJAX: Reset positions from modal
add_action('wp_ajax_reset_admin_notes_positions', function() {
    check_ajax_referer('admin_notes_nonce', 'nonce');
    $user_id = get_current_user_id();

    delete_user_meta($user_id, 'admin_notes_modal_position');
    delete_user_meta($user_id, 'admin_notes_button_position');

    wp_send_json_success(['message' => 'Positions reset']);
});
