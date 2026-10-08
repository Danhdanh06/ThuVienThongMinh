<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('thongke');
?>
<div class="statsx-page">
  <section class="statsx-hero">
    <div class="statsx-hero-copy">
      <span class="statsx-kicker"><i class="fa-solid fa-chart-line"></i> Statistics Center</span>
      <h2>Thống kê thư viện</h2>
      <p>Theo dõi hiệu suất thư viện, tình trạng kho sách, độc giả và hoạt động mượn – trả theo thời gian thực.</p>
      <div class="statsx-hero-actions">
        <button class="btn btn-primary" id="statsExportBtn" type="button"><i class="fa-solid fa-file-export me-2"></i>Xuất báo cáo</button>
        <button class="btn btn-outline-primary" id="statsRangeBtn" type="button"><i class="fa-regular fa-calendar me-2"></i>Chọn khoảng thời gian</button>
        <button class="btn btn-light" id="statsCompareBtn" type="button"><i class="fa-solid fa-code-compare me-2"></i>So sánh kỳ trước</button>
        <button class="btn btn-light" id="statsRefreshBtn" type="button"><i class="fa-solid fa-rotate-right me-2"></i>Làm mới</button>
      </div>
      <div class="statsx-updated" id="statsUpdatedText"><i class="fa-regular fa-clock"></i> Đang tải dữ liệu...</div>
    </div>
    <div class="statsx-hero-visual" aria-hidden="true">
      <div class="statsx-hero-icon"><i class="fa-solid fa-chart-pie"></i></div>
      <div class="statsx-blob b1"></div><div class="statsx-blob b2"></div>
      <div class="statsx-mini-card sm1"><span>Kho sách</span><strong id="heroBooksValue">0</strong></div>
      <div class="statsx-mini-card sm2"><span>Độc giả</span><strong id="heroReadersValue">0</strong></div>
      <div class="statsx-mini-card sm3"><span>Tỷ lệ đúng hạn</span><strong id="heroOnTimeValue">0%</strong></div>
      <div class="statsx-visual-bars"><span></span><span></span><span></span><span></span><span></span><span></span></div>
    </div>
  </section>

  <section class="statsx-summary-grid">
    <article class="statsx-summary-card blue"><div><span>Tổng số sách</span><strong id="statTotalBooks">0</strong><small id="statBooksSub">Toàn bộ kho hiện tại</small></div><i class="fa-solid fa-book"></i><div class="statsx-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
    <article class="statsx-summary-card green is-clickable" tabindex="0" role="button" data-legacy-chart="docgia2.php" data-legacy-title="Thống kê độc giả"><div><span>Độc giả</span><strong id="statReaders">0</strong><small id="statReadersSub">Độc giả trong hệ thống</small><em class="statsx-open-chart"><i class="fa-solid fa-chart-line"></i> Mở biểu đồ chi tiết</em></div><i class="fa-solid fa-users"></i><div class="statsx-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
    <article class="statsx-summary-card orange is-clickable" tabindex="0" role="button" data-legacy-chart="dangmuon.php" data-legacy-title="Đang mượn"><div><span>Đang mượn</span><strong id="statBorrowing">0</strong><small id="statBorrowingSub">Sách chưa hoàn trả</small><em class="statsx-open-chart"><i class="fa-solid fa-chart-column"></i> Mở biểu đồ chi tiết</em></div><i class="fa-solid fa-book-open-reader"></i><div class="statsx-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
    <article class="statsx-summary-card red is-clickable" tabindex="0" role="button" data-legacy-chart="quahan.php" data-legacy-title="Quá hạn"><div><span>Quá hạn</span><strong id="statOverdue">0</strong><small id="statOverdueSub">Cần xử lý</small><em class="statsx-open-chart"><i class="fa-solid fa-chart-line"></i> Mở biểu đồ chi tiết</em></div><i class="fa-solid fa-triangle-exclamation"></i><div class="statsx-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
  </section>

  <section class="statsx-global-filter">
    <div class="statsx-range-chips" id="statsRangeChips">
      <button type="button" data-range="today">Hôm nay</button>
      <button type="button" data-range="7d">7 ngày</button>
      <button type="button" data-range="30d">30 ngày</button>
      <button type="button" class="active" data-range="month">Tháng này</button>
      <button type="button" data-range="quarter">Quý này</button>
      <button type="button" data-range="year">Năm này</button>
      <button type="button" data-range="custom">Tùy chọn</button>
    </div>
    <div class="statsx-custom-range" id="statsCustomRange">
      <label>Từ ngày<input type="date" id="statsStartDate"></label>
      <label>Đến ngày<input type="date" id="statsEndDate"></label>
      <button type="button" class="btn btn-primary" id="statsApplyRangeBtn">Áp dụng</button>
    </div>
    <div class="statsx-compare-note" id="statsCompareNote"><i class="fa-solid fa-circle-info"></i> So sánh với kỳ liền trước có cùng số ngày.</div>
  </section>

  <section class="statsx-tabs-wrap">
    <div class="statsx-tabs" role="tablist">
      <button type="button" class="active" data-stats-tab="books"><i class="fa-solid fa-books"></i><span>Thống kê Sách</span></button>
      <button type="button" data-stats-tab="readers"><i class="fa-solid fa-user-group"></i><span>Thống kê Độc giả</span></button>
      <button type="button" data-stats-tab="loans"><i class="fa-solid fa-right-left"></i><span>Thống kê Mượn – Trả</span></button>
    </div>
    <div class="statsx-view-switch">
      <button type="button" class="active" data-stats-view="overview">Tổng hợp</button>
      <button type="button" data-stats-view="chart">Biểu đồ</button>
      <button type="button" data-stats-view="table">Bảng</button>
    </div>
  </section>

  <section class="statsx-panel active" data-panel="books">
    <div class="statsx-mini-metrics" id="bookMiniMetrics"></div>
    <div class="statsx-grid two statsx-overview-block">
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Biểu đồ</span><h3>Số lượng sách theo thể loại</h3></div></div><div class="statsx-chart-box tall"><canvas id="bookCategoryBar"></canvas></div></article>
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Tỷ trọng</span><h3>Cơ cấu kho sách</h3></div></div><div class="statsx-chart-box donut"><canvas id="bookCategoryDonut"></canvas></div></article>
    </div>
    <div class="statsx-grid three statsx-overview-block">
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Top</span><h3>Top 10 thể loại nhiều sách nhất</h3></div></div><div class="statsx-progress-list" id="bookTopList"></div></article>
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Cân bằng kho</span><h3>Top 5 thể loại ít sách nhất</h3></div></div><div class="statsx-mini-list" id="bookLowList"></div></article>
      <article class="statsx-card insight"><div class="statsx-card-head"><div><span class="statsx-label">Insight</span><h3>Nhận xét nhanh</h3></div></div><div class="statsx-insight-list" id="bookInsights"></div></article>
    </div>
    <article class="statsx-card statsx-table-card"><div class="statsx-card-head"><div><span class="statsx-label">Chi tiết</span><h3>Thống kê thể loại</h3></div><span id="bookTableCount"></span></div><div class="statsx-table-wrap"><table class="table statsx-table"><thead><tr><th>STT</th><th>Thể loại</th><th>Đầu sách</th><th>Số lượng hiện có</th><th>Tỷ trọng</th><th>Trạng thái</th></tr></thead><tbody id="statCategoryBody"></tbody></table></div></article>
  </section>

  <section class="statsx-panel" data-panel="readers">
    <div class="statsx-mini-metrics" id="readerMiniMetrics"></div>
    <div class="statsx-grid three statsx-overview-block">
      <article class="statsx-card span2"><div class="statsx-card-head"><div><span class="statsx-label">Xu hướng</span><h3>Đăng ký độc giả theo thời gian</h3></div></div><div class="statsx-chart-box"><canvas id="readerMonthlyChart"></canvas></div></article>
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Cơ cấu</span><h3>Tỷ lệ giới tính</h3></div></div><div class="statsx-chart-box donut"><canvas id="readerGenderChart"></canvas></div></article>
    </div>
    <div class="statsx-grid three statsx-overview-block">
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Khu vực</span><h3>Phân bố độc giả</h3></div></div><div class="statsx-progress-list" id="readerAreaList"></div></article>
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Nổi bật</span><h3>Top độc giả mượn nhiều</h3></div></div><div class="statsx-person-list" id="topReaderList"></div></article>
      <article class="statsx-card insight"><div class="statsx-card-head"><div><span class="statsx-label">Insight</span><h3>Nhận xét nhanh</h3></div></div><div class="statsx-insight-list" id="readerInsights"></div></article>
    </div>
    <article class="statsx-card statsx-table-card"><div class="statsx-card-head"><div><span class="statsx-label">Gần đây</span><h3>Độc giả mới đăng ký</h3></div></div><div class="statsx-card-grid" id="latestReaderGrid"></div></article>
  </section>

  <section class="statsx-panel" data-panel="loans">
    <div class="statsx-mini-metrics" id="loanMiniMetrics"></div>
    <div class="statsx-grid three statsx-overview-block">
      <article class="statsx-card span2"><div class="statsx-card-head"><div><span class="statsx-label">Xu hướng</span><h3>Mượn / Trả theo thời gian</h3></div></div><div class="statsx-chart-box"><canvas id="loanTrendChart"></canvas></div></article>
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Hiệu suất</span><h3>Đúng hạn / Quá hạn</h3></div></div><div class="statsx-chart-box donut"><canvas id="loanStatusDonut"></canvas></div></article>
    </div>
    <div class="statsx-grid three statsx-overview-block">
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Phổ biến</span><h3>Top sách được mượn nhiều</h3></div></div><div class="statsx-progress-list" id="topBookList"></div></article>
      <article class="statsx-card"><div class="statsx-card-head"><div><span class="statsx-label">Độc giả</span><h3>Top độc giả mượn nhiều</h3></div></div><div class="statsx-person-list" id="loanTopReaderList"></div></article>
      <article class="statsx-card insight"><div class="statsx-card-head"><div><span class="statsx-label">Insight</span><h3>Nhận xét nhanh</h3></div></div><div class="statsx-insight-list" id="loanInsights"></div></article>
    </div>
    <article class="statsx-card statsx-table-card"><div class="statsx-card-head"><div><span class="statsx-label">Chi tiết</span><h3>Phiếu mượn trong kỳ</h3></div><span id="loanTableCount"></span></div><div class="statsx-table-wrap"><table class="table statsx-table"><thead><tr><th>Mã phiếu</th><th>Độc giả</th><th>Sách</th><th>Ngày mượn</th><th>Hạn trả</th><th>Ngày trả</th><th>Trạng thái</th></tr></thead><tbody id="loanStatsBody"></tbody></table></div></article>
  </section>

  <div class="statsx-export-menu" id="statsExportMenu">
    <button type="button" data-export="csv"><i class="fa-solid fa-file-csv"></i> Xuất CSV</button>
    <button type="button" data-export="print"><i class="fa-solid fa-print"></i> In / Lưu PDF</button>
    <button type="button" data-export="image"><i class="fa-solid fa-image"></i> Tải ảnh biểu đồ đang xem</button>
  </div>
</div>
