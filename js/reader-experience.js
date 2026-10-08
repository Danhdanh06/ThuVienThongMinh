(() => {
  if (!window.libraryAccess?.isAuthenticated) return;
  const isReader = window.libraryAccess?.roleKey === 'customer';
  const $ = id => document.getElementById(id);
  const esc = window.escapeHTML || (v => String(v ?? '').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])));
  const apiGet = async (action, params={}) => { const u=new URL('advanced_api.php',location.href);u.searchParams.set('action',action);Object.entries(params).forEach(([k,v])=>u.searchParams.set(k,v));const p=await fetch(u,{credentials:'same-origin',cache:'no-store'}).then(r=>r.json());if(!p.ok)throw new Error(p.message||'Có lỗi xảy ra');return p.data; };
  const apiPost = async (action,data={}) => { const u=new URL('advanced_api.php',location.href);u.searchParams.set('action',action);const p=await fetch(u,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}).then(r=>r.json());if(!p.ok)throw new Error(p.message||'Có lỗi xảy ra');return p; };

  // toast
  const nativeAlert = window.alert.bind(window);
  window.libraryToast = function(message, type='success', title='Thông báo'){
    let stack=$('libraryToastStack'); if(!stack){stack=document.createElement('div');stack.id='libraryToastStack';stack.className='library-toast-stack';document.body.appendChild(stack)}
    const node=document.createElement('div');node.className=`library-toast-modern ${type}`;const icon=type==='error'?'fa-xmark':type==='warning'?'fa-triangle-exclamation':'fa-check';node.innerHTML=`<i class="fa-solid ${icon}"></i><div><strong>${esc(title)}</strong><span>${esc(message)}</span></div>`;stack.appendChild(node);setTimeout(()=>{node.style.opacity='0';node.style.transform='translateY(-5px)';setTimeout(()=>node.remove(),220)},3600);
  };

  window.alert = message => window.libraryToast ? window.libraryToast(String(message),'warning','Thông báo') : nativeAlert(message);

  // async modern confirm for new and upgraded flows
  window.libraryConfirm = function(message, title='Xác nhận thao tác', danger=true){ return new Promise(resolve=>{let modal=$('libraryConfirmModern');if(!modal){modal=document.createElement('div');modal.id='libraryConfirmModern';modal.className='library-confirm-modern';modal.innerHTML=`<div class="library-confirm-card"><div class="icon"><i class="fa-solid fa-triangle-exclamation"></i></div><h3 id="libraryConfirmTitle"></h3><p id="libraryConfirmMessage"></p><div class="library-confirm-actions"><button class="cancel" id="libraryConfirmCancel">Hủy</button><button class="ok" id="libraryConfirmOk">Xác nhận</button></div></div>`;document.body.appendChild(modal)}$('libraryConfirmTitle').textContent=title;$('libraryConfirmMessage').textContent=message;const ok=$('libraryConfirmOk'),cancel=$('libraryConfirmCancel');ok.style.background=danger?'#dc2626':'var(--reader-accent)';const done=v=>{modal.classList.remove('show');ok.onclick=cancel.onclick=null;resolve(v)};ok.onclick=()=>done(true);cancel.onclick=()=>done(false);modal.onclick=e=>{if(e.target===modal)done(false)};modal.classList.add('show')}); };

  function timeAgo(raw){ if(!raw)return '';const d=new Date(String(raw).replace(' ','T'));if(Number.isNaN(d.getTime()))return raw;const diff=Math.max(0,(Date.now()-d.getTime())/1000);if(diff<60)return 'Vừa xong';if(diff<3600)return `${Math.floor(diff/60)} phút trước`;if(diff<86400)return `${Math.floor(diff/3600)} giờ trước`;if(diff<604800)return `${Math.floor(diff/86400)} ngày trước`;return d.toLocaleDateString('vi-VN'); }
  const notificationIcon = x => x==='warning'?'fa-clock':x==='success'?'fa-bookmark':'fa-bell';
  let notificationRows=[];
  async function loadNotificationCenter(){
    const list=$('readerNotificationList'); if(!list)return;list.innerHTML='<div class="reader-empty-state"><i class="fa-solid fa-spinner fa-spin"></i><strong>Đang tải thông báo</strong></div>';
    try{const data=await apiGet('notifications');notificationRows=data.rows||[];renderNotifications('all');const b=$('notificationBadge');if(b){b.textContent=data.unread||0;b.hidden=!data.unread}}catch(e){list.innerHTML=`<div class="reader-empty-state"><i class="fa-solid fa-circle-exclamation"></i><strong>Không tải được thông báo</strong><span>${esc(e.message)}</span></div>`}
  }
  function renderNotifications(filter='all'){
    const list=$('readerNotificationList');if(!list)return;let rows=notificationRows;if(filter==='unread')rows=rows.filter(x=>!+x.DaDoc);else if(filter==='warning')rows=rows.filter(x=>String(x.Loai||'').toLowerCase()==='warning');
    list.innerHTML=rows.map(x=>`<article class="reader-notification ${esc(String(x.Loai||'info').toLowerCase())} ${+x.DaDoc?'':'unread'}" data-notification-id="${+x.MaThongBao||0}" data-notification-page="${esc(x.DuongDan||'')}"><span class="reader-notification-icon"><i class="fa-solid ${notificationIcon(String(x.Loai||'').toLowerCase())}"></i></span><div class="reader-notification-copy"><strong>${esc(x.TieuDe||'Thông báo')}</strong><p>${esc(x.NoiDung||'')}</p><small>${esc(timeAgo(x.NgayTao))}</small></div>${+x.DaDoc?'':'<span class="reader-unread-dot"></span>'}</article>`).join('')||'<div class="reader-empty-state"><i class="fa-regular fa-bell-slash"></i><strong>Không có thông báo ở đây</strong><span>Mọi cập nhật quan trọng sẽ xuất hiện tại trung tâm này.</span></div>';
    list.querySelectorAll('[data-notification-id]').forEach(n=>n.onclick=async()=>{const id=+n.dataset.notificationId;try{if(n.classList.contains('unread'))await apiPost('notification_read',{id});n.classList.remove('unread');const page=n.dataset.notificationPage;if(page){closeNotifications();loadPage(page,page.includes('muontra')?'Mượn sách':page.includes('sach')?'Sách':'')}loadNotificationCenter()}catch(e){libraryToast(e.message,'error')}});
  }
  function openNotifications(){if(!isReader){loadPage('nangcao.php','Thư viện thông minh');setTimeout(()=>document.querySelector('[data-tab="notifications"]')?.click(),350);return}$('readerNotificationCenter')?.classList.add('show');document.body.classList.add('drawer-open');loadNotificationCenter()}
  function closeNotifications(){$('readerNotificationCenter')?.classList.remove('show');document.body.classList.remove('drawer-open')}
  window.openNotificationCenter=openNotifications;
  $('readerNotificationClose')?.addEventListener('click',closeNotifications);$('readerNotificationCenter')?.addEventListener('click',e=>{if(e.target.classList.contains('reader-sheet-backdrop'))closeNotifications()});
  $('readerMarkAllRead')?.addEventListener('click',async()=>{try{await apiPost('notification_read_all');libraryToast('Đã đánh dấu tất cả thông báo là đã đọc.');loadNotificationCenter()}catch(e){libraryToast(e.message,'error')}});
  document.querySelectorAll('[data-notif-filter]').forEach(b=>b.onclick=()=>{document.querySelectorAll('[data-notif-filter]').forEach(x=>x.classList.toggle('active',x===b));renderNotifications(b.dataset.notifFilter)});

  // preferences
  function applyPrefs(){const p=JSON.parse(localStorage.getItem('reader_prefs')||'{}');document.body.classList.remove('reader-accent-violet','reader-accent-mint','reader-font-sm','reader-font-lg','reader-density-compact','reader-density-comfort');if(p.accent==='violet')document.body.classList.add('reader-accent-violet');if(p.accent==='mint')document.body.classList.add('reader-accent-mint');if(p.font==='sm')document.body.classList.add('reader-font-sm');if(p.font==='lg')document.body.classList.add('reader-font-lg');if(p.density==='compact')document.body.classList.add('reader-density-compact');if(p.density==='comfort')document.body.classList.add('reader-density-comfort');document.querySelectorAll('[data-reader-pref]').forEach(b=>b.classList.toggle('active',p[b.dataset.readerPref]===b.dataset.value || (!p[b.dataset.readerPref]&&b.dataset.value==='default')))}
  function openPrefs(){$('readerPreferences')?.classList.add('show');document.body.classList.add('drawer-open');applyPrefs()}
  function closePrefs(){$('readerPreferences')?.classList.remove('show');document.body.classList.remove('drawer-open')}
  $('readerPrefsBtn')?.addEventListener('click',openPrefs);$('readerPrefsClose')?.addEventListener('click',closePrefs);$('readerPreferences')?.addEventListener('click',e=>{if(e.target.classList.contains('reader-sheet-backdrop'))closePrefs()});
  document.querySelectorAll('[data-reader-pref]').forEach(b=>b.onclick=()=>{const p=JSON.parse(localStorage.getItem('reader_prefs')||'{}');p[b.dataset.readerPref]=b.dataset.value;localStorage.setItem('reader_prefs',JSON.stringify(p));applyPrefs();libraryToast('Đã áp dụng tùy chỉnh giao diện.','success','Giao diện')});applyPrefs();

  // onboarding
  if(isReader && !localStorage.getItem('reader_onboarding_v22'))setTimeout(()=>$('readerOnboarding')?.classList.add('show'),750);
  function finishOnboarding(target){localStorage.setItem('reader_onboarding_v22','1');$('readerOnboarding')?.classList.remove('show');if(target)loadPage(target,target==='sach.php'?'Sách':'Trang chủ')}
  $('readerOnboardingSkip')?.addEventListener('click',()=>finishOnboarding());$('readerOnboardingStart')?.addEventListener('click',()=>finishOnboarding('sach.php'));

  // install PWA
  let installPrompt=null;window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();installPrompt=e;if(isReader&&!localStorage.getItem('reader_install_dismissed'))setTimeout(()=>$('readerInstallBanner')?.classList.add('show'),1600)});$('readerInstallBtn')?.addEventListener('click',async()=>{if(!installPrompt)return;installPrompt.prompt();await installPrompt.userChoice;installPrompt=null;$('readerInstallBanner')?.classList.remove('show')});$('readerInstallDismiss')?.addEventListener('click',()=>{localStorage.setItem('reader_install_dismissed','1');$('readerInstallBanner')?.classList.remove('show')});
  if('serviceWorker' in navigator)window.addEventListener('load',()=>navigator.serviceWorker.register('service-worker.js').catch(()=>{}));

  // enrich command palette with recent searches
  window.readerSaveRecentSearch=function(item){try{const list=JSON.parse(localStorage.getItem('library_recent_searches')||'[]').filter(x=>x.title!==item.title);list.unshift(item);localStorage.setItem('library_recent_searches',JSON.stringify(list.slice(0,6)))}catch(_){}};
})();
