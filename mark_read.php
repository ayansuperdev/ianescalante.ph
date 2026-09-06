<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

redirectIfNotLoggedIn();

if (isset($_GET['id'])) {
    $messageId = $_GET['id'];
    
    // Verify the message belongs to the current user (for user dashboard)
    if (!isAdmin()) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$messageId, $_SESSION['user_id']]);
    } else {
        // Admin can mark any message as read
        $stmt = $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE id = ?");
        $stmt->execute([$messageId]);
    }
    
    $_SESSION['message'] = 'Message marked as read';
}

header("Location: " . (isAdmin() ? "admin_dashboard.php?page=messages" : "user_dashboard.php?page=messages"));
exit;