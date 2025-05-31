// features/fullscreen/assets/fullscreen.js
;(function () {
  const MODAL      = document.getElementById('admin-hero-modal');
  const HEADER     = document.getElementById('admin-hero-header');
  let btn          = null;

  // persistence keys
  const POS_KEY    = 'adminHeroModalPosition';
  const PRE_FS_KEY = 'adminHeroModalPositionBeforeFullscreen';
  const FS_KEY     = 'adminHeroModalFullscreen';

  // helper to disable/restore page scrolling
  function togglePageScroll(disable) {
    document.documentElement.style.overflow = disable ? 'hidden' : '';
    document.body.style.overflow            = disable ? 'hidden' : '';
  }

  // snapshot current POS_KEY → PRE_FS_KEY
  function snapshotPreFS() {
    const raw = localStorage.getItem(POS_KEY);
    if (raw) {
      localStorage.setItem(PRE_FS_KEY, raw);
    }
  }

  // restore position from PRE_FS_KEY
  function restorePreFS() {
    const raw = localStorage.getItem(PRE_FS_KEY);
    if (!raw) return;
    try {
      const { top, left } = JSON.parse(raw);
      MODAL.style.setProperty('position','fixed','important');
      MODAL.style.setProperty('top',     `${top}px`,  'important');
      MODAL.style.setProperty('left',    `${left}px`, 'important');
    } catch {}
  }

  // Enter fullscreen: snapshot, clamp, class + inline sizing, persist FS_KEY
  function enterFS() {
    snapshotPreFS();
    MODAL.classList.add('admin-hero-fullscreen');
    togglePageScroll(true);
    MODAL.style.setProperty('position', 'fixed',  'important');
    MODAL.style.setProperty('top',      '0px',     'important');
    MODAL.style.setProperty('left',     '0px',     'important');
    MODAL.style.setProperty('width',    '100vw',   'important');
    MODAL.style.setProperty('height',   '100vh',   'important');

    // ←—— Write to localStorage so other tabs mirror instantly —→
    localStorage.setItem(FS_KEY, 'true');
  }

  // Exit fullscreen: clear sizing, restore snapshot, clear FS_KEY
  function exitFS() {
    MODAL.classList.remove('admin-hero-fullscreen');
    togglePageScroll(false);
    ['position','top','left','width','height'].forEach(prop =>
      MODAL.style.removeProperty(prop)
    );
    restorePreFS();

    // ←—— Remove from localStorage so other tabs mirror instantly —→
    localStorage.removeItem(FS_KEY);
  }

  function addBtn() {
    if (!MODAL || !HEADER || btn) return;

    btn = document.createElement('div');
    btn.id        = 'admin-hero-fullscreen-toggle';
    btn.className = 'admin-hero-fullscreen-toggle admin-hero-fullscreen-icon';
    btn.title     = 'Toggle Fullscreen';
    btn.innerHTML = '<i class="fas fa-expand"></i><i class="fas fa-compress"></i>';

    btn.addEventListener('click', () => {
      const isFS = MODAL.classList.contains('admin-hero-fullscreen');
      if (!isFS) {
        enterFS();
      } else {
        exitFS();
      }
      // persistence.js will also see the FS_KEY change via storage event
    });

    HEADER.insertBefore(
      btn,
      HEADER.querySelector('.admin-hero-close-btn')
    );
  }

  function removeBtn() {
    if (!btn) return;
    // if still in FS, exit it first
    if (MODAL.classList.contains('admin-hero-fullscreen')) {
      exitFS();
    }
    btn.remove();
    btn = null;
  }

  function init() {
    const feat = window.AdminHero?.features?.find(f => f.id === 'fullscreen');
    if (feat?.enabled) addBtn();

    // Listen for live toggle in dashboard
    document.addEventListener('admin-hero-feature-toggle', e => {
      if (e.detail.featureId !== 'fullscreen') return;
      e.detail.enabled ? addBtn() : removeBtn();
    });

    // Cross-tab sync via storage events on FS_KEY
    window.addEventListener('storage', e => {
      if (e.storageArea !== localStorage || e.key !== FS_KEY) return;
      // e.newValue === 'true' means remote tab entered fullscreen
      if (e.newValue === 'true') {
        if (!MODAL.classList.contains('admin-hero-fullscreen')) {
          enterFS();
        }
      } else {
        // remote tab exited fullscreen
        if (MODAL.classList.contains('admin-hero-fullscreen')) {
          exitFS();
        }
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
