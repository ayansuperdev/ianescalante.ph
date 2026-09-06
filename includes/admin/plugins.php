<?php
// includes/admin/plugins.php

// Add this at the top
require_once __DIR__ . '/../helpers.php';

// Handle plugin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle bulk actions
    if (isset($_POST['bulk_action']) && !empty($_POST['plugin_ids'])) {
        $bulk_action = $_POST['bulk_action'];
        $plugin_ids = $_POST['plugin_ids'];
        
        // FIX: Add bulk activation/deactivation
        if ($bulk_action === 'activate') {
            $placeholders = rtrim(str_repeat('?,', count($plugin_ids)), ',');
            $stmt = $pdo->prepare("UPDATE plugins SET active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($plugin_ids);
            $_SESSION['message'] = count($plugin_ids) . " plugins activated successfully";
                header("Location: admin_dashboard.php?page=plugins");
                exit;
        } 
        elseif ($bulk_action === 'deactivate') {
            $placeholders = rtrim(str_repeat('?,', count($plugin_ids)), ',');
            $stmt = $pdo->prepare("UPDATE plugins SET active = 0 WHERE id IN ($placeholders)");
            $stmt->execute($plugin_ids);
            $_SESSION['message'] = count($plugin_ids) . " plugins deactivated successfully";
        }
        elseif ($bulk_action === 'delete') {
            foreach ($plugin_ids as $plugin_id) {
                $stmt = $pdo->prepare("SELECT slug FROM plugins WHERE id = ?");
                $stmt->execute([$plugin_id]);
                $plugin = $stmt->fetch();
                
                if ($plugin) {
                    $pluginDir = __DIR__ . '/../../plugins/' . $plugin['slug'];
                    if (file_exists($pluginDir)) {
                        deleteDirectory($pluginDir);
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM plugins WHERE id = ?");
                    $stmt->execute([$plugin_id]);
                }
            }
            $_SESSION['message'] = count($plugin_ids) . " plugins deleted successfully";
        }
        
        header("Location: admin_dashboard.php?page=plugins");
        exit;
    }
    
    // Handle individual plugin delete
    if (isset($_POST['delete_plugin'])) {
        $pluginId = $_POST['plugin_id'];
        $pluginSlug = $_POST['plugin_slug'];
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM plugins WHERE id = ?");
        $stmt->execute([$pluginId]);
        
        // Delete plugin files
        $pluginDir = __DIR__ . '/../../plugins/' . $pluginSlug;
        if (file_exists($pluginDir)) {
            deleteDirectory($pluginDir);
        }
        
        $_SESSION['message'] = "Plugin deleted successfully";
        header("Location: admin_dashboard.php?page=plugins");
        exit;
    }
}

// Handle GET activation/deactivation
if (isset($_GET['action']) && isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    $action = $_GET['action'];
    
    if ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE plugins SET active = 1 WHERE slug = ?");
        $stmt->execute([$slug]);
        $_SESSION['message'] = "Plugin activated successfully";
    } 
    elseif ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE plugins SET active = 0 WHERE slug = ?");
        $stmt->execute([$slug]);
        $_SESSION['message'] = "Plugin deactivated successfully";
    }
    
    header("Location: admin_dashboard.php?page=plugins");
    exit;
}

//function deleteDirectory($dir) {
//    if (!file_exists($dir)) return;
    
//    $files = new RecursiveIteratorIterator(
//        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
//        RecursiveIteratorIterator::CHILD_FIRST
//    );
    
//    foreach ($files as $fileinfo) {
//        if ($fileinfo->isDir() && !$fileinfo->isLink()) {
//            rmdir($fileinfo->getRealPath());
//        } else {
//            unlink($fileinfo->getRealPath());
//        }
//    }
    
//    rmdir($dir);
//}

// Get all plugins
$stmt = $pdo->query("SELECT * FROM plugins ORDER BY name ASC");
$plugins = $stmt->fetchAll();

// Add this at the top after $plugins = $stmt->fetchAll();
global $menu, $submenu;

// Register the plugins page in WordPress-style menu
if (!isset($menu['plugins.php'])) {
    add_menu_page(
        'Plugins', 
        'Plugins', 
        'manage_options', 
        'plugins.php', 
        function() use ($plugins) {
            // ... rest of your plugins.php content ...
        },
        'dashicons-admin-plugins',
        65
    );
}

// Calculate counts for tabs
$allCount = count($plugins);
$activeCount = count(array_filter($plugins, function($plugin) { return $plugin['active']; }));
$inactiveCount = $allCount - $activeCount;
$updateAvailableCount = 0; // You'll need to implement update checking logic
?>

<div class="admin-section">
    <h2><i class="fas fa-plug"></i> Plugin Management</h2>
    
    <!-- Tab Navigation -->
    <div class="plugin-tabs">
        <ul>
            <li class="active"><a href="#">All <span class="count">(<?= $allCount ?>)</span></a></li>
            <li><a href="#">Active <span class="count">(<?= $activeCount ?>)</span></a></li>
            <li><a href="#">Inactive <span class="count">(<?= $inactiveCount ?>)</span></a></li>
            <li><a href="#">Update Available <span class="count">(<?= $updateAvailableCount ?>)</span></a></li>
        </ul>
        
        <div class="plugin-search">
            <input type="search" placeholder="Search installed plugins..." value="">
        </div>
    </div>
    
    <!-- Bulk Actions -->
    <form method="POST" id="bulk-action-form" class="plugin-actions">
        <div class="bulk-actions-container">
            <select name="bulk_action" id="bulk-action-select">
                <option value="">Bulk Actions</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
                <option value="delete">Delete</option>
            </select>
            <button type="submit" class="btn-apply">Apply</button>
        </div>
    </form>
    
    <!-- Plugin Table -->
    <div class="plugin-table-container">
        <table class="plugin-table">
            <thead>
                <tr>
                    <th class="check-column"><input type="checkbox" id="cb-select-all"></th>
                    <th>Plugin</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plugins)): ?>
                    <tr>
                        <td colspan="5" class="no-plugins">
                            <i class="fas fa-box-open"></i>
                            <p>No plugins installed. Upload your first plugin to get started!</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($plugins as $plugin): ?>
                    <tr class="<?= $plugin['active'] ? 'active' : 'inactive' ?>">
                        <td class="check-column">
                            <input type="checkbox" name="plugin_ids[]" value="<?= $plugin['id'] ?>" form="bulk-action-form">
                        </td>
                        <td class="plugin-title">
                            <strong><?= htmlspecialchars($plugin['name']) ?></strong>
                            <div class="plugin-meta">
                                Version <?= htmlspecialchars($plugin['version']) ?> | 
                                By <?= htmlspecialchars($plugin['author']) ?>
                            </div>
                        </td>
                        <td class="plugin-description">
                            <?= htmlspecialchars($plugin['description']) ?>
                        </td>
                        <td class="plugin-status">
                            <?= $plugin['active'] ? 
                                '<span class="badge bg-success">Active</span>' : 
                                '<span class="badge bg-secondary">Inactive</span>' ?>
                        </td>
                        <td class="plugin-actions">
                            <?php if ($plugin['active']): ?>
                                <a href="?page=plugins&action=deactivate&slug=<?= $plugin['slug'] ?>" 
                                   class="btn btn-sm btn-warning">Deactivate</a>
                            <?php else: ?>
                                <a href="?page=plugins&action=activate&slug=<?= $plugin['slug'] ?>" 
                                   class="btn btn-sm btn-success">Activate</a>
                            <?php endif; ?>
                            
                            <form method="POST" style="display:inline-block;" 
                                  onsubmit="return confirm('Are you sure you want to delete this plugin?')">
                                <input type="hidden" name="plugin_id" value="<?= $plugin['id'] ?>">
                                <input type="hidden" name="plugin_slug" value="<?= $plugin['slug'] ?>">
                                <button type="submit" name="delete_plugin" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Upload Button -->
    <div class="top-plugin-actions">
        <button class="btn-upload-plugin" data-bs-toggle="modal" data-bs-target="#uploadPluginModal">
            <i class="fas fa-upload"></i> Upload Plugin
        </button>
    </div>
</div>

<!-- Upload Plugin Modal -->
<div class="modal fade" id="uploadPluginModal" tabindex="-1" aria-labelledby="uploadPluginModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadPluginModalLabel"><i class="fas fa-upload"></i> Upload Plugin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadPluginForm" action="includes/admin/upload_plugin.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pluginZip" class="form-label">Plugin ZIP File</label>
                        <input class="form-control" type="file" id="pluginZip" name="plugin_zip" accept=".zip" required>
                        <div class="form-text">Upload a ZIP file containing your plugin files</div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="activate_after_install" id="activateAfterInstall" checked>
                        <label class="form-check-label" for="activateAfterInstall">Activate plugin after installation</label>
                    </div>
                    <div class="upload-progress d-none">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="status-text">Preparing to upload...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload & Install</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Select all checkboxes functionality
document.getElementById('cb-select-all').addEventListener('change', function(e) {
    const checkboxes = document.querySelectorAll('input[name="plugin_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = e.target.checked;
    });
});
</script>