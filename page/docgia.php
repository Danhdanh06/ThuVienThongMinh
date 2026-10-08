<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('docgia');
$canManageReaders = hasLibraryPermission('readers_manage');
$canDeleteReaders = hasLibraryPermission('readers_delete');
$isCustomerReader = currentLibraryRoleKey() === 'customer';
?>
<div class="readerx-page">
  <section class="readerx-hero">
    <div class="readerx-hero-copy">
      <span class="readerx-kicker"><i class="fa-solid fa-users-viewfinder"></i> Reader Management Dashboard</span>
      <h2><?= $isCustomerReader ? 'Hồ sơ độc giả của tôi' : 'Quản lý độc giả' ?></h2>
      <p><?= $isCustomerReader ? 'Theo dõi thông tin hồ sơ và hành trình đọc sách của bạn.' : 'Theo dõi hồ sơ độc giả, trạng thái hoạt động, lịch sử mượn và mức độ tương tác.' ?></p>
      <div class="readerx-actions">
        <?php if ($canManageReaders): ?><button class="readerx-btn primary" id="addReaderBtn" type="button"><i class="fa-solid fa-plus"></i> Thêm độc giả</button><?php endif; ?>
        <button class="readerx-btn" id="readerExportBtn" type="button"><i class="fa-solid fa-file-export"></i> Xuất danh sách</button>
        <button class="readerx-btn" id="readerRefreshBtn" type="button"><i class="fa-solid fa-rotate-right"></i> Làm mới</button>
      </div>
    </div>
    <div class="readerx-hero-visual" aria-hidden="true">
      <div class="readerx-hero-icon"><i class="fa-solid fa-user-group"></i></div><div class="readerx-blob b1"></div><div class="readerx-blob b2"></div>
      <div class="readerx-floating-card fc1"><strong id="readerHeroTotal">0</strong><span>Độc giả</span></div>
      <div class="readerx-floating-card fc2"><strong id="readerHeroActive">0</strong><span>Đang hoạt động</span></div>
      <div class="readerx-floating-card fc3"><strong id="readerHeroRecent">0</strong><span>Mới đăng ký</span></div>
    </div>
  </section>

  <section class="readerx-metrics">
    <article class="readerx-metric blue"><div><span>Tổng độc giả</span><strong id="readerMetricTotal">0</strong><small>Tất cả hồ sơ hiện có</small></div><i class="fa-solid fa-users"></i><div class="readerx-spark"><b></b><b></b><b></b><b></b></div></article>
    <article class="readerx-metric green"><div><span>Đang hoạt động</span><strong id="readerMetricActive">0</strong><small>Hồ sơ có thể sử dụng</small></div><i class="fa-solid fa-circle-check"></i><div class="readerx-spark"><b></b><b></b><b></b><b></b></div></article>
    <article class="readerx-metric orange"><div><span>Tạm khóa / ngừng</span><strong id="readerMetricInactive">0</strong><small>Cần kiểm tra trạng thái</small></div><i class="fa-solid fa-user-lock"></i><div class="readerx-spark"><b></b><b></b><b></b><b></b></div></article>
    <article class="readerx-metric purple"><div><span>Đăng ký gần đây</span><strong id="readerMetricRecent">0</strong><small>Trong 30 ngày gần nhất</small></div><i class="fa-solid fa-user-plus"></i><div class="readerx-spark"><b></b><b></b><b></b><b></b></div></article>
  </section>

  <section class="readerx-spotlight"><div class="readerx-section-head"><div><span>Gợi ý nhanh</span><h3>Độc giả nổi bật / cần chú ý</h3></div></div><div class="readerx-spotlight-grid" id="readerSpotlightGrid"></div></section>

  <section class="readerx-toolbar">
    <div class="readerx-toolbar-main">
      <label class="readerx-search"><i class="fa-solid fa-magnifying-glass"></i><input id="readerSearch" type="text" placeholder="Tìm tên, mã độc giả, Email, SĐT, địa chỉ..."></label>
      <select id="readerGenderFilter"><option value="">Tất cả giới tính</option><option>Nam</option><option>Nữ</option><option>Khác</option></select>
      <select id="readerStatusFilter"><option value="">Tất cả trạng thái</option><option>Đang hoạt động</option><option>Ngừng hoạt động</option><option>Khóa</option></select>
      <input id="readerDateFilter" type="date" title="Ngày đăng ký">
      <select id="readerSort"><option value="newest">Mới nhất</option><option value="az">Tên A → Z</option><option value="borrowed">Mượn nhiều</option></select>
    </div>
    <div class="readerx-toolbar-bottom"><div class="readerx-chips" id="readerFilterChips"><button class="active" data-reader-chip="all">Tất cả</button><button data-reader-chip="active">Đang hoạt động</button><button data-reader-chip="inactive">Không hoạt động</button><button data-reader-chip="top">Mượn nhiều</button><button data-reader-chip="recent">Mới đăng ký</button></div><div class="readerx-view-switch"><button class="active" data-reader-view="table"><i class="fa-solid fa-table-list"></i> Bảng</button><button data-reader-view="card"><i class="fa-solid fa-grip"></i> Card</button></div></div>
  </section>

  <section class="readerx-main-card">
    <div class="readerx-table-wrap" id="readerTableView"><table class="readerx-table"><thead><tr><th>STT</th><th>Độc giả</th><th>Giới tính</th><th>Ngày sinh</th><th>Liên hệ</th><th>Địa chỉ</th><th>Ngày đăng ký</th><th>Trạng thái</th><?php if ($canManageReaders): ?><th>Thao tác</th><?php endif; ?></tr></thead><tbody id="readerTableBody"></tbody></table></div>
    <div class="readerx-card-grid d-none" id="readerCardGrid"></div><div class="readerx-result" id="readerResultText"></div>
  </section>

  <section class="readerx-mini-stats">
    <article><div class="readerx-donut" id="readerGenderDonut"><span><strong id="readerGenderMain">0</strong><small>độc giả</small></span></div><div><h4>Tỉ lệ giới tính</h4><div id="readerGenderLegend"></div></div></article>
    <article><h4>Khu vực có nhiều độc giả</h4><div class="readerx-bar-list" id="readerAreaStats"></div></article>
    <article><h4>Đăng ký theo tháng</h4><div class="readerx-month-bars" id="readerMonthStats"></div></article>
  </section>
</div>

<?php if ($canManageReaders): ?>
<div class="readerx-form-modal" id="readerFormModal" aria-hidden="true"><div class="readerx-form-backdrop" data-close-reader-form></div><div class="readerx-form-panel" id="readerFormBox">
  <div class="readerx-form-head"><div><span>Hồ sơ độc giả</span><h3 id="readerFormTitle">Thêm độc giả</h3></div><button type="button" data-close-reader-form><i class="fa-solid fa-xmark"></i></button></div>
  <p class="readerx-form-note">Hồ sơ độc giả và tài khoản đăng nhập được quản lý riêng. Dữ liệu cũ vẫn được giữ nguyên.</p>
  <div class="readerx-form-grid">
    <div><label>Mã độc giả</label><input id="readerId" type="text" readonly placeholder="Tự động"></div><div><label>Họ tên *</label><input id="readerName" type="text" required></div>
    <div><label>Giới tính</label><select id="readerGender"><option>Nam</option><option>Nữ</option><option>Khác</option></select></div><div><label>Ngày sinh</label><input id="readerBirthDate" type="date"></div>
    <div><label>Số điện thoại</label><input id="readerPhone" type="text"></div><div><label>Email</label><input id="readerEmail" type="email"></div>
    <div class="span-2"><label>Địa chỉ</label><input id="readerAddress" type="text"></div><div><label>Ngày đăng ký</label><input id="readerRegisterDate" type="date"></div>
    <div><label>Trạng thái</label><select id="readerStatus"><option>Đang hoạt động</option><option>Ngừng hoạt động</option><option>Khóa</option></select></div>
  </div>
  <div class="readerx-form-actions"><button class="readerx-btn success" id="readerSaveBtn" type="button"><i class="fa fa-save"></i> Lưu</button><button class="readerx-btn primary" id="readerUpdateBtn" type="button"><i class="fa fa-pen"></i> Cập nhật</button><button class="readerx-btn" id="readerCardBtn" type="button" disabled><i class="fa-solid fa-id-card"></i> Thẻ QR</button><button class="readerx-btn" id="readerProfileBtn" type="button" disabled><i class="fa-solid fa-chart-line"></i> Hồ sơ đọc</button><?php if ($canDeleteReaders): ?><button class="readerx-btn danger" id="readerDeleteBtn" type="button"><i class="fa fa-trash"></i> Xóa</button><?php endif; ?><button class="readerx-btn" id="readerResetBtn" type="button"><i class="fa fa-rotate"></i> Làm mới</button></div>
</div></div>
<?php endif; ?>
<div class="reader-card-modal" id="readerCardModal" aria-hidden="true">
  <div class="reader-card-dialog">
    <div class="reader-card-modal-head">
      <div>
        <h3><i class="fa-solid fa-id-card"></i> THẺ THƯ VIỆN ĐIỆN TỬ</h3>
        <p>Quét QR để nhận diện nhanh độc giả khi mượn/trả sách.</p>
      </div>
      <button type="button" class="reader-card-close" id="readerCardClose" aria-label="Đóng">×</button>
    </div>
    <div id="readerCardContent" class="reader-card-content"><div class="reader-card-loading">Đang tải thẻ...</div></div>
    <div class="reader-card-actions">
      <button type="button" class="reader-card-btn secondary" id="readerCardZoom"><i class="fa-solid fa-magnifying-glass-plus"></i> Phóng to QR</button>
      <button type="button" class="reader-card-btn primary" id="readerCardPrint"><i class="fa-solid fa-print"></i> In / Lưu PDF</button>
    </div>
  </div>
</div>

<?php if ($isCustomerReader): ?>
<section class="reader-self-profile"><h3><i class="fa-solid fa-chart-line"></i> HỒ SƠ ĐỌC SÁCH CỦA TÔI</h3><div id="readerSelfProfile">Đang tải hồ sơ đọc...</div></section>
<?php endif; ?>

<div class="reader-card-modal" id="readerProfileModal" aria-hidden="true"><div class="reader-card-dialog"><div class="reader-card-modal-head"><div><h3><i class="fa-solid fa-chart-line"></i> HỒ SƠ ĐỌC SÁCH</h3><p>Thống kê được tính từ lịch sử mượn hiện có.</p></div><button type="button" class="reader-card-close" id="readerProfileClose">×</button></div><div id="readerProfileContent" class="reader-profile-content">Đang tải...</div></div></div>

