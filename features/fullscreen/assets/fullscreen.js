;(function () {
  const MODAL  = document.getElementById('admin-hero-modal');
  const HEADER = document.getElementById('admin-hero-header');
  let btn      = null;
  let savedPos = null; // holds inline { position, left, top }

  // helper to disable/restore page scroll
  function togglePageScroll(disable) {
    document.documentElement.style.overflow = disable ? 'hidden' : '';
    document.body.style.overflow            = disable ? 'hidden' : '';
  }

  function addBtn() {
    if (btn) return;
    btn = document.createElement('div');
    btn.id        = 'admin-hero-fullscreen-toggle';
    btn.className = 'admin-hero-fullscreen-toggle admin-hero-fullscreen-icon';
    btn.title     = 'Toggle Fullscreen';
    btn.innerHTML = '<i class="fas fa-expand"></i><i class="fas fa-compress"></i>';

    btn.addEventListener('click', () => {
      const isFS = MODAL.classList.contains('admin-hero-fullscreen');

      if (!isFS) {
        // → Entering fullscreen: stash inline coords
        const inline = {};
        ['position','left','top'].forEach(prop => {
          if (MODAL.style[prop]) inline[prop] = MODAL.style[prop];
          MODAL.style.removeProperty(prop);
        });
        savedPos = Object.keys(inline).length ? inline : null;
      }

      // → toggle full-screen class
      MODAL.classList.toggle('admin-hero-fullscreen');

      if (!isFS) {
        // → disable page scroll
        togglePageScroll(true);
      } else {
        // → exit fullscreen: restore scroll and position
        togglePageScroll(false);
        if (savedPos) {
          Object.entries(savedPos).forEach(([prop,val]) => {
            MODAL.style[prop] = val;
          });
        } else {
          ['position','left','top'].forEach(prop => {
            MODAL.style.removeProperty(prop);
          });
        }
      }
    });

    // inject into header next to close-X
    HEADER.insertBefore(btn, HEADER.querySelector('.admin-hero-close-btn'));
  }

  function removeBtn() {
    if (!btn) return;
    // if we’re tearing it down mid-fullscreen, restore scroll
    if (MODAL.classList.contains('admin-hero-fullscreen')) {
      togglePageScroll(false);
    }
    btn.remove();
    btn = null;
    savedPos = null;
    MODAL.classList.remove('admin-hero-fullscreen');
  }

  function init() {
    const feat = window.AdminHero?.features?.find(f => f.id === 'fullscreen');
    if (feat?.enabled) addBtn();

    document.addEventListener('admin-hero-feature-toggle', e => {
      if (e.detail.featureId !== 'fullscreen') return;
      e.detail.enabled ? addBtn() : removeBtn();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
