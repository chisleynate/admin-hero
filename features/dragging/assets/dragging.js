// features/dragging/assets/dragging.js
;(function(){
  const MODAL  = document.getElementById('admin-hero-modal');
  const HEADER = document.getElementById('admin-hero-header');
  const ICONS  = document.getElementById('admin-hero-settings-icons');
  let isDragging = false;
  let startX = 0, startY = 0, origX = 0, origY = 0;

  // ─── Helper: keep modal within viewport ───────────────────────────────────
  function clampPosition(x, y) {
    const cw = document.documentElement.clientWidth;
    const ch = document.documentElement.clientHeight;
    const mw = MODAL.offsetWidth;
    const mh = MODAL.offsetHeight;
    const minX = 0, minY = 0;
    const maxX = cw - mw, maxY = ch - mh;
    return [
      Math.min(Math.max(x, minX), maxX),
      Math.min(Math.max(y, minY), maxY)
    ];
  }

  // ─── Mouse events for dragging ───────────────────────────────────────────
  function onMouseDown(e) {
    // Skip if clicking any of the header’s icons/buttons
    if ( e.target.closest(
      '.admin-hero-close-btn,' +
      '.admin-hero-settings-icon,' +
      '.admin-hero-info-icon,' +
      '.admin-hero-fullscreen-icon'
    ) ) return;
    if (e.button !== 0) return;

    // Disable text selection while dragging
    document.body.style.userSelect = 'none';
    document.body.style.webkitUserSelect = 'none';
    document.body.style.msUserSelect = 'none';

    isDragging = true;
    startX = e.clientX;
    startY = e.clientY;
    const rect = MODAL.getBoundingClientRect();
    origX = rect.left;
    origY = rect.top;

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup',   onMouseUp);
    MODAL.classList.add('admin-hero-dragging');
  }

  function onMouseMove(e) {
    if (!isDragging) return;
    let [newX, newY] = [
      origX + (e.clientX - startX),
      origY + (e.clientY - startY)
    ];
    [newX, newY] = clampPosition(newX, newY);
    MODAL.style.position = 'fixed';
    MODAL.style.left     = newX + 'px';
    MODAL.style.top      = newY + 'px';
  }

  function onMouseUp() {
    if (!isDragging) return;
    isDragging = false;

    // Restore text selection
    document.body.style.userSelect = '';
    document.body.style.webkitUserSelect = '';
    document.body.style.msUserSelect = '';

    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup',   onMouseUp);
    MODAL.classList.remove('admin-hero-dragging');
  }

  // ─── Header-icon for “Dragging Active” ───────────────────────────────────
  function addIcon() {
    if (!ICONS || ICONS.querySelector('.admin-hero-dragging-icon')) return;
    const d = document.createElement('div');
    d.className = 'admin-hero-dragging-icon';
    d.title     = 'Dragging Active';
    d.innerHTML = '<i class="fas fa-arrows-alt"></i>';
    ICONS.appendChild(d);
  }
  function removeIcon() {
    const d = ICONS?.querySelector('.admin-hero-dragging-icon');
    if (d) d.remove();
  }

  function startDragging() { 
    HEADER.addEventListener('mousedown', onMouseDown); 
  }
  function stopDragging() { 
    HEADER.removeEventListener('mousedown', onMouseDown); 
  }

  function enableStyles() {
    MODAL.classList.add('dragging-enabled');
    addIcon();
    window.addEventListener('resize', clampOnResize);
  }
  function disableStyles() {
    MODAL.classList.remove('dragging-enabled');
    removeIcon();
    window.removeEventListener('resize', clampOnResize);
    // Reset to CSS-default position
    MODAL.style.removeProperty('position');
    MODAL.style.removeProperty('left');
    MODAL.style.removeProperty('top');
  }

  function clampOnResize() {
    if (!MODAL.classList.contains('dragging-enabled') || !MODAL.style.left) return;
    const left = parseInt(MODAL.style.left, 10);
    const top  = parseInt(MODAL.style.top, 10);
    const [x, y] = clampPosition(left, top);
    MODAL.style.left = x + 'px';
    MODAL.style.top  = y + 'px';
  }

  function handleToggle(enabled) {
    if (enabled) {
      startDragging();
      enableStyles();
    } else {
      stopDragging();
      disableStyles();
    }
  }

  // ─── Reset-to-default link injection ─────────────────────────────────────
  function addResetControl(line) {
    // If the reset link already exists on that line, do nothing
    if (line.querySelector('.admin-hero-reset-link')) return;

    const reset = document.createElement('a');
    reset.href = '#';
    reset.textContent = '🡶';
    reset.className = 'admin-hero-reset-link';
    reset.title = 'Reset position';

    reset.addEventListener('click', e => {
      e.preventDefault();
      // Remove any inline positioning so CSS picks up its default bottom-right placement
      MODAL.style.removeProperty('position');
      MODAL.style.removeProperty('top');
      MODAL.style.removeProperty('left');
    });

    // Insert before the ".switch" toggle on that line:
    const switchEl = line.querySelector('.switch');
    if (switchEl) {
      line.insertBefore(reset, switchEl);
    } else {
      line.appendChild(reset);
    }
  }

  function tagSettingsLine() {
    // Find each ".admin-hero-setting-line" and look for the label text "Dragging Modal"
    document.querySelectorAll('.admin-hero-setting-line').forEach(line => {
      const label = line.querySelector('.admin-hero-toggle-text');
      if (label && label.textContent.trim() === 'Dragging Modal') {
        // Mark for styling
        line.classList.add('admin-hero-setting-dragging');
        // Insert the reset-link arrow
        addResetControl(line);
      }
    });
  }

  // ─── Bootstrap: wait for AdminHero.features to be available ───────────────
  document.addEventListener('DOMContentLoaded', () => {
    let tries = 0;
    (function check() {
      const feats = window.AdminHero?.features;
      if (Array.isArray(feats)) {
        // 1) Inject reset link next to the Dragging Modal toggle
        tagSettingsLine();

        // 2) Immediately apply the current toggle state
        const f = feats.find(f => f.id === 'dragging');
        if (f) {
          handleToggle(f.enabled);
        }
        return;
      }
      if (tries++ < 10) {
        setTimeout(check, 200);
      }
    })();
  });

  // ─── Live toggle: listen to BOTH “before” and “after” events ─────────────
  ['admin-hero-feature-toggle','admin-hero-feature-toggled']
    .forEach(evtName => {
      document.addEventListener(evtName, e => {
        if (e.detail?.featureId === 'dragging') {
          handleToggle(!!e.detail.enabled);
        }
      });
    });
})();
