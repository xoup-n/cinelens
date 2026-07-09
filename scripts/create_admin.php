<?php

declare(strict_types=1);

/**
 * Usage: php scripts/create_admin.php <username> <email> <password>
 */

require_once __DIR__ . '/../src/Database.php';

if ($argc !== 4) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php <username> <email> <password>\n");
    exit(1);
}

[$script, $username, $email, $password] = $argv;

if (strlen($password) < 6) {
    fwrite(STDERR, "Password must be at least 6 characters.\n");
    exit(1);
}

$db = Database::connection();

$check = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$check->execute([$username, $email]);
if ($check->fetch() !== false) {
    fwrite(STDERR, "A user with that username or email already exists.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$insert = $db->prepare(
    'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, "admin")'
);
$insert->execute([$username, $email, $hash]);

echo "Admin user '{$username}' created (id " . $db->lastInsertId() . ").\n";
