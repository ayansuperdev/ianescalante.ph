<!--  defined('BASE_PATH') or exit('No direct script access allowed'); ?> -->


<div class="add-user-container">
    <h2><i class="fas fa-user-plus"></i> Add New User</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <form method="POST" class="add-user-form">
        <div class="form-group">
            <label for="username"><i class="fas fa-user"></i> Username</label>
            <input type="text" id="username" name="username" required 
                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                   placeholder="Enter username">
        </div>
        
        <div class="form-group">
            <label for="email"><i class="fas fa-envelope"></i> Email</label>
            <input type="email" id="email" name="email" required 
                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                   placeholder="Enter email">
        </div>
        
        <div class="form-group">
            <label for="password"><i class="fas fa-lock"></i> Password</label>
            <input type="password" id="password" name="password" required 
                   placeholder="Create password (min 8 characters)">
        </div>
        
        <div class="form-group">
            <label for="role"><i class="fas fa-user-tag"></i> Role</label>
            <select id="role" name="role">
                <option value="admin">Admin</option>
                <option value="user" selected>User</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="add_user" class="btn-submit">
                <i class="fas fa-plus-circle"></i> Create User
            </button>
            <a href="admin_dashboard.php?page=users" class="btn-cancel">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>