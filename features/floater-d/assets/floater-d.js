;(function(){
  const ICONS_ID = 'admin-hero-settings-icons';

  // ── Inject/remove the floating button ──────────────────────────────────
  function injectFloater(){
    if ( document.getElementById('admin-hero-floating-button') ) return;
    const btn = document.createElement('button');
    btn.id    = 'admin-hero-floating-button';
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
    const btn = document.getElementById('admin-hero-floating-button');
    if ( btn ) btn.remove();
  }

  // ── Add/remove header “D” icon ─────────────────────────────────────────
  function addHeaderIcon(){
    const icons = document.getElementById(ICONS_ID);
    if (!icons || icons.querySelector('.admin-hero-floater-d-icon')) return;
    const wrap = document.createElement('div');
    wrap.className = 'admin-hero-floater-d-icon';
    wrap.title     = 'Dashboard Floater Active';
    wrap.innerHTML = '<i class="fas fa-d"></i>';
    icons.appendChild(wrap);
  }
  function removeHeaderIcon(){
    document.querySelector(`#${ICONS_ID} .admin-hero-floater-d-icon`)?.remove();
  }

  // ── Read initial state (server output) ─────────────────────────────────
  function init(){
    const initial = Array.isArray(AdminHero.features)
      && AdminHero.features.find(f => f.id === 'floater-d')?.enabled;

    if ( initial ) {
      // If in WP-Admin, inject the actual floating button…
      if ( AdminHero.is_admin ) {
        injectFloater();
      }
      // …but in all cases (admin or front-end) show the “D” icon in the header
      addHeaderIcon();
    }
  }

  // ── Handle toggle events (before/after) ─────────────────────────────────
  function handleToggle(e){
    if ( e.detail?.featureId !== 'floater-d' ) return;

    if ( e.detail.enabled ) {
      // Turning “Floater (Dashboard)” ON:
      if ( AdminHero.is_admin ) {
        injectFloater();
      }
      addHeaderIcon();
    } else {
      // Turning it OFF:
      if ( AdminHero.is_admin ) {
        removeFloater();
      }
      removeHeaderIcon();
    }
  }

  // ── Bootstrap ─────────────────────────────────────────────────────────
  if ( document.readyState === 'loading' ) {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Listen to both “before‐toggle” and “after‐toggle” so we update instantly
  document.addEventListener('admin-hero-feature-toggle',  handleToggle);
  document.addEventListener('admin-hero-feature-toggled', handleToggle);
})();
