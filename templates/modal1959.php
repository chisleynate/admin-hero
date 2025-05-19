<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="admin-hero-modal" class="admin-hero-hidden">
    <div id="admin-hero-overlay">
        <i class="fa-solid fa-floppy-disk save-icon"></i>
        <span>Notes Saved</span>
    </div>

    <div id="admin-hero-header">
        <div class="admin-hero-settings-toggle admin-hero-settings-icon" id="admin-hero-settings-toggle" title="Settings"><i class="fas fa-gear"></i></div>
        <div class="admin-hero-info-toggle admin-hero-info-icon" id="admin-hero-info-toggle" title="Client Info"><i class="fas fa-info-circle"></i></div>
        <div id="admin-hero-settings-icons"></div>
        <?php do_action( 'admin_hero_modal_header_buttons' ); ?>
        <div class="admin-hero-close-btn" title="Close"><i class="fas fa-times"></i></div>
    </div>

    <div id="admin-hero-settings-panel" class="admin-hero-settings-overlay">
        <div id="admin-hero-settings-header">Settings</div>
        <div class="admin-hero-settings-content">
            <?php do_action( 'admin_hero_settings_ui' ); ?>

            <?php
            // Show Pro‑features teaser if EITHER Pro isn't installed OR license isn't valid
            if ( ! function_exists( 'admin_hero_pro_license_valid' ) || ! admin_hero_pro_license_valid() ) : ?>
                <div class="admin-hero-pro-features">
                    <h4>Pro Features</h4>
                    <ul>
                        <li><i class="fa-solid fa-square-check"></i>24/7 Support</li>
                        <li><i class="fa-solid fa-square-check"></i>Fullscreen Mode</li>
                        <li><i class="fa-solid fa-square-check"></i>Autosave Notes (live save as you type)</li>
                        <li><i class="fa-solid fa-square-check"></i>Draggable Modal with Memory and Reset</li>
                        <li><i class="fa-solid fa-square-check"></i>Modal Persistence on navigation</li>
                        <li>and more coming soon...</li>
                    </ul>
                </div>
                <a href="https://adminhero.pro" target="_blank"
                   class="admin-hero-button button-pro get-pro-button">Get Pro</a>
                <?php if ( defined( 'ADMIN_HERO_PRO_VERSION' ) ) : ?>
                    <div class="enter-license-link">
                        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=admin-hero-pro' ) ); ?>">
                            Enter License
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <button id="admin-hero-close-settings" class="admin-hero-button">Close</button>
        </div>
    </div>

    <div id="admin-hero-info-panel" class="admin-hero-info-overlay">
        <div id="admin-hero-info-header">Client Info</div>
        <div class="admin-hero-info-content">
            <?php
            // Same teaser in the info panel
            if ( ! function_exists( 'admin_hero_pro_license_valid' ) || ! admin_hero_pro_license_valid() ) : ?>
                <div class="admin-hero-pro-features">
                    <h4>Coming Soon!</h4>
                    <p>No more hunting through emails or spreadsheets. Everything you need to stay on point is just one click away!</p>
                </div>
                <a href="https://adminhero.pro" target="_blank"
                   class="admin-hero-button button-pro get-pro-button">Get Pro</a>
                <?php if ( defined( 'ADMIN_HERO_PRO_VERSION' ) ) : ?>
                    <div class="enter-license-link">
                        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=admin-hero-pro' ) ); ?>">
                            Enter License
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <button id="admin-hero-close-info" class="admin-hero-button">Close</button>
        </div>
    </div>

    <div id="admin-hero-editor" style="height: 200px;"></div>
    <input type="hidden" id="admin-hero-nonce" value="<?php echo esc_attr( $nonce ); ?>">
    <button id="admin-hero-save" class="admin-hero-button"><i class="fa-solid fa-floppy-disk"></i> Save</button>

    <div class="admin-hero-footer">
        <div class="admin-hero-title"><?php echo esc_html( $user->display_name ); ?></div>
        <div id="admin-hero-timestamp"><?php echo esc_html( $timestamp ); ?></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const noteContent = <?php echo json_encode( $note ); ?>;
    if (typeof Quill !== 'undefined') {
        const quill = new Quill('#admin-hero-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{'header': [1, 2, 3, 4, false]}],
                    ['bold', 'italic', 'underline'],
                    [{'list': 'ordered'}, {'list': 'bullet'}],
                    ['link'],
                    [{'undo': 'undo'}, {'redo': 'redo'}]
                ],
                history: {
                    delay: 1000,
                    maxStack: 100,
                    userOnly: true
                }
            }
        });
        quill.root.innerHTML = noteContent || '';
        window.quillEditor = quill;

        const undoButton = document.querySelector('.ql-undo');
        const redoButton = document.querySelector('.ql-redo');
        if (undoButton) {
            undoButton.addEventListener('click', () => quill.history.undo());
        }
        if (redoButton) {
            redoButton.addEventListener('click', () => quill.history.redo());
        }
    } else {
        console.error('Quill editor not loaded');
    }
});
</script>
