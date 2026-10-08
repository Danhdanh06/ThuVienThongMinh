<?php
session_start();
require_once __DIR__ . '/database/connect.php';

$registerError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirmPassword'] ?? '');

    if ($fullName === '' || strlen($username) < 5 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^0\\d{9}$/', $phone)) {
        $registerError = 'Vui lòng kiểm tra lại các thông tin đăng ký.';
    } elseif (strlen($password) < 6 || $password !== $confirmPassword) {
        $registerError = 'Mật khẩu phải có ít nhất 6 ký tự và hai lần nhập phải trùng nhau.';
    } elseif (empty($_POST['agreeTerms'])) {
        $registerError = 'Bạn cần đồng ý với điều khoản sử dụng.';
    } else {
        try {
            $check = $conn->prepare('SELECT MaTaiKhoan FROM taikhoan WHERE TenDangNhap=? LIMIT 1');
            $check->bind_param('s', $username);
            $check->execute();
            if ($check->get_result()->fetch_assoc()) {
                $registerError = 'Tên đăng nhập đã tồn tại.';
            } else {
                $conn->begin_transaction();
                $stmt = $conn->prepare(
                    'SELECT MaDocGia, HoTen, SDT, Email, TrangThai FROM docgia WHERE SDT=? OR Email=? ORDER BY MaDocGia ASC'
                );
                $stmt->bind_param('ss', $phone, $email);
                $stmt->execute();
                $matchedReaders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                if (count($matchedReaders) > 1) {
                    throw new RuntimeException('Số điện thoại và email đang thuộc các hồ sơ độc giả khác nhau. Vui lòng liên hệ thư viện để kiểm tra.');
                }
                if (count($matchedReaders) === 1) {
                    $existing = $matchedReaders[0];
                    $existingPhone = trim((string)($existing['SDT'] ?? ''));
                    $existingEmail = trim((string)($existing['Email'] ?? ''));
                    if (($existingPhone !== '' && $existingPhone !== $phone) || ($existingEmail !== '' && strcasecmp($existingEmail, $email) !== 0)) {
                        throw new RuntimeException('Thông tin đăng ký chưa khớp với hồ sơ độc giả hiện có. Vui lòng liên hệ thư viện để cập nhật hồ sơ.');
                    }
                    $readerId = (int)$existing['MaDocGia'];
                    $checkLinked = $conn->prepare('SELECT MaTaiKhoan FROM taikhoan WHERE MaDocGia=? LIMIT 1');
                    $checkLinked->bind_param('i', $readerId);
                    $checkLinked->execute();
                    if ($checkLinked->get_result()->fetch_assoc()) {
                        throw new RuntimeException('Hồ sơ độc giả này đã có tài khoản đăng nhập. Vui lòng dùng tài khoản hiện có hoặc liên hệ Admin.');
                    }
                } else {
                    $registerDate = date('Y-m-d');
                    $statusReader = 'Đang hoạt động';
                    $stmt = $conn->prepare('INSERT INTO docgia (HoTen, SDT, Email, NgayDangKy, TrangThai) VALUES (?, ?, ?, ?, ?)');
                    $stmt->bind_param('sssss', $fullName, $phone, $email, $registerDate, $statusReader);
                    $stmt->execute();
                    $readerId = (int)$conn->insert_id;
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'Khách';
                $statusAccount = 'Đang hoạt động';
                $stmt = $conn->prepare('INSERT INTO taikhoan (TenDangNhap, MatKhau, LoaiTaiKhoan, MaDocGia, TrangThai) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssis', $username, $hash, $role, $readerId, $statusAccount);
                $stmt->execute();
                $conn->commit();
                header('Location: dangnhap.php?registered=1');
                exit;
            }
        } catch (Throwable $e) {
            try { $conn->rollback(); } catch (Throwable $ignored) {}
            $registerError = 'Không thể tạo tài khoản: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - Quản lý thư viện</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 35px;
            font-family: Arial, Helvetica, sans-serif;
            background:
                linear-gradient(
                    135deg,
                    #edf6ff,
                    #d9ebff
                );
        }
        .register-container {
            width: 1340px;
            min-height: 850px;
            display: flex;
            background-color: white;
            border-radius: 28px;
            overflow: hidden;
            box-shadow:
                0 15px 45px
                rgba(25, 78, 145, 0.18);
        }
        .library-banner {
            position: relative;

            width: 42%;
            min-height: 850px;

            display: flex;
            flex-direction: column;
            align-items: center;

            padding-top: 55px;

            color: white;

            background-image:
                linear-gradient(
                    rgba(6, 65, 165, 0.22),
                    rgba(6, 65, 165, 0.22)
                ),
                url("DangKi.jpg");

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .library-banner::before {
            content: "";

            position: absolute;
            inset: 0;

            background:
                linear-gradient(
                    to bottom,
                    rgba(10, 65, 160, 0.15),
                    rgba(10, 65, 160, 0.18)
                );
        }
        .library-information {
            position: relative;
            z-index: 1;

            text-align: center;
        }

        .library-icon {
            margin-bottom: 22px;
            font-size: 84px;
        }

        .library-title {
            margin-bottom: 12px;
font-size: 38px;
            font-weight: 700;
        }

        .library-description {
            font-size: 21px;
            font-weight: 400;
        }
        .register-section {
            width: 58%;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 55px 65px;
        }

        .register-content {
            width: 100%;
            max-width: 690px;
        }

        .register-heading {
            margin-bottom: 10px;

            color: #123f8f;

            font-size: 38px;
            font-weight: 750;
            text-align: center;
        }

        .register-subtitle {
            margin-bottom: 38px;

            color: #555555;

            font-size: 18px;
            text-align: center;
        }
        .form-group {
            display: grid;
            grid-template-columns: 150px 1fr;
            align-items: center;

            gap: 18px;

            margin-bottom: 20px;
        }

        .form-label {
            color: #111111;

            font-size: 16px;
            font-weight: 650;
        }

        .input-container {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 20px;

            transform: translateY(-50%);

            color: #646b76;

            font-size: 19px;
        }

        .form-input {
            width: 100%;
            height: 57px;

            padding: 0 52px 0 58px;

            border: 1px solid #d3d6db;
            border-radius: 12px;

            outline: none;

            color: #333333;

            font-size: 16px;

            background-color: #ffffff;

            transition: 0.25s;
        }

        .form-input::placeholder {
            color: #999da4;
        }

        .form-input:focus {
            border-color: #2367dc;

            box-shadow:
                0 0 0 3px
                rgba(35, 103, 220, 0.12);
        }
        .password-button {
            position: absolute;
            top: 50%;
            right: 18px;

            transform: translateY(-50%);

            border: none;
            outline: none;

            color: #616975;
            background: transparent;

            font-size: 19px;

            cursor: pointer;
        }
        .error-message {
            display: none;

            margin-top: 6px;

            color: #e12525;

            font-size: 13px;
        }

        .form-input.error {
            border-color: #e12525;
        }
        .terms-container {
            display: flex;
            align-items: flex-start;

            gap: 11px;

            margin: 27px 0;
        }

        .terms-container input {
            width: 20px;
            height: 20px;

            margin-top: 1px;

            cursor: pointer;
        }

        .terms-container label {
            color: #444444;

            font-size: 15px;
            line-height: 1.5;
        }

        .terms-container a {
            color: #1460cc;
            text-decoration: none;
        }

        .terms-container a:hover {
            text-decoration: underline;
        }
        .register-button {
            width: 100%;
            height: 65px;

            display: flex;
            justify-content: center;
            align-items: center;

            gap: 14px;

            border: none;
            border-radius: 11px;

            color: white;
            background:
                linear-gradient(
                    90deg,
                    #2364d8,
                    #2873eb
                );

            font-size: 21px;
            font-weight: 700;

            cursor: pointer;

            transition: 0.25s;
        }

        .register-button:hover {
            transform: translateY(-2px);

            box-shadow:
                0 10px 22px
                rgba(35, 100, 216, 0.25);
        }

        .register-button:active {
            transform: translateY(0);
        }
        .divider {
            display: flex;
            align-items: center;

            gap: 16px;

            margin: 28px 0;
        }

        .divider::before,
        .divider::after {
            content: "";

            flex: 1;

            height: 1px;

            background-color: #e1e1e1;
        }

        .divider span {
            color: #777777;
            font-size: 14px;
        }
        .login-text {
            color: #333333;

            font-size: 16px;
            text-align: center;
        }

        .login-text a {
            margin-left: 5px;

            color: #185dc2;

            font-weight: 700;
            text-decoration: none;
        }

        .login-text a:hover {
            text-decoration: underline;
        }
        .success-notification {
            position: fixed;
            top: 25px;
            right: 25px;

            display: none;
            align-items: center;
            gap: 13px;

            max-width: 400px;
            padding: 18px 22px;

            border-left: 5px solid #23a55a;
            border-radius: 10px;

            color: #24563a;
            background-color: #ecfff3;

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.15);

            z-index: 1000;
        }

        .success-notification i {
            color: #23a55a;
            font-size: 25px;
        }
        @media screen and (max-width: 1100px) {
            body {
                padding: 20px;
            }

            .register-container {
                width: 100%;
            }

            .library-banner {
                width: 38%;
            }

            .register-section {
                width: 62%;
                padding: 45px 35px;
            }

            .form-group {
                grid-template-columns: 125px 1fr;
            }
        }

        @media screen and (max-width: 850px) {
            body {
                align-items: flex-start;
                padding: 15px;
            }

            .register-container {
                display: block;

                min-height: auto;

                border-radius: 20px;
            }

            .library-banner {
                width: 100%;
                min-height: 330px;

                padding-top: 40px;

                background-position: center 65%;
            }

            .library-icon {
                font-size: 60px;
            }

            .library-title {
                font-size: 30px;
            }

            .library-description {
                font-size: 17px;
            }

            .register-section {
                width: 100%;

                padding: 40px 28px;
            }
        }

        @media screen and (max-width: 600px) {
            .register-section {
                padding: 35px 20px;
            }

            .register-heading {
                font-size: 29px;
            }

            .register-subtitle {
                font-size: 16px;
            }

            .form-group {
                display: block;

                margin-bottom: 18px;
            }

            .form-label {
                display: block;

                margin-bottom: 8px;
            }

            .form-input {
                height: 54px;
            }

            .register-button {
                font-size: 17px;
            }
        }
    </style>
</head>

<body>
    <div class="success-notification" id="successNotification">
        <i class="fa-solid fa-circle-check"></i>
        <div>
            <strong>Đăng ký thành công!</strong>
            <p>Tài khoản của bạn đã được tạo.</p>
        </div>
    </div>
    <main class="register-container">
        <section class="library-banner">
            <div class="library-information">
                <div class="library-icon">
                    <i class="fa-solid fa-book-open-reader"></i>
                </div>
                <h1 class="library-title">
                    QUẢN LÝ THƯ VIỆN
                </h1>
                <p class="library-description">
                    Hệ thống quản lý thư viện thông minh
                </p>
            </div>
        </section>
        <section class="register-section">

            <div class="register-content">

                <h2 class="register-heading">
                    ĐĂNG KÝ TÀI KHOẢN
                </h2>

                <p class="register-subtitle">
                    Tạo tài khoản để sử dụng hệ thống
                </p>

                <?php if ($registerError): ?>
                    <div style="margin-bottom:18px;padding:12px 14px;border-radius:10px;background:#fee2e2;color:#b91c1c;font-weight:600;">
                        <?= htmlspecialchars($registerError, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form
                    id="registerForm"
                    method="post"
                    action="dangky.php"
                    novalidate
                >
                    <div class="form-group">

                        <label
                            class="form-label"
                            for="fullName"
                        >
                            Họ và tên
                        </label>

                        <div class="input-container">

                            <i
                                class="fa-solid fa-user input-icon"
                            ></i>

                            <input
                                class="form-input"
                                type="text"
                                id="fullName"
                                name="fullName"
                                value="<?= htmlspecialchars($_POST['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Nhập họ và tên"
                            >

                            <div
                                class="error-message"
                                id="fullNameError"
                            >
                                Vui lòng nhập họ và tên.
                            </div>

                        </div>

                    </div>
                    <div class="form-group">

                        <label
                            class="form-label"
                            for="username"
                        >
                            Tên đăng nhập
                        </label>

                        <div class="input-container">

                            <i
                                class="fa-regular fa-address-card input-icon"
                            ></i>

                            <input
                                class="form-input"
                                type="text"
                                id="username"
                                name="username"
                                value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Nhập tên đăng nhập"
                            >

                            <div
                                class="error-message"
                                id="usernameError"
                            >
                                Tên đăng nhập phải có ít nhất 5 ký tự.
                            </div>

                        </div>

                    </div>
                    <div class="form-group">

                        <label
                            class="form-label"
                            for="email"
                        >
                            Email
                        </label>

                        <div class="input-container">

                            <i
                                class="fa-solid fa-envelope input-icon"
                            ></i>

                            <input
                                class="form-input"
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Nhập email của bạn"
                            >

                            <div
                                class="error-message"
                                id="emailError"
                            >
                                Email không hợp lệ.
                            </div>

                        </div>

                    </div>
                    <div class="form-group">

                        <label
                            class="form-label"
                            for="phone"
                        >
                            Số điện thoại
                        </label>

                        <div class="input-container">

                            <i
                                class="fa-solid fa-phone input-icon"
                            ></i>

                            <input
                                class="form-input"
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Nhập số điện thoại"
                            >

                            <div
                                class="error-message"
                                id="phoneError"
                            >
                                Số điện thoại phải có 10 chữ số.
                            </div>

                        </div>

                    </div>
                    <div class="form-group">

                        <label
                            class="form-label"
                            for="password"
                        >
                            Mật khẩu
                        </label>
                        <div class="input-container">

                            <i
                                class="fa-solid fa-lock input-icon"
                            ></i>

                            <input
                                class="form-input"
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Nhập mật khẩu"
                            >

                            <button
                                class="password-button"
                                type="button"
                                onclick="togglePassword(
                                    'password',
                                    'passwordIcon'
                                )"
                                aria-label="Hiện hoặc ẩn mật khẩu"
                            >
                                <i
                                    class="fa-regular fa-eye"
                                    id="passwordIcon"
                                ></i>
                            </button>

                            <div
                                class="error-message"
                                id="passwordError"
                            >
                                Mật khẩu phải có ít nhất 6 ký tự.
                            </div>

                        </div>

                    </div>
                    <div class="form-group">

                        <label
                            class="form-label"
                            for="confirmPassword"
                        >
                            Nhập lại mật khẩu
                        </label>

                        <div class="input-container">

                            <i
                                class="fa-solid fa-lock input-icon"
                            ></i>

                            <input
                                class="form-input"
                                type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                placeholder="Nhập lại mật khẩu"
                            >

                            <button
                                class="password-button"
                                type="button"
                                onclick="togglePassword(
                                    'confirmPassword',
                                    'confirmPasswordIcon'
                                )"
                                aria-label="Hiện hoặc ẩn mật khẩu"
                            >
                                <i
                                    class="fa-regular fa-eye"
                                    id="confirmPasswordIcon"
                                ></i>
                            </button>
<div
                                class="error-message"
                                id="confirmPasswordError"
                            >
                                Mật khẩu nhập lại không trùng khớp.
                            </div>

                        </div>

                    </div>
                    <div class="terms-container">

                        <input
                            type="checkbox"
                            id="agreeTerms"
                            name="agreeTerms"
                            value="1"
                        >

                        <label for="agreeTerms">
                            Tôi đồng ý với

                            <a href="#">
                                điều khoản sử dụng
                            </a>

                            và

                            <a href="#">
                                chính sách bảo mật
                            </a>
                        </label>

                    </div>

                    <div
                        class="error-message"
                        id="termsError"
                    >
                        Bạn cần đồng ý với điều khoản sử dụng.
                    </div>
                    <button
                        class="register-button"
                        type="submit"
                    >
                        <i class="fa-solid fa-user-plus"></i>

                        ĐĂNG KÝ TÀI KHOẢN
                    </button>

                </form>

                <div class="divider">
                    <span>hoặc</span>
                </div>
                <p class="login-text">
                    Đã có tài khoản?

                    <a href="dangnhap.php">
                        Đăng nhập ngay
                    </a>
                </p>

            </div>

        </section>

    </main>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            icon?.classList.toggle('fa-eye', showing);
            icon?.classList.toggle('fa-eye-slash', !showing);
        }

        function setFieldState(input, error, valid) {
            input.classList.toggle('error', !valid);
            error.style.display = valid ? 'none' : 'block';
        }

        document.getElementById('registerForm')?.addEventListener('submit', function (event) {
            const fullName = document.getElementById('fullName');
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const phone = document.getElementById('phone');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            const agreeTerms = document.getElementById('agreeTerms');

            let valid = true;
            const tests = [
                [fullName, document.getElementById('fullNameError'), fullName.value.trim() !== ''],
                [username, document.getElementById('usernameError'), username.value.trim().length >= 5],
                [email, document.getElementById('emailError'), /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())],
                [phone, document.getElementById('phoneError'), /^0\d{9}$/.test(phone.value.trim())],
                [password, document.getElementById('passwordError'), password.value.length >= 6],
                [confirmPassword, document.getElementById('confirmPasswordError'), confirmPassword.value !== '' && confirmPassword.value === password.value]
            ];
            tests.forEach(([input, error, result]) => {
                setFieldState(input, error, result);
                if (!result) valid = false;
            });
            const termsError = document.getElementById('termsError');
            termsError.style.display = agreeTerms.checked ? 'none' : 'block';
            if (!agreeTerms.checked) valid = false;
            if (!valid) event.preventDefault();
        });
    </script>

</body>

</html>
