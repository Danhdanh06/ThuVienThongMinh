(() => {
    const canManage = !!window.libraryAccess?.canManageShifts;
    const selfOnly = !!window.libraryAccess?.canViewSelfShifts && !window.libraryAccess?.canViewAllShifts;
    let shifts = [], assignments = [], rosterAssignments = [], employees = [];
    let currentEmployeeId = 0;
    let calendarDate = new Date();
    let selectedCalendarDate = '';
    let shiftChip = 'all';
    let catalogView = 'table';

    const el = id => document.getElementById(id);
    const today = () => new Date().toLocaleDateString('en-CA');
    const addDays = (date, days) => { const d = new Date(date + 'T00:00:00'); d.setDate(d.getDate() + days); return d.toLocaleDateString('en-CA'); };
    const timeShort = value => value ? String(value).slice(0,5) : '--';
    const nowTime = () => { const d=new Date(); return `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`; };
    const isCurrentAssignment = a => a.NgayLam===today() && nowTime()>=timeShort(a.GioBatDau) && nowTime()<=timeShort(a.GioKetThuc);
    const isTodayAssignment = a => a.NgayLam===today();
    const pad2 = n => String(n).padStart(2, '0');
    const dateKey = (year, monthIndex, day) => `${year}-${pad2(monthIndex + 1)}-${pad2(day)}`;
    const monthLabel = d => `Tháng ${d.getMonth() + 1} / ${d.getFullYear()}`;

    function toast(message) {
        const t = el('shiftToast'); if (!t) return alert(message);
        t.textContent = message; t.classList.add('show'); clearTimeout(toast.timer); toast.timer = setTimeout(() => t.classList.remove('show'), 2400);
    }

    function setThisWeek() {
        const now = new Date(); const day = now.getDay(); const diff = day === 0 ? -6 : 1 - day;
        const monday = new Date(now); monday.setDate(now.getDate() + diff);
        const sunday = new Date(monday); sunday.setDate(monday.getDate() + 6);
        if (el('shiftFromDate')) el('shiftFromDate').value = monday.toLocaleDateString('en-CA');
        if (el('shiftToDate')) el('shiftToDate').value = sunday.toLocaleDateString('en-CA');
        renderAssignments();
    }

    function filteredAssignments() {
        const from = el('shiftFromDate')?.value || '';
        const to = el('shiftToDate')?.value || '';
        const employeeId = el('shiftEmployeeFilter')?.value || '';
        const shiftId = el('shiftTypeFilter')?.value || '';
        const t = today();
        let rows = assignments.filter(a => (!from || a.NgayLam >= from) && (!to || a.NgayLam <= to));
        if (employeeId) rows = rows.filter(a => String(a.MaNhanVien) === String(employeeId));
        if (shiftId) rows = rows.filter(a => String(a.MaCa) === String(shiftId));
        if (shiftChip === 'live') rows = rows.filter(isCurrentAssignment);
        if (shiftChip === 'today') rows = rows.filter(a => a.NgayLam === t);
        if (shiftChip === 'upcoming') rows = rows.filter(a => a.NgayLam >= t);
        if (shiftChip === 'selfreg') rows = rows.filter(a => normalizeLibraryText(a.GhiChu || '').includes('tu dang ky'));
        return rows;
    }

    function animateNumber(node, value) {
        if (!node) return;
        const end = Number(value || 0), start = Number(node.dataset.current || 0), begin = performance.now(), duration = 520;
        const step = now => { const p=Math.min(1,(now-begin)/duration); const v=Math.round(start+(end-start)*(1-Math.pow(1-p,3))); node.textContent=v.toLocaleString('vi-VN'); if(p<1) requestAnimationFrame(step); else node.dataset.current=String(end); };
        requestAnimationFrame(step);
    }
    function renderStats() {
        const t = today();
        const weekEnd = addDays(t, 6);
        const now = new Date();
        const monthStart = `${now.getFullYear()}-${pad2(now.getMonth()+1)}-01`;
        const monthEnd = new Date(now.getFullYear(), now.getMonth()+1, 0).toLocaleDateString('en-CA');
        const todayRows = assignments.filter(a => a.NgayLam === t);
        const weekRows = assignments.filter(a => a.NgayLam >= t && a.NgayLam <= weekEnd);
        const monthRows = assignments.filter(a => a.NgayLam >= monthStart && a.NgayLam <= monthEnd);
        const staffCount = selfOnly ? monthRows.length : new Set(assignments.map(a => a.MaNhanVien)).size;
        const liveCount = assignments.filter(isCurrentAssignment).length;
        animateNumber(el('todayShiftCount'), todayRows.length);
        animateNumber(el('weekShiftCount'), weekRows.length);
        animateNumber(el('staffShiftCount'), staffCount);
        animateNumber(el('liveShiftCount'), liveCount);
        animateNumber(el('heroTodayCount'), todayRows.length);
        animateNumber(el('heroLiveCount'), liveCount);
        animateNumber(el('heroWeekCount'), weekRows.length);
        const clock=el('shiftClockText'); if(clock) clock.textContent=nowTime();
        renderEmployeeUpcoming();
        renderEmployeeTimeline();
        renderWeeklyChart();
        renderAlertCenter();
    }

    function renderCurrentShift() {
        const box=el('currentShiftNotice'); if(!box)return;
        const current=assignments.filter(isCurrentAssignment);
        const later=assignments.filter(a=>isTodayAssignment(a) && nowTime()<timeShort(a.GioBatDau)).sort((a,b)=>timeShort(a.GioBatDau).localeCompare(timeShort(b.GioBatDau)));
        if(current.length){
            const pills=current.map(a=>`<span class="live-person-pill"><b class="live-avatar">${escapeHTML((a.TenNhanVien||a.TenCa||'C').trim().charAt(0).toUpperCase())}</b><span><strong>${selfOnly ? escapeHTML(a.TenCa||'Ca làm') : `NV${String(a.MaNhanVien).padStart(3,'0')} · ${escapeHTML(a.TenNhanVien||'--')}`}</strong><small>${escapeHTML(a.TenCa||'Ca làm')} · ${timeShort(a.GioBatDau)}–${timeShort(a.GioKetThuc)}</small></span><em>Đang trực</em></span>`).join('');
            box.className='shift-now shift-live-strip active'; box.innerHTML=`<i class="fa-solid fa-circle-play pulse-icon"></i><div class="shift-live-copy"><strong>Đang trực lúc này</strong><div class="shift-live-people">${pills}</div></div>`;
        } else if(later.length){
            const a=later[0];
            box.className='shift-now shift-live-strip upcoming'; box.innerHTML=`<i class="fa-solid fa-clock"></i><div><strong>${selfOnly?'Ca tiếp theo của bạn':'Ca tiếp theo hôm nay'}</strong><span>${selfOnly?'':`NV${String(a.MaNhanVien).padStart(3,'0')} · ${escapeHTML(a.TenNhanVien||'--')} · `}${escapeHTML(a.TenCa||'Ca làm')} · ${timeShort(a.GioBatDau)}–${timeShort(a.GioKetThuc)}</span></div>`;
        } else { box.className='shift-now shift-live-strip'; box.innerHTML=`<i class="fa-solid fa-calendar-check"></i><div><strong>${selfOnly?'Bạn hiện chưa trong ca làm việc':'Hiện không có ca đang diễn ra'}</strong><span>${selfOnly?'Bạn có thể xem ca còn trống và đăng ký ở phía dưới.':'Hôm nay chưa có ca tiếp theo trong lịch được phân công.'}</span></div>`; }
    }

    function assignmentsOnDate(key) {
        return assignments
            .filter(a => a.NgayLam === key)
            .sort((a,b) => timeShort(a.GioBatDau).localeCompare(timeShort(b.GioBatDau)) || String(a.TenNhanVien || '').localeCompare(String(b.TenNhanVien || '')));
    }

    function rosterOnDate(key) {
        return rosterAssignments
            .filter(a => a.NgayLam === key)
            .sort((a,b) => timeShort(a.GioBatDau).localeCompare(timeShort(b.GioBatDau)) || String(a.TenNhanVien || '').localeCompare(String(b.TenNhanVien || '')));
    }

    function groupRosterByShift(rows) {
        const groups = [];
        const map = new Map();
        rows.forEach(a => {
            const key = String(a.MaCa || `${a.TenCa}-${a.GioBatDau}-${a.GioKetThuc}`);
            if (!map.has(key)) {
                const group = {
                    MaCa: a.MaCa,
                    TenCa: a.TenCa || 'Ca làm',
                    GioBatDau: a.GioBatDau,
                    GioKetThuc: a.GioKetThuc,
                    people: []
                };
                map.set(key, group);
                groups.push(group);
            }
            map.get(key).people.push(a);
        });
        return groups;
    }

    function personRosterLine(a) {
        const mine = currentEmployeeId > 0 && Number(a.MaNhanVien) === Number(currentEmployeeId);
        return `<div class="roster-person ${mine ? 'is-me' : ''}">
            <span><i class="fa-regular fa-user"></i> NV${String(a.MaNhanVien).padStart(3,'0')} - ${escapeHTML(a.TenNhanVien || '--')}</span>
            ${mine ? '<strong class="roster-me-badge">Bạn</strong>' : ''}
        </div>`;
    }

    function renderEmployeeUpcoming() {
        const box=el('employeeUpcomingList'); if(!box)return;
        const t=today();
        const rows=assignments.filter(a=>a.NgayLam>=t).sort((a,b)=>a.NgayLam.localeCompare(b.NgayLam)||timeShort(a.GioBatDau).localeCompare(timeShort(b.GioBatDau))).slice(0,4);
        if(!rows.length){box.innerHTML='<div class="shift-empty employee-empty-state"><i class="fa-regular fa-calendar-plus"></i><strong>Chưa có ca sắp tới</strong><span>Bạn có thể đăng ký ca còn chỗ ở phía dưới.</span></div>';return;}
        box.innerHTML=rows.map((a,i)=>`<div class="employee-upcoming-item ${i===0?'next':''}"><span class="upcoming-date">${formatLibraryDate(a.NgayLam)}</span><div><strong>${escapeHTML(a.TenCa||'Ca làm')}</strong><small>${timeShort(a.GioBatDau)}–${timeShort(a.GioKetThuc)}${a.GhiChu?` · ${escapeHTML(a.GhiChu)}`:''}</small></div>${i===0?'<em>Tiếp theo</em>':''}</div>`).join('');
    }
    function renderEmployeeTimeline() {
        const box=el('employeeShiftTimeline'); if(!box)return;
        const rows=[...assignments].sort((a,b)=>a.NgayLam.localeCompare(b.NgayLam)||timeShort(a.GioBatDau).localeCompare(timeShort(b.GioBatDau))).slice(0,10);
        if(!rows.length){box.innerHTML='<div class="shift-empty employee-empty-state"><i class="fa-solid fa-timeline"></i><strong>Chưa có timeline ca</strong><span>Khi có lịch phân công, các mốc gần nhất sẽ xuất hiện tại đây.</span></div>';return;}
        box.innerHTML=rows.map(a=>`<div class="employee-timeline-item"><span class="timeline-dot"></span><div><strong>${formatLibraryDate(a.NgayLam)} · ${escapeHTML(a.TenCa||'Ca làm')}</strong><small>${timeShort(a.GioBatDau)}–${timeShort(a.GioKetThuc)}${a.GhiChu?` · ${escapeHTML(a.GhiChu)}`:''}</small></div></div>`).join('');
    }
    function renderWeeklyChart(){
        const box=el('shiftWeeklyChart'); if(!box)return;
        const t=today(); const rows=[];
        for(let i=0;i<7;i++){const key=addDays(t,i);rows.push({key,count:assignments.filter(a=>a.NgayLam===key).length});}
        const max=Math.max(1,...rows.map(x=>x.count));
        box.innerHTML=rows.map(x=>{const d=new Date(x.key+'T00:00:00');const label=d.toLocaleDateString('vi-VN',{weekday:'short'});return `<div class="shift-mini-bar"><span class="bar-value">${x.count}</span><div class="bar-track"><i style="height:${Math.max(8,x.count/max*100)}%"></i></div><small>${escapeHTML(label)}</small></div>`}).join('');
    }
    function renderAlertCenter(){
        const box=el('shiftAlertCenter'); if(!box)return;
        const t=today(), current=assignments.filter(isCurrentAssignment).length;
        const todayRows=assignments.filter(a=>a.NgayLam===t);
        const emptyShifts=activeShifts().filter(s=>!todayRows.some(a=>Number(a.MaCa)===Number(s.MaCa))).length;
        const items=[
            {icon:'fa-user-clock',tone:'blue',title:`${current} nhân viên đang trực`,desc:'Tính theo giờ hiện tại'},
            {icon:'fa-calendar-xmark',tone:emptyShifts?'red':'green',title:`${emptyShifts} ca hôm nay chưa có người`,desc:emptyShifts?'Cần kiểm tra phân công':'Các ca hôm nay đã có lịch'},
            {icon:'fa-users',tone:'orange',title:`${new Set(todayRows.map(a=>a.MaNhanVien)).size} nhân viên có lịch hôm nay`,desc:`${todayRows.length} lượt phân công`}
        ];
        box.innerHTML=items.map(x=>`<div class="shift-alert-item ${x.tone}"><i class="fa-solid ${x.icon}"></i><div><strong>${x.title}</strong><small>${x.desc}</small></div></div>`).join('');
    }

    function renderMyTodayShifts() {
        const box = el('myTodayShifts');
        if (!box) return;
        const rows = assignmentsOnDate(today());
        if (!rows.length) {
            box.innerHTML = '<div class="shift-empty compact-shift-empty"><i class="fa-regular fa-calendar-xmark"></i><span>Hôm nay bạn không có ca làm.</span></div>';
            return;
        }
        box.innerHTML = `<div class="my-shift-list">${rows.map(a => {
            const current = isCurrentAssignment(a);
            const upcoming = !current && nowTime() < timeShort(a.GioBatDau);
            return `<div class="my-shift-item ${current ? 'live' : upcoming ? 'upcoming' : ''}">
                <div class="my-shift-name"><strong>${escapeHTML(a.TenCa || 'Ca làm')}</strong>${current ? '<span>Đang trực</span>' : ''}</div>
                <div class="my-shift-time"><i class="fa-regular fa-clock"></i> ${timeShort(a.GioBatDau)} - ${timeShort(a.GioKetThuc)}</div>
                ${a.GhiChu ? `<div class="my-shift-note"><i class="fa-regular fa-note-sticky"></i> ${escapeHTML(a.GhiChu)}</div>` : ''}
            </div>`;
        }).join('')}</div>`;
    }

    function renderTodayRoster() {
        const box = el('todayRosterByShift');
        if (!box) return;
        const rows = rosterOnDate(today());
        const groupsById = new Map(groupRosterByShift(rows).map(g => [String(g.MaCa), g]));
        const shiftList = activeShifts().slice().sort((a,b) => timeShort(a.GioBatDau).localeCompare(timeShort(b.GioBatDau)));

        if (!shiftList.length) {
            box.innerHTML = '<div class="shift-empty">Chưa có ca làm hoạt động.</div>';
            return;
        }

        box.innerHTML = `<div class="today-roster-grid">${shiftList.map(s => {
            const group = groupsById.get(String(s.MaCa));
            const people = group?.people || [];
            const live = today() === new Date().toLocaleDateString('en-CA') && nowTime() >= timeShort(s.GioBatDau) && nowTime() <= timeShort(s.GioKetThuc);
            return `<div class="today-roster-shift ${live ? 'live' : ''}">
                <div class="today-roster-shift-head">
                    <div><strong>${escapeHTML(s.TenCa || 'Ca làm')}</strong><span>${timeShort(s.GioBatDau)} - ${timeShort(s.GioKetThuc)}</span></div>
                    ${live ? '<em>Đang diễn ra</em>' : ''}
                </div>
                <div class="today-roster-people">
                    ${people.length ? people.map(personRosterLine).join('') : '<span class="roster-none">Chưa có nhân viên được phân ca.</span>'}
                </div>
            </div>`;
        }).join('')}</div>`;
    }

    function renderCalendarDetail(key) {
        const detail = el('shiftCalendarDetail');
        if (!detail) return;
        selectedCalendarDate = key;
        const ownRows = assignmentsOnDate(key);
        const rows = ownRows;
        const parsed = new Date(key + 'T00:00:00');
        const label = Number.isNaN(parsed.getTime()) ? key : parsed.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' });

        if (!rows.length) {
            detail.innerHTML = `<div class="calendar-detail-head"><span>${escapeHTML(label)}</span><strong>Không có ca làm</strong></div>
                <div class="calendar-detail-empty compact"><i class="fa-regular fa-calendar-xmark"></i><span>Ngày này chưa có lịch phân công.</span></div>`;
            return;
        }

        if (selfOnly) {
            detail.innerHTML = `<div class="calendar-detail-head"><span>${escapeHTML(label)}</span><strong>${rows.length} ca của tôi</strong></div>
                <div class="calendar-detail-list">${rows.map(a => {
                    const live = isCurrentAssignment(a);
                    return `<div class="calendar-shift-item ${live ? 'live' : ''}">
                        <div class="calendar-shift-time"><i class="fa-regular fa-clock"></i><strong>${timeShort(a.GioBatDau)} - ${timeShort(a.GioKetThuc)}</strong></div>
                        <div class="calendar-shift-info">
                            <strong>${escapeHTML(a.TenCa || 'Ca làm')}</strong>
                            <span><i class="fa-regular fa-user"></i> Ca của bạn</span>
                            ${a.GhiChu ? `<span><i class="fa-regular fa-note-sticky"></i> ${escapeHTML(a.GhiChu)}</span>` : ''}
                        </div>
                        ${live ? '<span class="calendar-live-label">Đang trực</span>' : ''}
                    </div>`;
                }).join('')}</div>`;
            return;
        }

        detail.innerHTML = `<div class="calendar-detail-head"><span>${escapeHTML(label)}</span><strong>${rows.length} ca làm</strong></div>
            <div class="calendar-detail-list">${rows.map(a => {
                const current = isCurrentAssignment(a);
                return `<div class="calendar-shift-item ${current ? 'live' : ''}">
                    <div class="calendar-shift-time"><i class="fa-regular fa-clock"></i><strong>${timeShort(a.GioBatDau)} - ${timeShort(a.GioKetThuc)}</strong></div>
                    <div class="calendar-shift-info">
                        <strong>${escapeHTML(a.TenCa || 'Ca làm')}</strong>
                        <span><i class="fa-regular fa-user"></i> NV${String(a.MaNhanVien).padStart(3,'0')} - ${escapeHTML(a.TenNhanVien || '--')}</span>
                        ${a.GhiChu ? `<span><i class="fa-regular fa-note-sticky"></i> ${escapeHTML(a.GhiChu)}</span>` : ''}
                    </div>
                    ${current ? '<span class="calendar-live-label">Đang trực</span>' : ''}
                </div>`;
            }).join('')}</div>`;
    }

    function renderCalendar() {
        const grid = el('shiftCalendarGrid');
        const title = el('shiftCalendarTitle');
        if (!grid || !title) return;

        const year = calendarDate.getFullYear();
        const month = calendarDate.getMonth();
        title.textContent = monthLabel(calendarDate);

        const firstDay = new Date(year, month, 1);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const prevMonthDays = new Date(year, month, 0).getDate();
        const mondayIndex = (firstDay.getDay() + 6) % 7;
        const cells = [];

        for (let i = mondayIndex - 1; i >= 0; i--) {
            const d = prevMonthDays - i;
            const dt = new Date(year, month - 1, d);
            cells.push({ day: d, key: dateKey(dt.getFullYear(), dt.getMonth(), d), outside: true });
        }
        for (let d = 1; d <= daysInMonth; d++) {
            cells.push({ day: d, key: dateKey(year, month, d), outside: false });
        }
        let nextDay = 1;
        while (cells.length < 42) {
            const dt = new Date(year, month + 1, nextDay);
            cells.push({ day: nextDay, key: dateKey(dt.getFullYear(), dt.getMonth(), nextDay), outside: true });
            nextDay++;
        }

        grid.innerHTML = cells.map(cell => {
            const rows = assignmentsOnDate(cell.key);
            const isToday = cell.key === today();
            const isSelected = cell.key === selectedCalendarDate;
            const dots = rows.slice(0, 4).map(a => `<i class="calendar-dot ${isCurrentAssignment(a) ? 'red' : 'green'}"></i>`).join('');
            const more = rows.length > 4 ? `<span class="calendar-more">+${rows.length - 4}</span>` : '';
            const classes = ['shift-calendar-day', cell.outside ? 'outside' : '', isToday ? 'today' : '', isSelected ? 'selected' : '', rows.length ? 'has-shift' : ''].filter(Boolean).join(' ');
            const aria = `${cell.key}${rows.length ? `, ${rows.length} ca làm` : ', không có ca làm'}`;
            return `<button type="button" class="${classes}" data-calendar-date="${cell.key}" aria-label="${aria}">
                <span class="calendar-day-number">${cell.day}</span>
                <span class="calendar-day-dots">${dots}${more}</span>
            </button>`;
        }).join('');

        grid.querySelectorAll('[data-calendar-date]').forEach(btn => btn.addEventListener('click', () => {
            const key = btn.dataset.calendarDate;
            const clicked = new Date(key + 'T00:00:00');
            if (clicked.getMonth() !== month || clicked.getFullYear() !== year) {
                calendarDate = new Date(clicked.getFullYear(), clicked.getMonth(), 1);
            }
            selectedCalendarDate = key;
            renderCalendar();
            renderCalendarDetail(key);
        }));

        if (!selectedCalendarDate) {
            const t = today();
            const td = new Date(t + 'T00:00:00');
            if (td.getMonth() === month && td.getFullYear() === year) {
                selectedCalendarDate = t;
                renderCalendarDetail(t);
                const todayBtn = grid.querySelector(`[data-calendar-date="${t}"]`);
                todayBtn?.classList.add('selected');
            }
        } else {
            renderCalendarDetail(selectedCalendarDate);
        }
    }

    function changeCalendarMonth(delta) {
        calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + delta, 1);
        selectedCalendarDate = '';
        renderCalendar();
    }

    function goCalendarToday() {
        const now = new Date();
        calendarDate = new Date(now.getFullYear(), now.getMonth(), 1);
        selectedCalendarDate = today();
        renderCalendar();
        renderCalendarDetail(selectedCalendarDate);
    }

    function shiftUsageCount(id){return assignments.filter(a=>Number(a.MaCa)===Number(id)).length;}
    function renderShifts() {
        const body = el('shiftTableBody');
        const grid = el('shiftCatalogGrid');
        if (!shifts.length) { if(body)body.innerHTML = `<tr><td colspan="${canManage ? 6 : 5}" class="shift-empty">Chưa có ca làm.</td></tr>`; if(grid)grid.innerHTML='<div class="shift-empty">Chưa có ca làm.</div>'; return; }
        if(body) body.innerHTML = shifts.map(s => `<tr><td><span class="shift-code-badge">CA${String(s.MaCa).padStart(3,'0')}</span></td><td><div class="shift-name-cell"><i class="fa-solid fa-clock"></i><div><strong>${escapeHTML(s.TenCa)}</strong><small>${shiftUsageCount(s.MaCa)} lịch đã dùng</small></div></div></td><td><span class="shift-time-pill">${timeShort(s.GioBatDau)} – ${timeShort(s.GioKetThuc)}</span></td><td><span class="shift-description-cell">${escapeHTML(s.MoTa || '--')}</span></td><td><span class="shift-status ${normalizeLibraryText(s.TrangThai).includes('ngung') ? 'off' : ''}">${escapeHTML(s.TrangThai || 'Hoạt động')}</span></td>${canManage ? `<td><div class="shift-row-actions"><button class="shift-action view" data-view-shift="${s.MaCa}" type="button" title="Xem"><i class="fa-solid fa-eye"></i></button><button class="shift-action edit" data-edit-shift="${s.MaCa}" type="button" title="Sửa"><i class="fa-solid fa-pen"></i></button><button class="shift-action delete" data-delete-shift="${s.MaCa}" type="button" title="Xóa"><i class="fa-solid fa-trash"></i></button></div></td>` : ''}</tr>`).join('');
        if(grid) grid.innerHTML=shifts.map(s=>`<article class="shift-catalog-item"><div class="catalog-icon"><i class="fa-solid fa-clock"></i></div><div class="catalog-head"><span>CA${String(s.MaCa).padStart(3,'0')}</span><span class="shift-status ${normalizeLibraryText(s.TrangThai).includes('ngung')?'off':''}">${escapeHTML(s.TrangThai||'Hoạt động')}</span></div><h4>${escapeHTML(s.TenCa)}</h4><div class="catalog-time">${timeShort(s.GioBatDau)} – ${timeShort(s.GioKetThuc)}</div><p>${escapeHTML(s.MoTa||'Chưa có mô tả')}</p><small>${shiftUsageCount(s.MaCa)} lịch phân công đã dùng</small>${canManage?`<div class="catalog-actions"><button data-edit-shift="${s.MaCa}" class="shift-btn primary">Sửa</button><button data-delete-shift="${s.MaCa}" class="shift-btn danger">Xóa</button></div>`:''}</article>`).join('');
        [body,grid].filter(Boolean).forEach(scope=>{if(canManage){scope.querySelectorAll('[data-edit-shift]').forEach(b=>b.addEventListener('click',()=>editShift(Number(b.dataset.editShift))));scope.querySelectorAll('[data-delete-shift]').forEach(b=>b.addEventListener('click',()=>deleteShift(Number(b.dataset.deleteShift))));}scope.querySelectorAll('[data-view-shift]').forEach(b=>b.addEventListener('click',()=>editShift(Number(b.dataset.viewShift))));});
    }

    function renderAssignments() {
        const body = el('assignmentTableBody'); if (!body) return;
        const rows = filteredAssignments();
        if (el('assignmentSummary')) el('assignmentSummary').textContent = `${rows.length} lịch phân công`;
        if (!rows.length) { body.innerHTML = `<tr><td colspan="${selfOnly ? 5 : (canManage ? 7 : 6)}" class="shift-empty">Chưa có lịch trong khoảng ngày đã chọn.</td></tr>`; return; }
        body.innerHTML = rows.map((a,i) => { const current=isCurrentAssignment(a), todayRow=isTodayAssignment(a); const badge=current?'<span class="shift-live-badge">Đang trực</span>':(todayRow?'<span class="shift-today-badge">Hôm nay</span>':''); return `<tr class="${current?'shift-current-row':todayRow?'shift-today-row':''}"><td>${i+1}</td><td>${formatLibraryDate(a.NgayLam)} ${badge}</td>
            ${selfOnly ? '' : `<td>NV${String(a.MaNhanVien).padStart(3,'0')} - ${escapeHTML(a.TenNhanVien || '--')}</td>`}
            <td>${escapeHTML(a.TenCa || '--')}</td><td>${timeShort(a.GioBatDau)} - ${timeShort(a.GioKetThuc)}</td><td>${escapeHTML(a.GhiChu || '--')}</td>
            ${canManage ? `<td><div class="shift-row-actions"><button class="shift-btn warning" data-edit-assignment="${a.MaPhanCong}" type="button">Sửa</button><button class="shift-btn danger" data-delete-assignment="${a.MaPhanCong}" type="button">Xóa</button></div></td>` : ''}</tr>`; }).join('');
        if (canManage) {
            body.querySelectorAll('[data-edit-assignment]').forEach(b => b.addEventListener('click', () => editAssignment(Number(b.dataset.editAssignment))));
            body.querySelectorAll('[data-delete-assignment]').forEach(b => b.addEventListener('click', () => deleteAssignment(Number(b.dataset.deleteAssignment))));
        }
    }

    function fillSelects() {
        const emp = el('assignmentEmployee');
        if (emp) emp.innerHTML = '<option value="">-- Chọn nhân viên --</option>' + employees.map(x => `<option value="${x.MaNhanVien}">NV${String(x.MaNhanVien).padStart(3,'0')} - ${escapeHTML(x.HoTen)}</option>`).join('');
        const filterEmp=el('shiftEmployeeFilter'); if(filterEmp) filterEmp.innerHTML='<option value="">Tất cả nhân viên</option>'+employees.map(x=>`<option value="${x.MaNhanVien}">NV${String(x.MaNhanVien).padStart(3,'0')} - ${escapeHTML(x.HoTen)}</option>`).join('');
        const filterShift=el('shiftTypeFilter'); if(filterShift) filterShift.innerHTML='<option value="">Tất cả ca</option>'+shifts.map(x=>`<option value="${x.MaCa}">${escapeHTML(x.TenCa)}</option>`).join('');
        renderAssignmentShiftChoices();
    }

    function activeShifts() {
        return shifts.filter(x => !normalizeLibraryText(x.TrangThai || '').includes('ngung'));
    }

    function renderAssignmentShiftChoices(selectedIds = []) {
        const box = el('assignmentShiftChoices');
        if (!box) return;
        const selected = new Set((selectedIds || []).map(Number));
        const rows = activeShifts();
        if (!rows.length) {
            box.innerHTML = '<div class="shift-empty">Chưa có ca hoạt động để phân công.</div>';
            updateAssignmentSelectionSummary();
            return;
        }
        box.innerHTML = rows.map(s => `<label class="assignment-shift-option">
            <input type="checkbox" class="assignment-shift-checkbox" value="${s.MaCa}" ${selected.has(Number(s.MaCa)) ? 'checked' : ''}>
            <span class="shift-choice-text"><strong>${escapeHTML(s.TenCa)}</strong><span>${timeShort(s.GioBatDau)} - ${timeShort(s.GioKetThuc)}</span></span>
        </label>`).join('');
        box.querySelectorAll('.assignment-shift-checkbox').forEach(cb => cb.addEventListener('change', updateAssignmentSelectionSummary));
        updateAssignmentSelectionSummary();
    }

    function assignmentDateValues() {
        return [...document.querySelectorAll('#assignmentDateList .assignment-date-input')]
            .map(input => input.value)
            .filter(Boolean)
            .filter((value, index, arr) => arr.indexOf(value) === index);
    }

    function assignmentShiftValues() {
        return [...document.querySelectorAll('#assignmentShiftChoices .assignment-shift-checkbox:checked')]
            .map(cb => Number(cb.value))
            .filter(Boolean);
    }

    function updateDateRemoveButtons() {
        const rows = [...document.querySelectorAll('#assignmentDateList .assignment-date-row')];
        rows.forEach(row => {
            const btn = row.querySelector('.assignment-date-remove');
            if (btn) btn.disabled = rows.length <= 1 || !!el('assignmentId')?.value;
        });
    }

    function addAssignmentDate(value = '') {
        const list = el('assignmentDateList');
        if (!list) return;
        const row = document.createElement('div');
        row.className = 'assignment-date-row';
        row.innerHTML = `<input class="assignment-date-input" type="date" value="${escapeHTML(value || '')}" required>
            <button class="assignment-date-remove" type="button" title="Bỏ ngày này"><i class="fa-solid fa-xmark"></i></button>`;
        row.querySelector('.assignment-date-input')?.addEventListener('change', updateAssignmentSelectionSummary);
        row.querySelector('.assignment-date-remove')?.addEventListener('click', () => {
            row.remove();
            updateDateRemoveButtons();
            updateAssignmentSelectionSummary();
        });
        list.appendChild(row);
        updateDateRemoveButtons();
        updateAssignmentSelectionSummary();
    }

    function setAssignmentDates(values = []) {
        const list = el('assignmentDateList');
        if (!list) return;
        list.innerHTML = '';
        const dates = values.length ? values : [today()];
        dates.forEach(addAssignmentDate);
        updateDateRemoveButtons();
    }

    function updateAssignmentSelectionSummary() {
        const summary = el('assignmentSelectionSummary');
        if (!summary) return;
        const dates = assignmentDateValues();
        const shiftIds = assignmentShiftValues();
        const total = dates.length * shiftIds.length;
        if (!dates.length || !shiftIds.length) {
            summary.className = 'assignment-selection-summary full empty';
            summary.textContent = 'Chọn ít nhất 1 ngày và 1 ca để tạo lịch phân công.';
            return;
        }
        if (el('assignmentId')?.value) {
            summary.className = 'assignment-selection-summary full';
            summary.innerHTML = '<span class="assignment-edit-note"><i class="fa-solid fa-pen"></i> Đang sửa 1 lịch phân công. Khi sửa chỉ chọn 1 ngày và 1 ca.</span>';
            return;
        }
        summary.className = 'assignment-selection-summary full';
        summary.innerHTML = `<strong>${dates.length} ngày × ${shiftIds.length} ca = ${total} lịch phân công</strong><br>Hệ thống sẽ tạo tất cả tổ hợp ngày và ca đã chọn cho nhân viên này.`;
    }

    function resetShiftForm() {
        if (!canManage) return;
        el('shiftId').value=''; el('shiftName').value=''; el('shiftStart').value=''; el('shiftEnd').value=''; el('shiftDescription').value=''; el('shiftStatus').value='Hoạt động'; if(el('shiftFormTitle'))el('shiftFormTitle').textContent='Thêm ca làm';
    }
    function editShift(id) { const row=shifts.find(x=>Number(x.MaCa)===id); if(!row)return; el('shiftId').value=row.MaCa; el('shiftName').value=row.TenCa||''; el('shiftStart').value=timeShort(row.GioBatDau); el('shiftEnd').value=timeShort(row.GioKetThuc); el('shiftDescription').value=row.MoTa||''; el('shiftStatus').value=row.TrangThai||'Hoạt động'; if(el('shiftFormTitle'))el('shiftFormTitle').textContent='Chỉnh sửa ca'; openShiftModal(); }
    async function deleteShift(id) { if(!(await window.libraryConfirm('Xóa ca làm này?','Xóa ca làm')))return; try{const p=await libraryApi.post('shift_delete',{id});toast(p.message);await reload();}catch(e){toast(e.message);} }

    function resetAssignmentForm(){
        if(!canManage)return;
        el('assignmentId').value='';
        el('assignmentEmployee').value='';
        el('assignmentNote').value='';
        setAssignmentDates([today()]);
        renderAssignmentShiftChoices([]);
        const addBtn = el('assignmentAddDateBtn'); if (addBtn) addBtn.disabled = false;
        if (el('assignmentFormTitle')) el('assignmentFormTitle').textContent = 'Phân công nhân viên';
        updateAssignmentSelectionSummary();
    }

    function editAssignment(id){
        const a=assignments.find(x=>Number(x.MaPhanCong)===id);if(!a)return;
        el('assignmentId').value=a.MaPhanCong;
        el('assignmentEmployee').value=a.MaNhanVien;
        el('assignmentNote').value=a.GhiChu||'';
        setAssignmentDates([a.NgayLam]);
        renderAssignmentShiftChoices([a.MaCa]);
        const addBtn = el('assignmentAddDateBtn'); if (addBtn) addBtn.disabled = true;
        if (el('assignmentFormTitle')) el('assignmentFormTitle').textContent = 'Sửa phân công nhân viên';
        updateDateRemoveButtons();
        updateAssignmentSelectionSummary();
        openAssignmentModal();
    }
    async function deleteAssignment(id){if(!(await window.libraryConfirm('Xóa lịch phân công này?','Xóa lịch phân công')))return;try{const p=await libraryApi.post('shift_assignment_delete',{id});toast(p.message);await reload();}catch(e){toast(e.message);}}

    function openShiftModal(){el('shiftEditorModal')?.classList.add('show');document.body.classList.add('shift-modal-open');}
    function closeShiftModal(){el('shiftEditorModal')?.classList.remove('show');document.body.classList.remove('shift-modal-open');}
    function openAssignmentModal(){el('assignmentModal')?.classList.add('show');document.body.classList.add('shift-modal-open');}
    function closeAssignmentModal(){el('assignmentModal')?.classList.remove('show');document.body.classList.remove('shift-modal-open');}
    function setTodayRange(){const t=today();if(el('shiftFromDate'))el('shiftFromDate').value=t;if(el('shiftToDate'))el('shiftToDate').value=t;renderAssignments();}
    function setThisMonth(){const n=new Date();const from=`${n.getFullYear()}-${pad2(n.getMonth()+1)}-01`;const to=new Date(n.getFullYear(),n.getMonth()+1,0).toLocaleDateString('en-CA');if(el('shiftFromDate'))el('shiftFromDate').value=from;if(el('shiftToDate'))el('shiftToDate').value=to;renderAssignments();}
    function setCatalogView(view){catalogView=view;document.querySelectorAll('[data-shift-view]').forEach(b=>b.classList.toggle('active',b.dataset.shiftView===view));el('shiftCatalogTableWrap')?.classList.toggle('d-none',view!=='table');el('shiftCatalogGrid')?.classList.toggle('d-none',view!=='card');}
    async function reload(){try{const p=await libraryApi.get('shifts');shifts=p.data.shifts||[];assignments=p.data.assignments||[];rosterAssignments=p.data.rosterAssignments||assignments||[];employees=p.data.employees||[];currentEmployeeId=Number(p.data.currentEmployeeId||0);fillSelects();renderShifts();renderAssignments();renderStats();renderCurrentShift();renderMyTodayShifts();renderTodayRoster();renderCalendar();if(p.data.message)toast(p.data.message);}catch(e){toast(e.message);}}

    el('shiftForm')?.addEventListener('submit',async e=>{e.preventDefault();try{const p=await libraryApi.post('shift_save',{id:el('shiftId').value||null,name:el('shiftName').value.trim(),start:el('shiftStart').value,end:el('shiftEnd').value,description:el('shiftDescription').value.trim(),status:el('shiftStatus').value});toast(p.message);resetShiftForm();closeShiftModal();await reload();}catch(err){toast(err.message);}});
    el('assignmentForm')?.addEventListener('submit',async e=>{
        e.preventDefault();
        try{
            const id = el('assignmentId').value || null;
            const employeeId = el('assignmentEmployee').value;
            const dates = assignmentDateValues();
            const shiftIds = assignmentShiftValues();
            if (!employeeId) throw new Error('Vui lòng chọn nhân viên.');
            if (!dates.length) throw new Error('Vui lòng chọn ít nhất 1 ngày làm.');
            if (!shiftIds.length) throw new Error('Vui lòng chọn ít nhất 1 ca làm.');
            if (id && (dates.length !== 1 || shiftIds.length !== 1)) throw new Error('Khi sửa một phân công, vui lòng chỉ chọn 1 ngày và 1 ca.');
            const p=await libraryApi.post('shift_assign',{id,employeeId,dates,shiftIds,note:el('assignmentNote').value.trim()});
            toast(p.message);
            resetAssignmentForm();
            closeAssignmentModal();
            await reload();
        }catch(err){toast(err.message);}
    });
    el('assignmentAddDateBtn')?.addEventListener('click',()=>{ if(!el('assignmentId')?.value) addAssignmentDate(''); });
    el('shiftResetBtn')?.addEventListener('click',resetShiftForm); el('assignmentResetBtn')?.addEventListener('click',resetAssignmentForm);
    el('shiftThisWeekBtn')?.addEventListener('click',setThisWeek); el('shiftFromDate')?.addEventListener('change',renderAssignments); el('shiftToDate')?.addEventListener('change',renderAssignments);
    el('shiftPrevMonth')?.addEventListener('click', () => changeCalendarMonth(-1));
    el('shiftNextMonth')?.addEventListener('click', () => changeCalendarMonth(1));
    el('shiftTodayMonth')?.addEventListener('click', goCalendarToday);

    el('openShiftModalBtn')?.addEventListener('click',()=>{resetShiftForm();openShiftModal();});
    el('openAssignModalBtn')?.addEventListener('click',()=>{resetAssignmentForm();openAssignmentModal();});
    document.querySelectorAll('[data-close-shift-modal]').forEach(x=>x.addEventListener('click',closeShiftModal));
    document.querySelectorAll('[data-close-assignment-modal]').forEach(x=>x.addEventListener('click',closeAssignmentModal));
    el('shiftTodayFilterBtn')?.addEventListener('click',setTodayRange);
    el('shiftThisMonthBtn')?.addEventListener('click',setThisMonth);
    el('shiftThisWeekHeroBtn')?.addEventListener('click',()=>{setThisWeek();document.querySelector('.shift-calendar-card')?.scrollIntoView({behavior:'smooth'});});
    el('shiftRefreshBtn')?.addEventListener('click',reload);
    el('scrollApprovalBtn')?.addEventListener('click',()=>el('advancedShiftRequestsCard')?.scrollIntoView({behavior:'smooth'}));
    el('scrollRegisterBtn')?.addEventListener('click',()=>el('advancedShiftBoard')?.scrollIntoView({behavior:'smooth'}));
    el('shiftEmployeeFilter')?.addEventListener('change',renderAssignments);
    el('shiftTypeFilter')?.addEventListener('change',renderAssignments);
    document.querySelectorAll('[data-shift-chip]').forEach(btn=>btn.addEventListener('click',()=>{shiftChip=btn.dataset.shiftChip||'all';document.querySelectorAll('[data-shift-chip]').forEach(x=>x.classList.toggle('active',x===btn));renderAssignments();}));
    document.querySelectorAll('[data-shift-view]').forEach(btn=>btn.addEventListener('click',()=>setCatalogView(btn.dataset.shiftView||'table')));
    document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeShiftModal();closeAssignmentModal();}});

    setThisWeek(); resetShiftForm(); resetAssignmentForm(); reload();
    clearInterval(window.__libraryShiftClock);
    window.__libraryShiftClock=setInterval(()=>{renderCurrentShift();renderAssignments();renderStats();renderMyTodayShifts();renderTodayRoster();renderCalendar();},60000);
})();

// === Nhân viên tự đăng ký ca / Quản lý duyệt ngay tại trang Ca làm ===
(async function loadAdvancedShiftRegistration(){
 const board=document.getElementById('advancedShiftAvailable');if(!board)return;const role=window.libraryAccess?.roleKey;
 const esc=window.escapeHTML||(x=>String(x??''));const fmt=d=>d?new Date(d+'T00:00:00').toLocaleDateString('vi-VN'):'--';
 const todayKey=()=>new Date().toLocaleDateString('en-CA');
 const addDay=(d,n)=>{const x=new Date(d+'T00:00:00');x.setDate(x.getDate()+n);return x.toLocaleDateString('en-CA')};
 let boardRows=[], mineRows=[], requestRows=[]; let registerFilter='all', approvalFilter='all';
 async function get(a){const u=new URL('advanced_api.php',location.href);u.searchParams.set('action',a);const r=await fetch(u,{credentials:'same-origin',cache:'no-store'});const p=await r.json();if(!r.ok||!p.ok)throw new Error(p.message||'Lỗi');return p.data}
 async function post(a,d){const r=await fetch('advanced_api.php?action='+a,{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(d)});const p=await r.json();if(!r.ok||!p.ok)throw new Error(p.message||'Lỗi');return p.data}
 function statusBadge(status){const n=String(status||'');const cls=n==='Đã duyệt'?'approved':n==='Từ chối'?'rejected':'pending';return `<span class="shift-request-status ${cls}">${esc(n||'Chờ duyệt')}</span>`}
 function availabilityClass(x){const max=Number(x.SoNguoiToiDa||4), used=Number(x.DaPhanCong||0); if(used>=max)return 'full'; if(used>=Math.max(1,max-1))return 'near'; return 'open'}
 function renderBoard(){
   const mine=new Map((mineRows||[]).map(r=>[`${r.MaCa}|${r.NgayLam}`,r.TrangThai]));
   const t=todayKey(), tomorrow=addDay(t,1);
   let rows=[...boardRows];
   if(registerFilter==='available')rows=rows.filter(x=>Number(x.DaPhanCong||0)<Number(x.SoNguoiToiDa||4));
   if(registerFilter==='today')rows=rows.filter(x=>x.NgayLam===t);
   if(registerFilter==='tomorrow')rows=rows.filter(x=>x.NgayLam===tomorrow);
   if(registerFilter==='morning')rows=rows.filter(x=>/sáng/i.test(x.TenCa||''));
   if(registerFilter==='afternoon')rows=rows.filter(x=>/chiều/i.test(x.TenCa||''));
   if(registerFilter==='evening')rows=rows.filter(x=>/tối/i.test(x.TenCa||''));
   if(registerFilter==='full')rows=rows.filter(x=>/nguyên/i.test(x.TenCa||''));
   board.innerHTML=rows.map(x=>{const status=mine.get(`${x.MaCa}|${x.NgayLam}`)||'';const max=Number(x.SoNguoiToiDa||4),used=Number(x.DaPhanCong||0),left=Math.max(0,max-used),tone=availabilityClass(x);let action='';if(role==='employee'){if(status==='Chờ duyệt') action='<button class="shift-registered" disabled><i class="fa-solid fa-clock"></i> Chờ duyệt</button>';else if(status==='Đã duyệt') action='<button class="shift-approved" disabled><i class="fa-solid fa-circle-check"></i> Đã duyệt</button>';else if(used>=max) action='<button class="shift-full" disabled>Đã đầy</button>';else action=`<button data-reg-shift="${x.MaCa}" data-reg-date="${x.NgayLam}"><i class="fa-solid fa-plus"></i> Đăng ký</button>`;}return `<article class="shift-opportunity-card ${tone}" data-date="${x.NgayLam}" data-name="${esc(x.TenCa)}"><div class="opportunity-date"><span>${fmt(x.NgayLam)}</span><small>${esc(x.TenCa)}</small></div><div class="opportunity-main"><div class="opportunity-time"><i class="fa-regular fa-clock"></i>${String(x.GioBatDau).slice(0,5)}–${String(x.GioKetThuc).slice(0,5)}</div><div class="opportunity-capacity"><div><strong>${used}/${max} người</strong><span>${left>0?`Còn ${left} chỗ`:'Đã đủ người'}</span></div><div class="capacity-track"><i style="width:${Math.min(100,used/max*100)}%"></i></div></div></div><span class="availability-badge ${tone}">${tone==='full'?'Đã đủ':tone==='near'?'Sắp đầy':'Còn chỗ'}</span>${action}</article>`}).join('')||'<div class="shift-empty employee-empty-state"><i class="fa-regular fa-calendar-xmark"></i><strong>Không có ca phù hợp</strong><span>Thử đổi bộ lọc hoặc xem lại trong ít phút.</span></div>';
   board.querySelectorAll('[data-reg-shift]').forEach(x=>x.onclick=async()=>{try{x.disabled=true;await post('shift_register',{shiftId:+x.dataset.regShift,date:x.dataset.regDate});await load()}catch(e){x.disabled=false;alert(e.message)}});
   const pending=mineRows.filter(x=>x.TrangThai==='Chờ duyệt').length;const node=document.getElementById('employeePendingCount');if(node)node.textContent=pending;
 }
 function renderRequests(){
   const box=document.getElementById('advancedShiftRequests');if(!box)return;
   let rows=[...requestRows];if(approvalFilter==='pending')rows=rows.filter(x=>x.TrangThai==='Chờ duyệt');if(approvalFilter==='approved')rows=rows.filter(x=>x.TrangThai==='Đã duyệt');if(approvalFilter==='rejected')rows=rows.filter(x=>x.TrangThai==='Từ chối');
   box.innerHTML=rows.map(x=>`<article class="shift-request-card"><div class="request-avatar">${esc((x.HoTen||'N').trim().charAt(0).toUpperCase())}</div><div class="request-person"><strong>${esc(x.HoTen)}</strong><small>${fmt(x.NgayLam)} · ${esc(x.TenCa)}</small></div><div class="request-time"><i class="fa-regular fa-clock"></i>${String(x.GioBatDau).slice(0,5)}–${String(x.GioKetThuc).slice(0,5)}</div>${statusBadge(x.TrangThai)}${x.TrangThai==='Chờ duyệt'?`<div class="request-actions"><button data-shift-approve="${x.MaDangKy}"><i class="fa-solid fa-check"></i> Duyệt</button><button class="reject" data-shift-reject="${x.MaDangKy}"><i class="fa-solid fa-xmark"></i> Từ chối</button></div>`:''}</article>`).join('')||'<div class="shift-empty">Không có yêu cầu đăng ký ca.</div>';
   box.querySelectorAll('[data-shift-approve],[data-shift-reject]').forEach(x=>x.onclick=async()=>{try{await post('shift_decide',{id:+(x.dataset.shiftApprove||x.dataset.shiftReject),mode:x.dataset.shiftApprove?'approve':'reject'});load()}catch(e){alert(e.message)}});
   const pending=requestRows.filter(x=>x.TrangThai==='Chờ duyệt').length;const node=document.getElementById('pendingRequestCount');if(node)node.textContent=pending;
 }
 function updateManagerMetrics(){const under=boardRows.filter(x=>Number(x.DaPhanCong||0)<Number(x.SoNguoiToiDa||4)).length;const u=document.getElementById('understaffedCount');if(u)u.textContent=under;const alert=document.getElementById('shiftAlertCenter');if(alert){const extra=`<div class="shift-alert-item ${under?'red':'green'}"><i class="fa-solid fa-users-slash"></i><div><strong>${under} ca còn thiếu người trong 7 ngày</strong><small>${under?'Ưu tiên phân công hoặc duyệt đăng ký':'Không có ca thiếu người'}</small></div></div><div class="shift-alert-item orange"><i class="fa-solid fa-envelope-open-text"></i><div><strong>${requestRows.filter(x=>x.TrangThai==='Chờ duyệt').length} yêu cầu chờ duyệt</strong><small>Đăng ký ca từ nhân viên</small></div></div>`;alert.insertAdjacentHTML('beforeend',extra);}}
 async function load(){try{const b=await get('shift_board');boardRows=b.rows||[];mineRows=b.mine||[];renderBoard();if(['admin','manager'].includes(role)){requestRows=await get('shift_requests')||[];renderRequests();updateManagerMetrics();}}catch(e){board.innerHTML=`<div class="shift-empty">${esc(e.message)}</div>`}}
 document.querySelectorAll('[data-register-filter]').forEach(btn=>btn.addEventListener('click',()=>{registerFilter=btn.dataset.registerFilter||'all';document.querySelectorAll('[data-register-filter]').forEach(x=>x.classList.toggle('active',x===btn));renderBoard();}));
 document.querySelectorAll('[data-approval-filter]').forEach(btn=>btn.addEventListener('click',()=>{approvalFilter=btn.dataset.approvalFilter||'all';document.querySelectorAll('[data-approval-filter]').forEach(x=>x.classList.toggle('active',x===btn));renderRequests();}));
 load();
})();
