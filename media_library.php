<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();
$current = 'media_library';

$error = '';
$success = '';

// Handle Upload New Asset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_asset'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        header("Location: " . BASE_URL . "/media_library.php");
        exit;
    }

    $title    = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Kegiatan');
    $lanud    = trim($_POST['lanud'] ?? 'Mabes TNI AU');
    $tags     = trim($_POST['tags'] ?? '');

    if (!$title) {
        $error = 'Judul aset wajib diisi.';
    } elseif (empty($_FILES['asset_file']['name'])) {
        $error = 'File gambar aset wajib diunggah.';
    } else {
        $file = $_FILES['asset_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $error = 'Hanya format JPG, PNG, atau WEBP yang diperbolehkan.';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $error = 'Ukuran file aset maksimal 10MB.';
            } else {
                $filename = 'dam_' . uniqid() . '_' . time() . '.' . $ext;
                $dest = UPLOAD_DIR . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $fileSize = $file['size'];
                    $stmt = $pdo->prepare("
                        INSERT INTO digital_assets (title, category, tags, file_path, file_size, lanud, uploaded_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $category, $tags, $filename, $fileSize, $lanud, $user['id']]);
                    logAudit('UPLOAD_ASSET', "Upload aset DAM baru: '$title' ($filename)", $user['id']);
                    $success = "Aset foto berhasil ditambahkan ke Digital Asset Library.";
                } else {
                    $error = 'Gagal menyimpan file upload.';
                }
            }
        } else {
            $error = 'Terjadi kesalahan saat upload file.';
        }
    }
}

// Handle Delete Asset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_asset'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $assetId = (int)($_POST['asset_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM digital_assets WHERE id = ?");
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();
        if ($asset) {
            // Delete file
            $filePath = UPLOAD_DIR . $asset['file_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $pdo->prepare("DELETE FROM digital_assets WHERE id = ?")->execute([$assetId]);
            logAudit('DELETE_ASSET', "Menghapus aset DAM ID #$assetId", $user['id']);
            $success = "Aset berhasil dihapus dari Digital Asset Library.";
        }
    }
}

// Filter datasets
$filterCategory = $_GET['category'] ?? '';
$filterLanud    = $_GET['lanud'] ?? '';
$searchQuery    = trim($_GET['q'] ?? '');

$sql = "SELECT d.*, u.full_name as uploader_name FROM digital_assets d 
        LEFT JOIN users u ON d.uploaded_by = u.id 
        WHERE 1=1";
$params = [];

if ($filterCategory) {
    $sql .= " AND d.category = ?";
    $params[] = $filterCategory;
}
if ($filterLanud) {
    $sql .= " AND d.lanud = ?";
    $params[] = $filterLanud;
}
if ($searchQuery) {
    $sql .= " AND (d.title LIKE ? OR d.tags LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY d.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();

include_once __DIR__ . '/includes/lanud_list.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Asset Management (DAM) Media Library TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .dam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .dam-card {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .dam-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
        }
        .dam-thumb-wrap {
            position: relative;
            width: 100%;
            height: 170px;
            background: #f1f5f9;
            overflow: hidden;
        }
        .dam-thumb-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .dam-thumb-wrap:hover img {
            transform: scale(1.04);
        }
        .dam-cat-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(15, 23, 42, 0.75);
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }
        .dam-body {
            padding: 14px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .dam-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
            line-height: 1.3;
        }
        .dam-meta {
            font-size: 11px;
            color: #64748b;
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .dam-footer {
            padding: 10px 14px;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .copy-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1e293b;
            color: #fff;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 13px;
            display: none;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="dashboard-body">
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>

        <div class="page-content">
            <div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
                <div>
                    <h1 class="page-title" style="margin:0; font-size:22px; color:#1a202c;">Pusat Aset Media Digital (DAM)</h1>
                    <p style="margin:4px 0 0; color:#718096; font-size:13px;">Pustaka foto resmi dokumentasi kedinasan TNI AU beresolusi tinggi siap pakai untuk artikel berita.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" onclick="openUploadAssetModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        Upload Aset Baru
                    </button>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom:16px;"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom:16px;"><?= $success ?></div>
            <?php endif; ?>

            <!-- Filter Card -->
            <div class="card" style="padding:16px; margin-bottom:20px;">
                <form method="GET" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 120px; gap:12px; align-items:end;">
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Cari Judul atau Tag Aset</label>
                        <input type="text" name="q" class="form-input" placeholder="Contoh: Sukhoi, Danlanud, Upacara..." value="<?= e($searchQuery) ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Kategori Aset</label>
                        <select name="category" class="form-input">
                            <option value="">-- Semua Kategori --</option>
                            <option value="Kegiatan" <?= $filterCategory==='Kegiatan'?'selected':'' ?>>Kegiatan Kedinasan</option>
                            <option value="Alutsista" <?= $filterCategory==='Alutsista'?'selected':'' ?>>Alutsista & Pesawat</option>
                            <option value="Upacara" <?= $filterCategory==='Upacara'?'selected':'' ?>>Upacara & Sertijab</option>
                            <option value="Operasi & Latihan" <?= $filterCategory==='Operasi & Latihan'?'selected':'' ?>>Operasi & Latihan</option>
                            <option value="Kunjungan Kerja" <?= $filterCategory==='Kunjungan Kerja'?'selected':'' ?>>Kunjungan Kerja</option>
                            <option value="Human Interest" <?= $filterCategory==='Human Interest'?'selected':'' ?>>Human Interest dan Bakti Sosial</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Wilayah / Lanud</label>
                        <?= render_lanud_select('lanud', $filterLanud, 'class="form-input"') ?>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Filter Aset</button>
                    </div>
                </form>
            </div>

            <!-- Assets Grid -->
            <?php if (empty($assets)): ?>
                <div class="card" style="text-align:center; padding:50px 20px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:12px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <p style="color:#64748b; font-size:14px; margin:0;">Belum ada aset media digital yang ditemukan. Klik tombol <strong>Upload Aset Baru</strong> untuk menambahkan koleksi foto resolusi tinggi.</p>
                </div>
            <?php else: ?>
                <div class="dam-grid">
                    <?php foreach ($assets as $ast): 
                        $imgUrl = BASE_URL . '/uploads/' . $ast['file_path'];
                        $kb = round(($ast['file_size'] ?? 0) / 1024);
                    ?>
                        <div class="dam-card">
                            <div class="dam-thumb-wrap">
                                <span class="dam-cat-badge"><?= e($ast['category']) ?></span>
                                <img src="<?= $imgUrl ?>" alt="<?= e($ast['title']) ?>" loading="lazy">
                            </div>
                            <div class="dam-body">
                                <div class="dam-title"><?= e($ast['title']) ?></div>
                                <div class="dam-meta">
                                    <span>
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <?= e($ast['lanud'] ?? 'Mabes TNI AU') ?></span>
                                    <span>
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <?= e($ast['uploader_name'] ?? 'Penerangan') ?> &bull; <?= date('d M Y', strtotime($ast['created_at'])) ?></span>
                                    <?php if ($kb > 0): ?><span>
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px"><rect x="2" y="2" width="20" height="20" rx="2" ry="2"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="12" y1="8" x2="12" y2="16"/></svg>
                                        <?= $kb ?> KB</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="dam-footer">
                                <button type="button" class="btn btn-outline" style="font-size:11px; padding:4px 8px;" onclick="copyAssetUrl('<?= $imgUrl ?>')">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    Salin URL
                                </button>
                                <a href="<?= $imgUrl ?>" target="_blank" download class="btn btn-outline" style="font-size:11px; padding:4px 8px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Download
                                </a>
                                <?php if ($user['role'] === 'E' || $user['id'] == $ast['uploaded_by']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus aset foto ini?')">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="delete_asset" value="1">
                                        <input type="hidden" name="asset_id" value="<?= $ast['id'] ?>">
                                        <button type="submit" class="btn btn-outline" style="font-size:11px; padding:4px 8px; color:#C0392B;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- TOAST ALERT -->
<div id="copyToast" class="copy-toast">URL aset foto berhasil disalin ke clipboard!</div>

<!-- MODAL UPLOAD ASSET -->
<div id="uploadAssetModal" class="modal-form" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-card" style="background:#fff; border-radius:8px; max-width:520px; width:90%; padding:24px;">
        <h3 style="margin-top:0; margin-bottom:16px;">Upload Aset Foto Digital (DAM)</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="upload_asset" value="1">
            
            <div class="form-group">
                <label>Judul & Keterangan Foto</label>
                <input type="text" name="title" class="form-input" required placeholder="Contoh: Pesawat Tempur F-16 Siap Takeoff di Runway Lanud Iswahjudi">
            </div>

            <div class="form-group">
                <label>Kategori Aset</label>
                <select name="category" class="form-input" required>
                    <option value="Kegiatan">Kegiatan Kedinasan</option>
                    <option value="Alutsista">Alutsista & Pesawat</option>
                    <option value="Upacara">Upacara & Sertijab</option>
                    <option value="Operasi & Latihan">Operasi & Latihan</option>
                    <option value="Kunjungan Kerja">Kunjungan Kerja</option>
                    <option value="Human Interest">Human Interest & Bakti Sosial</option>
                </select>
            </div>

            <div class="form-group">
                <label>Satuan & Lanud</label>
                <?= render_lanud_select('lanud', $user['lanud'] ?? 'Lanud Atang Sendjaja', 'class="form-input"') ?>
            </div>

            <div class="form-group">
                <label>Tag & Kata Kunci (Dipisahkan Koma)</label>
                <input type="text" name="tags" class="form-input" placeholder="Contoh: f16, latihan tempur, penerbang">
            </div>

            <div class="form-group">
                <label>File Foto Resolusi Tinggi (JPG/PNG/WEBP, Maks 10MB)</label>
                <input type="file" name="asset_file" class="form-input" accept="image/*" required>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('uploadAssetModal').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Unggah Aset</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUploadAssetModal() {
    document.getElementById('uploadAssetModal').style.display = 'flex';
}
function copyAssetUrl(url) {
    navigator.clipboard.writeText(url).then(function() {
        var toast = document.getElementById('copyToast');
        toast.style.display = 'block';
        setTimeout(function() {
            toast.style.display = 'none';
        }, 2500);
    });
}
</script>
</body>
</html>
