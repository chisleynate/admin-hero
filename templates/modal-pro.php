<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="admin-hero-modal" class="admin-hero-hidden">
    <div id="admin-hero-overlay">
        <i class="fa-solid fa-floppy-disk save-icon"></i>
        <span>Notes Saved</span>
    </div>
    <div id="admin-hero-overlay-info" class="admin-hero-overlay">
        <i class="fa-solid fa-floppy-disk save-icon"></i>
        <span>Info Saved</span>
    </div>

    <div id="admin-hero-header">
        <div class="admin-hero-settings-toggle admin-hero-settings-icon"
             id="admin-hero-settings-toggle" title="Settings">
            <i class="fas fa-gear"></i>
        </div>
        <div class="admin-hero-info-toggle admin-hero-info-icon"
             id="admin-hero-info-toggle" title="Info">
            <i class="fas fa-info-circle"></i>
        </div>
        <div id="admin-hero-settings-icons"></div>
        <?php do_action( 'admin_hero_modal_header_buttons' ); ?>
        <div class="admin-hero-close-btn" title="Close">
            <i class="fas fa-times"></i>
        </div>
    </div>

    <!-- ==== SETTINGS PANEL ==== -->
    <div id="admin-hero-settings-panel" class="admin-hero-settings-overlay">
        <div id="admin-hero-settings-header">Settings</div>
        <div class="admin-hero-settings-content">
            <?php do_action( 'admin_hero_settings_ui' ); ?>

            <?php if ( ! function_exists( 'admin_hero_pro_license_valid' ) || ! admin_hero_pro_license_valid() ) : ?>
                <!-- Pro teaser here… -->
            <?php endif; ?>
        </div>
        <div class="ah-panel-footer ah-settings-footer">
            <button id="admin-hero-close-settings" class="ah-close-button">Close</button>
        </div>
    </div>
    <!-- ==== END SETTINGS PANEL ==== -->

    <!-- ==== CLIENT INFO PANEL ==== -->
    <div id="admin-hero-info-panel" class="admin-hero-info-overlay">
        <div id="admin-hero-info-header">Info</div>
        <div class="admin-hero-info-content">
            <?php
            // — Free Info panel —
            $tpls = glob( ADMIN_HERO_DIR . 'features/info/templates/info-panel*.php' );
            rsort( $tpls );
            $file = $tpls[0] 
                  ?? ( ADMIN_HERO_DIR . 'features/info/templates/info-panel.php' );
            if ( file_exists( $file ) ) {
                include $file;
            }

            // — Pro “Hosting & Server” block —
            $pro_tpls = glob( ADMIN_HERO_PRO_DIR . 'features/info-pro/templates/info-panel-pro*.php' );
            rsort( $pro_tpls );
            $pro_file = $pro_tpls[0]
                      ?? ( ADMIN_HERO_PRO_DIR . 'features/info-pro/templates/info-panel-pro.php' );
            if ( file_exists( $pro_file ) ) {
                include $pro_file;
            }
            ?>
        </div>
        <div class="ah-panel-footer ah-info-footer">
            <button id="admin-hero-close-info" class="ah-close-button">Close</button>
        </div>
    </div>
    <!-- ==== END CLIENT INFO PANEL ==== -->

    <div id="admin-hero-editor" style="height: 200px;"></div>
    <input type="hidden" id="admin-hero-nonce" value="<?php echo esc_attr( $nonce ); ?>">
    <button id="admin-hero-save" class="admin-hero-button">
        <i class="fa-solid fa-floppy-disk"></i> Save
    </button>

    <div class="admin-hero-footer">
        <div class="admin-hero-title"><?php echo esc_html( $user->display_name ); ?></div>
        <div id="admin-hero-timestamp"><?php echo esc_html( $timestamp ); ?></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Quill (same as free)
    const noteContent = <?php echo json_encode( $note ); ?>;
    if ( typeof Quill !== 'undefined' ) {
        const quill = new Quill('#admin-hero-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1,2,3,4,false] }],
                    ['bold','italic','underline'],
                    [{ list: 'ordered' },{ list: 'bullet' }],
                    ['link'],
                    [{ undo:'undo' },{ redo:'redo' }]
                ],
                history: { delay:1000, maxStack:100, userOnly:true }
            }
        });
        quill.root.innerHTML = noteContent || '';
        window.quillEditor = quill;
        document.querySelector('.ql-undo')?.addEventListener('click', () => quill.history.undo());
        document.querySelector('.ql-redo')?.addEventListener('click', () => quill.history.redo());
    }

    // helper to remove a setting by its label
    function removeSetting(labelText) {
        document.querySelectorAll('#admin-hero-settings-panel .admin-hero-setting-line').forEach(line => {
            const lbl = line.querySelector('label');
            if ( lbl && lbl.textContent.trim() === labelText ) {
                line.remove();
            }
        });
    }

    // hide Info & Fullscreen when panel opens
    const settingsBtn = document.getElementById('admin-hero-settings-toggle');
    settingsBtn.addEventListener('click', () => {
        // slight delay so the generic UI has time to render
        setTimeout(() => {
            removeSetting('Info');
            removeSetting('Fullscreen Mode');
        }, 50);
    });

    // Close Info panel
    document.getElementById('admin-hero-close-info')
        .addEventListener('click', () => {
            document.getElementById('admin-hero-info-panel').style.display = 'none';
        });
});
</script>
