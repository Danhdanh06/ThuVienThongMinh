<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('muontra');
$roleKey = currentLibraryRoleKey();
$isCustomer = $roleKey === 'customer';
$canManageLoans = hasLibraryPermission('loans_manage');
$canManageReservations = hasLibraryPermission('reservations_manage');
$canBorrowSelf = hasLibraryPermission('borrow_self');
$canReserveSelf = hasLibraryPermission('reservations_self');
$canManageRenewals = hasLibraryPermission('renewals_manage');
$canRenewSelf = hasLibraryPermission('renewals_self');
?>
<main class="main">
  <section class="content">
    <section class="loanx-hero">
      <div class="loanx-hero-copy"><span class="loanx-kicker"><i class="fa-solid fa-arrows-rotate"></i> Borrow & Return Dashboard</span><h1><?= $isCustomer ? 'Mượn sách của tôi' : 'Quản lý mượn – trả' ?></h1><p class="loan-subtitle"><?= $isCustomer ? 'Gửi yêu cầu mượn, theo dõi phiếu của bạn và lịch đặt trước.' : 'Theo dõi phiếu mượn, lịch đặt trước, yêu cầu gia hạn và trạng thái xử lý theo thời gian thực.' ?></p>
        <div class="loanx-hero-actions"><?php if ($canBorrowSelf || $canManageLoans): ?><button class="primary-btn" id="addLoanBtn" type="button"><i class="fa-solid fa-plus"></i><?= $isCustomer ? 'Gửi yêu cầu mượn' : 'Tạo phiếu mượn' ?></button><?php endif; ?><?php if (!$isCustomer && in_array($roleKey,['admin','manager'],true)): ?><button class="secondary-btn" id="quickReturnHeroBtn" type="button"><i class="fa-solid fa-barcode"></i>Trả sách nhanh</button><?php endif; ?><button class="secondary-btn" id="loanExportBtn" type="button"><i class="fa-solid fa-file-export"></i>Xuất danh sách</button><button class="secondary-btn" id="loanRefreshBtn" type="button"><i class="fa-solid fa-rotate-right"></i>Làm mới</button></div>
      </div>
      <div class="loanx-hero-visual" aria-hidden="true"><div class="loanx-orb o1"></div><div class="loanx-orb o2"></div><div class="loanx-circle"><strong id="loanHeroRate">0%</strong><span>Đúng hạn</span></div><div class="loanx-mini m1"><b id="loanHeroToday">0</b><span>Phiếu hôm nay</span></div><div class="loanx-mini m2"><b id="loanHeroAlerts">0</b><span>Cần xử lý</span></div></div>
    </section>

    <div class="stats">
      <div class="stat-card"><div class="stat-icon blue">⇄</div><div><div class="stat-title" id="borrowingLabel">Đang mượn</div><div class="stat-number" id="borrowingCount">0</div></div></div>
      <div class="stat-card"><div class="stat-icon green">✓</div><div><div class="stat-title" id="returnedLabel">Đã trả</div><div class="stat-number" id="returnedCount">0</div></div></div>
      <div class="stat-card"><div class="stat-icon yellow">◷</div><div><div class="stat-title" id="dueSoonLabel">Sắp đến hạn</div><div class="stat-number" id="dueSoonCount">0</div></div></div>
      <div class="stat-card"><div class="stat-icon red">!</div><div><div class="stat-title" id="overdueLabel">Quá hạn</div><div class="stat-number" id="overdueCount">0</div></div></div>
    </div>

    <div class="loan-tabs">
      <button class="loan-tab active" id="loanTabBtn" type="button"><i class="fa-solid fa-receipt"></i> <?= $isCustomer ? 'Phiếu của tôi' : 'Phiếu mượn' ?></button>
      <button class="loan-tab" id="reservationTabBtn" type="button"><i class="fa-solid fa-calendar-check"></i> <?= $isCustomer ? 'Lịch đặt của tôi' : 'Lịch đặt trước' ?></button>
      <?php if ($canRenewSelf || $canManageRenewals): ?><button class="loan-tab" id="renewalTabBtn" type="button"><i class="fa-solid fa-clock-rotate-left"></i> <?= $isCustomer ? 'Gia hạn của tôi' : 'Yêu cầu gia hạn' ?></button><?php endif; ?>
    </div>


    <section class="loanx-ops-grid" id="loanOpsGrid">
      <article class="loanx-alert-center"><div class="loanx-section-head"><div><span>Cảnh báo</span><h3>Trung tâm cảnh báo</h3></div></div><div id="loanAlertCards" class="loanx-alert-grid"></div></article>
      <article class="loanx-activity"><div class="loanx-section-head"><div><span>Hoạt động</span><h3>Phiếu gần đây</h3></div></div><div id="loanActivityFeed" class="loanx-activity-feed"></div></article>
      <article class="loanx-chart-card"><div class="loanx-section-head"><div><span>7 ngày gần nhất</span><h3>Mượn / trả / quá hạn</h3></div></div><div class="loanx-chart" id="loanSevenDayChart"></div><div class="loanx-chart-legend"><span><b class="borrow"></b>Mượn</span><span><b class="returned"></b>Trả</span><span><b class="overdue"></b>Quá hạn</span></div></article>
    </section>

    <div id="loanPanel" class="tab-panel active">
      <div class="toolbar">
        <div class="field"><div class="input-wrap"><span class="search-icon">⌕</span><input id="searchInput" type="text" placeholder="Tìm mã phiếu, sách..."></div></div>
        <div class="field"><label for="statusFilter">Trạng thái</label><select id="statusFilter"><option value="">Tất cả</option><option value="pending">Chờ duyệt</option><option value="borrowing">Đang mượn</option><option value="due-soon">Sắp đến hạn</option><option value="overdue">Quá hạn</option><option value="returned">Đã trả</option></select></div>
        <div class="field"><label for="dateFilter">Ngày mượn</label><input id="dateFilter" type="date"></div>
        <div class="field"><label for="sortSelect">Sắp xếp</label><select id="sortSelect"><option value="newest">Mới nhất</option><option value="oldest">Cũ nhất</option><option value="due-asc">Hạn trả gần nhất</option><option value="reader-asc">Tên độc giả A → Z</option></select></div>
      </div>
      <div class="loanx-filter-bottom"><div class="loanx-chips" id="loanQuickChips"><button class="active" data-loan-chip="all">Tất cả</button><button data-loan-chip="borrowing">Đang mượn</button><button data-loan-chip="returned">Đã trả</button><button data-loan-chip="due-soon">Sắp đến hạn</button><button data-loan-chip="overdue">Quá hạn</button><button data-loan-chip="pending">Cần xử lý</button></div><div class="loanx-view-tools"><label>Hiển thị <select id="loanPageSize"><option>10</option><option>20</option><option>50</option></select></label><div class="loanx-view-switch"><button class="active" data-loan-view="table"><i class="fa-solid fa-table-list"></i></button><button data-loan-view="card"><i class="fa-solid fa-grip"></i></button></div></div></div>

      <div class="table-card" id="loanTableCard">
        <div class="table-scroll">
          <table id="loanTable"><thead id="loanTableHead"></thead><tbody id="loanTableBody"></tbody></table>
          <div class="empty" id="emptyState" hidden>Không tìm thấy phiếu mượn phù hợp.</div>
        </div>
        <div class="table-footer"><div id="summaryText"></div><div class="pagination" id="pagination"></div></div>
      </div>
      <div class="loanx-card-grid d-none" id="loanCardGrid"></div>
    </div>

    <div id="reservationPanel" class="tab-panel">
      <div class="reservation-toolbar"><div><h3><?= $isCustomer ? 'Lịch đặt mượn của tôi' : 'Lịch đặt trước của độc giả' ?></h3><p><?= $isCustomer ? 'Đặt lịch từ trang Sách và theo dõi trạng thái tại đây.' : 'Nhân viên xác nhận, hủy hoặc giao sách để chuyển thành phiếu mượn.' ?></p></div></div>
      <div class="table-card"><div class="table-scroll"><table id="reservationTable"><thead id="reservationTableHead"></thead><tbody id="reservationTableBody"></tbody></table><div class="empty" id="reservationEmpty" hidden>Chưa có lịch đặt.</div></div></div>
    </div>

    <?php if ($canRenewSelf || $canManageRenewals): ?>
    <div id="renewalPanel" class="tab-panel">
      <div class="reservation-toolbar"><div><h3><?= $isCustomer ? 'Yêu cầu gia hạn của tôi' : 'Yêu cầu gia hạn của độc giả' ?></h3>
        <p><?= $isCustomer ? 'Gia hạn chỉ có hiệu lực sau khi nhân viên duyệt.' : 'Kiểm tra tình trạng quá hạn và lịch đặt trước khi duyệt.' ?></p></div></div>
      <div class="table-card"><div class="table-scroll">
        <table id="renewalTable"><thead id="renewalTableHead"></thead><tbody id="renewalTableBody"></tbody></table>
        <div class="empty" id="renewalEmpty" hidden>Chưa có yêu cầu gia hạn.</div>
      </div></div>
    </div>
    <?php endif; ?>
  </section>
</main>

<?php if ($canBorrowSelf || $canManageLoans): ?>
<div class="modal" id="loanModal">
  <div class="modal-box">
    <div class="modal-head"><h2 id="loanModalTitle"><?= $isCustomer ? 'Gửi yêu cầu mượn sách' : 'Tạo phiếu mượn' ?></h2><button class="icon-btn" id="closeLoanModalBtn" type="button">×</button></div>
    <form id="loanForm">
      <div class="modal-body">
        <?php if ($isCustomer): ?>
          <div class="field"><label for="readerName">Tên độc giả</label><input id="readerName" type="text" maxlength="100" readonly></div>
          <div class="field"><label for="readerPhone">Số điện thoại</label><input id="readerPhone" type="tel" maxlength="15" readonly></div>
          <select id="readerId" hidden></select>
        <?php else: ?>
          <div class="field full"><label for="readerId">Độc giả *</label><select id="readerId" required><option value="">-- Chọn độc giả --</option></select></div>
          <div class="field"><label for="readerName">Tên độc giả</label><input id="readerName" readonly></div>
          <div class="field"><label for="readerPhone">Số điện thoại</label><input id="readerPhone" readonly></div>
        <?php endif; ?>
        <div class="field full"><label for="bookId">Sách *</label><select id="bookId" required><option value="">-- Chọn sách --</option></select></div>
        <div class="field full"><label for="bookTitle">Tên sách</label><input id="bookTitle" readonly></div>
        <div class="field"><label for="borrowDate"><?= $isCustomer ? 'Ngày gửi yêu cầu (tự động)' : 'Ngày mượn' ?> *</label><input id="borrowDate" type="date" required <?= $isCustomer ? 'readonly' : '' ?>></div>
        <div class="field"><label for="dueDate">Hạn trả dự kiến<?= $isCustomer ? ' (tự động)' : '' ?> *</label><input id="dueDate" type="date" required <?= $isCustomer ? 'readonly' : '' ?>></div>
        <div class="field"><label for="quantity">Số lượng *</label><input id="quantity" type="number" min="1" max="20" value="1" required></div>
        <?php if (!$isCustomer): ?><div class="field"><label for="staffId">Nhân viên lập phiếu</label><select id="staffId"><option value="">-- Tự động / không chọn --</option></select></div><?php else: ?><select id="staffId" hidden></select><?php endif; ?>
        <div class="field full"><label for="note">Ghi chú</label><textarea id="note" placeholder="Nhập ghi chú nếu có..."></textarea></div>
      </div>
      <div class="modal-foot"><button type="button" class="secondary-btn" id="cancelLoanBtn">Hủy</button><button type="submit" class="primary-btn"><?= $isCustomer ? 'Gửi yêu cầu' : 'Lưu phiếu mượn' ?></button></div>
    </form>
  </div>
</div>
<?php endif; ?>


<?php if ($canRenewSelf): ?>
<div class="modal" id="renewalModal">
  <div class="modal-box">
    <div class="modal-head"><h2>Yêu cầu gia hạn</h2><button class="icon-btn" id="closeRenewalModalBtn" type="button">×</button></div>
    <form id="renewalForm">
      <div class="modal-body">
        <input id="renewalLoanId" type="hidden">
        <div class="field full"><label>Phiếu mượn</label><input id="renewalLoanInfo" type="text" readonly></div>
        <div class="field"><label for="renewalDays">Số ngày muốn gia hạn *</label><input id="renewalDays" type="number" min="1" max="30" value="7" required></div>
        <div class="field"><label>Hạn trả hiện tại</label><input id="renewalOldDue" type="date" readonly></div>
        <div class="field full"><div style="padding:12px;border-radius:10px;background:#fff7ed;color:#9a3412;font-size:13px;">
          Quy định: tối đa 2 lần/phiếu; từ 3 sách quá hạn trở lên hoặc có sách quá hạn trên 30 ngày sẽ bị chặn; sách có người khác đặt lịch cũng không được gia hạn.
        </div></div>
      </div>
      <div class="modal-foot"><button type="button" class="secondary-btn" id="cancelRenewalBtn">Hủy</button><button type="submit" class="primary-btn">Gửi yêu cầu</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="modal" id="detailModal"><div class="modal-box"><div class="modal-head"><h2>Chi tiết phiếu mượn</h2><button class="icon-btn" id="closeDetailBtn" type="button">×</button></div><div class="modal-body"><div class="detail-box full" id="detailContent"></div></div><div class="modal-foot"><button class="secondary-btn" id="closeDetailFooterBtn" type="button">Đóng</button></div></div></div>
<div class="confirm" id="confirmDialog"><div class="confirm-box"><h3 id="confirmTitle">Xác nhận</h3><p id="confirmText"></p><div class="confirm-actions"><button class="secondary-btn" id="cancelConfirmBtn" type="button">Hủy</button><button class="danger-btn" id="confirmActionBtn" type="button">Xác nhận</button></div></div></div>
<div class="toast" id="toast"></div>
