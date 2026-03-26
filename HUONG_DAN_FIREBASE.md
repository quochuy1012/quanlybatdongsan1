# 🔥 Hướng dẫn cấu hình Firebase để đăng nhập bằng Google

## ❌ Lỗi thường gặp: `redirect_uri_mismatch`

Lỗi này xảy ra khi domain của bạn chưa được thêm vào danh sách **Authorized domains** trong Firebase Console.

---

## 📋 Các bước cấu hình Firebase

### Bước 1: Tạo/Chọn Project Firebase

1. Mở trình duyệt và vào: https://console.firebase.google.com/
2. Đăng nhập bằng tài khoản Google của bạn
3. Nếu chưa có project, click **"Thêm dự án"** (Add project) và làm theo hướng dẫn
4. Nếu đã có project, chọn project: **quanlybatdongsan1-64a29** (hoặc project của bạn)

### Bước 2: Đăng ký ứng dụng Web (Web App)

**Đây là bước bạn đang làm!** 

1. Trong Firebase Console, click vào biểu tượng **⚙️ Settings** (Cài đặt) ở góc trên bên trái
2. Chọn **"Project settings"** (Cài đặt dự án)
3. Cuộn xuống phần **"Your apps"** (Ứng dụng của bạn)
4. Click vào biểu tượng **`</>`** (Web) để thêm ứng dụng Web

5. **Trong màn hình "Add Firebase to your Web app":**
   - **Pseudo de l'application** (Tên ứng dụng): 
     - Có thể giữ mặc định: `Mon application Web`
     - Hoặc đổi thành: `Quản lý Bất động sản` hoặc tên bạn muốn
   - **Configurez aussi Firebase Hosting** (Cấu hình Firebase Hosting):
     - ✅ **Bỏ chọn** (không tích) nếu bạn chỉ dùng localhost
     - ✅ **Chọn** nếu bạn muốn deploy lên Firebase Hosting sau này
   - Click nút **"Enregistrer l'application"** (Đăng ký ứng dụng)

6. **Sau khi click "Enregistrer l'application", bạn sẽ thấy thông tin cấu hình:**
   ```javascript
   const firebaseConfig = {
     apiKey: "AIzaSy...",
     authDomain: "quanlybatdongsan1-64a29.firebaseapp.com",
     projectId: "quanlybatdongsan1-64a29",
     storageBucket: "quanlybatdongsan1-64a29.firebasestorage.app",
     messagingSenderId: "50944668930",
     appId: "1:50944668930:web:..."
   };
   ```

7. **Copy các thông tin này** và cập nhật vào file `config/firebase_config.php`:
   - Nếu file đã có sẵn, kiểm tra xem thông tin có đúng không
   - Nếu chưa có, tạo file mới dựa trên `config/firebase_config.example.php`

8. Click **"Continuer vers la console"** (Tiếp tục đến console) hoặc đóng popup

**Lưu ý:** Nếu bạn đã có file `config/firebase_config.php` với thông tin đúng, bạn có thể bỏ qua bước copy này.

### Bước 3: Bật Google Authentication

1. Trong menu bên trái, click vào **Authentication** (Xác thực)
2. Click vào tab **Sign-in method** (Phương thức đăng nhập)
3. Tìm **Google** trong danh sách providers
4. Click vào **Google**
5. Bật **Enable** (Bật)
6. Chọn **Project support email** (Email hỗ trợ dự án)
7. Click **Save** (Lưu)

### Bước 4: Thêm Authorized Domains (QUAN TRỌNG NHẤT)

1. Vẫn trong trang **Authentication** > **Sign-in method**
2. Cuộn xuống phần **Authorized domains** (Các domain được ủy quyền)
3. Click vào **Add domain** (Thêm domain)

4. **Thêm các domain sau:**
   - `localhost` (cho môi trường local)
   - `127.0.0.1` (nếu bạn dùng IP)
   - Domain của bạn nếu đã deploy (ví dụ: `yourdomain.com`)

5. Click **Add** (Thêm) cho mỗi domain

**Lưu ý:** Firebase tự động thêm một số domain như:
- `quanlybatdongsan1-64a29.firebaseapp.com`
- `quanlybatdongsan1-64a29.web.app`

Nhưng bạn **PHẢI** thêm `localhost` thủ công!

### Bước 5: Kiểm tra OAuth Consent Screen (Nếu cần)

1. Vào **Google Cloud Console**: https://console.cloud.google.com/
2. Chọn project: **quanlybatdongsan1-64a29**
3. Vào **APIs & Services** > **OAuth consent screen**
4. Đảm bảo:
   - **User Type**: External (hoặc Internal nếu dùng G Suite)
   - **App name**: Tên ứng dụng của bạn
   - **Support email**: Email của bạn
   - **Authorized domains**: Đã thêm `localhost` và domain của bạn

5. Trong phần **Scopes**, đảm bảo có:
   - `.../auth/userinfo.email`
   - `.../auth/userinfo.profile`
   - `openid`

6. Trong phần **Test users** (nếu app chưa publish):
   - Thêm email Google của bạn để test

### Bước 6: Kiểm tra OAuth 2.0 Client IDs

1. Vẫn trong **Google Cloud Console**
2. Vào **APIs & Services** > **Credentials**
3. Tìm **OAuth 2.0 Client IDs**
4. Click vào client ID của Web application
5. Kiểm tra **Authorized JavaScript origins**:
   ```
   http://localhost
   http://localhost:80
   http://127.0.0.1
   http://127.0.0.1:80
   ```
   (Thêm port nếu bạn dùng port khác, ví dụ: `http://localhost:8080`)

6. Kiểm tra **Authorized redirect URIs**:
   ```
   http://localhost/__/auth/handler
   http://localhost/quanlybatdongsan1/__/auth/handler
   http://127.0.0.1/__/auth/handler
   ```
   (Thêm đường dẫn đầy đủ nếu cần)

### Bước 7: Kiểm tra file cấu hình

Đảm bảo file `config/firebase_config.php` có đúng thông tin:

```php
define('FIREBASE_AUTH_DOMAIN', 'quanlybatdongsan1-64a29.firebaseapp.com');
```

### Bước 8: Xóa cache và thử lại

1. Xóa cache trình duyệt (Ctrl + Shift + Delete)
2. Hoặc mở **Incognito/Private mode**
3. Thử đăng nhập lại bằng Google

---

## 🔍 Kiểm tra nhanh

Sau khi cấu hình, kiểm tra:

1. **Firebase Console** > **Authentication** > **Sign-in method** > **Google**:
   - ✅ Đã bật (Enabled)
   - ✅ Có `localhost` trong Authorized domains

2. **Google Cloud Console** > **Credentials** > **OAuth 2.0 Client IDs**:
   - ✅ Có `http://localhost` trong Authorized JavaScript origins
   - ✅ Có redirect URI phù hợp

---

## 🐛 Xử lý lỗi thường gặp

### Lỗi 1: `redirect_uri_mismatch`
**Nguyên nhân:** Domain chưa được thêm vào Authorized domains
**Giải pháp:** Làm lại Bước 3

### Lỗi 2: `popup_closed_by_user`
**Nguyên nhân:** Người dùng đóng popup
**Giải pháp:** Không phải lỗi, thử lại

### Lỗi 3: `unauthorized_domain`
**Nguyên nhân:** Domain không được phép
**Giải pháp:** Kiểm tra lại Authorized domains trong Firebase

### Lỗi 4: `access_denied`
**Nguyên nhân:** OAuth consent screen chưa được cấu hình đúng
**Giải pháp:** Làm lại Bước 4

---

## 📝 Lưu ý quan trọng

1. **Localhost:** Phải thêm `localhost` vào Authorized domains
2. **Port:** Nếu dùng port khác (ví dụ: 8080), thêm `http://localhost:8080`
3. **HTTPS:** Firebase yêu cầu HTTPS cho production (trừ localhost)
4. **Thời gian:** Sau khi thay đổi cấu hình, có thể mất vài phút để có hiệu lực

---

## ✅ Checklist hoàn tất

- [ ] Đã bật Google Authentication trong Firebase
- [ ] Đã thêm `localhost` vào Authorized domains
- [ ] Đã cấu hình OAuth consent screen
- [ ] Đã thêm JavaScript origins trong Google Cloud Console
- [ ] Đã thêm redirect URIs
- [ ] Đã xóa cache trình duyệt
- [ ] Đã test lại đăng nhập

---

## 🆘 Cần hỗ trợ?

Nếu vẫn gặp lỗi, kiểm tra:
1. Console của trình duyệt (F12) để xem lỗi chi tiết
2. Network tab để xem request/response
3. Firebase Console > Authentication > Users để xem có user nào được tạo không

---

**Chúc bạn cấu hình thành công! 🎉**

