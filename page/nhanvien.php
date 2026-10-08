<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('nhanvien');
$roleKey = currentLibraryRoleKey();
$isEmployeeSelf = $roleKey === 'employee';
$canManageEmployees = hasLibraryPermission('employees_manage');
$canDeleteEmployees = hasLibraryPermission('employees_delete');
$canManageSalary = hasLibraryPermission('salary_manage');
$canViewSalary = hasAnyLibraryPermission(['salary_view_all','salary_self_view']);
?>
<div class="empx-page">
  <section class="empx-hero">
    <div class="empx-hero-copy">
      <span class="empx-kicker"><i class="fa-solid fa-people-group"></i> Workforce Management</span>
      <h2><?= $isEmployeeSelf ? 'Hồ sơ nhân viên của tôi' : 'Quản lý nhân viên' ?></h2>
      <p><?= $isEmployeeSelf ? 'Theo dõi thông tin hồ sơ, ca làm và trạng thái công việc của bạn.' : 'Theo dõi hồ sơ nhân sự, trạng thái làm việc, ca trực và thông tin vận hành của đội ngũ thư viện.' ?></p>
      <div class="empx-hero-actions">
        <?php if ($canManageEmployees): ?><button type="button" class="btn btn-primary" id="addEmployeeBtn"><i class="fa-solid fa-plus me-2"></i>Thêm nhân viên</button><?php endif; ?>
        <button type="button" class="btn btn-outline-primary" id="employeeExportBtn"><i class="fa-solid fa-file-export me-2"></i>Xuất danh sách</button>
        <button type="button" class="btn btn-light" id="employeeRefreshBtn"><i class="fa-solid fa-rotate-right me-2"></i>Làm mới</button>
      </div>
      <div class="empx-hero-status">
        <span><i class="fa-solid fa-circle-check"></i> Dữ liệu nhân sự đồng bộ theo hệ thống</span>
        <span id="employeeTodayText"><i class="fa-regular fa-calendar"></i> --</span>
      </div>
    </div>
    <div class="empx-hero-visual" aria-hidden="true">
      <div class="empx-big-icon"><i class="fa-solid fa-users-gear"></i></div>
      <div class="empx-orb orb-a"></div><div class="empx-orb orb-b"></div>
      <div class="empx-hero-card hc-a"><strong id="heroActiveEmployees">0</strong><span>Đang làm việc</span></div>
      <div class="empx-hero-card hc-b"><strong id="heroTodayShifts">0</strong><span>Ca hôm nay</span></div>
      <div class="empx-hero-card hc-c"><strong id="heroTopRole">--</strong><span>Vai trò phổ biến</span></div>
      <div class="empx-team-stack"><span class="av a1">NV</span><span class="av a2">QL</span><span class="av a3">AD</span><span class="av a4">+</span></div>
    </div>
  </section>

  <section class="empx-metrics">
    <article class="empx-metric metric-blue"><div><span>Tổng nhân viên</span><strong id="employeeTotalCount">0</strong><small>Hồ sơ đang quản lý</small></div><i class="fa-solid fa-users"></i><div class="empx-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
    <article class="empx-metric metric-green"><div><span>Đang làm việc</span><strong id="employeeActiveCount">0</strong><small>Nhân sự đang hoạt động</small></div><i class="fa-solid fa-user-check"></i><div class="empx-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
    <article class="empx-metric metric-red"><div><span>Nghỉ / Tạm khóa</span><strong id="employeeInactiveCount">0</strong><small>Cần theo dõi trạng thái</small></div><i class="fa-solid fa-user-lock"></i><div class="empx-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
    <article class="empx-metric metric-purple"><div><span>Ca làm hôm nay</span><strong id="employeeTodayShiftCount">0</strong><small>Lịch phân công trong ngày</small></div><i class="fa-solid fa-calendar-day"></i><div class="empx-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
  </section>

  <?php if (!$isEmployeeSelf): ?>
  <section class="empx-toolbar-card">
    <div class="empx-toolbar-top">
      <label class="empx-search"><i class="fa-solid fa-magnifying-glass"></i><input id="employeeSearch" type="text" placeholder="Tìm theo tên, mã NV, email hoặc số điện thoại..."></label>
      <div class="empx-toolbar-controls">
        <select id="employeeRoleFilter"><option value="">Tất cả vai trò</option><option>Admin</option><option>Quản trị viên</option><option>Thủ thư</option><option>Nhân viên</option></select>
        <select id="employeeStatusFilter"><option value="">Tất cả trạng thái</option><option>Đang làm việc</option><option>Nghỉ việc</option><option>Khóa</option></select>
        <input type="date" id="employeeHireDateFilter" title="Lọc theo ngày vào làm">
        <select id="employeeSort"><option value="newest">Mới nhất</option><option value="az">Tên A-Z</option><?php if ($canViewSalary): ?><option value="salary_desc">Lương cao → thấp</option><?php endif; ?></select>
      </div>
    </div>
    <div class="empx-chip-row">
      <button class="empx-chip active" data-emp-chip="all" type="button">Tất cả</button>
      <button class="empx-chip" data-emp-chip="active" type="button">Đang làm</button>
      <button class="empx-chip" data-emp-chip="inactive" type="button">Nghỉ / Khóa</button>
      <button class="empx-chip" data-emp-chip="new" type="button">Mới vào</button>
      <button class="empx-chip" data-emp-chip="today_shift" type="button">Có ca hôm nay</button>
    </div>
    <div class="empx-subtools"><div class="empx-view-switch"><button type="button" class="active" data-emp-view="table"><i class="fa-solid fa-table-list"></i> Bảng</button><button type="button" data-emp-view="card"><i class="fa-solid fa-grip"></i> Card</button></div><span id="employeeResultText">Đang tải...</span></div>
  </section>
  <?php endif; ?>

  <section class="empx-insight-grid">
    <article class="empx-panel">
      <div class="empx-panel-head"><div><span class="empx-section-kicker">Cần chú ý</span><h3>Nhân sự nổi bật & cần theo dõi</h3></div></div>
      <div class="empx-attention-grid" id="employeeAttentionGrid"></div>
    </article>
    <article class="empx-panel">
      <div class="empx-panel-head"><div><span class="empx-section-kicker">Thống kê</span><h3>Cơ cấu nhân sự</h3></div></div>
      <div class="empx-stat-grid"><div class="empx-donut" id="employeeGenderDonut"><div><strong id="employeeGenderTotal">0</strong><span>nhân viên</span></div></div><div class="empx-stat-lists"><div><h4>Giới tính</h4><div id="employeeGenderLegend"></div></div><div><h4>Vai trò</h4><div id="employeeRoleStats"></div></div></div></div>
    </article>
  </section>

  <?php if ($isEmployeeSelf): ?>
  <div class="empx-self-note"><i class="fa-solid fa-shield-halved"></i><span>Bạn chỉ xem được hồ sơ và mức lương của chính mình. Việc chỉnh sửa lương do Quản lý thực hiện.</span></div>
  <?php endif; ?>

  <section class="empx-list-card">
    <div class="employee-table-wrapper" id="employeeTableWrap">
      <table class="employee-table empx-table">
        <thead><tr><th>STT</th><th>Nhân viên</th><th>Giới tính</th><th>Ngày sinh</th><th>Vai trò</th><?php if ($canViewSalary): ?><th>Lương</th><?php endif; ?><th>Ngày vào làm</th><th>Trạng thái</th><th>Ca tháng này</th><th>Phiếu xử lý</th><?php if ($canManageEmployees): ?><th>Thao tác</th><?php endif; ?></tr></thead>
        <tbody id="employeeTableBody"></tbody>
      </table>
    </div>
    <div class="empx-card-grid d-none" id="employeeCardGrid"></div>
  </section>

  <aside class="empx-drawer" id="employeeDrawer"><div class="empx-drawer-backdrop" data-employee-drawer-close></div><div class="empx-drawer-panel"><button type="button" class="empx-drawer-close" data-employee-drawer-close><i class="fa-solid fa-xmark"></i></button><div id="employeeDrawerBody" class="empx-drawer-body"></div></div></aside>

  <?php if ($canManageEmployees): ?>
  <div class="empx-modal" id="employeeModal"><div class="empx-modal-backdrop" data-employee-modal-close></div><div class="empx-modal-panel">
    <div class="empx-modal-head"><div><span class="empx-section-kicker">Hồ sơ nhân sự</span><h3 id="employeeModalTitle">Thêm nhân viên</h3></div><button type="button" class="empx-modal-close" data-employee-modal-close><i class="fa-solid fa-xmark"></i></button></div>
    <div class="empx-modal-body">
      <div class="empx-form-avatar"><div class="empx-form-avatar-circle" id="employeeFormAvatar">NV</div><div><strong id="employeeFormAvatarName">Nhân viên mới</strong><span>Thông tin hồ sơ và công việc</span></div></div>
      <div class="employee-grid">
        <div><label>Mã nhân viên</label><input id="employeeId" type="text" readonly placeholder="Tự động"></div>
        <div><label>Email</label><input id="employeeEmail" type="email"></div>
        <div><label>Họ tên *</label><input id="employeeName" type="text"></div>
        <div><label>Giới tính</label><select id="employeeGender"><option>Nam</option><option>Nữ</option><option>Khác</option></select></div>
        <div><label>Ngày sinh</label><input id="employeeBirthDate" type="date"></div>
        <div><label>Số điện thoại</label><input id="employeePhone" type="text"></div>
        <div><label>Vai trò</label><select id="employeeRole"><option>Admin</option><option>Quản trị viên</option><option>Thủ thư</option><option>Nhân viên</option></select></div>
        <?php if ($canManageSalary): ?><div><label>Lương (đ/tháng)</label><input id="employeeSalary" type="number" min="0" step="1000" value="0"></div><?php endif; ?>
        <div><label>Ngày vào làm</label><input id="employeeHireDate" type="date"></div>
        <div><label>Trạng thái</label><select id="employeeStatus"><option>Đang làm việc</option><option>Nghỉ việc</option><option>Khóa</option></select></div>
      </div>
    </div>
    <div class="empx-modal-foot"><button class="btn btn-secondary" id="employeeResetBtn" type="button"><i class="fa-solid fa-rotate-right me-2"></i>Làm mới</button><button class="btn btn-success" id="employeeSaveBtn" type="button"><i class="fa-solid fa-floppy-disk me-2"></i>Lưu</button><button class="btn btn-primary" id="employeeUpdateBtn" type="button"><i class="fa-solid fa-pen me-2"></i>Cập nhật</button><?php if ($canDeleteEmployees): ?><button class="btn btn-danger" id="employeeDeleteBtn" type="button"><i class="fa-solid fa-trash me-2"></i>Xóa</button><?php endif; ?></div>
  </div></div>
  <?php endif; ?>
</div>
