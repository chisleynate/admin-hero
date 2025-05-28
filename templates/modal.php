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

            <?php
            // 1) Render ALL free‐version feature toggles (fullscreen, etc.)
            do_action( 'admin_hero_settings_ui' );
            ?>

            <?php if ( ! function_exists( 'admin_hero_pro_license_valid' ) || ! admin_hero_pro_license_valid() ) : ?>
                <!-- 2) Then the Pro CTA teaser -->
                <div class="admin-hero-pro-features">
                    <hr style="margin-top: 6px;"/>
                    <h5>Get much more with Pro!</h5>
                    <ul>
                        <li><i class="fa-solid fa-square-check"></i>24/7 Support</li>
                        <li><i class="fa-solid fa-square-check"></i>Autosave notes as you type</li>
                        <li><i class="fa-solid fa-square-check"></i>Draggable modal with memory and reset</li>
                        <li><i class="fa-solid fa-square-check"></i>Modal persistence on navigation</li>
                        <li><i class="fa-solid fa-square-check"></i>Full frontend functionality</li>
                        <li><i class="fa-solid fa-square-check"></i>Persistent floating buttons</li>
                        <li><i class="fa-solid fa-square-check"></i>Extended info panel</li>
                        <li>Much more coming! See the <a href="https://adminhero.pro" target="_blank">full features list</a>!</li>
                    </ul>
                </div>
                <a href="https://adminhero.pro" target="_blank"
                   class="admin-hero-button button-pro get-pro-button">
                   Get Pro Today!
                </a>
                <?php if ( defined( 'ADMIN_HERO_PRO_VERSION' ) ) : ?>
                    <div class="enter-license-link">
                        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=admin-hero-pro' ) ); ?>">
                            Enter License
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="ah-panel-footer ah-settings-footer">
            <button id="admin-hero-close-settings" class="ah-close-button">Close</button>
        </div>
    </div>
    <!-- ==== END SETTINGS PANEL ==== -->

    <!-- ==== INFO PANEL ==== -->
    <div id="admin-hero-info-panel" class="admin-hero-info-overlay">
        <div id="admin-hero-info-header">Info</div>
        <div class="admin-hero-info-content">
            <?php
            $tpls = glob( ADMIN_HERO_DIR . 'features/info/templates/info-panel*.php' );
            rsort( $tpls );
            $file = $tpls[0] ?? ADMIN_HERO_DIR . 'features/info/templates/info-panel.php';
            if ( file_exists( $file ) ) {
                include $file;
            }
            ?>
        </div>
        <div class="ah-panel-footer ah-info-footer">
            <button id="admin-hero-close-info" class="ah-close-button">Close</button>
        </div>
    </div>
    <!-- ==== END INFO PANEL ==== -->

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
    // ——— Initialize Quill ———
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
        // undo/redo
        document.querySelector('.ql-undo')?.addEventListener('click', () => quill.history.undo());
        document.querySelector('.ql-redo')?.addEventListener('click', () => quill.history.redo());
    }

    // ——— Hide “Info” toggle in Settings ———
    document.querySelectorAll('#admin-hero-settings-panel .admin-hero-setting-line').forEach(line => {
        const lbl = line.querySelector('label');
        if ( lbl?.textContent.trim() === 'Info' ) {
            line.remove();
        }
    });
});
</script>
