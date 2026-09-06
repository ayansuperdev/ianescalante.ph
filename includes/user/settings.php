<div class="user-section">
    <h2><i class="fas fa-cog"></i> Account Settings</h2>
    
    <form method="POST" class="settings-form">
        <div class="form-group">
            <label for="theme">Theme Preference</label>
            <select id="theme" name="theme">
                <option value="light" <?= ($userSettings['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light Mode</option>
                <option value="dark" <?= ($userSettings['theme'] ?? 'light') === 'dark' ? 'selected' : '' ?>>Dark Mode</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="language">Language</label>
            <select id="language" name="language">
                <option value="en" <?= ($userSettings['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                <option value="es" <?= ($userSettings['language'] ?? 'en') === 'es' ? 'selected' : '' ?>>Spanish</option>
                <option value="fr" <?= ($userSettings['language'] ?? 'en') === 'fr' ? 'selected' : '' ?>>French</option>
            </select>
        </div>
        
        <div class="form-group checkbox-group">
            <input type="checkbox" id="notifications" name="notifications" 
                   <?= ($userSettings['notifications'] ?? 1) ? 'checked' : '' ?>>
            <label for="notifications">Enable Email Notifications</label>
        </div>
        
        <div class="form-group checkbox-group">
            <input type="checkbox" id="newsletter" name="newsletter" 
                   <?= ($userSettings['newsletter'] ?? 1) ? 'checked' : '' ?>>
            <label for="newsletter">Subscribe to Newsletter</label>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="update_settings" class="btn-submit">Save Settings</button>
        </div>
    </form>
</div>