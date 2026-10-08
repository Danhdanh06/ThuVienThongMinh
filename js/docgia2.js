(() => {
    function lastSixMonths() {
        const result = [];
        const now = new Date();
        for (let i = 5; i >= 0; i--) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            result.push({
                key: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`,
                label: `T${d.getMonth() + 1}`
            });
        }
        return result;
    }

    function drawLine(monthRows) {
        const canvas = document.getElementById("lineChart");
        if (!canvas || typeof Chart === "undefined") return;
        const months = lastSixMonths();
        const valuesByMonth = new Map((monthRows || []).map(x => [x.Thang, Number(x.SoLuong || 0)]));
        const values = months.map(m => valuesByMonth.get(m.key) || 0);
        const ctx = canvas.getContext("2d");
        const gradient = ctx.createLinearGradient(0, 0, 0, 350);
        gradient.addColorStop(0, "rgba(13,110,253,0.5)");
        gradient.addColorStop(1, "rgba(13,110,253,0)");

        new Chart(ctx, {
            type: "line",
            data: {
                labels: months.map(m => m.label),
                datasets: [{
                    label: "Độc giả mới",
                    data: values,
                    borderColor: "#0d6efd",
                    backgroundColor: gradient,
                    fill: true,
                    borderWidth: 3,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBackgroundColor: "#0d6efd"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: "nearest", intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title(items) { return `Tháng: ${items[0].label}`; },
                            label(item) { return `Độc giả đăng ký: ${item.raw} người`; }
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

    function drawPie(genderRows) {
        const canvas = document.getElementById("pieChart");
        if (!canvas || typeof Chart === "undefined") return;
        const rows = (genderRows || []).map(x => ({
            label: x.GioiTinh === "Khác" ? "Chưa cập nhật" : x.GioiTinh,
            value: Number(x.SoLuong || 0)
        })).filter(x => x.value > 0);

        const labels = rows.length ? rows.map(x => x.label) : ["Chưa có dữ liệu"];
        const values = rows.length ? rows.map(x => x.value) : [1];
        const colorMap = {
            "Nam": "#0d6efd",
            "Nữ": "#ff69b4",
            "Chưa cập nhật": "#94a3b8",
            "Chưa có dữ liệu": "#e5e7eb"
        };
        const colors = labels.map(label => colorMap[label] || "#6f42c1");
        const plugins = (typeof ChartDataLabels !== "undefined" && rows.length) ? [ChartDataLabels] : [];

        new Chart(canvas.getContext("2d"), {
            plugins,
            type: "pie",
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: "#ffffff",
                    borderWidth: 3,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: "bottom" },
                    datalabels: rows.length ? {
                        color: "#fff",
                        font: { size: 18, weight: "bold" },
                        formatter(value) { return value; }
                    } : { display: false },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                if (!rows.length) return "Chưa có dữ liệu giới tính";
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percent = total ? ((context.raw / total) * 100).toFixed(1) : "0.0";
                                return `${context.label}: ${context.raw} người (${percent}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    async function init() {
        try {
            const payload = await libraryApi.get("statistics");
            const data = payload.data;
            document.getElementById("readerStatTotal").textContent = data.summary?.totalReaders || 0;
            const genders = Object.fromEntries((data.gender || []).map(x => [x.GioiTinh, Number(x.SoLuong)]));
            document.getElementById("readerStatMale").textContent = genders.Nam || 0;
            document.getElementById("readerStatFemale").textContent = genders.Nữ || 0;
            drawLine(data.legacyReaderMonthly || data.readerMonthly || []);
            drawPie(data.gender || []);
        } catch (error) {
            alert(error.message);
        }
    }

    init();
})();
