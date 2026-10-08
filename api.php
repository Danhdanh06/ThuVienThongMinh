<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require_once __DIR__ . '/database/connect.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database/legacy_migration.php';
require_once __DIR__ . '/database/mail_helper.php';

function respond(array $payload, int $status = 200): never
{
    if (($payload['ok'] ?? false) === true && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        try {
            global $conn, $action;
            $skip = [''];
            if ($conn instanceof mysqli && !in_array((string)$action, $skip, true)) {
                $uid = (int)($_SESSION['user_id'] ?? 0);
                $name = (string)($_SESSION['display_name'] ?? $_SESSION['username'] ?? '');
                $role = currentLibraryRoleKey();
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $detail = trim((string)($payload['message'] ?? ''));
                $stmt = $conn->prepare("INSERT INTO nhatkyhoatdong(MaTaiKhoan,NguoiThucHien,VaiTro,HanhDong,DoiTuong,ChiTiet,DiaChiIP) VALUES(NULLIF(?,0),?,?,?,NULL,?,?)");
                $act = (string)$action;
                $stmt->bind_param('isssss', $uid, $name, $role, $act, $detail, $ip);
                $stmt->execute();
            }
        } catch (Throwable $e) {}
    }
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ok(mixed $data = null, string $message = ''): never
{
    respond(['ok' => true, 'data' => $data, 'message' => $message]);
}

function fail(string $message, int $status = 400, mixed $details = null): never
{
    $payload = ['ok' => false, 'message' => $message];
    if ($details !== null) {
        $payload['details'] = $details;
    }
    respond($payload, $status);
}

function inputData(): array
{
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($type, 'application/json')) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

function strv(array $data, string $key, string $default = ''): string
{
    return trim((string)($data[$key] ?? $default));
}

function intv(array $data, string $key, int $default = 0): int
{
    return (int)($data[$key] ?? $default);
}

function nullableInt(array $data, string $key): ?int
{
    if (!isset($data[$key]) || $data[$key] === '' || $data[$key] === null) {
        return null;
    }
    return (int)$data[$key];
}

function nullableString(array $data, string $key): ?string
{
    $value = trim((string)($data[$key] ?? ''));
    return $value === '' ? null : $value;
}

function fetchAll(mysqli_result $result): array
{
    return $result->fetch_all(MYSQLI_ASSOC);
}


function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        fail('Vui lòng đăng nhập hoặc đăng ký tài khoản để sử dụng chức năng này.', 401);
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = inputData();

$publicReadActions = ['dashboard', 'dashboard_list', 'books', 'categories'];
$isAuthenticated = !empty($_SESSION['user_id']);

if ($isAuthenticated) {
    if (!syncLibrarySessionAccount($conn)) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
        fail('Tài khoản đã bị khóa, không còn tồn tại hoặc chưa được phân quyền hợp lệ.', 401);
    }
} elseif (!in_array($action, $publicReadActions, true)) {
    requireLogin();
}

$actionPermissions = [
    'legacy_status' => 'legacy_manage', 'legacy_import' => 'legacy_manage', 'legacy_seed' => 'legacy_manage',
    'dashboard' => 'dashboard_view', 'dashboard_list' => 'dashboard_view',
    'books' => 'books_view', 'book_save' => 'books_manage', 'book_delete' => 'books_delete',
    'categories' => 'categories_view', 'category_save' => 'categories_manage', 'category_delete' => 'categories_delete',
    'readers' => 'readers_view', 'reader_save' => 'readers_manage', 'reader_delete' => 'readers_delete',
    'employee_save' => 'employees_manage', 'employee_delete' => 'employees_delete',
    'loans' => 'loans_view', 'loan_approve' => 'loans_manage', 'loan_return' => 'loans_manage', 'loan_delete' => 'loans_delete',
    'reservation_action' => 'reservations_manage',
    'shift_save' => 'shifts_manage', 'shift_delete' => 'shifts_manage',
    'shift_assign' => 'shifts_manage', 'shift_assignment_delete' => 'shifts_manage',
    'statistics' => 'statistics_view', 'overdue_summary' => 'overdue_view',
    'settings' => 'settings_view', 'settings_save' => 'settings_manage',
    'email_test' => 'email_test', 'email_overdue' => 'email_overdue_send',
    'account_save' => 'accounts_manage', 'account_delete' => 'accounts_manage', 'account_toggle' => 'accounts_manage',
    'backup' => 'backup',
    'renewal_action' => 'renewals_manage'
];
if (isset($actionPermissions[$action]) && !hasLibraryPermission($actionPermissions[$action])) {
    fail('Bạn không có quyền thực hiện chức năng này.', 403);
}

try {
    switch ($action) {
        case 'legacy_status': {
            ok([
                'books' => legacyTableCount($conn, 'sach'),
                'loans' => legacyTableCount($conn, 'phieumuon'),
                'readers' => legacyTableCount($conn, 'docgia'),
                'staff' => legacyTableCount($conn, 'nhanvien'),
            ]);
        }
        case 'legacy_import': {
            $books = isset($data['books']) && is_array($data['books']) ? $data['books'] : [];
            $loans = isset($data['loans']) && is_array($data['loans']) ? $data['loans'] : [];
            $result = legacyImportData($conn, $books, $loans);
            ok($result, 'Đã khôi phục dữ liệu cũ vào MySQL.');
        }
        case 'legacy_seed': {
            $result = legacyImportData($conn, legacyDefaultBooks(), legacyDefaultLoans());
            ok($result, 'Đã khôi phục dữ liệu mẫu cũ vào MySQL.');
        }
        case 'dashboard': {
            $roleKey = currentLibraryRoleKey();
            $isGuest = $roleKey === 'guest';
            $isCustomer = $roleKey === 'customer';
            if ($isGuest) {
                $summary = $conn->query(
                    "SELECT
                        (SELECT COALESCE(SUM(SoLuong),0) FROM sach) AS totalBooks,
                        (SELECT COUNT(*) FROM sach) AS totalTitles,
                        (SELECT COUNT(*) FROM theloai) AS totalCategories,
                        (SELECT COUNT(*) FROM sach WHERE SoLuong > 0) AS availableTitles"
                )->fetch_assoc();
            } elseif ($isCustomer) {
                $readerId = (int)($_SESSION['reader_id'] ?? 0);
                if (!$readerId) {
                    ok(['rows' => [], 'summary' => ['ChoXacNhan' => 0, 'DaXacNhan' => 0, 'DaNhanSach' => 0, 'DaHuy' => 0]]);
                }
                $stmt = $conn->prepare(
                    "SELECT
                        (SELECT COALESCE(SUM(SoLuong),0) FROM sach) AS totalBooks,
                        (SELECT COUNT(*) FROM phieumuon WHERE MaDocGia=?) AS totalReaders,
                        (SELECT COALESCE(SUM(ct.SoLuong),0) FROM chitietphieumuon ct JOIN phieumuon pm ON pm.MaPhieuMuon=ct.MaPhieuMuon WHERE pm.MaDocGia=? AND pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy')) AS borrowingBooks,
                        (SELECT COALESCE(SUM(ct.SoLuong),0) FROM chitietphieumuon ct JOIN phieumuon pm ON pm.MaPhieuMuon=ct.MaPhieuMuon WHERE pm.MaDocGia=? AND pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy') AND pm.HanTra<CURDATE()) AS overdueBooks"
                );
                $stmt->bind_param('iii', $readerId, $readerId, $readerId);
                $stmt->execute();
                $summary = $stmt->get_result()->fetch_assoc();
            } else {
                $summary = $conn->query(
                    "SELECT
                        (SELECT COALESCE(SUM(SoLuong), 0) FROM sach) AS totalBooks,
                        (SELECT COUNT(*) FROM docgia) AS totalReaders,
                        (SELECT COALESCE(SUM(ct.SoLuong), 0)
                            FROM chitietphieumuon ct JOIN phieumuon pm ON pm.MaPhieuMuon = ct.MaPhieuMuon
                            WHERE pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy')) AS borrowingBooks,
                        (SELECT COALESCE(SUM(ct.SoLuong), 0)
                            FROM chitietphieumuon ct JOIN phieumuon pm ON pm.MaPhieuMuon = ct.MaPhieuMuon
                            WHERE pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy') AND pm.HanTra < CURDATE()) AS overdueBooks"
                )->fetch_assoc();
            }

            $popular = fetchAll($conn->query(
                "SELECT s.MaSach, s.TenSach, s.TacGia, s.HinhAnh, t.TenTheLoai, COALESCE(SUM(ct.SoLuong), 0) AS LuotMuon
                 FROM sach s LEFT JOIN theloai t ON t.MaTheLoai=s.MaTheLoai LEFT JOIN chitietphieumuon ct ON ct.MaSach = s.MaSach
                 GROUP BY s.MaSach, s.TenSach, s.TacGia, s.HinhAnh, t.TenTheLoai ORDER BY LuotMuon DESC, s.MaSach DESC LIMIT 3"
            ));
            $newBooks = fetchAll($conn->query(
                "SELECT s.MaSach, s.TenSach, s.TacGia, s.SoLuong, s.HinhAnh, t.TenTheLoai
                 FROM sach s LEFT JOIN theloai t ON t.MaTheLoai=s.MaTheLoai ORDER BY s.MaSach DESC LIMIT 3"
            ));

            if ($isGuest) {
                $recentLoans = [];
            } elseif ($isCustomer) {
                $stmt = $conn->prepare(
                    "SELECT pm.MaPhieuMuon, dg.HoTen AS DocGia, pm.NgayMuon, pm.HanTra, pm.NgayTra, pm.TrangThai,
                            GROUP_CONCAT(CONCAT(s.TenSach, ' (', ct.SoLuong, ')') ORDER BY s.TenSach SEPARATOR ', ') AS SachMuon
                     FROM phieumuon pm
                     LEFT JOIN docgia dg ON dg.MaDocGia=pm.MaDocGia
                     LEFT JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                     LEFT JOIN sach s ON s.MaSach=ct.MaSach
                     WHERE pm.MaDocGia=?
                     GROUP BY pm.MaPhieuMuon,dg.HoTen,pm.NgayMuon,pm.HanTra,pm.NgayTra,pm.TrangThai
                     ORDER BY pm.MaPhieuMuon DESC LIMIT 3"
                );
                $stmt->bind_param('i', $readerId); $stmt->execute(); $recentLoans=fetchAll($stmt->get_result());
            } else {
                $recentLoans = fetchAll($conn->query(
                    "SELECT pm.MaPhieuMuon, dg.HoTen AS DocGia, pm.NgayMuon, pm.HanTra, pm.NgayTra, pm.TrangThai,
                            GROUP_CONCAT(CONCAT(s.TenSach, ' (', ct.SoLuong, ')') ORDER BY s.TenSach SEPARATOR ', ') AS SachMuon
                     FROM phieumuon pm
                     LEFT JOIN docgia dg ON dg.MaDocGia=pm.MaDocGia
                     LEFT JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                     LEFT JOIN sach s ON s.MaSach=ct.MaSach
                     GROUP BY pm.MaPhieuMuon,dg.HoTen,pm.NgayMuon,pm.HanTra,pm.NgayTra,pm.TrangThai
                     ORDER BY pm.MaPhieuMuon DESC LIMIT 3"
                ));
            }
            ok(['summary'=>$summary,'popularBooks'=>$popular,'newBooks'=>$newBooks,'recentLoans'=>$recentLoans]);
        }

        case 'dashboard_list': {
            $type = strtolower(trim((string)($_GET['type'] ?? '')));

            if ($type === 'popular') {
                $rows = fetchAll($conn->query(
                    "SELECT s.MaSach, s.TenSach, s.TacGia, s.NhaXuatBan, s.NamXuatBan,
                            t.TenTheLoai, COALESCE(SUM(ct.SoLuong), 0) AS LuotMuon
                     FROM sach s
                     LEFT JOIN theloai t ON t.MaTheLoai = s.MaTheLoai
                     LEFT JOIN chitietphieumuon ct ON ct.MaSach = s.MaSach
                     GROUP BY s.MaSach, s.TenSach, s.TacGia, s.NhaXuatBan, s.NamXuatBan, t.TenTheLoai
                     ORDER BY LuotMuon DESC, s.MaSach DESC"
                ));
                ok(['type' => 'popular', 'title' => 'Sách được mượn nhiều', 'rows' => $rows]);
            }

            if ($type === 'new') {
                $rows = fetchAll($conn->query(
                    "SELECT s.MaSach, s.TenSach, s.TacGia, s.NhaXuatBan, s.NamXuatBan, s.SoLuong, t.TenTheLoai
                     FROM sach s
                     LEFT JOIN theloai t ON t.MaTheLoai = s.MaTheLoai
                     ORDER BY s.MaSach DESC"
                ));
                ok(['type' => 'new', 'title' => 'Sách mới nhập', 'rows' => $rows]);
            }

            if ($type === 'recent') {
                if (currentLibraryRoleKey() === 'guest') {
                    fail('Vui lòng đăng nhập để xem phiếu mượn.', 401);
                }
                if (currentLibraryRoleKey() === 'customer') {
                    $readerId = (int)($_SESSION['reader_id'] ?? 0);
                    if (!$readerId) fail('Tài khoản Độc giả chưa liên kết hồ sơ độc giả.');
                    $stmt = $conn->prepare(
                        "SELECT pm.MaPhieuMuon, dg.HoTen AS DocGia, nv.HoTen AS NhanVien,
                                pm.NgayMuon, pm.HanTra, pm.NgayTra, pm.TrangThai,
                                GROUP_CONCAT(CONCAT(s.TenSach, ' (', ct.SoLuong, ')') ORDER BY s.TenSach SEPARATOR ', ') AS SachMuon
                         FROM phieumuon pm
                         LEFT JOIN docgia dg ON dg.MaDocGia=pm.MaDocGia
                         LEFT JOIN nhanvien nv ON nv.MaNhanVien=pm.MaNhanVien
                         LEFT JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                         LEFT JOIN sach s ON s.MaSach=ct.MaSach
                         WHERE pm.MaDocGia=?
                         GROUP BY pm.MaPhieuMuon,dg.HoTen,nv.HoTen,pm.NgayMuon,pm.HanTra,pm.NgayTra,pm.TrangThai
                         ORDER BY pm.MaPhieuMuon DESC"
                    );
                    $stmt->bind_param('i', $readerId); $stmt->execute(); $rows=fetchAll($stmt->get_result());
                } else {
                    $rows = fetchAll($conn->query(
                        "SELECT pm.MaPhieuMuon, dg.HoTen AS DocGia, nv.HoTen AS NhanVien,
                                pm.NgayMuon, pm.HanTra, pm.NgayTra, pm.TrangThai,
                                GROUP_CONCAT(CONCAT(s.TenSach, ' (', ct.SoLuong, ')') ORDER BY s.TenSach SEPARATOR ', ') AS SachMuon
                         FROM phieumuon pm
                         LEFT JOIN docgia dg ON dg.MaDocGia = pm.MaDocGia
                         LEFT JOIN nhanvien nv ON nv.MaNhanVien = pm.MaNhanVien
                         LEFT JOIN chitietphieumuon ct ON ct.MaPhieuMuon = pm.MaPhieuMuon
                         LEFT JOIN sach s ON s.MaSach = ct.MaSach
                         GROUP BY pm.MaPhieuMuon, dg.HoTen, nv.HoTen, pm.NgayMuon, pm.HanTra, pm.NgayTra, pm.TrangThai
                         ORDER BY pm.MaPhieuMuon DESC"
                    ));
                }
                ok(['type' => 'recent', 'title' => 'Phiếu mượn gần đây', 'rows' => $rows]);
            }

            fail('Loại danh sách không hợp lệ.');
        }

        case 'books': {
            $books = fetchAll($conn->query(
                "SELECT s.MaSach, s.TenSach, s.TacGia, s.NhaXuatBan, s.NamXuatBan, s.SoLuong,
                        s.HinhAnh, s.MoTa, s.MaTheLoai, COALESCE(t.TenTheLoai, 'Chưa phân loại') AS TenTheLoai
                 FROM sach s
                 LEFT JOIN theloai t ON t.MaTheLoai = s.MaTheLoai
                 ORDER BY s.MaSach DESC"
            ));
            $categories = fetchAll($conn->query(
                "SELECT MaTheLoai, TenTheLoai FROM theloai ORDER BY TenTheLoai"
            ));
            ok(['books' => $books, 'categories' => $categories]);
        }

        case 'book_save': {
            $id = nullableInt($data, 'id');
            $title = strv($data, 'title');
            $author = nullableString($data, 'author');
            $publisher = nullableString($data, 'publisher');
            $year = nullableInt($data, 'year');
            $quantity = max(0, intv($data, 'quantity'));
            $cover = nullableString($data, 'cover');
            $description = nullableString($data, 'description');
            $categoryId = nullableInt($data, 'categoryId');

            if ($title === '') fail('Tên sách không được để trống.');
            if ($year !== null && ($year < 1000 || $year > (int)date('Y'))) fail('Năm xuất bản không hợp lệ.');

            if ($id) {
                $oldStmt = $conn->prepare("SELECT SoLuong FROM sach WHERE MaSach=? LIMIT 1");
                $oldStmt->bind_param('i', $id); $oldStmt->execute();
                $oldRow = $oldStmt->get_result()->fetch_assoc();
                if (!$oldRow) fail('Không tìm thấy sách.');
                $oldQuantity = (int)$oldRow['SoLuong'];
                $diff = $quantity - $oldQuantity;

                if ($diff < 0) {
                    $need = abs($diff);
                    $available = (int)$conn->query("SELECT COUNT(*) c FROM cuonsach WHERE MaSach=".(int)$id." AND TrangThai='Có sẵn'")->fetch_assoc()['c'];
                    if ($available < $need) fail('Không thể giảm số lượng vì có bản đang mượn/giữ chỗ. Hãy xử lý các cuốn đó trước.');
                }

                $stmt = $conn->prepare(
                    "UPDATE sach SET TenSach=?, TacGia=?, NhaXuatBan=?, NamXuatBan=?, SoLuong=?, HinhAnh=?, MoTa=?, MaTheLoai=?
                     WHERE MaSach=?"
                );
                $stmt->bind_param('sssiissii', $title, $author, $publisher, $year, $quantity, $cover, $description, $categoryId, $id);
                $stmt->execute();

                // Đồng bộ số bản vật lý theo phần chênh lệch, không đụng lịch sử cuốn đang mượn.
                if ($diff > 0) {
                    $count = (int)$conn->query("SELECT COUNT(*) c FROM cuonsach WHERE MaSach=".(int)$id)->fetch_assoc()['c'];
                    $ins = $conn->prepare("INSERT INTO cuonsach(MaSach,MaVach,TrangThai,NgayNhap) VALUES(?,?,'Có sẵn',CURDATE())");
                    for ($i=1; $i<=$diff; $i++) { $code=sprintf('TV-S%03d-%02d',$id,$count+$i); $ins->bind_param('is',$id,$code); $ins->execute(); }
                } elseif ($diff < 0) {
                    $need = abs($diff);
                    $rows = fetchAll($conn->query("SELECT MaCuon FROM cuonsach WHERE MaSach=".(int)$id." AND TrangThai='Có sẵn' ORDER BY MaCuon DESC LIMIT ".$need));
                    foreach ($rows as $row) { $cid=(int)$row['MaCuon']; $conn->query("UPDATE cuonsach SET TrangThai='Thanh lý' WHERE MaCuon=$cid"); }
                }
                ok(['id' => $id], 'Đã cập nhật sách.');
            }

            $stmt = $conn->prepare(
                "INSERT INTO sach (TenSach, TacGia, NhaXuatBan, NamXuatBan, SoLuong, HinhAnh, MoTa, MaTheLoai)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sssiissi', $title, $author, $publisher, $year, $quantity, $cover, $description, $categoryId);
            $stmt->execute();
            $newId = (int)$conn->insert_id;
            if ($quantity > 0) {
                $ins = $conn->prepare("INSERT INTO cuonsach(MaSach,MaVach,TrangThai,NgayNhap) VALUES(?,?,'Có sẵn',CURDATE())");
                for ($i=1; $i<=$quantity; $i++) { $code=sprintf('TV-S%03d-%02d',$newId,$i); $ins->bind_param('is',$newId,$code); $ins->execute(); }
            }
            ok(['id' => $newId], 'Đã thêm sách.');
        }

        case 'book_delete': {
            $id = intv($data, 'id');
            if (!$id) fail('Thiếu mã sách.');
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM chitietphieumuon WHERE MaSach=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $used = (int)$stmt->get_result()->fetch_assoc()['total'];
            if ($used > 0) fail('Không thể xóa sách đã có lịch sử mượn. Bạn có thể đặt số lượng về 0 thay vì xóa.');
            $stmt = $conn->prepare("DELETE FROM sach WHERE MaSach=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            ok(null, 'Đã xóa sách.');
        }

        case 'categories': {
            $categories = fetchAll($conn->query(
                "SELECT t.MaTheLoai, t.TenTheLoai, t.MoTa,
                        COUNT(s.MaSach) AS SoDauSach,
                        COALESCE(SUM(s.SoLuong), 0) AS TongSoLuong
                 FROM theloai t
                 LEFT JOIN sach s ON s.MaTheLoai = t.MaTheLoai
                 GROUP BY t.MaTheLoai, t.TenTheLoai, t.MoTa
                 ORDER BY t.MaTheLoai DESC"
            ));
            $summary = $conn->query(
                "SELECT
                    (SELECT COUNT(*) FROM theloai) AS totalCategories,
                    (SELECT COUNT(*) FROM sach) AS totalTitles,
                    (SELECT COALESCE(SUM(SoLuong),0) FROM sach) AS totalBooks"
            )->fetch_assoc();
            ok(['categories' => $categories, 'summary' => $summary]);
        }

        case 'category_save': {
            $id = nullableInt($data, 'id');
            $name = strv($data, 'name');
            $description = nullableString($data, 'description');
            if ($name === '') fail('Tên thể loại không được để trống.');

            if ($id) {
                $stmt = $conn->prepare("UPDATE theloai SET TenTheLoai=?, MoTa=? WHERE MaTheLoai=?");
                $stmt->bind_param('ssi', $name, $description, $id);
                $stmt->execute();
                ok(['id' => $id], 'Đã cập nhật thể loại.');
            }
            $stmt = $conn->prepare("INSERT INTO theloai (TenTheLoai, MoTa) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $description);
            $stmt->execute();
            ok(['id' => $conn->insert_id], 'Đã thêm thể loại.');
        }

        case 'category_delete': {
            $id = intv($data, 'id');
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM sach WHERE MaTheLoai=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) {
                fail('Thể loại đang có sách. Hãy chuyển sách sang thể loại khác trước khi xóa.');
            }
            $stmt = $conn->prepare("DELETE FROM theloai WHERE MaTheLoai=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            ok(null, 'Đã xóa thể loại.');
        }

        case 'readers': {
            if (currentLibraryRoleKey() === 'customer') {
                $readerId = (int)($_SESSION['reader_id'] ?? 0);
                if (!$readerId) fail('Tài khoản Độc giả chưa được liên kết với hồ sơ độc giả.', 400);
                $stmt = $conn->prepare("SELECT * FROM docgia WHERE MaDocGia=? LIMIT 1");
                $stmt->bind_param('i', $readerId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                ok($row ? [$row] : []);
            }
            ok(fetchAll($conn->query("SELECT * FROM docgia ORDER BY MaDocGia ASC")));
        }

        case 'reader_save': {
            $id = nullableInt($data, 'id');
            $name = strv($data, 'name');
            $birthDate = nullableString($data, 'birthDate');
            $gender = nullableString($data, 'gender');
            $phone = nullableString($data, 'phone');
            $email = nullableString($data, 'email');
            $address = nullableString($data, 'address');
            $registerDate = nullableString($data, 'registerDate') ?? date('Y-m-d');
            $status = strv($data, 'status', 'Đang hoạt động');
            if ($name === '') fail('Họ tên độc giả không được để trống.');
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Email không hợp lệ.');
            if ($phone) {
                if ($id !== null) {
                    $check = $conn->prepare("SELECT MaDocGia FROM docgia WHERE SDT=? AND MaDocGia<>? LIMIT 1");
                    $check->bind_param('si', $phone, $id);
                } else {
                    $check = $conn->prepare("SELECT MaDocGia FROM docgia WHERE SDT=? LIMIT 1");
                    $check->bind_param('s', $phone);
                }
                $check->execute();
                if ($check->get_result()->fetch_assoc()) fail('Số điện thoại này đã thuộc về một độc giả khác.');
            }
            if ($email) {
                if ($id !== null) {
                    $check = $conn->prepare("SELECT MaDocGia FROM docgia WHERE Email=? AND MaDocGia<>? LIMIT 1");
                    $check->bind_param('si', $email, $id);
                } else {
                    $check = $conn->prepare("SELECT MaDocGia FROM docgia WHERE Email=? LIMIT 1");
                    $check->bind_param('s', $email);
                }
                $check->execute();
                if ($check->get_result()->fetch_assoc()) fail('Email này đã thuộc về một độc giả khác.');
            }

            if ($id) {
                $stmt = $conn->prepare(
                    "UPDATE docgia SET HoTen=?, NgaySinh=?, GioiTinh=?, SDT=?, Email=?, DiaChi=?, NgayDangKy=?, TrangThai=? WHERE MaDocGia=?"
                );
                $stmt->bind_param('ssssssssi', $name, $birthDate, $gender, $phone, $email, $address, $registerDate, $status, $id);
                $stmt->execute();
                ok(['id' => $id], 'Đã cập nhật độc giả.');
            }
            $stmt = $conn->prepare(
                "INSERT INTO docgia (HoTen, NgaySinh, GioiTinh, SDT, Email, DiaChi, NgayDangKy, TrangThai)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ssssssss', $name, $birthDate, $gender, $phone, $email, $address, $registerDate, $status);
            $stmt->execute();
            $readerId = (int)$conn->insert_id;
            ok(['id' => $readerId], 'Đã thêm độc giả. Tài khoản đăng nhập chỉ được tạo khi khách tự đăng ký hoặc Admin tạo trong Quản lý tài khoản.');
        }

        case 'reader_delete': {
            $id = intv($data, 'id');
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM phieumuon WHERE MaDocGia=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) {
                fail('Không thể xóa độc giả đã có lịch sử mượn. Hãy chuyển trạng thái sang Ngừng hoạt động.');
            }
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM taikhoan WHERE MaDocGia=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) {
                fail('Độc giả đang được liên kết với tài khoản. Hãy xóa tài khoản trước.');
            }
            $stmt = $conn->prepare("DELETE FROM docgia WHERE MaDocGia=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            ok(null, 'Đã xóa độc giả.');
        }

        case 'employees': {
            if (hasLibraryPermission('employees_view')) {
                ok(fetchAll($conn->query("SELECT * FROM nhanvien ORDER BY MaNhanVien ASC")));
            }
            if (hasLibraryPermission('employees_self_view')) {
                $employeeId = (int)($_SESSION['employee_id'] ?? 0);
                if (!$employeeId) fail('Tài khoản Nhân viên chưa được Admin liên kết với hồ sơ nhân viên.');
                $stmt = $conn->prepare("SELECT * FROM nhanvien WHERE MaNhanVien=? LIMIT 1");
                $stmt->bind_param('i', $employeeId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                ok($row ? [$row] : []);
            }
            fail('Bạn không có quyền xem thông tin nhân viên.', 403);
        }

        case 'employee_save': {
            $id = nullableInt($data, 'id');
            $name = strv($data, 'name');
            $gender = nullableString($data, 'gender');
            $birthDate = nullableString($data, 'birthDate');
            $phone = nullableString($data, 'phone');
            $email = nullableString($data, 'email');
            $role = nullableString($data, 'role');
            $hireDate = nullableString($data, 'hireDate');
            $status = strv($data, 'status', 'Đang làm việc');
            $salary = max(0, (float)($data['salary'] ?? 0));

            if ($name === '') fail('Họ tên nhân viên không được để trống.');
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Email không hợp lệ.');

            if ($id === null && $phone) {
                $check = $conn->prepare("SELECT MaNhanVien FROM nhanvien WHERE SDT=? LIMIT 1");
                $check->bind_param('s', $phone);
                $check->execute();
                if ($check->get_result()->fetch_assoc()) fail('Số điện thoại này đã thuộc về một nhân viên khác.');
            }
            if ($id === null && $email) {
                $check = $conn->prepare("SELECT MaNhanVien FROM nhanvien WHERE Email=? LIMIT 1");
                $check->bind_param('s', $email);
                $check->execute();
                if ($check->get_result()->fetch_assoc()) fail('Email này đã thuộc về một nhân viên khác.');
            }

            if ($id) {
                if (hasLibraryPermission('salary_manage')) {
                    $stmt = $conn->prepare(
                        "UPDATE nhanvien SET HoTen=?, GioiTinh=?, NgaySinh=?, SDT=?, Email=?, VaiTro=?, Luong=?, NgayVaoLam=?, TrangThai=? WHERE MaNhanVien=?"
                    );
                    $stmt->bind_param('ssssssdssi', $name, $gender, $birthDate, $phone, $email, $role, $salary, $hireDate, $status, $id);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE nhanvien SET HoTen=?, GioiTinh=?, NgaySinh=?, SDT=?, Email=?, VaiTro=?, NgayVaoLam=?, TrangThai=? WHERE MaNhanVien=?"
                    );
                    $stmt->bind_param('ssssssssi', $name, $gender, $birthDate, $phone, $email, $role, $hireDate, $status, $id);
                }
                $stmt->execute();
                ok(['id' => $id], 'Đã cập nhật nhân viên.');
            }

            $stmt = $conn->prepare(
                "INSERT INTO nhanvien (HoTen, GioiTinh, NgaySinh, SDT, Email, VaiTro, Luong, NgayVaoLam, TrangThai)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ssssssdss', $name, $gender, $birthDate, $phone, $email, $role, $salary, $hireDate, $status);
            $stmt->execute();
            $employeeId = (int)$conn->insert_id;
            ok(['id' => $employeeId], 'Đã thêm nhân viên. Tài khoản đăng nhập do Admin tạo và phân quyền riêng.');
        }

        case 'employee_delete': {
            $id = intv($data, 'id');
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM phieumuon WHERE MaNhanVien=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) {
                fail('Không thể xóa nhân viên đã lập phiếu mượn. Hãy chuyển trạng thái sang Nghỉ việc.');
            }
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM taikhoan WHERE MaNhanVien=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) {
                fail('Nhân viên đang được liên kết với tài khoản. Hãy xóa tài khoản trước.');
            }
            $stmt = $conn->prepare("DELETE FROM nhanvien WHERE MaNhanVien=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            ok(null, 'Đã xóa nhân viên.');
        }

        case 'loans': {
            $isCustomer = currentLibraryRoleKey() === 'customer';
            if ($isCustomer) {
                $readerId = (int)($_SESSION['reader_id'] ?? 0);
                $employees = [];
                $loans = [];
                $readers = [];
                if ($readerId) {
                    $stmt = $conn->prepare(
                        "SELECT pm.MaPhieuMuon, pm.MaDocGia, pm.MaNhanVien, pm.NgayMuon, pm.HanTra, pm.NgayTra, pm.TrangThai,
                                dg.HoTen AS TenDocGia, dg.SDT AS SDTDocGia,
                                nv.HoTen AS TenNhanVien,
                                GROUP_CONCAT(s.TenSach ORDER BY s.TenSach SEPARATOR ', ') AS TenSach,
                                GROUP_CONCAT(s.MaSach ORDER BY s.MaSach SEPARATOR ',') AS MaSachList,
                                COALESCE(SUM(ct.SoLuong), 0) AS TongSoLuong,
                                COALESCE(SUM(ct.TienPhat), 0) AS TongTienPhat,
                                GROUP_CONCAT(DISTINCT NULLIF(ct.GhiChu,'') SEPARATOR '; ') AS GhiChu
                         FROM phieumuon pm
                         LEFT JOIN docgia dg ON dg.MaDocGia = pm.MaDocGia
                         LEFT JOIN nhanvien nv ON nv.MaNhanVien = pm.MaNhanVien
                         LEFT JOIN chitietphieumuon ct ON ct.MaPhieuMuon = pm.MaPhieuMuon
                         LEFT JOIN sach s ON s.MaSach = ct.MaSach
                         WHERE pm.MaDocGia=?
                         GROUP BY pm.MaPhieuMuon, pm.MaDocGia, pm.MaNhanVien, pm.NgayMuon, pm.HanTra, pm.NgayTra, pm.TrangThai,
                                  dg.HoTen, dg.SDT, nv.HoTen
                         ORDER BY pm.MaPhieuMuon DESC"
                    );
                    $stmt->bind_param('i', $readerId);
                    $stmt->execute();
                    $loans = fetchAll($stmt->get_result());

                    $stmt = $conn->prepare("SELECT MaDocGia, HoTen, SDT FROM docgia WHERE MaDocGia=? LIMIT 1");
                    $stmt->bind_param('i', $readerId);
                    $stmt->execute();
                    $reader = $stmt->get_result()->fetch_assoc();
                    $readers = $reader ? [$reader] : [];
                }
            } else {
                $loans = fetchAll($conn->query(
                    "SELECT pm.MaPhieuMuon, pm.MaDocGia, pm.MaNhanVien, pm.NgayMuon, pm.HanTra, pm.NgayTra, pm.TrangThai,
                            dg.HoTen AS TenDocGia, dg.SDT AS SDTDocGia,
                            nv.HoTen AS TenNhanVien,
                            GROUP_CONCAT(s.TenSach ORDER BY s.TenSach SEPARATOR ', ') AS TenSach,
                            GROUP_CONCAT(s.MaSach ORDER BY s.MaSach SEPARATOR ',') AS MaSachList,
                            COALESCE(SUM(ct.SoLuong), 0) AS TongSoLuong,
                            COALESCE(SUM(ct.TienPhat), 0) AS TongTienPhat,
                            GROUP_CONCAT(DISTINCT NULLIF(ct.GhiChu,'') SEPARATOR '; ') AS GhiChu
                     FROM phieumuon pm
                     LEFT JOIN docgia dg ON dg.MaDocGia = pm.MaDocGia
                     LEFT JOIN nhanvien nv ON nv.MaNhanVien = pm.MaNhanVien
                     LEFT JOIN chitietphieumuon ct ON ct.MaPhieuMuon = pm.MaPhieuMuon
                     LEFT JOIN sach s ON s.MaSach = ct.MaSach
                     GROUP BY pm.MaPhieuMuon, pm.MaDocGia, pm.MaNhanVien, pm.NgayMuon, pm.HanTra, pm.NgayTra, pm.TrangThai,
                              dg.HoTen, dg.SDT, nv.HoTen
                     ORDER BY pm.MaPhieuMuon DESC"
                ));
                $readers = fetchAll($conn->query(
                    "SELECT MaDocGia, HoTen, SDT FROM docgia WHERE COALESCE(TrangThai,'') NOT IN ('Khóa','Ngừng hoạt động') ORDER BY HoTen"
                ));
                $employees = fetchAll($conn->query(
                    "SELECT MaNhanVien, HoTen FROM nhanvien WHERE COALESCE(TrangThai,'') NOT IN ('Khóa','Nghỉ việc') ORDER BY HoTen"
                ));
            }

            $books = fetchAll($conn->query("SELECT MaSach, TenSach, TacGia, SoLuong FROM sach ORDER BY TenSach"));
            $settings = $conn->query("SELECT SoNgayMuon, SoNgayGiaHan, MucPhat FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc() ?: ['SoNgayMuon' => 14, 'SoNgayGiaHan' => 7, 'MucPhat' => 5000];
            $publicSummary = null;
            $loanSummary = null;
            if ($isCustomer) {
                $publicSummary = $conn->query(
                    "SELECT
                        COUNT(DISTINCT CASE WHEN NgayTra IS NULL AND COALESCE(TrangThai,'')='Đang mượn' THEN MaDocGia END) AS DangMuon,
                        COUNT(DISTINCT CASE WHEN NgayTra IS NOT NULL OR COALESCE(TrangThai,'')='Đã trả' THEN MaDocGia END) AS DaTra,
                        COUNT(CASE WHEN COALESCE(TrangThai,'')='Chờ duyệt' THEN 1 END) AS ChoDuyet
                     FROM phieumuon"
                )->fetch_assoc();
            } else {
                $loanSummary = $conn->query(
                    "SELECT
                        COALESCE(SUM(CASE WHEN pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy') THEN ct.SoLuong ELSE 0 END),0) AS borrowingBooks,
                        COALESCE(SUM(CASE WHEN pm.NgayTra IS NOT NULL OR COALESCE(pm.TrangThai,'')='Đã trả' THEN ct.SoLuong ELSE 0 END),0) AS returnedBooks,
                        COALESCE(SUM(CASE WHEN pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy') AND pm.HanTra BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN ct.SoLuong ELSE 0 END),0) AS dueSoonBooks,
                        COALESCE(SUM(CASE WHEN pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy') AND pm.HanTra<CURDATE() THEN ct.SoLuong ELSE 0 END),0) AS overdueBooks
                     FROM phieumuon pm
                     LEFT JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon"
                )->fetch_assoc();
            }
            ok(['loans' => $loans, 'readers' => $readers, 'books' => $books, 'employees' => $employees, 'settings' => $settings, 'publicSummary' => $publicSummary, 'loanSummary' => $loanSummary]);
        }

        case 'loan_save': {
            $isCustomer = currentLibraryRoleKey() === 'customer';
            if ($isCustomer) {
                if (!hasLibraryPermission('borrow_self')) fail('Bạn không có quyền mượn sách.', 403);
            } elseif (!hasLibraryPermission('loans_manage')) {
                fail('Bạn không có quyền quản lý phiếu mượn.', 403);
            }

            $id = nullableInt($data, 'id');
            $readerId = intv($data, 'readerId');
            $readerName = strv($data, 'readerName');
            $readerPhone = strv($data, 'readerPhone');
            $staffId = nullableInt($data, 'staffId');
            $bookId = intv($data, 'bookId');
            $quantity = min(20, max(1, intv($data, 'quantity', 1)));
            $borrowDate = strv($data, 'borrowDate', date('Y-m-d'));
            $dueDate = strv($data, 'dueDate');
            $note = nullableString($data, 'note');

            if ($isCustomer) {
                if ($id) fail('Tài khoản Khách không được sửa phiếu mượn.', 403);
                $readerId = (int)($_SESSION['reader_id'] ?? 0);
                $staffId = null;
                $borrowDate = date('Y-m-d');
                $dueDate = '';

                if (!$readerId) {
                    fail('Tài khoản Khách chưa liên kết hồ sơ độc giả. Hãy đăng ký tài khoản Khách hoặc liên hệ Admin.', 400);
                }

                $stmt = $conn->prepare("SELECT HoTen, SDT, TrangThai FROM docgia WHERE MaDocGia=? LIMIT 1");
                $stmt->bind_param('i', $readerId);
                $stmt->execute();
                $readerRow = $stmt->get_result()->fetch_assoc();
                if (!$readerRow) fail('Không tìm thấy hồ sơ độc giả của tài khoản này.');

                $readerName = (string)($readerRow['HoTen'] ?? '');
                $readerPhone = (string)($readerRow['SDT'] ?? '');
                $readerStatus = libraryPlainText((string)($readerRow['TrangThai'] ?? ''));
                if (in_array($readerStatus, ['khoa','ngung hoat dong'], true)) {
                    fail('Hồ sơ độc giả hiện không được phép mượn sách.');
                }
            }

            if (!$isCustomer && currentLibraryRoleKey() === 'employee') {
                $staffId = (int)($_SESSION['employee_id'] ?? 0);
                if (!$staffId) fail('Tài khoản Nhân viên chưa được Admin liên kết với hồ sơ nhân viên.');
            } elseif (!$isCustomer && !$staffId && !empty($_SESSION['employee_id'])) {
                $staffId = (int)$_SESSION['employee_id'];
            }

            if (!$readerId || !$bookId) fail('Vui lòng chọn độc giả và sách.');
            if (!$dueDate) {
                $setting = $conn->query("SELECT SoNgayMuon FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc();
                $days = (int)($setting['SoNgayMuon'] ?? 14);
                $dueDate = date('Y-m-d', strtotime($borrowDate . " +{$days} days"));
            }
            if ($dueDate < $borrowDate) fail('Hạn trả phải từ ngày mượn trở đi.');
            if ($isCustomer) {
                $conn->begin_transaction();
                $stmt = $conn->prepare("SELECT SoLuong FROM sach WHERE MaSach=? FOR UPDATE");
                $stmt->bind_param('i', $bookId);
                $stmt->execute();
                $bookRow = $stmt->get_result()->fetch_assoc();
                if (!$bookRow) throw new RuntimeException('Không tìm thấy sách.');
                if ((int)$bookRow['SoLuong'] < $quantity) throw new RuntimeException('Số lượng sách hiện có không đủ để gửi yêu cầu.');

                $stmt = $conn->prepare(
                    "SELECT COUNT(*) AS total
                     FROM phieumuon pm
                     JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                     WHERE pm.MaDocGia=? AND ct.MaSach=? AND pm.NgayTra IS NULL
                       AND COALESCE(pm.TrangThai,'') IN ('Chờ duyệt','Đang mượn')"
                );
                $stmt->bind_param('ii', $readerId, $bookId);
                $stmt->execute();
                if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) {
                    throw new RuntimeException('Bạn đã có yêu cầu/phiếu đang mượn cho cuốn sách này.');
                }

                $status = 'Chờ duyệt';
                $stmt = $conn->prepare(
                    "INSERT INTO phieumuon (MaDocGia, MaNhanVien, NgayMuon, HanTra, NgayTra, TrangThai)
                     VALUES (?, NULL, ?, ?, NULL, ?)"
                );
                $stmt->bind_param('isss', $readerId, $borrowDate, $dueDate, $status);
                $stmt->execute();
                $loanId = $conn->insert_id;

                $zero = 0.0;
                $stmt = $conn->prepare(
                    "INSERT INTO chitietphieumuon (MaPhieuMuon, MaSach, SoLuong, TienPhat, GhiChu)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('iiids', $loanId, $bookId, $quantity, $zero, $note);
                $stmt->execute();
                $conn->commit();
                ok(['id' => $loanId, 'status' => $status], 'Đã gửi yêu cầu mượn sách. Nhân viên sẽ xem và duyệt phiếu.');
            }

            $conn->begin_transaction();
            $stmt = $conn->prepare("SELECT SoLuong FROM sach WHERE MaSach=? FOR UPDATE");
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            $bookRow = $stmt->get_result()->fetch_assoc();
            if (!$bookRow) throw new RuntimeException('Không tìm thấy sách.');

            if ($id) {
                $stmt = $conn->prepare("SELECT NgayTra, TrangThai FROM phieumuon WHERE MaPhieuMuon=? FOR UPDATE");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $loanRow = $stmt->get_result()->fetch_assoc();
                if (!$loanRow) throw new RuntimeException('Không tìm thấy phiếu mượn.');
                if (!empty($loanRow['NgayTra'])) throw new RuntimeException('Phiếu đã trả không thể chỉnh sửa.');

                $stmt = $conn->prepare("SELECT MaCTPM, MaSach, SoLuong FROM chitietphieumuon WHERE MaPhieuMuon=? ORDER BY MaCTPM");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                if (count($details) !== 1) throw new RuntimeException('Chỉ có thể chỉnh sửa phiếu có một đầu sách bằng giao diện này.');

                $oldBookId = (int)$details[0]['MaSach'];
                $oldQty = (int)$details[0]['SoLuong'];
                if ($oldBookId === $bookId) {
                    $availableAfterRestore = (int)$bookRow['SoLuong'] + $oldQty;
                    if ($availableAfterRestore < $quantity) throw new RuntimeException('Số lượng sách còn lại không đủ.');
                    $newStock = $availableAfterRestore - $quantity;
                    $stmt = $conn->prepare("UPDATE sach SET SoLuong=? WHERE MaSach=?");
                    $stmt->bind_param('ii', $newStock, $bookId);
                    $stmt->execute();
                } else {
                    $stmt = $conn->prepare("UPDATE sach SET SoLuong=SoLuong+? WHERE MaSach=?");
                    $stmt->bind_param('ii', $oldQty, $oldBookId);
                    $stmt->execute();
                    if ((int)$bookRow['SoLuong'] < $quantity) throw new RuntimeException('Số lượng sách còn lại không đủ.');
                    $stmt = $conn->prepare("UPDATE sach SET SoLuong=SoLuong-? WHERE MaSach=?");
                    $stmt->bind_param('ii', $quantity, $bookId);
                    $stmt->execute();
                }

                $status = 'Đang mượn';
                $stmt = $conn->prepare("UPDATE phieumuon SET MaDocGia=?, MaNhanVien=?, NgayMuon=?, HanTra=?, TrangThai=? WHERE MaPhieuMuon=?");
                $stmt->bind_param('iisssi', $readerId, $staffId, $borrowDate, $dueDate, $status, $id);
                $stmt->execute();

                $detailId = (int)$details[0]['MaCTPM'];
                $stmt = $conn->prepare("UPDATE chitietphieumuon SET MaSach=?, SoLuong=?, GhiChu=? WHERE MaCTPM=?");
                $stmt->bind_param('iisi', $bookId, $quantity, $note, $detailId);
                $stmt->execute();

                $conn->commit();
                ok(['id' => $id], 'Đã cập nhật phiếu mượn.');
            }

            if ((int)$bookRow['SoLuong'] < $quantity) throw new RuntimeException('Số lượng sách còn lại không đủ.');
            $status = 'Đang mượn';
            $stmt = $conn->prepare(
                "INSERT INTO phieumuon (MaDocGia, MaNhanVien, NgayMuon, HanTra, NgayTra, TrangThai)
                 VALUES (?, ?, ?, ?, NULL, ?)"
            );
            $stmt->bind_param('iisss', $readerId, $staffId, $borrowDate, $dueDate, $status);
            $stmt->execute();
            $loanId = $conn->insert_id;

            $zero = 0.0;
            $stmt = $conn->prepare(
                "INSERT INTO chitietphieumuon (MaPhieuMuon, MaSach, SoLuong, TienPhat, GhiChu)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iiids', $loanId, $bookId, $quantity, $zero, $note);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE sach SET SoLuong=SoLuong-? WHERE MaSach=?");
            $stmt->bind_param('ii', $quantity, $bookId);
            $stmt->execute();

            $conn->commit();
            ok(['id' => $loanId], $isCustomer ? 'Đã tạo phiếu mượn cho tài khoản của bạn.' : 'Đã tạo phiếu mượn.');
        }

        case 'loan_approve': {
            $id = intv($data, 'id');
            if (!$id) fail('Thiếu mã phiếu mượn.');
            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT MaDocGia, TrangThai, NgayTra FROM phieumuon WHERE MaPhieuMuon=? FOR UPDATE");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $loan = $stmt->get_result()->fetch_assoc();
            if (!$loan) throw new RuntimeException('Không tìm thấy phiếu mượn.');
            if (!empty($loan['NgayTra'])) throw new RuntimeException('Phiếu đã trả, không thể duyệt.');
            if (($loan['TrangThai'] ?? '') !== 'Chờ duyệt') throw new RuntimeException('Chỉ phiếu Chờ duyệt mới cần xác nhận.');

            $stmt = $conn->prepare("SELECT MaSach, SoLuong FROM chitietphieumuon WHERE MaPhieuMuon=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            if (!$details) throw new RuntimeException('Phiếu chưa có sách.');

            foreach ($details as $detail) {
                $bookId = (int)$detail['MaSach'];
                $qty = (int)$detail['SoLuong'];
                $lock = $conn->prepare("SELECT SoLuong FROM sach WHERE MaSach=? FOR UPDATE");
                $lock->bind_param('i', $bookId);
                $lock->execute();
                $book = $lock->get_result()->fetch_assoc();
                if (!$book || (int)$book['SoLuong'] < $qty) {
                    throw new RuntimeException('Không đủ số lượng sách để duyệt phiếu này.');
                }
                $update = $conn->prepare("UPDATE sach SET SoLuong=SoLuong-? WHERE MaSach=?");
                $update->bind_param('ii', $qty, $bookId);
                $update->execute();
            }

            $settingsRow = $conn->query("SELECT SoNgayMuon FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc();
            $days = max(1, (int)($settingsRow['SoNgayMuon'] ?? 14));
            $borrowDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime($borrowDate . " +{$days} days"));
            $staffId = (int)($_SESSION['employee_id'] ?? 0);
            if (!$staffId) $staffId = nullableInt($data, 'staffId') ?? 0;
            $staffDb = $staffId > 0 ? $staffId : null;
            $status = 'Đang mượn';

            $stmt = $conn->prepare("UPDATE phieumuon SET MaNhanVien=?, NgayMuon=?, HanTra=?, TrangThai=? WHERE MaPhieuMuon=?");
            $stmt->bind_param('isssi', $staffDb, $borrowDate, $dueDate, $status, $id);
            $stmt->execute();
            $conn->commit();
            ok(['id' => $id], 'Đã duyệt yêu cầu. Phiếu chuyển sang Đang mượn và tồn kho đã được cập nhật.');
        }

        case 'loan_return': {
            $id = intv($data, 'id');
            if (!$id) fail('Thiếu mã phiếu mượn.');
            $returnDate = strv($data, 'returnDate', date('Y-m-d'));
            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT HanTra, NgayTra, TrangThai FROM phieumuon WHERE MaPhieuMuon=? FOR UPDATE");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $loan = $stmt->get_result()->fetch_assoc();
            if (!$loan) throw new RuntimeException('Không tìm thấy phiếu mượn.');
            if (!empty($loan['NgayTra'])) throw new RuntimeException('Phiếu này đã được trả trước đó.');
            if (($loan['TrangThai'] ?? '') === 'Chờ duyệt') throw new RuntimeException('Phiếu đang chờ duyệt, chưa thể trả sách.');

            $stmt = $conn->prepare("SELECT MaSach, SoLuong FROM chitietphieumuon WHERE MaPhieuMuon=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach ($details as $detail) {
                $bookId = (int)$detail['MaSach'];
                $qty = (int)$detail['SoLuong'];
                $stmt2 = $conn->prepare("UPDATE sach SET SoLuong=SoLuong+? WHERE MaSach=?");
                $stmt2->bind_param('ii', $qty, $bookId);
                $stmt2->execute();
            }

            $setting = $conn->query("SELECT MucPhat FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc();
            $finePerDay = (float)($setting['MucPhat'] ?? 0);
            $lateDays = 0;
            if (!empty($loan['HanTra']) && $returnDate > $loan['HanTra']) {
                $lateDays = max(0, (int)((strtotime($returnDate) - strtotime($loan['HanTra'])) / 86400));
            }
            if ($lateDays > 0 && $finePerDay > 0) {
                $stmt = $conn->prepare("UPDATE chitietphieumuon SET TienPhat = SoLuong * ? * ? WHERE MaPhieuMuon=?");
                $stmt->bind_param('dii', $finePerDay, $lateDays, $id);
                $stmt->execute();
            }

            $status = 'Đã trả';
            $stmt = $conn->prepare("UPDATE phieumuon SET NgayTra=?, TrangThai=? WHERE MaPhieuMuon=?");
            $stmt->bind_param('ssi', $returnDate, $status, $id);
            $stmt->execute();
            $conn->commit();
            ok(['lateDays' => $lateDays], 'Đã xác nhận trả sách.');
        }

        case 'loan_delete': {
            $id = intv($data, 'id');
            if (!$id) fail('Thiếu mã phiếu mượn.');
            $conn->begin_transaction();
            $stmt = $conn->prepare("SELECT NgayTra FROM phieumuon WHERE MaPhieuMuon=? FOR UPDATE");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $loan = $stmt->get_result()->fetch_assoc();
            if (!$loan) throw new RuntimeException('Không tìm thấy phiếu mượn.');

            if (empty($loan['NgayTra']) && ($loan['TrangThai'] ?? '') !== 'Chờ duyệt') {
                $stmt = $conn->prepare("SELECT MaSach, SoLuong FROM chitietphieumuon WHERE MaPhieuMuon=?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $detail) {
                    $bookId = (int)$detail['MaSach'];
                    $qty = (int)$detail['SoLuong'];
                    $stmt2 = $conn->prepare("UPDATE sach SET SoLuong=SoLuong+? WHERE MaSach=?");
                    $stmt2->bind_param('ii', $qty, $bookId);
                    $stmt2->execute();
                }
            }

            $stmt = $conn->prepare("DELETE FROM phieumuon WHERE MaPhieuMuon=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $conn->commit();
            ok(null, 'Đã xóa phiếu mượn.');
        }

        case 'reservation_list': {
            $roleKey = currentLibraryRoleKey();
            $isCustomer = $roleKey === 'customer';
            if (!$isCustomer && !hasLibraryPermission('reservations_view') && !hasLibraryPermission('reservations_manage')) {
                fail('Bạn không có quyền xem lịch đặt mượn.', 403);
            }
            if ($isCustomer && !hasLibraryPermission('reservations_self')) {
                fail('Bạn không có quyền xem lịch đặt mượn.', 403);
            }

            if ($isCustomer) {
                $readerId = (int)($_SESSION['reader_id'] ?? 0);
                if (!$readerId) {
                    ok(['rows' => [], 'summary' => ['ChoXacNhan' => 0, 'DaXacNhan' => 0, 'DaNhanSach' => 0, 'DaHuy' => 0]]);
                }
                $stmt = $conn->prepare(
                    "SELECT dl.MaDatLich, dl.MaDocGia, dl.MaSach, dl.MaNhanVien, dl.NgayDat, dl.NgayDuKienMuon,
                            dl.GioDuKien, dl.SoLuong, dl.TrangThai, dl.GhiChu, dl.NgayXuLy,
                            dg.HoTen AS TenDocGia, dg.SDT AS SDTDocGia,
                            s.TenSach, s.TacGia, s.SoLuong AS SoLuongCon,
                            nv.HoTen AS TenNhanVien
                     FROM datlichmuon dl
                     JOIN docgia dg ON dg.MaDocGia=dl.MaDocGia
                     JOIN sach s ON s.MaSach=dl.MaSach
                     LEFT JOIN nhanvien nv ON nv.MaNhanVien=dl.MaNhanVien
                     WHERE dl.MaDocGia=?
                     ORDER BY dl.MaDatLich DESC"
                );
                $stmt->bind_param('i', $readerId);
                $stmt->execute();
                $rows = fetchAll($stmt->get_result());
            } else {
                $rows = fetchAll($conn->query(
                    "SELECT dl.MaDatLich, dl.MaDocGia, dl.MaSach, dl.MaNhanVien, dl.NgayDat, dl.NgayDuKienMuon,
                            dl.GioDuKien, dl.SoLuong, dl.TrangThai, dl.GhiChu, dl.NgayXuLy,
                            dg.HoTen AS TenDocGia, dg.SDT AS SDTDocGia,
                            s.TenSach, s.TacGia, s.SoLuong AS SoLuongCon,
                            nv.HoTen AS TenNhanVien
                     FROM datlichmuon dl
                     JOIN docgia dg ON dg.MaDocGia=dl.MaDocGia
                     JOIN sach s ON s.MaSach=dl.MaSach
                     LEFT JOIN nhanvien nv ON nv.MaNhanVien=dl.MaNhanVien
                     ORDER BY dl.NgayDuKienMuon ASC, dl.GioDuKien ASC, dl.MaDatLich DESC"
                ));
            }

            $summary = $conn->query(
                "SELECT
                    SUM(CASE WHEN TrangThai='Chờ xác nhận' THEN 1 ELSE 0 END) AS ChoXacNhan,
                    SUM(CASE WHEN TrangThai='Đã xác nhận' THEN 1 ELSE 0 END) AS DaXacNhan,
                    SUM(CASE WHEN TrangThai='Đã nhận sách' THEN 1 ELSE 0 END) AS DaNhanSach,
                    SUM(CASE WHEN TrangThai='Đã hủy' THEN 1 ELSE 0 END) AS DaHuy
                 FROM datlichmuon"
            )->fetch_assoc();
            ok(['rows' => $rows, 'summary' => $summary]);
        }

        case 'reservation_save': {
            if (currentLibraryRoleKey() !== 'customer' || !hasLibraryPermission('reservations_self')) {
                fail('Chỉ tài khoản Độc giả mới được tự đặt lịch mượn.', 403);
            }
            $readerId = (int)($_SESSION['reader_id'] ?? 0);
            if (!$readerId) fail('Tài khoản Độc giả chưa được liên kết với hồ sơ độc giả.');
            $bookId = intv($data, 'bookId');
            $date = strv($data, 'date');
            $time = nullableString($data, 'time');
            $quantity = min(10, max(1, intv($data, 'quantity', 1)));
            $note = nullableString($data, 'note');
            if (!$bookId || !$date) fail('Vui lòng chọn sách và ngày dự kiến đến mượn.');
            if ($date < date('Y-m-d')) fail('Ngày dự kiến mượn không được ở quá khứ.');

            $stmt = $conn->prepare("SELECT TrangThai FROM docgia WHERE MaDocGia=? LIMIT 1");
            $stmt->bind_param('i', $readerId);
            $stmt->execute();
            $reader = $stmt->get_result()->fetch_assoc();
            if (!$reader) fail('Không tìm thấy hồ sơ độc giả.');
            if (in_array(libraryPlainText((string)($reader['TrangThai'] ?? '')), ['khoa','ngung hoat dong'], true)) {
                fail('Hồ sơ độc giả hiện không được phép đặt lịch mượn.');
            }

            $stmt = $conn->prepare("SELECT TenSach, SoLuong FROM sach WHERE MaSach=? LIMIT 1");
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            $book = $stmt->get_result()->fetch_assoc();
            if (!$book) fail('Không tìm thấy sách.');
            if ((int)$book['SoLuong'] < $quantity) fail('Số lượng sách hiện có không đủ cho lịch đặt này.');

            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS total FROM datlichmuon
                 WHERE MaDocGia=? AND MaSach=? AND NgayDuKienMuon=?
                   AND TrangThai IN ('Chờ xác nhận','Đã xác nhận')"
            );
            $stmt->bind_param('iis', $readerId, $bookId, $date);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) {
                fail('Bạn đã có lịch đặt cho cuốn sách này trong ngày đã chọn.');
            }

            $status = 'Chờ xác nhận';
            $stmt = $conn->prepare(
                "INSERT INTO datlichmuon (MaDocGia,MaSach,NgayDuKienMuon,GioDuKien,SoLuong,TrangThai,GhiChu)
                 VALUES (?,?,?,?,?,?,?)"
            );
            $stmt->bind_param('iississ', $readerId, $bookId, $date, $time, $quantity, $status, $note);
            $stmt->execute();
            ok(['id' => $conn->insert_id], 'Đã gửi lịch đặt mượn. Nhân viên sẽ xác nhận lịch của bạn.');
        }

        case 'reservation_action': {
            $id = intv($data, 'id');
            $type = libraryPlainText(strv($data, 'type'));
            if (!$id) fail('Thiếu mã lịch đặt.');
            if (!in_array($type, ['xac nhan','huy','da nhan sach'], true)) fail('Thao tác lịch đặt không hợp lệ.');

            $conn->begin_transaction();
            $stmt = $conn->prepare(
                "SELECT dl.*, s.SoLuong AS SoLuongCon
                 FROM datlichmuon dl JOIN sach s ON s.MaSach=dl.MaSach
                 WHERE dl.MaDatLich=? FOR UPDATE"
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) throw new RuntimeException('Không tìm thấy lịch đặt.');
            $currentStatus = (string)($row['TrangThai'] ?? '');
            if (in_array($currentStatus, ['Đã nhận sách','Đã hủy'], true)) {
                throw new RuntimeException('Lịch đặt này đã kết thúc, không thể thao tác thêm.');
            }

            $staffId = (int)($_SESSION['employee_id'] ?? 0);
            if (!$staffId) $staffId = nullableInt($data, 'staffId') ?? 0;
            $staffDb = $staffId > 0 ? $staffId : null;

            if ($type === 'huy') {
                $status = 'Đã hủy';
                $stmt = $conn->prepare("UPDATE datlichmuon SET TrangThai=?, MaNhanVien=?, NgayXuLy=NOW() WHERE MaDatLich=?");
                $stmt->bind_param('sii', $status, $staffDb, $id);
                $stmt->execute();
                $conn->commit();
                ok(null, 'Đã hủy lịch đặt.');
            }

            if ($type === 'xac nhan') {
                $status = 'Đã xác nhận';
                $stmt = $conn->prepare("UPDATE datlichmuon SET TrangThai=?, MaNhanVien=?, NgayXuLy=NOW() WHERE MaDatLich=?");
                $stmt->bind_param('sii', $status, $staffDb, $id);
                $stmt->execute();
                $conn->commit();
                ok(null, 'Đã xác nhận lịch đặt.');
            }
            $qty = max(1, (int)$row['SoLuong']);
            if ((int)$row['SoLuongCon'] < $qty) throw new RuntimeException('Không đủ tồn kho để giao sách theo lịch đặt.');
            $borrowDate = date('Y-m-d');
            $setting = $conn->query("SELECT SoNgayMuon FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc();
            $days = max(1, (int)($setting['SoNgayMuon'] ?? 14));
            $dueDate = date('Y-m-d', strtotime($borrowDate . " +{$days} days"));
            $loanStatus = 'Đang mượn';

            $stmt = $conn->prepare(
                "INSERT INTO phieumuon (MaDocGia,MaNhanVien,NgayMuon,HanTra,NgayTra,TrangThai)
                 VALUES (?,?,?,?,NULL,?)"
            );
            $readerId = (int)$row['MaDocGia'];
            $stmt->bind_param('iisss', $readerId, $staffDb, $borrowDate, $dueDate, $loanStatus);
            $stmt->execute();
            $loanId = $conn->insert_id;

            $zero = 0.0;
            $note = trim((string)($row['GhiChu'] ?? ''));
            $note = $note !== '' ? 'Từ lịch đặt: ' . $note : 'Từ lịch đặt trước';
            $bookId = (int)$row['MaSach'];
            $stmt = $conn->prepare("INSERT INTO chitietphieumuon (MaPhieuMuon,MaSach,SoLuong,TienPhat,GhiChu) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iiids', $loanId, $bookId, $qty, $zero, $note);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE sach SET SoLuong=SoLuong-? WHERE MaSach=?");
            $stmt->bind_param('ii', $qty, $bookId);
            $stmt->execute();

            $status = 'Đã nhận sách';
            $stmt = $conn->prepare("UPDATE datlichmuon SET TrangThai=?, MaNhanVien=?, NgayXuLy=NOW() WHERE MaDatLich=?");
            $stmt->bind_param('sii', $status, $staffDb, $id);
            $stmt->execute();
            $conn->commit();
            ok(['loanId' => $loanId], 'Đã giao sách và tạo phiếu mượn từ lịch đặt.');
        }

        case 'shifts': {
            if (!hasAnyLibraryPermission(['shifts_view','shifts_self_view'])) fail('Bạn không có quyền xem ca làm.', 403);
            $roleKey = currentLibraryRoleKey();
            $isSelfOnly = $roleKey === 'employee' && hasLibraryPermission('shifts_self_view') && !hasLibraryPermission('shifts_view');

            $shiftRows = fetchAll($conn->query("SELECT * FROM calamviec ORDER BY GioBatDau ASC, MaCa ASC"));
            if ($isSelfOnly) {
                $employeeId = (int)($_SESSION['employee_id'] ?? 0);
                if (!$employeeId) {
                    ok([
                        'shifts' => $shiftRows,
                        'assignments' => [],
                        'rosterAssignments' => [],
                        'employees' => [],
                        'selfOnly' => true,
                        'currentEmployeeId' => 0,
                        'message' => 'Tài khoản Nhân viên chưa liên kết hồ sơ nhân viên.'
                    ]);
                }
                $stmt = $conn->prepare(
                    "SELECT pc.MaPhanCong, pc.MaNhanVien, pc.MaCa, pc.NgayLam, pc.GhiChu,
                            nv.HoTen AS TenNhanVien, c.TenCa, c.GioBatDau, c.GioKetThuc
                     FROM phancongca pc
                     JOIN nhanvien nv ON nv.MaNhanVien=pc.MaNhanVien
                     JOIN calamviec c ON c.MaCa=pc.MaCa
                     WHERE pc.MaNhanVien=?
                     ORDER BY pc.NgayLam ASC, c.GioBatDau ASC"
                );
                $stmt->bind_param('i', $employeeId);
                $stmt->execute();
                $assignments = fetchAll($stmt->get_result());
                ok([
                    'shifts' => $shiftRows,
                    'assignments' => $assignments,
                    'rosterAssignments' => [],
                    'employees' => [],
                    'selfOnly' => true,
                    'currentEmployeeId' => $employeeId
                ]);
            }

            $assignments = fetchAll($conn->query(
                "SELECT pc.MaPhanCong, pc.MaNhanVien, pc.MaCa, pc.NgayLam, pc.GhiChu,
                        nv.HoTen AS TenNhanVien, c.TenCa, c.GioBatDau, c.GioKetThuc
                 FROM phancongca pc
                 JOIN nhanvien nv ON nv.MaNhanVien=pc.MaNhanVien
                 JOIN calamviec c ON c.MaCa=pc.MaCa
                 ORDER BY pc.NgayLam ASC, c.GioBatDau ASC, nv.HoTen ASC"
            ));
            $employees = fetchAll($conn->query(
                "SELECT MaNhanVien, HoTen FROM nhanvien
                 WHERE COALESCE(TrangThai,'') NOT IN ('Khóa','Nghỉ việc') ORDER BY HoTen ASC"
            ));
            ok([
                'shifts' => $shiftRows,
                'assignments' => $assignments,
                'rosterAssignments' => $assignments,
                'employees' => $employees,
                'selfOnly' => false,
                'currentEmployeeId' => (int)($_SESSION['employee_id'] ?? 0)
            ]);
        }

        case 'shift_save': {
            $id = nullableInt($data, 'id');
            $name = strv($data, 'name');
            $start = strv($data, 'start');
            $end = strv($data, 'end');
            $description = nullableString($data, 'description');
            $status = strv($data, 'status', 'Hoạt động');
            if ($name === '' || $start === '' || $end === '') fail('Vui lòng nhập tên ca, giờ bắt đầu và giờ kết thúc.');
            if ($start >= $end) fail('Giờ kết thúc phải sau giờ bắt đầu.');
            if ($id) {
                $stmt = $conn->prepare("UPDATE calamviec SET TenCa=?,GioBatDau=?,GioKetThuc=?,MoTa=?,TrangThai=? WHERE MaCa=?");
                $stmt->bind_param('sssssi', $name, $start, $end, $description, $status, $id);
                $stmt->execute();
                ok(['id'=>$id], 'Đã cập nhật ca làm.');
            }
            $stmt = $conn->prepare("INSERT INTO calamviec (TenCa,GioBatDau,GioKetThuc,MoTa,TrangThai) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssss', $name, $start, $end, $description, $status);
            $stmt->execute();
            ok(['id'=>$conn->insert_id], 'Đã thêm ca làm.');
        }

        case 'shift_delete': {
            $id = intv($data, 'id');
            if (!$id) fail('Thiếu mã ca.');
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM phancongca WHERE MaCa=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) fail('Ca này đã có lịch phân công. Hãy xóa/chuyển phân công trước.');
            $stmt = $conn->prepare("DELETE FROM calamviec WHERE MaCa=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            ok(null, 'Đã xóa ca làm.');
        }

        case 'shift_assign': {
            $id = nullableInt($data, 'id');
            $employeeId = intv($data, 'employeeId');
            $note = nullableString($data, 'note');
            $rawDates = isset($data['dates']) && is_array($data['dates']) ? $data['dates'] : [strv($data, 'date')];
            $rawShiftIds = isset($data['shiftIds']) && is_array($data['shiftIds']) ? $data['shiftIds'] : [intv($data, 'shiftId')];

            $dates = [];
            foreach ($rawDates as $value) {
                $date = trim((string)$value);
                if ($date === '') continue;
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) fail('Ngày làm không hợp lệ.');
                $parts = explode('-', $date);
                if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) fail('Ngày làm không hợp lệ.');
                $dates[$date] = $date;
            }
            $dates = array_values($dates);

            $shiftIds = [];
            foreach ($rawShiftIds as $value) {
                $shiftId = (int)$value;
                if ($shiftId > 0) $shiftIds[$shiftId] = $shiftId;
            }
            $shiftIds = array_values($shiftIds);

            if (!$employeeId || !$dates || !$shiftIds) fail('Vui lòng chọn nhân viên, ít nhất 1 ngày và ít nhất 1 ca.');

            $stmt = $conn->prepare("SELECT MaNhanVien FROM nhanvien WHERE MaNhanVien=? AND COALESCE(TrangThai,'') NOT IN ('Khóa','Nghỉ việc') LIMIT 1");
            $stmt->bind_param('i', $employeeId);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) fail('Nhân viên không tồn tại hoặc hiện không hoạt động.');

            foreach ($shiftIds as $shiftId) {
                $stmt = $conn->prepare("SELECT MaCa FROM calamviec WHERE MaCa=? AND COALESCE(TrangThai,'Hoạt động')<>'Ngừng hoạt động' LIMIT 1");
                $stmt->bind_param('i', $shiftId);
                $stmt->execute();
                if (!$stmt->get_result()->fetch_assoc()) fail('Có ca làm không tồn tại hoặc đã ngừng hoạt động.');
            }
            if ($id) {
                if (count($dates) !== 1 || count($shiftIds) !== 1) fail('Khi sửa một phân công, vui lòng chỉ chọn 1 ngày và 1 ca.');
                $date = $dates[0];
                $shiftId = $shiftIds[0];
                $stmt = $conn->prepare("SELECT MaPhanCong FROM phancongca WHERE MaNhanVien=? AND MaCa=? AND NgayLam=? AND MaPhanCong<>? LIMIT 1");
                $stmt->bind_param('iisi', $employeeId, $shiftId, $date, $id);
                $stmt->execute();
                if ($stmt->get_result()->fetch_assoc()) fail('Nhân viên này đã được phân ca này trong ngày đã chọn.');
                $stmt = $conn->prepare("UPDATE phancongca SET MaNhanVien=?,MaCa=?,NgayLam=?,GhiChu=? WHERE MaPhanCong=?");
                $stmt->bind_param('iissi', $employeeId, $shiftId, $date, $note, $id);
                $stmt->execute();
                ok(['id'=>$id], 'Đã cập nhật phân công ca.');
            }

            $conn->begin_transaction();
            $inserted = 0;
            $skipped = 0;
            $createdIds = [];
            $check = $conn->prepare("SELECT MaPhanCong FROM phancongca WHERE MaNhanVien=? AND MaCa=? AND NgayLam=? LIMIT 1");
            $insert = $conn->prepare("INSERT INTO phancongca (MaNhanVien,MaCa,NgayLam,GhiChu) VALUES (?,?,?,?)");

            foreach ($dates as $date) {
                foreach ($shiftIds as $shiftId) {
                    $check->bind_param('iis', $employeeId, $shiftId, $date);
                    $check->execute();
                    if ($check->get_result()->fetch_assoc()) {
                        $skipped++;
                        continue;
                    }
                    $insert->bind_param('iiss', $employeeId, $shiftId, $date, $note);
                    $insert->execute();
                    $createdIds[] = $conn->insert_id;
                    $inserted++;
                }
            }
            $conn->commit();

            if ($inserted === 0) fail('Các ngày và ca đã chọn đều đã được phân cho nhân viên này.');
            $message = "Đã tạo {$inserted} lịch phân công.";
            if ($skipped > 0) $message .= " Bỏ qua {$skipped} lịch bị trùng.";
            ok(['ids'=>$createdIds, 'inserted'=>$inserted, 'skipped'=>$skipped], $message);
        }

        case 'shift_assignment_delete': {
            $id = intv($data, 'id');
            if (!$id) fail('Thiếu mã phân công.');
            $stmt = $conn->prepare("DELETE FROM phancongca WHERE MaPhanCong=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            ok(null, 'Đã xóa phân công ca.');
        }

        case 'overdue_summary': {
            $rows = fetchAll($conn->query(
                "SELECT pm.MaPhieuMuon, pm.MaDocGia, dg.HoTen AS TenDocGia, dg.Email,
                        pm.NgayMuon, pm.HanTra, DATEDIFF(CURDATE(), pm.HanTra) AS SoNgayQuaHan,
                        GROUP_CONCAT(s.TenSach ORDER BY s.TenSach SEPARATOR ', ') AS TenSach,
                        COALESCE(SUM(ct.SoLuong),0) AS TongSoLuong
                 FROM phieumuon pm
                 JOIN docgia dg ON dg.MaDocGia=pm.MaDocGia
                 JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                 JOIN sach s ON s.MaSach=ct.MaSach
                 WHERE pm.NgayTra IS NULL
                   AND COALESCE(pm.TrangThai,'')='Đang mượn'
                   AND pm.HanTra < CURDATE()
                 GROUP BY pm.MaPhieuMuon,pm.MaDocGia,dg.HoTen,dg.Email,pm.NgayMuon,pm.HanTra
                 ORDER BY pm.HanTra ASC"
            ));
            $monthly = fetchAll($conn->query(
                "SELECT DATE_FORMAT(pm.NgayMuon, '%Y-%m') AS Thang, COALESCE(SUM(ct.SoLuong),0) AS SoLuong
                 FROM phieumuon pm
                 JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                 WHERE pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy')
                   AND pm.HanTra < CURDATE()
                   AND pm.NgayMuon >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
                 GROUP BY DATE_FORMAT(pm.NgayMuon, '%Y-%m') ORDER BY Thang"
            ));
            $setting = $conn->query("SELECT MucPhat FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc();
            ok(['rows'=>$rows,'monthly'=>$monthly,'fineRate'=>(float)($setting['MucPhat'] ?? 0)]);
        }

        case 'renewal_list': {
            $isCustomer = currentLibraryRoleKey() === 'customer';
            if ($isCustomer) {
                if (!hasLibraryPermission('renewals_self')) fail('Bạn không có quyền xem yêu cầu gia hạn.', 403);
                $readerId = (int)($_SESSION['reader_id'] ?? 0);
                if (!$readerId) fail('Tài khoản Khách chưa liên kết hồ sơ độc giả.');
                $stmt = $conn->prepare(
                    "SELECT gh.*, dg.HoTen AS TenDocGia, nv.HoTen AS TenNhanVienXuLy,
                            GROUP_CONCAT(s.TenSach ORDER BY s.TenSach SEPARATOR ', ') AS TenSach
                     FROM yeucaugiahan gh
                     JOIN docgia dg ON dg.MaDocGia=gh.MaDocGia
                     LEFT JOIN nhanvien nv ON nv.MaNhanVien=gh.MaNhanVienXuLy
                     JOIN chitietphieumuon ct ON ct.MaPhieuMuon=gh.MaPhieuMuon
                     JOIN sach s ON s.MaSach=ct.MaSach
                     WHERE gh.MaDocGia=?
                     GROUP BY gh.MaYeuCau,dg.HoTen,nv.HoTen
                     ORDER BY gh.MaYeuCau DESC"
                );
                $stmt->bind_param('i', $readerId); $stmt->execute();
                ok(['rows'=>fetchAll($stmt->get_result())]);
            }
            if (!hasAnyLibraryPermission(['renewals_view','renewals_manage'])) fail('Bạn không có quyền xem yêu cầu gia hạn.', 403);
            $rows = fetchAll($conn->query(
                "SELECT gh.*, dg.HoTen AS TenDocGia, dg.SDT AS SDTDocGia, nv.HoTen AS TenNhanVienXuLy,
                        GROUP_CONCAT(s.TenSach ORDER BY s.TenSach SEPARATOR ', ') AS TenSach
                 FROM yeucaugiahan gh
                 JOIN docgia dg ON dg.MaDocGia=gh.MaDocGia
                 LEFT JOIN nhanvien nv ON nv.MaNhanVien=gh.MaNhanVienXuLy
                 JOIN chitietphieumuon ct ON ct.MaPhieuMuon=gh.MaPhieuMuon
                 JOIN sach s ON s.MaSach=ct.MaSach
                 GROUP BY gh.MaYeuCau,dg.HoTen,dg.SDT,nv.HoTen
                 ORDER BY CASE WHEN gh.TrangThai='Chờ duyệt' THEN 0 ELSE 1 END, gh.MaYeuCau DESC"
            ));
            ok(['rows'=>$rows]);
        }

        case 'renewal_save': {
            if (!hasLibraryPermission('renewals_self') || currentLibraryRoleKey() !== 'customer') {
                fail('Chỉ tài khoản Khách được gửi yêu cầu gia hạn.', 403);
            }
            $readerId = (int)($_SESSION['reader_id'] ?? 0);
            $loanId = intv($data, 'loanId');
            $days = min(30, max(1, intv($data, 'days', 7)));
            if (!$readerId || !$loanId) fail('Thiếu thông tin phiếu cần gia hạn.');

            $stmt = $conn->prepare("SELECT MaDocGia, HanTra, NgayTra, TrangThai FROM phieumuon WHERE MaPhieuMuon=? LIMIT 1");
            $stmt->bind_param('i', $loanId); $stmt->execute();
            $loan = $stmt->get_result()->fetch_assoc();
            if (!$loan || (int)$loan['MaDocGia'] !== $readerId) fail('Phiếu mượn không thuộc tài khoản của bạn.');
            if (!empty($loan['NgayTra']) || ($loan['TrangThai'] ?? '') !== 'Đang mượn') fail('Chỉ phiếu đang mượn mới được yêu cầu gia hạn.');
            if (empty($loan['HanTra'])) fail('Phiếu chưa có hạn trả hợp lệ.');

            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM yeucaugiahan WHERE MaPhieuMuon=? AND TrangThai='Đã duyệt'");
            $stmt->bind_param('i', $loanId); $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] >= 2) fail('Phiếu này đã gia hạn đủ 2 lần.');

            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM yeucaugiahan WHERE MaPhieuMuon=? AND TrangThai='Chờ duyệt'");
            $stmt->bind_param('i', $loanId); $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) fail('Phiếu này đang có yêu cầu gia hạn chờ duyệt.');

            $stmt = $conn->prepare(
                "SELECT COALESCE(SUM(ct.SoLuong),0) AS SoSachQuaHan,
                        COALESCE(MAX(DATEDIFF(CURDATE(), pm.HanTra)),0) AS QuaHanLauNhat
                 FROM phieumuon pm JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                 WHERE pm.MaDocGia=? AND pm.NgayTra IS NULL
                   AND COALESCE(pm.TrangThai,'')='Đang mượn' AND pm.HanTra<CURDATE()"
            );
            $stmt->bind_param('i', $readerId); $stmt->execute();
            $overdue = $stmt->get_result()->fetch_assoc();
            $overdueBooks = (int)($overdue['SoSachQuaHan'] ?? 0);
            $maxLate = (int)($overdue['QuaHanLauNhat'] ?? 0);
            if ($maxLate > 30) fail('Bạn có sách quá hạn trên 30 ngày nên hệ thống tạm khóa chức năng gia hạn.');
            if ($overdueBooks >= 3) fail("Bạn hiện có {$overdueBooks} sách quá hạn. Vui lòng xử lý các sách quá hạn trước khi gia hạn.");

            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS total
                 FROM datlichmuon dl
                 JOIN chitietphieumuon ct ON ct.MaSach=dl.MaSach
                 WHERE ct.MaPhieuMuon=? AND dl.MaDocGia<>?
                   AND dl.TrangThai IN ('Chờ xác nhận','Đã xác nhận')
                   AND dl.NgayDuKienMuon <= DATE_ADD(?, INTERVAL ? DAY)"
            );
            $oldDue = (string)$loan['HanTra'];
            $stmt->bind_param('iisi', $loanId, $readerId, $oldDue, $days); $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) fail('Không thể gia hạn vì sách trong phiếu đang có độc giả khác đặt lịch.');

            $newDue = date('Y-m-d', strtotime($oldDue . " +{$days} days"));
            $warning = $overdueBooks > 0 ? "Độc giả đang có {$overdueBooks} sách quá hạn; nhân viên cần cân nhắc trước khi duyệt." : null;
            $status = 'Chờ duyệt';
            $stmt = $conn->prepare(
                "INSERT INTO yeucaugiahan (MaPhieuMuon,MaDocGia,SoNgayGiaHan,HanTraCu,HanTraMoi,TrangThai,CanhBao)
                 VALUES (?,?,?,?,?,?,?)"
            );
            $stmt->bind_param('iiissss', $loanId, $readerId, $days, $oldDue, $newDue, $status, $warning);
            $stmt->execute();
            ok(['id'=>$conn->insert_id,'warning'=>$warning], $warning ? 'Đã gửi yêu cầu gia hạn kèm cảnh báo quá hạn.' : 'Đã gửi yêu cầu gia hạn. Nhân viên sẽ xem và duyệt.');
        }

        case 'renewal_action': {
            $id = intv($data, 'id');
            $type = libraryPlainText(strv($data, 'type'));
            $reason = nullableString($data, 'reason');
            if (!$id || !in_array($type, ['duyet','tu choi'], true)) fail('Thao tác gia hạn không hợp lệ.');

            $conn->begin_transaction();
            $stmt = $conn->prepare("SELECT * FROM yeucaugiahan WHERE MaYeuCau=? FOR UPDATE");
            $stmt->bind_param('i', $id); $stmt->execute();
            $req = $stmt->get_result()->fetch_assoc();
            if (!$req) throw new RuntimeException('Không tìm thấy yêu cầu gia hạn.');
            if (($req['TrangThai'] ?? '') !== 'Chờ duyệt') throw new RuntimeException('Yêu cầu này đã được xử lý.');

            $staffId = (int)($_SESSION['employee_id'] ?? 0);
            if (!$staffId && currentLibraryRoleKey() === 'manager') {
                $staffId = nullableInt($data, 'staffId') ?? 0;
            }
            $staffDb = $staffId > 0 ? $staffId : null;

            if ($type === 'tu choi') {
                $status = 'Từ chối';
                $reason = $reason ?: 'Không đủ điều kiện gia hạn.';
                $stmt = $conn->prepare("UPDATE yeucaugiahan SET TrangThai=?, LyDoTuChoi=?, MaNhanVienXuLy=?, NgayXuLy=NOW() WHERE MaYeuCau=?");
                $stmt->bind_param('ssii', $status, $reason, $staffDb, $id);
                $stmt->execute(); $conn->commit();
                ok(null, 'Đã từ chối yêu cầu gia hạn.');
            }

            $loanId = (int)$req['MaPhieuMuon'];
            $readerId = (int)$req['MaDocGia'];
            $stmt = $conn->prepare("SELECT HanTra,NgayTra,TrangThai FROM phieumuon WHERE MaPhieuMuon=? FOR UPDATE");
            $stmt->bind_param('i', $loanId); $stmt->execute();
            $loan = $stmt->get_result()->fetch_assoc();
            if (!$loan || !empty($loan['NgayTra']) || ($loan['TrangThai'] ?? '') !== 'Đang mượn') throw new RuntimeException('Phiếu không còn ở trạng thái đang mượn.');

            $stmt = $conn->prepare(
                "SELECT COALESCE(SUM(ct.SoLuong),0) AS SoSachQuaHan,
                        COALESCE(MAX(DATEDIFF(CURDATE(), pm.HanTra)),0) AS QuaHanLauNhat
                 FROM phieumuon pm JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                 WHERE pm.MaDocGia=? AND pm.NgayTra IS NULL
                   AND COALESCE(pm.TrangThai,'')='Đang mượn' AND pm.HanTra<CURDATE()"
            );
            $stmt->bind_param('i', $readerId); $stmt->execute(); $overdue=$stmt->get_result()->fetch_assoc();
            if ((int)($overdue['QuaHanLauNhat'] ?? 0) > 30) throw new RuntimeException('Độc giả có sách quá hạn trên 30 ngày, không thể duyệt gia hạn.');
            if ((int)($overdue['SoSachQuaHan'] ?? 0) >= 3) throw new RuntimeException('Độc giả đang có từ 3 sách quá hạn trở lên, không thể duyệt gia hạn.');

            $stmt = $conn->prepare(
                "SELECT COUNT(*) AS total FROM datlichmuon dl
                 JOIN chitietphieumuon ct ON ct.MaSach=dl.MaSach
                 WHERE ct.MaPhieuMuon=? AND dl.MaDocGia<>?
                   AND dl.TrangThai IN ('Chờ xác nhận','Đã xác nhận')
                   AND dl.NgayDuKienMuon <= ?"
            );
            $newDue = (string)$req['HanTraMoi'];
            $stmt->bind_param('iis', $loanId, $readerId, $newDue); $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['total'] > 0) throw new RuntimeException('Sách đang có lịch đặt của độc giả khác nên không thể duyệt gia hạn.');

            $stmt = $conn->prepare("UPDATE phieumuon SET HanTra=? WHERE MaPhieuMuon=?");
            $stmt->bind_param('si', $newDue, $loanId); $stmt->execute();
            $status = 'Đã duyệt';
            $stmt = $conn->prepare("UPDATE yeucaugiahan SET TrangThai=?, MaNhanVienXuLy=?, NgayXuLy=NOW(), LyDoTuChoi=NULL WHERE MaYeuCau=?");
            $stmt->bind_param('sii', $status, $staffDb, $id); $stmt->execute();
            $conn->commit();
            ok(['newDue'=>$newDue], 'Đã duyệt gia hạn và cập nhật hạn trả mới.');
        }

        case 'statistics': {
            // Statistics Center: read-only analytics over existing library data.
            $today = date('Y-m-d');
            $startDate = trim((string)($_GET['start'] ?? ''));
            $endDate = trim((string)($_GET['end'] ?? ''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = date('Y-m-01');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) $endDate = $today;
            if ($startDate > $endDate) { $tmp=$startDate; $startDate=$endDate; $endDate=$tmp; }

            $startTs = strtotime($startDate . ' 00:00:00');
            $endTs = strtotime($endDate . ' 00:00:00');
            $rangeDays = max(1, (int)floor(($endTs - $startTs) / 86400) + 1);
            $prevEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));
            $prevStart = date('Y-m-d', strtotime($prevEnd . ' -' . ($rangeDays - 1) . ' days'));

            $escStart = $conn->real_escape_string($startDate);
            $escEnd = $conn->real_escape_string($endDate);
            $escPrevStart = $conn->real_escape_string($prevStart);
            $escPrevEnd = $conn->real_escape_string($prevEnd);

            $summary = $conn->query(
                "SELECT
                    (SELECT COALESCE(SUM(SoLuong),0) FROM sach) AS totalBooks,
                    (SELECT COUNT(*) FROM sach) AS totalTitles,
                    (SELECT COUNT(*) FROM theloai) AS totalCategories,
                    (SELECT COUNT(*) FROM sach WHERE SoLuong>0) AS availableTitles,
                    (SELECT COUNT(*) FROM sach WHERE SoLuong<=0) AS outOfStockTitles,
                    (SELECT COUNT(*) FROM docgia) AS totalReaders,
                    (SELECT COUNT(*) FROM docgia WHERE COALESCE(TrangThai,'') IN ('Đang hoạt động','Hoạt động')) AS activeReaders,
                    (SELECT COUNT(*) FROM docgia WHERE NgayDangKy BETWEEN '$escStart' AND '$escEnd') AS newReaders,
                    (SELECT COALESCE(SUM(ct.SoLuong),0) FROM chitietphieumuon ct JOIN phieumuon pm ON pm.MaPhieuMuon=ct.MaPhieuMuon WHERE pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy')) AS borrowing,
                    (SELECT COALESCE(SUM(ct.SoLuong),0) FROM chitietphieumuon ct JOIN phieumuon pm ON pm.MaPhieuMuon=ct.MaPhieuMuon WHERE pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy') AND pm.HanTra<CURDATE()) AS overdue,
                    (SELECT COUNT(*) FROM phieumuon WHERE NgayMuon BETWEEN '$escStart' AND '$escEnd') AS totalLoanSlips,
                    (SELECT COUNT(*) FROM phieumuon WHERE NgayMuon BETWEEN '$escStart' AND '$escEnd' AND NgayTra IS NOT NULL) AS returnedSlips,
                    (SELECT COUNT(*) FROM phieumuon WHERE NgayMuon BETWEEN '$escStart' AND '$escEnd' AND NgayTra IS NULL AND COALESCE(TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy')) AS activeLoanSlips,
                    (SELECT COUNT(*) FROM phieumuon WHERE NgayMuon BETWEEN '$escStart' AND '$escEnd' AND ((NgayTra IS NULL AND HanTra<CURDATE()) OR (NgayTra IS NOT NULL AND NgayTra>HanTra))) AS overdueSlips,
                    (SELECT COUNT(*) FROM phieumuon WHERE NgayMuon BETWEEN '$escStart' AND '$escEnd' AND NgayTra IS NOT NULL AND NgayTra<=HanTra) AS onTimeReturnedSlips"
            )->fetch_assoc();
            $returnedCount = (int)($summary['returnedSlips'] ?? 0);
            $onTimeCount = (int)($summary['onTimeReturnedSlips'] ?? 0);
            $summary['onTimeRate'] = $returnedCount > 0 ? round(($onTimeCount / $returnedCount) * 100, 1) : 0;

            $previous = $conn->query(
                "SELECT
                    (SELECT COUNT(*) FROM docgia WHERE NgayDangKy BETWEEN '$escPrevStart' AND '$escPrevEnd') AS newReaders,
                    (SELECT COUNT(*) FROM phieumuon WHERE NgayMuon BETWEEN '$escPrevStart' AND '$escPrevEnd') AS totalLoanSlips,
                    (SELECT COUNT(*) FROM phieumuon WHERE NgayMuon BETWEEN '$escPrevStart' AND '$escPrevEnd' AND ((NgayTra IS NULL AND HanTra<CURDATE()) OR (NgayTra IS NOT NULL AND NgayTra>HanTra))) AS overdueSlips,
                    (SELECT COUNT(*) FROM phieumuon WHERE NgayMuon BETWEEN '$escPrevStart' AND '$escPrevEnd' AND NgayTra IS NOT NULL) AS returnedSlips,
                    (SELECT COUNT(*) FROM phieumuon WHERE NgayMuon BETWEEN '$escPrevStart' AND '$escPrevEnd' AND NgayTra IS NOT NULL AND NgayTra<=HanTra) AS onTimeReturnedSlips"
            )->fetch_assoc();
            $prevReturned = (int)($previous['returnedSlips'] ?? 0);
            $prevOnTime = (int)($previous['onTimeReturnedSlips'] ?? 0);
            $previous['onTimeRate'] = $prevReturned > 0 ? round(($prevOnTime / $prevReturned) * 100, 1) : 0;

            $byCategory = fetchAll($conn->query(
                "SELECT t.MaTheLoai, t.TenTheLoai, COUNT(s.MaSach) AS SoDauSach, COALESCE(SUM(s.SoLuong),0) AS SoLuong
                 FROM theloai t LEFT JOIN sach s ON s.MaTheLoai=t.MaTheLoai
                 GROUP BY t.MaTheLoai, t.TenTheLoai ORDER BY SoLuong DESC, SoDauSach DESC, t.TenTheLoai"
            ));
            $gender = fetchAll($conn->query(
                "SELECT COALESCE(NULLIF(GioiTinh,''),'Khác') AS GioiTinh, COUNT(*) AS SoLuong FROM docgia GROUP BY COALESCE(NULLIF(GioiTinh,''),'Khác') ORDER BY SoLuong DESC"
            ));
            $readerMonthly = fetchAll($conn->query(
                "SELECT DATE_FORMAT(NgayDangKy, '%Y-%m') AS Thang, COUNT(*) AS SoLuong
                 FROM docgia WHERE NgayDangKy IS NOT NULL AND NgayDangKy BETWEEN '$escStart' AND '$escEnd'
                 GROUP BY DATE_FORMAT(NgayDangKy, '%Y-%m') ORDER BY Thang"
            ));
            $readerAreas = fetchAll($conn->query(
                "SELECT COALESCE(NULLIF(TRIM(DiaChi),''),'Chưa cập nhật') AS KhuVuc, COUNT(*) AS SoLuong
                 FROM docgia GROUP BY COALESCE(NULLIF(TRIM(DiaChi),''),'Chưa cập nhật') ORDER BY SoLuong DESC, KhuVuc LIMIT 8"
            ));
            $topReaders = fetchAll($conn->query(
                "SELECT dg.MaDocGia, dg.HoTen, dg.TrangThai,
                        COUNT(DISTINCT pm.MaPhieuMuon) AS LuotMuon,
                        COALESCE(SUM(CASE WHEN pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy') THEN ct.SoLuong ELSE 0 END),0) AS DangMuon,
                        COALESCE(SUM(CASE WHEN ((pm.NgayTra IS NULL AND pm.HanTra<CURDATE()) OR (pm.NgayTra IS NOT NULL AND pm.NgayTra>pm.HanTra)) THEN 1 ELSE 0 END),0) AS QuaHan
                 FROM docgia dg
                 LEFT JOIN phieumuon pm ON pm.MaDocGia=dg.MaDocGia AND pm.NgayMuon BETWEEN '$escStart' AND '$escEnd'
                 LEFT JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                 GROUP BY dg.MaDocGia,dg.HoTen,dg.TrangThai
                 ORDER BY LuotMuon DESC,DangMuon DESC,dg.MaDocGia DESC LIMIT 10"
            ));
            $topBooks = fetchAll($conn->query(
                "SELECT s.MaSach,s.TenSach,s.TacGia,s.HinhAnh,COALESCE(t.TenTheLoai,'Chưa phân loại') AS TenTheLoai,
                        COALESCE(SUM(CASE WHEN pm.NgayMuon BETWEEN '$escStart' AND '$escEnd' THEN ct.SoLuong ELSE 0 END),0) AS LuotMuon,
                        s.SoLuong
                 FROM sach s
                 LEFT JOIN theloai t ON t.MaTheLoai=s.MaTheLoai
                 LEFT JOIN chitietphieumuon ct ON ct.MaSach=s.MaSach
                 LEFT JOIN phieumuon pm ON pm.MaPhieuMuon=ct.MaPhieuMuon
                 GROUP BY s.MaSach,s.TenSach,s.TacGia,s.HinhAnh,t.TenTheLoai,s.SoLuong
                 ORDER BY LuotMuon DESC,s.MaSach DESC LIMIT 10"
            ));

            $groupByMonth = $rangeDays > 45;
            if ($groupByMonth) {
                $loanTrend = fetchAll($conn->query(
                    "SELECT DATE_FORMAT(NgayMuon,'%Y-%m') AS Ky,
                            COUNT(*) AS Muon,
                            SUM(CASE WHEN NgayTra IS NOT NULL THEN 1 ELSE 0 END) AS Tra,
                            SUM(CASE WHEN ((NgayTra IS NULL AND HanTra<CURDATE()) OR (NgayTra IS NOT NULL AND NgayTra>HanTra)) THEN 1 ELSE 0 END) AS QuaHan
                     FROM phieumuon WHERE NgayMuon BETWEEN '$escStart' AND '$escEnd'
                     GROUP BY DATE_FORMAT(NgayMuon,'%Y-%m') ORDER BY Ky"
                ));
            } else {
                $loanTrend = fetchAll($conn->query(
                    "SELECT DATE_FORMAT(NgayMuon,'%Y-%m-%d') AS Ky,
                            COUNT(*) AS Muon,
                            SUM(CASE WHEN NgayTra IS NOT NULL THEN 1 ELSE 0 END) AS Tra,
                            SUM(CASE WHEN ((NgayTra IS NULL AND HanTra<CURDATE()) OR (NgayTra IS NOT NULL AND NgayTra>HanTra)) THEN 1 ELSE 0 END) AS QuaHan
                     FROM phieumuon WHERE NgayMuon BETWEEN '$escStart' AND '$escEnd'
                     GROUP BY DATE_FORMAT(NgayMuon,'%Y-%m-%d') ORDER BY Ky"
                ));
            }

            $loanStatus = [
                ['TrangThai'=>'Đang mượn','SoLuong'=>(int)($summary['activeLoanSlips'] ?? 0)],
                ['TrangThai'=>'Đã trả','SoLuong'=>(int)($summary['returnedSlips'] ?? 0)],
                ['TrangThai'=>'Quá hạn','SoLuong'=>(int)($summary['overdueSlips'] ?? 0)],
            ];
            $latestReaders = fetchAll($conn->query(
                "SELECT MaDocGia,HoTen,NgayDangKy,TrangThai FROM docgia WHERE NgayDangKy IS NOT NULL ORDER BY NgayDangKy DESC,MaDocGia DESC LIMIT 6"
            ));
            $recentLoans = fetchAll($conn->query(
                "SELECT pm.MaPhieuMuon,pm.NgayMuon,pm.HanTra,pm.NgayTra,pm.TrangThai,dg.HoTen AS DocGia,
                        GROUP_CONCAT(DISTINCT s.TenSach ORDER BY s.TenSach SEPARATOR ', ') AS Sach,
                        COALESCE(SUM(ct.SoLuong),0) AS SoLuong
                 FROM phieumuon pm
                 LEFT JOIN docgia dg ON dg.MaDocGia=pm.MaDocGia
                 LEFT JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                 LEFT JOIN sach s ON s.MaSach=ct.MaSach
                 WHERE pm.NgayMuon BETWEEN '$escStart' AND '$escEnd'
                 GROUP BY pm.MaPhieuMuon,pm.NgayMuon,pm.HanTra,pm.NgayTra,pm.TrangThai,dg.HoTen
                 ORDER BY pm.NgayMuon DESC,pm.MaPhieuMuon DESC LIMIT 30"
            ));

            // Keep the original statistics feeds intact for the legacy chart pages.
            // These intentionally ignore the Statistics Center global range because the old pages
            // were designed around the latest six calendar months.
            $legacyReaderMonthly = fetchAll($conn->query(
                "SELECT DATE_FORMAT(NgayDangKy, '%Y-%m') AS Thang, COUNT(*) AS SoLuong
                 FROM docgia
                 WHERE NgayDangKy IS NOT NULL
                   AND NgayDangKy >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
                 GROUP BY DATE_FORMAT(NgayDangKy, '%Y-%m')
                 ORDER BY Thang"
            ));
            $borrowingCategory = fetchAll($conn->query(
                "SELECT COALESCE(t.TenTheLoai,'Chưa phân loại') AS TenTheLoai, COALESCE(SUM(ct.SoLuong),0) AS SoLuong
                 FROM chitietphieumuon ct
                 JOIN phieumuon pm ON pm.MaPhieuMuon=ct.MaPhieuMuon
                 JOIN sach s ON s.MaSach=ct.MaSach
                 LEFT JOIN theloai t ON t.MaTheLoai=s.MaTheLoai
                 WHERE pm.NgayTra IS NULL
                   AND COALESCE(pm.TrangThai,'') NOT IN ('Chờ duyệt','Đã trả','Đã hủy','Hủy')
                 GROUP BY t.MaTheLoai,t.TenTheLoai
                 ORDER BY SoLuong DESC LIMIT 5"
            ));
            $borrowingCategoryMonthly = fetchAll($conn->query(
                "SELECT DATE_FORMAT(pm.NgayMuon, '%Y-%m') AS Thang,
                        COALESCE(t.TenTheLoai,'Chưa phân loại') AS TenTheLoai,
                        COALESCE(SUM(ct.SoLuong),0) AS SoLuong
                 FROM chitietphieumuon ct
                 JOIN phieumuon pm ON pm.MaPhieuMuon=ct.MaPhieuMuon
                 JOIN sach s ON s.MaSach=ct.MaSach
                 LEFT JOIN theloai t ON t.MaTheLoai=s.MaTheLoai
                 WHERE pm.NgayTra IS NULL
                   AND COALESCE(pm.TrangThai,'')='Đang mượn'
                   AND pm.NgayMuon >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
                 GROUP BY DATE_FORMAT(pm.NgayMuon, '%Y-%m'), t.MaTheLoai, t.TenTheLoai
                 ORDER BY Thang, TenTheLoai"
            ));
            $overdueMonthly = fetchAll($conn->query(
                "SELECT DATE_FORMAT(pm.NgayMuon, '%Y-%m') AS Thang, COALESCE(SUM(ct.SoLuong),0) AS SoLuong
                 FROM phieumuon pm
                 JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                 WHERE pm.NgayTra IS NULL
                   AND COALESCE(pm.TrangThai,'')='Đang mượn'
                   AND pm.HanTra < CURDATE()
                   AND pm.NgayMuon >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
                 GROUP BY DATE_FORMAT(pm.NgayMuon, '%Y-%m')
                 ORDER BY Thang"
            ));

            ok([
                'range'=>['start'=>$startDate,'end'=>$endDate,'days'=>$rangeDays,'previousStart'=>$prevStart,'previousEnd'=>$prevEnd,'groupByMonth'=>$groupByMonth],
                'summary'=>$summary,'previous'=>$previous,'byCategory'=>$byCategory,'gender'=>$gender,'readerMonthly'=>$readerMonthly,
                'legacyReaderMonthly'=>$legacyReaderMonthly,
                'readerAreas'=>$readerAreas,'topReaders'=>$topReaders,'topBooks'=>$topBooks,'loanTrend'=>$loanTrend,'loanStatus'=>$loanStatus,
                'latestReaders'=>$latestReaders,'recentLoans'=>$recentLoans,
                'borrowingCategory'=>$borrowingCategory,'borrowingCategoryMonthly'=>$borrowingCategoryMonthly,'overdueMonthly'=>$overdueMonthly
            ]);
        }

        case 'settings': {
            $settings = $conn->query("SELECT * FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc();
            $accounts = [];
            $employees = [];
            $readers = [];
            $recentRegistrations = [];
            $newRegistrationCount = 0;
            if (hasLibraryPermission('accounts_manage')) {
                $accounts = fetchAll($conn->query(
                    "SELECT tk.MaTaiKhoan, tk.TenDangNhap, tk.LoaiTaiKhoan, tk.MaNhanVien, tk.MaDocGia, tk.NgayTao, tk.TrangThai,
                            COALESCE(nv.HoTen, dg.HoTen, tk.TenDangNhap) AS HoTen
                     FROM taikhoan tk
                     LEFT JOIN nhanvien nv ON nv.MaNhanVien=tk.MaNhanVien
                     LEFT JOIN docgia dg ON dg.MaDocGia=tk.MaDocGia
                     ORDER BY tk.MaTaiKhoan ASC"
                ));
                foreach ($accounts as &$account) {
                    $key = libraryRoleKeyFromValue($account['LoaiTaiKhoan'] ?? '');
                    if ($key !== 'unknown') $account['LoaiTaiKhoan'] = libraryRoleLabel($key);
                }
                unset($account);
                $recentRows = fetchAll($conn->query(
                    "SELECT tk.MaTaiKhoan, tk.TenDangNhap, tk.LoaiTaiKhoan, tk.MaDocGia, tk.NgayTao, tk.TrangThai,
                            COALESCE(dg.HoTen, tk.TenDangNhap) AS HoTen
                     FROM taikhoan tk
                     LEFT JOIN docgia dg ON dg.MaDocGia=tk.MaDocGia
                     WHERE tk.NgayTao >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                     ORDER BY tk.NgayTao DESC, tk.MaTaiKhoan DESC"
                ));
                foreach ($recentRows as $row) {
                    if (libraryRoleKeyFromValue($row['LoaiTaiKhoan'] ?? '') !== 'customer') continue;
                    $row['LoaiTaiKhoan'] = libraryRoleLabel('customer');
                    $recentRegistrations[] = $row;
                }
                $newRegistrationCount = count($recentRegistrations);

                $employees = fetchAll($conn->query("SELECT MaNhanVien, HoTen, TrangThai FROM nhanvien ORDER BY MaNhanVien ASC"));
                $readers = fetchAll($conn->query("SELECT MaDocGia, HoTen, TrangThai FROM docgia ORDER BY MaDocGia ASC"));
            }
            ok([
                'settings' => $settings,
                'accounts' => $accounts,
                'employees' => $employees,
                'readers' => $readers,
                'recentRegistrations' => $recentRegistrations,
                'newRegistrationCount' => $newRegistrationCount,
                'currentUserId' => (int)($_SESSION['user_id'] ?? 0),
                'canManageAccounts' => hasLibraryPermission('accounts_manage'),
                'system' => [
                    'phpVersion' => PHP_VERSION,
                    'mysqlVersion' => $conn->server_info,
                    'database' => 'web_qlthuvien',
                    'serverTime' => date('d/m/Y H:i:s'),
                ]
            ]);
        }

        case 'settings_save': {
            $name = strv($data, 'libraryName');
            $address = nullableString($data, 'address');
            $email = nullableString($data, 'email');
            $phone = nullableString($data, 'phone');
            $borrowDays = max(1, intv($data, 'borrowDays', 14));
            $renewDays = max(1, intv($data, 'renewDays', 7));
            $fine = max(0, (float)($data['fine'] ?? 0));
            if ($name === '') fail('Tên thư viện không được để trống.');
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Email thư viện không hợp lệ.');

            $row = $conn->query("SELECT MaCaiDat FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc();
            if ($row) {
                $id = (int)$row['MaCaiDat'];
                $stmt = $conn->prepare("UPDATE caidat SET TenThuVien=?, DiaChi=?, Email=?, SDT=?, SoNgayMuon=?, SoNgayGiaHan=?, MucPhat=? WHERE MaCaiDat=?");
                $stmt->bind_param('ssssiidi', $name, $address, $email, $phone, $borrowDays, $renewDays, $fine, $id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO caidat (TenThuVien, DiaChi, Email, SDT, SoNgayMuon, SoNgayGiaHan, MucPhat) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssiid', $name, $address, $email, $phone, $borrowDays, $renewDays, $fine);
                $stmt->execute();
            }
            ok(null, 'Đã lưu cài đặt.');
        }

        case 'email_test': {
            if (currentLibraryRoleKey() !== 'manager') fail('Chỉ Quản lý được gửi email thử.', 403);
            $to = strv($data, 'email');
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) fail('Vui lòng nhập email nhận hợp lệ.');
            $cfg = require __DIR__ . '/config/email.php';
            $library = $conn->query("SELECT TenThuVien FROM caidat ORDER BY MaCaiDat LIMIT 1")->fetch_assoc();
            $libraryName = trim((string)($library['TenThuVien'] ?? 'Quản lý thư viện')) ?: 'Quản lý thư viện';
            $subject = 'Email thử từ hệ thống thư viện';
            $html = '<p>Xin chào,</p><p>Đây là email thử từ <b>' . htmlspecialchars($libraryName, ENT_QUOTES, 'UTF-8') . '</b>.</p><p>Nếu bạn nhận được email này thì cấu hình SMTP đang hoạt động bình thường.</p><p>Thời gian gửi: ' . date('d/m/Y H:i:s') . '</p>';
            smtpSendLibraryMail($cfg, $to, $to, $subject, $html);
            ok(null, 'Đã gửi email thử. Hãy kiểm tra Hộp thư đến hoặc Spam.');
        }

        case 'email_overdue': {
            if (!in_array(currentLibraryRoleKey(), ['manager','employee'], true)) fail('Chỉ Quản lý hoặc Nhân viên được gửi mail nhắc quá hạn.', 403);
            $result = processOverdueEmailNotifications($conn);
            if (empty($result['emailEnabled'])) fail('Chức năng email đang tắt. Hãy cấu hình config/email.php trước.');
            $message = 'Đã kiểm tra email quá hạn >30 ngày: gửi ' . (int)$result['sent'] . ', lỗi ' . (int)$result['failed'] . ', bỏ qua ' . (int)$result['skipped'] . '.';
            ok($result, $message);
        }

        case 'account_save': {
            $id = nullableInt($data, 'id');
            $username = strv($data, 'username');
            $password = strv($data, 'password');
            $roleKey = libraryRoleKeyFromValue(strv($data, 'role', 'Nhân viên'));
            $status = libraryAccountIsLocked(strv($data, 'status', 'Đang hoạt động')) ? 'Khóa' : 'Đang hoạt động';
            $employeeId = nullableInt($data, 'employeeId');
            $readerId = nullableInt($data, 'readerId');

            if (strlen($username) < 3) fail('Tên đăng nhập phải có ít nhất 3 ký tự.');
            if ($roleKey === 'unknown') fail('Vai trò không hợp lệ. Chỉ dùng Admin, Quản lý, Nhân viên hoặc Khách.');
            $role = libraryRoleLabel($roleKey);

            if ($id) {
                $check = $conn->prepare("SELECT MaTaiKhoan FROM taikhoan WHERE TenDangNhap=? AND MaTaiKhoan<>? LIMIT 1");
                $check->bind_param('si', $username, $id);
            } else {
                $check = $conn->prepare("SELECT MaTaiKhoan FROM taikhoan WHERE TenDangNhap=? LIMIT 1");
                $check->bind_param('s', $username);
            }
            $check->execute();
            if ($check->get_result()->fetch_assoc()) fail('Tên đăng nhập đã tồn tại.');

            $currentUserId = (int)($_SESSION['user_id'] ?? 0);
            if ($id && $id === $currentUserId) {
                if ($roleKey !== 'admin') fail('Admin đang đăng nhập không thể tự hạ quyền của chính mình.');
                if ($status === 'Khóa') fail('Admin đang đăng nhập không thể tự khóa tài khoản của chính mình.');
            }

            if ($roleKey === 'customer') {
                $employeeId = null;
                if (!$readerId) fail('Tài khoản Khách phải liên kết với một độc giả để có thể mượn sách.');
                if ($id) {
                    $checkLink = $conn->prepare('SELECT MaTaiKhoan FROM taikhoan WHERE MaDocGia=? AND MaTaiKhoan<>? LIMIT 1');
                    $checkLink->bind_param('ii', $readerId, $id);
                } else {
                    $checkLink = $conn->prepare('SELECT MaTaiKhoan FROM taikhoan WHERE MaDocGia=? LIMIT 1');
                    $checkLink->bind_param('i', $readerId);
                }
                $checkLink->execute();
                if ($checkLink->get_result()->fetch_assoc()) fail('Độc giả này đã được liên kết với một tài khoản khác.');
            } elseif (in_array($roleKey, ['manager','employee'], true)) {
                $readerId = null;
                if (!$employeeId) fail('Tài khoản Quản lý/Nhân viên phải liên kết với một hồ sơ nhân viên.');
                if ($id) {
                    $checkLink = $conn->prepare('SELECT MaTaiKhoan FROM taikhoan WHERE MaNhanVien=? AND MaTaiKhoan<>? LIMIT 1');
                    $checkLink->bind_param('ii', $employeeId, $id);
                } else {
                    $checkLink = $conn->prepare('SELECT MaTaiKhoan FROM taikhoan WHERE MaNhanVien=? LIMIT 1');
                    $checkLink->bind_param('i', $employeeId);
                }
                $checkLink->execute();
                if ($checkLink->get_result()->fetch_assoc()) fail('Nhân viên này đã được liên kết với một tài khoản khác.');
            } else {
                $employeeId = null;
                $readerId = null;
            }

            if ($id) {
                if ($password !== '') {
                    if (strlen($password) < 6) fail('Mật khẩu phải có ít nhất 6 ký tự.');
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE taikhoan SET TenDangNhap=?, MatKhau=?, LoaiTaiKhoan=?, MaNhanVien=?, MaDocGia=?, TrangThai=? WHERE MaTaiKhoan=?");
                    $stmt->bind_param('sssiisi', $username, $hash, $role, $employeeId, $readerId, $status, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE taikhoan SET TenDangNhap=?, LoaiTaiKhoan=?, MaNhanVien=?, MaDocGia=?, TrangThai=? WHERE MaTaiKhoan=?");
                    $stmt->bind_param('ssiisi', $username, $role, $employeeId, $readerId, $status, $id);
                }
                $stmt->execute();
                ok(['id' => $id], 'Đã cập nhật tài khoản và phân quyền.');
            }

            if (strlen($password) < 6) fail('Mật khẩu phải có ít nhất 6 ký tự.');
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO taikhoan (TenDangNhap, MatKhau, LoaiTaiKhoan, MaNhanVien, MaDocGia, TrangThai) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssiis', $username, $hash, $role, $employeeId, $readerId, $status);
            $stmt->execute();
            ok(['id' => $conn->insert_id], 'Đã thêm tài khoản.');
        }

        case 'account_toggle': {
            $id = intv($data, 'id');
            if (!$id) fail('Thiếu mã tài khoản.');
            if ($id === (int)($_SESSION['user_id'] ?? 0)) fail('Bạn không thể tự khóa tài khoản đang đăng nhập.');
            $stmt = $conn->prepare("SELECT TrangThai FROM taikhoan WHERE MaTaiKhoan=? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if (!$row) fail('Không tìm thấy tài khoản.');
            $newStatus = libraryAccountIsLocked($row['TrangThai'] ?? '') ? 'Đang hoạt động' : 'Khóa';
            $stmt = $conn->prepare("UPDATE taikhoan SET TrangThai=? WHERE MaTaiKhoan=?");
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
            ok(['status' => $newStatus], $newStatus === 'Khóa' ? 'Đã khóa tài khoản.' : 'Đã mở khóa tài khoản.');
        }

        case 'account_delete': {
            $id = intv($data, 'id');
            if (!$id) fail('Thiếu mã tài khoản.');
            if ($id === (int)($_SESSION['user_id'] ?? 0)) fail('Bạn không thể xóa tài khoản đang đăng nhập.');
            $stmt = $conn->prepare("DELETE FROM taikhoan WHERE MaTaiKhoan=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) fail('Không tìm thấy tài khoản.');
            ok(null, 'Đã xóa tài khoản.');
        }

        case 'backup': {
            $tables = ['caidat','theloai','sach','docgia','nhanvien','phieumuon','chitietphieumuon','datlichmuon','calamviec','phancongca','yeucaugiahan','thongbaoemail','taikhoan'];
            $backup = [
                'database' => 'web_qlthuvien',
                'createdAt' => date(DATE_ATOM),
                'tables' => []
            ];
            foreach ($tables as $table) {
                $backup['tables'][$table] = fetchAll($conn->query("SELECT * FROM `$table`"));
            }
            ok($backup, 'Đã tạo dữ liệu sao lưu.');
        }

        default:
            fail('Action API không hợp lệ.', 404);
    }
} catch (mysqli_sql_exception $e) {
    if ($conn->errno || $conn->warning_count >= 0) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
    }
    $message = $e->getCode() === 1062
        ? 'Dữ liệu bị trùng. Vui lòng kiểm tra tài khoản, lịch đặt hoặc lịch phân công đã tồn tại.'
        : 'Lỗi MySQL: ' . $e->getMessage();
    fail($message, 500);
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    fail($e->getMessage(), 400);
}
