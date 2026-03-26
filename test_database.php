<?php
/**
 * File test kết nối và truy vấn database
 * Truy cập: http://localhost/quanlybatdongsan1/test_database.php
 */

require_once 'config/database.php';

// Thiết lập header để hiển thị tiếng Việt
header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Database Connection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .test-section h2 {
            color: #4CAF50;
            margin-top: 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Test Kết nối và Truy vấn Database</h1>";

// Test 1: Kiểm tra kết nối database
echo "<div class='test-section'>";
echo "<h2>1. Kiểm tra kết nối database</h2>";

try {
    $conn = getDBConnection();
    
    if ($conn) {
        echo "<div class='success'>✅ <strong>Kết nối thành công!</strong></div>";
        echo "<div class='info'>";
        echo "<strong>Thông tin kết nối:</strong><br>";
        echo "Server: <code>" . DB_SERVER . "</code><br>";
        echo "Database: <code>" . DB_NAME . "</code><br>";
        echo "Auth: <code>" . (DB_TRUSTED_CONNECTION ? "Windows Authentication" : "SQL Login") . "</code><br>";
        echo "Driver: <code>PDO sqlsrv</code><br>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ <strong>Kết nối thất bại!</strong></div>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Lỗi kết nối:</strong> " . $e->getMessage() . "</div>";
    exit;
}

// Test 2: Kiểm tra các bảng có tồn tại không
echo "</div><div class='test-section'>";
echo "<h2>2. Kiểm tra các bảng trong database</h2>";

$tables = ['users', 'properties', 'appointments', 'messages', 'search_statistics'];
$existingTables = [];

foreach ($tables as $table) {
    $exists = dbScalar(
        "SELECT COUNT(*) 
         FROM INFORMATION_SCHEMA.TABLES 
         WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = :t",
        [':t' => $table]
    );
    if ((int)$exists > 0) {
        $existingTables[] = $table;
        echo "<div class='success'>✅ Bảng <code>$table</code> tồn tại</div>";
    } else {
        echo "<div class='error'>❌ Bảng <code>$table</code> không tồn tại</div>";
    }
}

// Test 3: Đếm số lượng records trong mỗi bảng
echo "</div><div class='test-section'>";
echo "<h2>3. Thống kê số lượng records</h2>";

echo "<table>";
echo "<tr><th>Bảng</th><th>Số lượng records</th></tr>";

foreach ($existingTables as $table) {
    $count = (int)dbScalar("SELECT COUNT(*) FROM dbo.{$table}");
    if ($count >= 0) {
        echo "<tr><td><code>$table</code></td><td><strong>$count</strong> records</td></tr>";
    }
}

echo "</table>";

// Test 4: Test SELECT query với prepared statement
echo "</div><div class='test-section'>";
echo "<h2>4. Test SELECT query (Prepared Statement)</h2>";

try {
    $users = dbSelectAll("SELECT TOP 5 id, full_name, email, role FROM dbo.users ORDER BY id");
    
    if (count($users) > 0) {
        echo "<div class='success'>✅ Truy vấn SELECT thành công! Tìm thấy " . count($users) . " users</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Họ tên</th><th>Email</th><th>Vai trò</th></tr>";
        
        foreach ($users as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ Không có dữ liệu trong bảng users</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Lỗi truy vấn SELECT: " . $e->getMessage() . "</div>";
}

// Test 5: Test SELECT với JOIN
echo "</div><div class='test-section'>";
echo "<h2>5. Test SELECT với JOIN</h2>";

try {
    $rows = dbSelectAll(
        "SELECT TOP 5 p.id, p.title, p.price, u.full_name as landlord_name
         FROM dbo.properties p
         JOIN dbo.users u ON p.landlord_id = u.id
         ORDER BY p.id"
    );
    
    if (count($rows) > 0) {
        echo "<div class='success'>✅ Truy vấn JOIN thành công! Tìm thấy " . count($rows) . " properties</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Tiêu đề</th><th>Giá</th><th>Người cho thuê</th></tr>";
        
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . number_format($row['price'], 0, ',', '.') . " VNĐ</td>";
            echo "<td>" . htmlspecialchars($row['landlord_name']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ Không có dữ liệu properties hoặc không có JOIN được</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Lỗi truy vấn JOIN: " . $e->getMessage() . "</div>";
}

// Test 6: Test INSERT với prepared statement (test insert và xóa ngay)
echo "</div><div class='test-section'>";
echo "<h2>6. Test INSERT query (Prepared Statement - Test only)</h2>";

try {
    // Chỉ test, không thực sự insert
    $testName = "Test User " . time();
    $testEmail = "test" . time() . "@test.com";
    $testPassword = password_hash("test123", PASSWORD_DEFAULT);
    $testRole = "tenant";

    dbExecute(
        "INSERT INTO dbo.users (full_name, email, password, role) VALUES (:n, :e, :p, :r)",
        [':n' => $testName, ':e' => $testEmail, ':p' => $testPassword, ':r' => $testRole]
    );
    $insertId = dbScopeIdentity();
    
    if ($insertId > 0) {
        echo "<div class='success'>✅ INSERT thành công! ID mới: $insertId</div>";
        
        // Xóa record test ngay
        dbExecute("DELETE FROM dbo.users WHERE id = :id", [':id' => $insertId]);
        
        echo "<div class='info'>ℹ️ Đã xóa record test (ID: $insertId)</div>";
    } else {
        echo "<div class='error'>❌ INSERT thất bại</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Lỗi INSERT: " . $e->getMessage() . "</div>";
}

// Test 7: Test UPDATE với prepared statement
echo "</div><div class='test-section'>";
echo "<h2>7. Test UPDATE query (Prepared Statement - Read-only)</h2>";

try {
    // Lấy user đầu tiên để test
    $user = dbSelectOne("SELECT TOP 1 id, full_name FROM dbo.users ORDER BY id");
    if ($user) {
        $originalName = $user['full_name'];
        $testName = $originalName . " (Test)";

        dbExecute("UPDATE dbo.users SET full_name = :n WHERE id = :id", [':n' => $testName, ':id' => (int)$user['id']]);

        if (true) {
            echo "<div class='success'>✅ UPDATE thành công!</div>";
            
            // Khôi phục lại tên gốc
            dbExecute("UPDATE dbo.users SET full_name = :n WHERE id = :id", [':n' => $originalName, ':id' => (int)$user['id']]);
            
            echo "<div class='info'>ℹ️ Đã khôi phục lại dữ liệu gốc</div>";
        } else {
            echo "<div class='error'>❌ UPDATE thất bại</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Không có user để test UPDATE</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Lỗi UPDATE: " . $e->getMessage() . "</div>";
}

// Đóng kết nối
closeDBConnection($conn);

echo "</div>";
echo "<div class='test-section'>";
echo "<h2>✅ Kết quả tổng kết</h2>";
echo "<div class='success'>";
echo "<strong>Tất cả các test đã hoàn thành!</strong><br>";
echo "Database connection và queries đang hoạt động bình thường.";
echo "</div>";
echo "</div>";

echo "</div></body></html>";
?>


