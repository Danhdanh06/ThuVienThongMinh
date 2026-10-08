<?php
require_once __DIR__ . '/../auth.php';
requireLibraryPage('trangchu');
$isGuestHome = currentLibraryRoleKey() === 'guest';
$isCustomerHome = currentLibraryRoleKey() === 'customer';
$isStaffHome = in_array(currentLibraryRoleKey(), ['admin','manager','employee'], true);
?>

<?php if ($isGuestHome): ?>
<div class="guest-motion-home">
<section class="guest-motion-hero motion-reveal">
    <div class="guest-hero-orb orb-one"></div><div class="guest-hero-orb orb-two"></div><div class="guest-hero-orb orb-three"></div>
    <div class="guest-floating-icons" aria-hidden="true">
        <i class="fa-solid fa-book-open float-book f1"></i><i class="fa-regular fa-file-lines float-book f2"></i><i class="fa-solid fa-bookmark float-book f3"></i><i class="fa-solid fa-sparkles float-book f4"></i>
    </div>
    <div class="guest-hero-copy">
        <span class="guest-welcome-badge"><i class="fa-solid fa-book-open"></i> THAM QUAN THƯ VIỆN</span>
        <h1>Khám phá kho tri thức<br><span>theo cách sống động hơn.</span></h1>
        <p>Bạn có thể xem sách, thể loại và thông tin thư viện mà không cần đăng nhập. Khi muốn mượn sách hoặc đặt lịch mượn, hệ thống sẽ yêu cầu đăng nhập hoặc đăng ký tài khoản.</p>
        <div class="guest-hero-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input id="guestHeroSearch" type="search" placeholder="Tìm tên sách, tác giả hoặc thể loại..." autocomplete="off">
            <button type="button" id="guestHeroSearchBtn">Tìm sách <i class="fa-solid fa-arrow-right"></i></button>
        </div>
        <div class="guest-welcome-actions">
            <button type="button" onclick="loadPage('sach.php','Sách')"><i class="fa-solid fa-compass"></i> Khám phá sách</button>
            <button type="button" class="outline" onclick="loadPage('thongtinthuvien.php','Thông tin thư viện')"><i class="fa-solid fa-building-columns"></i> Thông tin thư viện</button>
        </div>
    </div>
    <div class="guest-hero-visual" aria-hidden="true">
        <div class="hero-visual-stage">
            <div class="hero-cover-cluster" id="heroCoverCluster">
                <div class="hero-cover-card hc-main"><div class="hero-cover-placeholder">Đang tạo bìa sách…</div></div>
                <div class="hero-cover-card hc-side hc-side-top"><div class="hero-cover-placeholder">Gợi ý theo thể loại</div></div>
                <div class="hero-cover-card hc-side hc-side-bottom"><div class="hero-cover-placeholder">Đọc và khám phá</div></div>
            </div>
            <div class="hero-visual-stats" id="heroVisualStats">
                <div class="hero-visual-stat"><strong>620+</strong><span>Cuốn sách</span></div>
                <div class="hero-visual-stat"><strong>19</strong><span>Thể loại</span></div>
                <div class="hero-visual-stat"><strong>42</strong><span>Còn sẵn</span></div>
            </div>
            <div class="hero-genre-cloud" id="heroGenreCloud">
                <span class="hero-genre-pill"><i class="fa-solid fa-heart"></i> Ngôn tình</span>
                <span class="hero-genre-pill"><i class="fa-solid fa-microchip"></i> Công nghệ</span>
                <span class="hero-genre-pill"><i class="fa-solid fa-book-open-reader"></i> Ngoại ngữ</span>
            </div>
            <div class="hero-light-column hero-light-one"></div>
            <div class="hero-light-column hero-light-two"></div>
            <div class="hero-glow"></div>
        </div>
        <div class="hero-quote"><i class="fa-solid fa-quote-left"></i><span>Mỗi trang sách mở ra một thế giới mới.</span></div>
    </div>
</section>

<section class="cards guest-public-cards motion-reveal" data-reveal-delay="1">
    <div class="dashboard-card guest-stat-card" data-dashboard-target="sach.php"><div><h3 id="totalBooks" data-count-target="0">0</h3><p>Tổng số cuốn sách</p></div><i class="fa-solid fa-book-open blue"></i></div>
    <div class="dashboard-card guest-stat-card" data-dashboard-target="sach.php"><div><h3 id="totalTitles" data-count-target="0">0</h3><p>Đầu sách</p></div><i class="fa-solid fa-book green"></i></div>
    <div class="dashboard-card guest-stat-card" data-dashboard-target="theloai.php"><div><h3 id="totalCategories" data-count-target="0">0</h3><p>Thể loại</p></div><i class="fa-solid fa-layer-group orange"></i></div>
    <div class="dashboard-card guest-stat-card" data-dashboard-target="sach.php"><div><h3 id="availableTitles" data-count-target="0">0</h3><p>Đầu sách còn hàng</p></div><i class="fa-solid fa-circle-check blue"></i></div>
</section>

<section class="guest-category-zone motion-reveal" data-reveal-delay="2">
    <div class="guest-section-heading"><div><span>KHÁM PHÁ NHANH</span><h2>Thể loại nổi bật</h2></div><button type="button" onclick="loadPage('theloai.php','Thể loại')">Xem tất cả <i class="fa-solid fa-arrow-right"></i></button></div>
    <div class="guest-category-scroller"><div class="guest-category-track" id="guestCategoryTrack"><span class="guest-category-chip skeleton-chip">Đang tải thể loại...</span></div></div>
</section>

<section class="guest-featured motion-reveal" data-reveal-delay="3">
    <div class="guest-section-heading">
        <div><span>GỢI Ý HÔM NAY</span><h2>Sách nổi bật hôm nay</h2><p>Carousel tự chạy — rê chuột để dừng, kéo hoặc vuốt để xem thêm.</p></div>
        <div class="guest-carousel-controls"><button id="guestFeaturedPrev" type="button" aria-label="Sách trước"><i class="fa-solid fa-chevron-left"></i></button><button id="guestFeaturedNext" type="button" aria-label="Sách tiếp theo"><i class="fa-solid fa-chevron-right"></i></button></div>
    </div>
    <div class="guest-featured-viewport" id="guestFeaturedViewport"><div class="guest-featured-track" id="guestFeaturedTrack"></div></div>
</section>

<section class="guest-marquee-zone motion-reveal" data-reveal-delay="4">
    <div class="guest-section-heading"><div><span>TRƯNG BÀY</span><h2>Kệ sách chuyển động</h2></div></div>
    <div class="guest-book-marquee marquee-left"><div class="guest-marquee-track" id="guestMarqueeOne"></div></div>
    <div class="guest-book-marquee marquee-right"><div class="guest-marquee-track" id="guestMarqueeTwo"></div></div>
</section>

<section class="dashboard-table-area motion-reveal" data-reveal-delay="5">
    <div class="dashboard-table">
        <h2>Sách được mượn nhiều</h2>
        <table><thead><tr><th>Tên sách</th><th>Tác giả</th><th>Lượt mượn</th></tr></thead><tbody id="popularBooksBody"><tr><td>--</td><td>--</td><td>--</td></tr></tbody></table>
        <div class="view-all"><a href="#" data-view-all="xemtatca.php?type=popular" data-title="Sách được mượn nhiều">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a></div>
    </div>
    <div class="dashboard-table">
        <h2>Sách mới nhập</h2>
        <table><thead><tr><th>Tên sách</th><th>Tác giả</th><th>Số lượng</th></tr></thead><tbody id="newBooksBody"><tr><td>--</td><td>--</td><td>--</td></tr></tbody></table>
        <div class="view-all"><a href="#" data-view-all="xemtatca.php?type=new" data-title="Sách mới nhập">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a></div>
    </div>
</section>

<section class="guest-login-callout guest-motion-cta motion-reveal" data-reveal-delay="6">
    <div class="cta-glow"></div>
    <div><i class="fa-solid fa-book-reader"></i><strong>Sẵn sàng bắt đầu hành trình đọc sách?</strong><span>Đăng nhập nếu đã có tài khoản, hoặc tạo tài khoản Khách hàng để mượn và đặt lịch sách.</span></div>
    <div><a href="dangnhap.php">Đăng nhập</a><a href="dangky.php" class="primary">Đăng ký ngay <i class="fa-solid fa-arrow-right"></i></a></div>
</section>
</div>
<?php else: ?>

<?php if ($isStaffHome): ?>
<div class="staff-motion-home">
<section class="staff-hero motion-reveal is-visible">
  <div class="staff-hero-bg staff-orb-a"></div><div class="staff-hero-bg staff-orb-b"></div>
  <div class="staff-hero-copy">
    <div class="staff-kicker"><i class="fa-solid fa-chart-line"></i> DASHBOARD VẬN HÀNH</div>
    <h1>Tổng quan thư viện hôm nay</h1>
    <p>Theo dõi tình trạng sách, độc giả, mượn trả, cảnh báo và hoạt động vận hành trên cùng một màn hình.</p>
    <div class="staff-hero-badges">
      <span id="staffTodayLabel"><i class="fa-regular fa-calendar"></i> Hôm nay</span>
      <span class="system-online"><i class="fa-solid fa-circle-check"></i> Hệ thống đang hoạt động</span>
    </div>
  </div>
  <div class="staff-hero-panel">
    <div class="staff-hero-panel-head"><span>Nhịp hoạt động hôm nay</span><strong id="staffSystemStatus">Đang kết nối</strong></div>
    <div class="staff-ring-wrap">
      <div class="staff-ring" id="staffActivityRing"><div><strong id="staffTodayTotal">0</strong><span>lượt xử lý</span></div></div>
      <div class="staff-today-list">
        <div><span><i class="fa-solid fa-arrow-right-to-bracket"></i> Mượn hôm nay</span><b id="todayBorrowed">0</b></div>
        <div><span><i class="fa-solid fa-arrow-right-from-bracket"></i> Trả hôm nay</span><b id="todayReturned">0</b></div>
        <div><span><i class="fa-solid fa-triangle-exclamation"></i> Quá hạn hiện tại</span><b id="todayOverdue">0</b></div>
      </div>
    </div>
    <div class="staff-system-mini"><span><i class="fa-solid fa-database"></i> Database <b id="staffDbState">--</b></span><span><i class="fa-brands fa-php"></i> PHP <b id="staffPhpState">--</b></span></div>
  </div>
</section>

<section class="cards staff-stat-grid motion-reveal is-visible">
    <div class="dashboard-card staff-stat-card stat-blue" data-dashboard-target="sach.php"><div class="staff-stat-main"><span class="staff-stat-label">Tổng số sách</span><h3 id="totalBooks">0</h3><small>Kho sách hiện tại</small></div><i class="fa-solid fa-book-open"></i><div class="staff-sparkline" data-spark="books"></div></div>
    <div class="dashboard-card staff-stat-card stat-green" data-dashboard-target="docgia.php"><div class="staff-stat-main"><span class="staff-stat-label">Độc giả</span><h3 id="totalReaders">0</h3><small>Tài khoản độc giả</small></div><i class="fa-solid fa-users"></i><div class="staff-sparkline" data-spark="readers"></div></div>
    <div class="dashboard-card staff-stat-card stat-orange" data-dashboard-target="muontra.php"><div class="staff-stat-main"><span class="staff-stat-label">Đang mượn</span><h3 id="borrowingBooks">0</h3><small>Cuốn chưa hoàn trả</small></div><i class="fa-solid fa-book-open-reader"></i><div class="staff-sparkline" data-spark="borrowing"></div></div>
    <div class="dashboard-card staff-stat-card stat-red" data-dashboard-target="quahan.php"><div class="staff-stat-main"><span class="staff-stat-label">Quá hạn</span><h3 id="overdueBooks">0</h3><small>Cần ưu tiên xử lý</small></div><i class="fa-solid fa-triangle-exclamation"></i><div class="staff-sparkline" data-spark="overdue"></div></div>
</section>

<section class="staff-overview-grid motion-reveal is-visible">
  <article class="staff-panel staff-alert-panel">
    <div class="staff-panel-head"><div><span>ƯU TIÊN</span><h2>Trung tâm cảnh báo</h2></div><i class="fa-solid fa-bell"></i></div>
    <div id="managerAlerts" class="staff-alert-list"><div class="staff-empty">Đang tải cảnh báo...</div></div>
  </article>
  <article class="staff-panel staff-activity-panel">
    <div class="staff-panel-head"><div><span>THEO THỜI GIAN</span><h2>Hoạt động gần đây</h2></div><i class="fa-solid fa-clock-rotate-left"></i></div>
    <div id="staffRecentActivity" class="staff-timeline"><div class="staff-empty">Đang tải hoạt động...</div></div>
  </article>
</section>

<section class="staff-books-grid motion-reveal is-visible">
  <article class="staff-panel staff-book-panel">
    <div class="staff-panel-head"><div><span>XẾP HẠNG</span><h2>Sách được mượn nhiều</h2></div><a href="#" data-view-all="xemtatca.php?type=popular" data-title="Sách được mượn nhiều">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a></div>
    <div id="popularBooksBody" class="staff-book-ranking"><div class="staff-empty">Đang tải sách...</div></div>
  </article>
  <article class="staff-panel staff-book-panel">
    <div class="staff-panel-head"><div><span>MỚI NHẬP</span><h2>Sách mới nhập</h2></div><a href="#" data-view-all="xemtatca.php?type=new" data-title="Sách mới nhập">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a></div>
    <div id="newBooksBody" class="staff-book-ranking"><div class="staff-empty">Đang tải sách...</div></div>
  </article>
</section>

<section class="staff-panel staff-chart-panel motion-reveal is-visible">
  <div class="staff-panel-head"><div><span>7 NGÀY GẦN NHẤT</span><h2>Xu hướng mượn – trả – quá hạn</h2></div><div class="staff-chart-legend"><span><i class="dot borrow"></i>Mượn</span><span><i class="dot returned"></i>Trả</span><span><i class="dot overdue"></i>Quá hạn</span></div></div>
  <div class="staff-chart-wrap"><canvas id="staffSevenDayChart" height="110"></canvas></div>
</section>

<section class="staff-panel staff-advanced-panel motion-reveal is-visible" id="managerAdvancedDashboard">
  <div class="staff-panel-head"><div><span>VẬN HÀNH NÂNG CAO</span><h2>Dashboard quản lý nâng cao</h2><p>Dữ liệu lấy trực tiếp từ hệ thống hiện tại.</p></div><button type="button" class="advanced-link-btn" onclick="loadPage('nangcao.php','Thư viện thông minh')">Mở toàn bộ</button></div>
  <div class="advanced-metrics staff-metric-grid" id="managerAdvancedMetrics"><div class="staff-empty">Đang tải...</div></div>
</section>

<section class="staff-panel staff-quick-panel motion-reveal is-visible">
  <div class="staff-panel-head"><div><span>THAO TÁC NHANH</span><h2>Truy cập nhanh</h2></div><i class="fa-solid fa-bolt"></i></div>
  <div class="staff-quick-grid" id="staffQuickActions">
    <button type="button" onclick="openSmartTab('inventory','Kho & kiểm kê')"><i class="fa-solid fa-boxes-stacked"></i><span>Kho & kiểm kê</span><small>Theo dõi tồn kho</small></button>
    <button type="button" onclick="openSmartTab('audit','Nhật ký hoạt động')"><i class="fa-solid fa-clock-rotate-left"></i><span>Nhật ký hoạt động</span><small>Xem lịch sử hệ thống</small></button>
    <button type="button" onclick="loadPage('calamviec.php','Ca làm việc')"><i class="fa-solid fa-calendar-days"></i><span>Ca làm việc</span><small>Phân công & đăng ký</small></button>
    <button type="button" onclick="loadPage('muontra.php','Mượn - Trả')"><i class="fa-solid fa-right-left"></i><span>Mượn – Trả</span><small>Xử lý phiếu sách</small></button>
    <button type="button" onclick="loadPage('sach.php','Sách')"><i class="fa-solid fa-book"></i><span>Quản lý sách</span><small>Kho đầu sách</small></button>
    <button type="button" data-staff-statistics onclick="loadPage('thongke.php','Thống kê')"><i class="fa-solid fa-chart-pie"></i><span>Thống kê</span><small>Báo cáo & phân tích</small></button>
  </div>
</section>

<section class="staff-panel staff-recent-panel motion-reveal is-visible">
  <div class="staff-panel-head"><div><span>MỚI NHẤT</span><h2>Phiếu mượn gần đây</h2></div><a href="#" data-view-all="xemtatca.php?type=recent" data-title="Phiếu mượn gần đây">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a></div>
  <div class="staff-loan-table-wrap">
    <table class="staff-loan-table"><thead><tr><th>Mã phiếu</th><th>Độc giả</th><th>Sách</th><th>Ngày mượn</th><th>Trạng thái</th><th></th></tr></thead><tbody id="recentLoansBody"><tr><td colspan="6">Đang tải dữ liệu...</td></tr></tbody></table>
  </div>
</section>
</div>

<?php else: ?>
<?php $readerDisplayName = $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Bạn'; ?>
<div class="reader-home" data-reader-home>
<section class="reader-hero reader-reveal">
  <div class="reader-hero-orb orb-a"></div><div class="reader-hero-orb orb-b"></div>
  <div class="reader-hero-copy">
    <span class="reader-kicker"><i class="fa-solid fa-book-open-reader"></i> KHÔNG GIAN ĐỌC CỦA BẠN</span>
    <h1>Chào mừng trở lại, <span><?= htmlspecialchars($readerDisplayName, ENT_QUOTES, 'UTF-8') ?></span> 👋</h1>
    <p>Hôm nay mình đọc gì? Khám phá sách mới, tiếp tục những cuốn đang mượn và theo dõi hành trình đọc của riêng bạn.</p>
    <div class="reader-hero-actions">
      <button type="button" class="primary" onclick="loadPage('sach.php','Sách')"><i class="fa-solid fa-compass"></i> Khám phá sách</button>
      <button type="button" onclick="loadPage('muontra.php','Mượn sách')"><i class="fa-solid fa-book-open"></i> Mượn sách</button>
      <button type="button" onclick="openSmartTab('discover','Thư viện thông minh')"><i class="fa-solid fa-heart"></i> Muốn đọc</button>
    </div>
    <div class="reader-live-note" id="readerLiveNote"><i class="fa-solid fa-sparkles"></i><span>Đang chuẩn bị gợi ý cho bạn...</span></div>
  </div>
  <div class="reader-hero-visual" aria-hidden="true">
    <div class="reader-visual-glow"></div>
    <div class="reader-book-stack">
      <div class="reader-stack-book rb-1"><span>ĐỌC</span></div>
      <div class="reader-stack-book rb-2"><span>KHÁM PHÁ</span></div>
      <div class="reader-stack-book rb-3"><span>TRI THỨC</span></div>
    </div>
    <div class="reader-floating-card reader-float-a"><i class="fa-solid fa-bookmark"></i><span>Danh sách<br>muốn đọc</span></div>
    <div class="reader-floating-card reader-float-b"><i class="fa-solid fa-fire"></i><strong id="readerStreakHero">--</strong><span>nhịp đọc</span></div>
  </div>
</section>

<section class="reader-metrics reader-reveal">
  <article class="reader-metric rm-blue" data-dashboard-target="sach.php"><div><span>Tổng sách thư viện</span><strong id="totalBooks">0</strong><small>Kho tri thức đang chờ bạn</small></div><i class="fa-solid fa-books"></i><div class="reader-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
  <article class="reader-metric rm-violet" data-dashboard-target="muontra.php"><div><span>Phiếu của tôi</span><strong id="totalReaders">0</strong><small>Lịch sử mượn sách</small></div><i class="fa-solid fa-receipt"></i><div class="reader-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
  <article class="reader-metric rm-emerald" data-dashboard-target="muontra.php"><div><span>Đang mượn</span><strong id="borrowingBooks">0</strong><small>Cuốn đang trong hành trình</small></div><i class="fa-solid fa-book-open-reader"></i><div class="reader-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
  <article class="reader-metric rm-rose" data-dashboard-target="muontra.php"><div><span>Quá hạn của tôi</span><strong id="overdueBooks">0</strong><small id="readerOverdueSub">Không có cảnh báo</small></div><i class="fa-solid fa-triangle-exclamation"></i><div class="reader-spark"><b></b><b></b><b></b><b></b><b></b></div></article>
</section>

<section class="reader-journey reader-reveal">
  <div class="reader-section-head"><div><span>ĐANG ĐỌC</span><h2>Tiếp tục hành trình đọc</h2><p>Sách bạn đang mượn và thời gian còn lại trước hạn trả.</p></div><button type="button" onclick="loadPage('muontra.php','Mượn sách')">Xem phiếu của tôi <i class="fa-solid fa-arrow-right"></i></button></div>
  <div class="reader-loan-carousel" id="readerCurrentLoans"><div class="reader-empty">Đang tải sách đang mượn...</div></div>
  <div class="reader-alert-strip d-none" id="readerDueAlert"></div>
</section>

<section class="reader-book-section reader-reveal">
  <div class="reader-section-head"><div><span>DÀNH CHO BẠN</span><h2>Có thể bạn sẽ thích</h2><p>Gợi ý dựa trên thể loại và lịch sử đọc thực tế của bạn.</p></div><button type="button" onclick="openSmartTab('discover','Thư viện thông minh')">Xem tất cả <i class="fa-solid fa-arrow-right"></i></button></div>
  <div class="reader-book-carousel" id="homeRecommendations"><div class="reader-empty">Đang chuẩn bị gợi ý...</div></div>
</section>

<section class="reader-for-you-hub reader-reveal" id="readerForYouHub">
  <div class="reader-section-head"><div><span>FOR YOU</span><h2>Gợi ý được cá nhân hóa sâu hơn</h2><p>Được suy ra từ lịch sử mượn và danh sách muốn đọc của chính bạn.</p></div><button type="button" onclick="loadPage('sach.php','Sách')">Khám phá thêm <i class="fa-solid fa-arrow-right"></i></button></div>
  <div class="reader-personal-row"><div class="reader-personal-label"><i class="fa-solid fa-heart"></i><div><strong id="readerBecauseTitle">Vì bạn thích...</strong><span>Sách cùng gu đọc gần đây</span></div></div><div class="reader-book-carousel compact" id="readerBecauseShelf"><div class="reader-empty">Đang phân tích gu đọc...</div></div></div>
  <div class="reader-personal-row"><div class="reader-personal-label"><i class="fa-solid fa-bookmark"></i><div><strong>Tiếp theo nên đọc</strong><span>Ưu tiên sách bạn đã lưu và đang có sẵn</span></div></div><div class="reader-book-carousel compact" id="readerNextShelf"><div class="reader-empty">Đang chuẩn bị...</div></div></div>
  <div class="reader-personal-row"><div class="reader-personal-label"><i class="fa-solid fa-people-group"></i><div><strong>Độc giả cùng gu cũng đọc</strong><span>Dựa trên lượt mượn thật trong cùng thể loại bạn yêu thích</span></div></div><div class="reader-book-carousel compact" id="readerSimilarShelf"><div class="reader-empty">Đang tổng hợp...</div></div></div>
</section>

<section class="reader-split reader-reveal">
  <article class="reader-book-panel">
    <div class="reader-section-head compact"><div><span>ĐƯỢC YÊU THÍCH</span><h2>Sách được mượn nhiều</h2></div><a href="#" data-view-all="xemtatca.php?type=popular" data-title="Sách được mượn nhiều">Xem tất cả</a></div>
    <div class="reader-mini-carousel" id="popularBooksBody"><div class="reader-empty">Đang tải...</div></div>
  </article>
  <article class="reader-book-panel">
    <div class="reader-section-head compact"><div><span>MỚI TRÊN KỆ</span><h2>Sách mới nhập</h2></div><a href="#" data-view-all="xemtatca.php?type=new" data-title="Sách mới nhập">Xem tất cả</a></div>
    <div class="reader-mini-carousel" id="newBooksBody"><div class="reader-empty">Đang tải...</div></div>
  </article>
</section>

<section class="reader-wishlist-zone reader-reveal">
  <div class="reader-section-head"><div><span>YÊU THÍCH</span><h2>Danh sách muốn đọc</h2><p>Những cuốn bạn đã đánh dấu để quay lại sau.</p></div><button type="button" onclick="openSmartTab('discover','Thư viện thông minh')">Quản lý danh sách <i class="fa-solid fa-heart"></i></button></div>
  <div class="reader-book-carousel" id="homeWishlist"><div class="reader-empty">Đang tải danh sách...</div></div>
</section>

<section class="reader-profile-zone reader-reveal">
  <article class="reader-reading-profile">
    <div class="reader-section-head compact"><div><span>HỒ SƠ ĐỌC</span><h2>Hành trình đọc của tôi</h2></div><button type="button" onclick="openSmartTab('profile','Thư viện thông minh')">Xem chi tiết</button></div>
    <div id="homeReadingProfile"><div class="reader-empty">Đang phân tích thói quen đọc...</div></div>
  </article>
  <article class="reader-genres-panel">
    <div class="reader-section-head compact"><div><span>KHÁM PHÁ</span><h2>Thể loại dành cho bạn</h2></div></div>
    <div class="reader-genre-scroller" id="readerGenreChips"><span class="reader-genre-chip">Đang tải...</span></div>
  </article>
</section>

<section class="reader-reading-journey reader-reveal">
  <div class="reader-section-head"><div><span>READING JOURNEY</span><h2>Dòng thời gian đọc của tôi</h2><p>Từ lúc mượn đến khi hoàn trả, cùng những cột mốc đọc đã đạt được.</p></div><button type="button" onclick="openSmartTab('profile','Thư viện thông minh')">Hồ sơ đọc <i class="fa-solid fa-arrow-right"></i></button></div>
  <div class="reader-journey-grid"><div class="reader-journey-timeline" id="readerJourneyTimeline"><div class="reader-empty">Đang tải hành trình...</div></div><aside class="reader-journey-summary"><div class="reader-month-bars" id="readerJourneyMonths"></div><div class="reader-milestones" id="readerMilestones"></div></aside></div>
</section>

<section class="reader-history reader-reveal">
  <div class="reader-section-head"><div><span>GẦN ĐÂY</span><h2>Phiếu mượn của tôi</h2><p>Theo dõi nhanh những lần mượn sách mới nhất.</p></div><a href="#" data-view-all="xemtatca.php?type=recent" data-title="Phiếu mượn gần đây">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a></div>
  <div class="reader-timeline" id="recentLoansBody"><div class="reader-empty">Đang tải lịch sử...</div></div>
</section>

<section class="reader-final-cta reader-reveal">
  <div><span>CUỐN TIẾP THEO ĐANG CHỜ BẠN</span><h2>Tìm cuốn sách tiếp theo của bạn</h2><p>Khám phá kho sách theo thể loại, tác giả hoặc những gợi ý được cá nhân hóa.</p></div>
  <button type="button" onclick="loadPage('sach.php','Sách')">Khám phá thư viện <i class="fa-solid fa-arrow-right"></i></button>
</section>
</div>
<?php endif; ?>

<?php endif; ?>
