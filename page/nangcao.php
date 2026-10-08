<?php require_once __DIR__.'/../database/connect.php'; require_once __DIR__.'/../auth.php'; requireLibraryPage('nangcao'); $rk=currentLibraryRoleKey(); ?>
<div class="smart-library smart-control-center" data-role="<?=htmlspecialchars($rk)?>">
  <section class="smart-hero smart-command-hero">
    <div class="smart-hero-copy">
      <span class="smart-kicker"><i class="fa-solid fa-wave-square"></i> LIBRARY CONTROL CENTER</span>
      <h2>Thư viện thông minh</h2>
      <p>Điều hành kho sách, mượn – trả, cảnh báo, tài khoản và nghiệp vụ thư viện trong một không gian quản trị thống nhất.</p>
      <div class="smart-hero-status-row">
        <span class="smart-live-dot"><i></i> Hệ thống đang hoạt động</span>
        <span id="smartLastSync"><i class="fa-regular fa-clock"></i> Đang đồng bộ...</span>
      </div>
      <div class="smart-hero-actions">
        <button class="smart-btn primary" id="openCommand"><i class="fa-solid fa-magnifying-glass"></i> Tìm nhanh <kbd>Ctrl K</kbd></button>
        <button class="smart-btn glass" id="refreshSmart"><i class="fa-solid fa-rotate"></i> Làm mới</button>
      </div>
    </div>
    <div class="smart-system-radar" aria-label="Trạng thái hệ thống">
      <div class="smart-radar-glow"></div>
      <div class="smart-radar-ring ring-1"></div><div class="smart-radar-ring ring-2"></div><div class="smart-radar-ring ring-3"></div>
      <div class="smart-radar-core"><i class="fa-solid fa-book-open-reader"></i><strong>ONLINE</strong><span>Library OS</span></div>
      <div class="smart-radar-node node-stock"><span>Kho sách</span><strong id="radarStock">--</strong></div>
      <div class="smart-radar-node node-loan"><span>Mượn trả</span><strong id="radarLoan">--</strong></div>
      <div class="smart-radar-node node-alert"><span>Cảnh báo</span><strong id="radarAlert">--</strong></div>
      <div class="smart-radar-node node-account"><span>Tài khoản</span><strong id="radarAccount">--</strong></div>
    </div>
    <div class="smart-grid-overlay"></div><div class="smart-orb orb-a"></div><div class="smart-orb orb-b"></div>
  </section>

  <div class="smart-tabs-shell">
    <div class="smart-tabs" id="smartTabs">
      <span class="smart-tab-indicator" id="smartTabIndicator"></span>
      <button data-tab="overview" class="active"><i class="fa-solid fa-gauge-high"></i><span>Tổng quan</span></button>
      <?php if(in_array($rk,['admin','manager','employee'])):?><button data-tab="scan"><i class="fa-solid fa-qrcode"></i><span>Quét mượn/trả</span></button><button data-tab="copies"><i class="fa-solid fa-barcode"></i><span>Mã từng cuốn</span></button><?php endif;?>
      <button data-tab="discover"><i class="fa-solid fa-compass"></i><span>Khám phá sách</span></button><button data-tab="queue"><i class="fa-solid fa-bookmark"></i><span>Giữ chỗ</span><b class="tab-badge" data-tab-badge="queue" hidden></b></button><button data-tab="notifications"><i class="fa-solid fa-bell"></i><span>Thông báo</span></button><button data-tab="fines"><i class="fa-solid fa-coins"></i><span>Tiền phạt</span><b class="tab-badge" data-tab-badge="fines" hidden></b></button>
      <?php if(in_array($rk,['admin','manager','employee'])):?><button data-tab="inventory"><i class="fa-solid fa-boxes-stacked"></i><span>Kho & kiểm kê</span><b class="tab-badge" data-tab-badge="inventory" hidden></b></button><button data-tab="staff"><i class="fa-solid fa-user-clock"></i><span>Nhân viên & ca</span><b class="tab-badge" data-tab-badge="staff" hidden></b></button><button data-tab="audit"><i class="fa-solid fa-clock-rotate-left"></i><span>Nhật ký</span></button><?php endif;?>
      <?php if($rk==='customer'):?><button data-tab="card"><i class="fa-solid fa-id-card"></i><span>Thẻ của tôi</span></button><button data-tab="profile"><i class="fa-solid fa-chart-simple"></i><span>Hồ sơ đọc</span></button><?php endif;?>
    </div>
  </div>

  <section class="smart-panel active smart-overview-panel" data-panel="overview">
    <div class="metric-grid smart-metric-grid" id="smartMetrics"></div>

    <div class="smart-analytics-strip" id="smartAnalyticsStrip" aria-label="Hoạt động hôm nay">
      <div class="smart-strip-placeholder">Đang tổng hợp hoạt động hôm nay...</div>
    </div>

    <div class="smart-overview-grid">
      <article class="smart-card smart-ranking-card">
        <div class="smart-card-title-row"><div><span class="smart-section-label">BẢNG XẾP HẠNG</span><h3>Top sách được mượn</h3></div><i class="fa-solid fa-ranking-star"></i></div>
        <div id="topBooks"></div>
      </article>
      <article class="smart-card smart-category-card">
        <div class="smart-card-title-row"><div><span class="smart-section-label">PHÂN TÍCH THỂ LOẠI</span><h3>Thể loại nổi bật</h3></div><i class="fa-solid fa-layer-group"></i></div>
        <div id="topCategories"></div>
      </article>
    </div>

    <div class="smart-ops-grid">
      <article class="smart-card smart-alert-card">
        <div class="smart-card-title-row"><div><span class="smart-section-label">OPERATIONS</span><h3>Trung tâm cảnh báo</h3></div><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div id="alertCenter"></div>
      </article>
      <article class="smart-card smart-activity-card">
        <div class="smart-card-title-row"><div><span class="smart-section-label">LIVE FEED</span><h3>Hoạt động gần đây</h3></div><span class="smart-live-mini"><i></i> LIVE</span></div>
        <div id="smartActivityFeed"><div class="smart-empty-state"><i class="fa-solid fa-clock-rotate-left"></i><span>Đang tải hoạt động...</span></div></div>
      </article>
    </div>
  </section>
  <section class="smart-panel" data-panel="scan"><div class="smart-grid two"><article class="smart-card"><h3>⚡ Mượn nhanh</h3><p class="muted">Quét/nhập <b>DG001</b> trước, sau đó quét các mã <b>TV-S001-01</b>.</p><div class="scan-row"><input id="borrowScan" placeholder="Quét hoặc nhập mã..."><button class="smart-btn primary" id="borrowAdd">Thêm mã</button></div><div id="borrowReader" class="selected-reader">Chưa chọn độc giả</div><div id="borrowCopies" class="chip-list"></div><button class="smart-btn success wide" id="confirmBorrow">Xác nhận mượn</button></article><article class="smart-card"><h3>↩️ Trả nhanh</h3><div class="scan-row"><input id="returnScan" placeholder="TV-S004-02"><button class="smart-btn" id="returnAdd">Thêm mã</button></div><div id="returnCopies" class="chip-list"></div><button class="smart-btn danger wide" id="confirmReturn">Xác nhận trả</button></article></div><article class="smart-card"><h3>Camera quét mã</h3><p class="muted">Thiết bị/trình duyệt hỗ trợ BarcodeDetector có thể dùng camera. Nếu không hỗ trợ, vẫn nhập mã thủ công bình thường.</p><button class="smart-btn" id="cameraScan"><i class="fa-solid fa-camera"></i> Mở camera</button><video id="scanVideo" playsinline hidden></video></article></section>
  <section class="smart-panel" data-panel="copies"><article class="smart-card"><div class="card-head"><div><h3>📚 Từng cuốn sách</h3><p class="muted">Mỗi bản có mã riêng và trạng thái riêng.</p></div><input id="copySearch" placeholder="Tìm mã/tên sách..."></div><div class="table-wrap"><table class="smart-table"><thead><tr><th>Mã</th><th>Sách</th><th>Vị trí</th><th>Trạng thái</th><th>QR</th></tr></thead><tbody id="copyTable"></tbody></table></div></article></section>
  <section class="smart-panel" data-panel="discover"><div class="smart-grid two"><article class="smart-card"><h3>🧠 Có thể bạn sẽ thích</h3><div id="recommendations" class="book-grid"></div></article><article class="smart-card"><h3>❤️ Muốn đọc</h3><div id="wishlist" class="book-grid compact"></div></article></div><article class="smart-card"><h3>📖 Xem chi tiết sách</h3><div class="scan-row"><input id="detailBookId" type="number" min="1" placeholder="Nhập mã sách"><button class="smart-btn" id="loadBookDetail">Xem chi tiết</button></div><div id="bookDetail"></div></article></section>
  <section class="smart-panel" data-panel="queue"><article class="smart-card"><h3>🪑 Hàng chờ giữ sách</h3><p class="muted">Người đầu hàng chờ có thể được giữ một bản trong 24 giờ khi sách sẵn sàng.</p><div id="queueList"></div></article></section>
  <section class="smart-panel" data-panel="notifications"><article class="smart-card"><h3>🔔 Trung tâm thông báo</h3><div id="notificationList"></div></article></section>
  <section class="smart-panel" data-panel="fines"><div class="metric-grid" id="fineMetrics"></div><article class="smart-card"><h3>💰 Lịch sử tiền phạt</h3><div class="table-wrap"><table class="smart-table"><thead><tr><th>Mã</th><th>Độc giả</th><th>Phiếu</th><th>Số tiền</th><th>Trạng thái</th><th>Xử lý</th></tr></thead><tbody id="fineTable"></tbody></table></div></article><article class="smart-card"><h3>🧾 Biên nhận mượn/trả</h3><div class="scan-row"><input id="receiptLoanId" type="number" min="1" placeholder="Mã phiếu mượn"><button class="smart-btn" id="loadReceipt">Xem biên nhận</button></div><div id="receiptBox"></div></article></section>
  <section class="smart-panel" data-panel="inventory"><div class="smart-grid two"><article class="smart-card"><h3>🧾 Phiếu nhập sách</h3><div class="form-grid"><select id="receiptSupplier"><option value="">Không chọn NCC</option></select><input id="receiptBookId" type="number" placeholder="Mã sách"><input id="receiptQty" type="number" min="1" value="1" placeholder="Số lượng"><input id="receiptPrice" type="number" min="0" value="0" placeholder="Đơn giá"><input id="receiptNote" placeholder="Ghi chú"><button class="smart-btn primary" id="receiptCreate">Tạo phiếu nhập</button></div></article><article class="smart-card"><h3>📦 Điều chỉnh kho</h3><div class="form-grid"><input id="stockBookId" type="number" placeholder="Mã sách"><select id="stockType"><option>Nhập</option><option>Mất</option><option>Hỏng</option><option>Thanh lý</option><option>Kiểm kê</option></select><input id="stockQty" type="number" value="1" min="1"><input id="stockNote" placeholder="Ghi chú"><button class="smart-btn primary" id="stockSave">Ghi nhận</button></div></article></div><article class="smart-card"><h3>🏢 Nhà cung cấp</h3><div class="form-grid"><input id="supplierName" placeholder="Tên nhà cung cấp"><input id="supplierPhone" placeholder="SĐT"><input id="supplierEmail" placeholder="Email"><input id="supplierAddress" placeholder="Địa chỉ"><button class="smart-btn" id="supplierSave">Thêm nhà cung cấp</button></div></article><article class="smart-card"><h3>Nhật ký tồn kho</h3><div id="stockLogs"></div></article></section>
  <section class="smart-panel" data-panel="staff"><div class="smart-grid two"><article class="smart-card"><h3>👥 Hồ sơ nhân viên</h3><div id="staffProfiles"></div></article><article class="smart-card"><h3>🗓️ Đăng ký ca 7 ngày</h3><div id="shiftBoard"></div></article></div><?php if(in_array($rk,['admin','manager'])):?><article class="smart-card"><h3>Yêu cầu đăng ký ca</h3><div id="shiftRequests"></div></article><?php endif;?></section>
  <section class="smart-panel" data-panel="audit"><article class="smart-card"><div class="card-head"><h3>📜 Nhật ký hoạt động</h3><input id="auditSearch" placeholder="Lọc người/hành động..."></div><div id="auditList"></div></article></section>
  <section class="smart-panel" data-panel="card"><article class="smart-card"><h3>📱 Thẻ thư viện điện tử</h3><div id="libraryCard"></div></article></section>
  <section class="smart-panel" data-panel="profile"><div class="metric-grid" id="readingMetrics"></div><article class="smart-card"><h3>🏆 Huy hiệu</h3><div id="badgeList" class="badge-list"></div></article></section>
</div>
<div class="smart-modal" id="smartModal"><div class="smart-modal-box"><button class="modal-close" id="smartModalClose">×</button><div id="smartModalBody"></div></div></div>
