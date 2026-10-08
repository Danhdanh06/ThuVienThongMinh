-- ============================================================
-- BỔ SUNG CHỨC NĂNG: ĐẶT LỊCH MƯỢN + CA LÀM VIỆC
-- Database: web_qlthuvien
-- An toàn cho database đang có dữ liệu: chỉ CREATE IF NOT EXISTS.
-- KHÔNG DROP / TRUNCATE / XÓA dữ liệu cũ.
-- ============================================================

USE `web_qlthuvien`;

CREATE TABLE IF NOT EXISTS `datlichmuon` (
  `MaDatLich` int(11) NOT NULL AUTO_INCREMENT,
  `MaDocGia` int(11) NOT NULL,
  `MaSach` int(11) NOT NULL,
  `MaNhanVien` int(11) DEFAULT NULL,
  `NgayDat` datetime NOT NULL DEFAULT current_timestamp(),
  `NgayDuKienMuon` date NOT NULL,
  `GioDuKien` time DEFAULT NULL,
  `SoLuong` int(11) NOT NULL DEFAULT 1,
  `TrangThai` varchar(30) NOT NULL DEFAULT 'Chờ xác nhận',
  `GhiChu` text DEFAULT NULL,
  `NgayXuLy` datetime DEFAULT NULL,
  PRIMARY KEY (`MaDatLich`),
  KEY `IDX_DatLich_DocGia` (`MaDocGia`),
  KEY `IDX_DatLich_Sach` (`MaSach`),
  KEY `IDX_DatLich_NhanVien` (`MaNhanVien`),
  KEY `IDX_DatLich_Ngay` (`NgayDuKienMuon`),
  CONSTRAINT `FK_DatLich_DocGia` FOREIGN KEY (`MaDocGia`) REFERENCES `docgia` (`MaDocGia`) ON UPDATE CASCADE,
  CONSTRAINT `FK_DatLich_Sach` FOREIGN KEY (`MaSach`) REFERENCES `sach` (`MaSach`) ON UPDATE CASCADE,
  CONSTRAINT `FK_DatLich_NhanVien` FOREIGN KEY (`MaNhanVien`) REFERENCES `nhanvien` (`MaNhanVien`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `calamviec` (
  `MaCa` int(11) NOT NULL AUTO_INCREMENT,
  `TenCa` varchar(60) NOT NULL,
  `GioBatDau` time NOT NULL,
  `GioKetThuc` time NOT NULL,
  `MoTa` varchar(255) DEFAULT NULL,
  `TrangThai` varchar(30) NOT NULL DEFAULT 'Hoạt động',
  PRIMARY KEY (`MaCa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `phancongca` (
  `MaPhanCong` int(11) NOT NULL AUTO_INCREMENT,
  `MaNhanVien` int(11) NOT NULL,
  `MaCa` int(11) NOT NULL,
  `NgayLam` date NOT NULL,
  `GhiChu` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`MaPhanCong`),
  UNIQUE KEY `UQ_PhanCongCa` (`MaNhanVien`,`MaCa`,`NgayLam`),
  KEY `IDX_PhanCongCa_Ca` (`MaCa`),
  KEY `IDX_PhanCongCa_Ngay` (`NgayLam`),
  CONSTRAINT `FK_PhanCongCa_NhanVien` FOREIGN KEY (`MaNhanVien`) REFERENCES `nhanvien` (`MaNhanVien`) ON UPDATE CASCADE,
  CONSTRAINT `FK_PhanCongCa_Ca` FOREIGN KEY (`MaCa`) REFERENCES `calamviec` (`MaCa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `calamviec` (`TenCa`,`GioBatDau`,`GioKetThuc`,`MoTa`,`TrangThai`)
SELECT 'Ca sáng','07:00:00','12:00:00','Ca làm buổi sáng','Hoạt động'
WHERE NOT EXISTS (SELECT 1 FROM `calamviec` WHERE `TenCa`='Ca sáng');

INSERT INTO `calamviec` (`TenCa`,`GioBatDau`,`GioKetThuc`,`MoTa`,`TrangThai`)
SELECT 'Ca chiều','12:00:00','17:00:00','Ca làm buổi chiều','Hoạt động'
WHERE NOT EXISTS (SELECT 1 FROM `calamviec` WHERE `TenCa`='Ca chiều');

INSERT INTO `calamviec` (`TenCa`,`GioBatDau`,`GioKetThuc`,`MoTa`,`TrangThai`)
SELECT 'Ca tối','17:00:00','21:00:00','Ca làm buổi tối','Hoạt động'
WHERE NOT EXISTS (SELECT 1 FROM `calamviec` WHERE `TenCa`='Ca tối');


-- ============================================================
-- BỔ SUNG: LƯƠNG + YÊU CẦU GIA HẠN + LỊCH SỬ EMAIL QUÁ HẠN
-- ============================================================

ALTER TABLE `nhanvien`
  ADD COLUMN IF NOT EXISTS `Luong` decimal(15,2) NOT NULL DEFAULT 0 AFTER `VaiTro`;

CREATE TABLE IF NOT EXISTS `yeucaugiahan` (
  `MaYeuCau` int(11) NOT NULL AUTO_INCREMENT,
  `MaPhieuMuon` int(11) NOT NULL,
  `MaDocGia` int(11) NOT NULL,
  `SoNgayGiaHan` int(11) NOT NULL DEFAULT 7,
  `HanTraCu` date NOT NULL,
  `HanTraMoi` date NOT NULL,
  `NgayYeuCau` datetime NOT NULL DEFAULT current_timestamp(),
  `TrangThai` varchar(30) NOT NULL DEFAULT 'Chờ duyệt',
  `LyDoTuChoi` varchar(255) DEFAULT NULL,
  `CanhBao` varchar(255) DEFAULT NULL,
  `MaNhanVienXuLy` int(11) DEFAULT NULL,
  `NgayXuLy` datetime DEFAULT NULL,
  PRIMARY KEY (`MaYeuCau`),
  KEY `IDX_GiaHan_Phieu` (`MaPhieuMuon`),
  KEY `IDX_GiaHan_DocGia` (`MaDocGia`),
  KEY `IDX_GiaHan_TrangThai` (`TrangThai`),
  KEY `IDX_GiaHan_NhanVien` (`MaNhanVienXuLy`),
  CONSTRAINT `FK_GiaHan_Phieu` FOREIGN KEY (`MaPhieuMuon`) REFERENCES `phieumuon` (`MaPhieuMuon`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_GiaHan_DocGia` FOREIGN KEY (`MaDocGia`) REFERENCES `docgia` (`MaDocGia`) ON UPDATE CASCADE,
  CONSTRAINT `FK_GiaHan_NhanVien` FOREIGN KEY (`MaNhanVienXuLy`) REFERENCES `nhanvien` (`MaNhanVien`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `thongbaoemail` (
  `MaThongBao` int(11) NOT NULL AUTO_INCREMENT,
  `MaPhieuMuon` int(11) NOT NULL,
  `MaDocGia` int(11) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `LoaiThongBao` varchar(40) NOT NULL DEFAULT 'QUAHAN_30',
  `NgayGui` datetime DEFAULT NULL,
  `TrangThai` varchar(30) NOT NULL DEFAULT 'Chờ gửi',
  `Loi` text DEFAULT NULL,
  PRIMARY KEY (`MaThongBao`),
  UNIQUE KEY `UQ_Email_Phieu_Loai` (`MaPhieuMuon`,`LoaiThongBao`),
  KEY `IDX_Email_DocGia` (`MaDocGia`),
  CONSTRAINT `FK_Email_Phieu` FOREIGN KEY (`MaPhieuMuon`) REFERENCES `phieumuon` (`MaPhieuMuon`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_Email_DocGia` FOREIGN KEY (`MaDocGia`) REFERENCES `docgia` (`MaDocGia`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
