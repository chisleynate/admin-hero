// assets/js/admin-hero.js
;(function () {
    console.log(
        'AdminHero: Script initializing, version ' +
            (window.AdminHero?.version || 'unknown')
    );

    const AdminHero = {
        init() {
            this.cacheElements();
            this.bindEvents();
            this.initFeatures();
            if (this.is_admin && this.is_pro) this.initPro();
        },

        cacheElements() {
            this.modal            = document.getElementById('admin-hero-modal');
            this.closeBtn         = document.querySelector('.admin-hero-close-btn');
            this.saveButton       = document.getElementById('admin-hero-save');
            this.timestamp        = document.getElementById('admin-hero-timestamp');
            this.overlay          = document.getElementById('admin-hero-overlay');
            this.nonceInput       = document.getElementById('admin-hero-nonce');
            this.settingsToggle   = document.getElementById('admin-hero-settings-toggle');
            this.settingsPanel    = document.getElementById('admin-hero-settings-panel');
            this.infoToggle       = document.getElementById('admin-hero-info-toggle');
            this.infoPanel        = document.getElementById('admin-hero-info-panel');
        },

        bindEvents() {
            // ─────── admin-bar button ───────
            const targetAdminBar =
                document.getElementById('wp-admin-bar-top-secondary') ||
                document.getElementById('wp-admin-bar-root-default');

            if (targetAdminBar) {
                const li = document.createElement('li');
                li.id = 'wp-admin-bar-admin-hero';

                const a = document.createElement('a');
                a.href  = '#';
                a.title = 'AdminHero';

                // choose free vs pro logo
                const logoSrc = this.is_pro && this.logo_pro_url
                    ? this.logo_pro_url
                    : this.logo_url;

                a.innerHTML = `<img src="${logoSrc}" class="admin-hero-bar-logo" alt="AdminHero">`;
                a.addEventListener('click', e => this.toggleModal(e));

                li.appendChild(a);
                targetAdminBar.appendChild(li);
            }

            // ─────── modal controls ───────
            this.closeBtn?.addEventListener('click', e => this.toggleModal(e));

            this.saveButton?.addEventListener('click', e => {
                e.preventDefault();
                const content = window.quillEditor?.root.innerHTML || '';
                this.showOverlay();
                this.apiCall(
                    'admin_hero_save_note',
                    { nonce: this.nonceInput.value || this.nonce, note: content }
                ).then(json => {
                    // update timestamp
                    if (this.timestamp) {
                        this.timestamp.textContent = 'Last saved: ' + json.data.timestamp;
                    }
                    document.dispatchEvent(new CustomEvent('admin-hero-saved', {
                        detail: { timestamp: json.data.timestamp }
                    }));
                    // hide overlay after a little pause
                    setTimeout(() => {
                        this.overlay.classList.remove('admin-hero-visible');
                        setTimeout(() => this.overlay.style.display = 'none', 200);
                    }, 1000);
                });
            });

            this.settingsToggle?.addEventListener('click', () =>
                this.togglePanel(this.settingsPanel)
            );
            this.infoToggle?.addEventListener('click', () =>
                this.togglePanel(this.infoPanel)
            );

            this.settingsPanel
                ?.querySelector('#admin-hero-close-settings')
                ?.addEventListener('click', () => this.settingsPanel.style.display = 'none');

            this.infoPanel
                ?.querySelector('#admin-hero-close-info')
                ?.addEventListener('click', () => this.infoPanel.style.display = 'none');
        },

        apiCall(action, payload, retries = 2) {
            const data = new FormData();
            data.append('action', action);
            Object.entries(payload).forEach(([key, val]) => data.append(key, val));

            return new Promise((resolve, reject) => {
                const attempt = n => {
                    fetch(this.ajax_url, { method: 'POST', body: data })
                        .then(res => res.json())
                        .then(json => {
                            if (json.success) {
                                document.dispatchEvent(new CustomEvent(
                                    'admin-hero-request-success',
                                    { detail: { action, data: json.data } }
                                ));
                                resolve(json);
                            } else {
                                throw new Error(json.data?.message || 'Unknown error');
                            }
                        })
                        .catch(err => {
                            if (n < retries) return attempt(n + 1);
                            console.error(`Action ${action} failed`, err);
                            document.dispatchEvent(new CustomEvent(
                                'admin-hero-request-error',
                                { detail: { action, error: err } }
                            ));
                            reject(err);
                        });
                };
                attempt(1);
            });
        },

        showOverlay() {
            if (!this.overlay) return;
            this.overlay.innerHTML =
                '<i class="fa-solid fa-floppy-disk save-icon"></i><span>Notes Saved</span>';
            this.overlay.style.display = 'flex';
            void this.overlay.offsetWidth;
            this.overlay.classList.add('admin-hero-visible');
            document.dispatchEvent(new CustomEvent('admin-hero-overlay-shown'));
        },

        toggleModal(e) {
            e.preventDefault();
            if (!this.modal) this.cacheElements();
            const hidden = this.modal.classList.toggle('admin-hero-hidden');
            this.modal.style.display = hidden ? 'none' : 'flex';
        },

        togglePanel(panel) {
            const isOpen = panel.style.display === 'flex';
            this.settingsPanel.style.display = 'none';
            this.infoPanel.style.display     = 'none';
            panel.style.display = isOpen ? 'none' : 'flex';
        },

        initFeatures() {
            const container = this.settingsPanel?.querySelector('.admin-hero-settings-content');
            if (!container || !Array.isArray(this.features)) return;

            // Pick insertion point:
            // 1) before the Pro-features block
            // 2) else before the Get Pro button
            // 3) else before the Close button
            const refElement =
                container.querySelector('.admin-hero-pro-features')
                || container.querySelector('.get-pro-button')
                || container.querySelector('#admin-hero-close-settings');

            this.features.forEach(feature => {
                const line = document.createElement('div');
                line.className = 'admin-hero-setting-line';

                const label = document.createElement('label');
                label.className   = 'admin-hero-toggle-text';
                label.textContent = feature.name;

                const wrap = document.createElement('label');
                wrap.className = 'switch';

                const input = document.createElement('input');
                input.type    = 'checkbox';
                input.checked = feature.enabled;
                input.addEventListener('change', () => {
                    const settingKey = `settings[${feature.id}]`;
                    const settingVal = input.checked ? '1' : '0';
                    this.apiCall(
                        'admin_hero_save_settings',
                        { nonce: this.nonceInput.value || this.nonce, [settingKey]: settingVal }
                    ).then(() => {
                        document.dispatchEvent(new CustomEvent('admin-hero-feature-toggle', {
                            detail: { featureId: feature.id, enabled: input.checked }
                        }));
                    });
                });

                const slider = document.createElement('span');
                slider.className = 'slider';

                wrap.append(input, slider);
                line.append(label, wrap);

                container.insertBefore(line, refElement);
            });
        },

        initPro() {
            if (!this.proStyleUrl || !this.proScriptUrl) return;
            const link = document.createElement('link');
            link.rel  = 'stylesheet';
            link.href = this.proStyleUrl;
            document.head.appendChild(link);

            const script = document.createElement('script');
            script.src     = this.proScriptUrl;
            script.onload = () => document.dispatchEvent(new CustomEvent('admin-hero-pro-loaded'));
            document.body.appendChild(script);
        }
    };

    // inherit globals passed in by PHP
    if (window.AdminHero) {
        AdminHero.ajax_url      = window.AdminHero.ajax_url;
        AdminHero.nonce         = window.AdminHero.nonce;
        AdminHero.version       = window.AdminHero.version;
        AdminHero.is_admin      = window.AdminHero.is_admin;
        AdminHero.is_pro        = window.AdminHero.is_pro;
        AdminHero.features      = window.AdminHero.features;
        AdminHero.proStyleUrl   = window.AdminHero.proStyleUrl;
        AdminHero.proScriptUrl  = window.AdminHero.proScriptUrl;
        AdminHero.logo_url      = window.AdminHero.logo_url;
        AdminHero.logo_pro_url  = window.AdminHero.logo_pro_url;
    }

    window.AdminHero = AdminHero;
    document.addEventListener('DOMContentLoaded', () => AdminHero.init());
})();
