<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('sach');
$canManageBooks = hasLibraryPermission('books_manage');
$canReserveSelf = hasLibraryPermission('reservations_self');
$isGuestVisitor = currentLibraryRoleKey() === 'guest';
$isReaderCustomer = currentLibraryRoleKey() === 'customer';
?>
<div class="app">
  <main class="main">
    <section class="content">
      <?php if ($isGuestVisitor): ?>
      <div class="alert alert-primary d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" role="alert" style="border-radius:16px;border-color:#cfe0ff;background:#f4f8ff;color:#334155">
        <div><strong><i class="fa-solid fa-circle-info me-2 text-primary"></i>Bạn đang xem sách ở chế độ tham quan.</strong><br><span style="font-size:13px">Muốn mượn hoặc đặt lịch mượn sách, vui lòng đăng nhập hoặc đăng ký tài khoản Khách hàng.</span></div>
        <div class="d-flex gap-2"><a href="dangnhap.php" class="btn btn-outline-primary btn-sm">Đăng nhập</a><a href="dangky.php" class="btn btn-primary btn-sm">Đăng ký</a></div>
      </div>
      <?php endif; ?>
      <?php if (!$isGuestVisitor && !$isReaderCustomer): ?>
      <section class="book-management-hero book-motion-reveal">
        <div class="book-hero-copy">
          <span class="book-hero-kicker"><i class="fa-solid fa-book-open"></i> BOOK MANAGEMENT</span>
          <h1>Quản lý kho sách</h1>
          <p>Theo dõi đầu sách, số lượng, trạng thái tồn kho và thao tác nhanh trong một không gian trực quan hơn.</p>
          <div class="book-hero-pulse"><span class="pulse-dot"></span><span>Dữ liệu đang đồng bộ với kho thư viện hiện tại</span></div>
        </div>
        <div class="book-hero-actions">
          <?php if ($canManageBooks): ?><button class="hero-action primary" id="heroAddBookBtn" type="button"><i class="fa-solid fa-plus"></i><span>Thêm sách</span></button><?php endif; ?>
          <button class="hero-action" id="heroRefreshBtn" type="button"><i class="fa-solid fa-rotate"></i><span>Làm mới</span></button>
          <button class="hero-action" id="exportBooksBtn" type="button"><i class="fa-solid fa-file-arrow-down"></i><span>Xuất CSV</span></button>
          <button class="hero-action" id="toggleAdvancedFilterBtn" type="button"><i class="fa-solid fa-sliders"></i><span>Lọc nâng cao</span></button>
          <?php if ($canManageBooks): ?><button class="hero-action" id="openInventoryBtn" type="button"><i class="fa-solid fa-boxes-stacked"></i><span>Kho & kiểm kê</span></button><?php endif; ?>
        </div>
        <div class="book-hero-orb orb-a"></div><div class="book-hero-orb orb-b"></div>
      </section>

      <section class="book-overview-grid book-motion-reveal" id="bookOverviewGrid">
        <article class="book-overview-card tone-blue"><div><span>Đầu sách</span><strong id="overviewTitles">0</strong><small>Danh mục hiện có</small></div><i class="fa-solid fa-book-open"></i></article>
        <article class="book-overview-card tone-indigo"><div><span>Tổng số cuốn</span><strong id="overviewCopies">0</strong><small>Tổng tồn kho</small></div><i class="fa-solid fa-book-open"></i></article>
        <article class="book-overview-card tone-green"><div><span>Còn sách</span><strong id="overviewAvailable">0</strong><small>Có thể phục vụ</small></div><i class="fa-solid fa-circle-check"></i></article>
        <article class="book-overview-card tone-orange"><div><span>Cần chú ý</span><strong id="overviewAttention">0</strong><small>Sắp hết hoặc hết</small></div><i class="fa-solid fa-triangle-exclamation"></i></article>
      </section>
      <?php endif; ?>
      <?php if ($isReaderCustomer): ?>
      <section class="reader-catalog-hero book-motion-reveal">
        <div class="reader-catalog-copy"><span><i class="fa-solid fa-compass"></i> KHÁM PHÁ KHO SÁCH</span><h1>Tìm cuốn sách hợp với bạn</h1><p>Tìm theo tên, tác giả hoặc thể loại; xem chi tiết, lưu Muốn đọc và đặt lịch mượn ngay từ một nơi.</p><div class="reader-catalog-actions"><button type="button" onclick="openSmartTab('discover','Thư viện thông minh')"><i class="fa-regular fa-heart"></i> Muốn đọc</button><button type="button" onclick="loadPage('muontra.php','Mượn sách')"><i class="fa-solid fa-book-open-reader"></i> Mượn sách</button><button type="button" onclick="loadPage('trangchu.php','Trang chủ')"><i class="fa-solid fa-wand-magic-sparkles"></i> Gợi ý cho tôi</button></div></div>
        <div class="reader-catalog-art" aria-hidden="true"><i class="fa-solid fa-book-open"></i><span class="art-book a1"></span><span class="art-book a2"></span><span class="art-book a3"></span></div>
      </section>
      <?php endif; ?>

      <section class="book-toolbar-shell book-motion-reveal" id="bookToolbarShell">
      <div class="toolbar">
        <div class="field search-field">
          <div class="input-wrap">
            <span class="search-symbol">⌕</span>
            <input id="searchInput" type="text" placeholder="Tìm kiếm sách (mã, tên sách, tác giả, nhà xuất bản...)" />
          </div>
        </div>
        <div class="field">
          <label for="categoryFilter">Thể loại</label>
          <select id="categoryFilter"><option value="">Tất cả</option></select>
        </div>
        <div class="field">
          <label for="statusFilter">Trạng thái</label>
          <select id="statusFilter">
            <option value="">Tất cả</option>
            <option value="available">Còn sách</option>
            <option value="unavailable">Hết sách</option>
          </select>
        </div>
        <div class="field">
          <label for="sortSelect">Sắp xếp</label>
          <select id="sortSelect">
            <option value="default">Mới thêm</option>
            <option value="title-asc">Tên A → Z</option>
            <option value="title-desc">Tên Z → A</option>
            <option value="year-desc">Năm mới nhất</option>
            <option value="year-asc">Năm cũ nhất</option>
            <option value="quantity-desc">Số lượng giảm dần</option>
            <option value="quantity-asc">Số lượng tăng dần</option>
          </select>
        </div>
        <?php if ($canManageBooks): ?>
        <div class="add-wrap">
          <button class="primary-btn" id="addBookBtn" type="button"><span style="font-size:22px">＋</span>Thêm sách</button>
        </div>
        <?php endif; ?>
      </div>
      <div class="book-filter-extras" id="bookFilterExtras">
        <div class="quick-filter-row" id="quickFilterRow">
          <button type="button" class="quick-filter active" data-quick-filter="all"><i class="fa-solid fa-border-all"></i>Tất cả</button>
          <button type="button" class="quick-filter" data-quick-filter="healthy"><i class="fa-solid fa-circle-check"></i>Còn nhiều</button>
          <button type="button" class="quick-filter" data-quick-filter="low"><i class="fa-solid fa-battery-quarter"></i>Sắp hết</button>
          <button type="button" class="quick-filter" data-quick-filter="empty"><i class="fa-solid fa-ban"></i>Hết sách</button>
          <button type="button" class="quick-filter" data-quick-filter="new"><i class="fa-solid fa-sparkles"></i>Mới thêm</button>
          <button type="button" class="quick-filter" data-quick-filter="popular"><i class="fa-solid fa-fire"></i>Mượn nhiều</button>
        </div>
        <div class="filter-extra-actions">
          <button type="button" class="soft-btn" id="clearBookFiltersBtn"><i class="fa-solid fa-eraser"></i>Xóa lọc</button>
        </div>
      </div>
      </section>

      <?php if (!$isGuestVisitor && !$isReaderCustomer): ?>
      <section class="book-attention-section book-motion-reveal">
        <div class="book-section-head"><div><span>THEO DÕI NHANH</span><h2>Sách cần chú ý</h2><p>Dùng dữ liệu hiện có để phát hiện sách sắp hết, được mượn nhiều và mới thêm.</p></div></div>
        <div class="book-attention-grid" id="bookAttentionGrid"><div class="attention-skeleton">Đang tổng hợp...</div></div>
      </section>
      <?php endif; ?>

      <section class="book-list-zone book-motion-reveal">
        <div class="book-list-head">
          <div><span>DANH SÁCH KHO</span><h2>Tất cả sách</h2></div>
          <div class="book-list-tools">
            <label class="page-size-control">Hiển thị<select id="pageSizeSelect"><option value="10">10</option><option value="20">20</option><option value="50">50</option></select></label>
            <div class="view-switch" role="group" aria-label="Chế độ xem"><button type="button" id="tableViewBtn" class="active" title="Xem bảng"><i class="fa-solid fa-table-list"></i></button><button type="button" id="cardViewBtn" title="Xem thẻ"><i class="fa-solid fa-grip"></i></button></div>
          </div>
        </div>
        <?php if ($canManageBooks): ?><div class="bulk-action-bar" id="bulkActionBar" hidden><div><strong id="selectedBookCount">0</strong> sách đã chọn</div><div><button type="button" class="soft-btn" id="exportSelectedBooksBtn"><i class="fa-solid fa-file-export"></i>Xuất đã chọn</button><?php if (hasLibraryPermission('books_delete')): ?><button type="button" class="soft-btn danger-soft" id="deleteSelectedBooksBtn"><i class="fa-solid fa-trash"></i>Xóa đã chọn</button><?php endif; ?><button type="button" class="soft-btn" id="clearSelectedBooksBtn">Bỏ chọn</button></div></div><?php endif; ?>

      <div class="table-card">
        <div class="table-scroll">
          <table id="bookTable">
            <thead>
              <tr>
                <?php if ($canManageBooks): ?><th class="select-col"><input type="checkbox" id="selectAllBooks" aria-label="Chọn tất cả sách trang này"></th><?php endif; ?>
                <th>STT</th><th>Ảnh bìa</th><th>Mã sách</th><th>Tên sách</th><th>Tác giả</th>
                <th>Thể loại</th><th>Năm XB</th><th>Số lượng</th><th>Trạng thái</th><th>Thao tác</th>
              </tr>
            </thead>
            <tbody id="bookTableBody"></tbody>
          </table>
          <div class="empty" id="emptyState" hidden>Không tìm thấy sách phù hợp.</div>
        </div>
        <div class="table-footer"><div id="summaryText"></div><div class="pagination" id="pagination"></div></div>
      </div>
      <div class="book-card-view" id="bookCardView" hidden></div>
      </section>
    </section>
  </main>
</div>

<div class="modal" id="bookModal">
  <div class="modal-box">
    <div class="modal-head">
      <h2 id="modalTitle">Thêm sách</h2>
      <button class="icon-btn" id="closeModalBtn" type="button">×</button>
    </div>
    <form id="bookForm">
      <div class="modal-body">
        <div class="field full"><label for="bookTitle">Tên sách *</label><input id="bookTitle" required maxlength="200"></div>
        <div class="field"><label for="bookAuthor">Tác giả</label><input id="bookAuthor" maxlength="150"></div>
        <div class="field"><label for="bookCategory">Thể loại</label><select id="bookCategory"><option value="">Chưa phân loại</option></select></div>
        <div class="field"><label for="bookPublisher">Nhà xuất bản</label><input id="bookPublisher" maxlength="150"></div>
        <div class="field"><label for="bookYear">Năm xuất bản</label><input id="bookYear" type="number" min="1000" max="<?= date('Y') ?>"></div>
        <div class="field"><label for="bookQuantity">Số lượng *</label><input id="bookQuantity" type="number" required min="0" max="99999" value="1"></div>
        <div class="field full"><label for="bookCover">URL ảnh bìa</label><input id="bookCover" maxlength="255" placeholder="Có thể để trống"></div>
        <div class="field full"><label for="bookDescription">Mô tả</label><textarea id="bookDescription" rows="3" placeholder="Mô tả ngắn về sách"></textarea></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="secondary-btn" id="cancelBtn">Hủy</button>
        <button type="submit" class="primary-btn">Lưu sách</button>
      </div>
    </form>
  </div>
</div>


<?php if ($canReserveSelf): ?>
<div class="modal" id="reservationModal">
  <div class="modal-box">
    <div class="modal-head"><h2>Đặt lịch mượn sách</h2><button class="icon-btn" id="closeReservationModalBtn" type="button">×</button></div>
    <form id="reservationForm">
      <div class="modal-body">
        <div class="field full"><label for="reservationBookId">Sách *</label><select id="reservationBookId" required><option value="">-- Chọn sách --</option></select></div>
        <div class="field"><label for="reservationDate">Ngày dự kiến đến mượn *</label><input id="reservationDate" type="date" required></div>
        <div class="field"><label for="reservationTime">Giờ dự kiến</label><input id="reservationTime" type="time"></div>
        <div class="field"><label for="reservationQuantity">Số lượng *</label><input id="reservationQuantity" type="number" min="1" max="10" value="1" required></div>
        <div class="field full"><label for="reservationNote">Ghi chú</label><textarea id="reservationNote" rows="3" placeholder="Ví dụ: Tôi sẽ đến nhận sách vào buổi sáng"></textarea></div>
      </div>
      <div class="modal-foot"><button type="button" class="secondary-btn" id="cancelReservationBtn">Hủy</button><button type="submit" class="primary-btn">Gửi lịch đặt</button></div>
    </form>
  </div>
</div>
<?php endif; ?>


<?php if ($canManageBooks): ?>
<div class="modal" id="copyQrModal">
  <div class="modal-box copy-qr-modal-box">
    <div class="modal-head">
      <div><h2><i class="fa-solid fa-qrcode"></i> Mã từng cuốn sách</h2><p id="copyQrSubtitle" style="margin:4px 0 0;color:#64748b;font-size:13px"></p></div>
      <button class="icon-btn" id="closeCopyQrModalBtn" type="button">×</button>
    </div>
    <div class="copy-qr-tools"><input id="copyQrSearch" placeholder="Tìm mã cuốn..."><button class="secondary-btn" id="printAllCopyQr" type="button"><i class="fa-solid fa-print"></i> In danh sách mã</button></div>
    <div id="copyQrList" class="copy-qr-list"></div>
  </div>
</div>
<?php endif; ?>

<div class="modal" id="bookDetailModal">
  <div class="modal-box book-detail-modal-box">
    <div class="modal-head"><h2><i class="fa-solid fa-book-open"></i> Chi tiết sách</h2><button class="icon-btn" id="closeBookDetailBtn" type="button">×</button></div>
    <div id="bookDetailContent" class="book-detail-content"><div style="padding:30px;text-align:center">Đang tải...</div></div>
  </div>
</div>

<div class="confirm" id="confirmDialog">
  <div class="confirm-box">
    <h3>Xóa sách</h3><p id="confirmText">Bạn có chắc muốn xóa sách này?</p>
    <div class="confirm-actions">
      <button class="secondary-btn" id="cancelDeleteBtn" type="button">Hủy</button>
      <button class="danger-btn" id="confirmDeleteBtn" type="button">Xóa</button>
    </div>
  </div>
</div>
<div class="toast" id="toast"></div>
