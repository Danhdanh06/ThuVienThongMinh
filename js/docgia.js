(() => {
    const canManageReaders = !!window.libraryAccess?.canManageReaders;
    const canDeleteReaders = !!window.libraryAccess?.canDeleteReaders;
    const isCustomer = window.libraryAccess?.roleKey === 'customer';
    let readers = [];
    let selectedId = null;
    let busy = false;
    let readerLoans = [];
    let readerView = "table";
    let readerChip = "all";
    const el = id => document.getElementById(id);

    function notify(message) { if(window.libraryToast) window.libraryToast(message); else alert(message); }

    async function advancedGet(action, params = {}) {
        const url = new URL('advanced_api.php', location.href);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([key, value]) => { if (value !== undefined && value !== null && value !== '') url.searchParams.set(key, value); });
        const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store'});
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.ok === false) throw new Error(payload.message || 'Không tải được dữ liệu thẻ.');
        return payload.data;
    }

    function readerCode(id) { return `DG${String(id).padStart(3, '0')}`; }
    function qrUrl(code, size = 220) { return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=8&data=${encodeURIComponent(code)}`; }

    async function openReaderCard(id) {
        const modal = el('readerCardModal');
        const box = el('readerCardContent');
        if (!modal || !box || !id) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        box.classList.remove('qr-zoomed');
        box.innerHTML = '<div class="reader-card-loading"><i class="fa-solid fa-spinner fa-spin"></i>&nbsp; Đang tải thẻ thư viện...</div>';
        try {
            const r = await advancedGet('reader_card', {readerId: id});
            const code = readerCode(r.MaDocGia);
            box.innerHTML = `<div class="digital-reader-card" id="printableReaderCard">
                <div class="reader-card-info">
                    <div class="reader-card-brand">THẺ THƯ VIỆN ĐIỆN TỬ</div>
                    <div class="reader-card-name">${escapeHTML(r.HoTen || 'Độc giả')}</div>
                    <div class="reader-card-code">${code}</div>
                    <div class="reader-card-meta">
                        <div><span>Số điện thoại</span><b>${escapeHTML(r.SDT || '--')}</b></div>
                        <div><span>Email</span><b>${escapeHTML(r.Email || '--')}</b></div>
                        <div><span>Ngày đăng ký</span><b>${formatLibraryDate(r.NgayDangKy)}</b></div>
                        <div><span>Tổng lượt mượn</span><b>${Number(r.TongLuotMuon || 0)}</b></div>
                    </div>
                    <div class="reader-card-status">${escapeHTML(r.TrangThai || 'Đang hoạt động')}</div>
                </div>
                <div class="reader-card-qr">
                    <img src="${qrUrl(code)}" alt="QR ${code}">
                    <small>${code}</small>
                </div>
            </div>`;
            modal.dataset.readerId = String(id);
            modal.dataset.readerCode = code;
        } catch (error) {
            box.innerHTML = `<div class="reader-card-loading" style="color:#b91c1c">${escapeHTML(error.message)}</div>`;
        }
    }

    function closeReaderCard() {
        const modal = el('readerCardModal');
        modal?.classList.remove('show');
        modal?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        el('readerCardContent')?.classList.remove('qr-zoomed');
    }

    function printReaderCard() {
        const card = document.getElementById('printableReaderCard');
        if (!card) return;
        const popup = window.open('', '_blank', 'width=900,height=650');
        if (!popup) return notify('Trình duyệt đang chặn cửa sổ in. Hãy cho phép popup rồi thử lại.');
        popup.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Thẻ thư viện</title><style>
          *{box-sizing:border-box}body{font-family:Arial,sans-serif;margin:0;padding:35px;background:#fff}.digital-reader-card{position:relative;overflow:hidden;border-radius:22px;padding:26px;background:linear-gradient(135deg,#143a8f,#2563eb 55%,#4f7df5);color:#fff;display:grid;grid-template-columns:1fr 210px;gap:24px;max-width:760px;margin:auto;-webkit-print-color-adjust:exact;print-color-adjust:exact}.reader-card-brand{font-size:12px;letter-spacing:1.4px;font-weight:700}.reader-card-name{font-size:26px;font-weight:700;margin:14px 0 4px}.reader-card-code{display:inline-block;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.25);border-radius:999px;padding:6px 12px;margin:6px 0 16px;font-weight:700}.reader-card-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px 18px;font-size:13px}.reader-card-meta span{opacity:.8;display:block;font-size:11px;margin-bottom:2px}.reader-card-status{display:inline-block;margin-top:16px;padding:6px 10px;border-radius:999px;background:#dcfce7;color:#166534;font-weight:700;font-size:12px}.reader-card-qr{display:grid;place-items:center;align-content:center;background:#fff;border-radius:18px;padding:14px;color:#0f172a}.reader-card-qr img{width:178px;height:178px}.reader-card-qr small{margin-top:8px;font-weight:700}@page{size:A4;margin:15mm}
        </style></head><body>${card.outerHTML}<script>window.onload=()=>{window.print()}<\/script></body></html>`);
        popup.document.close();
    }

    async function advancedGet(action, params={}){const u=new URL('advanced_api.php',location.href);u.searchParams.set('action',action);Object.entries(params).forEach(([k,v])=>u.searchParams.set(k,v));const r=await fetch(u,{credentials:'same-origin',cache:'no-store'});const p=await r.json();if(!r.ok||!p.ok)throw new Error(p.message||'Không tải được hồ sơ');return p.data}
    function profileHtml(d){const s=d.summary||{};return `<div class="reader-profile-metrics"><div><b>${s.SoCuonNam||0}</b><span>Cuốn năm nay</span></div><div><b>${s.LuotMuonNam||0}</b><span>Lượt mượn</span></div><div><b>${s.TyLeDungHan??0}%</b><span>Trả đúng hạn</span></div><div><b>${escapeHTML(d.favorite?.TenTheLoai||'--')}</b><span>Thể loại nhiều nhất</span></div><div><b>${escapeHTML(d.bestMonth?.Thang||'--')}</b><span>Tháng đọc nhiều nhất</span></div></div><div class="reader-badges">${(d.badges||[]).map(x=>`<span>${escapeHTML(x)}</span>`).join('')||'<span>Chưa mở huy hiệu</span>'}</div>${d.history?`<h4>Lịch sử đọc gần đây</h4><div class="reader-history">${d.history.map(x=>`<div><b>PM${String(x.MaPhieuMuon).padStart(3,'0')}</b><span>${escapeHTML(x.Sach||'--')}</span><small>${formatLibraryDate(x.NgayMuon)} → ${x.NgayTra?formatLibraryDate(x.NgayTra):'Đang mượn'}</small></div>`).join('')||'Chưa có lịch sử.'}</div>`:''}`}
    async function openReaderProfile(id){const modal=el('readerProfileModal'),box=el('readerProfileContent');if(!modal||!box)return;modal.classList.add('show');box.innerHTML='Đang tải hồ sơ đọc...';try{box.innerHTML=profileHtml(await advancedGet('reading_profile_reader',{readerId:id}))}catch(e){box.innerHTML=escapeHTML(e.message)}}
    function closeReaderProfile(){el('readerProfileModal')?.classList.remove('show')}
    async function loadSelfProfile(){const box=el('readerSelfProfile');if(!box)return;try{box.innerHTML=profileHtml(await advancedGet('reading_profile'))}catch(e){box.innerHTML=escapeHTML(e.message)}}

    function refreshActionButtons() {
        const editing = selectedId !== null;
        if (!canManageReaders) return;
        const saveBtn = el('readerSaveBtn');
        const updateBtn = el('readerUpdateBtn');
        const deleteBtn = el('readerDeleteBtn');
        const resetBtn = el('readerResetBtn');
        const addBtn = el('addReaderBtn');
        const cardBtn = el('readerCardBtn');
        const profileBtn = el('readerProfileBtn');
        if (saveBtn) saveBtn.disabled = busy || editing;
        if (updateBtn) updateBtn.disabled = busy || !editing;
        if (deleteBtn) deleteBtn.disabled = busy || !editing || !canDeleteReaders;
        if (resetBtn) resetBtn.disabled = busy;
        if (addBtn) addBtn.disabled = busy;
        if (cardBtn) cardBtn.disabled = busy || !editing;
        if (profileBtn) profileBtn.disabled = busy || !editing;
    }

    function setBusy(value) {
        busy = value;
        refreshActionButtons();
    }

    function getForm() {
        return {
            id: selectedId,
            name: el('readerName').value.trim(),
            gender: el('readerGender').value,
            birthDate: el('readerBirthDate').value || null,
            phone: el('readerPhone').value.trim(),
            email: el('readerEmail').value.trim(),
            address: el('readerAddress').value.trim(),
            registerDate: el('readerRegisterDate').value || new Date().toLocaleDateString('en-CA'),
            status: el('readerStatus').value
        };
    }

    function validate(data) {
        if (!data.name) return 'Vui lòng nhập họ tên độc giả.';
        if (data.phone && !/^[0-9+ .()-]{8,20}$/.test(data.phone)) return 'Số điện thoại không hợp lệ.';
        if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) return 'Email không hợp lệ.';
        if (data.birthDate && data.birthDate > new Date().toLocaleDateString('en-CA')) return 'Ngày sinh không thể ở tương lai.';
        return '';
    }

    function resetForm() {
        selectedId = null;
        ['readerId','readerName','readerAddress','readerPhone','readerBirthDate','readerEmail'].forEach(id => {
            if (el(id)) el(id).value = '';
        });
        if (el('readerGender')) el('readerGender').value = 'Nam';
        if (el('readerStatus')) el('readerStatus').value = 'Đang hoạt động';
        if (el('readerRegisterDate')) el('readerRegisterDate').value = new Date().toLocaleDateString('en-CA');
        document.querySelectorAll('#readerTableBody tr').forEach(row => row.classList.remove('selected-row'));
        refreshActionButtons();
    }

    function openReaderForm() {
        const modal = el('readerFormModal');
        modal?.classList.add('show');
        modal?.setAttribute('aria-hidden','false');
        document.body.classList.add('reader-form-open');
    }
    function closeReaderForm() {
        const modal = el('readerFormModal');
        modal?.classList.remove('show');
        modal?.setAttribute('aria-hidden','true');
        document.body.classList.remove('reader-form-open');
    }
    function selectReader(id) {
        if (!canManageReaders) return;
        const r = readers.find(x => Number(x.MaDocGia) === Number(id));
        if (!r) return;
        selectedId = Number(r.MaDocGia);
        el('readerId').value = readerCode(r.MaDocGia);
        el('readerName').value = r.HoTen || '';
        el('readerGender').value = r.GioiTinh || 'Nam';
        el('readerBirthDate').value = r.NgaySinh || '';
        el('readerPhone').value = r.SDT || '';
        el('readerEmail').value = r.Email || '';
        el('readerAddress').value = r.DiaChi || '';
        el('readerRegisterDate').value = r.NgayDangKy || '';
        el('readerStatus').value = r.TrangThai || 'Đang hoạt động';
        if (el('readerFormTitle')) el('readerFormTitle').textContent = 'Chỉnh sửa độc giả';
        refreshActionButtons();
        openReaderForm();
    }

    function initials(name='') {
        const parts = String(name).trim().split(/\s+/).filter(Boolean);
        return (parts.length ? (parts[0][0] + (parts.length > 1 ? parts[parts.length-1][0] : '')) : 'ĐG').toUpperCase();
    }
    function recentWithin30(date) {
        if (!date) return false;
        const d = new Date(date + 'T00:00:00');
        const now = new Date();
        return !isNaN(d) && (now - d) >= 0 && (now - d) <= 30*86400000;
    }
    function loanStatsForReader(id) {
        const mine = readerLoans.filter(l => Number(l.MaDocGia) === Number(id));
        let total = mine.length, active = 0, overdue = 0;
        const today = new Date().toLocaleDateString('en-CA');
        mine.forEach(l => {
            const returned = !!l.NgayTra || l.TrangThai === 'Đã trả';
            if (!returned && l.TrangThai !== 'Chờ duyệt') active++;
            if (!returned && l.HanTra && l.HanTra < today) overdue++;
        });
        return {total,active,overdue};
    }
    function statusBadge(r) {
        const st = String(r.TrangThai || 'Đang hoạt động');
        const cls = st === 'Đang hoạt động' ? 'active' : st === 'Khóa' ? 'locked' : 'inactive';
        return `<span class="readerx-status ${cls}"><i class="fa-solid ${cls==='active'?'fa-circle-check':cls==='locked'?'fa-lock':'fa-circle-pause'}"></i>${escapeHTML(st)}</span>`;
    }
    function filtered() {
        const q = normalizeLibraryText(el('readerSearch')?.value || '');
        const gender = el('readerGenderFilter')?.value || '';
        const status = el('readerStatusFilter')?.value || '';
        const date = el('readerDateFilter')?.value || '';
        let data = readers.filter(r => {
            const text = normalizeLibraryText(`${r.MaDocGia} ${r.HoTen} ${r.Email || ''} ${r.SDT || ''} ${r.DiaChi || ''}`);
            const stats = loanStatsForReader(r.MaDocGia);
            const chipOk = readerChip === 'all' ||
                (readerChip === 'active' && r.TrangThai === 'Đang hoạt động') ||
                (readerChip === 'inactive' && r.TrangThai !== 'Đang hoạt động') ||
                (readerChip === 'recent' && recentWithin30(r.NgayDangKy)) ||
                (readerChip === 'top' && stats.total > 0);
            return (!q || text.includes(q)) && (!gender || r.GioiTinh === gender) && (!status || r.TrangThai === status) && (!date || r.NgayDangKy === date) && chipOk;
        });
        const sort = el('readerSort')?.value || 'newest';
        if (sort === 'az') data.sort((a,b)=>String(a.HoTen||'').localeCompare(String(b.HoTen||''),'vi'));
        else if (sort === 'borrowed') data.sort((a,b)=>loanStatsForReader(b.MaDocGia).total-loanStatsForReader(a.MaDocGia).total || Number(b.MaDocGia)-Number(a.MaDocGia));
        else data.sort((a,b)=>Number(b.MaDocGia)-Number(a.MaDocGia));
        if (readerChip === 'top') data = data.sort((a,b)=>loanStatsForReader(b.MaDocGia).total-loanStatsForReader(a.MaDocGia).total).slice(0,8);
        return data;
    }
    function bindReaderActions(scope) {
        scope.querySelectorAll('[data-reader-view-detail]').forEach(btn=>btn.addEventListener('click',()=>openReaderProfile(Number(btn.dataset.readerViewDetail))));
        scope.querySelectorAll('[data-card]').forEach(btn => btn.addEventListener('click', () => openReaderCard(Number(btn.dataset.card))));
        scope.querySelectorAll('[data-profile]').forEach(btn => btn.addEventListener('click', () => openReaderProfile(Number(btn.dataset.profile))));
        if (canManageReaders) {
            scope.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', () => selectReader(btn.dataset.edit)));
            scope.querySelectorAll('[data-delete]').forEach(btn => btn.addEventListener('click', () => removeReader(Number(btn.dataset.delete))));
        }
    }
    function renderDashboardExtras() {
        const total=readers.length, active=readers.filter(r=>r.TrangThai==='Đang hoạt động').length, inactive=total-active, recent=readers.filter(r=>recentWithin30(r.NgayDangKy)).length;
        [['readerMetricTotal',total],['readerMetricActive',active],['readerMetricInactive',inactive],['readerMetricRecent',recent],['readerHeroTotal',total],['readerHeroActive',active],['readerHeroRecent',recent]].forEach(([id,v])=>{const n=el(id);if(n)n.textContent=Number(v).toLocaleString('vi-VN')});
        const top=[...readers].sort((a,b)=>loanStatsForReader(b.MaDocGia).total-loanStatsForReader(a.MaDocGia).total)[0];
        const newest=[...readers].sort((a,b)=>String(b.NgayDangKy||'').localeCompare(String(a.NgayDangKy||'')))[0];
        const overdue=[...readers].sort((a,b)=>loanStatsForReader(b.MaDocGia).overdue-loanStatsForReader(a.MaDocGia).overdue)[0];
        const locked=readers.find(r=>r.TrangThai && r.TrangThai!=='Đang hoạt động');
        const cards=[
            top&&{label:'Mượn nhiều nhất',icon:'fa-crown',r:top,value:`${loanStatsForReader(top.MaDocGia).total} phiếu`},
            newest&&{label:'Mới đăng ký',icon:'fa-user-plus',r:newest,value:formatLibraryDate(newest.NgayDangKy)},
            overdue&&loanStatsForReader(overdue.MaDocGia).overdue>0&&{label:'Đang có quá hạn',icon:'fa-triangle-exclamation',r:overdue,value:`${loanStatsForReader(overdue.MaDocGia).overdue} phiếu`},
            locked&&{label:'Cần chú ý',icon:'fa-user-lock',r:locked,value:locked.TrangThai}
        ].filter(Boolean);
        const spot=el('readerSpotlightGrid'); if(spot) spot.innerHTML=cards.length?cards.map(c=>`<button class="readerx-spot-card" data-reader-view-detail="${c.r.MaDocGia}"><span class="readerx-avatar">${initials(c.r.HoTen)}</span><span><small><i class="fa-solid ${c.icon}"></i> ${c.label}</small><strong>${escapeHTML(c.r.HoTen||'--')}</strong><em>${escapeHTML(c.value)}</em></span></button>`).join(''):'<div class="readerx-empty">Chưa có dữ liệu nổi bật.</div>';
        if(spot) bindReaderActions(spot);
        const genders={Nam:0,Nữ:0,Khác:0};readers.forEach(r=>genders[r.GioiTinh] = (genders[r.GioiTinh]||0)+1);
        const donut=el('readerGenderDonut'); if(donut){const t=Math.max(1,total),m=genders.Nam/t*100,n=genders.Nữ/t*100;donut.style.background=`conic-gradient(#2563eb 0 ${m}%,#ec4899 ${m}% ${m+n}%,#8b5cf6 ${m+n}% 100%)`;}
        if(el('readerGenderMain')) el('readerGenderMain').textContent=total;
        if(el('readerGenderLegend')) el('readerGenderLegend').innerHTML=`<span><b style="background:#2563eb"></b>Nam ${genders.Nam}</span><span><b style="background:#ec4899"></b>Nữ ${genders.Nữ}</span><span><b style="background:#8b5cf6"></b>Khác ${genders.Khác}</span>`;
        const areas={};readers.forEach(r=>{const a=(r.DiaChi||'Chưa rõ').trim();areas[a]=(areas[a]||0)+1});const areaRows=Object.entries(areas).sort((a,b)=>b[1]-a[1]).slice(0,5),mx=Math.max(1,...areaRows.map(x=>x[1]));if(el('readerAreaStats')) el('readerAreaStats').innerHTML=areaRows.map(([a,c])=>`<div><span>${escapeHTML(a)}</span><b>${c}</b><i><u style="width:${c/mx*100}%"></u></i></div>`).join('');
        const months={};readers.forEach(r=>{if(r.NgayDangKy){const k=r.NgayDangKy.slice(0,7);months[k]=(months[k]||0)+1}});const monthRows=Object.entries(months).sort((a,b)=>a[0].localeCompare(b[0])).slice(-6),mm=Math.max(1,...monthRows.map(x=>x[1]));if(el('readerMonthStats')) el('readerMonthStats').innerHTML=monthRows.map(([m,c])=>`<div><i style="height:${Math.max(12,c/mm*90)}px"></i><b>${c}</b><span>${m.slice(5)}/${m.slice(2,4)}</span></div>`).join('');
    }
    function render() {
        const data = filtered();
        const body = el('readerTableBody');
        if (body) {
            body.innerHTML = data.length ? data.map((r,i)=>{const st=loanStatsForReader(r.MaDocGia);return `<tr><td>${i+1}</td><td><div class="readerx-person"><span class="readerx-avatar">${initials(r.HoTen)}</span><span><strong>${escapeHTML(r.HoTen||'--')}</strong><small>${readerCode(r.MaDocGia)} · ${escapeHTML(r.SDT||'--')}</small></span></div></td><td>${escapeHTML(r.GioiTinh||'--')}</td><td>${formatLibraryDate(r.NgaySinh)}</td><td><div class="readerx-contact"><span>${escapeHTML(r.SDT||'--')}</span><small>${escapeHTML(r.Email||'--')}</small></div></td><td>${escapeHTML(r.DiaChi||'--')}</td><td>${formatLibraryDate(r.NgayDangKy)}</td><td><div class="readerx-status-stack">${statusBadge(r)}${st.overdue?`<span class="readerx-subbadge overdue">${st.overdue} quá hạn</span>`:st.active?`<span class="readerx-subbadge borrowing">${st.active} đang mượn</span>`:''}</div></td>${canManageReaders?`<td><div class="readerx-actions-cell"><button data-profile="${r.MaDocGia}" title="Hồ sơ đọc"><i class="fa-solid fa-eye"></i></button><button data-card="${r.MaDocGia}" title="Thẻ QR"><i class="fa-solid fa-id-card"></i></button><button data-edit="${r.MaDocGia}" title="Sửa"><i class="fa-solid fa-pen"></i></button>${canDeleteReaders?`<button class="danger" data-delete="${r.MaDocGia}" title="Xóa"><i class="fa-solid fa-trash"></i></button>`:''}</div></td>`:''}</tr>`}).join(''):`<tr><td colspan="${canManageReaders?9:8}" class="readerx-empty">Chưa có độc giả phù hợp.</td></tr>`;
            bindReaderActions(body);
        }
        const grid=el('readerCardGrid'); if(grid){grid.innerHTML=data.length?data.map(r=>{const st=loanStatsForReader(r.MaDocGia);return `<article class="readerx-card"><div class="readerx-card-top"><span class="readerx-avatar big">${initials(r.HoTen)}</span>${statusBadge(r)}</div><h4>${escapeHTML(r.HoTen||'--')}</h4><p>${readerCode(r.MaDocGia)} · ${escapeHTML(r.GioiTinh||'--')}</p><div class="readerx-card-meta"><span><i class="fa-solid fa-phone"></i>${escapeHTML(r.SDT||'--')}</span><span><i class="fa-solid fa-envelope"></i>${escapeHTML(r.Email||'--')}</span><span><i class="fa-solid fa-location-dot"></i>${escapeHTML(r.DiaChi||'--')}</span><span><i class="fa-solid fa-book-open"></i>${st.active} đang mượn · ${st.total} phiếu</span></div><div class="readerx-card-buttons"><button data-profile="${r.MaDocGia}">Xem</button>${canManageReaders?`<button data-edit="${r.MaDocGia}">Sửa</button>`:''}</div></article>`}).join(''):'<div class="readerx-empty">Chưa có độc giả phù hợp.</div>';bindReaderActions(grid)}
        if(el('readerResultText')) el('readerResultText').textContent=`Hiển thị ${data.length} / ${readers.length} độc giả`;
        renderDashboardExtras();
    }

    async function reload() {
        try {
            const [rp,lp]=await Promise.allSettled([libraryApi.get('readers'),libraryApi.get('loans')]);
            if(rp.status!=='fulfilled') throw rp.reason;
            readers=(rp.value.data||[]);
            readerLoans=lp.status==='fulfilled' ? (lp.value.data.loans||[]) : [];
            render();
        } catch (error) { notify(error.message); }
    }

    async function createReader() {
        if (!canManageReaders) return notify('Bạn chỉ có quyền xem thông tin độc giả.');
        if (busy) return;
        if (selectedId !== null) return notify('Bạn đang sửa một độc giả. Hãy bấm Cập nhật hoặc Làm mới trước khi thêm mới.');
        const data = getForm();
        data.id = null;
        const error = validate(data);
        if (error) return notify(error);
        setBusy(true);
        try {
            const payload = await libraryApi.post('reader_save', data);
            notify(payload.message);
            resetForm();
            closeReaderForm();
            await reload();
        } catch (error) {
            notify(error.message);
        } finally {
            setBusy(false);
        }
    }

    async function updateReader() {
        if (!canManageReaders) return notify('Bạn chỉ có quyền xem thông tin độc giả.');
        if (busy) return;
        if (selectedId === null) return notify('Hãy bấm nút Sửa ở độc giả cần cập nhật trước.');
        const data = getForm();
        data.id = selectedId;
        const error = validate(data);
        if (error) return notify(error);
        setBusy(true);
        try {
            const payload = await libraryApi.post('reader_save', data);
            notify(payload.message);
            resetForm();
            closeReaderForm();
            await reload();
        } catch (error) {
            notify(error.message);
        } finally {
            setBusy(false);
        }
    }

    async function removeReader(id = selectedId) {
        if (!canManageReaders) return notify('Bạn chỉ có quyền xem thông tin độc giả.');
        if (busy || !id) return notify('Hãy chọn độc giả cần xóa.');
        if (!(await window.libraryConfirm('Bạn có chắc muốn xóa độc giả này?','Xóa độc giả'))) return;
        setBusy(true);
        try {
            const payload = await libraryApi.post('reader_delete', {id});
            notify(payload.message);
            resetForm();
            await reload();
        } catch (error) {
            notify(error.message);
        } finally {
            setBusy(false);
        }
    }

    el('readerSaveBtn')?.addEventListener('click', createReader);
    el('readerUpdateBtn')?.addEventListener('click', updateReader);
    el('readerCardBtn')?.addEventListener('click', () => { if (selectedId) openReaderCard(selectedId); });
    el('readerProfileBtn')?.addEventListener('click', () => { if (selectedId) openReaderProfile(selectedId); });
    el('readerDeleteBtn')?.addEventListener('click', () => removeReader());
    el('readerResetBtn')?.addEventListener('click', resetForm);
    el('addReaderBtn')?.addEventListener('click', () => { resetForm(); if(el('readerFormTitle'))el('readerFormTitle').textContent='Thêm độc giả'; openReaderForm(); setTimeout(()=>el('readerName')?.focus(),50); });
    ['readerSearch','readerGenderFilter','readerStatusFilter','readerDateFilter','readerSort'].forEach(id => el(id)?.addEventListener(id === 'readerSearch' ? 'input' : 'change', render));
    document.querySelectorAll('[data-reader-chip]').forEach(btn=>btn.addEventListener('click',()=>{readerChip=btn.dataset.readerChip||'all';document.querySelectorAll('[data-reader-chip]').forEach(x=>x.classList.toggle('active',x===btn));render()}));
    document.querySelectorAll('[data-reader-view]').forEach(btn=>btn.addEventListener('click',()=>{readerView=btn.dataset.readerView||'table';document.querySelectorAll('[data-reader-view]').forEach(x=>x.classList.toggle('active',x===btn));el('readerTableView')?.classList.toggle('d-none',readerView!=='table');el('readerCardGrid')?.classList.toggle('d-none',readerView!=='card')}));
    document.querySelectorAll('[data-close-reader-form]').forEach(btn=>btn.addEventListener('click',closeReaderForm));
    el('readerRefreshBtn')?.addEventListener('click',reload);
    el('readerExportBtn')?.addEventListener('click',()=>{const rows=[['Mã','Họ tên','Giới tính','SĐT','Email','Địa chỉ','Ngày đăng ký','Trạng thái'],...filtered().map(r=>[readerCode(r.MaDocGia),r.HoTen||'',r.GioiTinh||'',r.SDT||'',r.Email||'',r.DiaChi||'',r.NgayDangKy||'',r.TrangThai||''])];const csv=rows.map(row=>row.map(v=>'"'+String(v).replace(/"/g,'""')+'"').join(',')).join('\n');const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='danh-sach-doc-gia.csv';a.click();URL.revokeObjectURL(url)});

    el('readerCardClose')?.addEventListener('click', closeReaderCard);
    el('readerCardModal')?.addEventListener('click', event => { if (event.target === el('readerCardModal')) closeReaderCard(); });
    el('readerCardPrint')?.addEventListener('click', printReaderCard);
    el('readerCardZoom')?.addEventListener('click', () => el('readerCardContent')?.classList.toggle('qr-zoomed'));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && el('readerCardModal')?.classList.contains('show')) closeReaderCard(); });

    el('readerProfileClose')?.addEventListener('click', closeReaderProfile);
    el('readerProfileModal')?.addEventListener('click',e=>{if(e.target===el('readerProfileModal'))closeReaderProfile()});
    loadSelfProfile();
    resetForm();
    reload();
})();
