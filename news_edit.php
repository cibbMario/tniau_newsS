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

$isAuthorA = ($user['role'] === 'A' && $news['created_by'] == $user['id']);
$isUserD   = ($user['role'] === 'D');
$isUserE   = ($user['role'] === 'E');

if (!$isAuthorA && !$isUserD && !$isUserE) {
    die("Anda tidak memiliki akses untuk mengedit berita ini.");
}

if (!in_array($news['status'], ['draft', 'pending_b', 'pending_c', 'pending_d', 'revision_b', 'revision_c', 'revision_d', 'published'])) {
    die("Berita ini tidak dapat diedit.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {    
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        header("Location: " . BASE_URL . "/news_list.php");
        exit;
    }
}

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

            if ($isAuthorA && !empty($_POST['resubmit_after_edit'])) {
                $curStat = $news['status'];
                if ($curStat === 'revision_b') {
                    updateNewsStatus($id, 'pending_b', $user['id'], 'Reporter telah menyelesaikan revisi dan mengirim ulang ke Editor');
                    $editorStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'B' AND lanud = ?");
                    $editorStmt->execute([$news['wilayah']]);
                    foreach ($editorStmt->fetchAll() as $uTarget) {
                        sendNotification($id, $uTarget['id'], "Berita \"$title\" telah direvisi oleh Reporter dan memerlukan review ulang Editor.");
                    }
                    $success = "Berita berhasil diperbarui dan dikirim ulang ke Editor.";
                } elseif ($curStat === 'revision_c') {
                    updateNewsStatus($id, 'pending_c', $user['id'], 'Reporter telah menyelesaikan revisi dan mengirim ulang ke Penyetuju');
                    foreach ($pdo->query("SELECT id FROM users WHERE role = 'C'")->fetchAll() as $uTarget) {
                        sendNotification($id, $uTarget['id'], "Berita \"$title\" telah direvisi oleh Reporter dan memerlukan persetujuan ulang Penyetuju.");
                    }
                    $success = "Berita berhasil diperbarui dan dikirim ulang ke Penyetuju.";
                } elseif ($curStat === 'revision_d') {
                    updateNewsStatus($id, 'pending_d', $user['id'], 'Reporter telah menyelesaikan revisi dan mengirim ulang ke Peninjau Kejelasan');
                    foreach ($pdo->query("SELECT id FROM users WHERE role = 'D'")->fetchAll() as $uTarget) {
                        sendNotification($id, $uTarget['id'], "Berita \"$title\" telah direvisi oleh Reporter dan memerlukan persetujuan ulang Peninjau Kejelasan.");
                    }
                    $success = "Berita berhasil diperbarui dan dikirim ulang ke Peninjau Kejelasan.";
                } elseif ($curStat === 'draft') {
                    updateNewsStatus($id, 'pending_b', $user['id'], 'Reporter telah menyelesaikan berita draft dan mengirim ke Editor untuk direview');
                    $editorStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'B' AND lanud = ?");
                    $editorStmt->execute([$news['wilayah']]);
                    foreach ($editorStmt->fetchAll() as $uTarget) {
                        sendNotification($id, $uTarget['id'], "Berita baru \"$title\" telah selesai dibuat dan dikirim oleh Reporter untuk direview.");
                    }
                    $_SESSION['flash_success'] = "Berita \"$title\" berhasil dikirim ke Editor untuk direview.";
                    header("Location: " . BASE_URL . "/news_list.php");
                    exit;
                }
            }

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
    <title>Edit Berita Portal Berita TNI AU</title>
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
        .btn-selesai {
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }
        .btn-selesai::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        .btn-selesai:hover::before {
            left: 100%;
        }
        .btn-selesai:hover {
            background: linear-gradient(135deg, #1e8449, #145a32) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.45) !important;
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
                <button class="hamburger-btn" title="Toggle Menu" aria-label="Toggle menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="media-tabs">
                    <span class="media-tab-item active" style="color: #4A89DC; border-bottom: 2px solid #4A89DC;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px; vertical-align:text-bottom;"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16l-2 2z"></path><line x1="14" y1="8" x2="18" y2="8"></line><line x1="14" y1="12" x2="18" y2="12"></line><line x1="10" y1="16" x2="18" y2="16"></line></svg>
                        Berita Wilayah
                    </span>
                    <span class="media-tab-item text-muted">Media Online</span>
                    <span class="media-tab-item text-muted">Media Sosial</span>
                    <span class="media-tab-item text-muted">Semua Sumber</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <span class="top-action-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Hari Ini <span>▼</span>
                </span>
                <span class="top-action-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Filter
                </span>
                <span class="top-action-btn" style="border:none;background:transparent;color:var(--text-sec);display:inline-flex;align-items:center;gap:4px;"><?= e(explode(' ',$user['full_name'])[0]) ?> <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></span>
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
                <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                <input type="hidden" name="update_news" value="1">

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

                        <div class="content-tabs">
                            <div class="content-tab active" id="tab-btn-isi" onclick="switchInnerTab('isi')">Isi</div>
                            <div class="content-tab" id="tab-btn-tempat" onclick="switchInnerTab('tempat')">Tempat</div>
                        </div>

                        <div id="pane-isi" style="display:block;">
                            <div class="rich-editor-container">
                            <div class="rich-editor-toolbar" style="position:relative; flex-wrap:wrap; gap:4px;">
                                <!-- Formatting Basics -->
                                <button type="button" class="rich-editor-btn" onclick="fmt('bold')" title="Bold"><strong>B</strong></button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('italic')" title="Italic"><em>I</em></button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('underline')" title="Underline"><u>U</u></button>
                                
                                <!-- Eraser / Remove Font Style (CTRL+\) -->
                                <button type="button" class="rich-editor-btn" onclick="removeFontStyle()" title="Remove Font Style (CTRL+\)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 21-4-4 8-8 4 4-8 8Z"/><path d="M11 7 15 3l6 6-4 4"/><path d="M2 21h18"/></svg>
                                </button>

                                <div class="toolbar-divider"></div>

                                <!-- Font Family Dropdown -->
                                <select class="rich-editor-select" id="fontFamilySelect" onchange="applyFont(this.value)" title="Font Family">
                                    <option value="Arial" style="font-family:Arial">Arial</option>
                                    <option value="Arial Black" style="font-family:'Arial Black'">Arial Black</option>
                                    <option value="Comic Sans MS" style="font-family:'Comic Sans MS'">Comic Sans MS</option>
                                    <option value="Courier New" style="font-family:'Courier New'">Courier New</option>
                                    <option value="Helvetica" style="font-family:Helvetica">Helvetica</option>
                                    <option value="Impact" style="font-family:Impact">Impact</option>
                                    <option value="Tahoma" style="font-family:Tahoma">Tahoma</option>
                                    <option value="Times New Roman" style="font-family:'Times New Roman'">Times New Roman</option>
                                    <option value="Verdana" style="font-family:Verdana">Verdana</option>
                                    <option value="Inter" style="font-family:Inter">Inter</option>
                                </select>

                                <!-- Font Size Dropdown -->
                                <select class="rich-editor-select" onchange="fmt('fontSize', this.value)" title="Font Size">
                                    <option value="1">8</option>
                                    <option value="2">10</option>
                                    <option value="3" selected>12</option>
                                    <option value="4">14</option>
                                    <option value="5">18</option>
                                    <option value="6">24</option>
                                    <option value="7">36</option>
                                </select>

                                <!-- Text & Background Color Picker Modal Trigger -->
                                <div class="rte-popover-container" style="position:relative; display:inline-block;">
                                    <button type="button" class="rich-editor-btn" onclick="toggleColorPickerModal()" title="Color Picker (Text & Background)" style="font-weight:bold; display:inline-flex; align-items:center; gap:3px;">
                                        <span style="position:relative; display:inline-block; border-bottom:3px solid #ffff00; padding:0 2px;">A</span>
                                        <span style="font-size:9px;">▼</span>
                                    </button>

                                    <!-- Color Picker Popover Modal -->
                                    <div class="rte-popover" id="rteColorPopover">
                                        <div class="rte-color-tabs">
                                            <button type="button" class="rte-color-tab active" id="tabBgBtn" onclick="switchColorTab('bg')">Background Color</button>
                                            <button type="button" class="rte-color-tab" id="tabTextBtn" onclick="switchColorTab('text')">Text Color</button>
                                        </div>

                                        <!-- Action button depending on active tab -->
                                        <button type="button" class="rte-color-action-btn" id="actionBtnBg" onclick="applyColorAction('transparent')">Transparent</button>
                                        <button type="button" class="rte-color-action-btn" id="actionBtnText" onclick="applyColorAction('resetText')" style="display:none;">Reset to default</button>

                                        <!-- Preset Grid (8x5) -->
                                        <div class="rte-color-grid">
                                            <!-- Row 1 -->
                                            <button type="button" class="rte-color-swatch" style="background:#000000" onclick="selectPresetColor('#000000')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#434343" onclick="selectPresetColor('#434343')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#666666" onclick="selectPresetColor('#666666')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#999999" onclick="selectPresetColor('#999999')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#b7b7b7" onclick="selectPresetColor('#b7b7b7')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#cccccc" onclick="selectPresetColor('#cccccc')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#d9d9d9" onclick="selectPresetColor('#d9d9d9')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#ffffff" onclick="selectPresetColor('#ffffff')"></button>
                                            <!-- Row 2 -->
                                            <button type="button" class="rte-color-swatch" style="background:#980000" onclick="selectPresetColor('#980000')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#ff0000" onclick="selectPresetColor('#ff0000')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#ff9900" onclick="selectPresetColor('#ff9900')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#ffff00" onclick="selectPresetColor('#ffff00')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#00ff00" onclick="selectPresetColor('#00ff00')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#00ffff" onclick="selectPresetColor('#00ffff')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#4a86e8" onclick="selectPresetColor('#4a86e8')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#0000ff" onclick="selectPresetColor('#0000ff')"></button>
                                            <!-- Row 3 -->
                                            <button type="button" class="rte-color-swatch" style="background:#9900ff" onclick="selectPresetColor('#9900ff')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#ff00ff" onclick="selectPresetColor('#ff00ff')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#e6b8af" onclick="selectPresetColor('#e6b8af')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#fce5cd" onclick="selectPresetColor('#fce5cd')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#fff2cc" onclick="selectPresetColor('#fff2cc')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#d9ead3" onclick="selectPresetColor('#d9ead3')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#d0e0e3" onclick="selectPresetColor('#d0e0e3')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#c9daf8" onclick="selectPresetColor('#c9daf8')"></button>
                                            <!-- Row 4 -->
                                            <button type="button" class="rte-color-swatch" style="background:#cfe2f3" onclick="selectPresetColor('#cfe2f3')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#d9d2e9" onclick="selectPresetColor('#d9d2e9')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#ead1dc" onclick="selectPresetColor('#ead1dc')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#dd7e6b" onclick="selectPresetColor('#dd7e6b')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#f6b26b" onclick="selectPresetColor('#f6b26b')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#ffd966" onclick="selectPresetColor('#ffd966')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#93c47d" onclick="selectPresetColor('#93c47d')"></button>
                                            <button type="button" class="rte-color-swatch" style="background:#76a5af" onclick="selectPresetColor('#76a5af')"></button>
                                        </div>

                                        <!-- Custom Color Trigger -->
                                        <button type="button" class="rte-color-action-btn" onclick="triggerCustomColorPicker()">Select</button>
                                        <input type="color" id="rteCustomColorInput" hidden onchange="applyCustomColor(this.value)">
                                    </div>
                                </div>

                                <div class="toolbar-divider"></div>

                                <!-- Link & Image Buttons -->
                                <button type="button" class="rich-editor-btn" onclick="insertLink()" title="Sisipkan Link"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg></button>
                                <button type="button" class="rich-editor-btn" onclick="document.getElementById('editorImageInput').click()" title="Sisipkan Gambar">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                </button>
                                <input type="file" id="editorImageInput" accept="image/*" hidden onchange="insertImageFile(this)">
                                
                                <button type="button" class="rich-editor-btn" onclick="fmt('insertUnorderedList')" title="Bullet List">•</button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('insertOrderedList')" title="Numbered List">1.</button>
                                <button type="button" class="rich-editor-btn" onclick="insertTable()" title="Sisipkan Tabel"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px;"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="12" y1="3" x2="12" y2="21"/></svg></button>
                                
                                <!-- Clear Format (X) -->
                                <button type="button" class="rich-editor-btn" onclick="fmt('removeFormat')" title="Clear Format">✕</button>

                                <!-- Line Height Dropdown Trigger (TI Icon) -->
                                <div class="rte-popover-container" style="position:relative; display:inline-block;">
                                    <button type="button" class="rich-editor-btn" onclick="toggleLineHeightDropdown()" title="Line Height" style="display:inline-flex; align-items:center; gap:2px; font-weight:bold;">
                                        <span>T<span style="font-size:10px;">I</span></span>
                                        <span style="font-size:9px;">▼</span>
                                    </button>
                                    <div class="rte-dropdown-menu" id="rteLineHeightDropdown">
                                        <div class="rte-dropdown-item" data-value="1.2" onclick="setLineHeight('1.2')"><span>1.2</span><span class="check-icon" style="display:none;">✓</span></div>
                                        <div class="rte-dropdown-item active" data-value="1.4" onclick="setLineHeight('1.4')"><span>1.4</span><span class="check-icon" style="display:inline;">✓</span></div>
                                        <div class="rte-dropdown-item" data-value="1.5" onclick="setLineHeight('1.5')"><span>1.5</span><span class="check-icon" style="display:none;">✓</span></div>
                                        <div class="rte-dropdown-item" data-value="1.6" onclick="setLineHeight('1.6')"><span>1.6</span><span class="check-icon" style="display:none;">✓</span></div>
                                        <div class="rte-dropdown-item" data-value="1.8" onclick="setLineHeight('1.8')"><span>1.8</span><span class="check-icon" style="display:none;">✓</span></div>
                                        <div class="rte-dropdown-item" data-value="2.0" onclick="setLineHeight('2.0')"><span>2.0</span><span class="check-icon" style="display:none;">✓</span></div>
                                        <div class="rte-dropdown-item" data-value="3.0" onclick="setLineHeight('3.0')"><span>3.0</span><span class="check-icon" style="display:none;">✓</span></div>
                                    </div>
                                </div>

                                <div class="toolbar-divider"></div>
                                <button type="button" class="rich-editor-btn" onclick="fmt('justifyLeft')" title="Kiri"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg></button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('justifyCenter')" title="Tengah"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="10" x2="6" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="18" y1="18" x2="6" y2="18"/></svg></button>
                                <button type="button" class="rich-editor-btn" onclick="fmt('justifyRight')" title="Kanan"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="10" x2="7" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="7" y2="18"/></svg></button>
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
                                <button type="button" class="btn btn-outline" onclick="document.getElementById('imageInput').click()" style="display:inline-flex;align-items:center;gap:6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg> Unggah Gambar Cover</button>
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
                        </div> <!-- End of gallery-grid-row -->
                        <input type="file" id="imageInput" name="image" accept="image/*" hidden>
                        <input type="file" id="galleryInput" name="gallery[]" accept="image/*" multiple hidden>

                        </div> <!-- End of pane-isi -->

                        <!-- PANE TEMPAT -->
                        <div id="pane-tempat" style="display:none; padding:16px; border:1px solid #ced4da; border-radius:4px; margin-bottom:24px;">
                            <div class="horizontal-group">
                                <label>Lokasi / Tempat</label>
                                <div class="input-wrapper">
                                    <input type="text" name="tempat" class="form-input" value="<?= e($news['tempat']) ?>" placeholder="Masukkan lokasi kejadian">
                                </div>
                            </div>
                            <div style="margin-top:16px; width:100%; height:250px; background:#e9ecef; border-radius:4px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#6c757d;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:8px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span>Preview Peta Lokasi (Placeholder)</span>
                            </div>
                        </div> <!-- End of pane-tempat -->

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

                            <?php
                            include_once __DIR__ . '/includes/lanud_list.php';
                            $selectedWilayah = $_POST['wilayah'] ?? $news['wilayah'];
                            echo render_lanud_select('wilayah', $selectedWilayah, 'class="sidebar-select"');
                            ?>

                            <label class="sidebar-label">Sumber Media</label>
                            <select name="media" class="sidebar-select">
                                <option value="Wilayah" <?= $news['media']==='Wilayah'?'selected':'' ?>>Berita Wilayah</option>
                                <option value="Media Online" <?= $news['media']==='Media Online'?'selected':'' ?>>Media Online</option>
                                <option value="Media Sosial" <?= $news['media']==='Media Sosial'?'selected':'' ?>>Media Sosial</option>
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

                            <button type="submit" name="update_news" value="1" class="btn-save-blue" onclick="prepareSubmit()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                Simpan
                            </button>

                            <?php if ($isAuthorA && $news['status'] === 'draft'): ?>
                                <button type="submit" name="resubmit_after_edit" value="1" class="btn-save-blue btn-selesai" style="background: linear-gradient(135deg, #27ae60, #1e8449); margin-top:8px; width:100%; justify-content:center; box-shadow: 0 4px 15px rgba(39,174,96,0.35);" onclick="prepareSubmit()">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    Selesai &amp; Kirim ke Editor
                                </button>
                            <?php endif; ?>

                            <?php if ($isAuthorA && in_array($news['status'], ['revision_b', 'revision_c', 'revision_d'])): ?>
                                <?php
                                $targetRoleName = match($news['status']) {
                                    'revision_b' => 'Editor',
                                    'revision_c' => 'Penyetuju (Approver)',
                                    'revision_d' => 'Peninjau Kejelasan',
                                    default => 'Reviewer'
                                };
                                ?>
                                <button type="submit" name="resubmit_after_edit" value="1" class="btn-save-blue" style="background:#27ae60; margin-top:8px;" onclick="prepareSubmit()">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    Simpan &amp; Selesai Revisi (Kirim ke <?= $targetRoleName ?>)
                                </button>
                            <?php endif; ?>
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

function removeFontStyle() {
    document.execCommand('removeFormat', false, null);
    const sel = window.getSelection();
    if (sel.rangeCount > 0) {
        let node = sel.getRangeAt(0).commonAncestorContainer;
        if (node.nodeType === 3) node = node.parentNode;
        while (node && node.id !== 'editorBody') {
            if (node.style) {
                node.style.fontFamily = '';
                node.style.color = '';
                node.style.backgroundColor = '';
                node.style.lineHeight = '';
            }
            node = node.parentNode;
        }
    }
}

let activeColorTab = 'bg';
function toggleColorPickerModal() {
    const popover = document.getElementById('rteColorPopover');
    if (!popover) return;
    const isVisible = popover.classList.contains('active');
    closeAllRtePopovers();
    if (!isVisible) {
        popover.classList.add('active');
    }
}

function switchColorTab(tabName) {
    activeColorTab = tabName;
    document.querySelectorAll('#rteColorPopover .rte-color-tab').forEach(t => t.classList.remove('active'));
    if (tabName === 'bg') {
        document.getElementById('tabBgBtn').classList.add('active');
        document.getElementById('actionBtnBg').style.display = 'block';
        document.getElementById('actionBtnText').style.display = 'none';
    } else {
        document.getElementById('tabTextBtn').classList.add('active');
        document.getElementById('actionBtnBg').style.display = 'none';
        document.getElementById('actionBtnText').style.display = 'block';
    }
}

function selectPresetColor(hex) {
    if (activeColorTab === 'bg') {
        document.execCommand('hiliteColor', false, hex);
    } else {
        document.execCommand('foreColor', false, hex);
    }
    closeAllRtePopovers();
}

function applyColorAction(type) {
    if (type === 'transparent') {
        document.execCommand('hiliteColor', false, 'transparent');
    } else if (type === 'resetText') {
        document.execCommand('foreColor', false, 'inherit');
    }
    closeAllRtePopovers();
}

function triggerCustomColorPicker() {
    const inp = document.getElementById('rteCustomColorInput');
    if (inp) inp.click();
}

function applyCustomColor(hex) {
    selectPresetColor(hex);
}

function toggleLineHeightDropdown() {
    const dropdown = document.getElementById('rteLineHeightDropdown');
    if (!dropdown) return;
    const isVisible = dropdown.classList.contains('active');
    closeAllRtePopovers();
    if (!isVisible) {
        dropdown.classList.add('active');
    }
}

function setLineHeight(lh) {
    const sel = window.getSelection();
    if (sel.rangeCount > 0) {
        let node = sel.getRangeAt(0).commonAncestorContainer;
        if (node.nodeType === 3) node = node.parentNode;
        while (node && node.id !== 'editorBody' && !['P','DIV','H1','H2','H3','H4','LI'].includes(node.tagName)) {
            node = node.parentNode;
        }
        if (node && node.id !== 'editorBody') {
            node.style.lineHeight = lh;
        } else {
            document.execCommand('formatBlock', false, 'p');
            const newSel = window.getSelection();
            let parentP = newSel.getRangeAt(0).commonAncestorContainer;
            if (parentP.nodeType === 3) parentP = parentP.parentNode;
            if (parentP && parentP.id !== 'editorBody') parentP.style.lineHeight = lh;
        }
    }
    
    document.querySelectorAll('#rteLineHeightDropdown .rte-dropdown-item').forEach(item => {
        const val = item.getAttribute('data-value');
        if (val === String(lh)) {
            item.classList.add('active');
            item.querySelector('.check-icon').style.display = 'inline';
        } else {
            item.classList.remove('active');
            item.querySelector('.check-icon').style.display = 'none';
        }
    });

    closeAllRtePopovers();
}

function closeAllRtePopovers() {
    document.querySelectorAll('.rte-popover, .rte-dropdown-menu').forEach(el => el.classList.remove('active'));
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.rte-popover-container')) {
        closeAllRtePopovers();
    }
});

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
<script src="<?= BASE_URL ?>/assets/js/tabs.js"></script>
<script>
function switchInnerTab(tab) {
    document.querySelectorAll('.content-tabs .content-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-btn-' + tab).classList.add('active');
    
    if (tab === 'isi') {
        document.getElementById('pane-isi').style.display = 'block';
        document.getElementById('pane-tempat').style.display = 'none';
    } else {
        document.getElementById('pane-isi').style.display = 'none';
        document.getElementById('pane-tempat').style.display = 'display:flex';
        document.getElementById('pane-tempat').style.display = 'block';
    }
}
</script>
</body>
</html>
