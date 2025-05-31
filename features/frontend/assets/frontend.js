// features/frontend/assets/frontend.js
;(function(){
  const ICONS_ID        = 'admin-hero-settings-icons';
  const MODAL_SELECTOR  = '#admin-hero-modal';
  const FLOATER_ID      = 'admin-hero-floating-button';
  const ADMIN_BAR_ID    = 'wp-admin-bar-admin-hero'; // only exists in admin bar on front-end

  // ────────── Helpers ────────────────────────────────────────────────────────────

  function injectFloater(){
    if ( document.getElementById(FLOATER_ID) ) return;
    const btn = document.createElement('button');
    btn.id    = FLOATER_ID;
    btn.title = 'AdminHero';
    const logo = document.createElement('img');
    logo.src       = AdminHero.is_pro ? AdminHero.logo_pro_url : AdminHero.logo_url;
    logo.alt       = AdminHero.is_pro ? 'AdminHero Pro Logo' : 'AdminHero Logo';
    logo.className = 'admin-hero-floating-logo';
    btn.appendChild(logo);
    btn.addEventListener('click', e => AdminHero.toggleModal(e));
    document.body.appendChild(btn);
  }
  function removeFloater(){
    document.getElementById(FLOATER_ID)?.remove();
  }

  function addModeIcon(){
    const icons = document.getElementById(ICONS_ID);
    if (!icons || icons.querySelector('.admin-hero-frontend-icon')) return;
    const wrap = document.createElement('div');
    wrap.className = 'admin-hero-frontend-icon';
    wrap.title     = 'Frontend Mode Active';
    wrap.innerHTML = '<i class="fa-solid fa-globe"></i>';
    icons.appendChild(wrap);
  }
  function removeModeIcon(){
    document.querySelector(`#${ICONS_ID} .admin-hero-frontend-icon`)?.remove();
  }

  function hideAdminBarButton(){
    // Only affects front-end (admin bar). In admin screens, the admin bar item isn't present.
    const li = document.getElementById(ADMIN_BAR_ID);
    if (li) {
      li.style.display = 'none';
    }
  }
  function restoreAdminBarButton(){
    const li = document.getElementById(ADMIN_BAR_ID);
    if (li) {
      li.style.display = '';
    }
  }

  function hideModalIfOpen(){
    const m = document.querySelector(MODAL_SELECTOR);
    if (!m) return;
    m.classList.add('admin-hero-hidden');
    m.style.setProperty('display','none','important');
  }

  // ────────── Handler: toggles “Frontend Mode” ────────────────────────────────────

  function handleFrontendToggle(enabled){
    if (enabled) {
      // 1) Show globe icon in the modal header (on both admin & front-end)
      addModeIcon();

      // 2) On front-end only, restore admin-bar button & floating button
      if (!AdminHero.is_admin) {
        restoreAdminBarButton();
        injectFloater();
      }

    } else {
      // 1) Remove globe icon from modal header (on both admin & front-end)
      removeModeIcon();

      // 2) On front-end only, hide everything:
      if (!AdminHero.is_admin) {
        hideAdminBarButton();   // remove the Admin-Hero item from WP admin bar
        removeFloater();       // remove the bottom-corner floating button
        hideModalIfOpen();     // if the modal was open, force it closed
      }
      // NOTE: on admin (dashboard), we do NOT remove the floater or modal there.
    }
  }

  // ────────── Initial bootstrap ─────────────────────────────────────────────────

  function init(){
    const feats       = Array.isArray(AdminHero.features) ? AdminHero.features : [];
    const frontendOn  = feats.some(f => f.id === 'frontend' && f.enabled);
    handleFrontendToggle(frontendOn);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // ────────── React to live‐toggle events ────────────────────────────────────────

  function onFeatureEvent(e){
    if (e.detail?.featureId === 'frontend') {
      handleFrontendToggle(!!e.detail.enabled);
    }
  }
  document.addEventListener('admin-hero-feature-toggle',  onFeatureEvent);
  document.addEventListener('admin-hero-feature-toggled', onFeatureEvent);
})();
