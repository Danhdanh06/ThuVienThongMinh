<?php
// Không lưu App Password/SMTP password trực tiếp trong source code.
// Ưu tiên biến môi trường. Có thể tạo config/email.local.php (không commit)
// để cấu hình riêng trên máy chạy XAMPP.
$config = [
    'enabled' => filter_var(getenv('LIBRARY_MAIL_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'host' => getenv('LIBRARY_MAIL_HOST') ?: 'smtp.gmail.com',
    'port' => (int)(getenv('LIBRARY_MAIL_PORT') ?: 587),
    'encryption' => getenv('LIBRARY_MAIL_ENCRYPTION') ?: 'tls',
    'username' => getenv('LIBRARY_MAIL_USERNAME') ?: '',
    'password' => getenv('LIBRARY_MAIL_PASSWORD') ?: '',
    'from_email' => getenv('LIBRARY_MAIL_FROM_EMAIL') ?: (getenv('LIBRARY_MAIL_USERNAME') ?: ''),
    'from_name' => getenv('LIBRARY_MAIL_FROM_NAME') ?: 'Quản lý thư viện',
];

$localFile = __DIR__ . '/email.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_replace($config, $local);
    }
}

return $config;
