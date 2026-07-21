<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$current = 'list';
$user = currentUser();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: " . BASE_URL . "/news_list.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
$stmt->execute([$id]);
$news = $stmt->fetch();

if (!$news) {
    die("Berita tidak ditemukan.");
}

if ($user['role'] !== 'A') {
    die("Anda tidak memiliki akses untuk mengedit berita ini.");
}

if (!in_array($news['status'], ['draft', 'pending_b', 'revision_b', 'revision_c'])) {
    die("Berita yang sedang di-review atau sudah diterbitkan tidak dapat diedit.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_news'])) {
    $title          = trim($_POST['title'] ?? '');
    $content        = trim($_POST['content'] ?? '');
    $sentiment      = $_POST['sentiment'] ?? 'Netral';
    $priority       = $_POST['priority'] ?? 'Medium';
    $classification = trim($_POST['classification'] ?? '');
    $wilayah        = trim($_POST['wilayah'] ?? '');
    $tempat         = trim($_POST['tempat'] ?? '');
    $media          = trim($_POST['media'] ?? '');
    $aktor          = trim($_POST['aktor'] ?? '');
    $tag            = trim($_POST['tag'] ?? '');
    $topik          = trim($_POST['topik'] ?? '');
    $keyword        = trim($_POST['keyword'] ?? '');
    $published_at   = !empty($_POST['published_at']) ? $_POST['published_at'] : null;

    if (!$title || !$content) {
        $error = "Judul dan isi berita wajib diisi.";
    } else {
        try {
            $imagePath = $news['image_path'] ?? null;
            if (!empty($_FILES['image']['name'])) {
                $imagePath = uploadNewsImage('image');
            }

            $stmt = $pdo->prepare("
                UPDATE news SET 
                    title=?, content=?, image_path=?, sentiment=?, priority=?, 
                    classification=?, wilayah=?, tempat=?, media=?, aktor=?, 
                    tag=?, topik=?, keyword=?, published_at=?
                WHERE id=?
            ");
            $stmt->execute([
                $title, $content, $imagePath, $sentiment, $priority,
                $classification, $wilayah, $tempat, $media, $aktor,
                $tag, $topik, $keyword, $published_at, $id
            ]);

            if (!empty($_FILES['gallery']['name'][0])) {
                foreach ($_FILES['gallery']['tmp_name'] as $i => $tmpName) {
                    if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['gallery']['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                            $fname = 'gallery_' . uniqid() . '.' . $ext;
                            move_uploaded_file($tmpName, UPLOAD_DIR . $fname);
                            $pdo->prepare("INSERT INTO news_images (news_id, image_path) VALUES (?, ?)")
                                ->execute([$id, $fname]);
                        }
                    }
                }
            }

            $success = "Berita berhasil diperbarui.";
            $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
            $stmt->execute([$id]);
            $news = $stmt->fetch();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_gallery_id'])) {
    $gid = $_POST['delete_gallery_id'];
    $gstmt = $pdo->prepare("SELECT image_path FROM news_images WHERE id=? AND news_id=?");
    $gstmt->execute([$gid, $id]);
    $gRow = $gstmt->fetch();
    if ($gRow) {
        @unlink(UPLOAD_DIR . $gRow['image_path']);
        $pdo->prepare("DELETE FROM news_images WHERE id=?")->execute([$gid]);
    }
    header("Location: " . BASE_URL . "/news_edit.php?id=$id");
    exit;
}

$images = $pdo->prepare("SELECT * FROM news_images WHERE news_id = ?");
$images->execute([$id]);
$gallery = $images->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Berita — Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .edit-layout-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            margin-top: 10px;
        }
        
        .main-edit-area {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
        }

        .edit-header-row {
            margin-bottom: 24px;
        }
        .edit-header-row h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 12px;
        }
        .meta-info-row {
            display: flex;
            align-items: center;
            gap: 16px;
            color: var(--text-sec);
            font-size: 12px;
        }
        .meta-info-row span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .horizontal-group {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            gap: 16px;
        }
        .horizontal-group label {
            width: 130px;
            font-size: 13px;
            color: var(--text-sec);
            font-weight: 500;
            flex-shrink: 0;
        }
        .horizontal-group .input-wrapper {
            flex: 1;
        }
        .horizontal-group .form-input {
            width: 100%;
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 0 12px;
            font-size: 13.5px;
            color: var(--text);
            transition: border-color var(--transition);
        }
        .horizontal-group .form-input:focus {
            outline: none;
            border-color: var(--blue);
        }

        .content-tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
            margin-top: 24px;
        }
        .content-tab {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-sec);
            border-bottom: 2px solid transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .content-tab.active {
            color: var(--text);
            border-bottom-color: var(--blue);
        }

        .rich-editor-container {
            border: 1px solid #ced4da;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .rich-editor-toolbar {
            display: flex;
            align-items: center;
            gap: 2px;
            padding: 6px;
            background: #f8f9fa;
            border-bottom: 1px solid #ced4da;
            flex-wrap: wrap;
        }
        .rich-editor-btn {
            background: transparent;
            border: none;
            padding: 6px 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .rich-editor-btn:hover {
            background: #e2e6ea;
        }
        .toolbar-divider {
            width: 1px;
            height: 20px;
            background: #ced4da;
            margin: 0 6px;
        }
        .rich-editor-select {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
            background: #fff;
            cursor: pointer;
        }
        .rich-editor-body {
            min-height: 300px;
            padding: 16px;
            font-size: 14px;
            line-height: 1.7;
            color: #333;
            outline: none;
            overflow-y: auto;
        }

        .gallery-grid-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .gallery-thumb {
            width: 180px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 4px;
            background: #fff;
            position: relative;
        }
        .gallery-thumb img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 2px;
            margin-bottom: 6px;
        }
        .gallery-thumb-info {
            font-size: 10.5px;
            color: var(--text-sec);
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .gallery-add-box {
            width: 180px;
            height: 140px;
            border: 2px dashed #ced4da;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--text-sec);
            font-size: 11.5px;
            font-weight: 500;
            cursor: pointer;
            padding: 16px;
        }
        .gallery-add-box:hover {
            background: #f8f9fa;
            border-color: var(--blue);
            color: var(--blue);
        }

        /* Sidebar info area */
        .sidebar-edit-area {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .sidebar-info-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
        }
        .sidebar-info-header {
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            color: var(--text);
        }
        .sidebar-select {
            width: 100%;
            height: 36px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 0 10px;
            font-size: 13px;
            margin-bottom: 16px;
            background: #fff;
        }
        .sidebar-label {
            font-size: 12px;
            color: var(--text-sec);
            margin-bottom: 8px;
            display: block;
        }
        .sidebar-radio-group {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        .sidebar-radio {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            color: var(--text);
            cursor: pointer;
        }
        .sidebar-radio input[type="radio"] {
            accent-color: var(--blue);
            width: 14px;
            height: 14px;
        }

        .chip-input-container {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 8px;
            margin-bottom: 16px;
            background: #fff;
        }
        .chip-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eaf2f8;
            color: var(--blue);
            font-size: 11.5px;
            padding: 4px 8px;
            border-radius: 4px;
            margin-bottom: 6px;
            border: 1px solid rgba(41, 128, 185, 0.2);
            word-break: break-word;
        }
        .chip-item .chip-close {
            cursor: pointer;
            font-weight: bold;
            opacity: 0.6;
        }
        .chip-item .chip-close:hover {
            opacity: 1;
        }
        .chip-input-field {
            border: none;
            outline: none;
            font-size: 12px;
            width: 100%;
            background: transparent;
            color: var(--text-sec);
        }

        .btn-save-blue {
            background: #4A89DC;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .btn-save-blue:hover {
            background: #3b6eb0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 137, 220, 0.3);
        }
        
        /* Smooth animations */
        .form-input, .sidebar-select, .chip-input-container {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .form-input:focus, .sidebar-select:focus, .chip-input-container:focus-within {
            border-color: #4A89DC;
            box-shadow: 0 0 0 3px rgba(74, 137, 220, 0.15);
            transform: translateY(-1px);
        }
        .chip-item {
            transition: all 0.3s ease;
        }
        .chip-item:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 6px rgba(41, 128, 185, 0.15);
        }
        .rich-editor-btn {
            transition: all 0.2s ease;
        }
        .rich-editor-btn:hover {
            background: #e2e6ea;
            transform: translateY(-1px);
        }
        .gallery-add-box {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .gallery-add-box:hover {
            background: #f0f4f8;
            border-color: #4A89DC;
            color: #4A89DC;
            transform: scale(1.02);
        }
        .gallery-thumb {
            transition: all 0.3s ease;
        }
        .gallery-thumb:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- TOP NAVBAR MATCHING SCREENSHOT -->
        <div class="top-navbar" style="height:56px">
            <div class="top-navbar-left">
                <button class="hamburger-btn" title="Toggle Menu">&#9776;</button>
                <div class="media-tabs">
                    <span class="media-tab-item active" style="color: #4A89DC; border-bottom: 2px solid #4A89DC;"><span class="icon" style="margin-right:5px">📰</span>Berita Wilayah</span>
                    <span class="media-tab-item text-muted">Media Online</span>
                    <span class="media-tab-item text-muted">Media Sosial</span>
                    <span class="media-tab-item text-muted">Semua Sumber</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <span class="top-action-btn">📅 Hari Ini <span>▼</span></span>
                <span class="top-action-btn">⚙️ Filter</span>
                <span class="top-action-btn" style="border:none;background:transparent;color:var(--text-sec)"><?= e(explode(' ',$user['full_name'])[0]) ?> <span>➔</span></span>
            </div>
        </div>

        <!-- WORKSPACE TABS MATCHING SCREENSHOT -->
        <div class="workspace-tabs-row" style="padding-top:10px;background:#fff;border-bottom:1px solid #ced4da;">
            <div class="workspace-tab text-muted" style="background:transparent;border:none;border-right:1px solid #eee;">Berita Wilayah <span class="close-tab" style="margin-left:8px;opacity:0.5;">×</span></div>
            <div class="workspace-tab text-muted" style="background:transparent;border:none;border-right:1px solid #eee;">Penerban... <span class="close-tab" style="margin-left:8px;opacity:0.5;">×</span></div>
            <div class="workspace-tab active" style="border:none;border-bottom:2px solid transparent;background:#fff;color:var(--text);font-weight:600;">Edit - Pen... <span class="close-tab" style="margin-left:8px;">×</span></div>
        </div>


        <div class="page-container" style="padding:24px 32px">
            
            <?php if ($error): ?>
                <div style="background:#fceae8;color:#c0392b;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid rgba(192,57,43,.15)">Peringatan: <?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="background:#eafaf1;color:#27ae60;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid rgba(39,174,96,.15)">Berhasil: <?= e($success) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="update_news" value="1">
                <input type="hidden" name="media" value="<?= e($news['media']) ?>"> <!-- Hidden for media if not in sidebar -->

                <div class="edit-layout-grid">
                    
                    <!-- MAIN LEFT COLUMN -->
                    <div class="main-edit-area">
                        <div class="edit-header-row">
                            <h2>Edit Berita</h2>
                            <div class="meta-info-row">
                                <span><?= $news['published_at'] ? date('d-m-Y H:i', strtotime($news['published_at'])) : date('d-m-Y H:i', strtotime($news['created_at'])) ?></span>
                                <span><?= e($news['wilayah']) ?></span>
                                <span><?= e($news['author_label'] ?? $news['author_name'] ?? 'pen ats') ?></span>
                            </div>
                        </div>

                        <div class="horizontal-group">
                            <label>Tanggal Terbit</label>
                            <div class="input-wrapper">
                                <input type="datetime-local" name="published_at" class="form-input" value="<?= $news['published_at'] ? date('Y-m-d\TH:i', strtotime($news['published_at'])) : date('Y-m-d\TH:i', strtotime($news['created_at'])) ?>">
                            </div>
                        </div>

                        <div class="horizontal-group">
                            <label>Judul</label>
                            <div class="input-wrapper">
                                <input type="text" name="title" class="form-input" value="<?= e($news['title']) ?>" required>
                            </div>
                        </div>

                        <div class="horizontal-group">
                            <label>Tempat</label>
                            <div class="input-wrapper">
                                <input type="text" name="tempat" class="form-input" value="<?= e($news['tempat']) ?>">
                            </div>
                        </div>

                        <div class="content-tabs">
                            <div class="content-tab active">Isi</div>
                            <div class="content-tab">Tempat</div>
                        </div>

                        <div class="rich-editor-container">
                            <div class="rich-editor-toolbar">
                                <button type="button" class="rich-editor-btn" onclick="fmt('bold')"><strong>B</strong></button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('italic')"><em>I</em></button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('underline')"><u>U</u></button>
                                <div class="toolbar-divider"></div>
                                <select class="rich-editor-select" id="fontFamilySelect" onchange="applyFont(this.value)" title="Font">
                                    <option value="Poppins">Poppins</option>
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Verdana">Verdana</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Courier New">Courier New</option>
                                    <option value="Trebuchet MS">Trebuchet MS</option>
                                    <option value="Tahoma">Tahoma</option>
                                    <option value="Impact">Impact</option>
                                    <option value="Comic Sans MS">Comic Sans MS</option>
                                    <option value="Palatino">Palatino</option>
                                    <option value="Garamond">Garamond</option>
                                </select>
                                <select class="rich-editor-select" onchange="fmt('fontSize', this.value)" title="Ukuran Font">
                                    <option value="1">8</option>
                                    <option value="2">10</option>
                                    <option value="3" selected>12</option>
                                    <option value="4">14</option>
                                    <option value="5">18</option>
                                    <option value="6">24</option>
                                    <option value="7">36</option>
                                </select>
                                <div class="toolbar-divider"></div>
                                <input type="color" id="txtColorPicker" title="Warna Teks" style="width:28px;height:28px;border:1px solid #ced4da;border-radius:4px;padding:2px;cursor:pointer;background:#fff" onchange="fmt('foreColor', this.value)">
                                <button type="button" class="rich-editor-btn" onclick="insertLink()" title="Sisipkan Link">🔗 Link</button>
                                <button type="button" class="rich-editor-btn" onclick="document.getElementById('editorImageInput').click()" title="Sisipkan Gambar">🖼️ Gambar</button>
                                <input type="file" id="editorImageInput" accept="image/*" hidden onchange="insertImageFile(this)">
                                <div class="toolbar-divider"></div>
                                <button type="button" class="rich-editor-btn" onclick="fmt('insertUnorderedList')" title="Bullet List">• Daftar</button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('insertOrderedList')" title="Numbered List">1. Urutan</button>
                                <button type="button" class="rich-editor-btn" onclick="insertTable()" title="Sisipkan Tabel">⊞ Tabel</button>
                                <div class="toolbar-divider"></div>
                                <button type="button" class="rich-editor-btn" onclick="fmt('justifyLeft')" title="Kiri">⬅</button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('justifyCenter')" title="Tengah">≡</button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('justifyRight')" title="Kanan">➡</button>
                            </div>
                            <div class="rich-editor-body" id="editorBody" contenteditable="true"><?= $news['content'] ?></div>
                            <textarea name="content" id="hiddenContent" hidden></textarea>
                        </div>

                        <div class="gallery-grid-row">
                            <div class="gallery-thumb" style="width:100%;max-width:360px;padding:14px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                                <?php if (!empty($news['image_path'])): ?>
                                    <img src="<?= UPLOAD_URL . e($news['image_path']) ?>" alt="Cover" style="width:100%;height:180px;object-fit:cover;border-radius:4px;margin-bottom:12px;">
                                <?php else: ?>
                                    <div style="width:100%;height:180px;border:1px dashed #ced4da;border-radius:4px;display:flex;align-items:center;justify-content:center;color:var(--text-sec);margin-bottom:12px;">
                                        Tidak ada gambar cover saat ini
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline" onclick="document.getElementById('imageInput').click()">📷 Unggah Gambar Cover</button>
                            </div>

                            <?php foreach($gallery as $img): ?>
                                <div class="gallery-thumb">
                                    <img src="<?= UPLOAD_URL . e($img['image_path']) ?>" alt="galeri">
                                    <div class="gallery-thumb-info"><?= e($img['image_path']) ?><br>332 KB</div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="gallery-add-box" onclick="document.getElementById('galleryInput').click()">
                                <span style="font-size:24px;font-weight:300;margin-bottom:8px">+</span>
                                Seret Dan Lepas atau Klik Untuk Menambahkan File
                            </div>
                        </div>
                        <input type="file" id="imageInput" name="image" accept="image/*" hidden>
                        <input type="file" id="galleryInput" name="gallery[]" accept="image/*" multiple hidden>

                    </div>

                    <!-- RIGHT SIDEBAR (INFO & METADATA) -->
                    <div class="sidebar-edit-area">
                        <div class="sidebar-info-card">
                            <div class="sidebar-info-header">
                                <span>ⓘ Info</span>
                            </div>

                            <select name="classification" class="sidebar-select">
                                <option value="<?= e($news['classification']) ?>"><?= e($news['classification']) ?></option>
                                <option value="9. Tni au" <?= $news['classification']==='9. Tni au'?'selected':'' ?>>9. Tni au</option>
                            </select>

                            <select name="wilayah" class="sidebar-select">
                                <option value="<?= e($news['wilayah']) ?>"><?= e($news['wilayah']) ?></option>
                                <option value="Lanud Atang Sendjaja" <?= $news['wilayah']==='Lanud Atang Sendjaja'?'selected':'' ?>>Lanud Atang Sendjaja</option>
                                <option value="Lanud Halim Perdanakusuma" <?= $news['wilayah']==='Lanud Halim Perdanakusuma'?'selected':'' ?>>Lanud Halim Perdanakusuma</option>
                            </select>

                            <label class="sidebar-label">Sentimen</label>
                            <div class="sidebar-radio-group">
                                <label class="sidebar-radio"><input type="radio" name="sentiment" value="Positif" <?= $news['sentiment']==='Positif'?'checked':'' ?>> Positif</label>
                                <label class="sidebar-radio"><input type="radio" name="sentiment" value="Negatif" <?= $news['sentiment']==='Negatif'?'checked':'' ?>> Negatif</label>
                                <label class="sidebar-radio"><input type="radio" name="sentiment" value="Netral" <?= $news['sentiment']==='Netral'?'checked':'' ?>> Netral</label>
                            </div>

                            <label class="sidebar-label">Prioritas</label>
                            <div class="sidebar-radio-group">
                                <label class="sidebar-radio"><input type="radio" name="priority" value="High" <?= $news['priority']==='High'?'checked':'' ?>> High</label>
                                <label class="sidebar-radio"><input type="radio" name="priority" value="Medium" <?= $news['priority']==='Medium'?'checked':'' ?>> Medium</label>
                                <label class="sidebar-radio"><input type="radio" name="priority" value="Low" <?= $news['priority']==='Low'?'checked':'' ?>> Low</label>
                            </div>

                            <input type="text" class="sidebar-select" value="<?= e($news['author_label'] ?? 'PEN ATS') ?>" readonly>

                            <!-- CHIP INPUTS -->
                            <div class="chip-input-container">
                                <?php if(!empty($news['aktor'])): ?>
                                    <div class="chip-item">
                                        <?= e($news['aktor']) ?> <span class="chip-close">×</span>
                                    </div>
                                <?php endif; ?>
                                <input type="text" name="aktor" class="chip-input-field" placeholder="Aktor" value="<?= e($news['aktor'] ?? '') ?>">
                            </div>

                            <div class="chip-input-container">
                                <?php if(!empty($news['tag'])): ?>
                                    <div class="chip-item">
                                        <?= e($news['tag']) ?> <span class="chip-close">×</span>
                                    </div>
                                <?php endif; ?>
                                <input type="text" name="tag" class="chip-input-field" placeholder="Tag" value="<?= e($news['tag'] ?? '') ?>">
                            </div>

                            <div class="chip-input-container">
                                <?php if(!empty($news['topik'])): ?>
                                    <div class="chip-item">
                                        <?= e($news['topik']) ?> <span class="chip-close">×</span>
                                    </div>
                                <?php endif; ?>
                                <input type="text" name="topik" class="chip-input-field" placeholder="Topik" value="<?= e($news['topik'] ?? '') ?>">
                            </div>

                            <div class="chip-input-container">
                                <?php if(!empty($news['keyword'])): ?>
                                    <div class="chip-item">
                                        <?= e($news['keyword']) ?> <span class="chip-close">×</span>
                                    </div>
                                <?php endif; ?>
                                <input type="text" name="keyword" class="chip-input-field" placeholder="Keyword" value="<?= e($news['keyword'] ?? '') ?>">
                            </div>

                            <button type="submit" class="btn-save-blue" onclick="prepareSubmit()">
                                💾 Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
        </div>
    </main>
</div>

<script>
function fmt(cmd, val) {
    document.getElementById('editorBody').focus();
    document.execCommand(cmd, false, val !== undefined ? val : null);
}

function applyFont(font) {
    document.getElementById('editorBody').focus();
    document.execCommand('fontName', false, font);
}

function insertLink() {
    var url = prompt('Masukkan URL link:', 'https://');
    if (url) {
        document.getElementById('editorBody').focus();
        document.execCommand('createLink', false, url);
    }
}

function insertTable() {
    var rows = parseInt(prompt('Jumlah baris:', '3'));
    var cols = parseInt(prompt('Jumlah kolom:', '3'));
    if (!rows || !cols || rows < 1 || cols < 1) return;
    var html = '<table border="1" style="border-collapse:collapse;width:100%;margin:8px 0">';
    for (var r = 0; r < rows; r++) {
        html += '<tr>';
        for (var c = 0; c < cols; c++) {
            html += '<td style="border:1px solid #ced4da;padding:8px;min-width:60px">&nbsp;</td>';
        }
        html += '</tr>';
    }
    html += '</table><br>';
    document.getElementById('editorBody').focus();
    document.execCommand('insertHTML', false, html);
}

function insertImageFile(input) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('editorBody').focus();
        document.execCommand('insertImage', false, e.target.result);
    };
    reader.readAsDataURL(file);
    input.value = '';
}

function prepareSubmit() {
    var editor = document.getElementById('editorBody');
    var hidden = document.getElementById('hiddenContent');
    if (editor && hidden) {
        hidden.value = editor.innerHTML;
    }
    return true;
}
document.addEventListener('DOMContentLoaded', function() {
    var editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            prepareSubmit();
        });
    }
});
</script>
</body>
</html>
