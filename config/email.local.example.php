<?php
// Sao chép file này thành email.local.php trên máy chạy web và điền thông tin thật.
// KHÔNG đưa email.local.php lên Git/hosting công khai.
return [
    'enabled' => true,
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@gmail.com',
    'password' => 'your-google-app-password',
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Quản lý thư viện',
];
