<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();
$current = 'military_dictionary';

$error = '';
$success = '';

// Handle add term
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_term'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header("Location: " . BASE_URL . "/military_dictionary.php");
        exit;
    }

    $term = trim($_POST['term'] ?? '');
    $expansion = trim($_POST['expansion'] ?? '');
    $category = trim($_POST['category'] ?? 'Satuan & Komando');
    $correct_standard = trim($_POST['correct_standard'] ?? '');
    $is_outdated = !empty($_POST['is_outdated']) ? 1 : 0;
    $notes = trim($_POST['notes'] ?? '');

    if (!$term || !$expansion) {
        $error = 'Istilah/singkatan dan kepanjangan wajib diisi.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO military_dictionary (term, expansion, category, correct_standard, is_outdated, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$term, $expansion, $category, $correct_standard ?: $term, $is_outdated, $notes, $user['id']]);
        $success = "Istilah \"$term\" berhasil ditambahkan ke Kamus Militer.";
    }
}

// Handle delete term (Admin E only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_term']) && $user['role'] === 'E') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $termId = (int)($_POST['term_id'] ?? 0);
        $pdo->prepare("DELETE FROM military_dictionary WHERE id = ?")->execute([$termId]);
        $success = "Istilah berhasil dihapus dari Kamus Militer.";
    }
}

// Filters
$categoryFilter = trim($_GET['cat'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');
$outdatedOnly = !empty($_GET['outdated']);

$sql = "SELECT * FROM military_dictionary WHERE 1=1";
$params = [];

if ($categoryFilter && $categoryFilter !== 'Semua') {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

if ($outdatedOnly) {
    $sql .= " AND is_outdated = 1";
}

if ($searchQuery) {
    $sql .= " AND (term LIKE ? OR expansion LIKE ? OR correct_standard LIKE ? OR notes LIKE ?)";
    $like = "%$searchQuery%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY is_outdated DESC, category ASC, term ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$terms = $stmt->fetchAll();

// Counts
$totalTerms    = (int)$pdo->query("SELECT COUNT(*) FROM military_dictionary")->fetchColumn();
$countPangkat  = (int)$pdo->query("SELECT COUNT(*) FROM military_dictionary WHERE category='Pangkat Militer'")->fetchColumn();
$countJabatan  = (int)$pdo->query("SELECT COUNT(*) FROM military_dictionary WHERE category='Jabatan & Pimpinan'")->fetchColumn();
$countSatuan   = (int)$pdo->query("SELECT COUNT(*) FROM military_dictionary WHERE category='Satuan & Komando'")->fetchColumn();
$countPedoman  = (int)$pdo->query("SELECT COUNT(*) FROM military_dictionary WHERE category='Istilah & Pedoman'")->fetchColumn();
$countOutdated = (int)$pdo->query("SELECT COUNT(*) FROM military_dictionary WHERE is_outdated=1")->fetchColumn();

$categories = [
    'Semua' => $totalTerms,
    'Pangkat Militer' => $countPangkat,
    'Jabatan & Pimpinan' => $countJabatan,
    'Satuan & Komando' => $countSatuan,
    'Istilah & Pedoman' => $countPedoman
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kamus & Korektor Singkatan Militer TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .dict-hero {
            background: linear-gradient(135deg, #065f46 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 24px 28px;
            color: #ffffff;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 10px 25px -5px rgba(6, 95, 70, 0.2);
        }
        .dict-hero-title {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dict-hero-desc {
            font-size: 13px;
            color: #cbd5e1;
            max-width: 650px;
            line-height: 1.5;
        }
        .checker-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .checker-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .checker-textarea {
            width: 100%;
            height: 90px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            margin-bottom: 10px;
        }
        .checker-textarea:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }
        .dict-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }
        .dict-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            transition: transform 0.15s, border-color 0.15s;
        }
        .dict-card:hover {
            transform: translateY(-2px);
            border-color: #cbd5e1;
        }
        .dict-card.outdated {
            border-left: 4px solid #f59e0b;
            background: #fffdfa;
        }
        .dict-card.standard {
            border-left: 4px solid #10b981;
        }
        .dict-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
        }
        .dict-term {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }
        .dict-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        .dict-badge.valid { background: #d1fae5; color: #065f46; }
        .dict-badge.warn  { background: #fef3c7; color: #92400e; }
        .dict-expansion {
            font-size: 13.5px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .dict-standard-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 12px;
            color: #065f46;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dict-standard-box.correction {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .dict-notes {
            font-size: 12px;
            color: #64748b;
            line-height: 1.45;
            margin-top: auto;
            border-top: 1px dashed #f1f5f9;
            padding-top: 8px;
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
            background: #065f46;
            color: #ffffff;
            border-color: #065f46;
            box-shadow: 0 4px 12px rgba(6, 95, 70, 0.25);
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
            <div class="dict-hero">
                <div>
                    <div class="dict-hero-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6ee7b7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                        Kamus & Korektor Singkatan Militer TNI AU
                    </div>
                    <div class="dict-hero-desc">
                        Panduan standardisasi singkatan pangkat, jabatan komando, satuan, dan istilah kedinasan TNI Angkatan Udara. Dilengkapi pendeteksi istilah lama (seperti Paskhas, KSAU, Koopsau) dan saran perbaikan resmi.
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" onclick="openAddModal()" style="background:#059669;border-color:#059669;display:inline-flex;align-items:center;gap:6px;padding:10px 18px;font-size:13px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Tambah Istilah Baru
                    </button>
                </div>
            </div>

            <!-- INTERACTIVE QUICK TEXT SCANNER / CHECKER -->
            <div class="checker-box">
                <div class="checker-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Uji Pindai & Koreksi Teks Siaran Pers
                </div>
                <p style="font-size:12.5px; color:#64748b; margin-bottom:10px;">
                    Tempelkan paragraf berita Anda di bawah untuk menguji apakah ada penulisan istilah atau singkatan militer yang tidak baku/usang:
                </p>
                <textarea id="testScannerInput" class="checker-textarea" placeholder="Contoh: KSAU meninjau latihan prajurit Paskhas dan alutsista Skadud di bawah jajaran Koopsau..."></textarea>
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <button type="button" class="btn btn-primary" onclick="runTextScan()" style="background:#059669; border-color:#059669; font-size:12.5px; padding:7px 16px;">
                        🔍 Pindai & Analisis Teks
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="clearTextScan()">Bersihkan</button>
                </div>

                <div id="scanResultArea" style="display:none; margin-top:14px; padding-top:14px; border-top:1px solid #e2e8f0;"></div>
            </div>

            <!-- SEARCH & CATEGORY FILTER -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                <div style="display:flex; gap:8px; overflow-x:auto; padding-bottom:6px; flex-wrap:wrap;">
                    <?php foreach ($categories as $catName => $catCount): 
                        $isActive = ($categoryFilter === $catName) || (!$categoryFilter && $catName === 'Semua');
                    ?>
                        <a href="<?= BASE_URL ?>/military_dictionary.php?cat=<?= urlencode($catName) ?><?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>" 
                           class="cat-pill <?= $isActive ? 'active' : '' ?>">
                            <?= e($catName) ?>
                            <span class="count"><?= $catCount ?></span>
                        </a>
                    <?php endforeach; ?>

                    <a href="<?= BASE_URL ?>/military_dictionary.php?outdated=1<?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>" 
                       class="cat-pill <?= $outdatedOnly ? 'active' : '' ?>" style="<?= $outdatedOnly ? 'background:#d97706;border-color:#d97706;' : 'color:#b45309;border-color:#fde68a;' ?>">
                        ⚠️ Perlu Koreksi
                        <span class="count"><?= $countOutdated ?></span>
                    </a>
                </div>

                <!-- Search box -->
                <form method="GET" action="<?= BASE_URL ?>/military_dictionary.php" style="display:flex; gap:8px; min-width:260px;">
                    <?php if ($categoryFilter): ?>
                        <input type="hidden" name="cat" value="<?= e($categoryFilter) ?>">
                    <?php endif; ?>
                    <?php if ($outdatedOnly): ?>
                        <input type="hidden" name="outdated" value="1">
                    <?php endif; ?>
                    <input type="text" name="q" class="form-input" placeholder="Cari singkatan, kepanjangan..." value="<?= e($searchQuery) ?>" style="font-size:13px; padding:7px 12px;">
                    <button type="submit" class="btn btn-outline btn-sm">Cari</button>
                    <?php if ($searchQuery || $outdatedOnly): ?>
                        <a href="<?= BASE_URL ?>/military_dictionary.php" class="btn btn-outline btn-sm" title="Reset">✕</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- DICTIONARY ITEMS GRID -->
            <?php if (empty($terms)): ?>
                <div class="card empty-state" style="padding:40px; text-align:center;">
                    <div style="font-size:15px; font-weight:600; color:#334155; margin-bottom:4px;">Tidak ada istilah militer yang cocok</div>
                    <div style="font-size:13px; color:#64748b;">Silakan ubah kata kunci pencarian Anda.</div>
                </div>
            <?php else: ?>
                <div class="dict-grid">
                    <?php foreach ($terms as $t): ?>
                        <div class="dict-card <?= $t['is_outdated'] ? 'outdated' : 'standard' ?>">
                            <div class="dict-header">
                                <div>
                                    <span class="dict-term"><?= e($t['term']) ?></span>
                                    <span style="font-size:11px; color:#64748b; margin-left:6px;">(<?= e($t['category']) ?>)</span>
                                </div>
                                <?php if ($t['is_outdated']): ?>
                                    <span class="dict-badge warn">Istilah Lama</span>
                                <?php else: ?>
                                    <span class="dict-badge valid">Baku Resmi</span>
                                <?php endif; ?>
                            </div>

                            <div class="dict-expansion"><?= e($t['expansion']) ?></div>

                            <?php if ($t['is_outdated']): ?>
                                <div class="dict-standard-box correction">
                                    <span>⚠️ Koreksi Resmi:</span>
                                    <strong><?= e($t['correct_standard']) ?></strong>
                                </div>
                            <?php else: ?>
                                <div class="dict-standard-box">
                                    <span>✓ Format Standar:</span>
                                    <strong><?= e($t['correct_standard'] ?: $t['term']) ?></strong>
                                </div>
                            <?php endif; ?>

                            <?php if ($t['notes']): ?>
                                <div class="dict-notes">
                                    <?= e($t['notes']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<!-- MODAL TAMBAH ISTILAH -->
<div id="addTermModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); z-index:9999; backdrop-filter:blur(4px); align-items:center; justify-content:center;">
    <div class="modal-box" style="background:#ffffff; border-radius:14px; max-width:500px; width:90%; padding:24px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="font-size:16px; font-weight:700; color:#0f172a; margin:0;">Tambah Istilah / Singkatan Militer</h3>
            <button type="button" onclick="closeAddModal()" style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b;">✕</button>
        </div>

        <form method="POST" style="display:flex; flex-direction:column; gap:12px;">
            <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
            <input type="hidden" name="add_term" value="1">

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">Istilah / Singkatan <span style="color:#ef4444">*</span></label>
                <input type="text" name="term" class="form-input" placeholder="Contoh: KSAU atau Danlanud" required>
            </div>

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">Kepanjangan / Arti Lengkap <span style="color:#ef4444">*</span></label>
                <input type="text" name="expansion" class="form-input" placeholder="Contoh: Kepala Staf Angkatan Udara" required>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div class="form-group">
                    <label style="font-size:12px; font-weight:600; color:#334155;">Kategori</label>
                    <select name="category" class="form-input">
                        <option value="Satuan & Komando">Satuan & Komando</option>
                        <option value="Jabatan & Pimpinan">Jabatan & Pimpinan</option>
                        <option value="Pangkat Militer">Pangkat Militer</option>
                        <option value="Istilah & Pedoman">Istilah & Pedoman</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-size:12px; font-weight:600; color:#334155;">Bentuk Baku / Koreksi</label>
                    <input type="text" name="correct_standard" class="form-input" placeholder="Contoh: Kasau">
                </div>
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="outdatedCheck" name="is_outdated" value="1">
                <label for="outdatedCheck" style="font-size:13px; color:#334155; margin:0; cursor:pointer;">Tandai sebagai istilah <strong>usang/lama</strong> yang perlu dikoreksi</label>
            </div>

            <div class="form-group">
                <label style="font-size:12px; font-weight:600; color:#334155;">Catatan / Dasar Aturan (Opsional)</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Contoh: Sesuai Kep Panglima TNI No Kep/66/I/2022..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
                <button type="button" class="btn btn-outline" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary" style="background:#059669; border-color:#059669;">Simpan Istilah</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addTermModal').style.display = 'flex';
}
function closeAddModal() {
    document.getElementById('addTermModal').style.display = 'none';
}

// Client-side text scanning using dictionary data
const dictData = <?= json_encode($terms, JSON_UNESCAPED_UNICODE) ?>;

function runTextScan() {
    const text = document.getElementById('testScannerInput').value.trim();
    const resultArea = document.getElementById('scanResultArea');
    if (!text) {
        alert('Silakan masukkan teks berita yang ingin dipindai.');
        return;
    }

    let foundIssues = [];

    dictData.forEach(item => {
        // Create regex matching whole word or standard boundaries
        const regex = new RegExp('\\b' + item.term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'gi');
        if (regex.test(text)) {
            foundIssues.push(item);
        }
    });

    resultArea.style.display = 'block';

    if (foundIssues.length === 0) {
        resultArea.innerHTML = `
            <div style="background:#d1fae5; border:1px solid #6ee7b7; color:#065f46; padding:12px 16px; border-radius:8px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                <span style="font-size:18px;">✓</span>
                Bagus! Tidak ditemukan istilah militer yang salah atau usang dalam teks tersebut.
            </div>
        `;
    } else {
        let html = `
            <div style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:10px;">
                Ditemukan <strong>${foundIssues.length}</strong> istilah militer terdeteksi dalam teks:
            </div>
            <div style="display:flex; flex-direction:column; gap:8px;">
        `;

        foundIssues.forEach(item => {
            if (item.is_outdated == 1) {
                html += `
                    <div style="background:#fffbeb; border:1px solid #fde68a; padding:10px 14px; border-radius:8px; font-size:12.5px; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                        <div>
                            <span style="color:#b45309; font-weight:700;">⚠️ ${item.term}</span> (Istilah Lama) 
                            <span style="color:#64748b;">➔ Gunakan resmi:</span> <strong style="color:#059669; font-weight:700;">${item.correct_standard}</strong>
                            <div style="font-size:11px; color:#78350f; margin-top:2px;">${item.notes || item.expansion}</div>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="applyFixToTest('${item.term}', '${item.correct_standard}')" style="white-space:nowrap; border-color:#d97706; color:#b45309;">
                            Terapkan Koreksi
                        </button>
                    </div>
                `;
            } else {
                html += `
                    <div style="background:#ecfdf5; border:1px solid #a7f3d0; padding:10px 14px; border-radius:8px; font-size:12.5px;">
                        <span style="color:#065f46; font-weight:700;">✓ ${item.term}</span>: ${item.expansion} (${item.category})
                    </div>
                `;
            }
        });

        html += `</div>`;
        resultArea.innerHTML = html;
    }
}

function applyFixToTest(from, to) {
    const input = document.getElementById('testScannerInput');
    const regex = new RegExp('\\b' + from.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'gi');
    input.value = input.value.replace(regex, to);
    runTextScan();
}

function clearTextScan() {
    document.getElementById('testScannerInput').value = '';
    document.getElementById('scanResultArea').style.display = 'none';
}

window.addEventListener('click', function(e) {
    const addM = document.getElementById('addTermModal');
    if (e.target === addM) closeAddModal();
});
</script>
</body>
</html>
