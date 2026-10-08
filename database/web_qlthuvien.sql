-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 13, 2026 lúc 02:55 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `web_qlthuvien`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `caidat`
--

CREATE TABLE `caidat` (
  `MaCaiDat` int(11) NOT NULL,
  `TenThuVien` varchar(200) DEFAULT NULL,
  `DiaChi` varchar(255) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `SDT` varchar(20) DEFAULT NULL,
  `SoNgayMuon` int(11) DEFAULT NULL,
  `SoNgayGiaHan` int(11) DEFAULT NULL,
  `MucPhat` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietphieumuon`
--

CREATE TABLE `chitietphieumuon` (
  `MaCTPM` int(11) NOT NULL,
  `MaPhieuMuon` int(11) DEFAULT NULL,
  `MaSach` int(11) DEFAULT NULL,
  `SoLuong` int(11) DEFAULT 1,
  `TienPhat` decimal(10,2) DEFAULT 0.00,
  `GhiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `docgia`
--

CREATE TABLE `docgia` (
  `MaDocGia` int(11) NOT NULL,
  `HoTen` varchar(100) NOT NULL,
  `NgaySinh` date DEFAULT NULL,
  `GioiTinh` varchar(10) DEFAULT NULL,
  `SDT` varchar(15) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `DiaChi` varchar(255) DEFAULT NULL,
  `NgayDangKy` date DEFAULT NULL,
  `TrangThai` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhanvien`
--

CREATE TABLE `nhanvien` (
  `MaNhanVien` int(11) NOT NULL,
  `HoTen` varchar(100) NOT NULL,
  `GioiTinh` varchar(10) DEFAULT NULL,
  `NgaySinh` date DEFAULT NULL,
  `SDT` varchar(15) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `VaiTro` varchar(50) DEFAULT NULL,
  `NgayVaoLam` date DEFAULT NULL,
  `TrangThai` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieumuon`
--

CREATE TABLE `phieumuon` (
  `MaPhieuMuon` int(11) NOT NULL,
  `MaDocGia` int(11) DEFAULT NULL,
  `MaNhanVien` int(11) DEFAULT NULL,
  `NgayMuon` date DEFAULT NULL,
  `HanTra` date DEFAULT NULL,
  `NgayTra` date DEFAULT NULL,
  `TrangThai` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sach`
--

CREATE TABLE `sach` (
  `MaSach` int(11) NOT NULL,
  `TenSach` varchar(200) NOT NULL,
  `TacGia` varchar(150) DEFAULT NULL,
  `NhaXuatBan` varchar(150) DEFAULT NULL,
  `NamXuatBan` int(11) DEFAULT NULL,
  `SoLuong` int(11) DEFAULT 0,
  `HinhAnh` varchar(255) DEFAULT NULL,
  `MoTa` text DEFAULT NULL,
  `MaTheLoai` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `taikhoan`
--

CREATE TABLE `taikhoan` (
  `MaTaiKhoan` int(11) NOT NULL,
  `TenDangNhap` varchar(50) NOT NULL,
  `MatKhau` varchar(255) NOT NULL,
  `LoaiTaiKhoan` varchar(20) NOT NULL,
  `MaNhanVien` int(11) DEFAULT NULL,
  `MaDocGia` int(11) DEFAULT NULL,
  `NgayTao` datetime DEFAULT current_timestamp(),
  `TrangThai` varchar(30) DEFAULT 'Đang hoạt động'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `theloai`
--

CREATE TABLE `theloai` (
  `MaTheLoai` int(11) NOT NULL,
  `TenTheLoai` varchar(100) NOT NULL,
  `MoTa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `caidat`
--
ALTER TABLE `caidat`
  ADD PRIMARY KEY (`MaCaiDat`);

--
-- Chỉ mục cho bảng `chitietphieumuon`
--
ALTER TABLE `chitietphieumuon`
  ADD PRIMARY KEY (`MaCTPM`),
  ADD KEY `FK_CTPM_PM` (`MaPhieuMuon`),
  ADD KEY `FK_CTPM_Sach` (`MaSach`);

--
-- Chỉ mục cho bảng `docgia`
--
ALTER TABLE `docgia`
  ADD PRIMARY KEY (`MaDocGia`);

--
-- Chỉ mục cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  ADD PRIMARY KEY (`MaNhanVien`);

--
-- Chỉ mục cho bảng `phieumuon`
--
ALTER TABLE `phieumuon`
  ADD PRIMARY KEY (`MaPhieuMuon`),
  ADD KEY `FK_PhieuMuon_DocGia` (`MaDocGia`),
  ADD KEY `FK_PhieuMuon_NhanVien` (`MaNhanVien`);

--
-- Chỉ mục cho bảng `sach`
--
ALTER TABLE `sach`
  ADD PRIMARY KEY (`MaSach`),
  ADD KEY `FK_Sach_TheLoai` (`MaTheLoai`);

--
-- Chỉ mục cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`MaTaiKhoan`),
  ADD UNIQUE KEY `TenDangNhap` (`TenDangNhap`),
  ADD KEY `FK_TaiKhoan_NhanVien` (`MaNhanVien`),
  ADD KEY `FK_TaiKhoan_DocGia` (`MaDocGia`);

--
-- Chỉ mục cho bảng `theloai`
--
ALTER TABLE `theloai`
  ADD PRIMARY KEY (`MaTheLoai`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `caidat`
--
ALTER TABLE `caidat`
  MODIFY `MaCaiDat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietphieumuon`
--
ALTER TABLE `chitietphieumuon`
  MODIFY `MaCTPM` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `docgia`
--
ALTER TABLE `docgia`
  MODIFY `MaDocGia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  MODIFY `MaNhanVien` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phieumuon`
--
ALTER TABLE `phieumuon`
  MODIFY `MaPhieuMuon` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `sach`
--
ALTER TABLE `sach`
  MODIFY `MaSach` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `MaTaiKhoan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `theloai`
--
ALTER TABLE `theloai`
  MODIFY `MaTheLoai` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chitietphieumuon`
--
ALTER TABLE `chitietphieumuon`
  ADD CONSTRAINT `FK_CTPM_PM` FOREIGN KEY (`MaPhieuMuon`) REFERENCES `phieumuon` (`MaPhieuMuon`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_CTPM_Sach` FOREIGN KEY (`MaSach`) REFERENCES `sach` (`MaSach`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phieumuon`
--
ALTER TABLE `phieumuon`
  ADD CONSTRAINT `FK_PhieuMuon_DocGia` FOREIGN KEY (`MaDocGia`) REFERENCES `docgia` (`MaDocGia`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_PhieuMuon_NhanVien` FOREIGN KEY (`MaNhanVien`) REFERENCES `nhanvien` (`MaNhanVien`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `sach`
--
ALTER TABLE `sach`
  ADD CONSTRAINT `FK_Sach_TheLoai` FOREIGN KEY (`MaTheLoai`) REFERENCES `theloai` (`MaTheLoai`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD CONSTRAINT `FK_TaiKhoan_DocGia` FOREIGN KEY (`MaDocGia`) REFERENCES `docgia` (`MaDocGia`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_TaiKhoan_NhanVien` FOREIGN KEY (`MaNhanVien`) REFERENCES `nhanvien` (`MaNhanVien`) ON DELETE CASCADE ON UPDATE CASCADE;


-- ============================================================
-- BỔ SUNG CHỨC NĂNG MỚI CHO ĐỒ ÁN
-- Ca làm việc, đặt lịch mượn, gia hạn, lương, lịch sử email
-- ============================================================

ALTER TABLE `nhanvien`
  ADD COLUMN IF NOT EXISTS `Luong` decimal(15,2) NOT NULL DEFAULT 0 AFTER `VaiTro`;

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
  CONSTRAINT `FK_PhanCongCa_NhanVien` FOREIGN KEY (`MaNhanVien`) REFERENCES `nhanvien` (`MaNhanVien`) ON UPDATE CASCADE,
  CONSTRAINT `FK_PhanCongCa_Ca` FOREIGN KEY (`MaCa`) REFERENCES `calamviec` (`MaCa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

INSERT INTO `calamviec` (`TenCa`,`GioBatDau`,`GioKetThuc`,`MoTa`,`TrangThai`)
SELECT 'Ca sáng','07:00:00','12:00:00','Ca làm buổi sáng','Hoạt động'
WHERE NOT EXISTS (SELECT 1 FROM `calamviec` WHERE `TenCa`='Ca sáng');

INSERT INTO `calamviec` (`TenCa`,`GioBatDau`,`GioKetThuc`,`MoTa`,`TrangThai`)
SELECT 'Ca chiều','12:00:00','17:00:00','Ca làm buổi chiều','Hoạt động'
WHERE NOT EXISTS (SELECT 1 FROM `calamviec` WHERE `TenCa`='Ca chiều');

INSERT INTO `calamviec` (`TenCa`,`GioBatDau`,`GioKetThuc`,`MoTa`,`TrangThai`)
SELECT 'Ca tối','17:00:00','21:00:00','Ca làm buổi tối','Hoạt động'
WHERE NOT EXISTS (SELECT 1 FROM `calamviec` WHERE `TenCa`='Ca tối');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
