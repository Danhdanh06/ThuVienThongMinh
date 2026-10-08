<?php
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../auth.php';
requireLibraryPage('thongtinthuvien');

$info = $conn->query("SELECT TenThuVien, DiaChi, Email, SDT FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc() ?: [];

function libraryInfoValue(array $info, string $key, string $fallback = 'Chưa cập nhật'): string
{
    $value = trim((string)($info[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
}
function libraryInfoInt(mysqli $conn, string $sql): int
{
    try {
        $row = $conn->query($sql)->fetch_row();
        return (int)($row[0] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

$name = libraryInfoValue($info, 'TenThuVien', 'Thư viện');
$address = libraryInfoValue($info, 'DiaChi');
$email = libraryInfoValue($info, 'Email');
$phone = libraryInfoValue($info, 'SDT');
$phoneHref = preg_replace('/[^0-9+]/', '', $phone);
$mapQuery = rawurlencode($address !== 'Chưa cập nhật' ? $address : $name);
$mapUrl = "https://www.google.com/maps/search/?api=1&query={$mapQuery}";
$mapEmbed = "https://www.google.com/maps?q={$mapQuery}&output=embed";

$stats = [
    'books' => libraryInfoInt($conn, "SELECT COALESCE(SUM(SoLuong),0) FROM sach"),
    'titles' => libraryInfoInt($conn, "SELECT COUNT(*) FROM sach"),
    'categories' => libraryInfoInt($conn, "SELECT COUNT(*) FROM theloai"),
    'readers' => libraryInfoInt($conn, "SELECT COUNT(*) FROM docgia"),
    'activeLoans' => libraryInfoInt($conn, "SELECT COUNT(*) FROM phieumuon WHERE NgayTra IS NULL AND COALESCE(TrangThai,'') NOT IN ('Đã trả','Đã hủy','Hủy','Chờ duyệt')")
];

$tz = new DateTimeZone('Asia/Ho_Chi_Minh');
$now = new DateTime('now', $tz);
$weekday = (int)$now->format('N'); // 1-7
$minutes = ((int)$now->format('H')) * 60 + (int)$now->format('i');
$isOpenDay = $weekday >= 1 && $weekday <= 6;
$isOpen = $isOpenDay && $minutes >= 7 * 60 && $minutes < 21 * 60;
if ($isOpen) {
    $openText = 'Đang mở cửa';
    $openNote = 'Hôm nay mở đến 21:00';
} else {
    $openText = 'Hiện đang đóng cửa';
    if ($weekday === 7) {
        $openNote = 'Mở lại lúc 07:00 Thứ 2';
    } elseif ($minutes < 7 * 60) {
        $openNote = 'Mở lại lúc 07:00 hôm nay';
    } elseif ($weekday === 6) {
        $openNote = 'Mở lại lúc 07:00 Thứ 2';
    } else {
        $openNote = 'Mở lại lúc 07:00 ngày mai';
    }
}
$days = [
    ['Thứ 2', '07:00 – 21:00', 1], ['Thứ 3', '07:00 – 21:00', 2], ['Thứ 4', '07:00 – 21:00', 3],
    ['Thứ 5', '07:00 – 21:00', 4], ['Thứ 6', '07:00 – 21:00', 5], ['Thứ 7', '07:00 – 21:00', 6], ['Chủ nhật', 'Nghỉ', 7]
];
?>
<div class="libinfo-page" data-library-open="<?= $isOpen ? '1' : '0' ?>">
  <section class="libprofile-hero libinfo-reveal">
    <div class="libprofile-copy">
      <div class="libinfo-badge"><i class="fa-solid fa-building-columns"></i> HỒ SƠ THƯ VIỆN</div>
      <h2><?= h($name) ?></h2>
      <p class="libprofile-lead">Không gian đọc sách, tra cứu và mượn sách hiện đại dành cho học tập, nghiên cứu và giải trí.</p>
      <div class="libprofile-status-row">
        <span class="libstatus-badge <?= $isOpen ? 'is-open' : 'is-closed' ?>"><i class="fa-solid fa-circle"></i> <?= h($openText) ?></span>
        <span class="libstatus-badge stable"><i class="fa-solid fa-shield-check"></i> Hệ thống hoạt động ổn định</span>
        <span class="libstatus-badge online"><i class="fa-solid fa-arrow-right-arrow-left"></i> Hỗ trợ mượn – trả trực tuyến</span>
      </div>
      <div class="libprofile-actions">
        <?php if (filter_var($email, FILTER_VALIDATE_EMAIL)): ?>
          <a class="libinfo-btn" href="mailto:<?= h($email) ?>"><i class="fa-solid fa-envelope"></i> Gửi email</a>
        <?php endif; ?>
        <?php if ($phone !== 'Chưa cập nhật'): ?>
          <a class="libinfo-btn light" href="tel:<?= h($phoneHref) ?>"><i class="fa-solid fa-phone"></i> Gọi thư viện</a>
        <?php endif; ?>
        <a class="libinfo-btn ghost" href="<?= h($mapUrl) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-location-arrow"></i> Xem bản đồ</a>
      </div>
      <div class="libprofile-open-note"><i class="fa-regular fa-clock"></i><span><?= h($openNote) ?></span></div>
    </div>

    <div class="libprofile-visual" aria-hidden="true">
      <div class="libprofile-glow glow-a"></div><div class="libprofile-glow glow-b"></div>
      <div class="libprofile-shelf">
        <div class="shelf-book b1">ĐỌC</div><div class="shelf-book b2">HỌC</div><div class="shelf-book b3">KHÁM PHÁ</div><div class="shelf-book b4">TRI THỨC</div>
        <div class="shelf-line"></div>
      </div>
      <div class="libprofile-mini-grid">
        <div class="libprofile-mini-card"><span>Trạng thái</span><strong><?= $isOpen ? 'Đang mở cửa' : 'Đang đóng cửa' ?></strong></div>
        <div class="libprofile-mini-card"><span>Kho sách</span><strong><?= number_format($stats['books'], 0, ',', '.') ?> cuốn</strong></div>
        <div class="libprofile-mini-card"><span>Thể loại</span><strong><?= number_format($stats['categories'], 0, ',', '.') ?> thể loại</strong></div>
        <div class="libprofile-mini-card"><span>Đầu sách</span><strong><?= number_format($stats['titles'], 0, ',', '.') ?> đầu sách</strong></div>
      </div>
      <i class="fa-solid fa-book-open-reader libprofile-watermark"></i>
    </div>
  </section>

  <section class="libstats-grid libinfo-reveal">
    <article class="libstat-card blue"><i class="fa-solid fa-books"></i><div><strong data-count="<?= $stats['books'] ?>">0</strong><span>Cuốn sách</span></div></article>
    <article class="libstat-card indigo"><i class="fa-solid fa-book"></i><div><strong data-count="<?= $stats['titles'] ?>">0</strong><span>Đầu sách</span></div></article>
    <article class="libstat-card violet"><i class="fa-solid fa-layer-group"></i><div><strong data-count="<?= $stats['categories'] ?>">0</strong><span>Thể loại</span></div></article>
    <article class="libstat-card green"><i class="fa-solid fa-users"></i><div><strong data-count="<?= $stats['readers'] ?>">0</strong><span>Độc giả</span></div></article>
    <article class="libstat-card amber"><i class="fa-solid fa-book-open"></i><div><strong data-count="<?= $stats['activeLoans'] ?>">0</strong><span>Phiếu đang mượn</span></div></article>
  </section>

  <section class="libinfo-contact-map libinfo-reveal">
    <article class="libinfo-panel contact-panel">
      <div class="libinfo-panel-head">
        <div class="libinfo-panel-icon"><i class="fa-solid fa-address-card"></i></div>
        <div><h3>Thông tin liên hệ</h3><p>Thông tin chính thức và thao tác nhanh.</p></div>
      </div>
      <div class="libinfo-list">
        <div class="libinfo-item">
          <div class="libinfo-item-icon blue"><i class="fa-solid fa-book-open-reader"></i></div>
          <div class="libinfo-item-copy"><span>Tên thư viện</span><strong><?= h($name) ?></strong><button type="button" class="libmini-action" data-copy="<?= h($name) ?>"><i class="fa-regular fa-copy"></i> Sao chép</button></div>
        </div>
        <div class="libinfo-item">
          <div class="libinfo-item-icon pink"><i class="fa-solid fa-location-dot"></i></div>
          <div class="libinfo-item-copy"><span>Địa chỉ</span><strong><?= h($address) ?></strong><div class="libmini-actions"><a class="libmini-action" href="<?= h($mapUrl) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i> Mở bản đồ</a><button type="button" class="libmini-action" data-copy="<?= h($address) ?>"><i class="fa-regular fa-copy"></i> Sao chép</button></div></div>
        </div>
        <div class="libinfo-item">
          <div class="libinfo-item-icon green"><i class="fa-solid fa-envelope"></i></div>
          <div class="libinfo-item-copy"><span>Email</span><strong><?= h($email) ?></strong><div class="libmini-actions"><?php if (filter_var($email, FILTER_VALIDATE_EMAIL)): ?><a class="libmini-action" href="mailto:<?= h($email) ?>"><i class="fa-solid fa-paper-plane"></i> Gửi email</a><?php endif; ?><button type="button" class="libmini-action" data-copy="<?= h($email) ?>"><i class="fa-regular fa-copy"></i> Sao chép</button></div></div>
        </div>
        <div class="libinfo-item">
          <div class="libinfo-item-icon amber"><i class="fa-solid fa-phone"></i></div>
          <div class="libinfo-item-copy"><span>Số điện thoại</span><strong><?= h($phone) ?></strong><div class="libmini-actions"><?php if ($phone !== 'Chưa cập nhật'): ?><a class="libmini-action" href="tel:<?= h($phoneHref) ?>"><i class="fa-solid fa-phone-volume"></i> Gọi ngay</a><?php endif; ?><button type="button" class="libmini-action" data-copy="<?= h($phone) ?>"><i class="fa-regular fa-copy"></i> Sao chép</button></div></div>
        </div>
      </div>
    </article>

    <article class="libinfo-panel map-panel">
      <div class="libinfo-panel-head">
        <div class="libinfo-panel-icon map"><i class="fa-solid fa-map-location-dot"></i></div>
        <div><h3>Vị trí thư viện</h3><p>Xem nhanh vị trí và mở chỉ đường khi cần.</p></div>
      </div>
      <div class="libmap-frame"><iframe title="Bản đồ vị trí thư viện" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="<?= h($mapEmbed) ?>"></iframe></div>
      <div class="libmap-footer"><span><i class="fa-solid fa-location-dot"></i> <?= h($address) ?></span><a href="<?= h($mapUrl) ?>" target="_blank" rel="noopener">Xem đường đi <i class="fa-solid fa-arrow-up-right-from-square"></i></a></div>
    </article>
  </section>

  <section class="libinfo-duo libinfo-reveal">
    <article class="libinfo-panel hours-panel">
      <div class="libinfo-panel-head"><div class="libinfo-panel-icon purple"><i class="fa-solid fa-clock"></i></div><div><h3>Giờ mở cửa</h3><p>Lịch phục vụ trong tuần.</p></div></div>
      <div class="libhours-status <?= $isOpen ? 'open' : 'closed' ?>"><span><i class="fa-solid fa-circle"></i> <?= h($openText) ?></span><strong><?= h($openNote) ?></strong></div>
      <div class="libhours-list">
        <?php foreach ($days as [$dayName, $timeText, $dayNumber]): ?>
          <div class="libhours-row <?= $weekday === $dayNumber ? 'today' : '' ?>"><span><?= h($dayName) ?><?= $weekday === $dayNumber ? ' · Hôm nay' : '' ?></span><strong><?= h($timeText) ?></strong></div>
        <?php endforeach; ?>
      </div>
    </article>

    <article class="libinfo-panel about-panel">
      <div class="libinfo-panel-head"><div class="libinfo-panel-icon teal"><i class="fa-solid fa-landmark"></i></div><div><h3>Giới thiệu thư viện</h3><p>Không gian học tập và tra cứu dành cho cộng đồng.</p></div></div>
      <p class="libabout-lead">Thư viện <?= h($name) ?> là không gian học tập và tra cứu dành cho học sinh, sinh viên và người yêu sách. Hệ thống hỗ trợ quản lý đầu sách, mượn – trả, theo dõi tình trạng sách và tra cứu nhanh ngay trên nền tảng số.</p>
      <div class="libabout-points">
        <div><i class="fa-solid fa-bullseye"></i><span><strong>Sứ mệnh</strong><small>Kết nối người đọc với nguồn tri thức thuận tiện, rõ ràng và dễ tiếp cận.</small></span></div>
        <div><i class="fa-solid fa-user-group"></i><span><strong>Đối tượng phục vụ</strong><small>Học sinh, sinh viên, người học và độc giả yêu thích đọc sách.</small></span></div>
        <div><i class="fa-solid fa-wand-magic-sparkles"></i><span><strong>Dịch vụ chính</strong><small>Tra cứu, mượn – trả, đặt trước và theo dõi lịch sử sử dụng thư viện.</small></span></div>
      </div>
    </article>
  </section>

  <section class="libinfo-panel services-panel libinfo-reveal">
    <div class="libinfo-panel-head"><div class="libinfo-panel-icon services"><i class="fa-solid fa-grid-2"></i></div><div><h3>Dịch vụ thư viện</h3><p>Các tiện ích chính đang được hỗ trợ trên hệ thống.</p></div></div>
    <div class="libservices-grid">
      <article><i class="fa-solid fa-magnifying-glass"></i><h4>Tra cứu sách</h4><p>Tìm nhanh theo tên sách, tác giả và thể loại.</p><button type="button" onclick="loadPage('sach.php','Sách')">Khám phá <i class="fa-solid fa-arrow-right"></i></button></article>
      <article><i class="fa-solid fa-arrow-right-arrow-left"></i><h4>Mượn – trả sách</h4><p>Theo dõi phiếu mượn, hạn trả và tình trạng xử lý.</p><button type="button" onclick="loadPage('muontra.php','Mượn - Trả')">Xem dịch vụ <i class="fa-solid fa-arrow-right"></i></button></article>
      <article><i class="fa-solid fa-calendar-check"></i><h4>Đặt trước sách</h4><p>Đăng ký trước những đầu sách đang được quan tâm.</p><button type="button" onclick="loadPage('muontra.php','Mượn - Trả')">Xem đặt trước <i class="fa-solid fa-arrow-right"></i></button></article>
      <article><i class="fa-solid fa-clock-rotate-left"></i><h4>Lịch sử mượn</h4><p>Theo dõi quá trình mượn và trả sách trên hệ thống.</p><button type="button" onclick="loadPage('muontra.php','Mượn - Trả')">Xem lịch sử <i class="fa-solid fa-arrow-right"></i></button></article>
    </div>
  </section>

  <section class="libspaces-section libinfo-reveal">
    <div class="libinfo-section-title"><div><span>KHÔNG GIAN</span><h3>Không gian thư viện</h3><p>Minh họa các khu vực phục vụ chính của thư viện.</p></div></div>
    <div class="libspaces-grid">
      <article class="libspace-card reading"><i class="fa-solid fa-book-open-reader"></i><div><span>Không gian đọc</span><strong>Đọc sách & học tập</strong></div></article>
      <article class="libspace-card counter"><i class="fa-solid fa-right-left"></i><div><span>Quầy dịch vụ</span><strong>Mượn – trả nhanh</strong></div></article>
      <article class="libspace-card shelves"><i class="fa-solid fa-books"></i><div><span>Kệ sách</span><strong>Phân loại dễ tìm</strong></div></article>
      <article class="libspace-card lookup"><i class="fa-solid fa-display"></i><div><span>Khu tra cứu</span><strong>Tìm sách trực tuyến</strong></div></article>
    </div>
  </section>

  <section class="libinfo-panel faq-panel libinfo-reveal">
    <div class="libinfo-panel-head"><div class="libinfo-panel-icon faq"><i class="fa-solid fa-circle-question"></i></div><div><h3>Câu hỏi thường gặp</h3><p>Một số thông tin người dùng thường quan tâm.</p></div></div>
    <div class="libfaq-list">
      <details><summary>Làm sao để đăng ký tài khoản độc giả?<i class="fa-solid fa-chevron-down"></i></summary><p>Bạn có thể dùng chức năng Đăng ký ở trang khách. Sau khi hoàn tất thông tin, hệ thống sẽ tạo tài khoản theo quy trình hiện có.</p></details>
      <details><summary>Khi nào được mượn sách?<i class="fa-solid fa-chevron-down"></i></summary><p>Độc giả có thể mượn sách khi tài khoản hợp lệ và đầu sách còn số lượng khả dụng theo quy định của hệ thống.</p></details>
      <details><summary>Có thể đặt trước sách không?<i class="fa-solid fa-chevron-down"></i></summary><p>Có. Khu Mượn – Trả hỗ trợ lịch đặt trước theo các chức năng đang được bật trong hệ thống.</p></details>
      <details><summary>Nếu trả sách trễ thì sao?<i class="fa-solid fa-chevron-down"></i></summary><p>Phiếu sẽ được đánh dấu quá hạn để thư viện và độc giả dễ theo dõi, xử lý theo quy định của thư viện.</p></details>
      <details><summary>Thư viện mở cửa những ngày nào?<i class="fa-solid fa-chevron-down"></i></summary><p>Thư viện phục vụ từ Thứ 2 đến Thứ 7, 07:00 – 21:00; Chủ nhật nghỉ.</p></details>
    </div>
  </section>

  <section class="libcta libinfo-reveal">
    <div><span class="libinfo-badge"><i class="fa-solid fa-sparkles"></i> BẮT ĐẦU NGAY</span><h3>Bạn muốn bắt đầu sử dụng thư viện?</h3><p>Khám phá kho sách, xem thể loại hoặc liên hệ thư viện khi cần hỗ trợ.</p></div>
    <div class="libcta-actions"><button type="button" class="libinfo-btn" onclick="loadPage('sach.php','Sách')"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu sách ngay</button><button type="button" class="libinfo-btn light" onclick="loadPage('theloai.php','Thể loại')"><i class="fa-solid fa-layer-group"></i> Xem thể loại</button><?php if (filter_var($email, FILTER_VALIDATE_EMAIL)): ?><a class="libinfo-btn ghost" href="mailto:<?= h($email) ?>"><i class="fa-solid fa-headset"></i> Liên hệ thư viện</a><?php endif; ?></div>
  </section>
</div>
