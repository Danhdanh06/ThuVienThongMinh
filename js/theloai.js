(() => {
    const canManageCategories = !!window.libraryAccess?.canManageCategories;
    let categories = [];
    let books = [];
    let selectedId = null;
    let busy = false;
    let currentView = 'table';
    let currentChip = 'all';
    let currentSort = 'newest';
    let advancedOpen = false;
    const el = id => document.getElementById(id);
    const esc = value => (window.escapeHTML ? window.escapeHTML(value) : String(value ?? ''));
    const norm = value => (window.normalizeLibraryText ? window.normalizeLibraryText(value) : String(value ?? '').toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').trim());

    function notify(message) { if(window.libraryToast) window.libraryToast(message); else alert(message); }
    function todayText() {
        try {
            return new Intl.DateTimeFormat('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date());
        } catch (_) { return new Date().toLocaleDateString('vi-VN'); }
    }
    function setBusy(value) {
        busy = value;
        ['categorySaveBtn', 'categoryUpdateBtn', 'categoryDeleteBtn', 'categoryResetBtn', 'categoryAddBtn', 'categoryExportBtn', 'categoryRefreshBtn'].forEach(id => {
            const button = el(id); if (button) button.disabled = value;
        });
    }
    function categoryCode(id) { return `TL${String(id).padStart(3, '0')}`; }
    function clamp(n, min, max) { return Math.min(max, Math.max(min, n)); }

    function genreTheme(name = '') {
        const value = String(name || '').toLowerCase();
        const themes = [
            { keys: ['ngôn tình', 'tình', 'romance'], icon: 'fa-heart', symbol: '❤', palette: ['#ec4899', '#8b5cf6', '#2563eb'] },
            { keys: ['công nghệ', 'kỹ thuật', 'tin học'], icon: 'fa-microchip', symbol: '⌘', palette: ['#2563eb', '#0891b2', '#14b8a6'] },
            { keys: ['y học', 'sức khỏe', 'dinh dưỡng'], icon: 'fa-staff-snake', symbol: '✚', palette: ['#0ea5e9', '#2563eb', '#14b8a6'] },
            { keys: ['phiêu lưu', 'thám hiểm'], icon: 'fa-compass', symbol: '✦', palette: ['#4f46e5', '#1d4ed8', '#06b6d4'] },
            { keys: ['khoa học'], icon: 'fa-flask', symbol: '⚗', palette: ['#2563eb', '#4338ca', '#0891b2'] },
            { keys: ['kinh dị', 'trinh thám', 'bí ẩn'], icon: 'fa-ghost', symbol: '☾', palette: ['#312e81', '#1d4ed8', '#0891b2'] },
            { keys: ['nấu ăn', 'ẩm thực'], icon: 'fa-utensils', symbol: '✿', palette: ['#ea580c', '#f59e0b', '#fb7185'] },
            { keys: ['thể thao'], icon: 'fa-person-running', symbol: '⚑', palette: ['#0f766e', '#2563eb', '#22c55e'] },
            { keys: ['ngoại ngữ', 'giáo dục'], icon: 'fa-book-open-reader', symbol: 'A', palette: ['#0f766e', '#2563eb', '#8b5cf6'] },
            { keys: ['kinh tế', 'kỹ năng', 'làm giàu'], icon: 'fa-chart-line', symbol: '$', palette: ['#2563eb', '#7c3aed', '#ec4899'] },
            { keys: ['thiếu nhi', 'cổ tích'], icon: 'fa-wand-sparkles', symbol: '★', palette: ['#22c55e', '#3b82f6', '#8b5cf6'] }
        ];
        return themes.find(theme => theme.keys.some(key => value.includes(key))) || { icon: 'fa-book-open', symbol: '✦', palette: ['#2563eb', '#4338ca', '#0891b2'] };
    }
    function svgDataUri(svg) { return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`; }
    function categoryArtSrc(name = '') {
        const theme = genreTheme(name);
        const label = String(name || 'Thể loại').trim();
        const short = label.length > 14 ? label.slice(0, 14) + '…' : label;
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="360" height="220" viewBox="0 0 360 220"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="${theme.palette[0]}"/><stop offset="50%" stop-color="${theme.palette[1]}"/><stop offset="100%" stop-color="${theme.palette[2]}"/></linearGradient></defs><rect width="360" height="220" rx="30" fill="url(#g)"/><circle cx="278" cy="52" r="38" fill="rgba(255,255,255,.14)"/><circle cx="78" cy="172" r="66" fill="rgba(255,255,255,.09)"/><text x="40" y="84" font-size="48" font-weight="700" fill="#fff" font-family="Inter,Arial,sans-serif">${theme.symbol}</text><text x="40" y="134" font-size="28" font-weight="800" fill="#fff" font-family="Inter,Arial,sans-serif">${short.replace(/[&<>"']/g, '')}</text></svg>`;
        return svgDataUri(svg);
    }

    function resetForm() {
        selectedId = null;
        if (el('categoryId')) el('categoryId').value = '';
        if (el('categoryName')) el('categoryName').value = '';
        if (el('categoryDescription')) el('categoryDescription').value = '';
        const modalTitle = el('categoryModalTitle');
        if (modalTitle) modalTitle.textContent = 'Thêm thể loại';
        updateModalButtons();
    }
    function updateModalButtons() {
        if (el('categorySaveBtn')) el('categorySaveBtn').classList.toggle('d-none', !!selectedId);
        if (el('categoryUpdateBtn')) el('categoryUpdateBtn').classList.toggle('d-none', !selectedId);
        if (el('categoryDeleteBtn')) el('categoryDeleteBtn').classList.toggle('d-none', !selectedId);
    }
    function openModal(editId = null) {
        if (!canManageCategories) return;
        if (editId) {
            const item = categories.find(x => Number(x.MaTheLoai) === Number(editId));
            if (!item) return;
            selectedId = Number(item.MaTheLoai);
            el('categoryId').value = categoryCode(item.MaTheLoai);
            el('categoryName').value = item.TenTheLoai || '';
            el('categoryDescription').value = item.MoTa || '';
            el('categoryModalTitle').textContent = 'Chỉnh sửa thể loại';
        } else {
            resetForm();
        }
        updateModalButtons();
        el('categoryModal')?.classList.add('show');
        document.body.classList.add('modal-open');
        setTimeout(() => el('categoryName')?.focus(), 40);
    }
    function closeModal() {
        el('categoryModal')?.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
    function openDrawer(id) {
        const item = categories.find(x => Number(x.MaTheLoai) === Number(id));
        if (!item) return;
        selectedId = Number(item.MaTheLoai);
        renderDrawer(item);
        el('categoryDrawer')?.classList.add('show');
        document.body.classList.add('drawer-open');
    }
    function closeDrawer() {
        el('categoryDrawer')?.classList.remove('show');
        document.body.classList.remove('drawer-open');
    }

    function topBooksByCategory(id, limit = 5) {
        return books.filter(book => Number(book.MaTheLoai) === Number(id))
            .sort((a, b) => Number(b.SoLuong || 0) - Number(a.SoLuong || 0) || Number(b.MaSach || 0) - Number(a.MaSach || 0))
            .slice(0, limit);
    }
    function bookCountLabel(count) {
        if (count <= 0) return 'Chưa có';
        if (count <= 10) return 'Ít';
        if (count <= 40) return 'Ổn định';
        return 'Dồi dào';
    }
    function categoryStatus(item) {
        const total = Number(item.TongSoLuong || 0);
        if (total <= 0) return { label: 'Hết sách', className: 'danger' };
        if (total <= 12) return { label: 'Ít sách', className: 'warning' };
        return { label: 'Dồi dào', className: 'success' };
    }
    function formatNumber(num) { return Number(num || 0).toLocaleString('vi-VN'); }
    function maxBookCount() { return Math.max(1, ...categories.map(item => Number(item.TongSoLuong || 0))); }
    function maxTitleCount() { return Math.max(1, ...categories.map(item => Number(item.SoDauSach || 0))); }
    function rowDescription(text) {
        const str = String(text || '').trim();
        return str ? str : 'Chưa có mô tả cho thể loại này.';
    }

    function setChip(chip) {
        currentChip = chip;
        document.querySelectorAll('.catx-chip').forEach(btn => btn.classList.toggle('active', btn.dataset.chip === chip));
        render();
    }
    function setView(view) {
        currentView = view;
        document.querySelectorAll('.catx-view-switch button').forEach(btn => btn.classList.toggle('active', btn.dataset.view === view));
        el('categoryTableWrap')?.classList.toggle('d-none', view !== 'table');
        el('categoryCardGrid')?.classList.toggle('d-none', view !== 'card');
        render();
    }
    function getFilteredCategories() {
        let rows = [...categories];
        const q = norm(el('categorySearch')?.value || '');
        if (q) rows = rows.filter(item => norm(`${categoryCode(item.MaTheLoai)} ${item.TenTheLoai} ${item.MoTa || ''}`).includes(q));
        const topByTitles = [...categories].sort((a, b) => Number(b.SoDauSach || 0) - Number(a.SoDauSach || 0)).slice(0, 5).map(item => Number(item.MaTheLoai));
        const topByStock = [...categories].sort((a, b) => Number(b.TongSoLuong || 0) - Number(a.TongSoLuong || 0)).slice(0, 5).map(item => Number(item.MaTheLoai));
        const lowBooks = [...categories].sort((a, b) => Number(a.SoDauSach || 0) - Number(b.SoDauSach || 0) || Number(a.TongSoLuong || 0) - Number(b.TongSoLuong || 0)).slice(0, 5).map(item => Number(item.MaTheLoai));
        if (currentChip === 'top_titles') rows = rows.filter(item => topByTitles.includes(Number(item.MaTheLoai)));
        if (currentChip === 'top_stock') rows = rows.filter(item => topByStock.includes(Number(item.MaTheLoai)));
        if (currentChip === 'low_books') rows = rows.filter(item => lowBooks.includes(Number(item.MaTheLoai)));

        switch (currentSort) {
            case 'many_titles': rows.sort((a, b) => Number(b.SoDauSach || 0) - Number(a.SoDauSach || 0) || norm(a.TenTheLoai).localeCompare(norm(b.TenTheLoai))); break;
            case 'few_titles': rows.sort((a, b) => Number(a.SoDauSach || 0) - Number(b.SoDauSach || 0) || norm(a.TenTheLoai).localeCompare(norm(b.TenTheLoai))); break;
            case 'many_books': rows.sort((a, b) => Number(b.TongSoLuong || 0) - Number(a.TongSoLuong || 0) || Number(b.SoDauSach || 0) - Number(a.SoDauSach || 0)); break;
            case 'az': rows.sort((a, b) => String(a.TenTheLoai || '').localeCompare(String(b.TenTheLoai || ''), 'vi')); break;
            default: rows.sort((a, b) => Number(b.MaTheLoai || 0) - Number(a.MaTheLoai || 0));
        }
        return rows;
    }
    function animateCount(node, value) {
        if (!node) return;
        const end = Number(value || 0);
        const start = Number(node.dataset.current || 0);
        const duration = 700;
        const begin = performance.now();
        function frame(now) {
            const p = clamp((now - begin) / duration, 0, 1);
            const current = Math.round(start + (end - start) * (1 - Math.pow(1 - p, 3)));
            node.textContent = formatNumber(current);
            if (p < 1) requestAnimationFrame(frame);
            else node.dataset.current = String(end);
        }
        requestAnimationFrame(frame);
    }

    function renderFeatured() {
        const track = el('categoryFeaturedTrack'); if (!track) return;
        const rows = [...categories].sort((a, b) => Number(b.SoDauSach || 0) - Number(a.SoDauSach || 0) || Number(b.TongSoLuong || 0) - Number(a.TongSoLuong || 0)).slice(0, 10);
        track.innerHTML = rows.map(item => {
            const theme = genreTheme(item.TenTheLoai || 'Thể loại');
            const status = categoryStatus(item);
            return `<article class="catx-featured-card" data-open-drawer="${item.MaTheLoai}" style="--g1:${theme.palette[0]};--g2:${theme.palette[1]};--g3:${theme.palette[2]}">
                <div class="catx-featured-art"><img src="${esc(categoryArtSrc(item.TenTheLoai || 'Thể loại'))}" alt="${esc(item.TenTheLoai || 'Thể loại')}" loading="lazy"></div>
                <div class="catx-featured-body">
                    <span class="catx-inline-badge ${status.className}">${status.label}</span>
                    <h4>${esc(item.TenTheLoai || '--')}</h4>
                    <p>${esc(rowDescription(item.MoTa)).slice(0, 92)}</p>
                    <div class="catx-featured-meta"><span><i class="fa-solid fa-book"></i> ${formatNumber(item.SoDauSach)} đầu sách</span><span><i class="fa-solid fa-books"></i> ${formatNumber(item.TongSoLuong)} cuốn</span></div>
                </div>
            </article>`;
        }).join('');
        track.querySelectorAll('[data-open-drawer]').forEach(node => node.addEventListener('click', () => {
            openDrawer(node.dataset.openDrawer);
            if (advancedOpen) closeAdvanced();
        }));
    }

    function tableRowMarkup(item, index) {
        const theme = genreTheme(item.TenTheLoai || 'Thể loại');
        const status = categoryStatus(item);
        const bookPercent = clamp(Number(item.TongSoLuong || 0) / maxBookCount() * 100, 0, 100);
        return `<tr>
            <td>${index + 1}</td>
            <td>
                <button type="button" class="catx-category-cell" data-open-drawer="${item.MaTheLoai}">
                    <span class="catx-category-thumb" style="--t1:${theme.palette[0]};--t2:${theme.palette[2]}"><i class="fa-solid ${theme.icon}"></i></span>
                    <span class="catx-category-text"><strong>${esc(item.TenTheLoai || '--')}</strong><small>${categoryCode(item.MaTheLoai)}</small></span>
                </button>
            </td>
            <td><div class="catx-desc">${esc(rowDescription(item.MoTa))}</div></td>
            <td><span class="catx-badge-count">${formatNumber(item.SoDauSach)} đầu sách</span></td>
            <td>
                <div class="catx-progress-wrap">
                    <div class="catx-progress-top"><span>${formatNumber(item.TongSoLuong)} cuốn</span><small>${bookCountLabel(Number(item.TongSoLuong || 0))}</small></div>
                    <div class="catx-progress"><span style="width:${bookPercent}%"></span></div>
                </div>
            </td>
            ${canManageCategories ? `<td><div class="catx-action-pill"><button type="button" class="catx-action-btn view" title="Xem chi tiết" data-open-drawer="${item.MaTheLoai}"><i class="fa-solid fa-eye"></i><span>Xem</span></button><button type="button" class="catx-action-btn edit" title="Sửa" data-edit="${item.MaTheLoai}"><i class="fa-solid fa-pen"></i><span>Sửa</span></button><button type="button" class="catx-action-btn delete" title="Xóa" data-delete="${item.MaTheLoai}"><i class="fa-solid fa-trash"></i><span>Xóa</span></button></div></td>` : ''}
        </tr>`;
    }
    function cardMarkup(item) {
        const theme = genreTheme(item.TenTheLoai || 'Thể loại');
        const status = categoryStatus(item);
        return `<article class="catx-category-card" style="--g1:${theme.palette[0]};--g2:${theme.palette[1]};--g3:${theme.palette[2]}">
            <div class="catx-card-art"><img src="${esc(categoryArtSrc(item.TenTheLoai || 'Thể loại'))}" alt="${esc(item.TenTheLoai || 'Thể loại')}" loading="lazy"></div>
            <div class="catx-card-body">
                <div class="catx-card-head"><span class="catx-inline-badge ${status.className}">${status.label}</span><span class="catx-card-code">${categoryCode(item.MaTheLoai)}</span></div>
                <h4>${esc(item.TenTheLoai || '--')}</h4>
                <p>${esc(rowDescription(item.MoTa))}</p>
                <div class="catx-card-stats"><span><i class="fa-solid fa-book"></i> ${formatNumber(item.SoDauSach)} đầu sách</span><span><i class="fa-solid fa-books"></i> ${formatNumber(item.TongSoLuong)} cuốn</span></div>
                <div class="catx-card-actions"><button type="button" class="btn btn-outline-primary btn-sm" data-open-drawer="${item.MaTheLoai}"><i class="fa-solid fa-eye me-1"></i>Xem</button>${canManageCategories ? `<button type="button" class="btn btn-primary btn-sm" data-edit="${item.MaTheLoai}"><i class="fa-solid fa-pen me-1"></i>Sửa</button>` : ''}</div>
            </div>
        </article>`;
    }

    function bindRenderEvents(scope) {
        scope.querySelectorAll('[data-open-drawer]').forEach(btn => btn.addEventListener('click', ev => {
            ev.stopPropagation();
            openDrawer(btn.dataset.openDrawer);
        }));
        if (canManageCategories) {
            scope.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', ev => {
                ev.stopPropagation();
                openModal(btn.dataset.edit);
            }));
            scope.querySelectorAll('[data-delete]').forEach(btn => btn.addEventListener('click', ev => {
                ev.stopPropagation();
                remove(Number(btn.dataset.delete));
            }));
        }
    }

    function renderInsights() {
        const totalBooks = categories.reduce((sum, item) => sum + Number(item.TongSoLuong || 0), 0);
        const top = [...categories].sort((a, b) => Number(b.TongSoLuong || 0) - Number(a.TongSoLuong || 0)).slice(0, 6);
        const colors = ['#2563eb', '#7c3aed', '#0ea5e9', '#14b8a6', '#f59e0b', '#ef4444'];
        const totalForChart = Math.max(1, top.reduce((sum, item) => sum + Number(item.TongSoLuong || 0), 0));
        let angle = 0;
        const parts = top.map((item, idx) => {
            const pct = Number(item.TongSoLuong || 0) / totalForChart * 100;
            const segment = `${colors[idx]} ${angle}% ${angle + pct}%`;
            angle += pct;
            return segment;
        });
        if (angle < 100) parts.push(`#dbeafe ${angle}% 100%`);
        const donut = el('categoryDonutChart');
        if (donut) donut.style.background = `conic-gradient(${parts.join(',')})`;
        const legend = el('categoryDonutLegend');
        if (legend) legend.innerHTML = top.map((item, idx) => `<div class="catx-legend-item"><span class="dot" style="background:${colors[idx]}"></span><div><strong>${esc(item.TenTheLoai || '--')}</strong><small>${formatNumber(item.TongSoLuong)} cuốn · ${formatNumber(item.SoDauSach)} đầu sách</small></div></div>`).join('');
        if (el('categoryDonutTotal')) el('categoryDonutTotal').textContent = formatNumber(totalBooks);

        const topList = el('categoryTopList');
        const maxTop = Math.max(1, ...categories.map(item => Number(item.SoDauSach || 0)));
        if (topList) topList.innerHTML = [...categories].sort((a, b) => Number(b.SoDauSach || 0) - Number(a.SoDauSach || 0) || Number(b.TongSoLuong || 0) - Number(a.TongSoLuong || 0)).slice(0, 5).map((item, idx) => {
            const pct = clamp(Number(item.SoDauSach || 0) / maxTop * 100, 4, 100);
            return `<div class="catx-bar-item"><div class="catx-bar-label"><strong>#${idx + 1} ${esc(item.TenTheLoai || '--')}</strong><span>${formatNumber(item.SoDauSach)} đầu sách</span></div><div class="catx-bar-track"><span style="width:${pct}%"></span></div><small>${formatNumber(item.TongSoLuong)} cuốn trong kho</small></div>`;
        }).join('');

        const lowList = el('categoryLowList');
        if (lowList) lowList.innerHTML = [...categories].sort((a, b) => Number(a.SoDauSach || 0) - Number(b.SoDauSach || 0) || Number(a.TongSoLuong || 0) - Number(b.TongSoLuong || 0)).slice(0, 5).map(item => `<div class="catx-mini-row"><div><strong>${esc(item.TenTheLoai || '--')}</strong><small>${categoryCode(item.MaTheLoai)}</small></div><div class="catx-mini-values"><span>${formatNumber(item.SoDauSach)} đầu</span><b>${formatNumber(item.TongSoLuong)} cuốn</b></div></div>`).join('');
    }

    function renderDrawer(item) {
        const body = el('categoryDrawerBody'); if (!body) return;
        const theme = genreTheme(item.TenTheLoai || 'Thể loại');
        const status = categoryStatus(item);
        const topBooks = topBooksByCategory(item.MaTheLoai, 5);
        body.innerHTML = `<div class="catx-drawer-art"><img src="${esc(categoryArtSrc(item.TenTheLoai || 'Thể loại'))}" alt="${esc(item.TenTheLoai || 'Thể loại')}" loading="lazy"></div>
            <div class="catx-drawer-copy">
                <span class="catx-inline-badge ${status.className}">${status.label}</span>
                <h3>${esc(item.TenTheLoai || '--')}</h3>
                <p>${esc(rowDescription(item.MoTa))}</p>
                <div class="catx-drawer-meta">
                    <div><span>Mã thể loại</span><strong>${categoryCode(item.MaTheLoai)}</strong></div>
                    <div><span>Đầu sách</span><strong>${formatNumber(item.SoDauSach)}</strong></div>
                    <div><span>Tổng số lượng</span><strong>${formatNumber(item.TongSoLuong)}</strong></div>
                    <div><span>Nhóm gợi ý</span><strong><i class="fa-solid ${theme.icon}"></i> ${esc(item.TenTheLoai || '--')}</strong></div>
                </div>
                <div class="catx-drawer-list"><h4>Top sách trong thể loại</h4>${topBooks.length ? topBooks.map((book, idx) => `<div class="catx-book-row"><span class="num">${idx + 1}</span><div class="book-copy"><strong>${esc(book.TenSach || '--')}</strong><small>${esc(book.TacGia || 'Chưa rõ tác giả')}</small></div><span class="qty">${formatNumber(book.SoLuong)} cuốn</span></div>`).join('') : '<div class="catx-empty-note">Chưa có sách trong thể loại này.</div>'}</div>
                ${canManageCategories ? `<div class="catx-drawer-actions"><button type="button" class="btn btn-primary" id="drawerEditBtn"><i class="fa-solid fa-pen me-2"></i>Sửa</button><button type="button" class="btn btn-danger" id="drawerDeleteBtn"><i class="fa-solid fa-trash me-2"></i>Xóa</button></div>` : ''}
            </div>`;
        if (canManageCategories) {
            el('drawerEditBtn')?.addEventListener('click', () => { closeDrawer(); openModal(item.MaTheLoai); });
            el('drawerDeleteBtn')?.addEventListener('click', () => remove(Number(item.MaTheLoai)));
        }
    }

    function render() {
        const data = getFilteredCategories();
        const resultText = el('categoryResultText');
        if (resultText) resultText.textContent = `Hiển thị ${formatNumber(data.length)} / ${formatNumber(categories.length)} thể loại`;

        const body = el('categoryTableBody');
        if (body) {
            body.innerHTML = data.length ? data.map(tableRowMarkup).join('') : `<tr><td colspan="${canManageCategories ? 6 : 5}" class="text-center py-5">Chưa có thể loại phù hợp với bộ lọc hiện tại.</td></tr>`;
            bindRenderEvents(body);
        }
        const grid = el('categoryCardGrid');
        if (grid) {
            grid.innerHTML = data.length ? data.map(cardMarkup).join('') : `<div class="catx-empty-grid">Chưa có thể loại phù hợp với bộ lọc hiện tại.</div>`;
            bindRenderEvents(grid);
        }
    }

    function renderHero(summary) {
        animateCount(el('categoryCount'), summary.totalCategories || 0);
        animateCount(el('categoryTitleCount'), summary.totalTitles || 0);
        animateCount(el('categoryBookCount'), summary.totalBooks || 0);
        animateCount(el('heroTotalTitles'), summary.totalTitles || 0);
        animateCount(el('heroTotalBooks'), summary.totalBooks || 0);
        const topCategory = [...categories].sort((a, b) => Number(b.SoDauSach || 0) - Number(a.SoDauSach || 0))[0]?.TenTheLoai || '--';
        if (el('heroTopCategory')) el('heroTopCategory').textContent = topCategory;
        if (el('categoryTodayText')) el('categoryTodayText').innerHTML = `<i class="fa-regular fa-calendar"></i> ${todayText()}`;
    }

    function downloadCsv() {
        const rows = [['Mã thể loại', 'Tên thể loại', 'Mô tả', 'Đầu sách', 'Tổng số lượng']]
            .concat(getFilteredCategories().map(item => [categoryCode(item.MaTheLoai), item.TenTheLoai || '', item.MoTa || '', Number(item.SoDauSach || 0), Number(item.TongSoLuong || 0)]));
        const csv = rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'the-loai-thu-vien.csv';
        a.click();
        URL.revokeObjectURL(url);
    }

    function openAdvanced() {
        advancedOpen = true;
        const manyTitles = document.querySelector('.catx-chip[data-chip="top_titles"]');
        manyTitles?.click();
        el('categorySort').value = 'many_titles';
        currentSort = 'many_titles';
    }
    function closeAdvanced() {
        advancedOpen = false;
    }

    async function reload() {
        setBusy(true);
        try {
            const [categoryPayload, bookPayload] = await Promise.all([
                libraryApi.get('categories'),
                libraryApi.get('books')
            ]);
            categories = categoryPayload.data.categories || [];
            books = bookPayload.data.books || [];
            renderHero(categoryPayload.data.summary || {});
            renderFeatured();
            renderInsights();
            render();
        } catch (error) {
            notify(error.message || 'Không thể tải dữ liệu thể loại.');
        } finally {
            setBusy(false);
        }
    }

    async function save(update) {
        if (!canManageCategories) return notify('Bạn chỉ có quyền xem thể loại.');
        if (busy) return;
        const name = String(el('categoryName')?.value || '').trim();
        if (!name) return notify('Vui lòng nhập tên thể loại.');
        if (update && !selectedId) return notify('Hãy chọn thể loại cần cập nhật.');
        setBusy(true);
        try {
            const payload = await libraryApi.post('category_save', { id: update ? selectedId : null, name, description: String(el('categoryDescription')?.value || '').trim() });
            notify(payload.message || 'Đã lưu thể loại.');
            closeModal();
            resetForm();
            await reload();
        } catch (error) {
            notify(error.message || 'Không thể lưu thể loại.');
        } finally {
            setBusy(false);
        }
    }
    async function remove(id = selectedId) {
        if (!canManageCategories) return notify('Bạn chỉ có quyền xem thể loại.');
        if (busy || !id) return notify('Hãy chọn thể loại cần xóa.');
        if (!(await window.libraryConfirm('Bạn có chắc muốn xóa thể loại này?','Xóa thể loại'))) return;
        setBusy(true);
        try {
            const payload = await libraryApi.post('category_delete', { id });
            notify(payload.message || 'Đã xóa thể loại.');
            closeDrawer();
            closeModal();
            resetForm();
            await reload();
        } catch (error) {
            notify(error.message || 'Không thể xóa thể loại.');
        } finally {
            setBusy(false);
        }
    }

    el('categorySearch')?.addEventListener('input', render);
    el('categorySort')?.addEventListener('change', ev => { currentSort = ev.target.value; render(); });
    document.querySelectorAll('.catx-chip').forEach(btn => btn.addEventListener('click', () => setChip(btn.dataset.chip || 'all')));
    document.querySelectorAll('.catx-view-switch button').forEach(btn => btn.addEventListener('click', () => setView(btn.dataset.view || 'table')));
    el('categoryRefreshBtn')?.addEventListener('click', () => reload());
    el('categoryExportBtn')?.addEventListener('click', downloadCsv);
    el('categoryAdvancedBtn')?.addEventListener('click', openAdvanced);
    el('categoryResetFilterBtn')?.addEventListener('click', () => {
        currentSort = 'newest'; currentChip = 'all'; closeAdvanced();
        if (el('categorySort')) el('categorySort').value = 'newest';
        if (el('categorySearch')) el('categorySearch').value = '';
        document.querySelectorAll('.catx-chip').forEach(btn => btn.classList.toggle('active', btn.dataset.chip === 'all'));
        render();
    });
    el('categoryAddBtn')?.addEventListener('click', () => openModal());
    el('categorySaveBtn')?.addEventListener('click', () => save(false));
    el('categoryUpdateBtn')?.addEventListener('click', () => save(true));
    el('categoryDeleteBtn')?.addEventListener('click', () => remove());
    el('categoryResetBtn')?.addEventListener('click', resetForm);
    document.querySelectorAll('[data-close-modal]').forEach(btn => btn.addEventListener('click', closeModal));
    document.querySelectorAll('[data-close-drawer]').forEach(btn => btn.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', ev => {
        if (ev.key === 'Escape') { closeModal(); closeDrawer(); }
    });

    setView('table');
    setChip('all');
    resetForm();
    reload();
})();
