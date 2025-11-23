<?php
// config.php - Database and mail settings
// IMPORTANT: Move this file outside the public webroot if possible.

declare(strict_types=1);

ini_set('display_errors', '0'); // turn off in production
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Database settings - update for your environment
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'school_applications');
define('DB_USER', 'db_user');
define('DB_PASS', 'db_pass');
define('DB_CHARSET', 'utf8mb4');

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If MySQL connection fails, fall back to a local SQLite database so the app can run without MySQL.
    error_log("MySQL connection failed: " . $e->getMessage());
    // Attempt SQLite fallback
    try {
        $dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
        $sqliteFile = $dataDir . DIRECTORY_SEPARATOR . 'database.sqlite';
        $sqliteDsn = 'sqlite:' . $sqliteFile;
        $pdo = new PDO($sqliteDsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create minimal tables if they don't exist (SQLite-compatible)
        $pdo->exec("CREATE TABLE IF NOT EXISTS applicants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fullname TEXT NOT NULL,
            email TEXT NOT NULL,
            gender TEXT,
            age INTEGER,
            student_class TEXT,
            parent_contact TEXT,
            former_school TEXT,
            results TEXT,
            date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
            status TEXT DEFAULT 'Pending'
        );");

        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_applicants_email ON applicants(email);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_applicants_status ON applicants(status);");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL
        );");

        $pdo->exec("CREATE TABLE IF NOT EXISTS attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            applicant_id INTEGER NOT NULL,
            stored_name TEXT NOT NULL,
            original_name TEXT NOT NULL,
            mime TEXT,
            size INTEGER,
            path TEXT NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
        );");

        define('USE_SQLITE_FALLBACK', true);
        error_log('Using SQLite fallback database at ' . $sqliteFile);
    } catch (Exception $ex) {
        error_log('SQLite fallback failed: ' . $ex->getMessage());
        http_response_code(500);
        exit('Database connection error');
    }
}

// Email settings
// Set the sender address used for automated emails (change as needed)
define('MAIL_FROM_NAME', 'Dr Nolan - Admissions');
define('MAIL_FROM_EMAIL', 'dr.nolantheastronaut@gmail.com');

// SMTP settings for PHPMailer or direct SMTP testing
// Note: For Gmail, you must use an App Password or allow SMTP access for the account.
// Plain account password may be blocked by Google; see https://support.google.com/accounts/answer/185833
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'dr.nolantheastronaut@gmail.com');
// Use the app password you generated for Gmail SMTP (keep this secret)
define('SMTP_PASS', 'cplp igum dbxs ebov');
define('SMTP_SECURE', 'tls');
