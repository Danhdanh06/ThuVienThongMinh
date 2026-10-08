BẢN NÂNG CẤP QL THƯ VIỆN - 24 TÍNH NĂNG

Nguyên tắc:
- Không DROP bảng, không DELETE/reset dữ liệu cũ trong migration.
- Các bảng/cột mới được tạo tự động khi web kết nối database.
- Các trang/chức năng cũ được giữ nguyên; bổ sung khu "Thư viện thông minh".

Đã bổ sung:
1. Quản lý từng cuốn sách bằng mã TV-Sxxx-xx, trạng thái Có sẵn/Đang mượn/Đang giữ chỗ/Hỏng/Mất/Thanh lý.
2. Thẻ thư viện điện tử cho độc giả, QR DGxxx, hỗ trợ in/lưu PDF từ trình duyệt.
3. Mượn/trả nhanh bằng quét/nhập mã; hỗ trợ BarcodeDetector khi trình duyệt có hỗ trợ.
4. Trung tâm thông báo + chuông/badge trên header.
5. Sổ tiền phạt: Chưa thanh toán/Đã thanh toán/Miễn phạt, người thu, ngày thanh toán, lý do.
6. Biên nhận mượn/trả và nút In/Lưu PDF.
7. Gợi ý sách theo thể loại lịch sử mượn.
8. Danh sách Muốn đọc/Yêu thích, thông báo khi sách có sẵn.
9. Hàng chờ giữ sách; giữ 24 giờ và tự chuyển người tiếp theo khi hết hạn.
10. Nhà cung cấp, phiếu nhập, giá nhập, nhật ký tồn kho, mất/hỏng/thanh lý/kiểm kê.
11. Nhật ký hoạt động toàn hệ thống; các POST API cũ cũng được ghi log.
12. Hồ sơ nhân viên hiển thị chức vụ/trạng thái/ngày vào làm/số ca tháng.
13. Nhân viên tự đăng ký ca; quản lý duyệt/từ chối; chống trùng giờ; giới hạn số người/ca.
14. Dashboard nâng cao: bản có sẵn, đang mượn, quá hạn, tiền phạt, top sách/thể loại.
15. Trung tâm cảnh báo: quá hạn, hàng chờ, tiền phạt, tài khoản khóa, sách hết tồn, ca thiếu người.
16. Global Search toàn hệ thống; hỗ trợ tìm sách/độc giả/nhân viên/phiếu mượn.
17. Command Palette bằng Ctrl+K hoặc phím /.
18. Theme Sáng/Tối/Theo hệ thống; nhấn nút theme để xoay vòng.
19. Mobile bottom navigation: Trang chủ/Sách/Quét/Thông báo/Tôi.
20. Khu chi tiết sách: mô tả, ISBN, vị trí, tồn, lượt mượn, đánh giá, sách liên quan.
21. Vị trí kệ sách, đồng bộ xuống từng cuốn chưa có vị trí riêng.
22. Đánh giá/nhận xét: chỉ người từng mượn được đánh giá; quản lý có thể ẩn.
23. Huy hiệu độc giả theo số cuốn, trả đúng hạn, số thể loại đã khám phá.
24. Hồ sơ đọc cá nhân: số cuốn/lượt mượn năm nay, tỷ lệ đúng hạn, thể loại yêu thích, tháng đọc nhiều.

Lưu ý QR:
- Hình QR đang dùng dịch vụ tạo QR trực tuyến; mã chữ vẫn hiển thị và có thể nhập thủ công nếu mất mạng.
- Quét camera phụ thuộc trình duyệt hỗ trợ BarcodeDetector; nếu không, nhập mã bằng bàn phím/máy quét barcode USB vẫn dùng được.

Database cũ:
- Chỉ cần giữ nguyên database web_qlthuvien và chép code mới vào XAMPP.
- Lần mở đầu tiên, migration tự thêm bảng/cột mới.
- Không cần import lại dữ liệu cũ.
