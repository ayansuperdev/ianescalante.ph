<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverId = $_POST['receiver_id'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate inputs
    if (empty($subject) || empty($message)) {
        $_SESSION['error'] = 'Subject and message are required';
        header("Location: user_dashboard.php?page=messages&action=compose");
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiverId, $subject, $message]);
        
        $_SESSION['message'] = 'Message sent successfully';
        header("Location: user_dashboard.php?page=messages");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to send message';
        header("Location: user_dashboard.php?page=messages&action=compose");
        exit;
    }
}

header("Location: user_dashboard.php");
exit;