<?php
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../database/mail_helper.php';
$result = processOverdueEmailNotifications($conn);
echo '[' . date('Y-m-d H:i:s') . '] ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
