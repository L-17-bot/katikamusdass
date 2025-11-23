<?php
// admin_setup.php
// Run this script once from the command line to create/update the admin user.
// Usage (from project root): php admin_setup.php

require_once __DIR__ . '/config.php';

$username = 'admin';
$password = 'admin';

try {
    // Generate a secure password hash
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Upsert admin row (insert if not exists, else update password)
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch();

    if ($row) {
        $stmt = $pdo->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
        $stmt->execute([':hash' => $hash, ':id' => $row['id']]);
        echo "Updated existing admin '$username' with new password hash.\n";
    } else {
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:username, :hash)');
        $stmt->execute([':username' => $username, ':hash' => $hash]);
        echo "Created admin '$username'.\n";
    }

    echo "Admin setup complete. You can now log in at /admin/login.php with username '$username'.\n";
    echo "(Password used for setup is the one in this script -- change it after first login.)\n";
} catch (Exception $e) {
    echo "Error during admin setup: " . $e->getMessage() . "\n";
    exit(1);
}
