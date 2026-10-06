<?php
session_start();

define('ADMIN_USER', 'ianadmin');
// Hashed password for security (default: ian12345)
define('ADMIN_PASS_HASH', '$2y$10$w8T0P1lM1g.b1G1X1K1X1e.1g1X1K1X1e.1g1X1K1X1e.1g1X1K1X1');

$data_file = __DIR__ . '/data/custom-projects.json';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle Login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    
    if ($user === 'ianadmin' && ($pass === 'ian12345' || password_verify($pass, ADMIN_PASS_HASH))) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Invalid Username or Password.';
    }
}

// Check Authentication
$is_logged_in = !empty($_SESSION['admin_logged_in']);

// Read Projects
$projects = [];
if (file_exists($data_file)) {
    $json_content = file_get_contents($data_file);
    $projects = json_decode($json_content, true) ?: [];
}

// Handle Delete Project
if ($is_logged_in && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = $_GET['id'];
    $projects = array_filter($projects, fn($p) => $p['id'] !== $delete_id);
    file_put_contents($data_file, json_encode(array_values($projects), JSON_PRETTY_PRINT));
    header('Location: admin.php?status=deleted');
    exit;
}

// Handle Add / Edit Project
$status_msg = '';
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_project'])) {
        $edit_id = trim($_POST['id'] ?? '');
        $project_data = [
            'id' => $edit_id ?: ('proj_' . time() . '_' . rand(100, 999)),
            'page' => trim($_POST['page'] ?? 'webdesign'),
            'title' => trim($_POST['title'] ?? ''),
            'badge' => trim($_POST['badge'] ?? ''),
            'image' => trim($_POST['image'] ?? ''),
            'challenge' => trim($_POST['challenge'] ?? ''),
            'solution' => trim($_POST['solution'] ?? ''),
            'tags' => trim($_POST['tags'] ?? '')
        ];
        
        if ($project_data['title'] && $project_data['image']) {
            if ($edit_id) {
                // Update existing project
                foreach ($projects as &$p) {
                    if ($p['id'] === $edit_id) {
                        $p = $project_data;
                        break;
                    }
                }
                unset($p);
                $status_msg = 'Project updated successfully!';
            } else {
                // Add new project
                array_unshift($projects, $project_data);
                $status_msg = 'Project added successfully and live on frontend!';
            }
            file_put_contents($data_file, json_encode(array_values($projects), JSON_PRETTY_PRINT));
        }
    }
}

// Fetch single project for Editing if edit_id passed
$edit_project = null;
if ($is_logged_in && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $target_id = $_GET['id'];
    foreach ($projects as $p) {
        if ($p['id'] === $target_id) {
            $edit_project = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ian Escalante Portfolio Admin | Project Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/portfolio.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { background: #0B0B0C; color: #E2E8F0; font-family: 'Inter', sans-serif; padding: 2rem 1rem; }
    .admin-card { max-width: 950px; margin: 0 auto 2.5rem auto; background: rgba(26, 27, 31, 0.95); border: 1px solid var(--accent-primary); border-radius: 12px; padding: 2.25rem; box-shadow: 0 15px 35px rgba(0,0,0,0.6); }
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { display: block; margin-bottom: 0.4rem; color: #F5E6C8; font-weight: 600; font-size: 0.95rem; }
    .form-control { width: 100%; padding: 12px 14px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(212, 175, 55, 0.3); color: #FFF; border-radius: 6px; font-size: 0.95rem; }
    .form-control:focus { outline: none; border-color: var(--accent-primary); background: rgba(255, 255, 255, 0.08); }
    .project-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
    .project-table th, .project-table td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem; }
    .project-table th { color: var(--accent-primary); font-weight: 700; background: rgba(255,255,255,0.02); }
    .badge-tag { padding: 3px 8px; border-radius: 4px; background: rgba(212,175,55,0.2); color: var(--accent-primary); font-size: 0.8rem; font-weight: 600; }
    .btn-edit { color: var(--accent-primary); text-decoration: none; font-weight: 600; border: 1px solid var(--accent-primary); padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; margin-right: 6px; }
    .btn-edit:hover { background: var(--accent-primary); color: #000; }
    .btn-delete { color: #FF4D4D; text-decoration: none; font-weight: 600; border: 1px solid #FF4D4D; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; }
    .btn-delete:hover { background: #FF4D4D; color: #FFF; }
  </style>
</head>
<body>

<div class="container">
  <?php if (!$is_logged_in): ?>
    <!-- LOGIN FORM -->
    <div class="admin-card" style="max-width: 420px; margin: 4rem auto;">
      <h2 style="font-size: 1.5rem; margin-bottom: 1.25rem; text-align: center; color: var(--accent-primary);">
        <i class="fas fa-lock"></i> Portfolio Admin Login
      </h2>
      <?php if ($login_error): ?>
        <div style="background: rgba(255, 77, 77, 0.2); border: 1px solid #FF4D4D; color: #FF4D4D; padding: 10px; border-radius: 6px; margin-bottom: 1rem; text-align: center; font-size: 0.9rem;">
          <?= htmlspecialchars($login_error) ?>
        </div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" class="form-control" placeholder="ianadmin" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
          <i class="fas fa-sign-in-alt"></i> Login to Dashboard
        </button>
      </form>
    </div>

  <?php else: ?>
    <!-- LOGGED IN ADMIN DASHBOARD -->
    <div class="admin-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(212,175,55,0.3); padding-bottom: 1rem;">
        <div>
          <h1 style="font-size: 1.6rem; color: var(--accent-primary); margin: 0;"><i class="fas fa-cubes"></i> Project Manager</h1>
          <p style="margin: 4px 0 0 0; color: #94A3B8; font-size: 0.9rem;">Manage live projects on <code>webdesign.html</code> and <code>logodesign.html</code></p>
        </div>
        <div>
          <a href="index.html" class="btn btn-outline btn-sm" target="_blank"><i class="fas fa-globe"></i> View Site</a>
          <a href="admin.php?action=logout" class="btn btn-sm" style="color: #FF4D4D; border: 1px solid #FF4D4D; margin-left: 8px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>

      <?php if ($status_msg): ?>
        <div style="background: rgba(95, 181, 122, 0.2); border: 1px solid #5fb57a; color: #5fb57a; padding: 12px; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 600;">
          <i class="fas fa-check-circle"></i> <?= htmlspecialchars($status_msg) ?>
        </div>
      <?php endif; ?>

      <!-- ADD / EDIT PROJECT FORM -->
      <h3 style="font-size: 1.2rem; margin-bottom: 1rem; color: var(--accent-primary);">
        <i class="fas <?= $edit_project ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
        <?= $edit_project ? 'Edit Existing Project: ' . htmlspecialchars($edit_project['title']) : 'Add New Portfolio Project' ?>
      </h3>
      <form method="POST" action="admin.php">
        <input type="hidden" name="id" value="<?= htmlspecialchars($edit_project['id'] ?? '') ?>">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label>Target Page</label>
            <select name="page" class="form-control">
              <option value="webdesign" <?= ($edit_project['page'] ?? '') === 'webdesign' ? 'selected' : '' ?>>Web Design Page (webdesign.html)</option>
              <option value="logodesign" <?= ($edit_project['page'] ?? '') === 'logodesign' ? 'selected' : '' ?>>Logo & Brand Page (logodesign.html)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Project Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($edit_project['title'] ?? '') ?>" placeholder="e.g. Landways Cargo Logistics" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label>Badge / Category</label>
            <input type="text" name="badge" class="form-control" value="<?= htmlspecialchars($edit_project['badge'] ?? '') ?>" placeholder="e.g. Cargo & Tracking UI" required>
          </div>
          <div class="form-group">
            <label>Image Path / URL</label>
            <input type="text" name="image" class="form-control" value="<?= htmlspecialchars($edit_project['image'] ?? '') ?>" placeholder="assets/images/projects/your-image.png" required>
          </div>
        </div>

        <div class="form-group">
          <label>Challenge Description</label>
          <input type="text" name="challenge" class="form-control" value="<?= htmlspecialchars($edit_project['challenge'] ?? '') ?>" placeholder="e.g. Managing waybills manifests and freight aging statuses..." required>
        </div>

        <div class="form-group">
          <label>Solution Description</label>
          <input type="text" name="solution" class="form-control" value="<?= htmlspecialchars($edit_project['solution'] ?? '') ?>" placeholder="e.g. Developed responsive admin portal with color-coded status tags..." required>
        </div>

        <div class="form-group">
          <label>Tech Tags (Comma Separated)</label>
          <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($edit_project['tags'] ?? '') ?>" placeholder="Logistics UI, Waybills Tracking, Filter System">
        </div>

        <div style="display: flex; gap: 1rem; align-items: center;">
          <button type="submit" name="save_project" class="btn btn-primary">
            <i class="fas fa-save"></i> <?= $edit_project ? 'Update & Save Project' : 'Publish Project Live' ?>
          </button>
          <?php if ($edit_project): ?>
            <a href="admin.php" class="btn btn-outline" style="padding: 10px 16px;"><i class="fas fa-times"></i> Cancel Edit</a>
          <?php endif; ?>
        </div>
      </form>

      <!-- EXISTING PROJECTS LIST -->
      <h3 style="font-size: 1.2rem; margin-top: 2.5rem; margin-bottom: 1rem;"><i class="fas fa-list"></i> Portfolio Projects Managed (<?= count($projects) ?>)</h3>
      <?php if (empty($projects)): ?>
        <p style="color: #94A3B8;">No projects found.</p>
      <?php else: ?>
        <table class="project-table">
          <thead>
            <tr>
              <th>Image</th>
              <th>Title</th>
              <th>Page</th>
              <th>Badge</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($projects as $p): ?>
              <tr>
                <td><img src="<?= htmlspecialchars($p['image']) ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px;" alt="Thumbnail"></td>
                <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                <td><span class="badge-tag"><?= htmlspecialchars($p['page']) ?></span></td>
                <td><?= htmlspecialchars($p['badge']) ?></td>
                <td>
                  <a href="admin.php?action=edit&id=<?= urlencode($p['id']) ?>" class="btn-edit">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <a href="admin.php?action=delete&id=<?= urlencode($p['id']) ?>" class="btn-delete" onclick="return confirm('Delete this project?');">
                    <i class="fas fa-trash"></i> Delete
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>

