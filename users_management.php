<?php
require_once __DIR__ . '/config/config.php';
requireLogin();
$user = currentUser();

// Hanya Role E (Admin) atau C (Petinggi) yang dapat mengakses
if (!in_array($user['role'], ['E', 'C'])) {
    die("Akses ditolak. Halaman ini hanya untuk Administrator atau Pimpinan.");
}

$current = 'users_management';
$error = '';
$success = '';

// Handle Actions: Add, Edit, Toggle Status, Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        header("Location: " . BASE_URL . "/users_management.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $username  = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password  = trim($_POST['password'] ?? '');
        $role      = $_POST['role'] ?? 'A';
        $lanud     = trim($_POST['lanud'] ?? 'Lanud Atang Sendjaja');

        if (!$username || !$full_name || !$password) {
            $error = 'Semua field wajib diisi.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter.';
        } else {
            // Check username uniqueness
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = 'Username sudah digunakan oleh akun lain.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, full_name, password_hash, role, lanud, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$username, $full_name, $hash, $role, $lanud]);
                $newId = $pdo->lastInsertId();
                logAudit('CREATE_USER', "Membuat pengguna baru '$username' ($full_name) role $role", $user['id']);
                $success = "Pengguna <strong>" . e($full_name) . "</strong> berhasil ditambahkan.";
            }
        }
    } elseif ($action === 'edit_user') {
        $userId    = (int)($_POST['user_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $role      = $_POST['role'] ?? 'A';
        $lanud     = trim($_POST['lanud'] ?? 'Lanud Atang Sendjaja');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($userId <= 0 || !$full_name) {
            $error = 'Data pengguna tidak valid.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, lanud = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$full_name, $role, $lanud, $is_active, $userId]);
            logAudit('UPDATE_USER', "Memperbarui profil pengguna ID #$userId ($full_name)", $user['id']);
            $success = "Data pengguna berhasil diperbarui.";
        }
    } elseif ($action === 'reset_password') {
        $userId      = (int)($_POST['user_id'] ?? 0);
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($userId <= 0 || strlen($newPassword) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            logAudit('RESET_PASSWORD', "Mereset password pengguna ID #$userId", $user['id']);
            $success = "Password pengguna berhasil direset.";
        }
    } elseif ($action === 'toggle_status') {
        $userId    = (int)($_POST['user_id'] ?? 0);
        $newStatus = (int)($_POST['status'] ?? 0);
        if ($userId > 0) {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            logAudit('TOGGLE_USER_STATUS', "Mengubah status aktif pengguna ID #$userId menjadi $newStatus", $user['id']);
            $success = "Status pengguna berhasil diperbarui.";
        }
    }
}

// Fetch users with counts
$search = trim($_GET['q'] ?? '');
$filterRole = $_GET['role'] ?? '';
$filterLanud = $_GET['lanud'] ?? '';

$sql = "SELECT u.*, 
        COUNT(n.id) as total_news,
        SUM(CASE WHEN n.status = 'published' THEN 1 ELSE 0 END) as published_news
        FROM users u
        LEFT JOIN news n ON n.created_by = u.id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterRole) {
    $sql .= " AND u.role = ?";
    $params[] = $filterRole;
}
if ($filterLanud) {
    $sql .= " AND u.lanud = ?";
    $params[] = $filterLanud;
}

$sql .= " GROUP BY u.id ORDER BY u.role ASC, u.full_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

include_once __DIR__ . '/includes/lanud_list.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna & Hierarki Satuan Portal Berita TNI AU</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
        .badge-active { background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-inactive { background: #f8d7da; color: #721c24; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .role-pill { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .role-A { background: #e3f2fd; color: #0d47a1; }
        .role-B { background: #fff3e0; color: #e65100; }
        .role-C { background: #e8f5e9; color: #1b5e20; }
        .role-D { background: #f3e5f5; color: #4a148c; }
        .role-E { background: #fce4ec; color: #880e4f; }
        .action-btns { display: flex; gap: 6px; }
        .modal-form { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .modal-form.active { display: flex; }
        .modal-card { background: #fff; border-radius: 8px; max-width: 500px; width: 90%; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
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
                    <h1 class="page-title" style="margin:0; font-size:22px; color:#1a202c;">Manajemen Pengguna & Hierarki Satuan</h1>
                    <p style="margin:4px 0 0; color:#718096; font-size:13px;">Kelola hak akses jajaran Dispenau, Reporter Lanud, Editor, Petinggi, dan Peninjau.</p>
                </div>
                <div style="display:flex; gap:10px;">
                    <a href="<?= BASE_URL ?>/audit_log.php" class="btn btn-outline">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        Lihat Audit Trail
                    </a>
                    <button type="button" class="btn btn-primary" onclick="openAddUserModal()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Tambah Pengguna Baru    
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
            <div class="card" style="margin-bottom:20px; padding:16px;">
                <form method="GET" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 120px; gap:12px; align-items:end;">
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Cari Nama / Username</label>
                        <input type="text" name="q" class="form-input" placeholder="Ketik nama pengguna..." value="<?= e($search) ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Peran / Role</label>
                        <select name="role" class="form-input">
                            <option value="">-- Semua Role --</option>
                            <option value="A" <?= $filterRole==='A'?'selected':'' ?>>User A (Reporter)</option>
                            <option value="B" <?= $filterRole==='B'?'selected':'' ?>>User B (Editor)</option>
                            <option value="C" <?= $filterRole==='C'?'selected':'' ?>>User C (Petinggi/Approver)</option>
                            <option value="D" <?= $filterRole==='D'?'selected':'' ?>>User D (Peninjau Kejelasan)</option>
                            <option value="E" <?= $filterRole==='E'?'selected':'' ?>>User E (Administrator)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:12px; margin-bottom:4px;">Asal Lanud / Satuan</label>
                        <?= render_lanud_select('lanud', $filterLanud, 'class="form-input"') ?>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Satuan / Lanud</th>
                                <th>Produktivitas</th>
                                <th>Status</th>
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usersList)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:30px; color:#a0aec0;">Tidak ada data pengguna yang cocok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usersList as $u): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:#2d3748;"><?= e($u['full_name']) ?></div>
                                            <div style="font-size:11px; color:#a0aec0;">ID #<?= $u['id'] ?> &bull; Dibuat <?= date('d M Y', strtotime($u['created_at'])) ?></div>
                                        </td>
                                        <td><code><?= e($u['username']) ?></code></td>
                                        <td>
                                            <span class="role-pill role-<?= $u['role'] ?>">
                                                <?= userDisplayName($u['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size:13px; color:#4a5568;"><?= e($u['lanud'] ?? 'Mabes TNI AU') ?></span>
                                        </td>
                                        <td>
                                            <span style="font-size:12px; color:#4a5568;">
                                                <strong><?= (int)$u['total_news'] ?></strong> Berita (<strong><?= (int)$u['published_news'] ?></strong> Terbit)
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ((int)($u['is_active'] ?? 1) === 1): ?>
                                                <span class="badge-active">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge-inactive">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <div class="action-btns" style="justify-content:center;">
                                                <button type="button" class="btn btn-outline" style="padding:4px 8px; font-size:11px;" 
                                                    onclick='openEditUserModal(<?= json_encode($u) ?>)'>
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-outline" style="padding:4px 8px; font-size:11px;" 
                                                    onclick="openResetModal(<?= $u['id'] ?>, '<?= e($u['username']) ?>')">
                                                    Reset Pass
                                                </button>
                                                <?php if ($u['id'] !== $user['id']): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Ubah status pengguna ini?')">
                                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <input type="hidden" name="status" value="<?= ((int)($u['is_active'] ?? 1) === 1) ? 0 : 1 ?>">
                                                        <button type="submit" class="btn btn-outline" style="padding:4px 8px; font-size:11px; color:<?= ((int)($u['is_active'] ?? 1) === 1) ? '#e53e3e' : '#38a169' ?>;">
                                                            <?= ((int)($u['is_active'] ?? 1) === 1) ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- MODAL ADD USER -->
<div id="addUserModal" class="modal-form">
    <div class="modal-card">
        <h3 style="margin-top:0; margin-bottom:16px;">Tambah Pengguna Baru</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="create_user">
            
            <div class="form-group">
                <label>Nama Lengkap & Gelar / Pangkat</label>
                <input type="text" name="full_name" class="form-input" required placeholder="Contoh: Letda Sus Mario (Reporter Lanud ATS)">
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-input" required placeholder="Contoh: mario_ats">
            </div>
            <div class="form-group">
                <label>Password Awal</label>
                <input type="password" name="password" class="form-input" required minlength="6" placeholder="Minimal 6 karakter">
            </div>
            <div class="form-group">
                <label>Hak Akses / Peran</label>
                <select name="role" class="form-input" required>
                    <option value="A">User A Reporter (Pembuat Berita)</option>
                    <option value="B">User B Editor (Pemeriksa Tingkat 1)</option>
                    <option value="C">User C Approver / Petinggi (Persetujuan Terbit)</option>
                    <option value="D">User D Peninjau Kejelasan</option>
                    <option value="E">User E Administrator Sistem</option>
                </select>
            </div>
            <div class="form-group">
                <label>Satuan / Lanud Asal</label>
                <?= render_lanud_select('lanud', 'Lanud Atang Sendjaja', 'class="form-input"') ?>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT USER -->
<div id="editUserModal" class="modal-form">
    <div class="modal-card">
        <h3 style="margin-top:0; margin-bottom:16px;">Edit Data Pengguna</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Hak Akses / Peran</label>
                <select name="role" id="edit_role" class="form-input" required>
                    <option value="A">User A Reporter (Pembuat Berita)</option>
                    <option value="B">User B Editor (Pemeriksa Tingkat 1)</option>
                    <option value="C">User C Approver / Petinggi (Persetujuan Terbit)</option>
                    <option value="D">User D Peninjau Kejelasan</option>
                    <option value="E">User E Administrator Sistem</option>
                </select>
            </div>
            <div class="form-group">
                <label>Satuan / Lanud Asal</label>
                <?= render_lanud_select('lanud', '', 'id="edit_lanud" class="form-input"') ?>
            </div>
            <div class="form-group" style="display:flex; align-items:center; gap:8px; margin-top:10px;">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                <label for="edit_is_active" style="margin:0; cursor:pointer;">Akun Aktif (Dapat Login)</label>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Perbarui Data</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL RESET PASSWORD -->
<div id="resetPassModal" class="modal-form">
    <div class="modal-card">
        <h3 style="margin-top:0; margin-bottom:16px;">Reset Password Pengguna</h3>
        <p style="font-size:13px; color:#4a5568;">Reset password untuk username: <strong id="reset_username_label"></strong></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset_user_id">
            
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" class="form-input" required minlength="6" placeholder="Masukkan password baru">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('resetPassModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Password Baru</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').classList.add('active');
}
function openEditUserModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_lanud').value = user.lanud || 'Lanud Atang Sendjaja';
    document.getElementById('edit_is_active').checked = parseInt(user.is_active || 1) === 1;
    document.getElementById('editUserModal').classList.add('active');
}
function openResetModal(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username_label').textContent = username;
    document.getElementById('resetPassModal').classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
</script>
</body>
</html>
