<?php
header('Content-Type: application/xml; charset=utf-8');

include 'config.php';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>http://halarnati.free.nf/index.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>http://halarnati.free.nf/login.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>http://halarnati.free.nf/register.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php
    // Fetch all entries
    $entries = $db->fetchAll("SELECT slug, created_at FROM entries ORDER BY created_at DESC");
    foreach ($entries as $entry) {
        $loc = 'http://halarnati.free.nf/entry.php?slug=' . htmlspecialchars($entry['slug']);
        $lastmod = date('Y-m-d', strtotime($entry['created_at']));
        echo "    <url>\n";
        echo "        <loc>" . $loc . "</loc>\n";
        echo "        <lastmod>" . $lastmod . "</lastmod>\n";
        echo "        <changefreq>weekly</changefreq>\n";
        echo "        <priority>0.9</priority>\n";
        echo "    </url>\n";
    }

    // Fetch all categories
    $categories = $db->fetchAll("SELECT slug FROM categories ORDER BY name ASC");
    foreach ($categories as $category) {
        $loc = 'http://halarnati.free.nf/category.php?slug=' . htmlspecialchars($category['slug']);
        echo "    <url>\n";
        echo "        <loc>" . $loc . "</loc>\n";
        echo "        <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        echo "        <changefreq>weekly</changefreq>\n";
        echo "        <priority>0.7</priority>\n";
        echo "    </url>\n";
    }
    ?>
</urlset>
