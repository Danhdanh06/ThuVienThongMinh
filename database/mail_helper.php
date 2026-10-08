<?php
function smtpReadResponse($socket): string
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') break;
    }
    return $response;
}
function smtpCommand($socket, string $command, array $expectedCodes): string
{
    fwrite($socket, $command . "\r\n");
    $response = smtpReadResponse($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) throw new RuntimeException("SMTP lỗi {$code}: " . trim($response));
    return $response;
}
function smtpSendLibraryMail(array $cfg, string $toEmail, string $toName, string $subject, string $html): void
{
    if (empty($cfg['enabled'])) throw new RuntimeException('Chức năng email chưa được bật trong config/email.php.');
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email người nhận không hợp lệ.');
    $host = (string)($cfg['host'] ?? 'smtp.gmail.com');
    $port = (int)($cfg['port'] ?? 587);
    $socket = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 20);
    if (!$socket) throw new RuntimeException("Không kết nối được SMTP: {$errstr} ({$errno})");
    stream_set_timeout($socket, 20);
    $hello = smtpReadResponse($socket);
    if ((int)substr($hello, 0, 3) !== 220) throw new RuntimeException('SMTP không sẵn sàng: ' . trim($hello));
    $hostname = gethostname() ?: 'localhost';
    smtpCommand($socket, "EHLO {$hostname}", [250]);
    if (($cfg['encryption'] ?? 'tls') === 'tls') {
        smtpCommand($socket, 'STARTTLS', [220]);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('Không bật được TLS cho SMTP.');
        smtpCommand($socket, "EHLO {$hostname}", [250]);
    }
    smtpCommand($socket, 'AUTH LOGIN', [334]);
    smtpCommand($socket, base64_encode((string)$cfg['username']), [334]);
    smtpCommand($socket, base64_encode((string)$cfg['password']), [235]);
    $fromEmail = (string)($cfg['from_email'] ?? $cfg['username']);
    $fromName = (string)($cfg['from_name'] ?? 'Quản lý thư viện');
    smtpCommand($socket, "MAIL FROM:<{$fromEmail}>", [250]);
    smtpCommand($socket, "RCPT TO:<{$toEmail}>", [250, 251]);
    smtpCommand($socket, 'DATA', [354]);
    $encode = static fn(string $text): string => '=?UTF-8?B?' . base64_encode($text) . '?=';
    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . $encode($fromName) . " <{$fromEmail}>",
        'To: ' . $encode($toName ?: $toEmail) . " <{$toEmail}>",
        'Subject: ' . $encode($subject),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $html) . "\r\n.";
    smtpCommand($socket, $message, [250]);
    try { smtpCommand($socket, 'QUIT', [221]); } catch (Throwable $ignored) {}
    fclose($socket);
}
function processOverdueEmailNotifications(mysqli $conn): array
{
    $cfg = require __DIR__ . '/../config/email.php';
    if (empty($cfg['enabled'])) {
        return ['found'=>0,'sent'=>0,'failed'=>0,'skipped'=>0,'emailEnabled'=>false];
    }
    $rows = $conn->query(
        "SELECT pm.MaPhieuMuon, pm.MaDocGia, pm.NgayMuon, pm.HanTra, dg.HoTen, dg.Email,
                DATEDIFF(CURDATE(), pm.HanTra) AS SoNgayQuaHan,
                GROUP_CONCAT(CONCAT(s.TenSach, ' (', ct.SoLuong, ')') ORDER BY s.TenSach SEPARATOR ', ') AS SachMuon,
                tb.MaThongBao, tb.TrangThai AS TrangThaiEmail
         FROM phieumuon pm
         JOIN docgia dg ON dg.MaDocGia=pm.MaDocGia
         JOIN chitietphieumuon ct ON ct.MaPhieuMuon=pm.MaPhieuMuon
         JOIN sach s ON s.MaSach=ct.MaSach
         LEFT JOIN thongbaoemail tb ON tb.MaPhieuMuon=pm.MaPhieuMuon AND tb.LoaiThongBao='QUAHAN_30'
         WHERE pm.NgayTra IS NULL AND COALESCE(pm.TrangThai,'')='Đang mượn'
           AND pm.HanTra IS NOT NULL AND DATEDIFF(CURDATE(), pm.HanTra) > 30
           AND dg.Email IS NOT NULL AND dg.Email <> ''
           AND (tb.MaThongBao IS NULL OR tb.TrangThai <> 'Đã gửi')
         GROUP BY pm.MaPhieuMuon, pm.MaDocGia, pm.NgayMuon, pm.HanTra, dg.HoTen, dg.Email, tb.MaThongBao, tb.TrangThai"
    )->fetch_all(MYSQLI_ASSOC);
    $sent = 0; $failed = 0; $skipped = 0;
    foreach ($rows as $row) {
        $loanId = (int)$row['MaPhieuMuon']; $readerId = (int)$row['MaDocGia']; $email = trim((string)$row['Email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $skipped++; continue; }
        $type = 'QUAHAN_30'; $status = 'Chờ gửi';
        $noticeId = (int)($row['MaThongBao'] ?? 0);
        if ($noticeId > 0) {
            $stmt = $conn->prepare("UPDATE thongbaoemail SET Email=?, TrangThai=?, Loi=NULL WHERE MaThongBao=?");
            $stmt->bind_param('ssi', $email, $status, $noticeId); $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO thongbaoemail (MaPhieuMuon,MaDocGia,Email,LoaiThongBao,TrangThai) VALUES (?,?,?,?,?)");
            $stmt->bind_param('iisss', $loanId, $readerId, $email, $type, $status); $stmt->execute();
            $noticeId = (int)$conn->insert_id;
        }
        $subject = 'Thông báo sách quá hạn trên 30 ngày';
        $name = htmlspecialchars((string)$row['HoTen'], ENT_QUOTES, 'UTF-8');
        $books = htmlspecialchars((string)$row['SachMuon'], ENT_QUOTES, 'UTF-8');
        $late = (int)$row['SoNgayQuaHan']; $due = htmlspecialchars((string)$row['HanTra'], ENT_QUOTES, 'UTF-8');
        $html = "<p>Xin chào <b>{$name}</b>,</p><p>Phiếu mượn <b>PM" . str_pad((string)$loanId, 3, '0', STR_PAD_LEFT)
              . "</b> của bạn đã quá hạn <b>{$late} ngày</b>.</p><p><b>Sách:</b> {$books}<br><b>Hạn trả:</b> {$due}</p>"
              . "<p>Vui lòng liên hệ thư viện và hoàn trả sách sớm nhất có thể.</p><p>Trân trọng,<br>Quản lý thư viện</p>";
        try {
            smtpSendLibraryMail($cfg, $email, (string)$row['HoTen'], $subject, $html);
            $sentStatus = 'Đã gửi';
            $stmt = $conn->prepare("UPDATE thongbaoemail SET TrangThai=?, NgayGui=NOW(), Loi=NULL WHERE MaThongBao=?");
            $stmt->bind_param('si', $sentStatus, $noticeId); $stmt->execute(); $sent++;
        } catch (Throwable $e) {
            $failedStatus = 'Gửi lỗi'; $error = mb_substr($e->getMessage(), 0, 1000);
            $stmt = $conn->prepare("UPDATE thongbaoemail SET TrangThai=?, Loi=? WHERE MaThongBao=?");
            $stmt->bind_param('ssi', $failedStatus, $error, $noticeId); $stmt->execute(); $failed++;
        }
    }
    return ['found'=>count($rows),'sent'=>$sent,'failed'=>$failed,'skipped'=>$skipped,'emailEnabled'=>!empty($cfg['enabled'])];
}
