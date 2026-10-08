<?php
function legacyTableCount(mysqli $conn, string $table): int
{
    $allowed = ['sach', 'phieumuon', 'chitietphieumuon', 'docgia', 'nhanvien', 'theloai'];
    if (!in_array($table, $allowed, true)) return 0;
    $row = $conn->query("SELECT COUNT(*) AS total FROM `$table`")->fetch_assoc();
    return (int)($row['total'] ?? 0);
}

function legacyDefaultBooks(): array
{
    return [
        ['id'=>1,'title'=>'Đắc nhân tâm','author'=>'Dale Carnegie','category'=>'Kỹ năng sống','year'=>2018,'quantity'=>23],
        ['id'=>2,'title'=>'Nhà giả kim','author'=>'Paulo Coelho','category'=>'Văn học','year'=>2016,'quantity'=>18],
        ['id'=>3,'title'=>'Tuổi thơ dữ dội','author'=>'Phùng Quán','category'=>'Văn học','year'=>2017,'quantity'=>15],
        ['id'=>4,'title'=>'Lịch sử thời gian','author'=>'Stephen Hawking','category'=>'Khoa học','year'=>2015,'quantity'=>14],
        ['id'=>5,'title'=>'Sapiens','author'=>'Yuval Noah Harari','category'=>'Lịch sử','year'=>2018,'quantity'=>12],
        ['id'=>6,'title'=>'Quẳng gánh lo đi và vui sống','author'=>'Dale Carnegie','category'=>'Kỹ năng sống','year'=>2019,'quantity'=>20],
        ['id'=>7,'title'=>'Atomic Habits','author'=>'James Clear','category'=>'Kỹ năng sống','year'=>2020,'quantity'=>17],
        ['id'=>8,'title'=>'Đàn ông sao Hỏa, đàn bà sao Kim','author'=>'John Gray','category'=>'Tâm lý','year'=>2014,'quantity'=>16],
        ['id'=>9,'title'=>'Dám nghĩ lớn','author'=>'David Schwartz','category'=>'Kỹ năng sống','year'=>2017,'quantity'=>11],
        ['id'=>10,'title'=>'Cha giàu cha nghèo','author'=>'Robert Kiyosaki','category'=>'Kinh tế','year'=>2016,'quantity'=>19],
        ['id'=>11,'title'=>'Tài giỏi, bạn cũng thế','author'=>'Adam Khoo','category'=>'Kỹ năng sống','year'=>2019,'quantity'=>10],
        ['id'=>12,'title'=>'Muôn kiếp nhân sinh','author'=>'Nguyễn Phong','category'=>'Tâm linh','year'=>2021,'quantity'=>8],
        ['id'=>13,'title'=>'Cây cam ngọt của tôi','author'=>'José Mauro de Vasconcelos','category'=>'Văn học','year'=>2020,'quantity'=>9],
        ['id'=>14,'title'=>'Không gia đình','author'=>'Hector Malot','category'=>'Văn học','year'=>2018,'quantity'=>0],
        ['id'=>15,'title'=>'Tư duy nhanh và chậm','author'=>'Daniel Kahneman','category'=>'Tâm lý','year'=>2017,'quantity'=>6],
        ['id'=>16,'title'=>'Những người khốn khổ','author'=>'Victor Hugo','category'=>'Văn học','year'=>2015,'quantity'=>7],
        ['id'=>17,'title'=>'Hoàng tử bé','author'=>'Antoine de Saint-Exupéry','category'=>'Văn học','year'=>2019,'quantity'=>13],
        ['id'=>18,'title'=>'Đời ngắn đừng ngủ dài','author'=>'Robin Sharma','category'=>'Kỹ năng sống','year'=>2020,'quantity'=>5],
        ['id'=>19,'title'=>'Đi tìm lẽ sống','author'=>'Viktor Frankl','category'=>'Tâm lý','year'=>2018,'quantity'=>4],
        ['id'=>20,'title'=>'Khuyến học','author'=>'Fukuzawa Yukichi','category'=>'Giáo dục','year'=>2016,'quantity'=>3],
        ['id'=>21,'title'=>'Bố già','author'=>'Mario Puzo','category'=>'Văn học','year'=>2017,'quantity'=>0],
        ['id'=>22,'title'=>'Thiên nga đen','author'=>'Nassim Nicholas Taleb','category'=>'Kinh tế','year'=>2021,'quantity'=>9],
        ['id'=>23,'title'=>'Steve Jobs','author'=>'Walter Isaacson','category'=>'Tiểu sử','year'=>2015,'quantity'=>2],
        ['id'=>24,'title'=>'Rừng Na Uy','author'=>'Haruki Murakami','category'=>'Văn học','year'=>2019,'quantity'=>8],
        ['id'=>25,'title'=>'Kafka bên bờ biển','author'=>'Haruki Murakami','category'=>'Văn học','year'=>2020,'quantity'=>7],
        ['id'=>26,'title'=>'Totto-chan bên cửa sổ','author'=>'Tetsuko Kuroyanagi','category'=>'Giáo dục','year'=>2018,'quantity'=>10],
        ['id'=>27,'title'=>'Sức mạnh của hiện tại','author'=>'Eckhart Tolle','category'=>'Tâm lý','year'=>2021,'quantity'=>6],
        ['id'=>28,'title'=>'Nghĩ giàu làm giàu','author'=>'Napoleon Hill','category'=>'Kinh tế','year'=>2017,'quantity'=>14],
        ['id'=>29,'title'=>'Người bán hàng vĩ đại nhất thế giới','author'=>'Og Mandino','category'=>'Kinh tế','year'=>2016,'quantity'=>9],
        ['id'=>30,'title'=>'Sherlock Holmes','author'=>'Arthur Conan Doyle','category'=>'Trinh thám','year'=>2019,'quantity'=>16],
        ['id'=>31,'title'=>'Một bức','author'=>'Nguyễn Nhật Ánh','category'=>'Văn học','year'=>2020,'quantity'=>12],
        ['id'=>32,'title'=>'Cho tôi xin một vé đi tuổi thơ','author'=>'Nguyễn Nhật Ánh','category'=>'Văn học','year'=>2018,'quantity'=>11],
        ['id'=>33,'title'=>'Dế mèn phiêu lưu ký','author'=>'Tô Hoài','category'=>'Thiếu nhi','year'=>2017,'quantity'=>13],
        ['id'=>34,'title'=>'Vũ trụ trong vỏ hạt dẻ','author'=>'Stephen Hawking','category'=>'Khoa học','year'=>2016,'quantity'=>5],
        ['id'=>35,'title'=>'Lịch sử vạn vật','author'=>'Bill Bryson','category'=>'Khoa học','year'=>2020,'quantity'=>7],
    ];
}

function legacyDateOffset(int $days): string
{
    return date('Y-m-d', strtotime(($days >= 0 ? '+' : '') . $days . ' days'));
}

function legacyDefaultLoans(): array
{
    return [
        ['id'=>1,'readerId'=>'DG001','readerName'=>'Nguyễn Văn An','readerPhone'=>'0901234567','bookId'=>'S001','bookTitle'=>'Đắc nhân tâm','quantity'=>1,'borrowDate'=>legacyDateOffset(-6),'dueDate'=>legacyDateOffset(8),'returnDate'=>'','staffName'=>'Admin','note'=>'Sách cần mượn.'],
        ['id'=>2,'readerId'=>'DG002','readerName'=>'Trần Thế Bình','readerPhone'=>'0912345678','bookId'=>'S002','bookTitle'=>'Nhà giả kim','quantity'=>1,'borrowDate'=>legacyDateOffset(-12),'dueDate'=>legacyDateOffset(2),'returnDate'=>'','staffName'=>'Admin','note'=>''],
        ['id'=>3,'readerId'=>'DG003','readerName'=>'Lê Hoàng Minh','readerPhone'=>'0923456789','bookId'=>'S007','bookTitle'=>'Atomic Habits','quantity'=>1,'borrowDate'=>legacyDateOffset(-20),'dueDate'=>legacyDateOffset(-6),'returnDate'=>'','staffName'=>'Nguyễn Lan','note'=>'Độc giả đã được nhắc trả sách.'],
        ['id'=>4,'readerId'=>'DG004','readerName'=>'Phạm Ngọc Mai','readerPhone'=>'0934567890','bookId'=>'S005','bookTitle'=>'Sapiens','quantity'=>1,'borrowDate'=>legacyDateOffset(-25),'dueDate'=>legacyDateOffset(-11),'returnDate'=>legacyDateOffset(-12),'staffName'=>'Admin','note'=>'Đã trả đúng hạn.'],
        ['id'=>5,'readerId'=>'DG005','readerName'=>'Võ Thành Đạt','readerPhone'=>'0945678901','bookId'=>'S010','bookTitle'=>'Cha giàu cha nghèo','quantity'=>2,'borrowDate'=>legacyDateOffset(-4),'dueDate'=>legacyDateOffset(10),'returnDate'=>'','staffName'=>'Admin','note'=>''],
        ['id'=>6,'readerId'=>'DG006','readerName'=>'Đặng Mỹ Linh','readerPhone'=>'0956789012','bookId'=>'S003','bookTitle'=>'Tuổi thơ dữ dội','quantity'=>1,'borrowDate'=>legacyDateOffset(-16),'dueDate'=>legacyDateOffset(-2),'returnDate'=>'','staffName'=>'Nguyễn Lan','note'=>'Quá hạn 2 ngày.'],
        ['id'=>7,'readerId'=>'DG007','readerName'=>'Bùi Quốc Huy','readerPhone'=>'0967890123','bookId'=>'S004','bookTitle'=>'Lịch sử thời gian','quantity'=>1,'borrowDate'=>legacyDateOffset(-8),'dueDate'=>legacyDateOffset(6),'returnDate'=>'','staffName'=>'Admin','note'=>''],
        ['id'=>8,'readerId'=>'DG008','readerName'=>'Ngô Thanh Trúc','readerPhone'=>'0978901234','bookId'=>'S008','bookTitle'=>'Đàn ông sao Hỏa, đàn bà sao Kim','quantity'=>1,'borrowDate'=>legacyDateOffset(-30),'dueDate'=>legacyDateOffset(-16),'returnDate'=>legacyDateOffset(-17),'staffName'=>'Nguyễn Lan','note'=>''],
        ['id'=>9,'readerId'=>'DG009','readerName'=>'Á Minh Khang','readerPhone'=>'0989012345','bookId'=>'S009','bookTitle'=>'Dám nghĩ lớn','quantity'=>1,'borrowDate'=>legacyDateOffset(-11),'dueDate'=>legacyDateOffset(3),'returnDate'=>'','staffName'=>'Admin','note'=>'Sắp đến hạn.'],
        ['id'=>10,'readerId'=>'DG010','readerName'=>'Huỳnh Gia Bảo','readerPhone'=>'0990123456','bookId'=>'S012','bookTitle'=>'Muôn kiếp nhân sinh','quantity'=>1,'borrowDate'=>legacyDateOffset(-14),'dueDate'=>legacyDateOffset(0),'returnDate'=>'','staffName'=>'Admin','note'=>'Hẹn trả hôm nay.'],
        ['id'=>11,'readerId'=>'DG011','readerName'=>'Nguyễn Thùy Hồng','readerPhone'=>'0909988776','bookId'=>'S013','bookTitle'=>'Cây cam ngọt của tôi','quantity'=>1,'borrowDate'=>legacyDateOffset(-18),'dueDate'=>legacyDateOffset(-4),'returnDate'=>legacyDateOffset(-5),'staffName'=>'Admin','note'=>''],
        ['id'=>12,'readerId'=>'DG012','readerName'=>'Trương Quốc Nam','readerPhone'=>'0918877665','bookId'=>'S015','bookTitle'=>'Tư duy nhanh và chậm','quantity'=>1,'borrowDate'=>legacyDateOffset(-3),'dueDate'=>legacyDateOffset(11),'returnDate'=>'','staffName'=>'Nguyễn Lan','note'=>''],
    ];
}

function legacyDigits(mixed $value): int
{
    if (is_int($value)) return $value;
    if (preg_match('/(\d+)/', (string)$value, $m)) return (int)$m[1];
    return 0;
}

function legacyEnsureCategory(mysqli $conn, string $name, array &$cache): ?int
{
    $name = trim($name);
    if ($name === '') return null;
    if (isset($cache[$name])) return $cache[$name];
    $stmt = $conn->prepare('SELECT MaTheLoai FROM theloai WHERE TenTheLoai=? LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return $cache[$name] = (int)$row['MaTheLoai'];
    $desc = 'Dữ liệu được khôi phục từ phiên bản cũ';
    $stmt = $conn->prepare('INSERT INTO theloai (TenTheLoai, MoTa) VALUES (?, ?)');
    $stmt->bind_param('ss', $name, $desc);
    $stmt->execute();
    return $cache[$name] = (int)$conn->insert_id;
}

function legacyEnsureReader(mysqli $conn, array $loan): int
{
    $id = legacyDigits($loan['readerId'] ?? 0);
    $name = trim((string)($loan['readerName'] ?? 'Độc giả'));
    $phone = trim((string)($loan['readerPhone'] ?? ''));

    if ($id > 0) {
        $stmt = $conn->prepare('SELECT MaDocGia FROM docgia WHERE MaDocGia=? LIMIT 1');
        $stmt->bind_param('i', $id); $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) return $id;
    }
    if ($phone !== '') {
        $stmt = $conn->prepare('SELECT MaDocGia FROM docgia WHERE SDT=? LIMIT 1');
        $stmt->bind_param('s', $phone); $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) return (int)$row['MaDocGia'];
    }

    $registered = date('Y-m-d');
    $status = 'Hoạt động';
    if ($id > 0) {
        $stmt = $conn->prepare('INSERT INTO docgia (MaDocGia, HoTen, SDT, NgayDangKy, TrangThai) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $id, $name, $phone, $registered, $status);
    } else {
        $stmt = $conn->prepare('INSERT INTO docgia (HoTen, SDT, NgayDangKy, TrangThai) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $phone, $registered, $status);
    }
    $stmt->execute();
    return $id > 0 ? $id : (int)$conn->insert_id;
}

function legacyEnsureStaff(mysqli $conn, string $name): int
{
    $name = trim($name) ?: 'Admin';
    $stmt = $conn->prepare('SELECT MaNhanVien FROM nhanvien WHERE HoTen=? LIMIT 1');
    $stmt->bind_param('s', $name); $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) return (int)$row['MaNhanVien'];
    $role = $name === 'Admin' ? 'Quản trị viên' : 'Thủ thư';
    $start = date('Y-m-d');
    $status = 'Đang hoạt động';
    $stmt = $conn->prepare('INSERT INTO nhanvien (HoTen, VaiTro, NgayVaoLam, TrangThai) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $name, $role, $start, $status);
    $stmt->execute();
    return (int)$conn->insert_id;
}

function legacyImportData(mysqli $conn, array $books, array $loans): array
{
    if (!$books) $books = legacyDefaultBooks();
    if (!$loans) $loans = legacyDefaultLoans();

    $result = ['booksImported'=>0, 'loansImported'=>0, 'readersCreated'=>0, 'staffCreated'=>0];
    $conn->begin_transaction();
    try {
        $categoryCache = [];
        if (legacyTableCount($conn, 'sach') === 0) {
            foreach ($books as $book) {
                $id = legacyDigits($book['id'] ?? 0);
                $title = trim((string)($book['title'] ?? $book['TenSach'] ?? ''));
                if ($title === '') continue;
                $author = trim((string)($book['author'] ?? $book['TacGia'] ?? '')) ?: null;
                $publisher = trim((string)($book['publisher'] ?? $book['NhaXuatBan'] ?? '')) ?: null;
                $year = (int)($book['year'] ?? $book['NamXuatBan'] ?? 0); if ($year <= 0) $year = null;
                $qty = max(0, (int)($book['quantity'] ?? $book['SoLuong'] ?? 0));
                $cover = trim((string)($book['cover'] ?? $book['HinhAnh'] ?? '')) ?: null;
                $desc = trim((string)($book['description'] ?? $book['MoTa'] ?? '')) ?: null;
                $category = trim((string)($book['category'] ?? $book['TenTheLoai'] ?? ''));
                $categoryId = legacyEnsureCategory($conn, $category, $categoryCache);

                if ($id > 0) {
                    $stmt = $conn->prepare('INSERT INTO sach (MaSach, TenSach, TacGia, NhaXuatBan, NamXuatBan, SoLuong, HinhAnh, MoTa, MaTheLoai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('isssiissi', $id, $title, $author, $publisher, $year, $qty, $cover, $desc, $categoryId);
                } else {
                    $stmt = $conn->prepare('INSERT INTO sach (TenSach, TacGia, NhaXuatBan, NamXuatBan, SoLuong, HinhAnh, MoTa, MaTheLoai) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('sssiissi', $title, $author, $publisher, $year, $qty, $cover, $desc, $categoryId);
                }
                $stmt->execute();
                $result['booksImported']++;
            }
        }

        if (legacyTableCount($conn, 'phieumuon') === 0) {
            foreach ($loans as $loan) {
                $beforeReaders = legacyTableCount($conn, 'docgia');
                $readerId = legacyEnsureReader($conn, $loan);
                if (legacyTableCount($conn, 'docgia') > $beforeReaders) $result['readersCreated']++;

                $staffName = trim((string)($loan['staffName'] ?? 'Admin')) ?: 'Admin';
                $beforeStaff = legacyTableCount($conn, 'nhanvien');
                $staffId = legacyEnsureStaff($conn, $staffName);
                if (legacyTableCount($conn, 'nhanvien') > $beforeStaff) $result['staffCreated']++;

                $bookId = legacyDigits($loan['bookId'] ?? 0);
                if ($bookId <= 0 || !$conn->query('SELECT 1 FROM sach WHERE MaSach=' . (int)$bookId . ' LIMIT 1')->fetch_row()) {
                    $bookTitle = trim((string)($loan['bookTitle'] ?? ''));
                    if ($bookTitle !== '') {
                        $stmt = $conn->prepare('SELECT MaSach FROM sach WHERE TenSach=? LIMIT 1');
                        $stmt->bind_param('s', $bookTitle); $stmt->execute();
                        if ($row = $stmt->get_result()->fetch_assoc()) $bookId = (int)$row['MaSach'];
                    }
                }
                if ($bookId <= 0) continue;

                $loanId = legacyDigits($loan['id'] ?? $loan['code'] ?? 0);
                $borrowDate = trim((string)($loan['borrowDate'] ?? '')) ?: date('Y-m-d');
                $dueDate = trim((string)($loan['dueDate'] ?? '')) ?: date('Y-m-d', strtotime($borrowDate . ' +14 days'));
                $returnDate = trim((string)($loan['returnDate'] ?? '')) ?: null;
                $status = $returnDate ? 'Đã trả' : ($dueDate < date('Y-m-d') ? 'Quá hạn' : 'Đang mượn');
                $qty = max(1, (int)($loan['quantity'] ?? 1));
                $note = trim((string)($loan['note'] ?? '')) ?: null;

                if ($loanId > 0) {
                    $stmt = $conn->prepare('INSERT INTO phieumuon (MaPhieuMuon, MaDocGia, MaNhanVien, NgayMuon, HanTra, NgayTra, TrangThai) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('iiissss', $loanId, $readerId, $staffId, $borrowDate, $dueDate, $returnDate, $status);
                } else {
                    $stmt = $conn->prepare('INSERT INTO phieumuon (MaDocGia, MaNhanVien, NgayMuon, HanTra, NgayTra, TrangThai) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->bind_param('iissss', $readerId, $staffId, $borrowDate, $dueDate, $returnDate, $status);
                }
                $stmt->execute();
                $actualLoanId = $loanId > 0 ? $loanId : (int)$conn->insert_id;

                $fine = 0.0;
                $stmt = $conn->prepare('INSERT INTO chitietphieumuon (MaPhieuMuon, MaSach, SoLuong, TienPhat, GhiChu) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('iiids', $actualLoanId, $bookId, $qty, $fine, $note);
                $stmt->execute();
                $result['loansImported']++;
            }
        }
        $stmt = $conn->prepare("SELECT MaTaiKhoan FROM taikhoan WHERE TenDangNhap='admin' AND MaNhanVien IS NULL LIMIT 1");
        $stmt->execute();
        if ($account = $stmt->get_result()->fetch_assoc()) {
            $adminStaffId = legacyEnsureStaff($conn, 'Admin');
            $accountId = (int)$account['MaTaiKhoan'];
            $stmt = $conn->prepare('UPDATE taikhoan SET MaNhanVien=? WHERE MaTaiKhoan=?');
            $stmt->bind_param('ii', $adminStaffId, $accountId);
            $stmt->execute();
        }

        $conn->commit();
        return $result;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
