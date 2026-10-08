<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/database/connect.php';
require_once __DIR__ . '/auth.php';

$isAuthenticated = !empty($_SESSION['user_id']);
if ($isAuthenticated && !syncLibrarySessionAccount($conn)) {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    header('Location: dangnhap.php');
    exit;
}

$isAuthenticated = !empty($_SESSION['user_id']);
$roleKey = currentLibraryRoleKey();
$displayName = $isAuthenticated ? ($_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Người dùng') : 'Khách tham quan';
$role = $isAuthenticated ? libraryRoleLabel($roleKey) : 'Chưa đăng nhập';
$overdueLoginNotice = $_SESSION['overdue_login_notice'] ?? null;
unset($_SESSION['overdue_login_notice']);
[$defaultPage, $defaultTitle] = libraryDefaultPage();
$access = [
    'isAuthenticated' => $isAuthenticated,
    'roleKey' => $roleKey,
    'roleLabel' => $role,
    'defaultPage' => $defaultPage,
    'defaultTitle' => $defaultTitle,
    'canManageBooks' => hasLibraryPermission('books_manage'),
    'canDeleteBooks' => hasLibraryPermission('books_delete'),
    'canManageCategories' => hasLibraryPermission('categories_manage'),
    'canDeleteCategories' => hasLibraryPermission('categories_delete'),
    'canManageReaders' => hasLibraryPermission('readers_manage'),
    'canDeleteReaders' => hasLibraryPermission('readers_delete'),
    'canManageEmployees' => hasLibraryPermission('employees_manage'),
    'canDeleteEmployees' => hasLibraryPermission('employees_delete'),
    'canViewEmployeesSelf' => hasLibraryPermission('employees_self_view'),
    'canManageSalary' => hasLibraryPermission('salary_manage'),
    'canViewSalaryAll' => hasLibraryPermission('salary_view_all'),
    'canViewSalarySelf' => hasLibraryPermission('salary_self_view'),
    'canManageLoans' => hasLibraryPermission('loans_manage'),
    'canDeleteLoans' => hasLibraryPermission('loans_delete'),
    'canBorrowSelf' => hasLibraryPermission('borrow_self'),
    'canManageReservations' => hasLibraryPermission('reservations_manage'),
    'canReserveSelf' => hasLibraryPermission('reservations_self'),
    'canManageRenewals' => hasLibraryPermission('renewals_manage'),
    'canRenewSelf' => hasLibraryPermission('renewals_self'),
    'canViewOverdue' => hasLibraryPermission('overdue_view'),
    'canManageSettings' => hasLibraryPermission('settings_manage'),
    'canTestEmail' => hasLibraryPermission('email_test'),
    'canSendOverdueEmail' => hasLibraryPermission('email_overdue_send'),
    'canManageAccounts' => hasLibraryPermission('accounts_manage'),
    'canManageShifts' => hasLibraryPermission('shifts_manage'),
    'canViewAllShifts' => hasLibraryPermission('shifts_view'),
    'canViewSelfShifts' => hasLibraryPermission('shifts_self_view'),
    'canRestoreData' => hasLibraryPermission('legacy_manage'),
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thư viện</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/trangchu.css">
    <link id="pageCSS" rel="stylesheet">
    <link rel="stylesheet" href="css/dark-mode.css?v=3">
    <link rel="stylesheet" href="css/ui-fixes.css?v=3">
    <link rel="stylesheet" href="css/reader-experience.css?v=22">
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="theme-color" content="#315efb">

    <style>
      .guest-sidebar-auth{margin-top:auto;padding:0 18px 22px;display:grid;gap:10px}
      .guest-sidebar-auth button{width:100%;border:0;border-radius:12px;padding:12px 14px;font-weight:600;cursor:pointer}
      .guest-login-btn{background:#fff;color:#2563eb}
      .guest-register-btn{background:#ef4444;color:#fff}
      .guest-header-auth{display:flex;align-items:center;gap:10px}
      .guest-header-copy{display:flex;flex-direction:column;margin-right:4px;text-align:right}
      .guest-header-copy strong{font-size:14px;color:#0f172a}
      .guest-header-copy span{font-size:11px;color:#64748b}
      .guest-header-login,.guest-header-register{display:inline-flex;align-items:center;gap:7px;padding:10px 13px;border-radius:11px;text-decoration:none;font-size:13px;font-weight:600}
      .guest-header-login{color:#2563eb;background:#eff6ff;border:1px solid #dbeafe}
      .guest-header-register{color:#fff;background:#2563eb;border:1px solid #2563eb}
      @media(max-width:760px){.guest-header-copy{display:none}.guest-header-auth{gap:6px}.guest-header-login,.guest-header-register{padding:9px 10px;font-size:12px}}

      .smart-header-tools{display:flex;gap:8px;align-items:center;margin-left:auto;margin-right:12px}.smart-header-tools button{position:relative;border:1px solid #e1e7ef;background:#fff;border-radius:12px;padding:9px 11px;color:#34425a;display:flex;align-items:center;gap:7px}.smart-header-tools kbd{font-size:9px;background:#eef2f7;padding:2px 5px;border-radius:5px}.notify-badge{position:absolute;right:-4px;top:-5px;min-width:18px;height:18px;border-radius:999px;background:#e23b4f;color:#fff;font-size:10px;line-height:18px}.global-command{display:none;position:fixed;inset:0;z-index:4000;background:rgba(19,27,42,.55);padding:10vh 20px}.global-command.show{display:block}.global-command-box{max-width:720px;margin:auto;background:#fff;border-radius:20px;box-shadow:0 28px 80px rgba(0,0,0,.28);overflow:hidden}.global-search-row{display:flex;align-items:center;gap:12px;padding:16px;border-bottom:1px solid #e7ebf1}.global-search-row input{flex:1;border:0;outline:0;font-size:17px}.global-search-row button{border:0;background:#eef2f7;border-radius:8px;padding:6px 9px}.command-shortcuts{display:flex;flex-wrap:wrap;gap:8px;padding:12px 16px}.command-shortcuts button{border:1px solid #e0e5ee;background:#f8fafc;border-radius:10px;padding:8px 10px}.global-result{display:flex;justify-content:space-between;gap:10px;padding:12px 16px;border-top:1px solid #f0f2f6;cursor:pointer}.global-result:hover{background:#f7f9fc}.mobile-bottom-nav{display:none}.dark-mode{background:#0f1723!important;color:#e9eef7}.dark-mode .dashboard-main,.dark-mode .dashboard-header,.dark-mode #content{background:#0f1723!important}.dark-mode .dashboard-header,.dark-mode .global-command-box{color:#e9eef7;background:#172033}.dark-mode .smart-header-tools button,.dark-mode .global-search-row input,.dark-mode .global-search-row button{background:#202c40;color:#e9eef7;border-color:#334158}.dark-mode .global-result{border-color:#303b4e}.dark-mode .global-result:hover{background:#202c40}
      @media(max-width:760px){body{padding-bottom:72px}.smart-header-tools button span,.smart-header-tools kbd{display:none}.smart-header-tools{margin-right:6px}.mobile-bottom-nav{display:grid;grid-template-columns:repeat(5,1fr);position:fixed;z-index:3000;left:8px;right:8px;bottom:8px;background:#fff;border:1px solid #e0e5ed;border-radius:18px;box-shadow:0 12px 35px rgba(28,39,61,.18);padding:7px}.mobile-bottom-nav button{border:0;background:transparent;color:#667287;display:grid;place-items:center;gap:3px;font-size:10px;padding:5px}.mobile-bottom-nav button i{font-size:17px}.mobile-bottom-nav .scan-main i{background:#315efb;color:#fff;border-radius:16px;padding:11px;margin-top:-22px;box-shadow:0 8px 20px rgba(49,94,251,.35)}.dark-mode .mobile-bottom-nav{background:#172033;border-color:#334158}.dark-mode .mobile-bottom-nav button{color:#cbd5e1}}
    </style>
</head>
<body class="<?= $roleKey === 'customer' ? 'reader-portal' : '' ?>">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fa-solid fa-book-open-reader"></i>
                <?php if ($roleKey === 'customer'): ?>
                <div class="reader-brand-copy">
                    <h2>Thư Viện</h2>
                    <span>Không gian độc giả</span>
                </div>
                <?php else: ?>
                <h2>Quản Lý Thư Viện</h2>
                <?php endif; ?>
            </div>
            <ul>
                <?php if ($roleKey === 'customer'): ?>
                <li class="reader-menu-label"><span>KHÁM PHÁ</span></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('trangchu')): ?>
                <li><a href="#" onclick="loadPage('trangchu.php', 'Trang chủ'); return false;"><i class="fa-solid fa-house"></i><span>Trang chủ</span></a></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('sach')): ?>
                <li><a href="#" onclick="loadPage('sach.php', 'Sách'); return false;"><i class="fa-solid fa-book"></i><span>Sách</span></a></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('theloai')): ?>
                <li><a href="#" onclick="loadPage('theloai.php', 'Thể loại'); return false;"><i class="fa-solid fa-layer-group"></i><span>Thể loại</span></a></li>
                <?php endif; ?>
                <?php if ($roleKey === 'customer'): ?>
                <li class="reader-menu-label"><span>CÁ NHÂN</span></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('docgia')): ?>
                <li><a href="#" onclick="loadPage('docgia.php', '<?= $roleKey === 'customer' ? 'Hồ sơ của tôi' : 'Khách hàng' ?>'); return false;"><i class="fa-solid fa-user"></i><span><?= $roleKey === 'customer' ? 'Hồ sơ của tôi' : 'Khách hàng' ?></span></a></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('muontra')): ?>
                <li><a href="#" onclick="loadPage('muontra.php', '<?= $roleKey === 'customer' ? 'Mượn sách' : 'Mượn - Trả' ?>'); return false;"><i class="fa-solid fa-right-left"></i><span><?= $roleKey === 'customer' ? 'Mượn sách' : 'Mượn - Trả' ?></span></a></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('nhanvien')): ?>
                <li><a href="#" onclick="loadPage('nhanvien.php', '<?= $roleKey === 'employee' ? 'Thông tin nhân viên của tôi' : 'Nhân viên' ?>'); return false;"><i class="fa-solid fa-user-tie"></i><span><?= $roleKey === 'employee' ? 'Thông tin nhân viên của tôi' : 'Nhân viên' ?></span></a></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('calamviec')): ?>
                <li><a href="#" onclick="loadPage('calamviec.php', 'Ca làm việc'); return false;"><i class="fa-solid fa-calendar-days"></i><span>Ca làm việc</span></a></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('thongke')): ?>
                <li><a href="#" onclick="loadPage('thongke.php', 'Thống kê'); return false;"><i class="fa-solid fa-chart-line"></i><span>Thống kê</span></a></li>
                <?php endif; ?>
                <?php if ($isAuthenticated && canAccessLibraryModule('nangcao')): ?>
                <li><a href="#" onclick="loadPage('nangcao.php', 'Thư viện thông minh'); return false;"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Thư viện thông minh</span></a></li>
                <?php endif; ?>
                <?php if ($roleKey === 'customer'): ?>
                <li class="reader-menu-label"><span>THƯ VIỆN</span></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('thongtinthuvien')): ?>
                <li><a href="#" onclick="loadPage('thongtinthuvien.php', 'Thông tin thư viện'); return false;"><i class="fa-solid fa-building-columns"></i><span>Thông tin thư viện</span></a></li>
                <?php endif; ?>
                <?php if (canAccessLibraryModule('caidat')): ?>
                <li><a href="#" onclick="loadPage('caidat.php', '<?= $roleKey === 'admin' ? 'Quản lý tài khoản' : 'Cài đặt' ?>'); return false;"><i class="fa-solid <?= $roleKey === 'admin' ? 'fa-user-shield' : 'fa-gear' ?>"></i><span><?= $roleKey === 'admin' ? 'Quản lý tài khoản' : 'Cài đặt' ?></span></a></li>
                <?php endif; ?>
            </ul>
            <?php if ($isAuthenticated): ?>
            <button class="logout" type="button" onclick="window.location.href='logout.php'">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </button>
            <?php else: ?>
            <div class="guest-sidebar-auth">
                <button class="guest-login-btn" type="button" onclick="window.location.href='dangnhap.php'"><i class="fa-solid fa-right-to-bracket"></i> Đăng nhập</button>
                <button class="guest-register-btn" type="button" onclick="window.location.href='dangky.php'"><i class="fa-solid fa-user-plus"></i> Đăng ký</button>
            </div>
            <?php endif; ?>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuBtn" type="button" aria-label="Mở menu"><i class="fa-solid fa-bars"></i></button>
                    <div class="title">
                        <h1 id="pageTitle"><?= htmlspecialchars($defaultTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                        <p>Hệ thống quản lý thư viện</p>
                    </div>
                </div>
                <?php if ($isAuthenticated): ?>
                <div class="smart-header-tools">
                    <button type="button" id="globalSearchBtn" title="Tìm toàn hệ thống"><i class="fa-solid fa-magnifying-glass"></i><span>Tìm kiếm</span><kbd>Ctrl K</kbd></button>
                    <button type="button" id="themeToggle" title="Đổi giao diện"><i class="fa-solid fa-moon"></i></button>
                    <?php if ($roleKey === 'customer'): ?><button type="button" id="readerPrefsBtn" title="Tùy chỉnh trải nghiệm"><i class="fa-solid fa-sliders"></i></button><?php endif; ?>
                    <button type="button" id="notificationBell" title="Thông báo"><i class="fa-solid fa-bell"></i><span id="notificationBadge" class="notify-badge" hidden>0</span></button>
                </div>
                <div class="admin-menu">
                    <div class="admin-info">
                        <img src="https://png.pngtree.com/png-vector/20201226/ourlarge/pngtree-admin-line-icon-png-image_2644812.jpg" alt="Tài khoản">
                        <div class="admin-text">
                            <h4><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h4>
                            <p><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                    <div class="dropdown">
                        <?php if (canAccessLibraryModule('caidat')): ?>
                        <a href="#" onclick="loadPage('caidat.php','<?= $roleKey === 'admin' ? 'Quản lý tài khoản' : 'Cài đặt' ?>'); return false;"><i class="fa-solid <?= $roleKey === 'admin' ? 'fa-user-shield' : 'fa-user' ?>"></i> <?= $roleKey === 'admin' ? 'Quản lý tài khoản' : 'Cài đặt' ?></a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="guest-header-auth">
                    <div class="guest-header-copy">
                        <strong>Khách tham quan</strong>
                        <span>Xem sách và thông tin thư viện miễn phí</span>
                    </div>
                    <a href="dangnhap.php" class="guest-header-login"><i class="fa-solid fa-right-to-bracket"></i> Đăng nhập</a>
                    <a href="dangky.php" class="guest-header-register"><i class="fa-solid fa-user-plus"></i> Đăng ký</a>
                </div>
                <?php endif; ?>
            </header>
            <div id="content"></div>
        </main>
    </div>


    <?php if ($isAuthenticated): ?>
    <div class="global-command" id="globalCommand">
      <div class="global-command-box">
        <div class="global-search-row"><i class="fa-solid fa-magnifying-glass"></i><input id="globalSearchInput" autocomplete="off" placeholder="Tìm sách, độc giả, nhân viên..."><button id="globalCommandClose">ESC</button></div>
        <div class="command-shortcuts" id="commandShortcuts"></div>
        <div id="commandRecentWrap" hidden><div class="global-command-title">TÌM GẦN ĐÂY</div><div class="command-recent" id="commandRecent"></div></div>
        <div id="globalSearchResults"></div>
      </div>
    </div>
    <nav class="mobile-bottom-nav" id="mobileBottomNav">
      <button data-mobile-page="trangchu.php" data-title="Trang chủ"><i class="fa-solid fa-house"></i><span>Trang chủ</span></button>
      <button data-mobile-page="sach.php" data-title="Sách"><i class="fa-solid fa-book"></i><span>Sách</span></button>
      <button class="scan-main" data-mobile-page="nangcao.php" data-title="Thư viện thông minh"><i class="fa-solid fa-qrcode"></i><span>Quét</span></button>
      <button id="mobileNotify"><i class="fa-solid fa-bell"></i><span>Thông báo</span></button>
      <button data-mobile-page="<?= $roleKey==='customer'?'docgia.php':'caidat.php' ?>" data-title="<?= $roleKey==='customer'?'Hồ sơ của tôi':'Cài đặt' ?>"><i class="fa-solid fa-user"></i><span>Tôi</span></button>
    </nav>
    <?php endif; ?>

    <?php if ($isAuthenticated && $roleKey === 'customer'): ?>
    <section class="reader-sheet" id="readerNotificationCenter" aria-label="Trung tâm thông báo">
      <div class="reader-sheet-backdrop"></div><div class="reader-sheet-panel">
        <div class="reader-sheet-head"><div><span class="reader-sheet-kicker">THÔNG BÁO CỦA BẠN</span><h3>Trung tâm thông báo</h3></div><div class="reader-sheet-actions"><button class="reader-icon-btn" id="readerMarkAllRead" title="Đánh dấu tất cả đã đọc"><i class="fa-solid fa-check-double"></i></button><button class="reader-icon-btn" id="readerNotificationClose" title="Đóng"><i class="fa-solid fa-xmark"></i></button></div></div>
        <div class="reader-sheet-toolbar"><button class="active" data-notif-filter="all">Tất cả</button><button data-notif-filter="unread">Chưa đọc</button><button data-notif-filter="warning">Cần chú ý</button></div>
        <div class="reader-notification-list" id="readerNotificationList"></div>
      </div>
    </section>
    <section class="reader-sheet" id="readerPreferences" aria-label="Tùy chỉnh trải nghiệm">
      <div class="reader-sheet-backdrop"></div><div class="reader-sheet-panel">
        <div class="reader-sheet-head"><div><span class="reader-sheet-kicker">CÁ NHÂN HÓA</span><h3>Giao diện của bạn</h3></div><button class="reader-icon-btn" id="readerPrefsClose"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="reader-prefs-body">
          <div class="reader-pref-block"><h4>Màu nhấn</h4><div class="reader-pref-options"><button data-reader-pref="accent" data-value="default"><span class="reader-accent-dot blue"></span>Xanh</button><button data-reader-pref="accent" data-value="violet"><span class="reader-accent-dot violet"></span>Tím</button><button data-reader-pref="accent" data-value="mint"><span class="reader-accent-dot mint"></span>Ngọc</button></div></div>
          <div class="reader-pref-block"><h4>Cỡ chữ</h4><div class="reader-pref-options"><button data-reader-pref="font" data-value="sm">Nhỏ</button><button data-reader-pref="font" data-value="default">Mặc định</button><button data-reader-pref="font" data-value="lg">Lớn</button></div></div>
          <div class="reader-pref-block"><h4>Mật độ hiển thị</h4><div class="reader-pref-options"><button data-reader-pref="density" data-value="compact">Gọn</button><button data-reader-pref="density" data-value="default">Cân bằng</button><button data-reader-pref="density" data-value="comfort">Thoáng</button></div></div>
          <div class="reader-empty-state"><i class="fa-solid fa-universal-access"></i><strong>Trải nghiệm dễ đọc hơn</strong><span>Giao diện hỗ trợ bàn phím, focus rõ ràng và tự giảm chuyển động nếu thiết bị yêu cầu.</span></div>
        </div>
      </div>
    </section>
    <section class="reader-onboarding" id="readerOnboarding">
      <div class="reader-onboarding-card"><div class="reader-onboarding-visual"><i class="fa-solid fa-book-open-reader reader-onboarding-books"></i></div><div class="reader-onboarding-body"><h2>Chào mừng đến với không gian đọc của bạn 👋</h2><p>Ba bước ngắn để bắt đầu sử dụng thư viện thuận tiện hơn.</p><div class="reader-onboarding-steps"><div class="reader-onboarding-step"><i class="fa-solid fa-compass"></i><strong>1. Khám phá sách</strong><span>Tìm sách theo tên, tác giả hoặc thể loại.</span></div><div class="reader-onboarding-step"><i class="fa-regular fa-heart"></i><strong>2. Lưu Muốn đọc</strong><span>Thả tim để giữ lại những cuốn bạn quan tâm.</span></div><div class="reader-onboarding-step"><i class="fa-solid fa-book-open-reader"></i><strong>3. Mượn & theo dõi</strong><span>Theo dõi hạn trả, lịch sử và hành trình đọc cá nhân.</span></div></div><div class="reader-onboarding-actions"><button class="skip" id="readerOnboardingSkip">Để sau</button><button class="start" id="readerOnboardingStart">Khám phá ngay</button></div></div></div>
    </section>
    <div class="reader-install-banner" id="readerInstallBanner"><i class="fa-solid fa-mobile-screen-button"></i><div><strong>Cài Thư viện lên thiết bị</strong><small>Mở nhanh như một ứng dụng và giữ một số tài nguyên giao diện khi ngoại tuyến.</small></div><button class="install" id="readerInstallBtn">Cài đặt</button><button class="dismiss" id="readerInstallDismiss" title="Để sau">×</button></div>
    <?php endif; ?>

    <script>window.libraryAccess = <?= json_encode($access, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <?php if (is_array($overdueLoginNotice) && (int)($overdueLoginNotice['books'] ?? 0) > 0): ?>
    <script>
      window.addEventListener('DOMContentLoaded', () => {
        const count = <?= (int)$overdueLoginNotice['books'] ?>;
        const maxDays = <?= (int)($overdueLoginNotice['maxDays'] ?? 0) ?>;
        const extra = maxDays > 30 ? '\nCó sách đã quá hạn trên 30 ngày. Chức năng gia hạn đang bị khóa cho đến khi xử lý quá hạn.' : '';
        const msg=`Bạn hiện có ${count} cuốn sách quá hạn.${extra.replace('\n',' ')} Vui lòng kiểm tra mục Mượn sách và hoàn trả đúng quy định.`; setTimeout(()=>window.libraryToast ? window.libraryToast(msg,'warning','Thông báo quá hạn') : alert(msg),60);
      });
    </script>
    <?php endif; ?>
    <script src="js/main.js"></script>
    <script src="js/reader-experience.js?v=22"></script>
</body>
</html>
