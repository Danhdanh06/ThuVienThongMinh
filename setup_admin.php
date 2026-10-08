<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
session_start();
require_once __DIR__ . '/database/connect.php';

$countRow = $conn->query("SELECT COUNT(*) AS total FROM taikhoan")->fetch_assoc();
if ((int)($countRow['total'] ?? 0) > 0) {
    header('Location: dangnhap.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (strlen($username) < 3) {
        $message = 'Tên đăng nhập phải có ít nhất 3 ký tự.';
    } elseif (strlen($password) < 8) {
        $message = 'Mật khẩu phải có ít nhất 8 ký tự.';
    } elseif ($password !== $confirm) {
        $message = 'Mật khẩu xác nhận không khớp.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'Admin';
        $status = 'Đang hoạt động';
        $stmt = $conn->prepare("INSERT INTO taikhoan (TenDangNhap, MatKhau, LoaiTaiKhoan, TrangThai) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $username, $hash, $role, $status);
        $stmt->execute();
        header('Location: dangnhap.php?setup=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tạo Admin đầu tiên</title>
<style>
*{box-sizing:border-box;font-family:Arial,Helvetica,sans-serif}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f3f8ff;color:#0f172a;padding:20px}.box{width:min(460px,100%);background:#fff;border:1px solid #dbe7f5;border-radius:18px;padding:30px;box-shadow:0 18px 50px rgba(15,23,42,.1)}h1{font-size:25px;margin:0 0 8px}p{color:#64748b;line-height:1.5}.field{display:grid;gap:7px;margin-top:16px}.field span{font-weight:700;font-size:14px}.field input{height:48px;border:1px solid #cbd5e1;border-radius:10px;padding:0 12px;font-size:15px;outline:none}.field input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}button{width:100%;height:48px;border:0;border-radius:10px;background:#1265e8;color:#fff;font-weight:700;font-size:16px;margin-top:22px;cursor:pointer}.err{background:#fff0ef;color:#b42318;border:1px solid #f4bbb6;padding:11px 12px;border-radius:10px;margin-top:14px}.note{font-size:13px;background:#eff6ff;color:#36536f;padding:11px 12px;border-radius:10px;margin-top:14px}
</style></head><body><main class="box"><h1>Tạo Admin đầu tiên</h1><p>Database chưa có tài khoản. Hãy tự đặt tài khoản quản trị thay vì dùng mật khẩu mặc định.</p>
<?php if ($message): ?><div class="err"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="post"><label class="field"><span>Tên đăng nhập</span><input name="username" minlength="3" required autocomplete="username"></label><label class="field"><span>Mật khẩu</span><input type="password" name="password" minlength="8" required autocomplete="new-password"></label><label class="field"><span>Nhập lại mật khẩu</span><input type="password" name="confirm_password" minlength="8" required autocomplete="new-password"></label><div class="note">Trang này chỉ hoạt động khi bảng tài khoản đang trống. Dữ liệu cũ không bị thay đổi.</div><button type="submit">Tạo tài khoản Admin</button></form></main></body></html>
