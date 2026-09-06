<div class="admin-section">
    <h2><i class="fas fa-user-cog"></i> Admin Profile</h2>
    
    <?php
    // Get current admin data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $adminData = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" class="profile-form" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group avatar-upload">
                <div class="avatar-preview">
                    <img src="<?= getAvatarUrl($_SESSION['user_id']) ?>" 
                         alt="Admin Avatar" 
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
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?= htmlspecialchars($adminData['username']) ?>" disabled>
                
                <label for="email" style="margin-top: 15px;">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($adminData['email']) ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="update_admin_profile" class="btn-submit">Save Changes</button>
            
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