# SQL Server Setup (PHP + PDO SQLSRV)

Project này đã được chuyển từ MySQL sang **Microsoft SQL Server** và dùng **PDO driver `pdo_sqlsrv`**.

## 1) Chuẩn bị SQL Server
- Tạo database: `quanlybatdongsan1`
- Chạy script: `quanlybatdongsan1_mssql.sql` trong SSMS/Azure Data Studio

## 2) Bật driver `pdo_sqlsrv` cho PHP
Bạn cần đúng **PHP đang chạy web server** (Apache/XAMPP/Laragon/IIS).

### Cách kiểm tra nhanh PHP đang dùng
- Tạo file `phpinfo.php` (tạm thời) trong web root:

```php
<?php phpinfo();
```

- Mở `http://localhost/phpinfo.php`
- Xem mục **Loaded Configuration File** để biết `php.ini` nào đang dùng.

### Bật extension trong `php.ini`
Trong file `php.ini` đó:
- Tìm và bật:
  - `extension=pdo_sqlsrv`
  - (tuỳ chọn) `extension=sqlsrv`

Nếu chưa có DLL, bạn cần cài **Microsoft Drivers for PHP for SQL Server** đúng version PHP + x64/x86.

Sau đó:
- Restart Apache/IIS.
- Mở lại `phpinfo()` và tìm module `pdo_sqlsrv`.

## 3) Cấu hình kết nối trong code
File: `config/database.php`
- `DB_SERVER`: hiện để mặc định `quochuy`
- `DB_NAME`: `quanlybatdongsan1`
- `DB_TRUSTED_CONNECTION`: `true` (Windows Authentication)

Nếu bạn muốn dùng SQL Login:
- set `DB_TRUSTED_CONNECTION` = `false`
- điền `DB_USER` / `DB_PASS`

## 4) Ghi chú quan trọng
- Windows Authentication sẽ chạy theo **user của service** (ví dụ Apache service). Nếu service user không có quyền SQL Server, bạn sẽ cần:\n  - Chạy Apache dưới user của bạn, hoặc\n  - Cấp quyền SQL Server cho service user, hoặc\n+  - Dùng SQL Login.

