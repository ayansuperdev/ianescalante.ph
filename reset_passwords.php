<?php
require_once 'includes/config.php';

// This is a temporary script to reset passwords - DELETE AFTER USE!
$passwords = [
    'admin' => 'Admin123!',
    'user1' => 'User123!'
];

foreach ($passwords as $username => $password) {
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hashed, $username]);
    echo "Updated password for $username to $password<br>";
}

echo "All passwords reset successfully. DELETE THIS FILE NOW!";
?>