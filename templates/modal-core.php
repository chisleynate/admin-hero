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

    <!-- ==== HEADER ==== -->
    <div id="admin-hero-header">
        <div id="admin-hero-settings-icons"></div>
        <?php do_action( 'admin_hero_modal_header_buttons' ); ?>

        <!-- ==== SETTINGS ICON ==== -->
        <!-- onmousedown="event.stopPropagation()" prevents the drag handler from running when you click here -->
        <div
            class="admin-hero-settings-toggle admin-hero-settings-icon"
            id="admin-hero-settings-toggle"
            title="Settings"
            onmousedown="event.stopPropagation()"
        >
            <i class="fas fa-gear"></i>
        </div>

        <!-- ==== INFO ICON ==== -->
        <div
            class="admin-hero-info-toggle admin-hero-info-icon"
            id="admin-hero-info-toggle"
            title="Info"
            onmousedown="event.stopPropagation()"
        >
            <i class="fas fa-info-circle"></i>
        </div>

        <!-- ==== TASKS ICON ==== -->
        <div
            class="admin-hero-tasks-toggle admin-hero-tasks-icon"
            id="admin-hero-tasks-toggle"
            title="Tasks"
            onmousedown="event.stopPropagation()"
        >
            <i class="fas fa-tasks"></i>
        </div>

        <!-- ==== CLOSE ICON (closes the entire modal) ==== -->
        <div class="admin-hero-close-btn" title="Close">
            <i class="fas fa-times"></i>
        </div>
    </div>
    <!-- ==== END HEADER ==== -->

    <!-- ==== SETTINGS PANEL ==== -->
    <?php
    // Determine free vs. pro styling for the settings panel
    $extra_class = 'settings-free';
    if (
        defined( 'ADMIN_HERO_PRO_VERSION' ) &&
        function_exists( 'admin_hero_pro_license_valid' ) &&
        admin_hero_pro_license_valid()
    ) {
        $extra_class = 'settings-pro';
    }
    ?>
    <div
        id="admin-hero-settings-panel"
        class="admin-hero-settings-overlay <?php echo esc_attr( $extra_class ); ?>"
        style="display: none;"
    >
        <div id="admin-hero-settings-header">Settings</div>
        <div class="admin-hero-settings-content">
            <?php
            echo '<fieldset class="admin-hero-settings-fieldset">';
            echo '<legend>Global</legend>';
                do_action( 'admin_hero_settings_ui' );
            echo '</fieldset>';
            ?>

            <?php if ( ! function_exists( 'admin_hero_pro_license_valid' ) || ! admin_hero_pro_license_valid() ) : ?>
                <!-- Pro teaser (unchanged) -->
                <div class="admin-hero-pro-features">
                    <hr style="margin-top: 6px;"/>
                    <h2>Get much more with Pro!</h2>
                    <ul>
                        <li><i class="fa-solid fa-square-check"></i>24/7 Support</li>
                        <li><i class="fa-solid fa-square-check"></i>Autosave notes as you type</li>
                        <li><i class="fa-solid fa-square-check"></i>Draggable modal with memory and reset</li>
                        <li><i class="fa-solid fa-square-check"></i>Modal persistence on navigation</li>
                        <li><i class="fa-solid fa-square-check"></i>Full frontend functionality</li>
                        <li><i class="fa-solid fa-square-check"></i>Persistent floating buttons</li>
                        <li><i class="fa-solid fa-square-check"></i>Extended info panel</li>
                        <li>
                          Much more coming! See the 
                          <a href="https://adminhero.pro" target="_blank">full features list</a>!
                        </li>
                    </ul>
                </div>
                <a
                    href="https://adminhero.pro"
                    target="_blank"
                    class="admin-hero-button button-pro get-pro-button"
                >
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
            <button id="admin-hero-close-settings" class="ah-close-button">
                Close
            </button>
        </div>
    </div>
    <!-- ==== END SETTINGS PANEL ==== -->

    <!-- ==== INFO PANEL ==== -->
    <div
        id="admin-hero-info-panel"
        class="admin-hero-info-overlay"
        style="display: none;"
    >
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
            <button id="admin-hero-close-info" class="ah-close-button">
                Close
            </button>
        </div>
    </div>
    <!-- ==== END INFO PANEL ==== -->

    <!-- ==== TASKS PANEL ==== -->
    <div
        id="admin-hero-tasks-panel"
        class="admin-hero-tasks-overlay"
        style="display: none;"
    >
        <?php
        $tpls = glob( ADMIN_HERO_DIR . 'features/tasks/templates/tasks-panel*.php' );
        rsort( $tpls );
        $file = $tpls[0] ?? ( ADMIN_HERO_DIR . 'features/tasks/templates/tasks-panel.php' );
        if ( file_exists( $file ) ) {
            include $file;
        }
        ?>
    </div>
    <!-- ==== END TASKS PANEL ==== -->

    <!-- The notes editor placeholder, hidden nonce, Save button, and footer (unchanged) -->
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
    // ——— QUILL INIT (UNCHANGED) ———
    const noteContent = <?php echo json_encode($note); ?>;
    if (typeof Quill !== 'undefined') {
        const quill = new Quill('#admin-hero-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, 4, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    [{ undo: 'undo' }, { redo: 'redo' }]
                ],
                history: { delay: 1000, maxStack: 100, userOnly: true }
            }
        });
        quill.root.innerHTML = noteContent || '';
        window.quillEditor = quill;
        document.querySelector('.ql-undo')?.addEventListener('click', () => quill.history.undo());
        document.querySelector('.ql-redo')?.addEventListener('click', () => quill.history.redo());
    }

    // ——— GRAB BUTTONS & PANELS ———
    const settingsBtn = document.getElementById('admin-hero-settings-toggle');
    const infoBtn = document.getElementById('admin-hero-info-toggle');
    const tasksBtn = document.getElementById('admin-hero-tasks-toggle');
    const settingsPanel = document.getElementById('admin-hero-settings-panel');
    const infoPanel = document.getElementById('admin-hero-info-panel');
    const tasksPanel = document.getElementById('admin-hero-tasks-panel');

    // Debug: Verify elements exist
    console.log('Settings button:', settingsBtn, 'Panel:', settingsPanel);
    console.log('Info button:', infoBtn, 'Panel:', infoPanel);
    console.log('Tasks button:', tasksBtn, 'Panel:', tasksPanel);

    // ——— UTILS ———
    function isVisible(panel) {
        if (!panel) {
            console.error('Panel is null');
            return false;
        }
        const display = window.getComputedStyle(panel).display;
        console.log('Checking visibility for', panel.id, ': display =', display);
        return display !== 'none';
    }

    function show(panel) {
        if (panel) {
            console.log('Showing panel:', panel.id);
            panel.style.display = 'flex';
        }
    }

    function hide(panel) {
        if (panel) {
            console.log('Hiding panel:', panel.id);
            panel.style.display = 'none';
        }
    }

    // ——— TOGGLE FUNCTION ———
    function togglePanel(key) {
        let targetPanel;
        if (key === 'settings') targetPanel = settingsPanel;
        if (key === 'info') targetPanel = infoPanel;
        if (key === 'tasks') targetPanel = tasksPanel;

        if (!targetPanel) {
            console.error('No panel found for key:', key);
            return;
        }

        console.log('Button clicked:', key);

        if (isVisible(targetPanel)) {
            // Panel is open → close it
            console.log('Panel was open, now closing:', targetPanel.id);
            hide(targetPanel);
        } else {
            // Panel is closed → close any other open panels, then open this one
            console.log('Panel was closed, closing others and opening:', targetPanel.id);
            [settingsPanel, infoPanel, tasksPanel].forEach(p => {
                if (p && p !== targetPanel && isVisible(p)) {
                    hide(p);
                }
            });
            show(targetPanel);
        }
    }

    // ——— ATTACH CLICK HANDLERS ———
    settingsBtn?.addEventListener('click', () => {
        console.log('Settings button clicked');
        togglePanel('settings');
    });
    infoBtn?.addEventListener('click', () => {
        console.log('Info button clicked');
        togglePanel('info');
    });
    tasksBtn?.addEventListener('click', () => {
        console.log('Tasks button clicked');
        togglePanel('tasks');
    });

    // ——— CLOSE BUTTONS WITHIN PANELS ———
    document.getElementById('admin-hero-close-settings')?.addEventListener('click', () => {
        console.log('Close button clicked: settingsPanel');
        hide(settingsPanel);
    });
    document.getElementById('admin-hero-close-info')?.addEventListener('click', () => {
        console.log('Close button clicked: infoPanel');
        hide(infoPanel);
    });
    document.getElementById('admin-hero-close-tasks')?.addEventListener('click', () => {
        console.log('Close button clicked: tasksPanel');
        hide(tasksPanel);
    });

    // ——— REMOVE “INFO” & “FULLSCREEN MODE” FROM SETTINGS UI ———
    function removeSetting(labelText) {
        document.querySelectorAll('#admin-hero-settings-panel .admin-hero-setting-line').forEach(line => {
            const lbl = line.querySelector('label');
            if (lbl && lbl.textContent.trim() === labelText) {
                console.log('Removing setting line:', labelText);
                line.remove();
            }
        });
    }
    settingsBtn?.addEventListener('click', function() {
        setTimeout(function() {
            removeSetting('Info');
            removeSetting('Fullscreen Mode');
        }, 50);
    });
});
</script>