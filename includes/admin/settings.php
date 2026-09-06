<div class="admin-section">
    <h2><i class="fas fa-cogs"></i> System Settings</h2>
    
    <form method="POST" class="settings-form">
        <div class="form-group">
            <label for="site_name">Site Name</label>
            <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars(getSetting('site_name')) ?>">
        </div>
        
        <div class="form-group">
            <label for="users_can_register">User Registration</label>
            <select id="users_can_register" name="users_can_register">
                <option value="1" <?= getSetting('users_can_register') ? 'selected' : '' ?>>Enabled</option>
                <option value="0" <?= !getSetting('users_can_register') ? 'selected' : '' ?>>Disabled</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="default_role">Default User Role</label>
            <select id="default_role" name="default_role">
                <option value="user" <?= getSetting('default_role') === 'user' ? 'selected' : '' ?>>Regular User</option>
                <option value="admin" <?= getSetting('default_role') === 'admin' ? 'selected' : '' ?>>Administrator</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="items_per_page">Items Per Page</label>
            <input type="number" id="items_per_page" name="items_per_page" 
                   min="5" max="100" value="<?= htmlspecialchars(getSetting('items_per_page', 10)) ?>">
        </div>
        
        <button type="submit" name="save_settings" class="btn-submit">Save Settings</button>
    </form>
</div>