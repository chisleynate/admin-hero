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
            this.enforceFrontendFloater();
            if (this.is_admin && this.is_pro) this.initPro();
        },

        cacheElements() {
            this.modal = document.getElementById('admin-hero-modal');
            this.closeBtn = document.querySelector('.admin-hero-close-btn');
            this.saveButton = document.getElementById('admin-hero-save');
            this.timestamp = document.getElementById('admin-hero-timestamp');
            this.overlay = document.getElementById('admin-hero-overlay');
            this.nonceInput = document.getElementById('admin-hero-nonce');
            this.settingsToggle = document.getElementById('admin-hero-settings-toggle');
            this.settingsPanel = document.getElementById('admin-hero-settings-panel');
            this.infoToggle = document.getElementById('admin-hero-info-toggle');
            this.infoPanel = document.getElementById('admin-hero-info-panel');
            this.frontendIcon = document.querySelector('.admin-hero-frontend-icon');
            this.floaterIcon = document.querySelector('.admin-hero-floater-icon');
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
                a.href = '#';
                a.title = 'AdminHero';

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
                    if (this.timestamp) {
                        this.timestamp.textContent = 'Last saved: ' + json.data.timestamp;
                    }
                    document.dispatchEvent(new CustomEvent('admin-hero-saved', {
                        detail: { timestamp: json.data.timestamp }
                    }));
                    setTimeout(() => {
                        this.overlay.classList.remove('admin-hero-visible');
                        setTimeout(() => this.overlay.style.display = 'none', 200);
                    }, 1000);
                });
            });

            // Panel toggle logic is now handled in modal-core.php

            // Listen for feature toggle
            document.addEventListener('admin-hero-feature-toggle', ({ detail }) => {
                this.handleFeatureToggle(detail.featureId, detail.enabled);
            });
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

        initFeatures() {
            const container = this.settingsPanel?.querySelector('.admin-hero-settings-content');
            if (!container || !Array.isArray(this.features)) return;

            let fieldset = container.querySelector('fieldset.admin-hero-settings-fieldset');

            if (!fieldset) {
                fieldset = document.createElement('fieldset');
                fieldset.className = 'admin-hero-settings-fieldset';
                const legend = document.createElement('legend');
                legend.textContent = 'Global';
                fieldset.appendChild(legend);

                const refElement =
                    container.querySelector('.admin-hero-pro-features')
                    || container.querySelector('.get-pro-button')
                    || container.querySelector('#admin-hero-close-settings');
                container.insertBefore(fieldset, refElement);
            }

            const refElementInside = 
                fieldset.querySelector('.admin-hero-pro-features')
                || fieldset.querySelector('.get-pro-button')
                || fieldset.querySelector('#admin-hero-close-settings')
                || null;

            this.features.forEach(feature => {
                const line = document.createElement('div');
                line.className = 'admin-hero-setting-line';

                const label = document.createElement('label');
                label.className = 'admin-hero-toggle-text';
                label.textContent = feature.name;

                if (typeof feature.tooltip === 'string' && feature.tooltip.trim().length > 0) {
                    const infoSpan = document.createElement('span');
                    infoSpan.className = 'admin-hero-toggle-info';
                    infoSpan.title = feature.tooltip.trim();

                    const infoIcon = document.createElement('i');
                    infoIcon.className = 'fas fa-info-circle';
                    infoSpan.appendChild(infoIcon);

                    infoSpan.style.marginLeft = '6px';
                    infoSpan.style.cursor = 'help';
                    infoIcon.style.color = '#aaa';
                    infoIcon.style.fontSize = '0.9em';
                    infoSpan.addEventListener('mouseenter', () => infoIcon.style.color = '#56ff00');
                    infoSpan.addEventListener('mouseleave', () => infoIcon.style.color = '#aaa');

                    label.appendChild(infoSpan);
                }

                const wrap = document.createElement('label');
                wrap.className = 'switch';

                const input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = feature.enabled;
                input.dataset.featureId = feature.id;

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

                if (refElementInside) {
                    fieldset.insertBefore(line, refElementInside);
                } else {
                    fieldset.appendChild(line);
                }

                if (typeof feature.description === 'string' && feature.description.trim().length > 0) {
                    const descDiv = document.createElement('div');
                    descDiv.className = 'admin-hero-toggle-description';

                    const bulletIcon = document.createElement('i');
                    bulletIcon.className = 'fas fa-info-circle admin-hero-desc-icon';
                    bulletIcon.style.marginRight = '6px';
                    bulletIcon.style.color = '#aaa';
                    bulletIcon.style.fontSize = '0.9em';
                    bulletIcon.addEventListener('mouseenter', () => bulletIcon.style.color = '#56ff00');
                    bulletIcon.addEventListener('mouseleave', () => bulletIcon.style.color = '#aaa');

                    descDiv.appendChild(bulletIcon);
                    descDiv.appendChild(document.createTextNode(feature.description.trim()));

                    if (refElementInside) {
                        fieldset.insertBefore(descDiv, refElementInside);
                    } else {
                        fieldset.appendChild(descDiv);
                    }
                }
            });
        },

        enforceFrontendFloater() {
            const fe = document.querySelector('input[data-feature-id="frontend"]');
            const fl = document.querySelector('input[data-feature-id="floater"]');
            if (!fe || !fl) return;

            if (!fe.checked) {
                fl.checked = false;
                fl.disabled = true;
            } else {
                fl.disabled = false;
                if (!fl.checked) {
                    fl.checked = true;
                    this.apiCall('admin_hero_save_settings', {
                        nonce: this.nonceInput.value || this.nonce,
                        'settings[floater]': '1'
                    }).catch(console.error);
                }
            }
            this.updateHeaderIcons();
        },

        handleFeatureToggle(featureId, enabled) {
            if (featureId === 'frontend' || featureId === 'floater') {
                this.enforceFrontendFloater();
            }
        },

        updateHeaderIcons() {
            const fe = document.querySelector('input[data-feature-id="frontend"]');
            const fl = document.querySelector('input[data-feature-id="floater"]');
            if (!this.frontendIcon || !this.floaterIcon || !fe) return;

            if (!fe.checked) {
                this.frontendIcon.style.display = 'none';
                this.floaterIcon.style.display = 'none';
                return;
            }
            if (fe.checked && !fl.checked) {
                this.frontendIcon.style.display = 'block';
                this.floaterIcon.style.display = 'none';
                return;
            }
            this.frontendIcon.style.display = 'none';
            this.floaterIcon.style.display = 'none';
        },

        initPro() {
            if (!this.proStyleUrl || !this.proScriptUrl) return;
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = this.proStyleUrl;
            document.head.appendChild(link);

            const script = document.createElement('script');
            script.src = this.proScriptUrl;
            script.onload = () => document.dispatchEvent(new CustomEvent('admin-hero-pro-loaded'));
            document.body.appendChild(script);
        }
    };

    if (window.AdminHero) {
        AdminHero.ajax_url = window.AdminHero.ajax_url;
        AdminHero.nonce = window.AdminHero.nonce;
        AdminHero.version = window.AdminHero.version;
        AdminHero.is_admin = window.AdminHero.is_admin;
        AdminHero.is_pro = window.AdminHero.is_pro;
        AdminHero.features = window.AdminHero.features;
        AdminHero.proStyleUrl = window.AdminHero.proStyleUrl;
        AdminHero.proScriptUrl = window.AdminHero.proScriptUrl;
        AdminHero.logo_url = window.AdminHero.logo_url;
        AdminHero.logo_pro_url = window.AdminHero.logo_pro_url;
    }

    window.AdminHero = AdminHero;
    document.addEventListener('DOMContentLoaded', () => AdminHero.init());
})();