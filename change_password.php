<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

redirectIfNotLoggedIn();

// Get user email early in the process
try {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userEmail = $stmt->fetchColumn();
    
    if (!$userEmail) {
        throw new Exception("Could not retrieve your email address");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: " . (isAdmin() ? "admin_dashboard.php" : "user_dashboard.php"));
    exit;
}

// Set default return URL
$returnUrl = isAdmin() ? 'admin_dashboard.php?page=profile' : 'user_dashboard.php?page=profile';

// Handle return_to parameter safely
$allowedReturns = ['profile', 'settings'];
if (isset($_GET['return_to']) && in_array($_GET['return_to'], $allowedReturns)) {
    $returnUrl = (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php') . '?page=' . $_GET['return_to'];
}

// Handle OTP cancellation
if (isset($_GET['cancel'])) {
    unset($_SESSION['otp_requested']);
    $_SESSION['message'] = "OTP request cancelled";
    header("Location: change_password.php");
    exit;
}

// Handle OTP generation and sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    try {
        // Generate random 6-digit OTP
        $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP in database using database time functions
        $stmt = $pdo->prepare("INSERT INTO password_reset_otps (user_id, otp, expires_at) 
                              VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
        $stmt->execute([$_SESSION['user_id'], $otp]);
        
        // Debug: Verify OTP was inserted
        $lastId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM password_reset_otps WHERE id = ?");
        $stmt->execute([$lastId]);
        $debugRecord = $stmt->fetch();
        
        error_log("OTP DEBUG: Inserted OTP for user {$_SESSION['user_id']} with ID $lastId: " . 
                 print_r($debugRecord, true));
        
        // Prepare email content
        $emailSubject = "Your Password Change Verification Code";
        $emailMessage = sprintf(
            "<html>
            <head>
                <title>Password Change OTP</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .otp-code { 
                        font-size: 24px; 
                        font-weight: bold; 
                        letter-spacing: 2px;
                        color: #2c3e50;
                    }
                </style>
            </head>
            <body>
                <h2>Password Change Request</h2>
                <p>Your verification code is: <span class='otp-code'>%s</span></p>
                <p>This code will expire in 15 minutes.</p>
                <p>If you didn't request this change, please contact support immediately.</p>
            </body>
            </html>",
            $otp
        );
        
        // Prepare email headers
        $emailHeaders = [
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=UTF-8',
            'From' => 'noreply@yourdomain.com',
            'Reply-To' => 'support@yourdomain.com',
            'X-Mailer' => 'PHP/' . phpversion()
        ];
        
        // Format headers as string
        $formattedHeaders = '';
        foreach ($emailHeaders as $key => $value) {
            $formattedHeaders .= "$key: $value\r\n";
        }
        
        // Handle email sending based on environment
        $isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);
        
        if ($isLocal) {
            // Development mode - log instead of sending
            error_log("OTP for $userEmail: $otp");
            $_SESSION['debug_otp'] = $otp;
            $mailSent = true;
        } else {
            // Production mode - actually send the email
            $mailSent = mail(
                $userEmail,
                $emailSubject,
                $emailMessage,
                $formattedHeaders
            );
        }
        
        if (!$mailSent) {
            throw new Exception("Failed to send OTP email. Please try again later.");
        }
        
        $_SESSION['otp_requested'] = true;
        $_SESSION['message'] = "We've sent a 6-digit OTP to your email address";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: change_password.php");
    exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $otp = trim($_POST['otp']);
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validate
        if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
            throw new Exception("Please enter a valid 6-digit OTP");
        }
        if ($newPassword !== $confirmPassword) {
            throw new Exception("Passwords do not match");
        }
        if (strlen($newPassword) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }
        
        // Get database time for debugging
        $stmt = $pdo->query("SELECT NOW() AS db_time");
        $dbTime = $stmt->fetchColumn();
        error_log("Database time during verification: $dbTime");
        
        // Verify OTP using database time
        $stmt = $pdo->prepare("SELECT * FROM password_reset_otps 
                              WHERE user_id = ? 
                              AND otp = ? 
                              AND used = 0 
                              AND expires_at > NOW()");
        $stmt->execute([$_SESSION['user_id'], $otp]);
        $otpRecord = $stmt->fetch();
        
        if (!$otpRecord) {
            // Log detailed error for debugging
            error_log("OTP verification failed for user: {$_SESSION['user_id']}, OTP: $otp");
            
            // Check if any matching record exists (for debugging)
            $stmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS time_left 
                                  FROM password_reset_otps 
                                  WHERE user_id = ? 
                                  AND otp = ?");
            $stmt->execute([$_SESSION['user_id'], $otp]);
            $anyRecord = $stmt->fetch();
            
            if ($anyRecord) {
                $timeLeft = $anyRecord['time_left'];
                $isExpired = $timeLeft <= 0 ? 'EXPIRED' : 'VALID';
                error_log("OTP found but might be expired or used. Status: " . 
                         "Used: {$anyRecord['used']}, " .
                         "Expires: {$anyRecord['expires_at']}, " .
                         "Time left: $timeLeft seconds, " .
                         "Status: $isExpired");
            } else {
                error_log("No OTP record found for user: {$_SESSION['user_id']}, OTP: $otp");
            }
            
            throw new Exception("Invalid or expired OTP");
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update password
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
        
        // Mark OTP as used
        $stmt = $pdo->prepare("UPDATE password_reset_otps SET used = 1 WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        
        $pdo->commit();
        
        $_SESSION['message'] = "Password changed successfully";
        // Clear OTP session flag
        unset($_SESSION['otp_requested']);
        
        // Redirect to appropriate dashboard
        header("Location: $returnUrl");
        exit;
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: change_password.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="password-change-container">
        <div class="password-change-card">
            <h1><i class="fas fa-key"></i> Change Password</h1>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            
            <?php if (!isset($_SESSION['otp_requested'])): ?>
                <form method="POST" class="password-change-form">
                    <p>For security reasons, we need to verify your identity before changing your password.</p>
                    <div class="form-group">
                        <label for="email">Your Registered Email</label>
                        <input type="email" id="email" name="email" 
                            value="<?= htmlspecialchars($userEmail) ?>" 
                            required readonly
                            class="readonly-email">
                    </div>
                    <button type="submit" name="request_otp" class="btn-request-otp">
                        <i class="fas fa-paper-plane"></i> Send OTP via Email
                    </button>
                </form>
                
                <?php if (isset($_SESSION['debug_otp']) && ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1')): ?>
                    <div class="alert info">
                        <strong>DEVELOPMENT MODE:</strong> Your OTP is <code><?= $_SESSION['debug_otp'] ?></code>
                        <p>In production, this would be sent to <?= htmlspecialchars($userEmail) ?></p>
                    </div>
                    <?php unset($_SESSION['debug_otp']); ?>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST" class="password-change-form">
                    <div class="form-group">
                        <label for="otp">Enter 6-digit OTP</label>
                        <input type="text" id="otp" name="otp" required maxlength="6" pattern="\d{6}" 
                               placeholder="123456" autocomplete="off">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-submit">
                        <i class="fas fa-save"></i> Change Password
                    </button>
                    
                    <div class="form-footer">
                        <a href="change_password.php?cancel=1" class="cancel-link">
                            <i class="fas fa-times"></i> Cancel and start over
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>