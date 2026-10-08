(() => {
  const $ = id => document.getElementById(id);
  const esc = v => window.escapeHTML ? window.escapeHTML(v ?? '') : String(v ?? '');
  const fmt = n => Number(n || 0).toLocaleString('vi-VN');
  const fmtDate = v => window.formatLibraryDate ? window.formatLibraryDate(v) : (v || '--');
  let payload = null;
  let currentTab = 'books';
  let currentView = 'overview';
  let compareEnabled = false;
  let charts = {};
  let currentRangeKey = 'month';

  const chartColors = ['#2563eb','#7c3aed','#0ea5e9','#14b8a6','#f59e0b','#ef4444','#ec4899','#22c55e','#6366f1','#0891b2','#f97316','#84cc16'];
  function destroyChart(key){ if(charts[key]){ charts[key].destroy(); charts[key]=null; } }
  function countUp(node, value){
    if(!node) return;
    const end = Number(value || 0), start = Number(node.dataset.value || 0), t0 = performance.now(), duration=650;
    const tick = now => { const p=Math.min(1,(now-t0)/duration), eased=1-Math.pow(1-p,3); node.textContent=fmt(Math.round(start+(end-start)*eased)); if(p<1) requestAnimationFrame(tick); else node.dataset.value=String(end); };
    requestAnimationFrame(tick);
  }
  function pctChange(curr, prev){
    curr=Number(curr||0); prev=Number(prev||0);
    if(prev===0) return curr===0 ? null : 100;
    return ((curr-prev)/prev)*100;
  }
  function compareText(curr, prev, suffix=' so với kỳ trước'){
    if(!compareEnabled) return '';
    const pct=pctChange(curr,prev);
    if(pct===null) return 'Không thay đổi so với kỳ trước';
    const up=pct>0, down=pct<0;
    return `${up?'↑ ':down?'↓ ':''}${Math.abs(pct).toFixed(1)}%${suffix}`;
  }
  function setCompareClass(node, curr, prev, invert=false){
    if(!node) return;
    node.classList.remove('positive','negative');
    if(!compareEnabled) return;
    const d=Number(curr||0)-Number(prev||0);
    if(d===0) return;
    const good = invert ? d<0 : d>0;
    node.classList.add(good?'positive':'negative');
  }
  function formatPeriodLabel(k){
    if(!k) return '--';
    if(/^\d{4}-\d{2}-\d{2}$/.test(k)) return fmtDate(k);
    if(/^\d{4}-\d{2}$/.test(k)){ const [y,m]=k.split('-'); return `${m}/${y}`; }
    return k;
  }
  function initials(name=''){ return String(name).trim().split(/\s+/).filter(Boolean).slice(-2).map(x=>x[0]?.toUpperCase()||'').join('') || 'DG'; }

  function resolveRange(key){
    const now=new Date(); const d=new Date(now.getFullYear(),now.getMonth(),now.getDate());
    let start=new Date(d), end=new Date(d);
    if(key==='7d') start.setDate(d.getDate()-6);
    if(key==='30d') start.setDate(d.getDate()-29);
    if(key==='month') start=new Date(d.getFullYear(),d.getMonth(),1);
    if(key==='quarter'){ const qm=Math.floor(d.getMonth()/3)*3; start=new Date(d.getFullYear(),qm,1); }
    if(key==='year') start=new Date(d.getFullYear(),0,1);
    const iso=x=>`${x.getFullYear()}-${String(x.getMonth()+1).padStart(2,'0')}-${String(x.getDate()).padStart(2,'0')}`;
    return {start:iso(start), end:iso(end)};
  }
  function activateRange(key){
    currentRangeKey=key;
    document.querySelectorAll('#statsRangeChips button').forEach(b=>b.classList.toggle('active',b.dataset.range===key));
    $('statsCustomRange')?.classList.toggle('show',key==='custom');
    if(key!=='custom'){
      const r=resolveRange(key);
      $('statsStartDate').value=r.start; $('statsEndDate').value=r.end;
      loadStats(r.start,r.end);
    }
  }

  async function loadStats(start, end){
    try{
      const p=await libraryApi.get('statistics',{start,end}); payload=p.data||{};
      renderAll();
      const r=payload.range||{};
      $('statsUpdatedText').innerHTML=`<i class="fa-regular fa-clock"></i> Cập nhật lần cuối: ${new Date().toLocaleString('vi-VN')} · Kỳ ${fmtDate(r.start)} – ${fmtDate(r.end)}`;
    }catch(e){ alert(e.message || 'Không thể tải thống kê.'); }
  }

  function renderSummary(){
    const s=payload.summary||{}, prev=payload.previous||{};
    countUp($('statTotalBooks'),s.totalBooks); countUp($('statReaders'),s.totalReaders); countUp($('statBorrowing'),s.borrowing); countUp($('statOverdue'),s.overdue);
    countUp($('heroBooksValue'),s.totalBooks); countUp($('heroReadersValue'),s.totalReaders); if($('heroOnTimeValue')) $('heroOnTimeValue').textContent=`${Number(s.onTimeRate||0).toFixed(1)}%`;
    $('statBooksSub').textContent=`${fmt(s.totalTitles)} đầu sách · ${fmt(s.totalCategories)} thể loại`;
    $('statReadersSub').textContent=compareEnabled ? compareText(s.newReaders,prev.newReaders,' độc giả mới') : `${fmt(s.newReaders)} độc giả mới trong kỳ`;
    $('statBorrowingSub').textContent=compareEnabled ? compareText(s.totalLoanSlips,prev.totalLoanSlips) : `${fmt(s.totalLoanSlips)} phiếu phát sinh trong kỳ`;
    $('statOverdueSub').textContent=compareEnabled ? compareText(s.overdueSlips,prev.overdueSlips) : `${fmt(s.overdueSlips)} phiếu quá hạn trong kỳ`;
    setCompareClass($('statReadersSub'),s.newReaders,prev.newReaders,false);
    setCompareClass($('statBorrowingSub'),s.totalLoanSlips,prev.totalLoanSlips,false);
    setCompareClass($('statOverdueSub'),s.overdueSlips,prev.overdueSlips,true);
  }
  function metricCard(label,value,sub,icon,cls='blue'){
    return `<article class="statsx-mini-metric ${cls}"><div><span>${esc(label)}</span><strong>${esc(value)}</strong><small>${esc(sub||'')}</small></div><i class="fa-solid ${icon}"></i></article>`;
  }
  function renderBookSection(){
    const s=payload.summary||{}, rows=payload.byCategory||[];
    $('bookMiniMetrics').innerHTML=[
      metricCard('Tổng đầu sách',fmt(s.totalTitles),'Đầu sách đã phân loại','fa-book','blue'),
      metricCard('Tổng số cuốn',fmt(s.totalBooks),'Toàn bộ số lượng trong kho','fa-books','purple'),
      metricCard('Số thể loại',fmt(s.totalCategories),'Nhóm phân loại hiện có','fa-layer-group','green'),
      metricCard('Còn hàng / hết hàng',`${fmt(s.availableTitles)} / ${fmt(s.outOfStockTitles)}`,'Theo đầu sách','fa-boxes-stacked','orange')
    ].join('');
    $('bookTableCount').textContent=`${fmt(rows.length)} thể loại`;
    const total=Math.max(1,rows.reduce((a,b)=>a+Number(b.SoLuong||0),0)), max=Math.max(1,...rows.map(x=>Number(x.SoLuong||0)));
    $('statCategoryBody').innerHTML=rows.length?rows.map((x,i)=>{
      const share=Number(x.SoLuong||0)/total*100; let status='Ổn định', cls='ok';
      if(Number(x.SoLuong||0)<=5){status='Cần bổ sung';cls='danger';} else if(Number(x.SoLuong||0)<=15){status='Ít';cls='warn';} else if(Number(x.SoLuong||0)>=max*.7){status='Nhiều';cls='good';}
      return `<tr><td>${i+1}</td><td><div class="statsx-cat-cell"><span class="statsx-cat-icon"><i class="fa-solid fa-layer-group"></i></span><strong>${esc(x.TenTheLoai||'--')}</strong></div></td><td><span class="statsx-soft-badge">${fmt(x.SoDauSach)} đầu</span></td><td><div class="statsx-stock"><div><strong>${fmt(x.SoLuong)}</strong><small>${share.toFixed(1)}%</small></div><span><b style="width:${Math.max(3,Number(x.SoLuong||0)/max*100)}%"></b></span></div></td><td>${share.toFixed(1)}%</td><td><span class="statsx-state ${cls}">${status}</span></td></tr>`;
    }).join(''):'<tr><td colspan="6" class="text-center py-4">Chưa có dữ liệu.</td></tr>';
    $('bookTopList').innerHTML=rows.slice(0,10).map((x,i)=>`<div class="statsx-progress-row"><div><strong>#${i+1} ${esc(x.TenTheLoai)}</strong><span>${fmt(x.SoLuong)} cuốn</span></div><div class="statsx-progress-track"><b style="width:${Math.max(3,Number(x.SoLuong||0)/max*100)}%"></b></div><small>${fmt(x.SoDauSach)} đầu sách</small></div>`).join('');
    $('bookLowList').innerHTML=[...rows].sort((a,b)=>Number(a.SoLuong||0)-Number(b.SoLuong||0)).slice(0,5).map(x=>`<div class="statsx-mini-row"><div><strong>${esc(x.TenTheLoai)}</strong><small>${fmt(x.SoDauSach)} đầu sách</small></div><b>${fmt(x.SoLuong)} cuốn</b></div>`).join('');
    const top=rows[0], low=[...rows].sort((a,b)=>Number(a.SoLuong||0)-Number(b.SoLuong||0))[0], top3=rows.slice(0,3), top3sum=top3.reduce((a,b)=>a+Number(b.SoLuong||0),0);
    $('bookInsights').innerHTML=[
      top?`<div><i class="fa-solid fa-arrow-trend-up"></i><span><strong>${esc(top.TenTheLoai)}</strong> hiện có nhiều sách nhất với <b>${fmt(top.SoLuong)}</b> cuốn.</span></div>`:'',
      low?`<div><i class="fa-solid fa-box-open"></i><span><strong>${esc(low.TenTheLoai)}</strong> là nhóm ít sách nhất với <b>${fmt(low.SoLuong)}</b> cuốn.</span></div>`:'',
      rows.length?`<div><i class="fa-solid fa-chart-pie"></i><span>Top 3 thể loại chiếm <b>${(top3sum/total*100).toFixed(1)}%</b> tổng kho hiện tại.</span></div>`:''
    ].join('');
    renderBookCharts(rows);
  }
  function renderBookCharts(rows){
    destroyChart('bookBar'); destroyChart('bookDonut');
    const bar=$('bookCategoryBar'), donut=$('bookCategoryDonut');
    if(window.Chart && bar){ charts.bookBar=new Chart(bar,{type:'bar',data:{labels:rows.map(x=>x.TenTheLoai),datasets:[{label:'Số lượng',data:rows.map(x=>+x.SoLuong||0),backgroundColor:rows.map((_,i)=>chartColors[i%chartColors.length]),borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,animation:{duration:850},plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{precision:0}}},onClick:(e,els)=>{ if(els[0]){ const name=rows[els[0].index]?.TenTheLoai; [...document.querySelectorAll('#statCategoryBody tr')].forEach(tr=>tr.classList.toggle('highlight',tr.textContent.includes(name))); } }}}); }
    if(window.Chart && donut){ const top=rows.slice(0,8); charts.bookDonut=new Chart(donut,{type:'doughnut',data:{labels:top.map(x=>x.TenTheLoai),datasets:[{data:top.map(x=>+x.SoLuong||0),backgroundColor:top.map((_,i)=>chartColors[i%chartColors.length]),borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom',labels:{boxWidth:10,usePointStyle:true}}}}}); }
  }

  function renderReaderSection(){
    const s=payload.summary||{}, prev=payload.previous||{}, gender=payload.gender||[], monthly=payload.readerMonthly||[], areas=payload.readerAreas||[], tops=payload.topReaders||[];
    const readersBorrowing=tops.filter(x=>Number(x.DangMuon||0)>0).length, readersOverdue=tops.filter(x=>Number(x.QuaHan||0)>0).length;
    $('readerMiniMetrics').innerHTML=[
      metricCard('Tổng độc giả',fmt(s.totalReaders),'Toàn hệ thống','fa-users','blue'),
      metricCard('Đang hoạt động',fmt(s.activeReaders),'Theo trạng thái hồ sơ','fa-user-check','green'),
      metricCard('Mới đăng ký',fmt(s.newReaders),compareEnabled?compareText(s.newReaders,prev.newReaders):'Trong khoảng đã chọn','fa-user-plus','purple'),
      metricCard('Đang có phiếu mượn',fmt(readersBorrowing),'Trong nhóm hoạt động nổi bật','fa-book-open-reader','orange'),
      metricCard('Có quá hạn',fmt(readersOverdue),'Cần theo dõi','fa-triangle-exclamation','red')
    ].join('');
    const maxArea=Math.max(1,...areas.map(x=>+x.SoLuong||0));
    $('readerAreaList').innerHTML=areas.map(x=>`<div class="statsx-progress-row"><div><strong>${esc(x.KhuVuc)}</strong><span>${fmt(x.SoLuong)} độc giả</span></div><div class="statsx-progress-track"><b style="width:${Math.max(4,+x.SoLuong/maxArea*100)}%"></b></div></div>`).join('')||'<div class="statsx-empty">Chưa có dữ liệu khu vực.</div>';
    $('topReaderList').innerHTML=tops.slice(0,6).map((x,i)=>`<div class="statsx-person-row"><span class="statsx-avatar">${initials(x.HoTen)}</span><div><strong>${esc(x.HoTen)}</strong><small>DG${String(x.MaDocGia).padStart(3,'0')} · ${fmt(x.LuotMuon)} lượt mượn</small></div><span class="statsx-soft-badge">${fmt(x.DangMuon)} đang mượn</span></div>`).join('')||'<div class="statsx-empty">Chưa có dữ liệu mượn trong kỳ.</div>';
    const latest=payload.latestReaders||[];
    $('latestReaderGrid').innerHTML=latest.map(x=>`<article class="statsx-reader-card"><span class="statsx-avatar big">${initials(x.HoTen)}</span><div><strong>${esc(x.HoTen)}</strong><small>DG${String(x.MaDocGia).padStart(3,'0')}</small><p>Đăng ký ${fmtDate(x.NgayDangKy)}</p></div><span class="statsx-state ok">${esc(x.TrangThai||'--')}</span></article>`).join('')||'<div class="statsx-empty">Chưa có độc giả.</div>';
    const gTop=[...gender].sort((a,b)=>+b.SoLuong-+a.SoLuong)[0];
    $('readerInsights').innerHTML=[
      gTop?`<div><i class="fa-solid fa-venus-mars"></i><span>Nhóm giới tính có số lượng cao nhất là <strong>${esc(gTop.GioiTinh)}</strong> với <b>${fmt(gTop.SoLuong)}</b> độc giả.</span></div>`:'',
      `<div><i class="fa-solid fa-user-plus"></i><span>Có <b>${fmt(s.newReaders)}</b> độc giả mới trong kỳ đang xem.${compareEnabled&&prev.newReaders!=null?` ${compareText(s.newReaders,prev.newReaders)}`:''}</span></div>`,
      `<div><i class="fa-solid fa-book-reader"></i><span>Top danh sách hiện có <b>${fmt(readersBorrowing)}</b> độc giả đang giữ sách và <b>${fmt(readersOverdue)}</b> độc giả có dấu hiệu quá hạn.</span></div>`
    ].join('');
    renderReaderCharts(monthly,gender);
  }
  function renderReaderCharts(monthly,gender){
    destroyChart('readerMonthly'); destroyChart('readerGender');
    if(window.Chart && $('readerMonthlyChart')) charts.readerMonthly=new Chart($('readerMonthlyChart'),{type:'line',data:{labels:monthly.map(x=>formatPeriodLabel(x.Thang)),datasets:[{label:'Độc giả mới',data:monthly.map(x=>+x.SoLuong||0),borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.12)',fill:true,tension:.38,pointRadius:4}]},options:{responsive:true,maintainAspectRatio:false,animation:{duration:850},plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{precision:0}}}}});
    if(window.Chart && $('readerGenderChart')) charts.readerGender=new Chart($('readerGenderChart'),{type:'doughnut',data:{labels:gender.map(x=>x.GioiTinh),datasets:[{data:gender.map(x=>+x.SoLuong||0),backgroundColor:gender.map((_,i)=>chartColors[i%chartColors.length]),borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:10}}}}});
  }

  function loanStatusLabel(row){
    if(row.NgayTra) return 'Đã trả';
    if(row.HanTra && new Date(row.HanTra+'T23:59:59') < new Date()) return 'Quá hạn';
    return row.TrangThai || 'Đang mượn';
  }
  function renderLoanSection(){
    const s=payload.summary||{}, prev=payload.previous||{}, trend=payload.loanTrend||[], topBooks=payload.topBooks||[], topReaders=payload.topReaders||[], loans=payload.recentLoans||[];
    $('loanMiniMetrics').innerHTML=[
      metricCard('Tổng phiếu mượn',fmt(s.totalLoanSlips),compareEnabled?compareText(s.totalLoanSlips,prev.totalLoanSlips):'Trong kỳ','fa-receipt','blue'),
      metricCard('Đang mượn',fmt(s.activeLoanSlips),'Chưa hoàn trả','fa-book-open-reader','orange'),
      metricCard('Đã trả',fmt(s.returnedSlips),'Trong kỳ','fa-circle-check','green'),
      metricCard('Quá hạn',fmt(s.overdueSlips),compareEnabled?compareText(s.overdueSlips,prev.overdueSlips):'Cần xử lý','fa-triangle-exclamation','red'),
      metricCard('Tỷ lệ trả đúng hạn',`${Number(s.onTimeRate||0).toFixed(1)}%`,compareEnabled?compareText(s.onTimeRate,prev.onTimeRate):'Trên các phiếu đã trả','fa-gauge-high','purple')
    ].join('');
    const maxBook=Math.max(1,...topBooks.map(x=>+x.LuotMuon||0));
    $('topBookList').innerHTML=topBooks.slice(0,8).map((x,i)=>`<div class="statsx-progress-row"><div><strong>#${i+1} ${esc(x.TenSach)}</strong><span>${fmt(x.LuotMuon)} lượt</span></div><div class="statsx-progress-track"><b style="width:${Math.max(4,+x.LuotMuon/maxBook*100)}%"></b></div><small>${esc(x.TenTheLoai||'')}</small></div>`).join('')||'<div class="statsx-empty">Chưa có lượt mượn trong kỳ.</div>';
    $('loanTopReaderList').innerHTML=topReaders.slice(0,6).map(x=>`<div class="statsx-person-row"><span class="statsx-avatar">${initials(x.HoTen)}</span><div><strong>${esc(x.HoTen)}</strong><small>${fmt(x.LuotMuon)} lượt mượn</small></div><span class="statsx-soft-badge">${fmt(x.DangMuon)} sách</span></div>`).join('')||'<div class="statsx-empty">Chưa có dữ liệu.</div>';
    $('loanTableCount').textContent=`${fmt(loans.length)} phiếu hiển thị`;
    $('loanStatsBody').innerHTML=loans.length?loans.map(x=>{ const st=loanStatusLabel(x); const cls=st==='Quá hạn'?'danger':st==='Đã trả'?'good':'ok'; return `<tr><td><strong>PM${String(x.MaPhieuMuon).padStart(3,'0')}</strong></td><td>${esc(x.DocGia||'--')}</td><td><div class="statsx-loan-book">${esc(x.Sach||'--')}<small>${fmt(x.SoLuong)} cuốn</small></div></td><td>${fmtDate(x.NgayMuon)}</td><td>${fmtDate(x.HanTra)}</td><td>${fmtDate(x.NgayTra)}</td><td><span class="statsx-state ${cls}">${esc(st)}</span></td></tr>`; }).join(''):'<tr><td colspan="7" class="text-center py-4">Chưa có phiếu trong kỳ.</td></tr>';
    const topBook=topBooks[0], maxTrend=[...trend].sort((a,b)=>+b.Muon-+a.Muon)[0];
    $('loanInsights').innerHTML=[
      topBook&&Number(topBook.LuotMuon)>0?`<div><i class="fa-solid fa-fire"></i><span><strong>${esc(topBook.TenSach)}</strong> là sách được mượn nhiều nhất trong kỳ với <b>${fmt(topBook.LuotMuon)}</b> lượt.</span></div>`:'<div><i class="fa-solid fa-book"></i><span>Chưa có lượt mượn đủ để xác định sách nổi bật trong kỳ.</span></div>',
      `<div><i class="fa-solid fa-clock"></i><span>Tỷ lệ trả đúng hạn hiện là <b>${Number(s.onTimeRate||0).toFixed(1)}%</b>${compareEnabled?`; ${compareText(s.onTimeRate,prev.onTimeRate)}`:''}.</span></div>`,
      maxTrend?`<div><i class="fa-solid fa-calendar-day"></i><span>Kỳ có lượng mượn cao nhất là <strong>${formatPeriodLabel(maxTrend.Ky)}</strong> với <b>${fmt(maxTrend.Muon)}</b> phiếu.</span></div>`:''
    ].join('');
    renderLoanCharts(trend,s);
  }
  function renderLoanCharts(trend,s){
    destroyChart('loanTrend'); destroyChart('loanStatus');
    if(window.Chart && $('loanTrendChart')) charts.loanTrend=new Chart($('loanTrendChart'),{type:'line',data:{labels:trend.map(x=>formatPeriodLabel(x.Ky)),datasets:[{label:'Mượn',data:trend.map(x=>+x.Muon||0),borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.08)',tension:.38,fill:true,pointRadius:3},{label:'Trả',data:trend.map(x=>+x.Tra||0),borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.05)',tension:.38,fill:true,pointRadius:3},{label:'Quá hạn',data:trend.map(x=>+x.QuaHan||0),borderColor:'#ef4444',backgroundColor:'rgba(239,68,68,.04)',tension:.38,fill:true,pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,animation:{duration:900},interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom',labels:{usePointStyle:true}}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{precision:0}}}}});
    if(window.Chart && $('loanStatusDonut')) charts.loanStatus=new Chart($('loanStatusDonut'),{type:'doughnut',data:{labels:['Đúng hạn','Quá hạn'],datasets:[{data:[+s.onTimeReturnedSlips||0,+s.overdueSlips||0],backgroundColor:['#22c55e','#ef4444'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true}}}}});
  }

  function renderAll(){ renderSummary(); renderBookSection(); renderReaderSection(); renderLoanSection(); applyView(); }
  function setTab(tab){ currentTab=tab; currentView='overview'; document.querySelectorAll('[data-stats-view]').forEach(b=>b.classList.toggle('active',b.dataset.statsView==='overview')); document.querySelectorAll('[data-stats-tab]').forEach(b=>b.classList.toggle('active',b.dataset.statsTab===tab)); document.querySelectorAll('.statsx-panel').forEach(p=>p.classList.toggle('active',p.dataset.panel===tab)); applyView(); setTimeout(()=>{ Object.values(charts).forEach(c=>c?.resize?.()); },80); }
  function setView(view){ currentView=view; document.querySelectorAll('[data-stats-view]').forEach(b=>b.classList.toggle('active',b.dataset.statsView===view)); applyView(); }
  function applyView(){
    document.querySelectorAll('.statsx-overview-block').forEach(n=>n.classList.toggle('view-hidden',currentView==='table'));
    document.querySelectorAll('.statsx-table-card').forEach(n=>n.classList.toggle('view-hidden',currentView==='chart'));
  }

  function csvEscape(v){ return `"${String(v??'').replace(/"/g,'""')}"`; }
  function exportCsv(){
    if(!payload) return;
    let rows=[];
    if(currentTab==='books') rows=[['Thể loại','Đầu sách','Số lượng'],...(payload.byCategory||[]).map(x=>[x.TenTheLoai,x.SoDauSach,x.SoLuong])];
    if(currentTab==='readers') rows=[['Mã độc giả','Họ tên','Lượt mượn','Đang mượn','Quá hạn'],...(payload.topReaders||[]).map(x=>[`DG${String(x.MaDocGia).padStart(3,'0')}`,x.HoTen,x.LuotMuon,x.DangMuon,x.QuaHan])];
    if(currentTab==='loans') rows=[['Mã phiếu','Độc giả','Sách','Ngày mượn','Hạn trả','Ngày trả','Trạng thái'],...(payload.recentLoans||[]).map(x=>[`PM${String(x.MaPhieuMuon).padStart(3,'0')}`,x.DocGia,x.Sach,x.NgayMuon,x.HanTra,x.NgayTra,loanStatusLabel(x)])];
    const blob=new Blob(['\ufeff'+rows.map(r=>r.map(csvEscape).join(',')).join('\n')],{type:'text/csv;charset=utf-8'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download=`thong-ke-${currentTab}.csv`; a.click(); URL.revokeObjectURL(url);
  }
  function exportImage(){
    const canvas=currentTab==='books'?$('bookCategoryBar'):currentTab==='readers'?$('readerMonthlyChart'):$('loanTrendChart'); if(!canvas) return alert('Không có biểu đồ để tải.');
    const a=document.createElement('a'); a.href=canvas.toDataURL('image/png'); a.download=`bieu-do-${currentTab}.png`; a.click();
  }

  $('statsRefreshBtn')?.addEventListener('click',()=>loadStats($('statsStartDate').value,$('statsEndDate').value));
  $('statsCompareBtn')?.addEventListener('click',()=>{ compareEnabled=!compareEnabled; $('statsCompareBtn').classList.toggle('active',compareEnabled); $('statsCompareNote').classList.toggle('show',compareEnabled); renderAll(); });
  $('statsRangeBtn')?.addEventListener('click',()=>{ $('statsCustomRange').classList.add('show'); currentRangeKey='custom'; document.querySelectorAll('#statsRangeChips button').forEach(b=>b.classList.toggle('active',b.dataset.range==='custom')); });
  $('statsApplyRangeBtn')?.addEventListener('click',()=>{ if(!$('statsStartDate').value||!$('statsEndDate').value) return alert('Vui lòng chọn đủ khoảng thời gian.'); loadStats($('statsStartDate').value,$('statsEndDate').value); });
  document.querySelectorAll('#statsRangeChips button').forEach(b=>b.addEventListener('click',()=>activateRange(b.dataset.range)));
  document.querySelectorAll('[data-stats-tab]').forEach(b=>b.addEventListener('click',()=>setTab(b.dataset.statsTab)));
  document.querySelectorAll('[data-stats-view]').forEach(b=>b.addEventListener('click',()=>setView(b.dataset.statsView)));
  document.querySelectorAll('[data-legacy-chart]').forEach(card=>{ const open=()=>loadPage(card.dataset.legacyChart,card.dataset.legacyTitle||'Thống kê'); card.addEventListener('click',open); card.addEventListener('keydown',e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); open(); } }); });
  $('statsExportBtn')?.addEventListener('click',e=>{ e.stopPropagation(); $('statsExportMenu').classList.toggle('show'); const r=e.currentTarget.getBoundingClientRect(); $('statsExportMenu').style.top=`${r.bottom+8}px`; $('statsExportMenu').style.left=`${Math.max(12,r.left)}px`; });
  document.querySelectorAll('#statsExportMenu [data-export]').forEach(b=>b.addEventListener('click',()=>{ const x=b.dataset.export; $('statsExportMenu').classList.remove('show'); if(x==='csv') exportCsv(); if(x==='print') window.print(); if(x==='image') exportImage(); }));
  document.addEventListener('click',e=>{ if(!e.target.closest('#statsExportMenu')&&!e.target.closest('#statsExportBtn')) $('statsExportMenu')?.classList.remove('show'); });

  const initial=resolveRange('month'); $('statsStartDate').value=initial.start; $('statsEndDate').value=initial.end; setTab('books'); setView('overview'); loadStats(initial.start,initial.end);
})();
