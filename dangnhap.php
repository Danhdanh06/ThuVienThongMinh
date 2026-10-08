<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
session_start();
require_once __DIR__ . '/database/connect.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database/mail_helper.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$accountCount = $conn->query("SELECT COUNT(*) AS total FROM taikhoan")->fetch_assoc();
if ((int)($accountCount['total'] ?? 0) === 0) {
    header('Location: setup_admin.php');
    exit;
}

$loginMessage = '';
$loginType = 'error';

if (isset($_GET['registered'])) {
    $loginMessage = 'Đăng ký thành công. Bạn có thể đăng nhập bằng tài khoản vừa tạo.';
    $loginType = 'success';
} elseif (isset($_GET['setup'])) {
    $loginMessage = 'Đã tạo tài khoản Admin đầu tiên. Hãy đăng nhập bằng mật khẩu bạn vừa đặt.';
    $loginType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    if ($remember && $username != '') {
        setcookie('libraryUsername', $username, [
            'expires' => time() + 30 * 24 * 60 * 60,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } elseif (!$remember) {
        setcookie('libraryUsername', '', time() - 3600, '/');
    }

    if ($username === '' || $password === '') {
        $loginMessage = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $stmt = $conn->prepare(
            "SELECT tk.MaTaiKhoan, tk.TenDangNhap, tk.MatKhau, tk.LoaiTaiKhoan, tk.MaNhanVien, tk.MaDocGia, tk.TrangThai, tk.LanDangNhapSai, tk.KhoaDangNhapDen,
                    COALESCE(nv.HoTen, dg.HoTen, tk.TenDangNhap) AS HoTen
             FROM taikhoan tk
             LEFT JOIN nhanvien nv ON nv.MaNhanVien = tk.MaNhanVien
             LEFT JOIN docgia dg ON dg.MaDocGia = tk.MaDocGia
             WHERE tk.TenDangNhap = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();
        $passwordOk = false;
        $temporarilyLocked = false;
        if ($account) {
            $lockUntil = !empty($account['KhoaDangNhapDen']) ? strtotime((string)$account['KhoaDangNhapDen']) : false;
            $temporarilyLocked = $lockUntil !== false && $lockUntil > time();
            if (!$temporarilyLocked) {
                $stored = (string)$account['MatKhau'];
                $passwordOk = password_verify($password, $stored) || hash_equals($stored, $password);
            }
        }

        if ($temporarilyLocked) {
            $lockUntilText = date('H:i d/m/Y', strtotime((string)$account['KhoaDangNhapDen']));
            $loginMessage = 'Bạn đã nhập sai quá nhiều lần. Tài khoản tạm khóa đăng nhập đến ' . $lockUntilText . '.';
        } elseif (!$account || !$passwordOk) {
            if ($account) {
                $accountId = (int)$account['MaTaiKhoan'];
                $failed = (int)($account['LanDangNhapSai'] ?? 0) + 1;
                if ($failed >= 5) {
                    $lock = $conn->prepare("UPDATE taikhoan SET LanDangNhapSai=0, KhoaDangNhapDen=DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE MaTaiKhoan=?");
                    $lock->bind_param('i', $accountId);
                    $lock->execute();
                    $loginMessage = 'Bạn đã nhập sai 5 lần. Tài khoản tạm khóa đăng nhập trong 10 phút.';
                } else {
                    $updateFail = $conn->prepare("UPDATE taikhoan SET LanDangNhapSai=?, KhoaDangNhapDen=NULL WHERE MaTaiKhoan=?");
                    $updateFail->bind_param('ii', $failed, $accountId);
                    $updateFail->execute();
                    $remaining = 5 - $failed;
                    $loginMessage = 'Tên đăng nhập hoặc mật khẩu không chính xác. Còn ' . $remaining . '/5 lần thử trước khi khóa tạm thời.';
                }
            } else {
                $loginMessage = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
            }
        } elseif (libraryAccountIsLocked($account['TrangThai'] ?? '')) {
            $loginMessage = 'Tài khoản hiện đang bị khóa. Vui lòng liên hệ Admin để mở lại.';
        } else {
            $accountId = (int)$account['MaTaiKhoan'];
            $resetAttempts = $conn->prepare("UPDATE taikhoan SET LanDangNhapSai=0, KhoaDangNhapDen=NULL WHERE MaTaiKhoan=?");
            $resetAttempts->bind_param('i', $accountId);
            $resetAttempts->execute();
            if (!password_get_info((string)$account['MatKhau'])['algo']) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare('UPDATE taikhoan SET MatKhau=? WHERE MaTaiKhoan=?');
                $id = (int)$account['MaTaiKhoan'];
                $update->bind_param('si', $newHash, $id);
                $update->execute();
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$account['MaTaiKhoan'];
            $_SESSION['username'] = $account['TenDangNhap'];
            $_SESSION['display_name'] = $account['HoTen'];
            $roleKey = libraryRoleKeyFromValue($account['LoaiTaiKhoan'] ?? '');
            if ($roleKey == 'unknown') {
                $_SESSION = [];
                session_destroy();
                $loginMessage = 'Tài khoản chưa được phân quyền hợp lệ.';
            } else {
                $_SESSION['role_key'] = $roleKey;
                $_SESSION['role'] = libraryRoleLabel($roleKey);
                $_SESSION['employee_id'] = $account['MaNhanVien'] != null ? (int)$account['MaNhanVien'] : null;
                $_SESSION['reader_id'] = $account['MaDocGia'] != null ? (int)$account['MaDocGia'] : null;
                if ($roleKey === 'customer' && !empty($_SESSION['reader_id'])) {
                    $readerId = (int)$_SESSION['reader_id'];
                    $stmtOverdue = $conn->prepare(
                        "SELECT COALESCE(SUM(ct.SoLuong),0) AS SoSach,
                                COALESCE(MAX(DATEDIFF(CURDATE(), pm.HanTra)),0) AS QuaHanLauNhat
                         FROM phieumuon pm
                         JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
                         WHERE pm.MaDocGia=? AND pm.NgayTra IS NULL
                           AND COALESCE(pm.TrangThai,'')='Đang mượn'
                           AND pm.HanTra<CURDATE()"
                    );
                    $stmtOverdue->bind_param('i', $readerId);
                    $stmtOverdue->execute();
                    $late = $stmtOverdue->get_result()->fetch_assoc();
                    $lateBooks = (int)($late['SoSach'] ?? 0);
                    $maxLate = (int)($late['QuaHanLauNhat'] ?? 0);
                    if ($lateBooks > 0) {
                        $_SESSION['overdue_login_notice'] = [
                            'books' => $lateBooks,
                            'maxDays' => $maxLate
                        ];
                    }
                }
                try { processOverdueEmailNotifications($conn); } catch (Throwable $ignored) {}
                header('Location: index.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Quản lý thư viện</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }
        body {
            min-height: 100vh;
            background: #f3f8ff;
            color: #101d38;
        }
        button, input {
            font: inherit;
        }
        .login-page {
            position: relative;
            width: 100%;
            min-height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 45px 55px 105px;
            background:
                linear-gradient(
                    115deg,
                    #f2f7ff 0%,
                    #f8fbff 55%,
                    #f3f8ff 100%
                );
        }
        .decor-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(211, 226, 248, 0.5);
            pointer-events: none;
        }
        .circle-top-left {
            width: 390px;
            height: 390px;
            top: -245px;
            left: -150px;
        }
        .circle-bottom-left {
            width: 430px;
            height: 430px;
            bottom: -310px;
            left: -140px;
        }
        .dots {
            position: absolute;
            width: 82px;
            height: 82px;
            background-image:
                radial-gradient(circle, #a9c9ef 3px, transparent 3.5px);

            background-size: 22px 22px;
            pointer-events: none;
        }
        .dots-top {
            top: 43px;
            left: 28px;
        }
        .dots-bottom {
            left: 35px;
            bottom: 105px;
        }
        .container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1280px;
            display: grid;
            grid-template-columns: minmax(500px, 1fr) 555px;
            align-items: center;
            gap: 75px;
        }
        .left-section {
            text-align: center;
            padding-top: 5px;
        }

        .logo-icon {
            color: #1369eb;
            font-size: 62px;
            margin-bottom: 17px;
        }

        .library-title {
            font-size: 42px;
            line-height: 1.15;
            font-weight: 750;
            letter-spacing: 1px;
            color: #0d1c39;
            margin-bottom: 12px;
        }

        .library-description {
            font-size: 18px;
            color: #707887;
            margin-bottom: 35px;
        }

        .library-image {
            display: block;
            width: 100%;
            max-width: 610px;
            height: 390px;
            margin: 0 auto;
            object-fit: contain;
        }
        .login-card {
            width: 100%;
            min-height: 680px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #edf0f5;
            border-radius: 7px;
            padding: 72px 55px 55px;
            box-shadow:
                0 12px 28px rgba(39, 65, 100, 0.08),
                0 2px 8px rgba(39, 65, 100, 0.04);
        }

        .login-header {
            text-align: center;
            margin-bottom: 45px;
        }

        .login-header h2 {
            font-size: 34px;
            font-weight: 750;
            color: #0d1c39;
            margin-bottom: 13px;
        }

        .login-header p {
            font-size: 16px;
            color: #737b88;
        }
        .form-group {
            margin-bottom: 29px;
        }

        .form-group label {
            display: block;
            font-size: 16px;
            font-weight: 600;
            color: #222a38;
            margin-bottom: 11px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            height: 62px;

            border: 1px solid #cfd4dc;
            border-radius: 5px;
            outline: none;

            background: #ffffff;
            color: #252d3b;

            font-size: 16px;
            padding: 0 52px 0 57px;

            transition: 0.25s;
        }

        .input-wrapper input::placeholder {
            color: #8a909b;
        }

        .input-wrapper input:focus {
            border-color: #1769e8;
            box-shadow: 0 0 0 3px rgba(23, 105, 232, 0.12);
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 19px;
            transform: translateY(-50%);

            color: #4e5766;
            font-size: 20px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 18px;
            transform: translateY(-50%);

            border: none;
            outline: none;
            background: transparent;

            color: #4d5665;
            font-size: 20px;
            cursor: pointer;
        }

        .toggle-password:hover {
            color: #1769e8;
        }
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 20px;
            margin-top: 1px;
            margin-bottom: 38px;
        }

        .remember-login {
            display: flex;
            align-items: center;
            gap: 10px;

            font-size: 15px;
            color: #252d3a;
            cursor: pointer;
        }

        .remember-login input {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #1769e8;
        }

        .forgot-password {
            color: #155fd5;
            font-size: 15px;
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }
        .login-button {
            width: 100%;
            height: 62px;

            border: none;
            border-radius: 5px;

            background: #1265e8;
            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;

            font-size: 19px;
            cursor: pointer;
            transition: 0.25s;
        }

        .login-button i {
            font-size: 19px;
        }

        .login-button:hover {
            background: #0957d2;
            transform: translateY(-1px);
        }

        .login-button:active {
            transform: translateY(0);
        }
        .message {
            display: none;
            margin-top: 18px;
            padding: 13px 15px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
        }

        .message.error {
            display: block;
            color: #b42318;
            background: #fff0ef;
            border: 1px solid #f4bbb6;
        }

        .message.success {
            display: block;
            color: #067647;
            background: #ecfdf3;
            border: 1px solid #a9e5c7;
        }

        .register-link { margin-top:18px; text-align:center; font-size:15px; color:#64748b; }
        .register-link a { color:#1265e8; font-weight:700; text-decoration:none; }
        .register-link a:hover { text-decoration:underline; }
        .reset-modal { position:fixed; inset:0; background:rgba(15,23,42,.5); display:none; align-items:center; justify-content:center; padding:20px; z-index:100; }
        .reset-modal.show { display:flex; }
        .reset-box { width:min(460px,100%); background:#fff; border-radius:18px; padding:24px; box-shadow:0 24px 70px rgba(15,23,42,.28); }
        .reset-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:18px; }
        .reset-head h3 { margin:0; font-size:22px; }
        .reset-head p { margin:6px 0 0; color:#64748b; font-size:13px; line-height:1.5; }
        .reset-close { border:0; width:36px; height:36px; border-radius:50%; background:#f1f5f9; cursor:pointer; font-size:22px; }
        .reset-fields { display:grid; gap:14px; }
        .reset-fields label { display:grid; gap:7px; color:#334155; font-size:14px; font-weight:600; }
        .reset-fields input { height:48px; border:1px solid #cbd5e1; border-radius:10px; padding:0 12px; outline:none; }
        .reset-fields input:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
        .reset-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; }
        .reset-actions button { border:0; border-radius:10px; padding:10px 15px; font-weight:700; cursor:pointer; }
        .reset-primary { background:#1265e8; color:#fff; }
        .reset-secondary { background:#e2e8f0; color:#334155; }
        .reset-status { margin-top:12px; padding:10px 12px; border-radius:10px; font-size:13px; display:none; }
        .reset-status.show { display:block; }
        .reset-status.ok { background:#ecfdf3; color:#067647; }
        .reset-status.err { background:#fff0ef; color:#b42318; }
        .footer {
            position: absolute;
            z-index: 3;

            left: 50%;
            bottom: 36px;
            transform: translateX(-50%);

            width: 100%;
            padding: 0 15px;

            text-align: center;
            color: #6c7481;
            font-size: 15px;
        }

        @media screen and (max-width: 1150px) {
            .login-page {
                padding-left: 30px;
                padding-right: 30px;
            }

            .container {
                grid-template-columns: minmax(420px, 1fr) 490px;
                gap: 40px;
            }

            .library-title {
                font-size: 35px;
            }

            .library-image {
                height: 350px;
            }

            .login-card {
                min-height: 640px;
                padding: 58px 40px 45px;
            }
        }

        @media screen and (max-width: 900px) {
            .login-page {
                align-items: flex-start;
                padding: 35px 18px 100px;
            }

            .container {
                display: block;
                max-width: 580px;
            }

            .left-section {
                display: none;
            }

            .login-card {
                min-height: auto;
                padding: 55px 38px;
            }
        }

        @media screen and (max-width: 500px) {
            .login-page {
                padding: 18px 12px 95px;
            }

            .login-card {
                padding: 42px 22px;
            }

            .login-header {
                margin-bottom: 35px;
            }

            .login-header h2 {
                font-size: 28px;
            }

            .login-header p {
                font-size: 14px;
                line-height: 1.5;
            }

            .input-wrapper input {
                height: 57px;
            }

            .login-button {
                height: 57px;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .footer {
                bottom: 25px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

    <main class="login-page">
        <div class="decor-circle circle-top-left"></div>
        <div class="decor-circle circle-bottom-left"></div>
        <div class="dots dots-top"></div>
        <div class="dots dots-bottom"></div>
        <div class="container">
            <section class="left-section">
                <div class="logo-icon">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <h1 class="library-title">
                    QUẢN LÝ THƯ VIỆN
                </h1>
                <p class="library-description">
                    Hệ thống quản lý thư viện hiệu quả và tiện lợi
                </p>
                <img
                    src="DangNhap.jpg"
                    alt="Hình minh họa thư viện"
                    class="library-image"
                >
            </section>
            <section class="login-card">
                <div class="login-header">
                    <h2>Đăng nhập</h2>
                    <p>
                        Vui lòng nhập thông tin để đăng nhập hệ thống
                    </p>
                </div>

                <form id="loginForm" method="post" action="dangnhap.php">
                    <div class="form-group">
                        <label for="username">
                            Tên đăng nhập
                        </label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-user input-icon"></i>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?= htmlspecialchars($_POST['username'] ?? $_COOKIE['libraryUsername'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Nhập tên đăng nhập"
                                autocomplete="username"
                            >

                        </div>

                    </div>
                    <div class="form-group">

                        <label for="password">
                            Mật khẩu
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-lock input-icon"></i>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Nhập mật khẩu"
                                autocomplete="current-password"
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                id="togglePassword"
                                aria-label="Hiện hoặc ẩn mật khẩu"
                            >
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-options">
                        <label class="remember-login">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                value="1"
                                <?= !empty($_COOKIE['libraryUsername']) ? 'checked' : '' ?>
                            >
                            <span>Ghi nhớ đăng nhập</span>

                        </label>
                        <a
                            href="#"
                            id="forgotPassword"
                            class="forgot-password"
                        >
                            Quên mật khẩu?
                        </a>

                    </div>
                    <button
                        type="submit"
                        class="login-button"
                    >
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Đăng nhập</span>
                    </button>

                    <div id="message" class="message <?= $loginMessage ? htmlspecialchars($loginType) : '' ?>"><?= htmlspecialchars($loginMessage, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="register-link">Chưa có tài khoản? <a href="dangky.php">Đăng ký tài khoản</a></div>
                    <div class="register-link" style="margin-top:10px">Chỉ muốn xem trước? <a href="index.php">Xem thư viện không cần đăng nhập</a></div>

                </form>

            </section>

        </div>

        <div class="reset-modal" id="resetModal" aria-hidden="true">
          <div class="reset-box">
            <div class="reset-head"><div><h3>Quên mật khẩu</h3><p>Nhập tên đăng nhập. Hệ thống sẽ gửi mã xác minh tới email đã liên kết trước khi cho đổi mật khẩu.</p></div><button class="reset-close" id="resetCloseBtn" type="button">×</button></div>
            <form id="resetStartForm">
              <div class="reset-fields"><label>Tên đăng nhập<input id="resetUsername" type="text" autocomplete="username" required></label></div>
              <div class="reset-actions"><button class="reset-secondary" id="resetCancelBtn" type="button">Hủy</button><button class="reset-primary" type="submit">Kiểm tra tài khoản</button></div>
            </form>
            <form id="resetFinishForm" hidden>
              <div class="reset-fields">
                <label>Mã xác minh<input id="resetCode" inputmode="numeric" maxlength="6" placeholder="6 chữ số" required></label>
                <label>Mật khẩu mới<input id="resetNewPassword" type="password" minlength="6" required></label>
                <label>Xác nhận mật khẩu<input id="resetConfirmPassword" type="password" minlength="6" required></label>
              </div>
              <div class="reset-actions"><button class="reset-secondary" id="resetBackBtn" type="button">Quay lại</button><button class="reset-primary" type="submit">Đổi mật khẩu</button></div>
            </form>
            <div class="reset-status" id="resetStatus"></div>
          </div>
        </div>

        <footer class="footer">
            © 2024 Hệ thống quản lý thư viện. All rights reserved.
        </footer>

    </main>

    <script>
        const usernameInput = document.getElementById("username");
        const passwordInput = document.getElementById("password");
        const rememberInput = document.getElementById("remember");
        const togglePassword = document.getElementById("togglePassword");
        const forgotPassword = document.getElementById("forgotPassword");

        togglePassword?.addEventListener("click", function () {
            const icon = togglePassword.querySelector("i");
            const showing = passwordInput.type === "password";
            passwordInput.type = showing ? "text" : "password";
            icon?.classList.toggle("fa-eye", !showing);
            icon?.classList.toggle("fa-eye-slash", showing);
            togglePassword.setAttribute("aria-label", showing ? "Ẩn mật khẩu" : "Hiện mật khẩu");
        });

        document.getElementById("loginForm")?.addEventListener("submit", function (event) {
            if (!usernameInput.value.trim() || !passwordInput.value) {
                event.preventDefault();
                const message = document.getElementById("message");
                message.textContent = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.";
                message.className = "message error";
                return;
            }
        });

        const resetModal = document.getElementById('resetModal');
        const resetStartForm = document.getElementById('resetStartForm');
        const resetFinishForm = document.getElementById('resetFinishForm');
        const resetStatus = document.getElementById('resetStatus');
        const showResetStatus = (text, ok=false) => { resetStatus.textContent=text; resetStatus.className='reset-status show ' + (ok?'ok':'err'); };
        const closeReset = () => { resetModal?.classList.remove('show'); resetModal?.setAttribute('aria-hidden','true'); resetStatus.className='reset-status'; };

        forgotPassword?.addEventListener("click", function (event) {
            event.preventDefault();
            resetStartForm.hidden = false; resetFinishForm.hidden = true;
            document.getElementById('resetUsername').value = usernameInput?.value.trim() || '';
            resetStatus.className='reset-status';
            resetModal?.classList.add('show'); resetModal?.setAttribute('aria-hidden','false');
        });
        document.getElementById('resetCloseBtn')?.addEventListener('click', closeReset);
        document.getElementById('resetCancelBtn')?.addEventListener('click', closeReset);
        document.getElementById('resetBackBtn')?.addEventListener('click', () => { resetFinishForm.hidden=true; resetStartForm.hidden=false; resetStatus.className='reset-status'; });
        resetModal?.addEventListener('click', e => { if (e.target = resetModal) closeReset(); });

        resetStartForm?.addEventListener('submit', async e => {
            e.preventDefault();
            const username = document.getElementById('resetUsername').value.trim();
            if (!username) return showResetStatus('Vui lòng nhập tên đăng nhập.');
            try {
                const r = await fetch('quenmatkhau.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'start',username})});
                const p = await r.json();
                if (!r.ok || !p.ok) throw new Error(p.message || 'Không thể kiểm tra tài khoản.');
                resetStartForm.hidden=true; resetFinishForm.hidden=false;
                showResetStatus(p.message || 'Đã gửi mã xác minh.', true);
            } catch (err) { showResetStatus(err.message); }
        });
        resetFinishForm?.addEventListener('submit', async e => {
            e.preventDefault();
            const payload={action:'finish',username:document.getElementById('resetUsername').value.trim(),code:document.getElementById('resetCode').value.trim(),password:document.getElementById('resetNewPassword').value,confirmPassword:document.getElementById('resetConfirmPassword').value};
            if (payload.password.length < 6) return showResetStatus('Mật khẩu mới phải có ít nhất 6 ký tự.');
            if (payload.password != payload.confirmPassword) return showResetStatus('Hai lần nhập mật khẩu chưa trùng nhau.');
            try {
                const r=await fetch('quenmatkhau.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
                const p=await r.json(); if(!r.ok||!p.ok) throw new Error(p.message||'Không thể đổi mật khẩu.');
                showResetStatus('✅ Đổi mật khẩu thành công. Bạn có thể quay lại đăng nhập.', true);
                setTimeout(closeReset, 1800);
            } catch(err){ showResetStatus(err.message); }
        });
    </script>

</body>
</html>