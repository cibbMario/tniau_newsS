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
    <title>Buat Berita Baru — Portal Berita TNI AU</title>
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
                                    <label for="wilayah">Wilayah / Satuan</label>
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
                                    <div class="editor-toolbar">
                                        <button type="button" class="editor-btn" onclick="fmt('bold')" title="Bold"><strong>B</strong></button>
                                        <button type="button" class="editor-btn" onclick="fmt('italic')" title="Italic"><em>I</em></button>
                                        <button type="button" class="editor-btn" onclick="fmt('underline')" title="Underline"><u>U</u></button>
                                        <button type="button" class="editor-btn" onclick="fmt('strikeThrough')" title="Strikethrough"><s>S</s></button>
                                        <div style="width:1px;height:20px;background:#ced4da;margin:0 4px;"></div>
                                        <select class="editor-select" id="createFontFamily" onchange="applyFont(this.value)" title="Font">
                                            <option value="Poppins">Poppins</option>
                                            <option value="Arial">Arial</option>
                                            <option value="Times New Roman">Times New Roman</option>
                                            <option value="Verdana">Verdana</option>
                                            <option value="Georgia">Georgia</option>
                                            <option value="Courier New">Courier New</option>
                                            <option value="Trebuchet MS">Trebuchet MS</option>
                                            <option value="Tahoma">Tahoma</option>
                                            <option value="Impact">Impact</option>
                                        </select>
                                        <select class="editor-select" onchange="fmt('fontSize', this.value)" title="Ukuran">
                                            <option value="1">8pt</option>
                                            <option value="2">10pt</option>
                                            <option value="3" selected>12pt</option>
                                            <option value="4">14pt</option>
                                            <option value="5">18pt</option>
                                            <option value="6">24pt</option>
                                            <option value="7">36pt</option>
                                        </select>
                                        <div style="width:1px;height:20px;background:#ced4da;margin:0 4px;"></div>
                                        <input type="color" title="Warna Teks" style="width:26px;height:26px;border:1px solid #ced4da;border-radius:4px;padding:2px;cursor:pointer" onchange="fmt('foreColor', this.value)">
                                        <input type="color" title="Highlight Warna Latar" value="#ffffff" style="width:26px;height:26px;border:1px solid #ced4da;border-radius:4px;padding:2px;cursor:pointer" onchange="fmt('hiliteColor', this.value)">
                                        <div style="width:1px;height:20px;background:#ced4da;margin:0 4px;"></div>
                                        <button type="button" class="editor-btn" onclick="fmt('insertUnorderedList')" title="Bullet List">• List</button>
                                        <button type="button" class="editor-btn" onclick="fmt('insertOrderedList')" title="Numbered List">1. List</button>
                                        <button type="button" class="editor-btn" onclick="fmt('formatBlock','h2')" title="Heading 2">H2</button>
                                        <button type="button" class="editor-btn" onclick="fmt('formatBlock','h3')" title="Heading 3">H3</button>
                                        <button type="button" class="editor-btn" onclick="fmt('formatBlock','blockquote')" title="Quote">❝</button>
                                        <div style="width:1px;height:20px;background:#ced4da;margin:0 4px;"></div>
                                        <button type="button" class="editor-btn" onclick="insertLinkCreate()" title="Link">🔗 Link</button>
                                        <button type="button" class="editor-btn" onclick="insertTableCreate()" title="Tabel">⊞ Tabel</button>
                                        <button type="button" class="editor-btn" onclick="fmt('removeFormat')" title="Hapus Format">🧹 Bersihkan</button>
                                        <div style="width:1px;height:20px;background:#ced4da;margin:0 4px;"></div>
                                        <button type="button" class="editor-btn" onclick="fmt('justifyLeft')" title="Kiri">⬅</button>
                                        <button type="button" class="editor-btn" onclick="fmt('justifyCenter')" title="Tengah">≡</button>
                                        <button type="button" class="editor-btn" onclick="fmt('justifyRight')" title="Kanan">➡</button>
                                        <button type="button" class="editor-btn" onclick="fmt('justifyFull')" title="Rata Kanan Kiri">↔</button>
                                        <div style="margin-left:auto;">
                                            <button type="button" class="editor-btn" onclick="toggleFullscreenEditor()" title="Mode Layar Penuh">⛶ Layar Penuh</button>
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
                                            <a href="<?= BASE_URL ?>/media_library.php" target="_blank" style="color:#2563eb; text-decoration:none; font-size:11px;">🖼️ Buka Media Library (DAM)</a>
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
