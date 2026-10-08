(() => {
    const palette = ["#0d6efd", "#fd7e14", "#198754", "#dc3545", "#6f42c1"];

    function lastSixMonths() {
        const result = [];
        const now = new Date();
        for (let i = 5; i >= 0; i--) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
            result.push({ key, label: `T${d.getMonth() + 1}` });
        }
        return result;
    }

    function chartFor(canvas, labels, values, color, categoryName) {
        if (!canvas || typeof Chart === "undefined") return;
        const ctx = canvas.getContext("2d");
        const gradient = ctx.createLinearGradient(0, 0, 0, 350);
        gradient.addColorStop(0, color + "66");
        gradient.addColorStop(1, color + "00");

        new Chart(ctx, {
            type: "line",
            data: {
                labels,
                datasets: [{
                    data: values,
                    borderColor: color,
                    backgroundColor: gradient,
                    fill: true,
                    borderWidth: 3,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBackgroundColor: color
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: "nearest", axis: "x", intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            title(items) { return `Tháng: ${items[0].label}`; },
                            label(item) { return `${categoryName}: ${item.raw} cuốn`; }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 } }
                }
            }
        });
    }


    function categoryBarChart(canvas, rows) {
        if (!canvas || typeof Chart === "undefined") return;
        const labels = rows.map(row => row.TenTheLoai || "Chưa phân loại");
        const values = rows.map(row => Number(row.SoLuong || 0));

        new Chart(canvas.getContext("2d"), {
            type: "bar",
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: rows.map((_, index) => palette[index % palette.length] + "CC"),
                    borderColor: rows.map((_, index) => palette[index % palette.length]),
                    borderWidth: 1.5,
                    borderRadius: 8,
                    maxBarThickness: 54
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            label(item) { return `${item.raw} cuốn đang mượn`; }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0, minRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, stepSize: 1 }
                    }
                }
            }
        });
    }

    async function init() {
        try {
            const payload = await libraryApi.get("statistics");
            const rows = payload.data.borrowingCategory || [];
            const history = payload.data.borrowingCategoryMonthly || [];
            const box = document.getElementById("borrowCategoryCards");
            if (!box) return;

            const totalEl = document.getElementById("borrowSummaryTotal");
            const summaryCanvas = document.getElementById("borrowCategorySummaryChart");

            if (!rows.length) {
                if (totalEl) totalEl.textContent = "0 cuốn";
                const summaryWrap = summaryCanvas?.closest(".borrow-summary-card");
                if (summaryWrap) summaryWrap.classList.add("is-empty");
                box.innerHTML = '<div class="book-card"><div class="borrow-count">Hiện chưa có sách đang mượn.</div></div>';
                return;
            }

            const totalTopCategories = rows.reduce((sum, row) => sum + Number(row.SoLuong || 0), 0);
            if (totalEl) totalEl.textContent = `${totalTopCategories} cuốn`;
            categoryBarChart(summaryCanvas, rows);

            const months = lastSixMonths();
            box.innerHTML = rows.map((row, index) => `
                <div class="book-card">
                    <div class="book-title">📚 ${escapeHTML(row.TenTheLoai || "Chưa phân loại")}</div>
                    <div class="borrow-count">Đang mượn: <b>${Number(row.SoLuong || 0)} cuốn</b></div>
                    <canvas id="borrowChart${index}"></canvas>
                </div>
            `).join("");

            rows.forEach((row, index) => {
                const byMonth = new Map(
                    history
                        .filter(item => item.TenTheLoai === row.TenTheLoai)
                        .map(item => [item.Thang, Number(item.SoLuong || 0)])
                );
                const values = months.map(m => byMonth.get(m.key) || 0);
                chartFor(
                    document.getElementById(`borrowChart${index}`),
                    months.map(m => m.label),
                    values,
                    palette[index % palette.length],
                    row.TenTheLoai || "Chưa phân loại"
                );
            });
        } catch (error) {
            alert(error.message);
        }
    }

    init();
})();
