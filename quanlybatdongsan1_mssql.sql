-- SQL Server schema + seed data for quanlybatdongsan1
-- Target: Microsoft SQL Server

IF DB_ID(N'quanlybatdongsan1') IS NULL
BEGIN
    CREATE DATABASE quanlybatdongsan1;
END
GO

USE quanlybatdongsan1;
GO

-- Drop tables if exist (order matters because of FKs)
IF OBJECT_ID(N'dbo.appointments', N'U') IS NOT NULL DROP TABLE dbo.appointments;
IF OBJECT_ID(N'dbo.messages', N'U') IS NOT NULL DROP TABLE dbo.messages;
IF OBJECT_ID(N'dbo.search_statistics', N'U') IS NOT NULL DROP TABLE dbo.search_statistics;
IF OBJECT_ID(N'dbo.properties', N'U') IS NOT NULL DROP TABLE dbo.properties;
IF OBJECT_ID(N'dbo.users', N'U') IS NOT NULL DROP TABLE dbo.users;
GO

CREATE TABLE dbo.users (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    full_name NVARCHAR(100) NOT NULL,
    phone NVARCHAR(20) NULL,
    email NVARCHAR(100) NULL,
    password NVARCHAR(255) NOT NULL,
    role NVARCHAR(20) NOT NULL CONSTRAINT DF_users_role DEFAULT N'tenant',
    created_at DATETIME2 NOT NULL CONSTRAINT DF_users_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_users_updated_at DEFAULT SYSUTCDATETIME()
);
GO

CREATE UNIQUE INDEX UX_users_phone ON dbo.users(phone) WHERE phone IS NOT NULL AND phone <> N'';
CREATE UNIQUE INDEX UX_users_email ON dbo.users(email) WHERE email IS NOT NULL AND email <> N'';
GO

CREATE TABLE dbo.properties (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    landlord_id INT NOT NULL,
    title NVARCHAR(200) NOT NULL,
    description NVARCHAR(MAX) NULL,
    address NVARCHAR(255) NOT NULL,
    district NVARCHAR(100) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    area DECIMAL(8,2) NOT NULL,
    bedrooms INT NOT NULL CONSTRAINT DF_properties_bedrooms DEFAULT 0,
    bathrooms INT NOT NULL CONSTRAINT DF_properties_bathrooms DEFAULT 0,
    total_rooms INT NOT NULL CONSTRAINT DF_properties_total_rooms DEFAULT 1,
    rented_rooms INT NOT NULL CONSTRAINT DF_properties_rented_rooms DEFAULT 0,
    property_type NVARCHAR(20) NOT NULL CONSTRAINT DF_properties_property_type DEFAULT N'apartment',
    status NVARCHAR(20) NOT NULL CONSTRAINT DF_properties_status DEFAULT N'available',
    images NVARCHAR(MAX) NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT DF_properties_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_properties_updated_at DEFAULT SYSUTCDATETIME(),
    CONSTRAINT FK_properties_landlord FOREIGN KEY (landlord_id) REFERENCES dbo.users(id) ON DELETE CASCADE
);
GO

CREATE TABLE dbo.appointments (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    tenant_id INT NOT NULL,
    property_id INT NOT NULL,
    landlord_id INT NOT NULL,
    appointment_date DATETIME2 NOT NULL,
    message NVARCHAR(MAX) NULL,
    status NVARCHAR(20) NOT NULL CONSTRAINT DF_appointments_status DEFAULT N'pending',
    created_at DATETIME2 NOT NULL CONSTRAINT DF_appointments_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT DF_appointments_updated_at DEFAULT SYSUTCDATETIME(),
    -- Tránh "multiple cascade paths" khi vừa appointments vừa messages đều trỏ tới users/properties
    CONSTRAINT FK_appointments_tenant FOREIGN KEY (tenant_id) REFERENCES dbo.users(id) ON DELETE NO ACTION,
    CONSTRAINT FK_appointments_property FOREIGN KEY (property_id) REFERENCES dbo.properties(id) ON DELETE NO ACTION,
    CONSTRAINT FK_appointments_landlord FOREIGN KEY (landlord_id) REFERENCES dbo.users(id) ON DELETE NO ACTION
);
GO

CREATE TABLE dbo.messages (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NULL,
    message NVARCHAR(MAX) NOT NULL,
    response NVARCHAR(MAX) NULL,
    status NVARCHAR(20) NOT NULL CONSTRAINT DF_messages_status DEFAULT N'pending',
    created_at DATETIME2 NOT NULL CONSTRAINT DF_messages_created_at DEFAULT SYSUTCDATETIME(),
    -- Tránh "multiple cascade paths" khi users bị xóa liên quan đến nhiều FK
    CONSTRAINT FK_messages_user FOREIGN KEY (user_id) REFERENCES dbo.users(id) ON DELETE NO ACTION,
    CONSTRAINT FK_messages_admin FOREIGN KEY (admin_id) REFERENCES dbo.users(id) ON DELETE NO ACTION
);
GO

CREATE TABLE dbo.search_statistics (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    district NVARCHAR(100) NOT NULL,
    search_count INT NOT NULL CONSTRAINT DF_search_statistics_count DEFAULT 1,
    last_searched DATETIME2 NOT NULL CONSTRAINT DF_search_statistics_last DEFAULT SYSUTCDATETIME(),
    CONSTRAINT UX_search_statistics_district UNIQUE (district)
);
GO

CREATE INDEX IX_search_statistics_district ON dbo.search_statistics(district);
GO

-- Update triggers for updated_at columns (SQL Server has no ON UPDATE CURRENT_TIMESTAMP)
CREATE OR ALTER TRIGGER dbo.trg_users_updated_at ON dbo.users
AFTER UPDATE AS
BEGIN
    SET NOCOUNT ON;
    UPDATE u SET updated_at = SYSUTCDATETIME()
    FROM dbo.users u
    INNER JOIN inserted i ON i.id = u.id;
END
GO

CREATE OR ALTER TRIGGER dbo.trg_properties_updated_at ON dbo.properties
AFTER UPDATE AS
BEGIN
    SET NOCOUNT ON;
    UPDATE p SET updated_at = SYSUTCDATETIME()
    FROM dbo.properties p
    INNER JOIN inserted i ON i.id = p.id;
END
GO

CREATE OR ALTER TRIGGER dbo.trg_appointments_updated_at ON dbo.appointments
AFTER UPDATE AS
BEGIN
    SET NOCOUNT ON;
    UPDATE a SET updated_at = SYSUTCDATETIME()
    FROM dbo.appointments a
    INNER JOIN inserted i ON i.id = a.id;
END
GO

-- Seed data from provided MySQL dump (ids preserved)
SET IDENTITY_INSERT dbo.users ON;
INSERT INTO dbo.users (id, full_name, phone, email, password, role, created_at, updated_at) VALUES
(1, N'Admin', NULL, N'admin@quanlybatdongsan1.com', N'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', N'admin', '2025-11-19T03:27:52', '2025-11-19T03:27:52'),
(5, N'Cương 5169_Nguyễn', NULL, N'cuonghotran17022004@gmail.com', N'$2y$10$kQ4czXHv69eYCa6DJ2z/O.Fg9KhjWrQleH7huUKQP/f0F8wz7KOBK', N'tenant', '2025-11-19T03:52:08', '2025-11-19T03:52:08'),
(6, N'Nguyễn Cương', N'0356012250', N'cuonghotran1233@gmail.com', N'$2y$10$4fQ4PTkFFeo/uxmjeQZa4O4ImvNSKD6xJ5yFAgCl4tGKHXD.yQTwS', N'tenant', '2025-11-19T03:52:56', '2025-11-19T03:52:56'),
(7, N'NGUYỄN HOA', N'0356012255', N'dsadas@gmail.com', N'$2y$10$YeiL0.OjzilX2k1DRAwI2OcfmXjTR0XMwPRib5V20MB8mT1VpI89q', N'landlord', '2025-11-19T05:39:50', '2025-11-19T05:39:50'),
(8, N'Nguyễn Ngọc', N'0999999999', N'ngoc123@gmail.com', N'$2y$10$5jh528HsZrNayS0u11YyE.MY/BN7uzk5Ty.bKD7.jGjDzbs53pGIO', N'landlord', '2025-11-19T07:01:52', '2025-11-19T07:01:52'),
(9, N'Người cho thuê', N'0988888888', N'khanhtrinh9999999@gmail.com', N'$2y$10$MQ6PZ7Z95haHHXfqmkloTeqIbrVcxXO2qA6NlZpXsNBmZ50Bd1GNe', N'landlord', '2025-11-19T07:58:58', '2025-11-19T07:58:58');
SET IDENTITY_INSERT dbo.users OFF;
GO

SET IDENTITY_INSERT dbo.properties ON;
INSERT INTO dbo.properties (id, landlord_id, title, description, address, district, price, area, bedrooms, bathrooms, total_rooms, rented_rooms, property_type, status, images, created_at, updated_at) VALUES
(6, 7, N'dsadsa', N'dsad', N'dsadsa', N'Bình Thạnh', 5000000.00, 1000.00, 2, 2, 1, 0, N'house', N'available', N'[\"https:\\/\\/noithatnhatminh.vn\\/wp-content\\/uploads\\/2022\\/03\\/z2705365994589_4c31781f22a5d8dacf47f4d91f572566.jpg\"]', '2025-11-19T05:41:27', '2025-11-19T06:20:07'),
(7, 8, N'hghghghg', N'dsadsadsa', N'dsadsad', N'Thủ Đức', 1000000000.00, 100.00, 3, 3, 1, 0, N'house', N'available', N'[\"https:\\/\\/encrypted-tbn0.gstatic.com\\/images?q=tbn:ANd9GcQkQKv-OD4gRTARNaGz8tAp0IwzR-VhzvgKcg&s\"]', '2025-11-19T07:03:06', '2025-11-19T07:03:06'),
(8, 9, N'dasdas', N'dasd', N'221 ô 1 khu phố hải bình', N'Gò Vấp', 10000000.00, 70.00, 2, 3, 1, 0, N'apartment', N'available', N'[\"https:\\/\\/media.vneconomy.vn\\/images\\/upload\\/2022\\/11\\/16\\/56c99f14-2861-4dc2-ae06-3c04c8b22a63.jpg\"]', '2025-11-19T08:18:29', '2025-11-19T08:27:31');
SET IDENTITY_INSERT dbo.properties OFF;
GO

SET IDENTITY_INSERT dbo.appointments ON;
INSERT INTO dbo.appointments (id, tenant_id, property_id, landlord_id, appointment_date, message, status, created_at, updated_at) VALUES
(1, 5, 6, 7, '2025-11-19T13:53:00', N'dasd', N'confirmed', '2025-11-19T06:52:38', '2025-11-19T06:54:13'),
(2, 5, 6, 7, '2025-11-19T09:00:00', N'sdasd', N'pending', '2025-11-19T06:53:06', '2025-11-19T06:53:06'),
(3, 5, 7, 8, '2025-11-20T11:00:00', N'dasdsadsa', N'confirmed', '2025-11-19T07:03:50', '2025-11-19T07:04:05'),
(4, 5, 8, 9, '2025-11-19T13:00:00', N'lẹ lên', N'pending', '2025-11-19T08:27:58', '2025-11-19T08:27:58');
SET IDENTITY_INSERT dbo.appointments OFF;
GO

SET IDENTITY_INSERT dbo.messages ON;
INSERT INTO dbo.messages (id, user_id, admin_id, message, response, status, created_at) VALUES
(1, 1, 1, N'gh', N'aaa', N'replied', '2025-11-19T06:36:13'),
(2, 5, NULL, N'dsa', NULL, N'pending', '2025-11-19T06:44:22'),
(3, 5, 1, N'aaaaaa', N'dsadsa', N'replied', '2025-11-19T07:00:12');
SET IDENTITY_INSERT dbo.messages OFF;
GO

SET IDENTITY_INSERT dbo.search_statistics ON;
INSERT INTO dbo.search_statistics (id, district, search_count, last_searched) VALUES
(1, N'Thủ Đức', 154, '2025-11-19T06:59:13'),
(2, N'Bình Thạnh', 128, '2025-11-19T06:59:15'),
(3, N'Gò Vấp', 102, '2025-11-19T06:33:08'),
(4, N'Quận 1', 80, '2025-11-19T03:27:52'),
(5, N'Quận 7', 60, '2025-11-19T03:27:52');
SET IDENTITY_INSERT dbo.search_statistics OFF;
GO

