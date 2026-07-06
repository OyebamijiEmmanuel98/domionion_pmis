<?php
require_once __DIR__ . '/../config/db.php';
$hash = password_hash('Password123!', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ?");
$stmt->execute([$hash]);
echo "Updated all user passwords to 'Password123!' successfully.\n";
echo "Rows affected: " . $stmt->rowCount() . "\n";
