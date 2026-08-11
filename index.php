<?php
require_once __DIR__ . '/config/config.php';

// Guest view: No authentication required
$q = trim($_GET['q'] ?? '');
$media = trim($_GET['media'] ?? 'semua');

// Base query for published news only
$sql = "SELECT n.*, u.full_name AS author_name 
        FROM news n 
        JOIN users u ON n.created_by = u.id 
        WHERE n.status = 'published'";
$params = [];

if ($media !== 'semua') {
    $sql .= " AND n.media = ?";
    $params[] = $media;
}

if ($q !== '') {
    $sql .= " AND (n.title LIKE ? OR n.content LIKE ? OR n.wilayah LIKE ? OR n.aktor LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$sql .= " ORDER BY n.published_at DESC, n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$newsList = $stmt->fetchAll();

// Select the latest article as featured only if no search/filter is active
$featuredNews = null;
if ($q === '' && $media === 'semua' && !empty($newsList)) {
    $featuredNews = $newsList[0];
    array_shift($newsList); // Remove from list to avoid duplication
}

function getSentimentBadgeClass($sentiment) {
    switch ($sentiment) {
        case 'Positif': return 'badge-green';
        case 'Negatif': return 'badge-red';
        case 'Netral':
        default: return 'badge-blue';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Berita Resmi TNI Angkatan Udara</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .public-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        #featuredContainer, #newsGridContainer {
            transition: opacity 0.22s ease-in-out;
        }

        /* Hero / Featured Article */
        .hero-section {
            background: linear-gradient(135deg, #0b2545 0%, #134074 100%);
            border-radius: 24px;
            overflow: hidden;
            display: flex;
            box-shadow: var(--shadow-lg);
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
        }
        .hero-img-container {
            flex: 1.2;
            height: 400px;
            position: relative;
        }
        .hero-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .hero-content {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #fff;
        }
        .hero-tag {
            align-self: flex-start;
            background: var(--gold);
            color: #0b2545;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .hero-title {
            font-size: 24px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 16px;
            color: #fff;
        }
        .hero-excerpt {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 24px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .hero-meta {
            display: flex;
            gap: 16px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            padding-top: 16px;
            margin-bottom: 24px;
        }

        /* Filter & Search Bar */
        .filter-search-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .category-tabs {
            display: flex;
            gap: 8px;
            background: rgba(255, 255, 255, 0.7);
            padding: 4px;
            border-radius: 12px;
            border: 1px solid rgba(30, 111, 191, 0.1);
            backdrop-filter: blur(10px);
        }
        .category-tab {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-sec);
            transition: all var(--transition);
        }
        .category-tab:hover {
            color: var(--navy);
            background: rgba(30, 111, 191, 0.05);
        }
        .category-tab.active {
            background: var(--navy);
            color: #fff;
        }
        .search-form {
            position: relative;
            min-width: 300px;
        }
        .search-input {
            width: 100%;
            height: 40px;
            border: 1px solid rgba(30, 111, 191, 0.15);
            background: #fff;
            border-radius: 12px;
            padding: 0 16px 0 40px;
            font-size: 12.5px;
            color: var(--text);
            outline: none;
            transition: all var(--transition);
            box-shadow: var(--shadow-sm);
        }
        .search-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(30, 111, 191, 0.15);
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* News Grid Layout */
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 60px;
        }
        .public-news-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition);
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }
        .public-news-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: rgba(30, 111, 191, 0.2);
        }
        .card-img-container {
            width: 100%;
            height: 200px;
            position: relative;
            overflow: hidden;
        }
        .card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition);
        }
        .public-news-card:hover .card-img {
            transform: scale(1.05);
        }
        .card-labels {
            position: absolute;
            bottom: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-label-left {
            background: rgba(11, 37, 69, 0.85);
            color: #fff;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 9.5px;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }
        .card-label-right {
            background: var(--gold);
            color: #0b2545;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
            line-height: 1.4;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 42px;
        }
        .card-excerpt {
            font-size: 12px;
            color: var(--text-sec);
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 38px;
            flex: 1;
        }
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 12px;
            margin-top: auto;
        }
        .card-date {
            font-size: 10.5px;
            color: var(--text-muted);
        }
        .card-sentiment {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .empty-results {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 20px;
            border: 1px solid var(--border-light);
            grid-column: 1 / -1;
        }

        /* Unified public footer styling is in style.css */

        @media (max-width: 768px) {
            .hero-section {
                flex-direction: column;
            }
            .hero-img-container {
                height: 240px;
            }
            .hero-content {
                padding: 24px;
            }
            .filter-search-row {
                flex-direction: column;
                align-items: stretch;
            }
            .category-tabs {
                overflow-x: auto;
            }
            .search-form {
                min-width: unset;
            }
        }
    </style>
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column; background: linear-gradient(145deg, #deeeff 0%, #edf4ff 40%, #e8f0fc 100%);">

    <!-- NAVBAR -->
    <?php include __DIR__ . '/includes/public_navbar.php'; ?>

    <!-- CONTENT -->
    <main class="public-container">

        <!-- HERO / FEATURED NEWS CONTAINER -->
        <div id="featuredContainer">
            <?php if ($featuredNews): ?>
                <?php
                // Strip tags and create excerpt
                $excerpt = strip_tags($featuredNews['content']);
                ?>
                <div class="hero-section">
                    <div class="hero-img-container">
                        <img src="<?= $featuredNews['image_path'] ? UPLOAD_URL . e($featuredNews['image_path']) : 'https://placehold.co/600x400/e9edf2/a0a8b3?text=TNI+AU' ?>" class="hero-img" alt="Featured News Image">
                    </div>
                    <div class="hero-content">
                        <span class="hero-tag"><?= e($featuredNews['media']) ?></span>
                        <h2 class="hero-title"><?= e($featuredNews['title']) ?></h2>
                        <p class="hero-excerpt"><?= e($excerpt) ?></p>
                        <div class="hero-meta">
                            <span>📍 <?= e($featuredNews['wilayah'] ?: '-') ?></span>
                            <span>📅 <?= formatTanggal($featuredNews['published_at'] ?: $featuredNews['created_at']) ?></span>
                            <span>✍️ <?= e($featuredNews['author_label'] ?? $featuredNews['author_name']) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>/public_view.php?id=<?= $featuredNews['id'] ?>" class="btn btn-primary" style="align-self: flex-start; padding: 10px 24px; font-weight: 600;">
                            Baca Berita Selengkapnya
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- FILTER & SEARCH BAR -->
        <div class="filter-search-row">
            <div class="category-tabs">
                <a href="?media=semua&amp;q=<?= urlencode($q) ?>" class="category-tab <?= $media === 'semua' ? 'active' : '' ?>">Semua Media</a>
                <a href="?media=Wilayah&amp;q=<?= urlencode($q) ?>" class="category-tab <?= $media === 'Wilayah' ? 'active' : '' ?>">Berita Wilayah</a>
                <a href="?media=Media Online&amp;q=<?= urlencode($q) ?>" class="category-tab <?= $media === 'Media Online' ? 'active' : '' ?>">Media Online</a>
                <a href="?media=Media Sosial&amp;q=<?= urlencode($q) ?>" class="category-tab <?= $media === 'Media Sosial' ? 'active' : '' ?>">Media Sosial</a>
            </div>
            
            <form action="" method="GET" class="search-form">
                <input type="hidden" name="media" value="<?= e($media) ?>">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" value="<?= e($q) ?>" class="search-input" placeholder="Cari berita TNI AU...">
            </form>
        </div>

        <!-- NEWS GRID CONTAINER -->
        <div id="newsGridContainer">
            <div class="news-grid">
                <?php if (empty($newsList) && !$featuredNews): ?>
                    <div class="empty-results">
                        <div style="font-size: 32px; margin-bottom: 12px;">🔍</div>
                        <h3 style="font-size: 15px; font-weight: 700; color: var(--navy); margin-bottom: 8px;">Berita tidak ditemukan</h3>
                        <p style="font-size: 12px; color: var(--text-sec);">Silakan coba pencarian lain atau pilih tab media yang berbeda.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($newsList as $n): ?>
                        <?php
                        $cardExcerpt = strip_tags($n['content']);
                        ?>
                        <article class="public-news-card">
                            <div class="card-img-container">
                                <img src="<?= $n['image_path'] ? UPLOAD_URL . e($n['image_path']) : 'https://placehold.co/400x250/e9edf2/a0a8b3?text=TNI+AU' ?>" class="card-img" alt="News Image">
                                <div class="card-labels">
                                    <span class="card-label-left">📍 <?= e($n['wilayah'] ?: '-') ?></span>
                                    <span class="card-label-right"><?= e($n['media']) ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title">
                                    <a href="<?= BASE_URL ?>/public_view.php?id=<?= $n['id'] ?>"><?= e($n['title']) ?></a>
                                </h3>
                                <p class="card-excerpt"><?= e($cardExcerpt) ?></p>
                                <div class="card-footer">
                                    <span class="card-date">📅 <?= date('d M Y', strtotime($n['published_at'] ?: $n['created_at'])) ?></span>
                                    <span class="badge <?= getSentimentBadgeClass($n['sentiment']) ?> card-sentiment"><?= e($n['sentiment']) ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <!-- FOOTER -->
    <?php include __DIR__ . '/includes/public_footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.category-tab');
        const searchForm = document.querySelector('.search-form');
        const searchInput = document.querySelector('.search-input');
        const featuredContainer = document.getElementById('featuredContainer');
        const newsGridContainer = document.getElementById('newsGridContainer');
        
        let debounceTimer;
        
        // Function to perform AJAX load
        async function loadContent(url, updateInput = false) {
            // Add loading opacity for visual transition feedback
            if (featuredContainer) featuredContainer.style.opacity = '0.3';
            if (newsGridContainer) newsGridContainer.style.opacity = '0.3';
            
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Response error');
                const html = await response.text();
                
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // 1. Update Hero/Featured Section
                const newFeatured = doc.getElementById('featuredContainer');
                if (featuredContainer && newFeatured) {
                    featuredContainer.innerHTML = newFeatured.innerHTML;
                }
                
                // 2. Update News Grid
                const newNewsGrid = doc.getElementById('newsGridContainer');
                if (newsGridContainer && newNewsGrid) {
                    newsGridContainer.innerHTML = newNewsGrid.innerHTML;
                }
                
                // 3. Update Active Tab class
                const docTabs = doc.querySelectorAll('.category-tab');
                tabs.forEach((tab, index) => {
                    if (docTabs[index]) {
                        if (docTabs[index].classList.contains('active')) {
                            tab.classList.add('active');
                        } else {
                            tab.classList.remove('active');
                        }
                        // Update target href for active query params
                        tab.setAttribute('href', docTabs[index].getAttribute('href'));
                    }
                });
                
                // 4. Update URL params for other tabs
                if (searchInput) {
                    const qVal = searchInput.value.trim();
                    tabs.forEach(tab => {
                        const href = tab.getAttribute('href');
                        const urlObj = new URL(href, window.location.origin);
                        urlObj.searchParams.set('q', qVal);
                        tab.setAttribute('href', urlObj.search + urlObj.hash);
                    });
                }
                
                // 5. Optionally update search input value
                if (updateInput) {
                    const newSearchInput = doc.querySelector('.search-input');
                    if (searchInput && newSearchInput) {
                        searchInput.value = newSearchInput.value;
                    }
                }
                
                // 6. Update browser history/URL
                if (window.location.href !== url) {
                    window.history.pushState({ url: url }, '', url);
                }
            } catch (error) {
                console.error('Error fetching filtered news:', error);
                window.location.href = url; // fallback
            } finally {
                setTimeout(() => {
                    if (featuredContainer) featuredContainer.style.opacity = '1';
                    if (newsGridContainer) newsGridContainer.style.opacity = '1';
                }, 100);
            }
        }
        
        // Event listeners for tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const url = tab.getAttribute('href');
                loadContent(url);
            });
        });
        
        // Event listener for search input typing (real-time search)
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const activeTab = document.querySelector('.category-tab.active');
                    let media = 'semua';
                    if (activeTab) {
                        const urlParams = new URLSearchParams(activeTab.getAttribute('href').split('?')[1]);
                        media = urlParams.get('media') || 'semua';
                    }
                    
                    const q = searchInput.value.trim();
                    const url = `?media=${encodeURIComponent(media)}&q=${encodeURIComponent(q)}`;
                    loadContent(url);
                }, 300);
            });
            
            // Prevent form submit reloading the page
            if (searchForm) {
                searchForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const activeTab = document.querySelector('.category-tab.active');
                    let media = 'semua';
                    if (activeTab) {
                        const urlParams = new URLSearchParams(activeTab.getAttribute('href').split('?')[1]);
                        media = urlParams.get('media') || 'semua';
                    }
                    const q = searchInput.value.trim();
                    const url = `?media=${encodeURIComponent(media)}&q=${encodeURIComponent(q)}`;
                    loadContent(url);
                });
            }
        }
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', (e) => {
            loadContent(window.location.href, true);
        });
    });
    </script>

</body>
</html>
