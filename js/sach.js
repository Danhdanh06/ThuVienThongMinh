(() => {
    let pageSize = 10;
    const canManageBooks = !!window.libraryAccess?.canManageBooks;
    const isGuestVisitor = window.libraryAccess?.roleKey === 'guest';
    const canDeleteBooks = !!window.libraryAccess?.canDeleteBooks;
    const canReserveSelf = !!window.libraryAccess?.canReserveSelf;
    let books = [];
    let categories = [];
    let currentPage = 1;
    let editingId = null;
    let deletingId = null;
    let copyQrRows = [];
    let copyQrBook = null;
    let quickFilter = 'all';
    let viewMode = 'table';
    const selectedBookIds = new Set();

    async function advancedGet(action, params = {}) {
        const url = new URL('advanced_api.php', location.href);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([k,v]) => { if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, v); });
        const response = await fetch(url, {credentials:'same-origin', cache:'no-store'});
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.ok === false) throw new Error(payload.message || 'Không tải được mã từng cuốn.');
        return payload.data;
    }
    async function advancedPost(action, data = {}) {
        const response = await fetch(`advanced_api.php?action=${encodeURIComponent(action)}`, {method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(data)});
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.ok === false) throw new Error(payload.message || 'Không thực hiện được thao tác.');
        return payload.data;
    }

    function copyQrUrl(code, size=170){ return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=6&data=${encodeURIComponent(code)}`; }

    const el = id => document.getElementById(id);
    const els = {
        body: el('bookTableBody'), table: el('bookTable'), empty: el('emptyState'), summary: el('summaryText'), pagination: el('pagination'),
        search: el('searchInput'), category: el('categoryFilter'), status: el('statusFilter'), sort: el('sortSelect'),
        modal: el('bookModal'), modalTitle: el('modalTitle'), form: el('bookForm'), title: el('bookTitle'), author: el('bookAuthor'),
        bookCategory: el('bookCategory'), publisher: el('bookPublisher'), year: el('bookYear'), quantity: el('bookQuantity'),
        cover: el('bookCover'), description: el('bookDescription'), confirm: el('confirmDialog'), confirmText: el('confirmText'), toast: el('toast'),
        cardView: el('bookCardView'), tableCard: document.querySelector('.book-list-zone .table-card'), pageSize: el('pageSizeSelect'),
        bulkBar: el('bulkActionBar'), selectedCount: el('selectedBookCount'), selectAll: el('selectAllBooks')
    };

    function toast(message) {
        if (!els.toast) return alert(message);
        els.toast.textContent = message;
        els.toast.classList.add('show');
        clearTimeout(toast.timer);
        toast.timer = setTimeout(() => els.toast.classList.remove('show'), 2400);
    }

    function coverFor(book) {
        if (book.HinhAnh) return book.HinhAnh;
        const genre = normalizeLibraryText(book.TenTheLoai || '');
        const palettes = genre.includes('ngon tinh') ? ['#ec4899','#7c3aed','#2563eb']
            : genre.includes('cong nghe') ? ['#2563eb','#0891b2','#14b8a6']
            : genre.includes('kinh di') ? ['#312e81','#1d4ed8','#0f766e']
            : genre.includes('nau an') ? ['#ea580c','#f59e0b','#fb7185']
            : genre.includes('y hoc') || genre.includes('suc khoe') ? ['#0ea5e9','#2563eb','#10b981']
            : genre.includes('khoa hoc') ? ['#4f46e5','#2563eb','#0ea5e9']
            : genre.includes('ngoai ngu') ? ['#0f766e','#0891b2','#2563eb']
            : ['#2563eb','#4f46e5','#0891b2'];
        const title = String(book.TenSach || 'Sách').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&apos;'}[m]));
        const words = String(book.TenSach || 'Sách').split(/\s+/).filter(Boolean);
        const lines=[]; let line='';
        words.forEach(word=>{ const next=line?line+' '+word:word; if(next.length<=14)line=next; else{if(line)lines.push(line); line=word;} }); if(line)lines.push(line);
        const textLines = lines.slice(0,4).map((x,i)=>`<text x="18" y="${68+i*22}" font-family="Arial" font-size="15" font-weight="700" fill="white">${String(x).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&apos;'}[m]))}</text>`).join('');
        const cat = String(book.TenTheLoai || 'Thư viện').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&apos;'}[m]));
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="180" height="250" viewBox="0 0 180 250"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${palettes[0]}"/><stop offset=".55" stop-color="${palettes[1]}"/><stop offset="1" stop-color="${palettes[2]}"/></linearGradient></defs><rect width="180" height="250" rx="16" fill="url(#g)"/><circle cx="150" cy="42" r="42" fill="rgba(255,255,255,.10)"/><circle cx="20" cy="220" r="55" fill="rgba(255,255,255,.08)"/><rect x="14" y="16" width="152" height="218" rx="12" fill="none" stroke="rgba(255,255,255,.22)"/><rect x="16" y="20" width="92" height="24" rx="12" fill="rgba(15,23,42,.24)"/><text x="26" y="36" font-family="Arial" font-size="9" font-weight="700" fill="white">${cat.slice(0,18)}</text>${textLines}<text x="18" y="218" font-family="Arial" font-size="10" fill="rgba(255,255,255,.85)">${String(book.TacGia || 'Thư viện').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&apos;'}[m])).slice(0,24)}</text></svg>`;
        return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
    }

    function stockState(book) {
        const qty = Number(book?.SoLuong || 0);
        if (qty <= 0) return { key:'empty', label:'Hết sách', cls:'stock-empty', pct:0 };
        if (qty <= 5) return { key:'low', label:'Sắp hết', cls:'stock-low', pct:Math.max(12, Math.min(35, qty * 7)) };
        return { key:'healthy', label:'Còn nhiều', cls:'stock-healthy', pct:Math.min(100, 38 + Math.log2(qty + 1) * 12) };
    }

    function animateNumber(node, value) {
        if (!node) return;
        const target = Number(value || 0);
        const start = performance.now(), duration = 650;
        const tick = now => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 3);
            node.textContent = Math.round(target * eased).toLocaleString('vi-VN');
            if (t < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }

    function updateOverview() {
        animateNumber(el('overviewTitles'), books.length);
        animateNumber(el('overviewCopies'), books.reduce((sum,b)=>sum + Number(b.SoLuong || 0), 0));
        animateNumber(el('overviewAvailable'), books.filter(b=>Number(b.SoLuong || 0)>0).length);
        animateNumber(el('overviewAttention'), books.filter(b=>Number(b.SoLuong || 0)<=5).length);
    }

    function csvEscape(value) {
        const text = String(value ?? '');
        return `"${text.replace(/"/g,'""')}"`;
    }

    function exportBooksCsv(rows, filename='danh-sach-sach.csv') {
        if (!rows.length) return toast('Không có sách để xuất.');
        const header = ['Mã sách','Tên sách','Tác giả','Thể loại','Nhà xuất bản','Năm XB','Số lượng','Trạng thái'];
        const lines = [header.map(csvEscape).join(',')];
        rows.forEach(book => {
            const state = stockState(book);
            lines.push([
                `S${String(book.MaSach).padStart(3,'0')}`, book.TenSach, book.TacGia || '', book.TenTheLoai || '', book.NhaXuatBan || '', book.NamXuatBan || '', Number(book.SoLuong || 0), state.label
            ].map(csvEscape).join(','));
        });
        const blob = new Blob(['\ufeff' + lines.join('\n')], {type:'text/csv;charset=utf-8'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href = url; a.download = filename; document.body.appendChild(a); a.click(); a.remove();
        setTimeout(()=>URL.revokeObjectURL(url),500);
    }

    function renderAttention() {
        const box = el('bookAttentionGrid'); if (!box) return;
        if (!books.length) { box.innerHTML = '<div class="attention-empty">Chưa có dữ liệu sách.</div>'; return; }
        const low = [...books].filter(b=>Number(b.SoLuong||0)<=5).sort((a,b)=>Number(a.SoLuong||0)-Number(b.SoLuong||0)).slice(0,3);
        const popular = [...books].sort((a,b)=>Number(b.LuotMuon||0)-Number(a.LuotMuon||0)).slice(0,3);
        const newest = [...books].sort((a,b)=>Number(b.MaSach||0)-Number(a.MaSach||0)).slice(0,3);
        const groups = [
            ['Tồn kho cần chú ý','fa-triangle-exclamation','attention-warn',low],
            ['Mượn nhiều','fa-fire','attention-hot',popular],
            ['Mới thêm','fa-sparkles','attention-new',newest]
        ];
        box.innerHTML = groups.map(([title,icon,cls,list]) => `<article class="attention-group ${cls}"><div class="attention-group-head"><i class="fa-solid ${icon}"></i><strong>${title}</strong></div><div class="attention-books">${list.length ? list.map(book=>`<button type="button" class="attention-book" data-attention-book="${book.MaSach}"><img src="${escapeHTML(coverFor(book))}" alt=""><span><b>${escapeHTML(book.TenSach||'--')}</b><small>${escapeHTML(book.TacGia||'--')} • ${Number(book.SoLuong||0)} cuốn</small></span><i class="fa-solid fa-chevron-right"></i></button>`).join('') : '<div class="attention-empty-small">Không có mục cần chú ý.</div>'}</div></article>`).join('');
        box.querySelectorAll('[data-attention-book]').forEach(btn=>btn.addEventListener('click',()=>openBookDetail(Number(btn.dataset.attentionBook))));
    }

    function updateBulkBar() {
        if (!els.bulkBar) return;
        els.bulkBar.hidden = selectedBookIds.size === 0;
        if (els.selectedCount) els.selectedCount.textContent = selectedBookIds.size;
        if (els.selectAll) {
            const visible = Array.from(document.querySelectorAll('[data-select-book]'));
            const count = visible.filter(x=>x.checked).length;
            els.selectAll.checked = visible.length > 0 && count === visible.length;
            els.selectAll.indeterminate = count > 0 && count < visible.length;
        }
    }

    function buildBookActionHtml(book, qty) {
        if (canManageBooks) return `<div class="actions action-pill-group">
            <button class="action-btn detail-action" type="button" data-detail="${book.MaSach}" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></button>
            <button class="action-btn copy-qr-action" type="button" data-copies="${book.MaSach}" title="Xem QR"><i class="fa-solid fa-qrcode"></i></button>
            <button class="action-btn edit-btn" type="button" data-edit="${book.MaSach}" title="Sửa"><i class="fa-solid fa-pen"></i></button>
            ${canDeleteBooks ? `<button class="action-btn delete-btn" type="button" data-delete="${book.MaSach}" title="Xóa"><i class="fa-solid fa-trash-can"></i></button>` : ''}
        </div>`;
        if (canReserveSelf) return `<div class="actions action-pill-group"><button class="action-btn detail-action" type="button" data-detail="${book.MaSach}" title="Chi tiết"><i class="fa-solid fa-eye"></i></button><button class="action-btn" type="button" data-reserve="${book.MaSach}" ${qty <= 0 ? 'disabled' : ''} title="Đặt lịch mượn"><i class="fa-solid fa-calendar-plus"></i></button></div>`;
        if (isGuestVisitor) return `<div class="actions action-pill-group"><button class="action-btn detail-action" type="button" data-detail="${book.MaSach}" title="Chi tiết"><i class="fa-solid fa-eye"></i></button><button class="action-btn" type="button" data-login-borrow="1" ${qty <= 0 ? 'disabled' : ''} title="Đăng nhập để mượn"><i class="fa-solid fa-right-to-bracket"></i></button></div>`;
        return `<button class="action-btn detail-action" type="button" data-detail="${book.MaSach}" title="Chi tiết"><i class="fa-solid fa-eye"></i></button>`;
    }

    function bindRenderedActions(scope) {
        if (!scope) return;
        scope.querySelectorAll('[data-detail]').forEach(btn => btn.addEventListener('click', () => openBookDetail(Number(btn.dataset.detail))));
        scope.querySelectorAll('[data-copies]').forEach(btn => btn.addEventListener('click', () => openCopyQr(Number(btn.dataset.copies))));
        scope.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', () => openEdit(Number(btn.dataset.edit))));
        scope.querySelectorAll('[data-delete]').forEach(btn => btn.addEventListener('click', () => askDelete(Number(btn.dataset.delete))));
        scope.querySelectorAll('[data-reserve]').forEach(btn => btn.addEventListener('click', () => openReservation(Number(btn.dataset.reserve))));
        scope.querySelectorAll('[data-login-borrow]').forEach(btn => btn.addEventListener('click', () => { window.location.href = 'dangnhap.php'; }));
        scope.querySelectorAll('[data-select-book]').forEach(cb => cb.addEventListener('change', () => {
            const id = Number(cb.dataset.selectBook);
            if (cb.checked) selectedBookIds.add(id); else selectedBookIds.delete(id);
            updateBulkBar();
        }));
    }

    function renderCardView(page, start) {
        if (!els.cardView) return;
        els.cardView.innerHTML = page.map((book,i)=>{
            const qty = Number(book.SoLuong||0), state = stockState(book);
            return `<article class="inventory-book-card">
                ${canManageBooks ? `<label class="inventory-card-select"><input type="checkbox" data-select-book="${book.MaSach}" ${selectedBookIds.has(Number(book.MaSach))?'checked':''}><span></span></label>` : ''}
                <div class="inventory-cover-wrap"><img src="${escapeHTML(coverFor(book))}" alt="Bìa ${escapeHTML(book.TenSach||'sách')}"><span class="inventory-rank">#${start+i+1}</span></div>
                <div class="inventory-card-body"><div class="inventory-code">S${String(book.MaSach).padStart(3,'0')} • ${escapeHTML(book.TenTheLoai||'Chưa phân loại')}</div><h3>${escapeHTML(book.TenSach||'--')}</h3><p>${escapeHTML(book.TacGia||'--')}</p><div class="inventory-card-meta"><span><i class="fa-regular fa-calendar"></i>${book.NamXuatBan||'--'}</span><span><i class="fa-solid fa-box"></i>${qty} cuốn</span></div><div class="stock-meter"><div style="width:${state.pct}%" class="${state.cls}"></div></div><div class="inventory-card-foot"><span class="status modern-status ${state.cls}">${state.label}</span>${buildBookActionHtml(book,qty)}</div></div>
            </article>`;
        }).join('') || '<div class="attention-empty">Không tìm thấy sách phù hợp.</div>';
        bindRenderedActions(els.cardView);
    }

    function fillCategorySelects() {
        const filterValue = els.category?.value || '';
        const formValue = els.bookCategory?.value || '';
        const options = categories.map(c => `<option value="${c.MaTheLoai}">${escapeHTML(c.TenTheLoai)}</option>`).join('');
        if (els.category) {
            els.category.innerHTML = '<option value="">Tất cả</option>' + options;
            els.category.value = filterValue;
        }
        if (els.bookCategory) {
            els.bookCategory.innerHTML = '<option value="">Chưa phân loại</option>' + options;
            els.bookCategory.value = formValue;
        }
    }

    function filteredBooks() {
        const q = normalizeLibraryText(els.search?.value.trim() || '');
        const category = els.category?.value || '';
        const status = els.status?.value || '';
        const data = books.filter(book => {
            const text = normalizeLibraryText(`${book.MaSach} ${book.TenSach} ${book.TacGia || ''} ${book.NhaXuatBan || ''}`);
            const matchSearch = !q || text.includes(q);
            const matchCategory = !category || String(book.MaTheLoai || '') === category;
            const qty = Number(book.SoLuong || 0);
            const matchStatus = !status || (status === 'available' ? qty > 0 : qty <= 0);
            const state = stockState(book);
            let matchQuick = true;
            if (quickFilter === 'healthy') matchQuick = state.key === 'healthy';
            else if (quickFilter === 'low') matchQuick = state.key === 'low';
            else if (quickFilter === 'empty') matchQuick = state.key === 'empty';
            return matchSearch && matchCategory && matchStatus && matchQuick;
        });

        switch (els.sort?.value) {
            case 'title-asc': data.sort((a,b) => a.TenSach.localeCompare(b.TenSach, 'vi')); break;
            case 'title-desc': data.sort((a,b) => b.TenSach.localeCompare(a.TenSach, 'vi')); break;
            case 'year-desc': data.sort((a,b) => Number(b.NamXuatBan || 0) - Number(a.NamXuatBan || 0)); break;
            case 'year-asc': data.sort((a,b) => Number(a.NamXuatBan || 0) - Number(b.NamXuatBan || 0)); break;
            case 'quantity-desc': data.sort((a,b) => Number(b.SoLuong) - Number(a.SoLuong)); break;
            case 'quantity-asc': data.sort((a,b) => Number(a.SoLuong) - Number(b.SoLuong)); break;
            default: data.sort((a,b) => Number(b.MaSach) - Number(a.MaSach));
        }
        if (quickFilter === 'new') data.sort((a,b)=>Number(b.MaSach||0)-Number(a.MaSach||0));
        if (quickFilter === 'popular') data.sort((a,b)=>Number(b.LuotMuon||0)-Number(a.LuotMuon||0));
        return data;
    }

    function renderPagination(totalPages) {
        if (!els.pagination) return;
        if (totalPages <= 1) { els.pagination.innerHTML = ''; return; }
        let html = `<button class="page-btn page-nav" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}"><i class="fa-solid fa-arrow-left"></i><span>Trước</span></button>`;
        for (let p = 1; p <= totalPages; p++) html += `<button class="page-btn ${p === currentPage ? 'active' : ''}" data-page="${p}">${p}</button>`;
        html += `<button class="page-btn page-nav" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}"><span>Sau</span><i class="fa-solid fa-arrow-right"></i></button>`;
        els.pagination.innerHTML = html;
        els.pagination.querySelectorAll('[data-page]').forEach(btn => btn.addEventListener('click', () => {
            currentPage = Number(btn.dataset.page); render();
        }));
    }

    function render() {
        fillCategorySelects();
        const data = filteredBooks();
        const totalPages = Math.max(1, Math.ceil(data.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * pageSize;
        const page = data.slice(start, start + pageSize);

        els.body.innerHTML = page.map((book, i) => {
            const qty = Number(book.SoLuong || 0);
            const state = stockState(book);
            return `<tr class="modern-book-row">
                ${canManageBooks ? `<td class="select-col"><input type="checkbox" data-select-book="${book.MaSach}" ${selectedBookIds.has(Number(book.MaSach))?'checked':''} aria-label="Chọn ${escapeHTML(book.TenSach||'sách')}"></td>` : ''}
                <td>${start + i + 1}</td>
                <td><div class="cover-frame"><img class="cover" src="${escapeHTML(coverFor(book))}" alt="Bìa sách"></div></td>
                <td><span class="book-code-pill">S${String(book.MaSach).padStart(3,'0')}</span></td>
                <td class="book-title"><strong>${escapeHTML(book.TenSach)}</strong><small>${escapeHTML(book.TacGia || '--')} • ${escapeHTML(book.TenTheLoai || 'Chưa phân loại')}</small></td>
                <td>${escapeHTML(book.TacGia || '--')}</td>
                <td><span class="category-soft-badge">${escapeHTML(book.TenTheLoai || 'Chưa phân loại')}</span></td>
                <td>${book.NamXuatBan || '--'}</td>
                <td><div class="qty-cell"><b>${qty}</b><div class="stock-meter compact"><div style="width:${state.pct}%" class="${state.cls}"></div></div></div></td>
                <td><span class="status modern-status ${state.cls}">${state.label}</span></td>
                <td>${buildBookActionHtml(book, qty)}</td>
            </tr>`;
        }).join('');

        els.empty.hidden = data.length !== 0;
        if (viewMode === 'table') {
            if (els.tableCard) els.tableCard.hidden = false;
            if (els.cardView) els.cardView.hidden = true;
            els.table.style.display = data.length ? 'table' : 'none';
        } else {
            if (els.tableCard) els.tableCard.hidden = true;
            if (els.cardView) els.cardView.hidden = false;
            renderCardView(page, start);
        }
        els.summary.textContent = data.length ? `Hiển thị ${start + 1}–${Math.min(start + pageSize, data.length)} / ${data.length} sách` : 'Không có dữ liệu';
        renderPagination(totalPages);
        bindRenderedActions(els.body);
        updateBulkBar();
    }

    function renderCopyQrList() {
        const list = el('copyQrList'); if (!list) return;
        const q = normalizeLibraryText(el('copyQrSearch')?.value || '');
        const rows = copyQrRows.filter(x => !q || normalizeLibraryText(`${x.MaVach} ${x.TrangThai} ${x.ViTri || ''}`).includes(q));
        list.innerHTML = rows.length ? rows.map(x => `<div class="copy-qr-card">
            <img src="${copyQrUrl(x.MaVach)}" alt="QR ${escapeHTML(x.MaVach)}">
            <div><h4>${escapeHTML(copyQrBook?.TenSach || x.TenSach || '')}</h4><div class="copy-code">${escapeHTML(x.MaVach)}</div><p>Vị trí: ${escapeHTML(x.ViTri || '--')}</p><span class="copy-status ${x.TrangThai === 'Có sẵn' ? '' : 'busy'}">${escapeHTML(x.TrangThai || '--')}</span></div>
        </div>`).join('') : '<div class="copy-qr-empty">Không có mã cuốn phù hợp.</div>';
    }

    async function openCopyQr(bookId) {
        const modal = el('copyQrModal'); if (!modal) return;
        copyQrBook = books.find(b => Number(b.MaSach) === Number(bookId)) || null;
        el('copyQrSubtitle').textContent = copyQrBook ? `S${String(bookId).padStart(3,'0')} • ${copyQrBook.TenSach}` : `S${String(bookId).padStart(3,'0')}`;
        el('copyQrList').innerHTML = '<div class="copy-qr-empty"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải mã từng cuốn...</div>';
        if (el('copyQrSearch')) el('copyQrSearch').value = '';
        modal.classList.add('show'); document.body.style.overflow='hidden';
        try {
            const all = await advancedGet('copies');
            copyQrRows = (all || []).filter(x => Number(x.MaSach) === Number(bookId));
            renderCopyQrList();
        } catch (error) {
            el('copyQrList').innerHTML = `<div class="copy-qr-empty" style="color:#b91c1c">${escapeHTML(error.message)}</div>`;
        }
    }
    function closeCopyQr(){ el('copyQrModal')?.classList.remove('show'); document.body.style.overflow=''; copyQrRows=[]; copyQrBook=null; }
    function printCopyQrs(){
        if (!copyQrRows.length) return toast('Không có mã cuốn để in.');
        const html = copyQrRows.map(x => `<div class="item"><img src="${copyQrUrl(x.MaVach,190)}"><b>${escapeHTML(x.MaVach)}</b><span>${escapeHTML(copyQrBook?.TenSach || x.TenSach || '')}</span><small>${escapeHTML(x.TrangThai || '')}${x.ViTri?' • '+escapeHTML(x.ViTri):''}</small></div>`).join('');
        const w = window.open('', '_blank', 'width=1000,height=800'); if(!w) return toast('Trình duyệt đang chặn cửa sổ in.');
        w.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Mã QR từng cuốn</title><style>body{font-family:Arial;padding:24px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.item{border:1px solid #ddd;border-radius:12px;padding:12px;text-align:center;break-inside:avoid}.item img{width:150px;height:150px;display:block;margin:auto}.item b,.item span,.item small{display:block;margin-top:5px}.item b{font-size:15px}.item span{font-size:12px}.item small{font-size:11px;color:#555}@page{size:A4;margin:12mm}</style></head><body><h2>${escapeHTML(copyQrBook?.TenSach || 'Mã từng cuốn sách')}</h2><div class="grid">${html}</div><script>window.onload=()=>print()<\/script></body></html>`);
        w.document.close();
    }

    async function openBookDetail(id){
        const modal=el('bookDetailModal'), box=el('bookDetailContent'); if(!modal||!box)return;
        modal.classList.add('show'); document.body.style.overflow='hidden'; box.innerHTML='<div class="reader-empty-state" style="margin:24px"><i class="fa-solid fa-spinner fa-spin"></i><strong>Đang mở cuốn sách</strong><span>Đang tải thông tin chi tiết...</span></div>';
        try{
            const d=await advancedGet('book_detail',{id}),b=d.book||{},reviews=d.reviews||[],sim=d.sim||[];
            const canModerate=['admin','manager'].includes(window.libraryAccess?.roleKey), isCustomer=window.libraryAccess?.roleKey==='customer';
            let wishActive=false;
            if(isCustomer){try{const wish=await advancedGet('wishlist');wishActive=(wish||[]).some(x=>Number(x.MaSach)===Number(id))}catch(_){}}
            const customerActions=isCustomer?`<div class="reader-book-detail-actions"><button class="primary" id="detailBorrowNow" type="button" ${Number(b.SoLuong||0)<=0?'disabled':''}><i class="fa-solid fa-book-open-reader"></i> Mượn sách</button><button id="detailReserveNow" type="button" ${Number(b.SoLuong||0)<=0?'disabled':''}><i class="fa-regular fa-calendar-plus"></i> Đặt trước</button><button class="heart ${wishActive?'active':''}" id="detailFavorite" type="button"><i class="fa-${wishActive?'solid':'regular'} fa-heart"></i> ${wishActive?'Đã muốn đọc':'Muốn đọc'}</button></div>`:'';
            box.innerHTML=`<div class="detail-hero"><img src="${escapeHTML(coverFor(b))}" onerror="this.style.display='none'"><div><span class="book-hero-kicker"><i class="fa-solid fa-book-open"></i> ${escapeHTML(b.TenTheLoai||'Sách')}</span><h2>${escapeHTML(b.TenSach||'')}</h2><p>${escapeHTML(b.TacGia||'--')}</p><div class="detail-tags"><span><i class="fa-solid fa-barcode"></i> ISBN: ${escapeHTML(b.ISBN||'--')}</span><span><i class="fa-solid fa-location-dot"></i> ${escapeHTML(b.ViTriKe||'Chưa cập nhật')}</span><span><i class="fa-solid fa-box-open"></i> Còn ${Number(b.SoLuong||0)} cuốn</span><span>⭐ ${Number(b.DiemDanhGia||0).toFixed(1)}/5</span><span><i class="fa-solid fa-fire"></i> ${Number(b.LuotMuon||0)} lượt mượn</span></div><p>${escapeHTML(b.MoTa||'Chưa có mô tả cho cuốn sách này.')}</p>${customerActions}</div></div>
            ${canManageBooks?`<div class="detail-edit"><input id="detailIsbn" value="${escapeHTML(b.ISBN||'')}" placeholder="ISBN"><input id="detailLocation" value="${escapeHTML(b.ViTriKe||'')}" placeholder="Khu A – Kệ 03 – Tầng 2"><button id="detailMetaSave" class="primary-btn" type="button">Lưu ISBN / vị trí</button></div>`:''}
            ${isCustomer?`<section class="reader-detail-section"><h3><i class="fa-regular fa-star"></i> Chia sẻ cảm nhận</h3><div class="detail-customer-actions"><select id="detailStars"><option value="5">5 sao</option><option value="4">4 sao</option><option value="3">3 sao</option><option value="2">2 sao</option><option value="1">1 sao</option></select><input id="detailReview" placeholder="Viết nhận xét sau khi bạn đã đọc..."><button id="detailReviewSave" type="button">Gửi đánh giá</button></div></section>`:''}
            <section class="reader-detail-section"><h3>⭐ Đánh giá & nhận xét</h3><div class="review-list">${reviews.length?reviews.map(r=>`<div class="review-row"><div><b>${escapeHTML(r.HoTen||'Độc giả')} • ${r.SoSao}/5</b><p>${escapeHTML(r.NoiDung||'')}</p></div>${canModerate?`<button data-hide-review="${r.MaDanhGia}" type="button">Ẩn</button>`:''}</div>`).join(''):'<div class="reader-empty-state"><i class="fa-regular fa-message"></i><strong>Chưa có đánh giá</strong><span>Nhận xét đầu tiên sẽ xuất hiện ở đây.</span></div>'}</div></section>
            <section class="reader-detail-section"><h3>📚 Sách cùng thể loại</h3><div class="reader-similar-grid">${sim.length?sim.map(x=>`<button data-similar="${x.MaSach}" type="button"><strong>${escapeHTML(x.TenSach)}</strong><small>${escapeHTML(x.TacGia||'')}</small></button>`).join(''):'<div class="reader-empty-state" style="grid-column:1/-1"><strong>Chưa có sách tương tự</strong></div>'}</div></section>`;
            el('detailBorrowNow')?.addEventListener('click',()=>{sessionStorage.setItem('readerBorrowBook',String(id));closeBookDetail();loadPage('muontra.php','Mượn sách')});
            el('detailReserveNow')?.addEventListener('click',()=>{closeBookDetail();openReservation(id)});
            el('detailMetaSave')?.addEventListener('click',async()=>{try{await advancedPost('book_meta_update',{bookId:id,isbn:el('detailIsbn').value,location:el('detailLocation').value});toast('Đã lưu ISBN và vị trí kệ.');openBookDetail(id)}catch(e){toast(e.message)}});
            el('detailFavorite')?.addEventListener('click',async()=>{try{const r=await advancedPost('wishlist_toggle',{bookId:id});window.libraryToast?.(r.data?.added?'Đã thêm vào danh sách muốn đọc.':'Đã bỏ khỏi danh sách muốn đọc.');openBookDetail(id)}catch(e){window.libraryToast?.(e.message,'error')||toast(e.message)}});
            el('detailReviewSave')?.addEventListener('click',async()=>{try{await advancedPost('review_save',{bookId:id,stars:+el('detailStars').value,text:el('detailReview').value});window.libraryToast?.('Đã lưu đánh giá của bạn.');openBookDetail(id)}catch(e){window.libraryToast?.(e.message,'error')||toast(e.message)}});
            box.querySelectorAll('[data-hide-review]').forEach(x=>x.onclick=async()=>{try{await advancedPost('review_moderate',{id:+x.dataset.hideReview,status:'Ẩn'});toast('Đã ẩn nhận xét.');openBookDetail(id)}catch(e){toast(e.message)}});
            box.querySelectorAll('[data-similar]').forEach(x=>x.onclick=()=>openBookDetail(+x.dataset.similar));
        }catch(e){box.innerHTML=`<div class="reader-empty-state" style="margin:24px"><i class="fa-solid fa-circle-exclamation"></i><strong>Không mở được sách</strong><span>${escapeHTML(e.message)}</span></div>`}
    }
    function closeBookDetail(){el('bookDetailModal')?.classList.remove('show');document.body.style.overflow=''}

    function openModal() { els.modal?.classList.add('show'); document.body.style.overflow = 'hidden'; }
    function closeModal() { els.modal?.classList.remove('show'); document.body.style.overflow = ''; editingId = null; els.form?.reset(); }

    function openEdit(id) {
        const book = books.find(b => Number(b.MaSach) === id); if (!book) return;
        editingId = id; els.modalTitle.textContent = 'Cập nhật sách';
        els.title.value = book.TenSach || ''; els.author.value = book.TacGia || ''; els.publisher.value = book.NhaXuatBan || '';
        els.year.value = book.NamXuatBan || ''; els.quantity.value = book.SoLuong || 0; els.cover.value = book.HinhAnh || '';
        els.description.value = book.MoTa || ''; els.bookCategory.value = book.MaTheLoai || '';
        openModal();
    }

    function askDelete(id) {
        if (!canDeleteBooks) return toast('Nhân viên chỉ được thêm/sửa sách, không có quyền xóa.');
        const book = books.find(b => Number(b.MaSach) === id); if (!book) return;
        deletingId = id; els.confirmText.textContent = `Bạn có chắc muốn xóa sách “${book.TenSach}”?`;
        els.confirm.classList.add('show');
    }

    function fillReservationBooks(selectedId = '') {
        const select = el('reservationBookId');
        if (!select) return;
        select.innerHTML = '<option value="">-- Chọn sách --</option>' + books.map(book => {
            const qty = Number(book.SoLuong || 0);
            return `<option value="${book.MaSach}" ${qty <= 0 ? 'disabled' : ''}>S${String(book.MaSach).padStart(3,'0')} - ${escapeHTML(book.TenSach)} (${qty} còn)</option>`;
        }).join('');
        if (selectedId) select.value = String(selectedId);
    }

    function openReservation(bookId) {
        if (!canReserveSelf) return;
        const modal = el('reservationModal');
        if (!modal) return;
        fillReservationBooks(bookId);
        if (el('reservationDate')) el('reservationDate').value = new Date().toLocaleDateString('en-CA');
        if (el('reservationTime')) el('reservationTime').value = '';
        if (el('reservationQuantity')) el('reservationQuantity').value = 1;
        if (el('reservationNote')) el('reservationNote').value = '';
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeReservation() {
        el('reservationModal')?.classList.remove('show');
        document.body.style.overflow = '';
    }

    async function reload() {
        try {
            const payload = await libraryApi.get('books');
            books = payload.data.books || []; categories = payload.data.categories || [];
            if (isGuestVisitor) {
                const landingSearch = sessionStorage.getItem('guestBookSearch');
                const landingCategory = sessionStorage.getItem('guestBookCategory');
                if (landingSearch && els.search) els.search.value = landingSearch;
                fillCategorySelects();
                if (landingCategory && els.category) els.category.value = landingCategory;
                sessionStorage.removeItem('guestBookSearch');
                sessionStorage.removeItem('guestBookCategory');
            }
            updateOverview();
            renderAttention();
            render();
        } catch (error) { toast(error.message); }
    }

    els.form?.addEventListener('submit', async event => {
        event.preventDefault();
        if (!canManageBooks) return toast('Tài khoản của bạn chỉ được xem sách.');
        const payload = {
            id: editingId,
            title: els.title.value.trim(), author: els.author.value.trim(), publisher: els.publisher.value.trim(),
            categoryId: els.bookCategory.value || null, year: els.year.value || null, quantity: Number(els.quantity.value || 0),
            cover: els.cover.value.trim(), description: els.description.value.trim()
        };
        try {
            const result = await libraryApi.post('book_save', payload);
            toast(result.message); closeModal(); currentPage = 1; await reload();
        } catch (error) { toast(error.message); }
    });

    el('addBookBtn')?.addEventListener('click', () => {
        editingId = null; els.form.reset(); els.modalTitle.textContent = 'Thêm sách'; els.quantity.value = 1; els.year.value = new Date().getFullYear(); openModal();
    });
    el('closeModalBtn')?.addEventListener('click', closeModal);
    el('cancelBtn')?.addEventListener('click', closeModal);
    els.modal?.addEventListener('click', e => { if (e.target === els.modal) closeModal(); });
    el('cancelDeleteBtn')?.addEventListener('click', () => { deletingId = null; els.confirm.classList.remove('show'); });
    el('confirmDeleteBtn')?.addEventListener('click', async () => {
        if (!canDeleteBooks) return toast('Bạn không có quyền xóa sách.');
        if (!deletingId) return;
        try { const result = await libraryApi.post('book_delete', { id: deletingId }); toast(result.message); els.confirm.classList.remove('show'); deletingId = null; await reload(); }
        catch (error) { toast(error.message); }
    });
    [els.search, els.category, els.status, els.sort].forEach(item => item?.addEventListener(item.tagName === 'INPUT' ? 'input' : 'change', () => { currentPage = 1; render(); }));

    el('heroAddBookBtn')?.addEventListener('click', () => el('addBookBtn')?.click());
    el('heroRefreshBtn')?.addEventListener('click', async () => { await reload(); toast('Đã làm mới dữ liệu sách.'); });
    el('exportBooksBtn')?.addEventListener('click', () => exportBooksCsv(filteredBooks()));
    el('openInventoryBtn')?.addEventListener('click', () => { if (typeof window.openSmartTab === 'function') window.openSmartTab('inventory','Kho & kiểm kê'); else loadPage('nangcao.php','Thư viện thông minh'); });
    el('toggleAdvancedFilterBtn')?.addEventListener('click', () => el('bookToolbarShell')?.classList.toggle('advanced-open'));
    el('clearBookFiltersBtn')?.addEventListener('click', () => {
        if (els.search) els.search.value=''; if (els.category) els.category.value=''; if (els.status) els.status.value=''; if (els.sort) els.sort.value='default';
        quickFilter='all'; document.querySelectorAll('[data-quick-filter]').forEach(x=>x.classList.toggle('active',x.dataset.quickFilter==='all')); currentPage=1; render();
    });
    document.querySelectorAll('[data-quick-filter]').forEach(btn=>btn.addEventListener('click',()=>{
        quickFilter=btn.dataset.quickFilter||'all'; document.querySelectorAll('[data-quick-filter]').forEach(x=>x.classList.toggle('active',x===btn)); currentPage=1; render();
    }));
    el('pageSizeSelect')?.addEventListener('change', e=>{ pageSize=Math.max(1,Number(e.target.value||10)); currentPage=1; render(); });
    el('tableViewBtn')?.addEventListener('click',()=>{ viewMode='table'; el('tableViewBtn')?.classList.add('active'); el('cardViewBtn')?.classList.remove('active'); render(); });
    el('cardViewBtn')?.addEventListener('click',()=>{ viewMode='card'; el('cardViewBtn')?.classList.add('active'); el('tableViewBtn')?.classList.remove('active'); render(); });
    els.selectAll?.addEventListener('change',()=>{
        document.querySelectorAll('[data-select-book]').forEach(cb=>{ cb.checked=els.selectAll.checked; const id=Number(cb.dataset.selectBook); if(cb.checked)selectedBookIds.add(id); else selectedBookIds.delete(id); }); updateBulkBar();
    });
    el('clearSelectedBooksBtn')?.addEventListener('click',()=>{ selectedBookIds.clear(); render(); });
    el('exportSelectedBooksBtn')?.addEventListener('click',()=>exportBooksCsv(books.filter(b=>selectedBookIds.has(Number(b.MaSach))),'sach-da-chon.csv'));
    el('deleteSelectedBooksBtn')?.addEventListener('click', async()=>{
        const ids=[...selectedBookIds]; if(!ids.length)return;
        if(!(await window.libraryConfirm(`Bạn có chắc muốn xóa ${ids.length} sách đã chọn? Thao tác này dùng đúng quyền xóa hiện tại.`,'Xóa nhiều sách')))return;
        let ok=0, failed=0;
        for(const id of ids){ try{ await libraryApi.post('book_delete',{id}); ok++; }catch(e){ failed++; } }
        selectedBookIds.clear(); await reload(); toast(failed?`Đã xóa ${ok} sách, ${failed} sách không thể xóa.`:`Đã xóa ${ok} sách.`);
    });

    el('reservationForm')?.addEventListener('submit', async event => {
        event.preventDefault();
        if (!canReserveSelf) return toast('Tài khoản của bạn không có quyền đặt lịch mượn.');
        try {
            const result = await libraryApi.post('reservation_save', {
                bookId: el('reservationBookId')?.value,
                date: el('reservationDate')?.value,
                time: el('reservationTime')?.value || null,
                quantity: Number(el('reservationQuantity')?.value || 1),
                note: el('reservationNote')?.value.trim() || ''
            });
            toast(result.message);
            closeReservation();
        } catch (error) { toast(error.message); }
    });
    el('closeReservationModalBtn')?.addEventListener('click', closeReservation);
    el('cancelReservationBtn')?.addEventListener('click', closeReservation);
    el('reservationModal')?.addEventListener('click', event => { if (event.target === el('reservationModal')) closeReservation(); });
    el('closeCopyQrModalBtn')?.addEventListener('click', closeCopyQr);
    el('copyQrModal')?.addEventListener('click', event => { if (event.target === el('copyQrModal')) closeCopyQr(); });
    el('copyQrSearch')?.addEventListener('input', renderCopyQrList);
    el('printAllCopyQr')?.addEventListener('click', printCopyQrs);

    if (!canManageBooks) {
        el('addBookBtn')?.remove();
        els.modal?.remove();
        els.confirm?.remove();
    }

    el('closeBookDetailBtn')?.addEventListener('click', closeBookDetail);
    el('bookDetailModal')?.addEventListener('click',e=>{if(e.target===el('bookDetailModal'))closeBookDetail()});
    reload();
})();
