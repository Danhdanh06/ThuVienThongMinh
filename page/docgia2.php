<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('docgia2');
?>
<div class="legacy-stat-page reader-stat-modern">
  <section class="legacy-stat-hero reader-hero">
    <div>
      <a href="#" onclick="loadPage('thongke.php','Thống kê'); return false;" class="legacy-back"><i class="fa-solid fa-arrow-left"></i> Trung tâm thống kê</a>
      <span class="legacy-kicker"><i class="fa-solid fa-users"></i> Reader Analytics</span>
      <h2>Thống kê độc giả</h2>
      <p>Giữ nguyên biểu đồ độc giả cũ, nhưng trình bày lại trực quan hơn để theo dõi xu hướng đăng ký và cơ cấu giới tính.</p>
    </div>
    <div class="legacy-hero-art"><i class="fa-solid fa-chart-line"></i><span></span><span></span><span></span></div>
  </section>

  <section class="legacy-metric-grid three">
    <article class="legacy-metric blue"><i class="fa-solid fa-users"></i><div><span>Tổng độc giả</span><strong id="readerStatTotal">0</strong><small>Toàn bộ hồ sơ hiện có</small></div></article>
    <article class="legacy-metric cyan"><i class="fa-solid fa-person"></i><div><span>Nam</span><strong id="readerStatMale">0</strong><small>Cơ cấu giới tính</small></div></article>
    <article class="legacy-metric pink"><i class="fa-solid fa-person-dress"></i><div><span>Nữ</span><strong id="readerStatFemale">0</strong><small>Cơ cấu giới tính</small></div></article>
  </section>

  <section class="legacy-chart-grid">
    <article class="legacy-chart-card wide">
      <div class="legacy-chart-head"><div><span>Biểu đồ đường</span><h3>Độc giả đăng ký trong 6 tháng gần nhất</h3><p>Biểu đồ gốc được giữ lại, chỉ nâng cấp giao diện hiển thị.</p></div><i class="fa-solid fa-chart-line"></i></div>
      <div class="legacy-chart-canvas line"><canvas id="lineChart"></canvas></div>
    </article>
    <article class="legacy-chart-card">
      <div class="legacy-chart-head"><div><span>Biểu đồ tròn</span><h3>Tỷ lệ độc giả theo giới tính</h3><p>Cơ cấu Nam / Nữ / chưa cập nhật.</p></div><i class="fa-solid fa-chart-pie"></i></div>
      <div class="legacy-chart-canvas donut docgia-pie-box"><canvas id="pieChart"></canvas></div>
    </article>
  </section>
</div>
