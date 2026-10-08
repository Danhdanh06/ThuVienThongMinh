<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function libraryPlainText(string $value): string
{
    $value = trim($value);
    $map = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
        'À'=>'A','Á'=>'A','Ạ'=>'A','Ả'=>'A','Ã'=>'A','Â'=>'A','Ầ'=>'A','Ấ'=>'A','Ậ'=>'A','Ẩ'=>'A','Ẫ'=>'A','Ă'=>'A','Ằ'=>'A','Ắ'=>'A','Ặ'=>'A','Ẳ'=>'A','Ẵ'=>'A',
        'È'=>'E','É'=>'E','Ẹ'=>'E','Ẻ'=>'E','Ẽ'=>'E','Ê'=>'E','Ề'=>'E','Ế'=>'E','Ệ'=>'E','Ể'=>'E','Ễ'=>'E',
        'Ì'=>'I','Í'=>'I','Ị'=>'I','Ỉ'=>'I','Ĩ'=>'I',
        'Ò'=>'O','Ó'=>'O','Ọ'=>'O','Ỏ'=>'O','Õ'=>'O','Ô'=>'O','Ồ'=>'O','Ố'=>'O','Ộ'=>'O','Ổ'=>'O','Ỗ'=>'O','Ơ'=>'O','Ờ'=>'O','Ớ'=>'O','Ợ'=>'O','Ở'=>'O','Ỡ'=>'O',
        'Ù'=>'U','Ú'=>'U','Ụ'=>'U','Ủ'=>'U','Ũ'=>'U','Ư'=>'U','Ừ'=>'U','Ứ'=>'U','Ự'=>'U','Ử'=>'U','Ữ'=>'U',
        'Ỳ'=>'Y','Ý'=>'Y','Ỵ'=>'Y','Ỷ'=>'Y','Ỹ'=>'Y','Đ'=>'D'
    ];
    return strtolower(strtr($value, $map));
}

function libraryRoleKeyFromValue(?string $role): string
{
    $plain = libraryPlainText((string)$role);
    return match ($plain) {
        'admin', 'quan tri vien', 'administrator' => 'admin',
        'quan ly', 'thu thu', 'manager' => 'manager',
        'nhan vien', 'employee', 'staff' => 'employee',
        'khach', 'doc gia', 'customer', 'reader' => 'customer',
        'guest', 'khach tham quan', 'visitor' => 'guest',
        default => 'unknown',
    };
}

function libraryRoleLabel(string $roleKey): string
{
    return match ($roleKey) {
        'admin' => 'Admin',
        'manager' => 'Quản lý',
        'employee' => 'Nhân viên',
        'customer' => 'Khách',
        'guest' => 'Khách tham quan',
        default => 'Tài khoản',
    };
}
function libraryPermissionsForRole(string $roleKey): array
{
    return match ($roleKey) {
        'admin' => [
            'dashboard_view','books_view','categories_view','readers_view','employees_view',
            'loans_view','reservations_view','renewals_view','overdue_view',
            'statistics_view','settings_view','accounts_manage','shifts_view','salary_view_all'
        ],
        'manager' => [
            'dashboard_view',
            'books_view','books_manage','books_delete',
            'categories_view','categories_manage','categories_delete',
            'readers_view','readers_manage','readers_delete',
            'employees_view','employees_manage','employees_delete','salary_view_all','salary_manage',
            'loans_view','loans_manage','loans_delete',
            'reservations_view','reservations_manage',
            'renewals_view','renewals_manage','overdue_view',
            'statistics_view','settings_view','settings_manage','backup','legacy_manage',
            'email_test','email_overdue_send',
            'shifts_view','shifts_manage'
        ],
        'employee' => [
            'dashboard_view',
            'books_view','books_manage',
            'categories_view',
            'readers_view','readers_manage',
            'employees_self_view','salary_self_view',
            'loans_view','loans_manage',
            'reservations_view','reservations_manage',
            'renewals_view','renewals_manage','overdue_view','email_overdue_send',
            'shifts_self_view'
        ],
        'customer' => [
            'dashboard_view','books_view','categories_view','readers_view',
            'loans_view','borrow_self','reservations_self','renewals_self'
        ],
        'guest' => [
            'dashboard_view','books_view','categories_view'
        ],
        default => [],
    };
}

function currentLibraryRoleKey(): string
{
    if (empty($_SESSION['user_id'])) return 'guest';
    if (!empty($_SESSION['role_key'])) return (string)$_SESSION['role_key'];
    return libraryRoleKeyFromValue($_SESSION['role'] ?? '');
}

function hasLibraryPermission(string $permission): bool
{
    return in_array($permission, libraryPermissionsForRole(currentLibraryRoleKey()), true);
}

function hasAnyLibraryPermission(array $permissions): bool
{
    foreach ($permissions as $permission) {
        if (hasLibraryPermission($permission)) return true;
    }
    return false;
}

function libraryModulePermission(string $module): string
{
    return match ($module) {
        'trangchu', 'xemtatca', 'thongtinthuvien', 'nangcao' => 'dashboard_view',
        'sach' => 'books_view',
        'theloai' => 'categories_view',
        'docgia' => 'readers_view',
        'muontra' => 'loans_view',
        'nhanvien' => '__employees__',
        'thongke', 'dangmuon', 'docgia2' => 'statistics_view',
        'quahan' => '__overdue__',
        'caidat' => 'settings_view',
        'calamviec' => '__shifts__',
        default => '__none__',
    };
}

function canAccessLibraryModule(string $module): bool
{
    $permission = libraryModulePermission($module);
    if ($permission === '__shifts__') return hasAnyLibraryPermission(['shifts_view','shifts_self_view']);
    if ($permission === '__employees__') return hasAnyLibraryPermission(['employees_view','employees_self_view']);
    if ($permission === '__overdue__') return hasAnyLibraryPermission(['statistics_view','overdue_view']);
    return $permission !== '__none__' && hasLibraryPermission($permission);
}

function requireLibraryPage(string $module): void
{
    // Một số trang được mở công khai cho khách tham quan: Trang chủ, Sách, Thể loại, Thông tin thư viện.
    if (empty($_SESSION['user_id'])) {
        if (canAccessLibraryModule($module)) return;
        http_response_code(401);
        exit('Vui lòng đăng nhập hoặc đăng ký tài khoản để sử dụng chức năng này.');
    }
    if (!canAccessLibraryModule($module)) {
        http_response_code(403);
        exit('Bạn không có quyền truy cập chức năng này.');
    }
}

function libraryDefaultPage(): array
{
    return ['trangchu.php', 'Trang chủ'];
}

function libraryAccountIsLocked(?string $status): bool
{
    $plain = libraryPlainText((string)$status);
    return in_array($plain, ['khoa','da khoa','ngung hoat dong','ngung kich hoat','vo hieu hoa'], true);
}

function syncLibrarySessionAccount(mysqli $conn): bool
{
    if (empty($_SESSION['user_id'])) return false;
    $id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare(
        "SELECT tk.MaTaiKhoan, tk.TenDangNhap, tk.LoaiTaiKhoan, tk.MaNhanVien, tk.MaDocGia, tk.TrangThai,
                COALESCE(nv.HoTen, dg.HoTen, tk.TenDangNhap) AS HoTen
         FROM taikhoan tk
         LEFT JOIN nhanvien nv ON nv.MaNhanVien=tk.MaNhanVien
         LEFT JOIN docgia dg ON dg.MaDocGia=tk.MaDocGia
         WHERE tk.MaTaiKhoan=? LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    if (!$account || libraryAccountIsLocked($account['TrangThai'] ?? '')) return false;

    $roleKey = libraryRoleKeyFromValue($account['LoaiTaiKhoan'] ?? '');
    if ($roleKey === 'unknown') return false;

    $_SESSION['username'] = $account['TenDangNhap'];
    $_SESSION['display_name'] = $account['HoTen'];
    $_SESSION['role_key'] = $roleKey;
    $_SESSION['role'] = libraryRoleLabel($roleKey);
    $_SESSION['employee_id'] = $account['MaNhanVien'] !== null ? (int)$account['MaNhanVien'] : null;
    $_SESSION['reader_id'] = $account['MaDocGia'] !== null ? (int)$account['MaDocGia'] : null;
    return true;
}
