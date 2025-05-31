;(function(){
  function initAutosave(){
    const LOCAL_KEY  = 'adminHeroNoteBackup',
          overlay    = document.getElementById('admin-hero-overlay'),
          icons      = document.getElementById('admin-hero-settings-icons'),
          closeBtn   = document.querySelector('.admin-hero-close-btn'),
          timestamp  = document.getElementById('admin-hero-timestamp'),
          MAX_RETRY  = 1;
    let lastContent='', handlerAttached=false, stripCache=new Map();

    function stripHtml(html){
      if (stripCache.has(html)) return stripCache.get(html);
      const div=document.createElement('div');
      div.innerHTML=html;
      const txt=div.textContent||div.innerText||'';
      stripCache.set(html, txt);
      return txt;
    }
    function debounce(fn,ms){
      let t; return (...a)=>{
        clearTimeout(t); t=setTimeout(()=>fn(...a),ms);
      };
    }
    function showOverlay(icon,text){
      if(!overlay) return;
      overlay.innerHTML=`<i class="fa-solid ${icon} autosave-icon"></i><span>${text}</span>`;
      overlay.style.display='flex';
      overlay.classList.remove('admin-hero-visible');
      void overlay.offsetWidth;
      overlay.classList.add('admin-hero-visible');
      setTimeout(()=>{
        overlay.classList.remove('admin-hero-visible');
        setTimeout(()=>overlay.style.display='none',200);
      },1200);
    }
    function ajaxRetry(fd,retries=MAX_RETRY){
      return fetch(AdminHero.ajax_url,{method:'POST',body:fd})
        .then(r=>r.json())
        .then(j=>{ if(!j.success) throw 0; return j; })
        .catch(err=>{
          if(retries>0) return ajaxRetry(fd,retries-1);
          console.error('Autosave failed',err);
          throw err;
        });
    }
    function saveNow(content){
      showOverlay('fa-wand-sparkles','Notes Auto Saved');
      const fd=new FormData();
      fd.append('action','admin_hero_save_note');
      fd.append('nonce', AdminHero.nonce);
      fd.append('note',  content);
      return ajaxRetry(fd)
        .then(j=>{
          lastContent=content;
          localStorage.removeItem(LOCAL_KEY);
          if(timestamp) timestamp.textContent='Last saved: '+j.data.timestamp;
        })
        .catch(()=> showOverlay('fa-exclamation-triangle','Auto Save Failed'));
    }
    const scheduleSave=debounce(c=>{
      if(stripHtml(c)!==stripHtml(lastContent)) saveNow(c);
    },3000);

    function attachClose(){
      if(handlerAttached||!closeBtn) return;
      const h=e=>{
        e.stopImmediatePropagation();
        e.preventDefault();
        const content=window.quillEditor?.root.innerHTML||'';
        showOverlay('fa-wand-sparkles','Notes Auto Saved');
        const fd=new FormData();
        fd.append('action','admin_hero_save_note');
        fd.append('nonce', AdminHero.nonce);
        fd.append('note',  content);
        ajaxRetry(fd).catch(()=>{});
        setTimeout(()=>AdminHero.toggleModal(e),1200);
      };
      closeBtn.addEventListener('click',h,true);
      closeBtn._ahHandler=h;
      handlerAttached=true;
    }
    function detachClose(){
      if(closeBtn&&closeBtn._ahHandler){
        closeBtn.removeEventListener('click',closeBtn._ahHandler,true);
        delete closeBtn._ahHandler;
      }
      handlerAttached=false;
    }

    function startAutosave(){
      if(!window.quillEditor) return;
      if(icons&&!icons.querySelector('.admin-hero-autosave-icon')){
        const wrap=document.createElement('div');
        wrap.className='admin-hero-autosave-icon';
        wrap.title='Autosave Active';
        wrap.innerHTML='<i class="fa-solid fa-wand-sparkles"></i>';
        icons.appendChild(wrap);
      }
      const backup=localStorage.getItem(LOCAL_KEY);
      if(backup&&stripHtml(window.quillEditor.root.innerHTML)===''){
        window.quillEditor.root.innerHTML=backup;
      }
      lastContent=window.quillEditor.root.innerHTML||'';
      scheduleSave(lastContent);
      window.quillEditor.on('text-change',()=>{
        const c=window.quillEditor.root.innerHTML||'';
        localStorage.setItem(LOCAL_KEY,c);
        scheduleSave(c);
      });
      attachClose();
    }
    function stopAutosave(){
      if(window.quillEditor) window.quillEditor.off('text-change');
      detachClose();
      const icon=icons?.querySelector('.admin-hero-autosave-icon');
      if(icon) icon.remove();
    }

    // initial kick-off
    if(AdminHero.features?.some(f=>f.id==='autosave'&&f.enabled)){
      const ready=setInterval(()=>{
        if(window.quillEditor){
          clearInterval(ready);
          startAutosave();
        }
      },300);
    }

    // BOTH toggle events for instant on/off
    ['admin-hero-feature-toggle','admin-hero-feature-toggled']
    .forEach(evt=>{
      document.addEventListener(evt,e=>{
        if(e.detail?.featureId!=='autosave') return;
        e.detail.enabled ? startAutosave() : stopAutosave();
      });
    });
  }

  // bootstrap
  if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded', initAutosave);
  } else {
    initAutosave();
  }
})();
