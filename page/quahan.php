<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('quahan');
$canManageLoans = hasLibraryPermission('loans_manage');
$canViewStatistics = hasLibraryPermission('statistics_view');
$canSendOverdueEmail = hasLibraryPermission('email_overdue_send') && in_array(currentLibraryRoleKey(), ['manager','employee'], true);
?>
<div class="legacy-stat-page overdue-stat-modern quahan-container">
  <section class="legacy-stat-hero overdue-hero">
    <div>
      <a href="#" onclick="loadPage('<?= $canViewStatistics ? 'thongke.php' : 'trangchu.php' ?>','<?= $canViewStatistics ? 'Thống kê' : 'Trang chủ' ?>'); return false;" class="legacy-back"><i class="fa-solid fa-arrow-left"></i> <?= $canViewStatistics ? 'Trung tâm thống kê' : 'Trang chủ' ?></a>
      <span class="legacy-kicker"><i class="fa-solid fa-triangle-exclamation"></i> Overdue Analytics</span>
      <h2>Thống kê sách quá hạn</h2>
      <p>Giữ nguyên danh sách quá hạn và biểu đồ đường theo tháng mượn, nhưng nâng cấp giao diện để dễ theo dõi và xử lý hơn.</p>
    </div>
    <div class="legacy-hero-art"><i class="fa-solid fa-chart-line"></i><span></span><span></span><span></span></div>
  </section>

  <section class="legacy-metric-grid three quahan-info">
    <article class="legacy-metric red quahan-card"><i class="fa-solid fa-book-open"></i><div><span>Sách quá hạn</span><strong id="overdueBookCount">0</strong><small>Cuốn chưa hoàn trả</small></div></article>
    <article class="legacy-metric orange quahan-card"><i class="fa-solid fa-users"></i><div><span>Độc giả vi phạm</span><strong id="overdueReaderCount">0</strong><small>Có phiếu quá hạn</small></div></article>
    <article class="legacy-metric purple quahan-card"><i class="fa-solid fa-money-bill-wave"></i><div><span>Tiền phạt tạm tính</span><strong id="overdueFineTotal">0</strong><small>Đơn vị: đồng</small></div></article>
  </section>

  <section class="legacy-chart-card quahan-chart">
    <div class="legacy-chart-head"><div><span>Biểu đồ đường</span><h3>Quá hạn theo tháng mượn</h3><p>Biểu đồ cũ được giữ nguyên dữ liệu và logic.</p></div><i class="fa-solid fa-chart-line"></i></div>
    <div class="legacy-chart-canvas line"><canvas id="overdueChart"></canvas></div>
  </section>

  <section class="legacy-table-card quahan-table">
    <div class="legacy-chart-head"><div><span>Chi tiết</span><h3>Danh sách sách quá hạn</h3></div><i class="fa-solid fa-table-list"></i></div>
    <div class="quahan-search legacy-toolbar">
      <input id="overdueSearch" type="text" class="form-control" placeholder="Nhập tên độc giả hoặc tên sách">
      <button class="btn btn-primary" type="button"><i class="fa-solid fa-search"></i> Tìm kiếm</button>
      <?php if ($canSendOverdueEmail): ?><button class="btn btn-warning" id="sendOverdueEmailBtn" type="button"><i class="fa-solid fa-envelope"></i> Gửi mail nhắc quá hạn &gt;30 ngày</button><?php endif; ?>
    </div>
    <div class="legacy-table-wrap"><table class="table text-center">
      <thead><tr><th>STT</th><th>Mã độc giả</th><th>Độc giả</th><th>Tên sách</th><th>Ngày mượn</th><th>Hạn trả</th><th>Số ngày quá hạn</th><th>Tiền phạt</th><th>Trạng thái</th><?php if ($canManageLoans): ?><th>Xử lý</th><?php endif; ?></tr></thead>
      <tbody id="overdueTableBody"></tbody>
    </table></div>
  </section>
</div>
