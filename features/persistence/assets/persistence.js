// features/persistence/assets/persistence.js
;(function () {
  let modal, adminBarLink, floatingButton, ICONS, iconEl, observer;
  let isInitialized = false;

  // Keys for localStorage
  const OPEN_KEY   = 'adminHeroModalOpen';
  const POS_KEY    = 'adminHeroModalPosition';
  const FS_KEY     = 'adminHeroModalFullscreen';
  const PERSIST_ID = 'persistence'; // feature ID for Persistence itself

  /* ——— Open / Close ——— */
  function openModal() {
    modal.classList.remove('admin-hero-hidden');
    modal.style.setProperty('display', 'flex', 'important');
  }
  function closeModal() {
    modal.classList.add('admin-hero-hidden');
    modal.style.setProperty('display', 'none', 'important');
  }

  /* ——— Fullscreen ——— */
  function enterFullscreen() {
    modal.classList.add('admin-hero-fullscreen');
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow            = 'hidden';
  }
  function exitFullscreen() {
    modal.classList.remove('admin-hero-fullscreen');
    document.documentElement.style.overflow = '';
    document.body.style.overflow            = '';
  }

  /* ——— Position ——— */
  function applyStoredPosition() {
    const raw = localStorage.getItem(POS_KEY);
    if (!raw) return;
    try {
      const { top, left } = JSON.parse(raw);
      if (typeof top === 'number' && typeof left === 'number') {
        modal.style.setProperty('position', 'fixed', 'important');
        modal.style.setProperty('top',      `${top}px`,  'important');
        modal.style.setProperty('left',     `${left}px`, 'important');
        modal.style.removeProperty('bottom');
        modal.style.removeProperty('right');
      }
    } catch (_) {
      // invalid JSON → ignore
    }
  }

  /* ——— Saves (write to localStorage *from any tab*) ——— */
  function saveOpenState() {
    const isOpen = !modal.classList.contains('admin-hero-hidden');
    if (isOpen) {
      localStorage.setItem(OPEN_KEY, 'true');
    } else {
      localStorage.removeItem(OPEN_KEY);
    }
  }

  function savePosition() {
    if (modal.classList.contains('admin-hero-hidden')) return;
    const { top, left } = modal.getBoundingClientRect();
    localStorage.setItem(POS_KEY, JSON.stringify({ top, left }));
  }

  function saveFullscreenState() {
    const isFS = modal.classList.contains('admin-hero-fullscreen');
    if (isFS) {
      localStorage.setItem(FS_KEY, 'true');
    } else {
      localStorage.removeItem(FS_KEY);
    }
  }

  /* ——— Initial Restore (on page load or after WP AJAX) ——— */
  function restoreAll() {
    // 1) modal open/close
    if (localStorage.getItem(OPEN_KEY) === 'true') {
      openModal();
    } else {
      closeModal();
    }
    // 2) modal position
    applyStoredPosition();
    // 3) fullscreen
    if (localStorage.getItem(FS_KEY) === 'true') {
      enterFullscreen();
    }
  }

  /* ——— Mirror exactly one checkbox via data-feature-id ——— */
  function mirrorCheckbox(featureId, enabled) {
    const selector = 'input[type="checkbox"][data-feature-id="' + featureId + '"]';
    const checkbox = document.querySelector(selector);
    if (!checkbox) return;
    checkbox.checked = !!enabled;
  }

  /* ——— Cross-tab Sync Handler ——— */
  function onStorage(e) {
    if (e.storageArea !== localStorage) return;

    // — Sync modal open/close —
    if (e.key === OPEN_KEY) {
      e.newValue === 'true' ? openModal() : closeModal();
    }

    // — Sync modal position —
    if (e.key === POS_KEY) {
      applyStoredPosition();
    }

    // — Sync fullscreen —
    if (e.key === FS_KEY) {
      e.newValue === 'true' ? enterFullscreen() : exitFullscreen();
    }

    // — Sync any feature toggle: key = "adminHeroFeature_<featureId>" —
    if (typeof e.key === 'string' && e.key.startsWith('adminHeroFeature_')) {
      const featureId = e.key.slice('adminHeroFeature_'.length);
      const enabled   = (e.newValue === 'true');

      // 1) Update window.AdminHero.features[...] in this tab
      if (Array.isArray(window.AdminHero?.features)) {
        const feat = window.AdminHero.features.find((f) => f.id === featureId);
        if (feat) feat.enabled = enabled;
      }

      // 2) Mirror the matching Settings-pane checkbox by data-feature-id
      mirrorCheckbox(featureId, enabled);

      // 3) Fire “admin-hero-feature-toggled” so each feature’s handler runs instantly
      document.dispatchEvent(
        new CustomEvent('admin-hero-feature-toggled', {
          detail: { featureId, enabled },
        })
      );
    }
  }

  /* ——— Listeners & MutationObserver ——— */
  function attachListeners() {
    // 1) Observe changes to modal’s class & style to save open/close/position/fullscreen
    observer = new MutationObserver((records) => {
      records.forEach((m) => {
        if (m.attributeName === 'style') {
          savePosition();
        }
        if (m.attributeName === 'class') {
          saveOpenState();
          saveFullscreenState();
        }
      });
    });
    observer.observe(modal, {
      attributes:       true,
      attributeFilter: ['class', 'style']
    });

    // 2) Clicking admin-bar icon or floating button may open/close or move the modal
    adminBarLink   = document.querySelector('#wp-admin-bar-admin-hero a');
    floatingButton = document.getElementById('admin-hero-floating-button');

    adminBarLink?.addEventListener('click',   () => setTimeout(savePosition, 0));
    floatingButton?.addEventListener('click', () => setTimeout(savePosition, 0));

    // 3) Listen for localStorage “storage” events in this tab
    window.addEventListener('beforeunload', savePosition);
    window.addEventListener('storage',       onStorage);
  }

  /* ——— Show/hide the Persistence header icon ——— */
  function addIcon() {
    if (!ICONS || ICONS.querySelector('.admin-hero-persistence-icon')) return;
    iconEl = document.createElement('div');
    iconEl.className = 'admin-hero-persistence-icon';
    iconEl.title     = 'Persistence Active';
    iconEl.innerHTML = '<i class="fas fa-bookmark"></i>';
    ICONS.appendChild(iconEl);
  }
  function removeIcon() {
    const el = ICONS?.querySelector('.admin-hero-persistence-icon');
    if (el) el.remove();
  }

  /* ——— Listen for “persistence” being toggled so we can init/destroy ——— */
  function watchPersistenceToggleOnce() {
    function onPersistenceEvent(e) {
      if (e.detail?.featureId !== PERSIST_ID) return;

      if (e.detail.enabled) {
        // Persistence just turned ON → run init() once
        if (!isInitialized) {
          isInitialized = true;
          init();
        }
      } else {
        // Persistence turned OFF → immediately tear down
        destroy();
      }
    }

    document.addEventListener('admin-hero-feature-toggle',  onPersistenceEvent);
    document.addEventListener('admin-hero-feature-toggled', onPersistenceEvent);
  }

  /* ——— Feature Toggle → localStorage Sync ——— */
  function wireFeatureToggle() {
    ['admin-hero-feature-toggle', 'admin-hero-feature-toggled'].forEach((evtName) => {
      document.addEventListener(evtName, (e) => {
        if (!e.detail || !e.detail.featureId) return;
        const key = 'adminHeroFeature_' + e.detail.featureId;
        if (e.detail.enabled) {
          localStorage.setItem(key, 'true');
        } else {
          localStorage.removeItem(key);
        }
      });
    });
  }

  /* ——— Tear down everything when Persistence is turned OFF ——— */
  function destroy() {
    // 1) Disconnect MutationObserver
    if (observer) {
      observer.disconnect();
      observer = null;
    }

    // 2) Remove event listeners
    const abLink   = document.querySelector('#wp-admin-bar-admin-hero a');
    const floatBtn = document.getElementById('admin-hero-floating-button');
    abLink?.removeEventListener('click',   () => setTimeout(savePosition, 0));
    floatBtn?.removeEventListener('click', () => setTimeout(savePosition, 0));
    window.removeEventListener('beforeunload', savePosition);
    window.removeEventListener('storage',       onStorage);

    // 3) Remove the Persistence header icon
    removeIcon();

    isInitialized = false;
  }

  /* ——— Init (set up all of Persistence’s listeners) ——— */
  function init() {
    modal = document.getElementById('admin-hero-modal');
    ICONS = document.getElementById('admin-hero-settings-icons');
    if (!modal) return;

    // 1) Restore this tab’s modal state (open/position/fullscreen)
    restoreAll();

    // 2) Attach all listeners for modal changes + cross-tab syncing
    attachListeners();

    // 3) Show Persistence header icon if enabled here
    addIcon();

    // 4) Mirror every feature toggle into localStorage
    wireFeatureToggle();

    // 5) Reapply after WP’s plugin-update AJAX (if Admin-Hero UI re-renders)
    if (window.jQuery) {
      jQuery(document).on('ajaxComplete', (_e, _xhr, settings) => {
        if (
          settings.url?.includes('update.php') ||
          (settings.data && settings.data.includes('action=update-plugin'))
        ) {
          setTimeout(restoreAll, 50);
        }
      });
    }
  }

  // ── Bootstrap: Listen once for Persistence being toggled on/off ──
  watchPersistenceToggleOnce();

  // ── If Persistence was already on at page-load, initialize immediately ──
  if (
    window.AdminHero?.features?.some((f) => f.id === PERSIST_ID && f.enabled)
  ) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        isInitialized = true;
        init();
      });
    } else {
      isInitialized = true;
      init();
    }
  }
})();
