<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();
$current = 'alutsista_assets';

$error = '';
$success = '';

// Handle upload new asset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_asset'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: " . BASE_URL . "/alutsista_assets.php");
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $code_name = trim($_POST['code_name'] ?? '');
    $category = trim($_POST['category'] ?? 'Pesawat Tempur');
    $origin_country = trim($_POST['origin_country'] ?? 'Indonesia');
    $unit_assignment = trim($_POST['unit_assignment'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $image_url_manual = trim($_POST['image_url_manual'] ?? '');

    $imagePath = '';

    // Check if uploaded file
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            $error = 'Format file gambar tidak didukung (harus JPG, PNG, atau WEBP).';
        } else {
            $fname = 'alutsista_' . uniqid() . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], UPLOAD_DIR . $fname)) {
                $imagePath = $fname;
            } else {
                $error = 'Gagal menyimpan file gambar.';
            }
        }
    } elseif ($image_url_manual) {
        $imagePath = $image_url_manual;
    } else {
        $error = 'Silakan unggah file gambar atau masukkan URL gambar aset.';
    }

    if (!$error && $name) {
        $stmt = $pdo->prepare("
            INSERT INTO alutsista_assets (name, code_name, category, origin_country, unit_assignment, description, image_path, tags, is_official, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([$name, $code_name, $category, $origin_country, $unit_assignment, $description, $imagePath, $tags, $user['id']]);
        $success = "Aset alutsista resmi \"$name\" berhasil ditambahkan ke Bank Media.";
    } elseif (!$name) {
        $error = 'Nama aset alutsista wajib diisi.';
    }
}

// Handle delete asset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_asset'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $assetId = (int)($_POST['asset_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM alutsista_assets WHERE id = ?");
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();

        if ($asset && ($user['role'] === 'E' || $asset['created_by'] == $user['id'])) {
            // Delete local file if present and in upload dir
            if ($asset['image_path'] && !str_starts_with($asset['image_path'], 'http') && file_exists(UPLOAD_DIR . $asset['image_path'])) {
                @unlink(UPLOAD_DIR . $asset['image_path']);
            }
            $pdo->prepare("DELETE FROM alutsista_assets WHERE id = ?")->execute([$assetId]);
            $success = "Aset alutsista berhasil dihapus.";
        }
    }
}

// Filter params
$categoryFilter = trim($_GET['cat'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT a.*, u.full_name as uploader_name FROM alutsista_assets a LEFT JOIN users u ON u.id = a.created_by WHERE 1=1";
$params = [];

if ($categoryFilter && $categoryFilter !== 'Semua') {
    $sql .= " AND a.category = ?";
    $params[] = $categoryFilter;
}

if ($searchQuery) {
    $sql .= " AND (a.name LIKE ? OR a.code_name LIKE ? OR a.tags LIKE ? OR a.description LIKE ? OR a.unit_assignment LIKE ?)";
    $like = "%$searchQuery%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY a.is_official DESC, a.category ASC, a.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();

// Count stats
$totalAssets = (int)$pdo->query("SELECT COUNT(*) FROM alutsista_assets")->fetchColumn();
$countTempur = (int)$pdo->query("SELECT COUNT(*) FROM alutsista_assets WHERE category='Pesawat Tempur'")->fetchColumn();
$countAngkut = (int)$pdo->query("SELECT COUNT(*) FROM alutsista_assets WHERE category='Pesawat Angkut'")->fetchColumn();
$countHeli   = (int)$pdo->query("SELECT COUNT(*) FROM alutsista_assets WHERE category='Helikopter'")->fetchColumn();
$countLatih  = (int)$pdo->query("SELECT COUNT(*) FROM alutsista_assets WHERE category='Pesawat Latih & Khusus'")->fetchColumn();
$countLogo   = (int)$pdo->query("SELECT COUNT(*) FROM alutsista_assets WHERE category='Lambang & Logo'")->fetchColumn();
$countPasukan= (int)$pdo->query("SELECT COUNT(*) FROM alutsista_assets WHERE category='Pasukan & Senjata'")->fetchColumn();

$categories = [
    'Semua' => $totalAssets,
    'Pesawat Tempur' => $countTempur,
    'Pesawat Angkut' => $countAngkut,
    'Helikopter' => $countHeli,
    'Pesawat Latih & Khusus' => $countLatih,
    'Lambang & Logo' => $countLogo,
    'Pasukan & Senjata' => $countPasukan
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Aset Foto Resmi Alutsista - Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .asset-hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 24px 28px;
            color: #ffffff;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
        }
        .asset-hero-title {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .asset-hero-desc {
            font-size: 13px;
            color: #cbd5e1;
            max-width: 650px;
            line-height: 1.5;
        }
        .asset-cat-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .cat-pill {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: #475569;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .cat-pill:hover, .cat-pill.active {
            background: #1e3a8a;
            color: #ffffff;
            border-color: #1e3a8a;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.25);
        }
        .cat-pill .count {
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 10px;
            background: rgba(0,0,0,0.08);
        }
        .cat-pill.active .count {
            background: rgba(255,255,255,0.25);
            color: #fff;
        }
        .asset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .asset-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .asset-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.09);
        }
        .asset-img-wrap {
            position: relative;
            width: 100%;
            height: 180px;
            background: #0f172a;
            overflow: hidden;
        }
        .asset-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .asset-card:hover .asset-img-wrap img {
            transform: scale(1.05);
        }
        .asset-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #ffffff;
            backdrop-filter: blur(4px);
        }
        .asset-badge.tempur { background: rgba(220, 38, 38, 0.85); }
        .asset-badge.angkut { background: rgba(37, 99, 235, 0.85); }
        .asset-badge.heli   { background: rgba(13, 148, 136, 0.85); }
        .asset-badge.latih  { background: rgba(217, 119, 6, 0.85); }
        .asset-badge.logo   { background: rgba(79, 70, 229, 0.85); }
        .asset-badge.pasukan{ background: rgba(234, 88, 12, 0.85); }
        .asset-card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .asset-card-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
            margin-bottom: 4px;
        }
        .asset-card-unit {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .asset-card-desc {
            font-size: 12px;
            color: #475569;
            line-height: 1.45;
            margin-bottom: 14px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .asset-card-actions {
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            display: flex;
            gap: 8px;
        }
        .btn-use-asset {
            flex: 1;
            background: #1e3a8a;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-use-asset:hover {
            background: #1e40af;
            color: #ffffff;
        }
        .btn-copy-url {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 12px;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-copy-url:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-container">
            
            <?php if ($success): ?>
                <div style="background:#e8f5e9;border:1px solid rgba(46,125,50,.2);color:#2e7d32;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:16px;">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background:#fceae8;border:1px solid rgba(192,57,43,.15);color:#c0392b;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:16px;">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- HERO BANNER -->
            <div class="asset-hero">
                <div>
                    <div class="asset-hero-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        Bank Aset Foto Resmi Alutsista TNI AU
                    </div>
                    <div class="asset-hero-desc">
                        Koleksi foto resmi resolusi tinggi pesawat tempur, angkut, helikopter, radar, lambang kedinasan, dan pasukan TNI AU. Siap digunakan langsung dalam naskah siaran pers dan publikasi berita.
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" onclick="openUploadModal()" style="background:#2563eb;border-color:#2563eb;display:inline-flex;align-items:center;gap:6px;padding:10px 18px;font-size:13px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Tambah Aset Baru
                    </button>
                </div>
            </div>

            <!-- SEARCH & FILTER BAR -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                <!-- Category Pills -->
                <div class="asset-cat-pills" style="margin-bottom:0;">
                    <?php foreach ($categories as $catName => $catCount): 
                        $isActive = ($categoryFilter === $catName) || (!$categoryFilter && $catName === 'Semua');
                    ?>
                        <a href="<?= BASE_URL ?>/alutsista_assets.php?cat=<?= urlencode($catName) ?><?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>" 
                           class="cat-pill <?= $isActive ? 'active' : '' ?>">
                            <?= e($catName) ?>
                            <span class="count"><?= $catCount ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Search input -->
                <form method="GET" action="<?= BASE_URL ?>/alutsista_assets.php" style="display:flex; gap:8px; min-width:260px;">
                    <?php if ($categoryFilter): ?>
                        <input type="hidden" name="cat" value="<?= e($categoryFilter) ?>">
                    <?php endif; ?>
                    <input type="text" name="q" class="form-input" placeholder="Cari nama alutsista, skadron, tag..." value="<?= e($searchQuery) ?>" style="font-size:13px; padding:7px 12px;">
                    <button type="submit" class="btn btn-outline btn-sm">Cari</button>
                    <?php if ($searchQuery): ?>
                        <a href="<?= BASE_URL ?>/alutsista_assets.php<?= $categoryFilter ? '?cat=' . urlencode($categoryFilter) : '' ?>" class="btn btn-outline btn-sm" title="Reset">✕</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ASSET CARDS GRID -->
            <?php if (empty($assets)): ?>
                <div class="card empty-state" style="padding:40px; text-align:center;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <div style="font-size:15px; font-weight:600; color:#334155; margin-bottom:4px;">Tidak ada aset alutsista yang ditemukan</div>
                    <div style="font-size:13px; color:#64748b;">Coba ubah kata kunci pencarian atau kategori filter Anda.</div>
                </div>
            <?php else: ?>
                <div class="asset-grid">
                    <?php foreach ($assets as $a): 
                        $imgSrc = str_starts_with($a['image_path'], 'http') ? $a['image_path'] : (str_starts_with($a['image_path'], 'assets/') ? BASE_URL . '/' . $a['image_path'] : UPLOAD_URL . $a['image_path']);
                        
                        $badgeClass = match($a['category']) {
                            'Pesawat Tempur' => 'tempur',
                            'Pesawat Angkut' => 'angkut',
                            'Helikopter'     => 'heli',
                            'Pesawat Latih & Khusus' => 'latih',
                            'Lambang & Logo' => 'logo',
                            'Pasukan & Senjata' => 'pasukan',
                            default => 'tempur'
                        };
                    ?>
                        <div class="asset-card">
                            <div class="asset-img-wrap">
                                <img src="<?= e($imgSrc) ?>" alt="<?= e($a['name']) ?>" loading="lazy" onclick="viewFullAsset(<?= htmlspecialchars(json_encode([
                                    'name' => $a['name'],
                                    'code_name' => $a['code_name'],
                                    'category' => $a['category'],
                                    'unit_assignment' => $a['unit_assignment'],
                                    'origin_country' => $a['origin_country'],
                                    'description' => $a['description'],
                                    'tags' => $a['tags'],
                                    'imgSrc' => $imgSrc
                                ]), ENT_QUOTES, 'UTF-8') ?>)" style="cursor:pointer;">
                                <span class="asset-badge <?= $badgeClass ?>"><?= e($a['category']) ?></span>
                            </div>
                            <div class="asset-card-body">
                                <div class="asset-card-title"><?= e($a['name']) ?></div>
                                <div class="asset-card-unit">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <?= e($a['unit_assignment'] ?: 'TNI Angkatan Udara') ?>
                                </div>
                                <div class="asset-card-desc"><?= e($a['description']) ?></div>
                                <div class="asset-card-actions">
                                    <a href="<?= BASE_URL ?>/news_create.php?asset_image=<?= urlencode($imgSrc) ?>&asset_title=<?= urlencode($a['name']) ?>" class="btn-use-asset" title="Buat berita menggunakan foto ini">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        Pakai di Berita
                                    </a>
                                    <button type="button" class="btn-copy-url" onclick="copyAssetUrl('<?= e($imgSrc) ?>')" title="Salin tautan gambar">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<!-- MODAL TAMBAH ASET -->
<div id="uploadAssetModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); z-index:9999; backdrop-filter:blur(4px); align-items:center; justify-content:center;">
    <div class="modal-box" style="background:#ffffff; border-radius:14px; max-width:540px; width:90%; max-height:90vh; overflow-y:auto; padding:24px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="font-size:16px; font-weight:700; color:#0f172a; margin:0;">Unggah Aset Alutsista Resmi</h3>
            <button type="button" onclick="closeUploadModal()" style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b;">✕</button>
        </div>

        <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:12px;">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="add_asset" value="1">

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">Nama Aset Lengkap <span style="color:#ef4444">*</span></label>
                <input type="text" name="name" class="form-input" placeholder="Contoh: F-16 C/D Block 52ID Fighting Falcon" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label style="font-size:12px; font-weight:600; color:#334155;">Kode / Nama Pendek</label>
                    <input type="text" name="code_name" class="form-input" placeholder="Contoh: F-16 Falcon">
                </div>
                <div class="form-group">
                    <label style="font-size:12px; font-weight:600; color:#334155;">Kategori</label>
                    <select name="category" class="form-input">
                        <option value="Pesawat Tempur">Pesawat Tempur</option>
                        <option value="Pesawat Angkut">Pesawat Angkut</option>
                        <option value="Helikopter">Helikopter</option>
                        <option value="Pesawat Latih & Khusus">Pesawat Latih & Khusus</option>
                        <option value="Lambang & Logo">Lambang & Logo</option>
                        <option value="Pasukan & Senjata">Pasukan & Senjata</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">Satuan / Homebase Lanud</label>
                <input type="text" name="unit_assignment" class="form-input" placeholder="Contoh: Skadron Udara 3 (Lanud Iswahjudi)">
            </div>

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">Negara Produsen / Pabrikan</label>
                <input type="text" name="origin_country" class="form-input" placeholder="Contoh: Amerika Serikat (Lockheed Martin) / PTDI">
            </div>

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">Unggah File Foto (JPG, PNG, WEBP)</label>
                <input type="file" name="image_file" class="form-input" accept="image/*">
            </div>

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">ATAU Tautan URL Foto (Web / Cloud)</label>
                <input type="url" name="image_url_manual" class="form-input" placeholder="https://...">
            </div>

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">Deskripsi / Karakteristik Operasional</label>
                <textarea name="description" class="form-input" rows="2" placeholder="Uraian singkat spesifikasi dan peran..."></textarea>
            </div>

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">Tag / Kata Kunci</label>
                <input type="text" name="tags" class="form-input" placeholder="F-16, Supersonik, Skadud 3">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
                <button type="button" class="btn btn-outline" onclick="closeUploadModal()">Batal</button>
                <button type="submit" class="btn btn-primary" style="background:#2563eb; border-color:#2563eb;">Simpan Aset</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL PREVIEW DETAIL ASET -->
<div id="viewAssetModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.75); z-index:9999; backdrop-filter:blur(6px); align-items:center; justify-content:center;">
    <div style="background:#ffffff; border-radius:16px; max-width:680px; width:92%; max-height:90vh; overflow-y:auto; padding:0; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); position:relative;">
        <button type="button" onclick="closeViewModal()" style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.6); border:none; color:#fff; width:30px; height:30px; border-radius:50%; cursor:pointer; z-index:10; font-size:16px; display:flex; align-items:center; justify-content:center;">✕</button>
        <img id="previewModalImg" src="" style="width:100%; max-height:340px; object-fit:cover; display:block;">
        <div style="padding:20px 24px;">
            <div style="display:flex; gap:8px; margin-bottom:8px;">
                <span id="previewModalBadge" class="asset-badge tempur" style="position:static;"></span>
                <span id="previewModalOrigin" style="font-size:11px; background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:6px; font-weight:600;"></span>
            </div>
            <h3 id="previewModalTitle" style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:6px;"></h3>
            <div id="previewModalUnit" style="font-size:12.5px; color:#64748b; font-weight:600; margin-bottom:12px;"></div>
            <p id="previewModalDesc" style="font-size:13px; color:#334155; line-height:1.55; margin-bottom:16px;"></p>
            <div id="previewModalTags" style="font-size:11.5px; color:#64748b; margin-bottom:16px;"></div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #f1f5f9; padding-top:14px;">
                <button type="button" class="btn btn-outline" id="previewModalCopyBtn">Salin Tautan Foto</button>
                <a href="#" id="previewModalUseBtn" class="btn btn-primary" style="background:#1e3a8a; border-color:#1e3a8a;">Gunakan di Berita Baru</a>
            </div>
        </div>
    </div>
</div>

<script>
function openUploadModal() {
    const m = document.getElementById('uploadAssetModal');
    m.style.display = 'flex';
}
function closeUploadModal() {
    document.getElementById('uploadAssetModal').style.display = 'none';
}

function viewFullAsset(asset) {
    document.getElementById('previewModalImg').src = asset.imgSrc;
    document.getElementById('previewModalTitle').textContent = asset.name;
    document.getElementById('previewModalBadge').textContent = asset.category;
    document.getElementById('previewModalOrigin').textContent = asset.origin_country || 'TNI AU';
    document.getElementById('previewModalUnit').textContent = '📍 ' + (asset.unit_assignment || 'Jajaran TNI AU');
    document.getElementById('previewModalDesc').textContent = asset.description || '';
    document.getElementById('previewModalTags').innerHTML = asset.tags ? '🏷️ <strong>Tags:</strong> ' + asset.tags : '';
    
    document.getElementById('previewModalUseBtn').href = '<?= BASE_URL ?>/news_create.php?asset_image=' + encodeURIComponent(asset.imgSrc) + '&asset_title=' + encodeURIComponent(asset.name);
    document.getElementById('previewModalCopyBtn').onclick = function() {
        copyAssetUrl(asset.imgSrc);
    };

    document.getElementById('viewAssetModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewAssetModal').style.display = 'none';
}

function copyAssetUrl(url) {
    navigator.clipboard.writeText(url).then(function() {
        alert('Tautan gambar berhasil disalin ke clipboard:\n' + url);
    });
}

// Close modals when clicking outside
window.addEventListener('click', function(e) {
    const uploadM = document.getElementById('uploadAssetModal');
    const viewM = document.getElementById('viewAssetModal');
    if (e.target === uploadM) closeUploadModal();
    if (e.target === viewM) closeViewModal();
});
</script>
</body>
</html>
