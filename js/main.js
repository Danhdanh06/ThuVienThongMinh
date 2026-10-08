
window.addEventListener('pageshow', event => {
    if (event.persisted) window.location.reload();
});
window.libraryApi = {
    async get(action, params = {}) {
        return this.request(action, { method: 'GET' }, params);
    },

    async post(action, data = {}) {
        return this.request(action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
    },

    async request(action, options, params = {}) {
        const url = new URL('api.php', window.location.href);
        url.searchParams.set('action', action);
        Object.entries(params || {}).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') url.searchParams.set(key, value);
        });

        const response = await fetch(url.href, {
            cache: 'no-store',
            credentials: 'same-origin',
            ...options
        });

        let payload;
        try {
            payload = await response.json();
        } catch {
            throw new Error(`Máy chủ trả về dữ liệu không hợp lệ (HTTP ${response.status}).`);
        }

        if (response.status === 401) {
            window.location.href = 'dangnhap.php';
            throw new Error(payload.message || 'Phiên đăng nhập đã hết hạn.');
        }

        if (!response.ok || !payload.ok) {
            throw new Error(payload.message || 'Có lỗi xảy ra khi xử lý dữ liệu.');
        }

        return payload;
    }
};

window.escapeHTML = function escapeHTML(value = '') {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
};

window.formatLibraryDate = function formatLibraryDate(value) {
    if (!value) return '--';
    const parts = String(value).split('-');
    if (parts.length !== 3) return value;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
};

window.normalizeLibraryText = function normalizeLibraryText(value = '') {
    return String(value).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
};
async function restoreLegacyDataIfNeeded() {
    if (!window.libraryAccess?.canRestoreData) return;
    try {
        const statusPayload = await libraryApi.get('legacy_status');
        const status = statusPayload.data || {};
        if (Number(status.books || 0) > 0 && Number(status.loans || 0) > 0) return;

        let legacyBooks = [];
        let legacyLoans = [];
        try {
            const rawBooks = JSON.parse(localStorage.getItem('library_books_v1') || '[]');
            if (Array.isArray(rawBooks)) legacyBooks = rawBooks;
        } catch (_) {}
        try {
            const rawLoans = JSON.parse(localStorage.getItem('library_loans_v1') || '[]');
            if (Array.isArray(rawLoans)) legacyLoans = rawLoans;
        } catch (_) {}
        if (legacyBooks.length || legacyLoans.length) {
            const result = await libraryApi.post('legacy_import', {
                books: legacyBooks,
                loans: legacyLoans
            });
            console.info(result.message || 'Đã khôi phục dữ liệu cũ vào MySQL.');
        } else {
            const result = await libraryApi.post('legacy_seed', {});
            console.info(result.message || 'Đã khôi phục dữ liệu mẫu cũ vào MySQL.');
        }
    } catch (error) {
        console.warn('Không thể tự động khôi phục dữ liệu cũ:', error);
    }
}
function attachDynamicInteractions() {
    document.querySelectorAll('.card, .dashboard-card').forEach(card => {
        if (card.dataset.hoverBound) return;
        card.dataset.hoverBound = '1';
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-4px)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
}

const adminInfo = document.querySelector('.admin-info');
const dropdown = document.querySelector('.dropdown');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const menuBtn = document.getElementById('menuBtn');

if (adminInfo && dropdown) {
    adminInfo.addEventListener('click', () => dropdown.classList.toggle('show'));
    window.addEventListener('click', event => {
        if (!event.target.closest('.admin-menu')) dropdown.classList.remove('show');
    });
}

if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        sidebarOverlay?.classList.toggle('show');
    });
}

sidebarOverlay?.addEventListener('click', () => {
    sidebar?.classList.remove('open');
    sidebarOverlay.classList.remove('show');
});

document.querySelectorAll('.sidebar ul li a').forEach(link => {
    link.addEventListener('click', () => {
        sidebar?.classList.remove('open');
        sidebarOverlay?.classList.remove('show');
    });
});

const pageCssMap = {
    trangchu: 'css/trangchu.css',
    thongtinthuvien: 'css/thongtinthuvien.css',
    sach: 'css/sach.css',
    theloai: 'css/theloai.css',
    docgia: 'css/docgia.css',
    muontra: 'css/muontra.css',
    nhanvien: 'css/nhanvien.css',
    thongke: 'css/thongke.css',
    caidat: 'css/caidat.css',
    dangmuon: 'css/dangmuon.css',
    quahan: 'css/quahan.css',
    docgia2: 'css/docgia2.css',
    xemtatca: 'css/trangchu.css',
    calamviec: 'css/calamviec.css',
    nangcao: 'css/nangcao.css'
};

const statisticChildPages = new Set(['dangmuon', 'quahan', 'docgia2']);
const homeChildPages = new Set(['xemtatca']);

function normalizePageName(page) {
    return page.split('?')[0].replace(/^page\//, '').replace(/\.php$/i, '');
}

function getPageTitle(page, title) {
    if (title) return title;
    const titles = {
        trangchu: 'Trang chủ', thongtinthuvien: 'Thông tin thư viện', sach: 'Sách', theloai: 'Thể loại', docgia: 'Độc giả',
        muontra: 'Mượn - Trả', nhanvien: 'Nhân viên', thongke: 'Thống kê', caidat: 'Cài đặt',
        dangmuon: 'Đang mượn', quahan: 'Quá hạn', docgia2: 'Thống kê độc giả', xemtatca: 'Xem tất cả', calamviec: 'Ca làm việc', nangcao: 'Thư viện thông minh'
    };
    return titles[normalizePageName(page)] || 'Trang chủ';
}

function removePageScript() {
    document.querySelectorAll('script[data-page-script]').forEach(script => script.remove());
}

function loadPageScript(pageName) {
    removePageScript();
    const scriptUrl = new URL(`js/${pageName}.js`, window.location.href);
    scriptUrl.searchParams.set('v', Date.now());
    const script = document.createElement('script');
    script.src = scriptUrl.href;
    script.dataset.pageScript = pageName;
    script.onload = attachDynamicInteractions;
    script.onerror = () => console.warn(`Không thể load JS: ${scriptUrl.href}`);
    document.body.appendChild(script);
}

function loadPageCSS(pageName) {
    document.getElementById('pageCSS')?.remove();
    const cssFile = pageCssMap[pageName];
    if (!cssFile) return Promise.resolve();

    return new Promise(resolve => {
        const cssUrl = new URL(cssFile, window.location.href);
        cssUrl.searchParams.set('v', Date.now());
        const css = document.createElement('link');
        css.id = 'pageCSS';
        css.rel = 'stylesheet';
        css.href = cssUrl.href;
        css.onload = resolve;
        css.onerror = resolve;
        document.head.appendChild(css);
    });
}

function setSidebarActive(pageName) {
    document.querySelectorAll('.sidebar ul li').forEach(item => item.classList.remove('active'));
    const employeeOverdue = pageName === 'quahan' && window.libraryAccess?.roleKey === 'employee';
    const activePage = employeeOverdue ? 'trangchu' : (statisticChildPages.has(pageName) ? 'thongke' : (homeChildPages.has(pageName) ? 'trangchu' : pageName));
    document.querySelectorAll('.sidebar ul li a').forEach(link => {
        if ((link.getAttribute('onclick') || '').includes(`${activePage}.php`)) {
            link.parentElement?.classList.add('active');
        }
    });
}

window.openSmartTab = function openSmartTab(tab, title = 'Thư viện thông minh') {
    window.__smartTargetTab = tab;
    loadPage('nangcao.php', title);
};

window.loadPage = function loadPage(page, title) {
    const pagePath = page.startsWith('page/') ? page : `page/${page}`;
    const pageName = normalizePageName(page);
    const pageUrl = new URL(pagePath, window.location.href).href;
    const content = document.getElementById('content');

    if (content) {
        content.innerHTML = `<div class="library-page-skeleton" aria-label="Đang tải"><div class="sk-hero sk-shimmer"></div><div class="sk-row"><div class="sk-card sk-shimmer"></div><div class="sk-card sk-shimmer"></div><div class="sk-card sk-shimmer"></div><div class="sk-card sk-shimmer"></div></div><div class="sk-panel sk-shimmer"></div></div>`;
    }

    fetch(pageUrl, { cache: 'no-store', credentials: 'same-origin' })
        .then(response => {
            if (response.status === 401 || response.redirected && response.url.includes('dangnhap.php')) {
                window.location.href = 'dangnhap.php';
                throw new Error('Phiên đăng nhập đã hết hạn.');
            }
            if (response.status === 403) return response.text().then(text => { throw new Error(text || 'Bạn không có quyền truy cập trang này.'); });
            if (!response.ok) throw new Error(`Không thể tải trang (HTTP ${response.status}).`);
            return response.text();
        })
        .then(html => {
            if (!content) throw new Error('Không tìm thấy #content.');
            content.innerHTML = html;
            const pageTitle = document.getElementById('pageTitle');
            if (pageTitle) pageTitle.textContent = getPageTitle(page, title);
            setSidebarActive(pageName);
            sidebar?.classList.remove('open');
            sidebarOverlay?.classList.remove('show');
            return loadPageCSS(pageName);
        })
        .then(() => loadPageScript(pageName))
        .then(() => { if (pageName === 'nangcao' && window.__smartTargetTab) { const t=window.__smartTargetTab; window.__smartTargetTab=null; setTimeout(()=>document.querySelector(`[data-tab="${t}"]`)?.click(),80); } })
        .catch(error => {
            console.error(error);
            if (content) {
                content.innerHTML = `<div style="padding:30px;background:#fff;border-radius:15px;color:#dc2626"><h3>Không thể tải trang</h3><p>${window.escapeHTML(error.message)}</p></div>`;
            }
        });
};

document.addEventListener('DOMContentLoaded', async () => {
    await restoreLegacyDataIfNeeded();
    const content = document.getElementById('content');
    const page = window.libraryAccess?.defaultPage || 'sach.php';
    const title = window.libraryAccess?.defaultTitle || 'Sách';
    if (content && !content.innerHTML.trim()) loadPage(page, title);
});

// ===== Trải nghiệm hiện đại: tìm toàn hệ thống, command palette, theme, thông báo, mobile nav =====
(() => {
  if (!window.libraryAccess?.isAuthenticated) return;
  const command = document.getElementById('globalCommand');
  const input = document.getElementById('globalSearchInput');
  const results = document.getElementById('globalSearchResults');
  const shortcuts = document.getElementById('commandShortcuts');
  const recentWrap = document.getElementById('commandRecentWrap');
  const recentBox = document.getElementById('commandRecent');
  let timer;
  const role = window.libraryAccess?.roleKey;
  const commands = [
    ['📚 Sách','sach.php','Sách'], ['✨ Thư viện thông minh','nangcao.php','Thư viện thông minh'],
    ...(role==='customer' ? [['❤️ Muốn đọc','nangcao.php','Thư viện thông minh'],['📖 Mượn sách','muontra.php','Mượn sách']] : [['➕ Thêm/Quản lý sách','sach.php','Sách'],['🔄 Mượn - Trả','muontra.php','Mượn - Trả'],['🗓️ Ca làm','calamviec.php','Ca làm việc']])
  ];
  const getRecent=()=>{try{return JSON.parse(localStorage.getItem('library_recent_searches')||'[]')}catch(_){return[]}};
  const saveRecent=item=>{try{const list=getRecent().filter(x=>x.title!==item.title);list.unshift(item);localStorage.setItem('library_recent_searches',JSON.stringify(list.slice(0,6)));window.readerSaveRecentSearch?.(item)}catch(_){}};
  function renderRecent(){const list=getRecent();if(!recentWrap||!recentBox)return;recentWrap.hidden=!list.length;recentBox.innerHTML=list.map((x,i)=>`<button type="button" data-recent-index="${i}"><i class="fa-solid fa-clock-rotate-left"></i> ${escapeHTML(x.title||'')}</button>`).join('')}
  function openCommand(){ if(!command)return; command.classList.add('show'); input.value=''; results.innerHTML=''; shortcuts.innerHTML=commands.map((c,i)=>`<button data-cmd="${i}">${c[0]}</button>`).join('');renderRecent();setTimeout(()=>input.focus(),20); }
  function closeCommand(){command?.classList.remove('show')}
  window.openLibraryCommand=openCommand;
  document.getElementById('globalSearchBtn')?.addEventListener('click',openCommand);
  document.getElementById('globalCommandClose')?.addEventListener('click',closeCommand);
  command?.addEventListener('click',e=>{if(e.target===command)closeCommand()});
  document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();openCommand()} if(e.key==='Escape')closeCommand(); if(e.key==='/'&&!['INPUT','TEXTAREA'].includes(document.activeElement?.tagName)){e.preventDefault();openCommand()}});
  shortcuts?.addEventListener('click',e=>{const b=e.target.closest('[data-cmd]');if(!b)return;const c=commands[+b.dataset.cmd];closeCommand();loadPage(c[1],c[2])});
  recentBox?.addEventListener('click',e=>{const b=e.target.closest('[data-recent-index]');if(!b)return;const item=getRecent()[+b.dataset.recentIndex];if(!item)return;closeCommand();if(item.kind==='category')sessionStorage.setItem('guestBookCategory',String(item.id||''));if(item.kind==='book')sessionStorage.setItem('guestBookSearch',item.title||'');loadPage(item.page||'sach.php',item.page==='sach.php'?'Sách':'')});
  input?.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(async()=>{const q=input.value.trim();if(!q){results.innerHTML='';renderRecent();return}if(recentWrap)recentWrap.hidden=true;results.innerHTML='<div class="global-result"><span>Đang tìm kiếm...</span></div>';try{const u=new URL('advanced_api.php',location.href);u.searchParams.set('action','global_search');u.searchParams.set('q',q);const p=await fetch(u,{credentials:'same-origin',cache:'no-store'}).then(r=>r.json());if(!p.ok)throw new Error(p.message);results.innerHTML=(p.data||[]).map((x,i)=>{const kind=x.kind||'item';const fallback=kind==='category'?'fa-layer-group':kind==='reader'?'fa-user':kind==='employee'?'fa-user-tie':kind==='loan'?'fa-receipt':'fa-book';const visual=x.image?`<img class="global-result-thumb" src="${escapeHTML(x.image)}" alt="">`:`<span class="global-result-icon"><i class="fa-solid ${fallback}"></i></span>`;return `<div class="global-result" data-result-index="${i}"><span class="global-result-main">${visual}<span class="global-result-copy"><b>${escapeHTML(x.title)}</b><small>${escapeHTML(x.subtitle||'')}</small></span></span><i class="fa-solid fa-arrow-right"></i></div>`}).join('')||'<div class="global-result">Không tìm thấy kết quả.</div>';results._items=p.data||[]}catch(e){results.innerHTML=`<div class="global-result">${escapeHTML(e.message)}</div>`}},180)});
  results?.addEventListener('click',e=>{const r=e.target.closest('[data-result-index]');if(!r)return;const item=(results._items||[])[+r.dataset.resultIndex];if(!item)return;saveRecent(item);closeCommand();if(item.kind==='category'){sessionStorage.setItem('guestBookCategory',String(item.id||''));sessionStorage.removeItem('guestBookSearch')}else if(item.kind==='book'){sessionStorage.setItem('guestBookSearch',item.title||'');sessionStorage.removeItem('guestBookCategory')}loadPage(item.page||'sach.php',item.page==='sach.php'?'Sách':'')});

  function applyTheme(mode){const dark=mode==='dark'||(mode==='system'&&matchMedia('(prefers-color-scheme:dark)').matches);document.body.classList.toggle('dark-mode',dark);localStorage.setItem('library_theme',mode);const i=document.querySelector('#themeToggle i');if(i)i.className=dark?'fa-solid fa-sun':'fa-solid fa-moon'}
  const saved=localStorage.getItem('library_theme')||'system';applyTheme(saved);document.getElementById('themeToggle')?.addEventListener('click',()=>{const cur=localStorage.getItem('library_theme')||'system';const next=cur==='system'?'light':cur==='light'?'dark':'system';applyTheme(next);const b=document.getElementById('themeToggle');if(b)b.title='Giao diện: '+(next==='system'?'Theo hệ thống':next==='light'?'Sáng':'Tối')});

  async function refreshNotifications(){try{const u=new URL('advanced_api.php',location.href);u.searchParams.set('action','notifications');const p=await fetch(u,{credentials:'same-origin',cache:'no-store'}).then(r=>r.json());if(!p.ok)return;const b=document.getElementById('notificationBadge'),n=+p.data.unread||0;if(b){b.textContent=n;b.hidden=n===0}}catch(_){}}
  window.refreshLibraryNotifications=refreshNotifications; refreshNotifications(); setInterval(refreshNotifications,60000);
  document.getElementById('notificationBell')?.addEventListener('click',()=>{if(role==='customer'&&window.openNotificationCenter)window.openNotificationCenter();else{loadPage('nangcao.php','Thư viện thông minh');setTimeout(()=>document.querySelector('[data-tab="notifications"]')?.click(),350)}});
  document.getElementById('mobileNotify')?.addEventListener('click',()=>document.getElementById('notificationBell')?.click());
  document.querySelectorAll('[data-mobile-page]').forEach(b=>b.addEventListener('click',()=>loadPage(b.dataset.mobilePage,b.dataset.title||'')));
})();

