(() => {
    let rows = [];
    let fineRate = 0;
    const canManageLoans = !!window.libraryAccess?.canManageLoans;
    const canSendOverdueEmail = !!window.libraryAccess?.canSendOverdueEmail;
    const el = id => document.getElementById(id);
    const today = new Date().toLocaleDateString('en-CA');
    const days = due => Math.max(0, Math.ceil((new Date(today + "T00:00:00") - new Date(due + "T00:00:00")) / 86400000));

    function lastSixMonths() {
        const result = [], now = new Date();
        for (let i = 5; i >= 0; i--) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            result.push({key:`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,"0")}`,label:`Tháng ${d.getMonth()+1}`});
        }
        return result;
    }

    function filtered() {
        const q = normalizeLibraryText(el("overdueSearch")?.value || "");
        return rows.filter(x => !q || normalizeLibraryText(`${x.TenDocGia || ""} ${x.TenSach || ""} ${x.MaDocGia || ""}`).includes(q));
    }

    function render() {
        const data = filtered();
        const cols = canManageLoans ? 10 : 9;
        el("overdueTableBody").innerHTML = data.length ? data.map((x, i) => {
            const late = Number(x.SoNgayQuaHan || days(x.HanTra));
            const fine = late * Number(x.TongSoLuong || 0) * fineRate;
            return `<tr><td>${i+1}</td><td>DG${String(x.MaDocGia).padStart(3,"0")}</td><td>${escapeHTML(x.TenDocGia||"--")}</td>
                <td>${escapeHTML(x.TenSach||"--")}</td><td>${formatLibraryDate(x.NgayMuon)}</td><td>${formatLibraryDate(x.HanTra)}</td>
                <td>${late} ngày</td><td>${fine.toLocaleString("vi-VN")}đ</td><td><span class="quahan-danger">Chưa trả</span></td>
                ${canManageLoans ? `<td><button class="btn btn-success btn-sm" data-return="${x.MaPhieuMuon}" type="button"><i class="fa-solid fa-check"></i> Trả</button></td>` : ''}</tr>`;
        }).join("") : `<tr><td colspan="${cols}">Không có sách quá hạn.</td></tr>`;

        if (canManageLoans) el("overdueTableBody").querySelectorAll("[data-return]").forEach(button => button.addEventListener("click", async () => {
            if (!(await window.libraryConfirm("Xác nhận trả sách cho phiếu này?",'Xác nhận trả sách',false))) return;
            try { const p=await libraryApi.post("loan_return",{id:button.dataset.return}); alert(p.message); await init(); }
            catch(error){ alert(error.message); }
        }));
    }

    function drawChart(chartRows) {
        const canvas=el("overdueChart"); if(!canvas||typeof Chart==="undefined")return;
        if (canvas._chart) canvas._chart.destroy();
        const months=lastSixMonths(), valueMap=new Map((chartRows||[]).map(x=>[x.Thang,Number(x.SoLuong||0)]));
        const values=months.map(m=>valueMap.get(m.key)||0);
        canvas._chart = new Chart(canvas.getContext("2d"),{
            type:"line", data:{labels:months.map(m=>m.label),datasets:[{label:"Số sách quá hạn",data:values,fill:true,borderWidth:3,tension:.4,pointRadius:6}]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0,stepSize:1}},x:{grid:{display:false}}}}
        });
    }

    async function init() {
        try {
            const payload=await libraryApi.get("overdue_summary");
            rows=payload.data.rows||[]; fineRate=Number(payload.data.fineRate||0);
            const totalBooks=rows.reduce((a,x)=>a+Number(x.TongSoLuong||0),0);
            const readerCount=new Set(rows.map(x=>x.MaDocGia)).size;
            const totalFine=rows.reduce((a,x)=>a+Number(x.SoNgayQuaHan||days(x.HanTra))*Number(x.TongSoLuong||0)*fineRate,0);
            el("overdueBookCount").textContent=totalBooks;
            el("overdueReaderCount").textContent=readerCount;
            el("overdueFineTotal").textContent=totalFine.toLocaleString("vi-VN");
            render(); drawChart(payload.data.monthly||[]);
        } catch(error){ alert(error.message); }
    }

    el("overdueSearch")?.addEventListener("input",render);
    el('sendOverdueEmailBtn')?.addEventListener('click', async () => {
        if (!canSendOverdueEmail) return;
        if (!(await window.libraryConfirm('Gửi email nhắc cho các phiếu quá hạn trên 30 ngày chưa được gửi trước đó?','Gửi email nhắc',false))) return;
        const btn = el('sendOverdueEmailBtn');
        if (btn) { btn.disabled = true; btn.textContent = 'Đang gửi...'; }
        try {
            const p = await libraryApi.post('email_overdue', {});
            alert(p.message || 'Đã xử lý email quá hạn.');
        } catch (e) { alert(e.message); }
        finally { if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-envelope"></i> Gửi mail nhắc quá hạn >30 ngày'; } }
    });
    init();
})();
