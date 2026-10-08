<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('dangmuon');
?>
<div class="legacy-stat-page borrow-stat-modern">
  <section class="legacy-stat-hero borrow-hero">
    <div>
      <a href="#" onclick="loadPage('thongke.php','Thống kê'); return false;" class="legacy-back"><i class="fa-solid fa-arrow-left"></i> Trung tâm thống kê</a>
      <span class="legacy-kicker"><i class="fa-solid fa-book-open-reader"></i> Borrowing Analytics</span>
      <h2>Sách đang mượn</h2>
      <p>Giữ nguyên các biểu đồ cũ theo thể loại và 6 tháng gần nhất, đồng thời làm lại bố cục theo phong cách dashboard hiện đại.</p>
    </div>
    <div class="legacy-hero-art"><i class="fa-solid fa-chart-column"></i><span></span><span></span><span></span></div>
  </section>

  <section class="legacy-chart-card borrow-summary-card">
    <div class="legacy-chart-head borrow-summary-head">
      <div>
        <span>Biểu đồ cột</span>
        <h3>Số sách đang mượn theo thể loại</h3>
        <p class="borrow-summary-note">So sánh nhanh các thể loại có nhiều sách đang được mượn nhất.</p>
      </div>
      <div class="legacy-total-chip borrow-summary-total"><span>Tổng trong biểu đồ</span><strong id="borrowSummaryTotal">0 cuốn</strong></div>
    </div>
    <div class="legacy-chart-canvas tall borrow-summary-chart-wrap"><canvas id="borrowCategorySummaryChart"></canvas></div>
  </section>

  <div class="legacy-section-heading borrow-section-heading">
    <div><span class="legacy-kicker"><i class="fa-solid fa-wave-square"></i> Xu hướng</span><h3 class="borrow-section-title">Mượn theo thể loại trong 6 tháng gần nhất</h3><p class="borrow-section-note">Mỗi card bên dưới vẫn dùng biểu đồ đường cũ, nhưng được bố trí gọn và đồng bộ hơn.</p></div>
  </div>
  <section class="legacy-dynamic-chart-grid" id="borrowCategoryCards"></section>
</div>
