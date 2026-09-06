<?php if (isset($_SESSION['error'])): ?>
    <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="user-section">
    <h2><i class="fas fa-user-edit"></i> Your Profile</h2>
    
    <?php
    // Get current user email
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userEmail = $stmt->fetchColumn();
    ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

        <?php
    // Debug output - remove after testing
    echo "<!-- Debug Info -->";
    echo "<!-- Avatar Path: " . getAvatarUrl($_SESSION['user_id']) . " -->";
    if (!empty($profile['avatar'])) {
        echo "<!-- Database Avatar: " . $profile['avatar'] . " -->";
        echo "<!-- File Exists: " . 
             (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/' . $profile['avatar']) ? 'Yes' : 'No') . " -->";
    }
    ?>

    <form method="POST" class="profile-form" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group avatar-upload">
                <div class="avatar-preview">
                    <img src="<?= getAvatarUrl($_SESSION['user_id']) ?>" 
                         alt="Profile Picture" 
                         id="avatarPreview"
                         onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
                </div>
                <input type="file" id="avatar" name="avatar" accept="image/*" class="avatar-input">
                <label for="avatar" class="btn-upload">Change Avatar</label>
                <?php if (!empty($profile['avatar'])): ?>
                    <input type="hidden" name="existing_avatar" value="<?= $profile['avatar'] ?>">
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" required>
                
                <label for="email" style="margin-top: 15px;">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($userEmail) ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="update_profile" class="btn-submit">Save Profile</button>
<a href="<?= BASE_URL ?>/change_password.php?return_to=profile" class="btn-change-password">
    <i class="fas fa-key"></i> Change Password
</a>
        </div>
    </form>
</div>

<script>
// Preview image before upload <a href="?page=change_password" class="btn-change-password">Change Password</a>
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});
</script>