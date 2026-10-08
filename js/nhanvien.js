(() => {
    const canManageEmployees = !!window.libraryAccess?.canManageEmployees;
    const canDeleteEmployees = !!window.libraryAccess?.canDeleteEmployees;
    const canManageSalary = !!window.libraryAccess?.canManageSalary;
    const canViewSalary = !!window.libraryAccess?.canViewSalaryAll || !!window.libraryAccess?.canViewSalarySelf;
    const selfOnly = window.libraryAccess?.roleKey === 'employee';

    let employees = [];
    let shifts = [];
    let assignments = [];
    let loans = [];
    let selectedId = null;
    let busy = false;
    let currentView = 'table';
    let currentChip = 'all';
    let currentSort = 'newest';
    const el = id => document.getElementById(id);
    const money = value => Number(value || 0).toLocaleString('vi-VN') + ' đ';
    const norm = value => window.normalizeLibraryText ? normalizeLibraryText(value || '') : String(value || '').toLowerCase();
    const esc = value => window.escapeHTML ? escapeHTML(value) : String(value ?? '');
    const todayISO = () => new Date().toLocaleDateString('en-CA');

    function notify(message) { if(window.libraryToast) window.libraryToast(message); else alert(message); }
    function fmtDate(value) { return window.formatLibraryDate ? formatLibraryDate(value) : (value || '--'); }
    function initials(name) {
        const parts = String(name || 'NV').trim().split(/\s+/).filter(Boolean);
        return (parts.slice(-2).map(x => x[0]).join('') || 'NV').toUpperCase();
    }
    function statusClass(status) {
        const s = String(status || '').toLowerCase();
        if (s.includes('đang làm')) return 'success';
        if (s.includes('khóa')) return 'danger';
        if (s.includes('nghỉ')) return 'warning';
        return 'neutral';
    }
    function statusIcon(status) {
        const cls = statusClass(status);
        return cls === 'success' ? 'fa-circle-check' : cls === 'warning' ? 'fa-user-clock' : cls === 'danger' ? 'fa-user-lock' : 'fa-circle-info';
    }
    function roleClass(role) {
        const r = String(role || '').toLowerCase();
        if (r.includes('admin')) return 'purple';
        if (r.includes('quản')) return 'blue';
        if (r.includes('thủ thư')) return 'cyan';
        return 'slate';
    }
    function setBusy(value) { busy = value; refreshActionButtons(); }
    function refreshActionButtons() {
        if (!canManageEmployees) return;
        const editing = selectedId !== null;
        if (el('employeeSaveBtn')) el('employeeSaveBtn').disabled = busy || editing;
        if (el('employeeUpdateBtn')) el('employeeUpdateBtn').disabled = busy || !editing;
        if (el('employeeDeleteBtn')) el('employeeDeleteBtn').disabled = busy || !editing || !canDeleteEmployees;
        if (el('employeeResetBtn')) el('employeeResetBtn').disabled = busy;
        if (el('addEmployeeBtn')) el('addEmployeeBtn').disabled = busy;
    }
    function animateCount(node, target) {
        if (!node) return;
        const start = Number(node.dataset.current || 0);
        const end = Number(target || 0);
        const t0 = performance.now();
        const duration = 700;
        function tick(now) {
            const p = Math.min(1, (now - t0) / duration);
            const eased = 1 - Math.pow(1 - p, 3);
            node.textContent = Math.round(start + (end - start) * eased).toLocaleString('vi-VN');
            if (p < 1) requestAnimationFrame(tick); else node.dataset.current = String(end);
        }
        requestAnimationFrame(tick);
    }
    function openModal(id = null) {
        if (!canManageEmployees) return;
        if (id) fillForm(id); else resetForm();
        el('employeeModal')?.classList.add('show');
        document.body.classList.add('modal-open');
        setTimeout(() => el('employeeName')?.focus(), 40);
    }
    function closeModal() { el('employeeModal')?.classList.remove('show'); document.body.classList.remove('modal-open'); }
    function openDrawer(id) {
        const row = employees.find(x => Number(x.MaNhanVien) === Number(id));
        if (!row) return;
        selectedId = Number(row.MaNhanVien);
        renderDrawer(row);
        el('employeeDrawer')?.classList.add('show');
        document.body.classList.add('drawer-open');
    }
    function closeDrawer() { el('employeeDrawer')?.classList.remove('show'); document.body.classList.remove('drawer-open'); }
    function resetForm() {
        selectedId = null;
        ['employeeId','employeeEmail','employeeName','employeeBirthDate','employeePhone'].forEach(id => { if (el(id)) el(id).value=''; });
        if (el('employeeGender')) el('employeeGender').value='Nam';
        if (el('employeeRole')) el('employeeRole').value='Nhân viên';
        if (el('employeeSalary')) el('employeeSalary').value='0';
        if (el('employeeHireDate')) el('employeeHireDate').value=todayISO();
        if (el('employeeStatus')) el('employeeStatus').value='Đang làm việc';
        if (el('employeeModalTitle')) el('employeeModalTitle').textContent='Thêm nhân viên';
        if (el('employeeFormAvatar')) el('employeeFormAvatar').textContent='NV';
        if (el('employeeFormAvatarName')) el('employeeFormAvatarName').textContent='Nhân viên mới';
        refreshActionButtons();
    }
    function fillForm(id) {
        const r = employees.find(x => Number(x.MaNhanVien) === Number(id));
        if (!r) return;
        selectedId = Number(r.MaNhanVien);
        el('employeeId').value = `NV${String(r.MaNhanVien).padStart(3,'0')}`;
        el('employeeEmail').value = r.Email || '';
        el('employeeName').value = r.HoTen || '';
        el('employeeGender').value = r.GioiTinh || 'Nam';
        el('employeeBirthDate').value = r.NgaySinh || '';
        el('employeePhone').value = r.SDT || '';
        el('employeeRole').value = r.VaiTro || 'Nhân viên';
        if (el('employeeSalary')) el('employeeSalary').value = Number(r.Luong || 0);
        el('employeeHireDate').value = r.NgayVaoLam || '';
        el('employeeStatus').value = r.TrangThai || 'Đang làm việc';
        if (el('employeeModalTitle')) el('employeeModalTitle').textContent='Chỉnh sửa nhân viên';
        if (el('employeeFormAvatar')) el('employeeFormAvatar').textContent=initials(r.HoTen);
        if (el('employeeFormAvatarName')) el('employeeFormAvatarName').textContent=r.HoTen || 'Nhân viên';
        refreshActionButtons();
    }
    function formData() {
        return {
            id: selectedId,
            name: el('employeeName')?.value.trim() || '',
            email: el('employeeEmail')?.value.trim() || '',
            gender: el('employeeGender')?.value || '',
            birthDate: el('employeeBirthDate')?.value || null,
            phone: el('employeePhone')?.value.trim() || '',
            role: el('employeeRole')?.value || 'Nhân viên',
            salary: canManageSalary ? Number(el('employeeSalary')?.value || 0) : 0,
            hireDate: el('employeeHireDate')?.value || null,
            status: el('employeeStatus')?.value || 'Đang làm việc'
        };
    }
    function validate(data) {
        if (!data.name) return 'Vui lòng nhập họ tên nhân viên.';
        if (data.phone && !/^[0-9+ .()-]{8,20}$/.test(data.phone)) return 'Số điện thoại không hợp lệ.';
        if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) return 'Email không hợp lệ.';
        if (data.birthDate && data.birthDate > todayISO()) return 'Ngày sinh không thể ở tương lai.';
        if (data.salary < 0) return 'Lương không hợp lệ.';
        return '';
    }
    function employeeAssignments(id) { return assignments.filter(a => Number(a.MaNhanVien) === Number(id)); }
    function employeeLoans(id) { return loans.filter(l => Number(l.MaNhanVien) === Number(id)); }
    function shiftsThisMonth(id) {
        const now = new Date(); const ym = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
        return employeeAssignments(id).filter(a => String(a.NgayLam || '').startsWith(ym));
    }
    function todayAssignments(id) { return employeeAssignments(id).filter(a => a.NgayLam === todayISO()); }
    function nextAssignment(id) { return employeeAssignments(id).filter(a => (a.NgayLam || '') >= todayISO()).sort((a,b) => String(a.NgayLam).localeCompare(String(b.NgayLam)) || String(a.GioBatDau).localeCompare(String(b.GioBatDau)))[0] || null; }

    function filtered() {
        let data = [...employees];
        if (selfOnly) return data;
        const q = norm(el('employeeSearch')?.value || '');
        const role = el('employeeRoleFilter')?.value || '';
        const status = el('employeeStatusFilter')?.value || '';
        const hireDate = el('employeeHireDateFilter')?.value || '';
        if (q) data = data.filter(r => norm(`${r.MaNhanVien} ${r.HoTen} ${r.Email || ''} ${r.SDT || ''}`).includes(q));
        if (role) data = data.filter(r => r.VaiTro === role);
        if (status) data = data.filter(r => r.TrangThai === status);
        if (hireDate) data = data.filter(r => r.NgayVaoLam === hireDate);
        if (currentChip === 'active') data = data.filter(r => statusClass(r.TrangThai) === 'success');
        if (currentChip === 'inactive') data = data.filter(r => ['warning','danger'].includes(statusClass(r.TrangThai)));
        if (currentChip === 'new') {
            const cutoff = new Date(); cutoff.setDate(cutoff.getDate()-90); const c = cutoff.toLocaleDateString('en-CA');
            data = data.filter(r => r.NgayVaoLam && r.NgayVaoLam >= c);
        }
        if (currentChip === 'today_shift') data = data.filter(r => todayAssignments(r.MaNhanVien).length > 0);
        if (currentSort === 'az') data.sort((a,b) => String(a.HoTen||'').localeCompare(String(b.HoTen||''),'vi'));
        else if (currentSort === 'salary_desc') data.sort((a,b) => Number(b.Luong||0)-Number(a.Luong||0));
        else data.sort((a,b) => String(b.NgayVaoLam||'').localeCompare(String(a.NgayVaoLam||'')) || Number(b.MaNhanVien)-Number(a.MaNhanVien));
        return data;
    }
    function renderMetrics() {
        const total = employees.length;
        const active = employees.filter(r => statusClass(r.TrangThai)==='success').length;
        const inactive = total-active;
        const todayCount = assignments.filter(a => a.NgayLam === todayISO()).length;
        animateCount(el('employeeTotalCount'),total); animateCount(el('employeeActiveCount'),active); animateCount(el('employeeInactiveCount'),inactive); animateCount(el('employeeTodayShiftCount'),todayCount);
        animateCount(el('heroActiveEmployees'),active); animateCount(el('heroTodayShifts'),todayCount);
        const roleCounts = {};
        employees.forEach(r => roleCounts[r.VaiTro || 'Khác']=(roleCounts[r.VaiTro||'Khác']||0)+1);
        const topRole = Object.entries(roleCounts).sort((a,b)=>b[1]-a[1])[0]?.[0] || '--';
        if (el('heroTopRole')) el('heroTopRole').textContent=topRole;
        if (el('employeeTodayText')) el('employeeTodayText').innerHTML=`<i class="fa-regular fa-calendar"></i> ${new Intl.DateTimeFormat('vi-VN',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'}).format(new Date())}`;
    }
    function renderAttention() {
        const target = el('employeeAttentionGrid'); if (!target) return;
        const newest = [...employees].sort((a,b)=>String(b.NgayVaoLam||'').localeCompare(String(a.NgayVaoLam||'')))[0];
        const inactive = employees.find(r => statusClass(r.TrangThai)!=='success');
        const withoutShift = employees.find(r => employeeAssignments(r.MaNhanVien).filter(a => a.NgayLam >= todayISO()).length===0);
        const mostShift = [...employees].sort((a,b)=>employeeAssignments(b.MaNhanVien).length-employeeAssignments(a.MaNhanVien).length)[0];
        const cards = [
            {label:'Nhân viên mới',emp:newest,value:newest?.NgayVaoLam?fmtDate(newest.NgayVaoLam):'--',icon:'fa-user-plus',tone:'blue'},
            {label:'Cần theo dõi',emp:inactive,value:inactive?.TrangThai||'Không có',icon:'fa-user-clock',tone:'orange'},
            {label:'Chưa có ca sắp tới',emp:withoutShift,value:withoutShift?'Chưa phân công':'Đã ổn',icon:'fa-calendar-xmark',tone:'red'},
            {label:'Nhiều ca nhất',emp:mostShift,value:mostShift?`${employeeAssignments(mostShift.MaNhanVien).length} ca`:'--',icon:'fa-calendar-check',tone:'purple'}
        ];
        target.innerHTML=cards.map(c=>`<article class="empx-attention-card ${c.tone}">${c.emp?`<button type="button" data-emp-open="${c.emp.MaNhanVien}"><span class="empx-mini-avatar">${initials(c.emp.HoTen)}</span><span><small>${c.label}</small><strong>${esc(c.emp.HoTen)}</strong><b>${esc(c.value)}</b></span><i class="fa-solid ${c.icon}"></i></button>`:`<div class="empx-attention-empty"><i class="fa-solid ${c.icon}"></i><span><small>${c.label}</small><strong>Chưa có dữ liệu</strong></span></div>`}</article>`).join('');
        target.querySelectorAll('[data-emp-open]').forEach(btn=>btn.addEventListener('click',()=>openDrawer(btn.dataset.empOpen)));
    }
    function renderStats() {
        const male=employees.filter(r=>r.GioiTinh==='Nam').length, female=employees.filter(r=>r.GioiTinh==='Nữ').length, other=Math.max(0,employees.length-male-female), total=Math.max(1,employees.length);
        const p1=male/total*100,p2=female/total*100;
        const donut=el('employeeGenderDonut'); if(donut) donut.style.background=`conic-gradient(#2563eb 0 ${p1}%,#ec4899 ${p1}% ${p1+p2}%,#94a3b8 ${p1+p2}% 100%)`;
        if(el('employeeGenderTotal')) el('employeeGenderTotal').textContent=employees.length;
        if(el('employeeGenderLegend')) el('employeeGenderLegend').innerHTML=`<span><i style="background:#2563eb"></i>Nam <b>${male}</b></span><span><i style="background:#ec4899"></i>Nữ <b>${female}</b></span><span><i style="background:#94a3b8"></i>Khác <b>${other}</b></span>`;
        const roleCounts={}; employees.forEach(r=>roleCounts[r.VaiTro||'Khác']=(roleCounts[r.VaiTro||'Khác']||0)+1);
        if(el('employeeRoleStats')) el('employeeRoleStats').innerHTML=Object.entries(roleCounts).sort((a,b)=>b[1]-a[1]).map(([role,count])=>`<span><em>${esc(role)}</em><b>${count}</b></span>`).join('') || '<span>Chưa có dữ liệu</span>';
    }
    function rowMarkup(r,i){
        const monthShifts=shiftsThisMonth(r.MaNhanVien).length; const processed=employeeLoans(r.MaNhanVien).length;
        return `<tr><td>${i+1}</td><td><button class="empx-person-cell" type="button" data-emp-open="${r.MaNhanVien}"><span class="empx-avatar">${initials(r.HoTen)}</span><span><strong>${esc(r.HoTen)}</strong><small>NV${String(r.MaNhanVien).padStart(3,'0')} • ${esc(r.SDT||'--')} • ${esc(r.Email||'--')}</small></span></button></td><td>${esc(r.GioiTinh||'--')}</td><td>${fmtDate(r.NgaySinh)}</td><td><span class="empx-role-badge ${roleClass(r.VaiTro)}">${esc(r.VaiTro||'--')}</span></td>${canViewSalary?`<td><strong class="empx-salary">${money(r.Luong)}</strong></td>`:''}<td>${fmtDate(r.NgayVaoLam)}</td><td><span class="empx-status-badge ${statusClass(r.TrangThai)}"><i class="fa-solid ${statusIcon(r.TrangThai)}"></i>${esc(r.TrangThai||'--')}</span></td><td><span class="empx-number-badge">${monthShifts}</span></td><td><span class="empx-number-badge">${processed}</span></td>${canManageEmployees?`<td><div class="empx-actions"><button type="button" class="view" data-emp-open="${r.MaNhanVien}" title="Xem"><i class="fa-solid fa-eye"></i></button><button type="button" class="shift" data-emp-shift="${r.MaNhanVien}" title="Ca làm"><i class="fa-solid fa-calendar-days"></i></button><button type="button" class="edit" data-emp-edit="${r.MaNhanVien}" title="Sửa"><i class="fa-solid fa-pen"></i></button>${canDeleteEmployees?`<button type="button" class="delete" data-emp-delete="${r.MaNhanVien}" title="Xóa"><i class="fa-solid fa-trash"></i></button>`:''}</div></td>`:''}</tr>`;
    }
    function cardMarkup(r){const monthShifts=shiftsThisMonth(r.MaNhanVien).length,processed=employeeLoans(r.MaNhanVien).length;return `<article class="empx-employee-card"><div class="empx-card-top"><span class="empx-avatar big">${initials(r.HoTen)}</span><span class="empx-status-badge ${statusClass(r.TrangThai)}"><i class="fa-solid ${statusIcon(r.TrangThai)}"></i>${esc(r.TrangThai||'--')}</span></div><h4>${esc(r.HoTen)}</h4><p>NV${String(r.MaNhanVien).padStart(3,'0')} · ${esc(r.VaiTro||'--')}</p><div class="empx-card-info"><span><i class="fa-solid fa-phone"></i>${esc(r.SDT||'--')}</span><span><i class="fa-solid fa-envelope"></i>${esc(r.Email||'--')}</span><span><i class="fa-solid fa-calendar"></i>Vào làm ${fmtDate(r.NgayVaoLam)}</span>${canViewSalary?`<span><i class="fa-solid fa-money-bill-wave"></i>${money(r.Luong)}</span>`:''}</div><div class="empx-card-mini"><span><b>${monthShifts}</b> ca tháng này</span><span><b>${processed}</b> phiếu xử lý</span></div><div class="empx-card-actions"><button type="button" class="btn btn-outline-primary btn-sm" data-emp-open="${r.MaNhanVien}"><i class="fa-solid fa-eye me-1"></i>Xem</button>${canManageEmployees?`<button type="button" class="btn btn-primary btn-sm" data-emp-edit="${r.MaNhanVien}"><i class="fa-solid fa-pen me-1"></i>Sửa</button>`:''}</div></article>`}
    function bindListEvents(scope){
        scope.querySelectorAll('[data-emp-open]').forEach(b=>b.addEventListener('click',()=>openDrawer(b.dataset.empOpen)));
        scope.querySelectorAll('[data-emp-edit]').forEach(b=>b.addEventListener('click',()=>openModal(b.dataset.empEdit)));
        scope.querySelectorAll('[data-emp-delete]').forEach(b=>b.addEventListener('click',()=>removeEmployee(Number(b.dataset.empDelete))));
        scope.querySelectorAll('[data-emp-shift]').forEach(b=>b.addEventListener('click',()=>{sessionStorage.setItem('employeeShiftFocus',b.dataset.empShift);loadPage('calamviec.php','Ca làm việc');}));
    }
    function render(){
        const data=filtered();
        if(el('employeeResultText')) el('employeeResultText').textContent=`Hiển thị ${data.length} / ${employees.length} nhân viên`;
        const body=el('employeeTableBody'); if(body){const cols=9+(canViewSalary?1:0)+(canManageEmployees?1:0);body.innerHTML=data.length?data.map(rowMarkup).join(''):`<tr><td colspan="${cols}" class="employee-empty"><div class="employee-empty-icon"><i class="fa-solid fa-users"></i></div><p>Chưa có nhân viên phù hợp</p></td></tr>`;bindListEvents(body)}
        const grid=el('employeeCardGrid'); if(grid){grid.innerHTML=data.length?data.map(cardMarkup).join(''):'<div class="empx-empty">Chưa có nhân viên phù hợp.</div>';bindListEvents(grid)}
    }
    function renderDrawer(r){
        const today=todayAssignments(r.MaNhanVien), next=nextAssignment(r.MaNhanVien), month=shiftsThisMonth(r.MaNhanVien), processed=employeeLoans(r.MaNhanVien);
        const recentProcessed=[...processed].sort((a,b)=>Number(b.MaPhieuMuon)-Number(a.MaPhieuMuon)).slice(0,4);
        const body=el('employeeDrawerBody'); if(!body)return;
        body.innerHTML=`<div class="empx-drawer-profile"><span class="empx-avatar huge">${initials(r.HoTen)}</span><div><span class="empx-role-badge ${roleClass(r.VaiTro)}">${esc(r.VaiTro||'--')}</span><h3>${esc(r.HoTen)}</h3><p>NV${String(r.MaNhanVien).padStart(3,'0')}</p></div></div><div class="empx-drawer-meta"><div><span>Giới tính</span><strong>${esc(r.GioiTinh||'--')}</strong></div><div><span>Ngày sinh</span><strong>${fmtDate(r.NgaySinh)}</strong></div><div><span>Số điện thoại</span><strong>${esc(r.SDT||'--')}</strong></div><div><span>Email</span><strong>${esc(r.Email||'--')}</strong></div><div><span>Ngày vào làm</span><strong>${fmtDate(r.NgayVaoLam)}</strong></div><div><span>Trạng thái</span><strong>${esc(r.TrangThai||'--')}</strong></div>${canViewSalary?`<div><span>Lương</span><strong>${money(r.Luong)}</strong></div>`:''}<div><span>Phiếu đã xử lý</span><strong>${processed.length}</strong></div></div><section class="empx-drawer-section"><h4>Ca làm</h4><div class="empx-shift-summary"><div><span>Hôm nay</span><strong>${today.length?today.map(a=>esc(a.TenCa||'Ca làm')).join(', '):'Không có ca'}</strong></div><div><span>Ca sắp tới</span><strong>${next?`${fmtDate(next.NgayLam)} · ${esc(next.TenCa||'Ca')}`:'Chưa có'}</strong></div><div><span>Tổng ca tháng này</span><strong>${month.length}</strong></div></div></section><section class="empx-drawer-section"><h4>Phiếu xử lý gần đây</h4>${recentProcessed.length?recentProcessed.map(l=>`<div class="empx-loan-row"><span>PM${String(l.MaPhieuMuon).padStart(3,'0')}</span><div><strong>${esc(l.TenDocGia||'--')}</strong><small>${fmtDate(l.NgayMuon)} · ${esc(l.TrangThai||'--')}</small></div></div>`).join(''):'<div class="empx-empty-note">Chưa có phiếu được ghi nhận cho nhân viên này.</div>'}</section><div class="empx-drawer-actions"><button class="btn btn-outline-primary" id="drawerShiftBtn"><i class="fa-solid fa-calendar-days me-2"></i>Ca làm</button>${canManageEmployees?`<button class="btn btn-primary" id="drawerEditEmployeeBtn"><i class="fa-solid fa-pen me-2"></i>Sửa hồ sơ</button>`:''}</div>`;
        el('drawerShiftBtn')?.addEventListener('click',()=>{sessionStorage.setItem('employeeShiftFocus',r.MaNhanVien);loadPage('calamviec.php','Ca làm việc')});
        el('drawerEditEmployeeBtn')?.addEventListener('click',()=>{closeDrawer();openModal(r.MaNhanVien)});
    }
    function renderAll(){renderMetrics();renderAttention();renderStats();render()}
    async function reload(){
        try{
            const empPayload=await libraryApi.get('employees'); employees=(empPayload.data||[]);
            const extra=await Promise.allSettled([libraryApi.get('shifts'),libraryApi.get('loans')]);
            if(extra[0].status==='fulfilled'){shifts=extra[0].value.data?.shifts||[];assignments=extra[0].value.data?.assignments||extra[0].value.data?.rosterAssignments||[]}
            else{shifts=[];assignments=[]}
            if(extra[1].status==='fulfilled') loans=extra[1].value.data?.loans||[]; else loans=[];
            renderAll();
        }catch(error){notify(error.message)}
    }
    async function createEmployee(){if(!canManageEmployees)return notify('Bạn chỉ có quyền xem nhân viên.');if(busy)return;if(selectedId!==null)return notify('Bạn đang sửa một nhân viên. Hãy bấm Cập nhật hoặc Làm mới trước khi thêm mới.');const data=formData();data.id=null;const error=validate(data);if(error)return notify(error);setBusy(true);try{const payload=await libraryApi.post('employee_save',data);notify(payload.message);closeModal();resetForm();await reload()}catch(error){notify(error.message)}finally{setBusy(false)}}
    async function updateEmployee(){if(!canManageEmployees)return notify('Bạn chỉ có quyền xem nhân viên.');if(busy)return;if(selectedId===null)return notify('Hãy chọn nhân viên cần cập nhật.');const data=formData();data.id=selectedId;const error=validate(data);if(error)return notify(error);setBusy(true);try{const payload=await libraryApi.post('employee_save',data);notify(payload.message);closeModal();resetForm();await reload()}catch(error){notify(error.message)}finally{setBusy(false)}}
    async function removeEmployee(id=selectedId){if(!canDeleteEmployees)return notify('Bạn không có quyền xóa nhân viên.');if(busy||!id)return notify('Hãy chọn nhân viên cần xóa.');if(!(await window.libraryConfirm('Bạn có chắc muốn xóa nhân viên này?','Xóa nhân viên')))return;setBusy(true);try{const payload=await libraryApi.post('employee_delete',{id});notify(payload.message);closeDrawer();closeModal();resetForm();await reload()}catch(error){notify(error.message)}finally{setBusy(false)}}
    function exportCsv(){const rows=[['Mã NV','Họ tên','Giới tính','Ngày sinh','SĐT','Email','Vai trò',...(canViewSalary?['Lương']:[]),'Ngày vào làm','Trạng thái']].concat(filtered().map(r=>[`NV${String(r.MaNhanVien).padStart(3,'0')}`,r.HoTen||'',r.GioiTinh||'',r.NgaySinh||'',r.SDT||'',r.Email||'',r.VaiTro||'',...(canViewSalary?[Number(r.Luong||0)]:[]),r.NgayVaoLam||'',r.TrangThai||'']));const csv=rows.map(row=>row.map(x=>`"${String(x).replace(/"/g,'""')}"`).join(',')).join('\n');const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='danh-sach-nhan-vien.csv';a.click();URL.revokeObjectURL(url)}

    el('employeeSaveBtn')?.addEventListener('click',createEmployee);el('employeeUpdateBtn')?.addEventListener('click',updateEmployee);el('employeeDeleteBtn')?.addEventListener('click',()=>removeEmployee());el('employeeResetBtn')?.addEventListener('click',resetForm);el('addEmployeeBtn')?.addEventListener('click',()=>openModal());el('employeeExportBtn')?.addEventListener('click',exportCsv);el('employeeRefreshBtn')?.addEventListener('click',reload);
    ['employeeSearch','employeeRoleFilter','employeeStatusFilter','employeeHireDateFilter','employeeSort'].forEach(id=>el(id)?.addEventListener(id==='employeeSearch'?'input':'change',()=>{if(id==='employeeSort')currentSort=el(id).value;render()}));
    document.querySelectorAll('[data-emp-chip]').forEach(btn=>btn.addEventListener('click',()=>{currentChip=btn.dataset.empChip||'all';document.querySelectorAll('[data-emp-chip]').forEach(x=>x.classList.toggle('active',x===btn));render()}));
    document.querySelectorAll('[data-emp-view]').forEach(btn=>btn.addEventListener('click',()=>{currentView=btn.dataset.empView||'table';document.querySelectorAll('[data-emp-view]').forEach(x=>x.classList.toggle('active',x===btn));el('employeeTableWrap')?.classList.toggle('d-none',currentView!=='table');el('employeeCardGrid')?.classList.toggle('d-none',currentView!=='card')}));
    document.querySelectorAll('[data-employee-modal-close]').forEach(x=>x.addEventListener('click',closeModal));document.querySelectorAll('[data-employee-drawer-close]').forEach(x=>x.addEventListener('click',closeDrawer));document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();closeDrawer()}});
    resetForm();reload();
})();
