# 🏠 Bất Động Sản - Website Tìm Nhà Trọ, Căn Hộ

Website tìm kiếm và cho thuê bất động sản tại TP.HCM được xây dựng bằng PHP và MySQL.

## ✨ Tính năng

### 1. Giao diện
- ✅ Tông màu dễ nhìn, thân thiện
- ✅ Giao diện đơn giản, dễ sử dụng
- ✅ Trang chủ hiển thị các khu vực được tìm nhiều nhất (Thủ Đức, Bình Thạnh, Gò Vấp)
- ✅ Biểu đồ thống kê tìm kiếm theo khu vực

### 2. Chức năng
- ✅ **Đăng nhập/Đăng ký** với phân quyền:
  - Người thuê (Tenant)
  - Người cho thuê (Landlord)
  - Admin quản lý
- ✅ **Đăng nhập** bằng số điện thoại hoặc Gmail
- ✅ **Tìm kiếm** với bộ lọc:
  - Tìm kiếm bằng từ khóa
  - Lọc theo quận/huyện
  - Lọc theo loại bất động sản
  - Lọc theo giá
- ✅ **Đặt lịch hẹn** với người cho thuê
- ✅ **Chat với Admin** để được hỗ trợ
- ✅ **Thống kê biểu đồ** cho Admin

## 🚀 Cài đặt

### Yêu cầu hệ thống
- XAMPP (PHP 7.4+ và MySQL)
- Trình duyệt web hiện đại
- Tài khoản Firebase (cho đăng nhập Gmail)

### Các bước cài đặt

1. **Copy project vào thư mục htdocs của XAMPP**
   ```
   C:\xampp\htdocs\quanlybatdongsan1
   ```

2. **Khởi động XAMPP**
   - Mở XAMPP Control Panel
   - Start Apache và MySQL

3. **Tạo database**
   - Mở phpMyAdmin: http://localhost/phpmyadmin
   - Import file `database.sql` hoặc chạy các câu lệnh SQL trong file đó

4. **Cấu hình database** (nếu cần)
   - Mở file `config/database.php`
   - Kiểm tra và chỉnh sửa thông tin kết nối nếu cần:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'quanlybatdongsan1');
     ```

5. **Cấu hình Firebase (cho đăng nhập Gmail)** - Tùy chọn
   - Xem hướng dẫn chi tiết trong file `FIREBASE_SETUP.md`
   - Copy `config/firebase_config.example.php` thành `config/firebase_config.php`
   - Điền thông tin Firebase từ Firebase Console

6. **Truy cập website**
   - Mở trình duyệt và vào: http://localhost/quanlybatdongsan1

## 👤 Tài khoản mặc định

### Admin
- **Email:** admin@quanlybatdongsan1.com
- **Password:** password123


> **Lưu ý:** Tất cả tài khoản demo đều dùng mật khẩu `password123`

## 📁 Cấu trúc thư mục

```
quanlybatdongsan1/
├── admin/              # Trang quản trị
│   ├── dashboard.php   # Bảng điều khiển
│   └── messages.php    # Quản lý tin nhắn
├── assets/             # Tài nguyên
│   ├── css/
│   │   └── style.css   # File CSS chính
│   └── js/
│       └── main.js     # File JavaScript
├── config/             # Cấu hình
│   ├── database.php    # Kết nối database
│   └── session.php     # Quản lý session
├── includes/           # File include
│   ├── header.php      # Header chung
│   └── footer.php      # Footer chung
├── landlord/           # Trang người cho thuê
│   ├── properties.php  # Quản lý BĐS
│   ├── property_add.php
│   ├── property_edit.php
│   └── appointments.php
├── tenant/             # Trang người thuê
│   └── appointments.php
├── index.php           # Trang chủ
├── login.php           # Đăng nhập
├── register.php        # Đăng ký
├── search.php          # Tìm kiếm
├── property_detail.php # Chi tiết BĐS
├── appointment_create.php
├── chat.php            # Chat với Admin
├── profile.php         # Thông tin cá nhân
├── logout.php          # Đăng xuất
├── database.sql        # File SQL tạo database
└── README.md           # File hướng dẫn
```

## 🎨 Tính năng chi tiết

### Trang chủ
- Hiển thị các khu vực được tìm kiếm nhiều nhất
- Biểu đồ thống kê tìm kiếm
- Danh sách bất động sản mới nhất
- Thanh tìm kiếm với đầy đủ bộ lọc

### Người thuê (Tenant)
- Tìm kiếm bất động sản
- Xem chi tiết bất động sản
- Đặt lịch hẹn xem nhà
- Xem lịch hẹn của mình
- Chat với Admin

### Người cho thuê (Landlord)
- Quản lý bất động sản (thêm, sửa, xóa)
- Xem và quản lý lịch hẹn
- Xác nhận/hủy lịch hẹn

### Admin
- Bảng điều khiển với thống kê tổng quan
- Biểu đồ thống kê người dùng
- Biểu đồ thống kê tìm kiếm
- Quản lý tin nhắn từ người dùng
- Trả lời tin nhắn hỗ trợ

## 🔧 Công nghệ sử dụng

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Chart Library:** Chart.js
- **Server:** Apache (XAMPP)

## 📝 Ghi chú

- Website này được xây dựng cho mục đích học tập và demo
- Mật khẩu được hash bằng `password_hash()` của PHP
- Session được sử dụng để quản lý đăng nhập
- Tất cả dữ liệu được lưu trữ trong MySQL database

## 🐛 Xử lý lỗi thường gặp

1. **Lỗi kết nối database:**
   - Kiểm tra XAMPP đã start MySQL chưa
   - Kiểm tra thông tin trong `config/database.php`

2. **Lỗi 404:**
   - Kiểm tra đường dẫn file
   - Đảm bảo đã copy đầy đủ các file

3. **Lỗi session:**
   - Kiểm tra quyền ghi của thư mục
   - Xóa cache trình duyệt

## 📞 Hỗ trợ

Nếu có vấn đề, vui lòng liên hệ qua chức năng Chat với Admin trong website.

---

**Chúc bạn sử dụng vui vẻ! 🎉**

