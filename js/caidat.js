(() => {
    let accounts = [];
    let employees = [];
    let readers = [];
    let currentUserId = 0;
    let recentRegistrations = [];
    let newRegistrationCount = 0;
    let savedSettings = null;
    let systemInfo = {};
    let activeSettingsTab = 'overview';
    const BACKUP_HISTORY_KEY = 'qltv_settings_backup_history_v1';
    const EMAIL_TEST_KEY = 'qltv_settings_email_test_v1';
    const el = id => document.getElementById(id);
    const isAdmin = !!window.libraryAccess?.canManageAccounts;
    const canManageSettings = !!window.libraryAccess?.canManageSettings;

    function toast(message, type = 'success') {
        const t = el('settingsToast');
        if (!t) return alert(message);
        const text = t.querySelector('.settings-toast-text') || t;
        const icon = t.querySelector('.settings-toast-icon i');
        text.textContent = message;
        t.classList.remove('success', 'warning', 'error');
        t.classList.add(type);
        if (icon) icon.className = type === 'error' ? 'fa-solid fa-circle-xmark' : type === 'warning' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-check';
        t.classList.add('show');
        clearTimeout(toast.timer);
        toast.timer = setTimeout(() => t.classList.remove('show'), 3000);
    }

    function formatMoney(value) { return `${Number(value || 0).toLocaleString('vi-VN')}đ`; }
    function safeLocalStorageGet(key, fallback) { try { const raw = localStorage.getItem(key); return raw ? JSON.parse(raw) : fallback; } catch (_) { return fallback; } }
    function safeLocalStorageSet(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); } catch (_) {} }

    function collectSettingsFromForm() {
        return {
            TenThuVien: el('libraryName')?.value.trim() || '', DiaChi: el('libraryAddress')?.value.trim() || '',
            Email: el('libraryEmail')?.value.trim() || '', SDT: el('libraryPhone')?.value.trim() || '',
            SoNgayMuon: Number(el('borrowDays')?.value || 14), SoNgayGiaHan: Number(el('renewDays')?.value || 7),
            MucPhat: Number(el('fineAmount')?.value || 0)
        };
    }
    function applySettingsToForm(s = {}) {
        if (el('libraryName')) el('libraryName').value = s.TenThuVien || '';
        if (el('libraryAddress')) el('libraryAddress').value = s.DiaChi || '';
        if (el('libraryEmail')) el('libraryEmail').value = s.Email || '';
        if (el('libraryPhone')) el('libraryPhone').value = s.SDT || '';
        if (el('borrowDays')) el('borrowDays').value = s.SoNgayMuon || 14;
        if (el('renewDays')) el('renewDays').value = s.SoNgayGiaHan || 7;
        if (el('fineAmount')) el('fineAmount').value = Number(s.MucPhat || 0);
        renderSettingsPreview();
    }
    function renderSettingsPreview() {
        const s = collectSettingsFromForm();
        const setText = (id, value) => { if (el(id)) el(id).textContent = value || '--'; };
        setText('previewLibraryName', s.TenThuVien); setText('previewLibraryAddress', s.DiaChi);
        setText('previewLibraryEmail', s.Email); setText('previewLibraryPhone', s.SDT);
        setText('contactPreviewName', s.TenThuVien); setText('contactPreviewAddress', s.DiaChi || 'Chưa nhập địa chỉ');
        setText('previewBorrowDays', `${s.SoNgayMuon} ngày`); setText('previewRenewDays', `${s.SoNgayGiaHan} ngày`);
        setText('previewFine', `${formatMoney(s.MucPhat)} / ngày / cuốn`); setText('borrowRuleStatus', `${s.SoNgayMuon} ngày`);
        setText('borrowRuleHint', `Gia hạn ${s.SoNgayGiaHan} ngày · Phạt ${formatMoney(s.MucPhat)}`);
        if (el('policyPreviewText')) el('policyPreviewText').textContent = `Độc giả được mượn tối đa ${s.SoNgayMuon} ngày, được gia hạn thêm ${s.SoNgayGiaHan} ngày và mức phạt hiện tại là ${formatMoney(s.MucPhat)} / ngày / cuốn.`;
        renderAttention();
    }
    function renderAttention() {
        const s = collectSettingsFromForm(); const items = [];
        if (!s.Email) items.push({level:'warning', icon:'fa-envelope', title:'Chưa có email thư viện', text:'Bổ sung email để thông tin liên hệ đầy đủ hơn.'});
        if (!s.SDT) items.push({level:'warning', icon:'fa-phone', title:'Chưa có số điện thoại', text:'Người dùng sẽ khó liên hệ nhanh với thư viện.'});
        if (s.MucPhat <= 0) items.push({level:'warning', icon:'fa-coins', title:'Mức phạt đang là 0đ', text:'Kiểm tra lại nếu thư viện có áp dụng phạt quá hạn.'});
        if (s.SoNgayMuon > 30) items.push({level:'info', icon:'fa-calendar-days', title:'Thời gian mượn khá dài', text:`Quy định hiện tại là ${s.SoNgayMuon} ngày.`});
        const history = safeLocalStorageGet(BACKUP_HISTORY_KEY, []);
        if (!history.length) items.push({level:'warning', icon:'fa-cloud-arrow-up', title:'Chưa có lịch sử backup trên trình duyệt này', text:'Nên tạo một bản sao lưu dữ liệu.'});
        else { const last = new Date(history[0].time); if (!Number.isNaN(last.getTime()) && Date.now() - last.getTime() > 7 * 86400000) items.push({level:'warning', icon:'fa-cloud-arrow-up', title:'Backup đã cũ hơn 7 ngày', text:'Nên tạo bản sao lưu mới.'}); }
        if (!items.length) items.push({level:'ok', icon:'fa-circle-check', title:'Cấu hình đang ổn định', text:'Chưa phát hiện mục cấu hình nào cần chú ý.'});
        const box = el('settingsAttentionList');
        if (box) box.innerHTML = items.map(item => `<div class="attention-item ${item.level}"><span class="attention-icon"><i class="fa-solid ${item.icon}"></i></span><div><strong>${escapeHTML(item.title)}</strong><small>${escapeHTML(item.text)}</small></div><span class="attention-level">${item.level === 'ok' ? 'Tốt' : item.level === 'info' ? 'Theo dõi' : 'Cần chú ý'}</span></div>`).join('');
        if (el('attentionCount')) el('attentionCount').textContent = `${items.filter(x => x.level !== 'ok').length} mục`;
    }
    function getBackupHistory() { return safeLocalStorageGet(BACKUP_HISTORY_KEY, []); }
    function renderBackupHistory() {
        const list = getBackupHistory(); const box = el('backupTimeline');
        if (box) box.innerHTML = list.length ? list.slice(0,6).map((item,index)=>`<div class="backup-timeline-item"><span class="timeline-dot ${index===0?'active':''}"></span><div><strong>${escapeHTML(item.label || 'Backup thủ công')}</strong><small>${escapeHTML(item.displayTime || '--')} · ${escapeHTML(item.size || '--')}</small></div><span class="status-pill ${index===0?'ok':'neutral'}">${index===0?'Mới nhất':'Đã lưu'}</span></div>`).join('') : '<div class="settings-empty-state"><i class="fa-solid fa-cloud"></i><span>Chưa có lịch sử backup trên trình duyệt này.</span></div>';
        const latest=list[0]; if(el('backupSummaryStatus')) el('backupSummaryStatus').textContent=latest?(latest.relative||'Đã tạo'):'Chưa có';
        if(el('backupSummaryHint')) el('backupSummaryHint').textContent=latest?`${latest.displayTime} · ${latest.size}`:'Tạo backup để bảo vệ dữ liệu';
        if(el('heroBackupStatus')) el('heroBackupStatus').textContent=latest?'Đã có backup':'Chưa tạo';
        if(el('backupDateCompact')) el('backupDateCompact').textContent=latest?latest.displayTime:'--';
    }
    function recordBackup(sizeText) { const now=new Date(); const history=getBackupHistory(); history.unshift({time:now.toISOString(),displayTime:now.toLocaleString('vi-VN'),relative:'Vừa xong',size:sizeText,label:'Backup thủ công'}); safeLocalStorageSet(BACKUP_HISTORY_KEY,history.slice(0,12)); renderBackupHistory(); }
    function renderEmailLog() { const data=safeLocalStorageGet(EMAIL_TEST_KEY,null); if(el('emailLastTestTime')) el('emailLastTestTime').textContent=data?.time||'Chưa kiểm tra'; if(el('emailLastTestStatus')) el('emailLastTestStatus').textContent=data?.status||'Chưa có dữ liệu'; }
    function setSettingsTab(name) { if(!document.querySelector(`[data-settings-panel="${name}"]`)) name='overview'; activeSettingsTab=name; document.querySelectorAll('[data-settings-tab]').forEach(btn=>btn.classList.toggle('active',btn.dataset.settingsTab===name)); document.querySelectorAll('[data-settings-panel]').forEach(panel=>panel.classList.toggle('active',panel.dataset.settingsPanel===name)); }

    function statusClass(status) {
        const s = normalizeLibraryText(status || '');
        return s.includes('khoa') || s.includes('ngung') ? 'inactive' : 'active';
    }

    function isLocked(status) {
        const s = normalizeLibraryText(status || '');
        return s.includes('khoa') || s.includes('ngung');
    }

    function linkedText(account) {
        if (account.MaNhanVien) return `NV${String(account.MaNhanVien).padStart(3,'0')}`;
        if (account.MaDocGia) return `DG${String(account.MaDocGia).padStart(3,'0')}`;
        return '--';
    }

    function isCustomerAccount(account) {
        return normalizeLibraryText(account?.LoaiTaiKhoan || '') === 'khach';
    }

    function renderAccountRows(body, list, emptyMessage) {
        if (!body) return;
        if (!list.length) {
            body.innerHTML = `<tr><td colspan="5">${escapeHTML(emptyMessage)}</td></tr>`;
            return;
        }
        body.innerHTML = list.map(a => {
            const self = Number(a.MaTaiKhoan) === Number(currentUserId);
            const locked = isLocked(a.TrangThai);
            return `<tr>
                <td><strong>${escapeHTML(a.TenDangNhap)}</strong><br><small>${escapeHTML(a.HoTen || '')}${self ? ' · Bạn' : ''}</small></td>
                <td><span class="role-badge">${escapeHTML(a.LoaiTaiKhoan || '--')}</span></td>
                <td><span class="status-badge ${statusClass(a.TrangThai)}">${escapeHTML(a.TrangThai || '--')}</span></td>
                <td>${linkedText(a)}</td>
                <td><div class="account-actions">
                    <button class="btn btn-outline" type="button" data-edit-account="${a.MaTaiKhoan}"><i class="fa-solid fa-pen"></i> Sửa</button>
                    <button class="btn ${locked ? 'btn-unlock' : 'btn-lock'}" type="button" data-toggle-account="${a.MaTaiKhoan}" ${self ? 'disabled' : ''}>
                        <i class="fa-solid ${locked ? 'fa-lock-open' : 'fa-lock'}"></i> ${locked ? 'Mở' : 'Khóa'}
                    </button>
                    <button class="btn btn-delete-account" type="button" data-delete-account="${a.MaTaiKhoan}" ${self ? 'disabled' : ''}><i class="fa-solid fa-trash"></i> Xóa</button>
                </div></td>
            </tr>`;
        }).join('');

        body.querySelectorAll('[data-edit-account]').forEach(btn => btn.addEventListener('click', () => openAccountModal(Number(btn.dataset.editAccount))));
        body.querySelectorAll('[data-toggle-account]').forEach(btn => btn.addEventListener('click', () => toggleAccount(Number(btn.dataset.toggleAccount))));
        body.querySelectorAll('[data-delete-account]').forEach(btn => btn.addEventListener('click', () => deleteAccount(Number(btn.dataset.deleteAccount))));
    }

    function renderAccounts() {
        const staffAccounts = accounts.filter(a => !isCustomerAccount(a));
        const customerAccounts = accounts.filter(isCustomerAccount);

        if (el('staffAccountCount')) el('staffAccountCount').textContent = String(staffAccounts.length);
        if (el('customerAccountCount')) el('customerAccountCount').textContent = String(customerAccounts.length);

        renderAccountRows(el('staffAccountTableBody'), staffAccounts, 'Chưa có tài khoản nhân viên.');
        renderAccountRows(el('customerAccountTableBody'), customerAccounts, 'Chưa có tài khoản khách hàng.');
    }

    function formatRegistrationTime(value) {
        if (!value) return '--';
        const normalized = String(value).replace(' ', 'T');
        const d = new Date(normalized);
        if (Number.isNaN(d.getTime())) return escapeHTML(String(value));
        return d.toLocaleString('vi-VN', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function renderRecentRegistrations() {
        const countBox = el('newRegistrationCount');
        const list = el('recentRegistrationList');
        const caption = el('recentRegistrationCaption');
        if (countBox) countBox.textContent = String(newRegistrationCount || 0);
        if (caption) caption.textContent = newRegistrationCount > 0
            ? `${newRegistrationCount} khách hàng mới`
            : 'Chưa có đăng ký mới';
        if (!list) return;

        if (!recentRegistrations.length) {
            list.innerHTML = `<div class="recent-registration-empty">
                <i class="fa-regular fa-circle-check"></i>
                <span>Chưa có khách hàng nào đăng ký tài khoản trong 24 giờ gần nhất.</span>
            </div>`;
            return;
        }

        list.innerHTML = recentRegistrations.map(item => {
            const readerCode = item.MaDocGia ? `DG${String(item.MaDocGia).padStart(3, '0')}` : '--';
            return `<div class="recent-registration-item">
                <div class="recent-registration-avatar"><i class="fa-solid fa-user-plus"></i></div>
                <div class="recent-registration-info">
                    <strong>${escapeHTML(item.HoTen || item.TenDangNhap || 'Khách hàng')}</strong>
                    <span>@${escapeHTML(item.TenDangNhap || '--')} · ${readerCode}</span>
                </div>
                <div class="recent-registration-time">
                    <span>${formatRegistrationTime(item.NgayTao)}</span>
                    <small>${escapeHTML(item.TrangThai || 'Đang hoạt động')}</small>
                </div>
            </div>`;
        }).join('');
    }

    function renderSystem(info) {
        systemInfo = info || {}; const box = el('systemInfoList');
        const rows=[{label:'Database',value:info.database||'web_qlthuvien',icon:'fa-database',tone:'blue'},{label:'PHP Runtime',value:info.phpVersion||'--',icon:'fa-code',tone:'purple'},{label:'MySQL / MariaDB',value:info.mysqlVersion||'--',icon:'fa-server',tone:'green'},{label:'Thời gian máy chủ',value:info.serverTime||'--',icon:'fa-clock',tone:'orange'}];
        if(box) box.innerHTML=rows.map(row=>`<article class="system-status-item ${row.tone}"><span class="system-status-icon"><i class="fa-solid ${row.icon}"></i></span><div><span>${row.label}</span><strong>${escapeHTML(row.value)}</strong><small><i class="fa-solid fa-circle-check"></i> Đã kết nối</small></div></article>`).join('');
        if(el('heroDbStatus')) el('heroDbStatus').textContent=info.database?'Đã kết nối':'Chưa xác định';
        if(el('libraryConfigStatus')) el('libraryConfigStatus').textContent=savedSettings?.TenThuVien?'Đã cấu hình':'Cần bổ sung';
        if(el('settingsLastUpdated')) el('settingsLastUpdated').innerHTML=`<i class="fa-regular fa-clock"></i> Cập nhật lần cuối: ${escapeHTML(info.serverTime||new Date().toLocaleString('vi-VN'))}`;
    }

    function fillLinkOptions() {
        const employeeSelect = el('accountEmployee');
        const readerSelect = el('accountReader');
        if (employeeSelect) {
            employeeSelect.innerHTML = '<option value="">-- Không liên kết --</option>' + employees.map(x =>
                `<option value="${x.MaNhanVien}">NV${String(x.MaNhanVien).padStart(3,'0')} - ${escapeHTML(x.HoTen)}${x.TrangThai ? ` (${escapeHTML(x.TrangThai)})` : ''}</option>`
            ).join('');
        }
        if (readerSelect) {
            readerSelect.innerHTML = '<option value="">-- Chọn độc giả --</option>' + readers.map(x =>
                `<option value="${x.MaDocGia}">DG${String(x.MaDocGia).padStart(3,'0')} - ${escapeHTML(x.HoTen)}${x.TrangThai ? ` (${escapeHTML(x.TrangThai)})` : ''}</option>`
            ).join('');
        }
    }

    function updateRoleFields() {
        if (!isAdmin) return;
        const role = el('accountRole')?.value || 'Nhân viên';
        const employeeField = el('employeeLinkField');
        const readerField = el('readerLinkField');
        const readerSelect = el('accountReader');
        const employeeSelect = el('accountEmployee');
        const note = el('accountRoleNote');
        const isCustomer = role === 'Khách';
        const isStaff = role === 'Quản lý' || role === 'Nhân viên';

        if (employeeField) employeeField.style.display = isStaff ? 'flex' : 'none';
        if (readerField) readerField.style.display = isCustomer ? 'flex' : 'none';
        if (readerSelect) readerSelect.required = isCustomer;
        if (employeeSelect) employeeSelect.required = isStaff;
        if (note) {
            note.textContent = role === 'Admin' ? 'Admin xem toàn bộ hệ thống nhưng chỉ thao tác phần tài khoản/phân quyền.' :
                role === 'Quản lý' ? 'Quản lý thao tác toàn bộ nghiệp vụ và xếp ca, nhưng không quản lý tài khoản/phân quyền.' :
                role === 'Nhân viên' ? 'Nhân viên thao tác Sách, Khách hàng, Mượn - Trả, Lịch đặt trước và chỉ xem ca của mình.' :
                'Độc giả xem dữ liệu cần thiết, gửi yêu cầu mượn và đặt lịch cho chính mình.';
        }
    }

    function openAccountModal(id = null) {
        if (!isAdmin) return;
        const account = id ? accounts.find(x => Number(x.MaTaiKhoan) === Number(id)) : null;
        fillLinkOptions();
        el('accountId').value = account?.MaTaiKhoan || '';
        el('accountUsername').value = account?.TenDangNhap || '';
        el('accountPassword').value = '';
        el('accountRole').value = account?.LoaiTaiKhoan || 'Nhân viên';
        el('accountStatus').value = isLocked(account?.TrangThai) ? 'Khóa' : 'Đang hoạt động';
        el('accountEmployee').value = account?.MaNhanVien || '';
        el('accountReader').value = account?.MaDocGia || '';
        el('accountModalTitle').textContent = account ? 'Sửa tài khoản' : 'Thêm tài khoản';
        el('accountPassword').placeholder = account ? 'Để trống nếu không đổi' : 'Ít nhất 6 ký tự';
        updateRoleFields();
        el('accountModal')?.classList.add('show');
        el('accountModal')?.setAttribute('aria-hidden', 'false');
    }

    function closeAccountModal() {
        el('accountModal')?.classList.remove('show');
        el('accountModal')?.setAttribute('aria-hidden', 'true');
        el('accountForm')?.reset();
    }

    async function reload() {
        try {
            const p=await libraryApi.get('settings'); const data=p.data.settings||{}; savedSettings={...data}; applySettingsToForm(savedSettings);
            accounts=p.data.accounts||[]; employees=p.data.employees||[]; readers=p.data.readers||[]; currentUserId=Number(p.data.currentUserId||0);
            recentRegistrations=p.data.recentRegistrations||[]; newRegistrationCount=Number(p.data.newRegistrationCount||recentRegistrations.length||0);
            renderRecentRegistrations(); renderAccounts(); renderSystem(p.data.system||{}); renderBackupHistory(); renderEmailLog(); renderSettingsPreview();
            if(el('libraryConfigHint')) el('libraryConfigHint').textContent=data.TenThuVien?'Dữ liệu đang đồng bộ từ MySQL':'Cần bổ sung tên thư viện';
            if(el('emailConfigStatus')) el('emailConfigStatus').textContent=data.Email?'Đã có email':'Chưa cấu hình';
            if(el('emailConfigHint')) el('emailConfigHint').textContent=data.Email||'SMTP sử dụng cấu hình máy chủ';
            if(el('heroEmailStatus')) el('heroEmailStatus').textContent=data.Email?'Có email liên hệ':'Theo cấu hình';
        } catch(e) { toast(e.message,'error'); }
    }

    async function saveSettings() {
        if(!canManageSettings) return toast('Tài khoản của bạn chỉ được xem cài đặt.','warning');
        const data={libraryName:el('libraryName').value.trim(),address:el('libraryAddress').value.trim(),email:el('libraryEmail').value.trim(),phone:el('libraryPhone').value.trim(),borrowDays:Number(el('borrowDays').value||14),renewDays:Number(el('renewDays').value||7),fine:Number(el('fineAmount').value||0)};
        ['saveAllSettingsBtn','footerSaveAllBtn'].forEach(id=>{if(el(id)){el(id).disabled=true;el(id).classList.add('loading')}});
        try{const p=await libraryApi.post('settings_save',data);toast(p.message||'Đã lưu cài đặt.');await reload();}catch(e){toast(e.message,'error');}finally{['saveAllSettingsBtn','footerSaveAllBtn'].forEach(id=>{if(el(id)){el(id).disabled=false;el(id).classList.remove('loading')}})}
    }

    el('libraryForm')?.addEventListener('submit', e => { e.preventDefault(); saveSettings(); });
    el('borrowForm')?.addEventListener('submit', e => { e.preventDefault(); saveSettings(); });
    el('emailTestForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        const email = el('testEmailAddress')?.value.trim() || '';
        if (!email) return toast('Vui lòng nhập email nhận thử.');
        const button = el('sendTestEmailBtn');
        if (button) { button.disabled = true; button.textContent = 'Đang gửi...'; }
        try {
            const p = await libraryApi.post('email_test', {email});
            safeLocalStorageSet(EMAIL_TEST_KEY,{time:new Date().toLocaleString('vi-VN'),status:'Gửi thành công',email}); renderEmailLog();
            if(el('smtpStatusPill')) el('smtpStatusPill').textContent='Đã kiểm tra'; toast(p.message || 'Đã gửi email thử.');
        } catch (err) { safeLocalStorageSet(EMAIL_TEST_KEY,{time:new Date().toLocaleString('vi-VN'),status:'Gửi thất bại',email}); renderEmailLog(); toast(err.message,'error'); }
        finally { if (button) { button.disabled = false; button.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Gửi email thử'; } }
    });
    el('addAccountBtn')?.addEventListener('click', () => openAccountModal());
    el('closeAccountModalBtn')?.addEventListener('click', closeAccountModal);
    el('cancelAccountBtn')?.addEventListener('click', closeAccountModal);
    el('accountRole')?.addEventListener('change', updateRoleFields);
    el('accountModal')?.addEventListener('click', event => { if (event.target === el('accountModal')) closeAccountModal(); });

    el('accountForm')?.addEventListener('submit', async event => {
        event.preventDefault();
        const id = Number(el('accountId').value || 0) || null;
        const password = el('accountPassword').value;
        if (!id && password.length < 6) return toast('Mật khẩu tài khoản mới phải có ít nhất 6 ký tự.');
        const role = el('accountRole').value;
        const data = {
            id,
            username: el('accountUsername').value.trim(),
            password,
            role,
            status: el('accountStatus').value,
            employeeId: (role === 'Quản lý' || role === 'Nhân viên') ? (el('accountEmployee').value || null) : null,
            readerId: role === 'Khách' ? (el('accountReader').value || null) : null
        };
        try {
            const p = await libraryApi.post('account_save', data);
            toast(p.message);
            closeAccountModal();
            await reload();
        } catch (e) {
            toast(e.message);
        }
    });

    async function toggleAccount(id) {
        const a = accounts.find(x => Number(x.MaTaiKhoan) === id);
        if (!a) return;
        const action = isLocked(a.TrangThai) ? 'mở khóa' : 'khóa';
        if (!(await window.libraryConfirm(`Bạn có chắc muốn ${action} tài khoản “${a.TenDangNhap}”?`,'Xác nhận tài khoản',action==='khóa'))) return;
        try {
            const p = await libraryApi.post('account_toggle', {id});
            toast(p.message);
            await reload();
        } catch (e) {
            toast(e.message);
        }
    }

    async function deleteAccount(id) {
        const a = accounts.find(x => Number(x.MaTaiKhoan) === id);
        if (!a) return;
        if (!(await window.libraryConfirm(`Xóa tài khoản “${a.TenDangNhap}”? Hồ sơ nhân viên/độc giả liên kết sẽ không bị xóa.`,'Xóa tài khoản'))) return;
        try {
            const p = await libraryApi.post('account_delete', {id});
            toast(p.message);
            await reload();
        } catch (e) {
            toast(e.message);
        }
    }

    async function createBackup() {
        const status=el('backupStatus'),health=el('backupHealthPill'); if(status) status.textContent='Đang tạo...'; if(health){health.textContent='Đang tạo';health.classList.add('warning')}
        try{const p=await libraryApi.get('backup'); const text=JSON.stringify(p.data,null,2); const blob=new Blob([text],{type:'application/json;charset=utf-8'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download=`web_qlthuvien_backup_${new Date().toISOString().slice(0,10)}.json`; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url); const displayTime=new Date().toLocaleString('vi-VN'),sizeText=`${(blob.size/1024).toFixed(1)} KB`; if(el('backupDate')) el('backupDate').textContent=displayTime; if(el('backupSize')) el('backupSize').textContent=sizeText; if(status) status.textContent='Hoàn tất'; if(health){health.textContent='An toàn';health.classList.remove('warning');health.classList.add('ok')} recordBackup(sizeText); renderAttention(); toast('Đã tải bản sao lưu MySQL dạng JSON.');}
        catch(e){if(status)status.textContent='Lỗi';if(health){health.textContent='Có lỗi';health.classList.add('warning')}toast(e.message,'error')}
    }
    el('backupBtn')?.addEventListener('click',createBackup); el('heroBackupBtn')?.addEventListener('click',()=>{setSettingsTab('backup');setTimeout(createBackup,180)});
    document.querySelectorAll('[data-settings-tab]').forEach(btn=>btn.addEventListener('click',()=>setSettingsTab(btn.dataset.settingsTab)));
    document.querySelectorAll('[data-jump-tab]').forEach(btn=>btn.addEventListener('click',()=>setSettingsTab(btn.dataset.jumpTab)));
    ['libraryName','libraryAddress','libraryEmail','libraryPhone','borrowDays','renewDays','fineAmount'].forEach(id=>{el(id)?.addEventListener('input',renderSettingsPreview);el(id)?.addEventListener('change',renderSettingsPreview)});
    el('saveAllSettingsBtn')?.addEventListener('click',saveSettings); el('footerSaveAllBtn')?.addEventListener('click',saveSettings);
    el('restoreSettingsBtn')?.addEventListener('click',()=>{if(savedSettings){applySettingsToForm(savedSettings);toast('Đã khôi phục các giá trị đang lưu trong hệ thống.')}});
    el('discardSettingsBtn')?.addEventListener('click',()=>{if(savedSettings){applySettingsToForm(savedSettings);toast('Đã hủy các thay đổi chưa lưu.')}});
    el('refreshSettingsBtn')?.addEventListener('click',async()=>{await reload();toast('Đã làm mới dữ liệu cài đặt.')});
    document.querySelectorAll('[data-copy-from]').forEach(btn=>btn.addEventListener('click',async()=>{const input=el(btn.dataset.copyFrom),value=input?.value?.trim()||'';if(!value)return toast('Chưa có dữ liệu để sao chép.','warning');try{await navigator.clipboard.writeText(value);toast('Đã sao chép vào bộ nhớ tạm.')}catch(_){input?.select();document.execCommand?.('copy');toast('Đã sao chép vào bộ nhớ tạm.')}}));
    el('callPreviewBtn')?.addEventListener('click',()=>{const phone=el('libraryPhone')?.value.trim()||'';if(!phone)return toast('Chưa có số điện thoại thư viện.','warning');window.location.href=`tel:${phone.replace(/\s+/g,'')}`});
    el('mapPreviewBtn')?.addEventListener('click',()=>{const address=el('libraryAddress')?.value.trim()||'';if(!address)return toast('Chưa có địa chỉ thư viện.','warning');window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`,'_blank','noopener')});
    setSettingsTab('overview');


    reload();
})();
