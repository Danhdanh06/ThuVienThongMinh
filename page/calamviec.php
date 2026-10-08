<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('calamviec');
$canManageShifts = hasLibraryPermission('shifts_manage');
$selfOnly = hasLibraryPermission('shifts_self_view') && !hasLibraryPermission('shifts_view');
?>
<div class="shift-page <?= $selfOnly ? 'shift-employee-mode' : 'shift-manager-mode' ?>">
  <section class="shift-dashboard-hero">
    <div class="shift-hero-copy">
      <span class="shift-kicker"><i class="fa-solid <?= $selfOnly ? 'fa-user-clock' : 'fa-calendar-check' ?>"></i> <?= $selfOnly ? 'My Shift Dashboard' : 'Shift Management Dashboard' ?></span>
      <h2><?= $selfOnly ? 'Ca làm của tôi' : 'Quản lý ca làm việc' ?></h2>
      <p><?= $selfOnly ? 'Theo dõi lịch làm cá nhân, ca hôm nay và đăng ký những ca còn trống.' : 'Theo dõi lịch trực, phân công nhân viên, duyệt đăng ký ca và giám sát tình trạng vận hành theo ngày.' ?></p>
      <div class="shift-hero-actions">
        <?php if ($canManageShifts): ?>
          <button type="button" class="shift-btn primary" id="openShiftModalBtn"><i class="fa-solid fa-plus"></i> Tạo ca</button>
          <button type="button" class="shift-btn hero-outline" id="openAssignModalBtn"><i class="fa-solid fa-user-plus"></i> Phân công</button>
          <button type="button" class="shift-btn hero-outline" id="scrollApprovalBtn"><i class="fa-solid fa-user-check"></i> Duyệt đăng ký</button>
        <?php else: ?>
          <button type="button" class="shift-btn primary" id="scrollRegisterBtn"><i class="fa-solid fa-calendar-plus"></i> Đăng ký ca</button>
          <button type="button" class="shift-btn hero-outline" id="shiftThisWeekHeroBtn"><i class="fa-solid fa-calendar-week"></i> Lịch tuần này</button>
        <?php endif; ?>
        <button type="button" class="shift-btn hero-light" id="shiftRefreshBtn"><i class="fa-solid fa-rotate"></i> Làm mới</button>
      </div>
      <div class="shift-hero-note"><i class="fa-solid fa-circle-check"></i> Dữ liệu ca làm được đọc trực tiếp từ lịch phân công hiện có.</div>
    </div>
    <div class="shift-hero-visual" aria-hidden="true">
      <div class="shift-hero-icon"><i class="fa-solid fa-calendar-days"></i></div>
      <div class="shift-hero-blob blob-one"></div><div class="shift-hero-blob blob-two"></div>
      <div class="shift-mini-summary mini-a"><strong id="heroTodayCount">0</strong><span>Ca hôm nay</span></div>
      <div class="shift-mini-summary mini-b"><strong id="heroLiveCount">0</strong><span>Đang trực</span></div>
      <div class="shift-mini-summary mini-c"><strong id="heroWeekCount">0</strong><span>7 ngày tới</span></div>
      <div class="shift-hero-clock"><i class="fa-regular fa-clock"></i><span id="shiftClockText">--:--</span></div>
    </div>
  </section>

  <div class="shift-now shift-live-strip" id="currentShiftNotice"><i class="fa-solid fa-clock"></i><div><strong>Đang kiểm tra ca hiện tại...</strong><span>Hệ thống tự nhận biết theo ngày và giờ trên máy.</span></div></div>

  <?php if ($selfOnly): ?>
  <section class="employee-shift-overview">
    <div class="shift-card shift-my-today-card">
      <div class="shift-card-head"><div><h3><i class="fa-solid fa-user-clock"></i> Ca của tôi hôm nay</h3><span>Lịch cá nhân của tài khoản đang đăng nhập.</span></div></div>
      <div class="shift-my-today" id="myTodayShifts"><div class="shift-empty">Đang tải ca của bạn...</div></div>
    </div>
    <div class="shift-card shift-upcoming-card">
      <div class="shift-card-head"><div><h3><i class="fa-solid fa-forward"></i> Ca sắp tới của tôi</h3><span>Nhìn nhanh những lịch làm gần nhất.</span></div></div>
      <div class="employee-upcoming-list" id="employeeUpcomingList"><div class="shift-empty">Đang tải lịch sắp tới...</div></div>
    </div>
  </section>
  <?php endif; ?>

  <section class="shift-stats <?= $selfOnly ? 'employee-stats' : 'manager-stats' ?>">
    <article class="shift-stat stat-blue"><div><span>Ca hôm nay</span><strong id="todayShiftCount">0</strong><small><?= $selfOnly ? 'Lịch của bạn trong hôm nay' : 'Tổng lượt phân công hôm nay' ?></small></div><i class="fa-solid fa-calendar-day"></i><div class="shift-spark"><b></b><b></b><b></b><b></b></div></article>
    <article class="shift-stat stat-green"><div><span><?= $selfOnly ? 'Lịch 7 ngày tới' : 'Nhân viên có lịch' ?></span><strong id="<?= $selfOnly ? 'weekShiftCount' : 'staffShiftCount' ?>">0</strong><small><?= $selfOnly ? 'Số ca sắp tới trong 7 ngày' : 'Nhân sự đã được xếp ca' ?></small></div><i class="fa-solid <?= $selfOnly ? 'fa-calendar-week' : 'fa-user-group' ?>"></i><div class="shift-spark"><b></b><b></b><b></b><b></b></div></article>
    <article class="shift-stat stat-purple"><div><span><?= $selfOnly ? 'Tổng ca tháng này' : 'Yêu cầu chờ duyệt' ?></span><strong id="<?= $selfOnly ? 'staffShiftCount' : 'pendingRequestCount' ?>">0</strong><small><?= $selfOnly ? 'Lịch của bạn trong tháng hiện tại' : 'Đăng ký ca cần xử lý' ?></small></div><i class="fa-solid <?= $selfOnly ? 'fa-calendar-days' : 'fa-envelope-open-text' ?>"></i><div class="shift-spark"><b></b><b></b><b></b><b></b></div></article>
    <article class="shift-stat stat-orange"><div><span><?= $selfOnly ? 'Chờ duyệt' : 'Ca trống / thiếu người' ?></span><strong id="<?= $selfOnly ? 'employeePendingCount' : 'understaffedCount' ?>">0</strong><small><?= $selfOnly ? 'Ca bạn đã đăng ký đang chờ' : 'Ca còn thiếu nhân sự' ?></small></div><i class="fa-solid <?= $selfOnly ? 'fa-hourglass-half' : 'fa-triangle-exclamation' ?>"></i><div class="shift-spark"><b></b><b></b><b></b><b></b></div></article>
    <?php if (!$selfOnly): ?><article class="shift-stat stat-teal"><div><span>Đang trực lúc này</span><strong id="liveShiftCount">0</strong><small>Nhân viên đang trong ca</small></div><i class="fa-solid fa-circle-play"></i><div class="shift-spark"><b></b><b></b><b></b><b></b></div></article><?php endif; ?>
  </section>

  <section class="shift-toolbar shift-smart-toolbar">
    <div class="shift-toolbar-dates">
      <label>Từ ngày <input type="date" id="shiftFromDate"></label>
      <label>Đến ngày <input type="date" id="shiftToDate"></label>
    </div>
    <div class="shift-toolbar-actions">
      <button type="button" class="shift-btn secondary" id="shiftTodayFilterBtn"><i class="fa-solid fa-calendar-day"></i> Hôm nay</button>
      <button type="button" class="shift-btn secondary" id="shiftThisWeekBtn"><i class="fa-solid fa-calendar-week"></i> Tuần này</button>
      <button type="button" class="shift-btn secondary" id="shiftThisMonthBtn"><i class="fa-solid fa-calendar"></i> Tháng này</button>
      <?php if (!$selfOnly): ?>
      <select id="shiftEmployeeFilter" class="shift-filter-select"><option value="">Tất cả nhân viên</option></select>
      <select id="shiftTypeFilter" class="shift-filter-select"><option value="">Tất cả ca</option></select>
      <?php endif; ?>
    </div>
    <div class="shift-filter-chips" id="shiftFilterChips">
      <button type="button" class="active" data-shift-chip="all">Tất cả</button>
      <?php if ($selfOnly): ?>
        <button type="button" data-shift-chip="today">Hôm nay</button><button type="button" data-shift-chip="upcoming">Sắp tới</button><button type="button" data-shift-chip="registered">Đã đăng ký</button>
      <?php else: ?>
        <button type="button" data-shift-chip="live">Đang trực</button><button type="button" data-shift-chip="empty">Ca trống</button><button type="button" data-shift-chip="understaffed">Thiếu người</button><button type="button" data-shift-chip="approved">Đã duyệt</button><button type="button" data-shift-chip="selfreg">NV tự đăng ký</button>
      <?php endif; ?>
    </div>
  </section>

  <section class="shift-calendar-card modern-calendar-card">
    <div class="shift-calendar-main">
      <div class="shift-calendar-header">
        <div><span class="shift-section-kicker">Calendar</span><h3><i class="fa-regular fa-calendar"></i> Lịch làm việc theo tháng</h3><p>Ngày có ca được đánh dấu màu. Bấm vào ngày để xem lịch chi tiết.</p></div>
        <div class="shift-calendar-nav"><button type="button" class="calendar-nav-btn" id="shiftPrevMonth"><i class="fa-solid fa-chevron-left"></i></button><button type="button" class="calendar-today-btn" id="shiftTodayMonth">Hôm nay</button><button type="button" class="calendar-nav-btn" id="shiftNextMonth"><i class="fa-solid fa-chevron-right"></i></button></div>
      </div>
      <div class="shift-calendar-title" id="shiftCalendarTitle">Tháng</div>
      <div class="shift-calendar-weekdays"><span>T2</span><span>T3</span><span>T4</span><span>T5</span><span>T6</span><span>T7</span><span>CN</span></div>
      <div class="shift-calendar-grid" id="shiftCalendarGrid"></div>
      <div class="shift-calendar-legend"><span><i class="calendar-dot green"></i> Có ca</span><span><i class="calendar-dot red"></i> Đang diễn ra</span><span><i class="calendar-dot yellow"></i> Thiếu người</span><span><i class="calendar-dot purple"></i> Chờ duyệt</span><span><i class="calendar-dot blue"></i> Ngày đang chọn</span></div>
    </div>
    <aside class="shift-calendar-detail" id="shiftCalendarDetail"><div class="calendar-detail-empty"><i class="fa-regular fa-hand-pointer"></i><strong>Chọn một ngày trên lịch</strong><span>Thông tin ca làm của ngày đó sẽ hiển thị tại đây.</span></div></aside>
  </section>

  <?php if (!$selfOnly): ?>
  <section class="shift-manager-insights">
    <div class="shift-card shift-alert-center"><div class="shift-card-head"><div><h3><i class="fa-solid fa-bell"></i> Trung tâm cảnh báo</h3><span>Các điểm cần chú ý trong lịch trực.</span></div></div><div class="shift-alert-grid" id="shiftAlertCenter"></div></div>
    <div class="shift-card shift-mini-chart-card"><div class="shift-card-head"><div><h3><i class="fa-solid fa-chart-column"></i> Ca trong 7 ngày tới</h3><span>Phân bố lịch phân công theo ngày.</span></div></div><div class="shift-mini-bars" id="shiftWeeklyChart"></div></div>
  </section>

  <section class="shift-card shift-catalog-card">
    <div class="shift-card-head"><div><span class="shift-section-kicker">Shift Catalog</span><h3>Danh mục ca</h3></div><div class="shift-card-tools"><button class="shift-view-toggle active" data-shift-view="table"><i class="fa-solid fa-table-list"></i> Bảng</button><button class="shift-view-toggle" data-shift-view="card"><i class="fa-solid fa-grip"></i> Card</button></div></div>
    <div class="shift-table-wrap" id="shiftCatalogTableWrap"><table><thead><tr><th>Mã ca</th><th>Tên ca</th><th>Thời gian</th><th>Mô tả</th><th>Trạng thái</th><?php if ($canManageShifts): ?><th>Thao tác</th><?php endif; ?></tr></thead><tbody id="shiftTableBody"></tbody></table></div>
    <div class="shift-catalog-grid d-none" id="shiftCatalogGrid"></div>
  </section>
  <?php endif; ?>

  <section class="shift-card" id="advancedShiftBoard">
    <div class="shift-card-head"><div><span class="shift-section-kicker"><?= $selfOnly ? 'Available Shifts' : 'Open Shifts' ?></span><h3><i class="fa-solid fa-user-plus"></i> <?= $selfOnly ? 'Ca có thể đăng ký' : 'Đăng ký ca làm' ?></h3><span><?= $selfOnly ? 'Chọn ca còn chỗ phù hợp với lịch cá nhân của bạn.' : 'Theo dõi các ca còn chỗ trong 7 ngày tới.' ?></span></div></div>
    <?php if ($selfOnly): ?><div class="employee-register-filters" id="employeeRegisterFilters"><button class="active" data-register-filter="all">Tất cả</button><button data-register-filter="available">Còn chỗ</button><button data-register-filter="today">Hôm nay</button><button data-register-filter="tomorrow">Ngày mai</button><button data-register-filter="morning">Ca sáng</button><button data-register-filter="afternoon">Ca chiều</button><button data-register-filter="evening">Ca tối</button><button data-register-filter="full">Nguyên ngày</button></div><?php endif; ?>
    <div id="advancedShiftAvailable"><div class="shift-empty">Đang tải ca có thể đăng ký...</div></div>
  </section>

  <?php if ($canManageShifts): ?>
  <section class="shift-card approval-center" id="advancedShiftRequestsCard">
    <div class="shift-card-head"><div><span class="shift-section-kicker">Approval Center</span><h3><i class="fa-solid fa-user-check"></i> Trung tâm duyệt ca</h3><span>Duyệt hoặc từ chối yêu cầu đăng ký ca.</span></div><div class="approval-filter-row"><button class="active" data-approval-filter="all">Tất cả</button><button data-approval-filter="pending">Chờ duyệt</button><button data-approval-filter="approved">Đã duyệt</button><button data-approval-filter="rejected">Từ chối</button></div></div>
    <div id="advancedShiftRequests"><div class="shift-empty">Đang tải yêu cầu...</div></div>
  </section>
  <?php endif; ?>

  <section class="shift-card shift-assignment-card">
    <div class="shift-card-head"><div><span class="shift-section-kicker"><?= $selfOnly ? 'My Schedule' : 'Operations Schedule' ?></span><h3><?= $selfOnly ? 'Lịch làm của tôi' : 'Lịch phân công' ?></h3></div><span id="assignmentSummary"></span></div>
    <div class="shift-table-wrap"><table><thead><tr><th>STT</th><th>Ngày</th><?php if (!$selfOnly): ?><th>Nhân viên</th><?php endif; ?><th>Ca</th><th>Thời gian</th><th>Ghi chú</th><?php if ($canManageShifts): ?><th>Thao tác</th><?php endif; ?></tr></thead><tbody id="assignmentTableBody"></tbody></table></div>
  </section>

  <?php if ($selfOnly): ?>
  <section class="shift-card employee-timeline-card"><div class="shift-card-head"><div><span class="shift-section-kicker">Timeline</span><h3><i class="fa-solid fa-timeline"></i> Timeline ca cá nhân</h3><span>Các lịch gần nhất của bạn.</span></div></div><div class="employee-shift-timeline" id="employeeShiftTimeline"></div></section>
  <?php endif; ?>
</div>

<?php if ($canManageShifts): ?>
<div class="shift-modal" id="shiftEditorModal"><div class="shift-modal-backdrop" data-close-shift-modal></div><div class="shift-modal-panel"><div class="shift-modal-head"><div><span class="shift-section-kicker">Shift Editor</span><h3 id="shiftFormTitle">Thêm / sửa ca</h3></div><button type="button" data-close-shift-modal><i class="fa-solid fa-xmark"></i></button></div><form id="shiftForm" class="shift-form"><input type="hidden" id="shiftId"><label>Tên ca *<input id="shiftName" required placeholder="Ví dụ: Ca sáng"></label><label>Giờ bắt đầu *<input id="shiftStart" type="time" required></label><label>Giờ kết thúc *<input id="shiftEnd" type="time" required></label><label>Trạng thái<select id="shiftStatus"><option>Hoạt động</option><option>Ngừng hoạt động</option></select></label><label class="full">Mô tả<textarea id="shiftDescription" rows="3"></textarea></label><div class="shift-actions full"><button class="shift-btn primary" type="submit">Lưu ca</button><button class="shift-btn secondary" id="shiftResetBtn" type="button">Làm mới</button></div></form></div></div>

<div class="shift-modal shift-assign-modal" id="assignmentModal"><div class="shift-modal-backdrop" data-close-assignment-modal></div><div class="shift-modal-panel wide"><div class="shift-modal-head"><div><span class="shift-section-kicker">Assignment Wizard</span><h3 id="assignmentFormTitle">Phân công nhân viên</h3></div><button type="button" data-close-assignment-modal><i class="fa-solid fa-xmark"></i></button></div><form id="assignmentForm" class="shift-form"><input type="hidden" id="assignmentId"><label class="full">Nhân viên *<select id="assignmentEmployee" required><option value="">-- Chọn nhân viên --</option></select></label><div class="assignment-multi-block full"><div class="assignment-multi-head"><div><strong>Bước 1 · Chọn ngày làm *</strong><span>Có thể thêm nhiều ngày cho cùng một nhân viên.</span></div><button class="shift-btn secondary assignment-add-date" id="assignmentAddDateBtn" type="button"><i class="fa-solid fa-plus"></i> Thêm ngày</button></div><div class="assignment-date-list" id="assignmentDateList"></div></div><div class="assignment-multi-block full"><div class="assignment-multi-head"><div><strong>Bước 2 · Chọn ca *</strong><span>Ca trùng lịch sẽ được hệ thống kiểm tra khi lưu.</span></div></div><div class="assignment-shift-choices" id="assignmentShiftChoices"></div></div><label class="full">Ghi chú<input id="assignmentNote" placeholder="Ghi chú chung cho các ca được phân"></label><div class="assignment-selection-summary full" id="assignmentSelectionSummary"></div><div class="shift-actions full"><button class="shift-btn primary" type="submit">Lưu phân công</button><button class="shift-btn secondary" id="assignmentResetBtn" type="button">Làm mới</button></div></form></div></div>
<?php endif; ?>
<div class="toast" id="shiftToast"></div>
