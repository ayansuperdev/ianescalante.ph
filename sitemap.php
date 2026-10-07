<?php
/**
 * XML Sitemap Generator Script
 * Generates sitemap.xml including main pages and ONLY published blog articles.
 * Drafts are strictly excluded from sitemap.xml.
 */

header('Content-Type: application/xml; charset=utf-8');

$base_url = 'https://ianescalante-ph.vercel.app';
$articles_file = __DIR__ . '/data/articles.json';
$articles = [];
if (file_exists($articles_file)) {
    $articles = json_decode(file_get_contents($articles_file), true) ?: [];
}

$static_pages = [
    '' => '1.0',
    'design.html' => '0.8',
    'webdesign.html' => '0.8',
    'logodesign.html' => '0.8',
    'blog.php' => '0.9'
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <?php foreach ($static_pages as $page => $priority): ?>
    <url>
      <loc><?= $base_url . '/' . $page ?></loc>
      <lastmod><?= date('Y-m-d') ?></lastmod>
      <changefreq>weekly</changefreq>
      <priority><?= $priority ?></priority>
    </url>
  <?php endforeach; ?>

  <?php foreach ($articles as $art): ?>
    <?php if (($art['status'] ?? '') === 'published'): ?>
      <url>
        <loc><?= $base_url . '/blog.php?slug=' . urlencode($art['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($art['updated_at'] ?? $art['published_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
      </url>
    <?php endif; ?>
  <?php endforeach; ?>
</urlset>
