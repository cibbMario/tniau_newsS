<?php
require_once __DIR__ . '/config/config.php';
requireRole(['A','E']);
$current = 'create';
$user = currentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        header("Location: " . BASE_URL . "/news_list.php");
        exit;
    }

    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $action   = $_POST['action'] ?? 'draft';
    $wilayah  = trim($_POST['wilayah'] ?? 'Lanud Atang Sendjaja');
    $media    = trim($_POST['media'] ?? 'Wilayah');
    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
    $aktor    = trim($_POST['aktor'] ?? '');
    $tag      = trim($_POST['tag'] ?? '');
    $topik    = trim($_POST['topik'] ?? '');
    $keyword  = trim($_POST['keyword'] ?? '');
    $sentiment      = $_POST['sentiment'] ?? 'Positif';
    $priority       = $_POST['priority'] ?? 'Medium';
    $classification = trim($_POST['classification'] ?? '9. Tni au');
    $tempat         = trim($_POST['tempat'] ?? '');

    if (!$title) {
        $error = 'Judul berita wajib diisi.';
    } elseif (!$content) {
        $error = 'Isi berita wajib diisi.';
    } else {
        try {
            $imagePath = uploadNewsImage('image');
            $slug = generateSlug($title);
            $status = ($action === 'submit') ? 'pending_b' : 'draft';
            $author_label = getLanudInitials($wilayah);

            $stmt = $pdo->prepare(" 
                INSERT INTO news (
                    title, slug, content, image_path, status, sentiment, priority,
                    classification, wilayah, tempat, media, aktor, tag, topik, keyword, author_label,
                    created_by, created_at, published_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, NOW(), ?
                )
            ");
            $stmt->execute([
                $title, $slug, $content, $imagePath, $status, $sentiment, $priority,
                $classification, $wilayah, $tempat, $media, $aktor, $tag, $topik, $keyword, $author_label,
                $user['id'], $published_at
            ]);
            $newsId = $pdo->lastInsertId();

            // Upload gambar tambahan
            if (!empty($_FILES['gallery']['name'][0])) {
                foreach ($_FILES['gallery']['tmp_name'] as $i => $tmpName) {
                    if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['gallery']['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                            $fname = 'gallery_' . uniqid() . '.' . $ext;
                            move_uploaded_file($tmpName, UPLOAD_DIR . $fname);
                            $pdo->prepare("INSERT INTO news_images (news_id, image_path) VALUES (?, ?)")
                                ->execute([$newsId, $fname]);
                        }
                    }
                }
            }

            updateNewsStatus($newsId, $status, $user['id'], 'Berita baru dibuat');

            if ($action === 'submit') {
                $editors = $pdo->query("SELECT id FROM users WHERE role = 'B'")->fetchAll();
                foreach ($editors as $ed) {
                    sendNotification($newsId, $ed['id'], "Berita baru: \"$title\" menunggu review Anda.");
                }
            }

            header("Location: " . BASE_URL . "/news_view.php?id=" . $newsId);
            exit;
        } catch (Exception $ex) {
            $error = $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Berita Baru Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="top-navbar">
            <div class="top-navbar-left">
                <button class="hamburger-btn" title="Toggle Menu" aria-label="Toggle menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="media-tabs">
                    <span class="media-tab-item active">Buat Berita Baru</span>
                </div>
            </div>
            <div class="top-navbar-right">
                <a href="<?= BASE_URL ?>/news_list.php" class="top-action-btn" style="display:inline-flex;align-items:center;gap:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Kembali
                </a>
                <span class="top-action-btn">Tanggal <?= date('d M Y') ?></span>
            </div>
        </div>

        <div class="workspace-tabs-row">
            <a href="<?= BASE_URL ?>/news_list.php" class="workspace-tab">Daftar Berita</a>
            <div class="workspace-tab active"><span>Buat Berita Baru</span></div>
        </div>

        <div class="page-container">
            <div class="create-wrapper">
                <h2 class="create-heading">Buat Berita Baru</h2>

                <?php if ($error): ?>
                    <div style="background:#fceae8;border:1px solid rgba(192,57,43,.15);color:#c0392b;padding:12px 16px;border-radius:6px;font-size:13px;font-weight:500;margin-bottom:16px">
                        Peringatan: <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="createForm" class="create-card">
                    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                    <div class="create-layout-grid">
                        <div class="create-main">
                            <div class="form-group">
                                <label for="title">Judul Berita <span style="color:#c0392b">*</span></label>
                                <input type="text" id="title" name="title" class="form-input" placeholder="Masukkan judul berita..." required value="<?= e($_POST['title'] ?? '') ?>" style="font-size:15px;font-weight:600;padding:12px 14px">
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                                <div class="form-group">
                                    <label for="wilayah">Wilayah & Satuan</label>
                                    <?php
                                    include_once __DIR__ . '/includes/lanud_list.php';
                                    $selectedWilayah = $_POST['wilayah'] ?? 'Lanud Atang Sendjaja';
                                    echo render_lanud_select('wilayah', $selectedWilayah, 'id="wilayah" class="form-input"');
                                    ?>
                                </div>
                                <div class="form-group">
                                    <label for="media">Sumber Media</label>
                                    <select id="media" name="media" class="form-input">
                                        <option value="Wilayah" <?= ($_POST['media'] ?? '') === 'Wilayah' ? 'selected' : '' ?>>Berita Wilayah</option>
                                        <option value="Media Online" <?= ($_POST['media'] ?? '') === 'Media Online' ? 'selected' : '' ?>>Media Online</option>
                                        <option value="Media Sosial" <?= ($_POST['media'] ?? '') === 'Media Sosial' ? 'selected' : '' ?>>Media Sosial</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="published_at">Waktu Terbit (Opsional)</label>
                                    <input type="datetime-local" id="published_at" name="published_at" class="form-input" value="<?= e($_POST['published_at'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Gambar Utama Berita</label>
                                <div class="upload-dropzone" onclick="document.getElementById('imageInput').click()">
                                    <span class="plus-icon">Upload</span>
                                    <span id="fileLabel">Klik untuk memilih gambar (JPG, PNG, WEBP, maks 5MB)</span>
                                    <input type="file" id="imageInput" name="image" accept="image/*" hidden onchange="document.getElementById('fileLabel').textContent = this.files[0]?.name || 'Pilih gambar...'">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Gambar Pendukung (Galeri)</label>
                                <div class="upload-dropzone" onclick="document.getElementById('galleryInput').click()">
                                    <span class="plus-icon">Gambar</span>
                                    <span id="galleryLabel">Pilih beberapa gambar sekaligus (opsional)</span>
                                    <input type="file" id="galleryInput" name="gallery[]" accept="image/*" multiple hidden onchange="document.getElementById('galleryLabel').textContent = this.files.length + ' gambar dipilih'">
                                </div>
                            </div>

                            <div class="form-group">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <label for="content" style="margin:0;">Isi Berita <span style="color:#c0392b">*</span></label>
                                    <div id="autoSaveIndicator" style="font-size:11px; color:#64748b; display:flex; align-items:center; gap:6px;">
                                        <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#10b981;"></span>
                                        <span>Draft tersimpan otomatis di browser</span>
                                        <button type="button" id="restoreDraftBtn" style="display:none; background:none; border:none; color:#2563eb; text-decoration:underline; font-size:11px; cursor:pointer;" onclick="restoreLocalDraft()">Pulihkan Draft</button>
                                    </div>
                                </div>
                                <div class="editor-wrap" id="mainEditorWrap">
                                    <div class="editor-toolbar" style="position:relative; flex-wrap:wrap; gap:4px;">
                                        <!-- Formatting Basics -->
                                        <button type="button" class="editor-btn" onclick="fmt('bold')" title="Bold"><strong>B</strong></button>
                                        <button type="button" class="editor-btn" onclick="fmt('italic')" title="Italic"><em>I</em></button>
                                        <button type="button" class="editor-btn" onclick="fmt('underline')" title="Underline"><u>U</u></button>
                                        
                                        <!-- Eraser / Remove Font Style (CTRL+\) -->
                                        <button type="button" class="editor-btn" onclick="removeFontStyle()" title="Remove Font Style (CTRL+\)">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 21-4-4 8-8 4 4-8 8Z"/><path d="M11 7 15 3l6 6-4 4"/><path d="M2 21h18"/></svg>
                                        </button>

                                        <div style="width:1px;height:20px;background:#ced4da;margin:0 2px;"></div>

                                        <!-- Font Family Dropdown -->
                                        <select class="editor-select" id="createFontFamily" onchange="applyFont(this.value)" title="Font Family">
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
                                        <select class="editor-select" onchange="fmt('fontSize', this.value)" title="Font Size">
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
                                            <button type="button" class="editor-btn" onclick="toggleColorPickerModal()" title="Color Picker (Text & Background)" style="font-weight:bold; display:inline-flex; align-items:center; gap:3px;">
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

                                        <div style="width:1px;height:20px;background:#ced4da;margin:0 2px;"></div>

                                        <!-- Link, Lists, Alignments -->
                                        <button type="button" class="editor-btn" onclick="insertLinkCreate()" title="Link"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg></button>
                                        <button type="button" class="editor-btn" onclick="fmt('insertUnorderedList')" title="Bullet List">•</button>
                                        <button type="button" class="editor-btn" onclick="fmt('insertOrderedList')" title="Numbered List">1.</button>
                                        <button type="button" class="editor-btn" onclick="fmt('justifyLeft')" title="Kiri"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg></button>
                                        <button type="button" class="editor-btn" onclick="fmt('justifyCenter')" title="Tengah"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="10" x2="6" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="18" y1="18" x2="6" y2="18"/></svg></button>
                                        <button type="button" class="editor-btn" onclick="fmt('justifyRight')" title="Kanan"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="10" x2="7" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="7" y2="18"/></svg></button>
                                        
                                        <!-- Clear Format (X) -->
                                        <button type="button" class="editor-btn" onclick="fmt('removeFormat')" title="Clear Format">✕</button>

                                        <!-- Line Height Dropdown Trigger (TI Icon) -->
                                        <div class="rte-popover-container" style="position:relative; display:inline-block;">
                                            <button type="button" class="editor-btn" onclick="toggleLineHeightDropdown()" title="Line Height" style="display:inline-flex; align-items:center; gap:2px; font-weight:bold;">
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

                                        <div style="margin-left:auto;">
                                            <button type="button" class="editor-btn" onclick="toggleFullscreenEditor()" title="Mode Layar Penuh"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg> Layar Penuh</button>
                                        </div>
                                    </div>
                                    <div class="editor-body" id="editorBody" contenteditable="true" data-placeholder="Tulis isi berita di sini..." oninput="updateStatsAndAutoSave()"><?= e($_POST['content'] ?? '') ?></div>
                                    <!-- Stats Bar -->
                                    <div style="padding:6px 14px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; font-size:12px; color:#64748b;">
                                        <div style="display:flex; gap:16px;">
                                            <span><strong>Kata:</strong> <span id="wordCount">0</span></span>
                                            <span><strong>Karakter:</strong> <span id="charCount">0</span></span>
                                            <span><strong>Waktu Baca:</strong> ~<span id="readTime">0</span> mnt</span>
                                        </div>
                                        <div>
                                            <a href="<?= BASE_URL ?>/media_library.php" target="_blank" style="color:#2563eb; text-decoration:none; font-size:11px; display:inline-flex; align-items:center; gap:4px;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg> Buka Media Library (DAM)</a>
                                        </div>
                                    </div>
                                </div>
                                <textarea name="content" id="hiddenContent" hidden></textarea>
                            </div>

                            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid #e2e6ea">
                                <button type="submit" name="action" value="draft" class="btn btn-outline" onclick="prepareSubmit()">
                                    Simpan sebagai Draft
                                </button>
                                <button type="submit" name="action" value="submit" class="btn btn-primary" onclick="prepareSubmit()">
                                    Ajukan untuk Review
                                </button>
                            </div>
                        </div>

                        <aside class="create-sidebar">
                            <div class="sidebar-card">
                                <div class="sidebar-card-title">Detail Berita</div>
                                <div class="form-group">
                                    <label>Sentimen</label>
                                    <select name="sentiment" class="form-input">
                                        <option value="Positif" <?= (($_POST['sentiment'] ?? 'Positif') === 'Positif') ? 'selected' : '' ?>>Positif</option>
                                        <option value="Negatif" <?= (($_POST['sentiment'] ?? '') === 'Negatif') ? 'selected' : '' ?>>Negatif</option>
                                        <option value="Netral" <?= (($_POST['sentiment'] ?? '') === 'Netral') ? 'selected' : '' ?>>Netral</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Prioritas</label>
                                    <select name="priority" class="form-input">
                                        <option value="High" <?= (($_POST['priority'] ?? '') === 'High') ? 'selected' : '' ?>>High</option>
                                        <option value="Medium" <?= (($_POST['priority'] ?? 'Medium') === 'Medium') ? 'selected' : '' ?>>Medium</option>
                                        <option value="Low" <?= (($_POST['priority'] ?? '') === 'Low') ? 'selected' : '' ?>>Low</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Klasifikasi</label>
                                    <select name="classification" class="form-input">
                                        <option value="9. Tni au" <?= (($_POST['classification'] ?? '9. Tni au') === '9. Tni au') ? 'selected' : '' ?>>Tni au</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tempat</label>
                                    <input type="text" name="tempat" class="form-input" value="<?= e($_POST['tempat'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="sidebar-card">
                                <div class="sidebar-card-title">Meta Tambahan</div>
                                <div class="form-group">
                                    <label>Aktor</label>
                                    <input type="text" name="aktor" class="form-input" value="<?= e($_POST['aktor'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Tag</label>
                                    <input type="text" name="tag" class="form-input" value="<?= e($_POST['tag'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Topik</label>
                                    <input type="text" name="topik" class="form-input" value="<?= e($_POST['topik'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Keyword</label>
                                    <input type="text" name="keyword" class="form-input" value="<?= e($_POST['keyword'] ?? '') ?>">
                                </div>
                            </div>
                        </aside>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function fmt(cmd, val) {
    document.getElementById('editorBody').focus();
    document.execCommand(cmd, false, val !== undefined ? val : null);
    updateStatsAndAutoSave();
}
function applyFont(font) {
    document.getElementById('editorBody').focus();
    document.execCommand('fontName', false, font);
    updateStatsAndAutoSave();
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
    updateStatsAndAutoSave();
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
    updateStatsAndAutoSave();
}

function applyColorAction(type) {
    if (type === 'transparent') {
        document.execCommand('hiliteColor', false, 'transparent');
    } else if (type === 'resetText') {
        document.execCommand('foreColor', false, 'inherit');
    }
    closeAllRtePopovers();
    updateStatsAndAutoSave();
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
    updateStatsAndAutoSave();
}

function closeAllRtePopovers() {
    document.querySelectorAll('.rte-popover, .rte-dropdown-menu').forEach(el => el.classList.remove('active'));
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.rte-popover-container')) {
        closeAllRtePopovers();
    }
});

function insertLinkCreate() {
    var url = prompt('Masukkan URL link:', 'https://');
    if (url) {
        document.getElementById('editorBody').focus();
        document.execCommand('createLink', false, url);
        updateStatsAndAutoSave();
    }
}
function insertTableCreate() {
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
    updateStatsAndAutoSave();
}
function toggleFullscreenEditor() {
    var wrap = document.getElementById('mainEditorWrap');
    wrap.classList.toggle('editor-fullscreen');
    if (wrap.classList.contains('editor-fullscreen')) {
        wrap.style.position = 'fixed';
        wrap.style.inset = '0';
        wrap.style.zIndex = '99999';
        wrap.style.background = '#fff';
        wrap.style.height = '100vh';
        wrap.style.display = 'flex';
        wrap.style.flexDirection = 'column';
        document.getElementById('editorBody').style.flex = '1';
        document.getElementById('editorBody').style.maxHeight = 'none';
    } else {
        wrap.removeAttribute('style');
        document.getElementById('editorBody').removeAttribute('style');
    }
}

// Auto-Save & Word Counting
var saveTimeout = null;
function updateStatsAndAutoSave() {
    var body = document.getElementById('editorBody');
    var text = body.innerText || '';
    var trimmed = text.trim();
    var words = trimmed ? trimmed.split(/\s+/).length : 0;
    var chars = text.length;
    var readTime = Math.ceil(words / 180);

    document.getElementById('wordCount').textContent = words;
    document.getElementById('charCount').textContent = chars;
    document.getElementById('readTime').textContent = readTime;

    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(function() {
        var title = document.getElementById('title')?.value || '';
        var html = body.innerHTML;
        if (title || html) {
            localStorage.setItem('tniau_draft_create_title', title);
            localStorage.setItem('tniau_draft_create_content', html);
            localStorage.setItem('tniau_draft_create_time', new Date().toLocaleTimeString());
            document.getElementById('autoSaveIndicator').querySelector('span:nth-child(2)').textContent = 'Tersimpan otomatis (' + new Date().toLocaleTimeString() + ')';
        }
    }, 1000);
}

function restoreLocalDraft() {
    var title = localStorage.getItem('tniau_draft_create_title');
    var content = localStorage.getItem('tniau_draft_create_content');
    if (title && document.getElementById('title')) document.getElementById('title').value = title;
    if (content && document.getElementById('editorBody')) document.getElementById('editorBody').innerHTML = content;
    document.getElementById('restoreDraftBtn').style.display = 'none';
    updateStatsAndAutoSave();
}

window.addEventListener('DOMContentLoaded', function() {
    updateStatsAndAutoSave();
    var savedContent = localStorage.getItem('tniau_draft_create_content');
    var bodyContent = document.getElementById('editorBody').innerHTML.trim();
    if (savedContent && !bodyContent) {
        var restoreBtn = document.getElementById('restoreDraftBtn');
        if (restoreBtn) restoreBtn.style.display = 'inline';
    }
});

function prepareSubmit() {
    document.getElementById('hiddenContent').value = document.getElementById('editorBody').innerHTML;
    localStorage.removeItem('tniau_draft_create_title');
    localStorage.removeItem('tniau_draft_create_content');
}
document.getElementById('createForm').addEventListener('submit', function() {
    prepareSubmit();
});
</script>
</body>
</html>
