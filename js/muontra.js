(() => {
    let pageSize = 10;
    const roleKey = window.libraryAccess?.roleKey || '';
    const isCustomer = roleKey === 'customer';
    const canManageLoans = !!window.libraryAccess?.canManageLoans;
    const canDeleteLoans = !!window.libraryAccess?.canDeleteLoans;
    const canBorrowSelf = !!window.libraryAccess?.canBorrowSelf;
    const canManageReservations = !!window.libraryAccess?.canManageReservations;
    const canManageRenewals = !!window.libraryAccess?.canManageRenewals;
    const canRenewSelf = !!window.libraryAccess?.canRenewSelf;

    let loans = [], readers = [], books = [], employees = [], reservations = [], renewals = [];
    let settings = {SoNgayMuon: 14, MucPhat: 5000};
    let publicSummary = null, loanSummary = null;
    let currentPage = 1, editingId = null, pendingAction = null;
    let loanChip = "all", loanView = "table";
    const el = id => document.getElementById(id);
    const today = () => new Date().toLocaleDateString('en-CA');

    function addDays(dateString, days) {
        const d = new Date(dateString + 'T00:00:00');
        d.setDate(d.getDate() + Number(days || 0));
        return d.toLocaleDateString('en-CA');
    }
    function daysBetween(a,b) { return Math.ceil((new Date(b+'T00:00:00') - new Date(a+'T00:00:00')) / 86400000); }
    function toast(msg) {
        const t = el('toast'); if (!t) return alert(msg);
        t.textContent = msg; t.classList.add('show'); clearTimeout(toast.timer); toast.timer = setTimeout(() => t.classList.remove('show'), 2500);
    }

    function statusOf(loan) {
        if ((loan.TrangThai || '') === 'Chờ duyệt') return {key:'pending', text:'Chờ duyệt', css:'pending'};
        if (loan.NgayTra || (loan.TrangThai || '') === 'Đã trả') return {key:'returned',text:'Đã trả',css:'returned'};
        const remain = loan.HanTra ? daysBetween(today(), loan.HanTra) : 99;
        if (remain < 0) return {key:'overdue',text:`Quá hạn ${Math.abs(remain)} ngày`,css:'overdue'};
        if (remain <= 3) return {key:'due-soon',text:remain === 0 ? 'Hạn trả hôm nay' : `Còn ${remain} ngày`,css:'due-soon'};
        return {key:'borrowing',text:'Đang mượn',css:'borrowing'};
    }

    function reservationStatusClass(status='') {
        const s = normalizeLibraryText(status);
        if (s.includes('cho xac nhan')) return 'pending';
        if (s.includes('da xac nhan')) return 'confirmed';
        if (s.includes('da huy')) return 'cancelled';
        if (s.includes('da nhan sach')) return 'received';
        return 'borrowing';
    }

    function setupHeaders() {
        const loanHead = el('loanTableHead');
        if (loanHead) {
            loanHead.innerHTML = isCustomer
                ? '<tr><th>STT</th><th>Mã phiếu</th><th>Sách</th><th>SL</th><th>Ngày gửi/mượn</th><th>Hạn trả</th><th>Ngày trả</th><th>Trạng thái</th><th>Chi tiết</th></tr>'
                : '<tr><th>STT</th><th>Mã phiếu</th><th>Độc giả</th><th>Sách</th><th>SL</th><th>Ngày mượn</th><th>Hạn trả</th><th>Ngày trả</th><th>Nhân viên</th><th>Trạng thái</th><th>Thao tác</th></tr>';
        }
        const reserveHead = el('reservationTableHead');
        if (reserveHead) {
            reserveHead.innerHTML = isCustomer
                ? '<tr><th>Mã đặt</th><th>Sách</th><th>Ngày dự kiến</th><th>Giờ</th><th>SL</th><th>Trạng thái</th><th>Ghi chú</th></tr>'
                : `<tr><th>Mã đặt</th><th>Độc giả</th><th>SĐT</th><th>Sách</th><th>Ngày dự kiến</th><th>Giờ</th><th>SL</th><th>Nhân viên xử lý</th><th>Trạng thái</th>${canManageReservations ? '<th>Thao tác</th>' : ''}</tr>`;
        }
        const renewalHead = el('renewalTableHead');
        if (renewalHead) {
            renewalHead.innerHTML = isCustomer
                ? '<tr><th>Mã YC</th><th>Phiếu</th><th>Sách</th><th>Hạn cũ</th><th>Hạn mới</th><th>Số ngày</th><th>Trạng thái</th><th>Ghi chú</th></tr>'
                : `<tr><th>Mã YC</th><th>Độc giả</th><th>Phiếu</th><th>Sách</th><th>Hạn cũ</th><th>Hạn mới</th><th>Số ngày</th><th>Cảnh báo</th><th>Trạng thái</th>${canManageRenewals ? '<th>Thao tác</th>' : ''}</tr>`;
        }
    }

    function fillOptions() {
        const r = el('readerId'), b = el('bookId'), s = el('staffId');
        if (r) {
            const old = r.value;
            r.innerHTML = '<option value="">-- Chọn độc giả --</option>' + readers.map(x => `<option value="${x.MaDocGia}">DG${String(x.MaDocGia).padStart(3,'0')} - ${escapeHTML(x.HoTen)}</option>`).join('');
            if (isCustomer && readers[0]) r.value = String(readers[0].MaDocGia); else r.value = old;
        }
        if (b) {
            const old = b.value;
            b.innerHTML = '<option value="">-- Chọn sách --</option>' + [...books].sort((a,c) => String(a.TenSach||'').localeCompare(String(c.TenSach||''),'vi')).map(x => {
                const qty = Number(x.SoLuong || 0);
                return `<option value="${x.MaSach}" ${qty <= 0 ? 'disabled' : ''}>S${String(x.MaSach).padStart(3,'0')} - ${escapeHTML(x.TenSach)}${x.TacGia ? ` - ${escapeHTML(x.TacGia)}` : ''} (${qty} còn)</option>`;
            }).join('');
            if ([...b.options].some(o => o.value === String(old))) b.value = old;
        }
        if (s) {
            const old = s.value;
            s.innerHTML = '<option value="">-- Tự động / không chọn --</option>' + employees.map(x => `<option value="${x.MaNhanVien}">NV${String(x.MaNhanVien).padStart(3,'0')} - ${escapeHTML(x.HoTen)}</option>`).join('');
            s.value = old;
        }
        syncReader(); syncBook();
    }

    function syncReader() {
        const id = Number(el('readerId')?.value || 0);
        const reader = readers.find(x => Number(x.MaDocGia) === id);
        if (el('readerName')) el('readerName').value = reader?.HoTen || '';
        if (el('readerPhone')) el('readerPhone').value = reader?.SDT || '';
    }
    function syncBook() {
        const id = Number(el('bookId')?.value || 0);
        const book = books.find(x => Number(x.MaSach) === id);
        if (el('bookTitle')) el('bookTitle').value = book?.TenSach || '';
        if (book && el('quantity')) el('quantity').max = Math.max(1, Number(book.SoLuong || 0));
    }

    function resetForm() {
        editingId = null;
        el('loanForm')?.reset();
        fillOptions();
        if (el('borrowDate')) el('borrowDate').value = today();
        if (el('dueDate')) el('dueDate').value = addDays(today(), settings.SoNgayMuon || 14);
        if (el('quantity')) el('quantity').value = 1;
        if (isCustomer && readers[0] && el('readerId')) {
            el('readerId').value = String(readers[0].MaDocGia);
            syncReader();
        }
    }
    function openModal() { el('loanModal')?.classList.add('show'); document.body.style.overflow='hidden'; }
    function closeModal() { el('loanModal')?.classList.remove('show'); document.body.style.overflow=''; editingId=null; }
    function closeDetail() { el('detailModal')?.classList.remove('show'); }

    function filtered() {
        const q = normalizeLibraryText(el('searchInput')?.value || '');
        const st = el('statusFilter')?.value || '';
        const date = el('dateFilter')?.value || '';
        const data = loans.filter(l => { const key=statusOf(l).key; const chipOk=loanChip==='all'||key===loanChip||(loanChip==='pending'&&key==='pending'); return (!q || normalizeLibraryText(`${l.MaPhieuMuon} ${l.TenDocGia||''} ${l.TenSach||''}`).includes(q)) && (!st || key === st) && (!date || l.NgayMuon === date) && chipOk; });
        switch (el('sortSelect')?.value) {
            case 'oldest': data.sort((a,b)=>Number(a.MaPhieuMuon)-Number(b.MaPhieuMuon)); break;
            case 'due-asc': data.sort((a,b)=>String(a.HanTra||'').localeCompare(String(b.HanTra||''))); break;
            case 'reader-asc': data.sort((a,b)=>(a.TenDocGia||'').localeCompare(b.TenDocGia||'','vi')); break;
            default: data.sort((a,b)=>Number(b.MaPhieuMuon)-Number(a.MaPhieuMuon));
        }
        return data;
    }

    function renderStats() {
        if (isCustomer && publicSummary) {
            el('borrowingLabel').textContent = 'Người đang mượn';
            el('returnedLabel').textContent = 'Người đã trả';
            el('dueSoonLabel').textContent = 'Yêu cầu chờ duyệt';
            el('overdueLabel').textContent = 'Phiếu của tôi';
            el('borrowingCount').textContent = Number(publicSummary.DangMuon || 0);
            el('returnedCount').textContent = Number(publicSummary.DaTra || 0);
            el('dueSoonCount').textContent = Number(publicSummary.ChoDuyet || 0);
            el('overdueCount').textContent = loans.length;
            return;
        }
        if (loanSummary) {
            animateLoanNumber('borrowingCount',loanSummary.borrowingBooks||0); animateLoanNumber('returnedCount',loanSummary.returnedBooks||0); animateLoanNumber('dueSoonCount',loanSummary.dueSoonBooks||0); animateLoanNumber('overdueCount',loanSummary.overdueBooks||0);
            return;
        }
        let borrowing=0, returned=0, dueSoon=0, overdue=0;
        loans.forEach(l => {
            const s=statusOf(l).key, qty=Number(l.TongSoLuong || 0);
            if(s==='returned') returned += qty;
            else if(s !== 'pending') borrowing += qty;
            if(s==='due-soon') dueSoon += qty;
            if(s==='overdue') overdue += qty;
        });
        animateLoanNumber('borrowingCount',borrowing); animateLoanNumber('returnedCount',returned); animateLoanNumber('dueSoonCount',dueSoon); animateLoanNumber('overdueCount',overdue);
    }

    function loanInitials(name='') { const p=String(name||'').trim().split(/\s+/).filter(Boolean); return (p.length?(p[0][0]+(p.length>1?p[p.length-1][0]:'')):'ĐG').toUpperCase(); }
    function animateLoanNumber(id,value){const n=el(id);if(!n)return;const end=Number(value||0),start=Number(n.textContent||0),t0=performance.now();const tick=now=>{const p=Math.min(1,(now-t0)/650);n.textContent=Math.round(start+(end-start)*(1-Math.pow(1-p,3))).toLocaleString('vi-VN');if(p<1)requestAnimationFrame(tick)};requestAnimationFrame(tick)}
    function renderLoanExtras(){
        const todayStr=today();
        const todayLoans=loans.filter(l=>l.NgayMuon===todayStr).length;
        const active=loans.filter(l=>!l.NgayTra && statusOf(l).key!=='pending');
        const returned=loans.filter(l=>!!l.NgayTra || statusOf(l).key==='returned');
        const ontime=returned.filter(l=>l.NgayTra&&l.HanTra&&l.NgayTra<=l.HanTra).length;
        const rate=returned.length?Math.round(ontime/returned.length*100):0;
        if(el('loanHeroToday'))el('loanHeroToday').textContent=todayLoans;
        const need=loans.filter(l=>['overdue','due-soon','pending'].includes(statusOf(l).key)).length;
        if(el('loanHeroAlerts'))el('loanHeroAlerts').textContent=need;
        if(el('loanHeroRate'))el('loanHeroRate').textContent=rate+'%';
        const alerts=[];
        const overdue=loans.filter(l=>statusOf(l).key==='overdue').length,due=loans.filter(l=>statusOf(l).key==='due-soon').length,pending=loans.filter(l=>statusOf(l).key==='pending').length;
        if(overdue)alerts.push({c:'red',i:'fa-triangle-exclamation',t:`${overdue} phiếu quá hạn`,s:'Cần ưu tiên xử lý'});
        if(due)alerts.push({c:'orange',i:'fa-clock',t:`${due} phiếu sắp đến hạn`,s:'Trong tối đa 3 ngày'});
        if(pending)alerts.push({c:'purple',i:'fa-hourglass-half',t:`${pending} phiếu chờ duyệt`,s:'Đang chờ xử lý'});
        if(reservations.length)alerts.push({c:'blue',i:'fa-calendar-check',t:`${reservations.length} lịch đặt trước`,s:'Đang có trong hệ thống'});
        const box=el('loanAlertCards');if(box)box.innerHTML=alerts.length?alerts.slice(0,4).map(a=>`<div class="loanx-alert ${a.c}"><i class="fa-solid ${a.i}"></i><div><strong>${a.t}</strong><span>${a.s}</span></div></div>`).join(''):'<div class="loanx-empty">Không có cảnh báo đáng chú ý.</div>';
        const activity=el('loanActivityFeed');if(activity){const recent=[...loans].sort((a,b)=>Number(b.MaPhieuMuon)-Number(a.MaPhieuMuon)).slice(0,5);activity.innerHTML=recent.length?recent.map(l=>{const st=statusOf(l);return `<button data-view="${l.MaPhieuMuon}"><span class="loanx-activity-icon ${st.css}"><i class="fa-solid fa-receipt"></i></span><span><strong>PM${String(l.MaPhieuMuon).padStart(3,'0')} · ${escapeHTML(l.TenDocGia||'Độc giả')}</strong><small>${escapeHTML(l.TenSach||'--')} · ${st.text}</small></span></button>`}).join(''):'<div class="loanx-empty">Chưa có phiếu gần đây.</div>';activity.querySelectorAll('[data-view]').forEach(b=>b.addEventListener('click',()=>showDetail(b.dataset.view)))}
        const days=[];for(let i=6;i>=0;i--){const d=new Date();d.setDate(d.getDate()-i);days.push(d.toLocaleDateString('en-CA'))}
        const chart=el('loanSevenDayChart');if(chart){const vals=days.map(d=>({d,b:loans.filter(l=>l.NgayMuon===d).length,r:loans.filter(l=>l.NgayTra===d).length,o:loans.filter(l=>l.NgayMuon===d&&statusOf(l).key==='overdue').length}));const mx=Math.max(1,...vals.flatMap(v=>[v.b,v.r,v.o]));chart.innerHTML=vals.map(v=>`<div class="loanx-day"><div class="loanx-bars"><i class="borrow" style="height:${Math.max(4,v.b/mx*80)}px" title="Mượn ${v.b}"></i><i class="returned" style="height:${Math.max(4,v.r/mx*80)}px" title="Trả ${v.r}"></i><i class="overdue" style="height:${Math.max(4,v.o/mx*80)}px" title="Quá hạn ${v.o}"></i></div><span>${v.d.slice(8,10)}/${v.d.slice(5,7)}</span></div>`).join('')}
    }
    function renderLoanCards(data){const grid=el('loanCardGrid');if(!grid)return;grid.innerHTML=data.length?data.map(l=>{const st=statusOf(l);return `<article class="loanx-loan-card"><div class="loanx-loan-card-head"><span class="loanx-code">PM${String(l.MaPhieuMuon).padStart(3,'0')}</span><span class="status ${st.css}">${st.text}</span></div><div class="loanx-person-row"><span class="loanx-avatar">${loanInitials(l.TenDocGia||'Độc giả')}</span><div><strong>${escapeHTML(l.TenDocGia||'--')}</strong><small>${escapeHTML(l.TenNhanVien||'Chưa phân công')}</small></div></div><div class="loanx-book-row"><i class="fa-solid fa-book-open"></i><div><strong>${escapeHTML(l.TenSach||'--')}</strong><small>Số lượng: ${Number(l.TongSoLuong||0)}</small></div></div><div class="loanx-date-grid"><span><small>Ngày mượn</small><b>${formatLibraryDate(l.NgayMuon)}</b></span><span><small>Hạn trả</small><b>${formatLibraryDate(l.HanTra)}</b></span></div><button class="loanx-card-open" data-view="${l.MaPhieuMuon}">Xem chi tiết <i class="fa-solid fa-arrow-right"></i></button></article>`}).join(''):'<div class="loanx-empty">Không có phiếu phù hợp.</div>';grid.querySelectorAll('[data-view]').forEach(b=>b.addEventListener('click',()=>showDetail(b.dataset.view)))}

    function renderPagination(total) {
        const p=el('pagination'); if(!p) return;
        if(total<=1){p.innerHTML='';return;}
        let h=''; for(let i=1;i<=total;i++) h+=`<button class="page-btn ${i===currentPage?'active':''}" data-page="${i}">${i}</button>`;
        p.innerHTML=h; p.querySelectorAll('[data-page]').forEach(b=>b.addEventListener('click',()=>{currentPage=Number(b.dataset.page);renderLoans();}));
    }

    function renderLoans() {
        renderStats();
        const data=filtered(), total=Math.max(1,Math.ceil(data.length/pageSize));
        currentPage=Math.min(currentPage,total);
        const start=(currentPage-1)*pageSize, page=data.slice(start,start+pageSize);
        renderLoanCards(page);
        renderLoanExtras();
        const body = el('loanTableBody'); if (!body) return;
        body.innerHTML=page.map((l,i)=>{
            const s=statusOf(l), returned=!!l.NgayTra || s.key==='returned';
            let actions = `<button class="action-btn view-btn" data-view="${l.MaPhieuMuon}" title="Chi tiết" type="button"><i class="fa-solid fa-eye"></i></button>`;
            if (canManageLoans) {
                if (s.key === 'pending') actions += `<button class="action-btn approve-btn" data-approve="${l.MaPhieuMuon}" title="Duyệt yêu cầu" type="button"><i class="fa-solid fa-circle-check"></i></button>`;
                else if (!returned) actions += `<button class="action-btn edit-btn" data-edit="${l.MaPhieuMuon}" title="Sửa" type="button"><i class="fa-solid fa-pen"></i></button><button class="action-btn return-btn" data-return="${l.MaPhieuMuon}" title="Trả sách" type="button"><i class="fa-solid fa-check"></i></button>`;
                if (canDeleteLoans) actions += `<button class="action-btn delete-btn" data-delete="${l.MaPhieuMuon}" title="Xóa" type="button"><i class="fa-solid fa-trash"></i></button>`;
            }
            if (isCustomer && canRenewSelf && !returned && s.key !== 'pending') {
                actions += `<button class="action-btn edit-btn" data-renew="${l.MaPhieuMuon}" title="Yêu cầu gia hạn" type="button"><i class="fa-solid fa-clock-rotate-left"></i></button>`;
            }
            if (isCustomer) {
                return `<tr><td>${start+i+1}</td><td>PM${String(l.MaPhieuMuon).padStart(3,'0')}</td><td>${escapeHTML(l.TenSach||'--')}</td><td>${l.TongSoLuong||0}</td><td>${formatLibraryDate(l.NgayMuon)}</td><td>${formatLibraryDate(l.HanTra)}</td><td>${formatLibraryDate(l.NgayTra)}</td><td><span class="status ${s.css}">${s.text}</span></td><td>${actions}</td></tr>`;
            }
            return `<tr class="loanx-table-row"><td>${start+i+1}</td><td><div class="loanx-code-cell"><strong>PM${String(l.MaPhieuMuon).padStart(3,'0')}</strong><small>${formatLibraryDate(l.NgayMuon)}</small></div></td><td><div class="loanx-person-row"><span class="loanx-avatar small">${loanInitials(l.TenDocGia||'Độc giả')}</span><div><strong>${escapeHTML(l.TenDocGia||'--')}</strong><small>Độc giả</small></div></div></td><td><div class="loanx-book-cell"><i class="fa-solid fa-book-open"></i><span><strong>${escapeHTML(l.TenSach||'--')}</strong><small>${l.TongSoLuong||0} cuốn</small></span></div></td><td>${l.TongSoLuong||0}</td><td>${formatLibraryDate(l.NgayMuon)}</td><td>${formatLibraryDate(l.HanTra)}</td><td>${formatLibraryDate(l.NgayTra)}</td><td>${escapeHTML(l.TenNhanVien||'--')}</td><td><span class="status ${s.css}">${s.text}</span></td><td><div class="actions">${actions}</div></td></tr>`;
        }).join('');
        el('emptyState').hidden=data.length!==0;
        el('loanTable').style.display=data.length?'table':'none';
        el('summaryText').textContent=data.length?`Hiển thị ${start+1} đến ${Math.min(start+pageSize,data.length)} của ${data.length} phiếu`:'Không có dữ liệu';
        renderPagination(total);
        body.querySelectorAll('[data-view]').forEach(b=>b.addEventListener('click',()=>showDetail(b.dataset.view)));
        body.querySelectorAll('[data-renew]').forEach(b=>b.addEventListener('click',()=>openRenewal(b.dataset.renew)));
        if (canManageLoans) {
            body.querySelectorAll('[data-edit]').forEach(b=>b.addEventListener('click',()=>openEdit(b.dataset.edit)));
            body.querySelectorAll('[data-approve]').forEach(b=>b.addEventListener('click',()=>ask('approve',b.dataset.approve)));
            body.querySelectorAll('[data-return]').forEach(b=>b.addEventListener('click',()=>ask('return',b.dataset.return)));
            if (canDeleteLoans) body.querySelectorAll('[data-delete]').forEach(b=>b.addEventListener('click',()=>ask('delete',b.dataset.delete)));
        }
    }

    function showDetail(id) {
        const l=loans.find(x=>Number(x.MaPhieuMuon)===Number(id)); if(!l)return;
        const s=statusOf(l);
        const rows=[['Mã phiếu',`PM${String(l.MaPhieuMuon).padStart(3,'0')}`],['Độc giả',l.TenDocGia||'--'],['Sách',l.TenSach||'--'],['Số lượng',l.TongSoLuong||0],['Ngày mượn/gửi',formatLibraryDate(l.NgayMuon)],['Hạn trả',formatLibraryDate(l.HanTra)],['Ngày trả',formatLibraryDate(l.NgayTra)],['Trạng thái',s.text],['Tiền phạt',Number(l.TongTienPhat||0).toLocaleString('vi-VN')+' đ'],['Ghi chú',l.GhiChu||'--']];
        if (!isCustomer) rows.splice(8,0,['Nhân viên xử lý',l.TenNhanVien||'--']);
        const timeline=[{done:true,icon:'fa-file-circle-plus',text:'Đã tạo phiếu',date:formatLibraryDate(l.NgayMuon)},{done:s.key!=='pending',icon:'fa-book-open',text:'Đã giao sách',date:s.key==='pending'?'Chờ duyệt':formatLibraryDate(l.NgayMuon)},{done:['due-soon','overdue','returned'].includes(s.key),icon:'fa-clock',text:s.key==='overdue'?'Đã quá hạn':'Theo dõi hạn trả',date:formatLibraryDate(l.HanTra)},{done:s.key==='returned',icon:'fa-circle-check',text:'Đã trả sách',date:l.NgayTra?formatLibraryDate(l.NgayTra):'Chưa trả'}];
        el('detailContent').innerHTML=`<div class="loanx-detail-grid">${rows.map(([a,b])=>`<div class="detail-item"><div class="detail-label">${a}</div><div class="detail-value">${escapeHTML(b)}</div></div>`).join('')}</div><div class="loanx-timeline"><h3>Timeline trạng thái</h3>${timeline.map(t=>`<div class="loanx-timeline-item ${t.done?'done':''}"><i class="fa-solid ${t.icon}"></i><span><strong>${t.text}</strong><small>${t.date}</small></span></div>`).join('')}</div><div class="loanx-quick-actions"><button type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> In phiếu</button><button type="button" data-go-reader="${l.MaDocGia||''}"><i class="fa-solid fa-user"></i> Xem độc giả</button><button type="button" data-go-book="1"><i class="fa-solid fa-book"></i> Xem sách</button></div>`;
        el('detailContent').querySelector('[data-go-reader]')?.addEventListener('click',()=>loadPage('docgia.php','Độc giả'));
        el('detailContent').querySelector('[data-go-book]')?.addEventListener('click',()=>loadPage('sach.php','Sách'));
        el('detailModal').classList.add('show');
    }

    function openEdit(id) {
        if (!canManageLoans) return;
        const l=loans.find(x=>Number(x.MaPhieuMuon)===Number(id)); if(!l||l.NgayTra||statusOf(l).key==='pending')return;
        editingId=Number(l.MaPhieuMuon); fillOptions(); el('loanModalTitle').textContent='Cập nhật phiếu mượn';
        el('readerId').value=l.MaDocGia||''; syncReader();
        const firstBook=String(l.MaSachList||'').split(',')[0]; el('bookId').value=firstBook||''; syncBook();
        el('borrowDate').value=l.NgayMuon||today(); el('dueDate').value=l.HanTra||''; el('quantity').value=l.TongSoLuong||1; if(el('staffId')) el('staffId').value=l.MaNhanVien||''; el('note').value=l.GhiChu||''; openModal();
    }

    function ask(type,id) {
        if (!canManageLoans) return;
        if (type === 'delete' && !canDeleteLoans) return toast('Nhân viên không có quyền xóa phiếu mượn.');
        pendingAction={type,id:Number(id)};
        const messages = {
            approve:['Duyệt yêu cầu mượn','Duyệt yêu cầu này? Khi duyệt, hệ thống sẽ trừ tồn kho và chuyển phiếu sang Đang mượn.'],
            return:['Xác nhận trả sách','Xác nhận độc giả đã trả toàn bộ sách trong phiếu này?'],
            delete:['Xóa phiếu mượn','Bạn có chắc muốn xóa phiếu này?']
        };
        el('confirmTitle').textContent=messages[type][0]; el('confirmText').textContent=messages[type][1]; el('confirmDialog').classList.add('show');
    }

    function openRenewal(id) {
        if (!isCustomer || !canRenewSelf) return;
        const loan = loans.find(x => Number(x.MaPhieuMuon) === Number(id));
        if (!loan || loan.NgayTra || statusOf(loan).key === 'pending') return toast('Phiếu này không thể gia hạn.');
        el('renewalLoanId').value = loan.MaPhieuMuon;
        el('renewalLoanInfo').value = `PM${String(loan.MaPhieuMuon).padStart(3,'0')} - ${loan.TenSach || ''}`;
        el('renewalOldDue').value = loan.HanTra || '';
        el('renewalDays').value = Number(settings.SoNgayGiaHan || 7);
        el('renewalModal')?.classList.add('show');
        document.body.style.overflow='hidden';
    }

    function closeRenewal() {
        el('renewalModal')?.classList.remove('show');
        document.body.style.overflow='';
    }

    function renderRenewals() {
        const body = el('renewalTableBody');
        if (!body) return;
        if (!renewals.length) {
            body.innerHTML='';
            if (el('renewalEmpty')) el('renewalEmpty').hidden=false;
            if (el('renewalTable')) el('renewalTable').style.display='none';
            return;
        }
        if (el('renewalEmpty')) el('renewalEmpty').hidden=true;
        if (el('renewalTable')) el('renewalTable').style.display='table';

        body.innerHTML = renewals.map(r => {
            const status = r.TrangThai || '--';
            if (isCustomer) {
                const note = r.LyDoTuChoi || r.CanhBao || '--';
                return `<tr><td>GH${String(r.MaYeuCau).padStart(3,'0')}</td><td>PM${String(r.MaPhieuMuon).padStart(3,'0')}</td>
                    <td>${escapeHTML(r.TenSach||'--')}</td><td>${formatLibraryDate(r.HanTraCu)}</td><td>${formatLibraryDate(r.HanTraMoi)}</td>
                    <td>+${Number(r.SoNgayGiaHan||0)} ngày</td><td><span class="status ${status==='Đã duyệt'?'confirmed':status==='Từ chối'?'cancelled':'pending'}">${escapeHTML(status)}</span></td>
                    <td>${escapeHTML(note)}</td></tr>`;
            }
            let actions = '';
            if (canManageRenewals && status === 'Chờ duyệt') {
                actions = `<div class="actions">
                    <button class="action-btn approve-btn" data-renew-action="duyet" data-id="${r.MaYeuCau}" title="Duyệt"><i class="fa-solid fa-check"></i></button>
                    <button class="action-btn delete-btn" data-renew-action="tu choi" data-id="${r.MaYeuCau}" title="Từ chối"><i class="fa-solid fa-xmark"></i></button>
                </div>`;
            }
            return `<tr><td>GH${String(r.MaYeuCau).padStart(3,'0')}</td><td>${escapeHTML(r.TenDocGia||'--')}</td>
                <td>PM${String(r.MaPhieuMuon).padStart(3,'0')}</td><td>${escapeHTML(r.TenSach||'--')}</td>
                <td>${formatLibraryDate(r.HanTraCu)}</td><td>${formatLibraryDate(r.HanTraMoi)}</td><td>+${Number(r.SoNgayGiaHan||0)} ngày</td>
                <td>${escapeHTML(r.CanhBao||'--')}</td><td><span class="status ${status==='Đã duyệt'?'confirmed':status==='Từ chối'?'cancelled':'pending'}">${escapeHTML(status)}</span></td>
                ${canManageRenewals?`<td>${actions||'--'}</td>`:''}</tr>`;
        }).join('');

        if (canManageRenewals) body.querySelectorAll('[data-renew-action]').forEach(btn => btn.addEventListener('click', async () => {
            const type = btn.dataset.renewAction;
            let reason = null;
            if (type === 'tu choi') {
                reason = prompt('Lý do từ chối (có thể để trống):', 'Không đủ điều kiện gia hạn.') || 'Không đủ điều kiện gia hạn.';
            } else if (!(await window.libraryConfirm('Duyệt yêu cầu gia hạn này và cập nhật hạn trả mới?','Duyệt gia hạn',false))) return;
            try {
                const p = await libraryApi.post('renewal_action',{id:Number(btn.dataset.id),type,reason});
                toast(p.message); await reload();
            } catch(e){ toast(e.message); }
        }));
    }

    function renderReservations() {
        const body = el('reservationTableBody'); if(!body)return;
        if (!reservations.length) { body.innerHTML=''; el('reservationEmpty').hidden=false; el('reservationTable').style.display='none'; return; }
        el('reservationEmpty').hidden=true; el('reservationTable').style.display='table';
        body.innerHTML = reservations.map(r => {
            const status = r.TrangThai || '--';
            if (isCustomer) return `<tr><td>DL${String(r.MaDatLich).padStart(3,'0')}</td><td>${escapeHTML(r.TenSach||'--')}</td><td>${formatLibraryDate(r.NgayDuKienMuon)}</td><td>${String(r.GioDuKien||'--').slice(0,5)}</td><td>${r.SoLuong||1}</td><td><span class="status ${reservationStatusClass(status)}">${escapeHTML(status)}</span></td><td>${escapeHTML(r.GhiChu||'--')}</td></tr>`;
            let actions='';
            if (canManageReservations) {
                if (status === 'Chờ xác nhận') actions = `<div class="actions"><button class="action-btn reserve-confirm-btn" data-reserve-action="xac nhan" data-id="${r.MaDatLich}" title="Xác nhận"><i class="fa-solid fa-check"></i></button><button class="action-btn reserve-cancel-btn" data-reserve-action="huy" data-id="${r.MaDatLich}" title="Hủy"><i class="fa-solid fa-xmark"></i></button></div>`;
                else if (status === 'Đã xác nhận') actions = `<div class="actions"><button class="action-btn reserve-receive-btn" data-reserve-action="da nhan sach" data-id="${r.MaDatLich}" title="Khách đã nhận sách"><i class="fa-solid fa-book-open"></i></button><button class="action-btn reserve-cancel-btn" data-reserve-action="huy" data-id="${r.MaDatLich}" title="Hủy"><i class="fa-solid fa-xmark"></i></button></div>`;
            }
            return `<tr><td>DL${String(r.MaDatLich).padStart(3,'0')}</td><td>${escapeHTML(r.TenDocGia||'--')}</td><td>${escapeHTML(r.SDTDocGia||'--')}</td><td>${escapeHTML(r.TenSach||'--')}</td><td>${formatLibraryDate(r.NgayDuKienMuon)}</td><td>${String(r.GioDuKien||'--').slice(0,5)}</td><td>${r.SoLuong||1}</td><td>${escapeHTML(r.TenNhanVien||'--')}</td><td><span class="status ${reservationStatusClass(status)}">${escapeHTML(status)}</span></td>${canManageReservations?`<td>${actions||'<span style="color:#64748b">--</span>'}</td>`:''}</tr>`;
        }).join('');
        if (canManageReservations) body.querySelectorAll('[data-reserve-action]').forEach(btn => btn.addEventListener('click', () => handleReservationAction(Number(btn.dataset.id), btn.dataset.reserveAction)));
    }

    async function handleReservationAction(id,type) {
        const labels = {'xac nhan':'xác nhận lịch đặt','huy':'hủy lịch đặt','da nhan sach':'giao sách và tạo phiếu mượn'};
        if(!(await window.libraryConfirm(`Bạn có chắc muốn ${labels[type] || 'thực hiện thao tác'}?`,'Xác nhận thao tác',type==='delete'))) return;
        try { const p=await libraryApi.post('reservation_action',{id,type}); toast(p.message); await reload(); } catch(e){toast(e.message);}
    }

    function switchTab(tab) {
        const loan = tab === 'loan', reservation = tab === 'reservation', renewal = tab === 'renewal';
        el('loanTabBtn')?.classList.toggle('active', loan);
        el('reservationTabBtn')?.classList.toggle('active', reservation);
        el('renewalTabBtn')?.classList.toggle('active', renewal);
        el('loanPanel')?.classList.toggle('active', loan);
        el('reservationPanel')?.classList.toggle('active', reservation);
        el('renewalPanel')?.classList.toggle('active', renewal);
    }

    async function reload() {
        const [loanResult, bookResult, reservationResult, renewalResult] = await Promise.allSettled([
            libraryApi.get('loans'),
            libraryApi.get('books'),
            libraryApi.get('reservation_list'),
            libraryApi.get('renewal_list')
        ]);

        if (loanResult.status === 'fulfilled') {
            const loanPayload = loanResult.value;
            loans = loanPayload.data.loans || [];
            readers = loanPayload.data.readers || [];
            employees = loanPayload.data.employees || [];
            settings = loanPayload.data.settings || settings;
            publicSummary = loanPayload.data.publicSummary || null;
            loanSummary = loanPayload.data.loanSummary || null;
        } else {
            loans = []; readers = []; employees = []; publicSummary = null; loanSummary = null;
            console.warn('Không tải được dữ liệu phiếu mượn:', loanResult.reason);
        }

        if (bookResult.status === 'fulfilled') {
            books = bookResult.value.data.books || [];
        } else if (loanResult.status === 'fulfilled') {
            books = loanResult.value.data.books || [];
        } else {
            books = [];
        }

        reservations = reservationResult.status === 'fulfilled'
            ? (reservationResult.value.data.rows || [])
            : [];
        renewals = renewalResult.status === 'fulfilled'
            ? (renewalResult.value.data.rows || [])
            : [];

        fillOptions(); resetForm(); setupHeaders(); renderLoans(); renderReservations(); renderRenewals();

        if (!books.length && bookResult.status === 'rejected') {
            toast(bookResult.reason?.message || 'Không tải được danh sách sách.');
        }
    }

    el('loanForm')?.addEventListener('submit',async e=>{
        e.preventDefault();
        if (isCustomer && !canBorrowSelf) return toast('Tài khoản của bạn không được phép gửi yêu cầu mượn.');
        if (!isCustomer && !canManageLoans) return toast('Bạn chỉ được xem phiếu mượn.');
        const d={id:isCustomer?null:editingId,readerId:el('readerId')?.value,readerName:el('readerName')?.value.trim()||'',readerPhone:el('readerPhone')?.value.trim()||'',bookId:el('bookId')?.value,staffId:isCustomer?null:(el('staffId')?.value||null),borrowDate:el('borrowDate')?.value,dueDate:el('dueDate')?.value,quantity:Number(el('quantity')?.value||1),note:el('note')?.value.trim()||''};
        try{const p=await libraryApi.post('loan_save',d);toast(p.message);closeModal();await reload();}catch(err){toast(err.message);}
    });
    el('addLoanBtn')?.addEventListener('click',()=>{resetForm();if(el('loanModalTitle'))el('loanModalTitle').textContent=isCustomer?'Gửi yêu cầu mượn sách':'Tạo phiếu mượn';openModal();});
    el('readerId')?.addEventListener('change',syncReader); el('bookId')?.addEventListener('change',syncBook);
    el('borrowDate')?.addEventListener('change',()=>{if(!editingId&&!isCustomer&&el('dueDate'))el('dueDate').value=addDays(el('borrowDate').value,settings.SoNgayMuon||14);});
    el('closeLoanModalBtn')?.addEventListener('click',closeModal); el('cancelLoanBtn')?.addEventListener('click',closeModal);
    el('closeDetailBtn')?.addEventListener('click',closeDetail); el('closeDetailFooterBtn')?.addEventListener('click',closeDetail);
    el('cancelConfirmBtn')?.addEventListener('click',()=>{pendingAction=null;el('confirmDialog').classList.remove('show');});
    el('confirmActionBtn')?.addEventListener('click',async()=>{if(!pendingAction||!canManageLoans)return;if(pendingAction.type==='delete'&&!canDeleteLoans)return toast('Bạn không có quyền xóa phiếu.');try{const action=pendingAction.type==='approve'?'loan_approve':pendingAction.type==='return'?'loan_return':'loan_delete';const p=await libraryApi.post(action,{id:pendingAction.id});toast(p.message);pendingAction=null;el('confirmDialog').classList.remove('show');await reload();}catch(e){toast(e.message);}});
    ['searchInput','statusFilter','dateFilter','sortSelect'].forEach(id=>el(id)?.addEventListener(id==='searchInput'?'input':'change',()=>{currentPage=1;renderLoans();}));
    el('loanTabBtn')?.addEventListener('click',()=>switchTab('loan'));
    el('reservationTabBtn')?.addEventListener('click',()=>switchTab('reservation'));
    el('renewalTabBtn')?.addEventListener('click',()=>switchTab('renewal'));

    el('renewalForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        if (!isCustomer || !canRenewSelf) return toast('Bạn không có quyền gửi yêu cầu gia hạn.');
        try {
            const p = await libraryApi.post('renewal_save', {
                loanId: Number(el('renewalLoanId')?.value || 0),
                days: Number(el('renewalDays')?.value || settings.SoNgayGiaHan || 7)
            });
            toast(p.message + (p.data?.warning ? ` ${p.data.warning}` : ''));
            closeRenewal(); await reload(); switchTab('renewal');
        } catch(err){ toast(err.message); }
    });
    el('closeRenewalModalBtn')?.addEventListener('click', closeRenewal);
    el('cancelRenewalBtn')?.addEventListener('click', closeRenewal);

    document.querySelectorAll('[data-loan-chip]').forEach(btn=>btn.addEventListener('click',()=>{loanChip=btn.dataset.loanChip||'all';currentPage=1;document.querySelectorAll('[data-loan-chip]').forEach(x=>x.classList.toggle('active',x===btn));renderLoans()}));
    document.querySelectorAll('[data-loan-view]').forEach(btn=>btn.addEventListener('click',()=>{loanView=btn.dataset.loanView||'table';document.querySelectorAll('[data-loan-view]').forEach(x=>x.classList.toggle('active',x===btn));el('loanTableCard')?.classList.toggle('d-none',loanView!=='table');el('loanCardGrid')?.classList.toggle('d-none',loanView!=='card');renderLoans()}));
    el('loanPageSize')?.addEventListener('change',e=>{pageSize=Number(e.target.value||10);currentPage=1;renderLoans()});
    el('loanRefreshBtn')?.addEventListener('click',reload);
    el('loanExportBtn')?.addEventListener('click',()=>{const rows=[['Mã phiếu','Độc giả','Sách','SL','Ngày mượn','Hạn trả','Ngày trả','Nhân viên','Trạng thái'],...filtered().map(l=>[`PM${String(l.MaPhieuMuon).padStart(3,'0')}`,l.TenDocGia||'',l.TenSach||'',l.TongSoLuong||0,l.NgayMuon||'',l.HanTra||'',l.NgayTra||'',l.TenNhanVien||'',statusOf(l).text])];const csv=rows.map(row=>row.map(v=>'"'+String(v).replace(/"/g,'""')+'"').join(',')).join('\n');const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='danh-sach-muon-tra.csv';a.click();URL.revokeObjectURL(url)});
    el('quickReturnHeroBtn')?.addEventListener('click',async()=>{const raw=prompt('Nhập mã vạch cuốn sách cần trả. Có thể nhập nhiều mã, ngăn cách bằng dấu phẩy:');if(!raw)return;const codes=raw.split(',').map(x=>x.trim()).filter(Boolean);try{const r=await fetch('advanced_api.php?action=quick_return',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({codes})});const p=await r.json();if(!r.ok||!p.ok)throw new Error(p.message||'Không thể trả sách nhanh.');toast(p.message);await reload()}catch(e){toast(e.message)}});

    setupHeaders(); switchTab('loan'); reload();
})();
