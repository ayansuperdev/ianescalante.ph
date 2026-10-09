<?php
/**
 * Blog Routing & Entry Point for Portfolio Site
 * Handles:
 *   /blog               -> Blog Listing Page (Search, Category Filters, Pagination)
 *   /blog?slug=[slug]   -> Individual Article Page (Draft Preview / Public Article)
 *   /blog/[slug]        -> Clean URL alias via rewrite / query
 */

session_start();
$is_admin = !empty($_SESSION['admin_logged_in']);

$articles_file = __DIR__ . '/data/articles.json';
$experiences_file = __DIR__ . '/data/experiences.json';

$articles = [];
if (file_exists($articles_file)) {
    $articles = json_decode(file_get_contents($articles_file), true) ?: [];
}

$experiences = [];
if (file_exists($experiences_file)) {
    $experiences = json_decode(file_get_contents($experiences_file), true) ?: [];
}
$exp_map = [];
foreach ($experiences as $e) {
    $exp_map[$e['id']] = $e;
}

// Extract slug parameter
$requested_slug = trim($_GET['slug'] ?? '');
if (empty($requested_slug) && isset($_SERVER['REQUEST_URI'])) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = array_filter(explode('/', $path));
    $last = end($parts);
    if ($last && $last !== 'blog' && $last !== 'blog.php' && strpos($last, '.php') === false) {
        $requested_slug = urldecode($last);
    }
}

// Route 1: Single Article Page
if (!empty($requested_slug)) {
    $target_article = null;
    foreach ($articles as $art) {
        if ($art['slug'] === $requested_slug) {
            $target_article = $art;
            break;
        }
    }

    // Check if article found
    if (!$target_article) {
        http_response_code(404);
        render_404();
        exit;
    }

    // Draft Preview Protection: Only published articles are public
    if ($target_article['status'] !== 'published' && !$is_admin) {
        http_response_code(403);
        render_draft_restricted();
        exit;
    }

    render_article_page($target_article, $articles, $exp_map, $is_admin);
    exit;
}

// Route 2: Blog Listing Page
render_listing_page($articles, $is_admin);
exit;


/* ==========================================================================
   PAGE RENDER FUNCTIONS
   ========================================================================== */

function render_listing_page($articles, $is_admin) {
    // Filter out drafts for public visitors
    $public_articles = array_filter($articles, function($art) use ($is_admin) {
        return ($art['status'] === 'published');
    });

    // Get unique categories from published articles
    $categories = array_unique(array_map(fn($a) => $a['category'], $public_articles));
    sort($categories);

    // Search and Category filtering params
    $search_query = trim($_GET['search'] ?? '');
    $category_filter = trim($_GET['category'] ?? '');
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 6;

    $filtered_articles = array_values(array_filter($public_articles, function($art) use ($search_query, $category_filter) {
        $matches_search = true;
        if (!empty($search_query)) {
            $q = mb_strtolower($search_query);
            $title_match = str_contains(mb_strtolower($art['title']), $q);
            $client_match = str_contains(mb_strtolower($art['client_name'] ?? ''), $q);
            $excerpt_match = str_contains(mb_strtolower($art['excerpt']), $q);
            $category_match = str_contains(mb_strtolower($art['category']), $q);
            $matches_search = ($title_match || $client_match || $excerpt_match || $category_match);
        }

        $matches_cat = true;
        if (!empty($category_filter)) {
            $matches_cat = ($art['category'] === $category_filter);
        }

        return $matches_search && $matches_cat;
    }));

    $total_items = count($filtered_articles);
    $total_pages = max(1, ceil($total_items / $per_page));
    $page = min($page, $total_pages);
    $offset = ($page - 1) * $per_page;
    $paged_articles = array_slice($filtered_articles, $offset, $per_page);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Engineering Blog & Project Insights | Ian Escalante</title>
  <meta name="description" content="Technical project breakdowns, engineering case studies, WordPress plugin architecture, and API integration guides by Ian Escalante.">
  <link rel="canonical" href="https://ianescalante-ph.vercel.app/blog.php">

  <!-- Open Graph -->
  <meta property="og:title" content="Engineering Blog | Ian Escalante">
  <meta property="og:description" content="Technical project breakdowns and custom WordPress engineering case studies.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://ianescalante-ph.vercel.app/blog.php">
  <meta property="og:image" content="assets/images/profile/Upper body smile.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/portfolio.css">

  <style>
    .blog-hero { padding: 4.5rem 0 2.5rem 0; text-align: center; }
    .blog-search-bar { max-width: 680px; margin: 2rem auto 1.5rem auto; display: flex; gap: 10px; }
    .blog-search-input { flex: 1; padding: 14px 18px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: var(--radius-md); color: #FFF; font-size: 1rem; }
    .blog-search-input:focus { outline: none; border-color: var(--accent-primary); background: rgba(255,255,255,0.08); }
    .category-pills { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-bottom: 3rem; }
    .cat-pill { padding: 8px 16px; border-radius: var(--radius-pill); background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: var(--transition-fast); }
    .cat-pill:hover, .cat-pill.active { background: var(--accent-primary); color: #000; border-color: var(--accent-primary); }
    .blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 2rem; margin-bottom: 3.5rem; }
    .blog-card { display: flex; flex-direction: column; justify-content: space-between; height: 100%; transition: transform var(--transition-fast), border-color var(--transition-fast); }
    .blog-card:hover { transform: translateY(-4px); border-color: var(--accent-primary); }
    .blog-cover-wrapper { height: 200px; overflow: hidden; border-radius: var(--radius-md) var(--radius-md) 0 0; position: relative; }
    .blog-cover-img { width: 100%; height: 100%; object-fit: cover; transition: transform var(--transition-base); }
    .blog-card:hover .blog-cover-img { transform: scale(1.05); }
    .blog-card-content { padding: 1.5rem; flex: 1; display: flex; flex-direction: column; }
    .blog-meta-line { display: flex; justify-content: space-between; font-size: 0.825rem; color: var(--accent-primary); margin-bottom: 0.75rem; font-weight: 600; }
    .blog-title { font-size: 1.3rem; margin-bottom: 0.75rem; color: var(--text-primary); line-height: 1.35; }
    .blog-excerpt { font-size: 0.925rem; color: var(--text-secondary); margin-bottom: 1.25rem; flex: 1; }
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 2rem; }
    .page-link { padding: 8px 14px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: var(--radius-sm); text-decoration: none; font-weight: 600; }
    .page-link.active { background: var(--accent-primary); color: #000; border-color: var(--accent-primary); }
  </style>
</head>
<body>

  <!-- Sticky Header -->
  <header class="site-header">
    <div class="container header-inner">
      <button class="mobile-nav-toggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="primary-navigation">
        <i class="fas fa-bars"></i>
      </button>
      
      <a href="index.html" class="brand-logo" aria-label="Ian Escalante Portfolio Homepage">
        <img src="assets/images/profile/IC-logo.png" alt="ICE Logo" class="brand-logo-img">
      </a>

      <nav id="primary-navigation" class="nav-container">
        <ul class="nav-menu nav-left">
          <li><a href="index.html" class="nav-link">Home</a></li>
          <li><a href="index.html#about" class="nav-link">About</a></li>
          <li><a href="index.html#services" class="nav-link">Services</a></li>
          <li><a href="index.html#portfolio" class="nav-link">Portfolio</a></li>
          <li><a href="blog.php" class="nav-link active">Blog</a></li>
        </ul>

        <ul class="nav-menu nav-right">
          <li class="has-dropdown">
            <a href="design.html" class="nav-link">Design <i class="fas fa-chevron-down" style="font-size: 0.75rem; margin-left: 4px;"></i></a>
            <ul class="dropdown-menu">
              <li><a href="design.html" class="dropdown-item">Design Overview</a></li>
              <li><a href="webdesign.html" class="dropdown-item">Web Design</a></li>
              <li><a href="logodesign.html" class="dropdown-item">Logo & Brand Design</a></li>
            </ul>
          </li>
          <li><a href="index.html#experience" class="nav-link">Experience</a></li>
          <li><a href="index.html#contact" class="btn btn-primary btn-sm">Contact Me</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main>
    <!-- HERO SECTION -->
    <section class="blog-hero">
      <div class="container">
        <span class="section-tag">Engineering Case Studies & Articles</span>
        <h1>Project Breakdown Blog</h1>
        <p class="hero-subtitle" style="max-width: 680px; margin: 0.75rem auto 0 auto;">
          In-depth technical writeups on custom WordPress plugins, API integrations, database workflows, and UI engineering decisions built for real business operations.
        </p>

        <!-- Search Bar -->
        <form method="GET" action="blog.php" class="blog-search-bar">
          <?php if (!empty($category_filter)): ?>
            <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>">
          <?php endif; ?>
          <input type="text" name="search" class="blog-search-input" placeholder="Search by project name, title, or technology..." value="<?= htmlspecialchars($search_query) ?>">
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
        </form>

        <!-- Category Filters -->
        <div class="category-pills">
          <a href="blog.php<?= !empty($search_query) ? '?search=' . urlencode($search_query) : '' ?>" class="cat-pill <?= empty($category_filter) ? 'active' : '' ?>">All Categories</a>
          <?php foreach ($categories as $cat): ?>
            <a href="blog.php?category=<?= urlencode($cat) ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="cat-pill <?= $category_filter === $cat ? 'active' : '' ?>">
              <?= htmlspecialchars($cat) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ARTICLES LISTING GRID -->
    <section class="section-padding" style="padding-top: 0;">
      <div class="container">
        <?php if ($is_admin): ?>
          <div style="background: rgba(212,175,55,0.1); border: 1px solid var(--accent-primary); padding: 12px 18px; border-radius: var(--radius-md); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="color: var(--accent-primary); font-size: 0.9rem;"><i class="fas fa-user-shield"></i> Admin Mode Active: View all draft articles and access editing controls in <a href="admin.php?tab=articles" style="text-decoration: underline;">Admin Dashboard</a>.</span>
            <a href="admin.php?tab=articles" class="btn btn-sm btn-outline"><i class="fas fa-cog"></i> Manage Articles</a>
          </div>
        <?php endif; ?>

        <?php if (empty($paged_articles)): ?>
          <div class="glass-card" style="text-align: center; padding: 4rem 2rem; max-width: 600px; margin: 0 auto;">
            <i class="fas fa-search" style="font-size: 3rem; color: var(--accent-primary); margin-bottom: 1rem;"></i>
            <h3>No Published Articles Found</h3>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">
              <?= !empty($search_query) || !empty($category_filter) ? 'No articles match your current search or category filter.' : 'Initial project articles are currently saved as drafts for review.' ?>
            </p>
            <a href="blog.php" class="btn btn-outline btn-sm" style="margin-top: 1rem;">Reset Search Filters</a>
          </div>
        <?php else: ?>
          <div class="blog-grid">
            <?php foreach ($paged_articles as $art): ?>
              <article class="glass-card blog-card">
                <div>
                  <div class="blog-cover-wrapper">
                    <img src="<?= htmlspecialchars($art['cover_image']) ?>" alt="<?= htmlspecialchars($art['cover_alt']) ?>" class="blog-cover-img" loading="lazy">
                  </div>
                  <div class="blog-card-content">
                    <div class="blog-meta-line">
                      <span><?= htmlspecialchars($art['category']) ?></span>
                      <span><i class="far fa-clock"></i> <?= htmlspecialchars($art['reading_time']) ?></span>
                    </div>
                    <h2 class="blog-title">
                      <a href="blog.php?slug=<?= urlencode($art['slug']) ?>"><?= htmlspecialchars($art['title']) ?></a>
                    </h2>
                    <p class="blog-excerpt"><?= htmlspecialchars($art['excerpt']) ?></p>
                  </div>
                </div>
                <div style="padding: 0 1.5rem 1.5rem 1.5rem;">
                  <a href="blog.php?slug=<?= urlencode($art['slug']) ?>" class="btn btn-outline btn-sm" style="width: 100%; text-align: center;">Read Full Article <i class="fas fa-arrow-right"></i></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <!-- Pagination Controls -->
          <?php if ($total_pages > 1): ?>
            <div class="pagination">
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="blog.php?page=<?= $i ?><?= !empty($category_filter) ? '&category=' . urlencode($category_filter) : '' ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page-link <?= $page === $i ? 'active' : '' ?>">
                  <?= $i ?>
                </a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- CONTACT CTA -->
    <section class="section-padding" style="background: rgba(15, 23, 42, 0.4);">
      <div class="container text-center" style="text-align: center;">
        <h2>Have a Similar Custom WordPress Project?</h2>
        <p style="max-width: 600px; margin: 1rem auto 2rem auto;">I build secure websites, plugins, API integrations, and automated workflows tailored specifically to your business operations.</p>
        <a href="index.html#contact" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Discuss Your Project</a>
      </div>
    </section>
  </main>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr;">
        <div class="footer-brand">
          <a href="index.html" class="footer-logo" aria-label="Ian Escalante Portfolio Homepage">
            <img src="assets/images/profile/IC-logo.png" alt="ICE Logo" class="footer-logo-img">
          </a>
          <p>WordPress developer specializing in custom websites, plugins, integrations, automation, and responsive digital experiences.</p>
        </div>

        <div class="footer-links">
          <h4>Navigation</h4>
          <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="index.html#about">About</a></li>
            <li><a href="index.html#services">Services</a></li>
            <li><a href="index.html#portfolio">Portfolio</a></li>
            <li><a href="blog.php">Blog</a></li>
          </ul>
        </div>

        <div class="footer-links">
          <h4>Design Portfolio</h4>
          <ul>
            <li><a href="design.html">Design Overview</a></li>
            <li><a href="webdesign.html">Web Design Showcase</a></li>
            <li><a href="logodesign.html">Logo & Branding</a></li>
          </ul>
        </div>

        <div class="footer-links">
          <h4>Articles & Insights</h4>
          <ul>
            <li><a href="blog.php">Latest Articles</a></li>
            <li><a href="admin.php?tab=articles">Admin Manager</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <div>&copy; 2026 Ian Escalante. All Rights Reserved.</div>
        <div>
          <a href="mailto:ianfreelancer102@gmail.com" style="color: var(--accent-primary); font-weight: 600;">ianfreelancer102@gmail.com</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="assets/js/portfolio.js"></script>
</body>
</html>
    <?php
}

function render_article_page($art, $all_articles, $exp_map, $is_admin) {
    // Find related articles (same category or shared tags, excluding current)
    $related = array_values(array_filter($all_articles, function($a) use ($art, $is_admin) {
        if ($a['id'] === $art['id']) return false;
        if ($a['status'] !== 'published' && !$is_admin) return false;
        return ($a['category'] === $art['category'] || $a['project_id'] === $art['project_id']);
    }));
    $related = array_slice($related, 0, 3);

    // Associated Experience
    $exp = !empty($art['experience_id']) ? ($exp_map[$art['experience_id']] ?? null) : null;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($art['title']) ?> | Ian Escalante Blog</title>
  <meta name="description" content="<?= htmlspecialchars(substr($art['excerpt'], 0, 155)) ?>">
  <link rel="canonical" href="https://ianescalante-ph.vercel.app/blog.php?slug=<?= urlencode($art['slug']) ?>">

  <!-- Open Graph / Social -->
  <meta property="og:title" content="<?= htmlspecialchars($art['title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($art['excerpt']) ?>">
  <meta property="og:type" content="article">
  <meta property="og:url" content="https://ianescalante-ph.vercel.app/blog.php?slug=<?= urlencode($art['slug']) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($art['cover_image']) ?>">

  <!-- Schema.org JSON-LD Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": "<?= addslashes($art['title']) ?>",
    "image": "<?= addslashes($art['cover_image']) ?>",
    "author": {
      "@type": "Person",
      "name": "<?= addslashes($art['author']) ?>"
    },
    "publisher": {
      "@type": "Person",
      "name": "Ian Escalante"
    },
    "datePublished": "<?= date('c', strtotime($art['published_at'])) ?>",
    "dateModified": "<?= date('c', strtotime($art['updated_at'])) ?>",
    "description": "<?= addslashes($art['excerpt']) ?>"
  }
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/portfolio.css">

  <style>
    .article-header { padding: 4rem 0 2rem 0; border-bottom: 1px solid var(--border-color); margin-bottom: 2.5rem; }
    .article-meta-bar { display: flex; flex-wrap: wrap; gap: 1.25rem; font-size: 0.9rem; color: var(--text-muted); margin-top: 1.25rem; align-items: center; }
    .article-meta-item { display: flex; align-items: center; gap: 6px; }
    .article-container { max-width: 820px; margin: 0 auto; line-height: 1.8; font-size: 1.05rem; }
    .article-content h2 { font-size: 1.6rem; margin-top: 2.5rem; margin-bottom: 1rem; color: var(--accent-primary); border-bottom: 1px solid rgba(212,175,55,0.2); padding-bottom: 0.4rem; }
    .article-content h3 { font-size: 1.3rem; margin-top: 2rem; margin-bottom: 0.75rem; color: var(--text-primary); }
    .article-content p { margin-bottom: 1.35rem; color: var(--text-secondary); }
    .article-content ul, .article-content ol { margin-bottom: 1.5rem; padding-left: 1.5rem; color: var(--text-secondary); }
    .article-content li { margin-bottom: 0.5rem; }
    .article-content blockquote { border-left: 4px solid var(--accent-primary); padding: 1rem 1.5rem; background: rgba(255,255,255,0.03); border-radius: 0 var(--radius-md) var(--radius-md) 0; margin: 1.5rem 0; font-style: italic; color: var(--text-primary); }
    .article-figure { margin: 2rem 0; text-align: center; }
    .article-img { width: 100%; max-height: 480px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--border-color); box-shadow: var(--shadow-md); }
    figcaption { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.6rem; font-style: italic; }
    .article-context-banner { background: rgba(212,175,55,0.08); border-left: 3px solid var(--accent-primary); padding: 12px 18px; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin-bottom: 2rem; font-size: 0.95rem; }
    .article-footer-cta { margin-top: 4rem; padding: 2.5rem; background: rgba(26,27,31,0.95); border: 1px solid var(--accent-primary); border-radius: var(--radius-md); text-align: center; }
    .article-connections-panel { background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; margin-top: 3rem; }
  </style>
</head>
<body>

  <!-- Sticky Header -->
  <header class="site-header">
    <div class="container header-inner">
      <button class="mobile-nav-toggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="primary-navigation">
        <i class="fas fa-bars"></i>
      </button>
      
      <a href="index.html" class="brand-logo" aria-label="Ian Escalante Portfolio Homepage">
        <img src="assets/images/profile/IC-logo.png" alt="ICE Logo" class="brand-logo-img">
      </a>

      <nav id="primary-navigation" class="nav-container">
        <ul class="nav-menu nav-left">
          <li><a href="index.html" class="nav-link">Home</a></li>
          <li><a href="index.html#about" class="nav-link">About</a></li>
          <li><a href="index.html#services" class="nav-link">Services</a></li>
          <li><a href="index.html#portfolio" class="nav-link">Portfolio</a></li>
          <li><a href="blog.php" class="nav-link active">Blog</a></li>
        </ul>

        <ul class="nav-menu nav-right">
          <li class="has-dropdown">
            <a href="design.html" class="nav-link">Design <i class="fas fa-chevron-down" style="font-size: 0.75rem; margin-left: 4px;"></i></a>
            <ul class="dropdown-menu">
              <li><a href="design.html" class="dropdown-item">Design Overview</a></li>
              <li><a href="webdesign.html" class="dropdown-item">Web Design</a></li>
              <li><a href="logodesign.html" class="dropdown-item">Logo & Brand Design</a></li>
            </ul>
          </li>
          <li><a href="index.html#experience" class="nav-link">Experience</a></li>
          <li><a href="index.html#contact" class="btn btn-primary btn-sm">Contact Me</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main>
    <article class="article-container" style="padding-top: 2rem; padding-bottom: 4rem;">
      <div style="margin-bottom: 1.5rem;">
        <a href="blog.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;"><i class="fas fa-arrow-left"></i> Back to Blog Articles</a>
      </div>

      <?php if ($art['status'] === 'draft'): ?>
        <div style="background: rgba(245,158,11,0.15); border: 1px solid #F59E0B; color: #F59E0B; padding: 12px 18px; border-radius: var(--radius-md); margin-bottom: 2rem; font-weight: 600;">
          <i class="fas fa-exclamation-triangle"></i> DRAFT PREVIEW MODE: This article is currently unpublished and only accessible to logged-in admins.
          <?php if ($is_admin): ?>
            <a href="admin.php?action=edit_article&id=<?= urlencode($art['id']) ?>" class="btn btn-sm btn-primary" style="margin-left: 1rem;">Edit & Publish in Admin</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <header class="article-header">
        <span class="section-tag"><?= htmlspecialchars($art['category']) ?> &bull; <?= htmlspecialchars(ucwords(str_replace('_', ' ', $art['article_type'] ?? 'Story'))) ?></span>
        <h1 style="font-size: clamp(2rem, 4vw, 2.75rem); margin-top: 0.5rem; line-height: 1.25;"><?= htmlspecialchars($art['title']) ?></h1>

        <div class="article-meta-bar">
          <div class="article-meta-item">
            <i class="fas fa-user-circle" style="color: var(--accent-primary);"></i>
            <span>Author: <strong><?= htmlspecialchars($art['author']) ?></strong></span>
          </div>
          <div class="article-meta-item">
            <i class="far fa-calendar-alt"></i>
            <span>Published: <?= date('F j, Y', strtotime($art['published_at'])) ?></span>
          </div>
          <div class="article-meta-item">
            <i class="far fa-clock"></i>
            <span><?= htmlspecialchars($art['reading_time']) ?></span>
          </div>
          <div class="article-meta-item">
            <i class="fas fa-folder"></i>
            <span>Client/Project: <strong><?= htmlspecialchars($art['client_name']) ?></strong></span>
          </div>
        </div>
      </header>

      <!-- ARTICLE BODY -->
      <div class="article-content">
        <?= $art['body'] ?>
      </div>

      <!-- EXPLICIT RELATIONSHIPS & CONNECTIONS PANEL -->
      <div class="article-connections-panel">
        <h3 style="font-size: 1.15rem; color: var(--accent-primary); margin-bottom: 0.75rem;"><i class="fas fa-link"></i> Project &amp; Track Record Connections</h3>
        <p style="font-size: 0.95rem; margin-bottom: 1rem;">This technical article relates directly to the following verified portfolio components:</p>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
          <a href="index.html#portfolio" class="btn btn-outline btn-sm"><i class="fas fa-layer-group"></i> Featured Portfolio Project</a>
          <?php if (!empty($exp)): ?>
            <a href="index.html#experience" class="btn btn-outline btn-sm"><i class="fas fa-briefcase"></i> Work Experience: <?= htmlspecialchars($exp['organization']) ?></a>
          <?php else: ?>
            <span style="font-size: 0.85rem; color: var(--text-muted); align-self: center;"><i class="fas fa-check"></i> Independent Client Engagement</span>
          <?php endif; ?>
          <?php if (!empty($art['live_url'])): ?>
            <a href="<?= htmlspecialchars($art['live_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm"><i class="fas fa-external-link-alt"></i> Visit Live Website</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- RELATED ARTICLES -->
      <?php if (!empty($related)): ?>
        <div style="margin-top: 3.5rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
          <h3 style="font-size: 1.3rem; margin-bottom: 1.5rem; color: var(--accent-primary);"><i class="fas fa-bookmark"></i> Related Project Articles</h3>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem;">
            <?php foreach ($related as $rel): ?>
              <div class="glass-card" style="padding: 1.25rem;">
                <span style="font-size: 0.75rem; color: var(--accent-primary); font-weight: 700; text-transform: uppercase;"><?= htmlspecialchars($rel['category']) ?></span>
                <h4 style="font-size: 1.05rem; margin: 6px 0 10px 0;"><a href="blog.php?slug=<?= urlencode($rel['slug']) ?>" style="color: #FFF; text-decoration: none;"><?= htmlspecialchars($rel['title']) ?></a></h4>
                <a href="blog.php?slug=<?= urlencode($rel['slug']) ?>" style="font-size: 0.85rem; color: var(--accent-primary); font-weight: 600;">Read Article <i class="fas fa-arrow-right"></i></a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- FOOTER CONTACT CTA -->
      <div class="article-footer-cta">
        <h3 style="font-size: 1.4rem; margin-bottom: 0.75rem; color: var(--accent-primary);">Need a Similar Custom WordPress Solution?</h3>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto 1.5rem auto;">I turn complex business requirements into dependable WordPress websites, plugins, dashboards, and automated API systems.</p>
        <a href="index.html#contact" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Get in Touch With Ian</a>
      </div>
    </article>
  </main>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr;">
        <div class="footer-brand">
          <a href="index.html" class="footer-logo" aria-label="Ian Escalante Portfolio Homepage">
            <img src="assets/images/profile/IC-logo.png" alt="ICE Logo" class="footer-logo-img">
          </a>
          <p>WordPress developer specializing in custom websites, plugins, integrations, automation, and responsive digital experiences.</p>
        </div>

        <div class="footer-links">
          <h4>Navigation</h4>
          <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="index.html#about">About</a></li>
            <li><a href="index.html#services">Services</a></li>
            <li><a href="index.html#portfolio">Portfolio</a></li>
            <li><a href="blog.php">Blog</a></li>
          </ul>
        </div>

        <div class="footer-links">
          <h4>Design Portfolio</h4>
          <ul>
            <li><a href="design.html">Design Overview</a></li>
            <li><a href="webdesign.html">Web Design Showcase</a></li>
            <li><a href="logodesign.html">Logo & Branding</a></li>
          </ul>
        </div>

        <div class="footer-links">
          <h4>Articles & Insights</h4>
          <ul>
            <li><a href="blog.php">Latest Articles</a></li>
            <li><a href="admin.php?tab=articles">Admin Manager</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <div>&copy; 2026 Ian Escalante. All Rights Reserved.</div>
        <div>
          <a href="mailto:ianfreelancer102@gmail.com" style="color: var(--accent-primary); font-weight: 600;">ianfreelancer102@gmail.com</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="assets/js/portfolio.js"></script>
</body>
</html>
    <?php
}

function render_404() {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Article Not Found | Ian Escalante Portfolio</title>
  <link rel="stylesheet" href="assets/css/portfolio.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center;">
  <div class="glass-card" style="padding: 3rem; max-width: 500px;">
    <h1 style="color: var(--accent-primary); font-size: 3rem;">404</h1>
    <h2>Article Not Found</h2>
    <p style="margin: 1rem 0 2rem 0; color: var(--text-secondary);">The requested article does not exist or may have been updated.</p>
    <a href="blog.php" class="btn btn-primary">Return to Blog Listing</a>
  </div>
</body>
</html>
    <?php
}

function render_draft_restricted() {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Draft Preview Restricted | Ian Escalante Portfolio</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="assets/css/portfolio.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center;">
  <div class="glass-card" style="padding: 3rem; max-width: 520px;">
    <i class="fas fa-lock" style="font-size: 3rem; color: #F59E0B; margin-bottom: 1rem;"></i>
    <h2>Draft Article Restricted</h2>
    <p style="margin: 1rem 0 2rem 0; color: var(--text-secondary);">This article is currently saved as an internal draft and is not publicly exposed. If you are the site administrator, please log in via the admin panel to preview or publish.</p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
      <a href="admin.php" class="btn btn-primary">Admin Login</a>
      <a href="blog.php" class="btn btn-outline">Back to Blog</a>
    </div>
  </div>
</body>
</html>
    <?php
}
