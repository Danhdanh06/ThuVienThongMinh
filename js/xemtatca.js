(() => {
    const root = document.getElementById('dashboardFullList');
    if (!root) return;
    const type = root.dataset.listType || 'popular';
    const search = document.getElementById('dashboardListSearch');
    const head = document.getElementById('dashboardListHead');
    const body = document.getElementById('dashboardListBody');
    const heading = document.getElementById('dashboardListHeading');
    const count = document.getElementById('dashboardListCount');
    let rows = [];

    function localToday() {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }
    function status(loan) {
        if (loan.NgayTra) return ['Đã trả','status-success'];
        if (loan.HanTra && loan.HanTra < localToday()) return ['Quá hạn','status-danger'];
        return [loan.TrangThai || 'Đang mượn','status-success'];
    }
    function render() {
        const q = normalizeLibraryText(search?.value || '');
        const filtered = rows.filter(row => !q || normalizeLibraryText(Object.values(row).join(' ')).includes(q));
        count.textContent = `${filtered.length} mục`;

        if (type === 'popular') {
            head.innerHTML = '<tr><th>STT</th><th>Mã sách</th><th>Tên sách</th><th>Tác giả</th><th>Thể loại</th><th>NXB</th><th>Năm XB</th><th>Lượt mượn</th></tr>';
            body.innerHTML = filtered.length ? filtered.map((r,i)=>`<tr data-open="sach.php"><td>${i+1}</td><td>S${String(r.MaSach).padStart(3,'0')}</td><td>${escapeHTML(r.TenSach||'--')}</td><td>${escapeHTML(r.TacGia||'--')}</td><td>${escapeHTML(r.TenTheLoai||'--')}</td><td>${escapeHTML(r.NhaXuatBan||'--')}</td><td>${r.NamXuatBan||'--'}</td><td>${Number(r.LuotMuon||0)}</td></tr>`).join('') : '<tr><td colspan="8">Không có dữ liệu.</td></tr>';
        } else if (type === 'new') {
            head.innerHTML = '<tr><th>STT</th><th>Mã sách</th><th>Tên sách</th><th>Tác giả</th><th>Thể loại</th><th>NXB</th><th>Năm XB</th><th>Số lượng</th></tr>';
            body.innerHTML = filtered.length ? filtered.map((r,i)=>`<tr data-open="sach.php"><td>${i+1}</td><td>S${String(r.MaSach).padStart(3,'0')}</td><td>${escapeHTML(r.TenSach||'--')}</td><td>${escapeHTML(r.TacGia||'--')}</td><td>${escapeHTML(r.TenTheLoai||'--')}</td><td>${escapeHTML(r.NhaXuatBan||'--')}</td><td>${r.NamXuatBan||'--'}</td><td>${Number(r.SoLuong||0)}</td></tr>`).join('') : '<tr><td colspan="8">Không có dữ liệu.</td></tr>';
        } else {
            head.innerHTML = '<tr><th>STT</th><th>Mã phiếu</th><th>Độc giả</th><th>Sách</th><th>Ngày mượn</th><th>Hạn trả</th><th>Nhân viên</th><th>Trạng thái</th></tr>';
            body.innerHTML = filtered.length ? filtered.map((r,i)=>{const [text,cls]=status(r);return `<tr data-open="muontra.php"><td>${i+1}</td><td>PM${String(r.MaPhieuMuon).padStart(3,'0')}</td><td>${escapeHTML(r.DocGia||'--')}</td><td>${escapeHTML(r.SachMuon||'--')}</td><td>${formatLibraryDate(r.NgayMuon)}</td><td>${formatLibraryDate(r.HanTra)}</td><td>${escapeHTML(r.NhanVien||'--')}</td><td><span class="${cls}">${escapeHTML(text)}</span></td></tr>`}).join('') : '<tr><td colspan="8">Không có dữ liệu.</td></tr>';
        }
        body.querySelectorAll('tr[data-open]').forEach(row => {
            row.style.cursor='pointer';
            row.addEventListener('click',()=>loadPage(row.dataset.open, row.dataset.open==='sach.php'?'Sách':'Mượn - Trả'));
        });
    }
    async function init() {
        try {
            const payload = await libraryApi.get('dashboard_list', {type});
            rows = payload.data.rows || [];
            heading.textContent = payload.data.title || 'Danh sách';
            render();
        } catch (error) {
            heading.textContent = 'Không thể tải danh sách';
            body.innerHTML = `<tr><td>${escapeHTML(error.message)}</td></tr>`;
        }
    }
    document.getElementById('dashboardBackBtn')?.addEventListener('click',()=>loadPage('trangchu.php','Trang chủ'));
    search?.addEventListener('input', render);
    init();
})();
