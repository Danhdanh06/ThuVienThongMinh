<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/database/connect.php';
require_once __DIR__ . '/database/mail_helper.php';

function resetRespond(bool $ok, string $message, int $status=200, array $data=[]): never {
    http_response_code($status);
    echo json_encode(['ok'=>$ok,'message'=>$message,'data'=>$data], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
$raw=file_get_contents('php://input');
$data=json_decode($raw ?: '{}', true);
if(!is_array($data)) $data=[];
$action=trim((string)($data['action']??''));

if($action==='start'){
    $username=trim((string)($data['username']??''));
    if($username==='') resetRespond(false,'Vui lòng nhập tên đăng nhập.',400);
    $stmt=$conn->prepare("SELECT tk.MaTaiKhoan, tk.TenDangNhap, COALESCE(dg.Email,nv.Email,'') AS Email, COALESCE(dg.HoTen,nv.HoTen,tk.TenDangNhap) AS HoTen FROM taikhoan tk LEFT JOIN docgia dg ON dg.MaDocGia=tk.MaDocGia LEFT JOIN nhanvien nv ON nv.MaNhanVien=tk.MaNhanVien WHERE tk.TenDangNhap=? LIMIT 1");
    $stmt->bind_param('s',$username); $stmt->execute(); $account=$stmt->get_result()->fetch_assoc();
    if(!$account) resetRespond(false,'Không tìm thấy tài khoản này.',404);
    $email=trim((string)$account['Email']);
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) resetRespond(false,'Tài khoản chưa có email hợp lệ. Vui lòng liên hệ Admin/Quản lý.',400);
    $code=(string)random_int(100000,999999);
    $_SESSION['password_reset']=['accountId'=>(int)$account['MaTaiKhoan'],'username'=>(string)$account['TenDangNhap'],'codeHash'=>password_hash($code,PASSWORD_DEFAULT),'expires'=>time()+600,'attempts'=>0];
    $cfg=require __DIR__ . '/config/email.php';
    $subject='Mã xác minh đổi mật khẩu';
    $safeName=htmlspecialchars((string)$account['HoTen'],ENT_QUOTES,'UTF-8');
    $html="<p>Xin chào <b>{$safeName}</b>,</p><p>Mã xác minh đổi mật khẩu của bạn là:</p><p style='font-size:28px;font-weight:700;letter-spacing:4px'>{$code}</p><p>Mã có hiệu lực trong 10 phút. Nếu bạn không yêu cầu đổi mật khẩu, hãy bỏ qua email này.</p>";
    try { smtpSendLibraryMail($cfg,$email,(string)$account['HoTen'],$subject,$html); }
    catch(Throwable $e){ unset($_SESSION['password_reset']); resetRespond(false,'Không gửi được mã xác minh: '.$e->getMessage(),500); }
    $parts=explode('@',$email,2); $local=$parts[0]; $masked=substr($local,0,1).str_repeat('*',max(2,strlen($local)-2)).substr($local,-1).'@'.($parts[1]??'');
    resetRespond(true,'Tài khoản hợp lệ. Mã xác minh đã gửi tới '.$masked.'.',200,['email'=>$masked]);
}

if($action==='finish'){
    $username=trim((string)($data['username']??'')); $code=trim((string)($data['code']??''));
    $password=(string)($data['password']??''); $confirm=(string)($data['confirmPassword']??'');
    $reset=$_SESSION['password_reset']??null;
    if(!is_array($reset) || ($reset['username']??'')!==$username) resetRespond(false,'Phiên đổi mật khẩu không hợp lệ. Vui lòng kiểm tra tài khoản lại.',400);
    if(time()>(int)($reset['expires']??0)){ unset($_SESSION['password_reset']); resetRespond(false,'Mã xác minh đã hết hạn. Vui lòng gửi mã mới.',400); }
    if(strlen($password)<6) resetRespond(false,'Mật khẩu mới phải có ít nhất 6 ký tự.',400);
    if($password!==$confirm) resetRespond(false,'Hai lần nhập mật khẩu chưa trùng nhau.',400);
    $attempts=(int)($reset['attempts']??0)+1; $_SESSION['password_reset']['attempts']=$attempts;
    if($attempts>5){ unset($_SESSION['password_reset']); resetRespond(false,'Bạn đã nhập sai quá nhiều lần. Vui lòng gửi mã mới.',429); }
    if(!password_verify($code,(string)$reset['codeHash'])) resetRespond(false,'Mã xác minh không chính xác.',400);
    $hash=password_hash($password,PASSWORD_DEFAULT); $id=(int)$reset['accountId'];
    $stmt=$conn->prepare('UPDATE taikhoan SET MatKhau=? WHERE MaTaiKhoan=?'); $stmt->bind_param('si',$hash,$id); $stmt->execute();
    unset($_SESSION['password_reset']); resetRespond(true,'Đổi mật khẩu thành công.');
}
resetRespond(false,'Yêu cầu không hợp lệ.',400);
