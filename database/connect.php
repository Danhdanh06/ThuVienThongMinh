<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(
        "localhost",
        "root",
        "",
        "web_qlthuvien"
    );
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die("Kết nối MySQL thất bại. Hãy kiểm tra XAMPP/MySQL và database web_qlthuvien. Chi tiết: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
function ensureLibraryDefaults(mysqli $conn): void
{
    try {
        $result = $conn->query("SELECT COUNT(*) AS total FROM caidat");
        $row = $result->fetch_assoc();
        if ((int)($row['total'] ?? 0) === 0) {
            $stmt = $conn->prepare(
                "INSERT INTO caidat (TenThuVien, DiaChi, Email, SDT, SoNgayMuon, SoNgayGiaHan, MucPhat)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $name = 'Thư viện';
            $address = '';
            $email = '';
            $phone = '';
            $borrowDays = 14;
            $renewDays = 7;
            $fine = 5000.00;
            $stmt->bind_param('ssssiid', $name, $address, $email, $phone, $borrowDays, $renewDays, $fine);
            $stmt->execute();
        }

        // Không tự tạo tài khoản Admin với mật khẩu mặc định.
        // Nếu database chưa có tài khoản, dangnhap.php sẽ chuyển sang setup_admin.php
        // để người cài đặt tự tạo Admin đầu tiên với mật khẩu riêng.
    } catch (mysqli_sql_exception $e) {
    }
}

ensureLibraryDefaults($conn);

require_once __DIR__ . '/schema_upgrade.php';
ensureLibraryFeatureTables($conn);

require_once __DIR__ . '/advanced_features.php';
ensureAdvancedLibraryFeatures($conn);
