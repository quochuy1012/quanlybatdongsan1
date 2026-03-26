<?php
/**
 * SQL Server connection (PDO sqlsrv)
 *
 * Yêu cầu PHP có bật driver:
 * - pdo_sqlsrv (khuyên dùng)
 * - và/hoặc sqlsrv
 */

// Cấu hình kết nối SQL Server
define('DB_SERVER', 'quochuy'); // ví dụ: localhost, .\\SQLEXPRESS, SERVERNAME\\INSTANCE
define('DB_NAME', 'quanlybatdongsan1');

// Windows Authentication (Trusted Connection)
define('DB_TRUSTED_CONNECTION', true);

// Nếu dùng SQL Login thì set false và điền user/pass
define('DB_USER', '');
define('DB_PASS', '');

// Tùy môi trường dev: bật trust server certificate nếu Encrypt=Mandatory
define('DB_TRUST_SERVER_CERTIFICATE', true);

/**
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!extension_loaded('pdo_sqlsrv')) {
        die("Thiếu PHP extension pdo_sqlsrv. Hãy bật pdo_sqlsrv trong php.ini của PHP đang chạy web server.");
    }

    $dsnParts = [
        "Server=" . DB_SERVER,
        "Database=" . DB_NAME,
        "Encrypt=" . (DB_TRUST_SERVER_CERTIFICATE ? "yes" : "no"),
        "TrustServerCertificate=" . (DB_TRUST_SERVER_CERTIFICATE ? "yes" : "no"),
    ];

    // Note: SQLSRV PDO supports Authentication via username/password.
    // Trusted Connection thường hoạt động khi chạy PHP trên Windows + driver hỗ trợ.
    $dsn = "sqlsrv:" . implode(";", $dsnParts);

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        if (DB_TRUSTED_CONNECTION) {
            // Với pdo_sqlsrv, truyền user/pass rỗng thường dùng Windows Auth theo context service user.
            $pdo = new PDO($dsn, null, null, $options);
        } else {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return $pdo;
    } catch (Throwable $e) {
        die("Lỗi kết nối SQL Server: " . $e->getMessage());
    }
}

function closeDBConnection($conn) {
    // No-op for shared PDO connection
}

function dbSelectAll(string $sql, array $params = []): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbSelectOne(string $sql, array $params = []): ?array {
    $rows = dbSelectAll($sql, $params);
    return $rows[0] ?? null;
}

function dbScalar(string $sql, array $params = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();
    return $value;
}

function dbExecute(string $sql, array $params = []): int {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function dbLastInsertId(): string {
    // SQL Server identity retrieval via PDO::lastInsertId should work for single identity inserts
    return getDBConnection()->lastInsertId();
}

function dbScopeIdentity(): int {
    $val = dbScalar("SELECT CAST(SCOPE_IDENTITY() AS int)");
    return (int)$val;
}

/**
 * Tạo bảng chat lịch hẹn nếu chưa có (SQL Server).
 */
function ensureAppointmentMessagesTable(): void {
    static $checked = false;
    if ($checked) {
        return;
    }

    $sql = "
IF OBJECT_ID('dbo.appointment_messages', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.appointment_messages (
        id INT IDENTITY(1,1) PRIMARY KEY,
        appointment_id INT NOT NULL,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message NVARCHAR(MAX) NOT NULL,
        is_read BIT NOT NULL CONSTRAINT DF_appointment_messages_is_read DEFAULT 0,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_appointment_messages_created_at DEFAULT SYSUTCDATETIME()
    );

    CREATE INDEX IX_appointment_messages_appointment_created
    ON dbo.appointment_messages (appointment_id, created_at);

    CREATE INDEX IX_appointment_messages_receiver_read
    ON dbo.appointment_messages (receiver_id, is_read, created_at);
END
";
    dbExecute($sql);
    $checked = true;
}
?>

