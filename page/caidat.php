<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('caidat');
$isAdmin = hasLibraryPermission('accounts_manage');
$canManageSettings = hasLibraryPermission('settings_manage');
$canBackup = hasLibraryPermission('backup');
$canTestEmail = hasLibraryPermission('email_test') && currentLibraryRoleKey() === 'manager';
?>
<div class="settings-center" id="settingsCenter">
  <section class="settings-hero settings-reveal">
    <div class="settings-hero-copy">
      <span class="settings-kicker"><i class="fa-solid fa-sliders"></i> Settings Center</span>
      <h2>Trung tâm cài đặt hệ thống</h2>
      <p>Quản lý thông tin thư viện, quy định mượn – trả, email, sao lưu dữ liệu và trạng thái hệ thống trong một nơi.</p>
      <div class="settings-hero-meta">
        <span id="settingsLastUpdated"><i class="fa-regular fa-clock"></i> Cập nhật lần cuối: --</span>
        <span class="settings-health ok"><i class="fa-solid fa-circle-check"></i> Hệ thống đang hoạt động ổn định</span>
      </div>
      <div class="settings-hero-actions">
        <?php if ($canManageSettings): ?>
          <button type="button" class="settings-btn primary" id="saveAllSettingsBtn"><i class="fa-solid fa-floppy-disk"></i> Lưu tất cả</button>
          <button type="button" class="settings-btn soft" id="restoreSettingsBtn"><i class="fa-solid fa-arrow-rotate-left"></i> Khôi phục đã lưu</button>
        <?php endif; ?>
        <?php if ($canBackup): ?><button type="button" class="settings-btn dark" id="heroBackupBtn"><i class="fa-solid fa-cloud-arrow-up"></i> Tạo backup</button><?php endif; ?>
        <button type="button" class="settings-btn ghost" id="refreshSettingsBtn"><i class="fa-solid fa-rotate-right"></i> Làm mới</button>
      </div>
    </div>
    <div class="settings-hero-visual" aria-hidden="true">
      <div class="settings-grid-glow"></div>
      <div class="settings-orbit orbit-a"></div>
      <div class="settings-orbit orbit-b"></div>
      <div class="settings-hero-icon"><i class="fa-solid fa-gears"></i></div>
      <div class="settings-mini-status ms-a"><i class="fa-solid fa-database"></i><div><b>Database</b><span id="heroDbStatus">Đang kiểm tra</span></div></div>
      <div class="settings-mini-status ms-b"><i class="fa-solid fa-envelope"></i><div><b>Email</b><span id="heroEmailStatus">Sẵn sàng</span></div></div>
      <div class="settings-mini-status ms-c"><i class="fa-solid fa-shield-halved"></i><div><b>Backup</b><span id="heroBackupStatus">Chưa tạo</span></div></div>
    </div>
  </section>

  <section class="settings-status-grid settings-reveal">
    <article class="settings-status-card tone-blue" data-jump-tab="library">
      <div class="status-icon"><i class="fa-solid fa-building-columns"></i></div>
      <div><span>Thông tin thư viện</span><strong id="libraryConfigStatus">Đã cấu hình</strong><small id="libraryConfigHint">Đang đồng bộ từ MySQL</small></div>
    </article>
    <article class="settings-status-card tone-green" data-jump-tab="email">
      <div class="status-icon"><i class="fa-solid fa-envelope-circle-check"></i></div>
      <div><span>Email</span><strong id="emailConfigStatus">Sẵn sàng</strong><small id="emailConfigHint">Cấu hình SMTP hệ thống</small></div>
    </article>
    <article class="settings-status-card tone-orange" data-jump-tab="backup">
      <div class="status-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
      <div><span>Backup gần nhất</span><strong id="backupSummaryStatus">Chưa có</strong><small id="backupSummaryHint">Tạo backup để bảo vệ dữ liệu</small></div>
    </article>
    <article class="settings-status-card tone-purple" data-jump-tab="loan">
      <div class="status-icon"><i class="fa-solid fa-right-left"></i></div>
      <div><span>Quy định mượn</span><strong id="borrowRuleStatus">-- ngày</strong><small id="borrowRuleHint">Đang đọc cấu hình hiện tại</small></div>
    </article>
  </section>

  <nav class="settings-tabs settings-reveal" aria-label="Điều hướng cài đặt">
    <button type="button" class="settings-tab active" data-settings-tab="overview"><i class="fa-solid fa-table-cells-large"></i><span>Tổng quan</span></button>
    <button type="button" class="settings-tab" data-settings-tab="library"><i class="fa-solid fa-building-columns"></i><span>Thông tin thư viện</span></button>
    <button type="button" class="settings-tab" data-settings-tab="loan"><i class="fa-solid fa-right-left"></i><span>Mượn – Trả</span></button>
    <button type="button" class="settings-tab" data-settings-tab="email"><i class="fa-solid fa-envelope"></i><span>Email</span></button>
    <button type="button" class="settings-tab" data-settings-tab="backup"><i class="fa-solid fa-cloud-arrow-up"></i><span>Backup</span></button>
    <button type="button" class="settings-tab" data-settings-tab="system"><i class="fa-solid fa-server"></i><span>Hệ thống</span></button>
    <?php if ($isAdmin): ?><button type="button" class="settings-tab" data-settings-tab="accounts"><i class="fa-solid fa-user-shield"></i><span>Tài khoản</span></button><?php endif; ?>
  </nav>

  <div class="settings-tab-panels">
    <section class="settings-panel active" data-settings-panel="overview">
      <div class="settings-overview-grid">
        <article class="settings-card settings-attention-card">
          <div class="settings-card-head"><div><span class="card-eyebrow">Hệ thống</span><h3><i class="fa-solid fa-triangle-exclamation"></i> Cần chú ý</h3><p>Tự động tổng hợp từ cấu hình hiện tại.</p></div><span class="status-pill neutral" id="attentionCount">0 mục</span></div>
          <div class="attention-list" id="settingsAttentionList"></div>
        </article>
        <article class="settings-card settings-preview-card">
          <div class="settings-card-head"><div><span class="card-eyebrow">Live preview</span><h3><i class="fa-solid fa-eye"></i> Xem trước cấu hình</h3><p>Thay đổi trên form được phản ánh ngay tại đây trước khi lưu.</p></div></div>
          <div class="settings-live-preview">
            <div class="preview-brand"><span class="preview-logo"><i class="fa-solid fa-book-open"></i></span><div><strong id="previewLibraryName">--</strong><small id="previewLibraryAddress">--</small></div></div>
            <div class="preview-info-row"><span><i class="fa-solid fa-envelope"></i> <b id="previewLibraryEmail">--</b></span><span><i class="fa-solid fa-phone"></i> <b id="previewLibraryPhone">--</b></span></div>
            <div class="preview-rule-row"><div><span>Mượn tối đa</span><strong id="previewBorrowDays">-- ngày</strong></div><div><span>Gia hạn</span><strong id="previewRenewDays">-- ngày</strong></div><div><span>Phạt quá hạn</span><strong id="previewFine">--</strong></div></div>
          </div>
        </article>
      </div>
      <div class="settings-quick-grid">
        <button type="button" class="settings-quick-card" data-jump-tab="library"><i class="fa-solid fa-address-card"></i><span><b>Hồ sơ thư viện</b><small>Cập nhật tên, địa chỉ và liên hệ</small></span><i class="fa-solid fa-arrow-right"></i></button>
        <button type="button" class="settings-quick-card" data-jump-tab="loan"><i class="fa-solid fa-calendar-days"></i><span><b>Quy định mượn</b><small>Ngày mượn, gia hạn và mức phạt</small></span><i class="fa-solid fa-arrow-right"></i></button>
        <button type="button" class="settings-quick-card" data-jump-tab="backup"><i class="fa-solid fa-shield"></i><span><b>Bảo vệ dữ liệu</b><small>Tạo bản sao lưu JSON</small></span><i class="fa-solid fa-arrow-right"></i></button>
        <button type="button" class="settings-quick-card" data-jump-tab="system"><i class="fa-solid fa-server"></i><span><b>Trạng thái hệ thống</b><small>Database, PHP và máy chủ</small></span><i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </section>

    <section class="settings-panel" data-settings-panel="library">
      <div class="settings-split-grid">
        <section class="settings-card settings-form-card">
          <div class="settings-card-head"><div><span class="card-eyebrow">Library profile</span><h3><i class="fa-solid fa-building-columns"></i> <?= $canManageSettings ? 'Chỉnh sửa thông tin thư viện' : 'Thông tin thư viện' ?></h3><p><?= $canManageSettings ? 'Thông tin này được sử dụng tại trang Thông tin thư viện.' : 'Dữ liệu được lưu trực tiếp trong MySQL.' ?></p></div></div>
          <form id="libraryForm">
            <div class="settings-field-grid">
              <label class="settings-field"><span class="field-icon blue"><i class="fa-solid fa-book-open"></i></span><span class="field-body"><b>Tên thư viện</b><small>Tên hiển thị chính thức của hệ thống</small><input id="libraryName" <?= $canManageSettings ? '' : 'readonly' ?> type="text" required></span></label>
              <label class="settings-field"><span class="field-icon red"><i class="fa-solid fa-location-dot"></i></span><span class="field-body"><b>Địa chỉ</b><small>Địa điểm liên hệ của thư viện</small><input id="libraryAddress" <?= $canManageSettings ? '' : 'readonly' ?> type="text"></span></label>
              <label class="settings-field"><span class="field-icon green"><i class="fa-solid fa-envelope"></i></span><span class="field-body"><b>Email</b><small>Email chính thức dùng để liên hệ</small><input id="libraryEmail" <?= $canManageSettings ? '' : 'readonly' ?> type="email"></span></label>
              <label class="settings-field"><span class="field-icon orange"><i class="fa-solid fa-phone"></i></span><span class="field-body"><b>Số điện thoại</b><small>Số điện thoại liên hệ nhanh</small><input id="libraryPhone" <?= $canManageSettings ? '' : 'readonly' ?> type="tel"></span></label>
            </div>
            <?php if ($canManageSettings): ?><div class="settings-card-actions"><button type="submit" class="settings-btn primary"><i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi</button></div><?php endif; ?>
          </form>
        </section>
        <aside class="settings-card settings-contact-preview">
          <div class="settings-card-head"><div><span class="card-eyebrow">Preview</span><h3><i class="fa-solid fa-id-card"></i> Thẻ thông tin thư viện</h3><p>Xem trước dữ liệu người dùng sẽ nhìn thấy.</p></div></div>
          <div class="contact-preview-box">
            <div class="contact-preview-logo"><i class="fa-solid fa-book-open-reader"></i></div>
            <h4 id="contactPreviewName">--</h4>
            <p id="contactPreviewAddress">--</p>
            <div class="contact-preview-actions"><button type="button" data-copy-from="libraryEmail"><i class="fa-regular fa-copy"></i> Sao chép email</button><button type="button" id="callPreviewBtn"><i class="fa-solid fa-phone"></i> Gọi thử</button><button type="button" id="mapPreviewBtn"><i class="fa-solid fa-map-location-dot"></i> Mở bản đồ</button></div>
          </div>
        </aside>
      </div>
    </section>

    <section class="settings-panel" data-settings-panel="loan">
      <section class="settings-card settings-policy-card">
        <div class="settings-card-head"><div><span class="card-eyebrow">Loan Policy</span><h3><i class="fa-solid fa-right-left"></i> Quy định mượn – trả</h3><p>Các quy tắc đang áp dụng trực tiếp cho nghiệp vụ mượn sách.</p></div></div>
        <form id="borrowForm">
          <div class="policy-grid">
            <label class="policy-input-card blue"><span class="policy-icon"><i class="fa-solid fa-calendar-days"></i></span><span><b>Số ngày mượn</b><small>Thời hạn mặc định cho một phiếu mượn</small></span><input id="borrowDays" <?= $canManageSettings ? '' : 'readonly' ?> type="number" min="1" required></label>
            <label class="policy-input-card purple"><span class="policy-icon"><i class="fa-solid fa-rotate-right"></i></span><span><b>Số ngày gia hạn</b><small>Thời gian cộng thêm khi được duyệt</small></span><input id="renewDays" <?= $canManageSettings ? '' : 'readonly' ?> type="number" min="1" required></label>
            <label class="policy-input-card orange"><span class="policy-icon"><i class="fa-solid fa-coins"></i></span><span><b>Mức phạt quá hạn</b><small>Đơn vị: đồng / ngày / cuốn</small></span><input id="fineAmount" <?= $canManageSettings ? '' : 'readonly' ?> type="number" min="0" step="1000" required></label>
          </div>
          <div class="policy-preview"><span><i class="fa-solid fa-circle-info"></i> Quy định hiện tại</span><div id="policyPreviewText">--</div></div>
          <?php if ($canManageSettings): ?><div class="settings-card-actions"><button type="submit" class="settings-btn primary"><i class="fa-solid fa-floppy-disk"></i> Lưu quy định</button></div><?php endif; ?>
        </form>
      </section>
    </section>

    <section class="settings-panel" data-settings-panel="email">
      <div class="settings-split-grid">
        <section class="settings-card settings-email-card">
          <div class="settings-card-head"><div><span class="card-eyebrow">SMTP Center</span><h3><i class="fa-solid fa-envelope-circle-check"></i> Email & thông báo</h3><p>Kiểm tra khả năng gửi email từ cấu hình hiện tại.</p></div><span class="status-pill ok" id="smtpStatusPill">SMTP sẵn sàng</span></div>
          <?php if ($canTestEmail): ?>
          <form id="emailTestForm">
            <label class="settings-field compact"><span class="field-icon green"><i class="fa-solid fa-at"></i></span><span class="field-body"><b>Email nhận thử *</b><small>Gửi một email kiểm tra cấu hình SMTP</small><input id="testEmailAddress" type="email" placeholder="vidu@gmail.com" required></span></label>
            <div class="settings-card-actions"><button type="submit" class="settings-btn primary" id="sendTestEmailBtn"><i class="fa-solid fa-paper-plane"></i> Gửi email thử</button></div>
          </form>
          <?php else: ?>
          <div class="settings-readonly-note"><i class="fa-solid fa-shield-halved"></i><div><b>Chế độ theo quyền hiện tại</b><span>Chức năng gửi email thử chỉ khả dụng với tài khoản Quản lý.</span></div></div>
          <?php endif; ?>
        </section>
        <aside class="settings-card settings-email-log">
          <div class="settings-card-head"><div><span class="card-eyebrow">Delivery status</span><h3><i class="fa-solid fa-clock-rotate-left"></i> Lần kiểm tra gần nhất</h3><p>Lưu trên trình duyệt này để tiện theo dõi.</p></div></div>
          <div class="email-log-box"><span>Thời gian</span><strong id="emailLastTestTime">Chưa kiểm tra</strong></div>
          <div class="email-log-box"><span>Trạng thái</span><strong id="emailLastTestStatus">Chưa có dữ liệu</strong></div>
          <div class="email-config-note"><i class="fa-solid fa-code"></i><span>SMTP lấy từ <b>config/email.php</b>; giao diện này không thay đổi file cấu hình máy chủ.</span></div>
        </aside>
      </div>
    </section>

    <section class="settings-panel" data-settings-panel="backup">
      <section class="settings-card settings-backup-center">
        <div class="settings-card-head"><div><span class="card-eyebrow">Backup Center</span><h3><i class="fa-solid fa-cloud-arrow-up"></i> Sao lưu dữ liệu</h3><p>Xuất dữ liệu MySQL hiện tại thành tệp JSON để lưu trữ an toàn.</p></div><span class="status-pill ok" id="backupHealthPill">Sẵn sàng</span></div>
        <div class="backup-center-grid">
          <div class="backup-current-card"><div class="backup-cloud"><i class="fa-solid fa-cloud-arrow-up"></i></div><div><span>Trạng thái hiện tại</span><strong id="backupStatus">Sẵn sàng</strong><small id="backupDate">Chưa có bản sao lưu trong phiên này</small></div></div>
          <div class="backup-stat"><span>Kích thước gần nhất</span><strong id="backupSize">--</strong></div>
          <div class="backup-stat"><span>Lần sao lưu gần nhất</span><strong id="backupDateCompact">--</strong></div>
        </div>
        <div class="backup-timeline-wrap"><h4>Lịch sử backup trên trình duyệt này</h4><div class="backup-timeline" id="backupTimeline"></div></div>
        <?php if ($canBackup): ?><div class="settings-card-actions"><button type="button" class="settings-btn primary" id="backupBtn"><i class="fa-solid fa-download"></i> Tạo & tải backup</button></div><?php else: ?><div class="settings-readonly-note"><i class="fa-solid fa-lock"></i><div><b>Không có quyền sao lưu</b><span>Tài khoản hiện tại chỉ được xem trạng thái hệ thống.</span></div></div><?php endif; ?>
      </section>
    </section>

    <section class="settings-panel" data-settings-panel="system">
      <section class="settings-card settings-system-card">
        <div class="settings-card-head"><div><span class="card-eyebrow">System Health</span><h3><i class="fa-solid fa-server"></i> Thông tin hệ thống</h3><p>Môi trường đang kết nối và trạng thái runtime hiện tại.</p></div><span class="status-pill ok"><i class="fa-solid fa-circle-check"></i> Ổn định</span></div>
        <div class="system-status-grid" id="systemInfoList"></div>
      </section>
    </section>

    <?php if ($isAdmin): ?>
    <section class="settings-panel" data-settings-panel="accounts">
      <section class="settings-card account-card">
        <div class="settings-card-head"><div><span class="card-eyebrow">Access Control</span><h3><i class="fa-solid fa-user-shield"></i> Quản lý tài khoản & phân quyền</h3><p>Giữ nguyên toàn bộ chức năng tài khoản hiện tại.</p></div><button type="button" class="settings-btn primary" id="addAccountBtn"><i class="fa-solid fa-plus"></i> Thêm tài khoản</button></div>
        <div class="account-groups">
          <section class="account-group staff-account-group"><div class="account-group-head"><div><h4><i class="fa-solid fa-id-badge"></i> Tài khoản nhân viên</h4><p>Gồm Admin, Quản lý và Nhân viên.</p></div><span class="account-count"><b id="staffAccountCount">0</b> tài khoản</span></div><div class="table-wrap"><table><thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Trạng thái</th><th>Liên kết</th><th>Hành động</th></tr></thead><tbody id="staffAccountTableBody"></tbody></table></div></section>
          <section class="account-group customer-account-group"><div class="account-group-head"><div><h4><i class="fa-solid fa-users"></i> Tài khoản khách hàng</h4><p>Các tài khoản Khách/Độc giả tự đăng ký hoặc được Admin tạo.</p></div><span class="account-count"><b id="customerAccountCount">0</b> tài khoản</span></div><div class="new-registration-panel"><div class="new-registration-notice"><div class="new-registration-icon"><i class="fa-solid fa-bell"></i></div><div class="new-registration-text"><span>Khách hàng mới đăng ký trong 24 giờ qua</span><strong><b id="newRegistrationCount">0</b> tài khoản mới</strong></div></div><div class="recent-registration-box"><div class="recent-registration-head"><div><i class="fa-solid fa-clock-rotate-left"></i> Đăng ký gần nhất trong 1 ngày</div><small id="recentRegistrationCaption">Đang tải...</small></div><div class="recent-registration-list" id="recentRegistrationList"></div></div></div><div class="table-wrap"><table><thead><tr><th>Tài khoản</th><th>Vai trò</th><th>Trạng thái</th><th>Liên kết</th><th>Hành động</th></tr></thead><tbody id="customerAccountTableBody"></tbody></table></div></section>
        </div>
      </section>
    </section>
    <?php endif; ?>
  </div>

  <?php if ($canManageSettings): ?>
  <div class="settings-action-bar" id="settingsActionBar">
    <div><i class="fa-solid fa-wand-magic-sparkles"></i><span><b>Cài đặt hệ thống</b><small>Kiểm tra thay đổi trước khi lưu.</small></span></div>
    <div class="settings-action-buttons"><button type="button" class="settings-btn ghost" id="discardSettingsBtn">Hủy thay đổi</button><button type="button" class="settings-btn primary" id="footerSaveAllBtn"><i class="fa-solid fa-floppy-disk"></i> Lưu tất cả thay đổi</button></div>
  </div>
  <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<div class="account-modal" id="accountModal" aria-hidden="true"><div class="account-modal-box"><div class="account-modal-head"><div><h3 id="accountModalTitle">Thêm tài khoản</h3><p>Phân quyền truy cập theo đúng vai trò</p></div><button type="button" class="account-close" id="closeAccountModalBtn">×</button></div><form id="accountForm"><input id="accountId" type="hidden"><div class="account-form-grid"><label><span>Tên đăng nhập *</span><input id="accountUsername" type="text" minlength="3" required></label><label><span>Mật khẩu</span><input id="accountPassword" type="password" minlength="6" placeholder="Để trống nếu không đổi"></label><label><span>Vai trò *</span><select id="accountRole" required><option value="Admin">Admin</option><option value="Quản lý">Quản lý</option><option value="Nhân viên">Nhân viên</option><option value="Khách">Khách</option></select></label><label><span>Trạng thái *</span><select id="accountStatus" required><option value="Đang hoạt động">Đang hoạt động</option><option value="Khóa">Khóa</option></select></label><label id="employeeLinkField"><span>Liên kết nhân viên</span><select id="accountEmployee"><option value="">-- Không liên kết --</option></select></label><label id="readerLinkField"><span>Liên kết khách/độc giả</span><select id="accountReader"><option value="">-- Chọn độc giả --</option></select></label></div><div class="account-note" id="accountRoleNote"></div><div class="account-modal-actions"><button type="button" class="settings-btn ghost" id="cancelAccountBtn">Hủy</button><button type="submit" class="settings-btn primary">Lưu tài khoản</button></div></form></div></div>
<?php endif; ?>
<div class="settings-toast" id="settingsToast"><span class="settings-toast-icon"><i class="fa-solid fa-circle-check"></i></span><span class="settings-toast-text"></span></div>
