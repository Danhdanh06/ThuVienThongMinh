<?php
function advancedColumn(mysqli $conn, string $table, string $column, string $definition): void {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if (!$conn->query("SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'")->fetch_assoc()) {
        $conn->query("ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
    }
}
function ensureAdvancedLibraryFeatures(mysqli $conn): void {
    try {
        advancedColumn($conn,'sach','ISBN',"VARCHAR(30) DEFAULT NULL");
        advancedColumn($conn,'sach','ViTriKe',"VARCHAR(80) DEFAULT NULL");
        advancedColumn($conn,'docgia','AnhDaiDien',"VARCHAR(255) DEFAULT NULL");
        advancedColumn($conn,'docgia','NgayHetHanThe',"DATE DEFAULT NULL");
        advancedColumn($conn,'nhanvien','AnhDaiDien',"VARCHAR(255) DEFAULT NULL");
        advancedColumn($conn,'nhanvien','ChucVu',"VARCHAR(80) DEFAULT NULL");
        advancedColumn($conn,'calamviec','SoNguoiToiDa',"INT NOT NULL DEFAULT 4");

        $queries = [
"CREATE TABLE IF NOT EXISTS cuonsach (
 MaCuon INT AUTO_INCREMENT PRIMARY KEY, MaSach INT NOT NULL, MaVach VARCHAR(60) NOT NULL UNIQUE,
 TrangThai VARCHAR(30) NOT NULL DEFAULT 'Có sẵn', ViTriKe VARCHAR(80) DEFAULT NULL, NgayNhap DATE DEFAULT NULL,
 GhiChu VARCHAR(255) DEFAULT NULL, INDEX(MaSach), CONSTRAINT FK_CuonSach_Sach FOREIGN KEY(MaSach) REFERENCES sach(MaSach) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS chitietcuonmuon (
 MaLienKet INT AUTO_INCREMENT PRIMARY KEY, MaCTPM INT NOT NULL, MaCuon INT NOT NULL, NgayGan DATETIME DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY UQ_CuonDangGan(MaCTPM,MaCuon), INDEX(MaCuon),
 CONSTRAINT FK_CTCM_CT FOREIGN KEY(MaCTPM) REFERENCES chitietphieumuon(MaCTPM) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT FK_CTCM_Cuon FOREIGN KEY(MaCuon) REFERENCES cuonsach(MaCuon) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS thongbao (
 MaThongBao INT AUTO_INCREMENT PRIMARY KEY, MaTaiKhoan INT DEFAULT NULL, MaDocGia INT DEFAULT NULL, MaNhanVien INT DEFAULT NULL,
 TieuDe VARCHAR(160) NOT NULL, NoiDung TEXT NOT NULL, Loai VARCHAR(40) DEFAULT 'info', DuongDan VARCHAR(200) DEFAULT NULL,
 DaDoc TINYINT(1) NOT NULL DEFAULT 0, NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX(MaTaiKhoan), INDEX(MaDocGia), INDEX(MaNhanVien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS tienphat (
 MaTienPhat INT AUTO_INCREMENT PRIMARY KEY, MaPhieuMuon INT NOT NULL, MaCTPM INT DEFAULT NULL, MaDocGia INT NOT NULL,
 SoTien DECIMAL(12,2) NOT NULL DEFAULT 0, TrangThai VARCHAR(30) NOT NULL DEFAULT 'Chưa thanh toán',
 NgayPhat DATETIME DEFAULT CURRENT_TIMESTAMP, NgayThanhToan DATETIME DEFAULT NULL, MaNhanVienThu INT DEFAULT NULL,
 LyDoMienGiam VARCHAR(255) DEFAULT NULL, INDEX(MaPhieuMuon), INDEX(MaDocGia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS yeuthich (
 MaYeuThich INT AUTO_INCREMENT PRIMARY KEY, MaDocGia INT NOT NULL, MaSach INT NOT NULL, NgayThem DATETIME DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY UQ_YeuThich(MaDocGia,MaSach), INDEX(MaSach)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS hangchodatsach (
 MaHangCho INT AUTO_INCREMENT PRIMARY KEY, MaDocGia INT NOT NULL, MaSach INT NOT NULL, NgayDat DATETIME DEFAULT CURRENT_TIMESTAMP,
 TrangThai VARCHAR(30) NOT NULL DEFAULT 'Đang chờ', ThuTu INT DEFAULT NULL, GiuDen DATETIME DEFAULT NULL,
 INDEX(MaSach), INDEX(MaDocGia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS nhacungcap (
 MaNCC INT AUTO_INCREMENT PRIMARY KEY, TenNCC VARCHAR(150) NOT NULL, SDT VARCHAR(30) DEFAULT NULL, Email VARCHAR(120) DEFAULT NULL,
 DiaChi VARCHAR(255) DEFAULT NULL, TrangThai VARCHAR(30) DEFAULT 'Hoạt động'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS phieunhap (
 MaPhieuNhap INT AUTO_INCREMENT PRIMARY KEY, MaNCC INT DEFAULT NULL, MaNhanVien INT DEFAULT NULL, NgayNhap DATETIME DEFAULT CURRENT_TIMESTAMP,
 TongTien DECIMAL(15,2) DEFAULT 0, GhiChu TEXT DEFAULT NULL, INDEX(MaNCC), INDEX(MaNhanVien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS chitietphieunhap (
 MaCTPN INT AUTO_INCREMENT PRIMARY KEY, MaPhieuNhap INT NOT NULL, MaSach INT NOT NULL, SoLuong INT NOT NULL DEFAULT 1,
 DonGia DECIMAL(12,2) DEFAULT 0, INDEX(MaPhieuNhap), INDEX(MaSach)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS nhatkytonkho (
 MaNhatKy INT AUTO_INCREMENT PRIMARY KEY, MaSach INT NOT NULL, MaCuon INT DEFAULT NULL, Loai VARCHAR(40) NOT NULL,
 SoLuong INT NOT NULL DEFAULT 0, GhiChu VARCHAR(255) DEFAULT NULL, MaNhanVien INT DEFAULT NULL, NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
 INDEX(MaSach), INDEX(MaCuon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS nhatkyhoatdong (
 MaNhatKy INT AUTO_INCREMENT PRIMARY KEY, MaTaiKhoan INT DEFAULT NULL, NguoiThucHien VARCHAR(120) DEFAULT NULL, VaiTro VARCHAR(40) DEFAULT NULL,
 HanhDong VARCHAR(80) NOT NULL, DoiTuong VARCHAR(120) DEFAULT NULL, ChiTiet TEXT DEFAULT NULL, DiaChiIP VARCHAR(60) DEFAULT NULL,
 NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX(NgayTao), INDEX(MaTaiKhoan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS dangkyca (
 MaDangKy INT AUTO_INCREMENT PRIMARY KEY, MaNhanVien INT NOT NULL, MaCa INT NOT NULL, NgayLam DATE NOT NULL,
 TrangThai VARCHAR(30) NOT NULL DEFAULT 'Chờ duyệt', GhiChu VARCHAR(255) DEFAULT NULL, NgayDangKy DATETIME DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY UQ_DangKyCa(MaNhanVien,MaCa,NgayLam), INDEX(MaCa), INDEX(NgayLam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS danhgia (
 MaDanhGia INT AUTO_INCREMENT PRIMARY KEY, MaDocGia INT NOT NULL, MaSach INT NOT NULL, SoSao TINYINT NOT NULL, NoiDung TEXT DEFAULT NULL,
 TrangThai VARCHAR(30) DEFAULT 'Hiển thị', NgayDanhGia DATETIME DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY UQ_DanhGia(MaDocGia,MaSach), INDEX(MaSach)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        foreach ($queries as $q) $conn->query($q);

        // Đồng bộ các khoản phạt cũ đã có trong chi tiết phiếu mượn sang sổ tiền phạt, không sửa bản ghi cũ.
        $conn->query("INSERT INTO tienphat(MaPhieuMuon,MaCTPM,MaDocGia,SoTien,TrangThai,NgayPhat)
                      SELECT ct.MaPhieuMuon,ct.MaCTPM,pm.MaDocGia,ct.TienPhat,'Chưa thanh toán',COALESCE(pm.NgayTra,NOW())
                      FROM chitietphieumuon ct JOIN phieumuon pm ON pm.MaPhieuMuon=ct.MaPhieuMuon
                      LEFT JOIN tienphat tp ON tp.MaCTPM=ct.MaCTPM
                      WHERE ct.TienPhat>0 AND tp.MaTienPhat IS NULL");

        // Chỉ tạo mã cho số bản hiện đang có sẵn nếu đầu sách chưa có bản ghi cuốn sách nào.
        $books = $conn->query("SELECT MaSach, SoLuong, ViTriKe FROM sach ORDER BY MaSach")->fetch_all(MYSQLI_ASSOC);
        foreach ($books as $b) {
            $bookId=(int)$b['MaSach']; $qty=max(0,(int)$b['SoLuong']);
            $st=$conn->prepare("SELECT COUNT(*) c FROM cuonsach WHERE MaSach=?"); $st->bind_param('i',$bookId); $st->execute();
            $count=(int)$st->get_result()->fetch_assoc()['c'];
            if ($count===0 && $qty>0) {
                $ins=$conn->prepare("INSERT IGNORE INTO cuonsach(MaSach,MaVach,TrangThai,ViTriKe,NgayNhap) VALUES(?,?,?,?,CURDATE())");
                for($i=1;$i<=$qty;$i++) { $code=sprintf('TV-S%03d-%02d',$bookId,$i); $status='Có sẵn'; $loc=$b['ViTriKe']; $ins->bind_param('isss',$bookId,$code,$status,$loc); $ins->execute(); }
            }
        }
    } catch (Throwable $e) { /* migration bổ sung: không làm hỏng web cũ nếu môi trường cũ thiếu quyền ALTER */ }
}
