<?php
function ensureLibraryFeatureTables(mysqli $conn): void
{
    try {
        $conn->query(
            "CREATE TABLE IF NOT EXISTS datlichmuon (
                MaDatLich INT NOT NULL AUTO_INCREMENT,
                MaDocGia INT NOT NULL,
                MaSach INT NOT NULL,
                MaNhanVien INT DEFAULT NULL,
                NgayDat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                NgayDuKienMuon DATE NOT NULL,
                GioDuKien TIME DEFAULT NULL,
                SoLuong INT NOT NULL DEFAULT 1,
                TrangThai VARCHAR(30) NOT NULL DEFAULT 'Chờ xác nhận',
                GhiChu TEXT DEFAULT NULL,
                NgayXuLy DATETIME DEFAULT NULL,
                PRIMARY KEY (MaDatLich),
                KEY IDX_DatLich_DocGia (MaDocGia),
                KEY IDX_DatLich_Sach (MaSach),
                KEY IDX_DatLich_NhanVien (MaNhanVien),
                KEY IDX_DatLich_Ngay (NgayDuKienMuon),
                CONSTRAINT FK_DatLich_DocGia FOREIGN KEY (MaDocGia) REFERENCES docgia(MaDocGia) ON UPDATE CASCADE,
                CONSTRAINT FK_DatLich_Sach FOREIGN KEY (MaSach) REFERENCES sach(MaSach) ON UPDATE CASCADE,
                CONSTRAINT FK_DatLich_NhanVien FOREIGN KEY (MaNhanVien) REFERENCES nhanvien(MaNhanVien) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $conn->query(
            "CREATE TABLE IF NOT EXISTS calamviec (
                MaCa INT NOT NULL AUTO_INCREMENT,
                TenCa VARCHAR(60) NOT NULL,
                GioBatDau TIME NOT NULL,
                GioKetThuc TIME NOT NULL,
                MoTa VARCHAR(255) DEFAULT NULL,
                TrangThai VARCHAR(30) NOT NULL DEFAULT 'Hoạt động',
                PRIMARY KEY (MaCa)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $conn->query(
            "CREATE TABLE IF NOT EXISTS phancongca (
                MaPhanCong INT NOT NULL AUTO_INCREMENT,
                MaNhanVien INT NOT NULL,
                MaCa INT NOT NULL,
                NgayLam DATE NOT NULL,
                GhiChu VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (MaPhanCong),
                UNIQUE KEY UQ_PhanCongCa (MaNhanVien, MaCa, NgayLam),
                KEY IDX_PhanCongCa_Ca (MaCa),
                KEY IDX_PhanCongCa_Ngay (NgayLam),
                CONSTRAINT FK_PhanCongCa_NhanVien FOREIGN KEY (MaNhanVien) REFERENCES nhanvien(MaNhanVien) ON UPDATE CASCADE,
                CONSTRAINT FK_PhanCongCa_Ca FOREIGN KEY (MaCa) REFERENCES calamviec(MaCa) ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $conn->query(
            "CREATE TABLE IF NOT EXISTS yeucaugiahan (
                MaYeuCau INT NOT NULL AUTO_INCREMENT,
                MaPhieuMuon INT NOT NULL,
                MaDocGia INT NOT NULL,
                SoNgayGiaHan INT NOT NULL DEFAULT 7,
                HanTraCu DATE NOT NULL,
                HanTraMoi DATE NOT NULL,
                NgayYeuCau DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                TrangThai VARCHAR(30) NOT NULL DEFAULT 'Chờ duyệt',
                LyDoTuChoi VARCHAR(255) DEFAULT NULL,
                CanhBao VARCHAR(255) DEFAULT NULL,
                MaNhanVienXuLy INT DEFAULT NULL,
                NgayXuLy DATETIME DEFAULT NULL,
                PRIMARY KEY (MaYeuCau),
                KEY IDX_GiaHan_Phieu (MaPhieuMuon),
                KEY IDX_GiaHan_DocGia (MaDocGia),
                KEY IDX_GiaHan_TrangThai (TrangThai),
                KEY IDX_GiaHan_NhanVien (MaNhanVienXuLy),
                CONSTRAINT FK_GiaHan_Phieu FOREIGN KEY (MaPhieuMuon) REFERENCES phieumuon(MaPhieuMuon) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT FK_GiaHan_DocGia FOREIGN KEY (MaDocGia) REFERENCES docgia(MaDocGia) ON UPDATE CASCADE,
                CONSTRAINT FK_GiaHan_NhanVien FOREIGN KEY (MaNhanVienXuLy) REFERENCES nhanvien(MaNhanVien) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $conn->query(
            "CREATE TABLE IF NOT EXISTS thongbaoemail (
                MaThongBao INT NOT NULL AUTO_INCREMENT,
                MaPhieuMuon INT NOT NULL,
                MaDocGia INT NOT NULL,
                Email VARCHAR(150) NOT NULL,
                LoaiThongBao VARCHAR(40) NOT NULL DEFAULT 'QUAHAN_30',
                NgayGui DATETIME DEFAULT NULL,
                TrangThai VARCHAR(30) NOT NULL DEFAULT 'Chờ gửi',
                Loi TEXT DEFAULT NULL,
                PRIMARY KEY (MaThongBao),
                UNIQUE KEY UQ_Email_Phieu_Loai (MaPhieuMuon, LoaiThongBao),
                KEY IDX_Email_DocGia (MaDocGia),
                CONSTRAINT FK_Email_Phieu FOREIGN KEY (MaPhieuMuon) REFERENCES phieumuon(MaPhieuMuon) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT FK_Email_DocGia FOREIGN KEY (MaDocGia) REFERENCES docgia(MaDocGia) ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        // Bảo mật đăng nhập: chỉ bổ sung cột, không thay đổi/xóa dữ liệu tài khoản cũ.
        $loginFailColumn = $conn->query("SHOW COLUMNS FROM taikhoan LIKE 'LanDangNhapSai'")->fetch_assoc();
        if (!$loginFailColumn) {
            $conn->query("ALTER TABLE taikhoan ADD COLUMN LanDangNhapSai INT NOT NULL DEFAULT 0");
        }
        $loginLockColumn = $conn->query("SHOW COLUMNS FROM taikhoan LIKE 'KhoaDangNhapDen'")->fetch_assoc();
        if (!$loginLockColumn) {
            $conn->query("ALTER TABLE taikhoan ADD COLUMN KhoaDangNhapDen DATETIME DEFAULT NULL");
        }

        $column = $conn->query("SHOW COLUMNS FROM nhanvien LIKE 'Luong'")->fetch_assoc();
        if (!$column) {
            $conn->query("ALTER TABLE nhanvien ADD COLUMN Luong DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER VaiTro");
        }

        $defaults = [
            ['Ca sáng', '07:00:00', '12:00:00', 'Ca làm buổi sáng'],
            ['Ca chiều', '12:00:00', '17:00:00', 'Ca làm buổi chiều'],
            ['Ca tối', '17:00:00', '21:00:00', 'Ca làm buổi tối'],
        ];
        foreach ($defaults as [$name, $start, $end, $description]) {
            $stmt = $conn->prepare("SELECT MaCa FROM calamviec WHERE TenCa=? LIMIT 1");
            $stmt->bind_param('s', $name);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $status = 'Hoạt động';
                $insert = $conn->prepare("INSERT INTO calamviec (TenCa,GioBatDau,GioKetThuc,MoTa,TrangThai) VALUES (?,?,?,?,?)");
                $insert->bind_param('sssss', $name, $start, $end, $description, $status);
                $insert->execute();
            }
        }
    } catch (mysqli_sql_exception $e) {
    }
}
